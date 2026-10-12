# RECONCILE.md — تطبیق دو ممیزی مستقل ۲د

> فاز ۲د · ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری B (دور دوم) — **فقط سند**؛ هیچ کد، پایگاه‌داده یا تنظیمی عوض نشد.
> **الف** = همین پوشه در `main` (commit `9ae7c73e`) · **ب** = شاخۀ `claude/tender-volta-apf3de` (commit `c3488880`)، فقط خوانده شد (`git show FETCH_HEAD:…`)، merge نشد.
> مبنای سنجش دوباره: کد `main` امروز (`4649b21b`). برچسب‌ها همان قاعدۀ ۲د: `VERIFIED` فقط با مسیر:خط · `INFERRED` · `ASSUMED` · `UNKNOWN` با فرمان محلی.
> نام‌اختصار: `AI` = `plugin/topersia-ai-summarizer` · `CORE` = `plugin/topersia-core` · `RENDER` = `plugin/topersia-render` · `UC` = `plugin/topersia-user-core` · `SCHEMA` = `plugin/topersia-schema` · `REF` = `reference` · `DOC` = `docs` · `MAP/` = `RENDER/assets/map` · `WP` = `geo` در ریشۀ مخزن · `ROOT` = ریشۀ مخزن (همه نسبت به `ToPersia Claud/workspace/` جز `WP` و `ROOT`).
> هیچ مقدار کلید، رمز، پیشوند پایگاه‌داده، نام مدیر، شماره یا دادۀ کاربر در این سند نیست؛ فقط نام، مسیر و شمار.
> abzarwp: ردیف‌های موجود هر دو ممیزی همان‌طور ماندند؛ هیچ بررسی تازه‌ای نشد (تصمیم کاربر).

---

## ۰ — آیا دو ممیزی یک کد را خوانده‌اند؟

| پرسش | پاسخ | برچسب |
|---|---|---|
| پایۀ ب | `3526a5d2` (نیای `main`) | `[VERIFIED]` `git merge-base main FETCH_HEAD` |
| فرق کد افزونه‌های workspace میان پایۀ ب و `main` | **فقط یک پرونده**: `RENDER/assets/topersia-g-zones.js` (+۱۵ خط در ۴۹۴–۵۳۳، نگهبان اسکرول هدر) | `[VERIFIED]` `git diff --stat 3526a5d2 HEAD -- "ToPersia Claud/workspace/plugin"` |
| پیامد برای شمارۀ خط | هر ارجاع هر دو ممیزی به `topersia-g-zones.js` بالای خط ~۵۲۰ امروز **۱۵ خط پایین‌تر** است (مثلاً `setRTLTextPlugin`: ممیزی‌ها ۲۰۶۵ ← امروز `:2080`؛ `style()`: ۲۰۸۹–۲۰۹۹ ← امروز `:2104-2114`) | `[VERIFIED]` |
| فرق کپی نصب‌شدۀ `WP/wp-content/plugins` | میان پایۀ ب و `main` commit `b1ec34bb` («نسخۀ نصب‌شدۀ core و render هم‌بایت با workspace») آمده؛ پس R44 در هر دو ممیزی امروز **کهنه** است (بخش ۴، ناسازگاری ۵) | `[VERIFIED]` `git log 3526a5d2..9ae7c73e -- geo/wp-content/plugins/topersia-core` |
| نتیجه | یافته‌های کدی دو ممیزی هم‌سنج‌اند؛ هر جا اختلاف هست، اختلاف خوانش است نه کد | `[INFERRED]` |

---

## ۱ — یافته‌های «هر دو» (هم‌خوان؛ حکم نهایی = تأیید)

