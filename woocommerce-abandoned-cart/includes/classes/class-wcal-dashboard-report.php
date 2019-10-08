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
		 * HTML Code for dashboard.
		 */
		public static function wcal_dashboard_display() {
			$purchase_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite';
			?>
			<div id="wcal_dashboard_report" style="text-align: left;">
			<br class="clear">
				<form id="wcal_dash" method="get">
					<input type="hidden" name="page" value="woocommerce_ac_page" />
					<input type="hidden" name="action" value="report" />
					
					<?php
					self::wcal_dashboard_filter();
					self::wcal_setup_filter_parms();
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
											<?php echo esc_attr( get_woocommerce_currency_symbol() . self::wcal_get_recovered_amount() ); ?>
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
												<span><?php printf( __( '<strong>%s</strong> Recovered Orders', 'woocommerce-abandoned-cart' ), self::$recovered_count ); ?></span>
												<br>
												<span><?php printf( __( "Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to view detailed reports.", 'woocommerce-abandoned-cart' ), $purchase_link); ?></span>
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
											<?php echo esc_attr( self::wcal_get_abandoned_cart_count() ); ?>
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
												<span><?php printf( __( "Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to view detailed reports.", 'woocommerce-abandoned-cart' ), $purchase_link); ?></span>
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
											<?php echo esc_attr( self::wcal_get_emails_sent_count() ); ?>
										</div>
									</div>
									<div class="card-body panel-heading panel-body">
										<div class="body-label">
											<?php esc_html_e( 'Number of Emails Sent', 'woocommerce-abandoned-cart' ); ?>
										</div>
									</div>
									<div class="card-footer panel-footer">
										<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#emailsCount" aria-expanded="true" aria-controls="emailsCount">
											<span class="pull-left"><?php _e( 'View Details', 'woocommerce-abandoned-cart' ); ?></span> &nbsp;
											<span class="pull-right">
												<i class="fa fa-arrow-circle-right"></i>
											</span>
											<div class="clearfix"></div>
										</button>
										<div id="emailsCount" class="collapse" aria-labelledby="headingOne">
											<div class="card-body">
												<span><?php printf( __( "Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to view the number of emails opened and links clicked.", 'woocommerce-abandoned-cart' ), $purchase_link); ?></span><br>
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
											<?php echo esc_attr( self::wcal_get_collected_email_count() ); ?>
										</div>
									</div>
									<div class="card-body panel-heading panel-body">
										<div class="body-label">
											<?php esc_html_e( 'Emails Captured', 'woocommerce-abandoned-cart' ); ?>
										</div>
									</div>
									<div class="card-footer panel-footer">
										<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#atcCount" aria-expanded="true" aria-controls="atcCount">
											<span class="pull-left"><?php _e( 'View Details', 'woocommerce-abandoned-cart' ); ?></span> &nbsp;
											<span class="pull-right">
												<i class="fa fa-arrow-circle-right"></i>
											</span>
											<div class="clearfix"></div>
										</button>
										<div id="atcCount" class="collapse" aria-labelledby="headingOne">
											<div class="card-body">
												<span><?php printf( __( "Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to capture more guest carts.", 'woocommerce-abandoned-cart' ), $purchase_link); ?> Emails Captured</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Adds the filter for date range in the dashboard.
		 */
		public static function wcal_dashboard_filter() {

			$duration_range_select = array(

				'last_week'    => __( 'Last 7 Days', 'woocommerce-abandoned-cart' ),
				'this_month'   => __( 'This Month', 'woocommerce-abandoned-cart' ),
				'last_month'   => __( 'Last Month', 'woocommerce-abandoned-cart' ),
				'this_quarter' => __( 'This Quarter', 'woocommerce-abandoned-cart' ),
				'last_quarter' => __( 'Last Quarter', 'woocommerce-abandoned-cart' ),
				'this_year'    => __( 'This Year', 'woocommerce-abandoned-cart' ),
				'last_year'    => __( 'Last Year', 'woocommerce-abandoned-cart' ),
			);

			$duration_range = isset( $_GET['duration_select'] ) ? sanitize_text_field( wp_unslash( $_GET['duration_select'] ) ) : 'last_week'; //phpcs:ignore
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
							if ( $key == $duration_range ) {
								$sel = 'selected';
							}
							echo sprintf( "<option value='%s' %s>%s</option>", esc_attr( $key ), esc_attr( $sel ), esc_attr( __( $value, 'woocommerce-abandoned-cart' ) ) );
						}
						?>
					</select>
					&nbsp;&nbsp;&nbsp;
					<button type="submit" class="button-secondary" id="wcal_search" value="go"><?php esc_html_e( 'Go', 'woocommerce-abandoned-cart' ); ?></button>
					
				</div>
			</div>

			<?php
		}

		/**
		 * Setup the start & end time stamps to be used by all the functions to retrieve the data.
		 */
		public static function wcal_setup_filter_parms() {

			$duration_select = isset( $_GET['duration_select'] ) ? sanitize_text_field( wp_unslash( $_GET['duration_select'] ) ) : 'last_week'; //phpcs:ignore

			$current_time  = current_time( 'timestamp' );
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
						self::$start_timestamp = strtotime( '01-October-' . $current_year - 1 . '00:01:01' );
						self::$end_timestamp   = strtotime( '31-December-' . $current_year - 1 . '23:59:59' );
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

				case 'last_week':
					self::$start_timestamp = mktime( 00, 01, 01, $current_month, date( 'd' ) - 7 ); //phpcs:ignore
					self::$end_timestamp   = $current_time;
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

			$count_abandoned = $wpdb->get_var( $wpdb->prepare( 'SELECT count(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time >= %s AND abandoned_cart_time <= %s', $start_time, $end_time ) ); //phpcs:ignore
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
	}

}
?>
