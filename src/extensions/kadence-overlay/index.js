/**
 * Kadence RowLayout — Overlay Image Extension
 *
 * Adds a settings panel to kadence/rowlayout that lets editors upload a
 * decorative overlay image and control its left/top position. The image
 * is rendered via ::after so it sits independently of Kadence's own
 * ::before overlay layer.
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorAdvancedControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
    PanelBody,
    Button,
    __experimentalUnitControl as UnitControl,
    __experimentalHStack as HStack,
    BaseControl,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';

import './style.scss';

const BLOCK_NAME = 'kadence/rowlayout';

/**
 * Register overlay image attributes on kadence/rowlayout.
 */
addFilter(
    'blocks.registerBlockType',
    'pandora/kadence-overlay-add-attributes',
    ( settings, name ) => {
        if ( name !== BLOCK_NAME ) {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                kadenceOverlayImageUrl: { type: 'string', default: '' },
                kadenceOverlayImageId:  { type: 'number', default: 0 },
                kadenceOverlayLeft:     { type: 'string', default: '0px' },
                kadenceOverlayTop:      { type: 'string', default: '0px' },
            },
        };
    }
);

/**
 * Add "Overlay Image" panel to kadence/rowlayout inspector.
 */
addFilter(
    'editor.BlockEdit',
    'pandora/kadence-overlay-add-inspector-controls',
    createHigherOrderComponent( BlockEdit => {
        return props => {
            const { name, attributes, setAttributes } = props;

            if ( name !== BLOCK_NAME ) {
                return <BlockEdit { ...props } />;
            }

            const {
                kadenceOverlayImageUrl,
                kadenceOverlayImageId,
                kadenceOverlayLeft,
                kadenceOverlayTop,
            } = attributes;

            const unitOptions = [
                { value: 'px', label: 'px' },
                { value: '%',  label: '%'  },
                { value: 'em', label: 'em' },
            ];

            return (
                <>
                    <BlockEdit { ...props } />
                    <InspectorAdvancedControls>
                            <BaseControl
                                label={__('Additional Overlay Image', 'pandora-group')}
                            >
                                <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={ media =>
                                        setAttributes( {
                                            kadenceOverlayImageUrl: media.url,
                                            kadenceOverlayImageId:  media.id,
                                        } )
                                    }
                                    allowedTypes={ [ 'image' ] }
                                    value={ kadenceOverlayImageId }
                                    render={ ( { open } ) => (
                                        <div>
                                            { kadenceOverlayImageUrl ? (
                                                <>
                                                    <img
                                                        src={ kadenceOverlayImageUrl }
                                                        alt=""
                                                        style={ {
                                                            display: 'block',
                                                            width: '100%',
                                                            marginBottom: '8px',
                                                            borderRadius: '4px',
                                                        } }
                                                    />
                                                    <Button
                                                        variant="secondary"
                                                        onClick={ open }
                                                        style={ { width: '100%', marginBottom: '4px', justifyContent: 'center' } }
                                                    >
                                                        { __( 'Replace Image', 'pandora-group' ) }
                                                    </Button>
                                                    <Button
                                                        variant="tertiary"
                                                        isDestructive
                                                        onClick={ () =>
                                                            setAttributes( {
                                                                kadenceOverlayImageUrl: '',
                                                                kadenceOverlayImageId:  0,
                                                            } )
                                                        }
                                                        style={ { width: '100%', justifyContent: 'center' } }
                                                    >
                                                        { __( 'Remove Image', 'pandora-group' ) }
                                                    </Button>
                                                </>
                                            ) : (
                                                <Button
                                                    variant="secondary"
                                                    onClick={ open }
                                                    style={ { width: '100%', justifyContent: 'center' } }
                                                >
                                                    { __( 'Upload Image', 'pandora-group' ) }
                                                </Button>
                                            ) }
                                        </div>
                                    ) }
                                />
                                </MediaUploadCheck>

                                { kadenceOverlayImageUrl && (
                                    <HStack style={ { marginTop: '16px' } } spacing={ 3 }>
                                        <UnitControl
                                            label={ __( 'Left', 'pandora-group' ) }
                                            value={ kadenceOverlayLeft }
                                            units={ unitOptions }
                                            onChange={ value =>
                                                setAttributes( { kadenceOverlayLeft: value || '0px' } )
                                            }
                                        />
                                        <UnitControl
                                            label={ __( 'Top', 'pandora-group' ) }
                                            value={ kadenceOverlayTop }
                                            units={ unitOptions }
                                            onChange={ value =>
                                                setAttributes( { kadenceOverlayTop: value || '0px' } )
                                            }
                                        />
                                    </HStack>
                                ) }
                            </BaseControl>
                            
                    </InspectorAdvancedControls>
                </>
            );
        };
    } )
);

/**
 * Apply overlay class and CSS variables in the editor preview.
 */
addFilter(
    'editor.BlockListBlock',
    'pandora/kadence-overlay-add-styles',
    createHigherOrderComponent( BlockListBlock => {
        return props => {
            const { name, attributes } = props;

            if ( name !== BLOCK_NAME ) {
                return <BlockListBlock { ...props } />;
            }

            const { kadenceOverlayImageUrl, kadenceOverlayLeft, kadenceOverlayTop } = attributes;

            if ( ! kadenceOverlayImageUrl ) {
                return <BlockListBlock { ...props } />;
            }

            const wrapperProps = {
                ...props.wrapperProps,
                style: {
                    ...props.wrapperProps?.style,
                    '--pandora-koverlay-image': `url(${ kadenceOverlayImageUrl })`,
                    '--pandora-koverlay-left':  kadenceOverlayLeft || '0px',
                    '--pandora-koverlay-top':   kadenceOverlayTop  || '0px',
                },
            };

            const classes = [ props.className, 'has-kadence-overlay' ]
                .filter( Boolean )
                .join( ' ' );

            return <BlockListBlock { ...props } className={ classes } wrapperProps={ wrapperProps } />;
        };
    } )
);
