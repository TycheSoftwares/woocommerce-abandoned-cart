jQuery( document ).ready( function() {

    jQuery("#wcal_gdpr_no_thanks").click(  function () {
        
        // run an ajax call and save the data that user did not give consent
        var data = {
            action          : 'wcal_gdpr_refused'
        };
        jQuery.post( wcal_registered_capture_params.ajax_url, data, function() {
            jQuery("#wcal_gdpr_message_block").empty().append("<span style='font-size: small'>" + 
            wcal_registered_capture_params._gdpr_after_no_thanks_msg + "</span>").delay(5000).fadeOut();
        });
        
    } );

});
