<?php
/**
 * Team Custom Post Type
 *
 * Registers the "Team" CPT with title (Name) and featured image (Avatar)
 * support, plus three meta fields: Designation, Short Bio, and LinkedIn URL.
 * Single-post views are intentionally disabled.
 *
 * @package Pandora_Group
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// Register CPT
// =============================================================================

add_action( 'init', 'pandora_register_team_cpt' );

function pandora_register_team_cpt() {
	register_post_type(
		'pandora_team',
		array(
			'labels'              => array(
				'name'                  => __( 'Team', 'pandora-group' ),
				'singular_name'         => __( 'Team Member', 'pandora-group' ),
				'add_new'               => __( 'Add New Member', 'pandora-group' ),
				'add_new_item'          => __( 'Add New', 'pandora-group' ),
				'edit_item'             => __( 'Edit Team Member', 'pandora-group' ),
				'new_item'              => __( 'New Team Member', 'pandora-group' ),
				'view_item'             => __( 'View Team Member', 'pandora-group' ),
				'search_items'          => __( 'Search Team', 'pandora-group' ),
				'not_found'             => __( 'No team members found.', 'pandora-group' ),
				'not_found_in_trash'    => __( 'No team members found in trash.', 'pandora-group' ),
				'menu_name'             => __( 'Team', 'pandora-group' ),
				'featured_image'        => __( 'Team Member Photo', 'pandora-group' ),
				'set_featured_image'    => __( 'Set team member photo', 'pandora-group' ),
				'remove_featured_image' => __( 'Remove team member photo', 'pandora-group' ),
				'use_featured_image'    => __( 'Use as team member photo', 'pandora-group' ),
			),
			'public'              => true,
			'publicly_queryable'  => false,  // Prevents front-end single view.
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-groups',
			'hierarchical'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'supports'            => array( 'title', 'thumbnail' ),
		)
	);
}


// =============================================================================
// Title placeholder
// =============================================================================

add_filter( 'enter_title_here', 'pandora_team_title_placeholder', 10, 2 );

function pandora_team_title_placeholder( $placeholder, $post ) {
	if ( 'pandora_team' === $post->post_type ) {
		return __( 'Team member name', 'pandora-group' );
	}
	return $placeholder;
}


// =============================================================================
// Register meta fields
// =============================================================================

add_action( 'init', 'pandora_register_team_meta' );

function pandora_register_team_meta() {
	$common = array(
		'object_subtype'    => 'pandora_team',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function () {
			return current_user_can( 'edit_posts' );
		},
	);

	register_post_meta( 'pandora_team', '_pandora_team_designation', array_merge( $common, array(
		'type'        => 'string',
		'description' => 'Team member designation / job title.',
	) ) );

	register_post_meta( 'pandora_team', '_pandora_team_bio', array_merge( $common, array(
		'type'              => 'string',
		'description'       => 'Short bio for the team member.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) ) );

	register_post_meta( 'pandora_team', '_pandora_team_linkedin', array_merge( $common, array(
		'type'              => 'string',
		'description'       => 'LinkedIn profile URL.',
		'sanitize_callback' => 'sanitize_text_field',
	) ) );
}


// =============================================================================
// Meta box
// =============================================================================

add_action( 'add_meta_boxes', 'pandora_team_meta_boxes' );

function pandora_team_meta_boxes() {
	add_meta_box(
		'pandora_team_details',
		__( 'Team Member Details', 'pandora-group' ),
		'pandora_team_meta_box_html',
		'pandora_team',
		'normal',
		'high'
	);
}

function pandora_team_meta_box_html( $post ) {
	wp_nonce_field( 'pandora_team_save_meta', 'pandora_team_nonce' );

	$designation = get_post_meta( $post->ID, '_pandora_team_designation', true );
	$bio         = get_post_meta( $post->ID, '_pandora_team_bio', true );
	$linkedin    = get_post_meta( $post->ID, '_pandora_team_linkedin', true );
	?>
	<table class="form-table" style="margin-top:0;">
		<tr>
			<th scope="row">
				<label for="pandora_team_designation"><?php esc_html_e( 'Designation', 'pandora-group' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="pandora_team_designation"
					name="pandora_team_designation"
					value="<?php echo esc_attr( $designation ); ?>"
					class="large-text"
					placeholder="<?php esc_attr_e( 'e.g. Senior Developer', 'pandora-group' ); ?>"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="pandora_team_bio"><?php esc_html_e( 'Short Bio', 'pandora-group' ); ?></label>
			</th>
			<td>
				<textarea
					id="pandora_team_bio"
					name="pandora_team_bio"
					class="large-text"
					rows="5"
					placeholder="<?php esc_attr_e( 'A short bio about the team member...', 'pandora-group' ); ?>"
				><?php echo esc_textarea( $bio ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="pandora_team_linkedin"><?php esc_html_e( 'LinkedIn URL', 'pandora-group' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="pandora_team_linkedin"
					name="pandora_team_linkedin"
					value="<?php echo esc_attr( $linkedin ); ?>"
					class="large-text"
					placeholder="https://linkedin.com/in/username"
				/>
			</td>
		</tr>
	</table>
	<?php
}


// =============================================================================
// Save meta
// =============================================================================

add_action( 'save_post_pandora_team', 'pandora_team_save_meta' );

function pandora_team_save_meta( $post_id ) {
	if (
		! isset( $_POST['pandora_team_nonce'] ) ||
		! wp_verify_nonce( $_POST['pandora_team_nonce'], 'pandora_team_save_meta' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['pandora_team_designation'] ) ) {
		update_post_meta( $post_id, '_pandora_team_designation', sanitize_text_field( wp_unslash( $_POST['pandora_team_designation'] ) ) );
	}

	if ( isset( $_POST['pandora_team_bio'] ) ) {
		update_post_meta( $post_id, '_pandora_team_bio', sanitize_textarea_field( wp_unslash( $_POST['pandora_team_bio'] ) ) );
	}

	if ( isset( $_POST['pandora_team_linkedin'] ) ) {
		update_post_meta( $post_id, '_pandora_team_linkedin', sanitize_text_field( wp_unslash( $_POST['pandora_team_linkedin'] ) ) );
	}
}
