jQuery( document ).ready( function() {
    jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
    jQuery( "#wcal_start_date" ).datepicker({
      onSelect: function( date ) {
         //var date = jQuery('#wcal_start_date').datepicker('getDate');
         jQuery('#wcal_end_date').datepicker('option', 'minDate', date);
         setTimeout(function(){
              jQuery( "#wcal_end_date" ).datepicker('show');
            }, 16);     
        },
         maxDate: '0',
         changeMonth: true,
         changeYear: true,
         dateFormat: "yy-mm-dd" 
    } );

    jQuery( '#duration_select' ).change( function() {
      
      var group_name  = jQuery( '#duration_select' ).val();
        if ( jQuery(this).val() == "custom") {
          document.getElementById("wcal_start_end_date_div").style.display = "block";
        }
        if ( jQuery(this).val() != "custom" ) {
          document.getElementById("wcal_start_end_date_div").style.display = "none";
        }
    });

} );

jQuery( document ).ready( function() {
    jQuery( "#wcal_end_date" ).datepicker( {
         maxDate: '0',
         changeMonth: true,
         changeYear: true,
         dateFormat: "yy-mm-dd" } );

} );