/**
 * This function will toggle the status of the email template.
 * 
 * @function toggle_template_status
 */
jQuery(function( $ ) {

	$('.wcal-switch.wcal-toggle-template-status').click(function(){

		var $switch, state, new_state;

		$switch = $(this);

		if ( $switch.is('.wcal-loading') )
			return;

		state = $switch.attr( 'wcal-template-switch' );
		new_state = state === 'on' ? 'off' : 'on';

		$switch.addClass('wcal-loading');
		$switch.attr( 'wcal-template-switch', new_state );

		$.post( ajaxurl, {
			action          : 'wcal_toggle_template_status',
			wcal_template_id: $switch.attr( 'wcal-template-id' ),
			current_state   : new_state
		}, function( wcal_template_response ) {
			if ( wcal_template_response.indexOf('wcal-template-updated') > -1){
				var wcal_template_response_array = wcal_template_response.split ( ':' );

				var wcal_deactivate_ids = wcal_template_response_array[1];
				var wcal_split_all_ids  = wcal_deactivate_ids.split ( ',' );

				for (i = 0; i < wcal_split_all_ids.length; i++) { 
					var selelcted_id = wcal_split_all_ids[i];
				
					var $list = document.querySelector('[wcal-template-id="'+ selelcted_id+'"]');
					$($list).attr('wcal-template-switch','off');
				}
				
			}
			$switch.removeClass('wcal-loading');
		});
	});
});