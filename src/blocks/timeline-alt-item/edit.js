import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RawHTML } from '@wordpress/element';
import classNames from 'classnames';
import { NativeTextareaControl } from '../../components';

const INNER_TEMPLATE = [['core/paragraph']];

const Edit = props => {
    const { attributes, setAttributes } = props;
    const { label, customSvgCode, isLast, isEven } = attributes;

    const blockProps = useBlockProps({
        className: classNames({ 'is-last-item': isLast, 'is-even-item': isEven })
    });
    const innerBlockProps = useInnerBlocksProps({ className: 'timeline-alt-content' }, { template: INNER_TEMPLATE, templateLock: false });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Icon', 'pandora-group')} initialOpen={true}>
                    <NativeTextareaControl
                        label={__('Label', 'pandora-group')}
                        value={label}
                        onChange={value => setAttributes({ label: value })}
                        placeholder={__('e.g. Assessment', 'pandora-group')}
                        help={__('Press Enter for a manual line break.', 'pandora-group')}
                    />
                    <NativeTextareaControl
                        label={__('SVG Code', 'pandora-group')}
                        value={customSvgCode}
                        onChange={value => setAttributes({ customSvgCode: value })}
                        placeholder={__('Paste SVG code here...', 'pandora-group')}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {label && <div className="timeline-alt-label">{label}</div>}
                <div className="timeline-alt-center">
                    <div className="timeline-alt-icon">{customSvgCode && <RawHTML>{customSvgCode}</RawHTML>}</div>
                    <span className="timeline-alt-line" aria-hidden="true" />
                </div>
                <div {...innerBlockProps} />
            </div>
        </>
    );
};

export default Edit;
