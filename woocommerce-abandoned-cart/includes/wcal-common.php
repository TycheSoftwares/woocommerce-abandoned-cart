<?php

class wcal_common {    
    public static function wcal_get_abandoned_order_count( $get_section_result ){
        global $wpdb;
        $return_abandoned_count = 0;    
        $blank_cart_info        = '{"cart":[]}';
        $blank_cart_info_guest  = '[]';    
        $ac_cutoff_time         = get_option( 'ac_lite_cart_abandoned_time' );
        $cut_off_time           = $ac_cutoff_time * 60;
        $current_time           = current_time( 'timestamp' );
        $compare_time           = $current_time - $cut_off_time;
    
        switch ( $get_section_result ) {
            case 'wcal_all_abandoned':    
                $query_ac        = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0  ) ORDER BY recovered_cart desc ";
                $ac_results      = $wpdb->get_results( $query_ac );
                $return_abandoned_count = count( $ac_results );
                break;
    
            case 'wcal_all_registered':    
                $query_ac        = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) ORDER BY recovered_cart desc ";
                $ac_results      = $wpdb->get_results( $query_ac );
                $return_abandoned_count = count( $ac_results );
                break;
    
            case 'wcal_all_guest':
                $query_ac        = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 AND user_id >= 63000000 ) ORDER BY recovered_cart desc ";
                $ac_results      = $wpdb->get_results( $query_ac );
                $return_abandoned_count = count( $ac_results );    
                break;
    
            case 'wcal_all_visitor':
                $query_ac        = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0  AND user_id = 0 ) ORDER BY recovered_cart desc ";
                $ac_results      = $wpdb->get_results( $query_ac );
                $return_abandoned_count = count( $ac_results );    
                break;
    
            default:
                # code...
                break;
        }    
        return $return_abandoned_count;
    }
}
?>