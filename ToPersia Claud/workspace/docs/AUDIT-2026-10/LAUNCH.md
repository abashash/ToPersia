# LAUNCH.md — فهرست وارسی آمادگی راه‌اندازی سایت زنده (فاز ۱۰)

> ابری L · ۱۹ اکتبر ۲۰۲۶ · **فقط‌خواندنی**: پایه `main` `f567727f`؛ هیچ کد، تنظیم یا آزمونی عوض نشد.
> مبنا: «فاز ۱۰» و «پیوست ج» در `docs/SPRINT-4.4-PHASES.md` (خطوط ۱۲۵۵–۱۲۸۰)، تصمیم‌های P5-D353…D369 و OPS-D013 همان سند، و اسناد این پوشه (`RISKS.md` · `PERF.md` · `SCHEMA-PARITY.md` · `SCALE.md` · `SYSTEM.md` · `MAP.md` · `LOCAL-QUERIES.md`).
> هیچ کلید، رمز، شماره، لاگ یا نام کاربری مدیر در این سند نیست. هیچ فرمانی روی زنده یا محلی اجرا نشد؛ ستون «روش سنجش» فرمانی است که **کاربر یا کدکس محلی** اجرا می‌کند.
> `wp-config.php`، پایگاه‌داده، سرور زنده و سرآیندهای HTTP زنده در این مخزن نیست؛ پس هر چه فقط از آن‌ها پیداست «نامعلوم» است، نه «انجام‌شده».

**راهنمای ستون‌ها.** وضعیت: **انجام‌شده** (شاهد در کد/commit، ولی اثر روی زنده تا سنجش تأیید نیست مگر نوشته شود) · **باقی** (کاری مانده) · **نامعلوم** (به زنده یا محلی بستگی دارد). مانع: **مانع** = بدون آن راه‌اندازی نه · **مهم** = تا هفتهٔ اول · **بعداً** = پس از راه‌اندازی. مالک: **کاربر** (cPanel · wp-config · پیشخوان زنده) · **کدکس** (lane render/core) · **ابری** · **معمار** (تصمیم).
نام‌اختصار مسیر: `WS` = ریشهٔ workspace · `UC` = `plugin/topersia-user-core` · `AI` = `plugin/topersia-ai-summarizer` · `CORE` = `plugin/topersia-core` · `RENDER` = `plugin/topersia-render` · `ROOT` = ریشهٔ مخزن (هم‌ارز `public_html`).

---

## ۰ — پیش‌شرط‌ها (فاز ۱۰، بند «پیش‌شرط» و «مخزن و پوشه‌ها»)

| # | بند | وضعیت | شاهد | مالک | روش سنجش روی زنده | حکم |
|---|---|---|---|---|---|---|
| P0-1 | پشتیبان کامل Duplicator از زنده، **همان روز**، و آزمودن بازیابی روی نشانی موقت | باقی | `docs/SPRINT-4.4-PHASES.md:1258` (پیش‌شرط) | کاربر | بستهٔ همان روز در Duplicator Pro با تاریخ و حجم؛ بازیابی روی زیردامنهٔ آزمایشی تا صفحهٔ اصلی بالا بیاید؛ نسخهٔ جدا از `uploads` (حجم محلی ≈ ۱٫۳ گیگ، `PERF.md` §۵) | **مانع** |
| P0-2 | پوشهٔ `ToPersia Claud` هرگز در `public_html` زنده بارگذاری نشود؛ فقط `geo/` و فایل‌های ریشه | باقی | `docs/SPRINT-4.4-PHASES.md:1259` · `ROOT/CLAUDE.md` | کاربر | `curl -sI https://<دامنه>/ToPersia%20Claud/workspace/CLAUDE.md` ← انتظار ۴۰۴؛ و `ls public_html` | **مانع** |
| P0-3 | نسخهٔ افزونه‌های نصب‌شده = زیپ‌های `dist/` از workspace (نه کپی کهنهٔ `geo/`) | نامعلوم | `RISKS.md` R44 (کاهش‌یافته از `b1ec34bb`)؛ commitهای «geo: نسخهٔ نصب‌شده…» تا `95369f7d` | کاربر/کدکس | محلی: `diff -rq "ToPersia Claud/workspace/plugin/<p>" "geo/wp-content/plugins/<p>"` برای هر پنج افزونه؛ زنده: نسخه در سربرگ هر افزونه همان `dist/` | **مانع** |
| P0-4 | ترتیب نصب زیپ‌ها و کارهای پیشخوانی فقط‌کاربر (شرط‌های نمایش ۴۱ نوع P5-D302، دکمهٔ نوع‌های نظر JetReviews، `needs_verification` واژه‌نامهٔ ۳۷، سقف‌های AI، `admin_email`، Site Kit) | باقی | `docs/SPRINT-4.4-PHASES.md:1261-1274` | کاربر | هر بند با گام و ✓ در HANDOVER فاز ۱۰ (کدکس فهرست گام‌به‌گام می‌سازد) | **مانع** (بند به بند؛ `admin_email` و شرط‌های نمایش مانع، بقیه مهم) |

---

## ۱ — امنیت