| # | یافته | الف | ب | حکم نهایی | برچسب نهایی |
|---|---|---|---|---|---|
| H01 | کلید LLM متن ساده در `tpas_settings` با autoload (جزئیات بخش ۳-ج) | R08 · AI §۵ | S03 · AI §۴ | تأیید | `[VERIFIED]` `AI/includes/class-tp-ai-options.php:18,553-567` |
| H02 | کلید پیامک Mediana متن ساده و چاپ در HTML پیشخوان | R38 | S02 | تأیید | `[VERIFIED]` `UC/includes/class-topersia-admin.php:534` |
| H03 | دکمه‌های ۷ دستیار بیرونی پیش‌فرض روشن (تضاد با P5-D255)؛ مقدار زنده نامعلوم | AI ۲٫۳ | S01 | تأیید | `[VERIFIED]` `AI/includes/class-tp-ai-options.php:42,44` · زنده `[UNKNOWN]` (LQ-AI) |
| H04 | شناسۀ کش `sha1` بی نسخۀ پرامت/ارائه‌دهنده/مدل؛ `ai.text` بیرون از کاتالوگ ← `p/m/v` تهی | AI §۴ | AI §۳ | تأیید | `[VERIFIED]` `CORE/includes/topersia-cache.php:275-288` · `CORE/includes/topersia-services.php:47-57` |
| H05 | بی قفل هم‌زمانی/idempotency: دو کلیک = دو فراخوانی پولی | R12 | AI §۳ · ریسک ۱۲ | تأیید | `[VERIFIED]` `CORE/includes/topersia-cache.php:405-440` |
| H06 | سه سقف هم‌پوشان (ساعتی AI · روزانۀ render · ستون مرده در سرویس‌ها) | R46 | AI §۸ · SYSTEM §۱۰ | تأیید؛ ب «مسیر مرده» را `INFERRED` گذاشته بود، الف `VERIFIED` — شاهد الف کافی است | `[VERIFIED]` `RENDER/includes/topersia-g-ai.php:28-42` · `CORE/includes/topersia-services.php:285-288` |
| H07 | `client_ip()` خلاصه‌ساز `X-Forwarded-For` را باور می‌کند؛ user-core فقط با `TOPERSIA_TRUST_PROXY` | R47 | S16 | تأیید | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:516-531` · `UC/includes/class-topersia-auth.php:145-157` |
| H08 | `tpas_track` بی‌ورود؛ `update_option('tpas_stats')` در هر کلیک | R49 | S15 | تأیید + افزودۀ ب (بخش ۳، B07) | `[VERIFIED]` `AI/includes/class-tp-ai-tracker.php:24-25,56` |
| H09 | تولید هم‌زمان درون AJAX با مهلت ۶۰ ثانیه، بی صف | R26 | AI §۶ | تأیید | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:163,255,364` |
| H10 | بی fallback میان موتورها؛ ادعای P5-D317 پیاده نیست | AI §۵ | AI §۴ | تأیید | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:72-101` |
| H11 | تلهمتری: نه توکن، نه هزینه، نه تأخیر؛ توصیۀ هر دو: جدول `runs` در افزونۀ AI (حکم #17) | AI §۸ | AI §۱۰ | تأیید؛ توصیۀ یکسان | `[INFERRED]` (توصیه) |
| H12 | `uninstall.php` خلاصه‌ساز جدول‌ها و `tpas_db_version`/`tpas_prompt_overrides` را نمی‌افتد | AI §۳ | AI §۲ | تأیید | `[VERIFIED]` `AI/uninstall.php:13-34` |
| H13 | `/map-points`: سقف ثابت ۴۰۰، بی `meta`، دروازۀ فقط محلی | MAP §۶ | MAP §۷ · S05 | تأیید | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:80-90,116,130-132` |
| H14 | نمایۀ مکانی `entity_uid` ندارد | MAP §۱ | MAP §۹ | تأیید | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:63-77` |
| H15 | بنچمارک A/B پیش‌تر اجرا شده؛ حکم ARCH «پیش از ساخت جدول» گذشته | MAP §۷ | MAP §۹ | تأیید | `[VERIFIED]` `DOC/GEO-BENCH-2F.md` (وجود) · اعداد `[INFERRED]` |
| H16 | AS: دو بسته‌بندی 3.9.3 (core و Rank Math)؛ سنجاق اجرایی نیست | R42 | S19 · SYSTEM §۳ | تأیید (بخش ۴، ناسازگاری ۹) | `[VERIFIED]` `CORE/vendor/action-scheduler/classes/ActionScheduler_Versions.php:34-37` |
| H17 | دو کرون وردپرس (`topersia_log_prune` · `topersia_user_core_prune`) وابسته به WP-Cron/کرون سیستمی؛ `DISABLE_WP_CRON` زنده نامعلوم | SYSTEM §۵ | S09 | تأیید | `[VERIFIED]` (کد) · زنده `[UNKNOWN]` |
| H18 | رویداد: `event_start_date`/`event_end_date` نوع `date` در E1·E3·E5 هست؛ `next_*` نیست؛ رندر و JSON-LD ندارند | EVENTS §۱ | EVENTS §۲ · S07 | تأیید (قطعی‌شده در بخش ۳-ب) | `[VERIFIED]` |
| H19 | شناسۀ رابطه‌های `topersia-schema` عددی و سخت‌کد؛ export ۱۳۵ در برابر محلی ۱۳۹ | R21 | S28 · S29 | تأیید | `[VERIFIED]` `SCHEMA/topersia-schema.php:333-336` |
| H20 | کشور کاربر متن آزاد (`_topersia_country`)، نه ISO | USER §۴ | S21 | تأیید | `[VERIFIED]` `UC/includes/class-topersia-profile.php:21,27` |
| H21 | mu-pluginهای `tp-local-*` در مخزن | SYSTEM §۱٫۲ | S12 | تأیید (کاهش‌یافته: هر کدام گیت محیط دارد) | `[VERIFIED]` `WP/wp-content/mu-plugins/` |
| H22 | سه جای ذخیره برای یک خروجی AI (جدول + `_tpas_ai_*` + `_tpas_hash_*`) | AI §۳ | AI §۱۰ (آمار کلیک در سه جا) | تأیید هر دو (دو موضوع جدا) | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:326-341` |
| H23 | سه REST عمومی GET (`weather` · `air` · `map-points`)؛ سه AJAX بی‌ورود (`tpas_track` · `topersia_send_otp` · `topersia_verify_otp`) | SYSTEM §۳-۴ | SYSTEM §۶-۷ | تأیید | `[VERIFIED]` |
| H24 | abzarwp در ۱۱ افزونه و پوستۀ woodmart | R39 | S30 | **بی بررسی تازه** (تصمیم کاربر)؛ ردیف‌های هر دو همان‌طور | همان برچسب‌های هر ممیزی |

---

## ۲ — «فقط الف»: سنجش دوباره

