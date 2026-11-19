/**
 * Copied from the Pro plugin: src/admin/components/SettingsPanel/index.js
 * (ToggleGrid and AccordionCard, the components these settings use).
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Card, CardHeader, CardDivider, Button, Icon, ToggleControl } from '@wordpress/components';
import { chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import SettingRow from './SettingRow';
import './settings-panel.scss';

export { SettingRow };

/**
 * ToggleGrid — renders a set of labelled ToggleControls in a 2-column grid.
 *
 * The value is an object whose keys match each option's `key` field.
 * Stored values follow the legacy 'on'/''/falsy convention;
 * the component converts internally so parent code deals with booleans.
 *
 * @param {Array<{key:string, label:string}>} options
 * @param {Object}   value     - e.g. { all: 'on', checkout: '', ... }
 * @param {Function} onChange  - receives updated object
 */
export const ToggleGrid = ( { options, value = {}, onChange } ) => {
	const isOn = ( v ) => v === 'on' || v === true;

	const toggle = ( key ) => {
		const current = isOn( value[ key ] );
		const next    = current ? '' : 'on';

		if ( key === 'all' ) {
			// Propagate to every key.
			const updated = {};
			options.forEach( ( opt ) => { updated[ opt.key ] = next; } );
			onChange( updated );
			return;
		}

		const updated = { ...value, [ key ]: next };

		// Sync 'all': on when every non-all option is on, off otherwise.
		const others = options.filter( ( o ) => o.key !== 'all' );
		updated.all  = others.every( ( o ) => isOn( updated[ o.key ] ) ) ? 'on' : '';

		onChange( updated );
	};

	return (
		<div className="wcap-toggle-grid">
			{ options.map( ( opt ) => (
				<ToggleControl
					key={ opt.key }
					label={ opt.label }
					checked={ isOn( value[ opt.key ] ) }
					onChange={ () => toggle( opt.key ) }
					__nextHasNoMarginBottom
				/>
			) ) }
		</div>
	);
};

/**
 * A standalone accordion card — a Card with a clickable header that toggles its content.
 *
 * @param {string}    title          - Header title.
 * @param {string}    [subtitle]     - Optional subtitle shown below the title in the header.
 * @param {boolean}   [defaultOpen]  - Whether the accordion starts open.
 * @param {ReactNode} children       - Card content.
 */
export const AccordionCard = ( { title, subtitle, children, defaultOpen = true, onSave, saving = false } ) => {
	const [ isOpen, setIsOpen ] = useState( defaultOpen );

	return (
		<Card size="none" className="wcap-sp-card">
			<CardHeader
				className="wcap-sp-card-header"
				onClick={ () => setIsOpen( ( o ) => ! o ) }
			>
				<div className="wcap-sp-card-header-text">
					<span className="wcap-sp-card-header-title">{ title }</span>
					{ subtitle && <span className="wcap-sp-card-header-subtitle">{ subtitle }</span> }
				</div>
				<Icon icon={ isOpen ? chevronUp : chevronDown } />
			</CardHeader>
			<CardDivider />
			{ isOpen && (
				<>
					{ children }
					{ onSave && (
						<div className="wcap-sp-card-footer">
							<Button
								className="wcap-sp-save-btn"
								onClick={ onSave }
								isBusy={ saving }
								disabled={ saving }
							>
								{ __( 'Save Settings', 'woocommerce-abandoned-cart' ) }
							</Button>
						</div>
					) }
				</>
			) }
		</Card>
	);
};
