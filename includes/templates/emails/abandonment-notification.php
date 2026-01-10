<?php
$text_align = is_rtl() ? 'right' : 'left';
?>
<p>
	<?php echo esc_html__( 'Hi', 'woocommerce-abandoned-cart' ); ?>,
</p>
<div>
	<p>
		<?php
		echo sprintf(
			// Translators: Site Name.
			esc_html__( 'We are writing to let you know that a cart has been abandoned on %s', 'woocommerce-abandoned-cart' ),
			esc_html( $blog_name )
		);
		?>
	</p>

	<p>
		<strong>
			<?php
			echo sprintf(
				// Translators: cart ID & Cart Abandonment Time.
				esc_html__( 'Cart #%1$s (%2$s)', 'woocommerce-abandoned-cart' ),
				esc_html( $cart_details->id ),
				esc_attr( $cart_details->abandoned_time )
			);
			?>
		</strong>
		<br>
		<?php
		echo sprintf(
			// Translators: Cart Source.
			esc_html__( 'The cart was captured by %1$s', 'woocommerce-abandoned-cart' ),
			esc_html( $cart_details->source )
		);
		?>
	</p>
</div>
<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'woocommerce-abandoned-cart' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Quantity', 'woocommerce-abandoned-cart' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Price', 'woocommerce-abandoned-cart' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$i = 0;
			foreach ( $cart_details->product_data as $details ) {
				$product = wc_get_product( $details->product_id );
				$sku     = is_object( $product ) ? $product->get_sku() : '';
				$i++;
				?>
				<tr>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
					<?php
					$display_args        = array(
						'display_own_line' => false,
						'position'         => 'below_name',
					);
					$display_args        = apply_filters( 'wcap_display_sku_in_own_line_admin_email', $display_args );
					$show_sku_separately = isset( $display_args['display_own_line'] ) ? $display_args['display_own_line'] : false;
					$position            = isset( $display_args['position'] ) ? $display_args['position'] : 'below_name';

					if ( $show_sku_separately && 'above_name' === $position ) {
						echo wp_kses_post( '<p class="product-sku">' . $sku . '</p>' );
					}
					// Product name.
					echo wp_kses_post( apply_filters( 'wcap_product_name', $details->product_name, $details->product_id, false ) );

					if ( ! $show_sku_separately ) {// SKU.
						if ( '' !== $sku ) {
							echo wp_kses_post( ' (#' . $sku . ')' );
						}
					}

					if ( $show_sku_separately && 'below_name' === $position ) {
						echo wp_kses_post( '<p class="product-sku">' . $sku . '</p>' );
					}

					?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php
						$qty_display = esc_html( $details->quantity );
						echo wp_kses_post( apply_filters( 'wcap_email_quantity', $qty_display, $details->product_id ) );
						?>
					</td>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo wp_kses_post( apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $details->line_subtotal, $cart_details->currency ), $cart_details->id, $details->line_subtotal, 'wcap_cron' ) ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
		<tfoot>
			<?php
			$item_totals = $cart_details->totals_data;

			if ( is_array( $item_totals ) && count( $item_totals ) > 0 ) {
				$i = 0;
				foreach ( $item_totals as $total ) {
					$price = apply_filters( 'acfac_change_currency', wcal_Common::wcal_get_price( $total['value'], $cart_details->currency ), $cart_details->id, $total['value'], 'wcap_cron' );
					$i++;
					?>
					<tr>
						<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['label'] ); ?></th>
						<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $price ); ?></td>
					</tr>
					<?php
				}
			}
			?>
		</tfoot>
	</table>
</div>
<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
	<p>
		<strong><i>
			<?php
			echo esc_html__( 'Customer Details', 'woocommerce-abandoned-cart' );
			?>
		</i></strong>
	</p>
	<address>
		<?php
		echo esc_html( sprintf( '%1$s %2$s', $customer_details->firstname, $customer_details->lastname ) );
		?>
		<br>
		<?php
		// Translators: Phone.
		echo esc_html( sprintf( __( 'Phone: %s', 'woocommerce-abandoned-cart' ), $customer_details->phone ) );
		?>
		<br>
		<?php
		// Translators: Email.
		echo esc_html( sprintf( __( 'Email: %s', 'woocommerce-abandoned-cart' ), $customer_details->email ) );
		?>
	</address>
	<?php do_action( 'wcap_admin_notification_email_customer_details', $cart_details->id ); ?>
</div>
<div>
	<p>
		<?php
		// translators: link to the WP admin dashboard.
		echo wp_kses_post( sprintf( __( 'You can check the cart details %s', 'woocommerce-abandoned-cart' ), '<a href="' . $cart_link . '" target="_blank">' . __( 'here', 'woocommerce-abandoned-cart' ) . '</a>' ) );
		?>
</div>
<?php do_action( 'wcap_admin_notification_email_above_footer', $cart_details->id ); ?>
