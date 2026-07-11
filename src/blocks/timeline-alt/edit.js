import { useBlockProps, useInnerBlocksProps, BlockControls } from '@wordpress/block-editor';
import { Fragment, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import classNames from 'classnames';
import Inspector from './inspector';

const TEMPLATE = [
    ['pandora/timeline-alt-item', {}],
    ['pandora/timeline-alt-item', {}],
    ['pandora/timeline-alt-item', {}]
];

const Edit = props => {
    const { attributes, clientId, isSelected } = props;
    const hasSelectedInnerBlock = useSelect(select => select('core/block-editor').hasSelectedInnerBlock(clientId, true));
    const { uniqueId, contentGap } = attributes;

    // The block editor wraps every inner block in its own list-item
    // container, which breaks CSS :last-child/:nth-child on the item's
    // own root element (it always looks like an only child there). So
    // position is tracked here instead and pushed down to each item as
    // real attributes, which both the editor and the saved front-end
    // markup can key off of reliably.
    const childOrder = useSelect(select => select('core/block-editor').getBlockOrder(clientId), [clientId]);
    const { updateBlockAttributes } = useDispatch('core/block-editor');

    useEffect(() => {
        if (!childOrder || childOrder.length === 0) return;

        childOrder.forEach((childClientId, index) => {
            const block = wp.data.select('core/block-editor').getBlock(childClientId);
            if (!block) return;

            const isLast = index === childOrder.length - 1;
            const isEven = index % 2 === 1;

            if (block.attributes.isLast !== isLast || block.attributes.isEven !== isEven) {
                updateBlockAttributes(childClientId, { isLast, isEven });
            }
        });
    }, [childOrder, updateBlockAttributes]);

    const cssCustomProperties = {
        ...(contentGap && { '--item-gap': `${contentGap}px` })
    };

    const blockProps = useBlockProps({
        className: classNames(uniqueId),
        style: cssCustomProperties
    });

    const innerBlockProps = useInnerBlocksProps(
        { className: 'pandora-timeline-alt' },
        {
            allowedBlocks: ['pandora/timeline-alt-item'],
            template: TEMPLATE,
            templateLock: false,
            renderAppender: false
        }
    );

    const addItem = () => {
        const childBlocks = wp.data.select('core/block-editor').getBlocks(clientId);
        const newBlock = wp.blocks.createBlock('pandora/timeline-alt-item', {});
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
