<?php 

class Wcal_Process_Base {
    
    public function __construct() {
        // Hook into that action that'll fire every 15 minutes
        add_action( 'woocommerce_ac_send_email_action',        array( &$this, 'wcal_process_handler' ), 11 );
        
    }
    
    public function wcal_process_handler() {
        // add any new reminder methods added in the future for cron here
        $reminders_list = array( 'emails' );

        if( is_array( $reminders_list ) && count( $reminders_list ) > 0 ) {
            foreach( $reminders_list as $reminder_type ) {
                switch( $reminder_type ) {
                    case 'emails':
	                    $wcal_cron = new woocommerce_abandon_cart_cron();
                        $wcal_cron->wcal_send_email_notification();
	                    break;
	            }
	            
            }
        }
        
    }
    
}
new Wcal_Process_Base();
?>