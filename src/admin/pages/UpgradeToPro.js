/**
 * Upgrade to Pro page. Shows the same content the plugin's Upgrade to Pro tab
 * has always shown.
 */

/**
 * Internal dependencies
 */
import { UpgradeSection } from '../components/UpgradeSection';

export function UpgradeToPro() {
	return (
		<div className="wcap-page wcap-page--upgrade-to-pro">
			<UpgradeSection section="wcap_fb_settings" />
		</div>
	);
}
