# ToPersia User Core 1.10.4

- Fullscreen frontend user dashboard with a site-independent shell.
- Save, follow, reviews and the login/sign-up window belong to this plugin;
  ToPersia Render only builds buttons and calls `ToPersiaUserCore.call()` /
  `ToPersiaUserCore.openLogin()`.
- Follow: user meta `_topersia_followed` (WPML default-language IDs); the
  "Following" tab lists each entity with unfollow and its latest news.
- Reviews: JetReviews review types per family (destination, nature, heritage,
  event, border crossing); logged-in only, always pending admin approval.
- Published only (P5-D247, 1.10.1): the panel never shows the title or link of a
  post that is no longer published; the review row stays with "This item is no
  longer available". An unpublished translation never replaces the original.
- SMS (OTP) login via the Mediana gateway, with a three-layer fail-closed rate limiter.
- Inline SVG icon set (21 icons); Font Awesome was removed in 1.9.6.
- Icon colors inherit ToPersia Render design tokens via `currentColor`.
- Built-in tabs and custom shortcode tabs are supported.
- Saved places live in a dedicated table `{prefix}topersia_saved`, keyed `(user_id, post_id)`.
- Saved places are stored under the WPML **default-language** post ID and rendered
  in the current language, so one entity never produces duplicate cards.
- The account page is resolved by page ID, not by a fixed URL, so `/en/account/`
  and `/ar/account/` work correctly.
- WPML string registration runs once per fingerprint in admin context only, never
  on front-end requests.
- Admin UI is a standalone WordPress admin menu, not a Settings submenu.
- Persian/Arabic use RTL content; English uses LTR content while dashboard
  navigation remains on the right.
- `uninstall.php` removes both tables, all six options, plugin user meta, and the
  rate-limit transients. JetReviews data is deliberately left untouched.

## Version history

See `ROADMAP.md` for the phase plan and `CHANGELOG.md` for what shipped when.
