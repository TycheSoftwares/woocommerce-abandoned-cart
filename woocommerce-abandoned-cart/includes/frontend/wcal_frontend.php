<?php
/**
 * Frontend loader for Abandoned Cart Lite
 * 
 * @since 5.3.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Wcal_Frontend' ) ) {

	/**
	 * Frontend loader
	 */
	class Wcal_Frontend {
		
		function __construct() {
			
			$this->include_files();
		}

		function include_files() {
			
			include_once 'wcal_checkout_process.php';
		}
	}
}

return new Wcal_Frontend();