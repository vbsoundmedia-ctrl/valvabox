<?php
/**
 * Block editor sidebar and block toolbar button.
 */

defined( 'ABSPATH' ) || exit;

class VBAI_Editor {

	public static function init() {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		if ( ! current_user_can( apply_filters( 'vbai_capability', 'edit_posts' ) ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'post' !== $screen->base ) {
			return; // Post/page editor only, not the site or widget editors.
		}

		wp_enqueue_script(
			'vbai-editor',
			VBAI_URL . 'assets/editor.js',
			array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks', 'wp-block-editor', 'wp-hooks', 'wp-compose', 'wp-api-fetch', 'wp-i18n', 'wp-notices', 'wp-editor', 'wp-edit-post' ),
			VBAI_VERSION,
			true
		);
		wp_enqueue_style( 'vbai-editor', VBAI_URL . 'assets/editor.css', array(), VBAI_VERSION );

		$s       = VBAI_Settings::get();
		$actions = array();
		foreach ( VBAI_Prompts::actions() as $id => $a ) {
			$actions[ $id ] = $a['label'];
		}
		wp_localize_script(
			'vbai-editor',
			'vbaiEditor',
			array(
				'actions'     => $actions,
				'tones'       => VBAI_Prompts::tones(),
				'tone'        => $s['tone'],
				'language'    => $s['language'],
				'settingsUrl' => current_user_can( 'manage_options' ) ? admin_url( 'options-general.php?page=vbai' ) : '',
			)
		);
		wp_set_script_translations( 'vbai-editor', 'valvabox-ai-writer' );
	}
}
