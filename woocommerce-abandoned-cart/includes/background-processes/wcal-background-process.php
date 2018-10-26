<?php

class WCAL_Background_Process extends WP_Background_Process {

	

	/**
	 * @var string
	 */
	protected $action = 'wcal_all_process';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {

	    if( isset( $item ) ) {
	        
	            switch( $item ) {
	                case 'emails':
	                    $wcal_cron = new woocommerce_abandon_cart_cron();
                        $wcal_cron->wcal_send_email_notification();
	                    break;
	            }
	            
	    } 
	    return false;
		
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		// Show notice to user or perform some other arbitrary task...
	}
	
}
