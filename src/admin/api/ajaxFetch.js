/**
 * Wrapper for wp_ajax_* endpoints.
 * Copied from the Pro plugin: src/admin/api/ajaxFetch.js
 */

const AJAX_URL = window.ajaxurl ?? '/wp-admin/admin-ajax.php';

/**
 * POST to wp-admin/admin-ajax.php.
 *
 * @param {string} action           The wp_ajax_{action} hook name.
 * @param {Object} params           Key/value pairs to include in the FormData body.
 * @param {Object} options          Nonce details.
 * @param {string} options.nonce    Nonce value.
 * @param {string} options.nonceKey FormData key for the nonce.
 * @return {Promise<any>} Parsed JSON response data.
 */
export async function ajaxPost( action, params = {}, { nonce, nonceKey } ) {
	const body = new FormData();
	body.append( 'action', action );
	body.append( nonceKey, nonce );

	Object.entries( params ).forEach( ( [ key, value ] ) => {
		body.append( key, value );
	} );

	const response = await fetch( AJAX_URL, { method: 'POST', body } );

	if ( ! response.ok ) {
		throw new Error( `AJAX request failed: ${ response.status } ${ response.statusText }` );
	}

	return response.json();
}
