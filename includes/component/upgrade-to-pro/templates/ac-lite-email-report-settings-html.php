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
								<h2 class="panel-title">Reports</h2>
							</div>
							<div class="panel-collapse collapse show">
								<div class="panel-body">
									<div class="tbl-mod-1">
										<div class="tm1-row flx-center">
											<div class="col-left">
												<label>Frequency with which report should be sent:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo esc_url( $icon_info ); ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Send emails on weekly/monthly basis." class="tt-info">
													<select class="ib-md">
														<option>Weekly</option>
														<option>Monthly</option>
													</select>
												</div>
											</div>
										</div>
										<div class="tm1-row">
											<div class="col-left">
												<label>Send report emails to:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center"><img src="<?php echo esc_url( $icon_info ); ?>" alt="Info" data-toggle="tooltip" data-placement="top" title="Enter a list of email addresses. Each email address should be on a new line." class="tt-info">
													<textarea rows="4" class="ib-xl"></textarea>
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
