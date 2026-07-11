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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/hover-color/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-hover-color-extension',
				get_theme_file_uri( 'build/extensions/hover-color/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-full-height/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-full-height-extension',
				get_theme_file_uri( 'build/extensions/group-full-height/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
// Group / Row Layout – Glass Effect Extension
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_glass_effect_editor_assets' ) ) :
	/**
	 * Enqueues the glass-effect extension script.
	 * Runs on `enqueue_block_editor_assets` (editor only). The frontend/editor CSS
	 * for `.has-glass-effect` already lives in the globally enqueued blocks.css.
	 */
	function pandora_enqueue_glass_effect_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/glass-effect/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-glass-effect-extension',
			get_theme_file_uri( 'build/extensions/glass-effect/index.js' ),
			$assets['dependencies'],
			$assets['version'],
			true
		);
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_glass_effect_editor_assets' );


if ( ! function_exists( 'pandora_render_glass_effect' ) ) :
	/**
	 * Injects the `has-glass-effect` class into core/group and kadence/rowlayout
	 * blocks on the frontend when the `glassEffectEnabled` attribute is enabled.
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_glass_effect( $block_content, $block ) {
		$allowed_blocks = array( 'core/group', 'kadence/rowlayout', 'kadence/column' );

		if ( ! in_array( $block['blockName'] ?? '', $allowed_blocks, true ) ) {
			return $block_content;
		}

		if ( empty( $block['attrs']['glassEffectEnabled'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			$processor->add_class( 'has-glass-effect' );
			return $processor->get_updated_html();
		}

		return $block_content;
	}
endif;
add_filter( 'render_block', 'pandora_render_glass_effect', 10, 2 );


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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-overlay-bg/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-overlay-bg-extension',
				get_theme_file_uri( 'build/extensions/group-overlay-bg/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-global-hover/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-global-hover-extension',
				get_theme_file_uri( 'build/extensions/group-global-hover/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/text-max-width/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-text-max-width-extension',
				get_theme_file_uri( 'build/extensions/text-max-width/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/button-full-width-mobile/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-button-full-width-mobile-extension',
				get_theme_file_uri( 'build/extensions/button-full-width-mobile/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/button-gradient-border/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-button-gradient-border-extension',
				get_theme_file_uri( 'build/extensions/button-gradient-border/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
// Query Carousel — Block Variation for core/query
// =============================================================================

if ( ! function_exists( 'pandora_enqueue_query_carousel_editor_assets' ) ) :
	/**
	 * Enqueues the query-carousel extension script and editor stylesheet.
	 * Runs on `enqueue_block_editor_assets` (editor only).
	 */
	function pandora_enqueue_query_carousel_editor_assets() {
		$asset_file = get_theme_file_path( 'build/extensions/query-carousel/index.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-query-carousel-extension',
			get_theme_file_uri( 'build/extensions/query-carousel/index.js' ),
			$assets['dependencies'],
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/query-carousel/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-query-carousel-extension',
				get_theme_file_uri( 'build/extensions/query-carousel/index.css' ),
				array(),
				$assets['version']
			);
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'pandora_enqueue_query_carousel_editor_assets' );


if ( ! function_exists( 'pandora_enqueue_query_carousel_frontend_assets' ) ) :
	/**
	 * Enqueues the query-carousel frontend stylesheet and Swiper assets
	 * only when a Query Carousel variation is present on the page.
	 * Runs on `wp_enqueue_scripts` with late priority so is_singular() etc. are available.
	 */
	function pandora_enqueue_query_carousel_frontend_assets() {
		$asset_file  = get_theme_file_path( 'build/extensions/query-carousel/index.asset.php' );
		$style_file  = get_theme_file_path( 'build/extensions/query-carousel/style-index.css' );

		if ( ! file_exists( $asset_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_style(
			'pandora-query-carousel-extension-style',
			get_theme_file_uri( 'build/extensions/query-carousel/style-index.css' ),
			array( 'pandora-swiper-style' ),
			$assets['version']
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_query_carousel_frontend_assets' );


if ( ! function_exists( 'pandora_enqueue_query_carousel_view_script' ) ) :
	/**
	 * Enqueues the Swiper init script + Swiper library on the frontend
	 * only when a Query Carousel block is rendered.
	 */
	function pandora_enqueue_query_carousel_view_script() {
		if ( is_admin() ) {
			return;
		}

		$asset_file = get_theme_file_path( 'build/extensions/query-carousel/view.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'pandora-swiper-script',
			get_parent_theme_file_uri( 'assets/js/swiper-bundle.min.js' ),
			array(),
			wp_get_theme()->get( 'Version' ),
			true
		);

		wp_enqueue_script(
			'pandora-query-carousel-view',
			get_theme_file_uri( 'build/extensions/query-carousel/view.js' ),
			array( 'pandora-swiper-script' ),
			$assets['version'],
			true
		);
	}
endif;


if ( ! function_exists( 'pandora_render_query_carousel' ) ) :
	/**
	 * Transforms core/query Query Carousel variation output into Swiper markup.
	 *
	 * When the namespace attribute matches 'pandora/query-carousel', this filter:
	 * 1. Wraps the post template items in Swiper markup
	 * 2. Adds a controls wrapper containing pagination and navigation arrows
	 * 3. Adds data attributes for Swiper configuration
	 * 4. Enqueues Swiper assets
	 *
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data including name and attributes.
	 * @return string Modified block HTML.
	 */
	function pandora_render_query_carousel( $block_content, $block ) {
		if ( 'core/query' !== $block['blockName'] ) {
			return $block_content;
		}

		$attrs = $block['attrs'] ?? array();

		if ( ( $attrs['namespace'] ?? '' ) !== 'pandora/query-carousel' ) {
			return $block_content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

		// Enqueue Swiper and view script.
		pandora_enqueue_query_carousel_view_script();

		// ── Extract carousel attributes ────────────────────────────────────
		$columns   = $attrs['qcColumns'] ?? array( 'Desktop' => 3, 'Tablet' => 2, 'Mobile' => 1 );
		$gaps      = $attrs['qcGaps'] ?? array( 'Desktop' => 20, 'Tablet' => 15, 'Mobile' => 0 );
		$loop      = $attrs['qcLoop'] ?? true;
		$autoplay  = $attrs['qcAutoplay'] ?? false;
		$delay     = $attrs['qcDelay'] ?? 3000;
		$show_arrows     = $attrs['qcShowArrows'] ?? true;
		$show_pagination = $attrs['qcShowPagination'] ?? false;

		// ── Build Swiper options data attribute ────────────────────────────
		$swiper_options = wp_json_encode( array(
			'loop'     => $loop,
			'autoplay' => $autoplay ? array( 'delay' => $delay ) : false,
			'columns'  => $columns,
			'gaps'     => $gaps,
		) );

		// ── Build class names ───────────────────────────────────────────────
		$classes = array( 'pandora-query-carousel' );

		// ── Icon SVG ─────────────────────────────────────────────────────────
		$svg_prev = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6z"/></svg>';
		$svg_next = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>';

		// ── Transform the HTML ──────────────────────────────────────────────
		// Replace <ul with <div class="swiper"><div class="swiper-wrapper">
		$transformed = preg_replace(
			'/<ul([^>]*class=["\'][^"\']*wp-block-post-template[^"\']*["\'][^>]*)>/',
			'<div class="swiper"><div class="swiper-wrapper">',
			$block_content
		);

		// If the preg_replace didn't match, try a more lenient pattern
		if ( $transformed === $block_content ) {
			$transformed = preg_replace(
				'/<ul([^>]*class=["\']([^"\']*)["\'][^>]*)>/',
				'<div class="swiper"><div class="swiper-wrapper">',
				$block_content
			);
		}

		// Change <li> to <div class="swiper-slide">
		$transformed = preg_replace(
			'/<li([^>]*)>/',
			'<div class="swiper-slide"$1>',
			$transformed
		);

		// Change </li> to </div>
		$transformed = str_replace( '</li>', '</div>', $transformed );

		// Close </ul> becomes </div></div>
		$transformed = preg_replace(
			'/<\/ul>/',
			'</div></div>',
			$transformed
		);

		// ── Build controls wrapper (pagination + nav arrows) ────────────────
		$controls_inner = '';
		if ( $show_pagination ) {
			$controls_inner .= '<div class="swiper-pagination"></div>';
		}
		if ( $show_arrows ) {
			$controls_inner .= '<div class="pandora-qc-nav-group">';
			$controls_inner .= '<div class="swiper-custom-prev gu-nav" aria-label="' . esc_attr__( 'Previous', 'pandora-group' ) . '">' . $svg_prev . '</div>';
			$controls_inner .= '<div class="swiper-custom-next gu-nav" aria-label="' . esc_attr__( 'Next', 'pandora-group' ) . '">' . $svg_next . '</div>';
			$controls_inner .= '</div>';
		}

		$controls_html = '';
		if ( $show_arrows || $show_pagination ) {
			$controls_html = '<div class="pandora-qc-controls">' . $controls_inner . '</div>';
		}

		// ── Add classes and data attribute to the outer wrapper ─────────────
		$processor = new WP_HTML_Tag_Processor( $transformed );
		if ( $processor->next_tag() ) {
			foreach ( $classes as $class ) {
				$processor->add_class( $class );
			}

			$processor->set_attribute( 'data-qc-options', $swiper_options );

			$transformed = $processor->get_updated_html();
		}

		// ── Append controls wrapper before the closing tag ──────────────────
		if ( $controls_html ) {
			$transformed = preg_replace(
				'/(<\/div>)\s*$/',
				$controls_html . '$1',
				$transformed,
				1
			);
		}

		return $transformed;
	}
endif;
add_filter( 'render_block', 'pandora_render_query_carousel', 10, 2 );


if ( ! function_exists( 'pandora_query_carousel_exclude_current' ) ) :
	/**
	 * When "Exclude Current Post" is enabled, injects the current post ID into
	 * the query block's exclude array before rendering.
	 *
	 * Uses render_block_data so the modification reaches the core/post-template
	 * inner block via context and is picked up by build_query_vars_from_query_block.
	 *
	 * @param array $parsed_block The parsed block data.
	 * @return array Modified block data.
	 */
	function pandora_query_carousel_exclude_current( $parsed_block ) {
		if ( 'core/query' !== ( $parsed_block['blockName'] ?? '' ) ) {
			return $parsed_block;
		}

		if ( ( $parsed_block['attrs']['namespace'] ?? '' ) !== 'pandora/query-carousel' ) {
			return $parsed_block;
		}

		if ( empty( $parsed_block['attrs']['qcExcludeCurrentPost'] ) ) {
			return $parsed_block;
		}

		$current_id = get_the_ID();

		if ( ! $current_id ) {
			return $parsed_block;
		}

		if ( ! is_array( $parsed_block['attrs']['query'] ?? null ) ) {
			$parsed_block['attrs']['query'] = array();
		}

		$exclude = array_map( 'intval', $parsed_block['attrs']['query']['exclude'] ?? array() );

		if ( ! in_array( (int) $current_id, $exclude, true ) ) {
			$exclude[] = (int) $current_id;
		}

		$parsed_block['attrs']['query']['exclude'] = $exclude;

		return $parsed_block;
	}
endif;
add_filter( 'render_block_data', 'pandora_query_carousel_exclude_current' );


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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/group-grid-columns/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-group-grid-columns-extension',
				get_theme_file_uri( 'build/extensions/group-grid-columns/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/iconic-button/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-iconic-button-extension',
				get_theme_file_uri( 'build/extensions/iconic-button/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
		$position  = $attrs['iconicButtonIconPosition'] ?? '';

		$block_content = preg_replace_callback(
			'/(<a[^>]*class="[^"]*wp-block-button__link[^"]*"[^>]*>)(.*?)(<\/a>)/s',
			function ( $matches ) use ( $icon_html, $position ) {
				$content = trim( $matches[2] );
				if ( '' !== $position ) {
					return $matches[1] . $icon_html . $content . $matches[3];
				}
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
			$assets['version'],
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/kadence-featured-bg/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-kadence-featured-bg-extension',
				get_theme_file_uri( 'build/extensions/kadence-featured-bg/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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
			$assets['version'],
			true
		);

		$editor_css = get_theme_file_path( 'build/extensions/kadence-overlay/index.css' );
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'pandora-kadence-overlay-extension',
				get_theme_file_uri( 'build/extensions/kadence-overlay/index.css' ),
				array(),
				$assets['version']
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
			$assets['version']
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