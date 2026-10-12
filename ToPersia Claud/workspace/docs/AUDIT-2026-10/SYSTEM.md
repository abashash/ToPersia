# SYSTEM — ممیزی ۲د (بخش کدخوانی): نسخه‌ها، صف، مسیرها، جدول‌ها

> ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ · فقط‌خواندنی (P5-D287).
> این ماشین وردپرس، پایگاه‌داده، WP-CLI و PHP-FPM زنده ندارد. هر بندی که اجرای زنده می‌خواست **UNKNOWN** با «محلی: فرمان» است. مسیرها نسبت به `ToPersia Claud/workspace/` (کد افزونه‌ها در `plugin/`) یا ریشۀ مخزن (`geo/…`، با پیشوند `ریشه:`).
> برچسب‌ها: VERIFIED (مسیر + خط/خروجی فرمان) · INFERRED · ASSUMED · UNKNOWN.
> بخش‌های «پایگاه‌دادهای» که امروز تکمیل نمی‌شوند: ۱ (موتور/نسخۀ DB)، ۲ (افزونه‌های **فعال**)، ۵ (WP-Cron زنده)، ۶ (کش شیء زنده)، ۱۰ (حجم autoload) — همه در «در انتظار تأیید محلی» CURRENT.md.

## ۱ — نسخه‌ها

| مورد | محلی | زنده | برچسب |
|---|---|---|---|
| وردپرس | `7.1.2` از `ریشه:geo/wp-includes/version.php` | UNKNOWN | **VERIFIED** (فایل) |
| حداقل PHP / MySQL وردپرس | `7.4` / `5.5.5` (همان فایل) | — | **VERIFIED** |
| PHP | `8.3.28` (`CLAUDE.md`، جدول محیط) | **`8.2`** (بستۀ `ea-php82` — `ریشه:.htaccess:39-40,59`؛ فایل پس از درون‌ریزی Duplicator در ۱۳ اوت ۲۰۲۶ نوشته شده، پس ممکن است کهنه باشد) | محلی **INFERRED**؛ زنده **INFERRED** از `.htaccess` — تأیید: محلی `php -v`؛ زنده `wp --info` یا MultiPHP Manager |
| حدّ PHP زنده | — | `max_execution_time 600` · `max_input_time 600` · `memory_limit 1024M` · `post_max_size 256M` · `upload_max_filesize 256M` · `max_input_vars 20000` · `display_errors Off` · **`log_errors Off`** (`ریشه:.htaccess:50-61`) | **INFERRED** (همان کهنگی) — مقدار مؤثر: `php -i` از میزبان |
| موتور و نسخۀ DB | MariaDB `10.11.19` (`CLAUDE.md`)؛ MySQL `8.4.7` برای بازگشت | ARCH-RECONCILIATION §۴: «زنده MariaDB 10.11» — نسخۀ دقیق UNKNOWN | **INFERRED** — محلی: `wp db query "SELECT VERSION(), @@version_comment"`؛ زنده: همین فرمان یا پنل میزبان |
| WP-CLI | `2.12.0` (`CLAUDE.md`) | — | **INFERRED** |
| پوسته | `woodmart 8.5.7`، `hello-elementor 3.5.1` (`ریشه:geo/wp-content/themes/*/style.css`) | UNKNOWN (کدام فعال است) | **VERIFIED** (فایل) |
| `mbstring` روی میزبان زنده | کامنت‌های توسعه می‌گویند **نیست** (`plugin/topersia-core/topersia-core.php:1017,3302` · `plugin/topersia-user-core/includes/class-topersia-review-form.php:130` · کامنت `mu-plugin tp-local-livelike.php`) | UNKNOWN | **ASSUMED** — زنده: `php -m \| grep mbstring` |
| لینوکس/ویندوز زنده | `tp-local-livelike.php`: «روی سرور زندۀ Linux/MariaDB»؛ محلی ویندوز با `lower_case_table_names=1` | — | **INFERRED** |
| نوع میزبان | `ریشه:.htaccess` بلوک‌های cPanel دارد (`:5,39-61`: «cPanel-generated handler»، «MultiPHP INI Editor»، ماژول‌های `php8_module` و `lsapi_module`) و `mu-plugins/wp-toolkit.php` (WP Toolkit) در مخزن هست | cPanel با (احتمالاً) LiteSpeed LSAPI؛ تأیید نسخۀ وب‌سرور UNKNOWN | **VERIFIED** (وجود بلوک‌ها) + **INFERRED** (LiteSpeed از `lsapi_module`) — با ARCH-RECONCILIATION («cPanel/LiteSpeed») هم‌خوان |

