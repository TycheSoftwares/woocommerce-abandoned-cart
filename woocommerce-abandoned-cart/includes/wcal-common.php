<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Common-Functions
 */

/**
 * It will have all the common funtions for the plugin.
 * @since 2.5.2
 */
class wcal_common {

    /**
     * It will fetch the total count for the abandoned cart section.
     * @param string $get_section_result Name of the section for which we need result
     * @return string | int $return_abandoned_count
     * @globals mixed $wpdb
     * @since 2.5.2
     */    
    public static function wcal_get_abandoned_order_count( $get_section_result ){
        global $wpdb;
        $return_abandoned_count = 0;    
        $blank_cart_info        = '{"cart":[]}';
        $blank_cart_info_guest  = '[]';
        $blank_cart             = '""';      
        $ac_cutoff_time         = get_option( 'ac_lite_cart_abandoned_time' );
        $cut_off_time           = intval( $ac_cutoff_time ) * 60;
        $current_time           = current_time( 'timestamp' );
        $compare_time           = $current_time - $cut_off_time;
    
        switch ( $get_section_result ) {
            case 'wcal_all_abandoned':    
                $query_ac        = "SELECT COUNT(`id`) as cnt FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '%$blank_cart%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '%$blank_cart%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count  = $wpdb->get_var( $query_ac );
                break;
    
            case 'wcal_all_registered':    
                $query_ac        = "SELECT COUNT(`id`) FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '%$blank_cart%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count = $wpdb->get_var( $query_ac );
                break;
    
            case 'wcal_all_guest':
                $query_ac        = "SELECT COUNT(`id`) FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '%$blank_cart%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 AND user_id >= 63000000 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count = $wpdb->get_var( $query_ac );
                break;
    
            case 'wcal_all_visitor':
                $query_ac        = "SELECT COUNT(`id`) FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '%$blank_cart%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0  AND user_id = 0 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count = $wpdb->get_var( $query_ac );   
                break;
    
            default:
                # code...
                break;
        }    
        return $return_abandoned_count;
    }


    /**
     * This function returns the Abandoned Cart Lite plugin version number.
     * @return string $plugin_version
     * @since 2.5.2
     */
    public static function wcal_get_version() {
        $plugin_version = '';
        $wcap_plugin_dir =  dirname ( dirname (__FILE__) );
        $wcap_plugin_dir .= '/woocommerce-ac.php';

        $plugin_data = get_file_data( $wcap_plugin_dir, array( 'Version' => 'Version' ) );
        if ( ! empty( $plugin_data['Version'] ) ) {
            $plugin_version = $plugin_data[ 'Version' ];
        }
        return $plugin_version;
    }

    /**
     * This function returns the plugin url.
     * @return string plugin url
     * @since 2.5.2 
     */
    public static function wcal_get_plugin_url() {
        return plugins_url() . '/woocommerce-abandoned-cart/';
    }

    /**
     * This function will alter Email Templates Table to include emojis
     * 
     * @return bool true if success else false
     * 
     * @since 4.8
     */
    public static function update_templates_table(){

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $query = "ALTER TABLE " . $wpdb->prefix . "ac_email_templates_lite" . " CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        return $wpdb->query( $query );
    }

    /**
     * This function will show a dismissible success message after DB update is completed
     * 
     * @since 4.8
     */
    public static function show_update_success() {
        ?>

        <div class="notice notice-success is-dismissible"> 
            <p><strong><?php _e( 'Database Updated Successfully', 'woocommerce-abandoned-cart');?></strong></p>
        </div>

        <?php
    }

    /**
     * This function will show a dismissible success message after DB update is completed
     * 
     * @since 4.8
     */
    public static function show_update_failure() {
        ?>

        <div class="notice notice-error is-dismissible"> 
            <p><strong><?php _e( 'Database Update Failed. Please try again after sometime', 'woocommerce-abandoned-cart');?></strong></p>
        </div>

        <?php
    }
}
?>