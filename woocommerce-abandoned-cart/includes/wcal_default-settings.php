<?php

/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will add the default template for the plugin.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/Default-Template
 * @since 2.5
 */

class wcal_default_template_settings {
   
   /** 
    * This function will load default template while activating the plugin.
    * @globals mixed $wpdb 
    * @since 2.5
    */
   function wcal_create_default_templates() {
       global $wpdb;
       $template_name_array    = 'Initial';
       $site_title             = get_bloginfo( 'name' );
       $site_url               = get_option( 'siteurl' );
       $template_subject_array = "Hey {{customer.firstname}}!! You left something in your cart"; 
       $active_post_array      = 0;
       $email_frequency_array  = 1;
       $day_or_hour_array      = 'Hours';
       if ( !defined( 'WCAL_PLUGIN_PATH' ) ) {
          define('WCAL_PLUGIN_PATH'                 ,untrailingslashit(plugin_dir_path(__FILE__)) );
      }
      ob_start();
      include( WCAL_PLUGIN_PATH . '/templates/template_1.php' );
      $content = ob_get_clean();
       $body_content_array     = addslashes( $content );
       $is_wc_template         =  1;
       $default_template       =  1;
       $header_text            = addslashes( 'You left Something in Your Cart!' );
       
       $query = "INSERT INTO `" . $wpdb->prefix . "ac_email_templates_lite`
           ( subject, body, is_active, frequency, day_or_hour, template_name, is_wc_template, default_template, wc_email_header )
           VALUES ( '" . $template_subject_array . "',
                   '" . $body_content_array . "',
                   '" . $active_post_array . "',
                   '" . $email_frequency_array . "',
                   '" . $day_or_hour_array . "',
                   '" . $template_name_array . "',
                   '" . $is_wc_template . "',
                   '" . $default_template . "',
                   '" . $header_text . "' )";
       $wpdb->query( $query );      
   }
}
