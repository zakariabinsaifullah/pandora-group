/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorAdvancedControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { NativeToggleControl } from '../../components';

import './style.scss';

const BLOCK_NAME = 'core/button';
const ATTRIBUTE = 'hasGradientBorder';
const CLASS_NAME = 'has-gradient-border';

addFilter('blocks.registerBlockType', 'pandora/button-gradient-border-add-attribute', (settings, name) => {
    if (name !== BLOCK_NAME) {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            [ATTRIBUTE]: {
                type: 'boolean',
                default: false
            }
        }
    };
});

addFilter(
    'editor.BlockEdit',
    'pandora/button-gradient-border-add-inspector-controls',
    createHigherOrderComponent(BlockEdit => {
        return props => {
            const { name, attributes, setAttributes } = props;

            if (name !== BLOCK_NAME) {
                return <BlockEdit {...props} />;
            }

            return (
                <>
                    <BlockEdit {...props} />
                    <InspectorAdvancedControls>
                        <NativeToggleControl
                            label={__('Has Gradient Border', 'pandora-group')}
                            checked={!!attributes[ATTRIBUTE]}
                            onChange={value => setAttributes({ [ATTRIBUTE]: value })}
                        />
                    </InspectorAdvancedControls>
                </>
            );
        };
    })
);

addFilter(
    'editor.BlockListBlock',
    'pandora/button-gradient-border-add-styles',
    createHigherOrderComponent(BlockListBlock => {
        return props => {
            const { name, attributes } = props;

            if (name !== BLOCK_NAME || !attributes[ATTRIBUTE]) {
                return <BlockListBlock {...props} />;
            }

            const classes = [props.className, CLASS_NAME].filter(Boolean).join(' ');

            return <BlockListBlock {...props} className={classes} />;
        };
    })
);
