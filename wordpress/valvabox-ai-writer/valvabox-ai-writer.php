<?php
/**
 * Plugin Name:       Valvabox AI Writer
 * Plugin URI:        https://valvabox.net
 * Description:       Free AI writing assistant for the block editor. Draft, rewrite, fix grammar, change tone, summarise, translate and write SEO titles and meta descriptions using free AI providers (no paid API needed).
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Valvabox
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       valvabox-ai-writer
 */

defined( 'ABSPATH' ) || exit;

define( 'VBAI_VERSION', '1.0.0' );
define( 'VBAI_FILE', __FILE__ );
define( 'VBAI_DIR', plugin_dir_path( __FILE__ ) );
define( 'VBAI_URL', plugin_dir_url( __FILE__ ) );

require_once VBAI_DIR . 'includes/class-vbai-prompts.php';
require_once VBAI_DIR . 'includes/class-vbai-providers.php';
require_once VBAI_DIR . 'includes/class-vbai-settings.php';
require_once VBAI_DIR . 'includes/class-vbai-rest.php';
require_once VBAI_DIR . 'includes/class-vbai-editor.php';

add_action( 'plugins_loaded', function () {
	VBAI_Settings::init();
	VBAI_REST::init();
	VBAI_Editor::init();
} );

register_activation_hook( __FILE__, function () {
	if ( false === get_option( VBAI_Settings::OPTION ) ) {
		add_option( VBAI_Settings::OPTION, VBAI_Settings::defaults(), '', false );
	}
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=vbai' ) ) . '">' . esc_html__( 'Settings', 'valvabox-ai-writer' ) . '</a>' );
	return $links;
} );
