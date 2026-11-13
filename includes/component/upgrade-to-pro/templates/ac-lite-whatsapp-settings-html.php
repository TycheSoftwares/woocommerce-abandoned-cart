<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$icon_info = plugins_url( 'woocommerce-abandoned-cart/includes/component/upgrade-to-pro/assets/images/' ) . 'icon-info.png';
?>

<div class="container ac-lite-container-section ac-lite-fb-settings">
	<div class="ac-lite-settings-inner-section">
		<div class="row">
			<div class="col-md-12">
				<div class="ac-page-head"></div>
				<div class="wbc-accordion">
					<div id="wbc-accordion" class="panel-group ac-accordian">
						<div class="panel panel-default">
							<div class="panel-heading">
								<h2 class="panel-title">WhatsApp Settings</h2>
								<p>Configure your WhatsApp Cloud API account by adding your Access Token and Phone Number ID below.</p>
							</div>
							<div class="panel-collapse collapse show">
								<div class="panel-body">
									<div class="tbl-mod-1">
										<div class="tm1-row">
											<div class="col-left">
												<label>Enable WhatsApp Reminders:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo esc_url( $icon_info ); ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Whatsapp notifications of the abandoned cart will be sent to the users. Note: Enabling or disabling this option will not affect the cart abandonment tracking." class="tt-info">
													<label class="el-switch el-switch-green">
														<input type="checkbox" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
												</div>
											</div>
										</div>
										<div class="tm1-row flx-center">
											<div class="col-left">
												<label>Access Token:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo esc_url( $icon_info ); ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Please enter your Access token. This is required for secure API authentication. Keep it private and do not share it publicly." class="tt-info">
													<input type="text" placeholder="Enter your WhatsApp Business account access token" class="ib-xl">
												</div>
											</div>
										</div>
										<div class="tm1-row flx-center">
											<div class="col-left">
												<label>Phone Number ID:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo esc_url( $icon_info ); ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Please enter your Phone ID from WhatsApp cloud account. This unique identifier is required for authentication and API access." class="tt-info">
													<input type="text" placeholder="Enter your Phone Number ID" class="ib-xl">
												</div>
											</div>
										</div>
										<div class="tm1-row flx-center">
											<div class="col-left">
												<label>Consent Message:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo esc_url( $icon_info ); ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="WhatsApp requires explicit opt-in from the customers to send messages." class="tt-info">
													<input type="text" placeholder="Enter consent text to display on checkout" class="ib-xl">
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
	</div>
	<?php do_action( 'wcal_after_settings_page_form' ); ?>
</div>
