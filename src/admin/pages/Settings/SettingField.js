/**
 * A row for a setting of this plugin, using the label and description of its
 * registered settings field. Read-only for users who can't save the settings.
 */

/**
 * WordPress dependencies
 */
import { Disabled, TextControl, TextareaControl, ToggleControl } from '@wordpress/components';

/**
 * External dependencies
 */
import { Controller } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { SettingRow } from '../../components/SettingsPanel';

const CONTROLS = {
	toggle: ( field ) => (
		<ToggleControl checked={ !! field.value } onChange={ field.onChange } __nextHasNoMarginBottom />
	),
	text: ( field ) => (
		<TextControl value={ field.value } onChange={ field.onChange } __nextHasNoMarginBottom />
	),
	textarea: ( field ) => (
		<TextareaControl value={ field.value } onChange={ field.onChange } rows={ 4 } __nextHasNoMarginBottom />
	),
};

/**
 * @param {string}   name     - Option name.
 * @param {Object}   control  - react-hook-form control.
 * @param {string}   [type]   - One of the CONTROLS keys.
 * @param {Function} [render] - Renders a custom control, instead of `type`.
 */
const SettingField = ( { name, control, type = 'text', render } ) => {
	const { fields, canSave } = window.wcalAdminData;

	return (
		<SettingRow label={ fields[ name ]?.label } tooltip={ fields[ name ]?.description }>
			<Disabled isDisabled={ ! canSave }>
				<Controller
					name={ name }
					control={ control }
					render={ ( { field } ) => ( render ?? CONTROLS[ type ] )( field ) }
				/>
			</Disabled>
		</SettingRow>
	);
};

export default SettingField;
