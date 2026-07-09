<?php
/**
 * Posts Grid Shortcode
 *
 * Renders a filterable, paginated post grid via AJAX.
 *
 * Usage: [pandora_posts_grid per_page="6" post_type="post"]
 */

// =============================================================================
// Asset enqueueing
// =============================================================================

if ( ! function_exists( 'pandora_posts_grid_enqueue_assets' ) ) :
	function pandora_posts_grid_enqueue_assets() {
		$version = wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			'pandora-posts-grid',
			get_theme_file_uri( 'assets/css/shortcode.css' ),
			array(),
			$version
		);

		wp_enqueue_script(
			'pandora-posts-grid',
			get_theme_file_uri( 'assets/js/shortcode.js' ),
			array(),
			$version,
			true
		);
	}
endif;


// =============================================================================
// Helpers
// =============================================================================

if ( ! function_exists( 'pandora_posts_grid_render_post_item' ) ) :
	/**
	 * Renders a single post card: image, title, excerpt, author avatar + name + date.
	 */
	function pandora_posts_grid_render_post_item( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$permalink   = get_permalink( $post_id );
		$title       = get_the_title( $post_id );
		$excerpt     = get_the_excerpt( $post_id );
		$date        = get_the_date( 'F j, Y', $post_id );
		$author_id   = (int) $post->post_author;
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$avatar      = get_avatar( $author_id, 32, '', esc_attr( $author_name ), array( 'class' => 'ipg-card__avatar-img' ) );
		$thumbnail   = has_post_thumbnail( $post_id )
			? get_the_post_thumbnail( $post_id, 'medium_large', array( 'loading' => 'lazy' ) )
			: '';

		$html  = '<div class="ipg-card">';

		if ( $thumbnail ) {
			$html .= '<a href="' . esc_url( $permalink ) . '" class="ipg-card__image" tabindex="-1" aria-hidden="true">';
			$html .= $thumbnail;
			$html .= '</a>';
		}

		$html .= '<div class="ipg-card__body">';
		$html .= '<h2 class="ipg-card__title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h2>';

		if ( $excerpt ) {
			$html .= '<p class="ipg-card__excerpt">' . esc_html( $excerpt ) . '</p>';
		}

		// $html .= '<div class="ipg-card__meta">';
		// $html .= '<span class="ipg-card__avatar">' . $avatar . '</span>';
		// $html .= '<span class="ipg-card__author">' . esc_html( $author_name ) . '</span>';
		// $html .= '<span class="ipg-card__sep" aria-hidden="true">&middot;</span>';
		// $html .= '<span class="ipg-card__date">' . esc_html( $date ) . '</span>';
		// $html .= '</div>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
endif;


if ( ! function_exists( 'pandora_posts_grid_render_posts' ) ) :
	/**
	 * Renders the full grid of post cards for a given WP_Query.
	 */
	function pandora_posts_grid_render_posts( $query ) {
		if ( ! $query->have_posts() ) {
			return '<p class="ipg-no-posts">' . esc_html__( 'No posts found.', 'pandora-group' ) . '</p>';
		}

		$html = '<div class="ipg-grid">';

		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= pandora_posts_grid_render_post_item( get_the_ID() );
		}

		$html .= '</div>';

		wp_reset_postdata();

		return $html;
	}
endif;


if ( ! function_exists( 'pandora_posts_grid_resolve_category_ids' ) ) :
	/**
	 * Resolves a comma-separated list of term IDs/slugs into an array of term IDs.
	 */
	function pandora_posts_grid_resolve_category_ids( $categories_raw, $taxonomy ) {
		$ids = array();

		foreach ( array_filter( array_map( 'trim', explode( ',', (string) $categories_raw ) ), 'strlen' ) as $token ) {
			$term = is_numeric( $token )
				? get_term_by( 'id', (int) $token, $taxonomy )
				: get_term_by( 'slug', sanitize_title( $token ), $taxonomy );

			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
			}
		}

		return array_unique( $ids );
	}
