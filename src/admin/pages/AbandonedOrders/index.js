/**
 * Abandoned Orders page — data via GET /wcal/v1/abandoned-carts.
 *
 * Tabs: All | Registered Users | Guest | Carts without Customer Details
 * Filters: date range, status
 * Table: Id, Email, Customer Details, Cart Total, Abandoned Date/Time, Status
 * Bulk actions: delete (via REST POST /wcal/v1/abandoned-carts/bulk)
 *
 * Recovered carts are listed here too, through the Recovered status filter.
 * Everything only the Pro plugin can do stays visible, locked with a PRO badge.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Spinner }                          from '@wordpress/components';
import { __ }                               from '@wordpress/i18n';
import apiFetch                             from '@wordpress/api-fetch';
import { useSearchParams }                  from 'react-router-dom';

import { Pagination }                      from '../../components/Pagination';
import { ProBadge }                        from '../../components/ProLockedRow';
import { formatAbandonedDate, formatWallClock, siteDaysAgo } from '../../utils/date';
import './abandoned-orders.scss';

// ── Constants ─────────────────────────────────────────────────────────────────

const DURATION_DAYS = {
	last_7_days:   7,
	last_15_days:  15,
	last_30_days:  30,
	last_90_days:  90,
	last_365_days: 365,
};

const DURATION_OPTIONS = [
	{ value: 'yesterday',     label: __( 'Yesterday',   'woocommerce-abandoned-cart' ) },
	{ value: 'today',         label: __( 'Today',       'woocommerce-abandoned-cart' ) },
	{ value: 'last_7_days',   label: __( 'Last 7 days', 'woocommerce-abandoned-cart' ) },
	{ value: 'last_15_days',  label: __( 'Last 15 days','woocommerce-abandoned-cart' ) },
	{ value: 'last_30_days',  label: __( 'Last 30 days','woocommerce-abandoned-cart' ) },
	{ value: 'last_90_days',  label: __( 'Last 90 days','woocommerce-abandoned-cart' ) },
	{ value: 'last_365_days', label: __( 'Last 365',    'woocommerce-abandoned-cart' ) },
	{ value: 'custom',        label: __( 'Custom Range', 'woocommerce-abandoned-cart' ) },
];

const STATUS_OPTIONS = [
	{ value: 'all',       label: __( 'All Statuses',               'woocommerce-abandoned-cart' ) },
	{ value: 'abandoned', label: __( 'Abandoned',                  'woocommerce-abandoned-cart' ) },
	{ value: 'recovered', label: __( 'Recovered',                  'woocommerce-abandoned-cart' ) },
	{ value: 'unpaid',    label: __( 'Abandoned - Pending Payment (PRO)', 'woocommerce-abandoned-cart' ), disabled: true },
	{ value: 'received',  label: __( 'Abandoned - Order Received (PRO)',  'woocommerce-abandoned-cart' ), disabled: true },
	{ value: 'cancelled', label: __( 'Abandoned - Order Cancelled (PRO)', 'woocommerce-abandoned-cart' ), disabled: true },
];

const BULK_OPTIONS = [
	{ value: '',                  label: __( 'Bulk Action',                      'woocommerce-abandoned-cart' ) },
	{ value: 'delete',            label: __( 'Delete',                           'woocommerce-abandoned-cart' ) },
	{ value: 'delete_registered', label: __( 'Delete All Registered User Carts', 'woocommerce-abandoned-cart' ), global: true },
	{ value: 'delete_guest',      label: __( 'Delete All Guest User Carts',      'woocommerce-abandoned-cart' ), global: true },
	{ value: 'delete_visitor',    label: __( 'Delete All Visitor Carts',         'woocommerce-abandoned-cart' ), global: true },
	{ value: 'delete_all',        label: __( 'Delete All Carts',                 'woocommerce-abandoned-cart' ), global: true },
	{ value: 'send_custom_email', label: __( 'Send Custom Email (PRO)',          'woocommerce-abandoned-cart' ), disabled: true },
	{ value: 'sync',              label: __( 'Sync Manually (PRO)',              'woocommerce-abandoned-cart' ), disabled: true },
	{ value: 'trash',             label: __( 'Move to Trash (PRO)',              'woocommerce-abandoned-cart' ), disabled: true },
];

const STATUS_CONFIG = {
	abandoned:     { label: __( 'Abandoned',                      'woocommerce-abandoned-cart' ), cls: 'abandoned'    },
	abandoned_new: { label: __( 'Abandoned but new cart created', 'woocommerce-abandoned-cart' ), cls: 'abandoned'    },
	recovered:     { label: __( 'Recovered',                      'woocommerce-abandoned-cart' ), cls: 'recovered'    },
	unsubscribed:  { label: __( 'Unsubscribed',                   'woocommerce-abandoned-cart' ), cls: 'unsubscribed' },
};

const rowStatus = ( row ) => {
	if ( row.status !== 'recovered' && row.unsubscribed ) {
		return STATUS_CONFIG.unsubscribed;
	}
	return STATUS_CONFIG[ row.status ] ?? STATUS_CONFIG.abandoned;
};

const Html = ( { html } ) => <span dangerouslySetInnerHTML={ { __html: html } } />;

/**
 * Lets a row action be reached and fired from the keyboard, as the links were on the old list table.
 *
 * @param {Function} onActivate Runs on click, Enter or Space.
 * @return {Object} Props for the action element.
 */