| # | یافتۀ الف | سنجش دور دوم (کد امروز) | حکم | برچسب |
|---|---|---|---|---|
| A01 | R36 — `post=0&url=…` بررسی منتشرشده/نوع مجاز/کش را دور می‌زند | `if ( ! $post && ! $url )` فقط هر دو تهی را رد می‌کند؛ سه بررسی بعدی با `$post &&` آغاز می‌شوند | **تأیید** | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:101,108,112` |
| A02 | R37 — شمارۀ موبایل به‌عنوان `display_name` عمومی | نام ورود `'tp_' . $mobile`؛ `wp_create_user` بی `display_name` ← وردپرس `user_login` را می‌گذارد؛ بلوک نظرها `display_name` را چاپ می‌کند. ب این را ندید (فقط «نام کاربری `tp_{شماره}`» را ثبت کرد) | **تأیید** (وقوع روی داده `UNKNOWN`) | `[VERIFIED]` `UC/includes/class-topersia-auth.php:531,545` · `WP/wp-includes/user.php:2483` · `RENDER/includes/topersia-g-zones.php:4960-4961` |
| A03 | R24 — پیام خطای سرور بی‌گریز در `innerHTML` | `showError` پیام را در `innerHTML` می‌گذارد؛ پیام از `json.data.message` و آن از `error.message` ارائه‌دهنده | **تأیید**؛ حکم وضعیت الف («جزئی») بر ب («کاهش‌یافته») ترجیح دارد | `[VERIFIED]` `AI/assets/js/topersia-ai.js:128,171-173` · `AI/includes/class-tp-ai-api.php:187-190` |
| A04 | نام ارائه‌دهنده و مدل در پاسخ AJAX (مغایر حکم #7) | `'provider' => …, 'model' => …` در پاسخ موفق | **تأیید** | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:132-133` |
| A05 | ستون `source` در `get_cached()` نادیده؛ متن داخلی جای LLM می‌نشیند | با B05 (بخش ۳) یکی می‌شود: ردیف داخلی هش ندارد و هرگز کهنه نمی‌شود | **تأیید و ادغام با B05** | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:309,326` · `AI/includes/class-tp-ai-core-bridge.php:79,91-93` |
| A06 | R40 — حساب بی ایمیل واقعی؛ بی مسیر بازیابی | دامنۀ ایمیل ساختگی `users.topersia.com` | تأیید (ب در USER §۱ همین را به‌عنوان نکته دارد، نه ریسک) | `[VERIFIED]` `UC/includes/class-topersia-auth.php:542-543` |
| A07 | R41 — نام کاربری مدیر در کد «هست» | ناسازگاری ۲ (بخش ۴) | **اصلاح برچسب**: وقوع تاریخی `INFERRED`، وضع امروز `UNKNOWN` | بخش ۴ |
| A08 | R43 — چهار دروازۀ «فقط محلی تا ممیزی پیوست الف» بی محرک | کامنت دروازه و منسوخ‌بودن پیوست الف | تأیید | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:11-12` |
| A09 | R45 — کش‌ها و شمارنده‌ها در ترنزینت بی کش شیء | انبارۀ ترنزینت | تأیید (حجم `UNKNOWN`) | `[VERIFIED]` `CORE/includes/topersia-cache.php:329-331` |
| A10 | R48 — سقف ۱۲۰ پیامک در ساعت برای کل سایت | ب همین عدد را در جریان ورود دارد، نه به‌عنوان ریسک | تأیید | `[VERIFIED]` `UC/includes/class-topersia-auth.php:29-47` |
| A11 | R50 — مستند کهنۀ `UC/ARCHITECTURE.md` و ادعای P5-D317 | — | تأیید (کم‌اهمیت) | `[VERIFIED]` `UC/includes/class-topersia-db.php:10-19` |
| A12 | SourceGuardian در ۱۹۱ از ۲۶۲ پروندۀ `abzarwp-auto-update` | — | **بی بررسی تازه** (تصمیم کاربر) | همان برچسب الف |
| A13 | `tpas_settings` autoload «بله (پیش‌فرض register_setting)» | ب: «`add_option` بی پارامتر» — هر دو درست، بخش ۳-ج | تأیید | بخش ۳-ج |
| A14 | گواه نمایه با هر ذخیرۀ `tp_*` عوض می‌شود (M4) و نبود قلاب رابطه (M5) | — | تأیید M4 `VERIFIED`؛ M5 همان `INFERRED` | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:141-148,284-295` |
| A15 | «render نظر را مستقیم در جدول JetReviews می‌نویسد» (SYSTEM §۶) | render فقط **می‌خواند**؛ نوشتن از API خود JetReviews در user-core است | **رد** ← جدول «رد شده» | `[VERIFIED]` `RENDER/includes/topersia-g-zones.php:4848,4859` · `UC/includes/class-topersia-review-form.php:205` |
| A16 | «بازه‌های عربی در هر سه دستۀ قلم هست» (MAP §۳) | فقط Regular و Medium؛ Italic تهی | **رد** ← جدول «رد شده» و بخش ۳-الف | `[VERIFIED]` `MAP/fonts/Noto Sans Italic/` |

---

## ۳ — «فقط ب»: سنجش دوباره و سه موضوع قطعی‌شده

### ۳-۰ جدول «فقط ب»

| # | یافتۀ ب | سنجش دور دوم | حکم | شمارۀ تازه در `RISKS.md` (دور دوم) | برچسب |
|---|---|---|---|---|---|
| B01 | قلم Italic بی گلیف فارسی/عربی در ۵ لایه | بخش ۳-الف | **تأیید** | R51 | `[VERIFIED]` |
| B02 | `text-letter-spacing` روی ۵ لایۀ برچسب در `lang=fa` | بخش ۳-الف | **تأیید** | R52 | `[VERIFIED]` |
| B03 | دورزدن سقف روزانه با خروجی «کهنه»: دروازۀ render اگر `get_cached()` چیزی بدهد (حتی کهنه) بی شمارش رد می‌شود و `handle_ajax` همان کهنه را بازمی‌سازد | دروازه: `TP_AI_Summary::get_cached(...)` ← `return`؛ تولید: فقط وقتی `empty($cached['stale'])` از کش جواب می‌دهد | **تأیید** | R53 | `[VERIFIED]` `RENDER/includes/topersia-g-ai.php:142-144` · `AI/includes/class-tp-ai-summary.php:125-128` |
| B04 | `post_content` و گزیده در هش محتوا نیستند | هش = عنوان + فیلدهای صافی `topersia_content_hash_fields` | **تأیید** (برای `tp_*` اثر کم چون متن در متاست؛ برای نوع‌های دیگر `INFERRED`) | R55 | `[VERIFIED]` `CORE/includes/topersia-cache.php:76-103` |
| B05 | ردیف موتور داخلی هش ندارد ← `is_stale` همیشه `false` | `store(...,'internal')` هش نمی‌نویسد؛ فقط پل کش `_tpas_hash_*` را می‌نویسد؛ `topersia_cache_is_stale` با هش تهی `false` | **تأیید** و با A05 ادغام: متن داخلی یک بار ذخیره شود، پس از ویرایش پست هم «تازه» است و جای LLM می‌نشیند | R54 | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:309,326` · `AI/includes/class-tp-ai-core-bridge.php:79,91-93` · `CORE/includes/topersia-cache.php:501-504` |
| B06 | کلید API به صافی عمومی `tpas_api_payload` داده می‌شود | `apply_filters( 'tpas_api_payload', $body, $endpoint, $key )` در هر سه پروتکل | **تأیید** | R56 | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:144,248,353` |
| B07 | `tpas_track` وجود/انتشار پست را نمی‌سنجد؛ متای هر شناسه‌ای را می‌نویسد | فقط `absint`؛ `update_post_meta` بی `get_post` | **تأیید** (افزوده به H08/R49) | R49 (افزوده) | `[VERIFIED]` `AI/includes/class-tp-ai-tracker.php:36-47` |
| B08 | `mb_strtolower` بی گارد در موتور داخلی؛ وردپرس جایگزینش را ندارد | `compat.php` فقط `mb_chr · mb_ord · mb_substr · mb_strlen` | **تأیید** (وجود mbstring زنده `UNKNOWN`) | R57 | `[VERIFIED]` `AI/includes/class-tp-ai-internal.php:346` · `WP/wp-includes/compat.php:126,196,272,354` |
| B09 | `log_errors Off` و `display_errors Off` در `.htaccess` ریشه | بلوک MultiPHP | **تأیید** (مقدار مؤثر زنده `UNKNOWN`) | R58 | `[VERIFIED]` `ROOT/.htaccess:50,52` |
| B10 | core و render `uninstall.php` ندارند | `ls` | **تأیید** | R59 | `[VERIFIED]` (نبود پرونده در `CORE/` و `RENDER/`) |
| B11 | دو منطق «منتشرشده»: `is_live()` در user-core فقط publish + بی‌گذرواژه؛ `topersia_g_published()` نسخۀ زبان جاری WPML را هم می‌سنجد | مقایسۀ دو تابع | **تأیید** | R60 | `[VERIFIED]` `UC/includes/class-topersia-jetengine.php:88-92` · `RENDER/includes/topersia-g-zones.php:762` |
| B12 | پیشوند واقعی پایگاه‌داده در کامنت کد | ب دو خط user-core را دید؛ دور دوم: **۶ پرونده** — `UC/includes/class-topersia-reviews.php` · `UC/uninstall.php` · `RENDER/includes/topersia-g-zones.php:4845` · `tests/assert.php` · `tests/js-logger.js` · `REF/topersia-log-20260927-0659(1).txt` (خروجی لاگ ۱۱۹ خطی در git؛ الگوی شماره/IP/ایمیل در آن صفر) | **تأیید و گسترش** | R61 | `[VERIFIED]` (`grep -rlF` با مقدار از متغیر؛ مقدار چاپ نشد) |
| B13 | ورود فقط شمارۀ ایران `^09[0-9]{9}$` | — | **تأیید** (تصمیم P5-D257 باز) | R62 | `[VERIFIED]` `UC/includes/class-topersia-auth.php:134-135` |
| B14 | نرخ‌سنج OTP بر پایۀ `REMOTE_ADDR`؛ پشت پراکسی/LiteSpeed همه یک IP ← سقف‌های IP و ۱۲۰ سایت مشترک می‌شوند | `TOPERSIA_TRUST_PROXY` در هیچ پروندۀ مخزن تعریف نشده | **تأیید** (کد)؛ اثر زنده `UNKNOWN` | R63 | `[VERIFIED]` `UC/includes/class-topersia-auth.php:145-157` · زنده `[UNKNOWN]` `grep -c TOPERSIA_TRUST_PROXY wp-config.php` |
| B15 | کادر ایران مستطیلی (۲۵–۴۰٫۵ × ۴۴–۶۳٫۵) نقاط همسایه‌ها را می‌پذیرد | — | **تأیید** (اثر `INFERRED`) | R64 | `[VERIFIED]` `CORE/includes/topersia-geo.php:24-26` |
| B16 | نانس مهمان روی صفحۀ کش‌شدۀ LiteSpeed | استنتاج از نانس در HTML | **تأیید با همان برچسب** | R65 | `[INFERRED]` `AI/includes/class-tp-ai-frontend.php:50-54` · TTL زنده `[UNKNOWN]` |
| B17 | ارائه‌دهندۀ پیامک سخت‌کد (`new ToPersia_Mediana_SMS()`)؛ متن خطای ارائه‌دهنده به کاربر | — | **تأیید** (کم) | R66 | `[VERIFIED]` `UC/includes/class-topersia-auth.php:381` · `UC/includes/class-topersia-mediana-sms.php:31-36` |
| B18 | دو کرون وردپرس برخلاف قاعدۀ «هیچ کرون موازی» صف واحد | کامنت `topersia-queue.php` و دو `wp_schedule_event` | **تأیید** (ادغام در H17) | — | `[VERIFIED]` `CORE/includes/topersia-queue.php:12-14` |
| B19 | ردیف یتیم `topersia_saved` پس از حذف پست | نبود `before_delete_post` در user-core | **تأیید با همان برچسب** (کم) | — | `[INFERRED]` |
| B20 | `/map-points` روی نصب‌شده ثبت نیست؛ نصب‌شده عقب‌تر (R44) | امروز نصب‌شده هم‌بایت است | **رد (کهنه)** ← جدول «رد شده» | — | `[VERIFIED]` بخش ۰ |
| B21 | گیت #5 «با نخستین ورودی آزاد» ✓ (ورودی آزاد نیست) | `url` ورودی کاربر است (A01) | **رد** ← ناسازگاری ۳ | — | بخش ۴ |

### ۳-الف — قلم Noto Sans Italic در برچسب‌های نقشه (قطعی)

| پرسش | پاسخ | برچسب |
|---|---|---|
| کدام لایه‌ها | اجرای `basemaps.layers('protomaps', namedFlavor(f), {lang})` با Node برای `fa` · `ar` · `en` × `light` · `dark` (هر شش ترکیب یکسان): از ۷۱ لایه، ۱۴ لایۀ symbol؛ **۵ لایه `text-font: ["Noto Sans Italic"]`**: `water_waterway_label` (رود/آبراه، source-layer `water`) · `water_label_ocean` (دریا، خلیج، تنگه، `water`) · `water_label_lakes` (دریاچه، `water`) · `earth_label_islands` (جزیره، `earth`) · `address_label` (پلاک ساختمان، `buildings`، zoom ≥ ۱۸) | `[VERIFIED]` `MAP/basemaps.js:15` (تابع `k`: `"text-font":[e.italic\|\|"Noto Sans Italic"]` پنج بار) + خروجی اجرا |
| کدام پرونده‌ها | `MAP/fonts/Noto Sans Italic/1536-1791.pbf` · `1792-2047.pbf` · `2048-2303.pbf` = **۲۶ بایت** (تهی)؛ `64512-64767.pbf` · `64768-65023.pbf` = **۲۸ بایت** (تهی)؛ `64256-64511.pbf` = ۵٬۵۶۹ بایت و `65024-65279.pbf` = ۲٬۵۵۱ بایت (بخشی). برای مقایسه Regular: `1536-1791` = ۱۱۰٬۷۱۶ · `64512-64767` = ۲۰۱٬۶۱۲ · `64768-65023` = ۲۳۲٬۳۶۷ بایت؛ Medium مشابه | `[VERIFIED]` (`ls -l`) |
| نسخۀ نصب‌شده | `WP/wp-content/plugins/topersia-render/assets/map/fonts/` هم‌بایت با workspace (بخش ۰) | `[VERIFIED]` |
| پیامد | برچسب «خلیج فارس»، «دریای خزر»، «دریاچۀ ارومیه»، «کیش»، «کارون» در `fa`/`ar` از بازه‌های تهی گلیف نمی‌گیرند؛ پشتۀ قلم تک‌عضوی است و MapLibre قلم دیگری جایگزین نمی‌کند ← برچسب نمایش داده نمی‌شود یا ناقص است. حتی در `en`، نام‌های بی `name:en` که `name` محلی فارسی دارند همین‌طورند | `[INFERRED]` (رفتار MapLibre) — تأیید چشمی محلی: صفحۀ کشور در کروم و فایرفاکس |
| `letter-spacing` | `text-letter-spacing`: `water_waterway_label=0.2` · `water_label_ocean=0.1` · `earth_label_islands=0.1` · `water_label_lakes=0.1` · `places_subplace=0.1` (این آخری Regular است) — قاعدۀ پروژه «`letter-spacing` روی متن فارسی ممنوع» | `[VERIFIED]` خروجی اجرا · `WS/CLAUDE.md` (قواعد کد) |
| کد ما لایه‌ها را دست نمی‌زند | `style()` خروجی `basemaps.layers` را مستقیم می‌گذارد؛ هیچ `setLayoutProperty` نیست | `[VERIFIED]` `RENDER/assets/topersia-g-zones.js:2104-2114` |
| **پیشنهاد اصلاح (فقط پیشنهاد؛ کد نقشه مال کدکس است)** | (۱) **یک خط، بی دست‌زدن به بسته:** در `style()` به‌جای `namedFlavor( flavorOf() )` ← `Object.assign( {}, window.basemaps.namedFlavor( flavorOf() ), { italic: 'Noto Sans Regular' } )`؛ کتابخانه خودش `e.italic` را می‌خواند. آزموده با Node: لایۀ Italic باقی‌مانده = ۰. (۲) پس از ساخت لایه‌ها، برای `lang ∈ {fa, ar}` کلید `text-letter-spacing` از `layout` هر لایه برداشته شود (یک `forEach` روی آرایه). (۳) گارد `assert`: هیچ `Italic` در خروجی `layers()` برای fa/ar و هیچ `text-letter-spacing` نامنفی؛ جهش: برگرداندن خط (۱) باید بیفتد. (۴) پوشۀ `Noto Sans Italic` را نگه دار (برای لاتین) یا حذف کن تا ۶۸۰ کیلوبایت کم شود — تصمیم کدکس | `[VERIFIED]` (آزمون Node روی `MAP/basemaps.js`) |
| آزمون محلی لازم | `node tests/behaviour.js` (نقشه) + `node tests/acceptance-shots.js` و نگاه به برچسب آب در کشور/استان fa و ar | `[UNKNOWN]` تا اجرای محلی |

### ۳-ب — `event_start_date` / `event_end_date` در `REF/Jetengine_Skin.json` (قطعی)

| پرسش | پاسخ | برچسب |
|---|---|---|
| کدام نوع‌ها | `meta-39` «E1 — جشنواره و رویداد» → `tp_festival` (شناسۀ فیلد ۸۹۵۵) · `meta-40` «E3 — رویداد ورزشی» → `tp_sports_event` (۵۴۱۶) · `meta-41` «E5 — مراسم مذهبی عمومی» → `tp_religious_event` (۶۵۸۰)؛ هر کدام فیلد چهارم و پنجم. **`meta-42` «E6 — رویداد طبیعی و فصلی» (`tp_seasonal_event`) هیچ فیلد `date` ندارد**؛ متاباکس مشترک `meta-1` فقط `last_verified_date` | `[VERIFIED]` `REF/Jetengine_Skin.json:1` (پرونده تک‌خطی؛ مسیر JSON `meta_boxes["meta-39".."meta-42"].meta_fields`) |
| نوع فیلد | `type: "date"`، `show_in_rest: true`، عرض ۳۳٪؛ توضیح فیلد: «تاریخ میلادی آغاز آخرین یا آتی‌ترین دورۀ برگزاری. برای رویداد قمری هر سال باید به‌روزرسانی شود.» | `[VERIFIED]` همان |
| قالب ذخیره | کلید `is_timestamp` در export **نیست** ← JetEngine آن را `false` می‌کند و مقدار ورودی HTML `date` را خام (`Y-m-d`) ذخیره می‌کند، نه timestamp | `[VERIFIED]` `WP/wp-content/plugins/jet-engine/includes/components/meta-boxes/post.php:598-612` (`is_timestamp` تهی ← `false`) · مقدار واقعی در پایگاه `[UNKNOWN]` محلی: `wp post meta get <ID> event_start_date` |
| آیا P5-D214 کهنه است؟ | **بله، در دو جهت:** (۱) نام فیلد در P5-D214 / پیوست ج (`next_start_date` / `next_end_date`) در هیچ‌جا نیست؛ (۲) فرض ARCH «تاریخ ساخت‌یافته نه؛ منتظر ممیزی» با export نمی‌خواند — مدل داده برای E1·E3·E5 آماده است. آنچه واقعاً کم است «نمایش» و «JSON-LD» و پوشش E6/P6 است، نه فیلد | `[VERIFIED]` `DOC/SPRINT-4.4-PHASES.md:1266` · `DOC/ARCH-RECONCILIATION-v1_0.md:71` |
| کامنت کد کهنه | «دادۀ ما تاریخ ماشین‌خوان ندارد (calendar_timing متن است)» | `[VERIFIED]` `SCHEMA/topersia-schema.php:255-256` |
| امروز در صفحه و اسکیما | هر چهار نوع E `Thing` با `additionalType`، **بی `startDate`**؛ هر دو فیلد در نقشۀ فیلد رندر «بی‌جا» | `[VERIFIED]` `SCHEMA/topersia-schema.php:333-336` · `DOC/AUDIT-META-2F.csv:1257-1258,1271-1272` |
| **چه چیزی برای `Event` با `startDate` کم است** | (۱) **نمایش تاریخ روی صفحه** (گوگل دادۀ ساخت‌یافتۀ ناپیدا را نمی‌پذیرد) — کار render؛ (۲) **سازندۀ `topersia_schema_event`** در SCHEMA (امروز فقط `place`/`thing`)؛ `startDate`/`endDate` به ISO 8601 (تاریخ خالی بدون ساعت پذیرفته است)؛ (۳) **`location` اجباری**: `Place` با `name` = `venue_name` و `address` (حداقل `addressRegion`/`addressLocality` از زنجیرۀ استان/شهر که از رابطه‌ها هست)؛ فیلد نشانی ساخت‌یافته نداریم؛ (۴) پیشنهادی: `eventStatus` (فیلد نداریم)، `eventAttendanceMode` (ثابت Offline)، `organizer` از `organizer_name` (E1·E3)، `url` از `official_event_url`، `image`؛ (۵) **تکرار**: `is_recurring`/`event_frequency` هست ولی گوگل `eventSchedule` را برای نتیجۀ غنی نمی‌خواند ← برای هر دوره یک `Event`، یعنی همان «آخرین یا آتی‌ترین دوره» که توضیح فیلد می‌گوید؛ (۶) **قاعدۀ تازگی**: رویداد قمری (E5) هر سال دستی به‌روز می‌شود؛ بی پایش، `startDate` گذشته در اسکیما می‌ماند ← قاعده: فقط وقتی `endDate` (یا `startDate`) از امروز − N روز جلوتر است `Event`، وگرنه `Thing`؛ (۷) **E6 و P6**: بی فیلد تاریخ ← `Thing` می‌مانند؛ (۸) **چندزبانه**: فیلد تاریخ در WPML باید `copy` باشد تا ترجمه همان تاریخ را داشته باشد — پیکربندی امروز `UNKNOWN`؛ (۹) **داده**: پر بودن فیلدها | (۱)–(۷) `[VERIFIED]` نبودشان در کد (`grep -rn event_start_date plugin/` جز export درون `UC/includes/jetengine-schema.php` صفر) · قواعد گوگل `[INFERRED]` (مستند گوگل، در مخزن نیست) · (۸) و (۹) `[UNKNOWN]` محلی: `wp db query "SELECT meta_key, COUNT(*) FROM {prefix}postmeta WHERE meta_key IN ('event_start_date','event_end_date') AND meta_value<>'' GROUP BY meta_key"` و `wp option get wpml-tm-custom-xml \| grep -c event_start_date` |

### ۳-ج — کلید هوش مصنوعی در `tpas_settings` (قطعی)

| پرسش | پاسخ | برچسب |
|---|---|---|
| کجا | option `tpas_settings`، زیرآرایۀ `internal_engines[].api_key`؛ ثبت با `register_setting( TP_AI_Options::GROUP, 'tpas_settings', … )` | `[VERIFIED]` `AI/includes/class-tp-ai-options.php:18` · `AI/includes/class-tp-ai-admin.php:94` |
| متن ساده | `sanitize_text_field( $submitted_key )` سپس در آرایه، بی رمزنگاری؛ رمزنگاری sodium فقط در لایۀ سرویس‌های هسته است | `[VERIFIED]` `AI/includes/class-tp-ai-options.php:553-567` · مقایسه `CORE/includes/topersia-services.php:112-157` |
| autoload | ساخت با `add_option( KEY, defaults )` بی پارامتر autoload (فعال‌سازی) و بعد `update_option` از Settings API ← در وردپرس `7.1.2` مقدار `auto` نوشته می‌شود و `auto` در فهرست بارشونده است ← **در هر درخواست به حافظه می‌آید** | `[VERIFIED]` `AI/topersia-ai-summarizer.php:104-105` · `WP/wp-includes/version.php:19` · `WP/wp-includes/option.php:1069,1333-1338,3260` |
| بازنمایش در HTML | نه — فیلد `password` و پس از ذخیره خالی | `[VERIFIED]` `AI/includes/class-tp-ai-options.php:554-566` |
| نشت جانبی | کلید به صافی عمومی `tpas_api_payload` داده می‌شود (R56) | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:144,248,353` |
| ثابت wp-config | ندارد | `[VERIFIED]` (نبود `key_const` برای موتورهای AI) |
| حذف افزونه | `delete_option( 'tpas_settings' )` کلید را می‌برد | `[VERIFIED]` `AI/uninstall.php:15` |
| استثنای آگاهانه از P5-D249 | CLAUDE.md: «تولید متن در این لایه نیست — کلید و موتورش در تنظیمات خود خلاصه‌ساز» | `[VERIFIED]` `WS/CLAUDE.md` (بند کلید سرویس) |
| حجم و وجود ردیف روی محلی/زنده | — | `[UNKNOWN]` محلی: `wp db query "SELECT LENGTH(option_value), autoload FROM {prefix}options WHERE option_name='tpas_settings'"` (فقط طول، نه مقدار) |
| پیشنهاد | (۱) ثابت wp-config برنده (`TOPERSIA_AI_KEY_<id>`)، (۲) رمزنگاری با همان تابع هسته، (۳) `autoload=false` با `wp_set_option_autoload`، (۴) حذف `$key` از امضای صافی `tpas_api_payload` (یا صافی جدا فقط برای سرآیند) | `[INFERRED]` |

