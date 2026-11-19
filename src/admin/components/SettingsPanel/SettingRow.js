/**
 * Copied from the Pro plugin: src/admin/components/SettingsPanel/SettingRow.js
 */

/**
 * WordPress dependencies
 */
import { CardBody, Tooltip, Icon } from '@wordpress/components';
import { info } from '@wordpress/icons';

/**
 * A single settings row: bold label on the left,
 * optional ⓘ tooltip + field control on the right.
 *
 * @param {string}    label     - Row label text.
 * @param {string}    [tooltip] - Text shown in the ⓘ tooltip. Omit to hide icon.
 * @param {ReactNode} children  - The field control(s).
 */
const SettingRow = ( { label, tooltip, children, alignTop = false, className = '' } ) => (
	<CardBody size="none" className={ `wcap-sp-row${ alignTop ? ' wcap-sp-row--align-top' : '' }${ className ? ' ' + className : '' }` }>
		<div className="wcap-sp-row-label">
			<span className="wcap-sp-row-label-text">{ label }</span>
		</div>

		<div className="wcap-sp-row-field">
			{ tooltip && (
				<Tooltip text={ tooltip }>
					<span className="wcap-sp-info-icon" tabIndex={ 0 } aria-label="More information">
						<Icon icon={ info } size={ 18 } />
					</span>
				</Tooltip>
			) }
			<div className="wcap-sp-row-field-content">
				{ children }
			</div>
		</div>
	</CardBody>
);

export default SettingRow;
