# SYSTEM.md — ممیزی سامانه (بخش کدخوانی؛ بندهای پایگاه‌داده‌ای/زنده `UNKNOWN`)

> فاز ۲د · ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ — **فقط‌خواندنی**.
> این ماشین وردپرس اجرایی، پایگاه‌داده، WP-CLI و `iran.pmtiles` ندارد؛ هرچه از کد و پرونده‌های داخل مخزن خوانده شد `VERIFIED`، هرچه به اجرا/پایگاه‌داده/زنده وابسته است `UNKNOWN` با «محلی: فرمان».
> برچسب‌ها: `VERIFIED` (مسیر و خط) · `INFERRED` · `ASSUMED` · `UNKNOWN`.
> نام‌اختصار: `CORE` = `plugin/topersia-core` · `RENDER` = `plugin/topersia-render` · `UC` = `plugin/topersia-user-core` · `AI` = `plugin/topersia-ai-summarizer` · `SCHEMA` = `plugin/topersia-schema` · `DOC` = `docs` · `WS` = ریشۀ workspace · `WP` = `geo` در ریشۀ مخزن (وردپرس محلی) · `ROOT` = ریشۀ مخزن.
> هیچ مقدار کلید، رمز یا دادۀ کاربر در این پرونده نیست.

---

## ۱ — نسخه‌ها

### ۱٫۱ هسته و محیط

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| وردپرس (نسخۀ پروندۀ محلی) | `7.1.2`؛ `$wp_db_version = 61833`؛ حداقل PHP وردپرس `7.4` | `WP/wp-includes/version.php:19,26,40` | `[VERIFIED]` |
| PHP محلی | 8.3.28 (WAMP) — ادعای سند، نه خروجی اجرا | `WS/CLAUDE.md` (جدول محیط، ردیف PHP) | `[INFERRED]` |
| PHP این ماشین ابری | 8.3.6 (فقط برای `php -l`/assert؛ نمایندۀ زنده نیست) | اجرای `php -v` در جلسه | `[VERIFIED]` |
| PHP لازم هر افزونۀ ما | summarizer 7.2 · core 7.4 · render 7.4 · schema 7.4 · **user-core 8.0** | `AI/topersia-ai-summarizer.php:8` · `CORE/topersia-core.php:6` · `RENDER/topersia-render.php:6` · `SCHEMA/topersia-schema.php:6` · `UC/topersia-user-core.php:8` | `[VERIFIED]` |
| PHP زنده | — | — | `[UNKNOWN]` محلی/زنده: `php -v` و `wp eval 'echo PHP_VERSION," ",php_sapi_name();'` روی هاست زنده |
| موتور/نسخۀ پایگاه‌داده محلی | MariaDB 10.11.19 (پورت ۳۳۰۷)؛ MySQL 8.4.7 (۳۳۰۶) برای بازگشت | `WS/CLAUDE.md` (ردیف پایگاه‌داده) · `DOC/GEO-BENCH-2F.json:3` («10.11.19-MariaDB») | `[INFERRED]` (سند + گزارش بنچمارک) |
| موتور/نسخۀ پایگاه‌داده زنده | ادعا: «هم‌ارز زنده» (0ب٫۵ · P5-D309) | — | `[UNKNOWN]` محلی/زنده: `wp db query "SELECT VERSION(), @@innodb_version, @@sql_mode"` |
| محلی در برابر زنده — جدول تفاوت | — | — | `[UNKNOWN]` محلی/زنده: همان فرمان‌ها روی هر دو و `wp core version --extra` |
| محیط اجرا (`WP_ENVIRONMENT_TYPE`) | چهار قابلیت بر پایۀ `'local' === wp_get_environment_type()` روشن می‌شوند (جدول کش، نمایۀ مکانی و `/map-points`، تاکردن جست‌وجو، گالری تجمیعی) | `CORE/includes/topersia-cache.php:292` · `CORE/includes/topersia-geo-index.php:27` · `CORE/includes/topersia-fold.php:170` · `RENDER/includes/topersia-g-gallery-agg.php:51` | `[VERIFIED]` |
| دروازۀ «فقط محلی تا ممیزی پیوست الف بند ۶» | هر چهار دروازه به پیوست الف (NotebookLM) بسته‌اند که از ۱۱ اکتبر منسوخ است؛ برداشتن دروازه تصمیم صریح لازم دارد | `CORE/includes/topersia-geo-index.php:11-12` · `DOC/SPRINT-4.4-PHASES.md:1284` | `[VERIFIED]` |
| مقدار محیط روی زنده | — | — | `[UNKNOWN]` محلی/زنده: `wp eval 'echo wp_get_environment_type();'` |

### ۱٫۲ افزونه‌ها و پوسته (نسخه در سربرگ پرونده‌های داخل مخزن)

