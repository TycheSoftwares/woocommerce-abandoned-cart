import { __ } from '@wordpress/i18n';
import './pagination.scss';

/**
 * Pagination controls.
 *
 * @param {Object}         props
 * @param {number}         props.currentPage
 * @param {number}         props.totalPages
 * @param {number}         props.totalItems
 * @param {boolean|string} props.previousDisabled  true | 'disabled' | ''
 * @param {boolean|string} props.nextDisabled      true | 'disabled' | ''
 * @param {number}         props.previousPage
 * @param {number}         props.nextPage
 * @param {number}         props.lastPage
 * @param {Function}       props.onPageChange      (page: number) => void
 */
export function Pagination( {
	currentPage,
	totalPages,
	totalItems,
	previousDisabled,
	nextDisabled,
	previousPage,
	nextPage,
	lastPage,
	onPageChange,
} ) {
	if ( ! totalItems ) return null;

	const isPrevDisabled = previousDisabled === 'disabled' || previousDisabled === true;
	const isNextDisabled = nextDisabled === 'disabled' || nextDisabled === true;

	return (
		<div className="wcap-pagination">
			<span className="wcap-pagination__count">{ totalItems } { __( 'items', 'woocommerce-abandoned-cart' ) }</span>

			{ totalPages > 1 && (
				<>
					<button
						className="wcap-pagination__btn"
						disabled={ isPrevDisabled }
						onClick={ () => onPageChange( 1 ) }
						aria-label={ __( 'First page', 'woocommerce-abandoned-cart' ) }
					>«</button>

					<button
						className="wcap-pagination__btn"
						disabled={ isPrevDisabled }
						onClick={ () => onPageChange( previousPage ) }
						aria-label={ __( 'Previous page', 'woocommerce-abandoned-cart' ) }
					>‹</button>

					<input
						type="text"
						className="wcap-pagination__input"
						value={ currentPage }
						aria-label={ __( 'Current page', 'woocommerce-abandoned-cart' ) }
						onChange={ ( e ) => {
							const p = parseInt( e.target.value, 10 );
							if ( ! isNaN( p ) && p >= 1 && p <= totalPages ) onPageChange( p );
						} }
					/>

					<span className="wcap-pagination__of">{ __( 'of', 'woocommerce-abandoned-cart' ) } { totalPages }</span>

					<button
						className="wcap-pagination__btn"
						disabled={ isNextDisabled }
						onClick={ () => onPageChange( nextPage ) }
						aria-label={ __( 'Next page', 'woocommerce-abandoned-cart' ) }
					>›</button>

					<button
						className="wcap-pagination__btn"
						disabled={ isNextDisabled }
						onClick={ () => onPageChange( lastPage ) }
						aria-label={ __( 'Last page', 'woocommerce-abandoned-cart' ) }
					>»</button>
				</>
			) }
		</div>
	);
}
