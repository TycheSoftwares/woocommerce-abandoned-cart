/**
 * Templates page — Email / SMS / WhatsApp template lists.
 * URL: #/email-template, #/email-template/email, #/email-template/sms, #/email-template/whatsapp
 *
 * SMS and WhatsApp are Pro-only, and so are segments, the rate columns and Duplicate.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Notice, TabPanel, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useNavigate, useMatch, useLocation } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import { Table } from '../../components';
import { UpgradeSection }            from '../../components/UpgradeSection';
import { ProBadge }                  from '../../components/ProLockedRow';
import { LockedAction }              from '../AbandonedOrders';
import EmailTemplateEdit             from './Email/EmailTemplateEdit';

const TABS = [
	{ name: 'email',    title: __( 'Email Templates',        'woocommerce-abandoned-cart' ) },
	{ name: 'sms',      title: __( 'SMS Notifications',      'woocommerce-abandoned-cart' ) },
	{ name: 'whatsapp', title: __( 'WhatsApp Notifications', 'woocommerce-abandoned-cart' ) },
];

const VALID_TABS = TABS.map( ( t ) => t.name );

const PRO_TABS = {
	sms:      'wcap_sms_settings',
	whatsapp: 'wcap_whatsapp_settings',
};

const LockedCell = () => <span style={ { color: '#757575' } }>—</span>;

const PER_PAGE = 20;

// ── Email template list ───────────────────────────────────────────────────────

function EmailTemplateList() {
	const navigate = useNavigate();

	const [ rows,       setRows       ] = useState( [] );
	const [ loading,    setLoading    ] = useState( true );
	const [ notice,     setNotice     ] = useState( null );
	const [ page,       setPage       ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalItems, setTotalItems ] = useState( 0 );
	const [ sortBy,     setSortBy     ] = useState( '' );
	const [ sortDir,    setSortDir    ] = useState( 'asc' );

	// ── Fetch ────────────────────────────────────────────────────────────────

	const fetchPage = useCallback( ( nextPage = 1 ) => {
		setLoading( true );
		apiFetch( { path: `/wcal/v1/templates/email?page=${ nextPage }&per_page=${ PER_PAGE }` } )
			.then( ( res ) => {
				setRows( res?.data ?? [] );
				const meta = res?.meta ?? {};
				setTotalPages( meta.pages ?? 1 );
				setTotalItems( meta.total ?? 0 );
				setPage( nextPage );
			} )
			.catch( () =>
				setNotice( { status: 'error', message: __( 'Failed to load email templates.', 'woocommerce-abandoned-cart' ) } )
			)
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => { fetchPage( 1 ); }, [ fetchPage ] );

	// ── Sort (client-side within page) ───────────────────────────────────────

	const handleSort = ( key ) => {
		const nextDir = sortBy === key && sortDir === 'asc' ? 'desc' : 'asc';
		setSortBy( key );
		setSortDir( nextDir );
		setRows( ( prev ) =>
			[ ...prev ].sort( ( a, b ) => {
				const numericKeys = [ 'sr', 'frequency', 'sent' ];
				if ( numericKeys.includes( key ) ) {
					return nextDir === 'asc'
						? Number( a[ key ] ) - Number( b[ key ] )
						: Number( b[ key ] ) - Number( a[ key ] );
				}
				const av = String( a[ key ] ?? '' ).toLowerCase();
				const bv = String( b[ key ] ?? '' ).toLowerCase();
				return nextDir === 'asc' ? av.localeCompare( bv ) : bv.localeCompare( av );
			} )
		);
	};

	// ── Toggle active ────────────────────────────────────────────────────────

	const handleToggle = async ( row ) => {
		const newStatus = ! ( row.is_active === true || row.is_active === 1 || row.is_active === '1' );
		setRows( ( prev ) =>
			prev.map( ( r ) => String( r.id ) === String( row.id ) ? { ...r, is_active: newStatus } : r )
		);
		try {
			await apiFetch( {
				path:   `/wcal/v1/templates/email/${ row.id }/status`,
				method: 'PUT',
				data:   { is_active: newStatus },
			} );
			// Activating a template deactivates the others sent after the same time.
			if ( newStatus ) {
				fetchPage( page );
			}
		} catch {
			setRows( ( prev ) =>
				prev.map( ( r ) => String( r.id ) === String( row.id ) ? { ...r, is_active: ! newStatus } : r )
			);
			setNotice( { status: 'error', message: __( 'Failed to update template status.', 'woocommerce-abandoned-cart' ) } );
		}
	};

	// ── Delete ───────────────────────────────────────────────────────────────

	const handleDelete = async ( id ) => {
		if ( ! window.confirm( __( 'Delete this email template?', 'woocommerce-abandoned-cart' ) ) ) return; // eslint-disable-line no-alert
		try {
			await apiFetch( { path: `/wcal/v1/templates/email/${ id }`, method: 'DELETE' } );
			fetchPage( rows.length === 1 && page > 1 ? page - 1 : page );
			setNotice( { status: 'success', message: __( 'Template deleted.', 'woocommerce-abandoned-cart' ) } );
			setTimeout( () => setNotice( null ), 3000 );
		} catch {
			setNotice( { status: 'error', message: __( 'Failed to delete template.', 'woocommerce-abandoned-cart' ) } );
		}
	};

	// ── Bulk delete ──────────────────────────────────────────────────────────

	const handleBulkApply = async ( action, selectedRows ) => {
		if ( action !== 'delete' ) return;
		if ( ! window.confirm( // eslint-disable-line no-alert
			`${ __( 'Delete', 'woocommerce-abandoned-cart' ) } ${ selectedRows.length } ${ __( 'email template(s)?', 'woocommerce-abandoned-cart' ) }`
		) ) return;

		const ids     = selectedRows.map( ( r ) => r.id );
		const results = await Promise.allSettled(
			ids.map( ( id ) => apiFetch( { path: `/wcal/v1/templates/email/${ id }`, method: 'DELETE' } ) )
		);
		const deleted = results.filter( ( r ) => r.status === 'fulfilled' ).length;
		const failed  = results.length - deleted;

		fetchPage( deleted === rows.length && page > 1 ? page - 1 : page );

		if ( failed > 0 ) {
			setNotice( { status: 'warning', message: `${ deleted } ${ __( 'deleted,', 'woocommerce-abandoned-cart' ) } ${ failed } ${ __( 'failed.', 'woocommerce-abandoned-cart' ) }` } );
		} else {
			setNotice( { status: 'success', message: `${ deleted } ${ __( 'template(s) deleted.', 'woocommerce-abandoned-cart' ) }` } );
			setTimeout( () => setNotice( null ), 3000 );
		}
	};

	// ── Column definitions ───────────────────────────────────────────────────

	const columns = [
		{
			key:      'id',
			label:    __( 'ID', 'woocommerce-abandoned-cart' ),
			width:    '42px',
			align:    'center',
			sortable: true,
			render:   ( row ) => row.id,
		},
		{
			key:      'template_name',
			label:    __( 'Name Of Template', 'woocommerce-abandoned-cart' ),
			width:    '170px',
			sortable: true,
			render:   ( row ) => (
				<div>
					<a
						href="#"
						style={ { fontWeight: 500, color: '#41278D', textDecoration: 'none' } }
						onClick={ ( e ) => { e.preventDefault(); navigate( `/email-template/email/edit/${ row.id }` ); } }
					>
						{ row.template_name || <em style={ { color: '#757575' } }>{ __( '(untitled)', 'woocommerce-abandoned-cart' ) }</em> }
					</a>
					<div className="wcap-et-row-actions">
						<a href="#" onClick={ ( e ) => { e.preventDefault(); navigate( `/email-template/email/edit/${ row.id }` ); } }>
							{ __( 'Edit', 'woocommerce-abandoned-cart' ) }
						</a>
						<span className="wcap-et-sep">|</span>
						<LockedAction label={ __( 'Duplicate', 'woocommerce-abandoned-cart' ) } />
						<span className="wcap-et-sep">|</span>
						<a href="#" className="wcap-et-action--delete" onClick={ ( e ) => { e.preventDefault(); handleDelete( row.id ); } }>
							{ __( 'Delete', 'woocommerce-abandoned-cart' ) }
						</a>
					</div>
				</div>
			),
		},
		{
			key:      'sent_after',
			label:    __( 'Sent After Set Time', 'woocommerce-abandoned-cart' ),
			width:    '140px',
			sortable: false,
			render:   ( row ) => row.frequency
				? `${ row.frequency } ${ row.day_or_hour } ${ __( 'After Abandonment', 'woocommerce-abandoned-cart' ) }`
				: '—',
		},
		{
			key:      'segments',
			label:    <>{ __( 'Send To Segment(s)', 'woocommerce-abandoned-cart' ) } <ProBadge /></>,
			width:    '110px',
			sortable: false,
			render:   () => <LockedCell />,
		},
		{
			key:      'sent',
			label:    __( 'No.of Emails Sent', 'woocommerce-abandoned-cart' ),
			width:    '80px',
			align:    'center',
			sortable: true,
			render:   ( row ) => row.sent ?? 0,
		},
		{
			key:    'open_rate',
			label:  <>{ __( 'Open Rate(%)', 'woocommerce-abandoned-cart' ) } <ProBadge /></>,
			width:  '76px',
			align:  'center',
			render: () => <LockedCell />,
		},
		{
			key:    'click_rate',
			label:  <>{ __( 'Link Click Rate(%)', 'woocommerce-abandoned-cart' ) } <ProBadge /></>,
			width:  '82px',
			align:  'center',
			render: () => <LockedCell />,
		},
		{
			key:    'coupon_redemption_rate',
			label:  <>{ __( 'Coupon Redemption Rate(%)', 'woocommerce-abandoned-cart' ) } <ProBadge /></>,
			width:  '100px',
			align:  'center',
			render: () => <LockedCell />,
		},
		{
			key:    'conversion_rate',
			label:  <>{ __( 'Conversion Rate(%)', 'woocommerce-abandoned-cart' ) } <ProBadge /></>,
			width:  '82px',
			align:  'center',
			render: () => <LockedCell />,
		},
		{
			key:    'is_active',
			label:  __( 'Start Sending', 'woocommerce-abandoned-cart' ),
			width:  '90px',
			align:  'center',
			render: ( row ) => (
				<ToggleControl
					checked={ row.is_active === true || row.is_active === 1 || row.is_active === '1' }
					onChange={ () => handleToggle( row ) }
					__nextHasNoMarginBottom
				/>
			),
		},
	];

	// ── Render ───────────────────────────────────────────────────────────────

	return (
		<div className='wcap-template-list'>

			{ notice && (
				<Notice
					status={ notice.status }
					isDismissible
					onDismiss={ () => setNotice( null ) }
					style={ { marginBottom: '16px' } }
				>
					{ notice.message }
				</Notice>
			) }

			{ /* Header row */ }
			<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', margin: '16px 0' } }>
				<div>
					<h2 style={ { margin: 0, fontSize: '16px', fontWeight: 600 } }>
						{ __( 'Email Templates', 'woocommerce-abandoned-cart' ) }
					</h2>
					<p style={ { margin: '4px 0 0', fontSize: '13px', color: '#757575' } }>
						{ __( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-abandoned-cart' ) }
					</p>
				</div>
				<Button variant="primary" onClick={ () => navigate( '/email-template/email/add' ) } className="add-new-button">
					{ __( '+ Add New Template', 'woocommerce-abandoned-cart' ) }
				</Button>
			</div>

			{ /* Table */ }
			<Table
				columns={ columns }
				rows={ rows }
				rowKey="id"
				loading={ loading }
				emptyMessage={ __( 'No email templates found. Create your first one!', 'woocommerce-abandoned-cart' ) }

				sortBy={ sortBy }
				sortDir={ sortDir }
				onSort={ handleSort }

				bulkActions={ [ { value: 'delete', label: __( 'Delete', 'woocommerce-abandoned-cart' ) } ] }
				onBulkApply={ handleBulkApply }

				currentPage={ page }
				totalPages={ totalPages }
				totalItems={ totalItems }
				onPageChange={ fetchPage }
			/>

			<div className="wcap-template-list__legend">
				<p><strong>{ __( 'Open Rate :', 'woocommerce-abandoned-cart' ) }</strong> { __( 'Number of emails opened versus number of emails sent.', 'woocommerce-abandoned-cart' ) }</p>
				<p><strong>{ __( 'Link Click Rate :', 'woocommerce-abandoned-cart' ) }</strong> { __( 'Number of links clicked versus number of emails sent. In cases where coupons are present for the template, the coupon application rate will be same as Link Click Rate, since coupons are auto applied when a link is clicked.', 'woocommerce-abandoned-cart' ) }</p>
				<p><strong>{ __( 'Coupon Redemption Rate :', 'woocommerce-abandoned-cart' ) }</strong> { __( 'Number of Coupons applied (i.e. number of links clicked) versus number of emails opened.', 'woocommerce-abandoned-cart' ) }</p>
				<p><strong>{ __( 'Conversion Rate :', 'woocommerce-abandoned-cart' ) }</strong> { __( 'Number of carts recovered versus number of emails sent.', 'woocommerce-abandoned-cart' ) }</p>
			</div>

		</div>
	);
}

