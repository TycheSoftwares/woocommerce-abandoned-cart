<?php
class default_template_settings {
   /* This function will load default template while activating the plugin.
    * 
    * @since: AFter 2.5 version
    */
   function create_default_templates() {
       global $wpdb;

       $template_name_array    = 'Initial';
       $site_title             = get_bloginfo( 'name' );
       $site_url               = get_option( 'siteurl' );
       $template_subject_array = $site_title . ": Did you have checkout trouble?"; 
       $active_post_array      = 1;
       $email_frequency_array  = 1;
       $day_or_hour_array      = 'Hours';
       $body_content_array     =  addslashes("<html>
                                   <head>
                                       <title>My document title</title>
                                   </head>
                                       <body>
                                       <p> Hello {{customer.fullname}}, </p>
                                       <p> &nbsp; </p>
                                       <p> We\'re following up with you, because we noticed that on {{cart.abandoned_date}} you attempted to purchase the following products on $site_title. </p>
                                       <p> &nbsp; </p>
                                       <p> {{products.cart}} </p>
                                       <p> &nbsp; </p>
                                       <p> If you had any purchase troubles, could you please Contact to share them? </p>
                                       <p> &nbsp; </p>
                                       <p> Otherwise, how about giving us another chance? Shop <a href= $site_url >$site_title</a>. </p>
                                       <hr></hr>
                                       <p> <a href=$site_url>$site_title</a> appreciates your business.  </p>
                                    </body>
                           </html>");

       $ac_from_name     = 'Admin'; 
       $is_wc_template   =  1 ;
       $default_template =  1;
       
           $query = "INSERT INTO `" . $wpdb->prefix . "ac_email_templates_lite`
           ( subject, body, is_active, frequency, day_or_hour, template_name, from_name, is_wc_template, default_template )
           VALUES ( '" . $template_subject_array . "',
                   '" . $body_content_array . "',
                   '" . $active_post_array . "',
                   '" . $email_frequency_array . "',
                   '" . $day_or_hour_array . "',
                   '" . $template_name_array . "',
                   '" . $ac_from_name . "',
                   '" . $is_wc_template . "',
                   '" . $default_template . "' )";
           

           $wpdb->query( $query );
           
   }
}