### یافتۀ نسخه

- اگر `mbstring` زنده واقعاً نباشد، وردپرس ۷٫۱ فقط `mb_chr` · `mb_ord` · `mb_substr` · `mb_strlen` را جایگزین می‌کند (`ریشه:geo/wp-includes/compat.php:126,196,272,354` — **VERIFIED**). فراخوانی بی‌گارد `mb_strtolower` در `plugin/topersia-ai-summarizer/includes/class-tp-ai-internal.php:346` (و `mb_strlen` در `:353` که جایگزین دارد) ← **VERIFIED** که گارد ندارد؛ **INFERRED** که روی میزبان بی‌mbstring موتور داخلی (پیش‌فرض وقتی API پیکربندی نیست) خطای مهلک می‌دهد. هسته ۲۲ جا را با `function_exists` پوشانده؛ این یکی جا مانده.

## ۲ — افزونه‌ها

### نصب‌شده (کد در مخزن، `ریشه:geo/wp-content/plugins/*`)

| پوشه | نام / نسخه (از سرآیند) | مالک |
|---|---|---|
| `topersia-core` | ToPersia Core Functions `4.13.0` (workspace: `4.13.1`) | ما |
| `topersia-render` | ToPersia — هستۀ نمایش `4.5.0` (workspace: `4.5.1`) | ما |
| `topersia-schema` | ToPersia Schema Engine `1.2.0` | ما |
| `topersia-user-core` | ToPersia User Core `1.10.4` | ما |
| `topersia-ai-summarizer` | ToPersia AI Summarizer `1.2.1` | ما |
| `jet-engine` | JetEngine `3.8.13.2` | ثالث تجاری |
| `jet-reviews` | JetReviews For Elementor `3.1.2.1` | ثالث تجاری |
| `jet-plugin-pro` | Jet Plugin Pro `1.6.0` | ثالث (نامشخص) |
| `elementor` · `elementor-pro` | `4.3.2` · `4.2.2` | ثالث / تجاری |
| `sitepress-multilingual-cms` | WPML Multilingual CMS `4.9.7` | تجاری |
| `wpml-string-translation` · `wpml-media-translation` · `wp-seo-multilingual` | `3.5.4` · `3.0.0` · `2.2.4` | تجاری |
| `seo-by-rank-math` · `seo-by-rank-math-pro` | `1.0.279` · `3.0.121` | رایگان / تجاری |
| `woodmart-core` | `1.1.8` | پوسته |
| `duplicator-pro` | `5.0.3` | تجاری |
| `abzarwp-auto-update` | AbzarWP – Auto Update `1.3.21` (دست نخورد؛ تصمیم کاربر) | واسط |
| `akismet` · `safe-svg` | `5.7.2` · `2.5.1` | رایگان |

همه **VERIFIED** (سرآیند پرونده‌ها). مقایسۀ workspace و نصب‌شده: بخش ۱۱.

### mu-plugins (`ریشه:geo/wp-content/mu-plugins`)

