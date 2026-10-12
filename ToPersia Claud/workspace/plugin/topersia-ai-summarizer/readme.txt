=== ToPersia AI Summarizer ===
Contributors: topersia
Tags: ai, ai-summarizer, chatgpt, perplexity, claude, grok, copilot, gemini, deepseek, summary, travel, translation
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Buttons that open AI assistants with a ready structured-summary prompt, plus an on-page AI panel that generates briefs server-side from the current page. Built for ToPersia with full 9-language support.

== Description ==

Two components, one plugin, dedicated to ToPersia.

**AI Assistant buttons.** Adds a row of buttons (ChatGPT, Perplexity, Claude, Grok, Copilot, Gemini and DeepSeek) before, after or below the featured image. Each button opens the chosen assistant with a structured, localized summary prompt already prepared for the current page (URL and title filled in). A copy-promo button copies the prompt instead.

**On-page AI panel.** An "AI TRAVEL BRIEF" panel styled like the ToPersia G3 template. It generates a structured summary, key points or a travel plan directly on the page from the page's own content, using an OpenAI-compatible engine (DeepSeek by default). Output is cached per post/language, rate-limited per IP, and never trusts model-provided HTML.

Both components are fully wrapped by filters, so they can be altered per page, per post, per language or per service.

= Languages =

The AI wording (heading, structured prompt and panel UI) is provided out of the box in all nine ToPersia languages: Persian, English, Arabic, French, German, Spanish, Russian, Turkish and Chinese. Language is detected from WPML or the site locale.

= Integration =

* Shortcodes: `[tpas]`, `[tpas_ai]`, aliases `[topersia_ai]`, `[topersia_ai_panel]`
* Gutenberg blocks: `tpas/buttons` and `tpas/panel` (server-rendered)
* Elementor widget: one widget, two modes (buttons / panel)
* Auto-insertion by position with per-post override boxes in the editor
* Anonymous click tracking (optional) with per-post and global totals

= Who makes the summaries (1.3.0 · P5-D358) =

Only the site admin, in WordPress, with the engine chosen in the settings. No visitor - guest or logged in - asks for a generation, so API tokens and cost stay under the admin's control.

* Metabox "ToPersia AI" in the post editor: the state of each output type in this post's language (Not made · Fresh · Stale) and a Generate / Regenerate button.
* Bulk action "Generate AI summary" in the posts list of every enabled type: jobs go to the ToPersia Core queue (Action Scheduler, group `topersia`, hook `tpas_generate`), each post in its own language, fresh outputs are not rebuilt, up to "Admin generations per day". Without the queue, a small batch runs at once and the rest is reported.
* "Generate automatically after publishing or a content change" (off by default): the same queue, the same cap.
* The site daily cap of ToPersia Render also applies to every admin path.
* An unpublished post or translation has no summary.
* `tpas_ai_generate` answers only a user who can `edit_post` that post (nonce `tpas_ai`); everyone else gets 403 before any engine call. "Allow visitor requests (later versions)" is off by default; while off, no other path generates and the front-end script sends no generation request.

= Reading a summary: tpas_get_summary() =

`tpas_get_summary( int $post_id, string $type = 'summary', string $lang = '' )` - the stable read API for themes and ToPersia Render.

* `$type`: `summary`, `keypoints` or `plan`. `$lang`: a language code; empty = the post's own language (WPML).
* Returns `array( 'text' => string, 'html' => string, 'stale' => bool, 'updated' => int )` or `null` (nothing stored, unknown type, unpublished post).
* `html` is the formatted, escaped output; `stale` is true when the post content changed after the output was made - the caller decides whether to show it.
* Reads the store only: it never generates, never calls an engine and never queues a job, whoever is viewing.

=Filters=

The plugin exposes filters such as `tpas_services`, `tpas_service_url`, `tpas_prompt`, `tpas_context`, `tpas_panel_html`, `tpas_buttons_html`, `tpas_api_payload`, `tpas_api_request_args` and more. See the code docblocks for details.

== Installation ==

1. Upload the `topersia-ai-summarizer` folder to `/wp-content/plugins/`, or install the ZIP through Plugins → Add New.
2. Activate the plugin.
3. Go to Settings → ToPersia AI, pick the assistants to show, choose a position, and configure the internal engine (API key).
4. Use `[tpas_ai]` in any post, drop the block, or set "Panel position" to auto-insert.

== Frequently Asked Questions ==

= How is the language chosen? =

The plugin reads WPML (Polylang compatible filter) and falls back to `get_locale()`. Unsupported locales fall back to English.

= Which engine powers the on-page panel? =

DeepSeek by default through `/v1/chat/completions`. Any OpenAI-compatible endpoint can be set under Settings → On-page AI panel (custom engine option).

= Does the panel store my API key? =

