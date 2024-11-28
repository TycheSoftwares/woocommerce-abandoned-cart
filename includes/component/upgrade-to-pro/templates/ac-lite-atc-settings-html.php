<?php

$icon_info = plugins_url( 'woocommerce-abandoned-cart/includes/component/upgrade-to-pro/assets/images/' ) . 'icon-info.png';
?>

<div class="container ac-lite-container-section ac-lite-sms-settings">
	<div class="ac-lite-settings-inner-section">
	<form method="post">
		<div id="template_add_edit" class="container max-1100">
			<input type="hidden" name="mode" value="addnewtemplate">
			<input type="hidden" name="id" class="template_id" value="0">
			<input type="hidden" name="atc_settings_frm" value="save">
			<div class="row">
				<div class="col-md-12">
					<div class="ac-page-head phw-btn justify-content-between">
						<div class="col-left">
							<h1>Popup Templates</h1>
							<p>Add different Add to Cart popup templates for different pages to maximize the possibility of collecting email addresses from users.</p>
						</div>
						<div class="col-right">
							<button type="button" class="top-back">Back</button>
							<button type="button">Save Settings</button>
						</div>
					</div>
					<div class="wbc-accordion">
						<div id="wbc-accordion" class="panel-group ac-accordian">
							<div class="panel panel-default mb-4">
								<div class="panel-heading">
									<h2 data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" class="panel-title">
										Edit/Add Popup Templates                                 </h2></div>
								<div id="collapseOne" class="panel-collapse collapse show">
									<div class="panel-body">
										<div class="tbl-mod-1">
											<div class="tm1-row align-items-center">
												<div class="col-left">
													<label>Template Name:
														<label></label>
													</label>
												</div>
												<div class="col-right">
													<div class="row-box-1">
														<div class="rb1-right">
															<div class="rb1-row flx-center">
																<div class="rb-col">
																	<input type="text" placeholder="Popup Templates name goes here..." id="wcap_template_name" name="wcap_template_name" class="ib-xl">
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="tm1-row align-items-center">
												<div class="col-left">
													<label>Template Type:
														<label></label>
													</label>
												</div>
												<div class="col-right">
													<div class="row-box-1">
														<div class="rb1-right">
															<div class="rb1-row flx-center">
																<div class="rb-col">
																	<select id="wcap_template_type" name="wcap_template_type" class="ib-md">
																		<option value="atc">Add To Cart</option>
																		<option value="exit_intent">Exit Intent</option>
																	</select>
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
							<div class="panel panel-default mb-4">
								<div class="panel-heading">
									<h2 data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" class="panel-title">
										Rules
									</h2></div>
								<div id="collapseTwo" class="panel-collapse collapse show">
									<div class="panel-body">
										<div class="tbl-mod-1">
											<div class="custom-integrations mb-4">
												<div class="bts-content">
													<div class="tbl-mod-2 flx-100">
														<div class="tm2-inner-wrap tbl-responsive">
															<table class="for-action" style="display: none;">
																<tbody>
																	<tr>
																		<td>
																			<button type="button" class="btn btn-outline-primary blue-button btn-sm add-new">Save Setting</button> <a data-toggle="collapse" href="#" id="action-edit" role="button" aria-expanded="false" aria-controls="collapseExample" class="edit-delvry-sche edit" style="display: none;"> Edit</a>                                                                        <a title="Enable" class="delete ml-2"><i class="fas fa-trash"></i></a></td>
																	</tr>
																</tbody>
															</table>
															<table class="table">
																<thead>
																	<tr>
																		<th class="rule_td">Rule Type</th>
																		<th class="rule_td">Conditions</th>
																		<th class="rule_td">Values</th>
																		<th class="rule_td">Actions</th>
																	</tr>
																</thead>
																<tbody></tbody>
															</table>
														</div>
														<div class="add-more-link">
															<a id="add_product_availability" class="al-link add_new_template_range"> Add Rule</a>
														</div>
													</div>
												</div>
											</div>
											<div class="tm1-row border-top-0 pt-0 pb-0" style="display: none;">
												<div class="col-left">
													<label>Match Rules:
														<label></label>
													</label>
												</div>
												<div class="col-right">
													<div class="row-box-1">
														<div class="rb1-right">
															<div class="rb1-row flx-center">
																<div class="rb-col">
																	<select id="wcap_match_rules" name="wcap_match_rules" class="ib-md">
																		<option value="">Select a value</option>
																		<option value="all">Match all rules</option>
																		<option value="any">Match any rule(s)</option>
																	</select>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="rb1-row flx-center mb-4"></div>
									</div>
								</div>
							</div>
							<div class="panel panel-default mb-4">
								<div class="panel-heading">
									<h2 data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" class="panel-title">
										Coupon Settings                                 </h2></div>
								<div id="collapseThree" class="panel-collapse collapse show">
									<div class="panel-body">
										<div class="tbl-mod-1">
											<div class="tm1-row">
												<div class="col-left">
													<label>Offer coupons on email address capture:
														<label></label>
													</label>
												</div>
												<div class="col-right">
													<div class="row-box-1">
														<div class="rb1-right">
															<div class="rb1-row flx-center">
																<label class="el-switch el-switch-green">
																	<input type="checkbox" id="wcap_auto_apply_coupons_atc" name="wcap_auto_apply_coupons_atc" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="coupon__main coupon__one" style="display: none;">
												<div class="tm1-row align-items-center">
													<div class="col-left">
														<label>Type of Coupon to apply:
															<label></label>
														</label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<div class="rb-col">
																		<select id="wcap_atc_coupon_type" name="wcap_atc_coupon_type" class="ib-md">
																			<option value="pre-selected">Existing Coupons</option>
																			<option value="unique">Generate Unique Coupon code</option>
																		</select>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div style="display: none;">
													<div class="tm1-row align-items-center">
														<div class="col-left">
															<label>Discount Type:
																<label></label>
															</label>
														</div>
														<div class="col-right">
															<div class="row-box-1">
																<div class="rb1-right">
																	<div class="rb1-row flx-center">
																		<div class="rb-col">
																			<select id="wcap_atc_discount_type" name="wcap_atc_discount_type" class="ib-md">
																				<option value="percent">Percentage Discount</option>
																				<option value="fixed_cart">Fixed Cart Amount</option>
																			</select>
																		</div>
																	</div>
																</div>
															</div>
														</div>
													</div>
													<div class="tm1-row align-items-center">
														<div class="col-left">
															<label>Discount Amount:
																<label></label>
															</label>
														</div>
														<div class="col-right">
															<div class="row-box-1">
																<div class="rb1-right">
																	<div class="rb1-row flx-center">
																		<div class="rb-col">
																			<input type="number" id="wcap_atc_discount_amount" name="wcap_atc_discount_amount" class="ib-mb">
																		</div>
																	</div>
																</div>
															</div>
														</div>
													</div>
													<div class="tm1-row">
														<div class="col-left">
															<label>Allow Free Shiping?:
																<label></label>
															</label>
														</div>
														<div class="col-right">
															<div class="row-box-1">
																<div class="rb1-right">
																	<div class="rb1-row flx-center">
																		<label class="el-switch el-switch-green">
																			<input type="checkbox" id="wcap_atc_coupon_free_shipping" name="wcap_atc_coupon_free_shipping" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="tm1-row align-items-center" style="display: none;">
													<div class="col-left">
														<label>Coupon code to apply:
															<label></label>
														</label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<div class="rb-col">
																		<select multiple="" id="coupon_ids" name="coupon_ids[]" data-placeholder="Search for a Coupon…" data-action="wcap_json_find_coupons" class="wc-product-search select2-hidden-accessible enhanced"
																		style="width: 99%;" tabindex="-1" aria-hidden="true"></select><span class="select2 select2-container select2-container--default" dir="ltr" style="width: 99%;"><span class="selection"><span class="select2-selection select2-selection--multiple" aria-haspopup="true" aria-expanded="false" tabindex="-1"><ul class="select2-selection__rendered" aria-live="polite" aria-relevant="additions removals" aria-atomic="true"><li class="select2-search select2-search--inline"><input class="select2-search__field" type="text" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="textbox" aria-autocomplete="list" placeholder="Search for a Coupon…" style="width: 0px;"></li></ul></span></span>
																		<span
																		class="dropdown-wrapper" aria-hidden="true"></span>
																			</span>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="tm1-row align-items-center">
													<div class="col-left">
														<label>Coupon validity (in minutes):
															<label></label>
														</label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<div class="rb-col">
																		<input type="text" id="wcap_atc_coupon_validity" name="wcap_atc_coupon_validity" class="ib-mb">
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="tm1-row align-items-center">
													<div class="col-left">
														<label>Urgency message to boost your conversions:
															<label></label>
														</label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<div class="rb-col">
																		<input type="text" id="wcap_countdown_msg" name="wcap_countdown_msg" placeholder="Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now." class="ib-xl">
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="tm1-row border-top-0 pt-0">
													<div class="col-left">
														<label></label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<p class="mb-0">Merge tags available: &lt;coupon_code&gt;, &lt;hh:mm:ss&gt;</p>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="tm1-row align-items-center">
													<div class="col-left">
														<label>Message to display after coupon validity is reached:
															<label></label>
														</label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<div class="rb-col">
																		<input type="text" placeholder="the offer is no longer valid." id="wcap_countdown_msg_expired" name="wcap_countdown_msg_expired" class="ib-xl">
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="tm1-row">
													<div class="col-left">
														<label>Display Urgency message on Cart page (If disabled it will display only on Checkout page):
															<label></label>
														</label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<label class="el-switch el-switch-green">
																		<input type="checkbox" id="wcap_countdown_timer_cart" name="wcap_countdown_timer_cart" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="tm1-row border-top-0 pt-0">
													<div class="col-left">
														<label></label>
													</div>
													<div class="col-right">
														<div class="row-box-1">
															<div class="rb1-right">
																<div class="rb1-row flx-center">
																	<p class="mb-0">Note: Orders that use the coupon selected/generated by the popup module will be marked as "ATC Coupon Used" in WooCommerce-&gt;Orders.</p>
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
							<div id="panel-add-cart-div" class="panel-add-cart">
								<div class="panel panel-default mb-4">
									<div class="panel-heading">
										<h2 data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" class="panel-title">
										Configure popup                                    </h2></div>
									<div id="collapseFour" class="panel-collapse collapse show">
										<div class="panel-body pt-0 pl-0 pb-0">
											<div class="tbl-mod-1">
												<div class="row align-items-center">
													<div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
														<div class="configure-popup">
															<div class="configure-head">
																<h2>Configure popup</h2></div>
															<div class="configure-body">
																<div class="tbl-mod-1">
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Modal Image:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="file" id="wcap_heading_section_text_image" name="wcap_heading_section_text_image" class="ib-md">
																						</div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Modal Heading:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="Subscribe Now Our Newsletter!" id="wcap_heading_section_text_email" name="wcap_heading_section_text_email" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither">
																						<input type="color" value="#E72C2C" id="wcap_popup_heading_color_picker" name="wcap_popup_heading_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#737f97</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Modal Text:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" name="" placeholder="Modal text" id="wcap_text_section_text" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither">
																						<input type="color" class="holiday-color"> <span for="favcolor" class="holiday-color">#bbc9d2</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Email placeholder:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" name="" placeholder="Modal text" id="wcap_email_placeholder_section_input_text" class="ib-md">
																						</div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Add to cart button text:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="Add To Cart" id="wcap_button_section_input_text" name="wcap_button_section_input_text" class="ib-md">
																						</div>
																					</div>
																					<div class="d-flex">
																						<div class="color-picker color-swither mr-2">
																							<input type="color" value="#FFBA00" id="wcap_button_color_picker" name="wcap_button_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#0085ba</span></div>
																						<div class="color-picker color-swither">
																							<input type="color" value="#1A8D34" id="wcap_button_text_color_picker" name="wcap_button_text_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#ffffff</span></div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Email address is mandatory?</label>
																			<label class="el-switch el-switch-green ml-3">
																				<input type="checkbox" id="wcap_switch_atc_modal_mandatory" name="wcap_switch_atc_modal_mandatory" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
																		</div>
																	</div>
																	<div id="enable_seasonal_price_div" class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Not mandatory text:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="No Thanks" id="wcap_non_mandatory_modal_section_fields_input_text" name="wcap_non_mandatory_modal_section_fields_input_text" class="ib-md">
																						</div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Capture Phone:
																				<label>
																					<label class="el-switch el-switch-green ml-3">
																						<input type="checkbox" id="wcap_switch_atc_capture_phone" name="wcap_switch_atc_capture_phone" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
																				</label>
																			</label>
																		</div>
																	</div>
																	<div class="tm1-row partial_payment_div align-items-center" style="display: none;">
																		<div class="col-left">
																			<label>Phone placeholder:
																				<label><img src=<?php echo $icon_info; ?> alt="Info" data-toggle="tooltip" data-placement="top"
																					title="" data-original-title="Tooltip content goes here" class="tt-info"></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="Phone number (e.g. +19876543210)" id="wcap_phone_placeholder_section_input_text" name="wcap_phone_placeholder_section_input_text" true-value="on"
																							false-value="" class="ib-md">
																						</div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center" style="display: none;">
																		<div class="col-left">
																			<label>Phone number is mandatory?
																				<label>
																					<label class="el-switch el-switch-green ml-3">
																						<input type="checkbox" id="wcap_switch_atc_phone_mandatory" name="wcap_switch_atc_phone_mandatory" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
																				</label>
																			</label>
																		</div>
																	</div>
																</div>
															</div>
														</div>
													</div>
													<div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
														<div class="subscribe-body">
															<div class="subscribe-head">
																<div>
																	<div class="Cancel-icon">
																	</div>
																</div>
																<h1 class="mb-0" style="color: rgb(115, 127, 151);">Please enter your email</h1>
																<p style="color: rgb(187, 201, 210);"> To add this item to your cart, please enter your email address. </p>
																<input type="text" name="" readonly="readonly" placeholder="Email Address" class="ib-md">
																<input type="text" name="" readonly="readonly" placeholder="Phone number (e.g. +19876543210)" class="ib-md min-auto" style="display: none;">
																<button type="button" class="mr-2" style="background-color: rgb(0, 133, 186); color: rgb(255, 255, 255);">Add to Cart</button>
																<div class="subscribe-btn" style="display: none;"><a href="">No Thanks</a></div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div id="panel-by-now-div" class="panel-by-now" style="display: none;">
								<div class="panel panel-default mb-4">
									<div class="panel-heading">
										<h2 data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" class="panel-title">
										Configure popup for guest users                                    </h2></div>
									<div id="collapseFive" class="panel-collapse collapse show">
										<div class="panel-body pt-0 pl-0 pb-0">
											<div class="tbl-mod-1">
												<div class="row align-items-center">
													<div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
														<div class="configure-popup">
															<div class="configure-head">
																<h2>Configure popup for guest users													   <img src=<?php echo $icon_info; ?> alt="Info" data-toggle="tooltip" data-placement="top" title="These settings would show a popup to motivate the user to redirect to the Checkout page. Note: This popup would appear for guest users by default where email address has not been captured until then." class="tt-info"></h2></div>
															<div class="configure-body">
																<div class="tbl-mod-1">
																	<div class="tm1-row align-items-center border-top-0 pt-0">
																		<div class="col-left">
																			<label>Modal Image:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="file" id="wcap_heading_section_text_image" name="wcap_heading_section_text_image" class="ib-md">
																						</div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Modal Heading:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="We are sad to see you leave" id="wcap_heading_section_text_email" name="wcap_heading_section_text_email" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither">
																						<input type="color" id="wcap_popup_heading_color_picker" name="wcap_popup_heading_color_picker" class="holiday-color2"> <span for="favcolor" class="holiday-color">#737f97</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Modal Text:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase." id="wcap_text_section_text"
																							name="wcap_text_section_text" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither">
																						<input type="color" id="wcap_quick_ck_popup_text_color_picker" name="wcap_popup_text_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#bbc9d2</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Email placeholder:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" name="" placeholder="Modal text" id="wcap_email_placeholder_section_input_text" class="ib-md">
																						</div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Link Text</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="Complete my order!" id="wcap_button_section_input_text" name="wcap_button_section_input_text" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither mr-2">
																						<input type="color" value="#FFFFFF" id="wcap_button_color_picker" name="wcap_button_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#0085ba</span></div>
																					<div class="color-picker color-swither mr-2">
																						<input type="color" value="#FFFFFF" id="wcap_button_text_color_picker" name="wcap_button_text_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#ffffff</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Email address is mandatory?</label>
																			<label class="el-switch el-switch-green ml-3">
																				<input type="checkbox" id="wcap_switch_atc_modal_mandatory" name="wcap_switch_atc_modal_mandatory" true-value="on" false-value=""> <span class="el-switch-style"></span></label>
																		</div>
																	</div>
																	<div id="enable_seasonal_price_div" class="tm1-row align-items-center" style="display: none;">
																		<div class="col-left">
																			<label>Not mandatory text:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="No Thanks" id="wcap_non_mandatory_modal_section_fields_input_text" name="wcap_non_mandatory_modal_section_fields_input_text" class="ib-md">
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
													<div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
														<div class="subscribe-body">
															<div class="subscribe-head">
																<div>
																	<div class="Cancel-icon"></div>
																</div>
																<h1 class="mb-0" style="color: rgb(115, 127, 151);">Please enter your email</h1>
																<p style="color: rgb(187, 201, 210);"> To add this item to your cart, please enter your email address. </p>
																<input type="text" name="" readonly="readonly" placeholder="Email Address" class="ib-md">
																<textarea rows="3" placeholder="Phone number (e.g. +19876543210)" class="ib-md min-auto" style="display: none;"></textarea>
																<div>
																	<button type="button" class="trietary-btn eic_popup" style="background-color: rgb(0, 133, 186); color: rgb(255, 255, 255);">Add to Cart</button>
																</div>
																<div class="subscribe-btn" style="display: none;"><a href="" class="etc_link">No Thanks</a></div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="panel panel-default mb-4">
									<div class="panel-heading">
										<h2 data-toggle="collapse" data-target="#collapseSix" aria-expanded="false" class="panel-title">
										Configure popup for logged-in users                                    </h2></div>
									<div id="collapseSix" class="panel-collapse collapse show">
										<div class="panel-body pt-0 pl-0 pb-0">
											<div class="tbl-mod-1">
												<div class="row align-items-center">
													<div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
														<div class="configure-popup">
															<div class="configure-head">
																<h2>Configure popup for logged-in users													  <img src=<?php echo $icon_info; ?> alt="Info" data-toggle="tooltip" data-placement="top" title="These settings would show a popup to force the user to redirect to the Checkout page. Note: This popup would appear for logged in users by default and can be forced for Guest users without email address as well." class="tt-info"></h2></div>
															<div class="configure-body">
																<div class="tbl-mod-1">
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Enable Exit Intent popup for logged-in users</label>
																			<label class="el-switch el-switch-green ml-1">
																				<input type="checkbox" id="wcap_enable_ei_for_registered_users" name="wcap_enable_ei_for_registered_users" true-value="on" false-value=""> <span class="el-switch-style"></span></label> <img src=<?php echo $icon_info; ?> alt="Info"
																			data-toggle="tooltip" data-placement="top" title="Please note that if this setting is disabled, the popup will not appear for logged-in users." class="tt-info"></div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Allow all users (including guest users) to checkout without capturing email</label>
																			<label class="el-switch el-switch-green ml-1">
																				<input type="checkbox" id="wcap_quick_ck_force_user_to_checkout" name="wcap_quick_ck_force_user_to_checkout" true-value="on" false-value=""> <span class="el-switch-style"></span></label> <img src=<?php echo $icon_info; ?> alt="Info"
																			data-toggle="tooltip" data-placement="top" title="Please note that if this setting is enabled, then the email address capture popup will not appear for Guest users." class="tt-info"></div>
																	</div>
																	<div class="tm1-row align-items-center border-top-0 pt-0">
																		<div class="col-left">
																			<label>Modal Image:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="file" id="wcap_heading_section_ei_text_image" name="wcap_heading_section_ei_text_image" class="ib-md">
																						</div>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Modal Heading:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="We are sad to see you leave" id="wcap_quick_ck_heading_section_text_email" name="wcap_quick_ck_heading_section_text_email" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither">
																						<input type="color" value="#737f97" id="wcap_quick_ck_popup_heading_color_picker" name="wcap_quick_ck_popup_heading_color_picker" class="holiday-color2"> <span for="favcolor" class="holiday-color">#737f97</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Modal Text:
																				<label></label>
																			</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase." id="wcap_quick_ck_text_section_text"
																							name="wcap_quick_ck_text_section_text" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither">
																						<input type="color" value="#000000" id="wcap_quick_ck_popup_text_color_picker" name="wcap_quick_ck_popup_text_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#bbc9d2</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row align-items-center">
																		<div class="col-left">
																			<label>Link Text</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" placeholder="Complete my order!" id="wcap_quick_ck_button_section_input_text" name="wcap_quick_ck_button_section_input_text" class="ib-md">
																						</div>
																					</div>
																					<div class="color-picker color-swither mr-2">
																						<input type="color" value="#FFFFFF" id="wcap_quick_ck_button_color_picker" name="wcap_quick_ck_button_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#0085ba</span></div>
																					<div class="color-picker color-swither mr-2">
																						<input type="color" value="#FFFFFF" id="wcap_quick_ck_button_text_color_picker" name="wcap_quick_ck_button_text_color_picker" class="holiday-color"> <span for="favcolor" class="holiday-color">#ffffff</span></div>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="tm1-row pb-0 align-items-center">
																		<div class="col-left">
																			<label>Link to redirect to</label>
																		</div>
																		<div class="col-right">
																			<div class="row-box-1">
																				<div class="rb1-right">
																					<div class="rb1-row flx-center mb-2">
																						<div class="rb-col mt-2">
																							<input type="text" id="wcap_quick_ck_redirect_to" name="wcap_quick_ck_redirect_to" class="ib-md">
																							<p class="mt-2 mb-0">URL of the page where the popup should redirect. Leaving blank here will take the user to the Checkout page.</p>
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
													<div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
														<div class="subscribe-body buy-subscribe-box text-center">
															<div class="subscribe-head">
																<div>
																	<div class="Cancel-icon"></div>
																</div>
																<h1 class="mb-0" style="color: rgb(115, 127, 151);">We are sad to see you leave</h1>
																<p style="color: rgb(187, 201, 210);">There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase. </p>
																<button class="trietary-btn" style="background-color: rgb(0, 133, 186); color: rgb(255, 255, 255);">Complete my order!</button>
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
					<div class="tbl-mod-1">
						<div class="tm1-row align-items-center">
							<div class="col-left">
								<button class="secondary-btn btn-red">Reset to default configuration</button>
							</div>
							<div class="col-right">
								<div class="row-box-1 add_edit_save">
									<button type="button" class="top-back">Back</button>
									<button type="button">Save Settings</button>
								</div>
							</div>
						</div>
					</div>
					<div class="save-btn mt-4 text-right">
						<div class="coupon__main coupon__two tbl-mod-1">
							<div class="tm1-row align-items-center">
								<div class="col-left">
									<div class="rb-col"></div>
								</div>
								<div class="col-right">
									<div class="row-box-1">
										<div class="rb1-right">
											<div class="rb1-row flx-center"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
	</div>
	<?php do_action( 'wcal_after_settings_page_form' ); ?>
</div>