const actionProps = ( onActivate ) => ( {
	role: 'button',
	tabIndex: 0,
	onClick: onActivate,
	onKeyDown: ( e ) => {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			onActivate();
		}
	},
} );

/**
 * A control only the Pro plugin has. The link leads to the trial page.
 */
export const LockedAction = ( { label, className = '' } ) => (
	<a
		className={ `wcal-locked-action ${ className }` }
		href={ window.wcalAdminData?.trialUrl }
		target="_blank"
		rel="noopener noreferrer"
	>
		{ label } <span className="wcal-pro-badge">{ __( 'PRO', 'woocommerce-abandoned-cart' ) }</span>
	</a>
);

/**
 * The order a recovered cart was linked to.
 */
const OrderLink = ( { recovered } ) => {
	const label = `${ __( 'Order', 'woocommerce-abandoned-cart' ) } #${ recovered.order_id }`;
	return recovered.order_url ? <a href={ recovered.order_url }>{ label }</a> : label;
};

// ── Cart detail modal (REST) ──────────────────────────────────────────────────

export function CartDetailModal( { cartId, onClose, hideFooter = false } ) {
	const [ cart,        setCart        ] = useState( null );
	const [ loading,     setLoading     ] = useState( true );
	const [ error,       setError       ] = useState( '' );
	const [ showDetails, setShowDetails ] = useState( false );

	useEffect( () => {
		apiFetch( { path: `/wcal/v1/abandoned-carts/${ cartId }` } )
			.then( ( res ) => setCart( res.data ?? res ) )
			.catch( () => setError( __( 'Failed to load cart details.', 'woocommerce-abandoned-cart' ) ) )
			.finally( () => setLoading( false ) );
	}, [ cartId ] );

	// Helper: render address block, only non-empty fields.
	function renderAddress( addr ) {
		if ( ! addr ) return null;
		const fields = [
			addr.company,
			addr.address_1,
			addr.address_2,
			[ addr.city, addr.state, addr.postcode ].filter( Boolean ).join( ', ' ),
			addr.country,
			addr.phone ? `${ __( 'Phone:', 'woocommerce-abandoned-cart' ) } ${ addr.phone }` : '',
		].filter( Boolean );
		if ( fields.length === 0 ) return null;
		return (
			<address style={ { fontStyle: 'normal', lineHeight: '1.6' } }>
				{ fields.map( ( line, i ) => <span key={ i }>{ line }<br /></span> ) }
			</address>
		);
	}

	// Shipping address block: check if all blank.
	function renderShippingAddress( shipping ) {
		return renderAddress( shipping ) ?? <p>{ __( 'Shipping Address same as Billing Address', 'woocommerce-abandoned-cart' ) }</p>;
	}

	const notifications = cart?.reminder_activity?.notifications ?? [];
	const pStyle        = { textAlign: 'center', margin: '0' };

	return (
		<div className="wcap-cd-overlay">
			<div className="wcap-cd-modal">

				{ /* ── Header ── */ }
				<div className="wcap-cd-modal__header">
					<span>
						{ __( 'Cart #', 'woocommerce-abandoned-cart' ) }{ cartId }
						{ cart && (
							<span className="wcap-cd-modal__status">{ '  ' }{ cart.status_label }</span>
						) }
					</span>
					<button className="wcap-cd-modal__close" onClick={ onClose } aria-label={ __( 'Close', 'woocommerce-abandoned-cart' ) }>
						✕
					</button>
				</div>

				{ /* ── Body ── */ }
				<div className="wcap-cd-modal__body">
					{ loading && <div className="wcap-ao-spinner"><Spinner /></div> }
					{ error   && <p style={ { color: 'red' } }>{ error }</p> }

					{ cart && (
						<>
							{ cart.recovered && (
								<p style={ { margin: '0 0 12px' } }>
									<OrderLink recovered={ cart.recovered } />
									{ cart.recovered.date && <> — { __( 'Recovered on', 'woocommerce-abandoned-cart' ) } { cart.recovered.date }</> }
								</p>
							) }

							{ /* Summary table */ }
							<table className="wcap-cd-summary">
								<thead>
									<tr>
										<th>{ __( 'Email Address',    'woocommerce-abandoned-cart' ) }</th>
										<th>{ __( 'Customer Details', 'woocommerce-abandoned-cart' ) }</th>
										<th>{ __( 'Order Total',      'woocommerce-abandoned-cart' ) }</th>
										<th>{ __( 'Abandoned Date',   'woocommerce-abandoned-cart' ) }</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>{ cart.email_id || '—' }</td>
										<td>
											<div>
												{ [ cart.billing?.first_name, cart.billing?.last_name ].filter( Boolean ).join( ' ' ) || '—' }
											</div>
											{ cart.billing?.phone && (
												<div>{ cart.billing.phone }</div>
											) }
											{ cart.user_role && (
												<div>{ cart.user_role.charAt( 0 ).toUpperCase() + cart.user_role.slice( 1 ).toLowerCase() }</div>
											) }
											<button
												className="wcap-cd-toggle"
												onClick={ () => setShowDetails( ( v ) => ! v ) }
											>
												{ showDetails
													? __( 'Hide Details', 'woocommerce-abandoned-cart' )
													: __( 'Show Details', 'woocommerce-abandoned-cart' ) }
											</button>
											{ showDetails && (
												<div className="wcap-cd-extra">
													<p>
														<strong>{ __( 'Shipping Charges:', 'woocommerce-abandoned-cart' ) }</strong>{ ' ' }
														<Html html={ cart.totals?.shipping_formatted ?? '' } />
													</p>
													{ ( () => {
														const billingBlock = renderAddress( cart.billing );
														return billingBlock ? (
															<div>
																<strong>{ __( 'Billing Address:', 'woocommerce-abandoned-cart' ) }</strong>
																{ billingBlock }
															</div>
														) : null;
													} )() }
													<div>
														<strong>{ __( 'Shipping Address:', 'woocommerce-abandoned-cart' ) }</strong>
														{ renderShippingAddress( cart.shipping ) }
													</div>
												</div>
											) }
										</td>
										<td>
											<Html html={ cart.totals?.formatted ?? '' } />
											{ cart.products?.length > 0 && ( () => {
												const totalQty = cart.products.reduce( ( sum, p ) => sum + p.quantity, 0 );
												return <><br /><span>{ totalQty } { totalQty !== 1 ? __( 'items', 'woocommerce-abandoned-cart' ) : __( 'item', 'woocommerce-abandoned-cart' ) }</span></>;
											} )() }
										</td>
										<td>{ formatAbandonedDate( cart.abandoned_cart_time_iso ) }</td>
									</tr>
								</tbody>
							</table>

							{ /* Products table */ }
							{ cart.products?.length > 0 && (
								<table className="wcap-cd-products">
									<thead>
										<tr>
											<th>{ __( 'Item',          'woocommerce-abandoned-cart' ) }</th>
											<th>{ __( 'Item Cost',     'woocommerce-abandoned-cart' ) }</th>
											<th>{ __( 'Item Quantity', 'woocommerce-abandoned-cart' ) }</th>
										</tr>
									</thead>
									<tbody>
										{ cart.products.map( ( p, i ) => (
											<tr key={ i }>
												<td>
													<div className="wcap-cd-product">
														{ p.image_url && (
															<img src={ p.image_url } alt="" className="wcap-cd-product__thumb" />
														) }
														<div>
															{ p.exists ? (
																<a
																	className="wcap-cd-product__name"
																	href={ `${ window.wcalAdminData?.adminUrl ?? '/wp-admin/' }post.php?post=${ p.product_id }&action=edit` }
																	target="_blank"
																	rel="noreferrer"
																>
																	<Html html={ p.name } />
																</a>
															) : <Html html={ p.name } /> }
														</div>
													</div>
												</td>
												<td><Html html={ p.formatted } /></td>
												<td>{ p.quantity } { p.quantity !== 1 ? __( 'items', 'woocommerce-abandoned-cart' ) : __( 'item', 'woocommerce-abandoned-cart' ) }</td>
											</tr>
										) ) }
									</tbody>
								</table>
							) }

							{ /* Reminders Sent section */ }
							{ notifications.length > 0 && (
								<>
									<hr style={ { borderColor: '#ddd', margin: '16px 0' } } />
									{ notifications.map( ( n, i ) => (
										<div key={ i }>
											{ i > 0 && <hr style={ { borderColor: '#ddd', margin: '16px 0' } } /> }
											<p style={ pStyle }>
												{ __( 'Email template', 'woocommerce-abandoned-cart' ) } <strong>{ n.template || '—' }</strong> { __( 'was sent to', 'woocommerce-abandoned-cart' ) } <strong>{ n.contact }</strong> { __( 'on', 'woocommerce-abandoned-cart' ) } { formatWallClock( n.sent_at ) }
											</p>
										</div>
									) ) }
								</>
							) }
						</>
					) }
				</div>

				{ /* ── Footer ── */ }
				{ ! hideFooter && (
					<div className="wcap-cd-modal__footer">
						{ cart && cart.status !== 'recovered' && ! cart.unsubscribed && (
							<>
								<LockedAction label={ __( 'Send Custom Email', 'woocommerce-abandoned-cart' ) } className="wcap-ao-btn wcap-ao-btn--outline wcap-cd-modal__footer-btn" />
								<LockedAction label={ __( 'Unsubscribe', 'woocommerce-abandoned-cart' ) } className="wcap-ao-btn wcap-ao-btn--outline wcap-cd-modal__footer-btn" />
							</>
						) }
						<button
							className="wcap-ao-btn wcap-ao-btn--outline wcap-cd-modal__footer-btn"
							onClick={ onClose }
						>
							{ __( 'Close', 'woocommerce-abandoned-cart' ) }
						</button>
					</div>
				) }

			</div>
		</div>
	);
}