| # | بند | وضعیت | شاهد | مالک | روش سنجش روی زنده | حکم |
|---|---|---|---|---|---|---|
| S-1 | **P5-D355** کلیدها فقط در پیشخوان و فقط‌نوشتنی: فیلد گذرواژه با `value=""` و «ذخیره‌شده» به‌جای مقدار؛ کلید پیامک بی autoload | انجام‌شده (کد) | `UC/includes/class-topersia-admin.php:568-575,299-303` · `AI/includes/class-tp-ai-admin.php:753,778-781` · commitهای `1c889de4…a77d2053` (`docs/COMMIT-LOG.md:65`) | کاربر (سنجش) | منبع HTML صفحهٔ تنظیمات هر افزونه و `/wp-admin/` با کلید ساختگی: `curl -s -b <کوکی مدیر> …/wp-admin/admin.php?page=… \| grep -c 'value="<بخشی از کلید>"'` ← ۰؛ `wp option get topersia_mediana_api_key --format=json \| wc -c` (فقط طول) | **مانع** تا سنجش یک‌بارهٔ زنده |
| S-2 | ذخیرهٔ کلید AI: `tpas_settings` متن ساده و autoload؛ کلید به صافی عمومی `tpas_api_payload` داده می‌شود (R08 · R56) | نامعلوم | `AI/includes/class-tp-ai-options.php:18,61,405-439` · `AI/includes/class-tp-ai-api.php:156,260,365` (صافی هنوز در کد) · `RISKS.md` R08 · R56 · `RECONCILE.md` §۳-ج | کاربر/ابری-AI | `wp option get tpas_settings --format=json \| wc -c` و `wp option list --autoload=on --search=tpas_settings --fields=option_name,size_bytes`؛ جایگزین: کلید در ثابت wp-config (`key_from_constant`) و خالی در پایگاه‌داده | مهم |
| S-3 | **P5-D354** شمارهٔ موبایل در شناسهٔ عمومی: ورود/ایمیل/نام نمایشی/نامک تصادفی برای حساب تازه؛ مهاجرت حساب‌های موجود در `init` | انجام‌شده (حساب تازه) · نامعلوم (حساب‌های موجود روی زنده) | `UC/includes/class-topersia-auth.php:531-556` · `UC/includes/class-topersia-privacy.php:36,58,179-197` · `RISKS.md` R10 · R37 | کاربر | `wp option get topersia_user_core_pii_migration`؛ `wp user list --field=display_name \| grep -c '^tp_[0-9]'` و همین برای `user_login` ← **۰** (فقط شمار)؛ نظرهای منتشرشده با `comment_author` شماره‌دار: `wp db query "SELECT COUNT(*) FROM <پیشوند>comments WHERE comment_author REGEXP '^[0-9+]{9,}$'"` | **مانع** تا شمار صفر ثابت شود |
| S-4 | **P5-D356** `TOPERSIA_TRUST_PROXY`: `X-Forwarded-For` فقط با ثابت صریح (user-core و خلاصه‌ساز)؛ تعریف روی زنده پس از دیدن سرآیند واقعی | انجام‌شده (کد) · باقی (ثابت روی زنده) | `UC/includes/class-topersia-auth.php:141-157` · `AI/includes/class-tp-ai-summary.php:672-686` · `RISKS.md` R47 · R63 · `RECONCILE.md` §۸ بند ۹ | کاربر (wp-config) | `grep -c TOPERSIA_TRUST_PROXY wp-config.php`؛ تصمیم با یک درخواست آزمون: اگر پشت CDN/پراکسی است، `curl -s https://<دامنه>/ -D- \| grep -i 'cf-connecting\|x-forwarded'` و دو IP متفاوت باید دو سطل نرخ‌سنج بدهند (شمار کلید `topersia_otp_ip_*`). بی CDN (فقط LiteSpeed روی همان میزبان): ثابت **تعریف نشود** | **مانع** (تصمیم و ثبت) |
| S-5 | `WP_DEBUG` و نمایش خطا روی زنده: `display_errors Off` در `.htaccess`؛ `WP_DEBUG` در `wp-config` زنده نامعلوم | نامعلوم | `ROOT/.htaccess:50,52` (`display_errors Off`، `log_errors Off`) · `RISKS.md` R58 | کاربر | `wp config get WP_DEBUG` · `WP_DEBUG_DISPLAY` · `WP_DEBUG_LOG`؛ `curl -s https://<دامنه>/?p=999999999 \| grep -ci 'warning\|notice\|stack trace'` ← ۰ | **مانع** (`WP_DEBUG_DISPLAY` یا `WP_DEBUG` روشن = نشت مسیر و پیشوند) |
| S-6 | لاگ خطای PHP: `log_errors Off` ⇒ خطای زنده جایی ثبت نمی‌شود؛ `error_log` در `disable_functions` cPanel برداشته شود (پیوست فاز ۱۰) | باقی | `ROOT/.htaccess:52` · `docs/SPRINT-4.4-PHASES.md:1272` · `RISKS.md` R58 | کاربر | cPanel ← MultiPHP INI Editor: `log_errors On` و `error_log` بیرون از `public_html`؛ آزمون: یک `error_log('tp-test')` از `wp eval` و دیدن خط در پروندهٔ خصوصی؛ `topersia_log` (هسته) فعال | مهم |
| S-7 | سرآیندهای امنیتی (`X-Content-Type-Options` · `X-Frame-Options`/`frame-ancestors` · `Referrer-Policy` · `Permissions-Policy` · `Strict-Transport-Security` · CSP): **هیچ‌کدام در کد ما یا `.htaccess` مخزن نیست** (grep بی‌نتیجه)؛ احتمالاً لایهٔ میزبان/LiteSpeed | نامعلوم | grep روی `WS/plugin` و `ROOT/.htaccess` (۱۹ اکتبر)؛ `docs/AUDIT-2026-10/SYSTEM.md` (بی ردیف سرآیند) | کاربر/معمار | `curl -sI https://<دامنه>/ \| grep -iE 'strict-transport\|x-content-type\|x-frame\|referrer-policy\|permissions-policy\|content-security'`؛ پیشنهاد کمینه: HSTS پس از تأیید HTTPS کامل، `nosniff`، `Referrer-Policy: strict-origin-when-cross-origin`؛ CSP تنها گزارش‌دهنده تا بازبینی میزبان‌های نقشه/Site Kit | مهم (HSTS و `nosniff` پیش از راه‌اندازی بهتر؛ CSP بعداً) |
| S-8 | نام کاربری مدیر که در کد/تاریخچهٔ مخزن به‌صورت رشتهٔ عمومی آمده (یافتهٔ امنیتی ۲ج): تغییر نام روی زنده؛ پاک‌سازی/بازنگری تاریخچه | باقی | `docs/SPRINT-4.4-PHASES.md:1274,1305` · `RISKS.md` R41 · گارد `scripts/git-guard` (متغیر `TP_GUARD_ADMIN_USER`) | کاربر | محلی: `git grep -nF -c -- "$TP_GUARD_ADMIN_USER" -- . ':!geo/wp-includes'` (فقط شمار؛ مقدار چاپ نشود)؛ زنده: `curl -s https://<دامنه>/wp-json/wp/v2/users \| grep -c slug` و `?author=1` ← بی نشت نام؛ ورود با نام قدیم رد شود؛ فعال‌بودن محدودکنندهٔ ورود | **مانع** |
| S-9 | `name_en` و نامک «01» در #204 (`tp_currency`) و `post_title` برابر کد استان («04») (کارهای داده، §۴) | باقی | `docs/SPRINT-4.4-PHASES.md:1276,48` | کاربر + کدکس | `wp post get 204 --field=post_name` و `wp post meta get 204 name_en`؛ `tp-data-doctor.php` در حالت گزارش | مهم |
| S-10 | پیشوند پایگاه‌داده در ۶ پروندهٔ مخزن (از جمله لاگ `.txt` در `reference/`)؛ `debug.log` در تاریخچه (مخزن از ۵ اکتبر خصوصی؛ رمز احتمالی چرخانده شود) | باقی | `RISKS.md` R61 · `docs/SPRINT-4.4-PHASES.md:1301` (پیوست ج) | کاربر | بیرون‌بردن لاگ از git و `.gitignore`؛ `git grep -lF "$TP_DB_PREFIX"` (مقدار از متغیر، بی چاپ)؛ چرخش هر رمز/کلیدی که در لاگ بوده | مهم |
| S-11 | `tpas_track` بی‌ورود، نانس عمومی و autoload آمار (R49)؛ مسیر `post=0&url=` در AJAX خلاصه‌ساز (R36) | نامعلوم | `RISKS.md` R36 · R49 · `AI/includes/class-tp-ai-summary.php:101-112` — پس از P5-D358 خلاصهٔ AI «فقط به دست مدیر» (`f1163acd`) | ابری-AI | نامعلوم‌بودن مسیر عمومی پس از D358: `curl -s -X POST …/wp-admin/admin-ajax.php -d action=tpas_ai_generate -d post=0 -d url=https://example.com` بی‌ورود ← ۴۰۰/۴۰۳ یا `0` (در کد فقط قلاب `wp_ajax_` هست، `nopriv` نیست؛ `AI/includes/class-tp-ai-summary.php:23-24`)، نه ۲۰۰ | مهم |

