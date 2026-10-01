# ToPersia AI ↔ ToPersia Core integration notes

Internal developer documentation. Not shipped to end users; not
auto-generated - keep it updated by hand when this integration changes.

## Where this plugin sits

ToPersia AI is a **read-only consumer** of ToPersia Core's data layer. It
never writes `entity_uid`, never creates its own entity/relation system, and
guards every Core call with `function_exists()` so it keeps working (in a
degraded, generic mode) on a site without Core/JetEngine installed.

## Global Entity ID (`entity_uid`)

**Correction vs. an earlier assumption:** the canonical format is
**`https://topersia.com/e/{type-segment}/{slug}`** — it includes a type
segment. A bare `/e/{slug}` was assumed at one point and is wrong: WordPress
only enforces slug uniqueness *per post type*, so two entities of different
types could otherwise collide on the same `@id`.

- Meta key: `entity_uid` (plain `get_post_meta()`, not wrapped by any
  Core helper - `topersia_entity_meta()` is for plain-text content fields).
- Written once, by Core, on `save_post` (priority 1000), only on the
  **default-language** post. Immutable after that.
- `TOPERSIA_UID_BASE` constant = `https://topersia.com/e`.
- Read it the same way `topersia-schema` does (`topersia_schema_uid()`):
  `get_post_meta( $post_id, 'entity_uid', true )`.
- The type segment can be parsed back out of an existing `entity_uid`
  (`substr` after `TOPERSIA_UID_BASE . '/'`, first path segment) rather than
  recomputed - see `TP_AI_Context::entity_type()`.

## Shared entity meta-box fields (real names)

All ~42 `tp_*` entity post types share one JetEngine meta box. Fields this
plugin actually reads (via `topersia_entity_meta( $post_id, $key )`,
guarded behind `function_exists('topersia_entity_meta')`):

| Key | Notes |
|---|---|
| `name_fa` / `name_en` / `name_ar` | Localized names. |
| `short_summary` | 15–30 words, editor-written. |
| `overview_text` | 300–700 words, WYSIWYG. |
| `hero_image` | Attachment **ID** (not a URL) - resolve with `wp_get_attachment_image_url()`. |
| `ai_summary` | 2–4 factual sentences, **written for AEO/answer-engine extraction**. Curated, not plugin-generated. `TP_AI_Internal::curated_summary()` prefers this over algorithmic extraction when present. |
| `meta_description` | SEO description. |

**Do not write to `ai_summary` or `short_summary` from this plugin.** They
belong to the content/SEO team's editorial pipeline and are also consumed
by `topersia-schema` for JSON-LD. This plugin's own AI-generated output is
stored separately (`TP_AI_DB` / `_tpas_ai_*` post meta) so the two can never
collide.

## `topersia_is_entity( $post_type )`

Just checks the `tp_` prefix. Used to default the plugin's "post_types"
setting to every real entity type instead of a hardcoded `post`/`page`.

## JetEngine relations (confirmed real API)

`topersia_relation( $relation_id )` returns a JetEngine relation object (or
`false`). **The object's real read methods are:**

```php
$relation->get_parents( $post_id, 'ids' );   // not get_related()
$relation->get_children( $post_id, 'ids' );  // not get_related_items()
```

(Confirmed against Core's own usage in `topersia-core.php`, not guessed.)
Core exposes named constants only for the geographic G-series chain:

- `TOPERSIA_REL_PROVINCE_COUNTY`
- `TOPERSIA_REL_COUNTY_CITY`

For every other entity type (attractions, hotels, routes, ...) there is no
named relation constant, so `TP_AI_Context::relations()` falls back to
enumerating `jet_engine()->relations->get_relations()` and calling
`get_children()`/`get_parents()` on each one directly.

## Logging

`error_log()` is disabled on the production host and has caused a fatal
error before. **Never call it.** Use:

```php
if ( function_exists( 'topersia_log' ) ) {
    topersia_log( $message, 'error', 'ai', array( /* context */ ) );
}
```

## Known follow-ups (not done in this pass)

- A real entity-candidate retrieval system for the Travel Planner (nearby
  attractions/restaurants with distance/hours) - `relations()` here only
  produces lightweight related-title strings for the Summary feature.
- Admin menu integration under Core's `topersia` top-level menu (the
  chain pattern is already established by `topersia-user-core`; AI's admin
  screen has not been migrated to it yet).
- `ai_unavailable` and a few other new/changed strings are only translated
  into Persian and English so far; the other 7 languages fall back to
  English via `TP_AI_Localize::all()`'s merge.
