import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { NativeRangeControl } from '../../components';

const Inspector = props => {
    const { attributes, setAttributes } = props;
    const { contentGap } = attributes;

    return (
        <InspectorControls>
            <PanelBody title={__('Timeline Alternating Settings', 'pandora-group')} initialOpen={true}>
                <NativeRangeControl
                    label={__('Gap (between items)', 'pandora-group')}
                    value={contentGap}
                    onChange={value => setAttributes({ contentGap: value })}
                    min={0}
                    max={150}
                    step={1}
                    resetFallbackValue={48}
                />
            </PanelBody>
        </InspectorControls>
    );
};

export default Inspector;
