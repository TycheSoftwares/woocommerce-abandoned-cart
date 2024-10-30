/**
 * Data Tracking notice.
 *
 * @namespace woocommerce_abandoned_cart
 * @since 5.20.0
 */
// Tracking Notice dismissed.
jQuery(document).ready( function() {
	jQuery( '.notice.is-dismissible' ).each( function() {
		var $this = jQuery( this ),
			$button = jQuery( '<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>' ),
			btnText = wp.i18n.dismiss || '';
		
		// Ensure plain text
		$button.find( '.screen-reader-text' ).text( btnText );

		$this.append( $button );

		/**
		 * Event when close icon is clicked.
		 * @fires event:notice-dismiss
		 * @since 6.8
		*/
		$button.on( 'click.notice-dismiss', function( event ) {
			event.preventDefault();
			$this.fadeTo( 100 , 0, function() {
				let data = {};
				if ( $this.hasClass( 'wcal-upgrade-to-pro-notice' ) ) {
					data = {
						action: 'wcal_dismiss_upgrade_to_pro',
						upgrade_to_pro_type: 'purchase',
						security: wcal_ts_dismiss_notice_params.tracking_notice
					};
				} else if ( $this.hasClass( 'wcal-pro-expired-notice' ) ) {
					data = {
						action: 'wcal_dismiss_upgrade_to_pro',
						upgrade_to_pro_type: 'expired',
						security: wcal_ts_dismiss_notice_params.tracking_notice
					};
				} else {
					data = {
						action: wcal_ts_dismiss_notice_params.ts_prefix_of_plugin + "_tracker_dismiss_notice",
						tracking_notice : wcal_ts_dismiss_notice_params.tracking_notice,
						security: wcal_ts_dismiss_notice_params.tracking_notice
					};
				}
				jQuery(this).slideUp( 100, function() {
					jQuery.post(
						wcal_ts_dismiss_notice_params.ts_admin_url,
						data,
						function( response ) {
							if ( 'success' === response ) {
								jQuery(this).remove();				
							}
						}
					);
				});
			});
		});
	});
});