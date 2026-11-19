/**
 * Dashboard page.
 *
 * Stats cards + two line charts + Product Report + Abandoned Cart Details tables.
 *
 * Data sources:
 *   GET /wcal/v1/dashboard?duration=<slug>          — KPI stats + graph_data
 *   GET /wcal/v1/dashboard/monthly-chart            — last-12-months chart
 *   GET /wcal/v1/product-report?per_page=5          — top 5 products
 *   GET /wcal/v1/abandoned-carts?per_page=5          — last 5 abandoned carts
 */

import { useState, useEffect, useCallback, createInterpolateElement } from '@wordpress/element';
import { SelectControl, Spinner }           from '@wordpress/components';
import { __ }                               from '@wordpress/i18n';
import apiFetch                             from '@wordpress/api-fetch';

import {
	Chart as ChartJS,
	CategoryScale, LinearScale,
	BarElement,
	PointElement, LineElement, Filler,
	Tooltip, Legend,
} from 'chart.js';
import { Bar, Line } from 'react-chartjs-2';

import { ProBadge } from '../components/ProLockedRow';
import './dashboard.scss';

ChartJS.register( CategoryScale, LinearScale, BarElement, PointElement, LineElement, Filler, Tooltip, Legend );

// ── Constants ─────────────────────────────────────────────────────────────────

const DURATION_OPTIONS = [
	{ value: 'this_month',   label: __( 'This Month',   'woocommerce-abandoned-cart' ) },
	{ value: 'last_month',   label: __( 'Last Month',   'woocommerce-abandoned-cart' ) },
	{ value: 'this_quarter', label: __( 'This Quarter', 'woocommerce-abandoned-cart' ) },
	{ value: 'last_quarter', label: __( 'Last Quarter', 'woocommerce-abandoned-cart' ) },
	{ value: 'this_year',    label: __( 'This Year',    'woocommerce-abandoned-cart' ) },
	{ value: 'last_year',    label: __( 'Last Year',    'woocommerce-abandoned-cart' ) },
	{ value: 'custom',       label: __( 'Custom',       'woocommerce-abandoned-cart' ) },
];

const sym = () => window.wcalAdminData?.currencySymbol ?? '';

function fmtCurrency( val ) {
	const n = parseFloat( val ?? 0 );
	return `${ sym() }${ isNaN( n ) ? 0 : n.toLocaleString( undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 } ) }`;
}

function fmtNum( val ) {
	const n = parseInt( val ?? 0, 10 );
	return isNaN( n ) ? 0 : n.toLocaleString();
}

function fmtPct( val ) {
	const n = parseFloat( val ?? 0 );
	return `${ isNaN( n ) ? 0 : n }%`;
}

// ── SVG icons ─────────────────────────────────────────────────────────────────

const IconTrend = () => (
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
		<polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
		<polyline points="16 7 22 7 22 13" />
	</svg>
);

const IconCart = () => (
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
		<circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" />
		<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
	</svg>
);

const IconMail = () => (
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
		<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
		<polyline points="22,6 12,13 2,6" />
	</svg>
);

const IconBar = () => (
	<svg viewBox="0 0 24 24" fill="currentColor">
		<rect x="3"  y="12" width="4" height="9" rx="1" />
		<rect x="10" y="7"  width="4" height="14" rx="1" />
		<rect x="17" y="3"  width="4" height="18" rx="1" />
	</svg>
);

// ── Pro upsell line on a stat card ───────────────────────────────────────────

function ProUpsell( { text } ) {
	const url = window.wcalAdminData?.proUrl;
	return (
		<span className="wcal-dash-upsell">
			{ createInterpolateElement( text, {
				a: <a href={ url } target="_blank" rel="noopener noreferrer" />, // eslint-disable-line jsx-a11y/anchor-has-content
			} ) }
			<ProBadge href={ url } />
		</span>
	);
}

// ── Stat card ─────────────────────────────────────────────────────────────────