endif;


if ( ! function_exists( 'pandora_posts_grid_pagination_range' ) ) :
	/**
	 * Returns an array of page numbers and '...' placeholders.
	 * Always shows first/last page and current page ± 1 neighbour.
	 */
	function pandora_posts_grid_pagination_range( $total_pages, $current_page ) {
		$total_pages  = (int) $total_pages;
		$current_page = (int) $current_page;

		if ( $total_pages <= 7 ) {
			return range( 1, $total_pages );
		}

		$left  = max( 2, $current_page - 1 );
		$right = min( $total_pages - 1, $current_page + 1 );
		$pages = array( 1 );

		if ( $left > 2 ) {
			$pages[] = '...';
		}

		for ( $i = $left; $i <= $right; $i++ ) {
			$pages[] = $i;
		}

		if ( $right < $total_pages - 1 ) {
			$pages[] = '...';
		}

		$pages[] = $total_pages;

		return $pages;
	}
endif;


if ( ! function_exists( 'pandora_posts_grid_render_pagination' ) ) :
	/**
	 * Renders prev/next arrows + numbered page buttons with ellipsis.
	 */
	function pandora_posts_grid_render_pagination( $total_pages, $current_page ) {
		$total_pages  = (int) $total_pages;
		$current_page = (int) $current_page;

		if ( $total_pages <= 1 ) {
			return '';
		}

		$svg_prev = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
		$svg_next = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

		$html = '<div class="ipg-pagination">';

		// Prev.
		$prev_page = max( 1, $current_page - 1 );
		$html     .= '<button class="ipg-page-btn ipg-page-arrow"'
			. ( 1 === $current_page ? ' disabled' : '' )
			. ' data-page="' . $prev_page . '" aria-label="' . esc_attr__( 'Previous page', 'pandora-group' ) . '">'
			. $svg_prev
			. '</button>';

		// Pages.
		foreach ( pandora_posts_grid_pagination_range( $total_pages, $current_page ) as $page ) {
			if ( '...' === $page ) {
				$html .= '<span class="ipg-page-ellipsis">&hellip;</span>';
			} else {
				$active = ( (int) $page === $current_page ) ? ' active' : '';
				$html  .= '<button class="ipg-page-btn' . $active . '" data-page="' . (int) $page . '" aria-label="' . sprintf( esc_attr__( 'Page %d', 'pandora-group' ), (int) $page ) . '">' . (int) $page . '</button>';
			}
		}

		// Next.
		$next_page = min( $total_pages, $current_page + 1 );
		$html     .= '<button class="ipg-page-btn ipg-page-arrow"'
			. ( $current_page === $total_pages ? ' disabled' : '' )
			. ' data-page="' . $next_page . '" aria-label="' . esc_attr__( 'Next page', 'pandora-group' ) . '">'
			. $svg_next
			. '</button>';

		$html .= '</div>';

		return $html;
	}
endif;


// =============================================================================
// AJAX handler
// =============================================================================

if ( ! function_exists( 'pandora_posts_grid_ajax' ) ) :
	function pandora_posts_grid_ajax() {
		check_ajax_referer( 'pandora_posts_grid_nonce', 'nonce' );

		$cat        = isset( $_POST['cat'] )        ? absint( $_POST['cat'] )                                        : 0;
		$page       = isset( $_POST['page'] )       ? max( 1, absint( $_POST['page'] ) )                             : 1;
		$per_page   = isset( $_POST['per_page'] )   ? min( 50, max( 1, absint( $_POST['per_page'] ) ) )              : 6;
		$post_type  = isset( $_POST['post_type'] )  ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) )       : 'post';
		$taxonomy   = isset( $_POST['taxonomy'] )   ? sanitize_key( $_POST['taxonomy'] )                             : 'category';
		$categories = isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) )      : '';

		if ( ! post_type_exists( $post_type ) ) {
			$post_type = 'post';
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			$taxonomy = 'category';
		}

		$allowed_cat_ids = array_filter( array_map( 'absint', explode( ',', $categories ) ) );

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
		);

		if ( $cat > 0 ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $cat,
				),
			);
		} elseif ( ! empty( $allowed_cat_ids ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $allowed_cat_ids,
				),
			);
		}

		$query = new WP_Query( $args );

		wp_send_json_success( array(
			'html'         => pandora_posts_grid_render_posts( $query ),
			'pagination'   => pandora_posts_grid_render_pagination( (int) $query->max_num_pages, $page ),
			'total_pages'  => (int) $query->max_num_pages,
			'current_page' => $page,
		) );
	}
