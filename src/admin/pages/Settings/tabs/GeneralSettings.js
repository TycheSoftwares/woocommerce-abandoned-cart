/**
 * Settings > General tab.
 * Same panels, order and "Save All" as the Pro plugin's
 * src/admin/pages/Settings/tabs/GeneralSettings.js, with the settings of this
 * plugin editable and the rest shown as Pro-only.
 */

/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { Button, FormTokenField, Notice, SelectControl, Spinner, TextareaControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { ajaxPost } from '../../../api/ajaxFetch';
import { AccordionCard, SettingRow, ToggleGrid } from '../../../components/SettingsPanel';
import ProLockedRow, { LockedText, LockedToggle, noop } from '../../../components/ProLockedRow';
import SettingField from '../SettingField';
import { useSettingsPanel } from '../useSettingsPanel';

const { canSave, fields, countries, intro, trialUrl } = window.wcalAdminData ?? {};

const Loading = () => <div style={ { padding: '24px', textAlign: 'center' } }><Spinner /></div>;

// ── Panel 1 — Email Settings ──────────────────────────────────────────────────

const EMAIL_DEFAULTS = {
	wcal_enable_cart_emails: false,
	ac_lite_email_admin_on_recovery: false,
	wcap_email_admin_on_abandonment: false,
};

function EmailPanel( { saveRef, onNotice, initialData } ) {
	const { control, loading, saving, saveCard } =
		useSettingsPanel( '/wcal/v1/settings/general/email', EMAIL_DEFAULTS, saveRef, onNotice, initialData );

	if ( loading ) {
		return <Loading />;
	}

	return (
		<AccordionCard
			title={ __( 'Email Settings', 'woocommerce-abandoned-cart' ) }
			onSave={ canSave ? saveCard : undefined }
			saving={ saving }
			defaultOpen={ false }
		>
			<SettingField name="wcal_enable_cart_emails" control={ control } type="toggle" />
			<SettingField name="ac_lite_email_admin_on_recovery" control={ control } type="toggle" />
			<SettingField name="wcap_email_admin_on_abandonment" control={ control } type="toggle" />

			<ProLockedRow
				label={ __( 'Email address to send abandonment notifications:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Comma-separated email addresses to notify. Leave blank to use the site admin email.', 'woocommerce-abandoned-cart' ) }
			>
				<TextareaControl value="" onChange={ noop } rows={ 3 } __nextHasNoMarginBottom />
			</ProLockedRow>

			<ProLockedRow
				label={ __( 'Select cart sources to send abandonment notifications for:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Select the cart sources for which the abandonment notifications should be sent. Enable "All" to include every source.', 'woocommerce-abandoned-cart' ) }
			>
				<ToggleGrid
					onChange={ noop }
					options={ [
						{ key: 'all', label: __( 'All', 'woocommerce-abandoned-cart' ) },
						{ key: 'exit_intent', label: __( 'Exit Intent Popup', 'woocommerce-abandoned-cart' ) },
						{ key: 'checkout', label: __( 'Checkout Page', 'woocommerce-abandoned-cart' ) },
						{ key: 'url', label: __( 'URLs', 'woocommerce-abandoned-cart' ) },
						{ key: 'profile', label: __( 'Product Page', 'woocommerce-abandoned-cart' ) },
						{ key: 'custom_form', label: __( 'Custom Forms', 'woocommerce-abandoned-cart' ) },
						{ key: 'atc', label: __( 'Add to Cart Popup', 'woocommerce-abandoned-cart' ) },
					] }
				/>
			</ProLockedRow>

			<LockedText
				label={ __( 'Send reminder emails for newly abandoned carts after X days of order placement:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Reminder emails will be sent for newly abandoned carts only after X days of a previously placed order for a user with the same email address as that of the abandoned cart', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedToggle
				label={ __( 'Capture email address from custom fields:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Enable this setting to capture email address from other form fields.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedText
				label={ __( 'Class Names Of The Form Fields:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Enter class names of fields separated by commas from where email needs to be captured.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedText
				label={ __( 'Capture Email address from URL:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'If your site URL contains the same key, the plugin will capture the value as an email address of the customer.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedToggle
				label={ __( 'Enable Email Verification:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Enable this checkbox to allow email verification to be done via DeBounce API services.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedText
				label={ __( 'Enter DeBounce API Key:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Your Debounce.io API key.', 'woocommerce-abandoned-cart' ) }
			/>
		</AccordionCard>
	);
}

// ── Panel 2 — Cart Settings ───────────────────────────────────────────────────

const CART_DEFAULTS = {
	ac_lite_cart_abandoned_time: '',
	ac_lite_track_guest_cart_from_cart_page: false,
};

function CartPanel( { saveRef, onNotice, initialData } ) {
	const { control, loading, saving, saveCard } =
		useSettingsPanel( '/wcal/v1/settings/general/cart', CART_DEFAULTS, saveRef, onNotice, initialData );

	if ( loading ) {
		return <Loading />;
	}

	return (
		<AccordionCard
			title={ __( 'Cart Settings', 'woocommerce-abandoned-cart' ) }
			onSave={ canSave ? saveCard : undefined }
			saving={ saving }
			defaultOpen={ false }
		>
			<SettingField name="ac_lite_cart_abandoned_time" control={ control } />
			<LockedText
				label={ __( 'Cart abandonment cut-off time for guest users (minutes):', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'For guest users & visitors consider cart abandoned after X minutes of item being added to cart & order not placed.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedToggle
				label={ __( 'Do not track carts of guest users:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Abandoned carts of guest users will not be tracked.', 'woocommerce-abandoned-cart' ) }
			/>
			<SettingField name="ac_lite_track_guest_cart_from_cart_page" control={ control } type="toggle" />
			<LockedToggle
				label={ __( 'Do not track carts of logged-in users:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Abandoned carts of logged-in users will not be tracked.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedToggle
				label={ __( 'Add product to cart when popup close icon is clicked', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Enable this setting if you want the product to the added to cart when the user clicks on the Close Icon in the Add to Cart Popup Window.', 'woocommerce-abandoned-cart' ) }
			/>
		</AccordionCard>
	);
}

// ── Panel 3 — Plugin Settings ─────────────────────────────────────────────────

const PLUGIN_DEFAULTS = {
	ac_lite_delete_abandoned_order_days: '365',
};

function PluginPanel( { saveRef, onNotice, initialData } ) {
	const { control, loading, saving, saveCard } =
		useSettingsPanel( '/wcal/v1/settings/general/plugin', PLUGIN_DEFAULTS, saveRef, onNotice, initialData );

	if ( loading ) {
		return <Loading />;
	}

	return (
		<AccordionCard
			title={ __( 'Plugin Settings', 'woocommerce-abandoned-cart' ) }
			onSave={ canSave ? saveCard : undefined }
			saving={ saving }
			defaultOpen={ false }
		>
			<SettingField name="ac_lite_delete_abandoned_order_days" control={ control } />
			<LockedToggle
				label={ __( 'Remove all data on uninstall:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Enable this setting if you want to completely remove Abandoned Cart data when plugin is deleted.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedToggle
				label={ __( 'Enable AI Reporting Abilities (lets AI assistants query your abandoned-cart reports):', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Lets AI assistants (e.g. Claude, connected via MCP) query abandoned-cart reporting data such as top abandoned products, recovered revenue, and template performance. Read-only, requires the same admin capability as this settings screen, and exposes no customer data.', 'woocommerce-abandoned-cart' ) }
			/>
			<ProLockedRow
				label={ __( 'Reset Usage Tracking:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'This will reset your usage tracking settings, causing it to show the opt-in banner and not send any data.', 'woocommerce-abandoned-cart' ) }
			>
				<Button variant="secondary" onClick={ noop }>
					{ __( 'Reset', 'woocommerce-abandoned-cart' ) }
				</Button>
			</ProLockedRow>
		</AccordionCard>
	);
}

// ── Panel 4 — GDPR Consent ────────────────────────────────────────────────────

const GDPR_DEFAULTS = {
	wcal_enable_gdpr_consent: false,
	wcal_guest_cart_capture_msg: '',
	wcal_logged_cart_capture_msg: '',
	wcal_gdpr_allow_opt_out: '',
	wcal_gdpr_opt_out_message: '',
};

function GdprPanel( { saveRef, onNotice, initialData } ) {
	const { control, watch, loading, saving, saveCard } =
		useSettingsPanel( '/wcal/v1/settings/general/gdpr', GDPR_DEFAULTS, saveRef, onNotice, initialData );

	const gdprEnabled = watch( 'wcal_enable_gdpr_consent' );

	if ( loading ) {
		return <Loading />;
	}

	return (
		<AccordionCard
			title={ __( 'GDPR Consent', 'woocommerce-abandoned-cart' ) }
			onSave={ canSave ? saveCard : undefined }
			saving={ saving }
			defaultOpen={ false }
		>
			<SettingField name="wcal_enable_gdpr_consent" control={ control } type="toggle" />

			{ gdprEnabled && (
				<>
					<SettingField name="wcal_guest_cart_capture_msg" control={ control } type="textarea" />
					<SettingField name="wcal_logged_cart_capture_msg" control={ control } type="textarea" />

					<ProLockedRow
						label={ __( 'GDPR Compliance Type:', 'woocommerce-abandoned-cart' ) }
						tooltip={ __( 'In compliance with GDPR, allow site visitors (guests) to opt in to cart tracking. This message will be displayed alongside the GDPR message above.', 'woocommerce-abandoned-cart' ) }
					>
						<SelectControl
							value="opt-out"
							onChange={ noop }
							options={ [
								{ value: 'opt-out', label: __( 'Opt Out of Cart Tracking', 'woocommerce-abandoned-cart' ) },
								{ value: 'opt-in', label: __( 'Opt In to Cart Tracking', 'woocommerce-abandoned-cart' ) },
							] }
							__nextHasNoMarginBottom
						/>
					</ProLockedRow>

					<LockedText
						label={ __( 'Allow the visitor to opt in to cart tracking:', 'woocommerce-abandoned-cart' ) }
						tooltip={ __( 'In compliance with GDPR, allow the site visitor (guests & registered users) to opt in to cart tracking. This message will be displayed in conjunction with the GDPR message above.', 'woocommerce-abandoned-cart' ) }
					/>

					<SettingField name="wcal_gdpr_allow_opt_out" control={ control } />
					<SettingField name="wcal_gdpr_opt_out_message" control={ control } />

					<LockedToggle
						label={ __( 'Enable SMS Consent:', 'woocommerce-abandoned-cart' ) }
						tooltip={ __( 'Enable this setting to display a notice informing customers that their phone number and cart data are saved to send abandonment reminders.', 'woocommerce-abandoned-cart' ) }
					/>
					<LockedText
						label={ __( 'SMS Consent Message:', 'woocommerce-abandoned-cart' ) }
						tooltip={ __( 'Message to be displayed below the Phone field at Checkout informing users that their phone number and cart data are saved to send abandonment reminders.', 'woocommerce-abandoned-cart' ) }
					/>
				</>
			) }
		</AccordionCard>
	);
}

// ── Panel 5 — Coupon Settings ─────────────────────────────────────────────────

const COUPON_DEFAULTS = {
	wcal_delete_coupon_data: false,
};

function CouponPanel( { saveRef, onNotice, initialData } ) {
	const { control, loading, saving, saveCard } =
		useSettingsPanel( '/wcal/v1/settings/general/coupon', COUPON_DEFAULTS, saveRef, onNotice, initialData );

	const [ purging, setPurging ] = useState( false );
	const [ purgeNotice, setPurgeNotice ] = useState( null );

	async function deleteCouponsManually() {
		const { delete_coupon_confirmation_msg: message, ajax_nonce: nonce } = window.wcal_dismiss_params; // eslint-disable-line camelcase
		if ( ! window.confirm( message ) ) { // eslint-disable-line no-alert
			return;
		}
		setPurging( true );
		setPurgeNotice( null );
		try {
			const res = await ajaxPost( 'wcal_delete_expired_used_coupon_code', {}, { nonce, nonceKey: 'ajax_nonce' } );
			setPurgeNotice( { type: res.success ? 'success' : 'error', message: res.data } );
		} catch {
			setPurgeNotice( { type: 'error', message: __( 'Something went wrong. Please try deleting again.', 'woocommerce-abandoned-cart' ) } );
		} finally {
			setPurging( false );
		}
	}

	if ( loading ) {
		return <Loading />;
	}

	return (
		<AccordionCard
			title={ __( 'Coupon Settings', 'woocommerce-abandoned-cart' ) }
			onSave={ canSave ? saveCard : undefined }
			saving={ saving }
			defaultOpen={ false }
		>
			<SettingField name="wcal_delete_coupon_data" control={ control } type="toggle" />

			<SettingRow
				label={ fields?.wcal_delete_coupon_data_manually?.label }
				tooltip={ fields?.wcal_delete_coupon_data_manually?.description }
			>
				<div>
					<Button
						variant="secondary"
						isDestructive
						onClick={ deleteCouponsManually }
						isBusy={ purging }
						disabled={ purging }
					>
						{ __( 'Delete', 'woocommerce-abandoned-cart' ) }
					</Button>
					{ purgeNotice && (
						<Notice
							status={ purgeNotice.type }
							isDismissible
							onRemove={ () => setPurgeNotice( null ) }
							style={ { marginTop: '8px' } }
						>
							{ purgeNotice.message }
						</Notice>
					) }
				</div>
			</SettingRow>
		</AccordionCard>
	);
}

// ── Panel 6 — Action Scheduler ────────────────────────────────────────────────

function SchedulerPanel() {
	return (
		<AccordionCard
			title={ __( 'Setting for sending Emails & SMS using Action Scheduler', 'woocommerce-abandoned-cart' ) }
			defaultOpen={ false }
		>
			<LockedToggle
				label={ __( 'Send Abandoned cart reminders automatically using Action Scheduler:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Enabling this setting will send the abandoned cart reminders to the customer after the set time. If disabled, automated abandoned cart reminders will not be sent by the plugin. Carts will be tracked based on the settings, but no reminders will be sent.', 'woocommerce-abandoned-cart' ) }
			/>
			<LockedText
				label={ __( 'Run automated scheduler action after X minutes:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'The duration in minutes after which an action should be automatically scheduled to send email, SMS & FB reminders to customers.', 'woocommerce-abandoned-cart' ) }
			/>
		</AccordionCard>
	);
}

// ── Panel 7 — Exclusion Rules ─────────────────────────────────────────────────

const EXCLUSIONS_DEFAULTS = {
	wcap_restrict_ip_address: '',
	wcap_restrict_email_address: '',
	wcap_restrict_domain_address: '',
	wcap_restrict_countries: '',
};

// Build code↔name maps from the localized country list.
const COUNTRY_CODE_TO_NAME = {};
const COUNTRY_NAME_TO_CODE = {};
( countries ?? [] ).forEach( ( { id, text } ) => {
	COUNTRY_CODE_TO_NAME[ id ] = text;
	COUNTRY_NAME_TO_CODE[ text ] = id;
} );
const COUNTRY_NAMES = Object.keys( COUNTRY_NAME_TO_CODE );

/**
 * Convert stored "US,GB" to ["United States", "United Kingdom"].
 *
 * @param {string} csv Country codes.
 * @return {string[]} Country names.
 */
function countryCodesToNames( csv ) {
	if ( ! csv ) {
		return [];
	}
	return csv.split( ',' ).map( ( c ) => COUNTRY_CODE_TO_NAME[ c.trim() ] ).filter( Boolean );
}

/**
 * Convert ["United States", "United Kingdom"] to "US,GB".
 *
 * @param {string[]} names Country names.
 * @return {string} Country codes.
 */
function countryNamesToCsv( names ) {
	return names.map( ( n ) => COUNTRY_NAME_TO_CODE[ n ] ).filter( Boolean ).join( ',' );
}

function ExclusionsPanel( { saveRef, onNotice, initialData } ) {
	const { control, loading, saving, saveCard } =
		useSettingsPanel( '/wcal/v1/settings/general/exclusions', EXCLUSIONS_DEFAULTS, saveRef, onNotice, initialData );

	if ( loading ) {
		return <Loading />;
	}

	return (
		<AccordionCard
			title={ __( 'Rules to exclude capturing abandoned carts:', 'woocommerce-abandoned-cart' ) }
			onSave={ canSave ? saveCard : undefined }
			saving={ saving }
			defaultOpen={ false }
		>
			<SettingField name="wcap_restrict_ip_address" control={ control } type="textarea" />
			<SettingField name="wcap_restrict_email_address" control={ control } type="textarea" />
			<SettingField name="wcap_restrict_domain_address" control={ control } type="textarea" />
			<SettingField
				name="wcap_restrict_countries"
				control={ control }
				render={ ( field ) => (
					<FormTokenField
						value={ countryCodesToNames( field.value ) }
						suggestions={ COUNTRY_NAMES }
						onChange={ ( tokens ) => field.onChange( countryNamesToCsv( tokens ) ) }
						placeholder={ __( 'Search and add countries…', 'woocommerce-abandoned-cart' ) }
						__experimentalExpandOnFocus
						__nextHasNoMarginBottom
					/>
				) }
			/>
		</AccordionCard>
	);
}

// ── Panel 8 — Unsubscribe Settings ────────────────────────────────────────────

function UnsubscribePanel() {
	return (
		<AccordionCard
			title={ __( 'Unsubscribe Emails Settings', 'woocommerce-abandoned-cart' ) }
			defaultOpen={ false }
		>
			<ProLockedRow
				label={ __( 'Unsubscribe Landing Page:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'Select a source where the user must be redirected when an Unsubscribe link is clicked from reminders sent. For details, please check the documentation.', 'woocommerce-abandoned-cart' ) }
			>
				<SelectControl
					value="default_page"
					onChange={ noop }
					options={ [
						{ value: 'default_page', label: __( 'Default unsubscribe page', 'woocommerce-abandoned-cart' ) },
						{ value: 'custom_text', label: __( 'Custom text', 'woocommerce-abandoned-cart' ) },
						{ value: 'custom_wp_page', label: __( 'Custom WordPress page', 'woocommerce-abandoned-cart' ) },
					] }
					__nextHasNoMarginBottom
				/>
			</ProLockedRow>
			<LockedToggle
				label={ __( 'Allow customers to unsubscribe from all future Abandoned Cart Reminders:', 'woocommerce-abandoned-cart' ) }
				tooltip={ __( 'This will give an option to the customers whether they want to unsubscribe from the current abandoned cart reminders or from all future abandoned cart reminders also when they click on the unsubscribe link in the email.', 'woocommerce-abandoned-cart' ) }
			/>
		</AccordionCard>
	);
}

// ── General Settings page (all panels) ────────────────────────────────────────

// ── Panel — Recovery Emails ───────────────────────────────────────────────────

const RECOVERY_EMAIL_DEFAULTS = {
	wcal_from_name: '',
	wcal_from_email: '',
	wcal_reply_email: '',
	wcal_add_utm_to_links: '',
	wcal_auto_login_users: false,
};

function RecoveryEmailPanel( { saveRef, onNotice, initialData } ) {
	const { control, loading, saving, saveCard } =
		useSettingsPanel( '/wcal/v1/settings/general/recovery-email', RECOVERY_EMAIL_DEFAULTS, saveRef, onNotice, initialData );

	return (
		<AccordionCard
			title={ __( 'Settings for abandoned cart recovery emails', 'woocommerce-abandoned-cart' ) }
			onSave={ canSave ? saveCard : undefined }
			saving={ saving }
		>
			{ loading ? <Loading /> : (
				<>
					<SettingField name="wcal_from_name" control={ control } />
					<SettingField name="wcal_from_email" control={ control } />
					<SettingField name="wcal_reply_email" control={ control } />

					<ProLockedRow
						label={ __( 'Product image size (px)', 'woocommerce-abandoned-cart' ) }
						tooltip={ __( 'This setting affects the dimension of the product image in the abandoned cart reminder email.', 'woocommerce-abandoned-cart' ) }
					>
						<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
							<TextControl value="" onChange={ noop } placeholder={ __( 'Width', 'woocommerce-abandoned-cart' ) } __nextHasNoMarginBottom />
							<span style={ { flexShrink: 0 } }>{ __( '×', 'woocommerce-abandoned-cart' ) }</span>
							<TextControl value="" onChange={ noop } placeholder={ __( 'Height', 'woocommerce-abandoned-cart' ) } __nextHasNoMarginBottom />
						</div>
					</ProLockedRow>

					<ProLockedRow
						label={ __( 'Product Name Redirects to:', 'woocommerce-abandoned-cart' ) }
						tooltip={ __( 'Select the page where product name in reminder emails should redirect to.', 'woocommerce-abandoned-cart' ) }
					>
						<SelectControl
							value=""
							onChange={ noop }
							options={ [ { value: '', label: __( 'Select…', 'woocommerce-abandoned-cart' ) } ] }
							__nextHasNoMarginBottom
						/>
					</ProLockedRow>

					<SettingField name="wcal_add_utm_to_links" control={ control } type="textarea" />
					<SettingField name="wcal_auto_login_users" control={ control } type="toggle" />
				</>
			) }
		</AccordionCard>
	);
}

const PANELS = [ 'email', 'cart', 'plugin', 'gdpr', 'coupon', 'exclusions', 'recovery-email' ];

function GeneralSettings( { onNotice } ) {
	const [ initialData, setInitialData ] = useState( null );

	useEffect( () => {
		apiFetch( { path: '/wcal/v1/settings/general/all' } )
			.then( ( res ) => setInitialData( res?.data ?? res ?? {} ) )
			.catch( () => {
				// Fallback: fetch all panels individually so the page still works.
				Promise.allSettled( PANELS.map( ( panel ) => apiFetch( { path: `/wcal/v1/settings/general/${ panel }` } ) ) )
					.then( ( results ) => {
						const data = {};
						results.forEach( ( r, i ) => {
							if ( r.status === 'fulfilled' ) {
								data[ PANELS[ i ] ] = r.value?.data ?? r.value;
							}
						} );
						setInitialData( data );
					} );
			} );
	}, [] );

	const emailRef = useRef( null );
	const cartRef = useRef( null );
	const pluginRef = useRef( null );
	const gdprRef = useRef( null );
	const couponRef = useRef( null );
	const exclusionsRef = useRef( null );
	const recoveryEmailRef = useRef( null );

	const [ saveAllBusy, setSaveAllBusy ] = useState( false );
	const [ saveAllNotice, setSaveAllNotice ] = useState( null );

	async function saveAll() {
		setSaveAllBusy( true );
		setSaveAllNotice( null );
		const refs = [ emailRef, cartRef, pluginRef, gdprRef, couponRef, exclusionsRef, recoveryEmailRef ];
		const results = await Promise.allSettled( refs.map( ( r ) => r.current?.() ) );
		const errors = results.filter( ( r ) => r.status === 'rejected' ).map( ( r ) => r.reason?.message ).filter( Boolean );

		if ( results.some( ( r ) => r.status === 'rejected' ) ) {
			// Panels report to the same notice, so show every panel's error together.
			onNotice( { type: 'error', message: errors.join( ' ' ) || __( 'Failed to save settings.', 'woocommerce-abandoned-cart' ) } );
			setSaveAllNotice( { type: 'error', message: __( 'Some settings could not be saved. Please check each panel for details.', 'woocommerce-abandoned-cart' ) } );
		} else {
			setSaveAllNotice( { type: 'success', message: __( 'All settings saved.', 'woocommerce-abandoned-cart' ) } );
		}
		setSaveAllBusy( false );
	}

	if ( ! initialData ) {
		return <div style={ { padding: '40px', textAlign: 'center' } }><Spinner /></div>;
	}

	return (
		<div className="wcap-settings-general" style={ { padding: '24px 20px' } }>
			<p style={ { margin: '10px 0px 30px', color: '#757575' } }>
				{ intro }{ ' ' }
				{ __( 'Settings marked PRO are available in the Pro version.', 'woocommerce-abandoned-cart' ) }{ ' ' }
				<a href={ trialUrl } target="_blank" rel="noopener noreferrer">
					{ __( 'Upgrade to Pro', 'woocommerce-abandoned-cart' ) }
				</a>
			</p>
			<EmailPanel saveRef={ emailRef } onNotice={ onNotice } initialData={ initialData.email } />
			<div style={ { marginTop: '16px' } }>
				<CartPanel saveRef={ cartRef } onNotice={ onNotice } initialData={ initialData.cart } />
			</div>
			<div style={ { marginTop: '16px' } }>
				<PluginPanel saveRef={ pluginRef } onNotice={ onNotice } initialData={ initialData.plugin } />
			</div>
			<div style={ { marginTop: '16px' } }>
				<CouponPanel saveRef={ couponRef } onNotice={ onNotice } initialData={ initialData.coupon } />
			</div>
			<div style={ { marginTop: '16px' } }>
				<SchedulerPanel />
			</div>
			<div style={ { marginTop: '16px' } }>
				<ExclusionsPanel saveRef={ exclusionsRef } onNotice={ onNotice } initialData={ initialData.exclusions } />
			</div>
			<div style={ { marginTop: '16px' } }>
				<RecoveryEmailPanel saveRef={ recoveryEmailRef } onNotice={ onNotice } initialData={ initialData[ 'recovery-email' ] } />
			</div>
			<div style={ { marginTop: '16px' } }>
				<UnsubscribePanel />
			</div>
			<div style={ { marginTop: '16px' } }>
				<GdprPanel saveRef={ gdprRef } onNotice={ onNotice } initialData={ initialData.gdpr } />
			</div>
			{ canSave && (
				<div style={ { marginTop: '24px', display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '8px' } }>
					{ saveAllNotice && (
						<Notice status={ saveAllNotice.type } isDismissible onRemove={ () => setSaveAllNotice( null ) }>
							{ saveAllNotice.message }
						</Notice>
					) }
					<Button
						variant="primary"
						onClick={ saveAll }
						isBusy={ saveAllBusy }
						disabled={ saveAllBusy }
						className="wcap-sp-save-btn"
					>
						{ __( 'Save All', 'woocommerce-abandoned-cart' ) }
					</Button>
				</div>
			) }
		</div>
	);
}

export default GeneralSettings;
