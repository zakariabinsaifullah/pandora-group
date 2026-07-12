<?php
/**
 * Block Editor Extensions
 *
 * Enqueues assets for block editor extensions that live in build/extensions/.
 * Extensions are different from custom blocks — they extend existing blocks
 * rather than registering new block types.
 *
 * @package Pandora_Group
 */

if ( ! function_exists( 'pandora_enqueue_hover_color_editor_assets' ) ) :
	/**
	 * Enqueues the hover-color extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_hover_color_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/hover-color/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-hover-color-extension',
			get_theme_file_uri( 'build/extensions/hover-color/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/hover-color/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-hover-color-extension',
				get_theme_file_uri( 'build/extensions/hover-color/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_hover_color_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_hover_color_frontend_assets' ) ) :
	/**
	 * Enqueues the hover-color extension frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_hover_color_frontend_assets() {
		$asset_file  = get_theme_file_path( 'build/extensions/hover-color/index.asset.php' );
		$style_file  = get_theme_file_path( 'build/extensions/hover-color/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-hover-color-extension-style',
			get_theme_file_uri( 'build/extensions/hover-color/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_hover_color_frontend_assets' );


if ( ! function_exists( 'pandora_render_hover_color_attributes' ) ) :
	/**
	 * Injects hover-color CSS classes and custom properties into block HTML on the frontend.
	 *
	 * The editor applies these via the JS `editor.BlockListBlock` filter, which has no effect
	 * on saved frontend markup. This PHP `render_block` filter replicates that logic so the
	 * CSS variables and classes are present in the rendered HTML.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_hover_color_attributes( $block_content, $block ) {
		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$attrs = $block['attrs'] ?? array();

		// Colors use the WordPress preset-slug + custom-value pair (withColors model).
		$hover_text_color             = $attrs['hoverTextColor'] ?? '';
		$custom_hover_text_color      = $attrs['customHoverTextColor'] ?? '';
		$hover_background_color       = $attrs['hoverBackgroundColor'] ?? '';
		$custom_hover_background_color = $attrs['customHoverBackgroundColor'] ?? '';
		$hover_border_color           = $attrs['hoverBorderColor'] ?? '';
		$custom_hover_border_color    = $attrs['customHoverBorderColor'] ?? '';
		$hover_transition_duration    = $attrs['hoverTransitionDuration'] ?? 200;
		$hover_transition_timing      = $attrs['hoverTransitionTiming'] ?? 'cubic-bezier(0.4, 0, 0.2, 1)';

		$has_hover_text   = $hover_text_color || $custom_hover_text_color;
		$has_hover_bg     = $hover_background_color || $custom_hover_background_color;
		$has_hover_border = $hover_border_color || $custom_hover_border_color;

		if ( ! $has_hover_text && ! $has_hover_bg && ! $has_hover_border ) {
			return $block_content;
		}

		// Resolve a preset slug to its CSS var, else fall back to the custom value.
		$get_color_value = function ( $preset, $custom ) {
			if ( $preset ) {
				return 'var(--wp--preset--color--' . $preset . ')';
			}
			return $custom ?: '';
		};

		// Build CSS custom properties string.
		$css_vars = array();
		if ( $has_hover_text ) {
			$css_vars[] = '--hover-color:' . $get_color_value( $hover_text_color, $custom_hover_text_color );
		}
		if ( $has_hover_bg ) {
			$css_vars[] = '--hover-background-color:' . $get_color_value( $hover_background_color, $custom_hover_background_color );
		}
		if ( $has_hover_border ) {
			$css_vars[] = '--hover-br-color:' . $get_color_value( $hover_border_color, $custom_hover_border_color );
		}
		$css_vars[] = '--hover-transition-duration:' . intval( $hover_transition_duration ) . 'ms';
		$css_vars[] = '--hover-transition-timing:' . $hover_transition_timing;

		// Build class list (matches selectors in hover-color/style.scss).
		$new_classes = array();
		if ( $has_hover_text ) {
			$new_classes[] = 'has-hover__color';
		}
		if ( $has_hover_bg ) {
			$new_classes[] = 'has-hover__background-color';
		}
		if ( $has_hover_border ) {
			$new_classes[] = 'has-hover__border-color';
		}

		// Use WP_HTML_Tag_Processor (WP 6.2+) for safe attribute manipulation.
		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			// Append classes.
			foreach ( $new_classes as $class ) {
				$processor->add_class( $class );
			}

			// Merge CSS variables into the existing style attribute.
			$existing_style = $processor->get_attribute( 'style' ) ?? '';
			$new_style      = rtrim( $existing_style, '; ' );
			if ( $new_style ) {
				$new_style .= ';';
			}
			$new_style .= implode( ';', $css_vars );
			$processor->set_attribute( 'style', $new_style );

			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_hover_color_attributes', 10, 2 );


// =============================================================================
// Group – Force Full Height Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_group_full_height_editor_assets' ) ) :
	/**
	 * Enqueues the group-full-height extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_group_full_height_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-full-height/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-group-full-height-extension',
			get_theme_file_uri( 'build/extensions/group-full-height/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-full-height/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-full-height-extension',
				get_theme_file_uri( 'build/extensions/group-full-height/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_group_full_height_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_group_full_height_frontend_assets' ) ) :
	/**
	 * Enqueues the group-full-height frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_group_full_height_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-full-height/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/group-full-height/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-group-full-height-extension-style',
			get_theme_file_uri( 'build/extensions/group-full-height/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_group_full_height_frontend_assets' );


if ( ! function_exists( 'pandora_render_group_full_height' ) ) :
	/**
	 * Injects the `has-force-full-height` class into core/group blocks on the frontend
	 * when the `forceFullHeight` attribute is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_group_full_height( $block_content, $block ) {
		if ( 'core/group' !== $block['blockName'] ) {
			return $block_content;
		}

		if ( empty( $block['attrs']['forceFullHeight'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-force-full-height' );
			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_group_full_height', 10, 2 );


// =============================================================================
// Group – Overlay Background Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_group_overlay_bg_editor_assets' ) ) :
	/**
	 * Enqueues the group-overlay-bg extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_group_overlay_bg_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-overlay-bg/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-group-overlay-bg-extension',
			get_theme_file_uri( 'build/extensions/group-overlay-bg/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-overlay-bg/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-overlay-bg-extension',
				get_theme_file_uri( 'build/extensions/group-overlay-bg/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_group_overlay_bg_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_group_overlay_bg_frontend_assets' ) ) :
	/**
	 * Enqueues the group-overlay-bg frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_group_overlay_bg_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-overlay-bg/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/group-overlay-bg/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-group-overlay-bg-extension-style',
			get_theme_file_uri( 'build/extensions/group-overlay-bg/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_group_overlay_bg_frontend_assets' );


if ( ! function_exists( 'pandora_render_group_overlay_bg' ) ) :
	/**
	 * Injects the `pandora-overlay-bg` class and `--pandora-overlay-bg` CSS custom property
	 * into core/group blocks on the frontend when an overlay background is set.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_group_overlay_bg( $block_content, $block ) {
		if ( 'core/group' !== $block['blockName'] ) {
			return $block_content;
		}

		$attrs                   = $block['attrs'] ?? array();
		$overlay_bg_color        = $attrs['overlayBgColor'] ?? '';
		$custom_overlay_bg_color = $attrs['customOverlayBgColor'] ?? '';
		$overlay_bg_gradient     = $attrs['overlayBgGradient'] ?? '';

		if ( ! $overlay_bg_color && ! $custom_overlay_bg_color && ! $overlay_bg_gradient ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		// Gradient wins; otherwise a preset slug resolves to its CSS var, else the custom value.
		if ( $overlay_bg_gradient ) {
			$bg_value = $overlay_bg_gradient;
		} elseif ( $overlay_bg_color ) {
			$bg_value = 'var(--wp--preset--color--' . $overlay_bg_color . ')';
		} else {
			$bg_value = $custom_overlay_bg_color;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'pandora-overlay-bg' );

			$existing_style = $processor->get_attribute( 'style' ) ?? '';
			$new_style      = rtrim( $existing_style, '; ' );
			if ( $new_style ) {
				$new_style .= ';';
			}
			$new_style .= '--pandora-overlay-bg:' . $bg_value;
			$processor->set_attribute( 'style', $new_style );

			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_group_overlay_bg', 10, 2 );


// =============================================================================
// Group – Global Hover Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_group_global_hover_editor_assets' ) ) :
	/**
	 * Enqueues the group-global-hover extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_group_global_hover_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-global-hover/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-group-global-hover-extension',
			get_theme_file_uri( 'build/extensions/group-global-hover/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-global-hover/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-global-hover-extension',
				get_theme_file_uri( 'build/extensions/group-global-hover/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_group_global_hover_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_group_global_hover_frontend_assets' ) ) :
	/**
	 * Enqueues the group-global-hover frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_group_global_hover_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-global-hover/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/group-global-hover/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-group-global-hover-extension-style',
			get_theme_file_uri( 'build/extensions/group-global-hover/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_group_global_hover_frontend_assets' );


if ( ! function_exists( 'pandora_render_group_global_hover' ) ) :
	/**
	 * Injects `pandora-global-hover` class + CSS variables into core/group blocks on the
	 * frontend when the global hover feature is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_group_global_hover( $block_content, $block ) {
		if ( 'core/group' !== $block['blockName'] ) {
			return $block_content;
		}

		$attrs = $block['attrs'] ?? array();

		if ( empty( $attrs['globalHoverEnabled'] ) ) {
			return $block_content;
		}

		// Colors use the WordPress preset-slug + custom-value pair (withColors model).
		$bg_color        = $attrs['globalHoverBgColor'] ?? '';
		$custom_bg_color = $attrs['customGlobalHoverBgColor'] ?? '';
		$color           = $attrs['globalHoverColor'] ?? '';
		$custom_color    = $attrs['customGlobalHoverColor'] ?? '';

		$has_bg    = $bg_color || $custom_bg_color;
		$has_color = $color || $custom_color;

		if ( ! $has_bg && ! $has_color ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$css_vars = array();

		if ( $has_bg ) {
			$css_vars[] = '--pandora-ghover-bg:' . ( $bg_color ? 'var(--wp--preset--color--' . $bg_color . ')' : $custom_bg_color );
		}

		if ( $has_color ) {
			$css_vars[] = '--pandora-ghover-color:' . ( $color ? 'var(--wp--preset--color--' . $color . ')' : $custom_color );
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'pandora-global-hover' );

			$existing_style = $processor->get_attribute( 'style' ) ?? '';
			$new_style      = rtrim( $existing_style, '; ' );
			if ( $new_style ) {
				$new_style .= ';';
			}
			$new_style .= implode( ';', $css_vars );
			$processor->set_attribute( 'style', $new_style );

			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_group_global_hover', 10, 2 );

// =============================================================================
// Heading & Paragraph – Max Width Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_text_max_width_editor_assets' ) ) :
	/**
	 * Enqueues the text-max-width extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_text_max_width_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/text-max-width/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-text-max-width-extension',
			get_theme_file_uri( 'build/extensions/text-max-width/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/text-max-width/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-text-max-width-extension',
				get_theme_file_uri( 'build/extensions/text-max-width/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_text_max_width_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_text_max_width_frontend_assets' ) ) :
	/**
	 * Enqueues the text-max-width frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_text_max_width_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/text-max-width/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/text-max-width/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-text-max-width-extension-style',
			get_theme_file_uri( 'build/extensions/text-max-width/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_text_max_width_frontend_assets' );


if ( ! function_exists( 'pandora_render_text_max_width' ) ) :
	/**
	 * Injects the `has-max-width` class and `--max-width` CSS custom property
	 * into supported blocks on the frontend when the maxWidth attribute is set.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_text_max_width( $block_content, $block ) {
		$supported = array( 'core/heading', 'core/paragraph' );

		if ( ! in_array( $block['blockName'], $supported, true ) ) {
			return $block_content;
		}

		$attrs = $block['attrs'] ?? array();

		if ( empty( $attrs['maxWidth'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-max-width' );

			$existing_style = $processor->get_attribute( 'style' ) ?? '';
			$new_style      = rtrim( $existing_style, '; ' );
			if ( $new_style ) {
				$new_style .= ';';
			}
			$new_style .= '--max-width:' . intval( $attrs['maxWidth'] ) . 'px';
			$processor->set_attribute( 'style', $new_style );

			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_text_max_width', 10, 2 );


// =============================================================================
// Button – Full Width Mobile Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_button_full_width_mobile_editor_assets' ) ) :
	/**
	 * Enqueues the button-full-width-mobile extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_button_full_width_mobile_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/button-full-width-mobile/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-button-full-width-mobile-extension',
			get_theme_file_uri( 'build/extensions/button-full-width-mobile/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/button-full-width-mobile/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-button-full-width-mobile-extension',
				get_theme_file_uri( 'build/extensions/button-full-width-mobile/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_button_full_width_mobile_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_button_full_width_mobile_frontend_assets' ) ) :
	/**
	 * Enqueues the button-full-width-mobile frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_button_full_width_mobile_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/button-full-width-mobile/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/button-full-width-mobile/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-button-full-width-mobile-extension-style',
			get_theme_file_uri( 'build/extensions/button-full-width-mobile/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_button_full_width_mobile_frontend_assets' );


if ( ! function_exists( 'pandora_render_button_full_width_mobile' ) ) :
	/**
	 * Injects the `has-full-width-mobile` class into core/button blocks on the frontend
	 * when the `fullWidthMobile` attribute is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_button_full_width_mobile( $block_content, $block ) {
		if ( 'core/button' !== $block['blockName'] ) {
			return $block_content;
		}

		if ( empty( $block['attrs']['fullWidthMobile'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-full-width-mobile' );
			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_button_full_width_mobile', 10, 2 );


// =============================================================================
// Button – Gradient Border Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_button_gradient_border_editor_assets' ) ) :
	/**
	 * Enqueues the button-gradient-border extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_button_gradient_border_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/button-gradient-border/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-button-gradient-border-extension',
			get_theme_file_uri( 'build/extensions/button-gradient-border/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/button-gradient-border/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-button-gradient-border-extension',
				get_theme_file_uri( 'build/extensions/button-gradient-border/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_button_gradient_border_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_button_gradient_border_frontend_assets' ) ) :
	/**
	 * Enqueues the button-gradient-border frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_button_gradient_border_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/button-gradient-border/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/button-gradient-border/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-button-gradient-border-extension-style',
			get_theme_file_uri( 'build/extensions/button-gradient-border/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_button_gradient_border_frontend_assets' );


if ( ! function_exists( 'pandora_render_button_gradient_border' ) ) :
	/**
	 * Injects the `has-gradient-border` class into core/button blocks on the frontend
	 * when the `hasGradientBorder` attribute is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_button_gradient_border( $block_content, $block ) {
		if ( 'core/button' !== $block['blockName'] ) {
			return $block_content;
		}

		if ( empty( $block['attrs']['hasGradientBorder'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-gradient-border' );
			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_button_gradient_border', 10, 2 );

// =============================================================================
// Group – Responsive Grid Columns Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_group_grid_columns_editor_assets' ) ) :
	/**
	 * Enqueues the group-grid-columns extension script and editor stylesheet.
	 */
	function pandora_enqueue_group_grid_columns_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-grid-columns/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-group-grid-columns-extension',
			get_theme_file_uri( 'build/extensions/group-grid-columns/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-grid-columns/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-grid-columns-extension',
				get_theme_file_uri( 'build/extensions/group-grid-columns/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_group_grid_columns_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_group_grid_columns_frontend_assets' ) ) :
	/**
	 * Enqueues the group-grid-columns frontend stylesheet.
	 */
	function pandora_enqueue_group_grid_columns_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/group-grid-columns/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/group-grid-columns/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-group-grid-columns-extension-style',
			get_theme_file_uri( 'build/extensions/group-grid-columns/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_group_grid_columns_frontend_assets' );