---

## ۴ — ناسازگاری‌ها و حکم

| # | موضوع | الف | ب | حکم با شاهد |
|---|---|---|---|---|
| ۱ | پوشش عربی قلم‌ها | «بازه‌های عربی هست» برای هر سه دسته | Italic ندارد | **ب درست است.** الف فقط پوشۀ Regular را فهرست کرده بود. بخش ۳-الف — `[VERIFIED]` |
| ۲ | R41 نام مدیر در کد | «هست» `[INFERRED]` از SPRINT | `UNKNOWN` | **ترکیب:** وقوع تاریخی (یافتۀ ۲ج) `[INFERRED]` `DOC/SPRINT-4.4-PHASES.md:1251,1279`؛ وضع امروز `[UNKNOWN]` — گارد فقط خط‌های **افزوده** را می‌سنجد (`scripts/git-guard:69-74`)، پس آنچه پیش‌تر آمده را نمی‌بیند. وضعیت: باز · کاربر. عنوان الف («هست») به «هست در گذشته؛ امروز نامعلوم» اصلاح شود |
| ۳ | گیت #5 دروازۀ گردشگری | «با شاهد نمی‌خواند» (`post=0&url`) | «✓ ورودی آزاد نیست» | **الف درست است.** `url` ورودی کاربر است و بی `post` مستقیم به پرامت می‌رود — `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:98,101` |
| ۴ | نوشتن در JetReviews | render «نوشتن مستقیم» | نوشتن از API، خواندن SQL | **ب درست است** — A15 |
| ۵ | R44 کپی نصب‌شده عقب‌تر | باز | باز (S06) | **هر دو کهنه برای core/render:** امروز `diff -rq` صفر؛ user-core (۲ مستند) و AI (۳ پروندۀ غیرکد) هنوز فرق دارند. وضعیت → **کاهش‌یافته** (خطر برگشت می‌ماند تا بررسی برابری خودکار نباشد) — `[VERIFIED]` |
| ۶ | R24 XSS خروجی | «جزئی» | «کاهش‌یافته» | **الف**: خروجی موفق امن است، مسیر خطا نه — A03 |
| ۷ | R09 SSRF | «کاهش‌یافته» | «نیمه‌کاهش‌یافته» | **کاهش‌یافته** با توصیۀ `wp_safe_remote_post`: تنها مسیر `base_url` مدیر است و هیچ واکشی از `url` کاربر نیست — `[VERIFIED]` `AI/includes/class-tp-ai-admin.php:833-852` · `AI/includes/class-tp-ai-summary.php:418-461` |
| ۸ | R14 رقابت شمارنده | «باز» | «موکول (اعتبار ساخته نشده)» | **باز**: اعتبار ساخته نشده ولی شمارنده‌های سقف امروز غیراتمی‌اند — `[VERIFIED]` `RENDER/includes/topersia-g-ai.php:101-108` |
| ۹ | AS: کدام نسخه بار می‌شود | «بالاترین نسخه؛ برابر ← اولی» | «ثبت دوم رد می‌شود» | **یکی است**؛ بیان الف دقیق‌تر (حالت نسخۀ بالاتر را هم می‌گوید) |
| ۱۰ | R18 ناهمخوانی ترجمه | «کاهش‌یافته» | «نیمه» | **نیمه**: کد کاهش داده، ولی پیکربندی WPML زنده (1.12 copy) با محلی (1.13 ignore) یکی نیست — `[VERIFIED]` `WS/CLAUDE.md` · زنده `[UNKNOWN]` |
| ۱۱ | R29 محتوای تکراری | «کاهش‌یافته» | `UNKNOWN` | **کاهش‌یافته در کد**؛ ایندکس‌شدن `UNKNOWN` (Search Console) |
| ۱۲ | P5-D214 | «جزئی؛ `next_*` نیست» | «پرچم کهنه» | **هم‌خوان** — بخش ۳-ب |
| ۱۳ | شدت abzarwp (R39/S30) | بحرانی · زیاد | بالا · بالا | **بی حکم** — تصمیم کاربر؛ ردیف‌ها همان‌طور |
| ۱۴ | شمار قابلیت‌ها در FEATURE-MAP | ۱۲۰ | ۹۴ | **دانه‌بندی متفاوت، نه تناقض**؛ طبقه‌ها: READY ۱۷/۱۴ · WITH MODIFICATIONS ۵۲/۳۲ · NEEDS INFRASTRUCTURE ۱۲/۱۱ · FUTURE ۳۲/۲۵ · NOT RECOMMENDED ۱۲/۱۲ (شمارش رشته در هر پرونده). مرجع: پروندۀ الف (main) |
| ۱۵ | autoload `tpas_settings` | «پیش‌فرض register_setting» | «`add_option` بی پارامتر» | **هر دو درست**؛ نتیجه `auto` = بارشونده — بخش ۳-ج |
| ۱۶ | شدت R37 | بحرانی | (ندید) | **بحرانی** (دادۀ شخصی عمومی، بی تعامل کاربر) |

