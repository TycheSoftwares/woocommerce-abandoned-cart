/**
 * Page footer, based on the Pro plugin's components/Footer/index.js.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';

const { docsUrl: DOCS_URL, supportUrl: SUPPORT_URL, rateUrl: RATE_URL } = window.wcalAdminData ?? {};

export function Footer() {
	return (
		<div className="wcap-footer">
			<Notice status="info" isDismissible={ false }>
				<Icon icon={ info } size={ 18 } className="wcap-footer__notice-icon" />
				<a href={ DOCS_URL } target="_blank" rel="noreferrer">
					{ __( 'Browse the full documentation', 'woocommerce-abandoned-cart' ) }
				</a>
				{ __( ' to get the most out of Abandoned Cart Lite for WooCommerce.', 'woocommerce-abandoned-cart' ) }
			</Notice>
			<p className="wcap-footer__support">
				<a href={ SUPPORT_URL } target="_blank" rel="noreferrer">
					{ __( 'Need Support?', 'woocommerce-abandoned-cart' ) }
				</a>
				{ ' ' }
				<strong>{ __( "We're always happy to help you.", 'woocommerce-abandoned-cart' ) }</strong>
			</p>
			<p className="wcap-footer__rate">
				{ __( 'If this plugin helped you,', 'woocommerce-abandoned-cart' ) }
				{ ' ' }
				<a href={ RATE_URL } target="_blank" rel="noreferrer">
					{ __( 'please rate it', 'woocommerce-abandoned-cart' ) }
				</a>
				{ ' ' }
				<span className="wcap-footer__stars" aria-hidden="true">★★★★★</span>
			</p>
		</div>
	);
}