if ( ! function_exists( 'pandora_render_group_grid_columns' ) ) :
	/**
	 * Injects responsive grid column CSS custom properties and classes into
	 * core/group blocks on the frontend when grid layout with responsive
	 * columns is configured.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_group_grid_columns( $block_content, $block ) {
		if ( 'core/group' !== $block['blockName'] ) {
			return $block_content;
		}

		$attrs = $block['attrs'] ?? array();

		$layout_type = $attrs['layout']['type'] ?? '';

		if ( 'grid' !== $layout_type ) {
			return $block_content;
		}

		$desktop_columns  = $attrs['layout']['columnCount'] ?? 2;
		$tablet_columns   = $attrs['gridColumnsTablet'] ?? null;
		$mobile_columns   = $attrs['gridColumnsMobile'] ?? null;

		$has_tablet = ! empty( $tablet_columns );
		$has_mobile = ! empty( $mobile_columns );

		if ( ! $has_tablet && ! $has_mobile ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$css_vars = array();

		$css_vars[] = '--grid-columns-tablet:' . intval( $has_tablet ? $tablet_columns : $desktop_columns );
		$css_vars[] = '--grid-columns-mobile:' . intval( $has_mobile ? $mobile_columns : ( $has_tablet ? $tablet_columns : 1 ) );

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-responsive-grid-columns' );

			$existing_style = $processor->get_attribute( 'style' ) ?? '';
			$new_style      = rtrim( $existing_style, '; ' );
			if ( $new_style ) {
				$new_style .= ';';
			}
			$new_style .= implode( ';', $css_vars );
			$processor->set_attribute( 'style', $new_style );

			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_group_grid_columns', 10, 2 );


// =============================================================================
// Button – Iconic Button Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_iconic_button_editor_assets' ) ) :
	/**
	 * Enqueues the iconic-button extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_iconic_button_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/iconic-button/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-iconic-button-extension',
			get_theme_file_uri( 'build/extensions/iconic-button/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/iconic-button/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-iconic-button-extension',
				get_theme_file_uri( 'build/extensions/iconic-button/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_iconic_button_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_iconic_button_frontend_assets' ) ) :
	/**
	 * Enqueues the iconic-button extension frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_iconic_button_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/iconic-button/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/iconic-button/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-iconic-button-extension-style',
			get_theme_file_uri( 'build/extensions/iconic-button/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_iconic_button_frontend_assets' );


if ( ! function_exists( 'pandora_iconic_button_svg_kses_args' ) ) :
	/**
	 * Allowed SVG tags/attributes for icons rendered inside the iconic-button markup.
	 *
	 * @return array Kses args for wp_kses().
	 */
	function pandora_iconic_button_svg_kses_args() {
		// Presentation attributes shared by most SVG shape elements — stroke-based
		// icon sets (Feather, Lucide, Heroicons outline, Tabler, etc.) rely on these
		// alongside fill, so omitting any of them renders the icon invisible.
		$presentation_attrs = array(
			'fill'             => true,
			'fill-rule'        => true,
			'fill-opacity'     => true,
			'clip-rule'        => true,
			'stroke'           => true,
			'stroke-width'     => true,
			'stroke-linecap'   => true,
			'stroke-linejoin'  => true,
			'stroke-dasharray' => true,
			'stroke-dashoffset' => true,
			'stroke-miterlimit' => true,
			'stroke-opacity'   => true,
			'opacity'          => true,
			'transform'        => true,
		);

		// Reference attributes needed for icons exported with <defs>/<clipPath>/<mask>
		// (a common Figma/Illustrator export pattern). Without these, wp_kses strips
		// the wrapping <defs>/<clipPath> tags but keeps their child shapes, which then
		// render as visible elements instead of staying invisible clip/mask definitions.
		$reference_attrs = array(
			'id'        => true,
			'clip-path' => true,
			'mask'      => true,
		);

		return array(
			'svg'       => array_merge(
				array(
					'class'           => true,
					'aria-hidden'     => true,
					'aria-labelledby' => true,
					'role'            => true,
					'xmlns'           => true,
					'width'           => true,
					'height'          => true,
					'viewbox'         => true,
				),
				$presentation_attrs,
				$reference_attrs
			),
			'defs'      => array( 'id' => true ),
			'clippath'  => array( 'id' => true, 'clippathunits' => true ),
			'mask'      => array_merge(
				array(
					'id'          => true,
					'maskunits'   => true,
					'x'           => true,
					'y'           => true,
					'width'       => true,
					'height'      => true,
				),
				$presentation_attrs
			),
			'path'      => array_merge( array( 'd' => true ), $presentation_attrs, $reference_attrs ),
			'g'         => array_merge( $presentation_attrs, $reference_attrs ),
			'circle'    => array_merge(
				array(
					'cx' => true,
					'cy' => true,
					'r'  => true,
				),
				$presentation_attrs,
				$reference_attrs
			),
			'rect'      => array_merge(
				array(
					'x'      => true,
					'y'      => true,
					'width'  => true,
					'height' => true,
					'rx'     => true,
					'ry'     => true,
				),
				$presentation_attrs,
				$reference_attrs
			),
			'polygon'   => array_merge( array( 'points' => true ), $presentation_attrs, $reference_attrs ),
			'polyline'  => array_merge( array( 'points' => true ), $presentation_attrs, $reference_attrs ),
			'line'      => array_merge(
				array(
					'x1' => true,
					'y1' => true,
					'x2' => true,
					'y2' => true,
				),
				$presentation_attrs,
				$reference_attrs
			),
			'ellipse'   => array_merge(
				array(
					'cx' => true,
					'cy' => true,
					'rx' => true,
					'ry' => true,
				),
				$presentation_attrs,
				$reference_attrs
			),
		);
	}