`tp-local-debug.php` · `tp-local-http-probe.php` · `tp-local-livelike.php` · `tp-local-safety.php` (همه صراحتاً «فقط محلی»)، `wp-toolkit.php` و پوشۀ `wp-toolkit/` (میزبان) — **VERIFIED**. چهار پروندۀ `tp-local-*` در مخزن هستند ولی طبق کامنتشان باید به زنده نروند: ریسک نشت آن‌ها هنگام انتقال `geo/` به زنده (RISKS.md).

### فعال بودن

افزونه‌های **فعال** هر دو محیط UNKNOWN. محلی: `wp plugin list --fields=name,status,version,update --format=csv` و `wp option get active_plugins --format=json`؛ زنده همان‌ها (فقط `wp plugin list`، مقدار گزینه‌ها بی کلید).

## ۳ — Action Scheduler

| پرسش | پاسخ | برچسب |
|---|---|---|
| کدام افزونه‌ها بستهبندی‌اش کرده‌اند | ۲ تا: `topersia-core/vendor/action-scheduler` و `seo-by-rank-math/vendor/woocommerce/action-scheduler` | **VERIFIED** (`grep -rl "class ActionScheduler_Versions"` روی `ریشه:geo/wp-content`) |
| نسخۀ هرکدام | هر دو `3.9.3` | **VERIFIED** (سرآیند `action-scheduler.php` هر دو) |
| WooCommerce | نصب نیست (فهرست بخش ۲) | **VERIFIED** |
| `seo-by-rank-math-pro` | AS ندارد (فقط مصرف: `as_*` در `includes/modules/redirections/class-schedule.php` و `analytics/workflows/*`) | **VERIFIED** (`grep -l` برای `action_scheduler_register_` فقط دو نتیجۀ بالا) |
| کدام نسخه بار می‌شود | همان `3.9.3`؛ ثبت دوم رد می‌شود (`ActionScheduler_Versions::register` اگر کلید نسخه موجود باشد `false` می‌دهد و بقیه را نمی‌نویسد). **کدام فایل** نمونه را راه می‌اندازد = افزونه‌ای که اول بار می‌شود (ترتیب `active_plugins`، معمولاً الفبایی ← `seo-by-rank-math` پیش از `topersia-core`) | **VERIFIED** (`plugin/topersia-core/vendor/action-scheduler/classes/ActionScheduler_Versions.php:34-37` از نسخۀ نصب‌شده) + **INFERRED** (ترتیب) |
| بار شدن هسته | `require_once __DIR__ . '/vendor/action-scheduler/action-scheduler.php'` بیرون از هر قلاب | `plugin/topersia-core/topersia-core.php:915-917` — **VERIFIED** |
| سلامت هنگام نبود `vendor/` | صف «آماده نیست»، لاگ هشدار | `plugin/topersia-core/includes/topersia-queue.php:30-33,45-51` — **VERIFIED** |
| جدول‌ها | `{prefix}actionscheduler_actions` · `_groups` · `_claims` · `_logs` (ساختۀ خود کتابخانه؛ مشترک میان هسته و Rank Math) | **VERIFIED** (`ActionScheduler_DBStore.php:157,289`) |
| گروه کارهای ما | `topersia` (`TOPERSIA_QUEUE_GROUP`) | `topersia-queue.php:25-26` — **VERIFIED** |
| قلاب‌های ثبت‌شده | `topersia_gallery_agg_rebuild` · `topersia_gallery_agg_rebuild_all` — تنها دو مصرف‌کننده | `plugin/topersia-render/includes/topersia-g-gallery-agg.php:511,516` — **VERIFIED** |
| ارتقا به 4.x | عمداً نشده (ستون `unique_key` و نمایۀ یکتا به جدول مشترک می‌افزاید؛ `topersia-queue.php:9-14`) | **VERIFIED** (کامنت) |
| اجرای صف روی زنده | به `DISABLE_WP_CRON` روشن + کرون سیستمی «فاز ۱۰» وابسته؛ وضعیت واقعی UNKNOWN | **UNKNOWN** — زنده: `wp cron event list`، `crontab -l`، `grep DISABLE_WP_CRON wp-config.php`، `wp action-scheduler status` (اگر CLI در دسترس) |

