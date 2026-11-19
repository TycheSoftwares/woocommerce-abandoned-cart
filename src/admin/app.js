/**
 * Root application component, based on the Pro plugin's src/admin/app.js.
 *
 * Defines all client-side routes. The HashRouter wrapping this component
 * lives in index.js, so all navigation uses hash URLs:
 *   #/dashboard, #/settings/general, #/reminders/email, etc.
 */

/**
 * External dependencies
 */
import { Routes, Route, Navigate } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { Header } from './components/Header';
import { Footer } from './components/Footer';
import { Dashboard } from './pages/Dashboard';
import { AbandonedOrders } from './pages/AbandonedOrders';
import { EmailTemplate } from './pages/Templates';
import { Reminders } from './pages/Reminders';
import { ProductReports } from './pages/ProductReports';
import { FaqSupport } from './pages/FaqSupport';
import { Settings } from './pages/Settings';
import { UpgradeToPro } from './pages/UpgradeToPro';

const App = () => (
	<div className="wcap-admin-app">
		<Header />
		<main className="wcap-page-content">
			<Routes>
				<Route path="/" element={ <Navigate to="/dashboard" replace /> } />
				<Route path="/dashboard" element={ <Dashboard /> } />
				<Route path="/abandoned-orders" element={ <AbandonedOrders /> } />
				<Route path="/email-template/*" element={ <EmailTemplate /> } />
				<Route path="/reminders/:tab?" element={ <Reminders /> } />
				<Route path="/product-reports" element={ <ProductReports /> } />
				<Route path="/faq-support" element={ <FaqSupport /> } />
				<Route path="/settings/*" element={ <Settings /> } />
				<Route path="/upgrade-to-pro" element={ <UpgradeToPro /> } />
			</Routes>
		</main>
		<Footer />
	</div>
);

export default App;