endif;


if ( ! function_exists( 'pandora_render_iconic_button' ) ) :
	/**
	 * Injects the icon SVG markup and size/gap/padding/background CSS custom
	 * properties into core/button blocks on the frontend when the iconic button
	 * feature is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_iconic_button( $block_content, $block ) {
		if ( 'core/button' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		$attrs = $block['attrs'] ?? array();

		if ( empty( $attrs['iconicButtonEnabled'] ) || empty( $attrs['iconicButtonUniqueClass'] ) ) {
			return $block_content;
		}

		$icon_svg = ! empty( $attrs['iconicButtonCustomSvg'] ) ? $attrs['iconicButtonCustomSvg'] : ( $attrs['iconicButtonIcon'] ?? '' );

		if ( empty( $icon_svg ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		// Merge icon size/gap/padding/background CSS custom properties into the outer wrapper's style attribute.
		$css_vars = array();
		if ( ! empty( $attrs['iconicButtonIconSize'] ) ) {
			$css_vars[] = '--pandora-icon-size:' . esc_attr( $attrs['iconicButtonIconSize'] );
		}
		if ( ! empty( $attrs['iconicButtonIconGap'] ) ) {
			$css_vars[] = '--pandora-icon-gap:' . esc_attr( $attrs['iconicButtonIconGap'] );
		}
		if ( ! empty( $attrs['iconicButtonIconPadding'] ) ) {
			$css_vars[] = '--pandora-icon-padding:' . esc_attr( $attrs['iconicButtonIconPadding'] );
		}
		if ( ! empty( $attrs['iconicButtonIconBgColor'] ) ) {
			$css_vars[] = '--pandora-icon-bg-color:' . esc_attr( $attrs['iconicButtonIconBgColor'] );
		}

		if ( ! empty( $css_vars ) ) {
			$processor = new WP_HTML_Tag_Processor( $block_content );
			if ( $processor->next_tag() ) {
				$existing_style = $processor->get_attribute( 'style' ) ?? '';
				$new_style      = rtrim( $existing_style, '; ' );
				if ( $new_style ) {
					$new_style .= ';';
				}
				$new_style .= implode( ';', $css_vars );
				$processor->set_attribute( 'style', $new_style );
				$block_content = $processor->get_updated_html();
			}
		}

		// Build the icon markup and inject it before/after the link's inner content.
		$icon_html = '<span class="pandora-icon-button-svg">' . wp_kses( $icon_svg, pandora_iconic_button_svg_kses_args() ) . '</span>';

		$block_content = preg_replace_callback(
			'/(<a[^>]*class="[^"]*wp-block-button__link[^"]*"[^>]*>)(.*?)(<\/a>)/s',
			function ( $matches ) use ( $icon_html ) {
				$content = trim( $matches[2] );
				return $matches[1] . $content . $icon_html . $matches[3];
			},
			$block_content
		);

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_iconic_button', 10, 2 );


// =============================================================================
// Icon List – Icon Position Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_iconlist_icon_position_editor_assets' ) ) :
	/**
	 * Enqueues the iconlist-icon-position extension script.
	 * Runs on `enqueue_block_editor_assets` (editor only). The frontend/editor CSS
	 * for `.pandora-icon-pos` already lives in the globally enqueued blocks.css.
	 */
	function pandora_enqueue_iconlist_icon_position_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/iconlist-icon-position/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-iconlist-icon-position-extension',
			get_theme_file_uri( 'build/extensions/iconlist-icon-position/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_iconlist_icon_position_editor_assets' );


