<?php
/**
 * Connectors Main File.
 *
 * @package Includes/Connectors
 */

/**
 * Integrations class file.
 */
class Wcap_Integrations {

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'wcap_load_connectors' ), 5 );
		add_action( 'wp_ajax_wcap_display_connectors', array( &$this, 'wcap_get_connectors_display' ) );
	}

	/**
	 * Load all the connector files.
	 *
	 * @since 8.15.0
	 */
	public function wcap_load_connectors() {
		$get_connectors = Wcap_Connectors_Common::wcap_get_connectors();
	}

	/**
	 * Connector page settings.
	 *
	 * @since 8.15.0
	 */
	public static function wcap_integrations_main() {

		$wcap_all_integrators      = '';
		$wcap_active_integrators   = '';
		$wcap_inactive_integrators = '';
		$wcap_section_view         = isset( $_GET['wcap_section_view'] ) ? sanitize_text_field( wp_unslash( $_GET['wcap_section_view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		switch ( $wcap_section_view ) {
			case 'all':
			case '':
			default:
				$wcap_all_integrators = 'current';
				$type                 = '';
				break;
			case 'active':
				$wcap_active_integrators = 'current';
				$type                    = 'active';
				break;
			case 'inactive':
				$wcap_inactive_integrators = 'current';
				$type                      = 'inactive';
				break;
		}
		$inactive_count = Wcap_Connectors_Common::wcap_get_inactive_connectors_count();
		$active_count   = Wcap_Connectors_Common::wcap_get_active_connectors_count();
		$all_count      = $inactive_count + $active_count;

		$active_link   = 0 === $active_count ? 'no_link' : '';
		$inactive_link = 0 === $inactive_count ? 'no_link' : '';

		?>
		<h2><?php esc_html_e( 'Connectors', 'woocommerce-ac' ); ?></h2>
		<p style='font-size:15px;'>
		<?php
		$upgrade_pro_msg = '<b><i>Upgrade to <a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite" target="_blank">Abandoned Cart Pro for WooCommerce</a> to enable any of the connectors and send the contact details and abandoned cart data to email marketing tools & CRMs.</i></b>';
		echo wp_kses_post( $upgrade_pro_msg );
		?>
		</p>
		<ul class='subsubsub' id='wcap_integrators_list'>
			<li>
				<a href='javascript:void(0)' id='wcap_all' class='wcap_integrators_view <?php echo esc_attr( $wcap_all_integrators ); ?>'><?php esc_html_e( 'All', 'woocommerce-ac' ); ?> (<?php echo esc_html( $all_count ); ?>)</a> |
			</li>
			<li>
				<a data-wcap-count='<?php echo esc_html( $active_count ); ?>' id='wcap_active' class='wcap_integrators_view <?php echo esc_attr( $wcap_active_integrators . $active_link ); ?>'><?php esc_html_e( 'Active', 'woocommerce-ac' ); ?>(<span id='wcap_active_count'><?php echo esc_html( $active_count ); ?></span>)</a> |
			</li>
			<li>
				<a data-wcap-count='<?php echo esc_html( $inactive_count ); ?>' id='wcap_inactive' class='wcap_integrators_view <?php echo esc_attr( $wcap_inactive_integrators . $inactive_link ); ?>'><?php esc_html_e( 'Inactive', 'woocommerce-ac' ); ?>(<span id='wcap_inactive_count'><?php echo esc_html( $inactive_count ); ?></span>)</a>
			</li>
		</ul>
		<hr id='wcap_integrators_break'>
		<div id="wcap_connectors_list">
		<?php
		self::wcap_display_connectors( $type );
		?>
		</div>
		<?php
	}

	/**
	 * Ajax to display the list of connectors in admin.
	 */
	public function wcap_get_connectors_display() {
		$type = isset( $_POST['type'] ) && '' !== $_POST['type'] ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$type = 'all' === $type ? '' : $type;
		self::wcap_display_connectors( $type );
		die();
	}

	/**
	 * Display the connectors based on type.
	 *
	 * @param string $type - all | active | inactive.
	 *
	 * @since 8.15.0
	 */
	public static function wcap_display_connectors( $type = '' ) {
		$get_connectors = Wcap_Connectors_Common::wcap_get_connectors( $type );

		if ( ! is_array( $get_connectors ) || 0 === count( $get_connectors ) ) {
			printf(
				/* translators: %1$s is replaced with connector type */
				esc_html__( 'No %1$s connector found', 'woocommerce-ac' ),
				esc_html( $type )
			);
			return;
		}
		echo '<div class="wcap-col-group">';
		foreach ( $get_connectors as $source_slug => $connector ) {
			$wcap_display_connectors = new Wcap_Display_Connectors( $source_slug, $connector );
			$wcap_display_connectors->print_card();
		}
		echo '</div>';
	}

	/**
	 * Load connector cards & settings.
	 *
	 * @since 8.15.0
	 */
	public static function wcap_load_connector_settings() {
		$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$connector_name = ucwords( $name );
		if ( '' !== $connector_name ) {
			$type           = '';
			$get_connectors = Wcap_Connectors_Common::wcap_get_connectors( $type );

			if ( ! is_array( $get_connectors ) || 0 === count( $get_connectors ) ) {
				return;
			}
			$connector = $get_connectors[ 'wcap_' . $name ];
			$connector->get_settings_view();
		}

		die();
	}
}
new Wcap_Integrations();
