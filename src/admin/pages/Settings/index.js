/**
 * Settings page — tab routing via URL param, based on the Pro plugin's
 * src/admin/pages/Settings/index.js.
 * URL: #/settings/general, #/settings/popup-templates, #/settings/sms, etc.
 */

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { TabPanel, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import { useNavigate, useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import GeneralSettings from './tabs/GeneralSettings';
import { UpgradeSection } from '../../components/UpgradeSection';

// ── Tab definitions ────────────────────────────────────────────────────────────

const TABS = [
	{ name: 'general', title: __( 'General', 'woocommerce-abandoned-cart' ) },
	{ name: 'popup-templates', title: __( 'Popup Templates', 'woocommerce-abandoned-cart' ) },
	{ name: 'sms', title: __( 'SMS', 'woocommerce-abandoned-cart' ) },
	{ name: 'whatsapp', title: __( 'WhatsApp', 'woocommerce-abandoned-cart' ) },
	{ name: 'connectors', title: __( 'Connectors', 'woocommerce-abandoned-cart' ) },
	{ name: 'email-report', title: __( 'Email Report', 'woocommerce-abandoned-cart' ) },
	{ name: 'import-export', title: __( 'Import/Export', 'woocommerce-abandoned-cart' ) },
];

// Pro-only tabs, by the section template each one shows.
const PRO_SECTIONS = {
	'popup-templates': 'wcap_atc_settings',
	sms: 'wcap_sms_settings',
	whatsapp: 'wcap_whatsapp_settings',
	connectors: 'wcap_connectors',
	'email-report': 'wcap_email_report_settings',
	'import-export': 'wcap_import_export_settings',
};

const VALID_TABS = TABS.map( ( t ) => t.name );

export function Settings() {
	const navigate = useNavigate();
	const location = useLocation();
	const { canSave } = window.wcalAdminData;

	const [ notice, setNotice ] = useState( null );

	// Scroll to top when a new notice appears.
	useEffect( () => {
		if ( ! notice ) {
			return;
		}
		const el = document.querySelector( '#wpbody-content' ) || document.documentElement;
		el.scrollTo( { top: 0, behavior: 'smooth' } );
		window.scrollTo( { top: 0, behavior: 'smooth' } );
	}, [ notice ] );

	const pathTab = location.pathname.replace( /^\/settings\/?/, '' ).split( '/' )[ 0 ];
	const activeTab = VALID_TABS.includes( pathTab ) ? pathTab : TABS[ 0 ].name;

	function handleTabSelect( selected ) {
		setNotice( null );
		navigate( `/settings/${ selected }`, { replace: true } );
	}

	return (
		<div className="wcap-page wcap-page--settings">
			<TabPanel
				key={ activeTab }
				tabs={ TABS }
				initialTabName={ activeTab }
				onSelect={ handleTabSelect }
			>
				{ ( t ) => {
					if ( PRO_SECTIONS[ t.name ] ) {
						return <UpgradeSection section={ PRO_SECTIONS[ t.name ] } />;
					}
					return (
						<>
							{ ! canSave && (
								<Notice status="warning" isDismissible={ false } style={ { margin: '16px 20px' } }>
									{ __( 'You can view these settings, but only users who can manage the site options can change them.', 'woocommerce-abandoned-cart' ) }
								</Notice>
							) }
							{ notice && (
								<Notice
									status={ notice.type }
									isDismissible
									onRemove={ () => setNotice( null ) }
									style={ { margin: '16px 20px' } }
								>
									{ notice.message }
								</Notice>
							) }
							<GeneralSettings onNotice={ setNotice } />
						</>
					);
				} }
			</TabPanel>
		</div>
	);
}