if ( ! function_exists( 'pandora_render_iconlist_icon_position' ) ) :
	/**
	 * Injects the `pandora-icon-pos` class into kadence/iconlist blocks on the
	 * frontend when the `changeIconPosition` attribute is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_iconlist_icon_position( $block_content, $block ) {
		if ( 'kadence/iconlist' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		if ( empty( $block['attrs']['changeIconPosition'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'pandora-icon-pos' );
			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_iconlist_icon_position', 10, 2 );


// =============================================================================
// Kadence RowLayout — Featured Image as Background Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_kadence_featured_bg_editor_assets' ) ) :
	/**
	 * Enqueues the kadence-featured-bg extension script and editor stylesheet.
	 */
	function pandora_enqueue_kadence_featured_bg_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/kadence-featured-bg/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-kadence-featured-bg-extension',
			get_theme_file_uri( 'build/extensions/kadence-featured-bg/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/kadence-featured-bg/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-kadence-featured-bg-extension',
				get_theme_file_uri( 'build/extensions/kadence-featured-bg/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_kadence_featured_bg_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_kadence_featured_bg_frontend_assets' ) ) :
	/**
	 * Enqueues the kadence-featured-bg frontend stylesheet.
	 */
	function pandora_enqueue_kadence_featured_bg_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/kadence-featured-bg/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/kadence-featured-bg/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-kadence-featured-bg-extension-style',
			get_theme_file_uri( 'build/extensions/kadence-featured-bg/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_kadence_featured_bg_frontend_assets' );


