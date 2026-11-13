/**
 * A setting that's available only in the Pro version.
 *
 * Shows the Pro plugin's row with its control disabled and a PRO badge linking
 * to the trial page. These rows aren't part of any form, so nothing is read or saved.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, TextControl, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SettingRow } from '../SettingsPanel';
import './pro-locked-row.scss';

const noop = () => {};

/**
 * @param {string} [href] - Link target. Defaults to the trial page.
 */
export const ProBadge = ( { href } ) => (
	<a
		className="wcal-pro-badge"
		href={ href ?? window.wcalAdminData?.trialUrl }
		target="_blank"
		rel="noopener noreferrer"
	>
		{ __( 'PRO', 'woocommerce-abandoned-cart' ) }
	</a>
);

/**
 * @param {string}    label     - Row label text.
 * @param {string}    [tooltip] - Text shown in the info tooltip.
 * @param {ReactNode} children  - The field control(s).
 */
const ProLockedRow = ( { label, tooltip, children } ) => (
	<SettingRow
		className="wcal-pro-locked"
		label={
			<>
				{ label } <ProBadge />
			</>
		}
		tooltip={ tooltip }
	>
		<Disabled>{ children }</Disabled>
	</SettingRow>
);

export const LockedToggle = ( props ) => (
	<ProLockedRow { ...props }>
		<ToggleControl checked={ false } onChange={ noop } __nextHasNoMarginBottom />
	</ProLockedRow>
);

export const LockedText = ( props ) => (
	<ProLockedRow { ...props }>
		<TextControl value="" onChange={ noop } __nextHasNoMarginBottom />
	</ProLockedRow>
);

export { noop };
export default ProLockedRow;
