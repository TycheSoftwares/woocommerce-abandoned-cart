/**
 * Reminders Sent page — Email / SMS / WhatsApp tabs with router URL sync.
 * Data: GET /wcal/v1/reminders-sent
 *
 * SMS and WhatsApp are Pro-only, and Lite doesn't track opens or clicks.
 */

import { useEffect, useState }                    from '@wordpress/element';
import { TabPanel, Notice, Button }                 from '@wordpress/components';
import { __ }                                      from '@wordpress/i18n';
import { useParams, useNavigate }                  from 'react-router-dom';
import { Table, Pagination }                        from '../components';
import { useRemindersData }                        from '../hooks/useRemindersData';
import { CartDetailModal }                         from './AbandonedOrders';
import { UpgradeSection }                          from '../components/UpgradeSection';
import { ProBadge }                                from '../components/ProLockedRow';
import { formatWallClock, siteDaysAgo }            from '../utils/date';

// ── Period presets ─────────────────────────────────────────────────────────────

const PERIOD_OPTIONS = [
	{ value: '1',     label: __( 'Yesterday',    'woocommerce-abandoned-cart' ), isYesterday: true },
	{ value: '0',     label: __( 'Today',        'woocommerce-abandoned-cart' ), isToday: true },
	{ value: '7',     label: __( 'Last 7 days',  'woocommerce-abandoned-cart' ) },
	{ value: '15',    label: __( 'Last 15 days', 'woocommerce-abandoned-cart' ) },
	{ value: '30',    label: __( 'Last 30 days', 'woocommerce-abandoned-cart' ) },
	{ value: '90',    label: __( 'Last 90 days', 'woocommerce-abandoned-cart' ) },
	{ value: '365',   label: __( 'Last 365',     'woocommerce-abandoned-cart' ) },
	{ value: 'custom', label: __( 'Custom Range', 'woocommerce-abandoned-cart' ) },
];

// ── Tab configuration ─────────────────────────────────────────────────────────

const EMAIL_TAB = {
	name:             'email',
	reminderType:     'email',
	contactLabel:     __( 'User Email Address',         'woocommerce-abandoned-cart' ),
	sentStatLabel:    __( 'Email Sent',                 'woocommerce-abandoned-cart' ),
	openedStatLabel:  __( 'Email Opened',               'woocommerce-abandoned-cart' ),
	clickedStatLabel: __( 'Links Clicked',              'woocommerce-abandoned-cart' ),
	sentTimeLabel:    __( 'Email Sent Time',            'woocommerce-abandoned-cart' ),
	openedTimeLabel:  __( 'Date / Time Email Opened',   'woocommerce-abandoned-cart' ),
	templateLabel:    __( 'Sent Email Template',        'woocommerce-abandoned-cart' ),
};

const PRO_TABS = {
	sms:      'wcap_sms_settings',
	whatsapp: 'wcap_whatsapp_settings',
};

const WP_TABS = [
	{ name: 'email',    title: __( 'Email Sent',    'woocommerce-abandoned-cart' ) },
	{ name: 'sms',      title: __( 'SMS Sent',      'woocommerce-abandoned-cart' ) },
	{ name: 'whatsapp', title: __( 'WhatsApp Sent', 'woocommerce-abandoned-cart' ) },
];

const VALID_TABS = WP_TABS.map( ( t ) => t.name );

// ── Stat pill ─────────────────────────────────────────────────────────────────

function StatPill( { label, children } ) {
	return (
		<div style={ {
			display:      'inline-flex',
			alignItems:   'center',
			border:       '1px solid #41278d',
			borderRadius: '4px',
			padding:      '6px 14px',
			fontSize:     '13px',
			fontWeight:   '500',
			color:        '#41278d',
			background:   '#fff',
			whiteSpace:   'nowrap',
		} }>
			<strong>{ label }:&nbsp;</strong>{ children }
		</div>
	);
}

// ── Hover cell wrapper ────────────────────────────────────────────────────────

