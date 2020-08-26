var gdpr_consent = true;
jQuery( 'input#billing_email' ).on( 'change', function() {

    var message_data = wcal_guest_capture_params._show_gdpr_message ? wcal_guest_capture_params._show_gdpr_message : true;

    if( gdpr_consent && message_data ) {
        var data = {
            billing_first_name	: jQuery('#billing_first_name').val(),
            billing_last_name	: jQuery('#billing_last_name').val(),
            billing_company		: jQuery('#billing_company').val(),
            billing_address_1	: jQuery('#billing_address_1').val(),
            billing_address_2	: jQuery('#billing_address_2').val(),
            billing_city		: jQuery('#billing_city').val(),
            billing_state		: jQuery('#billing_state').val(),
            billing_postcode	: jQuery('#billing_postcode').val(),
            billing_country		: jQuery('#billing_country').val(),
            billing_phone		: jQuery('#billing_phone').val(),
            billing_email		: jQuery('#billing_email').val(),
            order_notes			: jQuery('#order_comments').val(),
            shipping_first_name	: jQuery('#shipping_first_name').val(),
            shipping_last_name	: jQuery('#shipping_last_name').val(),
            shipping_company	: jQuery('#shipping_company').val(),
            shipping_address_1	: jQuery('#shipping_address_1').val(),
            shipping_address_2	: jQuery('#shipping_address_2').val(),
            shipping_city		: jQuery('#shipping_city').val(),
            shipping_state		: jQuery('#shipping_state').val(),
            shipping_postcode	: jQuery('#shipping_postcode').val(),
            shipping_country	: jQuery('#shipping_country').val(),
            ship_to_billing		: jQuery('#shiptobilling-checkbox').val(),
            wcal_guest_capture_nonce: jQuery( '#wcal_guest_capture_nonce' ).val(),
            action: 'save_data'
        };
        jQuery.post( wcal_guest_capture_params.ajax_url, data, function(response) {
        });
    }
});

jQuery( document ).ready( function() {
    
    if ( wcal_guest_capture_params._show_gdpr_message && ! jQuery("#wcal_gdpr_message_block").length && gdpr_consent ) {
        jQuery("#billing_email").after("<span id='wcal_gdpr_message_block'> <span style='font-size: small'> "+ wcal_guest_capture_params._gdpr_message +" <a style='cursor: pointer' id='wcal_gdpr_no_thanks'> "+ wcal_guest_capture_params._gdpr_nothanks_msg +" </a></span></span>");
    }

    jQuery("#wcal_gdpr_no_thanks").click(  function () {
        wcal_guest_capture_params._show_gdpr_message = false;
        
        gdpr_consent = false;        
        
        // run an ajax call and save the data that user did not give consent
        var data = {
            action : 'wcal_gdpr_refused'
        };
        jQuery.post( wcal_guest_capture_params.ajax_url, data, function() {
            jQuery("#wcal_gdpr_message_block").empty().append("<span style='font-size: small'>" + 
            wcal_guest_capture_params._gdpr_after_no_thanks_msg + "</span>").delay(5000).fadeOut();
        });
        
    });

});