function StatCard( { title, icon: Icon, value, subStats } ) {
	return (
		<div className="wcap-dash-card">
			<div className="wcap-dash-card__top">
				<span className="wcap-dash-card__title">{ title }</span>
				<span className="wcap-dash-card__icon-wrap">
					<Icon />
				</span>
			</div>
			<div className="wcap-dash-card__value">{ value }</div>
			<div className="wcap-dash-card__sub">
				{ subStats.map( ( s, i ) => (
					<div key={ i } className="wcap-dash-card__sub-row">{ s }</div>
				) ) }
			</div>
		</div>
	);
}

// ── Bar chart ─────────────────────────────────────────────────────────────────

function RevenueChart( { title, graphData, chartType = 'bar' } ) {
	const labels    = graphData ? Object.keys( graphData ) : [];
	const abandoned = labels.map( ( k ) => parseFloat( graphData[ k ]?.abandoned_amount ?? 0 ) );
	const recovered = labels.map( ( k ) => parseFloat( graphData[ k ]?.recovered_amount ?? 0 ) );

	const isLine = chartType === 'line';

	const data = {
		labels,
		datasets: isLine
			? [
				{
					label:           __( 'Abandoned Revenue', 'woocommerce-abandoned-cart' ),
					data:            abandoned,
					borderColor:     '#7c3aed',
					backgroundColor: 'rgba(124,58,237,0.07)',
					borderWidth:     2,
					pointRadius:     3,
					tension:         0.4,
					fill:            true,
				},
				{
					label:           __( 'Recovered Revenue', 'woocommerce-abandoned-cart' ),
					data:            recovered,
					borderColor:     '#166534',
					backgroundColor: 'rgba(22,101,52,0.07)',
					borderWidth:     2,
					pointRadius:     3,
					tension:         0.4,
					fill:            true,
				},
			]
			: [
				{
					label:           __( 'Abandoned Revenue', 'woocommerce-abandoned-cart' ),
					data:            abandoned,
					backgroundColor: '#7c3aed',
					borderRadius:    4,
					borderSkipped:   false,
				},
				{
					label:           __( 'Recovered Revenue', 'woocommerce-abandoned-cart' ),
					data:            recovered,
					backgroundColor: '#166534',
					borderRadius:    4,
					borderSkipped:   false,
				},
			],
	};

	const options = {
		responsive:          true,
		maintainAspectRatio: false,
		interaction:         { mode: 'index', intersect: false },
		plugins: {
			legend: { display: false },
			tooltip: {
				callbacks: {
					label: ( ctx ) => ` ${ ctx.dataset.label }: ${ sym() }${ ctx.parsed.y.toLocaleString( undefined, { minimumFractionDigits: 2 } ) }`,
				},
			},
		},
		scales: {
			x: {
				grid:  { display: false },
				ticks: { font: { size: 11 }, color: '#9ca3af' },
			},
			y: {
				grid:        { color: '#e8e8f0' },
				ticks: {
					font:     { size: 11 },
					color:    '#9ca3af',
					callback: ( v ) => {
						if ( v >= 1000 ) return `${ v / 1000 }k`;
						return v;
					},
				},
				beginAtZero: true,
			},
		},
	};

	return (
		<div className="wcap-dash-chart">
			<div className="wcap-dash-chart__header">
				<span className="wcap-dash-chart__title">{ title }</span>
				<span className="wcap-dash-chart__legend">
					<span className="wcap-dash-chart__legend-dot wcap-dash-chart__legend-dot--purple" />
					{ __( 'Abandoned Revenue', 'woocommerce-abandoned-cart' ) }
					<span className="wcap-dash-chart__legend-dot wcap-dash-chart__legend-dot--green" style={ { marginLeft: '16px' } } />
					{ __( 'Recovered Revenue', 'woocommerce-abandoned-cart' ) }
				</span>
			</div>
			<div className="wcap-dash-chart__canvas">
				{ labels.length > 0 ? (
					isLine
						? <Line data={ data } options={ options } />
						: <Bar  data={ data } options={ options } />
				) : (
					<div className="wcap-dash-chart__empty">{ __( 'No data for selected period.', 'woocommerce-abandoned-cart' ) }</div>
				) }
			</div>
		</div>
	);
}

// ── Product Report mini-table ─────────────────────────────────────────────────