// ── Root exported component ───────────────────────────────────────────────────

export function EmailTemplate() {
	const navigate = useNavigate();
	const location = useLocation();
	const isAdd    = useMatch( '/email-template/email/add' );
	const isEdit   = useMatch( '/email-template/email/edit/:id' );

	// Derive active tab from the current path segment.
	const pathTab   = location.pathname.replace( /^\/email-template\/?/, '' ).split( '/' )[ 0 ];
	const activeTab = ( isAdd || isEdit ) ? 'email' : ( VALID_TABS.includes( pathTab ) ? pathTab : TABS[ 0 ].name );

	return (
		<div className="wcap-page wcap-page--email-template">
			<TabPanel
				key={ activeTab }
				tabs={ TABS }
				initialTabName={ activeTab }
				onSelect={ ( selected ) => {
					// When on an edit/add URL the TabPanel fires onSelect on mount for
					// the initially-active tab. Guard against that replacing the edit URL.
					if ( ( isAdd || isEdit ) && selected === 'email' ) return;
					navigate( `/email-template/${ selected }`, { replace: true } );
				} }
			>
				{ ( t ) => {
					if ( t.name === 'email' ) {
						if ( isAdd || isEdit ) {
							return <EmailTemplateEdit key="email-edit" />;
						}
						return <EmailTemplateList key="email" />;
					}
					return <UpgradeSection key={ t.name } section={ PRO_TABS[ t.name ] } />;
				} }
			</TabPanel>
		</div>
	);
}
