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
global $woocommerce;
$help_tip_text = version_compare( $woocommerce->version, '3.0.0', '>=' ) ? 'help_tip_filter_3.0' : 'help_tip_filter';
$purchase_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite';

// Args sent to the template.
$selected_all = 'all' === $match ? 'selected' : '';
$selected_any = 'any' === $match ? 'selected' : '';
$rules_setup  = is_array( $rules ) && count( $rules ) > 0 ? $rules : array();
// Get the row data.
ob_start();
wc_get_template(
	'html-rules-add-row.php',
	'',
	'woocommerce-abandoned-cart/',
	WCAL_PLUGIN_PATH . '/includes/templates/rules/'
);
$row = ob_get_clean();
$row = apply_filters( 'wcap_rules_engine_add_row_content', $row );
?>
<table class='form-table'>
	<tr>
		<th>
			<label for='wcap_match_rules'>
				<?php esc_html_e( 'Match Rules:', 'woocommerce-ac' ); ?>
			</label>
		</th>
		<td>
			<select class='' id='wcap_match_rules' name='wcap_match_rules' disabled>
				<option value='all' <?php echo esc_attr( $selected_all ); ?>><?php esc_html_e( 'Match all rules', 'woocommerce-ac' ); ?></option>
				<option value='any' <?php echo esc_attr( $selected_any ); ?>><?php esc_html_e( 'Match any rule(s)', 'woocommerce-ac' ); ?></option>
			</select>
			<img id = <?php echo esc_html( $help_tip_text ); ?> class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Email template will be sent depending on whether all the rule matches are met or any rule matches.', 'woocommerce-ac' ); ?>' src="<?php echo esc_url( plugins_url() ); ?>/woocommerce/assets/images/help.png" /></p>
		</td>
	</tr>
	<th>
		<label for='wcap_rules_1'>
			<?php esc_html_e( 'Rules:', 'woocommerce-ac' ); ?>
		</label>
	</th>
	<td>
		<table class='wcap-rule-list form-table' id='wcap-rule-list' data-row='<?php echo esc_attr( $row ); ?>'>
			<?php
			$rule_head_style = ( count( $rules_setup ) > 0 ) ? 'style="display:table-header-group;"' : 'style=display:none;';
			?>
			<thead id='wcap-rule-list-header' <?php echo esc_attr( $rule_head_style ); ?>>
				<tr>
					<th><?php echo esc_html__( 'Rule Type', 'woocommerce-ac' ); ?></th>
					<th><?php echo esc_html__( 'Conditions', 'woocommerce-ac' ); ?></th>
					<th><?php echo esc_html__( 'Values', 'woocommerce-ac' ); ?></th>
					<th><?php echo esc_html__( 'Actions', 'woocommerce-ac' ); ?></th>
				</tr>
			</thead>
			<tbody id='wcap-rule-list-body'>
			<?php
			if ( count( $rules_setup ) > 0 ) {
				$count = count( $rules_setup );
				$i     = 1;
				foreach ( $rules_setup as $rule_data ) {
					$last_row    = $i === $count ? true : false;
					$rule_emails = isset( $rule_data->emails ) ? $rule_data->emails : '';
					$edit_row    = apply_filters(
						'wcap_rules_engine_edit_row_data',
						array(
							'rule_type'      => $rule_data->rule_type,
							'rule_condition' => $rule_data->rule_condition,
							'rule_value'     => $rule_data->rule_value,
							'rule_emails'    => $rule_emails,
							'last_row'       => $last_row,
							'row_id'         => $i,
						)
					);
					?>
					<tr id='<?php echo esc_attr( $i ); ?>'>
						<?php
						wc_get_template(
							'html-rules-edit-row.php',
							$edit_row,
							'woocommerce-abandoned-cart/',
							WCAL_PLUGIN_PATH . '/includes/templates/rules/'
						);
						?>
					</tr>
					<?php
					$i++;
				}
			}
			?>
			</tbody>
			<tfoot>
				<tr class="wcap_rule_list_footer_tr">
					<td id="wcap_rule_list_footer" colspan="4">
						<?php
						esc_html_e( 'Add rules as needed to create different email templates for different user bases. ', 'woocommerce-abandoned-cart' );
						// Translators: Plugin purchase Link.
						printf( wp_kses_post( __( "<b><i>Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to enable the feature.</i></b>", 'woocommerce-abandoned-cart' ) ), esc_url( $purchase_link ) );
						?>
						<br>
					<?php
					if ( count( $rules_setup ) === 0 ) {
						?>
						<a
							href="javascript:void(0)"
							id="add_new"
							class="button add-new-row"
							onclick="wcap_add_new_rule_row( this.id )"
							disabled >
							<?php esc_html_e( 'Add new', 'woocommerce-ac' ); ?>
						</a>
						<?php
					}
					?>
					</td>
				</tr>
			</tfoot>
		</table>
	</td>
</table>
