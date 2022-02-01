<?php
/**
 * Main Connector File. All connectors inherit this class.
 *
 * @package Includes/Connectors
 */

/**
 * Connector Class for Wcap.
 */
class Wcap_Connector {

	/**
	 * Connector URL.
	 *
	 * @var $connector_url
	 */
	protected $connector_url = '';

	/**
	 * Connector DIR.
	 *
	 * @var $dir
	 */
	public $dir = __DIR__;

	/**
	 * Get Connector Image.
	 */
	public function get_image() {
		return $this->connector_url . '/views/logo.png';
	}

	/**
	 * Get connector slug.
	 */
	public function get_slug() {
		return sanitize_title( get_class( $this ) );
	}

	/**
	 * Get connector folder name.
	 */
	public function get_folder_name() {
		return $this->folder_name;
	}

	/**
	 * Get Settings card.
	 */
	public function get_settings_view() {

		$connector_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $connector_name ) {
			return;
		}

		$file_path = trailingslashit( $this->dir ) . $connector_name . '/views/settings.php';
		ob_start();
		if ( file_exists( "$file_path" ) ) {
			include "$file_path";
		}
		$settings_display = ob_get_clean();
		echo $settings_display; // phpcs:ignore
	}

}
new Wcap_Connector();