---

## ۲ — SEO فنی

| # | بند | وضعیت | شاهد | مالک | روش سنجش روی زنده | حکم |
|---|---|---|---|---|---|---|
| E-1 | `robots.txt` فیزیکی ریشه + `blog_public`: فایل آماده است (بلاک `/wp-admin/` با استثنای admin-ajax، اجازهٔ بات‌های AI، بستن CCBot و Bytespider، نشانی نقشهٔ سایت)؛ مقدار `blog_public` و اینکه فایل مخزن روی زنده بارگذاری شده نامعلوم | انجام‌شده (فایل) · نامعلوم (زنده) | `ROOT/robots.txt:1-26` | کاربر | `curl -s https://<دامنه>/robots.txt` همین متن و آخرین خط `Sitemap: https://topersia.com/sitemap_index.xml`؛ `wp option get blog_public` ← **۱**؛ در `<head>`: بی `noindex`؛ بی `Disallow: /` | **مانع** |
| E-2 | حالت «به‌زودی»: مهمان بی‌نام صفحهٔ maintenance می‌گیرد؛ آن صفحه باید `noindex` و کد ۵۰۳ (یا ۲۰۰ با `noindex`) باشد و در لحظهٔ باز شدن برداشته شود؛ مدیر/ورودی‌ها سایت را می‌بینند | نامعلوم | `WS/CLAUDE.md` (بند «سایت پشت به‌زودی است») · `docs/AUDIT-2026-10/LOCAL-QUERIES.md` LQ-SCH-02 (محلی noindex) | کاربر | **پیش از باز کردن:** `curl -sI https://<دامنه>/` بی کوکی ← `X-Robots-Tag`/`<meta robots noindex>` یا ۵۰۳؛ **پس از باز کردن:** همان صفحه بی‌کوکی ← ۲۰۰، بی `noindex`، و صفحهٔ استان محتوای واقعی؛ کش صفحه پاک شود تا صفحهٔ «به‌زودی» کش‌شده نماند | **مانع** (ترتیب باز کردن) |
| E-3 | canonical در هر سه زبان (و ۹ زبان): کد ما نمی‌نویسد؛ Rank Math هر زبان را می‌نویسد؛ در محلی صفر (سایت noindex و Rank Math بی‌خروجی) | نامعلوم | `docs/AUDIT-2026-10/SCHEMA-PARITY.md:96` · `LOCAL-QUERIES.md` LQ-SCH-02 | کاربر | `for l in "" en/ ar/; do curl -s https://<دامنه>/$l<مسیر استان>/ \| grep -o '<link rel="canonical"[^>]*>'; done` ← یک canonical در هر صفحه به نشانی **همان زبان**؛ نه به فارسی | **مانع** |
| E-4 | hreflang (LQ-SCH-02 / S6): کد ما hreflang نمی‌نویسد؛ WPML `head_langs` (با `x-default`) یا نقشهٔ سایت؛ یکی از دو، هر دو نه؛ در محلی صفر | نامعلوم | `SCHEMA-PARITY.md:95,132` (S6) · `LOCAL-QUERIES.md` LQ-SCH-02 · `SCALE.md` §۵ | کاربر | `curl -s https://<دامنه>/<مسیر>/ \| grep -c 'rel="alternate" hreflang'` ← ۱۰ (۹ زبان + `x-default`) یا در نقشه `xhtml:link`؛ دوسویه بودن (هر ترجمه همه را فهرست کند) | **مانع** |
| E-5 | نقشهٔ سایت و `tp_county` (SC8): حذف با صافی هستهٔ وردپرس بی‌اثر در Rank Math؛ در **محلی** گزینهٔ `pt_tp_county_sitemap=off` است و صفر `tp_county` در نمایه؛ روی زنده نامعلوم؛ ~۹۷۰ پروندهٔ نقشه، ساخت سرد کند | نامعلوم (محلی ✓) | `CORE/topersia-core.php:2563-2569` · `SCALE.md` §۵ و ردیف SC8 (LQ-SCALE-10 محلی: ۱۹ نقشهٔ فرعی، صفر `tp_county`) | کاربر | `curl -s https://<دامنه>/sitemap_index.xml \| grep -c tp_county` ← ۰؛ `wp option get rank-math-options-sitemap --format=json \| grep -o 'pt_tp_county[^,]*'` ← `off`؛ زمان ساخت یک نقشهٔ بزرگ پس از پاک‌کردن کش | **مانع** (صفحهٔ شهرستان با ریدایرکت G4 نباید ایندکس شود) |
| E-6 | ۴۰۴ و ریدایرکت‌ها: ریدایرکت ۳۰۱ صفحهٔ شهرستان G4 به استان و `X-Robots-Tag: noindex`؛ نامک‌های لاتین نادرست فهرست ۲ج٫۱۰ فقط با تصمیم کاربر و ۳۰۱ Rank Math؛ نشانی‌های `/assets` و `/core` از `.htaccess` | انجام‌شده (G4) · باقی (نامک‌ها) | `CORE/topersia-core.php:2494-2583` · `ROOT/.htaccess:12-18` · `docs/SPRINT-4.4-PHASES.md:689,1275` | کاربر + کدکس | `curl -sI https://<دامنه>/<نشانی شهرستان>/` ← ۳۰۱ به استان؛ `curl -sI https://<دامنه>/assets/…css` ← ۲۰۰ از `geo/wp-content`؛ `curl -s -o /dev/null -w '%{http_code}' https://<دامنه>/نشانی-نادرست/` ← ۴۰۴ (نه ۲۰۰ نرم)؛ فهرست نامک‌های ناهمخوان از `tp-data-doctor.php` | مهم |
| E-7 | هم‌خوانی JSON-LD با صفحه: دروازهٔ زبان، `abstract`، `lastReviewed`، ریشهٔ Breadcrumb (schema 1.3.2)؛ `entity_uid` خالی (S12): محلی **۰ از ۲۱۹**؛ گارد هشدار S12 ساخته نشده | انجام‌شده (S1·S2·S4·S5) · باقی (گارد S12) | `docs/relay/CURRENT.md` (ابری H) · `LOCAL-QUERIES.md` LQ-SCH-03 · `SCHEMA-PARITY.md:138` | کدکس + کاربر | زنده: `wp post list --post_type=any --post_status=publish --meta_key=entity_uid --meta_compare='NOT EXISTS' --format=count` ← ۰ (اگر زنده محتوای بیشتری از محلی دارد)؛ `php tests/render-local.php` پیش از انتشار | مهم |
| E-8 | **Rich Results پس از راه‌اندازی:** Rich Results Test و Search Console برای `Place`/`TouristAttraction`/`BreadcrumbList`/`FAQPage` و رویداد بی `startDate` (`Thing`)؛ بررسی «نامعتبر» نبودن `Event` | باقی (پس از انتشار) | `WS/CLAUDE.md` (بند نقشهٔ شِما) · `docs/SPRINT-4.4-PHASES.md:1279` (PageSpeed پس از انتشار) · `JSONLD-SAMPLES-2026-10-18.md` | کاربر | Rich Results Test روی یک صفحهٔ هر نوع (fa/en/ar)؛ Search Console ← Enhancements و Sitemaps (ارسال `sitemap_index.xml`) و «پوشش» پس از ۷ روز | بعداً |