// ── Orders table ──────────────────────────────────────────────────────────────

const TABLE_HEADERS = [
	__( 'Id',                  'woocommerce-abandoned-cart' ),
	__( 'Email Address',       'woocommerce-abandoned-cart' ),
	__( 'Customer Details',    'woocommerce-abandoned-cart' ),
	__( 'Cart Total',          'woocommerce-abandoned-cart' ),
	__( 'Abandoned Date/ Time','woocommerce-abandoned-cart' ),
	__( 'Coupon Used/ Status', 'woocommerce-abandoned-cart' ),
	__( 'Captured By',         'woocommerce-abandoned-cart' ),
	__( 'Cart Status',         'woocommerce-abandoned-cart' ),
];

const LOCKED_COLUMNS = [ 5, 6 ];

function TableHeaderRow() {
	return (
		<tr className="wcap-ao-table__foot-row">
			<th className="wcap-ao-table__check"></th>
			{ TABLE_HEADERS.map( ( h, i ) => (
				<th key={ h }>{ h }{ LOCKED_COLUMNS.includes( i ) && <> <ProBadge /></> }</th>
			) ) }
		</tr>
	);
}

function OrdersTable( { rows, selected, onToggleRow, onToggleAll, onView, onDelete } ) {
	const allChecked = rows.length > 0 && rows.every( ( r ) => selected.has( r.id ) );

	return (
		<div className="wcap-ao-table-wrap">
		<table className="wcap-ao-table">
			<thead>
				<tr>
					<th className="wcap-ao-table__check">
						<input type="checkbox" checked={ allChecked } onChange={ () => onToggleAll( rows, allChecked ) } />
					</th>
					{ TABLE_HEADERS.map( ( h, i ) => (
						<th key={ h }>{ h }{ LOCKED_COLUMNS.includes( i ) && <> <ProBadge /></> }</th>
					) ) }
				</tr>
			</thead>
			<tbody>
				{ rows.length === 0 && (
					<tr>
						<td colSpan={ 9 } className="wcap-ao-table__empty">
							{ __( 'No abandoned orders found.', 'woocommerce-abandoned-cart' ) }
						</td>
					</tr>
				) }
				{ rows.map( ( row ) => {
					const statusCfg = rowStatus( row );
					const name      = [ row.billing?.first_name, row.billing?.last_name ].filter( Boolean ).join( ' ' ) || '—';

					return (
						<tr key={ row.id }>
							<td className="wcap-ao-table__check">
								<input
									type="checkbox"
									checked={ selected.has( row.id ) }
									onChange={ () => onToggleRow( row.id ) }
								/>
							</td>
							<td>{ row.id }</td>
							<td>
								<div className="wcap-ao-table__email-line">
									<span className="wcap-ao-table__link" { ...actionProps( () => onView( row ) ) }>
										{ row.email_id || '' }
									</span>
								</div>
								<div className="wcap-ao-table__actions">
									<span className="wcap-ao-table__action-link" { ...actionProps( () => onView( row ) ) }>
										{ __( 'View', 'woocommerce-abandoned-cart' ) }
									</span>
									{ row.status !== 'recovered' && (
										<>
											{ ' | ' }
											<LockedAction label={ __( 'Mark As Recovered', 'woocommerce-abandoned-cart' ) } />
											{ ! row.unsubscribed && (
												<>
													{ ' | ' }
													<LockedAction label={ __( 'Unsubscribe', 'woocommerce-abandoned-cart' ) } />
													{ ' | ' }
													<LockedAction label={ __( 'Send Custom Email', 'woocommerce-abandoned-cart' ) } />
												</>
											) }
										</>
									) }
									{ ' | ' }
									<span className="wcap-ao-table__action-link wcap-ao-table__action-link--trash" { ...actionProps( () => onDelete( row.id ) ) }>
										{ __( 'Delete', 'woocommerce-abandoned-cart' ) }
									</span>
								</div>
							</td>
							<td>
								{ name }
								{ row.billing?.phone && (
									<><br />{ row.billing.phone }</>
								) }
							</td>
							<td>
								<Html html={ row.totals?.formatted ?? '' } />
								{ row.item_count > 0 && (
									<><br /><span className="wcap-ao-table__sub">{ row.item_count } { row.item_count !== 1 ? __( 'items', 'woocommerce-abandoned-cart' ) : __( 'item', 'woocommerce-abandoned-cart' ) }</span></>
								) }
							</td>
							<td>{ formatAbandonedDate( row.abandoned_cart_time_iso ) }</td>
							<td></td>
							<td></td>
							<td>
								<span className={ `wcap-ao-status wcap-ao-status--${ statusCfg.cls }` }>
									{ statusCfg.label }
								</span>
								{ row.recovered && (
									<div className="wcal-ao-recovered">
										<OrderLink recovered={ row.recovered } />
										{ row.recovered.date && <><br />{ __( 'Recovered on', 'woocommerce-abandoned-cart' ) } { row.recovered.date }</> }
										{ row.recovered.order_total && <><br />{ __( 'Order Total:', 'woocommerce-abandoned-cart' ) } <Html html={ row.recovered.order_total } /></> }
									</div>
								) }
							</td>
						</tr>
					);
				} ) }
			</tbody>
			<tfoot>
				<TableHeaderRow />
			</tfoot>
		</table>
		</div>
	);
}

