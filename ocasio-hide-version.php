<?php
/**
 * Plugin Name: Ocasio Hide Version
 * Plugin URI:  https://kevinocasio.com/wordpress-plugins/ocasio-hide-version/
 * Description: Removes the WordPress version number from your website source code, feeds, and asset URLs for enhanced security.
 * Version:     1.0.0
 * Author:      Kevin Ocasio
 * Author URI:  https://kevinocasio.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ocasio-hide-version
 */

if (!defined('ABSPATH')) {
	exit;
}

define('OCASIO_HV_VERSION', '1.0.0');
define('OCASIO_HV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OCASIO_HV_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Helper: Smart Brand URL Resolver (Keeps links local on .local, points to live domain on production)
 */
function ocasio_hv_brand_url($path = '/wordpress-plugins/') {
	if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.local') !== false) {
		return home_url($path);
	}
	return 'https://kevinocasio.com' . $path;
}

/**
 * 1. Activation: Seed Default Option & Migrate Legacy Settings
 */
function ocasio_hv_activate() {
	if (get_option('ocasio_hv_enabled') === false) {
		$legacy = get_option('ko_hv_enabled', 1);
		update_option('ocasio_hv_enabled', (int) $legacy);
	}
}
register_activation_hook(__FILE__, 'ocasio_hv_activate');

/**
 * 2. Core Logic: Version Hiding & Asset Stripping
 */
function ocasio_hv_remove_wp_ver_css_js($src) {
	if (strpos($src, 'ver=') !== false) {
		$src = remove_query_arg('ver', $src);
	}
	return $src;
}

function ocasio_hv_apply_security() {
	$enabled = get_option('ocasio_hv_enabled');
	if ($enabled === false) {
		$enabled = get_option('ko_hv_enabled', 1);
	}

	if ((int) $enabled !== 1) {
		return;
	}

	// 1. Remove Generator Meta Tag from HTML <head>
	remove_action('wp_head', 'wp_generator');
	add_filter('the_generator', '__return_empty_string', 9999);
	add_filter('get_the_generator_html', '__return_empty_string', 9999);
	add_filter('get_the_generator_xhtml', '__return_empty_string', 9999);

	// 2. Remove Version from Feeds
	$feed_hooks = array(
		'rss2_head',
		'commentsrss2_head',
		'rss_head',
		'rdf_header',
		'atom_head',
		'comments_atom_head',
		'opml_head',
		'app_head',
	);
	foreach ($feed_hooks as $hook) {
		remove_action($hook, 'the_generator');
	}

	// 3. Remove Version from Styles, Scripts, and Script Modules
	add_filter('style_loader_src', 'ocasio_hv_remove_wp_ver_css_js', 9999);
	add_filter('script_loader_src', 'ocasio_hv_remove_wp_ver_css_js', 9999);
	add_filter('script_module_loader_src', 'ocasio_hv_remove_wp_ver_css_js', 9999);
}
add_action('init', 'ocasio_hv_apply_security');

/**
 * 3. Register Settings
 */
function ocasio_hv_register_settings() {
	register_setting('ocasio_hv_settings_group', 'ocasio_hv_enabled', array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 1,
	));
}
add_action('admin_init', 'ocasio_hv_register_settings');

/**
 * 4. Register Admin Menu under Ocasio Plugins (Position 65)
 */
function ocasio_hv_register_menu() {
	if (empty($GLOBALS['admin_page_hooks']['ocasio-plugins-main'])) {
		$icon_url = plugins_url('assets/favicon.svg', __FILE__);

		add_menu_page(
			'Ocasio Plugins',
			'Ocasio Plugins',
			'manage_options',
			'ocasio-plugins-main',
			'ocasio_plugins_suite_dashboard_html',
			$icon_url,
			65
		);

		add_submenu_page(
			'ocasio-plugins-main',
			'Ocasio Plugins',
			'Dashboard',
			'manage_options',
			'ocasio-plugins-main',
			'ocasio_plugins_suite_dashboard_html'
		);
	}

	add_submenu_page(
		'ocasio-plugins-main',
		'Ocasio Hide Version',
		'Hide Version',
		'manage_options',
		'ocasio-hide-version',
		'ocasio_hv_render_page'
	);
}
add_action('admin_menu', 'ocasio_hv_register_menu');

/**
 * 5. Action Link on Plugins Screen
 */