---

## ۳ — کارایی و میزبانی

| # | بند | وضعیت | شاهد | مالک | روش سنجش روی زنده | حکم |
|---|---|---|---|---|---|---|
| H-1 | **کش صفحه با پاک‌شدن والدها (P5-D301 · SC2):** `[tp_page]` برای المنتور «پویا» است، ولی کش صفحهٔ فاز ۹ **ساخته نشده**؛ سرور LiteSpeed است؛ ابطال والدها (استان/کشور با هزاران نشانی × ۹ زبان) طراحی نشده؛ داده زنده (هوا، «برای شما»، ذخیره) نباید در HTML کش بیاید | باقی | `docs/SPRINT-4.4-PHASES.md:125,1250` · `SCALE.md` ردیف SC2 و §۲-ج | کدکس + معمار + کاربر | `curl -sI https://<دامنه>/<استان>/ \| grep -i 'x-litespeed-cache\|cache-control'`؛ ویرایش یک شهر و دوباره‌خواندن صفحهٔ استان همان زبان (باید تازه شود)؛ مهمان در برابر واردشده (بلوک شخصی بیرون از کش) | **مانع** (بدون کش، خزش ۱۹۴k نشانی CPU را می‌برد؛ حداقل: TTL کوتاه + پاک‌کردن همهٔ زبان‌ها با هر ویرایش) |
| H-2 | کش شیء پایدار (SC6): Redis/Memcached نیست (محلی)؛ transient در `wp_options`؛ زنده نامعلوم | نامعلوم | `SYSTEM.md:187-189` · `SCALE.md` §۲-ه و SC6 · `RISKS.md` R45 | کاربر | `wp cache type`؛ `ls -l wp-content/object-cache.php`؛ `wp option list --search='_transient_tpc_*' --format=count` | مهم (راه‌اندازی با LiteSpeed و بی Redis ممکن؛ پس از رشد داده الزامی) |
| H-3 | کرون سیستمی برای Action Scheduler و رویداد بازسازی گالری (SC11 · P5-D274): `DISABLE_WP_CRON` روی زنده روشن فرض شده؛ بی کرون سیستمی صف نمی‌چرخد | باقی | `CORE/includes/topersia-queue.php:13-15` · `docs/SPRINT-4.4-PHASES.md:635,1269,1271` · `SYSTEM.md:135` | کاربر (cPanel Cron) | `wp config get DISABLE_WP_CRON`؛ cPanel ← Cron Jobs هر ۵ دقیقه `wp cron event run --due-now` یا `php wp-cron.php`؛ `wp action-scheduler action list --status=pending --group=topersia --format=count` کاهشی؛ `wp cron event list` رویدادها دیده شود | **مانع** (درون‌ریزی، ترجمه، گالری و AI صف‌محوراند) |
| H-4 | نسخهٔ Action Scheduler بارشده (R42): 3.9.3 ثبت‌شده در هسته؛ Rank Math هم نسخه‌ای دارد؛ بالاترین برنده | نامعلوم | `RISKS.md` R42 | کاربر | `wp eval 'echo ActionScheduler_Versions::instance()->latest_version();'` ← 3.9.3 یا بالاتر (یادداشت) | بعداً |
| H-5 | **PMTiles ۷۰۵ MB با Range:** `uploads/tiles/iran.pmtiles` باید روی زنده با `206`، `Accept-Ranges: bytes`، `Content-Range`، بی فشرده‌سازی و با CORS لازم سرو شود؛ آزمون‌های مخزن فقط وجود فایل را می‌سنجند؛ انتقال ۷۰۵ MB (و `Range` از LiteSpeed/CDN) | باقی | `MAP.md:65-66,119` (آزمون Range نشده) · `docs/SPRINT-4.4-PHASES.md:1268` · `WS/tests/assert-430.php:319` (فقط `is_file`) · P5-D353 | کاربر | `curl -sI -H 'Range: bytes=0-16383' https://<دامنه>/assets/uploads/tiles/iran.pmtiles` ← `206`، `Content-Range: bytes 0-16383/…`، `Accept-Ranges: bytes`؛ `Content-Length` فایل = اندازهٔ محلی (SHA از مخزن بیرونی)؛ باز شدن نقشه استان در شبکهٔ زنده | **مانع** (نقشهٔ آفلاین، پشتیبان P5-D353) |
| H-6 | GeoJSONهای مرز (۳۱ استان، بازساخته در دستهٔ ۵) و `province-osm.csv` روی زنده: فایل‌ها بیرون از git؛ باید با کاشی بارگذاری شوند؛ نگهبان نقطهٔ شهر | انجام‌شده (محلی) · باقی (بارگذاری) | `docs/SPRINT-4.4-PHASES.md:178-182` (P5-D363) · commitهای `594f2c95` · `0017419c` | کاربر + کدکس | `curl -sI https://<دامنه>/assets/uploads/…/<geojson>` ← ۲۰۰ و `Content-Type: application/geo+json` یا `json`؛ شمار ۳۱ فایل؛ نقشهٔ یک استان مرز دارد | مهم |
| H-7 | Lighthouse روی **زنده** (فاز ۹ هدف ≥ ۹۵ در چهار رده): امروز فقط نسخهٔ ایستای محلی: موبایل استان ۳۵ · شهر ۴۳؛ دسکتاپ ۸۵ · ۸۸؛ هدف دور؛ LCP همه `img.tp-hero__img`؛ TTFB زنده ناشناخته | باقی | `PERF.md` §۹ (جدول A0←A4) · `WS/tests/RESULT-lighthouse.txt` · `docs/SPRINT-4.4-PHASES.md:1232` · P5-D366 · P5-D368 | کدکس + کاربر | PageSpeed Insights (موبایل و دسکتاپ) روی استان و شهر اصفهان fa/en پس از کش؛ ثبت LCP، TBT، CLS؛ `curl -w '%{time_starttransfer}'` برای TTFB | مهم (مانع نیست؛ هدف ۹۵ پس از راه‌اندازی و کش) |
| H-8 | ریشه‌های هزینهٔ صفحهٔ موجودیت (SC1: N+1، HTML بی‌سقف، SC4 `meta_value` بی‌شاخص، SC5 جستجوی `LIKE`): گام ۱ SC1 ادغام شد (P5-D369)؛ بقیه تصمیم معماری | انجام‌شده (SC1 گام ۱) · باقی (گام ۲ تا ۴، SC4، SC5) | `SCALE.md` §۷ · commit `f1304f03` · `docs/SPRINT-4.4-PHASES.md` (P5-D369) | کدکس + معمار | زمان پاسخ سرد صفحهٔ استان و شمار پرس‌وجو روی زنده (Query Monitor موقت) | مهم (S1 بدون کش مانع می‌شود؛ پس H-1 مقدم است) |