## ۴ — WP-Cron و کرون‌ها (از کد)

| قلاب | افزونه | تناوب | ثبت | شاهد |
|---|---|---|---|---|
| `topersia_log_prune` | هسته | روزانه | `init` (`topersia_log_schedule`) | `plugin/topersia-core/topersia-core.php:1419,1837-1840` — **VERIFIED** |
| `topersia_user_core_prune` | user-core | روزانه | `activate()` | `plugin/topersia-user-core/includes/class-topersia-db.php:60-62` · پاک‌کردن `:74` · `uninstall.php:122` — **VERIFIED** |
| `topersia_gallery_agg_rebuild` / `_all` | render | تک‌رخدادی از راه AS | `topersia_queue_add()` | `topersia-g-gallery-agg.php:377,496,583` — **VERIFIED** |
| هیچ `wp_schedule_*` در خلاصه‌ساز، schema | — | — | — | **VERIFIED** (`grep` صفر) |

مشاهدۀ مهم: `topersia_log_prune` و `topersia_user_core_prune` وردپرس-کرون‌اند، نه AS؛ این برخلاف قاعدۀ «هیچ کرون موازی» (`topersia-queue.php:12-14`) است و فقط وقتی اجرا می‌شود که WP-Cron یا کرون سیستمی `wp-cron.php` را بزند. اگر زنده `DISABLE_WP_CRON` باشد و کرون سیستمی نباشد، پاک‌سازی لاگ و OTP هرگز اجرا نمی‌شود (جدول OTP حاوی شماره است — RISKS.md). **UNKNOWN** — محلی/زنده: بخش ۳.

وضعیت WP-Cron محلی و زنده، نبود/بود `DISABLE_WP_CRON`: **UNKNOWN** — `wp config get DISABLE_WP_CRON` (فقط مقدار بولی) و `wp cron event list --fields=hook,next_run_relative`.

## ۵ — کش شیء / صفحه

- `ریشه:geo/wp-content/object-cache.php` و `advanced-cache.php` در مخزن نیستند — **VERIFIED**؛ ولی ممکن است `.gitignore`ی باشند. وجود افزونۀ کش شیء (Redis/Memcached/LiteSpeed Object Cache) روی زنده **UNKNOWN**. محلی/زنده: `wp cache type` و `wp eval 'var_dump(wp_using_ext_object_cache());'`.
- بدون کش شیء، ترنزینت‌ها (نرخ‌سنج AI و OTP، کش هوا و خلاصه) در `wp_options` می‌نشینند — **INFERRED**.
- کش مرکزی هسته: `topersia_cache_remember()` با انبارۀ ترنزینت و (فقط محلی) جدول `topersia_cache` (`topersia-cache.php:291-293,329-331`) — **VERIFIED**.

## ۶ — مسیرهای REST (از کد)

فقط افزونۀ **render** مسیر REST ثبت می‌کند. هسته، schema، user-core و خلاصه‌ساز **هیچ** REST ندارند — **VERIFIED** (`grep -rn register_rest_route plugin/ \| grep -v vendor` فقط سه نتیجه).

| مسیر | متد | `permission_callback` | شاهد | نکته |
|---|---|---|---|---|
| `/wp-json/topersia/v1/weather?coords=` | GET | `__return_true` | `plugin/topersia-render/includes/topersia-g-weather.php:1006-1013` | داخل bbox ایران (`:677-678,725-728`)؛ شبکۀ گرد برای کش (`:226`)؛ سهم دقیقه‌ای بالادست (`:731`) |
| `/wp-json/topersia/v1/air?lat&lng` | GET | `__return_true` | `topersia-g-air.php:254-262` | گرد ¼ درجه (`:62`) |
| `/wp-json/topersia/v1/map-points?context=` | GET | `__return_true` | `topersia-g-mappoints.php:157-166` | فقط منتشرشده؛ سقف ثابت ۴۰۰ نقطه (`:80`)؛ خروجی GeoJSON FeatureCollection |

