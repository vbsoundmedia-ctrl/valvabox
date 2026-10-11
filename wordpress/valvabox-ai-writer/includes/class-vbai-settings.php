<?php
/**
 * Settings > AI Writer.
 */

defined( 'ABSPATH' ) || exit;

class VBAI_Settings {

	const OPTION = 'vbai_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function defaults() {
		return array(
			'provider'    => 'pollinations',
			'fallback'    => 1,
			'providers'   => array(),
			'temperature' => 0.7,
			'max_tokens'  => 2048,
			'tone'        => 'friendly',
			'language'    => 'English',
			'rate_limit'  => 40,
			'cache_hours' => 24,
		);
	}

	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function menu() {
		add_options_page(
			__( 'AI Writer', 'valvabox-ai-writer' ),
			__( 'AI Writer', 'valvabox-ai-writer' ),
			'manage_options',
			'vbai',
			array( __CLASS__, 'render' )
		);
		add_management_page(
			__( 'AI Writer', 'valvabox-ai-writer' ),
			__( 'AI Writer', 'valvabox-ai-writer' ),
			'edit_posts',
			'vbai-playground',
			array( __CLASS__, 'render_playground' )
		);
	}

	public static function register() {
		register_setting(
			'vbai',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	public static function sanitize( $input ) {
		$out       = self::defaults();
		$input     = is_array( $input ) ? $input : array();
		$providers = VBAI_Providers::all();
		$old       = self::get();

		$out['provider']    = isset( $input['provider'], $providers[ $input['provider'] ] ) ? $input['provider'] : 'pollinations';
		$out['fallback']    = empty( $input['fallback'] ) ? 0 : 1;
		$out['temperature'] = isset( $input['temperature'] ) ? min( 1.5, max( 0, (float) $input['temperature'] ) ) : 0.7;
		$out['max_tokens']  = isset( $input['max_tokens'] ) ? min( 8192, max( 256, (int) $input['max_tokens'] ) ) : 2048;
		$out['tone']        = isset( $input['tone'] ) && in_array( $input['tone'], VBAI_Prompts::tones(), true ) ? $input['tone'] : 'friendly';
		$out['language']    = isset( $input['language'] ) && '' !== trim( $input['language'] ) ? sanitize_text_field( $input['language'] ) : 'English';
		$out['rate_limit']  = isset( $input['rate_limit'] ) ? max( 0, (int) $input['rate_limit'] ) : 40;
		$out['cache_hours'] = isset( $input['cache_hours'] ) ? max( 0, (int) $input['cache_hours'] ) : 24;

		foreach ( array_keys( $providers ) as $id ) {
			$in  = isset( $input['providers'][ $id ] ) && is_array( $input['providers'][ $id ] ) ? $input['providers'][ $id ] : array();
			$key = isset( $in['key'] ) ? trim( sanitize_text_field( $in['key'] ) ) : '';
			// The key field is shown masked; an unchanged mask keeps the stored key.
			if ( '' !== $key && preg_match( '/^•+.{0,4}$/u', $key ) ) {
				$key = isset( $old['providers'][ $id ]['key'] ) ? $old['providers'][ $id ]['key'] : '';
			}
			$out['providers'][ $id ] = array(
				'key'   => $key,
				'model' => isset( $in['model'] ) ? sanitize_text_field( $in['model'] ) : '',
				'base'  => isset( $in['base'] ) ? esc_url_raw( trim( $in['base'] ) ) : '',
			);
		}
		return $out;
	}

	public static function assets( $hook ) {
		if ( 'settings_page_vbai' !== $hook && 'tools_page_vbai-playground' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'vbai-admin', VBAI_URL . 'assets/admin.css', array(), VBAI_VERSION );
		wp_enqueue_script( 'vbai-admin', VBAI_URL . 'assets/admin.js', array( 'wp-api-fetch' ), VBAI_VERSION, true );
		wp_localize_script(
			'vbai-admin',
			'vbaiAdmin',
			array(
				'testing' => __( 'Testing…', 'valvabox-ai-writer' ),
				'working' => __( 'Writing…', 'valvabox-ai-writer' ),
				'copied'  => __( 'Copied', 'valvabox-ai-writer' ),
			)
		);
	}

	private static function mask( $key ) {
		return '' === $key ? '' : str_repeat( '•', 12 ) . substr( $key, -4 );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s     = self::get();
		$name  = self::OPTION;
		$usage = get_option( 'vbai_usage', array() );
		$today = isset( $usage[ gmdate( 'Y-m-d' ) ] ) ? (int) $usage[ gmdate( 'Y-m-d' ) ] : 0;
		$total = isset( $usage['total'] ) ? (int) $usage['total'] : 0;
		?>
		<div class="wrap vbai-wrap">
			<h1><?php esc_html_e( 'AI Writer', 'valvabox-ai-writer' ); ?></h1>
			<p class="vbai-lead">
				<?php esc_html_e( 'Works out of the box with Pollinations (no key). For better quality and speed, add a free key from Google Gemini, Groq or OpenRouter. Keys stay on your server and are never sent to the browser.', 'valvabox-ai-writer' ); ?>
			</p>
			<p class="vbai-usage">
				<?php
				/* translators: 1: requests today, 2: requests all time */
				printf( esc_html__( 'Requests today: %1$s · All time: %2$s', 'valvabox-ai-writer' ), '<strong>' . esc_html( number_format_i18n( $today ) ) . '</strong>', '<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>' );
				?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'vbai' ); ?>

				<h2><?php esc_html_e( 'Provider', 'valvabox-ai-writer' ); ?></h2>
				<div class="vbai-providers">
					<?php foreach ( VBAI_Providers::all() as $id => $p ) : ?>
						<?php
						$key   = isset( $s['providers'][ $id ]['key'] ) ? $s['providers'][ $id ]['key'] : '';
						$model = isset( $s['providers'][ $id ]['model'] ) ? $s['providers'][ $id ]['model'] : '';
						$base  = isset( $s['providers'][ $id ]['base'] ) ? $s['providers'][ $id ]['base'] : '';
						$field = $name . '[providers][' . $id . ']';
						?>
						<div class="vbai-card<?php echo $s['provider'] === $id ? ' is-active' : ''; ?>">
							<label class="vbai-card-head">
								<input type="radio" name="<?php echo esc_attr( $name ); ?>[provider]" value="<?php echo esc_attr( $id ); ?>" <?php checked( $s['provider'], $id ); ?>>
								<strong><?php echo esc_html( $p['label'] ); ?></strong>
								<span class="vbai-badge"><?php echo $p['needs_key'] ? esc_html__( 'Free key', 'valvabox-ai-writer' ) : esc_html__( 'No key needed', 'valvabox-ai-writer' ); ?></span>
							</label>
							<p class="description"><?php echo esc_html( $p['note'] ); ?>
								<a href="<?php echo esc_url( $p['key_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo 'ollama' === $id ? esc_html__( 'Download', 'valvabox-ai-writer' ) : esc_html__( 'Get a key', 'valvabox-ai-writer' ); ?> ↗</a>
							</p>
							<div class="vbai-fields">
								<label><?php echo esc_html( $p['key_label'] ); ?>
									<input type="password" autocomplete="off" class="regular-text" name="<?php echo esc_attr( $field ); ?>[key]" value="<?php echo esc_attr( self::mask( $key ) ); ?>">
								</label>
								<label><?php esc_html_e( 'Model', 'valvabox-ai-writer' ); ?>
									<input type="text" class="regular-text" name="<?php echo esc_attr( $field ); ?>[model]" value="<?php echo esc_attr( $model ); ?>" placeholder="<?php echo esc_attr( $p['model'] ); ?>">
								</label>
								<label><?php esc_html_e( 'Endpoint', 'valvabox-ai-writer' ); ?>
									<input type="url" class="regular-text" name="<?php echo esc_attr( $field ); ?>[base]" value="<?php echo esc_attr( $base ); ?>" placeholder="<?php echo esc_attr( $p['base'] ); ?>">
								</label>
								<button type="button" class="button vbai-test" data-provider="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Test connection', 'valvabox-ai-writer' ); ?></button>
								<span class="vbai-test-result" aria-live="polite"></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php esc_html_e( 'Save before testing a newly entered key. Leave Model and Endpoint blank to use the defaults shown.', 'valvabox-ai-writer' ); ?></p>

				<h2><?php esc_html_e( 'Writing defaults', 'valvabox-ai-writer' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatic fallback', 'valvabox-ai-writer' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[fallback]" value="1" <?php checked( $s['fallback'], 1 ); ?>> <?php esc_html_e( 'If the chosen provider fails or hits its free limit, try the other providers you have keys for, then Pollinations.', 'valvabox-ai-writer' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="vbai-tone"><?php esc_html_e( 'Default tone', 'valvabox-ai-writer' ); ?></label></th>
						<td><select id="vbai-tone" name="<?php echo esc_attr( $name ); ?>[tone]">
							<?php foreach ( VBAI_Prompts::tones() as $tone ) : ?>
								<option value="<?php echo esc_attr( $tone ); ?>" <?php selected( $s['tone'], $tone ); ?>><?php echo esc_html( ucfirst( $tone ) ); ?></option>
							<?php endforeach; ?>
						</select></td>
					</tr>
					<tr>
						<th scope="row"><label for="vbai-language"><?php esc_html_e( 'Default language', 'valvabox-ai-writer' ); ?></label></th>
						<td><input id="vbai-language" type="text" name="<?php echo esc_attr( $name ); ?>[language]" value="<?php echo esc_attr( $s['language'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="vbai-temp"><?php esc_html_e( 'Creativity', 'valvabox-ai-writer' ); ?></label></th>
						<td><input id="vbai-temp" type="number" step="0.1" min="0" max="1.5" name="<?php echo esc_attr( $name ); ?>[temperature]" value="<?php echo esc_attr( $s['temperature'] ); ?>">
						<p class="description"><?php esc_html_e( '0 = precise, 0.7 = balanced, 1.2+ = very creative.', 'valvabox-ai-writer' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="vbai-max"><?php esc_html_e( 'Max reply length (tokens)', 'valvabox-ai-writer' ); ?></label></th>
						<td><input id="vbai-max" type="number" min="256" max="8192" name="<?php echo esc_attr( $name ); ?>[max_tokens]" value="<?php echo esc_attr( $s['max_tokens'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="vbai-rate"><?php esc_html_e( 'Requests per user per hour', 'valvabox-ai-writer' ); ?></label></th>
						<td><input id="vbai-rate" type="number" min="0" name="<?php echo esc_attr( $name ); ?>[rate_limit]" value="<?php echo esc_attr( $s['rate_limit'] ); ?>">
						<p class="description"><?php esc_html_e( 'Protects your free quota. 0 = unlimited. Administrators are not limited.', 'valvabox-ai-writer' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="vbai-cache"><?php esc_html_e( 'Cache identical requests (hours)', 'valvabox-ai-writer' ); ?></label></th>
						<td><input id="vbai-cache" type="number" min="0" name="<?php echo esc_attr( $name ); ?>[cache_hours]" value="<?php echo esc_attr( $s['cache_hours'] ); ?>">
						<p class="description"><?php esc_html_e( 'Saves quota when the same text is sent twice. "Try again" always skips the cache.', 'valvabox-ai-writer' ); ?></p></td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** Tools > AI Writer: a standalone writer for the classic editor or quick jobs. */
	public static function render_playground() {
		$s = self::get();
		?>
		<div class="wrap vbai-wrap">
			<h1><?php esc_html_e( 'AI Writer', 'valvabox-ai-writer' ); ?></h1>
			<p class="vbai-lead"><?php esc_html_e( 'In the block editor, open the ✦ AI Writer sidebar or the ✦ button on any paragraph. This page works anywhere: paste text, pick an action, copy the result.', 'valvabox-ai-writer' ); ?></p>
			<div class="vbai-play">
				<div>
					<label for="vbai-play-action"><strong><?php esc_html_e( 'Action', 'valvabox-ai-writer' ); ?></strong></label>
					<select id="vbai-play-action">
						<?php foreach ( VBAI_Prompts::actions() as $id => $a ) : ?>
							<?php if ( 'continue' === $id ) { continue; } ?>
							<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $a['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<select id="vbai-play-tone" aria-label="<?php esc_attr_e( 'Tone', 'valvabox-ai-writer' ); ?>">
						<?php foreach ( VBAI_Prompts::tones() as $tone ) : ?>
							<option value="<?php echo esc_attr( $tone ); ?>" <?php selected( $s['tone'], $tone ); ?>><?php echo esc_html( ucfirst( $tone ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<input id="vbai-play-language" type="text" value="<?php echo esc_attr( $s['language'] ); ?>" aria-label="<?php esc_attr_e( 'Language', 'valvabox-ai-writer' ); ?>">
					<textarea id="vbai-play-text" rows="12" placeholder="<?php esc_attr_e( 'Topic, notes or text to work on…', 'valvabox-ai-writer' ); ?>"></textarea>
					<p><button type="button" class="button button-primary" id="vbai-play-go"><?php esc_html_e( 'Generate', 'valvabox-ai-writer' ); ?></button></p>
				</div>
				<div>
					<strong><?php esc_html_e( 'Result', 'valvabox-ai-writer' ); ?></strong>
					<textarea id="vbai-play-out" rows="16" readonly></textarea>
					<p><button type="button" class="button" id="vbai-play-copy"><?php esc_html_e( 'Copy', 'valvabox-ai-writer' ); ?></button> <span id="vbai-play-status" aria-live="polite"></span></p>
				</div>
			</div>
		</div>
		<?php
	}
}
