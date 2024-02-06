/**
 * This function allows to dismiss the notices which are shown from the plugin.
 *
 * @namespace orddd_notice_dismissible
 * @since 6.8
 */
// Make notices dismissible
jQuery(document).ready( function() {
	jQuery('.wcal-tracker').on( 'click', 'button.notice-dismiss', function(){

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		var admin_url 	= jQuery( "#admin_url" ).val();
		ajaxurl 		= admin_url + "admin-ajax.php";

		var data = {
			action: "wcal_admin_notices",
			security: ts_notice_params.wcal_tracking_nonce
		};

		jQuery.post( ajaxurl, data, function( response ) {
		});	

	});
});