import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import classNames from 'classnames';

const Save = ({ attributes }) => {
    const { label, customSvgCode, isLast, isEven } = attributes;
    const blockProps = useBlockProps.save({
        className: classNames({ 'is-last-item': isLast, 'is-even-item': isEven })
    });

    return (
        <div {...blockProps}>
            {label && <div className="timeline-alt-label">{label}</div>}
            <div className="timeline-alt-center">
                <div className="timeline-alt-icon" {...(customSvgCode && { dangerouslySetInnerHTML: { __html: customSvgCode } })} />
                <span className="timeline-alt-line" aria-hidden="true" />
            </div>
            <div className="timeline-alt-content">
                <InnerBlocks.Content />
            </div>
        </div>
    );
};

export default Save;