if ( ! function_exists( 'pandora_render_kadence_featured_bg' ) ) :
	/**
	 * Injects the featured image as background on kadence/rowlayout blocks
	 * on the frontend when the useFeaturedImageAsBg attribute is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_kadence_featured_bg( $block_content, $block ) {
		if ( 'kadence/rowlayout' !== $block['blockName'] ) {
			return $block_content;
		}

		$attrs = $block['attrs'] ?? array();

		if ( empty( $attrs['useFeaturedImageAsBg'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		// Get the current post's featured image.
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $block_content;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( ! $thumbnail_id ) {
			return $block_content;
		}

		$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
		if ( ! $image_url ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-featured-image-bg' );

			$existing_style = $processor->get_attribute( 'style' ) ?? '';
			$new_style      = rtrim( $existing_style, '; ' );
			if ( $new_style ) {
				$new_style .= ';';
			}
			$new_style .= '--pandora-featured-bg-image:url(' . esc_url( $image_url ) . ')';
			$processor->set_attribute( 'style', $new_style );

			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_kadence_featured_bg', 10, 2 );


// =============================================================================
// Kadence RowLayout — Overlay Image Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_kadence_overlay_editor_assets' ) ) :
	/**
	 * Enqueues the kadence-overlay extension script and editor stylesheet.
	 */
	function pandora_enqueue_kadence_overlay_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/kadence-overlay/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-kadence-overlay-extension',
			get_theme_file_uri( 'build/extensions/kadence-overlay/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/kadence-overlay/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-kadence-overlay-extension',
				get_theme_file_uri( 'build/extensions/kadence-overlay/index.css' ),
				array(),
				wp_get_theme()->get( 'Version' )
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_kadence_overlay_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_kadence_overlay_frontend_assets' ) ) :
	/**
	 * Enqueues the kadence-overlay frontend stylesheet.
	 */
	function pandora_enqueue_kadence_overlay_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/kadence-overlay/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/kadence-overlay/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-kadence-overlay-extension-style',
			get_theme_file_uri( 'build/extensions/kadence-overlay/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_kadence_overlay_frontend_assets' );


if ( ! function_exists( 'pandora_render_kadence_overlay' ) ) :
	/**
	 * Injects the `has-kadence-overlay` class and overlay CSS custom properties
	 * into kadence/rowlayout blocks on the frontend when an overlay image is set.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_kadence_overlay( $block_content, $block ) {
		if ( 'kadence/rowlayout' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		$attrs     = $block['attrs'] ?? array();
		$image_url = $attrs['kadenceOverlayImageUrl'] ?? '';

		if ( empty( $image_url ) || empty( $block_content ) ) {
			return $block_content;
		}

		$left = $attrs['kadenceOverlayLeft'] ?? '0px';
		$top  = $attrs['kadenceOverlayTop']  ?? '0px';

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-kadence-overlay' );

			$existing_style = $processor->get_attribute( 'style' ) ?? '';
			$new_style      = rtrim( $existing_style, '; ' );
			if ( $new_style ) {
				$new_style .= ';';
			}
			$new_style .= '--pandora-koverlay-image:url(' . esc_url( $image_url ) . ')';
			$new_style .= ';--pandora-koverlay-left:'  . esc_attr( $left );
			$new_style .= ';--pandora-koverlay-top:'   . esc_attr( $top );
			$processor->set_attribute( 'style', $new_style );

			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_kadence_overlay', 10, 2 );


// =============================================================================
// Kadence Column — Quote Area Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_kadence_quote_area_editor_assets' ) ) :
	/**
	 * Enqueues the kadence-quote-area extension script.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_kadence_quote_area_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/kadence-quote-area/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-kadence-quote-area-extension',
			get_theme_file_uri( 'build/extensions/kadence-quote-area/index.js' ),
			$assets['dependencies'],
			wp_get_theme()->get( 'Version' ),
			true
		);
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_kadence_quote_area_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_kadence_quote_area_frontend_assets' ) ) :
	/**
	 * Enqueues the kadence-quote-area frontend stylesheet.
	 * Runs on `enqueue_block_assets` (editor + front end).
	 */
	function pandora_enqueue_kadence_quote_area_frontend_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/kadence-quote-area/index.asset.php' );
		$style_file = get_theme_file_path( 'build/extensions/kadence-quote-area/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-kadence-quote-area-extension-style',
			get_theme_file_uri( 'build/extensions/kadence-quote-area/style-index.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_kadence_quote_area_frontend_assets' );


if ( ! function_exists( 'pandora_render_kadence_quote_area' ) ) :
	/**
	 * Injects the `pandora-quote` class into kadence/column blocks on the
	 * frontend when the `quoteAreaEnabled` attribute is true.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_kadence_quote_area( $block_content, $block ) {
		if ( 'kadence/column' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		if ( empty( $block['attrs']['quoteAreaEnabled'] ) || empty( $block_content ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'pandora-quote' );
			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_kadence_quote_area', 10, 2 );