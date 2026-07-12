<?php
/**
 * Asset Enqueueing
 *
 * Registers and enqueues stylesheets and scripts for the front end
 * and the block editor.
 *
 * @package Pandora_Group
 */

if ( ! function_exists( 'pandora_enqueue_styles' ) ) :
	/**
	 * Enqueues the theme stylesheet on the front end.
	 */
	function pandora_enqueue_styles() {
		$theme_version = wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			'pandora-root-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			$theme_version
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'pandora_enqueue_styles' );


if ( ! function_exists( 'pandora_enqueue_block_styles' ) ) :
	/**
	 * Enqueues the shared block stylesheet in both the editor and on the front end.
	 */
	function pandora_enqueue_block_styles() {
		wp_enqueue_style(
			'pandora-block-style',
			get_parent_theme_file_uri( 'assets/css/blocks.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'enqueue_block_assets', 'pandora_enqueue_block_styles' );