function HoverCell( { children } ) {
	const [ hovered, setHovered ] = useState( false );
	return (
		<span
			onMouseEnter={ () => setHovered( true ) }
			onMouseLeave={ () => setHovered( false ) }
		>
			{ children( hovered ) }
		</span>
	);
}

// ── Single tab content ────────────────────────────────────────────────────────

function ReminderTab( { config } ) {
	const {
		rows, meta, isLoading, error,
		startDate, endDate, setStartDate, setEndDate,
		handleFilter, handlePageChange,
		page,
		reload,
	} = useRemindersData( config.reminderType );

	const [ period,    setPeriod    ] = useState( '7' );
	const [ cartPopup, setCartPopup ] = useState( null );

	// Initial load once per mount.
	useEffect( () => { reload(); }, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	function handlePeriodChange( e ) {
		const val = e.target.value;
		setPeriod( val );
		if ( val === 'custom' ) return;
		const opt = PERIOD_OPTIONS.find( ( o ) => o.value === val );
		if ( opt?.isToday ) {
			setStartDate( siteDaysAgo() );
			setEndDate( siteDaysAgo() );
		} else if ( opt?.isYesterday ) {
			setStartDate( siteDaysAgo( 1 ) );
			setEndDate( siteDaysAgo( 1 ) );
		} else {
			setStartDate( siteDaysAgo( parseInt( val, 10 ) ) );
			setEndDate( siteDaysAgo() );
		}
	}

	const columns = [
		{ key: 'email_id', label: config.contactLabel, render: ( row ) => (
			<HoverCell>
				{ ( hovered ) => (
					<>
						{ row.email_id ?? '—' }
						{ row.cart_id && ! row.recovered && (
							<>
								<br />
								<a
									href="#"
									style={ { fontSize: '12px', visibility: hovered ? 'visible' : 'hidden' } }
									onClick={ ( e ) => { e.preventDefault(); setCartPopup( row ); } }
								>
									{ __( 'Abandoned', 'woocommerce-abandoned-cart' ) }
								</a>
							</>
						) }
					</>
				) }
			</HoverCell>
		) },
		{ key: 'sent_at',       label: config.sentTimeLabel,                                        render: ( row ) => formatWallClock( row.sent_at ) },
		{ key: 'opened_at',     label: <>{ config.openedTimeLabel } <ProBadge /></>,                render: () => <span style={ { color: '#757575' } }>—</span> },
		{ key: 'link_clicked',  label: <>{ __( 'Link Clicked', 'woocommerce-abandoned-cart' ) } <ProBadge /></>, render: () => <span style={ { color: '#757575' } }>—</span> },
		{ key: 'template_name', label: config.templateLabel,                                        render: ( row ) => row.template_name ?? '—' },
	];

	const totalPages = meta.pages ?? 1;
	const totalItems = meta.total ?? 0;

	return (
		<div style={ { padding: '16px 0' } }>

			{ error && (
				<Notice status="error" isDismissible={ false } style={ { marginBottom: '12px' } }>
					{ error }
				</Notice>
			) }

			{ /* ── Description + stat pills ── */ }
			<div style={ {
				display:        'flex',
				alignItems:     'flex-start',
				justifyContent: 'space-between',
				flexWrap:       'wrap',
				gap:            '12px',
				marginBottom:   '20px',
			} }>
				<p style={ { margin: 0, color: '#555', fontSize: '13px', maxWidth: '560px' } }>
					{ __( 'The Report below shows emails sent, emails opened and other related stats for the selected date range', 'woocommerce-abandoned-cart' ) }
				</p>

				<div style={ { display: 'flex', gap: '8px', flexWrap: 'wrap' } }>
					<StatPill label={ config.sentStatLabel }>{ totalItems }</StatPill>
					<StatPill label={ config.openedStatLabel }><ProBadge /></StatPill>
					<StatPill label={ config.clickedStatLabel }><ProBadge /></StatPill>
				</div>
			</div>

			{ /* ── Filter bar ── */ }
			<div style={ {
				display:      'flex',
				alignItems:   'center',
				flexWrap:     'wrap',
				gap:          '8px',
				marginBottom: '16px',
			} }>
				{ /* Period dropdown */ }
				<select
					value={ period }
					onChange={ handlePeriodChange }
					style={ {
						height:       '36px',
						padding:      '0 32px 0 10px',
						border:       '1px solid #8c8f94',
						borderRadius: '4px',
						fontSize:     '13px',
						background:   '#fff url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'8\'%3E%3Cpath d=\'M0 0l6 8 6-8z\' fill=\'%23555\'/%3E%3C/svg%3E") no-repeat right 10px center / 10px 7px',
						appearance:   'none',
						cursor:       'pointer',
					} }
				>
					{ PERIOD_OPTIONS.map( ( opt ) => (
						<option key={ opt.value } value={ opt.value }>{ opt.label }</option>
					) ) }
				</select>

				{ /* Start date */ }
				<input
					type="date"
					value={ startDate }
					onChange={ ( e ) => { setPeriod( 'custom' ); setStartDate( e.target.value ); } }
					style={ {
						height:       '36px',
						padding:      '0 8px',
						border:       '1px solid #8c8f94',
						borderRadius: '4px',
						fontSize:     '13px',
					} }
				/>

				{ /* End date */ }
				<input
					type="date"
					value={ endDate }
					onChange={ ( e ) => { setPeriod( 'custom' ); setEndDate( e.target.value ); } }
					style={ {
						height:       '36px',
						padding:      '0 8px',
						border:       '1px solid #8c8f94',
						borderRadius: '4px',
						fontSize:     '13px',
					} }
				/>

				<Button
					variant="secondary"
					onClick={ handleFilter }
					style={ {
						height:       '36px',
						borderColor:  '#41278d',
						color:        '#41278d',
					} }
				>
					{ __( 'Apply Filter', 'woocommerce-abandoned-cart' ) }
				</Button>

				{ /* Pagination — pushed to the right, inline with filters */ }
				<div style={ { marginLeft: 'auto' } }>
					<Pagination
						currentPage={ page }
						totalPages={ totalPages }
						totalItems={ totalItems }
						previousDisabled={ page <= 1 ? 'disabled' : '' }
						nextDisabled={ page >= totalPages ? 'disabled' : '' }
						previousPage={ page - 1 }
						nextPage={ page + 1 }
						lastPage={ totalPages }
						onPageChange={ handlePageChange }
					/>
				</div>
			</div>

			{ /* ── Table ── */ }
			<Table
				columns={ columns }
				rows={ rows.map( ( r ) => ( { ...r, _id: r.id } ) ) }
				rowKey="_id"
				loading={ isLoading }
				emptyMessage={ __( 'No records found for the selected filters.', 'woocommerce-abandoned-cart' ) }
				currentPage={ page }
				totalPages={ totalPages }
				totalItems={ totalItems }
				onPageChange={ handlePageChange }
			/>

			{ /* ── Cart detail modal ── */ }
			{ cartPopup && (
				<CartDetailModal
					cartId={ cartPopup.cart_id }
					onClose={ () => setCartPopup( null ) }
					hideFooter
				/>
			) }
		</div>
	);
}

// ── Main page ─────────────────────────────────────────────────────────────────

export function Reminders() {
	const { tab }  = useParams();
	const navigate = useNavigate();

	const initialTab = VALID_TABS.includes( tab ) ? tab : 'email';

	return (
		<div className="wcap-page wcap-page--reminders">
			<TabPanel
				key={ initialTab }
				tabs={ WP_TABS }
				initialTabName={ initialTab }
				onSelect={ ( selected ) => navigate( `/reminders/${ selected }`, { replace: true } ) }
			>
				{ ( t ) => (
					PRO_TABS[ t.name ]
						? <UpgradeSection key={ t.name } section={ PRO_TABS[ t.name ] } />
						: <ReminderTab key={ t.name } config={ EMAIL_TAB } />
				) }
			</TabPanel>
		</div>
	);
}
