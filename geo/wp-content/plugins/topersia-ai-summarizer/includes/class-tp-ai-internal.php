<?php
/**
 * Built-in, offline "internal engine": extractive summary, key points, and a
 * basic itinerary built purely from the post's own content + structured data
 * (JetEngine fields/relations, taxonomies). No API key, nothing leaves the
 * server. Port of the classic extractive approach (TF scoring + lead bias +
 * title overlap).
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Deterministic, free, always-works summarizer used when no AI provider is
 * configured or when "internal" engine mode is selected.
 */
class TP_AI_Internal {

	/**
	 * Compact Persian + English stopword list (frequency words only).
	 *
	 * @var string[]
	 */
	private $stopwords = array(
		// Persian.
		'و', 'در', 'به', 'از', 'که', 'این', 'را', 'با', 'است', 'برای',
		'آن', 'یک', 'های', 'شود', 'شد', 'می', 'تا', 'هم', 'یا', 'اما',
		'نیز', 'بر', 'روی', 'اگر', 'چون', 'خود', 'ها', 'کند', 'کرد',
		'بود', 'شده', 'دارد', 'داشت', 'دیگر', 'همه', 'هر', 'وی', 'او',
		// English.
		'the', 'a', 'an', 'and', 'or', 'but', 'of', 'to', 'in', 'on',
		'for', 'with', 'is', 'are', 'was', 'were', 'this', 'that', 'it',
		'as', 'at', 'by', 'from', 'be', 'has', 'have', 'had',
	);

	/**
	 * Generate output for one type.
	 *
	 * @param string $type    summary | keypoints | plan.
	 * @param array  $context Context array (see TP_AI_Context::build).
	 * @return string Plain text output.
	 */
	public function generate( $type, array $context ) {
		$type = sanitize_key( $type );

		if ( 'keypoints' === $type ) {
			$points = $this->key_points( $context, 5 );
			if ( empty( $points ) ) {
				return '';
			}
			return implode( "\n", array_map( static fn( $p ) => '- ' . $p, $points ) );
		}

		if ( 'plan' === $type ) {
			$plan = $this->itinerary( $context );
			if ( '' === $plan ) {
				return $this->generate( 'keypoints', $context );
			}
			return $plan;
		}

		return $this->summarize( $context, 3 );
	}

	/**
	 * Extractive summary: highest-scoring sentences in original reading order.
	 *
	 * @param array $context Context array.
	 * @param int   $count   Sentences to keep.
	 * @return string
	 */
	public function summarize( array $context, $count = 3 ) {
		$curated = $this->curated_summary( $context );
		if ( '' !== $curated ) {
			return $curated;
		}

		$sentences = $this->split_sentences( $context['content_text'] ?? '' );

		if ( empty( $sentences ) ) {
			return '';
		}

		$scored      = $this->score_sentences( $sentences, $context );
		$top_indexes = array_slice( array_keys( $scored ), 0, max( 1, (int) $count ), true );
		sort( $top_indexes );

		$picked = array();
		foreach ( $top_indexes as $index ) {
			$picked[] = trim( $sentences[ $index ] );
		}

		return esc_html( implode( ' ', $picked ) );
	}

	/**
	 * Returns the editor-curated `ai_summary` field, or `short_summary` as a
	 * secondary choice, when ToPersia Core has supplied either via
	 * `core_fields` (see TP_AI_Context::core_fields()). These are
	 * human-written, already concise and factual - a strictly better source
	 * for the compact "Page Summary" feature than algorithmic extraction,
	 * and free to use since no generation is involved.
	 *
	 * @param array $context Context array.
	 * @return string Empty string when neither field is available.
	 */
	private function curated_summary( array $context ) {
		$core = $context['core_fields'] ?? array();

		foreach ( array( 'ai_summary', 'short_summary' ) as $key ) {
			if ( ! empty( $core[ $key ] ) ) {
				return esc_html( (string) $core[ $key ] );
			}
		}

		return '';
	}

