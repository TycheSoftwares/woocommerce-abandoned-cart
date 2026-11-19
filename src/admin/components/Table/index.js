/**
 * Table — a generic, accessible data table built on @wordpress/components.
 *
 * Features
 * ─────────
 * • Native <table> element (screen-reader friendly)
 * • Column-level render functions, widths, and alignment
 * • Optional row-checkbox selection + bulk-action bar
 * • Optional column sorting (controlled — parent owns sort state)
 * • Optional filter slot (any JSX, rendered above the card)
 * • Built-in loading skeleton and empty state
 * • Built-in pagination (uses the existing Pagination component)
 *
 * Basic usage
 * ───────────
 *   <Table
 *     columns={ [
 *       { key: 'name',   label: 'Name',   width: '40%' },
 *       { key: 'status', label: 'Status', width: '20%', align: 'center',
 *         render: ( row ) => <Badge>{ row.status }</Badge> },
 *       { key: 'date',   label: 'Date',   sortable: true },
 *     ] }
 *     rows={ data }
 *     rowKey="id"
 *   />
 *
 * Props
 * ─────
 *   columns          {Column[]}        Column definitions (see type below)
 *   rows             {Object[]}        Row data
 *   rowKey           {string|Function} Field name or (row)=>key
 *   loading          {boolean}
 *   emptyMessage     {string|ReactNode}
 *   renderActions    {Function}        (row) => ReactNode — appends "Actions" column
 *   rowClassName     {Function}        (row) => string
 *
 *   // Sorting (controlled)
 *   sortBy           {string}          Active sort column key
 *   sortDir          {'asc'|'desc'}
 *   onSort           {Function}        (key) => void
 *
 *   // Row selection + bulk actions
 *   bulkActions      {Array<{ value, label }>}
 *   onBulkApply      {Function}        (actionValue, selectedRows) => void
 *
 *   // Filter slot
 *   filters          {ReactNode}       Rendered above the card (free-form JSX)
 *
 *   // Pagination
 *   currentPage      {number}
 *   totalPages       {number}
 *   totalItems       {number}
 *   onPageChange     {Function}        (page: number) => void
 *
 * Column type
 * ───────────
 *   {
 *     key:      string,
 *     label:    string,
 *     width?:   string,           e.g. '20%', '120px'
 *     align?:   'left'|'center'|'right',
 *     tooltip?: string,
 *     sortable? boolean,
 *     render?:  (row, value) => ReactNode,
 *   }
 */

import { useState } from '@wordpress/element';
import {
	Card,
	Button,
	SelectControl,
	Spinner,
	Tooltip,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Pagination } from '../Pagination';
import './table.scss';

// ─── Helpers ──────────────────────────────────────────────────────────────────

const getRowKey = ( row, rowKey ) =>
	typeof rowKey === 'function' ? rowKey( row ) : row[ rowKey ];

// ─── Sort icon ────────────────────────────────────────────────────────────────

function SortIcon( { active, dir } ) {
	if ( ! active ) {
		return <span className="wcap-tbl-sort-icon" aria-hidden="true">⇅</span>;
	}
	return (
		<span className="wcap-tbl-sort-icon wcap-tbl-sort-icon--active" aria-hidden="true">
			{ dir === 'asc' ? '↑' : '↓' }
		</span>
	);
}

// ─── Main component ───────────────────────────────────────────────────────────

