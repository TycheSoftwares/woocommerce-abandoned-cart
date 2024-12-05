<?php
$icon_info = plugins_url( 'woocommerce-abandoned-cart/includes/component/upgrade-to-pro/assets/images/' ) . 'icon-info.png';
?>

<div class="container ac-lite-container-section ac-lite-sms-settings">
	<div class="ac-lite-settings-inner-section">
		<div class="row">
			<div class="col-md-12">
				<div class="ac-page-head"></div>
				<div class="wbc-accordion">
					<div id="wbc-accordion" class="panel-group ac-accordian">
						<div id="sms_cover" class="panel panel-default">
							<div class="panel-heading">
								<h2 data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" class="panel-title">
											SMS Reminders                                        </h2>
								<p>Twillio: Configure your Twillio account settings below. Please note that due to some restrictions from Twillio, customers may sometimes receive delayed messages</p>
							</div>
							<div id="collapseOne" class="panel-collapse collapse show">
								<div class="panel-body">
									<div class="tbl-mod-1">
										<div class="tm1-row">
											<div class="col-left">
												<label>Enable SMS:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="SMS notifications of the abandoned cart will be sent to the users. Note: Enabling or disabling this option will not affect the cart abandonment tracking. "
													class="tt-info">
													<label class="el-switch el-switch-green">
														<input type="checkbox" id="wcap_enable_sms_reminders" name="wcap_enable_sms_reminders" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label style="padding-top: 10px;">From:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Must be a Twilio phone number (in E.164 format) or alphanumeric sender ID."
													class="tt-info">
													<input type="text" id="wcap_sms_from_phone" name="wcap_sms_from_phone" class="ib-md">
												</div>
											</div>
										</div>
										<div class="tm1-row flx-center">
											<div class="col-left">
												<label>Account SID:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="" class="tt-info">
													<input type="text" placeholder="DS5164" id="wcap_sms_account_sid" name="wcap_sms_account_sid" class="ib-xl">
												</div>
											</div>
										</div>
										<div class="tm1-row flx-center">
											<div class="col-left">
												<label>Auth Token:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo $icon_info; ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="" class="tt-info">
													<input type="text" placeholder="DS5164" id="wcap_sms_auth_token" name="wcap_sms_auth_token" class="ib-xl">
												</div>
											</div>
										</div>
										<div class="ss-foot">
											<button type="button">Save Settings</button>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="panel panel-default">
							<div class="panel-heading">
								<h2 data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" class="panel-title">
											Send Test SMS                                        </h2></div>
							<div id="collapseTwo" class="panel-collapse collapse show">
								<div class="panel-body">
									<div class="tbl-mod-1">
										<div role="alert" class="alert alert-dark alert-dismissible fade show">Please make sure the Recipient Number and Message field are populated with valid details.</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Recipient:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center">
													<input type="text" id="test_number" name="test_number" class="ib-md">
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Message:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap">
													<textarea id="test_msg" name="test_msg" class="ta-sm">Hello World!</textarea>
												</div>
											</div>
										</div>
										<div class="tm1-row bdr-0 pt-0">
											<div class="col-left"></div>
											<div class="col-right sb-wrap">
												<input type="submit" name="" value="Send" class="secondary-btn">
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php do_action( 'wcal_after_settings_page_form' ); ?>
</div>