---

## ۵ — رد شده

| # | ادعا | منبع | دلیل رد | شاهد |
|---|---|---|---|---|
| X1 | «بازه‌های عربی در هر سه دستۀ Noto Sans هست» | الف MAP §۳ | پنج بازۀ عربی Italic ۲۶–۲۸ بایت (تهی) | `[VERIFIED]` `MAP/fonts/Noto Sans Italic/` |
| X2 | «render نظر را مستقیم در جدول JetReviews می‌نویسد» | الف SYSTEM §۶ | render فقط `SELECT`؛ نوشتن `add_new_review()` در user-core | `[VERIFIED]` `RENDER/includes/topersia-g-zones.php:4848,4859` · `UC/includes/class-topersia-review-form.php:205` |
| X3 | «`/map-points`، `/air` و نمایۀ مکانی روی کپی نصب‌شده نیستند» (R44/S06) | الف SYSTEM §۱٫۲ · ب MAP §۷، SYSTEM §۶ و §۱۱ | از `b1ec34bb` کپی نصب‌شدۀ core و render هم‌بایت با workspace است | `[VERIFIED]` `diff -rq` صفر · `WP/wp-content/plugins/topersia-core/topersia-core.php:5` (`4.13.1`) |
| X4 | «گیت #5 هم‌خوان: ورودی آزاد نیست» | ب FEATURE-MAP ردیف ۶ | `url` آزاد کاربر به پرامت می‌رود | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:98,101` |
| X5 | «R41 هست» به‌عنوان وضع امروز | الف RISKS | شاهد فقط سند تاریخی است؛ کد امروز از ابر سنجیدنی نیست | ناسازگاری ۲ |
| X6 | «ریسک ۱۴ موکول» | ب RISKS | شمارنده‌های غیراتمی امروز در کار است | ناسازگاری ۸ |

