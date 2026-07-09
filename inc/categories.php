<?php
/**
 * Block & Pattern Categories
 *
 * Registers custom block categories and block pattern categories
 * used throughout this theme.
 *
 * @package Pandora_Group
 */

if ( ! function_exists( 'pandora_block_categories' ) ) :
	/**
	 * Adds the "Brilliant Blocks" category to the block inserter.
	 *
	 * @param  array                   $block_categories     Existing block categories.
	 * @param  WP_Block_Editor_Context $block_editor_context Current editor context.
	 * @return array
	 */
	function pandora_block_categories( $block_categories, $block_editor_context ) {
		return array_merge(
			array(
				array(
					'slug'  => 'pandora',
					'title' => __( 'Pandora Group', 'pandora-group' ),
				),
			),
			$block_categories

		);
	}
endif;
add_filter( 'block_categories_all', 'pandora_block_categories', 10, 2 );


if ( ! function_exists( 'pandora_pattern_categories' ) ) :
	/**
	 * Registers the "Pandora Group" block pattern category.
	 */
	function pandora_pattern_categories() {
		register_block_pattern_category(
			'pandora',
			array(
				'label'       => __( 'Pandora Group', 'pandora-group' ),
				'description' => __( 'A collection of Pandora Group patterns.', 'pandora-group' ),
			)
		);
	}
endif;
add_action( 'init', 'pandora_pattern_categories' );
