<?php
/**
 * MAiljet Connector file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/Mailjet
 */

/**
 * Class for Mailjet Connector
 */
class Wcap_Mailjet extends Wcap_Connector {

	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'mailjet';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_mailjet';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'Mailjet';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Send emails collected from the plugin and add them to contacts list on Mailjet.';
	/**
	 * Signle instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;
	/**
	 * Headers for Curl calls
	 *
	 * @var $headers
	 */
	public static $headers = null;
	/**
	 * Array of registered calls for function
	 *
	 *  @var array All calls with object
	 */
	public $registered_calls = array();

	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_MAILJET_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
	}
	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_MAILJET_VERSION' ) ) {
			define( 'WCAP_MAILJET_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_MAILJET_FULL_NAME' ) ) {
			define( 'WCAP_MAILJET_FULL_NAME', 'Abandoned Carts Automations Connectors: Mailjet' );
		}
		if ( ! defined( 'WCAP_MAILJET_PLUGIN_FILE' ) ) {
			define( 'WCAP_MAILJET_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_MAILJET_PLUGIN_DIR' ) ) {
			define( 'WCAP_MAILJET_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_MAILJET_PLUGIN_URL' ) ) {
			define( 'WCAP_MAILJET_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_MAILJET_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_MAILJET_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_MAILJET_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}
	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_mailjet'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ),  //phpcs:ignore
			'connector_class' => 'Wcap_Mailjet',
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
