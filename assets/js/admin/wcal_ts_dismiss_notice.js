/**	
 * Update the DB to mark the notice as dismissed.
 */
jQuery(document).ready( function() {
	jQuery( '#wcal_cron_notice' ).on( 'click', '.notice-dismiss', function() {
		var data = {
			notice: 'wcal_scheduler_update_dismiss',
			action: "wcal_dismiss_admin_notice"

		};

		var admin_url = wcal_dismiss_params.ajax_url;

			jQuery.post( admin_url + "/admin-ajax.php", data, function( response ) {

		});

	});
	
	jQuery('#wcal_delete_coupons').click( function( event ) {
		var msg 	= "Are you sure you want delete the expired and used coupons created by Abandonment Cart Pro for WooCommerce Plugin?";
		var status 	= confirm( msg );
		if ( status == true ) {
			// disable delete button and show loader
			jQuery("#wcal_delete_coupons").attr( "disabled", true );
			jQuery( ".wcal-spinner" ).removeAttr( "style" );

			jQuery.post( ajaxurl, {
				action: 'wcal_delete_expired_used_coupon_code',
			}, function() {

			}).done(function( data ) {
				jQuery( "#wcal_delete_coupons" ).attr( "disabled", false );
				jQuery( ".wcal-spinner" ).hide();
				jQuery( ".wcal-coupon-response-msg" ).html( data.data );
				jQuery( ".wcal-coupon-response-msg" ).fadeOut(3000);
			}).fail(function( data ) {
				jQuery( "#wcal_delete_coupons" ).attr( "disabled", false );
				jQuery( ".wcal-spinner" ).hide();
				jQuery( ".wcal-coupon-response-msg" ).html( "Something went wrong. Please try deleting again." );
				jQuery( ".wcal-coupon-response-msg" ).fadeOut(3000);
			});
		}
	});
});