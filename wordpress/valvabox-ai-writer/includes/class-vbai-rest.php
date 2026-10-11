<?php
/**
 * REST routes: POST /vbai/v1/generate and POST /vbai/v1/test.
 */

defined( 'ABSPATH' ) || exit;

class VBAI_REST {

	const NS = 'vbai/v1';

	/** Characters of text/context sent to the model; keeps requests inside free-tier limits. */
	const MAX_TEXT    = 12000;
	const MAX_CONTEXT = 8000;

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			self::NS,
			'/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'generate' ),
				'permission_callback' => function () {
					return current_user_can( apply_filters( 'vbai_capability', 'edit_posts' ) );
				},
				'args'                => array(
					'action'   => array( 'type' => 'string', 'required' => true, 'enum' => array_keys( VBAI_Prompts::actions() ) ),
					'text'     => array( 'type' => 'string', 'default' => '' ),
					'context'  => array( 'type' => 'string', 'default' => '' ),
					'title'    => array( 'type' => 'string', 'default' => '' ),
					'tone'     => array( 'type' => 'string', 'default' => '' ),
					'language' => array( 'type' => 'string', 'default' => '' ),
					'length'   => array( 'type' => 'string', 'default' => 'medium', 'enum' => array_keys( VBAI_Prompts::lengths() ) ),
					'mode'     => array( 'type' => 'string', 'default' => 'markdown', 'enum' => array( 'markdown', 'inline' ) ),
					'fresh'    => array( 'type' => 'boolean', 'default' => false ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/test',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'test' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'provider' => array( 'type' => 'string', 'required' => true, 'enum' => array_keys( VBAI_Providers::all() ) ),
				),
			)
		);
	}

	private static function plain( $html, $limit ) {
		$text = wp_strip_all_tags( preg_replace( '/<!--.*?-->/s', '', (string) $html ) );
		$text = trim( preg_replace( "/\n{3,}/", "\n\n", html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );
		return mb_substr( $text, 0, $limit );
	}

	public static function generate( WP_REST_Request $request ) {
		$settings = VBAI_Settings::get();
		$action   = $request['action'];
		$mode     = $request['mode'];
		$actions  = VBAI_Prompts::actions();

		// Inline rewrites keep their inline HTML; everything else is sent as plain text.
		$text    = 'inline' === $mode ? mb_substr( wp_kses( (string) $request['text'], VBAI_Prompts::INLINE_TAGS ), 0, self::MAX_TEXT ) : self::plain( $request['text'], self::MAX_TEXT );
		$context = self::plain( $request['context'], self::MAX_CONTEXT );

		if ( ! $actions[ $action ]['needs_text'] && '' === $context && '' !== $text ) {
			$context = mb_substr( $text, 0, self::MAX_CONTEXT );
		}
		if ( $actions[ $action ]['needs_text'] && '' === trim( wp_strip_all_tags( $text ) ) ) {
			return new WP_Error( 'vbai_empty', __( 'Add some text or a topic first.', 'valvabox-ai-writer' ), array( 'status' => 400 ) );
		}
		if ( ! $actions[ $action ]['needs_text'] && '' === $context && '' === trim( $request['title'] ) ) {
			return new WP_Error( 'vbai_empty', __( 'Write a title or some content first.', 'valvabox-ai-writer' ), array( 'status' => 400 ) );
		}

		$tone = in_array( $request['tone'], VBAI_Prompts::tones(), true ) ? $request['tone'] : $settings['tone'];
		$lang = '' !== trim( $request['language'] ) ? sanitize_text_field( $request['language'] ) : $settings['language'];

		list( $system, $user ) = VBAI_Prompts::build(
			array(
				'action'   => $action,
				'text'     => $text,
				'context'  => $context,
				'title'    => sanitize_text_field( $request['title'] ),
				'tone'     => $tone,
				'language' => $lang,
				'length'   => $request['length'],
				'mode'     => $mode,
			)
		);

		$cache_key = 'vbai_' . md5( wp_json_encode( array( $settings['provider'], $settings['providers'], $settings['temperature'], $system, $user ) ) );
		if ( ! $request['fresh'] && $settings['cache_hours'] > 0 ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				$cached['cached'] = true;
				return rest_ensure_response( $cached );
			}
		}

		$limited = self::rate_limited( $settings );
		if ( $limited ) {
			return $limited;
		}

		$result = VBAI_Providers::generate( $system, $user, $settings );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = array(
			'text'     => VBAI_Prompts::clean( $result['text'], $action, $mode ),
			'provider' => VBAI_Providers::all()[ $result['provider'] ]['label'],
			'format'   => $actions[ $action ]['format'],
			'cached'   => false,
		);
		if ( '' === $response['text'] ) {
			return new WP_Error( 'vbai_empty_reply', __( 'The AI returned nothing. Try again.', 'valvabox-ai-writer' ), array( 'status' => 502 ) );
		}

		if ( $settings['cache_hours'] > 0 ) {
			set_transient( $cache_key, $response, $settings['cache_hours'] * HOUR_IN_SECONDS );
		}
		self::count();

		return rest_ensure_response( $response );
	}

	public static function test( WP_REST_Request $request ) {
		$result = VBAI_Providers::generate(
			'Reply with exactly the word: OK',
			'Say OK.',
			VBAI_Settings::get(),
			$request['provider']
		);
		if ( is_wp_error( $result ) ) {
			return rest_ensure_response( array( 'ok' => false, 'message' => $result->get_error_message() ) );
		}
		return rest_ensure_response( array( 'ok' => true, 'reply' => mb_substr( wp_strip_all_tags( $result['text'] ), 0, 60 ) ) );
	}

	/** @return WP_Error|false */
	private static function rate_limited( array $settings ) {
		$limit = (int) $settings['rate_limit'];
		if ( $limit <= 0 || current_user_can( 'manage_options' ) ) {
			return false;
		}
		$key   = 'vbai_rl_' . get_current_user_id() . '_' . gmdate( 'YmdH' );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return new WP_Error(
				'vbai_rate',
				/* translators: %d: requests per hour */
				sprintf( __( 'You have used your %d AI requests for this hour. Please try again later.', 'valvabox-ai-writer' ), $limit ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return false;
	}

	private static function count() {
		$usage = get_option( 'vbai_usage', array() );
		$usage = is_array( $usage ) ? $usage : array();
		$day   = gmdate( 'Y-m-d' );

		$usage[ $day ]  = isset( $usage[ $day ] ) ? $usage[ $day ] + 1 : 1;
		$usage['total'] = isset( $usage['total'] ) ? $usage['total'] + 1 : 1;

		// Keep 30 days of daily counts.
		$days = array_filter( array_keys( $usage ), function ( $k ) {
			return 'total' !== $k;
		} );
		sort( $days );
		foreach ( array_slice( $days, 0, max( 0, count( $days ) - 30 ) ) as $old ) {
			unset( $usage[ $old ] );
		}
		update_option( 'vbai_usage', $usage, false );
	}
}
