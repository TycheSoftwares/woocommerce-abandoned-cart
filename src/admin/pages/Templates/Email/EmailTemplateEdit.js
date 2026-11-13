/**
 * Email Template Add / Edit page.
 *
 * Routes:
 *   #/email-template/email/add
 *   #/email-template/email/edit/:id
 *
 * REST:
 *   GET    /wcal/v1/templates/email/:id  — load
 *   POST   /wcal/v1/templates/email       — create
 *   PUT    /wcal/v1/templates/email/:id  — update
 *
 * Test emails go through the plugin's wcal_preview_email_sent AJAX action and
 * previews through its wcal_preview_mail / wcal_preview_woocommerce_mail URLs.
 *
 * Rules, Insert Template, Import Template and some merge codes are Pro-only.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	Card,
	Disabled,
	TextControl,
	SelectControl,
	ToggleControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { __ }        from '@wordpress/i18n';
import apiFetch      from '@wordpress/api-fetch';
import { useNavigate, useMatch } from 'react-router-dom';
import { SettingRow } from '../../../components/SettingsPanel';
import ProLockedRow, { ProBadge, noop } from '../../../components/ProLockedRow';
import { CouponSearchControl } from '../../../components/CouponSearchControl';
import WpEditor, { getEditorContent }    from '../../../components/WpEditor';
import { setupWcapEditorButtons }        from '../../../components/WpEditor/wcapTinyMcePlugins';
import PreviewEmailModal                 from './PreviewEmailModal';
import './email-template-edit.scss';

// ── TinyMCE toolbar configuration ─────────────────────────────────────────────
// Plugins (abandoncart_pro, abandoncart_pro_css) are registered inline in
// WpEditor/wcapTinyMcePlugins.js — no external .js files required.

const TINYMCE_EXTRA = {
	toolbar1: 'formatselect,bold,italic,underline,blockquote,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,wp_more,abandoncart_pro,abandoncart_pro_css',
	toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,fullscreen',
	// There's no wp_adv button to reveal the second row.
	wordpress_adv_hidden: false,
};

// ── Constants ──────────────────────────────────────────────────────────────────

const EDITOR_ID = 'woocommerce_ac_email_body';

const DEFAULT_HEADER = __( 'Abandoned cart reminder', 'woocommerce-abandoned-cart' );

const TEST_EMAIL_REGEX = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;

const DEFAULT = {
	template_name:               '',
	subject:                     '',
	body:                        '',
	frequency:                   1,
	day_or_hour:                 'Minutes',
	is_wc_template:              false,
	wc_email_header:             DEFAULT_HEADER,
	coupon_code:                 '',
	discount:                    '0',
	discount_type:               'percent',
	discount_shipping:           false,
	discount_expiry:             '7-days',
	individual_use:              true,
	generate_unique_coupon_code: false,
};

// ── Helpers ────────────────────────────────────────────────────────────────────

const openTrial = () => window.open( window.wcalAdminData?.trialUrl, '_blank', 'noopener' );

// discount_expiry is stored as {number}-{hours|days}.
const splitExpiry = ( expiry ) => {
	const [ number = '', unit = '' ] = String( expiry ?? '' ).split( '-' );
	return [ number, unit || 'hours' ];
};

// ── Rules, a Pro feature ───────────────────────────────────────────────────────

const LockedRules = () => (
	<div className="wcap-et-rules-section wcal-pro-locked">
		<span className="wcap-et-rules-section__title">
			{ __( 'Rules', 'woocommerce-abandoned-cart' ) } <ProBadge />
		</span>
		<Disabled>
			<div className="wcap-rules-section">
				<table className="wcap-rules-table">
					<thead>
						<tr>
							<th>{ __( 'Rule Type',  'woocommerce-abandoned-cart' ) }</th>
							<th>{ __( 'Conditions', 'woocommerce-abandoned-cart' ) }</th>
							<th>{ __( 'Values',     'woocommerce-abandoned-cart' ) }</th>
							<th>{ __( 'Actions',    'woocommerce-abandoned-cart' ) }</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colSpan={ 4 }>
								<Button variant="link" className="wcap-add-rule-btn">
									+ { __( 'Add Rule', 'woocommerce-abandoned-cart' ) }
								</Button>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</Disabled>
	</div>
);

// ── Main Component ─────────────────────────────────────────────────────────────

const EmailTemplateEdit = () => {
	const editMatch = useMatch( '/email-template/email/edit/:id' );
	const id        = editMatch?.params?.id;
	const navigate  = useNavigate();
	const isEdit    = !! id;

	const [ loading, setLoading ] = useState( isEdit );
	const [ saving,  setSaving  ] = useState( false );
	const [ notice,  setNotice  ] = useState( null );

	// Form state
	const [ form, setForm ] = useState( { ...DEFAULT } );

	// Partial updater
	const set = ( key ) => ( val ) => setForm( ( prev ) => ( { ...prev, [ key ]: val } ) );

	// Test email state
	const [ testEmail,   setTestEmail   ] = useState( window.wcalAdminData?.currentUserEmail ?? '' );
	const [ testSending, setTestSending ] = useState( false );
	const [ testNotice,  setTestNotice  ] = useState( null );

	// Sticky footer: track whether the inline footer sentinel is off-screen.
	const [ isFooterSticky, setIsFooterSticky ] = useState( false );
	const footerSentinelRef = useCallback( ( el ) => {
		if ( ! el ) return;
		const observer = new window.IntersectionObserver(
			( [ entry ] ) => setIsFooterSticky( ! entry.isIntersecting ),
			{ threshold: 0 }
		);
		observer.observe( el );
	}, [] );

	// Preview email modal state.
	const [ previewModal, setPreviewModal ] = useState( { open: false, type: 'wc' } );
	const openPreview = ( type ) => setPreviewModal( { open: true, type } );

	// ── Load template on edit ──────────────────────────────────────────────────

	useEffect( () => {
		if ( ! isEdit ) return;
		apiFetch( { path: `/wcal/v1/templates/email/${ id }` } )
			.then( ( res ) => {
				const t = res.data ?? res;
				setForm( {
					template_name:               t.template_name ?? '',
					subject:                     t.subject ?? '',
					body:                        t.body ?? '',
					frequency:                   t.frequency ?? 1,
					day_or_hour:                 t.day_or_hour ?? 'Minutes',
					is_wc_template:              !! t.is_wc_template,
					wc_email_header:             t.wc_email_header || DEFAULT_HEADER,
					coupon_code:                 t.coupon_code ?? '',
					discount:                    t.discount ?? '',
					discount_type:               t.discount_type === 'fixed' ? 'fixed' : 'percent',
					discount_shipping:           !! t.discount_shipping,
					discount_expiry:             t.discount_expiry ?? '',
					individual_use:              !! t.individual_use,
					generate_unique_coupon_code: !! t.generate_unique_coupon_code,
				} );
			} )
			.catch( () => setNotice( { status: 'error', message: __( 'Failed to load template.', 'woocommerce-abandoned-cart' ) } ) )
			.finally( () => setLoading( false ) );
	}, [ id ] ); // eslint-disable-line react-hooks/exhaustive-deps

	// ── Save ──────────────────────────────────────────────────────────────────

	const handleSave = async () => {
		setSaving( true );
		setNotice( null );
		try {
			const payload = {
				...form,
				body:      getEditorContent( EDITOR_ID, form.body ),
				frequency: Number( form.frequency ),
			};

			if ( isEdit ) {
				await apiFetch( { path: `/wcal/v1/templates/email/${ id }`, method: 'PUT', data: payload } );
				setNotice( { status: 'success', message: __( 'The Email Template has been successfully updated.', 'woocommerce-abandoned-cart' ) } );
				setTimeout( () => setNotice( null ), 3000 );
			} else {
				const res   = await apiFetch( { path: '/wcal/v1/templates/email', method: 'POST', data: payload } );
				const newId = ( res.data ?? res ).id;
				navigate( `/email-template/email/edit/${ newId }`, { replace: true } );
				setNotice( { status: 'success', message: __( 'The Email Template has been successfully added. In order to start sending this email to your customers, please activate it.', 'woocommerce-abandoned-cart' ) } );
			}
		} catch ( e ) {
			setNotice( { status: 'error', message: e?.message || __( 'Failed to save template.', 'woocommerce-abandoned-cart' ) } );
		} finally {
			setSaving( false );
		}
	};

	// ── Test email ────────────────────────────────────────────────────────────

	// Sends what's in the form, saved or not, and resolves to a notice.
	const sendTest = async ( email, isWcTemplate ) => {
		if ( ! TEST_EMAIL_REGEX.test( email ) ) {
			return { status: 'error', message: __( 'Please enter a valid email.', 'woocommerce-abandoned-cart' ) };
		}

		const body = getEditorContent( EDITOR_ID, form.body );
		if ( ! body ) {
			return { status: 'error', message: __( 'Test email is not sent as the email body is empty.', 'woocommerce-abandoned-cart' ) };
		}

		const params = new window.FormData();
		params.append( 'action', 'wcal_preview_email_sent' );
		params.append( 'ajax_nonce', window.wcalAdminData?.testEmailNonce ?? '' );
		params.append( 'subject_email_preview', form.subject );
		params.append( 'body_email_preview', body );
		params.append( 'send_email_id', email );
		params.append( 'is_wc_template', isWcTemplate ? 'true' : 'false' );
		params.append( 'wc_template_header', form.wc_email_header || __( 'Abandoned Cart Reminder', 'woocommerce-abandoned-cart' ) );
		params.append( 'discount_expiry', form.discount_expiry );
		params.append( 'discount_type', form.discount_type );
		params.append( 'discount_shipping', form.discount_shipping ? 'yes' : 'no' );
		params.append( 'individual_use', form.individual_use ? '1' : '0' );
		params.append( 'discount_amount', form.discount );
		params.append( 'generate_unique_code', form.generate_unique_coupon_code ? '1' : '0' );
		params.append( 'default_template', '' );
		form.coupon_code.split( ',' ).filter( Boolean ).forEach( ( couponId ) => params.append( 'coupon_code[]', couponId ) );

		const failed = { status: 'error', message: __( 'Failed to send test email.', 'woocommerce-abandoned-cart' ) };
		try {
			const res  = await window.fetch( `${ window.wcalAdminData?.adminUrl }admin-ajax.php`, {
				method:      'POST',
				credentials: 'same-origin',
				body:        params,
			} );
			const text = await res.text();
			return text.includes( 'email sent' )
				? { status: 'success', message: __( 'Email has been sent successfully.', 'woocommerce-abandoned-cart' ) }
				: failed;
		} catch {
			return failed;
		}
	};

	const handleSendTest = async () => {
		setTestSending( true );
		setTestNotice( null );
		setTestNotice( await sendTest( testEmail, form.is_wc_template ) );
		setTestSending( false );
	};

	const handleBack = () => navigate( '/email-template/email' );

	// ── Render ────────────────────────────────────────────────────────────────

	if ( loading ) {
		return <div className="wcap-et-edit-loading"><Spinner /></div>;
	}

	const isUniqueCoupon = form.generate_unique_coupon_code;

	const [ expiryNumber, expiryUnit ] = splitExpiry( form.discount_expiry );

	const frequencyOptions = Array.from( { length: 59 }, ( _, i ) => {
		const v = String( i + 1 );
		return { value: v, label: v };
	} );

	return (
		<div className="wcap-et-edit">

			<h2 className="wcap-et-edit__title">
				{ isEdit ? __( 'Edit Template', 'woocommerce-abandoned-cart' ) : __( 'Add Template', 'woocommerce-abandoned-cart' ) }
			</h2>

			{ notice && (
				<Notice
					status={ notice.status }
					isDismissible
					onDismiss={ () => setNotice( null ) }
					className="wcap-et-edit__notice"
				>
					{ notice.message }
				</Notice>
			) }

			{ /* Single card */ }
			<Card size="none" className="wcap-sp-card wcap-et-card">

				{ /* Match Rules */ }
				<ProLockedRow
					label={ __( 'Match Rules', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Whether the cart must match all rules or any rule.', 'woocommerce-abandoned-cart' ) }
				>
					<SelectControl
						value="all"
						options={ [
							{ value: 'all', label: __( 'Match all rules',   'woocommerce-abandoned-cart' ) },
							{ value: 'any', label: __( 'Match any rule(s)', 'woocommerce-abandoned-cart' ) },
						] }
						onChange={ noop }
						__nextHasNoMarginBottom
					/>
				</ProLockedRow>

				{ /* Rules table — full width, no label column */ }
				<LockedRules />

				{ /* Template Name */ }
				<SettingRow
					label={ __( 'Template Name', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Enter a template name for reference', 'woocommerce-abandoned-cart' ) }
				>
					<TextControl
						value={ form.template_name }
						onChange={ set( 'template_name' ) }
						placeholder={ __( 'Enter template name…', 'woocommerce-abandoned-cart' ) }
						__nextHasNoMarginBottom
					/>
				</SettingRow>

				{ /* Send This Email */ }
				<SettingRow label={ __( 'Send this Email', 'woocommerce-abandoned-cart' ) }>
					<div className="wcap-et-send-after">
						<SelectControl
							value={ String( form.frequency ) }
							options={ frequencyOptions }
							onChange={ ( val ) => set( 'frequency' )( Number( val ) ) }
							__nextHasNoMarginBottom
						/>
						<SelectControl
							className="wcap-et-unit-select"
							value={ form.day_or_hour }
							options={ [
								{ value: 'Minutes', label: __( 'Minutes', 'woocommerce-abandoned-cart' ) },
								{ value: 'Hours',   label: __( 'Hours',   'woocommerce-abandoned-cart' ) },
								{ value: 'Days',    label: __( 'Days',    'woocommerce-abandoned-cart' ) },
							] }
							onChange={ set( 'day_or_hour' ) }
							__nextHasNoMarginBottom
						/>
						<span className="wcap-et-send-after__suffix">
							{ __( 'after cart is abandoned.', 'woocommerce-abandoned-cart' ) }
						</span>
					</div>
				</SettingRow>

				{ /* Subject */ }
				<SettingRow
					label={ __( 'Subject', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Enter the subject that should appear in the email sent', 'woocommerce-abandoned-cart' ) }
					alignTop
				>
					<TextControl
						value={ form.subject }
						onChange={ set( 'subject' ) }
						placeholder={ __( 'Email subject…', 'woocommerce-abandoned-cart' ) }
						__nextHasNoMarginBottom
					/>
					<p className="wcap-et-merge-hint">
						{ __( 'Add the shortcode {{customer.firstname}} or {{product.name}} to include the Customer First Name and Product name ( first in the cart ) to the Subject Line. For e.g. Hi John!! You left some Protein Bread in your cart', 'woocommerce-abandoned-cart' ) }
					</p>
				</SettingRow>

				{ /* Email Body */ }
				<SettingRow
					label={ __( 'Email Body', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Message to be sent in the reminder email.', 'woocommerce-abandoned-cart' ) }
					alignTop
				>
					<WpEditor
						id={ EDITOR_ID }
						value={ form.body }
						onChange={ set( 'body' ) }
						tinymceExtra={ TINYMCE_EXTRA }
						onEditorSetup={ setupWcapEditorButtons }
						extraMediaButtons={ [
							{
								label:     `${ __( 'Insert Template', 'woocommerce-abandoned-cart' ) } (${ __( 'PRO', 'woocommerce-abandoned-cart' ) })`,
								className: 'button wcal-locked-media-button',
								onClick:   openTrial,
							},
							{
								label:     `${ __( 'Import Template', 'woocommerce-abandoned-cart' ) } (${ __( 'PRO', 'woocommerce-abandoned-cart' ) })`,
								className: 'button wcal-locked-media-button',
								onClick:   openTrial,
							},
						] }
					/>
					<div className="wcap-et-merge-hint">
						<p>
							{ __( '1. You can add customer & cart information in the template using this icon', 'woocommerce-abandoned-cart' ) }
							{ ' ' }
							<img src={ `${ window.wcalAdminData?.pluginUrl }/assets/images/ac_editor_icon.png` } alt="" />
							{ ' ' }
							{ __( 'in top left of the editor.', 'woocommerce-abandoned-cart' ) }
						</p>
						<p>{ __( '2. The product information/cart contents table will be added in emails using the {{products.cart}} merge field.', 'woocommerce-abandoned-cart' ) }</p>
						<p>{ __( '3. Insert/Remove any of the new shortcodes that have been included for the default template.', 'woocommerce-abandoned-cart' ) }</p>
						<p>{ __( '4. Change the look and feel of the table by modifying the table style properties using CSS in "Text" mode.', 'woocommerce-abandoned-cart' ) }</p>
						<p>{ __( '5. Change the text color of the table rows by using the Toolbar of the editor.', 'woocommerce-abandoned-cart' ) }</p>
					</div>
				</SettingRow>

				{ /* Use WooCommerce Template Style */ }
				<SettingRow
					label={ __( 'Use WooCommerce Template Style', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Use WooCommerce default style template for abandoned cart reminder emails.', 'woocommerce-abandoned-cart' ) }
					alignTop
				>
					<ToggleControl
						checked={ form.is_wc_template }
						onChange={ set( 'is_wc_template' ) }
						__nextHasNoMarginBottom
					/>
					<p className="wcap-et-merge-hint">
						{ __( 'Note: When this setting is enabled, then "Send From This Name:" & "Send From This Email Address:" will be overwritten with WooCommerce -> Settings -> Email -> Email Sender Options.', 'woocommerce-abandoned-cart' ) }
					</p>
					<div className="wcap-et-preview-btns">
						<Button variant="secondary" onClick={ () => openPreview( 'wc' ) }>
							{ __( 'Preview WooCommerce Email', 'woocommerce-abandoned-cart' ) }
						</Button>
						<Button variant="secondary" onClick={ () => openPreview( 'custom' ) }>
							{ __( 'Preview Custom Email', 'woocommerce-abandoned-cart' ) }
						</Button>
					</div>
				</SettingRow>

				{ /* Email Template Header Text (always visible) */ }
				<SettingRow
					label={ __( 'Email Template Header Text', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Enter the header which will appear in the abandoned WooCommerce email sent. This is only applicable when only used when "Use WooCommerce Template Style:" is checked.', 'woocommerce-abandoned-cart' ) }
				>
					<TextControl
						value={ form.wc_email_header }
						onChange={ set( 'wc_email_header' ) }
						__nextHasNoMarginBottom
					/>
				</SettingRow>

				{ /* Generate Unique Coupon Codes */ }
				<SettingRow
					label={ __( 'Generate unique coupon codes', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Replace this coupon with unique coupon codes for each customer', 'woocommerce-abandoned-cart' ) }
				>
					<ToggleControl
						checked={ isUniqueCoupon }
						onChange={ set( 'generate_unique_coupon_code' ) }
						__nextHasNoMarginBottom
					/>
				</SettingRow>

				{ isUniqueCoupon && (
					<>
						<SettingRow label={ __( 'Discount Type', 'woocommerce-abandoned-cart' ) }>
							<SelectControl
								value={ form.discount_type }
								options={ [
									{ value: 'percent', label: __( 'Percentage discount', 'woocommerce-abandoned-cart' ) },
									{ value: 'fixed',   label: __( 'Fixed cart discount', 'woocommerce-abandoned-cart' ) },
								] }
								onChange={ set( 'discount_type' ) }
								__nextHasNoMarginBottom
							/>
						</SettingRow>

						<SettingRow
							label={ __( 'Coupon amount', 'woocommerce-abandoned-cart' ) }
							tooltip={ __( 'Value of the coupon.', 'woocommerce-abandoned-cart' ) }
						>
							<TextControl
								type="number"
								value={ form.discount }
								onChange={ set( 'discount' ) }
								placeholder="0"
								min={ 0 }
								__nextHasNoMarginBottom
							/>
						</SettingRow>

						<SettingRow
							label={ __( 'Allow free shipping', 'woocommerce-abandoned-cart' ) }
							tooltip={ __( 'Check this box if the coupon grants free shipping. A free shipping method must be enabled in your shipping zone and be set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'woocommerce-abandoned-cart' ) }
						>
							<ToggleControl
								checked={ form.discount_shipping }
								onChange={ set( 'discount_shipping' ) }
								__nextHasNoMarginBottom
							/>
						</SettingRow>

						<SettingRow
							label={ __( 'Coupon validity', 'woocommerce-abandoned-cart' ) }
							tooltip={ __( 'The coupon code which will be sent in the reminder emails will be expired based the validity set here. E.g if the coupon code sent in the reminder email should be expired after 7 days then set 7 Day(s) for this option.', 'woocommerce-abandoned-cart' ) }
						>
							<div className="wcap-et-expiry-row">
								<TextControl
									type="number"
									value={ expiryNumber }
									onChange={ ( v ) => set( 'discount_expiry' )( `${ v.replace( /\D/g, '' ) }-${ expiryUnit }` ) }
									placeholder="0"
									min={ 0 }
									__nextHasNoMarginBottom
								/>
								<SelectControl
									value={ expiryUnit }
									options={ [
										{ value: 'hours', label: __( 'Hour(s)', 'woocommerce-abandoned-cart' ) },
										{ value: 'days',  label: __( 'Day(s)',  'woocommerce-abandoned-cart' ) },
									] }
									onChange={ ( v ) => set( 'discount_expiry' )( `${ expiryNumber }-${ v }` ) }
									__nextHasNoMarginBottom
								/>
							</div>
						</SettingRow>

						<SettingRow
							label={ __( 'Individual use only', 'woocommerce-abandoned-cart' ) }
							tooltip={ __( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce-abandoned-cart' ) }
						>
							<ToggleControl
								checked={ form.individual_use }
								onChange={ set( 'individual_use' ) }
								__nextHasNoMarginBottom
							/>
						</SettingRow>
					</>
				) }

				{ /* OR divider */ }
				<div className="wcap-et-or-divider">
					<span>{ __( 'OR', 'woocommerce-abandoned-cart' ) }</span>
				</div>

				{ /* Coupon Code (existing) */ }
				<SettingRow
					label={ __( 'Enter a coupon code to add into email', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Search & select one coupon code that customers should use to get a discount. Generated coupon code which will be sent in email reminder will have the settings of coupon selected in this option.', 'woocommerce-abandoned-cart' ) }
				>
					<CouponSearchControl
						value={ form.coupon_code }
						onChange={ set( 'coupon_code' ) }
					/>
				</SettingRow>

				{ /* Send a test email */ }
				<SettingRow
					label={ __( 'Send a test email to', 'woocommerce-abandoned-cart' ) }
					tooltip={ __( 'Enter the email id to which the test email needs to be sent.', 'woocommerce-abandoned-cart' ) }
				>
					<div className="wcap-et-test-email">
						<div className="wcap-et-test-email__input">
							<TextControl
								type="email"
								value={ testEmail }
								onChange={ setTestEmail }
								placeholder={ __( 'you@example.com', 'woocommerce-abandoned-cart' ) }
								__nextHasNoMarginBottom
							/>
						</div>
						<Button
							className="wcap-send-test-btn"
							onClick={ handleSendTest }
							disabled={ testSending || ! testEmail }
							isBusy={ testSending }
						>
							{ __( 'Send a test email', 'woocommerce-abandoned-cart' ) }
						</Button>
					</div>
					{ testNotice && (
						<Notice
							status={ testNotice.status }
							isDismissible
							onDismiss={ () => setTestNotice( null ) }
							style={ { marginTop: '8px' } }
						>
							{ testNotice.message }
						</Notice>
					) }
				</SettingRow>

			</Card>

			{ /* Inline footer — acts as scroll sentinel */ }
			<div className="wcap-et-footer-inline" ref={ footerSentinelRef }>
				<Button className="wcap-edit-back-btn" onClick={ handleBack }>
					{ __( 'Back', 'woocommerce-abandoned-cart' ) }
				</Button>
				<div className="wcap-et-footer-inline__right">
					{ notice && (
						<span className={ `wcap-et-footer-notice wcap-et-footer-notice--${ notice.status }` }>
							{ notice.message }
						</span>
					) }
					<Button className="wcap-sp-save-btn" onClick={ handleSave } isBusy={ saving } disabled={ saving }>
						{ __( 'Save Settings', 'woocommerce-abandoned-cart' ) }
					</Button>
				</div>
			</div>

			{ /* Fixed footer — visible only when inline footer has scrolled out of view */ }
			{ isFooterSticky && (
				<div className="wcap-et-sticky-footer">
					<Button className="wcap-edit-back-btn" onClick={ handleBack }>
						{ __( 'Back', 'woocommerce-abandoned-cart' ) }
					</Button>
					<div className="wcap-et-footer-inline__right">
						{ notice && (
							<span className={ `wcap-et-footer-notice wcap-et-footer-notice--${ notice.status }` }>
								{ notice.message }
							</span>
						) }
						<Button className="wcap-sp-save-btn" onClick={ handleSave } isBusy={ saving } disabled={ saving }>
							{ __( 'Save Settings', 'woocommerce-abandoned-cart' ) }
						</Button>
					</div>
				</div>
			) }

			{ /* Preview Email Modal */ }
			<PreviewEmailModal
				isOpen={ previewModal.open }
				onClose={ () => setPreviewModal( ( prev ) => ( { ...prev, open: false } ) ) }
				templateId={ id }
				onSendTest={ sendTest }
				previewType={ previewModal.type }
			/>

		</div>
	);
};

export default EmailTemplateEdit;
