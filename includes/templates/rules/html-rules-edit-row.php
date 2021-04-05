<?php
/**
 * This file will add functions related to verifying email present on ATC field.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/ATC
 * @since 8.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$selected_rule_type  = isset( $rule_type ) ? $rule_type : '';
$selected_rule_cond  = isset( $rule_condition ) ? $rule_condition : '';
$selected_rule_value = isset( $rule_value ) ? $rule_value : '';

$rule_type_options = apply_filters(
	'wcap_rules_engine_rule_type_values',
	array(
		'select_disabled'  => __( 'Select Rule Type', 'woocommerce-ac' ),
		'coupons'          => __( 'Coupons', 'woocommerce-ac' ),
		'send_to'          => __( 'Send Emails to', 'woocommerce-ac' ),
		'order_disabled'   => __( 'Order', 'woocommerce-ac' ),
		'payment_gateways' => __( 'Payment Gateways', 'woocommerce-ac' ),
		'cart_disabled'    => __( 'Cart', 'woocommerce-ac' ),
		'cart_status'      => __( 'Cart Status', 'woocommerce-ac' ),
		'product_cat'      => __( 'Product Categories', 'woocommerce-ac' ),
		'product_tag'      => __( 'Product Tags', 'woocommerce-ac' ),
		'cart_items'       => __( 'Cart Items', 'woocommerce-ac' ),
		'cart_items_count' => __( 'Number of Cart Items', 'woocommerce-ac' ),
		'cart_total'       => __( 'Cart Total', 'woocommerce-ac' ),
	)
);

switch ( $selected_rule_type ) {
	case 'cart_items_count':
	case 'cart_total':
		$rule_condition_options = array(
			'greater_than_equal_to' => __( 'Greater than or equal to', 'woocommerce-ac' ),
			'equal_to'              => __( 'Equal to', 'woocommerce-ac' ),
			'less_than_equal_to'    => __( 'Less than or equal to', 'woocommerce-ac' ),
		);
		$style                  = '';
		break;
	default:
		$rule_condition_options = array(
			'includes' => __( 'Includes any of', 'woocommerce-ac' ),
			'excludes' => __( 'Excludes any of', 'woocommerce-ac' ),
		);
		$style                  = 'width:80%;';
		break;
}
$rule_condition_options = apply_filters( 'wcap_rules_engine_rule_condition_values', $rule_condition_options );

$rule_value_array = array();
switch ( $selected_rule_type ) {
	case 'payment_gateways':
		$wc_payment_gateways = new WC_Payment_Gateways();
		$payment_gateways    = $wc_payment_gateways->payment_gateways();
		foreach ( $payment_gateways as $slug => $gateways ) {
			if ( 'yes' === $gateways->enabled ) {
				$rule_value_array[ $slug ] = $gateways->title;
			}
		}
		break;
	case 'cart_status':
		$rule_value_array = array(
			'abandoned'           => __( 'Abandoned', 'woocommerce-ac' ),
			'abandoned-pending'   => __( 'Abandoned - Pending Payment', 'woocommerce-ac' ),
			'abandoned-cancelled' => __( 'Abandoned - Order Cancelled', 'woocommerce-ac' ),
		);
		break;
	case 'send_to':
		$rule_value_array = array(
			'all'                       => __( 'All', 'woocommerce-ac' ),
			'registered_users'          => __( 'Registered Users', 'woocommerce-ac' ),
			'guest_users'               => __( 'Guest Users', 'woocommerce-ac' ),
			'wcap_email_customer'       => __( 'Customers', 'woocommerce-ac' ),
			'wcap_email_admin'          => __( 'Admin', 'woocommerce-ac' ),
			'wcap_email_customer_admin' => __( 'Customers & Admin', 'woocommerce-ac' ),
			'email_addresses'           => __( 'Email Addresses', 'woocommerce-ac' ),
		);
		break;
}
$rule_value_array = apply_filters( 'wcap_rules_engine_rule_option_values', $rule_value_array );

$type_id   = "wcap_rule_type_$row_id";
$cond_id   = "wcap_rule_condition_$row_id";
$val_id    = "wcap_rule_value_$row_id";
$delete_id = "wcap_rule_delete_$row_id";

?>
<td class='wcap_rule_type_col'>
<select class='wcap_rule_type' id='<?php echo esc_attr( $type_id ); ?>' name='<?php echo esc_attr( $type_id ); ?>' onChange='wcap_rule_values( this.id )'>
<?php
foreach ( $rule_type_options as $type_key => $type_value ) {
	$disabled = '';
	$selected = '' === $selected_rule_type && 'selected_disabled' === $type_key ? 'selected' : '';
	$selected = '' !== $selected_rule_type && $selected_rule_type === $type_key ? 'selected' : '';

	if ( strpos( $type_key, 'disabled' ) !== false ) {
		$type_key = '';
		$disabled = 'disabled';
	}

	if ( in_array( $type_key, array( 'cart_status', 'payment_gateways', 'product_cat', 'product_tag', 'cart_items', 'cart_items_count', 'cart_total' ) ) ) { // phpcs:ignore
		$type_value = '→ ' . $type_value;
		$class      = 'wcap_rule_subcategory';
	} else {
		$class = 'wcap_rule_parent_category';
	}
	echo sprintf(
		"<option value='%s' %s %s class='%s'>%s</option>",
		esc_attr( $type_key ),
		esc_attr( $disabled ),
		esc_attr( $selected ),
		esc_attr( $class ),
		esc_html( $type_value )
	);
}
?>
</select>
</td>
<td class='wcap_rule_condition_col'>
<select class='wcap_rule_condition' id='<?php echo esc_attr( $cond_id ); ?>' name='<?php echo esc_attr( $cond_id ); ?>' style='<?php echo esc_attr( $style ); ?>'>
<?php
foreach ( $rule_condition_options as $cond_key => $cond_value ) {
	$disabled = '';
	$selected = '' === $selected_rule_cond && 'selected_disabled' === $cond_key ? 'selected' : '';
	$selected = '' !== $selected_rule_cond && $selected_rule_cond === $cond_key ? 'selected' : '';

	if ( strpos( $cond_key, 'disabled' ) !== false ) {
		$cond_key = '';
		$disabled = 'disabled';
	}
	echo sprintf(
		"<option value='%s' %s %s>%s</option>",
		esc_attr( $cond_key ),
		esc_attr( $disabled ),
		esc_attr( $selected ),
		esc_html( $cond_value )
	);
}
?>
</select>
</td>
<td class='wcap_rule_value_col'>
<?php
switch ( $selected_rule_type ) {
	case 'send_to':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for options&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_send_to" onChange='wcap_rule_value_updated( this.id )'>
			<?php
			foreach ( $selected_rule_value as $v_id ) {
				if ( '' !== $v_id ) {
					$v_object = $rule_value_array[ $v_id ];
					echo '<option value="' . esc_attr( $v_id ) . '"' . selected( true, true, false ) . '>' . esc_html( $v_object ) . '</option>';
				}
			}
			?>
		</select>
		<?php
		// Send to email addresses.
		if ( in_array( 'email_addresses', $selected_rule_value, true ) ) {
			?>
			<textarea name='wcap_rules_email_addresses' id='wcap_rules_email_addresses' rows='3' cols='35' placeholder='<?php esc_html_e( 'Please enter email addresses separated by a comma', 'woocommerce-ac' ); ?>'><?php echo esc_attr( $rule_emails ); ?></textarea>
			<?php
		}
		break;
	case 'payment_gateways':
		?>
		<select class='wcap_rule_value' id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id ); ?>'>
		<?php
		foreach ( $rule_value_array as $key => $value ) {
			$selected = '' !== $selected_rule_value && $selected_rule_value === $key ? 'selected' : '';

			echo sprintf(
				"<option value='%s' %s>%s</option>",
				esc_attr( $key ),
				esc_attr( $selected ),
				esc_html( $value )
			);

		}
		?>
		</select>
		<?php
		break;
	case 'cart_items_count':
	case 'cart_total':
		?>
		<input type='number' class='wcap_rule_value' min='1' id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id ); ?>' value='<?php echo esc_attr( $selected_rule_value ); ?>' />
		<?php
		break;
	case 'coupons':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_coupons">
			<?php
			if ( is_array( $selected_rule_value ) && count( $selected_rule_value ) > 0 ) {
				foreach ( $selected_rule_value as $v_id ) {
					if ( $v_id > 0 ) {
						$v_object = get_the_title( $v_id );
						echo '<option value="' . esc_attr( $v_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $v_object ) . '</option>';
					}
				}
			}
			?>
		</select>
		<?php
		break;
	case 'cart_status':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Status&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_cart_status">
			<?php
			foreach ( $selected_rule_value as $v_id ) {
				if ( '' !== $v_id ) {
					$v_object = $rule_value_array[ $v_id ];
					echo '<option value="' . esc_attr( $v_id ) . '"' . selected( true, true, false ) . '>' . esc_html( $v_object ) . '</option>';
				}
			}
			?>
		</select>
		<?php
		break;
	case 'product_cat':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Product Category&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_product_cat">
			<?php
			foreach ( $selected_rule_value as $v_id ) {
				if ( $v_id > 0 ) {
					$v_object = get_term( $v_id );
					echo '<option value="' . esc_attr( $v_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $v_object->name ) . '</option>';
				}
			}
			?>
		</select>
		<?php
		break;
	case 'product_tag':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Product Category&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_product_tag">
			<?php
			foreach ( $selected_rule_value as $v_id ) {
				if ( $v_id > 0 ) {
					$v_object = get_term( $v_id );
					echo '<option value="' . esc_attr( $v_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $v_object->name ) . '</option>';
				}
			}
			?>
		</select>
		<?php
		break;
	case 'cart_items':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Product&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_products">
			<?php
			foreach ( $selected_rule_value as $v_id ) {
				if ( $v_id > 0 ) {
					$v_object = get_the_title( $v_id );
					echo '<option value="' . esc_attr( $v_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $v_object ) . '</option>';
				}
			}
			?>
		</select>
		<?php
		break;
}
?>
</td>
<td>
	<a href='javascript:void(0)' class='wcap_delete_rule_button'>
		<i class='fa fa-trash fa-lg fa-fw' id='<?php echo esc_attr( $delete_id ); ?>' onclick='wcap_delete_rule_row( this.id )'></i>
	</a>
	<?php
	if ( $last_row ) {
		?>
		<a href='javascript:void(0)' class='wcap_add_rule_button' id='add_new' onclick='wcap_add_new_rule_row( this.id )'>
			<i class='fa fa-plus fa-lg fa-fw'></i>
			<?php echo esc_html__( 'Add Rule', 'woocommerce-ac' ); ?>
		</a>
		<?php
	}
	?>
</td>
