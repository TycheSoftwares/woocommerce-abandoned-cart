/**	
 * It will add the button of the dimiss on the pro version notice.
 * @function dismiss_notice
 */
jQuery(document).ready( function() {
	jQuery( '.notice.is-dismissible' ).each( function() {
		
		//console.log (this.className);
		var wcal_get_name = this.className;
		if (wcal_get_name.indexOf('wcal-tracker') !== -1){
			var $this = jQuery( this ),
				$button = jQuery( '<button type="button" class="notice-dismiss"><span class="wcal screen-reader-text"></span></button>' ),
				btnText = commonL10n.dismiss || '';

			// Ensure plain text
			$button.find( '.screen-reader-text' ).text( btnText );

			$this.append( $button );
			$button.on( 'click.notice-dismiss', function( event ) {
				console.log ('here');
				//alert('This');
				event.preventDefault();
				$this.fadeTo( 100 , 0, function() {
					//alert();
					jQuery(this).slideUp( 100, function() {
						jQuery(this).remove();
						var data = {
							action: "wcal_admin_notices"
						};
						var admin_url = jQuery( "#admin_url" ).val();
							jQuery.post( admin_url + "/admin-ajax.php", data, function( response ) {
						});
					});
				});
			});

		}
	});
});