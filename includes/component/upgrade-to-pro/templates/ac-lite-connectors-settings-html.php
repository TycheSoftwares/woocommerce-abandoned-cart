<?php
$icon_info = plugins_url( 'woocommerce-abandoned-cart/includes/component/upgrade-to-pro/assets/images/' ) . 'icon-info.svg';

$logos     = array(
	'activecampaign',
	'custom_smtp',
	'drip',
	'fluentcrm',
	'google_sheets',
	'hubspot',
	'klaviyo',
	'mailchimp',
	'mailjet',
	'salesforce_crm',
	'sendinblue',
	'wp_fusion',
);
$url       = plugins_url( 'woocommerce-abandoned-cart/includes/component/upgrade-to-pro/assets/images/logo/' );
$logo_urls = array();
foreach ( $logos as $logo ) {
	$logo_urls[ $logo ] = $url . $logo . '.png';
}
?>

<div class="container ac-lite-container-section ac-lite-connector-settings">
	<div class="ac-lite-settings-inner-section">
	<div class="row">
	<div class="col-md-12">
		<div class="col-left">
			<h1>Connectors</h1>
			<p>Please note that the plugin will no longer send reminder emails to abandoned carts if integration is enabled with a third party CRM/email marketing tool. If you still wish to send reminder emails with the integration enabled, please get in
				touch with <a href="https://support.tychesoftwares.com/help/2285384554" target="_blank">support at Tyche Softwares</a>.</p>
		</div>
		<div class="">
			<ul id="wcap_integrators_list" class="subsubsub">
				<li><a href="javascript:void(0)" id="wcap_all" class="wcap_integrators_view current">All (12)</a> |
				</li>
				<li><a data-wcap-count="1" id="wcap_active" class="wcap_integrators_view">Active(<span id="wcap_active_count">1</span>)</a> |
				</li>
				<li><a data-wcap-count="11" id="wcap_inactive" class="wcap_integrators_view">Inactive(<span id="wcap_inactive_count">11</span>)</a></li>
			</ul>
			<div id="wcap_connectors_list">
				<div class="wcap-col-group row">
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['custom_smtp']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Custom SMTP server</h3>
									<div class="wcap_connector_info_details">Use a custom SMTP server to send emails to recover abandoned carts.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_custom_smtp_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Custom SMTP server" data-wcap-name="wcap_custom_smtp" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_custom_smtp_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Custom SMTP server" data-wcap-name="wcap_custom_smtp" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Custom SMTP server" data-wcap-name="wcap_custom_smtp" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['activecampaign']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">ActiveCampaign</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to ActiveCampaign.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_activecampaign_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="ActiveCampaign" data-wcap-name="wcap_activecampaign" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_activecampaign_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="ActiveCampaign" data-wcap-name="wcap_activecampaign" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="ActiveCampaign" data-wcap-name="wcap_activecampaign" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_activecampaign" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['drip']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Drip</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to Drip.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_drip_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Drip" data-wcap-name="wcap_drip" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_drip_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Drip" data-wcap-name="wcap_drip" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Drip" data-wcap-name="wcap_drip" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_drip" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['fluentcrm']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Fluentcrm</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to Fluentcrm.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_fluentcrm_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Fluentcrm" data-wcap-name="wcap_fluentcrm" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_fluentcrm_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Fluentcrm" data-wcap-name="wcap_fluentcrm" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Fluentcrm" data-wcap-name="wcap_fluentcrm" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_fluentcrm" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['google_sheets']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Google Sheets</h3>
									<div class="wcap_connector_info_details">Export emails and abandoned carts collected from the plugin to Google Sheets.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_google_sheets_connect_div" class="wcap_connect_buttons" style="display: none;">
												<button data-wcap-title="Google Sheets" data-wcap-name="wcap_google_sheets" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_google_sheets_connected_div" class="wcap_connected_buttons" style="display: block;">
												<button data-wcap-title="Google Sheets" data-wcap-name="wcap_google_sheets" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Google Sheets" data-wcap-name="wcap_google_sheets" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_google_sheets" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['hubspot']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">HubSpot</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to HubSpot.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_hubspot_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="HubSpot" data-wcap-name="wcap_hubspot" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_hubspot_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="HubSpot" data-wcap-name="wcap_hubspot" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="HubSpot" data-wcap-name="wcap_hubspot" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_hubspot" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['klaviyo']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Klaviyo</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to Klaviyo.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_klaviyo_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Klaviyo" data-wcap-name="wcap_klaviyo" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_klaviyo_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Klaviyo" data-wcap-name="wcap_klaviyo" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Klaviyo" data-wcap-name="wcap_klaviyo" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_klaviyo" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['mailchimp']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Mailchimp</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to Mailchimp.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_mailchimp_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Mailchimp" data-wcap-name="wcap_mailchimp" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_mailchimp_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Mailchimp" data-wcap-name="wcap_mailchimp" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Mailchimp" data-wcap-name="wcap_mailchimp" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_mailchimp" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['mailjet']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Mailjet</h3>
									<div class="wcap_connector_info_details">Send emails collected from the plugin and add them to contacts list on Mailjet.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_mailjet_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Mailjet" data-wcap-name="wcap_mailjet" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_mailjet_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Mailjet" data-wcap-name="wcap_mailjet" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Mailjet" data-wcap-name="wcap_mailjet" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_mailjet" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['salesforce_crm']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Salesforce CRM</h3>
									<div class="wcap_connector_info_details">Exports emails and abandoned carts collected from the plugin to Salesforce CRM.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_salesforce_crm_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Salesforce CRM" data-wcap-name="wcap_salesforce_crm" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_salesforce_crm_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Salesforce CRM" data-wcap-name="wcap_salesforce_crm" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Salesforce CRM" data-wcap-name="wcap_salesforce_crm" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_salesforce_crm" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['sendinblue']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">Brevo (formerly Sendinblue)</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to Brevo (formerly Sendinblue).</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_sendinblue_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="Brevo (formerly Sendinblue)" data-wcap-name="wcap_sendinblue" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_sendinblue_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="Brevo (formerly Sendinblue)" data-wcap-name="wcap_sendinblue" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="Brevo (formerly Sendinblue)" data-wcap-name="wcap_sendinblue" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_sendinblue" class="trietary-btn reverse wcap_button_sync">Sync</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
						<div data-type="" class="wcap-connectors-box">
							<div class="wcap-connector_card_outer">
								<div class="wcap-connector-img-outer">
									<div class="wcap-connector-img">
										<div class="wcap-connector-img-section"><img src="<?php echo $logo_urls['wp_fusion']; ?>" class="wcap_connector_icon"></div>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wcap_connector_info">
									<h3 class="mb-1">WP Fusion</h3>
									<div class="wcap_connector_info_details">Send emails and abandoned carts collected from the plugin to WP Fusion.</div>
								</div>
								<div class="clear"></div>
							</div>
							<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="">
							<div class="wcap-connector-action">
								<div class="wcap-connector-btns">
									<div>
										<div class="connectors-left">
											<div id="wcap_wp_fusion_connect_div" class="wcap_connect_buttons" style="display: block;">
												<button data-wcap-title="WP Fusion" data-wcap-name="wcap_wp_fusion" class="trietary-btn reverse wcap_main_connect wcap_button_connect">Connect</button>
											</div>
										</div>
										<div class="connectors-right">
											<div id="wcap_wp_fusion_connected_div" class="wcap_connected_buttons" style="display: none;">
												<button data-wcap-title="WP Fusion" data-wcap-name="wcap_wp_fusion" class="wcap_button_disconnect"><span id="span_disconnect" class="dashicon dashicons dashicons-no-alt"></span>Disconnect</button>
												<button data-wcap-title="WP Fusion" data-wcap-name="wcap_wp_fusion" class="trietary-btn reverse wcap_settings wcap_button_connect">Settings</button>
												<button data-wcap-name="wcap_wp_fusion" class="trietary-btn reverse wcap_button_sync">Sync</button>
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
	</div>
	<?php do_action( 'wcal_after_settings_page_form' ); ?>
</div>