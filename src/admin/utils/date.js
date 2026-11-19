/**
 * Shared date-formatting utilities for the WC Abandon Cart Pro admin.
 *
 * abandoned_cart_time_iso is produced via gmdate() on current_time('timestamp'),
 * which stores WP-local time encoded as if it were UTC. Reading UTC getters from
 * the Date object recovers the original local time regardless of browser timezone.
 */

export function applyPhpFormat( d, fmt ) {
	const pad  = ( n ) => String( n ).padStart( 2, '0' );
	const h12  = d.getHours() % 12 || 12;
	const ampm = d.getHours() >= 12 ? 'pm' : 'am';
	const MONTHS_LONG  = [ 'January','February','March','April','May','June','July','August','September','October','November','December' ];
	const MONTHS_SHORT = [ 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec' ];
	const DAYS_LONG    = [ 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday' ];
	const DAYS_SHORT   = [ 'Sun','Mon','Tue','Wed','Thu','Fri','Sat' ];

	let out = '';
	for ( let i = 0; i < fmt.length; i++ ) {
		const c = fmt[ i ];
		if ( c === '\\' ) { out += fmt[ ++i ] ?? ''; continue; }
		switch ( c ) {
			case 'Y': out += d.getFullYear(); break;
			case 'y': out += String( d.getFullYear() ).slice( -2 ); break;
			case 'F': out += MONTHS_LONG[ d.getMonth() ]; break;
			case 'M': out += MONTHS_SHORT[ d.getMonth() ]; break;
			case 'm': out += pad( d.getMonth() + 1 ); break;
			case 'n': out += d.getMonth() + 1; break;
			case 'd': out += pad( d.getDate() ); break;
			case 'j': out += d.getDate(); break;
			case 'l': out += DAYS_LONG[ d.getDay() ]; break;
			case 'D': out += DAYS_SHORT[ d.getDay() ]; break;
			case 'H': out += pad( d.getHours() ); break;
			case 'G': out += d.getHours(); break;
			case 'h': out += pad( h12 ); break;
			case 'g': out += h12; break;
			case 'i': out += pad( d.getMinutes() ); break;
			case 's': out += pad( d.getSeconds() ); break;
			case 'a': out += ampm; break;
			case 'A': out += ampm.toUpperCase(); break;
			default:  out += c;
		}
	}
	return out;
}

/**
 * Format an abandoned_cart_time_iso string using WP date/time format settings.
 * Returns e.g. "September 22, 2024 | 6:00 am".
 */
export function formatAbandonedDate( isoStr ) {
	if ( ! isoStr ) return '—';
	const d       = new Date( isoStr );
	const dateFmt = window.wcalAdminData?.dateFormat || 'F j, Y';
	const timeFmt = window.wcalAdminData?.timeFormat || 'g:i a';
	const proxy = {
		getFullYear: () => d.getUTCFullYear(),
		getMonth:    () => d.getUTCMonth(),
		getDate:     () => d.getUTCDate(),
		getDay:      () => d.getUTCDay(),
		getHours:    () => d.getUTCHours(),
		getMinutes:  () => d.getUTCMinutes(),
		getSeconds:  () => d.getUTCSeconds(),
	};
	return applyPhpFormat( proxy, dateFmt ) + ' | ' + applyPhpFormat( proxy, timeFmt );
}

/**
 * Format a wall-clock ISO string (site local time encoded as UTC) as "date time".
 */
export function formatWallClock( isoStr ) {
	return formatAbandonedDate( isoStr ).replace( ' | ', ' ' );
}

/**
 * Y-m-d in the site's timezone, n days ago.
 */
export function siteDaysAgo( n = 0 ) {
	const offset = parseFloat( window.wcalAdminData?.gmtOffset ?? 0 ) * 3600000;
	return new Date( Date.now() + offset - n * 86400000 ).toISOString().slice( 0, 10 );
}