function ocasio_hv_action_links($links) {
	$settings_link = '<a href="' . esc_url(admin_url('admin.php?page=ocasio-hide-version')) . '">' . esc_html__('Settings', 'ocasio-hide-version') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ocasio_hv_action_links');

/**
 * 6. Conditional Admin Asset Enqueuing
 */
function ocasio_hv_admin_assets($hook) {
	wp_add_inline_style('common', '#adminmenu .toplevel_page_ocasio-plugins-main .wp-menu-image img { width:20px!important; height:20px!important; padding:5px 0 0 0!important; opacity:1!important; }');

	if (strpos($hook, 'ocasio-hide-version') !== false || strpos($hook, 'ocasio-plugins-main') !== false || $hook === 'toplevel_page_ocasio-plugins-main') {
		wp_enqueue_style('ocasio-hv-admin-css', plugins_url('assets/admin.css', __FILE__), array(), OCASIO_HV_VERSION);
		wp_enqueue_script('ocasio-hv-admin-js', plugins_url('assets/admin.js', __FILE__), array('jquery'), OCASIO_HV_VERSION, true);
		wp_localize_script('ocasio-hv-admin-js', 'ocasio_vars', array(
			'ajaxurl'     => admin_url('admin-ajax.php'),
			'suite_nonce' => wp_create_nonce('ocasio_suite_toggle_nonce'),
		));
		wp_localize_script('ocasio-hv-admin-js', 'ocasio_301_vars', array(
			'ajaxurl'     => admin_url('admin-ajax.php'),
			'suite_nonce' => wp_create_nonce('ocasio_suite_toggle_nonce'),
		));
	}
}
add_action('admin_enqueue_scripts', 'ocasio_hv_admin_assets');

/**
 * 7. Dedicated Settings Page (620px Centered Card)
 */
function ocasio_hv_render_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$is_enabled = get_option('ocasio_hv_enabled');
	if ($is_enabled === false) {
		$is_enabled = get_option('ko_hv_enabled', 1);
	}
	$is_enabled = ((int) $is_enabled === 1);

	$author_url = ocasio_hv_brand_url('/');
	$hub_url    = ocasio_hv_brand_url('/wordpress-plugins/');
	?>
	<div class="wrap ko-plugin-wrap">
		<div class="ko-plugin-container">

			<!-- Header (Zero KO: Red Bricolage + Crisp White Inter) -->
			<div class="ko-plugin-header">
				<h1 class="ko-plugin-header-title">
					<span class="ko-logo-ocasio">OCASIO</span>
					<span class="ko-title-text">HIDE VERSION</span>
				</h1>
			</div>

			<!-- Body Stage -->
			<div class="ko-plugin-body">
				<p class="ko-plugin-intro">Removes the WordPress version number from your website source code, RSS feeds, and asset URLs to protect against targeted exploits.</p>

				<form method="post" action="options.php">
					<?php settings_fields('ocasio_hv_settings_group'); ?>

					<div class="ko-setting-box">
						<!-- Row 1: Hide Version -->
						<div class="ko-setting-row">
							<div class="ko-setting-info">
								<strong>Hide WordPress Version</strong>
								<p>Removes generator meta tags and strips <code>?ver=</code> queries from styles and scripts.</p>
							</div>
							<label class="ko-switch">
								<input type="hidden" name="ocasio_hv_enabled" value="0">
								<input type="checkbox" name="ocasio_hv_enabled" value="1" <?php checked($is_enabled, true); ?>>
								<span class="ko-slider"></span>
							</label>
						</div>
					</div>

					<!-- Security Status Box -->
					<div class="ko-meta-section" style="margin-top:20px;">
						<?php if ($is_enabled): ?>
							<div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:12px 16px; border-radius:6px; font-size:13px; font-weight:500;">
								<strong style="color:#15803d;">Status: Protected</strong> &mdash; WordPress core version generator tags and script query strings are completely hidden.
							</div>
						<?php else: ?>
							<div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:6px; font-size:13px; font-weight:500;">
								<strong style="color:#b91c1c;">Status: Visible</strong> &mdash; WordPress core version is exposed in generator meta tags and asset URLs.
							</div>
						<?php endif; ?>
					</div>

					<div class="ko-submit-wrap">
						<?php
						$is_saved  = (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true');
						$btn_text  = $is_saved ? 'Settings Saved!' : 'Save Settings';
						$btn_class = 'ko-btn-submit' . ($is_saved ? ' ko-btn-saved' : '');
						?>
						<button type="submit" name="submit" id="ko-save-btn" class="<?php echo esc_attr($btn_class); ?>">
							<?php echo esc_html($btn_text); ?>
						</button>
					</div>
				</form>
			</div>

			<!-- Card Footer -->
			<div class="ko-plugin-footer">
				<p><a href="<?php echo esc_url($author_url); ?>" target="_blank" rel="noopener noreferrer">Kevin Ocasio</a> built this and other <a href="<?php echo esc_url($hub_url); ?>" target="_blank" rel="noopener noreferrer">WordPress plugins</a>.</p>
			</div>
		</div>
	</div>
	<?php
}

// -------------------------------------------------------------------------
// 8. MASTER OCASIO PLUGINS SUITE DASHBOARD CALLBACK (17-PLUGIN GRID)
// -------------------------------------------------------------------------

if (!function_exists('ocasio_plugins_suite_dashboard_html')) {
	function ocasio_plugins_suite_dashboard_html() {
		$all_plugins = array(
			'ocasio-admin-bar-hider' => array(
				'title'         => 'Admin Bar Hider',
				'desc'          => 'Hides the front-end WordPress admin bar for all users with a single toggle.',
				'file'          => 'ocasio-admin-bar-hider/ocasio-admin-bar-hider.php',
				'fallback_file' => 'ko-admin-bar-hider/ko-admin-bar-hider.php',
				'opt_toggle'    => 'ocasio_abh_enabled',
				'fallback_opt'  => 'ko_abh_enabled',
				'has_options'   => false,
			),
			'ocasio-admin-username-changer' => array(
				'title'         => 'Admin Username Changer',
				'desc'          => 'Safely changes the primary administrator username directly without touching phpMyAdmin.',
				'file'          => 'ocasio-admin-username-changer/ocasio-admin-username-changer.php',
				'fallback_file' => 'ko-admin-username-changer/ko-admin-username-changer.php',
				'fallback_slug' => 'ko-admin-username-changer',
				'opt_toggle'    => null,
				'has_options'   => true,
			),
			'ocasio-auto-copyright-year' => array(
				'title'         => 'Auto Copyright Year',
				'desc'          => 'Displays the current year, symbol, or translated text via simple shortcodes.',
				'file'          => 'ocasio-auto-copyright-year/ocasio-auto-copyright-year.php',
				'fallback_file' => 'ko-auto-copyright-year/ko-auto-copyright-year.php',
				'fallback_slug' => 'ko-auto-copyright-year',
				'opt_toggle'    => null,
				'has_options'   => true,
			),
			'ocasio-clean-image-filenames' => array(
				'title'         => 'Clean Image Filenames',
				'desc'          => 'Sanitizes uploaded media filenames into clean, lowercase, URL-friendly slugs.',
				'file'          => 'ocasio-clean-image-filenames/ocasio-clean-image-filenames.php',
				'fallback_file' => 'ko-clean-image-filenames/ko-clean-image-filenames.php',
				'opt_toggle'    => 'ocasio_cif_enabled',
				'fallback_opt'  => 'ko_cif_enabled',
				'has_options'   => false,
			),
			'ocasio-comment-link-remover' => array(
				'title'         => 'Comment Link Remover',
				'desc'          => 'Strips hyperlinked website URLs from author comments to eliminate backlink spam.',
				'file'          => 'ocasio-comment-link-remover/ocasio-comment-link-remover.php',
				'fallback_file' => 'ko-comment-link-remover/ko-comment-link-remover.php',
				'opt_toggle'    => 'ocasio_clr_enabled',
				'fallback_opt'  => 'ko_clr_enabled',
				'has_options'   => false,
			),
			'ocasio-disable-comments-globally' => array(
				'title'         => 'Disable Comments Globally',
				'desc'          => 'Closes comments and trackbacks across the entire site, posts, and media.',
				'file'          => 'ocasio-disable-comments-globally/ocasio-disable-comments-globally.php',
				'fallback_file' => 'ko-disable-comments-globally/ko-disable-comments-globally.php',
				'opt_toggle'    => 'ocasio_dcg_enabled',
				'fallback_opt'  => 'ko_dcg_enabled',
				'has_options'   => false,
			),
			'ocasio-disable-emojis' => array(
				'title'         => 'Disable Emojis',
				'desc'          => 'Removes WordPress core emoji scripts, styles, and DNS prefetch requests to boost page speed.',
				'file'          => 'ocasio-disable-emojis/ocasio-disable-emojis.php',
				'fallback_file' => 'ko-disable-emojis/ko-disable-emojis.php',
				'opt_toggle'    => 'ocasio_de_enabled',
				'fallback_opt'  => 'ko_de_enabled',
				'has_options'   => false,
			),
			'ocasio-disable-gutenberg' => array(
				'title'         => 'Disable Gutenberg',
				'desc'          => 'Restores the Classic Editor and removes block library CSS for a cleaner authoring workflow.',
				'file'          => 'ocasio-disable-gutenberg/ocasio-disable-gutenberg.php',
				'fallback_file' => 'ko-disable-gutenberg/ko-disable-gutenberg.php',
				'opt_toggle'    => 'ocasio_dg_enabled',
				'fallback_opt'  => 'ko_dg_enabled',
				'has_options'   => false,
			),
			'ocasio-disable-xml-rpc' => array(
				'title'         => 'Disable XML-RPC',
				'desc'          => 'Blocks XML-RPC API access to protect your site against brute-force attacks.',
				'file'          => 'ocasio-disable-xml-rpc/ocasio-disable-xml-rpc.php',
				'fallback_file' => 'ko-disable-xml-rpc/ko-disable-xml-rpc.php',
				'opt_toggle'    => 'ocasio_dxml_enabled',
				'fallback_opt'  => 'ko_dxml_enabled',
				'has_options'   => false,
			),
			'ocasio-duplicate-post-button' => array(
				'title'         => 'Duplicate Post Button',
				'desc'          => 'Adds a one-click Clone action to duplicate any post or page into a new draft.',
				'file'          => 'ocasio-duplicate-post-button/ocasio-duplicate-post-button.php',
				'fallback_file' => 'ko-duplicate-post-button/ko-duplicate-post-button.php',
				'fallback_slug' => 'ko-duplicate-post-button',
				'opt_toggle'    => null,
				'has_options'   => true,
			),
			'ocasio-estimated-reading-time' => array(
				'title'         => 'Estimated Reading Time',
				'desc'          => 'Calculates and displays article read time above post content automatically.',
				'file'          => 'ocasio-estimated-reading-time/ocasio-estimated-reading-time.php',
				'fallback_file' => 'ko-estimated-reading-time/ko-estimated-reading-time.php',
				'opt_toggle'    => 'ocasio_ert_enabled',
				'fallback_opt'  => 'ko_ert_enabled',
				'has_options'   => false,
			),
			'ocasio-external-links-new-tab' => array(
				'title'         => 'External Links New Tab',
				'desc'          => 'Forces external links to open in a new tab with target="_blank" and rel="noopener".',
				'file'          => 'ocasio-external-links-new-tab/ocasio-external-links-new-tab.php',
				'fallback_file' => 'ko-external-links-new-tab/ko-external-links-new-tab.php',
				'opt_toggle'    => 'ocasio_elnt_enabled',
				'fallback_opt'  => 'ko_elnt_enabled',
				'has_options'   => false,
			),
			'ocasio-hide-version' => array(
				'title'         => 'Hide Version',
				'desc'          => 'Removes WordPress version generator tags and script query strings for security.',
				'file'          => 'ocasio-hide-version/ocasio-hide-version.php',
				'fallback_file' => 'ko-hide-version/ko-hide-version.php',
				'opt_toggle'    => 'ocasio_hv_enabled',
				'fallback_opt'  => 'ko_hv_enabled',
				'has_options'   => false,
			),
			'ocasio-limit-login-attempts' => array(
				'title'         => 'Limit Login Attempts',
				'desc'          => 'Throttles repeated failed login attempts by IP address to block brute-force attacks.',
				'file'          => 'ocasio-limit-login-attempts/ocasio-limit-login-attempts.php',
				'fallback_file' => 'ko-limit-login-attempts/ko-limit-login-attempts.php',
				'fallback_slug' => 'ko-limit-login-attempts',
				'opt_toggle'    => null,
				'has_options'   => true,
			),
			'ocasio-show-current-template' => array(
				'title'         => 'Show Current Template',
				'desc'          => 'Displays active template hierarchy filename in the admin bar for developers.',
				'file'          => 'ocasio-show-current-template/ocasio-show-current-template.php',
				'fallback_file' => 'ko-show-current-template/ko-show-current-template.php',
				'opt_toggle'    => 'ocasio_sct_enabled',
				'fallback_opt'  => 'ko_sct_enabled',
				'has_options'   => false,
			),
			'ocasio-301-redirect-manager' => array(
				'title'         => '301 Redirect Manager',
				'desc'          => 'Manages 301 permanent redirects and fixes broken links cleanly inside WordPress.',
				'file'          => 'ocasio-301-redirect-manager/ocasio-301-redirect-manager.php',
				'fallback_file' => 'ko-simple-301-redirects/ko-simple-301-redirects.php',
				'fallback_slug' => 'ko-simple-301-redirects',
				'opt_toggle'    => null,
				'has_options'   => true,
			),
			'ocasio-simple-maintenance-mode' => array(
				'title'         => 'Simple Maintenance Mode',
				'desc'          => 'Displays a clean splash page to visitors while admins work on the site.',
				'file'          => 'ocasio-simple-maintenance-mode/ocasio-simple-maintenance-mode.php',
				'fallback_file' => 'ko-simple-maintenance-mode/ko-simple-maintenance-mode.php',
				'fallback_slug' => 'ko-simple-maintenance-mode',
				'opt_toggle'    => null,
				'has_options'   => true,
			),
		);

		if (!function_exists('is_plugin_active')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();
		$active_count      = 0;

		foreach ($all_plugins as $slug => $data) {
			$active_file = $data['file'];
			if (!is_plugin_active($active_file) && !empty($data['fallback_file']) && is_plugin_active($data['fallback_file'])) {
				$active_file = $data['fallback_file'];
			}
			if (is_plugin_active($active_file)) {
				$active_count++;
			}
		}

		$author_url = 'https://kevinocasio.com/';
		$hub_url    = 'https://kevinocasio.com/wordpress-plugins/';
		?>
		<div class="wrap ko-dash-wrap">
			<div class="ko-dash-hero">
				<div class="ko-dash-hero-left">
					<h1>
						<a href="<?php echo esc_url($hub_url); ?>" target="_blank" rel="noopener noreferrer" class="ko-dash-logo">
							<span class="ko-logo-ocasio">OCASIO</span>
							<span class="ko-logo-suite">PLUGINS SUITE</span>
						</a>
					</h1>
				</div>
				<div class="ko-dash-hero-right">
					<span class="ko-dash-count-pill"><?php echo esc_html($active_count); ?> of 17 Active</span>
				</div>
			</div>

			<div class="ko-dash-grid">
				<?php
				foreach ($all_plugins as $slug => $data):
					$active_file = $data['file'];
					if (!is_plugin_active($active_file) && !empty($data['fallback_file']) && is_plugin_active($data['fallback_file'])) {
						$active_file = $data['fallback_file'];
					}
					$is_installed = isset($installed_plugins[$active_file]) || isset($installed_plugins[$data['file']]) || (!empty($data['fallback_file']) && isset($installed_plugins[$data['fallback_file']]));
					$is_active    = is_plugin_active($active_file);
					$is_fallback  = ($active_file !== $data['file'] && !empty($data['fallback_file']));
					$page_slug    = ($is_fallback && !empty($data['fallback_slug'])) ? $data['fallback_slug'] : $slug;
					$settings_url = admin_url('admin.php?page=' . $page_slug);
					$activate_url = wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' . urlencode($data['file'])), 'activate-plugin_' . $data['file']);
					?>
					<div class="ko-dash-card">
						<div class="ko-dash-card-header">
							<h3 class="ko-dash-card-title"><?php echo esc_html($data['title']); ?></h3>
							<?php if ($is_active): ?>
								<?php if (!empty($data['opt_toggle'])):
									$opt_key      = (!empty($data['fallback_opt']) && get_option($data['opt_toggle'], null) === null) ? $data['fallback_opt'] : $data['opt_toggle'];
									$toggle_state = (int) get_option($opt_key, 1);
									$b_class      = ($toggle_state === 1) ? 'badge-active' : 'badge-paused';
									$b_label      = ($toggle_state === 1) ? 'Active' : 'Paused';
									?>
									<span class="ko-dash-badge <?php echo esc_attr($b_class); ?>" id="badge-<?php echo esc_attr($slug); ?>"><?php echo esc_html($b_label); ?></span>
								<?php else: ?>
									<span class="ko-dash-badge badge-active">Active</span>
								<?php endif; ?>
							<?php elseif ($is_installed): ?>
								<span class="ko-dash-badge badge-inactive">Inactive</span>
							<?php else: ?>
								<span class="ko-dash-badge badge-available">Available</span>
							<?php endif; ?>
						</div>

						<p class="ko-dash-card-desc"><?php echo esc_html($data['desc']); ?></p>

						<div class="ko-dash-card-footer">
							<?php if ($is_active): ?>
								<?php if ($data['has_options']): ?>
									<a href="<?php echo esc_url($settings_url); ?>" class="ko-dash-btn-primary">Manage Settings</a>
								<?php elseif (!empty($data['opt_toggle'])):
									$opt_key    = (!empty($data['fallback_opt']) && get_option($data['opt_toggle'], null) === null) ? $data['fallback_opt'] : $data['opt_toggle'];
									$toggle_val = (int) get_option($opt_key, 1);
									?>
									<div class="ko-dash-card-toggle-row">
										<span class="ko-dash-toggle-label">Active on Site</span>
										<div class="ko-dash-toggle-action">
											<span class="ko-dash-saved-pill" id="saved-<?php echo esc_attr($slug); ?>" style="display:none;">Saved</span>
											<label class="ko-switch">
												<input type="checkbox"
													class="ko-ajax-toggle"
													data-slug="<?php echo esc_attr($slug); ?>"
													data-option="<?php echo esc_attr($opt_key); ?>"
													value="1" <?php checked($toggle_val, 1); ?>>
												<span class="ko-slider"></span>
											</label>
										</div>
									</div>
								<?php else: ?>
									<span class="ko-dash-badge badge-active">Active</span>
								<?php endif; ?>
							<?php elseif ($is_installed): ?>
								<a href="<?php echo esc_url($activate_url); ?>" class="ko-dash-btn-activate">Activate</a>
							<?php else: ?>
								<a href="<?php echo esc_url($hub_url); ?>" target="_blank" rel="noopener noreferrer" class="ko-dash-btn-outline">Learn More</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="ko-dash-global-footer">
				<p>Built with pride by <a href="<?php echo esc_url($author_url); ?>" target="_blank" rel="noopener noreferrer">Kevin Ocasio</a>. Explore all <a href="<?php echo esc_url($hub_url); ?>" target="_blank" rel="noopener noreferrer">17 lightweight WordPress tools</a>.</p>
			</div>
		</div>
		<?php
	}
}

// -------------------------------------------------------------------------
// 5. MASTER AJAX HANDLER FOR DASHBOARD GRID IN-CARD TOGGLES
// -------------------------------------------------------------------------

if (!function_exists('ocasio_suite_save_toggle_ajax_callback')) {
	function ocasio_suite_save_toggle_ajax_callback() {
		check_ajax_referer('ocasio_suite_toggle_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized', 403);
		}

		$option_name  = isset($_POST['option_name']) ? sanitize_key($_POST['option_name']) : '';
		$option_value = isset($_POST['option_value']) ? absint($_POST['option_value']) : 0;

		$allowed_options = array(
			'ko_abh_enabled',
			'ko_cif_enabled',
			'ko_clr_enabled',
			'ko_dcg_enabled',
			'ko_de_enabled',
			'ko_dg_enabled',
			'ko_dxml_enabled',
			'ko_elnt_enabled',
			'ko_ert_enabled',
			'ko_hv_enabled',
			'ko_sct_enabled',
			'ocasio_abh_enabled',
			'ocasio_cif_enabled',
			'ocasio_clr_enabled',
			'ocasio_dcg_enabled',
			'ocasio_de_enabled',
			'ocasio_dg_enabled',
			'ocasio_dxml_enabled',
			'ocasio_elnt_enabled',
			'ocasio_ert_enabled',
			'ocasio_hv_enabled',
			'ocasio_sct_enabled',
		);

		if (in_array($option_name, $allowed_options, true)) {
			update_option($option_name, $option_value);
			wp_send_json_success();
		}

		wp_send_json_error('Invalid option key');
	}
	add_action('wp_ajax_ocasio_suite_save_toggle', 'ocasio_suite_save_toggle_ajax_callback');
}
