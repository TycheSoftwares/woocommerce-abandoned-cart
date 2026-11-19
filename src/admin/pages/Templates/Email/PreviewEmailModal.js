/**
 * PreviewEmailModal — shows a rendered email preview and allows sending a test.
 *
 * The preview is the plugin's own preview URL: the saved template when there is
 * one, otherwise its sample email.
 *
 * Props:
 *   isOpen         {boolean}   Whether the modal is visible.
 *   onClose        {Function}  Called when the modal should close.
 *   templateId     {string|number} Template ID, when the template has been saved.
 *   onSendTest     {Function}  Called with ( email, isWcTemplate ), resolves to a notice.
 *   previewType    {string}    'wc' | 'custom'
 */

import { useState } from '@wordpress/element';
import { Button, TextControl, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const PreviewEmailModal = ( {
	isOpen,
	onClose,
	templateId,
	onSendTest,
	previewType,
} ) => {
	const [ testEmail,   setTestEmail   ] = useState( window.wcalAdminData?.currentUserEmail ?? '' );
	const [ testSending, setTestSending ] = useState( false );
	const [ testNotice,  setTestNotice  ] = useState( null );

	const handleSendTest = async () => {
		setTestSending( true );
		setTestNotice( null );
		setTestNotice( await onSendTest( testEmail, previewType === 'wc' ) );
		setTestSending( false );
	};

	const handleClose = () => {
		setTestNotice( null );
		onClose();
	};

	if ( ! isOpen ) return null;

	const title = previewType === 'wc'
		? __( 'Preview WooCommerce Email', 'woocommerce-abandoned-cart' )
		: __( 'Preview Custom Email', 'woocommerce-abandoned-cart' );

	const previewUrl = window.wcalAdminData?.previewUrls?.[ previewType ] ?? '';

	return (
		// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions -- Backdrop click is a mouse shortcut; the Close button is the keyboard path.
		<div className="wcap-pem-overlay" onClick={ ( e ) => { if ( e.target === e.currentTarget ) handleClose(); } }>
			<div className="wcap-pem-modal" role="dialog" aria-modal="true" aria-label={ title }>

				{ /* Header */ }
				<div className="wcap-pem-header">
					<h2 className="wcap-pem-title">{ title }</h2>
					<button type="button" className="wcap-pem-close" onClick={ handleClose } aria-label={ __( 'Close', 'woocommerce-abandoned-cart' ) }>
						<span className="dashicons dashicons-no-alt" />
					</button>
				</div>

				{ /* Body — iframe preview */ }
				<div className="wcap-pem-body">
					<iframe
						className="wcap-pem-iframe"
						title={ title }
						sandbox=""
						src={ templateId ? `${ previewUrl }&id=${ templateId }` : previewUrl }
					/>
				</div>

				{ /* Footer — send test email */ }
				<div className="wcap-pem-footer">
					<div className="wcap-pem-test-row">
						<div className="wcap-pem-test-input">
							<TextControl
								type="email"
								value={ testEmail }
								onChange={ setTestEmail }
								placeholder={ __( 'you@example.com', 'woocommerce-abandoned-cart' ) }
								label={ __( 'Send a test email to', 'woocommerce-abandoned-cart' ) }
								__nextHasNoMarginBottom
							/>
						</div>
						<Button
							variant="primary"
							onClick={ handleSendTest }
							disabled={ testSending || ! testEmail }
							isBusy={ testSending }
							className="wcap-pem-send-btn"
						>
							{ __( 'Send a test email', 'woocommerce-abandoned-cart' ) }
						</Button>
					</div>
					{ testNotice && (
						<Notice
							status={ testNotice.status }
							isDismissible
							onDismiss={ () => setTestNotice( null ) }
							className="wcap-pem-test-notice"
						>
							{ testNotice.message }
						</Notice>
					) }
				</div>

			</div>
		</div>
	);
};

export default PreviewEmailModal;