// ── Bulk action bar ───────────────────────────────────────────────────────────

function BulkBar( { selected, onApply } ) {
	const [ action, setAction ] = useState( '' );
	const isGlobal = BULK_OPTIONS.find( ( o ) => o.value === action )?.global;

	return (
		<div className="wcap-ao-bulk__left">
			<select
				className="wcap-ao-bulk__select"
				value={ action }
				onChange={ ( e ) => setAction( e.target.value ) }
			>
				{ BULK_OPTIONS.map( ( o ) => <option key={ o.value } value={ o.value } disabled={ o.disabled }>{ o.label }</option> ) }
			</select>
			<button
				className="wcap-ao-btn wcap-ao-btn--outline"
				onClick={ () => onApply( action ) }
				disabled={ ! action || ( ! isGlobal && selected.size === 0 ) }
			>
				{ __( 'Apply', 'woocommerce-abandoned-cart' ) }
			</button>
		</div>
	);
}

// ── Tabs ──────────────────────────────────────────────────────────────────────

const TABS = [
	{ id: 'all',        label: __( 'All',                            'woocommerce-abandoned-cart' ) },
	{ id: 'registered', label: __( 'Registered Users',               'woocommerce-abandoned-cart' ) },
	{ id: 'guest',      label: __( 'Guest',                          'woocommerce-abandoned-cart' ) },
	{ id: 'visitor',    label: __( 'Carts without Customer Details', 'woocommerce-abandoned-cart' ) },
];

