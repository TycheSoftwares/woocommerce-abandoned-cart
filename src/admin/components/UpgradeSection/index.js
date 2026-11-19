/**
 * Shows a Pro-only section: the plugin's existing section template, with the
 * existing upgrade modal over it.
 *
 * The HTML and its stylesheets are the ones the plugin has always used. They
 * include Bootstrap, so they are kept in a shadow root where they cannot affect
 * the rest of the app.
 */

/**
 * WordPress dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ajaxPost } from '../../api/ajaxFetch';

const { proSectionNonce, proSectionStyles = [] } = window.wcalAdminData ?? {};

export function UpgradeSection( { section } ) {
	const hostRef = useRef( null );
	const [ ready, setReady ] = useState( false );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		let active = true;
		const host = hostRef.current;
		const shadow = host.shadowRoot || host.attachShadow( { mode: 'open' } );

		setReady( false );
		setError( null );
		shadow.innerHTML = '';

		ajaxPost( 'wcal_pro_section', { section }, { nonce: proSectionNonce, nonceKey: 'nonce' } )
			.then( ( res ) => {
				if ( ! active ) {
					return;
				}
				if ( ! res?.success ) {
					throw new Error( res?.data?.message );
				}
				// Wait for the stylesheets, so the page never shows the section unstyled.
				const loaded = proSectionStyles.map( ( href ) => new Promise( ( resolve ) => {
					const link = document.createElement( 'link' );
					link.rel = 'stylesheet';
					link.href = href;
					link.onload = resolve;
					link.onerror = resolve;
					shadow.appendChild( link );
				} ) );
				const content = document.createElement( 'div' );
				content.innerHTML = res.data.html;
				shadow.appendChild( content );
				return Promise.all( loaded ).then( () => active && setReady( true ) );
			} )
			.catch( ( e ) => {
				if ( active ) {
					setError( e?.message || __( 'Failed to load this section. Please try again.', 'woocommerce-abandoned-cart' ) );
				}
			} );

		return () => {
			active = false;
		};
	}, [ section ] );

	return (
		<>
			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
			{ ! ready && ! error && <div style={ { padding: '40px', textAlign: 'center' } }><Spinner /></div> }
			<div ref={ hostRef } style={ { display: ready ? 'block' : 'none' } } />
		</>
	);
}
