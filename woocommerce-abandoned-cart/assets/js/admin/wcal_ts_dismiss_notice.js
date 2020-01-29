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
});