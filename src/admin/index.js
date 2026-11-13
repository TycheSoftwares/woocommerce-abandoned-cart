/**
 * Admin SPA entry point.
 *
 * Bootstraps the React app into #wcal-admin-root, same as the Pro plugin's
 * src/admin/index.js. All routing logic lives in app.js.
 */

/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * External dependencies
 */
import { HashRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import App from './app';
import './admin.scss';

window.addEventListener( 'load', () => {
	const container = document.getElementById( 'wcal-admin-root' );
	if ( container ) {
		createRoot( container ).render(
			<HashRouter>
				<App />
			</HashRouter>
		);
	}
} );
