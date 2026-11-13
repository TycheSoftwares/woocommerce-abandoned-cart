/**
 * FAQ & Support page — accordion FAQ items matching the legacy PHP tab.
 */

import { useState } from '@wordpress/element';
import { __ }       from '@wordpress/i18n';

const FAQS = window.wcalAdminData?.faq ?? [];

// ── Accordion item ────────────────────────────────────────────────────────────

function AccordionItem( { question, answer } ) {
	const [ open, setOpen ] = useState( false );

	return (
		<div style={ { marginBottom: '12px' } }>
			<button
				type="button"
				onClick={ () => setOpen( ( o ) => ! o ) }
				aria-expanded={ open }
				style={ {
					width:           '100%',
					textAlign:       'left',
					background:      open ? '#eee9fe' : '#f2f2f2',
					border:          'none',
					borderRadius:    open ? '8px 8px 0 0' : '8px',
					padding:         '14px 48px 14px 15px',
					cursor:          'pointer',
					fontSize:        '13px',
					fontWeight:      '500',
					color:           '#1e1e1e',
					position:        'relative',
					display:         'block',
					lineHeight:      '1.4',
					transition:      'background 0.15s',
				} }
			>
				{ question }
				<span style={ {
					position:   'absolute',
					right:      '16px',
					top:        '50%',
					transform:  'translateY(-50%)',
					fontSize:   '18px',
					fontWeight: '300',
					color:      '#41278d',
				} }>
					{ open ? '−' : '+' }
				</span>
			</button>

			{ open && (
				<div style={ {
					background:    '#fbfcfa',
					borderRadius:  '0 0 8px 8px',
					padding:       '12px 24px',
					fontSize:      '13px',
					color:         '#444',
					lineHeight:    '1.6',
					borderBottom:  '2px solid rgba(66,71,76,0.05)',
				} }>
					<span dangerouslySetInnerHTML={ { __html: answer } } />
				</div>
			) }
		</div>
	);
}

// ── Page ──────────────────────────────────────────────────────────────────────

const SUPPORT_URL = window.wcalAdminData?.supportUrl;
const DOCS_URL    = window.wcalAdminData?.docsUrl;

const half  = Math.ceil( FAQS.length / 2 );
const left  = FAQS.slice( 0, half );
const right = FAQS.slice( half );

export function FaqSupport() {
	return (
		<div className="wcap-page wcap-page--faq" style={ { padding: '16px 20px', marginTop: '40px' } }>

			{ /* Header card */ }
			<div style={ {
				background:   '#fff',
				borderRadius: '10px',
				boxShadow:    '0 2px 2px rgba(0,0,0,.05), 0 0 1px rgba(66,71,76,.1)',
				marginBottom: '24px',
				overflow:     'hidden',
			} }>
				<div style={ {
					borderBottom: '1px solid rgba(220,220,222,.7)',
					padding:      '18px 30px',
				} }>
					<h2 style={ { margin: 0, fontSize: '16px', fontWeight: '600', color: '#1e1e1e' } }>
						{ __( 'Frequently Asked Questions', 'woocommerce-abandoned-cart' ) }
					</h2>
				</div>

				<div style={ { padding: '20px 30px 30px' } }>
					{ /* Two-column accordion */ }
					<div style={ { display: 'flex', gap: '24px', alignItems: 'flex-start' } }>
						<div style={ { flex: 1, minWidth: 0 } }>
							{ left.map( ( faq, i ) => (
								<AccordionItem
									key={ i }
									question={ faq.question }
									answer={ faq.answer }
								/>
							) ) }
						</div>
						<div style={ { flex: 1, minWidth: 0 } }>
							{ right.map( ( faq, i ) => (
								<AccordionItem
									key={ i + half }
									question={ faq.question }
									answer={ faq.answer }
								/>
							) ) }
						</div>
					</div>
				</div>
			</div>

			<p style={ { margin: '0 0 24px', fontSize: '13px', color: '#444' } }>
				{ __( 'If your queries are not answered here, you can send an email directly to support@tychesoftwares.com for some additional requirements.', 'woocommerce-abandoned-cart' ) }
			</p>

			{ /* Support banner */ }
			<div style={ {
				background:   '#41278d',
				borderRadius: '10px',
				padding:      '24px 30px',
				display:      'flex',
				alignItems:   'center',
				justifyContent: 'space-between',
				gap:          '16px',
				flexWrap:     'wrap',
			} }>
				<div>
					<p style={ { margin: '0 0 4px', color: '#fff', fontWeight: '600', fontSize: '15px' } }>
						{ __( 'Still have questions?', 'woocommerce-abandoned-cart' ) }
					</p>
					<p style={ { margin: 0, color: 'rgba(255,255,255,.8)', fontSize: '13px' } }>
						{ __( 'Browse the documentation or reach out to our support team.', 'woocommerce-abandoned-cart' ) }
					</p>
				</div>
				<div style={ { display: 'flex', gap: '12px', flexWrap: 'wrap' } }>
					<a
						href={ DOCS_URL }
						target="_blank"
						rel="noreferrer"
						style={ {
							display:      'inline-block',
							padding:      '8px 18px',
							border:       '1px solid rgba(255,255,255,.6)',
							borderRadius: '4px',
							color:        '#fff',
							fontSize:     '13px',
							textDecoration: 'none',
						} }
					>
						{ __( 'Documentation', 'woocommerce-abandoned-cart' ) }
					</a>
					<a
						href={ SUPPORT_URL }
						target="_blank"
						rel="noreferrer"
						style={ {
							display:        'inline-block',
							padding:        '8px 18px',
							background:     '#fff',
							borderRadius:   '4px',
							color:          '#41278d',
							fontWeight:     '600',
							fontSize:       '13px',
							textDecoration: 'none',
						} }
					>
						{ __( 'Contact Support', 'woocommerce-abandoned-cart' ) }
					</a>
				</div>
			</div>
		</div>
	);
}