| افزونه | نسخه | شاهد | کد پنهان؟ | برچسب |
|---|---|---|---|---|
| topersia-core | 4.13.0 (کپی `WP`) · **4.13.1** (workspace) | `WP/wp-content/plugins/topersia-core/topersia-core.php:5` · `CORE/topersia-core.php:5` | — | `[VERIFIED]` |
| topersia-render | 4.5.0 (کپی `WP`) · **4.5.1** (workspace) | `WP/wp-content/plugins/topersia-render/topersia-render.php:5` · `RENDER/topersia-render.php:5` | — | `[VERIFIED]` |
| topersia-schema | 1.2.0 (هر دو یکی) | `SCHEMA/topersia-schema.php:5` | — | `[VERIFIED]` |
| topersia-user-core | 1.10.4 (هر دو یکی؛ فقط مستندات فرق دارند) | `UC/topersia-user-core.php:6` | — | `[VERIFIED]` |
| topersia-ai-summarizer | 1.2.1 (هر دو یکی؛ readme و دو آزمون فرق دارند) | `AI/topersia-ai-summarizer.php:6` | — | `[VERIFIED]` |
| jet-engine | 3.8.13.2 | `WP/wp-content/plugins/jet-engine/jet-engine.php:6` | بی کد SourceGuardian | `[VERIFIED]` |
| jet-plugin-pro | 1.6.0 | `WP/wp-content/plugins/jet-plugin-pro/index.php:6` | شامل لودر abzarwp | `[VERIFIED]` |
| jet-reviews | 3.1.2.1 | `WP/wp-content/plugins/jet-reviews/jet-reviews.php:6` | شامل لودر abzarwp | `[VERIFIED]` |
| sitepress-multilingual-cms (WPML) | 4.9.7 | `WP/wp-content/plugins/sitepress-multilingual-cms/sitepress.php:8` | شامل لودر abzarwp | `[VERIFIED]` |
| wpml-string-translation | 3.5.4 | `WP/wp-content/plugins/wpml-string-translation/plugin.php:8` | شامل لودر abzarwp | `[VERIFIED]` |
| wpml-media-translation | 3.0.0 | `WP/wp-content/plugins/wpml-media-translation/plugin.php:8` | شامل لودر abzarwp | `[VERIFIED]` |
| wp-seo-multilingual | 2.2.4 | `WP/wp-content/plugins/wp-seo-multilingual/plugin.php:8` | شامل لودر abzarwp | `[VERIFIED]` |
| elementor | 4.3.2 | `WP/wp-content/plugins/elementor/elementor.php:6` | — | `[VERIFIED]` |
| elementor-pro | 4.2.2 | `WP/wp-content/plugins/elementor-pro/elementor-pro.php:6` | شامل لودر abzarwp و افزودۀ جلالی | `[VERIFIED]` |
| seo-by-rank-math | 1.0.279 | `WP/wp-content/plugins/seo-by-rank-math/rank-math.php:12` | — | `[VERIFIED]` |
| seo-by-rank-math-pro | 3.0.121 | `WP/wp-content/plugins/seo-by-rank-math-pro/rank-math-pro.php:12` | شامل لودر abzarwp | `[VERIFIED]` |
| duplicator-pro | 5.0.3 | `WP/wp-content/plugins/duplicator-pro/duplicator-pro.php:7` | شامل لودر abzarwp | `[VERIFIED]` |
| woodmart-core | 1.1.8 | `WP/wp-content/plugins/woodmart-core/woodmart-core.php:5` | شامل لودر abzarwp | `[VERIFIED]` |
| safe-svg | 2.5.1 | `WP/wp-content/plugins/safe-svg/safe-svg.php:6` | — | `[VERIFIED]` |
| akismet | 5.7.2 | `WP/wp-content/plugins/akismet/akismet.php:9` | — | `[VERIFIED]` |
| abzarwp-auto-update | 1.3.21 | `WP/wp-content/plugins/abzarwp-auto-update/index.php:5` | ۱۹۱ از ۲۶۲ پروندۀ PHP با SourceGuardian | `[VERIFIED]` |
| پوسته | woodmart 8.5.7 (با `abzarwp/`) · hello-elementor 3.5.1 | `WP/wp-content/themes/woodmart/style.css:7` · `WP/wp-content/themes/hello-elementor/style.css:7` | — | `[VERIFIED]` |
| mu-plugins | `tp-local-debug` · `tp-local-http-probe` (مسیر مطلق ویندوز سخت‌کد) · `tp-local-livelike` · `tp-local-safety` (قطع `wp_mail`، فقط `WP_ENVIRONMENT_TYPE=local`) · `wp-toolkit` (پلسک) | `WP/wp-content/mu-plugins/` · `WP/wp-content/mu-plugins/tp-local-http-probe.php:25` | — | `[VERIFIED]` |
| فعال‌ها (`active_plugins`، پوستۀ فعال) | — | — | — | `[UNKNOWN]` محلی: `wp plugin list --fields=name,status,version,update --format=csv` و `wp theme list --fields=name,status,version` |
| به‌روزرسانی‌های موجود | — | — | — | `[UNKNOWN]` محلی: `wp plugin list --update=available --fields=name,version,update_version` |

