<?php
/**
 * Frontend loader for Abandoned Cart Lite
 *
 * @since 5.3.0
 * @package Abandoned-Cart-Lite-for-WooCommerce/Frontend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcal_Frontend' ) ) {

	/**
	 * Frontend loader
	 */
	class Wcal_Frontend {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->include_files();
		}

		/**
		 * Include the file.
		 */
		public function include_files() {
			include_once 'class-wcal-checkout-process.php';
		}
	}
}

return new Wcal_Frontend();