function ProductReportTable( { rows, loading } ) {
	return (
		<div className="wcap-dash-table-wrap">
			<h3 className="wcap-dash-table-wrap__title">{ __( 'Product Report', 'woocommerce-abandoned-cart' ) }</h3>
			<table className="wcap-dash-table">
				<thead>
					<tr>
						<th>{ __( 'Product Name', 'woocommerce-abandoned-cart' ) } <span className="wcap-dash-table__sort">↑</span></th>
						<th>{ __( 'Number of Times Abandoned', 'woocommerce-abandoned-cart' ) } <span className="wcap-dash-table__sort">↑</span></th>
						<th>{ __( 'Number of Times Recovered', 'woocommerce-abandoned-cart' ) } <span className="wcap-dash-table__sort">↑</span></th>
					</tr>
				</thead>
				<tbody>
					{ loading && (
						<tr><td colSpan={ 3 } className="wcap-dash-table__center"><Spinner /></td></tr>
					) }
					{ ! loading && rows.length === 0 && (
						<tr><td colSpan={ 3 } className="wcap-dash-table__center wcap-dash-table__empty">{ __( 'No data found.', 'woocommerce-abandoned-cart' ) }</td></tr>
					) }
					{ ! loading && rows.map( ( row, i ) => (
						<tr key={ i }>
							<td>{ row.name ?? row.product_name ?? '—' }</td>
							<td>{ row.times_abandoned ?? 0 }</td>
							<td>{ row.times_recovered ?? 0 }</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
}

// ── Abandoned Cart Details mini-table ─────────────────────────────────────────

const STATUS_LABELS = {
	abandoned:   { label: __( 'Abandoned',   'woocommerce-abandoned-cart' ), cls: 'abandoned'   },
	recovered:   { label: __( 'Recovered',   'woocommerce-abandoned-cart' ), cls: 'recovered'   },
	unsubscribed:{ label: __( 'Unsubscribed','woocommerce-abandoned-cart' ), cls: 'in-progress' },
};

function formatDashDate( iso ) {
	if ( ! iso ) return '—';
	const d = new Date( iso );
	if ( isNaN( d ) ) return '—';
	// The API sends the site's local time in UTC fields.
	return d.toLocaleString( undefined, {
		year: 'numeric', month: 'short', day: 'numeric',
		hour: '2-digit', minute: '2-digit', timeZone: 'UTC',
	} );
}

function AbandonedCartTable( { rows, loading } ) {
	return (
		<div className="wcap-dash-table-wrap">
			<h3 className="wcap-dash-table-wrap__title">{ __( 'Abandoned Cart Details', 'woocommerce-abandoned-cart' ) }</h3>
			<table className="wcap-dash-table">
				<thead>
					<tr>
						<th>{ __( 'Customer Details', 'woocommerce-abandoned-cart' ) }</th>
						<th>{ __( 'Cart Total', 'woocommerce-abandoned-cart' ) }</th>
						<th>{ __( 'Abandoned Date / Time', 'woocommerce-abandoned-cart' ) }</th>
						<th>{ __( 'Cart Status', 'woocommerce-abandoned-cart' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ loading && (
						<tr><td colSpan={ 4 } className="wcap-dash-table__center"><Spinner /></td></tr>
					) }
					{ ! loading && rows.length === 0 && (
						<tr><td colSpan={ 4 } className="wcap-dash-table__center wcap-dash-table__empty">{ __( 'No data found.', 'woocommerce-abandoned-cart' ) }</td></tr>
					) }
					{ ! loading && rows.map( ( row, i ) => {
						const statusKey = row.unsubscribed ? 'unsubscribed' : ( row.status ?? 'abandoned' );
						const status    = STATUS_LABELS[ statusKey ] ?? STATUS_LABELS.abandoned;
						const name      = row.billing?.first_name
							? `${ row.billing.first_name } ${ row.billing.last_name ?? '' }`.trim()
							: ( row.email_id ?? '—' );
						const isGuest   = row.user_type === 'GUEST';
						const total     = row.totals?.formatted
							? row.totals.formatted
							: fmtCurrency( row.totals?.total ?? 0 );
						const itemCount = row.item_count ?? 0;
						return (
							<tr key={ i }>
								<td>
								{ name }
								{ isGuest && <><br /><span className="wcap-dash-table__sub">{ __( 'Guest', 'woocommerce-abandoned-cart' ) }</span></> }
							</td>
								<td>
									<span dangerouslySetInnerHTML={ { __html: total } } />
									{ itemCount > 0 && <><br /><span className="wcap-dash-table__sub">{ itemCount } { __( 'item(s)', 'woocommerce-abandoned-cart' ) }</span></> }
								</td>
								<td>{ formatDashDate( row.abandoned_cart_time_iso ) }</td>
								<td><span className={ `wcap-dash-status wcap-dash-status--${ status.cls }` }>{ status.label }</span></td>
							</tr>
						);
					} ) }
				</tbody>
			</table>
		</div>
	);
}

// ── Main component ────────────────────────────────────────────────────────────

export function Dashboard() {
	const [ duration,      setDuration      ] = useState( 'this_month' );
	const [ startDate,     setStartDate     ] = useState( '' );
	const [ endDate,       setEndDate       ] = useState( '' );
	const [ stats,         setStats         ] = useState( null );
	const [ monthlyGraph,  setMonthlyGraph  ] = useState( null );
	const [ productRows,   setProductRows   ] = useState( [] );
	const [ cartRows,      setCartRows      ] = useState( [] );
	const [ loadingStats,  setLoadingStats  ] = useState( false );
	const [ loadingProd,   setLoadingProd   ] = useState( false );
	const [ loadingCarts,  setLoadingCarts  ] = useState( false );
	const [ error,         setError         ] = useState( null );

	// fetch KPI + duration chart
	const fetchStats = useCallback( async ( dur, start = '', end = '' ) => {
		setLoadingStats( true );
		setError( null );
		try {
			let path = `/wcal/v1/dashboard?duration=${ dur }`;
			if ( dur === 'custom' && start && end ) {
				path += `&start_date=${ encodeURIComponent( start ) }&end_date=${ encodeURIComponent( end ) }`;
			}
			const res = await apiFetch( { path } );
			setStats( res?.data ?? res );
		} catch ( e ) {
			setError( e?.message || __( 'Failed to load dashboard data.', 'woocommerce-abandoned-cart' ) );
		} finally {
			setLoadingStats( false );
		}
	}, [] );

	// fetch monthly chart (fixed, no duration)
	const fetchMonthly = useCallback( async () => {
		try {
			const res = await apiFetch( { path: '/wcal/v1/dashboard/monthly-chart' } );
			setMonthlyGraph( ( res?.data ?? res )?.graph_data ?? null );
		} catch { /* silent */ }
	}, [] );

	// fetch product report (top 5)
	const fetchProducts = useCallback( async () => {
		setLoadingProd( true );
		try {
			const res = await apiFetch( { path: '/wcal/v1/product-report?per_page=5&page=1' } );
			setProductRows( res?.data ?? [] );
		} catch { setProductRows( [] ); }
		finally { setLoadingProd( false ); }
	}, [] );

	// fetch last 5 abandoned carts
	const fetchCarts = useCallback( async () => {
		setLoadingCarts( true );
		try {
			const res = await apiFetch( { path: '/wcal/v1/abandoned-carts?per_page=5&page=1' } );
			setCartRows( res?.data ?? [] );
		} catch { setCartRows( [] ); }
		finally { setLoadingCarts( false ); }
	}, [] );

	useEffect( () => {
		fetchStats( 'this_month' );
		fetchMonthly();
		fetchProducts();
		fetchCarts();
	}, [ fetchStats, fetchMonthly, fetchProducts, fetchCarts ] );

	function handleApply() {
		fetchStats( duration, startDate, endDate );
		fetchProducts();
	}

	const s = stats ?? {};

	return (
		<div className="wcap-page wcap-page--dashboard">

			{ /* ── Filter bar ── */ }
			<div className="wcap-dash-filters">
				<SelectControl
					value={ duration }
					options={ DURATION_OPTIONS }
					onChange={ setDuration }
					__nextHasNoMarginBottom
					className="wcap-dash-filters__select"
				/>
				{ duration === 'custom' && (
					<>
						<input
							type="date"
							className="wcap-dash-filters__date"
							value={ startDate }
							onChange={ ( e ) => setStartDate( e.target.value ) }
							aria-label={ __( 'Start date', 'woocommerce-abandoned-cart' ) }
						/>
						<span className="wcap-dash-filters__date-sep">{ __( 'to', 'woocommerce-abandoned-cart' ) }</span>
						<input
							type="date"
							className="wcap-dash-filters__date"
							value={ endDate }
							min={ startDate || undefined }
							onChange={ ( e ) => setEndDate( e.target.value ) }
							aria-label={ __( 'End date', 'woocommerce-abandoned-cart' ) }
						/>
					</>
				) }
				<button
					className="wcap-dash-filters__apply"
					onClick={ handleApply }
					disabled={ loadingStats || ( duration === 'custom' && ( ! startDate || ! endDate ) ) }
				>
					{ loadingStats ? __( 'Loading…', 'woocommerce-abandoned-cart' ) : __( 'Apply filters', 'woocommerce-abandoned-cart' ) }
				</button>
			</div>

			{ error && <div className="wcap-dash-error">{ error }</div> }

			{ loadingStats && ! stats && (
				<div className="wcap-dash-spinner"><Spinner /></div>
			) }

			{ /* ── 4 stat cards ── */ }
			<div className="wcap-dash-cards">
				<StatCard
					title={ __( 'Recovered Amount', 'woocommerce-abandoned-cart' ) }
					icon={ IconTrend }
					value={ fmtCurrency( s.recovered_amount ) }
					subStats={ [
						`${ fmtNum( s.recovered_orders ) } ${ __( 'Recovered Orders', 'woocommerce-abandoned-cart' ) }`,
						`${ fmtPct( s.recovery_rate_orders ) } ${ __( 'of Abandoned Carts Recovered', 'woocommerce-abandoned-cart' ) }`,
						`${ fmtPct( s.percent_of_sales ) } ${ __( 'of Total Revenue', 'woocommerce-abandoned-cart' ) }`,
					] }
				/>
				<StatCard
					title={ __( 'Abandoned Amount', 'woocommerce-abandoned-cart' ) }
					icon={ IconCart }
					value={ fmtCurrency( s.abandoned_amount ) }
					subStats={ [
						`${ fmtNum( s.abandoned_orders ) } ${ __( 'Abandoned Orders', 'woocommerce-abandoned-cart' ) }`,
					] }
				/>
				<StatCard
					title={ __( 'Number of Emails Sent', 'woocommerce-abandoned-cart' ) }
					icon={ IconMail }
					value={ fmtNum( s.emails_sent ) }
					subStats={ [
						<ProUpsell key="emails" text={ __( 'Upgrade to <a>Abandoned Cart Pro for WooCommerce</a> to view the number of emails opened and links clicked.', 'woocommerce-abandoned-cart' ) } />,
					] }
				/>
				<StatCard
					title={ __( 'Emails Captured', 'woocommerce-abandoned-cart' ) }
					icon={ IconBar }
					value={ fmtNum( s.guest_emails ) }
					subStats={ [
						`${ fmtNum( s.guest_emails ) } ${ __( 'Guest emails captured.', 'woocommerce-abandoned-cart' ) }`,
						<ProUpsell key="capture" text={ __( 'Upgrade to <a>Abandoned Cart Pro for WooCommerce</a> to capture more guest carts.', 'woocommerce-abandoned-cart' ) } />,
					] }
				/>
			</div>

			{ /* ── Charts ── */ }
			<RevenueChart
				title={ __( 'Abandoned Cart', 'woocommerce-abandoned-cart' ) }
				graphData={ s.graph_data ?? {} }
			/>
			<RevenueChart
				title={ __( 'Abandoned Cart', 'woocommerce-abandoned-cart' ) }
				graphData={ monthlyGraph ?? {} }
				chartType="line"
			/>

			{ /* ── Bottom tables ── */ }
			<div className="wcap-dash-tables">
				<ProductReportTable   rows={ productRows } loading={ loadingProd } />
				<AbandonedCartTable   rows={ cartRows }    loading={ loadingCarts } />
			</div>

		</div>
	);
}