The key is stored in the `tpas_settings` option, never printed, and kept when you save the settings screen with an empty key field.

= Do buttons open in a new tab? =

Yes by default, with `rel="noopener nofollow"`, both configurable.

== Changelog ==

= 1.2.3 =
* Security - P5-D355 (R56): the API key can be pinned in wp-config with `TOPERSIA_AI_API_KEY`; the constant wins over any stored key, the engine's key field then reads "Set in wp-config" and saving the settings stores nothing from the form. The key is no longer passed to the public `tpas_api_payload` filter in any of the three protocols: the filter runs without it (it still gets the body and a keyless endpoint) and the key is added to the request after it.
* Security - P5-D356 (R53): rebuilding a stale output is counted against the daily per-user and per-site caps (the cap gate used to step aside whenever a stale row existed). The filter `tpas_rebuild_counts_cap` turns this off if the gate ever counts stale rebuilds itself.
* Security - P5-D356 (R54): the internal engine's row is stamped with the content hash when saved, goes stale on an edit and is regenerated (before, it never went stale); an internal row that never had a hash is treated as stale. A stale internal output is no longer shown to visitors.
* Security - P5-D356 (R55): the tag-stripped post_content is part of an output's freshness (own digest next to the core hash; a tag-only edit does not make it stale).
* Security - P5-D356 (R24): an error message from the server or the provider is put into the panel as text, never parsed as markup; no JS of the plugin builds markup from a message.
* Security - P5-D356 (A04): the browser reply no longer carries `provider` and `model` (P5-D317 - no engine is shown); they stay in the summaries table and reach the new `tpas_reply` action (and the debug log when WP_DEBUG_LOG is on).
* Tests: tests/test-12-security-123.php - each claim also runs against a mutant that must fail.

= 1.2.2 =
* Security - P5-D356 (R36): generation requires a post id. A browser-sent URL is never trusted: without a post the request is rejected (400), with one it is ignored - the prompt only carries the post's own permalink in the page language (WPML `wpml_permalink`). The published, content-type, rate-limit and cache gates therefore run on every request. The panel no longer sends the page URL.
* Security - P5-D356 (R47): the rate limiter's IP follows the same rule as ToPersia User Core - X-Forwarded-For only when the site declares TOPERSIA_TRUST_PROXY, otherwise REMOTE_ADDR; X-Real-IP and CF-Connecting-IP are never trusted.
* Security - P5-D355 (R38): engine API keys are write-only in the settings screen (never printed; placeholder "Saved - type a new value to change it"; an empty field keeps the key; "Clear stored key" is the only way to delete it). The settings row that holds the keys is no longer autoloaded (on create, on save and on every schema upgrade).
* A markdown link in model output keeps only its label; no target (javascript:, tracking URLs) is displayed or copied.
* Tests: 13 of the 14 known failures were stale claims (panel contract since 1.1, rate-limit keys since 1.1, a tautological icon check, stub shortcode matching, cache order); they now assert the current behaviour with the reason in place. The 14th (markdown link target shown) was a real defect, fixed above.

= 1.2.1 =
* Text generation engine and key come only from this plugin's own settings: ToPersia Core's "External services" no longer has an ai.text row (the site owner's decision). The core stays the content-hash cache layer.
* Lowest token use by default: 700 output tokens (was 1200), 8000 context characters (was 40000), 30-day cache (was 7; a content edit marks the output stale through the hash), no automatic first generation.

= 1.1.1 =
* Now a workspace project of ToPersia (third plugin, own version and zip).
* AI context facts: only the `card` and `details` fields of TP-G-FIELDMAP.json, labelled and formatted by ToPersia Render; never a serialized value, an underscore key, a WPML key or a status field. The internal engine can no longer produce "Copied Media Ids: a:0:{}".
* Only published, password-free posts reach the AI context, and generation is refused for any other post (P5-D247).
* Upgrade routine drops the pre-1.1 unique key `post_language`, which dbDelta could not remove and which kept key points and plans out of the summaries table.
* Serialized-text check follows WordPress is_serialized() (strict) when WordPress is absent, so object payloads (O:…) are caught too.
* Persian strings use the single-codepoint ۀ (U+06C0) as the rest of ToPersia (P5-D248); the fa_IR catalogue was rewritten in place.
* Test stubs brought up to date so the suite runs to the end; wp_send_json_* stubs record the HTTP status like WordPress. New group "Phase 1 / 4.4.0" in tests/test-09-phase1.php.

= 1.0.0 =
* First release for ToPersia: 7 assistants, on-page AI panel, 9 languages, G3-styled UI, blocks, shortcodes and Elementor widget.

== Upgrade Notice ==

= 1.1.1 =
Drops the stale post_language unique key of the summaries table on the first load after upgrading (idempotent).

= 1.0.0 =
Initial release.