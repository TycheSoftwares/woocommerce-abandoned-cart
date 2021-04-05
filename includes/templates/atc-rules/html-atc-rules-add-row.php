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

		echo sprintf(
			"<option value='%s' %s %s class=''>%s</option>",
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
	<select class='wcap_rule_value' id='wcap_rule_value_' name='wcap_rule_value_' style='width: 90%;'>
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
