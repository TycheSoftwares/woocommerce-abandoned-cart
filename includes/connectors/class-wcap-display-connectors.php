<?php
/**
 * Display connector cards in admin.
 *
 * @package Includes/Connectors.
 */

/**
 * Class for display connectors.
 */
class Wcap_Display_Connectors {

	/**
	 * Construct.
	 *
	 * @param string $slug - Slug.
	 * @param string $connector - Connector.
	 */
	public function __construct( $slug, $connector ) {

		$this->type      = '';
		$this->image     = $connector->get_image();
		$this->name      = $connector->name;
		$this->desc      = $connector->desc;
		$this->slug      = $slug;
		$this->connector = $connector;
	}

	/**
	 * Get Type.
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get connector name.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get connector description.
	 */
	public function get_desc() {
		return $this->desc;
	}

	/**
	 * Get connector slug.
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Buttons in card for connected integrations.
	 *
	 * @param string $connector_slug - Connector slug.
	 */
	public function wcap_connected_buttons( $connector_slug = '' ) {

		$connector_slug = '' === $connector_slug ? $this->get_slug() : $connector_slug;
		$buttons_html   = '<button class="wcap_button_disconnect" data-wcap-title="' . $this->name . '" data-wcap-name="' . $connector_slug . '"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>' . __( 'Disconnect', 'woocommerce-ac' ) . '</button>';
		if ( ! isset( $this->connector->disable_sync ) ) {
			$buttons_html .= '<button class="wcap_button_sync" data-wcap-name="' . $connector_slug . '">' . __( 'Sync', 'woocommerce-ac' ) . '</button>';
		}
		$buttons_html .= '<button class="wcap_settings wcap_button_connect" data-wcap-title="' . $this->name . '" data-wcap-name="' . $connector_slug . '">' . __( 'Settings', 'woocommerce-ac' ) . '</button>';
		return $buttons_html;

	}

	/**
	 * Connect buttons.
	 *
	 * @param string $connector_slug - Connector Slug.
	 */
	public function wcap_connect_button( $connector_slug = '' ) {
		$connector_slug = '' === $connector_slug ? $this->get_slug() : $connector_slug;
		return '<button class="button-primary wcap_main_connect wcap_button_connect" disabled data-wcap-title="' . $this->name . '" data-wcap-name="' . $connector_slug . '" >' . __( 'Connect', 'woocommerce-ac' ) . '</button>';
	}

	/**
	 * Display buttons in card.
	 */
	public function button() {
		$connector_details = Wcap_Connectors_Common::wcap_get_connectors_data( $this->get_slug() );
		$connected_buttons = $this->wcap_connected_buttons( $this->get_slug() );
		$connect_buttons   = $this->wcap_connect_button( $this->get_slug() );
		$connector_status  = isset( $connector_details['status'] ) ? $connector_details['status'] : '';
		$display_connected = 'active' === $connector_status ? 'display:block;' : 'display:none;';
		$display_connect   = 'active' === $connector_status ? 'display:none;' : 'display:block;';
		echo "<div id='" . $this->get_slug() . "_connect_div' class='wcap_connect_buttons' style='$display_connect'>$connect_buttons</div>"; // phpcs:ignore
		echo "<div id='" . $this->get_slug() . "_connected_div' class='wcap_connected_buttons' style='$display_connected'>$connected_buttons</div>"; // phpcS:ignore
	}

	/**
	 * Print connector card.
	 */
	public function print_card() {
		?>
		<div class="wcap-col-md-4">
			<div class="wcap-connector-wrap" data-type="<?php echo esc_attr( $this->get_type() ); ?>">
				<div class="wcap-connector_card_outer" style='padding: 20px 15px;'>
					<div class="wcap-connector-img-outer">
						<div class="wcap-connector-img">
							<div class="wcap-connector-img-section">
								<img class='wcap_connector_icon' src="<?php echo esc_url( $this->image ); ?>"/>
							</div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="wcap_connector_info">
						<div class="wcap_connector_info_head"><?php echo esc_html( $this->get_name() ); ?></div>
						<div class="wcap_connector_info_details"><?php echo esc_html__( $this->get_desc(), 'woocommerce-ac' ); // phpcs:ignore ?></div>
					</div>
					<div class="clear"></div>
				</div>
				<div class="wcap-connector-action">
					<div class="wcap-connector-btns">
						<?php $this->button(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

}
