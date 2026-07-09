import { useBlockProps, useInnerBlocksProps, BlockControls } from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import classNames from 'classnames';
import Inspector from './inspector';

const TEMPLATE = [
    ['pandora/timeline-item', {}],
    ['pandora/timeline-item', {}],
    ['pandora/timeline-item', {}]
];

const Edit = props => {
    const { attributes, clientId, isSelected } = props;
    const hasSelectedInnerBlock = useSelect(select => select('core/block-editor').hasSelectedInnerBlock(clientId, true));
    const { uniqueId, contentGap } = attributes;

    const cssCustomProperties = {
        ...(contentGap && { '--item-gap': `${contentGap}px` })
    };

    const blockProps = useBlockProps({
        className: classNames(uniqueId),
        style: cssCustomProperties
    });

    const innerBlockProps = useInnerBlocksProps(
        { className: 'pandora-timeline' },
        {
            allowedBlocks: ['pandora/timeline-item'],
            template: TEMPLATE,
            templateLock: false,
            renderAppender: false
        }
    );

    const addItem = () => {
        const childBlocks = wp.data.select('core/block-editor').getBlocks(clientId);
        const newBlock = wp.blocks.createBlock('pandora/timeline-item', {});
        wp.data.dispatch('core/block-editor').insertBlocks(newBlock, childBlocks.length, clientId);
    };

    return (
        <Fragment>
            {(isSelected || hasSelectedInnerBlock) && <Inspector {...props} />}
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton icon={plus} label={__('Add Timeline Item', 'pandora-group')} onClick={addItem} />
                </ToolbarGroup>
            </BlockControls>
            <div {...blockProps}>
                <div {...innerBlockProps} />
            </div>
        </Fragment>
    );
};

export default Edit;
