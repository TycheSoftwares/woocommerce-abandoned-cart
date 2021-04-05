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

$rule_condition_options = apply_filters(
	'wcap_rules_engine_rule_condition_values',
	array(
		'select_disabled'       => __( 'Select Condition', 'woocommerce-ac' ),
		'includes'              => __( 'Includes any of', 'woocommerce-ac' ),
		'excludes'              => __( 'Excludes any of', 'woocommerce-ac' ),
		'greater_than_equal_to' => __( 'Greater than or equal to', 'woocommerce-ac' ),
		'equal_to'              => __( 'Equal to', 'woocommerce-ac' ),
		'less_than_equal_to'    => __( 'Less than or equal to', 'woocommerce-ac' ),
	)
);
?>
<td class='wcap_rule_type_col'>
	<select class='wcap_rule_type' id='wcap_rule_type_' name='wcap_rule_type_' onChange='wcap_rule_values( this.id )'>
	<?php
	foreach ( $rule_type_options as $type_key => $type_value ) {
		$disabled = '';
		$selected = 'select_disabled' === $type_key ? 'selected' : '';
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
	<select class='wcap_rule_condition' id='wcap_rule_condition_' name='wcap_rule_condition_'>
		<?php
		foreach ( $rule_condition_options as $cond_key => $cond_value ) {
			$disabled = '';

			if ( strpos( $cond_key, 'disabled' ) !== false ) {
				$cond_key = '';
				$disabled = 'disabled';
			}
			echo sprintf(
				"<option value='%s' %s>%s</option>",
				esc_attr( $cond_key ),
				esc_attr( $disabled ),
				esc_html( $cond_value )
			);
		}
		?>
	</select>
</td>
<td class='wcap_rule_value_col'>
	<select class='wcap_rule_value' id='wcap_rule_value_' name='wcap_rule_value_'>
		<option value='' disabled selected><?php esc_html_e( 'Select values', 'woocommerce-ac' ); ?></option>
	</select>
</td>
<td>
	<a href='javascript:void(0)' class='wcap_delete_rule_button'>
		<i class='fa fa-trash fa-lg fa-fw' id='wcap_rule_delete_' onclick='wcap_delete_rule_row( this.id )'></i>
	</a>
	<a href='javascript:void(0)' class='wcap_add_rule_button' id='add_new' onclick='wcap_add_new_rule_row( this.id )'>
		<i class='fa fa-plus fa-lg fa-fw'></i>
		<?php echo esc_html__( 'Add Rule', 'woocommerce-ac' ); ?>
	</a>
</td>
