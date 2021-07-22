/**
 * Abandoned cart detail Modal
 */

var Modal;
var wcal_clicked_cart_id;
var $wcal_get_email_address;
var $wcal_customer_details;
var $wcal_cart_total;
var $wcal_abandoned_date;
var $wcal_abandoned_status; 
var email_body;
var $wcal_cart_status;
var $wcal_show_customer_detail;

jQuery(function($) {

    Modal = {
        init: function(){

            $(document.body).on( 'click', '.wcal-js-close-modal', this.close );
            $(document.body).on( 'click', '.wcal-modal-overlay', this.close );
            $(document.body).on( 'click', '.wcal-js-open-modal', this.handle_link );
            $(document.body).on( 'click', '.wcal_customer_detail_modal', this.handle_customer_details );
            $(document.body).on( 'mousedown', '.wcal-js-open-modal', this.handle_link_mouse_middle_click );

            $(window).resize(function(){
                Modal.position();
            });

            $(document).keydown(function(e) {
                if (e.keyCode == 27) {
                    Modal.close();
                }
            });

        },
        handle_customer_details: function ( event ){
            
            event.preventDefault();
            var wcal_text_of_event = $(event.target).text();
            if ( wcal_text_of_event.indexOf ('Hide') == -1 ){
                $( ".wcal_modal_customer_all_details" ).fadeIn();
                Modal.position();
                $(event.target).text('Hide Details') ;
            }else{
                $( ".wcal_modal_customer_all_details" ).fadeOut();
                Modal.position();
                $(event.target).text('Show Details') ;
            }
        },
        handle_link_mouse_middle_click: function( e ){
            
           if( e.which == 2 ) {
                var wcal_get_currentpage = window.location.href;    
                this.href = wcal_get_currentpage;
                e.preventDefault();
                return false;
           }
        },
        handle_link: function( e ){
            e.preventDefault();

            var $a = $( this );
            var current_page   = ''; 
            var wcal_get_currentpage = window.location.href;
            var $wcal_get_email_address;
            var $wcal_break_email_text;
            var $email_text;
            var $wcal_row_data;
            
            if ( wcal_get_currentpage.indexOf('action=emailstats') == -1 ){ 
                $wcal_row_data = $a.closest("tr")[0];
                $email_text = $wcal_row_data.getElementsByTagName('td')[1].innerHTML;
                $wcal_break_email_text  = $email_text.split('<div');
                $wcal_get_email_address = $wcal_break_email_text[0];

                $wcal_customer_details = $wcal_row_data.getElementsByTagName('td')[2].innerHTML;
                $wcal_cart_total       = $wcal_row_data.getElementsByTagName('td')[3].innerHTML;
                $wcal_abandoned_date   = $wcal_row_data.getElementsByTagName('td')[4].innerHTML;
                $wcal_abandoned_status = $wcal_row_data.getElementsByClassName('status')[0].firstChild.innerText;
                
                $wcal_cart_status = $wcal_abandoned_status;
            }else{
                current_page            = 'send_email';
                $wcal_get_email_address = '';
                $wcal_customer_details  = '';
                $wcal_cart_total        = '';
                $wcal_abandoned_date    = '';
                $wcal_cart_status       = '';
            }

            $wcal_show_customer_detail = '<br><a href="#" id "wcal_customer_detail_modal"> Show Details </a>';
            
            email_body = '<div class="wcal-modal__body"> <div class="wcal-modal__body-inner"> <table cellspacing="0" cellpadding="6" border="1" class="wcal-cart-table"> <thead><th>Email Address</th><th>Customer Details</th><th>Order Total</th><th> Abandoned Date</th></tr></thead><tbody><tr><td>'+  $wcal_get_email_address+ '</td><td>'+ $wcal_customer_details + $wcal_show_customer_detail +'</td><td>'+ $wcal_cart_total+' </td><td>' +$wcal_abandoned_date+' </td></tr></tbody></table></div> </div>';

            var type = $a.data('modal-type');
            
            if ( type == 'ajax' )
            {
                wcal_clicked_cart_id     = $a.data('wcal-cart-id');
                Modal.open( 'type-ajax' );
                Modal.loading();
                var data = {
                    action                : 'wcal_abandoned_cart_info',
                    wcal_cart_id          : wcal_clicked_cart_id,
                    wcal_email_address    : $wcal_get_email_address,
                    wcal_customer_details : $wcal_customer_details,
                    wcal_cart_total       : $wcal_cart_total,
                    wcal_abandoned_date   : $wcal_abandoned_date,
                    wcal_abandoned_status : $wcal_cart_status,
                    wcal_current_page     : current_page
                }

                $.post( ajaxurl, data , function( response ){
                    
                    Modal.contents( response ); 
                });
            }
        },

        open: function( classes ) {
            $wcal_cart_status = '';
            $(document.body).addClass('wcal-modal-open').append('<div class="wcal-modal-overlay"></div>');
            var modal_body = '<div class="wcal-modal ' + classes + '"><div class="wcal-modal__contents"> <div class="wcal-modal__header"><h1>Cart #'+wcal_clicked_cart_id+'</h1>'+$wcal_cart_status+'</div>'+ email_body +' <div class = "wcal-modal-cart-content-hide" id ="wcal_remove_class">  </div> </div>  <div class="wcal-icon-close wcal-js-close-modal"></div>    </div>';

            $(document.body).append( modal_body );

            this.position();
        },

        loading: function() {
            $(document.body).addClass('wcal-modal-loading');
        },

        contents: function ( contents ) {
            $(document.body).removeClass('wcal-modal-loading');

            contents = contents.replace(/\\(.)/mg, "$1");

            $('.wcal-modal__contents').html(contents);

            this.position();
        },

        close: function() {
            $(document.body).removeClass('wcal-modal-open wcal-modal-loading');
            
            $('.wcal-modal, .wcal-modal-overlay').remove();
        },

        position: function() {

            $('.wcal-modal__body').removeProp('style');

            var modal_header_height = $('.wcal-modal__header').outerHeight();
            var modal_height = $('.wcal-modal').height();
            var modal_width = $('.wcal-modal').width();
            var modal_body_height = $('.wcal-modal__body').outerHeight();
            var modal_contents_height = modal_body_height + modal_header_height;

            $('.wcal-modal').css({
                'margin-left': -modal_width / 2,
                'margin-top': -modal_height / 2
            });

            if ( modal_height < modal_contents_height - 5 ) {
                $('.wcal-modal__body').height( modal_height - modal_header_height );
            }
        }
    };
    Modal.init();
});