---

## ۶ — افزوده‌های دور دوم در پرونده‌های `main`

| پرونده | چه افزوده شد |
|---|---|
| `RISKS.md` | بخش «ج — افزوده‌های دور دوم»: R51…R66 و اصلاح وضعیت R41 · R44 · R49 (ارجاع به این سند) |
| `MAP.md` | بخش «۹ — دور دوم»: اصلاح ردیف قلم §۳، لایه‌های Italic و letter-spacing، پیشنهاد |
| `EVENTS.md` | بخش «دور دوم»: قالب ذخیره، کهنگی P5-D214، کمبودهای `Event` |
| `AI.md` | بخش «دور دوم»: autoload، صافی کلید، دورزدن سقف با کهنه، ردیف داخلی هرگز کهنه نمی‌شود |
| `SYSTEM.md` | بخش «دور دوم»: R44 کاهش‌یافته، اصلاح ادعای JetReviews، `log_errors`، `mbstring` |

---

## ۷ — شمار برچسب‌ها پس از تطبیق

**یافته‌های این سند** (هر ردیف با برچسب نهایی‌اش، یک بار): بخش‌های ۱ تا ۳-۰.

| دسته | شمار ردیف | VERIFIED | INFERRED | ASSUMED | UNKNOWN (فقط وقوع/زنده) | بی بررسی (abzarwp) |
|---|---|---|---|---|---|---|
| هر دو (H) | 24 | 22 | 1 | 0 | 0 | 1 |
| فقط الف (A) — تأییدشده | 13 | 12 | 0 | 0 | 0 | 1 |
| فقط الف (A) — رد شده | 2 | — | — | — | — | — |
| فقط ب (B) — تأییدشده | 19 | 17 | 2 | 0 | 0 | 0 |
| فقط ب (B) — رد شده | 2 | — | — | — | — | — |
| **جمع یافته‌های پذیرفته** | **56** | **51** | **3** | **0** | **0** | **2** |