---

## ۴ — محتوا و داده

| # | بند | وضعیت | شاهد | مالک | روش سنجش روی زنده | حکم |
|---|---|---|---|---|---|---|
| C-1 | منوها و `cms_block`های **زنده** برای تصاویر picsum و نمونهٔ وودمارت: `LIVE-EXPORT-AUDIT` منو و بلوک را پوشش نمی‌دهد؛ در محلی بلوک مگامنو #1935 پاک شد (P5-D362)؛ زنده بررسی نشده | باقی | `docs/SPRINT-4.4-PHASES.md:1275,177,175` · P5-D359 (picsum باز می‌شود و دست نخورد؛ تصویر نمونه پیش از راه‌اندازی جایگزین شود) | کاربر + کدکس | `wp post list --post_type=cms_block --fields=ID,post_title`؛ `wp db query "SELECT ID FROM <پیشوند>posts WHERE post_content LIKE '%picsum.photos%' AND post_status='publish'"` ← ۰؛ HTML زندهٔ صفحه‌ها: `curl -s <url> \| grep -c 'picsum\|woodmart.xtemos'` ← ۰ | **مانع** (محتوای نمونه و درخواست بیرونی برای مهمان) |
| C-2 | مختصات ناقص: ۱۰۱ از ۱۱۶ شهر بی مختصات معتبر؛ #146 «0»؛ سه ردیف کشور و تالاب گاوخونی «ناخوانا» (فرمت درجه)؛ `tp_province` ۳۳ منتشرشده ۰ معتبر | باقی | `COORDS-REPORT.md` §۱ · `RISKS.md` R17 · P5-D363 (نگهبان نقطهٔ شهر) | کدکس + کاربر (محتوا) | `php scripts/local/tp-coords-report.php` (فقط خواندن) روی داده نهایی زنده؛ `wp topersia geo-index report`؛ نقشهٔ فهرست استان باید نشانگر داشته باشد | مهم |
| C-3 | `entity_uid` خالی (S12): محلی ۰ از ۲۱۹؛ زنده با ۳ زبان باید صفر بماند؛ بی آن `@id` هر زبان جدا می‌شود؛ گارد هشدار ساخته نشده | نامعلوم | `LOCAL-QUERIES.md` LQ-SCH-03 · `SCHEMA-PARITY.md:90,138` | کاربر + کدکس | همان فرمان E-7 روی زنده | مهم |
| C-4 | رابطه‌ها و دادهٔ زنده: ۷۱ (کشور↔استان) و ۹۶ (مرکز استان)؛ `tp-data-doctor.php`؛ نام انگلیسی شهرستان #884؛ ردیف‌های تکراری ۱۷۳ | باقی | `docs/SPRINT-4.4-PHASES.md:1276` | کاربر + کدکس | `scripts/tp-data-doctor.php` در حالت گزارش (محلی) و فرمان‌های زنده با پشتیبان؛ شمار ردیف `jet_rel_*` به‌ازای `rel_id` (LQ-SCALE-02) | مهم |
| C-5 | پیوندهای مرده (داخلی و بیرونی): هیچ خزش پیوندی انجام نشده؛ منوی «مقاصد و جغرافیا» تا فاز ۷ پنهان است؛ نشانی `topersia.local` در محتوا | نامعلوم | `docs/SPRINT-4.4-PHASES.md` (دستهٔ ۴: `93c6d623`) · `AUDIT-2026-10/FEATURE-MAP.md` | کدکس + کاربر | `wp search-replace 'topersia.local' --dry-run`؛ خزش پس از انتشار (Search Console «۴۰۴»، یا `wget --spider -r -l2`) روی fa/en/ar؛ `wp db query` برای `href` به `localhost/topersia.local` | مهم |
| C-6 | محتوای `tpc_news`، `topersia_geo`، `tpc_trip`، `display_priority`، `_tp_gallery_agg`، `content_status=ignore` و داده‌های آزمون محلی **به زنده نمی‌روند** تا ممیزی | باقی (مرز) | `docs/SPRINT-4.4-PHASES.md:1277` | کاربر | `wp post-type list` زنده ← بی `tpc_news` و `tpc_trip`؛ `wp post list --search='tp_test'` ← ۰ | **مانع** |
| C-7 | نمونهٔ آزمون محلی: حساب‌های آزمون (`_tp_test_account`) و داده LOCAL-DATA نباید در بستهٔ انتقال بیاید | نامعلوم | `WS/CLAUDE.md` (بند `test-admin`) | کاربر | `wp user list --meta_key=_tp_test_account --format=count` ← ۰ در زنده | **مانع** |

