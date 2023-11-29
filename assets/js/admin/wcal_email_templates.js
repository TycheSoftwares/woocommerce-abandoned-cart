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
			current_state   : new_state,
			ajax_nonce      : wcal_templates_params.wcal_status_nonce,
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

jQuery( document ).ready( function($) {
	$( "#preview_email" ).click( function() {	
		emailVal = jQuery( '#send_test_email' ).val();
		const re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;	
		if ( !re.test( emailVal ) ) {	
			jQuery( '#preview_email_sent_msg' ).html( wcal_templates_params.wcal_test_email_incorrect_input_msg );
			jQuery( '#preview_email_sent_msg' ).show();
			return false;		
		}

		$( '#preview_email_sent_msg' ).hide();

		$( '.ajax_img' ).show();
		var email_body = '';
		if ( $("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ) {
			email_body = tinyMCE.get('woocommerce_ac_email_body').getContent();
		} else {
			email_body = jQuery('#woocommerce_ac_email_body').val();
		}
		var subject_email_preview = $( '#woocommerce_ac_email_subject' ).val();
		var body_email_preview    = email_body;
		var send_email_id         = $( '#send_test_email' ).val();
		var is_wc_template        = document.getElementById( "is_wc_template" ).checked;
		var wc_template_header    = $( '#wcal_wc_email_header' ).val() != '' ? $( '#wcal_wc_email_header' ).val() : wcal_templates_params.wcal_test_email_default_header;

		var generate_unique_code = $( '#unique_coupon' ).is( ":checked" ) ? '1' : '0';
		var individual_use       = $( '#individual_use' ).is( ":checked" ) ? '1' : '0';
		var discount_shipping    = $( '#wcal_allow_free_shipping' ).is( ":checked" ) ? 'yes' : 'no';

		var coupon_code           = $( '#coupon_ids' ).val();
		var discount_type         = $( '#wcal_discount_type').val();
		var discount_amount       = $( '#wcal_coupon_amount').val();
		var discount_expiry       = $( '#wcal_coupon_expiry').val();
		var default_template      = '';
		
		var data = {
					subject_email_preview: subject_email_preview,
					body_email_preview   : body_email_preview,
					send_email_id        : send_email_id,
					is_wc_template       : is_wc_template,
					wc_template_header   : wc_template_header,
					discount_expiry      : discount_expiry,
					discount_type        : discount_type,
					coupon_code          : coupon_code,
					discount_shipping    : discount_shipping,
					individual_use       : individual_use,
					discount_amount      : discount_amount,
					generate_unique_code : generate_unique_code,
					default_template     : default_template,
					action               : 'wcal_preview_email_sent',
					ajax_nonce           : wcal_templates_params.wcal_test_email_nonce,
				};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post( ajaxurl, data, function( response ) {
			$( '.ajax_img' ).hide();
			if ( 'not sent' == response ) {
				$( "#preview_email_sent_msg" ).html( wcal_templates_params.wcal_test_email_empty_body_msg );
				$( "#preview_email_sent_msg" ).fadeIn();
					setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();}, 4000 );
			} else {
				var wcal_image_url = wcal_templates_params.wcal_email_sent_image_path;
				console.log( wcal_image_url );
				$( "#preview_email_sent_msg" ).html( "<img style = 'height: 18px; width:20px;' src="+ wcal_image_url +"> &nbsp;" + wcal_templates_params.wcal_test_email_success_msg );
					$( "#preview_email_sent_msg" ).fadeIn();
					setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();}, 3000 );
			}
		});
	});
});