**کپی `WP` در برابر workspace** (مخزن هر دو را دارد): برای سه افزونه نسخۀ سربرگ یکی نیست یا پرونده فرق دارد:

| افزونه | شمار خطوط diff (`diff -rq`) | جزئیات | برچسب |
|---|---|---|---|
| topersia-core | 5 | `topersia-geo.php` و `topersia-geo-index.php` فقط در workspace؛ `topersia-core.php` · `topersia-pack.php` · `topersia-services.php` فرق دارند | `[VERIFIED]` |
| topersia-render | 16 | `topersia-g-air.php` · `topersia-g-mappoints.php` · `topersia-g-relreg.php` فقط در workspace؛ CSS/JS/وزن‌ها فرق دارند | `[VERIFIED]` |
| user-core · summarizer · schema | 2 · 3 · 0 | فقط مستندات/آزمون | `[VERIFIED]` |

یعنی نمونه‌ای که در `geo/wp-content/plugins` ثبت شده **عقب‌تر** از workspace است (آخرین commit کپی: ۵ اکتبر؛ workspace: ۱۲ اکتبر). همگام‌سازی کپی با workspace دستی/اسکریپتی است و در مخزن گم می‌شود. `[INFERRED]` — دستور: `git log -1 --format=%cd -- geo/wp-content/plugins/topersia-core`.

---

## ۲ — Action Scheduler: چه کسی بسته‌بندی کرده و کدام نسخه بار می‌شود

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| نسخه بسته‌بندی‌شدۀ `topersia-core` | **3.9.3** | `CORE/vendor/action-scheduler/action-scheduler.php:8,47` | `[VERIFIED]` |
| نسخه بسته‌بندی‌شدۀ Rank Math | **3.9.3** (`vendor/woocommerce/action-scheduler`)؛ از راه فایل خودکار composer هنگام بارگذاری افزونه ثبت می‌شود | `WP/wp-content/plugins/seo-by-rank-math/vendor/woocommerce/action-scheduler/action-scheduler.php:47` · `WP/wp-content/plugins/seo-by-rank-math/vendor/composer/autoload_files.php:13` | `[VERIFIED]` |
| سایر بسته‌بندی‌کنندگان | **هیچ** (جستجوی `action-scheduler.php` و `ActionScheduler_Versions` در کل `geo/`: فقط همین دو)؛ WooCommerce نصب نیست | جستجوی مخزن | `[VERIFIED]` |
| قاعدۀ انتخاب نسخه | هر بسته‌بندی در `plugins_loaded` اولویت ۱ نسخۀ خود را ثبت می‌کند؛ **بالاترین نسخه برنده است** (`version_compare`)؛ برای نسخۀ برابر اولین ثبت‌کننده می‌ماند و بعدی رد می‌شود | `CORE/vendor/action-scheduler/classes/ActionScheduler_Versions.php:34-37,73-80` | `[VERIFIED]` |
| پیامد ۱ | با نسخۀ برابر، **ترتیب بارگذاری افزونه‌ها** تعیین می‌کند کدام مسیر فایل بار شود؛ ترتیب الفبایی پیش‌فرض Rank Math (`seo-…`) را جلوتر می‌گذارد؛ هر دو کد یکسان‌اند پس رفتار فرق نمی‌کند | استنتاج | `[INFERRED]` |
| پیامد ۲ (مهم) | **سنجاق‌کردن روی 3.9.3 (P5-D290) اجرایی نیست**: اگر Rank Math (یا افزونۀ آینده) نسخۀ بالاتر (مثلاً 4.x با ستون `unique_key`) بسته‌بندی کند، آن نسخه بار می‌شود و ساختار جدول مشترک `actionscheduler_actions` بی اراده عوض می‌شود | `CORE/includes/topersia-queue.php:5-12` در برابر قاعدۀ بالا | `[INFERRED]` |
| کدام نسخه/مسیر امروز بار شده | — | — | `[UNKNOWN]` محلی: `wp eval 'print_r(ActionScheduler_Versions::instance()->get_sources()); echo ActionScheduler_Versions::instance()->latest_version(),"\n"; echo ActionScheduler::plugin_path("");'` |
| جدول‌های کتابخانه | `actionscheduler_actions` · `_claims` · `_groups` · `_logs` (خود کتابخانه می‌سازد) | `CORE/vendor/action-scheduler/classes/schema/ActionScheduler_StoreSchema.php:11-13` · `…/ActionScheduler_LoggerSchema.php:11` | `[VERIFIED]` |
| مصرف‌کننده‌ها | تنها گروه `topersia` ← **گالری تجمیعی** (`topersia_gallery_agg_rebuild` و `…_rebuild_all`)؛ هیچ مصرف‌کنندۀ دیگری (نه AI، نه خبر، نه نمایه) | `RENDER/includes/topersia-g-gallery-agg.php:377,496,511,516,583` · `CORE/includes/topersia-queue.php:45-59` | `[VERIFIED]` |
| اجرای صف | کرون وردپرس: `action_scheduler_run_queue` هر دقیقه (`every_minute`) + درخواست ناهمگام | `CORE/vendor/action-scheduler/classes/ActionScheduler_QueueRunner.php:7,9,72-82` | `[VERIFIED]` |
| دروازه | گالری تجمیعی فقط محلی | `RENDER/includes/topersia-g-gallery-agg.php:51` | `[VERIFIED]` |
| داده‌ی صف | شمار کار در انتظار/ناموفق | — | `[UNKNOWN]` محلی: `wp action-scheduler action list --status=pending --group=topersia --format=count` و `wp action-scheduler action list --status=failed --format=count` |