---

## ۵ — دسترسی از ایران

| # | بند | وضعیت | شاهد | مالک | روش سنجش | حکم |
|---|---|---|---|---|---|---|
| I-1 | **P5-D353** OpenFreeMap (نقشهٔ آنلاین اول) و **P5-D359** میزبان‌های بیرونی (`fonts.googleapis.com` خاموش‌شده؛ `picsum` · `tiles.openfreemap.org` بازمانده): «باز از ایران بی VPN» **تأییدنشده**؛ ماشین محلی همیشه پشت VPN با IP آلمان | **نامعلوم** (تأییدنشده؛ OPS-D013) | `docs/SPRINT-4.4-PHASES.md:174,175,184` (OPS-D013) | کاربر | از ماشین/سرور **داخل ایران** یا پروفایل split-tunnel: `curl -sI -m 5 https://tiles.openfreemap.org/styles/liberty`؛ هر سنجش اول کشور IP را ثبت کند (`curl -s https://ipinfo.io/country`) | **مانع** برای نقشهٔ آنلاین؛ پشتیبان آفلاین (PMTiles) در جای خود تا H-5 |
| I-2 | روش سنجش از شبکهٔ ایران بدون VPN: (الف) split-tunnel که فقط `topersia.local` و میزبان‌های سنجشی را مستقیم ببرد، یا (ب) ماشین/سرور ایران؛ ثبت IP-کشور پیش از هر نتیجه؛ `tests/ext-hosts.js` اجرا شود و «جدول میزبان‌ها» با کشور IP ثبت شود | باقی | `docs/SPRINT-4.4-PHASES.md:184` (پیشنهاد، اجرا نشد) · `WS/tests/ext-hosts.js` | کاربر | `node tests/ext-hosts.js` از شبکهٔ ایران؛ خروجی: میزبان ← وضعیت (۲۰۰/تهی/زمان) و کشور IP؛ در گزارش «تأییدشده» فقط پس از آن | **مانع** (برای ادعای دسترسی؛ نه برای پایداری سایت) |
| I-3 | Site Kit / Sign in with Google و سرویس‌های Google (گوگل از ایران محدود است)؛ آزمون ورود با گوگل (P5-D280) | باقی | `docs/SPRINT-4.4-PHASES.md:1270,1102` | کاربر | ورود با گوگل روی HTTPS زنده؛ در شبکهٔ ایران بی VPN: ورود پیامکی باید **مسیر اصلی** بماند؛ هیچ اسکریپت Google در مسیر رندر مهمان (P5-D359 ادعای صفر `fonts.googleapis.com`) | مهم |
| I-4 | هوای MET/Open-Meteo و کاشی بیرونی در مسیر کاربر ایرانی؛ مهلت و بازگشت آفلاین در نقشه (۲٫۵ ثانیه) | نامعلوم | P5-D353 · `CORE/includes/topersia-services.php:87-88` | کاربر | شبکهٔ ایران: صفحهٔ استان، کارت هوا (`/weather`) و نقشه؛ زمان تا نمایش؛ خطای کنسول صفر | مهم |

---

## ۶ — حقوقی و انتساب

