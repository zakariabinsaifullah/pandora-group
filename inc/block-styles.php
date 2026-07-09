<?php
/**
 * Core Block Styles
 *
 * Registers custom style variations for core (and third-party) blocks.
 *
 * @package Pandora_Group
 */

if ( ! function_exists( 'pandora_block_styles' ) ) :
	/**
	 * Registers all custom block style variations for the theme.
	 */
	function pandora_block_styles() {
		register_block_style(
			'core/post-excerpt',
			array(
				'name'         => 'outline-link',
				'label'        => __( 'Outline Link', 'pandora-group' ),
			)
		);
		
		register_block_style(
			'core/group',
			array(
				'name'         => 'wrap-mobile',
				'label'        => __( 'Wrap Mobile', 'pandora-group' ),
			)
		);

		register_block_style(
			'core/button',
			array(
				'name'  => 'fill-white',
				'label' => __( 'Fill White', 'pandora-group' ),
			)
		);

		register_block_style(
			'core/button',
			array(
				'name'  => 'gradient-text',
				'label' => __( 'Gradient Text', 'pandora-group' ),
			)
		);

		register_block_style(
			'core/button',
			array(
				'name'  => 'link',
				'label' => __( 'Link', 'pandora-group' ),
			)
		);
	}
endif;
add_action( 'init', 'pandora_block_styles' );