---

## ۳ — مسیرهای REST

| مسیر | روش | دسترسی | شاهد | برچسب |
|---|---|---|---|---|
| `/topersia/v1/weather?coords=` | GET | عمومی (`__return_true`)؛ مختصات باید در کادر ایران؛ کش مرکزی (گرد دو رقم اعشار)؛ سقف بالادست در دقیقه | `RENDER/includes/topersia-g-weather.php:1004-1015,983-985,731-737` | `[VERIFIED]` |
| `/topersia/v1/air?lat=&lng=` | GET | عمومی؛ گرد به ¼ درجه | `RENDER/includes/topersia-g-air.php:252-263` · `RENDER/includes/topersia-g-air.php:62` | `[VERIFIED]` |
| `/topersia/v1/map-points?context=` | GET | عمومی؛ خالی اگر دروازۀ محلی خاموش | `RENDER/includes/topersia-g-mappoints.php:155-165` | `[VERIFIED]` |
| مسیر REST در core · schema · user-core · summarizer | **هیچ** | جستجوی `register_rest_route` | — | `[VERIFIED]` (نبود) |
| فیلتر | `rest_endpoints`: `/wp/v2/users` و `/wp/v2/users/(?P<id>)` برای مهمان حذف | `CORE/topersia-core.php:4410-4422` | — | `[VERIFIED]` |
| حکم: ۳ مسیر عمومی، همه GET | تنها داده‌ی قابل نوشتن از مرورگر = AJAX | — | — | `[VERIFIED]` |
| فهرست زندۀ مسیرها (شامل وردپرس، Elementor، JetEngine، WPML) | — | — | — | `[UNKNOWN]` محلی: `wp eval 'foreach(array_keys(rest_get_server()->get_routes()) as $r) echo $r,"\n";'` (یا `curl http://topersia.local/geo/wp-json/`) |

---

## ۴ — AJAX و admin-post

| اکشن | افزونه | ورود لازم | نانس/قابلیت | شاهد | برچسب |
|---|---|---|---|---|---|
| `tpas_ai_generate` | AI | **ورود** | نانس `tpas_ai`؛ دروازۀ سقف روزانه (render، اولویت ۱) | `AI/includes/class-tp-ai-summary.php:19,88,94` · `RENDER/includes/topersia-g-ai.php:163` | `[VERIFIED]` |
| `tpas_track` | AI | **نه (nopriv)** | نانس `tpas_ai` | `AI/includes/class-tp-ai-tracker.php:24-25,34` | `[VERIFIED]` |
| `tpas_test_key` | AI | مدیر | `manage_options` + نانس `tpas_admin` | `AI/includes/class-tp-ai-admin.php:24,833-835` | `[VERIFIED]` |
| `topersia_send_otp` · `topersia_verify_otp` | user-core | **نه (nopriv)** | نانس `topersia_user_core` | `UC/includes/class-topersia-auth.php:56-59` | `[VERIFIED]` |
| `topersia_logout` | user-core | ورود | — | `UC/includes/class-topersia-auth.php:60` | `[VERIFIED]` |
| `topersia_save_place` · `topersia_remove_saved_place` | user-core | ورود | نانس | `UC/includes/class-topersia-jetengine.php:20-21` | `[VERIFIED]` |
| `topersia_follow_entity` · `topersia_entity_state` | user-core | ورود | نانس | `UC/includes/class-topersia-follow.php:36-37` | `[VERIFIED]` |
| `topersia_submit_review` | user-core | ورود | — | `UC/includes/class-topersia-review-form.php:40` | `[VERIFIED]` |
| `topersia_save_profile` · `topersia_save_display_prefs` | user-core | ورود | نانس | `UC/includes/class-topersia-profile.php:9` · `UC/includes/class-topersia-display-prefs.php:50` | `[VERIFIED]` |
| `topersia_pack_plan` · `_run` · `_undo` | core | مدیر | `manage_options` + نانس `topersia_pack` | `CORE/includes/topersia-pack.php:3359-3370,3397,3459` | `[VERIFIED]` |
| `topersia_service_test` | core | مدیر | `manage_options` + نانس `topersia_service_test` | `CORE/includes/topersia-services.php:656` | `[VERIFIED]` |
| `topersia_log_js` | core | مدیر | `manage_options` | `CORE/topersia-core.php:1835` | `[VERIFIED]` |
| admin-post: `topersia_pack_export` · `topersia_log_export` · `topersia_gallery_agg_rebuild_all` · `topersia_setup_jetreviews` | core · render · user-core | مدیر | `check_admin_referer`/قابلیت | `CORE/includes/topersia-pack.php:3479` · `CORE/topersia-core.php:1732` · `RENDER/includes/topersia-g-gallery-agg.php:578-582` · `UC/includes/class-topersia-jetreviews-setup.php:46` | `[VERIFIED]` |
| حکم | سه نقطۀ بی‌ورود: `tpas_track`، `topersia_send_otp`، `topersia_verify_otp` | — | — | — | `[VERIFIED]` |
| فهرست زنده (شامل Elementor/WPML/JetEngine) | — | — | — | — | `[UNKNOWN]` محلی: `wp eval 'global $wp_filter; foreach(array_keys($wp_filter) as $h) if (strpos($h,"wp_ajax_")===0) echo $h,"\n";' \| sort` |