const LOCKED_TABS = [
	__( 'Unsubscribed', 'woocommerce-abandoned-cart' ),
	__( 'Trash',        'woocommerce-abandoned-cart' ),
];

// ── Main component ────────────────────────────────────────────────────────────

export function AbandonedOrders() {
	const [ searchParams ] = useSearchParams();
	const initialTab       = searchParams.get( 'tab' );
	const initialStatus    = searchParams.get( 'status' );

	const [ activeTab,    setActiveTab    ] = useState( TABS.some( ( t ) => t.id === initialTab ) ? initialTab : 'all' );
	const [ duration,     setDuration     ] = useState( 'last_7_days' );
	const [ startDate,    setStartDate    ] = useState( siteDaysAgo( 7 ) );
	const [ endDate,      setEndDate      ] = useState( siteDaysAgo() );
	const [ statusFilter, setStatusFilter ] = useState( [ 'abandoned', 'recovered' ].includes( initialStatus ) ? initialStatus : 'all' );
	const [ rows,         setRows         ] = useState( [] );
	const [ meta,         setMeta         ] = useState( {} );
	const [ loading,      setLoading      ] = useState( false );
	const [ error,        setError        ] = useState( null );
	const [ selected,     setSelected     ] = useState( new Set() );
	const [ cartPopup,    setCartPopup    ] = useState( null );
	const [ tabCounts,    setTabCounts    ] = useState( {} );

	const filterParams = useCallback( () => {
		const params = new URLSearchParams( { status: statusFilter } );
		if ( startDate ) params.set( 'start_date', startDate );
		if ( endDate )   params.set( 'end_date',   endDate );
		return params;
	}, [ statusFilter, startDate, endDate ] );

	const fetchData = useCallback( async ( page = 1 ) => {
		setLoading( true );
		setError( null );
		setSelected( new Set() );
		try {
			const params = filterParams();
			params.set( 'page', page );
			params.set( 'per_page', 20 );
			params.set( 'view', activeTab );

			const res = await apiFetch( { path: `/wcal/v1/abandoned-carts?${ params.toString() }` } );

			setRows( res.data ?? [] );
			setMeta( res.meta ?? {} );
		} catch ( e ) {
			setError( e?.message || __( 'Failed to load abandoned orders. Please try again.', 'woocommerce-abandoned-cart' ) );
		} finally {
			setLoading( false );
		}
	}, [ filterParams, activeTab ] );

	// Fetch counts for all tabs in a single request.
	const fetchTabCounts = useCallback( async () => {
		try {
			const res = await apiFetch( { path: `/wcal/v1/abandoned-carts/counts?${ filterParams().toString() }` } );
			setTabCounts( res?.data ?? {} );
		} catch ( e ) {
			setError( e?.message || __( 'Failed to load abandoned orders. Please try again.', 'woocommerce-abandoned-cart' ) );
		}
	}, [ filterParams ] );

	// Fetch when tab changes
	useEffect( () => {
		fetchData( 1 );
	}, [ activeTab ] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Load all tab counts on mount
	useEffect( () => {
		fetchTabCounts();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	function handleApplyFilter() {
		fetchData( 1 );
		fetchTabCounts();
	}

	function handleDurationChange( val ) {
		setDuration( val );
		if ( val === 'yesterday' ) { setStartDate( siteDaysAgo( 1 ) ); setEndDate( siteDaysAgo( 1 ) ); }
		if ( val === 'today' )     { setStartDate( siteDaysAgo() ); setEndDate( siteDaysAgo() ); }
		if ( DURATION_DAYS[ val ] ) { setStartDate( siteDaysAgo( DURATION_DAYS[ val ] ) ); setEndDate( siteDaysAgo() ); }
	}

	function toggleRow( id ) {
		setSelected( ( prev ) => {
			const next = new Set( prev );
			if ( next.has( id ) ) {
				next.delete( id );
			} else {
				next.add( id );
			}
			return next;
		} );
	}

	function toggleAll( visibleRows, allChecked ) {
		setSelected( allChecked ? new Set() : new Set( visibleRows.map( ( r ) => r.id ) ) );
	}

	// Stay on the current page unless deleting emptied it.
	function reloadAfterDelete( removed ) {
		const page = meta.page ?? 1;
		fetchData( removed >= rows.length && page > 1 ? page - 1 : page );
		fetchTabCounts();
	}

	async function handleBulkApply( action ) {
		const option = BULK_OPTIONS.find( ( o ) => o.value === action );
		const prompt = option?.global
			? `${ option.label }? ${ __( 'This cannot be undone.', 'woocommerce-abandoned-cart' ) }`
			: __( 'Permanently delete the selected carts? This cannot be undone.', 'woocommerce-abandoned-cart' );
		if ( ! window.confirm( prompt ) ) return; // eslint-disable-line no-alert
		try {
			await apiFetch( {
				path:   '/wcal/v1/abandoned-carts/bulk',
				method: 'POST',
				data:   option?.global ? { action } : { action, ids: [ ...selected ] },
			} );
			reloadAfterDelete( option?.global ? rows.length : selected.size );
		} catch ( e ) {
			setError( e?.message || __( 'Bulk action failed.', 'woocommerce-abandoned-cart' ) );
		}
	}

	async function handleDeleteRow( id ) {
		if ( ! window.confirm( __( 'Permanently delete this cart? This cannot be undone.', 'woocommerce-abandoned-cart' ) ) ) return; // eslint-disable-line no-alert
		try {
			await apiFetch( { path: `/wcal/v1/abandoned-carts/${ id }`, method: 'DELETE' } );
			reloadAfterDelete( 1 );
		} catch ( e ) {
			setError( e?.message || __( 'Failed to delete cart.', 'woocommerce-abandoned-cart' ) );
		}
	}

	const recoveredCount  = ! loading ? ( meta.recovered_count ?? 0 ) : 0;
	const hasNoRecoveries = ! loading && ( meta.total ?? 0 ) > 0 && recoveredCount === 0;

	const cur        = meta.page ?? 1;
	const totalPages = meta.pages ?? 1;
	const pager      = (
		<div className="wcap-ao-bulk">
			<BulkBar selected={ selected } onApply={ handleBulkApply } />
			<Pagination
				currentPage={ cur }
				totalPages={ totalPages }
				totalItems={ meta.total ?? 0 }
				previousDisabled={ cur <= 1 }
				nextDisabled={ cur >= totalPages }
				previousPage={ cur - 1 }
				nextPage={ cur + 1 }
				lastPage={ totalPages }
				onPageChange={ ( p ) => fetchData( p ) }
			/>
		</div>
	);

	return (
		<div className="wcap-page wcap-ao">

			{ /* ── Tabs ── */ }
			<div className="wcap-ao-tabs">
				{ TABS.map( ( tab, i ) => (
					<span key={ tab.id }>
						{ i > 0 && <span className="wcap-ao-tabs__sep">|</span> }
						<button
							className={ `wcap-ao-tabs__btn${ activeTab === tab.id ? ' wcap-ao-tabs__btn--active' : '' }` }
							onClick={ () => setActiveTab( tab.id ) }
						>
							{ tab.label }
							{ tabCounts[ tab.id ] !== null && tabCounts[ tab.id ] !== undefined && (
								<span className="wcap-ao-tabs__count"> ({ tabCounts[ tab.id ] })</span>
							) }
						</button>
					</span>
				) ) }
				{ LOCKED_TABS.map( ( label ) => (
					<span key={ label }>
						<span className="wcap-ao-tabs__sep">|</span>
						<LockedAction label={ label } className="wcap-ao-tabs__btn" />
					</span>
				) ) }
			</div>

			<p className="wcap-ao-desc">
				{ __( 'The list below shows all the carts that were abandoned and subsequently recovered.', 'woocommerce-abandoned-cart' ) }
			</p>

			{ /* ── Filter bar ── */ }
			<div className="wcap-ao-filterbar">
				<div className="wcap-ao-filterbar__left">
					<select className="wcap-ao-select" value={ duration } onChange={ ( e ) => handleDurationChange( e.target.value ) }>
						{ DURATION_OPTIONS.map( ( o ) => <option key={ o.value } value={ o.value }>{ o.label }</option> ) }
					</select>
					<input type="date" className="wcap-ao-date" value={ startDate } onChange={ ( e ) => { setDuration( 'custom' ); setStartDate( e.target.value ); } } />
					<input type="date" className="wcap-ao-date" value={ endDate }   onChange={ ( e ) => { setDuration( 'custom' ); setEndDate( e.target.value ); } }   />
					<select className="wcap-ao-select" value={ statusFilter } onChange={ ( e ) => setStatusFilter( e.target.value ) }>
						{ STATUS_OPTIONS.map( ( o ) => <option key={ o.value } value={ o.value } disabled={ o.disabled }>{ o.label }</option> ) }
					</select>
					<span className="wcal-ao-locked-control">
						<select className="wcap-ao-select" disabled>
							<option>{ __( 'All Sources', 'woocommerce-abandoned-cart' ) }</option>
						</select>
						<ProBadge />
					</span>
					<button className="wcap-ao-btn wcap-ao-btn--primary" onClick={ handleApplyFilter } disabled={ loading }>
						{ __( 'Apply Filter', 'woocommerce-abandoned-cart' ) }
					</button>
				</div>
				<div className="wcap-ao-filterbar__right">
					<LockedAction label={ __( 'CSV', 'woocommerce-abandoned-cart' ) } />
					<LockedAction label={ __( 'Print', 'woocommerce-abandoned-cart' ) } />
				</div>
			</div>

			{ error && <div className="wcap-ao-error">{ error }</div> }

			{ recoveredCount > 0 && (
				<div className="wcap-ao-banner">
					<strong className="wcap-ao-banner__count">{ recoveredCount }</strong>
					{ ' ' }{ __( 'carts worth', 'woocommerce-abandoned-cart' ) }{ ' ' }
					<strong className="wcap-ao-banner__value"><Html html={ meta.recovered_total_formatted ?? '' } /></strong>
					{ ' ' }{ __( 'were recovered during the selected range.', 'woocommerce-abandoned-cart' ) }
				</div>
			) }
			{ hasNoRecoveries && (
				<div className="wcap-ao-banner wcap-ao-banner--none">
					{ __( 'No carts were recovered during the selected range', 'woocommerce-abandoned-cart' ) }
				</div>
			) }

			{ /* ── Bulk + table ── */ }
			{ pager }
			{ loading ? (
				<div className="wcap-ao-spinner"><Spinner /></div>
			) : (
				<OrdersTable
					rows={ rows }
					selected={ selected }
					onToggleRow={ toggleRow }
					onToggleAll={ toggleAll }
					onView={ ( row ) => setCartPopup( row ) }
					onDelete={ handleDeleteRow }
				/>
			) }
			{ pager }

			{ /* ── Modal ── */ }
			{ cartPopup && (
				<CartDetailModal
					cartId={ cartPopup.id }
					onClose={ () => setCartPopup( null ) }
				/>
			) }
		</div>
	);
}
