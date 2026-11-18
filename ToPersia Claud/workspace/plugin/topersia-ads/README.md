# ToPersia Ads 1.0.0

Direct ad sales for ToPersia (batch 54, P5-D621 to P5-D625). Fixed placements on
every public page type, time / impression / click campaigns, anonymous aggregate
statistics, an admin report and a sales page. No external JS, no ad network.

## Scope and switch

- **Local only until the NotebookLM audit.** `topersia_ads_enabled()` is
  `'local' === wp_get_environment_type()` by default (same gate as every new
  structure). Off means no post type, no table, no route, no output; ToPersia
  Render then behaves exactly as before.
- Render only calls hooks: `topersia_ad_slot`, `topersia_ad_inline`,
  `topersia_ad_feed` (helpers `topersia_g_ad_*` in `topersia-g-core.php`) and
  `topersia_g_footer_ads_pre` for the footer. Destination pages get their two
  zones from the existing `topersia_g_layout` filter.

## Data

- Private post type `tp_ad`, one post for all three languages. Meta `_tp_ad`
  holds advertiser, URL, slots, one image per ratio (`wide` 1200×300, `mwide`
  800×400, `rect` 600×500, `card` 800×600), alt text per language, target
  languages, device, page types, start/end, caps, weight, priority and an
  internal note. Incomplete campaigns are saved as paused.
- `_tp_ad_imp` / `_tp_ad_clk`: running totals used for caps.
- Tables `{prefix}tp_ad_stats` (daily: ad, slot, language, impressions, clicks)
  and `{prefix}tp_ad_stats_month`; daily rows older than 13 months are rolled up.
  Versioned, reversible migration: `php scripts/tp-ads-migrate.php up|down --confirm|status`.
- No IP, cookie or user ID is stored. Click de-duplication uses a salted daily
  hash in a 30-minute transient; impressions are validated with a daily HMAC token
  (valid three days) because cached HTML cannot carry a per-user nonce.

## Placements

| id | where | desktop / mobile |
|---|---|---|
| `home-mid` | home page, twice | 4:1 / 2:1 |
| `hub-top` | below the hub toolbar | 4:1 / — |
| `hub-infeed` | after card 4, then every 8 cards | 4:3 / 4:3 |
| `single-inline` | after the 3rd top-level paragraph (news, article) | 4:1 / 2:1 |
| `single-rail` | sidebar, sticky on desktop only | 6:5 / — |
| `single-end` | before related items | 4:1 / 2:1 |
| `entity-rail` | destination sidebar, after the map | 6:5 / — |
| `entity-mid` | between destination sections | 4:1 / 2:1 |
| `footer` | every page, 1–4 side by side | 4:1 |

Never in the hero, legal pages, 404, search or account pages. No pop-ups and no
mobile sticky units. Density: at most 3 units on desktop and 2 on mobile per page
(footer excluded). Selection: filter, then priority, then weight, rotated
deterministically in 10-minute windows (`TOPERSIA_ADS_WINDOW`), so cached HTML is
the same for everyone within a window. An empty placement renders nothing for
visitors; users with `manage_tp_ads` see a dotted "Empty placement: X" marker.

## Front end

- Server-rendered HTML, fixed aspect ratio, explicit image dimensions, lazy
  `<picture>`, `rel="sponsored noopener"`, `target="_blank"`, a translated "Ad"
  label, `data-nosnippet`, colours from `--tp-*` tokens only.
- Clicks go through `/ad-go/{id}/` (302, `noindex`, blocked in `robots.txt`).
- `assets/tp-ads.js` (< 2 KB gzipped) counts an impression when at least half of
  a unit is visible for one second and sends batches with `sendBeacon` to
  `POST /wp-json/topersia-ads/v1/imp` (rate-limited, body ≤ 4 KB).
- Demo ads (`_tp_demo`) only exist in preview mode and are labelled
  "Demo sample". `php scripts/tp-ads-demo.php --launch` fails while any remain.

## Admin

Menu **Ads**: campaign list with status, placements, end date, impressions,
clicks and CTR; bulk pause / extend 7 / extend 30 days; report with date, slot
and status filters, totals, "ending soon" (7 days), empty placements and CSV
export. Every screen checks `manage_tp_ads` and a nonce. Guide:
`docs/ops/ADS-ADMIN.md`.

## Sales page

Shortcode `[tp_advertise]` on `/advertise/` (fa, en, ar): placement map, real
figures only, sales models, "Price: contact us", contact link. `noindex` until
the option `tp_ads_advertise_index` is switched on with the user's approval.

## Translation

Catalog `languages/topersia-ads-{en_US,ar}` (po, mo, l10n.php), built from
`scripts/i18n/topersia-ads.php` with `php scripts/tp-ads-i18n.php`; `--check` runs
inside `tests/assert-54.php`.
