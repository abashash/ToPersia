<?php
/**
 * 1.5.0 — length bounds of the three public outputs (ToPersia batch 26 · F-01).
 *
 *   summary    TPAS_SUMMARY_MIN … TPAS_SUMMARY_MAX characters
 *   keypoints  TPAS_KEYPOINTS_MIN … TPAS_KEYPOINTS_MAX items, each TPAS_KEYPOINT_MIN … TPAS_KEYPOINT_MAX characters
 *   plan       TPAS_PLAN_MIN … TPAS_PLAN_MAX characters
 *
 * The constants are defined in the main plugin file only when not already
 * defined, so wp-config.php can tune them. The generator states the bounds in
 * the prompt; an output outside them is made once more, and if it is still
 * outside it is stored with a "needs review" flag and never shown on the page
 * (tpas_get_summary() returns review = true). Characters are counted on the
 * plain text: no tags, no "**", no list markers, whitespace collapsed.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

class TP_AI_Bounds {

	/** Post meta holding the review flag of one output: _tpas_review_{type}_{lang}. */
	const REVIEW = '_tpas_review_';

	/**
	 * The bounds now in force.
	 *
	 * @return array<string,int[]> type => [min, max] (+ items => [min, max] for keypoints)
	 */
	public static function limits() {
		return array(
			'summary'   => array( (int) TPAS_SUMMARY_MIN, (int) TPAS_SUMMARY_MAX ),
			'keypoints' => array( (int) TPAS_KEYPOINT_MIN, (int) TPAS_KEYPOINT_MAX ),
			'items'     => array( (int) TPAS_KEYPOINTS_MIN, (int) TPAS_KEYPOINTS_MAX ),
			'plan'      => array( (int) TPAS_PLAN_MIN, (int) TPAS_PLAN_MAX ),
		);
	}

	/** Plain text: no tags or entities, no "**" / "__", whitespace of each line collapsed. */
	public static function plain( $text ) {
		$text = str_replace( array( '**', '__' ), '', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) );
		$text = preg_replace( '/[ \t\x{00A0}\x{200C}]+/u', ' ', $text );

		return trim( preg_replace( '/\s*\n\s*/u', "\n", (string) $text ) );
	}

	/** Characters of a plain run (a ZWNJ inside a word is not counted). */
	public static function chars( $text ) {
		return mb_strlen( str_replace( "\n", ' ', self::plain( $text ) ), 'UTF-8' );
	}

	/**
	 * The items of a key-point list: lines starting with - * • or "1." / "1)"; when
	 * no line has a marker, every non-empty line is an item.
	 *
	 * @return string[]
	 */
	public static function items( $text ) {
		$lines  = array_values( array_filter( array_map( 'trim', explode( "\n", self::plain( $text ) ) ), 'strlen' ) );
		$marked = preg_grep( '/^(?:[-*•]|[0-9۰-۹٠-٩]+[.)])\s+/u', $lines );
		$use    = $marked ? $marked : $lines;

		return array_values( array_map( function ( $l ) {
			return trim( preg_replace( '/^(?:[-*•]|[0-9۰-۹٠-٩]+[.)])\s+/u', '', $l ) );
		}, $use ) );
	}

	/**
	 * Whether an output is inside its bounds.
	 *
	 * @param string $type summary · keypoints · plan.
	 * @param string $text Output text.
	 * @return array { ok: bool, chars: int, items: int[], why: string }
	 */
	public static function check( $type, $text ) {
		$lim   = self::limits();
		$chars = self::chars( $text );
		$out   = array( 'ok' => true, 'chars' => $chars, 'items' => array(), 'why' => '' );

		if ( 'keypoints' === $type ) {
			$out['items'] = array_map( function ( $i ) { return mb_strlen( $i, 'UTF-8' ); }, self::items( $text ) );
			$n            = count( $out['items'] );

			if ( $n < $lim['items'][0] || $n > $lim['items'][1] ) {
				$out['ok']  = false;
				$out['why'] = sprintf( 'items %d (bounds %d-%d)', $n, $lim['items'][0], $lim['items'][1] );
				return $out;
			}

			foreach ( $out['items'] as $k => $len ) {
				if ( $len < $lim['keypoints'][0] || $len > $lim['keypoints'][1] ) {
					$out['ok']  = false;
					$out['why'] = sprintf( 'item %d: %d characters (bounds %d-%d)', $k + 1, $len, $lim['keypoints'][0], $lim['keypoints'][1] );
					return $out;
				}
			}

			return $out;
		}

		$b = isset( $lim[ $type ] ) ? $lim[ $type ] : $lim['summary'];

		if ( $chars < $b[0] || $chars > $b[1] ) {
			$out['ok']  = false;
			$out['why'] = sprintf( '%d characters (bounds %d-%d)', $chars, $b[0], $b[1] );
		}

		return $out;
	}

	/**
	 * The length rule stated in the prompt (and, after a miss, the correction).
	 *
	 * @param string $type Output type.
	 * @param array  $miss check() result of the previous answer, or empty.
	 * @return string
	 */
	public static function instruction( $type, array $miss = array() ) {
		$lim = self::limits();

		if ( 'keypoints' === $type ) {
			$rule = sprintf( 'Length: %d to %d bullet points, one per line starting with "- ", each %d to %d characters.', $lim['items'][0], $lim['items'][1], $lim['keypoints'][0], $lim['keypoints'][1] );
		} else {
			$b    = isset( $lim[ $type ] ) ? $lim[ $type ] : $lim['summary'];
			$rule = sprintf( 'Length: %d to %d characters in total (spaces included), plain text.', $b[0], $b[1] );
		}

		if ( $miss && empty( $miss['ok'] ) ) {
			$rule .= ' Your previous answer was outside this (' . $miss['why'] . '); rewrite it to fit exactly.';
		}

		return $rule;
	}

	/** Set or clear the review flag of one output. */
	public static function flag( $post_id, $type, $lang, array $check ) {
		$key = self::REVIEW . $type . '_' . $lang;

		if ( ! empty( $check['ok'] ) ) {
			delete_post_meta( (int) $post_id, $key );
			return;
		}

		update_post_meta( (int) $post_id, $key, array( 'why' => (string) $check['why'], 'chars' => (int) $check['chars'], 'at' => time() ) );
	}

	/**
	 * Needs review: flagged at generation, or (an output stored before 1.5.0)
	 * outside the bounds now in force.
	 */
	public static function needs_review( $post_id, $type, $lang, $text ) {
		$flag = get_post_meta( (int) $post_id, self::REVIEW . $type . '_' . $lang, true );

		return ! empty( $flag ) || empty( self::check( $type, $text )['ok'] );
	}
}