---

## ۵ — کرون

| رویداد | بازه | افزونه | شاهد | برچسب |
|---|---|---|---|---|
| `topersia_log_prune` | روزانه | core | `CORE/topersia-core.php:1385-1387,1839-1840` | `[VERIFIED]` |
| `topersia_user_core_prune` | روزانه | user-core | `UC/includes/class-topersia-db.php:60-61,74` · `UC/uninstall.php:122` | `[VERIFIED]` |
| `action_scheduler_run_queue` | هر دقیقه | کتابخانۀ AS (core و Rank Math) | `CORE/vendor/action-scheduler/classes/ActionScheduler_QueueRunner.php:7,9` | `[VERIFIED]` |
| `wp_schedule_*` در render · schema · summarizer | **هیچ** | — | جستجو | `[VERIFIED]` (نبود) |
| کرون‌های درون SourceGuardian | کنترلرهای `CronController` · `UpdateController` · `NewsController` · `LicenseController` در `abzarwp/license/Controllers/` هر افزونۀ تجاری — **قابل‌خواندن نیستند** | `WP/wp-content/plugins/sitepress-multilingual-cms/abzarwp/license/Controllers/CronController.php` (رمزگذاری‌شده) | — | `[VERIFIED]` (وجود) · محتوا `[UNKNOWN]` محلی: `wp cron event list --format=csv \| grep -i abzar` |
| `DISABLE_WP_CRON` | کد ما فرض می‌کند «روی زنده روشن است و صف به کرون سیستمی فاز ۱۰ وابسته است»؛ `wp-config.php` در مخزن نیست | `CORE/includes/topersia-queue.php:13-15` · `DOC/SPRINT-4.4-PHASES.md:1161` | — | `[ASSUMED]` |
| وضعیت واقعی WP-Cron | — | — | — | `[UNKNOWN]` محلی/زنده: `wp config get DISABLE_WP_CRON` · `wp cron event list --fields=hook,next_run_relative,recurrence` · `wp cron test` |
| کرون سیستمی (cPanel) | — | — | — | `[UNKNOWN]` زنده: `crontab -l` روی هاست یا صفحۀ Cron Jobs در cPanel |

---

## ۶ — جدول‌های سفارشی (از `CREATE TABLE`)

