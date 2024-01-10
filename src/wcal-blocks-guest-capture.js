import { CART_STORE_KEY } from '@woocommerce/block-data';
import { select, subscribe } from '@wordpress/data';

const store =  select ( CART_STORE_KEY );

const debounce = (callback, wait) => {
	let timeoutId = null;
	return (...args) => {
	  window.clearTimeout(timeoutId);
	  timeoutId = window.setTimeout(() => {
		callback.apply(null, args);
	  }, wait);
	};
}

var can_run = false;
var gdpr_consent = true;

const checkKeyPress = debounce((ev) => {
	can_run = true;
}, 1000 );

window.addEventListener('keypress', checkKeyPress );

const checkStorage = ( event ) => {
	if ( 'wcal_gdpr_no_thanks' === event.key ) {

		if ( 'wcal_gdpr_no_thanks' === event.key ) {
			if ( event.newValue ) {
				gdpr_consent = false;
			}
			var data = {
	            action : 'wcal_gdpr_refused',
				ajax_nonce: wcal_guest_capture_blocks_params.ajax_nonce,
	        };
		}

		jQuery.post( wcal_guest_capture_blocks_params.ajax_url, data, function( response ) {
		});

	}
}
window.addEventListener('storage', checkStorage );

const unsubscribe = subscribe( () => {
	// Figure out a way to store the old addresses from the previous `subscribe` call and compare them to the new addresses to know if they've changed.
	const { billingAddress, shippingAddress } = store.getCustomerData();

	if ( can_run && gdpr_consent ) {
		var saved_email = localStorage.getItem( 'wcal_user_email' );
		var saved_firstname = localStorage.getItem( 'wcal_user_firstname' );
		var saved_lastname = localStorage.getItem( 'wcal_user_lastname' );
		var saved_billing_postcode = localStorage.getItem( 'wcal_billing_postcode' );
		var saved_shipping_postcode = localStorage.getItem( 'wcal_shipping_postcode' );

		var page_email = billingAddress.email;
		var page_firstname = billingAddress.first_name;
		var page_lastname = billingAddress.last_name;
		var page_billing_postcode = billingAddress.postcode;
		var page_shipping_postcode = shippingAddress.postcode;

		if ( saved_email !== page_email ) {
			localStorage.setItem( 'wcal_user_email', page_email );
		}

		if ( saved_firstname != page_firstname ) {
			localStorage.setItem( 'wcal_user_firstname', page_firstname );
		}

		if ( saved_lastname != page_lastname ) {
			localStorage.setItem( 'wcal_user_lastname', page_lastname );
		}

		if ( saved_billing_postcode != page_billing_postcode ) {
			localStorage.setItem( 'wcal_billing_postcode', page_billing_postcode );
		}

		if ( saved_shipping_postcode != page_shipping_postcode ) {
			localStorage.setItem( 'wcal_shipping_postcode', page_shipping_postcode );
		}
		if ( saved_email !== page_email ) {

			var data = {
				billing_first_name  : localStorage.getItem( 'wcal_user_firstname' ),
				billing_last_name   : localStorage.getItem( 'wcal_user_lastname' ),
				billing_email       : localStorage.getItem( 'wcal_user_email' ),
				billing_postcode    : localStorage.getItem( 'wcal_billing_postcode' ),
				shipping_postcode   : localStorage.getItem( 'wcal_shipping_postcode' ),
				wcal_guest_capture_nonce: wcal_guest_capture_blocks_params.wcal_save_nonce,
				action              : 'save_data'
			};

			if ( localStorage.wcal_abandoned_id ) {
				data.wcal_abandoned_id = localStorage.wcal_abandoned_id;
			}

			jQuery.post( wcal_guest_capture_blocks_params.ajax_url, data, function( response ) {
			});
		}
		can_run = false;
	}

}, CART_STORE_KEY );
