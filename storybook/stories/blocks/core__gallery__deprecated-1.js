/**
 * Internal dependencies
 */
import core__gallery__deprecated_1 from '../../../test/integration/fixtures/blocks/core__gallery__deprecated-1.serialized.html';

export default {
	title: 'Blocks/core__gallery__deprecated_1',
};

export const _default = () => {
	return (
		<div
			dangerouslySetInnerHTML={ { __html: core__gallery__deprecated_1 } }
		></div>
	);
};
