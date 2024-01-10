/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
/**
 * Internal dependencies
 */
import Block from './block';
import metadata from './block.json';

registerCheckoutBlock({
	metadata,
	component: Block,
});
