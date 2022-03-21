/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { View } from '../../view';
import { contextConnect, WordPressComponentProps } from '../../ui/context';
import { useBorderBoxControlVisualizer } from './hook';

import type { VisualizerProps } from '../types';

const BorderBoxControlVisualizer = (
	props: WordPressComponentProps< VisualizerProps, 'div' >,
	forwardedRef: React.ForwardedRef< any >
) => {
	const visualizerProps = useBorderBoxControlVisualizer( props );

	return <View { ...visualizerProps } ref={ forwardedRef } />;
};

const ConnectedBorderBoxControlVisualizer = contextConnect(
	BorderBoxControlVisualizer,
	'BorderBoxControlVisualizer'
);
export default ConnectedBorderBoxControlVisualizer;
