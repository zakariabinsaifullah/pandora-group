import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RawHTML } from '@wordpress/element';
import { NativeTextareaControl } from '../../components';

const INNER_TEMPLATE = [['core/paragraph']];

const Edit = props => {
    const { attributes, setAttributes } = props;
    const { customSvgCode } = attributes;

    const blockProps = useBlockProps();
    const innerBlockProps = useInnerBlocksProps({ className: 'timeline-content' }, { template: INNER_TEMPLATE, templateLock: false });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Icon', 'pandora-group')} initialOpen={true}>
                    <NativeTextareaControl
                        label={__('SVG Code', 'pandora-group')}
                        value={customSvgCode}
                        onChange={value => setAttributes({ customSvgCode: value })}
                        placeholder={__('Paste SVG code here...', 'pandora-group')}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <div className="timeline-icon-row">
                    <div className="timeline-icon">{customSvgCode && <RawHTML>{customSvgCode}</RawHTML>}</div>
                </div>
                <div className="timeline-card">
                    <div {...innerBlockProps} />
                </div>
            </div>
        </>
    );
};

export default Edit;