نکته‌ها: (۱) A07 (R41) در ستون‌ها نیامده و در ناسازگاری ۲ با برچسب ترکیبی `INFERRED` + `UNKNOWN` آمده است. (۲) چند ردیف `VERIFIED` در کد، وقوع زنده‌شان `UNKNOWN` است (H03 · H17 · A02 · B08 · B09 · B14)؛ فرمان‌ها در `LOCAL-QUERIES.md` و «در انتظار تأیید محلی». (۳) خودشماری دو ممیزی قابل‌جمع نیست: الف ۶۰۲ نشان در ردیف‌ها (شمارش `[…]`)، ب نشان‌های بی‌کروشه با راهنمای سرصفحه — این سند هیچ‌کدام را جای دیگری نمی‌گذارد. (۴) ریسک‌های تازه در `RISKS.md` (بخش ج): ۱۶ ردیف (R51…R66) — `VERIFIED` ۱۴ · `INFERRED` ۱ (R65) · وقوع `UNKNOWN` در ۵ ردیف.

---

## ۸ — اقدام‌های پیشنهادی (به ترتیب اولویت)

> مالک: **کدکس محلی** (lane: `RENDER` · `CORE` · `tests` · `scripts`) · **جلسۀ ابری** (lane: `UC` · `AI` · `tests/assert-uc.php`؛ امروز جلسۀ ابری A) · **کاربر** (تصمیم، داده، میزبان).
> اثر: ● زیاد · ◐ متوسط · ○ کم · — بی اثر. SEO = موتور جست‌وجو · GEO = موتورهای مولد (پاسخ هوش مصنوعی) · AEO = موتورهای پاسخ/قطعۀ برجسته · چندزبانه = fa/en/ar.

