import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import classNames from 'classnames';

const Save = props => {
    const { attributes } = props;
    const { uniqueId, contentGap } = attributes;

    const blockProps = useBlockProps.save({
        className: classNames(uniqueId),
        style: {
            ...(contentGap && { '--item-gap': `${contentGap}px` })
        }
    });

    return (
        <div {...blockProps}>
            <div className="pandora-timeline">
                <InnerBlocks.Content />
            </div>
        </div>
    );
};

export default Save;