| # | بند | وضعیت | شاهد | مالک | روش سنجش روی زنده | حکم |
|---|---|---|---|---|---|---|
| L-1 | انتساب OpenStreetMap (و Protomaps) روی نقشه: پیکربندی نقشه «© مشارکت‌کنندگان OpenStreetMap» دارد؛ انتساب Protomaps/MapLibre در رابط نقشه و صفحهٔ «درباره/منابع» بررسی نشده؛ مجوز ODbL برای دادهٔ مشتق (کاشی)؛ انتساب MET/CAMS/OpenFreeMap | انجام‌شده (OSM در پیکربندی) · نامعلوم (Protomaps و صفحهٔ منابع) | `RENDER/includes/topersia-g-zones.php:3011-3055,3175-3176` · `RISKS.md` R31 · `docs/SPRINT-4.4-PHASES.md:174` | کاربر + کدکس | صفحهٔ استان fa/en/ar: دکمهٔ «i» انتساب باز شود و OSM و Protomaps را بنویسد؛ لینک به `openstreetmap.org/copyright`؛ در حالت آنلاین OpenFreeMap و OpenMapTiles | **مانع** (الزام مجوز) |
| L-2 | حریم خصوصی: صفحهٔ سیاست حریم خصوصی/شرایط (شماره موبایل، OTP، کوکی، AI، Site Kit)، کانال حذف داده، افشای ردیابی؛ در کد `ToPersia_Privacy` فقط برای نام عمومی است؛ هیچ صفحهٔ سیاست در مخزن نیست؛ هرس OTP و `topersia_ai_*` (R33) | باقی | `UC/includes/class-topersia-privacy.php` (نام عمومی، نه سیاست) · `RISKS.md` R33 · R40 · R62 | کاربر (متن) + معمار | پیوند سیاست در فوتر و فرم ورود؛ `wp post list --post_type=page --name=privacy-policy`؛ آزمون «درخواست حذف حساب» با کاربر آزمون؛ رضایت/اعلان کوکی اگر Site Kit/آمار فعال است | **مانع** (جمع‌آوری شماره موبایل بی سیاست ممنوع) |
| L-3 | **P5-D364** برچسب خلیج فارس در هر زبان: fa «خلیج فارس» · en «Persian Gulf» · ar «الخليج الفارسي» در هر دو سبک نقشه؛ `tests/map-gulf.js` ۱۲/۰؛ ایراد R51: برچسب آب با قلم Italic بی بازهٔ عربی در fa/ar نمایش داده نمی‌شود (اصلاح یک‌خطی پیشنهادی، در `main` بررسی شود) | انجام‌شده (D364) · نامعلوم (R51 در main) | `docs/SPRINT-4.4-PHASES.md:179` · `RISKS.md` R51 · commit `f19dd40e` | کدکس + کاربر | چشمی کروم و فایرفاکس روی کاشی زنده: صفحهٔ کشور و استان ساحلی در fa/en/ar، «خلیج فارس» دیده شود؛ `node tests/map-gulf.js` محلی؛ مرز و نام جزایر (ابوموسی و تنب‌ها) در هر زبان | **مانع** (برچسب غلط یا ناپدید = ریسک حیثیتی/حقوقی) |
| L-4 | مجوز و انتساب خدمات داده/AI: هوا (MET، CAMS)، ElevenLabs/Gemini TTS، تقویم فارسی (P5-D335 ممنوع تا بررسی مجوز)؛ پرچم «سایت تجاری» | کاهش‌یافته | `RISKS.md` R31 · `CORE/includes/topersia-services.php:90-91,253-255` | معمار + کاربر | کاتالوگ سرویس‌ها: `free_commercial`؛ صفحهٔ «منابع» هر منبع و تاریخ | مهم |
| L-5 | افزونه‌ها و پوسته‌های شخص ثالث تجاری روی زنده: مجوز معتبر و به‌روزرسانی (JetEngine · Elementor Pro · WPML · Rank Math Pro · وودمارت)؛ بررسی دقیق در جلسهٔ کاربر | نامعلوم | `RISKS.md` R23 · R27 | کاربر | پیشخوان ← افزونه‌ها: وضعیت مجوز هر مورد، تاریخ آخرین به‌روزرسانی، `wp plugin list --update=available` | مهم |

---

## ۷ — پشتیبان و برگشت

| # | بند | وضعیت | شاهد | مالک | روش سنجش | حکم |
|---|---|---|---|---|---|---|
| B-1 | پشتیبان کامل **پیش از انتشار** (فایل + پایگاه‌داده) و رونوشت **بیرون از میزبان**؛ یادداشت اندازه و تاریخ | باقی | `docs/SPRINT-4.4-PHASES.md:1258` · `docs/HANDOVER-Template-G-v2_0.md` (فاز ۱۰) | کاربر | فایل بسته در Duplicator Pro + دانلود محلی؛ `sha256sum` ثبت (بیرون از git)؛ هیچ پشتیبان/دیتابیس در git | **مانع** |
| B-2 | **برنامهٔ برگشت** نوشته‌شده: معیار تصمیم (مثلاً خطای ۵xx بالای حدی، ورود خراب، نقشهٔ ناپدید)، زمان‌بندی (پنجرهٔ کم‌ترافیک)، گام‌های برگشت (بازیابی Duplicator، بازگرداندن `.htaccess` و `wp-config`، برداشتن افزونه‌های تازه، `blog_public=0`/صفحهٔ «به‌زودی» برگردد، پاک‌کردن کش) و مالک هر گام؛ آزمون بازیابی روی نشانی موقت (P0-1) | باقی | **سند و گام‌ها وجود ندارد** (جست‌وجوی `rollback`/`برگشت` در `docs/` جز برگشت دادهٔ AI و بستهٔ محتوا نتیجهٔ دیگری نداد) | کدکس (نوشتن) + کاربر (اجرا) | پیش‌نویس در HANDOVER فاز ۱۰؛ تمرین برگشت یک‌بار روی نشانی موقت؛ زمان تا بازیابی ثبت شود | **مانع** |
| B-3 | برگشت‌پذیری گام‌های داده روی زنده: درون‌ریزی بسته (ردیف برگشت در کانال `import`)، `tp-heh-content.php --restore`، ریدایرکت‌های ۳۰۱؛ هر گام پیش از اجرا پشتیبان | انجام‌شده (ابزار) · باقی (روی زنده) | `WS/CLAUDE.md` (بندهای بستهٔ محتوا و «ۀ») · P5-D272 · P5-D265 | کاربر | «برگرداندن» در زبانهٔ «بستهٔ محتوا» پیشخوان (با کاربر آزمون روی محلی)؛ پشتیبان پیش از هر گام | مهم |
| B-4 | پس از راه‌اندازی: پایش ۴۸ ساعتهٔ نخست (خطای PHP، ۵۰۰/۴۰۴ در لاگ دسترسی، صف AS، هزینهٔ AI، OTP ۴۲۹، صف ایمیل/پیامک) و پشتیبان دوم | باقی | `RISKS.md` R13 · R26 · R48 · R63 | کاربر | `wp action-scheduler action list --status=failed`؛ `wp cron event list`؛ لاگ‌ها؛ هشدار هزینه | مهم |

