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
	'wcap_atc_rules_engine_rule_type_values',
	array(
		'select_disabled' => __( 'Select Rule Type', 'woocommerce-ac' ),
		'custom_pages'    => __( 'Pages', 'woocommerce-ac' ),
		'product_cat'     => __( 'Product Categories', 'woocommerce-ac' ),
		'products'        => __( 'Products', 'woocommerce-ac' ),
	)
);

$rule_condition_options = apply_filters(
	'wcap_atc_rules_engine_rule_condition_values',
	array(
		'select_disabled' => __( 'Select Condition', 'woocommerce-ac' ),
		'includes'        => __( 'Includes any of', 'woocommerce-ac' ),
		'excludes'        => __( 'Excludes any of', 'woocommerce-ac' ),
	)
);

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

	echo sprintf(
		"<option value='%s' %s %s>%s</option>",
		esc_attr( $type_key ),
		esc_attr( $disabled ),
		esc_attr( $selected ),
		esc_html( $type_value )
	);
}
?>
</select>
</td>
<td class='wcap_rule_condition_col'>
<select class='wcap_rule_condition' id='<?php echo esc_attr( $cond_id ); ?>' name='<?php echo esc_attr( $cond_id ); ?>'>
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
	case 'custom_pages':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Page&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_pages">
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
	case 'product_cat':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Product Category&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_product_cat">
			<?php
			if ( is_array( $selected_rule_value ) && count( $selected_rule_value ) > 0 ) {
				foreach ( $selected_rule_value as $v_id ) {
					if ( $v_id > 0 ) {
						$v_object = get_term( $v_id );
						echo '<option value="' . esc_attr( $v_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $v_object->name ) . '</option>';
					}
				}
			}
			?>
		</select>
		<?php
		break;
	case 'products':
		?>
		<select id='<?php echo esc_attr( $val_id ); ?>' name='<?php echo esc_attr( $val_id . '[]' ); ?>' class="wcap_rule_value wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for a Product&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_products">
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