| جدول (`{prefix}…`) | افزونه | ستون‌ها/شاخص‌های کلیدی | دروازه | شاهد | برچسب |
|---|---|---|---|---|---|
| `topersia_log` | core | `id`, `logged_at`, `level`, `channel`, `message`, `context LONGTEXT`, `request_id`, `user_id`, `url`, `source`؛ شاخص `(level,logged_at)` `(channel,logged_at)` `(request_id)` | همیشه | `CORE/topersia-core.php:1086-1101` | `[VERIFIED]` |
| `topersia_cache` | core | `ckey CHAR(40)` PK, `service`, `value LONGTEXT`, `hash`, `fresh_until`, `created` | **فقط محلی** | `CORE/includes/topersia-cache.php:291-293,310-320` | `[VERIFIED]` |
| `topersia_geo` | core | `post_id` PK, `post_type`, `lat DECIMAL(8,6)`, `lng DECIMAL(9,6)`, `geom_kind`, `province_id`, `city_id`, `index_version`؛ ۴ شاخص | **فقط محلی** | `CORE/includes/topersia-geo-index.php:26-28,63-77` | `[VERIFIED]` |
| `topersia_otp_attempts` | user-core | `mobile`, `purpose`, `attempts`, `verified`, `request_id`, `ip_hash`, `expires_at` | همیشه | `UC/includes/class-topersia-db.php:27-41` | `[VERIFIED]` |
| `topersia_saved` | user-core | `user_id`, `post_id`, `post_type`; یکتا `(user_id,post_id)` | همیشه | `UC/includes/class-topersia-db.php:45-56` | `[VERIFIED]` |
| `topersia_ai_summaries` | AI | `post_id,type,language` یکتا؛ `content LONGTEXT`, `provider`, `model`, `source` | همیشه | `AI/includes/class-tp-ai-db.php:68-83` | `[VERIFIED]` |
| `topersia_ai_events` | AI | `post_id,event_type,provider,created_at` | همیشه | `AI/includes/class-tp-ai-db.php:85-94` | `[VERIFIED]` |
| `actionscheduler_*` (۴ جدول) | کتابخانه | — | — | بخش ۲ | `[VERIFIED]` |
| جدول‌های دیگران که ما می‌خوانیم/می‌نویسیم | `jet_reviews`, `jet_review_guests` (JetReviews) — نوشتن مستقیم نظر با `approved=0` |  |  | `RENDER/includes/topersia-g-zones.php:4848,4859` · `UC/includes/class-topersia-review-form.php:67` | `[VERIFIED]` |
| `topersia-schema` و `topersia-render` | **بی جدول** |  |  | جستجوی `CREATE TABLE`/`dbDelta` | `[VERIFIED]` (نبود) |
| وجود، حجم و ردیف واقعی جدول‌ها (محلی/زنده) | — |  |  | — | `[UNKNOWN]` محلی/زنده: `wp db query "SELECT table_name, table_rows, ROUND((data_length+index_length)/1048576,2) AS mb FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name REGEXP 'topersia\|actionscheduler\|jet_' ORDER BY (data_length+index_length) DESC"` |

**نکتۀ مرز مالکیت (P5-D289):** `topersia-render` خواندن/نوشتن مستقیم روی متای کاربر (`_topersia_ai_daily`) و جدول JetReviews و فراخوانی مستقیم `TP_AI_Summary` دارد؛ قاعدۀ «فقط از تابع عمومی یا REST مالک» در کد ایستا اعمال نمی‌شود. `[VERIFIED]` `RENDER/includes/topersia-g-ai.php:88,106` · `RENDER/includes/topersia-g-zones.php:4568-4576`.

---

## ۷ — optionها

| option | نویسنده | autoload | محتوا | شاهد | برچسب |
|---|---|---|---|---|---|
| `topersia_settings` | core | **بله** | تنظیمات هسته | `CORE/topersia-core.php:516,4693` | `[VERIFIED]` |
| `topersia_services` | core | نه | ردیف‌های سرویس؛ کلید با sodium (کلید از `wp_salt('auth')`) | `CORE/includes/topersia-services.php:35,118-121,505,551` | `[VERIFIED]` |
| `topersia_services_usage` | core | نه | شمار ok/خطا هر سرویس | `CORE/includes/topersia-services.php:38,364` | `[VERIFIED]` |
| `topersia_site_is_commercial` | core | **بله** | پرچم تجاری | `CORE/includes/topersia-services.php:41,635` | `[VERIFIED]` |
| `topersia_pack_last_run`, `topersia_log_schema`, `topersia_cache_schema`, `topersia_geo_index_schema`, `topersia_geo_index_stamp` | core | نه | وضعیت | `CORE/includes/topersia-pack.php:47,2266` · `CORE/topersia-core.php:1104` · `CORE/includes/topersia-cache.php:322` · `CORE/includes/topersia-geo-index.php:79,144` | `[VERIFIED]` |
| `tpas_settings` (**شامل کلید LLM**) | AI | بله (پیش‌فرض `register_setting`) | بخش ۵ AI.md | `AI/includes/class-tp-ai-options.php:18` · `AI/includes/class-tp-ai-admin.php:94` | `[VERIFIED]` |
| `tpas_stats` | AI | بله (بی پارامتر سوم) و به‌روزرسانی با هر کلیک عمومی | شمار کلیک | `AI/includes/class-tp-ai-tracker.php:56` | `[VERIFIED]` |
| `tpas_prompt_overrides`, `tpas_db_version` | AI | بله | — | `AI/includes/class-tp-ai-prompt-manager.php:20` · `AI/includes/class-tp-ai-db.php:29,98` | `[VERIFIED]` |
| `topersia_render_settings` | render | پیش‌فرض (ثبت با `register_setting`) | سقف AI روزانه (پیش‌فرض کاربر 5/سایت 100) و غیره | `RENDER/includes/topersia-settings.php:55-56,74,154` | `[VERIFIED]` |
| `topersia_gallery_agg_last`, `topersia_news_rules` | render | نه | وضعیت | `RENDER/includes/topersia-g-gallery-agg.php:477` · `RENDER/includes/topersia-g-news.php:388-390` | `[VERIFIED]` |
| `topersia_mediana_api_key` (**کلید پیامک**), `topersia_mediana_pattern_code`, `topersia_account_page_url`, `topersia_user_tabs` | user-core | بله | بخش ۲ USER.md | `UC/includes/class-topersia-admin.php:245-263` | `[VERIFIED]` |
| `topersia_user_core_db_version`, `topersia_user_core_strings_fingerprint` | user-core | نه | — | `UC/includes/class-topersia-db.php:58` · `UC/includes/class-topersia-i18n.php:171,216` | `[VERIFIED]` |
| `jet-reviews-settings` | user-core تنظیم می‌کند | بله | تنظیمات JetReviews | `UC/includes/class-topersia-jetreviews-setup.php:34,328` | `[VERIFIED]` |
| حجم کل autoload و بزرگ‌ترین optionها | — |  |  | — | `[UNKNOWN]` محلی/زنده: `wp db query "SELECT option_name, LENGTH(option_value) b FROM $(wp db prefix)options WHERE autoload IN ('yes','on','auto','auto-on') ORDER BY b DESC LIMIT 30"` · جمع: `wp db query "SELECT COUNT(*), SUM(LENGTH(option_value)) FROM $(wp db prefix)options WHERE autoload IN ('yes','on','auto','auto-on')"` |
| شمار ترنزینت‌ها و سایز | کش `weather`/`map.points`/`ai.*` و شمارنده‌های OTP/rate در ترنزینت (بی کش شیء پایدار = ردیف `wp_options`) |  |  | — | `[UNKNOWN]` محلی/زنده: `wp db query "SELECT COUNT(*) FROM $(wp db prefix)options WHERE option_name LIKE '\_transient\_%'"` |

