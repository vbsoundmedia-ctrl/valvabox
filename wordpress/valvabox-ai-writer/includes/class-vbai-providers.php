<?php
/**
 * Free AI providers and the HTTP calls to them.
 */

defined( 'ABSPATH' ) || exit;

class VBAI_Providers {

	/**
	 * Every provider here can be used at no cost.
	 * "openai" = OpenAI-compatible /chat/completions, "gemini" = Google Generative Language API.
	 */
	public static function all() {
		return array(
			'pollinations' => array(
				'label'      => 'Pollinations.ai',
				'note'       => __( 'Free, no sign-up or key. Best for getting started; may be slower and rate-limited at busy times.', 'valvabox-ai-writer' ),
				'type'       => 'openai',
				'base'       => 'https://text.pollinations.ai/openai',
				'model'      => 'openai',
				'needs_key'  => false,
				'key_url'    => 'https://auth.pollinations.ai',
				'key_label'  => __( 'Token (optional, raises limits)', 'valvabox-ai-writer' ),
				'full_url'   => true,
			),
			'gemini'       => array(
				'label'     => 'Google Gemini',
				'note'      => __( 'Free tier with a Google account. High quality and fast; recommended.', 'valvabox-ai-writer' ),
				'type'      => 'gemini',
				'base'      => 'https://generativelanguage.googleapis.com/v1beta',
				'model'     => 'gemini-2.5-flash',
				'needs_key' => true,
				'key_url'   => 'https://aistudio.google.com/apikey',
				'key_label' => __( 'API key', 'valvabox-ai-writer' ),
			),
			'groq'         => array(
				'label'     => 'Groq',
				'note'      => __( 'Free tier, very fast open models (Llama, Qwen, GPT-OSS).', 'valvabox-ai-writer' ),
				'type'      => 'openai',
				'base'      => 'https://api.groq.com/openai/v1',
				'model'     => 'llama-3.3-70b-versatile',
				'needs_key' => true,
				'key_url'   => 'https://console.groq.com/keys',
				'key_label' => __( 'API key', 'valvabox-ai-writer' ),
			),
			'openrouter'   => array(
				'label'     => 'OpenRouter (free models)',
				'note'      => __( 'One key for dozens of models; any model ending in ":free" costs nothing.', 'valvabox-ai-writer' ),
				'type'      => 'openai',
				'base'      => 'https://openrouter.ai/api/v1',
				'model'     => 'meta-llama/llama-3.3-70b-instruct:free',
				'needs_key' => true,
				'key_url'   => 'https://openrouter.ai/keys',
				'key_label' => __( 'API key', 'valvabox-ai-writer' ),
			),
			'ollama'       => array(
				'label'     => __( 'Ollama / self-hosted', 'valvabox-ai-writer' ),
				'note'      => __( 'Run models on your own server or PC: unlimited and private. Works with any OpenAI-compatible server (Ollama, LM Studio, LocalAI).', 'valvabox-ai-writer' ),
				'type'      => 'openai',
				'base'      => 'http://localhost:11434/v1',
				'model'     => 'llama3.2',
				'needs_key' => false,
				'key_url'   => 'https://ollama.com/download',
				'key_label' => __( 'API key (optional)', 'valvabox-ai-writer' ),
			),
		);
	}

	/**
	 * Providers ready to use with the current settings, primary first.
	 */
	public static function chain( array $settings ) {
		$all     = self::all();
		$primary = isset( $all[ $settings['provider'] ] ) ? $settings['provider'] : 'pollinations';
		$order   = array( $primary );

		if ( ! empty( $settings['fallback'] ) ) {
			foreach ( array_keys( $all ) as $id ) {
				// Only fall back to providers the site owner has set up; Pollinations needs no setup but is last resort.
				if ( $id !== $primary && 'ollama' !== $id && ( 'pollinations' === $id || '' !== self::conf( $settings, $id, 'key' ) ) ) {
					$order[] = $id;
				}
			}
			// Keep Pollinations as the final fallback.
			if ( 'pollinations' !== $primary ) {
				$order = array_values( array_diff( $order, array( 'pollinations' ) ) );
				$order[] = 'pollinations';
			}
		}
		return $order;
	}

	/** Read a provider setting, falling back to the provider's default. */
	public static function conf( array $settings, $id, $field ) {
		$all   = self::all();
		$value = isset( $settings['providers'][ $id ][ $field ] ) ? trim( (string) $settings['providers'][ $id ][ $field ] ) : '';
		if ( '' === $value && 'key' !== $field && isset( $all[ $id ][ $field ] ) ) {
			$value = $all[ $id ][ $field ];
		}
		return $value;
	}

