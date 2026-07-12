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


// ── Post category list: ID column ──────────────────────────────────────────────

add_filter( 'manage_edit-category_columns', 'pandora_category_id_column' );
function pandora_category_id_column( $columns ) {
	$columns['pandora_cat_id'] = __( 'ID', 'pandora-group' );
	return $columns;
}

add_filter( 'manage_category_custom_column', 'pandora_category_id_column_content', 10, 3 );
function pandora_category_id_column_content( $content, $column_name, $term_id ) {
	if ( 'pandora_cat_id' === $column_name ) {
		$content = $term_id;
	}
	return $content;
}

add_action( 'admin_head', 'pandora_category_id_column_width' );
function pandora_category_id_column_width() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-category' !== $screen->id ) {
		return;
	}
	echo '<style>th.column-pandora_cat_id,td.column-pandora_cat_id{width:80px!important;text-align:center!important;} th.column-pandora_cat_id a{display:block;text-align:center;}</style>';
}

// ── Block & pattern categories ─────────────────────────────────────────────────

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
