/**
 * Kadence Column — Quote Area Extension
 *
 * Adds a "Quote Area" toggle to the Advanced inspector panel of
 * kadence/column. When enabled, a `pandora-quote` class is applied
 * which renders a decorative SVG quotation mark via ::before.
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorAdvancedControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';

import { NativeToggleControl } from '../../components';

import './style.scss';

const BLOCK_NAME = 'kadence/column';
const ATTRIBUTE  = 'quoteAreaEnabled';
const CLASS_NAME = 'pandora-quote';

/**
 * Register the `quoteAreaEnabled` attribute on kadence/column.
 */
addFilter(
    'blocks.registerBlockType',
    'pandora/kadence-quote-area-add-attribute',
    ( settings, name ) => {
        if ( name !== BLOCK_NAME ) {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                [ ATTRIBUTE ]: {
                    type: 'boolean',
                    default: false,
                },
            },
        };
    }
);

/**
 * Add "Quote Area" toggle to the Advanced inspector panel.
 */
addFilter(
    'editor.BlockEdit',
    'pandora/kadence-quote-area-add-inspector-controls',
    createHigherOrderComponent( BlockEdit => {
        return props => {
            const { name, attributes, setAttributes } = props;

            if ( name !== BLOCK_NAME ) {
                return <BlockEdit { ...props } />;
            }

            return (
                <>
                    <BlockEdit { ...props } />
                    <InspectorAdvancedControls>
                        <NativeToggleControl
                            label={ __( 'Act as Quote Area', 'pandora-group' ) }
                            checked={ !! attributes[ ATTRIBUTE ] }
                            onChange={ value => setAttributes( { [ ATTRIBUTE ]: value } ) }
                        />
                    </InspectorAdvancedControls>
                </>
            );
        };
    } )
);

/**
 * Apply the `pandora-quote` class in the editor preview.
 */
addFilter(
    'editor.BlockListBlock',
    'pandora/kadence-quote-area-add-styles',
    createHigherOrderComponent( BlockListBlock => {
        return props => {
            const { name, attributes } = props;

            if ( name !== BLOCK_NAME || ! attributes[ ATTRIBUTE ] ) {
                return <BlockListBlock { ...props } />;
            }

            const classes = [ props.className, CLASS_NAME ].filter( Boolean ).join( ' ' );

            return <BlockListBlock { ...props } className={ classes } />;
        };
    } )
);