	/**
	 * Send a prompt, trying fallback providers on failure.
	 *
	 * @return array{text:string,provider:string}|WP_Error
	 */
	public static function generate( $system, $user, array $settings, $only = null ) {
		$chain  = $only ? array( $only ) : self::chain( $settings );
		$errors = array();

		foreach ( $chain as $id ) {
			$result = self::call( $id, $system, $user, $settings );
			if ( ! is_wp_error( $result ) && '' !== trim( $result ) ) {
				return array( 'text' => $result, 'provider' => $id );
			}
			$all      = self::all();
			$errors[] = $all[ $id ]['label'] . ': ' . ( is_wp_error( $result ) ? $result->get_error_message() : __( 'empty reply', 'valvabox-ai-writer' ) );
		}

		return new WP_Error( 'vbai_failed', implode( ' | ', $errors ), array( 'status' => 502 ) );
	}

	/**
	 * @return string|WP_Error
	 */
	public static function call( $id, $system, $user, array $settings ) {
		$all = self::all();
		if ( ! isset( $all[ $id ] ) ) {
			return new WP_Error( 'vbai_provider', __( 'Unknown provider.', 'valvabox-ai-writer' ) );
		}
		$p     = $all[ $id ];
		$key   = self::conf( $settings, $id, 'key' );
		$model = self::conf( $settings, $id, 'model' );
		$base  = untrailingslashit( self::conf( $settings, $id, 'base' ) );

		if ( $p['needs_key'] && '' === $key ) {
			return new WP_Error( 'vbai_key', __( 'No API key set.', 'valvabox-ai-writer' ) );
		}

		$temperature = (float) $settings['temperature'];
		$max_tokens  = (int) $settings['max_tokens'];
		$headers     = array( 'Content-Type' => 'application/json' );

		if ( 'gemini' === $p['type'] ) {
			$url                       = $base . '/models/' . rawurlencode( $model ) . ':generateContent';
			$headers['x-goog-api-key'] = $key;
			$body                      = array(
				'systemInstruction' => array( 'parts' => array( array( 'text' => $system ) ) ),
				'contents'          => array( array( 'role' => 'user', 'parts' => array( array( 'text' => $user ) ) ) ),
				'generationConfig'  => array( 'temperature' => $temperature, 'maxOutputTokens' => $max_tokens ),
			);
		} else {
			$url = ! empty( $p['full_url'] ) ? $base : $base . '/chat/completions';
			if ( '' !== $key ) {
				$headers['Authorization'] = 'Bearer ' . $key;
			}
			if ( 'openrouter' === $id ) {
				$headers['HTTP-Referer'] = home_url( '/' );
				$headers['X-Title']      = get_bloginfo( 'name' );
			}
			$body = array(
				'model'       => $model,
				'messages'    => array(
					array( 'role' => 'system', 'content' => $system ),
					array( 'role' => 'user', 'content' => $user ),
				),
				'temperature' => $temperature,
				'max_tokens'  => $max_tokens,
				'stream'      => false,
			);
			if ( 'pollinations' === $id ) {
				$body['referrer'] = wp_parse_url( home_url(), PHP_URL_HOST );
				$body['private']  = true;
			}
		}

		$response = wp_remote_post(
			$url,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
				'timeout' => (int) apply_filters( 'vbai_http_timeout', 60 ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = '';
			if ( is_array( $data ) ) {
				if ( isset( $data['error']['message'] ) ) {
					$msg = $data['error']['message'];
				} elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
					$msg = $data['error'];
				}
			}
			if ( 429 === $code ) {
				$msg = __( 'Free limit reached, try again shortly.', 'valvabox-ai-writer' ) . ( $msg ? ' (' . $msg . ')' : '' );
			}
			/* translators: %d: HTTP status code */
			return new WP_Error( 'vbai_http', sprintf( __( 'HTTP %d', 'valvabox-ai-writer' ), $code ) . ( $msg ? ': ' . wp_strip_all_tags( $msg ) : '' ) );
		}

		if ( 'gemini' === $p['type'] ) {
			$text = '';
			if ( isset( $data['candidates'][0]['content']['parts'] ) ) {
				foreach ( $data['candidates'][0]['content']['parts'] as $part ) {
					if ( isset( $part['text'] ) && empty( $part['thought'] ) ) {
						$text .= $part['text'];
					}
				}
			}
			if ( '' === $text && isset( $data['promptFeedback']['blockReason'] ) ) {
				return new WP_Error( 'vbai_blocked', __( 'The request was blocked by the provider\'s safety filter.', 'valvabox-ai-writer' ) );
			}
			return $text;
		}

		return isset( $data['choices'][0]['message']['content'] ) ? (string) $data['choices'][0]['message']['content'] : '';
	}
}
