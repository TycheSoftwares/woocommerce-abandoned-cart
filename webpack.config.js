const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Remove SASS rule from the default config so we can define our own.
const defaultRules = defaultConfig.module.rules.filter((rule) => {
	return String(rule.test) !== String(/\.(sc|sa)ss$/);
});

// Same SASS rule as the Pro plugin's admin build.
const sassRule = {
	test: /\.(sc|sa)ss$/,
	use: [
		MiniCssExtractPlugin.loader,
		'css-loader',
		{
			loader: 'sass-loader',
			options: { sourceMap: true },
		},
	],
};

module.exports = {
	...defaultConfig,
	module: {
		...defaultConfig.module,
		rules: [ ...defaultRules, sassRule ],
	},
	entry: {
		'admin/admin': path.resolve( process.cwd(), 'src', 'admin', 'index.js' ),
		index: path.resolve( process.cwd(), 'src', 'index.js' ),
		'wcal-blocks-guest-capture':
			path.resolve(
				process.cwd(),
				'src',
				'wcal-blocks-guest-capture.js'
			),
		'wcal-blocks-gdpr-frontend':
			path.resolve(
				process.cwd(),
				'src',
				'wcal-blocks-gdpr-email-compliance',
				'frontend.js'
			),
	},
	output: {
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
		new MiniCssExtractPlugin({
			filename: `[name].css`,
		}),
	],
};