---

## ۸ — کش شیء و کرون سیستم

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| درآپین `object-cache.php` در `wp-content` | **نیست** در مخزن (محلی) | فهرست `WP/wp-content/` (فقط `index.php` از پرونده‌های PHP) | `[VERIFIED]` |
| پیامد | کش مرکزی (`topersia_cache_remember`) برای سرویس‌های غیرجدولی از ترنزینت استفاده می‌کند → در نبود کش شیء پایدار هر کش = ردیف `wp_options` | `CORE/includes/topersia-cache.php:329-331` | `[VERIFIED]` |
| درآپین روی زنده / Redis / Memcached / LSCache | — | — | `[UNKNOWN]` زنده: `wp cache type` · `wp plugin list \| grep -i "cache\|litespeed"` · `ls -l wp-content/object-cache.php` |

---

## ۹ — «سقف روزانه» صفحۀ سرویس‌های خارجی

| پرسش | پاسخ | شاهد | برچسب |
|---|---|---|---|
| برای کدام سرویس‌هاست؟ | **همۀ سطرها** (۹ سرویس کاتالوگ + شکاف‌های دوم): فیلد «سقف روزانه (کاربر · سایت)» در هر سطر چاپ می‌شود | `CORE/includes/topersia-services.php:56-64,956-958` | `[VERIFIED]` |
| ذخیره می‌شود؟ | بله: `limit_user`/`limit_site` در option `topersia_services` | `CORE/includes/topersia-services.php:525-526` | `[VERIFIED]` |
| چه کسی می‌خواندش؟ | `topersia_api()['limits']` را **هیچ کدی مصرف نمی‌کند** مگر `topersia_g_ai_caps()` که شاخه‌اش مرده است (`$layer = null`) و همیشه `legacy` برمی‌گرداند؛ نویسندۀ مهاجرتش هم با `if ( false )` خاموش است | `CORE/includes/topersia-services.php:285-288` · `RENDER/includes/topersia-g-ai.php:34-36` · `RENDER/includes/topersia-g-core.php:70-76` | `[VERIFIED]` |
| امروز هیچ؟ | بله — **هیچ سقفی از این صفحه اعمال نمی‌شود** (حتی برای سرویس‌های هزینه‌دار آینده: TTS، نشان) | جستجوی `['limits']` در ۵ افزونه | `[VERIFIED]` |
| تکراری با سقف هوش مصنوعی؟ | **بله، سه‌گانه**: (الف) ستون بی مصرف این صفحه؛ (ب) سقف روزانۀ `render` (`ai_user_daily` پیش‌فرض 5، `ai_site_daily` پیش‌فرض 100؛ اگر تابع تنظیمات نبود 10/200)؛ (ج) سقف ساعتی خلاصه‌ساز `internal_throttle` = 10 | `RENDER/includes/topersia-settings.php:55-56` · `RENDER/includes/topersia-g-ai.php:52-53` · `AI/includes/class-tp-ai-options.php:67` · `AI/includes/class-tp-ai-summary.php:362-380` | `[VERIFIED]` |
| پیشنهاد | یک مالک واحد (لایۀ سرویس‌های هسته) و حذف دو تای دیگر، یا حذف ستون از صفحه تا مصرف‌کننده داشته باشد | — | `[INFERRED]` |

