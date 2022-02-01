<?php
/**
 * Class for common functions for connectors.
 *
 * @package Includes/Connectors
 */

/**
 * Connectors common class.
 */
class Wcap_Connectors_Common {

	/**
	 * Class instance.
	 *
	 * @var $ins
	 */
	public static $ins = null;

	/**
	 * Connectors saved settings.
	 *
	 * @var $connectors_saved_data
	 */
	public static $connectors_saved_data = array();

	/**
	 * Saved Data.
	 *
	 * @var $saved_data
	 */
	public static $saved_data = false;

	/**
	 * Connectors List.
	 *
	 * @var array $connectors_list
	 */
	public static $connectors_list = array();

	/**
	 * Active connectors count.
	 *
	 * @var int $active_count
	 */
	public static $active_count = false;

	/**
	 * Inactive connectors count.
	 *
	 * @var int $inactive_count
	 */
	public static $inactive_count = false;

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( __CLASS__, 'wcap_get_connectors_data' ) );
	}

	/**
	 * Get instance of the class.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self; // phpcs:ignore
		}

		return self::$ins;
	}

	/**
	 * Get connector settings data from DB.
	 *
	 * @param string $connector_name - Connector slug.
	 * @return array containing the data. By default all connector data is sent.
	 *
	 * @since 8.15.0
	 */
	public static function wcap_get_connectors_data( $connector_name = '' ) {

		$temp_arr      = array();
		$fetch_from_db = false;
		$slug          = '' !== $connector_name && 'wcap' !== substr( $connector_name, 0, 4 ) ? "wcap_$connector_name" : $connector_name;
		if ( false === self::$saved_data ) {
			$fetch_from_db = true;
		} else {
			if ( '' !== $slug && array_key_exists( $slug, self::$saved_data ) ) {
				$temp_arr[ $slug ] = self::$saved_data[ $slug ];
			} else {
				// get the single record from wp_options & add it to the saved_data.
				$fetch_from_db = true;
			}
		}

		if ( $fetch_from_db ) {
			if ( '' === $slug ) {
				// get all the records from wp_options.
				foreach ( self::$connectors_list as $slug_name => $class ) {
					$details = json_decode( get_option( $slug_name . '_connector', '' ), true );
					if ( is_array( $details ) && count( $details ) > 0 ) {
						$temp_arr[ $slug_name ] = $details;
					}
				}
			} else {
				// get the single record from wp_options & add it to the saved_data.
				$details = json_decode( get_option( $slug . '_connector', '' ), true );
				if ( is_array( $details ) && count( $details ) > 0 ) {
					$temp_arr[ $slug ] = $details;
				}
			}
			self::$saved_data = $temp_arr;
		}
		self::$connectors_saved_data = $temp_arr;

		if ( $connector_name !=='' ) { //phpcs:ignore
			return isset( self::$connectors_saved_data[ $slug ] ) ? self::$connectors_saved_data[ $slug ] : false;
		}

		return self::$connectors_saved_data;
	}

	/**
	 * Get list of connectors.
	 *
	 * @param string $type - Connector type - active|inactive.
	 * @return array - connector list.
	 *
	 * @since 8.15.0
	 */
	public static function wcap_get_connectors( $type = '' ) {

		if ( empty( self::$connectors_list ) ) {
			$resource_dir = WCAL_PLUGIN_PATH . '/includes/connectors';
			foreach ( glob( $resource_dir . '/*' ) as $connector ) {

				if ( strpos( $connector, 'index.php' ) !== false ) {
					continue;
				}

				$_field_filename = $connector;
				// If file does not end in .php, then it is a folder.
				$is_folder = substr( $connector, -4 ) !== '.php';
				// Append connector.php if it is a folder.
				if ( $is_folder ) {
					$_field_filename = $connector . '/connector.php';
					if ( file_exists( $_field_filename ) ) {
						require_once $_field_filename;
						// Load class if file checked is a folder.
						$path                    = explode( '/', $connector );
						$folder_name             = array_pop( $path );
						$class_name              = 'Wcap_' . ucwords( $folder_name );
						$class_object            = $class_name::get_instance();
						$slug                    = $class_object->slug;
						$connector_list[ $slug ] = $class_object;
					}
				}
			}

			$connector_list        = apply_filters( 'wcap_basic_connectors_loaded', $connector_list );
			self::$connectors_list = $connector_list;
		}
		if ( '' === $type ) {
			return self::$connectors_list;
		} else {
			$return_list = array();
			// Identify the type i.e. active/inactive and only return those.
			foreach ( self::$connectors_list as $slug => $c_class ) {
				$details = self::wcap_get_connectors_data( $slug );
				switch ( $type ) {
					case 'active':
						if ( isset( $details['status'] ) && $type === $details['status'] ) {
							$return_list[ $slug ] = $c_class;
						}
						break;
					case 'inactive':
						if ( ( isset( $details['status'] ) && 'active' !== $details['status'] ) || ! isset( $details['status'] ) ) {
							$return_list[ $slug ] = $c_class;
						}
						break;
				}
			}
			return $return_list;
		}
	}

	/**
	 * Get count of active connectors.
	 */
	public static function wcap_get_active_connectors_count() {

		if ( false === self::$active_count ) {
			$total_active   = 0;
			$get_connectors = self::wcap_get_connectors();
			foreach ( $get_connectors as $connector_name => $connector_obj ) {
				$connector_settings = json_decode( get_option( $connector_name . '_connector', '' ), true );
				if ( is_array( $connector_settings ) && count( $connector_settings ) > 0 ) {
					$status = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
					if ( 'active' === $status ) {
						$total_active++;
					}
				}
			}
			self::$active_count = $total_active;
		}
		return self::$active_count;
	}

	/**
	 * Get inactive connectors count.
	 */
	public static function wcap_get_inactive_connectors_count() {

		if ( false === self::$inactive_count ) {
			$total_inactive = 0;
			$get_connectors = self::wcap_get_connectors();
			foreach ( $get_connectors as $connector_name => $connector_obj ) {
				$connector_settings = json_decode( get_option( $connector_name . '_connector', '' ), true );
				if ( is_array( $connector_settings ) && count( $connector_settings ) > 0 ) {
					$status = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
					if ( 'active' !== $status ) {
						$total_inactive++;
					}
				} else { // Settings have not yet been saved.
					$total_inactive++;
				}
			}
			self::$inactive_count = $total_inactive;
		}
		return self::$inactive_count;
	}

	/**
	 * Get active connectors list.
	 *
	 * @since 8.16.0
	 */
	public function wcap_get_active_connectors_list() {
		$connectors_list = self::wcap_get_connectors_data();
		$active_list     = array();
		if ( is_array( $connectors_list ) && count( $connectors_list ) > 0 ) {
			foreach ( $connectors_list as $slug => $settings ) {
				$connector_status = $settings['status'];
				if ( 'active' === $connector_status ) {
					array_push( $active_list, $slug );
				}
			}
			return $active_list;
		}
	}

}
$wcap_connectors_common = new Wcap_Connectors_Common();
