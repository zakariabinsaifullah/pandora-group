<?php
/**
 * Shortcodes Reference Page
 *
 * Registers an admin page under Appearance → Pandora that lists all
 * shortcodes available in this theme with copy-to-clipboard support.
 *
 * @package Pandora_Group
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'pandora_register_shortcodes_reference_page' );

function pandora_register_shortcodes_reference_page() {
	add_theme_page(
		__( 'Pandora Shortcodes', 'pandora-group' ),
		__( 'Pandora', 'pandora-group' ),
		'edit_posts',
		'pandora-shortcodes',
		'pandora_render_shortcodes_reference_page'
	);
}

function pandora_render_shortcodes_reference_page() {
	$shortcodes = array(

		array(
			'title'       => 'Posts Grid',
			'tag'         => 'pandora_posts_grid',
			'description' => 'Renders a filterable, AJAX-paginated post grid with category filter tabs.',
			'example'     => '[pandora_posts_grid per_page="9" post_type="post" categories="4,9"]',
			'attrs'       => array(
				array( 'name' => 'per_page',  'default' => '9',     'desc' => 'Number of posts to show per page.' ),
				array( 'name' => 'post_type', 'default' => 'post',  'desc' => 'WordPress post type slug.' ),
				array( 'name' => 'categories','default' => '(all)', 'desc' => 'Comma-separated category IDs. Leave empty to include all.' ),
				array( 'name' => 'id',        'default' => '(none)','desc' => 'Grid ID for remote tab connection via <code>[pandora_posts_tabs]</code>.' ),
			),
		),

		array(
			'title'       => 'Posts Tabs (Remote)',
			'tag'         => 'pandora_posts_tabs',
			'description' => 'Outputs standalone filter tabs that control a <code>[pandora_posts_grid id="…"]</code> placed anywhere else on the same page.',
			'example'     => '[pandora_posts_tabs for="blog" categories="4,9" post_type="post"]',
			'attrs'       => array(
				array( 'name' => 'for',       'default' => '(required)', 'desc' => 'Must match the <code>id</code> attribute of the target grid.' ),
				array( 'name' => 'categories','default' => '(all)',      'desc' => 'Comma-separated category IDs shown as filter tabs.' ),
				array( 'name' => 'post_type', 'default' => 'post',       'desc' => 'Post type slug — must match the target grid.' ),
			),
		),

		array(
			'title'       => 'Team Grid',
			'tag'         => 'pandora_team_grid',
			'description' => 'Displays Team members in a responsive grid with photo, name, designation, bio, and LinkedIn link.',
			'example'     => '[pandora_team_grid columns="3" order="ASC" orderby="menu_order"]',
			'attrs'       => array(
				array( 'name' => 'columns', 'default' => '3',          'desc' => 'Number of columns on desktop. Tablet auto-drops to 2, mobile to 1.' ),
				array( 'name' => 'members', 'default' => '(all)',       'desc' => 'Comma-separated Team member post IDs. Leave empty to show all.' ),
				array( 'name' => 'order',   'default' => 'ASC',         'desc' => '<code>ASC</code> or <code>DESC</code>.' ),
				array( 'name' => 'orderby', 'default' => 'menu_order',  'desc' => 'Any WP orderby value: <code>menu_order</code>, <code>title</code>, <code>date</code>, <code>rand</code>.' ),
			),
		),

	);
	?>
	<div class="wrap psr-wrap">

		<style>
			.psr-wrap { max-width: 960px; }
			.psr-header {
				display: flex;
				align-items: center;
				gap: 14px;
				margin: 24px 0 32px;
			}
			.psr-header__logo {
				width: 30px;
				height: 30px;
				display: flex;
				align-items: center;
				justify-content: center;
				flex-shrink: 0;
			}
			.psr-header__logo svg { display: block; }
			.psr-header__text h1 {
				margin: 0;
				font-size: 22px;
				font-weight: 600;
				line-height: 1.2;
				color: #1d2327;
			}
			.psr-header__text p {
				margin: 4px 0 0;
				color: #646970;
				font-size: 13px;
			}

			.psr-grid {
				display: flex;
				flex-direction: column;
				gap: 24px;
			}

			.psr-card {
				background: #fff;
				border: 1px solid #e2e4e7;
				border-radius: 12px;
				overflow: hidden;
			}
			.psr-card__head {
				padding: 20px 24px 16px;
				border-bottom: 1px solid #f0f0f0;
			}
			.psr-card__title-row {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 6px;
			}
			.psr-card__title {
				font-size: 16px;
				font-weight: 600;
				color: #1d2327;
				margin: 0;
			}
			.psr-card__badge {
				font-size: 11px;
				font-weight: 500;
				background: #f0f0f1;
				color: #646970;
				padding: 2px 8px;
				border-radius: 20px;
				font-family: monospace;
				letter-spacing: 0;
			}
			.psr-card__desc {
				margin: 0;
				color: #646970;
				font-size: 13px;
				line-height: 1.6;
			}
			.psr-card__desc code {
				background: #f6f7f7;
				padding: 1px 5px;
				border-radius: 3px;
				font-size: 12px;
				color: #2c3338;
			}

			.psr-card__body { padding: 20px 24px; }

			.psr-example-label {
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: .06em;
				color: #646970;
				margin: 0 0 8px;
			}
			.psr-example-row {
				display: flex;
				align-items: stretch;
				gap: 0;
				border: 1px solid #e2e4e7;
				border-radius: 8px;
				overflow: hidden;
				margin-bottom: 24px;
			}
			.psr-example-code {
				flex: 1;
				background: #f6f7f7;
				padding: 12px 16px;
				margin: 0;
				font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
				font-size: 13px;
				color: #2c3338;
				white-space: pre-wrap;
				word-break: break-all;
				border: none;
				line-height: 1.6;
			}
			.psr-copy-btn {
				flex-shrink: 0;
				padding: 0 16px;
				background: #fff;
				border: none;
				border-left: 1px solid #e2e4e7;
				cursor: pointer;
				font-size: 12px;
				font-weight: 500;
				color: #2271b1;
				display: flex;
				align-items: center;
				gap: 6px;
				transition: background .15s, color .15s;
				white-space: nowrap;
			}
			.psr-copy-btn:hover { background: #f0f6fc; }
			.psr-copy-btn.copied { color: #00a32a; }
			.psr-copy-btn svg { flex-shrink: 0; }

			.psr-attrs-label {
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: .06em;
				color: #646970;
				margin: 0 0 10px;
			}
			.psr-attrs {
				border: 1px solid #e2e4e7;
				border-radius: 8px;
				overflow: hidden;
			}
			.psr-attr {
				display: grid;
				grid-template-columns: 160px 100px 1fr;
				gap: 0;
				border-bottom: 1px solid #f0f0f0;
			}
			.psr-attr:last-child { border-bottom: none; }
			.psr-attr__name,
			.psr-attr__default,
			.psr-attr__desc {
				padding: 10px 14px;
				font-size: 13px;
				line-height: 1.5;
			}
			.psr-attr__name {
				font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
				font-size: 12px;
				color: #9333ea;
				background: #faf5ff;
				font-weight: 500;
				border-right: 1px solid #f0f0f0;
			}
			.psr-attr__default {
				font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
				font-size: 12px;
				color: #646970;
				background: #fafafa;
				border-right: 1px solid #f0f0f0;
			}
			.psr-attr__desc { color: #3c434a; }
			.psr-attr__desc code {
				background: #f6f7f7;
				padding: 1px 5px;
				border-radius: 3px;
				font-size: 12px;
				color: #2c3338;
			}

			.psr-attr-head {
				display: grid;
				grid-template-columns: 160px 100px 1fr;
				background: #f6f7f7;
				border-bottom: 1px solid #e2e4e7;
			}
			.psr-attr-head span {
				padding: 7px 14px;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: .06em;
				color: #646970;
			}
			.psr-attr-head span:not(:last-child) {
				border-right: 1px solid #e2e4e7;
			}

			.psr-footer {
				margin-top: 32px;
				padding: 16px 20px;
				background: #f6f7f7;
				border: 1px solid #e2e4e7;
				border-radius: 10px;
				font-size: 13px;
				color: #646970;
				line-height: 1.6;
			}
			.psr-footer strong { color: #1d2327; }
		</style>

		<div class="psr-header">
			<div class="psr-header__logo">
				<svg width="54" height="59" viewBox="0 0 54 59" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M26.728 4.89a2.44 2.44 0 0 0 2.43-2.445A2.44 2.44 0 0 0 26.728 0a2.44 2.44 0 0 0-2.43 2.445 2.44 2.44 0 0 0 2.43 2.445m12.149 7.825c1.878 0 3.402-1.533 3.402-3.423s-1.524-3.423-3.402-3.423-3.402 1.532-3.402 3.423c0 1.89 1.523 3.423 3.402 3.423m-7.29.978c0 2.701-2.175 4.89-4.86 4.89-2.683 0-4.859-2.189-4.859-4.89s2.176-4.89 4.86-4.89 4.86 2.19 4.86 4.89m-13.12 7.825c0 2.7-2.176 4.89-4.86 4.89s-4.86-2.19-4.86-4.89c0-2.701 2.176-4.89 4.86-4.89s4.86 2.189 4.86 4.89m26.242 0c0 2.7-2.176 4.89-4.86 4.89s-4.86-2.19-4.86-4.89c0-2.701 2.176-4.89 4.86-4.89s4.86 2.189 4.86 4.89m-11.177 7.825c0 3.78-3.046 6.846-6.804 6.846-3.757 0-6.803-3.065-6.803-6.846s3.046-6.847 6.803-6.847 6.804 3.065 6.804 6.847m6.317 12.714c2.684 0 4.86-2.189 4.86-4.89s-2.176-4.89-4.86-4.89-4.86 2.19-4.86 4.89c0 2.701 2.176 4.89 4.86 4.89m-26.242 0c2.684 0 4.86-2.189 4.86-4.89s-2.176-4.89-4.86-4.89-4.86 2.19-4.86 4.89c0 2.701 2.176 4.89 4.86 4.89m13.12 7.825c2.685 0 4.86-2.19 4.86-4.89 0-2.701-2.175-4.89-4.86-4.89-2.683 0-4.859 2.189-4.859 4.89s2.176 4.89 4.86 4.89m15.552-.489c0 1.89-1.524 3.423-3.402 3.423s-3.402-1.532-3.402-3.423c0-1.89 1.523-3.423 3.402-3.423 1.878 0 3.402 1.533 3.402 3.423M17.98 9.292c0 1.89-1.523 3.423-3.401 3.423-1.879 0-3.402-1.533-3.402-3.423s1.523-3.423 3.402-3.423c1.878 0 3.401 1.532 3.401 3.423m-3.4 43.524c1.878 0 3.401-1.532 3.401-3.423 0-1.89-1.523-3.423-3.401-3.423-1.879 0-3.402 1.533-3.402 3.423s1.523 3.423 3.402 3.423M6.803 29.344c0 1.89-1.523 3.423-3.401 3.423C1.523 32.766 0 31.233 0 29.343s1.523-3.424 3.402-3.424c1.878 0 3.401 1.533 3.401 3.424m43.251 3.423c1.879 0 3.402-1.533 3.402-3.423s-1.523-3.424-3.402-3.424-3.402 1.533-3.402 3.424c0 1.89 1.523 3.423 3.402 3.423M29.158 56.24a2.44 2.44 0 0 1-2.43 2.445 2.44 2.44 0 0 1-2.43-2.445 2.44 2.44 0 0 1 2.43-2.446 2.44 2.44 0 0 1 2.43 2.446m20.896-10.76a2.44 2.44 0 0 0 2.43-2.444 2.44 2.44 0 0 0-2.43-2.446 2.44 2.44 0 0 0-2.43 2.446 2.44 2.44 0 0 0 2.43 2.445m2.43-29.83a2.44 2.44 0 0 1-2.43 2.445 2.44 2.44 0 0 1-2.43-2.446 2.44 2.44 0 0 1 2.43-2.445 2.44 2.44 0 0 1 2.43 2.445M3.402 45.48a2.437 2.437 0 0 0 2.43-2.444 2.44 2.44 0 0 0-2.43-2.446 2.44 2.44 0 0 0-2.43 2.446 2.44 2.44 0 0 0 2.43 2.445m2.43-29.83a2.437 2.437 0 0 1-2.43 2.445 2.44 2.44 0 0 1-2.43-2.446 2.44 2.44 0 0 1 2.43-2.445 2.44 2.44 0 0 1 2.43 2.445" fill="url(#a)"/><defs><linearGradient id="a" x1="0" y1="0" x2="60.837" y2="9.047" gradientUnits="userSpaceOnUse"><stop stop-color="#d800fd"/><stop offset="1" stop-color="#1c00fd"/></linearGradient></defs></svg>
			</div>
			<div class="psr-header__text">
				<h1><?php esc_html_e( 'Pandora Shortcodes', 'pandora-group' ); ?></h1>
				<p><?php esc_html_e( 'All shortcodes available in this theme. Click Copy to grab the code.', 'pandora-group' ); ?></p>
			</div>
		</div>

		<div class="psr-grid">
			<?php foreach ( $shortcodes as $sc ) : ?>
			<div class="psr-card">
				<div class="psr-card__head">
					<div class="psr-card__title-row">
						<h2 class="psr-card__title"><?php echo esc_html( $sc['title'] ); ?></h2>
						<span class="psr-card__badge"><?php echo esc_html( '[' . $sc['tag'] . ']' ); ?></span>
					</div>
					<p class="psr-card__desc"><?php echo wp_kses( $sc['description'], array( 'code' => array() ) ); ?></p>
				</div>

				<div class="psr-card__body">
					<p class="psr-example-label"><?php esc_html_e( 'Example', 'pandora-group' ); ?></p>
					<div class="psr-example-row">
						<pre class="psr-example-code"><?php echo esc_html( $sc['example'] ); ?></pre>
						<button
							type="button"
							class="psr-copy-btn"
							data-code="<?php echo esc_attr( $sc['example'] ); ?>"
						>
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
								<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
							</svg>
							<?php esc_html_e( 'Copy', 'pandora-group' ); ?>
						</button>
					</div>

					<?php if ( ! empty( $sc['attrs'] ) ) : ?>
					<p class="psr-attrs-label"><?php esc_html_e( 'Attributes', 'pandora-group' ); ?></p>
					<div class="psr-attrs">
						<div class="psr-attr-head">
							<span><?php esc_html_e( 'Attribute', 'pandora-group' ); ?></span>
							<span><?php esc_html_e( 'Default', 'pandora-group' ); ?></span>
							<span><?php esc_html_e( 'Description', 'pandora-group' ); ?></span>
						</div>
						<?php foreach ( $sc['attrs'] as $attr ) : ?>
						<div class="psr-attr">
							<div class="psr-attr__name"><?php echo esc_html( $attr['name'] ); ?></div>
							<div class="psr-attr__default"><?php echo esc_html( $attr['default'] ); ?></div>
							<div class="psr-attr__desc"><?php echo wp_kses( $attr['desc'], array( 'code' => array() ) ); ?></div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="psr-footer">
			<strong><?php esc_html_e( 'Tip:', 'pandora-group' ); ?></strong>
			<?php esc_html_e( 'Shortcodes can be placed in any post, page, or widget that supports shortcodes. In the block editor, use the Shortcode block.', 'pandora-group' ); ?>
		</div>

	</div>

	<script>
	( function () {
		document.querySelectorAll( '.psr-copy-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var code = btn.getAttribute( 'data-code' );
				var label = btn.querySelector( 'span' ) || btn;

				function markCopied() {
					btn.classList.add( 'copied' );
					btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Copied!';
					setTimeout( function () {
						btn.classList.remove( 'copied' );
						btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Copy';
					}, 2000 );
				}

				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( code ).then( markCopied ).catch( fallback );
				} else {
					fallback();
				}

				function fallback() {
					var ta = document.createElement( 'textarea' );
					ta.value = code;
					ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
					document.body.appendChild( ta );
					ta.focus();
					ta.select();
					try { document.execCommand( 'copy' ); markCopied(); } catch (e) {}
					document.body.removeChild( ta );
				}
			} );
		} );
	} )();
	</script>
	<?php
}
