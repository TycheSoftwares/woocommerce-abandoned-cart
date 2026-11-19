/**
 * Header navigation, based on the Pro plugin's components/Header/index.js.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * External dependencies
 */
import { NavLink } from 'react-router-dom';

/**
 * Internal dependencies
 */
import './header.scss';

const NAV_ITEMS = [
	{ key: 'dashboard', label: __( 'Dashboard', 'woocommerce-abandoned-cart' ), path: '/dashboard' },
	{ key: 'abandoned-orders', label: __( 'Abandoned Orders', 'woocommerce-abandoned-cart' ), path: '/abandoned-orders' },
	{ key: 'email-template', label: __( 'Templates', 'woocommerce-abandoned-cart' ), path: '/email-template' },
	{ key: 'settings', label: __( 'Settings', 'woocommerce-abandoned-cart' ), path: '/settings' },
	{ key: 'reminders', label: __( 'Reminders Sent', 'woocommerce-abandoned-cart' ), path: '/reminders' },
	{ key: 'product-reports', label: __( 'Product Report', 'woocommerce-abandoned-cart' ), path: '/product-reports' },
	{ key: 'faq-support', label: __( 'FAQ & Support', 'woocommerce-abandoned-cart' ), path: '/faq-support' },
	{ key: 'upgrade-to-pro', label: __( 'Upgrade to Pro', 'woocommerce-abandoned-cart' ), path: '/upgrade-to-pro' },
];

const data = window.wcalAdminData || {};
const pluginUrl = data.pluginUrl || '';
const version = data.version || '';
const logoUrl = pluginUrl ? `${ pluginUrl }/assets/images/ac-logo.svg` : '';

export function Header() {
	const [ menuOpen, setMenuOpen ] = useState( false );

	return (
		<div className="wcap-header">
			<div className="wcap-header__wrap">

				{ /* Logo */ }
				{ logoUrl && (
					<div className="wcap-header__branding">
						<img src={ logoUrl } alt={ __( 'Tyche Softwares', 'woocommerce-abandoned-cart' ) } />
					</div>
				) }

				{ /* Nav */ }
				<nav className="wcap-header__nav">
					<Button
						className="wcap-header__toggler"
						onClick={ () => setMenuOpen( ( open ) => ! open ) }
						aria-expanded={ menuOpen }
						aria-label={ __( 'Toggle navigation', 'woocommerce-abandoned-cart' ) }
					>
						<span className="wcap-header__toggler-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
								<path fill="none" d="M0 0h24v24H0z" />
								<path d="M3 4h18v2H3V4zm0 7h12v2H3v-2zm0 7h18v2H3v-2z" fill="rgba(255,255,255,1)" />
							</svg>
						</span>
					</Button>

					<div className={ `wcap-header__collapse${ menuOpen ? ' wcap-header__collapse--open' : '' }` }>
						<ul className="wcap-header__menu">
							{ NAV_ITEMS.map( ( item ) => (
								<li key={ item.key }>
									<NavLink
										to={ item.path }
										className={ ( { isActive } ) =>
											'wcap-header__link' + ( isActive ? ' active' : '' )
										}
									>
										{ item.label }
									</NavLink>
								</li>
							) ) }
						</ul>
					</div>
				</nav>

				{ /* Version */ }
				{ version && (
					<div className="wcap-header__version">
						<span>{ __( 'Abandoned Cart Lite', 'woocommerce-abandoned-cart' ) }</span>
						<span>{ `Version ${ version }` }</span>
					</div>
				) }

			</div>
		</div>
	);
}