---

## ۸ — فهرست به ترتیب اولویت

### مانع راه‌اندازی (باید پیش از باز کردن سایت)

P0-1 · P0-2 · P0-3 · P0-4 (`admin_email` و شرط‌های نمایش) · S-1 · S-3 · S-4 · S-5 · S-8 · E-1 · E-2 · E-3 · E-4 · E-5 · H-1 · H-3 · H-5 · C-1 · C-6 · C-7 · I-1 · I-2 · L-1 · L-2 · L-3 · B-1 · B-2

### مهم (هفتهٔ نخست)

S-2 · S-6 · S-7 · S-9 · S-10 · S-11 · E-6 · E-7 · H-2 · H-6 · H-7 · H-8 · C-2 · C-3 · C-4 · C-5 · I-3 · I-4 · L-4 · L-5 · B-3 · B-4

### بعداً

E-8 (Rich Results و Search Console پس از ۷ روز) · H-4 · CSP کامل (S-7) · اجرای S1 گام ۲ تا ۴، SC4، SC5 (SCALE) · Redis/جدول FULLTEXT · سه‌زبانه‌کردن پیشخوان هسته

---

## ۹ — ۱۰ بند برتر

۱. **B-1 + B-2** — پشتیبان کامل همان روز و **برنامهٔ برگشت نوشته‌شده با آزمون بازیابی روی نشانی موقت** (برنامهٔ برگشت امروز اصلاً وجود ندارد). مالک: کاربر + کدکس.
۲. **S-8 + S-1 + S-3** — تغییر نام کاربری مدیر روی زنده، سنجش یک‌بارهٔ فرم‌های کلید (بی مقدار در HTML) و شمار صفر شماره‌های موبایل در `display_name`/`user_login`/نام نظر. مالک: کاربر.
۳. **S-5 + S-4** — `WP_DEBUG`/`WP_DEBUG_DISPLAY` خاموش روی زنده؛ تصمیم و ثبت `TOPERSIA_TRUST_PROXY` پس از دیدن سرآیند واقعی. مالک: کاربر (wp-config).
۴. **E-1 + E-2** — `blog_public=1`، `robots.txt` ریشه روی زنده، و **ترتیب باز کردن**: صفحهٔ «به‌زودی» `noindex`، پس از باز شدن برداشته و کش پاک شود. مالک: کاربر.
۵. **E-3 + E-4 + E-5** — canonical هر زبان، hreflang دوسویه با `x-default`، و نبود `tp_county` در نقشهٔ سایت (هر سه در محلی صفر/نامعلوم‌اند؛ تنها روی زنده معلوم می‌شود). مالک: کاربر.
۶. **H-1** — کش صفحه با ابطال والدها (P5-D301): بدون آن خزش ۱۹۴ هزار نشانی × ۹ زبان صفحهٔ بی‌کش را می‌کشد؛ حداقل ساده: TTL کوتاه و پاک‌کردن همهٔ زبان‌ها با هر ویرایش. مالک: کدکس + معمار + کاربر.
۷. **H-3** — کرون سیستمی برای Action Scheduler و بازسازی گالری (`DISABLE_WP_CRON` روشن فرض شده). مالک: کاربر.
۸. **H-5 + I-1 + I-2** — PMTiles ۷۰۵ MB با Range/206 روی زنده و سنجش واقعی از شبکهٔ ایران بدون VPN (امروز «تأییدنشده»، OPS-D013). مالک: کاربر.
۹. **C-1 + C-6 + C-7** — پاک‌بودن منو/`cms_block`های زنده از picsum و نمونهٔ وودمارت، و نرفتن `tpc_news`/`tpc_trip`/دادهٔ آزمون به زنده. مالک: کاربر + کدکس.
۱۰. **L-1 + L-2 + L-3** — انتساب OSM و Protomaps روی نقشه، سیاست حریم خصوصی برای جمع‌آوری شمارهٔ موبایل، و برچسب «خلیج فارس» در سه زبان روی کاشی زنده. مالک: کاربر + کدکس + معمار.

---

## ۱۰ — محدودیت‌های این ممیزی

- هیچ فرمان زنده، آزمون مرورگری، Lighthouse یا `*-local.php` در ابر اجرا نشد؛ اعداد کارایی نقل از `PERF.md` و `tests/RESULT-lighthouse.txt` است.
- «انجام‌شده (کد)» یعنی در `main` `f567727f` شاهد دارد، نه اینکه روی زنده نصب و اجرا شده؛ سنجش زنده ستون جدا دارد.
- بندهای نیازمند `wp-config.php`، سرآیند HTTP، cPanel، پایگاه‌داده زنده و شبکهٔ ایران «نامعلوم» ثبت شدند.
- بخش مجوز تجاری افزونه‌ها (L-5) تنها وضعیت مجوز و به‌روزرسانی را می‌خواهد؛ بازرسی عمیق‌تر کار این سند نیست.
