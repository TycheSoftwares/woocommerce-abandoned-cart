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
								<h2 class="panel-title">Export Settings</h2>
								<p>Download your plugin configuration as a JSON file.</p>
							</div>
							<div class="panel-collapse collapse show">
								<div class="panel-body">
									<div class="tbl-mod-1">
										<div class="tm1-row">
											<div class="col-left">
												<label>Settings to export:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center">
													<label><input type="radio" name="wcal_export_mode" checked> All Settings (Default)</label>
													&nbsp;&nbsp;
													<label><input type="radio" name="wcal_export_mode"> Select specific settings to export</label>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="panel panel-default">
							<div class="panel-heading">
								<h2 class="panel-title">Import Settings</h2>
							</div>
							<div class="panel-collapse collapse show">
								<div class="panel-body">
									<div class="tbl-mod-1">
										<div class="tm1-row">
											<div class="col-left">
												<label>JSON file:</label>
											</div>
											<div class="col-right">
												<div class="rc-flx-wrap flx-aln-center">
													<input type="file">
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