همه **VERIFIED**. هر سه عمومی‌اند و بی احراز؛ حفاظت از سوءاستفاده در weather با بودجۀ بالادست است، در air و map-points با کش مرکزی (**INFERRED** از `topersia_cache_remember`)؛ نرخ‌سنجی سمت مشتری ندارند.

> نسخۀ نصب‌شده (`geo/…/topersia-render 4.5.0`) فایل‌های `topersia-g-air.php` و `topersia-g-mappoints.php` را **ندارد** (R44، بخش ۱۱) ← دو مسیر `/air` و `/map-points` روی نصب‌شده فعلاً وجود ندارند.

## ۷ — مسیرهای AJAX و admin-post (از کد)

| کنش | دسترسی | افزونه | شاهد |
|---|---|---|---|
| `topersia_log_js` | مدیر | هسته | `topersia-core.php:1800-1835` (تابع پذیرش `:1800`، `manage_options` + نانس، ثبت `wp_ajax_topersia_log_js` `:1835`) |
| `topersia_service_test` | مدیر | هسته | `includes/topersia-services.php:656-670` |
| `topersia_pack_plan` · `topersia_pack_run` · `topersia_pack_undo` | مدیر | هسته | `includes/topersia-pack.php:3370,3397,3459` + گارد `:3359` |
| `admin_post_topersia_pack_export` | مدیر | هسته | `topersia-pack.php:3479` |
| `admin_post_topersia_log_export` | مدیر | هسته | `topersia-core.php:1732` |
| `admin_post_topersia_gallery_agg_rebuild_all` | مدیر | render | `topersia-g-gallery-agg.php:578` |
| `tpas_ai_generate` (+ دروازه `topersia_g_ai_gate` اولویت ۱) | واردشده | AI / render | `class-tp-ai-summary.php:19` · `topersia-g-ai.php:163` |
| `tpas_track` | **مهمان + واردشده** | AI | `class-tp-ai-tracker.php:24-25` |
| `tpas_test_key` | مدیر | AI | `class-tp-ai-admin.php:24,833` |
| `topersia_send_otp` · `topersia_verify_otp` | **مهمان + واردشده** | user-core | `class-topersia-auth.php:56-59` |
| `topersia_logout` | واردشده | user-core | `:60` |
| `topersia_save_profile` · `topersia_save_display_prefs` · `topersia_save_place` · `topersia_remove_saved_place` · `topersia_follow_entity` · `topersia_entity_state` · `topersia_submit_review` | واردشده | user-core | بخش ۷ USER.md |
| `admin_post_topersia_setup_jetreviews` | مدیر | user-core | `class-topersia-jetreviews-setup.php:46` |

همه **VERIFIED**. تنها سه کنش `nopriv`: `tpas_track`، `topersia_send_otp`، `topersia_verify_otp`.

### مسیرهای بازنویسی (rewrite)

یک مورد: `news` در render (`topersia-g-news.php:378`)، پشت نگهبان «نوع‌پست `tpc_news` موجود باشد»؛ روی نصب‌شده بی اثر (`topersia-g-news.php:30-32`). `flush_rewrite_rules` فقط با تغییر امضا (`:386-390`). **VERIFIED**.

### فرمان‌های WP-CLI

`wp topersia geo-index …` (هسته، `topersia-geo-index.php:352`) · `wp topersia i18n-gaps …` (`topersia-i18n-gaps.php:378`) · `wp topersia gallery-agg …` (render، `topersia-g-gallery-agg.php:641`) — **VERIFIED**.

### صفحه‌های پیشخوان

