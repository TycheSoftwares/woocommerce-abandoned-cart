/**
 * DataTable
 *
 * Adapted from the order-delivery-date DataTable pattern.
 * Uses @wordpress/components Card primitives + CSS Grid rows.
 *
 * Usage:
 *
 *   <DataTable
 *     columns={ [
 *       { key: 'email', label: 'Email Address' },
 *       { key: 'sent',  label: 'Email Sent Time' },
 *     ] }
 *     rows={ data }
 *     rowKey="id"
 *     filters={ [
 *       { type: 'select', value: duration, onChange: setDuration, options: [...] },
 *       { type: 'date',   value: startDate, onChange: setStartDate },
 *       { type: 'date',   value: endDate,   onChange: setEndDate },
 *     ] }
 *     onApplyFilter={ handleFilter }
 *     totalItems={ settings.total_items }
 *     renderCell={ ( row, colKey ) => row[ colKey ] }
 *     renderActions={ ( row ) => <Button>Delete</Button> }
 *     bulkActions={ [ { value: 'delete', label: 'Delete' } ] }
 *     onBulkApply={ ( action, rows ) => {} }
 *     loading={ isLoading }
 *     emptyMessage="No records found."
 *   />
 *
 * Props:
 *   columns        {Array<{ key, label, tooltip? }>}
 *   rows           {Array<Object>}
 *   rowKey         {string|Function}       Field name or (row)=>key
 *   filters        {Array}                 Filter bar definitions (see usage above)
 *   onApplyFilter  {Function}              Called when "Apply Filter" is clicked
 *   totalItems     {number}                Item count shown right of filter bar
 *   renderCell     {Function}              (row, colKey) => ReactNode
 *   renderActions  {Function}              (row) => ReactNode  — adds Actions column
 *   rowClassName   {Function}              (row) => string
 *   loading        {boolean}
 *   showForm       {boolean}               Show inline form
 *   onToggleForm   {Function}
 *   renderForm     {ReactNode}
 *   addMoreLabel   {string}
 *   bulkActions    {Array<{ value, label }>}
 *   onBulkApply    {Function}              (actionValue, selectedRows) => void
 *   emptyMessage   {string}
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardDivider,
	Button,
	SelectControl,
	Spinner,
	Tooltip,
} from '@wordpress/components';

import './data-table.scss';

const getRowKey = ( row, rowKey ) =>
	typeof rowKey === 'function' ? rowKey( row ) : row[ rowKey ];

const DataTable = ( {
	columns       = [],
	rows          = [],
	rowKey        = 'id',
	sortBy        = '',
	filters       = [],
	onApplyFilter,
	totalItems,
	renderCell,
	renderActions,
	rowClassName,
	loading       = false,
	showForm      = false,
	onToggleForm,
	renderForm,
	addMoreLabel  = __( 'Add More', 'woocommerce-abandoned-cart' ),
	bulkActions   = [],
	onBulkApply,
	emptyMessage  = __( 'No records found.', 'woocommerce-abandoned-cart' ),
} ) => {
	const [ selected,   setSelected   ] = useState( [] );
	const [ bulkAction, setBulkAction ] = useState( '' );

	const hasActions  = typeof renderActions === 'function';
	const hasFilters  = filters.length > 0 || onApplyFilter;
	const allChecked  = rows.length > 0 && selected.length === rows.length;
	const colTemplate = `40px repeat( ${ columns.length }, 1fr )${ hasActions ? ' 120px' : '' }`;

	const toggleAll = () =>
		setSelected( allChecked ? [] : rows.map( ( r ) => getRowKey( r, rowKey ) ) );

	const toggleOne = ( key ) =>
		setSelected( ( prev ) =>
			prev.includes( key ) ? prev.filter( ( k ) => k !== key ) : [ ...prev, key ]
		);

	const handleApply = () => {
		if ( ! bulkAction || ! onBulkApply ) return;
		const selectedRows = rows.filter( ( r ) => selected.includes( getRowKey( r, rowKey ) ) );
		onBulkApply( bulkAction, selectedRows );
		setSelected( [] );
		setBulkAction( '' );
	};

	return (
		<div className="wcap-dt-wrap">

			{ /* ── Filter bar (above card) ── */ }
			{ hasFilters && (
				<div className="wcap-dt-filter-bar">
					{ filters.map( ( filter, i ) => {
						if ( filter.type === 'select' ) {
							return (
								<SelectControl
									key={ i }
									value={ filter.value }
									options={ filter.options }
									onChange={ filter.onChange }
									className="wcap-dt-filter-select"
									__nextHasNoMarginBottom
								/>
							);
						}
						if ( filter.type === 'date' ) {
							return (
								<input
									key={ i }
									type="date"
									value={ filter.value }
									onChange={ ( e ) => filter.onChange( e.target.value ) }
									className="wcap-dt-filter-date"
								/>
							);
						}
						return null;
					} ) }

					{ onApplyFilter && (
						<Button
							variant="secondary"
							className="wcap-dt-apply-btn"
							onClick={ onApplyFilter }
						>
							{ __( 'Apply Filter', 'woocommerce-abandoned-cart' ) }
						</Button>
					) }

					{ totalItems !== undefined && (
						<span className="wcap-dt-item-count">
							{ totalItems } { totalItems === 1
								? __( 'item', 'woocommerce-abandoned-cart' )
								: __( 'items', 'woocommerce-abandoned-cart' ) }
						</span>
					) }
				</div>
			) }

			{ /* ── Card ── */ }
			<Card className="wcap-dt-card" size="none">
			<div className="wcap-dt-scroll">

				{ /* ── Header row ── */ }
				<div className="wcap-dt-header" style={ { gridTemplateColumns: colTemplate } }>
					<div className="wcap-dt-cell">
						<input
							type="checkbox"
							className="wcap-dt-checkbox"
							checked={ allChecked }
							onChange={ toggleAll }
							aria-label={ __( 'Select all rows', 'woocommerce-abandoned-cart' ) }
						/>
					</div>
					{ columns.map( ( col ) => {
						const isSorted  = sortBy === col.key;
						const sortable  = typeof col.onSort === 'function';
						return (
							<div
								key={ col.key }
								className={ `wcap-dt-cell wcap-dt-cell--heading${ sortable ? ' wcap-dt-cell--sortable' : '' }${ isSorted ? ' wcap-dt-cell--sorted' : '' }` }
								onClick={ sortable ? col.onSort : undefined }
								role={ sortable ? 'button' : undefined }
								tabIndex={ sortable ? 0 : undefined }
								onKeyDown={ sortable ? ( e ) => e.key === 'Enter' && col.onSort() : undefined }
							>
								{ col.label }
								{ sortable && (
									<span className={ `wcap-dt-sort-icon${ isSorted ? ' wcap-dt-sort-icon--active' : '' }` } aria-hidden="true">
										{ isSorted ? '↕' : '↕' }
									</span>
								) }
								{ col.tooltip && (
									<Tooltip text={ col.tooltip }>
										<span className="wcap-dt-col-tooltip" aria-label={ col.tooltip }>
											&#9432;
										</span>
									</Tooltip>
								) }
							</div>
						);
					} ) }
					{ hasActions && (
						<div className="wcap-dt-cell wcap-dt-cell--heading">
							{ __( 'Actions', 'woocommerce-abandoned-cart' ) }
						</div>
					) }
				</div>

				{ /* ── Body ── */ }
				{ loading ? (
					<CardBody className="wcap-dt-loading">
						<Spinner />
					</CardBody>
				) : (
					<>
						{ rows.length === 0 && ! showForm && (
							<CardBody className="wcap-dt-empty">
								{ emptyMessage }
							</CardBody>
						) }

						{ rows.map( ( row ) => {
							const key     = getRowKey( row, rowKey );
							const checked = selected.includes( key );

							return (
								<div key={ key }>
									<CardDivider />
									<div
										className={ `wcap-dt-row${ checked ? ' wcap-dt-row--selected' : '' }${ rowClassName ? ' ' + rowClassName( row ) : '' }` }
										style={ { gridTemplateColumns: colTemplate } }
									>
										<div className="wcap-dt-cell">
											<input
												type="checkbox"
												className="wcap-dt-checkbox"
												checked={ checked }
												onChange={ () => toggleOne( key ) }
												aria-label={ __( 'Select row', 'woocommerce-abandoned-cart' ) }
											/>
										</div>
										{ columns.map( ( col ) => (
											<div key={ col.key } className="wcap-dt-cell">
												{ renderCell
													? renderCell( row, col.key )
													: ( row[ col.key ] ?? '—' )
												}
											</div>
										) ) }
										{ hasActions && (
											<div className="wcap-dt-cell">
												{ renderActions( row ) }
											</div>
										) }
									</div>
								</div>
							);
						} ) }
					</>
				) }

				</div> { /* end wcap-dt-scroll */ }

				{ /* ── Inline form slot ── */ }
				{ ! loading && showForm && (
					<>
						<CardDivider />
						<CardBody size="none" className="wcap-dt-form-slot">
							{ renderForm }
						</CardBody>
					</>
				) }

				{ /* ── Add More / Cancel ── */ }
				{ onToggleForm && (
					<>
						<CardDivider />
						<CardBody className="wcap-dt-add-more-row" size="small">
							{ showForm ? (
								<Button variant="link" className="wcap-dt-cancel-link" onClick={ onToggleForm }>
									{ __( '✕ Cancel', 'woocommerce-abandoned-cart' ) }
								</Button>
							) : (
								<Button variant="link" className="wcap-dt-add-more-link" onClick={ onToggleForm }>
									<span className="wcap-dt-add-more-plus" aria-hidden="true">+</span>
									<span className="wcap-dt-add-more-label">{ addMoreLabel }</span>
								</Button>
							) }
						</CardBody>
					</>
				) }
			</Card>

			{ /* ── Footer: bulk actions ── */ }
			{ bulkActions.length > 0 && (
				<div className="wcap-dt-footer">
					<SelectControl
						className="wcap-dt-bulk-select"
						value={ bulkAction }
						options={ [
							{ value: '', label: __( 'Bulk Action', 'woocommerce-abandoned-cart' ) },
							...bulkActions,
						] }
						onChange={ ( val ) => setBulkAction( val ) }
						__nextHasNoMarginBottom
					/>
					<Button variant="secondary" className="wcap-dt-apply-btn" onClick={ handleApply }>
						{ __( 'Apply', 'woocommerce-abandoned-cart' ) }
					</Button>
				</div>
			) }
		</div>
	);
};

export default DataTable;
