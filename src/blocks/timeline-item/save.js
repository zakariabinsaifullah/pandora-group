import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

const Save = () => {
    const blockProps = useBlockProps.save();

    return (
        <div {...blockProps}>
            <div className="timeline-icon-row">
                <div className="timeline-icon" />
            </div>
            <div className="timeline-card">
                <div className="timeline-content">
                    <InnerBlocks.Content />
                </div>
            </div>
        </div>
    );
};

export default Save;
