/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

const { optInEmailsDefaultText } = getSetting( 'wcal-gdpr-email_data', '' );
const { optOutEmailsDefaultText } = getSetting( 'wcal-gdpr-email_data', '' );
const { optOutEmailsConfirmationText } = getSetting( 'wcal-gdpr-email_data', '' );
const { ajaxUrl } = getSetting( 'wcal-gdpr-email_data' );

const WcapGDPRBlock = ({ children, checkoutExtensionData }) => {

	const [showConfirmation, setShowConfirmation ] = useState( false );

	const handleOptOut = () => {
		updateLocalStorage( 'wcal_gdpr_no_thanks', true );
		setShowConfirmation(true);
	}

	const updateLocalStorage = (key, newValue) => {
		const oldValue = localStorage.getItem(key);
		localStorage.setItem(key, newValue);
	  
		const storageEvent = new StorageEvent('storage', {
		  key: key,
		  oldValue: oldValue,
		  newValue: newValue,
		  url: window.location.href,
		  storageArea: localStorage
		});
	  
		window.dispatchEvent(storageEvent);
	}
	  
	return (
		<div>
			{ !showConfirmation ? <WcapGDPRText /> : null }
			{showConfirmation ? <WcapGDPRConfirmation /> : null }
		</div>
	)

	function WcapGDPRText() {
		return (
			<div id='wcal_gdpr_msg'>
				{optInEmailsDefaultText} <a id='wcal_gdpr_no_thanks' onClick={handleOptOut}>{optOutEmailsDefaultText}</a>
			</div>
		);
	}
	
	function WcapGDPRConfirmation( {display } ) {
	
		return (
			<div id='wcal_gdpr_opt_out_confirmation'>
				{optOutEmailsConfirmationText}
			</div>
		);	
	}
};

export default WcapGDPRBlock;
