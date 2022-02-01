<?php
/**
 * CustomSMTP Connector file *
 * Icon from https://www.iconpacks.net/icons/1/free-mail-icon-142-thumb.png.
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/CustomSMTP
 */

/**
 * Class for CustomSMTP Connector
 */
class Wcap_Custom_SMTP extends Wcap_Connector {

	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'Custom SMTP';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_custom_smtp';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'Custom SMTP server';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Use a custom SMTP server to send emails to recover abandoned carts.';
	/**
	 * Sync Disabled
	 *
	 * @var $sync_disabled
	 */
	public $disable_sync = true;
	/**
	 * Signle instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;
	/**
	 * Custom SMTP Connection.
	 *
	 * @var $wcap_custom_smtp_settings.
	 */
	public static $wcap_custom_smtp_settings = false;
	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_CUSTOMSMTP_PLUGIN_URL;
		add_action( 'wp_ajax_wcap_save_connector_settings', array( &$this, 'wcap_save_connector_settings' ), 9 );
		add_filter( 'wcap_basic_connectors_loaded', array( &$this, 'wcap_basic_connectors_loaded' ), 9, 1 );
	}
	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_CUSTOMSMTP_VERSION' ) ) {
			define( 'WCAP_CUSTOMSMTP_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_FULL_NAME' ) ) {
			define( 'WCAP_CUSTOMSMTP_FULL_NAME', 'Abandoned Carts Automations Connectors: CustomSMTP' );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_FILE' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_DIR' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_URL' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_CUSTOMSMTP_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to re-arrange connectors list
	 *
	 * @param array $connector_list - list of connectors.
	 */
	public function wcap_basic_connectors_loaded( $connector_list ) {
		$custom_smtp = $connector_list['wcap_custom_smtp'];
		unset( $connector_list['wcap_custom_smtp'] );
		$connector_list = array_merge( array( 'wcap_custom_smtp' => $custom_smtp ), $connector_list );
		return $connector_list;
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors'][ $this->slug ] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ),  //phpcs:ignore
			'connector_class' => 'Wcap_CustomSMTP',
			'image'           => $this->get_image(),
			'source'          => '',
			'file'            => '',
		);

		return $available_connectors;
	}

	/**
	 * Function to get instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}
		return self::$ins;
	}
}