endif;
add_action( 'wp_ajax_pandora_posts_grid', 'pandora_posts_grid_ajax' );
add_action( 'wp_ajax_nopriv_pandora_posts_grid', 'pandora_posts_grid_ajax' );


// =============================================================================
// Shortcode
// =============================================================================

if ( ! function_exists( 'pandora_posts_grid_shortcode' ) ) :
	/**
	 * [pandora_posts_grid per_page="6" post_type="post" categories="4,9"]
	 *
	 * `categories` accepts a comma-separated list of term IDs and/or slugs.
	 * When set, only posts in those categories are shown/filterable; when
	 * empty (default), all categories are loaded as before.
	 */
	function pandora_posts_grid_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page'   => 6,
				'post_type'  => 'post',
				'categories' => '',
			),
			$atts,
			'pandora_posts_grid'
		);

		$per_page  = min( 50, max( 1, (int) $atts['per_page'] ) );
		$post_type = sanitize_key( $atts['post_type'] );

		if ( ! post_type_exists( $post_type ) ) {
			$post_type = 'post';
		}

		// Resolve primary hierarchical taxonomy for filter buttons.
		$taxonomy = 'category';
		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $tax ) {
			if ( $tax->public && $tax->hierarchical ) {
				$taxonomy = $tax->name;
				break;
			}
		}

		$allowed_cat_ids = pandora_posts_grid_resolve_category_ids( $atts['categories'], $taxonomy );

		$terms_args = array( 'taxonomy' => $taxonomy, 'hide_empty' => true );
		if ( ! empty( $allowed_cat_ids ) ) {
			$terms_args['include'] = $allowed_cat_ids;
			$terms_args['orderby'] = 'include';
		}

		$terms = get_terms( $terms_args );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		// Initial query (page 1, no filter).
		$query_args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $per_page,
			'paged'          => 1,
			'post_status'    => 'publish',
		);

		if ( ! empty( $allowed_cat_ids ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $allowed_cat_ids,
				),
			);
		}

		$query = new WP_Query( $query_args );

		pandora_posts_grid_enqueue_assets();

		$config = wp_json_encode( array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'pandora_posts_grid_nonce' ),
			'perPage'    => $per_page,
			'postType'   => $post_type,
			'taxonomy'   => $taxonomy,
			'categories' => implode( ',', $allowed_cat_ids ),
		) );

		$html = '<div class="ipg-wrapper" data-config="' . esc_attr( $config ) . '">';

		// Filter nav.
		if ( ! empty( $terms ) ) {
			$html .= '<div class="ipg-nav">';
			$html .= '<button class="ipg-filter-btn active" data-cat="0">' . esc_html__( 'View All', 'pandora-group' ) . '</button>';
			foreach ( $terms as $term ) {
				$html .= '<button class="ipg-filter-btn" data-cat="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</button>';
			}
			$html .= '</div>';
		}

		$html .= '<div class="ipg-posts">' . pandora_posts_grid_render_posts( $query ) . '</div>';
		$html .= '<div class="ipg-pagination-wrap">' . pandora_posts_grid_render_pagination( (int) $query->max_num_pages, 1 ) . '</div>';

		$html .= '</div>';

		wp_reset_postdata();

		return $html;
	}
endif;
add_shortcode( 'pandora_posts_grid', 'pandora_posts_grid_shortcode' );
