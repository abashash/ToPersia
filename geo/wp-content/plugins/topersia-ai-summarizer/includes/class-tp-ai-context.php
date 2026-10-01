<?php
/**
 * Post → structured context builder.
 *
 * When ToPersia Core is active, this reads the REAL ToPersia data layer:
 * the canonical entity_uid (see docs/ai-integration-notes.md), the shared
 * meta-box field names (name_fa/name_en/short_summary/overview_text/
 * hero_image/ai_summary), and JetEngine Relations via Core's guarded
 * `topersia_relation()` wrapper. Every Core call is guarded with
 * `function_exists()` so the plugin still works standalone (no Core, no
 * JetEngine) - it just falls back to generic post data in that case.
 *
 * This class NEVER writes entity_uid or any Core-owned meta field. It is a
 * read-only consumer, exactly like topersia-schema.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

class TP_AI_Context {

	/**
	 * Shared meta-box field names used across ToPersia's ~42 `tp_*` entity
	 * post types (see the project's JetEngine meta box "متاباکس مشترک").
	 * Kept as a single list so a future field rename only needs one edit.
	 */
	const FIELD_NAME_FA      = 'name_fa';
	const FIELD_NAME_EN      = 'name_en';
	const FIELD_NAME_AR      = 'name_ar';
	const FIELD_SHORT_SUMMARY = 'short_summary';
	const FIELD_OVERVIEW      = 'overview_text';
	const FIELD_HERO_IMAGE    = 'hero_image';
	const FIELD_AI_SUMMARY    = 'ai_summary';
	const FIELD_META_DESC     = 'meta_description';

	/**
	 * Build the context array for one post.
	 *
	 * @param int $post_id Post id.
	 * @return array
	 */
	public static function build( $post_id ) {
		$post = $post_id ? get_post( $post_id ) : null;

		$context = array(
			'post_id'            => $post_id,
			'canonical_entity_id' => null, // entity_uid - the real cross-language identity. Read-only.
			'entity_type'        => '',
			'title'              => $post ? wp_strip_all_tags( $post->post_title ) : '',
			'excerpt'            => $post ? trim( wp_strip_all_tags( get_the_excerpt( $post ) ) ) : '',
			'content_text'       => self::content_text( $post ),
			'language'           => TP_AI_Localize::for_post( $post_id ),
			'tags'               => array(),
			'taxonomies'         => array(),
			'core_fields'        => array(), // name_fa/short_summary/overview_text/hero_image/ai_summary, when Core is active.
			'jetengine'          => array(
				'meta_fields' => array(),
				'relations'   => array(),
			),
		);

		if ( ! $post ) {
			/** This filter is documented below. */
			return apply_filters( 'tpas_context_array', $context, $post_id );
		}

		$context['taxonomies'] = self::taxonomies( $post );
		$context['tags']       = array_slice( $context['taxonomies']['post_tag'] ?? array(), 0, 6 );

		if ( self::core_active() ) {
			$context['canonical_entity_id'] = self::entity_uid( $post->ID );
			$context['entity_type']         = self::entity_type( $post, $context['canonical_entity_id'] );
			$context['core_fields']         = self::core_fields( $post->ID );
		} else {
			// No Core: fall back to WordPress's own concept of "type".
			$context['entity_type'] = $post->post_type;
		}

		$context['jetengine'] = array(
			'meta_fields' => self::meta_facts( $post->ID ),
			'relations'   => self::relations( $post->ID ),
		);

		/**
		 * Filter the built context.
		 *
		 * @param array $context Context array.
		 * @param int   $post_id Post id.
		 */
		return apply_filters( 'tpas_context_array', $context, $post_id );
	}

	/**
	 * Whether ToPersia Core's public contract functions are available.
	 * Every Core-specific read in this class is guarded behind this check,
	 * per the project rule that no external call (including a sibling
	 * plugin's functions) may be made unguarded.
	 *
	 * @return bool
	 */
	public static function core_active() {
		return function_exists( 'topersia_is_entity' )
			&& function_exists( 'topersia_entity_meta' )
			&& defined( 'TOPERSIA_UID_BASE' );
	}

	/**
	 * The post's canonical entity_uid (e.g. "https://topersia.com/e/city/isfahan"),
	 * read directly via get_post_meta() - the same way topersia-schema's
	 * topersia_schema_uid() reads it, since entity_uid itself is not one of
	 * the "plain text" fields topersia_entity_meta() was written for and
	 * Core's own writer (topersia_fill_entity_uid()) reads/writes it the
	 * same direct way. This method never writes the value.
	 *
	 * @param int $post_id Post id.
	 * @return string|null
	 */
	protected static function entity_uid( $post_id ) {
		$uid = get_post_meta( $post_id, 'entity_uid', true );

		return ( is_string( $uid ) && '' !== $uid ) ? $uid : null;
	}

	/**
	 * The entity's "type segment" - the same segment Core embeds in
	 * entity_uid (e.g. "city", "monument", "route"). Parsed directly out of
	 * the existing entity_uid when present (zero duplicated logic); falls
	 * back to the post type's own rewrite slug - the exact same fallback
	 * Core's own topersia_fill_entity_uid() uses - for posts that don't
	 * have an entity_uid yet (translations, or content saved before Core's
	 * UID feature ran).
	 *
	 * @param WP_Post     $post Post object.
	 * @param string|null $uid  Already-resolved entity_uid, if any.
	 * @return string
	 */
	protected static function entity_type( \WP_Post $post, $uid ) {
		if ( $uid && defined( 'TOPERSIA_UID_BASE' ) && 0 === strpos( $uid, TOPERSIA_UID_BASE . '/' ) ) {
			$tail  = substr( $uid, strlen( TOPERSIA_UID_BASE ) + 1 );
			$parts = explode( '/', $tail );
			if ( ! empty( $parts[0] ) ) {
				return $parts[0];
			}
		}

		$type_object = get_post_type_object( $post->post_type );

		if ( $type_object && ! empty( $type_object->rewrite['slug'] ) ) {
			return $type_object->rewrite['slug'];
		}

		return str_replace( 'tp_', '', $post->post_type );
	}

	/**
	 * Reads the shared meta-box fields that exist on every ToPersia entity
	 * post type, via Core's guarded topersia_entity_meta() helper. Fields
	 * that are empty are omitted rather than included as "".
	 *
	 * ai_summary is READ here as one input among several - it is an
	 * editor-curated field topersia-schema also reads for JSON-LD/AEO
	 * fallback. This plugin never writes to it; the AI-generated summary
	 * this plugin produces is stored separately (see TP_AI_Summary), so the
	 * two never collide.
	 *
	 * @param int $post_id Post id.
	 * @return array<string,string>
	 */
	protected static function core_fields( $post_id ) {
		$keys = self::core_field_keys();

		$out = array();

		foreach ( $keys as $label => $field ) {
			$value = topersia_entity_meta( $post_id, $field );
			if ( '' !== $value ) {
				$out[ $label ] = $value;
			}
		}

		$hero_image_id = topersia_entity_meta( $post_id, self::FIELD_HERO_IMAGE );
		if ( '' !== $hero_image_id && is_numeric( $hero_image_id ) ) {
			$url = wp_get_attachment_image_url( (int) $hero_image_id, 'large' );
			if ( $url ) {
				$out['hero_image_url'] = $url;
				$alt                   = get_post_meta( (int) $hero_image_id, '_wp_attachment_image_alt', true );
				if ( $alt ) {
					$out['hero_image_alt'] = wp_strip_all_tags( (string) $alt );
				}
			}
		}

		return $out;
	}

	/**
	 * Label => meta key of the core text fields read into the context.
	 *
	 * @return array<string,string>
	 */
	public static function core_field_keys() {
		return array(
			'name_fa'          => self::FIELD_NAME_FA,
			'name_en'          => self::FIELD_NAME_EN,
			'name_ar'          => self::FIELD_NAME_AR,
			'short_summary'    => self::FIELD_SHORT_SUMMARY,
			'overview_text'    => self::FIELD_OVERVIEW,
			'ai_summary'       => self::FIELD_AI_SUMMARY,
			'meta_description' => self::FIELD_META_DESC,
		);
	}

	/**
	 * Every meta key this context reads from the post itself: the core text
	 * fields and the hero image. The FIELDMAP facts come from ToPersia Render.
	 * The ToPersia core content hash uses this list, so an edit to any input
	 * marks the stored summary stale (P5-D250).
	 *
	 * @return string[]
	 */
	public static function input_fields() {
		return array_values( array_merge( self::core_field_keys(), array( self::FIELD_HERO_IMAGE ) ) );
	}

	/**
	 * Plain-text content, truncated to the configured context size.
	 *
	 * @param WP_Post|null $post Post object.
	 * @return string
	 */
	protected static function content_text( $post ) {
		if ( ! $post ) {
			return '';
		}

		$chars = absint( TP_AI_Options::get_field( 'internal_context_chars', 40000 ) );

		$text = trim( wp_strip_all_tags( $post->post_content ) );
		$text = preg_replace( '/[\t ]+/', ' ', $text );

		if ( $chars > 0 && function_exists( 'mb_strlen' ) && mb_strlen( $text, 'UTF-8' ) > $chars ) {
			$text = mb_substr( $text, 0, $chars, 'UTF-8' ) . '…';
		}

		return $text;
	}

	/**
	 * Taxonomy terms, keyed by taxonomy name.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	protected static function taxonomies( $post ) {
		$out   = array();
		$taxes = get_object_taxonomies( $post->post_type, 'names' );

		foreach ( $taxes as $tax ) {
			if ( in_array( $tax, array( 'post_format', 'nav_menu' ), true ) ) {
				continue;
			}
			$terms = get_the_terms( $post->ID, $tax );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			$out[ $tax ] = array_map( 'wp_strip_all_tags', wp_list_pluck( $terms, 'name' ) );
		}

		return $out;
	}

	/**
	 * FIELDMAP zones whose fields are "facts" for the AI context.
	 */
	const FACT_ZONES = array( 'card', 'details' );

	/**
	 * Entity facts for the AI context, keyed by their display label.
	 * SPRINT-4.4 phase 1 · 1.10
	 *
	 * Only the fields of the `card` and `details` zones of TP-G-FIELDMAP.json,
	 * read through ToPersia Render's own map (`topersia_g_map()` is built from
	 * that file) and formatted by it, so choice fields carry their glossary
	 * label and numbers their unit — the same text the page shows.
	 *
	 * Never: a serialized value, an underscore key, a WPML key, a status
	 * field. Up to 1.1.0 every short meta value was a "fact", and the
	 * internal engine's key points read "Copied Media Ids: a:0:{}".
	 *
	 * Without ToPersia Render there are no facts — raw meta is never used.
	 *
	 * @param int $post_id Post id.
	 * @return array<string,string> label => value
	 */
	protected static function meta_facts( $post_id ) {
		$out = array();

		if ( ! function_exists( 'topersia_g_map' ) || ! function_exists( 'topersia_g_zone_fields' ) || ! function_exists( 'topersia_g_value' ) ) {
			return $out;
		}

		$map = topersia_g_map();

		foreach ( self::FACT_ZONES as $zone ) {
			foreach ( (array) topersia_g_zone_fields( $post_id, $zone ) as $name => $raw ) {

				if ( empty( $map[ $name ]['label'] ) || ! self::fact_allowed( $name, $raw ) ) {
					continue;
				}

				$value = self::plain_text( topersia_g_value( $name, $raw ) );

				if ( '' === $value || self::is_serialized_text( $value ) ) {
					continue;
				}

				$out[ (string) $map[ $name ]['label'] ] = $value;
			}
		}

		return array_slice( $out, 0, 12, true );
	}

	/**
	 * Key and raw-value guard on top of the FIELDMAP whitelist.
	 *
	 * @param string $key Field name.
	 * @param mixed  $raw Stored value.
	 * @return bool
	 */
	public static function fact_allowed( $key, $raw ) {
		$key = (string) $key;

		if ( '' === $key || '_' === $key[0] ) {
			return false;
		}

		// WPML bookkeeping and any status field (content_status, status …).
		if ( preg_match( '/wpml|media_ids|(^|_)status$/i', $key ) ) {
			return false;
		}

		return ! ( is_string( $raw ) && self::is_serialized_text( $raw ) );
	}

	/**
	 * Whether a string is PHP-serialized data (a:0:{}, s:3:"abc"; …).
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	public static function is_serialized_text( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		if ( function_exists( 'is_serialized' ) ) {
			return is_serialized( $value );
		}

		// Same rules as WordPress's is_serialized() (strict mode).
		$value = trim( $value );

		if ( 'N;' === $value ) {
			return true;
		}

		if ( strlen( $value ) < 4 || ':' !== $value[1] || ! in_array( substr( $value, -1 ), array( ';', '}' ), true ) ) {
			return false;
		}

		switch ( $value[0] ) {
			case 's':
				return (bool) preg_match( '/^s:\d+:".*";$/s', $value );
			case 'a':
			case 'O':
			case 'C':
			case 'E':
				return (bool) preg_match( '/^[aOCE]:\d+:/', $value );
			case 'b':
			case 'i':
			case 'd':
				return (bool) preg_match( '/^[bid]:[0-9.E+-]+;$/', $value );
		}

		return false;
	}

	/**
	 * Formatted HTML value → one line of plain text.
	 *
	 * @param string $html Value markup.
	 * @return string
	 */
	protected static function plain_text( $html ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' );

		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Only published, password-free posts in the current language may reach
	 * the AI context. P5-D247 — ToPersia Render's central gate when present.
	 *
	 * @param array $ids Post ids.
	 * @return array<int,int>
	 */
	public static function published_ids( array $ids ) {
		if ( function_exists( 'topersia_g_published' ) ) {
			return topersia_g_published( $ids );
		}

		$out = array();

		foreach ( $ids as $id ) {
			$id   = absint( $id );
			$post = $id ? get_post( $id ) : null;

			if ( $post && 'publish' === $post->post_status && '' === (string) $post->post_password && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}

		return $out;
	}

	/**
	 * Related item titles. When ToPersia Core is active, this walks the
	 * known geographic hierarchy relations (province → county → city) via
	 * Core's guarded topersia_relation() so we get a REAL ancestor chain
	 * for G-series entities. For every other entity (attractions, hotels,
	 * routes, ...) Core does not expose named relation-id constants for
	 * every possible link, so this falls back to enumerating all active
	 * JetEngine relations touching the post - the same general-purpose
	 * approach this class used before Core-awareness was added, just
	 * routed through jet_engine() directly since Core has no "list every
	 * relation" helper in its public contract.
	 *
	 * NOTE: a full entity-candidate retrieval system for the Travel
	 * Planner (nearby attractions/restaurants with distance/hours) is a
	 * separate, larger piece of work - this method only produces
	 * lightweight "related titles" for the Summary feature's context.
	 *
	 * JetEngine relation objects expose get_parents( $post_id, 'ids' ) and
	 * get_children( $post_id, 'ids' ) directly (confirmed against Core's
	 * own usage in topersia-core.php) - not a "get_related_items()"
	 * manager method, which is what this class assumed before Core's real
	 * API was confirmed.
	 *
	 * @param int $post_id Post id.
	 * @return array
	 */
	protected static function relations( $post_id ) {
		$titles = array();

		if ( self::core_active() ) {
			$titles = self::core_geographic_chain( $post_id );
		}

		if ( empty( $titles ) && function_exists( 'jet_engine' ) && isset( jet_engine()->relations ) ) {
			try {
				$jet_relations = jet_engine()->relations;
				$all_relations = method_exists( $jet_relations, 'get_relations' ) ? $jet_relations->get_relations() : array();

				foreach ( (array) $all_relations as $relation ) {
					if ( ! is_object( $relation ) ) {
						continue;
					}

					foreach ( array( 'get_children', 'get_parents' ) as $method ) {
						if ( ! method_exists( $relation, $method ) ) {
							continue;
						}

						try {
							$ids = $relation->$method( $post_id, 'ids' );
						} catch ( \Throwable $e ) {
							continue;
						}

						// Only published items reach the prompt. P5-D247
						foreach ( self::published_ids( (array) $ids ) as $item_id ) {
							$item_title = get_the_title( $item_id );
							if ( $item_title && ! in_array( $item_title, $titles, true ) ) {
								$titles[] = wp_strip_all_tags( $item_title );
							}
						}
					}

					if ( count( $titles ) >= 8 ) {
						break;
					}
				}
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// JetEngine API differences are non-fatal; fall through.
			}
		}

		/**
		 * Filter the resolved related-item titles.
		 *
		 * @param array $titles  Related item titles.
		 * @param int   $post_id Post id.
		 */
		return apply_filters( 'tpas_context_relations', $titles, $post_id );
	}

	/**
	 * Walks the province → county → city geographic chain using Core's
	 * named relation constants (TOPERSIA_REL_PROVINCE_COUNTY,
	 * TOPERSIA_REL_COUNTY_CITY) via topersia_relation(). Only meaningful
	 * for G-series entities; returns an empty array for anything else
	 * (attractions, hotels, ...) since Core does not name a relation
	 * constant for those - see the relations() docblock above.
	 *
	 * @param int $post_id Post id.
	 * @return string[] Ancestor titles, nearest first.
	 */
	protected static function core_geographic_chain( $post_id ) {
		$titles = array();

		$relation_ids = array_filter( array(
			defined( 'TOPERSIA_REL_COUNTY_CITY' ) ? TOPERSIA_REL_COUNTY_CITY : 0,
			defined( 'TOPERSIA_REL_PROVINCE_COUNTY' ) ? TOPERSIA_REL_PROVINCE_COUNTY : 0,
		) );

		$current = $post_id;

		foreach ( $relation_ids as $relation_id ) {
			if ( ! function_exists( 'topersia_relation' ) ) {
				break;
			}

			$relation = topersia_relation( (int) $relation_id );

			if ( ! $relation || ! is_object( $relation ) || ! method_exists( $relation, 'get_parents' ) ) {
				continue;
			}

			try {
				$parent_ids = $relation->get_parents( $current, 'ids' );
			} catch ( \Throwable $e ) {
				continue;
			}

			$parent_id = is_array( $parent_ids ) ? reset( $parent_ids ) : 0;

			if ( ! $parent_id ) {
				continue;
			}

			// Walk through an unpublished ancestor, but never name it. P5-D247
			$title = self::published_ids( array( $parent_id ) ) ? get_the_title( $parent_id ) : '';
			if ( $title ) {
				$titles[] = wp_strip_all_tags( $title );
			}

			$current = $parent_id; // Walk up: county's parent is the province.
		}

		return $titles;
	}

	/**
	 * A compact, newline-separated "structured data" block appended to the
	 * prompt context (Core fields + meta facts + related items).
	 *
	 * @param array $context Context array.
	 * @return string
	 */
	public static function structured_block( array $context ) {
		$lines = array();

		if ( ! empty( $context['canonical_entity_id'] ) ) {
			$lines[] = 'Entity: ' . $context['canonical_entity_id'];
		}

		if ( ! empty( $context['entity_type'] ) ) {
			$lines[] = 'Type: ' . $context['entity_type'];
		}

		$core = $context['core_fields'] ?? array();
		foreach ( array( 'short_summary' => 'Short summary', 'ai_summary' => 'Curated AI summary', 'overview_text' => 'Overview' ) as $key => $label ) {
			if ( ! empty( $core[ $key ] ) ) {
				$lines[] = sprintf( '%s: %s', $label, $core[ $key ] );
			}
		}

		$facts = $context['jetengine']['meta_fields'] ?? array();
		foreach ( $facts as $key => $value ) {
			if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
				continue;
			}
			$label = ucwords( str_replace( array( '_', '-' ), ' ', (string) $key ) );
			$lines[] = sprintf( '%s: %s', $label, $value );
		}

		$relations = $context['jetengine']['relations'] ?? array();
		if ( ! empty( $relations ) ) {
			$lines[] = 'Related: ' . implode( ' · ', array_map( 'strval', $relations ) );
		}

		if ( empty( $lines ) ) {
			return '';
		}

		return 'STRUCTURED DATA' . "\n" . implode( "\n", $lines );
	}
}
