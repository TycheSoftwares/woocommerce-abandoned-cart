/**
 * REST API wrapper using @wordpress/api-fetch.
 * Handles nonce injection automatically via the wp-api-fetch middleware.
 *
 * Usage:
 *   import { restGet, restPost, restPatch, restDelete } from './api/apiFetch';
 *   const data = await restGet( '/wcal/v1/abandoned-carts' );
 */
import apiFetch from '@wordpress/api-fetch';

const BASE = window.wcalAdminData?.restUrl ?? '/wp-json/wcal/v1/';

/**
 * Resolve a path to a full REST URL.
 * Accepts either a full URL or a path relative to wcal/v1/.
 */
function resolveUrl( path ) {
	if ( path.startsWith( 'http' ) ) {
		return path;
	}
	// Strip leading slash so we don't double-up
	return BASE.replace( /\/$/, '' ) + '/' + path.replace( /^\//, '' );
}

export function restGet( path, options = {} ) {
	return apiFetch( { url: resolveUrl( path ), method: 'GET', ...options } );
}

export function restPost( path, data, options = {} ) {
	return apiFetch( { url: resolveUrl( path ), method: 'POST', data, ...options } );
}

export function restPatch( path, data, options = {} ) {
	return apiFetch( { url: resolveUrl( path ), method: 'PATCH', data, ...options } );
}

export function restDelete( path, options = {} ) {
	return apiFetch( { url: resolveUrl( path ), method: 'DELETE', ...options } );
}
