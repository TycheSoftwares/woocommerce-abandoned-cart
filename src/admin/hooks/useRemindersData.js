import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { siteDaysAgo } from '../utils/date';

/**
 * Data-fetching hook for the Reminders page tabs.
 *
 * @param {string} reminderType  Only 'email' has data in Lite.
 */
export function useRemindersData( reminderType ) {
	const [ rows,       setRows       ] = useState( [] );
	const [ meta,       setMeta       ] = useState( {} );
	const [ isLoading,  setIsLoading  ] = useState( false );
	const [ error,      setError      ] = useState( null );
	const [ startDate,  setStartDate  ] = useState( siteDaysAgo( 7 ) );
	const [ endDate,    setEndDate    ] = useState( siteDaysAgo() );
	const [ page,       setPage       ] = useState( 1 );

	const fetchData = useCallback( async ( opts = {} ) => {
		setIsLoading( true );
		setError( null );
		try {
			const p          = opts.page      ?? 1;
			const start      = opts.startDate ?? startDate;
			const end        = opts.endDate   ?? endDate;
			const params     = new URLSearchParams( {
				reminder_type: reminderType,
				per_page:      20,
				page:          p,
			} );
			if ( start ) params.set( 'start_date', start );
			if ( end )   params.set( 'end_date',   end );

			const res = await apiFetch( { path: `/wcal/v1/reminders-sent?${ params.toString() }` } );
			setRows( res.data   ?? [] );
			setMeta( res.meta   ?? {} );
			setPage( p );
		} catch ( e ) {
			setError( e?.message || __( 'Failed to load data. Please try again.', 'woocommerce-abandoned-cart' ) );
		} finally {
			setIsLoading( false );
		}
	}, [ reminderType, startDate, endDate ] );

	function handleFilter() {
		fetchData( { page: 1, startDate, endDate } );
	}

	function handlePageChange( p ) {
		fetchData( { page: p, startDate, endDate } );
	}

	return {
		rows,
		meta,
		isLoading,
		error,
		startDate,
		endDate,
		setStartDate,
		setEndDate,
		handleFilter,
		handlePageChange,
		page,
		reload: fetchData,
	};
}