---

## ۱۰ — ‌توزیع‌کنندۀ واسط و کدهای رمزگذاری‌شده (برای RISKS)

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| لودر `abzarwp/` | در **۱۱ افزونه** و پوستۀ woodmart تزریق شده است (jet-plugin-pro، jet-reviews، sitepress-multilingual-cms، wpml-string-translation، wpml-media-translation، wp-seo-multilingual، elementor-pro، seo-by-rank-math-pro، duplicator-pro، woodmart-core، abzarwp-auto-update) | شمارش پوشه‌های `abzarwp/` در `WP/wp-content/plugins/*/` و `WP/wp-content/themes/woodmart/abzarwp` | `[VERIFIED]` |
| رمزگذاری | ۱۹۱ از ۲۶۲ پروندۀ PHP افزونۀ `abzarwp-auto-update` و ۳۷–۴۶ پرونده در هر افزونۀ تجاری فقط با **SourceGuardian** قابل‌اجراست؛ لودر نسخۀ ۱۳/۱۴/۱۶ را از `sg_loader_version()` انتخاب می‌کند | `WP/wp-content/plugins/abzarwp-auto-update/App/Backups.php:3-13` | `[VERIFIED]` |
| قابلیت‌های ماژول‌ها (نام‌ها) | `Account` · `Authentication` · `Backups` · `Cache` · `DB` · `Dashboard` · `Debug` · `Email` · `LoginWithoutPassword` · `Products` · `Settings` · `Update` · `Uninstall` · `Zip` — **«ورود بی‌رمز»، «پشتیبان» و «پایگاه‌داده» در کد رمزگذاری‌شده** | فهرست `WP/wp-content/plugins/abzarwp-auto-update/App/` | `[VERIFIED]` (نام) · رفتار `[UNKNOWN]` محلی: `php -m \| grep -i sourceguardian` (ادامه در ردیف بعد) |
| دیدن رفتار واقعی | — | — | `[UNKNOWN]` محلی: (ماشین جدا، بی داده‌ی واقعی) `php -m \| grep -i sourceguardian` و `wp cron event list \| grep -i abzar` و ثبت ترافیک شبکه با `mitmproxy` هنگام باز کردن پیشخوان |

---

## ۱۱ — WP-CLI سفارشی

| فرمان | شاهد | برچسب |
|---|---|---|
| `wp topersia geo-index` با زیرفرمان‌های `rebuild`، `verify`، `report` | `CORE/includes/topersia-geo-index.php:319-352` | `[VERIFIED]` |
| `wp topersia i18n-gaps` | `CORE/includes/topersia-i18n-gaps.php:378` | `[VERIFIED]` |
| کتابخانۀ AS: `wp action-scheduler …` | `CORE/vendor/action-scheduler/classes/abstracts/ActionScheduler.php:244-246` | `[VERIFIED]` |

---

## دور دوم (تطبیق با ممیزی مستقل ب)

> منبع: **دور دوم** — `RECONCILE.md`.

| مورد | اصلاح/افزوده | شاهد | برچسب |
|---|---|---|---|
| کپی نصب‌شده (§۱٫۲) | **کهنه:** از `b1ec34bb` کپی core و render هم‌بایت با workspace (`diff -rq` صفر، هر دو `4.13.1`/`4.5.1`)؛ user-core ۲ و AI ۳ پروندۀ غیرکد فرق دارند | `WP/wp-content/plugins/topersia-core/topersia-core.php:5` · `WP/wp-content/plugins/topersia-render/topersia-render.php:5` | `[VERIFIED]` |
| JetReviews (§۶) | **اصلاح:** render فقط می‌خواند؛ نوشتن نظر از API JetReviews در user-core | `RENDER/includes/topersia-g-zones.php:4848,4859` · `UC/includes/class-topersia-review-form.php:205` | `[VERIFIED]` |
| PHP زنده | `.htaccess` ریشه: `display_errors Off` · `log_errors Off` · `max_execution_time 600` (بلوک MultiPHP؛ ممکن است کهنه باشد) | `ROOT/.htaccess:50,52,53` | `[VERIFIED]` (پرونده) · مؤثر `[UNKNOWN]` |
| `mbstring` | وردپرس فقط `mb_chr · mb_ord · mb_substr · mb_strlen` را جایگزین می‌کند | `WP/wp-includes/compat.php:126,196,272,354` | `[VERIFIED]` |
| بی `uninstall.php` | core و render | (نبود پرونده) | `[VERIFIED]` |