منوی والد `topersia` (`topersia-core.php:4476`) با زیرصفحه‌های هسته · سرویس‌های خارجی (`topersia-services.php:619`) · هوش مصنوعی (`class-tp-ai-admin.php:60`) · کاربران (`class-topersia-admin.php:223`)؛ تنظیمات render با منو (`topersia-render.php:237`) — **VERIFIED**. زیرمنوهای AI و user-core اگر والد `topersia` نبود، به منوی `topersia-render` یا منوی مستقل می‌افتند (`class-tp-ai-admin.php:60-74`) — پیچیدگی ترتیب بارگذاری، **VERIFIED**.

## ۸ — جدول‌های سفارشی ما (از `CREATE TABLE`)

| جدول | افزونه | ساخت | شاهد | دروازه |
|---|---|---|---|---|
| `{p}topersia_log` | هسته | `dbDelta` در `admin_init` | `topersia-core.php:1085-1102`، شناسۀ جدول `:1002-1006`، نسخه `TOPERSIA_LOG_SCHEMA = '1.0'` (`:1010-1011`) | همیشه |
| `{p}topersia_cache` | هسته | `dbDelta` در `admin_init` | `includes/topersia-cache.php:310-319` | فقط `wp_get_environment_type() === 'local'` (`:291-293`) |
| `{p}topersia_geo` | هسته | `dbDelta` + `wp topersia geo-index` | `includes/topersia-geo-index.php:63-76` (ستون‌ها: `post_id` PK · `post_type` · `lat DECIMAL(8,6)` · `lng DECIMAL(9,6)` · `geom_kind` · `province_id` · `city_id` · `index_version`؛ نمایه‌ها `ll` · `tll` · `prov` · `city`) | فقط محلی (`:26-28`) |
| `{p}topersia_ai_summaries` | AI | `dbDelta` | `class-tp-ai-db.php:68-83` | همیشه |
| `{p}topersia_ai_events` | AI | `dbDelta` | `class-tp-ai-db.php:85-94` | همیشه |
| `{p}topersia_otp_attempts` | user-core | `dbDelta` | `class-topersia-db.php:27-42` | همیشه |
| `{p}topersia_saved` | user-core | `dbDelta` | `class-topersia-db.php:45-56` | همیشه |
| `{p}actionscheduler_*` (۴ جدول) | کتابخانۀ AS | خود کتابخانه | بخش ۳ | همیشه |

همه **VERIFIED**. `{p}` = پیشوند زنده/محلی **UNKNOWN** (محلی: `wp db prefix`؛ مقدار پیشوند عمداً در سند نیامد). طرح کامل ستون‌های AI و user-core: AI.md ۲ و USER.md.

- حذف افزونه: user-core هر دو جدول را می‌افتد (`uninstall.php:37-38`)؛ AI نه؛ هسته و render `uninstall.php` ندارند ← `topersia_log`، `topersia_cache`، `topersia_geo` و گزینه‌ها بعد از حذف می‌مانند — **VERIFIED** (`ls plugin/topersia-core plugin/topersia-render`: بی `uninstall.php`).
- هیچ جدول سفر، مجموعه، اعلان، `ai_cache`، `ai_runs`، `credits` وجود ندارد — **VERIFIED**.
- شمار ردیف‌ها و اندازه: **UNKNOWN** — محلی: `wp db query "SELECT table_name, table_rows, ROUND((data_length+index_length)/1024/1024,2) AS mb FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name LIKE '%topersia%' OR table_name LIKE '%actionscheduler%'"`.

## ۹ — گزینه‌ها (فقط نام)

گزینه‌هایی که کد می‌نویسد (autoload طبق کد؛ حجم واقعی UNKNOWN):