const Table = ( {
	// Data
	columns       = [],
	rows          = [],
	rowKey        = 'id',
	loading       = false,
	emptyMessage  = __( 'No records found.', 'woocommerce-abandoned-cart' ),

	// Cell / row customisation
	renderActions,
	rowClassName,

	// Sorting (controlled by parent)
	sortBy,
	sortDir = 'asc',
	onSort,

	// Selection + bulk actions
	bulkActions  = [],
	onBulkApply,

	// Filter slot (free-form JSX placed above the card)
	filters,

	// Pagination
	currentPage,
	totalPages,
	totalItems,
	onPageChange,
} ) => {
	const [ selected,   setSelected   ] = useState( [] );
	const [ bulkAction, setBulkAction ] = useState( '' );

	const hasActions    = typeof renderActions === 'function';
	const hasCheckboxes = bulkActions.length > 0;
	const allChecked    = rows.length > 0 && selected.length === rows.length;
	const hasPagination = onPageChange && totalPages > 0;

	// Selection helpers
	const toggleAll = () =>
		setSelected( allChecked ? [] : rows.map( ( r ) => getRowKey( r, rowKey ) ) );

	const toggleOne = ( key ) =>
		setSelected( ( prev ) =>
			prev.includes( key ) ? prev.filter( ( k ) => k !== key ) : [ ...prev, key ]
		);

	const handleBulkApply = () => {
		if ( ! bulkAction || ! onBulkApply ) return;
		const selectedRows = rows.filter( ( r ) => selected.includes( getRowKey( r, rowKey ) ) );
		onBulkApply( bulkAction, selectedRows );
		setSelected( [] );
		setBulkAction( '' );
	};

	const handleSort = ( key ) => {
		if ( typeof onSort === 'function' ) onSort( key );
	};

	// ── Shared header row renderer (used in both thead and tfoot) ────────────────
	const renderHeaderRow = ( isFooter = false ) => (
		<tr>
			{ hasCheckboxes && (
				<th className="wcap-tbl-th wcap-tbl-th--checkbox">
					{ ! isFooter && (
						<input
							type="checkbox"
							className="wcap-tbl-checkbox"
							checked={ allChecked }
							onChange={ toggleAll }
							aria-label={ __( 'Select all', 'woocommerce-abandoned-cart' ) }
						/>
					) }
				</th>
			) }

			{ columns.map( ( col ) => {
				const isSorted  = sortBy === col.key;
				const canSort   = ! isFooter && col.sortable && typeof onSort === 'function';
				const alignment = col.align ?? 'left';

				return (
					<th
						key={ col.key }
						className={ [
							'wcap-tbl-th',
							canSort  ? 'wcap-tbl-th--sortable' : '',
							isSorted ? 'wcap-tbl-th--sorted'   : '',
						].join( ' ' ).trim() }
						style={ { textAlign: alignment } }
						onClick={ canSort ? () => handleSort( col.key ) : undefined }
						aria-sort={
							isSorted
								? sortDir === 'asc' ? 'ascending' : 'descending'
								: canSort ? 'none' : undefined
						}
					>
						<span className="wcap-tbl-th-inner">
							{ col.label }
							{ canSort && <SortIcon active={ isSorted } dir={ sortDir } /> }
							{ ! isFooter && col.tooltip && (
								<Tooltip text={ col.tooltip }>
									<span
										className="wcap-tbl-th-tip"
										tabIndex={ 0 }
										aria-label={ col.tooltip }
									>
										&#9432;
									</span>
								</Tooltip>
							) }
						</span>
					</th>
				);
			} ) }

			{ hasActions && (
				<th className="wcap-tbl-th wcap-tbl-th--actions">
					{ __( 'Actions', 'woocommerce-abandoned-cart' ) }
				</th>
			) }
		</tr>
	);

	// ── Shared bulk bar renderer (used at top and bottom) ─────────────────────
	const renderBulkBar = () => (
		<div className="wcap-tbl-bottom-bar">
			{ bulkActions.length > 0 && (
				<div className="wcap-tbl-bulk">
					<SelectControl
						className="wcap-tbl-bulk-select"
						value={ bulkAction }
						options={ [
							{ value: '', label: __( 'Bulk Action', 'woocommerce-abandoned-cart' ) },
							...bulkActions,
						] }
						onChange={ setBulkAction }
						__nextHasNoMarginBottom
					/>
					<Button
						variant="secondary"
						className="wcap-tbl-bulk-apply"
						onClick={ handleBulkApply }
						disabled={ ! bulkAction || selected.length === 0 }
					>
						{ __( 'Apply', 'woocommerce-abandoned-cart' ) }
					</Button>
				</div>
			) }
		</div>
	);

	return (
		<div className="wcap-tbl-wrap">

			{ /* ── Filter slot ── */ }
			{ filters && (
				<div className="wcap-tbl-filters">{ filters }</div>
			) }

			{ /* ── Top bar: bulk actions + item count ── */ }
			{ ( bulkActions.length > 0 || ( totalItems !== null && totalItems !== undefined ) ) && renderBulkBar() }

			{ /* ── Card ── */ }
			<Card className="wcap-tbl-card" size="none">
				<table className="wcap-tbl">

					{ /* colgroup: only rendered when at least one column has a width */ }
					{ columns.some( ( c ) => c.width ) && (
						<colgroup>
							{ hasCheckboxes && <col style={ { width: '40px' } } /> }
							{ columns.map( ( col ) => (
								<col key={ col.key } style={ col.width ? { width: col.width } : undefined } />
							) ) }
							{ hasActions && <col style={ { width: '140px' } } /> }
						</colgroup>
					) }

					{ /* ── <thead> ── */ }
					<thead className="wcap-tbl-head">
						{ renderHeaderRow( false ) }
					</thead>

					{ /* ── <tbody> ── */ }
					<tbody className="wcap-tbl-body">
						{ loading ? (
							<tr>
								<td
									colSpan={ ( hasCheckboxes ? 1 : 0 ) + columns.length + ( hasActions ? 1 : 0 ) }
									className="wcap-tbl-td wcap-tbl-td--loading"
								>
									<Spinner />
								</td>
							</tr>
						) : rows.length === 0 ? (
							<tr>
								<td
									colSpan={ ( hasCheckboxes ? 1 : 0 ) + columns.length + ( hasActions ? 1 : 0 ) }
									className="wcap-tbl-td wcap-tbl-td--empty"
								>
									{ emptyMessage }
								</td>
							</tr>
						) : (
							rows.map( ( row ) => {
								const key     = getRowKey( row, rowKey );
								const checked = selected.includes( key );
								const extra   = rowClassName ? rowClassName( row ) : '';

								return (
									<tr
										key={ key }
										className={ [
											'wcap-tbl-tr',
											checked ? 'wcap-tbl-tr--selected' : '',
											extra,
										].join( ' ' ).trim() }
									>
										{ hasCheckboxes && (
											<td className="wcap-tbl-td wcap-tbl-td--checkbox">
												<input
													type="checkbox"
													className="wcap-tbl-checkbox"
													checked={ checked }
													onChange={ () => toggleOne( key ) }
													aria-label={ __( 'Select row', 'woocommerce-abandoned-cart' ) }
												/>
											</td>
										) }

										{ columns.map( ( col ) => {
											const value     = row[ col.key ];
											const alignment = col.align ?? 'left';
											return (
												<td
													key={ col.key }
													className="wcap-tbl-td"
													style={ { textAlign: alignment } }
												>
													{ typeof col.render === 'function'
														? col.render( row, value )
														: ( value ?? '—' )
													}
												</td>
											);
										} ) }

										{ hasActions && (
											<td className="wcap-tbl-td wcap-tbl-td--actions">
												{ renderActions( row ) }
											</td>
										) }
									</tr>
								);
							} )
						) }
					</tbody>

					{ /* ── <tfoot> — repeats column headers ── */ }
					<tfoot className="wcap-tbl-foot">
						{ renderHeaderRow( true ) }
					</tfoot>
				</table>
			</Card>

			{ /* ── Bottom bar: bulk actions + item count + pagination ── */ }
			{ ( bulkActions.length > 0 || hasPagination || ( totalItems !== null && totalItems !== undefined ) ) && (
				<div className="wcap-tbl-bottom-bar">
					{ bulkActions.length > 0 && (
						<div className="wcap-tbl-bulk">
							<SelectControl
								className="wcap-tbl-bulk-select"
								value={ bulkAction }
								options={ [
									{ value: '', label: __( 'Bulk Action', 'woocommerce-abandoned-cart' ) },
									...bulkActions,
								] }
								onChange={ setBulkAction }
								__nextHasNoMarginBottom
							/>
							<Button
								variant="secondary"
								className="wcap-tbl-bulk-apply"
								onClick={ handleBulkApply }
								disabled={ ! bulkAction || selected.length === 0 }
							>
								{ __( 'Apply', 'woocommerce-abandoned-cart' ) }
							</Button>
						</div>
					) }

					{ hasPagination && (
						<div className="wcap-tbl-pagination">
							<Pagination
								currentPage={ currentPage }
								totalPages={ totalPages }
								totalItems={ totalItems }
								previousDisabled={ currentPage <= 1 ? 'disabled' : '' }
								nextDisabled={ currentPage >= totalPages ? 'disabled' : '' }
								previousPage={ currentPage - 1 }
								nextPage={ currentPage + 1 }
								lastPage={ totalPages }
								onPageChange={ onPageChange }
							/>
						</div>
					) }
				</div>
			) }
		</div>
	);
};

export default Table;
