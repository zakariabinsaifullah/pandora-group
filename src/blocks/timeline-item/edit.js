import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

const INNER_TEMPLATE = [['core/paragraph']];

const Edit = () => {
    const blockProps = useBlockProps();
    const innerBlockProps = useInnerBlocksProps({ className: 'timeline-content' }, { template: INNER_TEMPLATE, templateLock: false });

    return (
        <div {...blockProps}>
            <div className="timeline-icon-row">
                <div className="timeline-icon" />
            </div>
            <div className="timeline-card">
                <div {...innerBlockProps} />
            </div>
        </div>
    );
};

export default Edit;
