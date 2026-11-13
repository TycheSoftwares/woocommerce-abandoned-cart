/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';

/**
 * Editor preview of the GDPR email message (block API v3, iframe editor safe).
 *
 * @return {JSX.Element} Block preview.
 */
export const Edit = () => {
	const blockProps = useBlockProps();
	const { optInEmailsDefaultText, optOutEmailsDefaultText } = getSetting(
		'wcal-gdpr-email_data',
		{}
	);

	return (
		<div { ...blockProps }>
			<div id="wcal_gdpr_msg">
				{ optInEmailsDefaultText ||
					__(
						'Your email address will be saved for sending abandoned cart reminders.',
						'woocommerce-abandoned-cart'
					) }{ ' ' }
				<a href="#wcal_gdpr_msg" onClick={ ( e ) => e.preventDefault() }>
					{ optOutEmailsDefaultText ||
						__( 'No thanks', 'woocommerce-abandoned-cart' ) }
				</a>
			</div>
		</div>
	);
};