	/**
	 * Bullet "key points": short structured meta facts first, then remaining
	 * high-scoring sentences.
	 *
	 * @param array $context Context array.
	 * @param int   $limit   Max points.
	 * @return string[]
	 */
	public function key_points( array $context, $limit = 5 ) {
		$points = array();

		if ( ! empty( $context['jetengine']['meta_fields'] ) && is_array( $context['jetengine']['meta_fields'] ) ) {
			foreach ( $context['jetengine']['meta_fields'] as $key => $value ) {
				if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
					continue;
				}
				if ( function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) > 80 : strlen( (string) $value ) > 80 ) {
					continue;
				}
				$label    = ucwords( str_replace( array( '_', '-' ), ' ', (string) $key ) );
				$points[] = sprintf( '%s: %s', $label, $value );
				if ( count( $points ) >= $limit ) {
					return array_map( 'esc_html', $points );
				}
			}
		}

		$sentences = $this->split_sentences( $context['content_text'] ?? '' );

		if ( empty( $sentences ) ) {
			return array_map( 'esc_html', $points );
		}

		$scored = $this->score_sentences( $sentences, $context );
		arsort( $scored );

		foreach ( array_keys( $scored ) as $index ) {
			if ( count( $points ) >= $limit ) {
				break;
			}
			$points[] = trim( $sentences[ $index ] );
		}

		return array_map( 'esc_html', $points );
	}

	/**
	 * Templated itinerary from JetEngine-related items, falling back to tags.
	 *
	 * @param array $context Context array.
	 * @return string
	 */
	public function itinerary( array $context ) {
		$title     = (string) ( $context['title'] ?? '' );
		$relations = ! empty( $context['jetengine']['relations'] ) && is_array( $context['jetengine']['relations'] )
			? $context['jetengine']['relations']
			: array();

		$stops = array();
		foreach ( $relations as $item ) {
			$item = (string) $item;
			if ( '' !== trim( $item ) && ! in_array( $item, $stops, true ) ) {
				$stops[] = $item;
			}
			if ( count( $stops ) >= 4 ) {
				break;
			}
		}

		if ( empty( $stops ) && ! empty( $context['tags'] ) ) {
			$stops = array_slice( (array) $context['tags'], 0, 3 );
		}

		if ( empty( $stops ) || '' === $title ) {
			return '';
		}

		$lang  = TP_AI_Localize::for_post( (int) ( $context['post_id'] ?? 0 ) );
		$phrasing = self::phrasing( $lang );

		$lines   = array();
		$lines[] = str_replace( '{t}', $title, $phrasing['begin'] );

		$step = 2;
		foreach ( $stops as $stop ) {
			$lines[] = str_replace( '{s}', $stop, str_replace( '{n}', (string) $step, $phrasing['then'] ) );
			++$step;
		}

		return esc_html( implode( "\n", $lines ) );
	}

	/**
	 * Per-language phrasing for the itinerary template.
	 *
	 * @param string $lang Language code.
	 * @return array
	 */
	protected static function phrasing( $lang ) {
		$map = array(
			'fa' => array(
				'begin' => 'بازدید را از «{t}» شروع کنید.',
				'then'  => '{n}. سپس سری به «{s}» بزنید.',
			),
			'en' => array(
				'begin' => 'Start with {t}.',
				'then'  => '{n}. Then visit {s}.',
			),
			'ar' => array(
				'begin' => 'ابدأ الزيارة من «{t}».',
				'then'  => '{n}. ثم قم بزيارة «{s}».',
			),
			'fr' => array(
				'begin' => 'Commencez par «{t}».',
				'then'  => '{n}. Visitez ensuite «{s}».',
			),
			'de' => array(
				'begin' => 'Beginnen Sie mit «{t}».',
				'then'  => '{n}. Besuchen Sie danach «{s}».',
			),
			'es' => array(
				'begin' => 'Comience con «{t}».',
				'then'  => '{n}. Luego visite «{s}».',
			),
			'ru' => array(
				'begin' => 'Начните с «{t}».',
				'then'  => '{n}. Затем посетите «{s}».',
			),
			'tr' => array(
				'begin' => '«{t}» ile başlayın.',
				'then'  => '{n}. Ardından «{s}» ziyaret edin.',
			),
			'zh' => array(
				'begin' => '从「{t}」开始。',
				'then'  => '{n}. 然后参观「{s}」。',
			),
		);

		return isset( $map[ $lang ] ) ? $map[ $lang ] : $map['en'];
	}

	/**
	 * Split text into sentences (Latin + Persian terminators).
	 *
	 * @param string $text Plain text.
	 * @return string[]
	 */
	private function split_sentences( $text ) {
		$text = trim( (string) $text );

		if ( '' === $text ) {
			return array();
		}

		$parts = preg_split( '/(?<=[\.!\?؟])\s+/u', $text );

		if ( ! is_array( $parts ) ) {
			return array( $text );
		}

		$parts = array_map( 'trim', $parts );
		$parts = array_values( array_filter( $parts, static fn( $s ) => '' !== $s ) );

		return $parts;
	}

	/**
	 * Score sentences: normalized token frequency × lead bonus × title bonus.
	 *
	 * @param string[] $sentences Sentences in original order.
	 * @param array    $context   Context array (title).
	 * @return array<int,float>
	 */
	private function score_sentences( array $sentences, array $context ) {
		$all_tokens   = array();
		$per_sentence = array();

		foreach ( $sentences as $index => $sentence ) {
			$tokens                 = $this->tokenize( $sentence );
			$per_sentence[ $index ] = $tokens;
			$all_tokens             = array_merge( $all_tokens, $tokens );
		}

		$frequency    = array_count_values( $all_tokens );
		$title_tokens = array_flip( $this->tokenize( (string) ( $context['title'] ?? '' ) ) );

		$scores = array();
		$total  = max( 1, count( $sentences ) );

		foreach ( $per_sentence as $index => $tokens ) {
			if ( empty( $tokens ) ) {
				$scores[ $index ] = 0.0;
				continue;
			}

			$freq_score = 0;
			$title_hits = 0;

			foreach ( $tokens as $token ) {
				$freq_score += isset( $frequency[ $token ] ) ? $frequency[ $token ] : 0;
				if ( isset( $title_tokens[ $token ] ) ) {
					++$title_hits;
				}
			}

			$normalized  = $freq_score / count( $tokens );
			$lead_bonus  = 1 - ( $index / ( $total * 4 ) );
			$title_bonus = 1 + ( 0.15 * $title_hits );

			$scores[ $index ] = $normalized * $lead_bonus * $title_bonus;
		}

		return $scores;
	}

	/**
	 * Lowercase, stopword-filtered word tokens (unicode-aware).
	 *
	 * @param string $text Text.
	 * @return string[]
	 */
	private function tokenize( $text ) {
		if ( '' === trim( (string) $text ) ) {
			return array();
		}

		preg_match_all( '/[\p{L}\p{N}]+/u', mb_strtolower( $text ), $matches );
		$tokens = isset( $matches[0] ) ? $matches[0] : array();

		return array_values(
			array_filter(
				$tokens,
				function ( $token ) {
					return mb_strlen( $token ) > 1 && ! in_array( $token, $this->stopwords, true );
				}
			)
		);
	}
}