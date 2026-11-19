/**
 * Adds the two custom toolbar buttons to a TinyMCE editor instance.
 *
 * Call this inside TinyMCE's `setup` callback:
 *
 *   setup( editor ) {
 *     setupWcapEditorButtons( editor );
 *   }
 *
 * Using `editor.addButton()` inside `setup` is the canonical TinyMCE 4 way
 * to register custom toolbar buttons — no PluginManager, no external files,
 * no entry in the `plugins` init string required.
 *
 * Only the merge codes this plugin replaces can be inserted. The rest of the
 * Pro plugin's list, and its cart / checkout buttons, are shown as Pro features.
 */

import { __ } from '@wordpress/i18n';

const MERGE_CODES = [
	{ text: 'Customer First Name',              value: '{{customer.firstname}}' },
	{ text: 'Customer Last Name',               value: '{{customer.lastname}}' },
	{ text: 'Customer Full Name',               value: '{{customer.fullname}}' },
	{ text: 'Product Information/Cart Content', value: '{{products.cart}}' },
	{ text: 'Cart Link',                        value: '{{cart.link}}' },
	{ text: 'Date when Cart was abandoned',     value: '{{cart.abandoned_date}}' },
	{ text: 'Unsubscribe Link',                 value: '{{cart.unsubscribe}}' },
	{ text: 'Coupon Code',                      value: '{{coupon.code}}' },
];

const PRO_MERGE_CODES = [
	'Admin Phone Number',
	'Cart Total',
	'Checkout Link',
	'Cross sells',
	'Customer e-mail address',
	'Customer Phone Number',
	'Product Image',
	'Product Name',
	'Product Price',
	'Product Quantity',
	'Product Subtotal',
	'Shop Name',
	'Shop URL',
	'Store Address',
	'Upsells',
	'Custom Merge Tag',
];

const openTrial = () => window.open( window.wcalAdminData?.trialUrl, '_blank', 'noopener' );

export function setupWcapEditorButtons( editor ) {
	// ── AC dropdown — merge codes ──────────────────────────────────────────────
	editor.addButton( 'abandoncart_pro', {
		type: 'menubutton',
		text: false,
		icon: 'abandoncart_email_variables_pro',
		menu: [
			...MERGE_CODES.map( ( item ) => ( {
				text: item.text,
				onclick() {
					editor.insertContent( item.value );
				},
			} ) ),
			...PRO_MERGE_CODES.map( ( text ) => ( {
				text: `${ text } (${ __( 'PRO', 'woocommerce-abandoned-cart' ) })`,
				disabled: true,
			} ) ),
		],
	} );

	// ── BUTTONS — cart / checkout HTML buttons ─────────────────────────────────
	editor.addButton( 'abandoncart_pro_css', {
		text: false,
		icon: 'abandoncart_email_variables_css_pro',
		tooltip: __( 'Cart and checkout buttons are available in the Pro version', 'woocommerce-abandoned-cart' ),
		onclick: openTrial,
	} );
}
