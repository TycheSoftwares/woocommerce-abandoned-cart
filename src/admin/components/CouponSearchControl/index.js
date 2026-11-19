/**
 * CouponSearchControl
 *
 * A FormTokenField that searches existing WooCommerce coupons via the
 * WC REST API (/wc/v3/coupons?search=…) as the user types.
 *
 * Props:
 *   value    {string}   Comma separated coupon post IDs (controlled).
 *   onChange {Function} Called with the comma separated coupon post IDs.
 *
 * The field displays the human-readable coupon code but stores/returns the
 * WooCommerce coupon post ID. An ID with no coupon behind it shows as #ID, so
 * saving the template doesn't drop it unless it's removed here.
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import { FormTokenField }                            from '@wordpress/components';
import { __ }                                        from '@wordpress/i18n';
import apiFetch                                      from '@wordpress/api-fetch';

const parseIds = ( value ) => String( value ?? '' ).split( ',' ).map( ( id ) => id.trim() ).filter( ( id ) => /^\d+$/.test( id ) );

export function CouponSearchControl( { value, onChange } ) {

	const [ suggestions, setSuggestions ] = useState( [] );
	const [ , setResolved ]               = useState( 0 );
	const debounceRef = useRef( null );

	// id ↔ code bidirectional maps populated from search results.
	const idToCode = useRef( {} );
	const codeToId = useRef( {} );

	const remember = ( coupons ) => {
		( coupons || [] ).forEach( ( c ) => {
			idToCode.current[ String( c.id ) ] = c.code;
			codeToId.current[ c.code ]         = String( c.id );
		} );
	};

	const ids = parseIds( value );

	// Resolve the display codes of IDs we haven't seen yet.
	useEffect( () => {
		const missing = ids.filter( ( id ) => ! idToCode.current[ id ] );
		if ( ! missing.length ) return;

		apiFetch( { path: `/wc/v3/coupons?include=${ missing.join( ',' ) }&per_page=100` } )
			.then( ( coupons ) => {
				remember( coupons );
				setResolved( ( n ) => n + 1 );
			} )
			.catch( () => {} );
	}, [ value ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const onInputChange = useCallback( ( search ) => {
		clearTimeout( debounceRef.current );

		if ( ! search ) {
			setSuggestions( [] );
			return;
		}

		debounceRef.current = setTimeout( async () => {
			try {
				const results = await apiFetch( {
					path: `/wc/v3/coupons?search=${ encodeURIComponent( search ) }&per_page=20`,
				} );
				remember( results );
				setSuggestions( ( results || [] ).map( ( c ) => c.code ) );
			} catch {
				setSuggestions( [] );
			}
		}, 300 );
	}, [] );

	const handleChange = ( tokens ) => {
		const nextIds = tokens
			.map( ( token ) => {
				const code = typeof token === 'string' ? token : token.value;
				return codeToId.current[ code ] ?? ( /^#\d+$/.test( code ) ? code.slice( 1 ) : null );
			} )
			.filter( Boolean );
		onChange( [ ...new Set( nextIds ) ].join( ',' ) );
	};

	return (
		<FormTokenField
			label={ __( 'Search for a Coupon…', 'woocommerce-abandoned-cart' ) }
			value={ ids.map( ( id ) => idToCode.current[ id ] ?? `#${ id }` ) }
			suggestions={ suggestions }
			onInputChange={ onInputChange }
			onChange={ handleChange }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		/>
	);
}
