<?php
$icon_info = plugins_url( 'woocommerce-abandoned-cart/includes/component/upgrade-to-pro/assets/images/' ) . 'icon-info.svg';
?>

<div class="container ac-lite-container-section ac-lite-fb-settings">
	<div class="ac-lite-settings-inner-section">
		<div class="row">
			<div class="col-md-12">
				<div class="ac-page-head phw-btn">
					<div class="col-left">
						<h1>Facebook Messenger</h1>
						<p>
							Configure the plugin to send notifications to Facebook Messenger using the settings below. Please refer the <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger"
							target="_blank"> following documentation </a> to complete the setup.
						</p>
					</div>
					<div class="col-right">
						<button type="button">Save Settings</button>
					</div>
				</div>
				<div class="wbc-accordion">
					<div id="wbc-accordion" class="panel-group ac-accordian">
						<div class="panel panel-default">
							<div id="collapseOne" class="panel-collapse collapse show">
								<div class="panel-body">
									<div class="tbl-mod-1">
										<div class="tm1-row">
											<div class="col-left">
												<label>Enable Facebook Messenger Reminders:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="By enabling this a check box will be shown after the Add to Cart button to get the user’s concern about connecting their Facebook account. Note: Enabling or disabling this option won’t affect the cart abandonment tracking."
													class="tt-info">
													<label class="el-switch el-switch-green">
														<input type="checkbox" id="wcap_enable_fb_reminders" name="wcap_enable_fb_reminders" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Facebook Messenger On Add To Cart Pop-Up Modal:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="This option will display a checkbox on the pop-up modal to connect with Facebook."
													class="tt-info">
													<label class="el-switch el-switch-green">
														<input type="checkbox" id="wcap_enable_fb_reminders_popup" name="wcap_enable_fb_reminders_popup" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
												</div>
											</div>
										</div>
										<div class="tm1-row flx-center">
											<div class="col-left">
												<label>Icon Size Of User:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Select the size of the user icon which shall be displayed below the checkbox in case the user is logged in to their Facebook account."
													class="tt-info">
													<select id="wcap_fb_user_icon" name="wcap_fb_user_icon" class="ib-md">
														<option value="small">Small</option>
														<option value="medium">Medium</option>
														<option value="large">Large</option>
														<option value="standard">Standard</option>
														<option value="xlarge">Extra Large</option>
													</select>
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Consent Text:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Text that will appear above the consent checkbox. HTML tags are also allowed."
													class="tt-info">
													<input type="text" id="wcap_fb_consent_text" name="wcap_fb_consent_text" placeholder="Allow Order Status to be sent to Facebook Messanger" class="ib-md">
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Facebook Page ID:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Facebook Page ID in numberic format."
													class="tt-info">
													<input type="text" placeholder="Enter Your Facebook ID" id="wcap_fb_page_id" name="wcap_fb_page_id" class="ib-md">
												</div>
												You can find your page ID from <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger#fbpageid" target="_blank">here.</a></div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Messenger App ID:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Enter your Messenger App ID"
													class="tt-info">
													<input type="text" placeholder="Enter your Messanger App ID" id="wcap_fb_app_id" name="wcap_fb_app_id" class="ib-md">
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Facebook Page Token:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Enter your Facebook Page Token"
													class="tt-info">
													<input type="text" placeholder="Enter Your Facebook Token" id="wcap_fb_page_token" name="wcap_fb_page_token" class="ib-md">
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Verify Token:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Enter your Verify Token"
													class="tt-info">
													<input type="text" name="timefrom" placeholder="Enter your verify token here." id="wcap_fb_verify_token" class="ib-md">
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ss-foot">
					<button type="button">Save Settings</button>
				</div>
			</div>
		</div>
	</div>
	<?php do_action( 'wcal_after_settings_page_form' ); ?>
	
</div>