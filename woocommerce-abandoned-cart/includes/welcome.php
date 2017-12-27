<?php 

/**
 * Abandoned Cart Lite Welcome Page Class
 *
 * Displays on plugin activation
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wcal_Welcome Class
 *
 * A general class for About page.
 *
 * @since 4.5
 */
class Wcal_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 *
	 * @since 4.5
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );

		if ( !isset( $_GET[ 'page' ] ) || 
			( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] != 'wcal-about' ) ) {
			add_action( 'admin_init', array( $this, 'welcome' ) );
		}
	}

	/**
	 * Register the Dashboard Page which is later hidden but this pages
	 * is used to render the Welcome page.
	 *
	 * @access public
	 * @since  4.5
	 * @return void
	 */
	public function admin_menus() {
		$display_version = WCAL_VERSION;

		// About Page
		add_dashboard_page(
			sprintf( esc_html__( 'Welcome to Abandoned Cart Lite %s', 'woocommerce-ac' ), $display_version ),
			esc_html__( 'Welcome to Abandoned Cart Lite', 'woocommerce-ac' ),
			$this->minimum_capability,
			'wcal-about',
			array( $this, 'about_screen' )
		);

	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since  4.5
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'wcal-about' );
	}

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since  4.5
	 * @return void
	 */
	public function about_screen() {
		$display_version = WCAL_VERSION;
		// Badge for welcome page
		$badge_url = WCAL_PLUGIN_URL . 'assets/images/icon-256x256.png';		
		?>
		<style>
			.feature-section .feature-section-item {
				float:left;
				width:48%;
			}
		</style>
        <div class="wrap about-wrap">

			<?php $this->get_welcome_header() ?>

            <div style="float:left;width: 80%;">
            <p class="about-text" style="margin-right:20px;"><?php
				printf(
					__( "Thank you for activating or updating to the latest version of Abandoned Cart Lite! If you're a first time user, welcome! You're well on your way to start recovering your lost revenues." )
				);
				?></p>
			</div>
            <div class="wcal-badge"><img src="<?php echo $badge_url; ?>" style="width:150px;"/></div>

            <p>&nbsp;</p>

            <div class="feature-section clearfix introduction">

                <h3><?php esc_html_e( "Get Started with Abandoned Cart Lite", 'woocommerce-ac' ); ?></h3>

                <div class="video feature-section-item" style="float:left;padding-right:10px;">
                    <img src="<?php echo WCAL_PLUGIN_URL . '/assets/images/abandoned-cart-lite-email-templates.png' ?>"
                         alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-ac' ); ?>" style="width:600px;">
                </div>

                <div class="content feature-section-item last-feature">
                    <h3><?php esc_html_e( 'Activate Email Template', 'woocommerce-ac' ); ?></h3>

                    <p><?php esc_html_e( 'To start sending out abandoned cart notification emails, simply activate the email template from under WooCommerce -> Abandoned Carts -> Email Templates page.', 'woocommerce-ac' ); ?></p>
                    <a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" target="_blank" class="button-secondary">
						<?php esc_html_e( 'Click Here to go to Email Templates page', 'woocommerce-ac' ); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>
                </div>
            </div>

            <!-- /.intro-section -->

            <div class="content">

                <h3><?php esc_html_e( "Know more about Abandoned Cart Pro", 'woocommerce-ac' ); ?></h3>

                <p><?php _e( 'The Abandoned Cart Pro plugin gives you features where you are able to recover more sales compared to the Lite plugin. Here are some notable features the Pro version provides.' ); ?></p>

	            <div class="feature-section clearfix introduction">
	                <div class="video feature-section-item" style="float:left;padding-right:10px;">
	                    <img src="https://www.tychesoftwares.com/wp-content/uploads/2017/08/atc_frontend.png"
	                         alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-ac' ); ?>" style="width:500px;">
	                </div>

	                <div class="content feature-section-item last-feature">
	                    <h3><?php esc_html_e( 'Capture Visitor Emails on click of Add to Cart button', 'woocommerce-ac' ); ?></h3>

	                    <p><?php esc_html_e( 'The ability to capture the email address early in the order process is very important to reduce cart abandonment by unknown users as well as to be able to recover their carts if they abandon it. This ultimately leads to increase in your store sales.', 'woocommerce-ac' ); ?></p>
	                    <a href="https://www.tychesoftwares.com/capture-guest-user-email-address-before-checkout-page-with-woocommerce-abandoned-cart-pro/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
							<?php esc_html_e( 'Learn More', 'woocommerce-ac' ); ?>
	                        <span class="dashicons dashicons-external"></span>
	                    </a>
	                </div>
	            </div>

				<div class="feature-section clearfix">
	                <div class="content feature-section-item">

	                	<h3><?php esc_html_e( 'Set different cut-off times for visitors & logged-in users', 'woocommerce-ac' ); ?></h3>

		                    <p><?php esc_html_e( 'The provision for setting two separate cut-off times for different roles is mainly because sometimes if the store admin wants the visitor carts to be captured earlier than the registered user carts, then these different settings can play an important role.', 'woocommerce-ac' ); ?></p>
		                    <a href="https://www.tychesoftwares.com/capturing-abandoned-carts-woocommerce-abandoned-cart-pro-plugin/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
								<?php esc_html_e( 'Learn More', 'woocommerce-ac' ); ?>
		                        <span class="dashicons dashicons-external"></span>
		                    </a>
	                </div>

	                <div class="content feature-section-item last-feature">
	                    <img src="<?php echo WCAL_PLUGIN_URL . 'assets/images/abandon-cart-cut-off-time.png'; ?>" alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-ac' ); ?>" style="width:450px;">
	                </div>
	            </div>

       
	            <div class="feature-section clearfix introduction">
	                <div class="video feature-section-item" style="float:left;padding-right:10px;">
	                    <img src="<?php echo WCAL_PLUGIN_URL . 'assets/images/email-templates-send-time.png'; ?>" alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-ac' ); ?>" style="width:450px;">
	                </div>

	                <div class="content feature-section-item last-feature">
	                    <h3><?php esc_html_e( 'Send abandoned cart recovery email in minutes of cart being abandoned', 'woocommerce-ac' ); ?></h3>

	                    <p><?php esc_html_e( 'The ability to send the abandoned cart recovery email within first few minutes of cart being abandoned is a big advantage. In the Lite plugin, the earliest an email can be sent is after 1 hour. Whereas in the Pro version, the first recovery email gets sent 15 minutes after the cart is abandoned. This increases the recovery chances manifold.', 'woocommerce-ac' ); ?></p>
	                    <a href="https://www.tychesoftwares.com/understanding-the-default-email-templates-of-abandoned-cart-pro-for-woocommerce-plugin/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
							<?php esc_html_e( 'Learn More', 'woocommerce-ac' ); ?>
	                        <span class="dashicons dashicons-external"></span>
	                    </a>
	                </div>
	            </div>

				<div class="feature-section clearfix">
	                <div class="content feature-section-item">

	                	<h3><?php esc_html_e( 'Full range of merge tags that allow you to personalize the abandoned cart email', 'woocommerce-ac' ); ?></h3>

		                    <p><?php esc_html_e( 'The Lite version has only 3 merge tags available to personalize the abandoned cart recovery emails. The Pro version instead, has 20 different merge tags that can be used effectively to personalize each email that gets sent out to the customers for recovering their abandoned carts.', 'woocommerce-ac' ); ?></p>
		                    <a href="https://www.tychesoftwares.com/understanding-the-default-email-templates-of-abandoned-cart-pro-for-woocommerce-plugin/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
								<?php esc_html_e( 'Learn More', 'woocommerce-ac' ); ?>
		                        <span class="dashicons dashicons-external"></span>
		                    </a>
	                </div>

	                <div class="content feature-section-item last-feature">
	                    <img src="https://www.tychesoftwares.com/wp-content/uploads/2016/10/drop-down-of-AC.png" alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-ac' ); ?>" style="width:450px;">
	                </div>
	            </div>

                <a href="https://www.tychesoftwares.com/differences-between-pro-and-lite-versions-of-abandoned-cart-for-woocommerce-plugin/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
					<?php esc_html_e( 'View full list of differences between Lite & Pro plugin', 'woocommerce-ac' ); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </div>

            <div class="feature-section clearfix">

                <div class="content feature-section-item">

                    <h3><?php esc_html_e( 'Getting to Know Tyche Softwares', 'woocommerce-ac' ); ?></h3>

                    <ul class="ul-disc">
                        <li><a href="https://tychesoftwares.com/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank"><?php esc_html_e( 'Visit the Tyche Softwares Website', 'woocommerce-ac' ); ?></a></li>
                        <li><a href="https://tychesoftwares.com/premium-plugins/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank"><?php esc_html_e( 'View all Premium Plugins', 'woocommerce-ac' ); ?></a>
                        <ul class="ul-disc">
                        	<li><a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank">Abandoned Cart Pro Plugin for WooCommerce</a></li>
                        	<li><a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank">Booking & Appointment Plugin for WooCommerce</a></li>
                        	<li><a href="https://www.tychesoftwares.com/store/premium-plugins/order-delivery-date-for-woocommerce-pro-21/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank">Order Delivery Date for WooCommerce</a></li>
                        	<li><a href="https://www.tychesoftwares.com/store/premium-plugins/product-delivery-date-pro-for-woocommerce/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank">Product Delivery Date for WooCommerce</a></li>
                        	<li><a href="https://www.tychesoftwares.com/store/premium-plugins/deposits-for-woocommerce/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank">Deposits for WooCommerce</a></li>
                        </ul>
                        </li>
                        <li><a href="https://tychesoftwares.com/about/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank"><?php esc_html_e( 'Meet the team', 'woocommerce-ac' ); ?></a></li>
                    </ul>

                </div>


                <div class="content feature-section-item">

                    <h3><?php esc_html_e( 'Current Offers', 'woocommerce-ac' ); ?></h3>

                    <p>Buy all our <a href="https://tychesoftwares.com/premium-plugins/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank">premium plugins</a> at 30% off till 31st December 2017</p>

                </div>

            </div>            
            <!-- /.feature-section -->

        </div>
		<?php

		update_option( 'wcal_welcome_page_shown', 'yes' );
		update_option( 'wcal_welcome_page_shown_time', current_time( 'timestamp' ) );
	}


	/**
	 * The header section for the welcome screen.
	 *
	 * @since 4.5
	 */
	public function get_welcome_header() {
		// Badge for welcome page
		$badge_url = WCAL_PLUGIN_URL . 'assets/images/icon-256x256.png';
		?>
        <h1 class="welcome-h1"><?php echo get_admin_page_title(); ?></h1>
		<?php $this->social_media_elements(); ?>

	<?php }


	/**
	 * Social Media Like Buttons
	 *
	 * Various social media elements to Tyche Softwares
	 */
	public function social_media_elements() { ?>

        <div class="social-items-wrap">

            <iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Ftychesoftwares&amp;send=false&amp;layout=button_count&amp;width=100&amp;show_faces=false&amp;font&amp;colorscheme=light&amp;action=like&amp;height=21&amp;appId=220596284639969"
                    scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;"
                    allowTransparency="true"></iframe>

            <a href="https://twitter.com/tychesoftwares" class="twitter-follow-button" data-show-count="false"><?php
				printf(
					esc_html_e( 'Follow %s', 'tychesoftwares' ),
					'@tychesoftwares'
				);
				?></a>
            <script>!function (d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
                    if (!d.getElementById(id)) {
                        js = d.createElement(s);
                        js.id = id;
                        js.src = p + '://platform.twitter.com/widgets.js';
                        fjs.parentNode.insertBefore(js, fjs);
                    }
                }(document, 'script', 'twitter-wjs');
            </script>

        </div>
        <!--/.social-items-wrap -->

		<?php
	}


	/**
	 * Sends user to the Welcome page on first activation of Abandoned Cart Lite as well as each
	 * time Abandoned Cart Lite is upgraded to a new version
	 *
	 * @access public
	 * @since  4.5
	 *
	 * @return void
	 */
	public function welcome() {

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		if( !get_option( 'wcal_welcome_page_shown' ) ) {
			wp_safe_redirect( admin_url( 'index.php?page=wcal-about' ) );
			exit;
		}
	}

}

new Wcal_Welcome();
