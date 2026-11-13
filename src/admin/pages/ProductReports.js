/**
 * Product Reports page — abandoned vs recovered per product.
 * Data: GET /wcal/v1/product-report
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Notice, Spinner }                  from '@wordpress/components';
import { __ }                               from '@wordpress/i18n';
import apiFetch                             from '@wordpress/api-fetch';

import { Pagination } from '../components';

// ── Simple table matching the screenshot ──────────────────────────────────────

const HEADER_BG = '#ede7f6';

const COLUMNS = [
	{ key: 'name',            label: __( 'Product Name',              'woocommerce-abandoned-cart' ) },
	{ key: 'times_abandoned', label: __( 'Number of Times Abandoned', 'woocommerce-abandoned-cart' ) },
	{ key: 'times_recovered', label: __( 'Number of Times Recovered', 'woocommerce-abandoned-cart' ) },
];

function HeaderRow() {
	return (
		<div style={ {
			display:             'grid',
			gridTemplateColumns: '2fr 1fr 1fr',
			padding:             '14px 20px',
			background:          HEADER_BG,
			fontWeight:          '600',
			fontSize:            '13px',
			color:               '#1e1e1e',
		} }>
			{ COLUMNS.map( ( col ) => (
				<div key={ col.key }>{ col.label }</div>
			) ) }
		</div>
	);
}

function ProductTable( { rows, loading, emptyMessage } ) {
	return (
		<div style={ {
			border:       '1px solid #ddd',
			borderRadius: '8px',
			overflow:     'hidden',
			background:   '#fff',
		} }>

			<HeaderRow />

			{ loading ? (
				<div style={ { padding: '32px', textAlign: 'center' } }>
					<Spinner />
				</div>
			) : rows.length === 0 ? (
				<div style={ { padding: '20px', color: '#757575', fontSize: '13px', borderTop: '1px solid #eee' } }>
					{ emptyMessage }
				</div>
			) : (
				rows.map( ( row, i ) => (
					<div
						key={ row.product_id ?? i }
						style={ {
							display:             'grid',
							gridTemplateColumns: '2fr 1fr 1fr',
							padding:             '14px 20px',
							fontSize:            '13px',
							color:               '#1e1e1e',
							borderTop:           '1px solid #eee',
						} }
					>
						<div>
							<a href={ `${ window.wcalAdminData?.adminUrl ?? '' }post.php?post=${ row.product_id }&action=edit` }>
								{ row.name ?? '—' }
							</a>
						</div>
						<div>{ row.times_abandoned }</div>
						<div>{ row.times_recovered }</div>
					</div>
				) )
			) }

			{ /* Footer — repeat header */ }
			{ ! loading && rows.length > 0 && <HeaderRow /> }
		</div>
	);
}

// ── Item count label ──────────────────────────────────────────────────────────

function ItemCount( { total } ) {
	return (
		<div style={ { textAlign: 'right', fontSize: '13px', color: '#555', marginBottom: '8px' } }>
			{ total } { total === 1
				? __( 'item', 'woocommerce-abandoned-cart' )
				: __( 'items', 'woocommerce-abandoned-cart' ) }
		</div>
	);
}

// ── Page ──────────────────────────────────────────────────────────────────────

export function ProductReports() {
	const [ rows,      setRows      ] = useState( [] );
	const [ meta,      setMeta      ] = useState( {} );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error,     setError     ] = useState( null );
	const [ page,      setPage      ] = useState( 1 );

	const fetchData = useCallback( async () => {
		setIsLoading( true );
		setError( null );
		try {
			const res = await apiFetch( { path: `/wcal/v1/product-report?per_page=30&order=desc&page=${ page }` } );
			setRows( res.data ?? [] );
			setMeta( res.meta ?? {} );
		} catch ( e ) {
			setError( e?.message || __( 'Failed to load product reports. Please try again.', 'woocommerce-abandoned-cart' ) );
		} finally {
			setIsLoading( false );
		}
	}, [ page ] );

	useEffect( () => { fetchData(); }, [ fetchData ] );

	const total      = meta.total ?? rows.length;
	const totalPages = meta.pages ?? 1;

	return (
		<div className="wcap-page wcap-page--product-reports" style={ { padding: '16px 20px', marginTop: '20px' } }>

			{ error && (
				<Notice status="error" isDismissible={ false } style={ { marginBottom: '16px' } }>
					{ error }
				</Notice>
			) }

			<p style={ { margin: '0 0 20px', color: '#444', fontSize: '13px' } }>
				{ __( 'The below list shows the products which have been abandoned and also the number of times they have been recovered.', 'woocommerce-abandoned-cart' ) }
			</p>

			<ItemCount total={ total } />

			<ProductTable
				rows={ rows }
				loading={ isLoading }
				emptyMessage={ __( 'No product reports found.', 'woocommerce-abandoned-cart' ) }
			/>

			{ ! isLoading && rows.length > 0 && (
				<div style={ { marginTop: '12px' } }>
					<Pagination
						currentPage={ page }
						totalPages={ totalPages }
						totalItems={ total }
						previousDisabled={ page <= 1 ? 'disabled' : '' }
						nextDisabled={ page >= totalPages ? 'disabled' : '' }
						previousPage={ page - 1 }
						nextPage={ page + 1 }
						lastPage={ totalPages }
						onPageChange={ setPage }
					/>
				</div>
			) }
		</div>
	);
}
