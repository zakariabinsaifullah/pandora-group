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

const ALLOWED_BLOCKS = ['kadence/iconlist'];
const ATTRIBUTE = 'changeIconPosition';
const CLASS_NAME = 'pandora-icon-pos';

/**
 * Add `changeIconPosition` attribute to kadence/iconlist.
 */
addFilter('blocks.registerBlockType', 'pandora/iconlist-icon-position-add-attribute', (settings, name) => {
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
 * Add "Change icon position" toggle to the block's Advanced inspector panel.
 */
addFilter(
    'editor.BlockEdit',
    'pandora/iconlist-icon-position-add-inspector-controls',
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
                            label={__('Change icon position', 'pandora-group')}
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
 * Apply the `pandora-icon-pos` class in the editor preview.
 */
addFilter(
    'editor.BlockListBlock',
    'pandora/iconlist-icon-position-add-styles',
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
