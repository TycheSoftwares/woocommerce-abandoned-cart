<?php
/**
 * Admin View: Abandoned Cart reminder Email Template Preview
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$current_time_stamp = current_time( 'timestamp' );
$date_format        = date_i18n( get_option( 'date_format' ), $current_time_stamp );
$time_format        = date_i18n( get_option( 'time_format' ), $current_time_stamp );
$test_date          = $date_format . ' ' . $time_format;
$wcal_price 		= wc_price( '150' );
$wcal_total_price 	= wc_price( '300' );
?>
<html>
    <head>
    <title>My document title</title>
    </head>
    <body>   
        <p align="center"> Hello John Carter, </p>
        <p> &nbsp; </p>
        <p align="center"> We're following up with you, because we noticed that on <?php echo "$test_date"; ?> you attempted to purchase the following products on <?php echo get_option( 'blogname' );?>. </p>
        <p> &nbsp; </p>
        <p>        
        <table border="0" cellspacing="5" align="center"><caption><b>Cart Details</b></caption>
            <tbody>
                <tr>
                    <th></th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
                <tr style="background-color:#f4f5f4;">
                    <td><img src = "<?php echo plugins_url();?>/woocommerce-abandoned-cart/assets/images/spectre.jpg" height="40px" width="40px"></td><td>Spectre</td><td> <?php echo "$wcal_price"; ?></td><td>2</td><td><?php echo "$wcal_total_price"; ?> </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <th>Cart Total:</th>
                    <td><?php echo "$wcal_total_price"; ?></td>
                </tr>
            </tbody>
        </table> 
        </p>
        <p> &nbsp; </p>
        <p align="center"> If you had any purchase troubles, could you please Contact to share them? </p>
        <p> &nbsp; </p>
        <p align="center"> Otherwise, how about giving us another chance? Shop <a href="<?php echo get_option( 'siteurl' );?>"><?php echo get_option( 'blogname' );?></a>. </p>
        <hr></hr>
        <p align="center"> You may <a href="<?php echo get_option( 'siteurl' );?>">unsubscribe</a> to stop receiving these emails. </p> 
        <p> &nbsp; </p>
        <p align="center"> <a href="<?php echo get_option( 'siteurl' );?>"><?php echo get_option( 'blogname' );?></a> appreciates your business.  </p>        
    </body>
</html>