| # | اقدام | شاهد | مالک | SEO | GEO | AEO | چندزبانه |
|---|---|---|---|---|---|---|---|
| 1 | **R37** — `display_name` تصادفی/«کاربر ToPersia» هنگام ساخت حساب + مهاجرت حساب‌های موجود؛ بلوک نظرها هرگز `user_login` را نشان ندهد | A02 | جلسۀ ابری (UC) + کدکس (فقط اگر render هم نیاز به گارد دارد) | ○ (اعتماد/E-E-A-T نظرها) | — | — | — |
| 2 | **R36** — `post` اجباری؛ رد `url` بی `post` | A01 | جلسۀ ابری (AI) | — | — | — | — |
| 3 | **R38/H02** — کلید پیامک: فیلد فقط‌نوشتنی، رمزنگاری یا ثابت wp-config | H02 | جلسۀ ابری (UC) | — | — | — | — |
| 4 | **کلید AI** — ثابت wp-config، رمزنگاری با تابع هسته، `autoload=false`، حذف `$key` از `tpas_api_payload` | بخش ۳-ج · R56 | جلسۀ ابری (AI) | — | — | — | — |
| 5 | **R51/R52** — برچسب آب و جزیره با قلم Regular (یک خط `italic`) و حذف `text-letter-spacing` برای fa/ar + گارد assert با جهش | بخش ۳-الف | کدکس محلی (RENDER) | ○ | ◐ (نقشۀ قابل‌ارجاع «خلیج فارس») | — | ● |
| 6 | **رویداد** — (الف) نمایش `event_start_date`/`event_end_date` در صفحۀ E1·E3·E5 به تقویم و رقم زبان صفحه؛ (ب) `topersia_schema_event` با `startDate`/`endDate`/`location`/`organizer`/`url` و قاعدۀ تازگی؛ (ج) WPML: فیلد تاریخ `copy` | بخش ۳-ب | کدکس (الف · RENDER) + جلسۀ ابری یا کدکس با lane تازه برای `SCHEMA` (ب) + کاربر (پر کردن داده؛ تصمیم P5-D214) | ● (نتیجۀ غنی رویداد) | ● | ● («کی برگزار می‌شود؟») | ● |
| 7 | **R53/R54/R55** — شمارش بازسازی کهنه در سقف؛ هش برای ردیف داخلی (یا کهنه‌شمردن ردیف بی هش)؛ `post_content` در هش برای نوع‌های غیر `tp_*` | بخش ۳-۰ | جلسۀ ابری (AI) + کدکس (CORE برای هش) | ◐ (خلاصۀ کهنۀ نمایش‌داده‌شده به مهمان) | ◐ | ◐ | ◐ (هر زبان ردیف جدا) |
| 8 | **R24** — پیام خطا با `textContent` به‌جای `innerHTML` | A03 | جلسۀ ابری (AI) | — | — | — | — |
| 9 | **R63/R48** — تعریف `TOPERSIA_TRUST_PROXY` روی زنده پس از دیدن سرآیند واقعی؛ CAPTCHA پیش از ارسال پیامک | B14 · A10 | کاربر (wp-config زنده) + جلسۀ ابری (UC) | — | — | — | — |
| 10 | **A04** — حذف `provider`/`model` از پاسخ AJAX عمومی | A04 | جلسۀ ابری (AI) | — | — | — | — |
| 11 | **R61** — حذف پیشوند پایگاه‌داده از ۶ پرونده و بیرون‌بردن لاگ `REF/topersia-log-…txt` از git (`.gitignore`؛ گارد `*.log` پسوند `.txt` را نمی‌گیرد) | B12 | جلسۀ ابری (UC) · کدکس (RENDER · tests) · کاربر (پروندۀ `reference/`) | — | — | — | — |
| 12 | **R41** — شمارش محلی بی چاپ: `git grep -c -F -- "$TP_GUARD_ADMIN_USER"` و `git log -S` | ناسازگاری ۲ | کاربر/محلی | — | — | — | — |
| 13 | **R39 abzarwp** — بی تغییر؛ تصمیم کاربر | H24 | کاربر | ◐ (Rank Math Pro/WPML از کانال واسط) | — | — | ◐ (WPML) |
| 14 | **R60** — `is_live()` همان دروازۀ زبان WPML را بگیرد (یا تابع عمومی هسته) | B11 | جلسۀ ابری (UC) | ○ | — | — | ● |
| 15 | **H13** — `/map-points` با `meta` و سقف وابسته به viewport؛ برداشتن دروازۀ «فقط محلی» (R43) با تصمیم معمار | H13 · A08 | کدکس (RENDER · CORE) + کاربر/معمار | ◐ | ◐ | ○ | ◐ |
| 16 | **R57/R58** — گارد `mb_strtolower`؛ `log_errors` روشن روی زنده (پروندۀ خصوصی) | B08 · B09 | جلسۀ ابری (AI) · کاربر (میزبان) | — | — | — | ○ |
| 17 | **H03** — `position=manual` و `services=[]` روی زنده (P5-D255) | H03 | کاربر (پس از LQ-AI) | ○ | ○ | — | — |
| 18 | **R44** — بررسی برابری workspace/نصب‌شده در preflight | ناسازگاری ۵ | کدکس (scripts) | — | — | — | — |
| 19 | **اسناد** — به‌روزرسانی P5-D214 (نام فیلد)، ARCH §۳ و §۴ (بنچمارک، `entity_uid`)، کامنت `SCHEMA/topersia-schema.php:255-256` | ۳-ب · H15 | کاربر/معمار (کلاد وب) | — | — | — | — |
| 20 | **R59/H12** — سیاست حذف داده برای core/render/AI | B10 · H12 | کاربر (تصمیم) | — | — | — | — |

### ده اقدام اول (خلاصه)

1. R37 شماره در `display_name` — ابری (UC).
2. R36 بستن `post=0&url` — ابری (AI).
3. کلید پیامک در HTML و متن ساده — ابری (UC).
4. کلید AI: wp-config/رمزنگاری/autoload خاموش/حذف از صافی — ابری (AI).
5. قلم Italic و letter-spacing برچسب نقشه — کدکس (RENDER).
6. رویداد: نمایش تاریخ + `Event` با `startDate`/`location` + WPML copy — کدکس + lane اسکیما + کاربر.
7. سقف و تازگی AI (کهنه، ردیف داخلی، `post_content`) — ابری (AI) + کدکس (CORE).
8. XSS پیام خطا — ابری (AI).
9. `TOPERSIA_TRUST_PROXY` زنده و CAPTCHA پیامک — کاربر + ابری (UC).
10. حذف نام ارائه‌دهنده/مدل از پاسخ — ابری (AI).

---

## ۹ — در انتظار تأیید محلی (ویژۀ این تطبیق)

| کار | فرمان / روش |
|---|---|
| نمایش واقعی برچسب آب در fa/ar | صفحۀ کشور و استان ساحلی در کروم و فایرفاکس؛ `node tests/acceptance-shots.js` |
| قالب ذخیرۀ تاریخ رویداد و پر بودن | `wp post meta get <ID> event_start_date` · پرس‌وجوی شمارشی بخش ۳-ب |
| WPML برای فیلدهای تاریخ | `wp option get wpml-tm-custom-xml \| grep -c event_start_date` (فقط شمار) |
| حجم و autoload `tpas_settings` | پرس‌وجوی طول در بخش ۳-ج |
| وقوع R37 | `wp user list --field=display_name \| grep -c '^tp_[0-9]'` (فقط شمار) |
| R41 | `git grep -c -F -- "$TP_GUARD_ADMIN_USER"` (فقط شمار؛ مقدار هرگز چاپ نشود) |
| R63 | `grep -c TOPERSIA_TRUST_PROXY wp-config.php` روی زنده |
