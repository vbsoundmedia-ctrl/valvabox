<?php
/**
 * Writing actions and the prompts behind them.
 */

defined( 'ABSPATH' ) || exit;

class VBAI_Prompts {

	/** Inline HTML the model may keep when rewriting a single block. */
	const INLINE_TAGS = array(
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'a'      => array( 'href' => true, 'title' => true, 'target' => true, 'rel' => true ),
		'code'   => array(),
		'br'     => array(),
		's'      => array(),
		'sub'    => array(),
		'sup'    => array(),
		'mark'   => array(),
	);

	/**
	 * Action id => [ label, needs text?, output format ].
	 * Formats: "markdown" (inserted as blocks), "inline" (replaces one block's text), "list" (one item per line), "plain".
	 */
	public static function actions() {
		return array(
			'write'     => array( 'label' => __( 'Write a draft', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'markdown' ),
			'continue'  => array( 'label' => __( 'Continue writing', 'valvabox-ai-writer' ), 'needs_text' => false, 'format' => 'markdown' ),
			'outline'   => array( 'label' => __( 'Outline', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'markdown' ),
			'improve'   => array( 'label' => __( 'Improve writing', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'rewrite' ),
			'grammar'   => array( 'label' => __( 'Fix spelling & grammar', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'rewrite' ),
			'shorter'   => array( 'label' => __( 'Make shorter', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'rewrite' ),
			'longer'    => array( 'label' => __( 'Make longer', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'rewrite' ),
			'simplify'  => array( 'label' => __( 'Simplify language', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'rewrite' ),
			'tone'      => array( 'label' => __( 'Change tone', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'rewrite' ),
			'translate' => array( 'label' => __( 'Translate', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'rewrite' ),
			'summarize' => array( 'label' => __( 'Summarise', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'markdown' ),
			'titles'    => array( 'label' => __( 'Suggest titles', 'valvabox-ai-writer' ), 'needs_text' => false, 'format' => 'list' ),
			'meta'      => array( 'label' => __( 'SEO meta description', 'valvabox-ai-writer' ), 'needs_text' => false, 'format' => 'plain' ),
			'excerpt'   => array( 'label' => __( 'Write excerpt', 'valvabox-ai-writer' ), 'needs_text' => false, 'format' => 'plain' ),
			'tags'      => array( 'label' => __( 'Suggest tags', 'valvabox-ai-writer' ), 'needs_text' => false, 'format' => 'list' ),
			'ask'       => array( 'label' => __( 'Ask AI', 'valvabox-ai-writer' ), 'needs_text' => true, 'format' => 'markdown' ),
		);
	}

	public static function tones() {
		return array( 'neutral', 'friendly', 'professional', 'casual', 'confident', 'persuasive', 'playful', 'formal', 'empathetic', 'journalistic' );
	}

	public static function lengths() {
		return array(
			'short'  => '150-250 words',
			'medium' => '400-600 words',
			'long'   => '900-1200 words',
		);
	}

	/**
	 * Build the system and user prompt for a request.
	 *
	 * @param array $req action, text, context, title, tone, language, length, mode ("inline" or "markdown").
	 * @return array{0:string,1:string}
	 */
	public static function build( array $req ) {
		$action   = $req['action'];
		$text     = $req['text'];
		$context  = $req['context'];
		$title    = $req['title'];
		$tone     = $req['tone'];
		$language = $req['language'];
		$lengths  = self::lengths();
		$length   = isset( $lengths[ $req['length'] ] ) ? $lengths[ $req['length'] ] : $lengths['medium'];
		$inline   = 'inline' === $req['mode'];

		$system = "You are a skilled writing assistant inside the WordPress editor. "
			. "Reply with the requested content only: no preamble, no explanations, no closing remarks, and do not wrap the answer in code fences. "
			. "Write in {$language} unless asked to translate.";

		if ( $inline ) {
			$out_rule = 'The text may contain inline HTML tags (strong, em, a, code). Keep them where they still make sense. Return a single passage of text with no Markdown, no headings and no surrounding quotes.';
		} else {
			$out_rule = 'Format the answer in Markdown (## headings, paragraphs, lists where useful).';
		}

		$about = '' !== $title ? "The article is titled \"{$title}\".\n" : '';
		$ctx   = '' !== $context ? "\n\nCurrent article text for context:\n\"\"\"\n{$context}\n\"\"\"" : '';

		switch ( $action ) {
			case 'write':
				$user = "Write a well-structured blog post draft of about {$length} in a {$tone} tone.\n{$about}Topic and notes:\n{$text}\n\nFormat in Markdown with an engaging introduction, ## subheadings and a short conclusion. Do not repeat the title as a heading.";
				break;
			case 'continue':
				$source = '' !== $text ? $text : $context;
				$user   = "Continue the following article naturally for 2-3 more paragraphs in the same voice. Return only the new paragraphs in Markdown.\n{$about}\n\"\"\"\n{$source}\n\"\"\"";
				$ctx    = '';
				break;
			case 'outline':
				$user = "Create a detailed blog post outline in Markdown (## sections with bullet points) for:\n{$text}";
				break;
			case 'improve':
				$user = "Improve the clarity, flow and word choice of this text while keeping its meaning and roughly its length. {$out_rule}\n\n{$text}";
				break;
			case 'grammar':
				$user = "Fix spelling, grammar and punctuation in this text. Change nothing else. {$out_rule}\n\n{$text}";
				break;
			case 'shorter':
				$user = "Rewrite this text to be about half as long, keeping the key points. {$out_rule}\n\n{$text}";
				break;
			case 'longer':
				$user = "Expand this text with more detail, examples or explanation (about twice as long). {$out_rule}\n\n{$text}";
				break;
			case 'simplify':
				$user = "Rewrite this text in plain, simple language a 12-year-old could follow. {$out_rule}\n\n{$text}";
				break;
			case 'tone':
				$user = "Rewrite this text in a {$tone} tone, keeping the meaning. {$out_rule}\n\n{$text}";
				break;
			case 'translate':
				$user = "Translate this text into {$language}. Keep names and formatting. {$out_rule}\n\n{$text}";
				break;
			case 'summarize':
				$user = "Summarise this text as a short paragraph followed by 3-5 key-point bullets in Markdown.\n\n{$text}";
				break;
			case 'titles':
				$user = "Suggest 5 compelling, SEO-friendly titles (under 65 characters each) for this article. Return one title per line with no numbering, bullets or quotes.\n{$about}";
				break;
			case 'meta':
				$user = "Write one SEO meta description (140-155 characters) for this article. Include the main keyword, make it click-worthy, return plain text only.\n{$about}";
				break;
			case 'excerpt':
				$user = "Write a 1-2 sentence excerpt (under 55 words) that makes readers want to read this article. Plain text only.\n{$about}";
				break;
			case 'tags':
				$user = "Suggest 5-8 short tags for this article. Return one tag per line, lowercase, no '#', numbering or bullets.\n{$about}";
				break;
			case 'ask':
			default:
				$user = $text . ( $inline ? "\n\n{$out_rule}" : '' );
				break;
		}

		return array( $system, $user . $ctx );
	}

	/**
	 * Tidy the model's raw reply for the requested format.
	 */
	public static function clean( $output, $action, $mode ) {
		$output = trim( (string) $output );

		// Strip reasoning blocks some free models emit, and stray code fences.
		$output = preg_replace( '#<think>.*?</think>#is', '', $output );
		$output = preg_replace( '/^```[a-z]*\s*\n(.*)\n```$/is', '$1', trim( $output ) );
		$output = trim( $output );

		$actions = self::actions();
		$format  = isset( $actions[ $action ] ) ? $actions[ $action ]['format'] : 'markdown';
		if ( 'rewrite' === $format ) {
			$format = 'inline' === $mode ? 'inline' : 'markdown';
		}
		if ( 'ask' === $action && 'inline' === $mode ) {
			$format = 'inline';
		}

		switch ( $format ) {
			case 'inline':
				$output = trim( $output, " \t\n\r\"“”" );
				return wp_kses( $output, self::INLINE_TAGS );
			case 'plain':
				$output = preg_replace( array( '/^\s*#+\s*/m', '/\*\*|__/' ), '', wp_strip_all_tags( $output ) );
				return trim( preg_replace( '/\s+/', ' ', $output ), " \t\n\r\"“”" );
			case 'list':
				$lines = preg_split( '/\r?\n/', wp_strip_all_tags( $output ) );
				$items = array();
				foreach ( $lines as $line ) {
					$line = trim( preg_replace( '/^\s*(?:[-*•]|\d+[.)])\s*/u', '', $line ), " \t\"“”#" );
					if ( '' !== $line ) {
						$items[] = $line;
					}
				}
				return implode( "\n", $items );
			default:
				// Markdown is turned into blocks by the editor's own sanitising paste handler.
				return wp_kses_post( $output );
		}
	}
}
