/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorAdvancedControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { NativeToggleControl } from '../../components';

const ALLOWED_BLOCKS = ['core/group', 'kadence/rowlayout', 'kadence/column'];
const ATTRIBUTE = 'glassEffectEnabled';
const CLASS_NAME = 'has-glass-effect';

/**
 * Add `glassEffectEnabled` attribute to core/group and kadence/rowlayout.
 */
addFilter('blocks.registerBlockType', 'pandora/glass-effect-add-attribute', (settings, name) => {
    if (!ALLOWED_BLOCKS.includes(name)) {
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

/**
 * Add "Add Glass Effect" toggle to the block's Advanced inspector panel.
 */
addFilter(
    'editor.BlockEdit',
    'pandora/glass-effect-add-inspector-controls',
    createHigherOrderComponent(BlockEdit => {
        return props => {
            const { name, attributes, setAttributes } = props;

            if (!ALLOWED_BLOCKS.includes(name)) {
                return <BlockEdit {...props} />;
            }

            return (
                <>
                    <BlockEdit {...props} />
                    <InspectorAdvancedControls>
                        <NativeToggleControl
                            label={__('Add Glass Effect', 'pandora-group')}
                            checked={!!attributes[ATTRIBUTE]}
                            onChange={value => setAttributes({ [ATTRIBUTE]: value })}
                        />
                    </InspectorAdvancedControls>
                </>
            );
        };
    })
);

/**
 * Apply the `has-glass-effect` class in the editor preview.
 */
addFilter(
    'editor.BlockListBlock',
    'pandora/glass-effect-add-styles',
    createHigherOrderComponent(BlockListBlock => {
        return props => {
            const { name, attributes } = props;

            if (!ALLOWED_BLOCKS.includes(name) || !attributes[ATTRIBUTE]) {
                return <BlockListBlock {...props} />;
            }

            const classes = [props.className, CLASS_NAME].filter(Boolean).join(' ');

            return <BlockListBlock {...props} className={classes} />;
        };
    })
);
