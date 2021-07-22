<?php

$site_title = get_bloginfo( 'name' );
$site_url   = get_option( 'siteurl' );
$admin_args = array(
	'role'   => 'administrator',
	'fields' => array( 'id' ),
);

$admin_usr   = get_users( $admin_args );
$uid         = $admin_usr[0]->id;
$admin_phone = get_user_meta( $uid, 'billing_phone', true );
?>

<p><style type="text/css">
		* {
			-webkit-font-smoothing: antialiased;
		}
		div, p, a, li, td {
			-webkit-text-size-adjust: none;
		}
		#outlook a {
			padding: 0;
		}
		html {
			width: 100%;
		}
		body {
			margin: 0;
			padding: 0;
			color: #808080;
			width: 100% !important;
			font-family: Arial, Helvetica, sans-serif;
			-webkit-text-size-adjust: 100%!important;
			-ms-text-size-adjust: 100%!important;
			-webkit-font-smoothing: antialiased!important;
		}
		img {
			outline: none;
			border: none;
			text-decoration: none;
			-ms-interpolation-mode: bicubic;
		}
		a {
			text-decoration: none;
		}
		a img {
			border: none;
		}
		table {
			color: #222222;
			font-family: Arial, sans-serif;
		}
		table td {
			border-collapse: collapse;
			mso-line-height-rule: exactly;
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
		}
		table {
			border-collapse: collapse;
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
		}
		.main-wrapper {
			max-width: 600px;
		}
		.container {
			width: 100%;
			table-layout: fixed;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		@media screen and (max-device-width:600px),
		screen and (max-width:600px) {
			td[class=td_em_hide] {
				display: none !important;
			}
			table[class=main-wrapper] {
				width: 100%!important;
			}
			td[class=em_bg_center] {
				background-position: center!important;
			}
			.main-wrapper {
				width: 100% !important;
			}
		}
		@media screen and (max-device-width:520px),
		screen and (max-width:520px) {
			td[class=td_w_sm] {
				width: 20px!important;
			}
			td[class=td_sm_hide] {
				display: none !important;
			}
			.sm-center-txt {
				text-align: center;
			}
			table[class=main-wrapper] {
				width: 100%!important;
			}
			table[class=sm_wrapper] {
				width: 100%!important;
			}
			td[class=td-sm_wrapper] {
				width: 100%!important;
			}
			td[class=sm-txt] {
				font-size: 24px!important;
			}
			td[class=sm-txt1] {
				font-size: 45px!important;
			}
			.main-wrapper {
				width: 100% !important;
			}
		}
	</style></p><p><style type="text/css">
		@import url(https://fonts.googleapis.com/css?family=Lato);
		@import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
		@import url(https://fonts.googleapis.com/css?family=Cabin);
	</style></p><table style="background-color: #f6f3f3; font-size: 15px; line-height: 20px; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;" border="0" width="100%" cellspacing="0" cellpadding="0" bgcolor="#f6f3f3"><tbody><tr><td align="center" valign="top" width="100%"><!-- [if mso | IE]>      
	<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">        
	<tr>          
	<td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">      
	<![endif]--><div style="margin: 0 auto; max-width: 600px;"><table class="main-wrapper" style="background-color: #ffffff; table-layout: fixed; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-family: Arial, Helvetica, sans-serif; color: #333333; margin: 0 auto; max-width: 600px;" role="presentation" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"><tbody><tr><td style="padding-top: 10px;" bgcolor="#ffffff" width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0"><tbody><tr><td class="td_w_sm" style="font-size: 0; line-height: 0;" width="30"> </td><td style="padding: 0;"><div style="font-size: 0pt; line-height: 0pt; height: 20px;"> </div><table border="0" width="100%" cellspacing="0" cellpadding="0"><tbody><tr><td class="sm-txt" style="font-size: 30px; line-height: 30px; text-align: center; color: #333032; text-transform: uppercase; font-family: Ubuntu, Helvetica, Arial, sans-serif, Helvetica, Arial, sans-serif; padding: 0;" align="center" width="100%"><?php echo "$site_title"; ?> </td>

	</tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="30"> </td></tr><tr><td class="sm-txt1" style="font-size: 50px; line-height: 50px; text-align: center; color: #000000; text-transform: uppercase; font-family: Ubuntu, Helvetica, Arial, sans-serif, Helvetica, Arial, sans-serif; padding: 0;" align="center" width="100%">PSST...</td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="20"> </td></tr><tr><td style="font-size: 15px; line-height: 24px; text-align: center; color: #333032; text-transform: uppercase; letter-spacing: 2px; padding: 0;" align="center" width="100%">Looks like you left something fabulous <br />in your shopping bag</td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="25"> </td></tr><tr><td style="font-size: 0pt; line-height: 0pt; padding: 0;" align="center"><img style="display: block; width: 100%; max-width: 301px; margin: 0 auto;" src="http://staging.tychesoftwares.com/templates/default1.jpeg" alt="" /></td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="25"> </td></tr><tr><td style="font-size: 15px; line-height: 24px; text-align: center; color: #333032; text-transform: uppercase; letter-spacing: 2px; padding: 0;" align="center" width="100%">Shop now before time runs out, <br />These must have ITEMs won't be around for long... </td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="20"> </td></tr><tr><td style="text-align: center; padding: 0;" align="center">

		{{products.cart}}


	</td></tr><tr><td style="line-height: 1px; font-size: 1px;" bgcolor="#cfcfcf" height="2"> </td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="30"> </td></tr><tr><td style="font-size: 15px; line-height: 20px; text-align: center; color: #333032; font-weight: bold; padding: 0;" align="center" width="100%">For your convenience, we have saved your shopping cart.</td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="30"> </td></tr><tr><td align="center"><table border="0" cellspacing="0" cellpadding="0" align="center"><tbody><tr><td align="center"><table border="0" width="270" cellspacing="0" cellpadding="0" align="center"><tbody><tr><td style="border-collapse: collapse; word-break: break-word; word-wrap: break-word;" align="center"><a style="display: block; font-family: Arial, Helvetica, sans-serif; font-size: 16px; letter-spacing: 1px; color: #ffffff; line-height: 20px; text-decoration: none; text-align: center; background-color: #000; margin: 0 auto; width: 270px; box-sizing: border-box; padding: 8px 8px 8px 8px;" href="{{cart.link}}">CHECKOUT NOW</a></td></tr></tbody></table></td></tr></tbody></table></td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="30"> </td></tr><tr><td style="font-size: 15px; line-height: 20px; text-align: center; color: #333032; padding: 0;" align="center" width="100%">For your assistance or if you would like to place an order directly with our customer service team, please call us at <?php echo "$admin_phone"; ?> </td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="30"> </td></tr><tr><td style="line-height: 1px; font-size: 1px;" bgcolor="#cfcfcf" height="2"> </td></tr><tr><td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="30"> </td></tr></tbody></table></td><td class="td_w_sm" style="font-size: 0; line-height: 0;" width="30"> </td></tr></tbody></table></td></tr><tr><td><!-- ======= footer start======= --><table border="0" width="100%" cellspacing="0" cellpadding="0"><tbody><tr><td class="td_w_sm" style="font-size: 0; line-height: 0;" width="30"> </td><td style="padding: 0;"><table border="0" width="100%" cellspacing="0" cellpadding="0"><tbody>

			<td style="font-size: 0pt; line-height: 0pt; text-align: left; padding: 0;" height="10"> </td></tr><tr><td style="font-size: 15px; line-height: 20px; text-align: center; color: #333032; padding: 0;" align="center" width="100%"><a href="{{cart.unsubscribe}}">Unsubscribe</a></td></tr></tbody></table><!-- ======= footer end======= --></td><td class="td_w_sm" style="font-size: 0; line-height: 0;" width="30"> </td></tr></tbody></table></td></tr></tbody></table></div><!-- [if mso | IE]>      </td></tr></table>      <![endif]--></td></tr></tbody></table>
