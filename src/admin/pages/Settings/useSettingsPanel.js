/**
 * Load, save and form state of a settings panel.
 * From the Pro plugin: src/admin/pages/Settings/tabs/GeneralSettings.js
 *
 * The form is also reset with what the server stored, since a validator can
 * store something other than what was submitted.
 */

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * External dependencies
 */
import { useForm } from 'react-hook-form';

export function useSettingsPanel( endpoint, defaults, saveRef, onNotice = null, initialData = null ) {
	const [ loading, setLoading ] = useState( () => ! initialData );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const { control, handleSubmit, reset, watch } = useForm( { defaultValues: defaults } );

	useEffect( () => {
		if ( initialData ) {
			reset( initialData );
			return;
		}
		apiFetch( { path: endpoint } )
			.then( ( res ) => reset( res.data ?? res ) )
			.catch( () => setNotice( { type: 'error', message: __( 'Failed to load settings.', 'woocommerce-abandoned-cart' ) } ) )
			.finally( () => setLoading( false ) );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	async function onSave( values ) {
		setSaving( true );
		setNotice( null );
		const notify = onNotice || setNotice;
		try {
			const res = await apiFetch( { path: endpoint, method: 'POST', data: values } );
			reset( res.data ?? values );
			notify( { type: 'success', message: __( 'Settings saved successfully.', 'woocommerce-abandoned-cart' ) } );
		} catch ( e ) {
			if ( e?.data?.saved ) {
				reset( e.data.saved );
			}
			notify( { type: 'error', message: e?.message || __( 'Failed to save settings.', 'woocommerce-abandoned-cart' ) } );
			throw e;
		} finally {
			setSaving( false );
		}
	}

	// Expose save to parent via ref so "Save All" can trigger it.
	if ( saveRef ) {
		saveRef.current = () => handleSubmit( onSave )();
	}

	// The error is already shown by onSave.
	const saveCard = () => handleSubmit( onSave )().catch( () => {} );

	return { control, watch, loading, saving, notice, setNotice, saveCard };
}