| گزینه | افزونه | autoload در کد | شاهد |
|---|---|---|---|
| `topersia_services` · `topersia_services_usage` · `topersia_site_is_commercial` · `topersia_services_migrated` | هسته | `topersia_services_migrated`: نه (`false`)؛ بقیه طبق `update_option` پیش‌فرض | `topersia-services.php:35-45,611` |
| `topersia_log_schema` · `topersia_cache_schema` · `topersia_geo_index_schema` · `topersia_geo_index_stamp` · `topersia_news_rules` · `topersia_gallery_agg_last` | هسته / render | همه `false` | `topersia-core.php:1104` · `topersia-cache.php:322` · `topersia-geo-index.php:79,144` · `topersia-g-news.php:390` · `topersia-g-gallery-agg.php` |
| `tpas_settings` (شامل کلیدهای موتور) · `tpas_stats` · `tpas_db_version` · `tpas_prompt_overrides` | AI | `tpas_settings`: **yes** (`add_option` بی پارامتر) | `topersia-ai-summarizer.php:104-105` |
| `topersia_user_core_db_version` | user-core | `false` | `class-topersia-db.php:58` |
| `topersia_mediana_api_key` · `topersia_mediana_pattern_code` · `topersia_account_page_url` · `topersia_user_tabs` | user-core | پیش‌فرض (yes) | `class-topersia-admin.php:67,145,534-560` |
| تنظیمات render | render | — | `topersia-settings.php` (کلید گروه در `topersia-render.php`) |

**VERIFIED** (کد). حجم autoload کل و بزرگ‌ترین گزینه‌ها: **UNKNOWN** — `wp db query "SELECT option_name, LENGTH(option_value) AS bytes FROM {prefix}options WHERE autoload IN ('yes','on','auto-on') ORDER BY bytes DESC LIMIT 20"` و `wp option list --autoload=on --fields=option_name --format=count`.

## ۱۰ — «سقف روزانه» صفحۀ سرویس‌های خارجی

- UI: ستون «سقف روزانه (کاربر · سایت)» در جدول هر سرویس (`plugin/topersia-core/includes/topersia-services.php:822,956-958`) — **VERIFIED**؛ برای **همۀ ۹ سرویس** کاتالوگ (`:47-57`) نمایش داده می‌شود، چه معنا داشته باشد چه نه.
- ذخیره: `limit_user` / `limit_site` در ردیف سرویس (`:525-526`) و بازگشت در `topersia_api()['limits']` (`:285-288`) — **VERIFIED**.
- **چه کسی می‌خواندش:** تنها یک مصرف‌کننده — `topersia_g_ai_caps()` در render (`topersia-g-ai.php:39-40`) که کلید `ai.text` را می‌خواند. `ai.text` از کاتالوگ برداشته شده (`topersia-services.php:47-57` بدون `ai.text`؛ تأیید تصمیم ۴٫۱۲٫۱ در `class-tp-ai-core-bridge.php:5-8`) ← **امروز هیچ** سرویسی سقفش را نمی‌خواند — **VERIFIED**.
- تکراری با سقف خلاصه‌ساز؟ **بله، سه‌گانه** (AI.md بخش ۸): ساعتی داخل AI · روزانۀ `topersia-settings` (`ai_user_daily`، `ai_site_daily`) · روزانۀ صفحۀ سرویس‌ها (مرده). پیشنهاد (INFERRED): یک منبع، ترجیحاً `topersia-settings` یا صفحۀ سرویس‌ها با کلید `ai.text` دوباره در کاتالوگ؛ تصمیم معمار.

## ۱۱ — برابری نسخۀ نصب‌شده با workspace (R44)

`diff -rq` (۱۲ اکتبر، ابری، فقط‌خواندنی) میان `plugin/<p>` و `ریشه:geo/wp-content/plugins/<p>`:

| افزونه | نتیجه |
|---|---|
| `topersia-schema` | یکسان |
| `topersia-ai-summarizer` | ۳ پرونده فرق: `readme.txt` · `tests/test-05-summary.php` · `tests/test-10-phase2.php` (کد `includes/` یکسان) |
| `topersia-user-core` | ۲ پرونده فرق: `CHANGELOG.md` · `CLAUDE.md` (کد یکسان) |
| `topersia-core` | workspace `4.13.1` در برابر `4.13.0`: فقط در workspace `includes/topersia-geo.php` و `includes/topersia-geo-index.php`؛ فرق محتوا در `topersia-core.php` · `includes/topersia-pack.php` · `includes/topersia-services.php` |
| `topersia-render` | workspace `4.5.1` در برابر `4.5.0`: فقط در workspace `includes/topersia-g-air.php` · `topersia-g-mappoints.php` · `topersia-g-relreg.php`؛ فرق محتوا در `assets/topersia-g-template.css` · `topersia-g-zones.js` · `topersia-tokens.css` · `includes/topersia-g-weather.php` · `topersia-g-zones.php` · `topersia-render.php` و ۷ پروندۀ زبان |

همه **VERIFIED** (خروجی فرمان). معنا: **این ممیزی کدِ workspace را می‌خواند**؛ آنچه روی `geo/` نصب است عقب‌تر است (هسته و render). ردیف‌های SYSTEM/MAP که به `/air` · `/map-points` · نمایۀ مکانی ارجاع می‌دهند، **فقط در workspace** وجودند. تأیید اینکه نصب‌شدۀ محلی/زنده کدام نسخه‌اند: **UNKNOWN** — محلی: `wp plugin list --fields=name,version`؛ و همگام‌سازی کار جلسۀ دیگر است (lane).

## ۱۲ — شکاف با اسناد ورودی

| پرسش | نتیجه |
|---|---|
| ARCH #۱۶ «هسته کتابخانه را خودش بسته‌بندی می‌کند؛ نسخه‌های هم‌زمان را خود کتابخانه مدیریت می‌کند» | **هم‌خوان با شاهد** (بخش ۳)؛ شرط: همان نسخه در هر دو (۳٫۹٫۳) |
| P5-D290 «روی زنده به کرون سیستمی وابسته» | کد منتظر آن است؛ **وجودش UNKNOWN** |
| قاعدۀ «هیچ کرون موازی» | دو کرون وردپرس باقی‌اند (بخش ۴) |
| ARCH-RECONCILIATION §۴ «۲د موتور DB `topersia_local` را تأیید می‌کند» | تأیید مستقیم از ابر ممکن نیست؛ **UNKNOWN** — محلی: `wp db query "SELECT @@version, @@version_comment"` |

## ۱۳ — UNKNOWN (فهرست یک‌جا، فرمان محلی)

| # | موضوع | فرمان |
|---|---|---|
| ۱ | نسخۀ DB و موتور (محلی + زنده) | `wp db query "SELECT VERSION(), @@version_comment"` |
| ۲ | تأیید PHP زنده (۸٫۲؟)، `mbstring`، مقدار مؤثر `max_execution_time`/`memory_limit` | `wp --info` / `php -m \| grep mbstring` / `php -r 'echo ini_get("max_execution_time");'` روی میزبان |
| ۳ | افزونه‌های فعال | `wp plugin list --status=active --fields=name,version` |
| ۴ | WP-Cron / کرون سیستمی | `wp config get DISABLE_WP_CRON`، `wp cron event list`، `crontab -l` میزبان |
| ۵ | کش شیء | `wp cache type` |
| ۶ | حجم autoload | بخش ۹ |
| ۷ | شمار و حجم جدول‌ها | بخش ۸ |
| ۸ | کدام `action-scheduler` واقعاً بار می‌شود | `wp eval 'echo ActionScheduler_Versions::instance()->latest_version();'` و `wp eval 'var_export(ActionScheduler_Versions::instance()->active_source());'` |
| ۹ | وضعیت صف: کارهای pending/failed | `wp action-scheduler list --status=pending --group=topersia --format=count` (یا SQL بر `actionscheduler_actions`) |
| ۱۰ | نسخۀ نصب‌شده در برابر workspace | `wp plugin list --fields=name,version` |
