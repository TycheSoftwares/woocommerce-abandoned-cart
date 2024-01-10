<?php
/**
 * WC Checkout Blocks Integration - GDPR Emails.
 *
 * @package WooCommerce Abandon Cart Pro
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

define( 'WCAL_GDPR_EMAILS_VERSION', '0.1.0' );

/**
 * Class for integrating with WooCommerce Blocks
 */
class Wcal_GDPR_Emails_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcal-gdpr-email';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_wcal_email_block_frontend_scripts();
		$this->register_gdpr_email_block_editor_scripts();
		$this->register_gdpr_email_block_editor_styles();
		$this->register_main_integration();
	}

	/**
	 * Registers the main JS file required to add filters and Slot/Fills.
	 */
	public function register_main_integration() {

		if ( 'on' === get_option( 'wcal_enable_gdpr_consent' ) ) {

			$script_path = '/build/index.js';

			$script_url = WCAL_PLUGIN_URL . $script_path;

			$script_asset_path = WCAL_PLUGIN_PATH . '/build/index.asset.php';
			$script_asset      = file_exists( $script_asset_path )
				? require $script_asset_path
				: array(
					'dependencies' => array(),
					'version'      => $this->get_file_version( $script_path ),
				);

			wp_register_script(
				'wcal-emails-blocks-integration',
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
			wp_set_script_translations(
				'wcal-emails-blocks-integration',
				'wcal_params',
				WCAL_PLUGIN_PATH . '/languages'
			);
		}
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		if ( 'on' === get_option( 'wcal_enable_gdpr_consent' ) ) {
			return array( 'wcal-emails-blocks-integration', 'wcal-gdpr-msg-block-frontend' );
		}
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		if ( is_user_logged_in() ) {
			$display_msg = __( get_option( 'wcal_logged_cart_capture_msg', '' ), 'woocommerce-abandoned-cart' ); // phpcs:ignore
		} else {
			$display_msg = __( get_option( 'wcal_guest_cart_capture_msg', '' ), 'woocommerce-abandoned-cart' ); // phpcs:ignore
		}

		if ( '' === $display_msg ) {
			$display_msg = __( 'Saving your email and cart details helps us keep you up to date with this order.', 'woocommerce-abandoned-cart' );
		}
		$display_msg = apply_filters( 'wcal_gdpr_email_consent_guest_users', $display_msg );

		$no_thanks = get_option( 'wcal_gdpr_allow_opt_out', '' );
		$no_thanks = apply_filters( 'wcal_gdpr_opt_out_text', $no_thanks );

		$opt_out_confirmation_msg = get_option( 'wcal_gdpr_opt_out_message', '' );
		$opt_out_confirmation_msg = apply_filters( 'wcal_gdpr_opt_out_confirmation_text', $opt_out_confirmation_msg );

		$data = array(
			'optInEmailsDefaultText'       => $display_msg,
			'optOutEmailsDefaultText'      => $no_thanks,
			'optOutEmailsConfirmationText' => $opt_out_confirmation_msg,
		);

		return $data;

	}

	/**
	 * Register block editor style files - admin.
	 */
	public function register_gdpr_email_block_editor_styles() {
	}

	/**
	 * Register block editor scripts - admin.
	 */
	public function register_gdpr_email_block_editor_scripts() {
	}

	/**
	 * Register front end scripts.
	 */
	public function register_wcal_email_block_frontend_scripts() {

		$script_path       = '/build/wcal-blocks-gdpr-frontend.js';
		$script_url        = WCAL_PLUGIN_URL . $script_path;
		$script_asset_path = WCAL_PLUGIN_PATH . '/build/wcal-blocks-gdpr-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'wcal-gdpr-msg-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'wcal-gdpr-msg-block-frontend', // script handle.
			'woocommerce-abandoned-cart', // text domain.
			WCAL_PLUGIN_PATH . '/languages'
		);

	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return WCAL_GDPR_EMAILS_VERSION;
	}
}
