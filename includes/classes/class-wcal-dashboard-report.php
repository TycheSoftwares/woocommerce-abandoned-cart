<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It shows the states on Dashboard tab.
 *
 * @author      Tyche Softwares
 * @package     Abandoned-Cart-Lite-for-WooCommerce
 * @category    Classes
 * @since       5.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcal_Dashoard_Report' ) ) {

	/**
	 * Dashboard Report for Lite.
	 */
	class Wcal_Dashboard_Report {

		/**
		 * Start Timestamp for filter.
		 *
		 * @var str start_timestamp - Start timestamp.
		 */
		public static $start_timestamp = '';

		/**
		 * End Timestamp for filter.
		 *
		 * @var str end_timestamp - End Timestamp.
		 */
		public static $end_timestamp = '';

		/**
		 * Total count of recovered orders.
		 *
		 * @var int recovered_count - Count
		 */
		public static $recovered_count = 0;

		/**
		 * Total count of abandoned carts.
		 *
		 * @var int $abandoned_count - Count.
		 */
		public static $abandoned_count = 0;

		/**
		 * HTML Code for dashboard.
		 */
		public static function wcal_dashboard_display() {
			$purchase_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite';

			?>
			<div id="wcal_dashboard_report" style="text-align: left;">
			<br class="clear">
				<form id="wcal_dash" method="get">
					<input type="hidden" name="page" value="woocommerce_ac_page" />

					<?php
					self::wcal_dashboard_filter();
					self::wcal_setup_filter_parms();

					$guest_emails_captured        = self::wcal_get_collected_email_count();
					$emails_sent_count            = self::wcal_get_emails_sent_count();
					$abandoned_carts_count        = self::wcal_get_abandoned_cart_count();
					$recovered_amount_unformatted = self::wcal_get_recovered_amount();
					$abandoned_amount_unformatted = self::wcal_abandoned_orders_amount();
					$placed_orders_amount         = self::wcal_placed_orders_amount();

					if ( self::$recovered_count > 0 && self::$abandoned_count > 0 ) {
						$percent_recovered = round( ( self::$recovered_count * 100 ) / ( self::$abandoned_count ), 2 );
						$percent_of_sales  = round( ( $recovered_amount_unformatted * 100 ) / ( $placed_orders_amount ), 2 );
					} else {
						$percent_recovered = 0;
						$percent_of_sales  = 0;
					}

					$graph_data = self::get_abandoned_data();

					wp_localize_script(
						'reports_js',
						'wcal_graph_data',
						array(
							'data' => $graph_data,
						)
					);
					wp_enqueue_script( 'reports_js' );
					?>

				</form>

				<div class="container-fluid">
					<div class="side-body">
						<div class="row">

							<!-- Blue Panel -->
							<div class="col-lg-3 col-md-3 col-sm-12">
								<div class="card panel-primary wcap-center">
									<div class="card-header panel-heading">
										<div class="huge padding-25">
											<?php echo esc_attr( get_woocommerce_currency_symbol() . $recovered_amount_unformatted ); ?>
										</div>
									</div>
									<div class="card-body panel-heading panel-body">
										<div class="body-label">
											<?php esc_html_e( 'Recovered Amount', 'woocommerce-abandoned-cart' ); ?>
										</div>
									</div>
									<div class="card-footer panel-footer">
										<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#recoveredDetails" aria-expanded="true" aria-controls="recoveredDetails">
											<span class="pull-left"><?php esc_html_e( 'View Details', 'woocommerce-abandoned-cart' ); ?></span> &nbsp;
											<span class="pull-right">
												<i class="fa fa-arrow-circle-right"></i>
											</span>
											<div class="clearfix"></div>
										</button>
										<div id="recoveredDetails" class="collapse" aria-labelledby="headingOne">
											<div class="card-body">
												<span>
												<?php
												// translators: Count of carts recovered.
												echo wp_kses_post( sprintf( __( '<strong>%s</strong> Recovered Orders', 'woocommerce-abandoned-cart' ), esc_attr( self::$recovered_count ) ) );
												?>
												</span>
												<br>
												<span>
												<?php
												// translators: recovered percent of carts.
												echo wp_kses_post( sprintf( __( '<strong>%s%%</strong> of Abandoned Carts Recovered', 'woocommerce-abandoned-cart' ), esc_attr( $percent_recovered ) ) );
												?>
												</span>
												<br>
												<span>
												<?php
												// translators: Percent of sales.
												echo wp_kses_post( sprintf( __( '<strong>%s%%</strong> of Total Revenue', 'woocommerce-abandoned-cart' ), esc_attr( $percent_of_sales ) ) );
												?>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Red Panel -->
							<div class="col-lg-3 col-md-3 col-sm-12">
								<div class="card panel-red wcap-center">
									<div class="card-header panel-heading">
										<div class="huge padding-25">
											<?php echo esc_attr( $abandoned_carts_count ); ?>
										</div>
									</div>
									<div class="card-body panel-heading panel-body">
										<div class="body-label">
											<?php esc_html_e( 'Abandoned Orders', 'woocommerce-abandoned-cart' ); ?>
										</div>
									</div>
									<div class="card-footer panel-footer">
										<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#abandonedCount" aria-expanded="true" aria-controls="abandonedCount">
											<span class="pull-left"><?php esc_html_e( 'View Details', 'woocommerce-abandoned-cart' ); ?></span> &nbsp;
											<span class="pull-right">
												<i class="fa fa-arrow-circle-right"></i>
											</span>
											<div class="clearfix"></div>
										</button>
										<div id="abandonedCount" class="collapse" aria-labelledby="headingOne">
											<div class="card-body">
												<span>
												<?php
												// translators: Abandoned Orders amount.
												echo esc_html( sprintf( __( '%s amount of Abandoned Orders', 'woocommerce-abandoned-cart' ), esc_attr( get_woocommerce_currency_symbol() . $abandoned_amount_unformatted ) ) );
												?>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Green Panel -->
							<div class="col-lg-3 col-md-3 col-sm-12">
								<div class="card panel-green wcap-center">
									<div class="card-header panel-heading">
										<div class="huge padding-25">
											<?php echo esc_attr( $emails_sent_count ); ?>
										</div>
									</div>
									<div class="card-body panel-heading panel-body">
										<div class="body-label">
											<?php esc_html_e( 'Number of Emails Sent', 'woocommerce-abandoned-cart' ); ?>
										</div>
									</div>
									<div class="card-footer panel-footer">
										<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#emailsCount" aria-expanded="true" aria-controls="emailsCount">
											<span class="pull-left"><?php esc_html_e( 'View Details', 'woocommerce-abandoned-cart' ); ?></span> &nbsp;
											<span class="pull-right">
												<i class="fa fa-arrow-circle-right"></i>
											</span>
											<div class="clearfix"></div>
										</button>
										<div id="emailsCount" class="collapse" aria-labelledby="headingOne">
											<div class="card-body">
												<span>
												<?php
												// translators: Link to Purchase the Pro version of the plugin.
												echo wp_kses_post( sprintf( __( "Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to view the number of emails opened and links clicked.", 'woocommerce-abandoned-cart' ), esc_attr( $purchase_link ) ) );
												?>
												</span><br>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Yellow Panel -->
							<div class="col-lg-3 col-md-3 col-sm-12">
								<div class="card panel-yellow wcap-center">
									<div class="card-header panel-heading">
										<div class="huge padding-25">
											<?php echo esc_attr( $guest_emails_captured ); ?>
										</div>
									</div>
									<div class="card-body panel-heading panel-body">
										<div class="body-label">
											<?php esc_html_e( 'Emails Captured', 'woocommerce-abandoned-cart' ); ?>
										</div>
									</div>
									<div class="card-footer panel-footer">
										<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#atcCount" aria-expanded="true" aria-controls="atcCount">
											<span class="pull-left"><?php esc_html_e( 'View Details', 'woocommerce-abandoned-cart' ); ?></span> &nbsp;
											<span class="pull-right">
												<i class="fa fa-arrow-circle-right"></i>
											</span>
											<div class="clearfix"></div>
										</button>
										<div id="atcCount" class="collapse" aria-labelledby="headingOne">
											<div class="card-body">
												<span>
												<?php
												// translators: Number of guest emails captured.
												echo esc_html( sprintf( __( '%s Guest emails captured.', 'woocommerce-abandoned-cart' ), esc_attr( $guest_emails_captured ) ) );
												?>
												</span>
												<span>
												<?php
												// translators: Link to Purchase the Pro version.
												echo wp_kses_post( sprintf( __( "Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to capture more guest carts.", 'woocommerce-abandoned-cart' ), esc_attr( $purchase_link ) ) );
												?>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="chartgraph"></div>

			</div>
			<?php
		}

		/**
		 * Adds the filter for date range in the dashboard.
		 */
		public static function wcal_dashboard_filter() {

			$duration_range_select = array(

				'this_month'   => __( 'This Month', 'woocommerce-abandoned-cart' ),
				'last_month'   => __( 'Last Month', 'woocommerce-abandoned-cart' ),
				'this_quarter' => __( 'This Quarter', 'woocommerce-abandoned-cart' ),
				'last_quarter' => __( 'Last Quarter', 'woocommerce-abandoned-cart' ),
				'this_year'    => __( 'This Year', 'woocommerce-abandoned-cart' ),
				'last_year'    => __( 'Last Year', 'woocommerce-abandoned-cart' ),
				'custom'       => __( 'Custom', 'woocommerce-abandoned-cart' ),
			);

			$duration_range = isset( $_GET['duration_select'] ) ? sanitize_text_field( wp_unslash( $_GET['duration_select'] ) ) : 'this_month'; //phpcs:ignore

			$start_date_range        = isset( $_GET['wcal_start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_start_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$end_date_range          = isset( $_GET['wcal_end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_end_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$start_end_date_div_show = ( ! isset( $_GET['duration_select'] ) || 'custom' !== $_GET['duration_select'] ) ? 'none' : 'block'; // phpcs:ignore WordPress.Security.NonceVerification
			?>
			<br>

			<div class="main_start_end_date" id="main_start_end_date" >
				<div class = "filter_date_drop_down" id = "filter_date_drop_down" >
					<label class="date_time_filter_label" for="date_time_filter_label" >
						<strong>
							<?php esc_html_e( 'Select date range:', 'woocommerce-abandoned-cart' ); ?>
						</strong>
					</label>

					<select id=duration_select name="duration_select" >
						<?php
						foreach ( $duration_range_select as $key => $value ) {
							$sel = '';
							if ( $key == $duration_range ) { // phpcs:ignore
								$sel = 'selected';
							}
							echo sprintf( "<option value='%s' %s>%s</option>", esc_attr( $key ), esc_attr( $sel ), esc_attr( __( $value, 'woocommerce-abandoned-cart' ) ) ); //phpcs:ignore
						}
						?>
					</select>
					<div class = "wcal_start_end_date_div" id = "wcal_start_end_date_div" style="display: <?php echo esc_attr( $start_end_date_div_show ); ?>;"  >
						<input type="text" id="wcal_start_date" name="wcal_start_date" readonly="readonly" value="<?php echo esc_attr( $start_date_range ); ?>" placeholder="yyyy-mm-dd"/>
						<input type="text" id="wcal_end_date" name="wcal_end_date" readonly="readonly" value="<?php echo esc_attr( $end_date_range ); ?>" placeholder="yyyy-mm-dd"/>
					</div>
					<div id="wcal_submit_button" class="wcal_submit_button">
						<button type="submit" class="button-secondary" id="wcal_search" value="go"><?php esc_html_e( 'Go', 'woocommerce-abandoned-cart' ); ?></button>
					</div>

				</div>
			</div>

			<?php
		}

		/**
		 * Setup the start & end time stamps to be used by all the functions to retrieve the data.
		 */
		public static function wcal_setup_filter_parms() {

			$duration_select = isset( $_GET['duration_select'] ) ? sanitize_text_field( wp_unslash( $_GET['duration_select'] ) ) : 'this_month'; //phpcs:ignore

			$current_time  = current_time( 'timestamp' ); // phpcs:ignore
			$current_month = date( 'n' ); //phpcs:ignore
			$current_year  = date( 'Y' ); //phpcs:ignore

			switch ( $duration_select ) {

				case 'this_month':
					self::$start_timestamp = mktime( 00, 01, 01, $current_month, 1 );
					self::$end_timestamp   = $current_time;
					break;

				case 'last_month':
					self::$start_timestamp = mktime( 00, 01, 01, $current_month - 1, 1 );
					self::$end_timestamp   = mktime( 23, 59, 59, $current_month - 1, date( 't' ) ); //phpcs:ignore
					break;

				case 'this_quarter':
					if ( $current_month >= 1 && $current_month <= 3 ) {
						self::$start_timestamp = mktime( 00, 01, 01, 1, 01 );
					} elseif ( $current_month >= 4 && $current_month <= 6 ) {
						self::$start_timestamp = mktime( 00, 01, 01, 4, 01 );
					} elseif ( $current_month >= 7 && $current_month <= 9 ) {
						self::$start_timestamp = mktime( 00, 01, 01, 7, 01 );
					} elseif ( $current_month >= 10 && $current_month <= 12 ) {
						self::$start_timestamp = mktime( 00, 01, 01, 10, 01 );
					}
					self::$end_timestamp = $current_time;
					break;

				case 'last_quarter':
					if ( $current_month >= 1 && $current_month <= 3 ) {
						self::$start_timestamp = strtotime( '01-October-' . ( $current_year - 1 ) . '00:01:01' );
						self::$end_timestamp   = strtotime( '31-December-' . ( $current_year - 1 ) . '23:59:59' );
					} elseif ( $current_month >= 4 && $current_month <= 6 ) {
						self::$start_timestamp = strtotime( "01-January-$current_year" . '00:01:01' );
						self::$end_timestamp   = strtotime( "31-March-$current_year" . '23:59:59' );
					} elseif ( $current_month >= 7 && $current_month <= 9 ) {
						self::$start_timestamp = strtotime( "01-April-$current_year" . '00:01:01' );
						self::$end_timestamp   = strtotime( "30-June-$current_year" . '23:59:59' );
					} elseif ( $current_month >= 10 && $current_month <= 12 ) {
						self::$start_timestamp = strtotime( "01-July-$current_year" . '00:01:01' );
						self::$end_timestamp   = strtotime( "30-September-$current_year" . '23:59:59' );
					}
					break;

				case 'this_year':
					self::$start_timestamp = mktime( 00, 01, 01, 1, 1, $current_year );
					self::$end_timestamp   = $current_time;
					break;

				case 'last_year':
					self::$start_timestamp = mktime( 00, 01, 01, 1, 1, $current_year - 1 );
					self::$end_timestamp   = mktime( 23, 59, 59, 12, 31, $current_year - 1 );
					break;

				case 'custom':
					$user_start = isset( $_GET['wcal_start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_start_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					$user_end   = isset( $_GET['wcal_end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_end_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

					if ( '' === $user_start ) {
						$user_start = date( 'Y-m-d', mktime( 00, 01, 01, $current_month, 1 ) ); //phpcs:ignore
						$user_end   = date( 'Y-m-d', $current_time ); //phpcs:ignore
					}

					if ( '' === $user_end ) {
						$user_end = date( 'Y-m-d', $current_time ); //phpcs:ignore
					}

					$start_explode         = explode( '-', $user_start );
					$end_explode           = explode( '-', $user_end );
					self::$start_timestamp = mktime( 00, 01, 01, $start_explode[1], $start_explode[2], $start_explode[0] ); //phpcs:ignore
					self::$end_timestamp   = mktime( 23, 59, 59, $end_explode[1], $end_explode[2], $end_explode[0] );
					break;

			}
		}

		/**
		 * Returns the abandoned cart count.
		 */
		public static function wcal_get_abandoned_cart_count() {
			global $wpdb;

			$start_time = self::$start_timestamp;
			$end_time   = self::$end_timestamp;

			$count_abandoned = $wpdb->get_var( $wpdb->prepare( 'SELECT count(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time >= %s AND abandoned_cart_time <= %s AND cart_ignored <> %s', $start_time, $end_time, '1' ) ); //phpcs:ignore
			return $count_abandoned;
		}

		/**
		 * Get the sent emails count.
		 */
		public static function wcal_get_emails_sent_count() {

			global $wpdb;

			$start_time = date( 'Y-m-d H:i:s', self::$start_timestamp ); //phpcs:ignore
			$end_time   = date( 'Y-m-d H:i:s', self::$end_timestamp ); //phpcs:ignore

			$count_sent = $wpdb->get_var( $wpdb->prepare( 'SELECT count(id) FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE sent_time >= %s AND sent_time <= %s', $start_time, $end_time ) ); //phpcs:ignore

			return $count_sent;
		}

		/**
		 * Get the recovered amount.
		 */
		public static function wcal_get_recovered_amount() {

			$start_time = self::$start_timestamp;
			$end_time   = self::$end_timestamp;

			$ids    = self::wcal_get_recovered_order_ids( $start_time, $end_time );
			$amount = 0;
			if ( is_array( $ids ) && count( $ids ) > 0 ) {

				self::$recovered_count = count( $ids );

				foreach ( $ids as $order_id ) {
					$amount += get_post_meta( $order_id, '_order_total', true );
				}
			}
			return $amount;

		}

		/**
		 * Returns the order IDs which have been recovered.
		 *
		 * @param string $start_time - Start Timestamp.
		 * @param string $end_time - End Timestamp.
		 */
		public static function wcal_get_recovered_order_ids( $start_time, $end_time ) {

			global $wpdb;

			$get_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time >= %s AND abandoned_cart_time <= %s AND cart_ignored = "1" AND recovered_cart > 0', $start_time, $end_time ) ); //phpcs:ignore

			return $get_ids;
		}

		/**
		 * Returns the collected guest emails.
		 */
		public static function wcal_get_collected_email_count() {

			global $wpdb;

			$start_time = self::$start_timestamp;
			$end_time   = self::$end_timestamp;

			$get_email_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time >= %s AND abandoned_cart_time <= %s AND user_id >= %d AND user_type = %s', $start_time, $end_time, 63000000, 'GUEST' ) ); //phpcs:ignore

			return $get_email_count;
		}

		/**
		 * Returns the amount for abandoned orders in the selected date range.
		 *
		 * @since 5.6
		 */
		public static function wcal_abandoned_orders_amount() {

			global $wpdb;

			$start_time            = self::$start_timestamp;
			$end_time              = self::$end_timestamp;
			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$get_carts = $wpdb->get_results( $wpdb->prepare( "SELECT abandoned_cart_info, recovered_cart FROM `$wpdb->prefix" . "ac_abandoned_cart_history_lite` WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time >= %s AND abandoned_cart_time <= %s AND cart_ignored <> %s", $blank_cart_info, $blank_cart_info_guest, $start_time, $end_time, '1' ) ); //phpcs:ignore

			$abandoned_amount = 0;
			$abandoned_count  = 0;
			if ( is_array( $get_carts ) && count( $get_carts ) > 0 ) {

				foreach ( $get_carts as $cart_value ) {

					if ( $cart_value->recovered_cart > 0 ) {
						$abandoned_amount += get_post_meta( $cart_value->recovered_cart, '_order_total', true );
						$abandoned_count++;
					} else {

						$cart_info = json_decode( stripslashes( $cart_value->abandoned_cart_info ) );

						if ( isset( $cart_info ) && false !== $cart_info && count( get_object_vars( $cart_info ) ) > 0 ) {
							$abandoned_count++;
							if ( isset( $cart_info->cart ) && count( get_object_vars( $cart_info->cart ) ) > 0 ) {
								foreach ( $cart_info->cart as $cart ) {
									if ( isset( $cart->line_total ) ) {
										$abandoned_amount += $cart->line_total;
									}
								}
							}
						}
					}
				}
			}

			self::$abandoned_count = $abandoned_count;
			return $abandoned_amount;
		}

		/**
		 * Returns the amount for all the placed orders in the selected date range.
		 *
		 * @since 5.6
		 */
		public static function wcal_placed_orders_amount() {

			global $wpdb;
			$count_month         = 0;
			$begin_date_of_month = date( 'Y-m-d H:i:s', self::$start_timestamp ); //phpcs:ignore
			$end_date_of_month   = date( 'Y-m-d H:i:s', self::$end_timestamp ); //phpcs:ignore

			$order_totals = $wpdb->get_row( //phpcs:ignore
				$wpdb->prepare(
					"SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts
					LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
					WHERE meta.meta_key = '_order_total'			
					AND posts.post_type = 'shop_order'
					AND posts.post_date >= %s
					AND posts.post_date <= %s
					AND posts.post_status IN ( '" . implode( "','", array( 'wc-completed', 'wc-processing', 'wc-on-hold' ) ) . "' )", //phpcs:ignore
					$begin_date_of_month,
					$end_date_of_month
				)
			);

			$count_month = null === $order_totals->total_sales ? 0 : $order_totals->total_sales;
			return $count_month;
		}

		/**
		 * Returned Abandoned & Recovered cart stats.
		 *
		 * @param string $selected_data_range - Range selected.
		 * @param string $start_date - Range Start Date.
		 * @param string $end_date - Range End Date.
		 * @return array - Stats Data.
		 *
		 * @since 5.8.8
		 */
		public static function get_adv_stats( $selected_data_range, $start_date, $end_date ) {
			global $wpdb;

			if ( '' === $start_date && '' === $end_date ) {
				return array();
			} else {
				$begin_of_month = strtotime( $start_date );
				$end_of_month   = strtotime( $end_date );
			}
			$count_month           = 0;
			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time = is_numeric( get_option( 'ac_lite_cart_abandoned_time', 10 ) ) ? get_option( 'ac_lite_cart_abandoned_time', 10 ) : 10;
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$count_month = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT abandoned_cart_info, recovered_cart FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite`
					WHERE abandoned_cart_time >=  %s
					AND abandoned_cart_time <= %s
					AND abandoned_cart_time <= %s
					AND ( cart_ignored <> '1' OR recovered_cart <> '0' )
					AND ( ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE %s ) OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s ) )",
					$begin_of_month,
					$end_of_month,
					$compare_time,
					"%$blank_cart_info%",
					$blank_cart_info_guest,
					"%$blank_cart_info%"
				)
			);

			$abandoned_count  = 0;
			$recovered_count  = 0;
			$abandoned_amount = 0;
			$recovered_amount = 0;

			foreach ( $count_month as $cart_value ) {

				$abandoned_count++;

				if ( (int) $cart_value->recovered_cart > 0 ) {
					$recovered_count++;
				}

				$cart_info = json_decode( stripslashes( $cart_value->abandoned_cart_info ) );
				if ( isset( $cart_info->cart ) ) {
					foreach ( $cart_info->cart as $cart ) {
						$abandoned_amount += isset( $cart->line_total ) ? $cart->line_total : 0;
						$recovered_id      = $cart_value->recovered_cart;
						if ( (int) $recovered_id > 0 ) {
							$rec_order_total   = get_post_meta( $recovered_id, '_order_total', true );
							$recovered_amount += isset( $rec_order_total ) && $rec_order_total > 0 ? $rec_order_total : 0;
						}
					}
				}
			}

			return array(
				'abandoned_count'  => $abandoned_count,
				'recovered_count'  => $recovered_count,
				'abandoned_amount' => $abandoned_amount,
				'recovered_amount' => $recovered_amount,
			);
		}
		/**
		 * Get Graph Data
		 *
		 * @since 5.8.8
		 */
		public static function get_abandoned_data() {

			$start_timestamp = self::$start_timestamp;
			$end_timestamp   = self::$end_timestamp;

			$current_date  = date( 'd' ); // phpcs:ignore
			$current_month = date( 'm' ); // phpcs:ignore
			$current_year  = date( 'Y' ); // phpcs:ignore

			$selected_data_range = isset( $_GET['duration_select'] ) ? sanitize_text_field( wp_unslash( $_GET['duration_select'] ) ) : 'this_month'; //phpcs:ignore
			switch ( $selected_data_range ) {
				case 'this_month':
					$display_freq  = $current_date > 15 ? 'weekly' : 'daily';
					$end_timestamp = current_time( 'timestamp' ); // phpcs:ignore
					break;
				case 'last_month':
				case 'this_quarter':
				case 'last_quarter':
					$display_freq = 'weekly';
					break;
				case 'this_year':
					$display_freq  = $current_month > 3 ? 'monthly' : 'weekly';
					$end_timestamp = current_time( 'timestamp' ); // phpcs:ignore
					break;
				case 'last_year':
					$display_freq = 'monthly';
					break;
				case 'custom':
					$display_freq   = 'weekly';
					$number_of_days = round( ( $end_timestamp - $start_timestamp ) / ( 60 * 60 * 24 ) );
					if ( is_numeric( $number_of_days ) && $number_of_days > 0 ) {
						if ( $number_of_days <= 15 ) {
							$display_freq = 'daily';
						} elseif ( $number_of_days <= 90 ) {
							$display_freq = 'weekly';
						} else {
							$display_freq = 'monthly';
						}
					}
					break;
			}

			$data = self::wcap_get_graph_data( $selected_data_range, $start_timestamp, $end_timestamp, $display_freq );
			return $data;

		}

		/**
		 * Collect Graph data to be displayed & return.
		 *
		 * @param string    $selected_data_range - Selected Date Range.
		 * @param timestamp $start_timestamp - Start Timestamp.
		 * @param timestamp $end_timestamp - End Timestamp.
		 * @param string    $display_freq - Display Frequency.
		 * @return array $data - Data to be returned.
		 *
		 * @since 5.8.8
		 */
		public static function wcap_get_graph_data( $selected_data_range, $start_timestamp, $end_timestamp, $display_freq ) {

			$start_date = date( 'Y-m-d H:i:s', $start_timestamp ); // phpcs:ignore
			switch ( $display_freq ) {
				case 'daily':
					$range_end = date( 'Y-m-d H:i:s', strtotime( '+1 day', $start_timestamp ) ); // phpcs:ignore
					do {
						$get_stats                   = self::get_adv_stats( $selected_data_range, $start_date, $range_end );
						$start_date_display          = date( 'd M', strtotime( $start_date ) ); // phpcs:ignore
						$data[ $start_date_display ] = array(
							'abandoned_amount' => $get_stats['abandoned_amount'],
							'recovered_amount' => $get_stats['recovered_amount'],
						);
						$start_date                  = date( 'Y-m-d H:i:s', strtotime( $range_end ) ); // phpcs:ignore
						$range_end                   = date( 'Y-m-d', strtotime( "$start_date +1 day" ) ); // phpcs:ignore
					} while ( strtotime( $start_date ) < $end_timestamp );
					break;
				case 'weekly':
					$range_end       = date( 'Y-m-d H:i:s', strtotime( '+7 days', $start_timestamp ) ); // phpcs:ignore
					$range_end_stamp = strtotime( $range_end );

					do {
						if ( $range_end_stamp > $end_timestamp ) {
							$range_end       = date( 'Y-m-d', $end_timestamp ); // phpcs:ignore
							$range_end_stamp = $end_timestamp;
						}

						$get_stats                   = self::get_adv_stats( $selected_data_range, $start_date, $range_end );
						$start_date_display          = date( 'd M', strtotime( $start_date ) ); // phpcs:ignore
						$data[ $start_date_display ] = array(
							'abandoned_amount' => $get_stats['abandoned_amount'],
							'recovered_amount' => $get_stats['recovered_amount'],
						);
						$start_date                  = date( 'Y-m-d H:i:s', $range_end_stamp ); // phpcs:ignore
						$range_end                   = date( 'Y-m-d', strtotime( "$start_date +7 days" ) ); // phpcs:ignore
						$range_end_stamp             = strtotime( $range_end );

					} while ( strtotime( $start_date ) < $end_timestamp );
					break;
				case 'monthly':
					$range_end = date( 'Y-m-d H:i:s', strtotime( '+1 month', $start_timestamp ) ); // phpcs:ignore
					do {
						$get_stats                   = self::get_adv_stats( $selected_data_range, $start_date, $range_end );
						$start_date_display          = date( 'M y', strtotime( $start_date ) ); // phpcs:ignore
						$data[ $start_date_display ] = array(
							'abandoned_amount' => $get_stats['abandoned_amount'],
							'recovered_amount' => $get_stats['recovered_amount'],
						);
						$start_date                  = date( 'Y-m-d H:i:s', strtotime( $range_end ) ); // phpcs:ignore
						$range_end                   = date( 'Y-m-d', strtotime( "$start_date +1 month" ) ); // phpcs:ignore
					} while ( strtotime( $start_date ) < $end_timestamp );

					break;
			}
			return $data;
		}
	}
}
?>
