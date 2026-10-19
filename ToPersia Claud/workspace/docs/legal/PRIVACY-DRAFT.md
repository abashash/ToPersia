# پیش‌نویس سیاست حریم خصوصی — ToPersia

> **پیش‌نویس — پیش از انتشار باید به دست مشاور حقوقی بازبینی شود.**

- مانع: `L-2` در `docs/AUDIT-2026-10/LAUNCH.md` · مبنا: `main` `50b8dc4f` · ابری M، ۱۹ اکتبر ۲۰۲۶.
- فقط سند است؛ هیچ کد، تنظیم یا آزمونی عوض نشد. `geo/` فقط خوانده شد. `abzarwp` کنار گذاشته شد.
- هر جا داده یا مدرک نیست، جای خالی `[تکمیل به دست کاربر]` مانده است. هیچ ادعای حقوقی یا قانونی بی‌شاهد ساخته نشده.
- شاهد = `پرونده:خط` نسبت به `ToPersia Claud/workspace/plugin/` (در جدول‌ها کوتاه‌شده: UC = `topersia-user-core`، AI = `topersia-ai-summarizer`، R = `topersia-render`، C = `topersia-core`). `[سنجیده‌نشده]` یعنی در کد ما نیست یا به تنظیم زندهٔ سایت بسته است.

---

## بخش ۱ — فهرست داده (از روی کد)

### ۱٫۱ آنچه از بازدیدکنندهٔ بی‌نام جمع می‌شود

| # | داده | چرا | کجا نگه داشته می‌شود | مدت | شخص ثالث؟ | شاهد |
|---|---|---|---|---|---|---|
| A1 | نشانی IP (فقط به‌صورت هش HMAC با نمک سایت) هنگام درخواست کد ورود | محدودسازی نرخ درخواست OTP و جلوگیری از سوءاستفاده | کلید ترنزینت `tp_otp_ip_<هش>` و ستون `ip_hash` جدول OTP | ترنزینت ۱ ساعت؛ ردیف جدول: تأییدنشده ۱ روز، تأییدشده ۷ روز | خیر | UC `class-topersia-auth.php:146-157` · `:197-252` · `class-topersia-db.php:36` · `:100-112` |
| A2 | نشانی IP خام (محدودسازی نرخ خلاصه‌ساز AI) — فقط وقتی `visitor_requests` روشن شود؛ پیش‌فرض خاموش و بی‌ورود رد می‌شود | سقف درخواست | ترنزینت `tpas_rt_ip_<md5(ip)>` (نه IP خام) | ۱ ساعت | خیر | AI `class-tp-ai-summary.php:496-513` · `:673-682` · `class-tp-ai-options.php:72` |
| A3 | شمارندهٔ کلیک دکمه‌های «موتور هوش مصنوعی» (بی IP و بی UA، فقط شمارش به‌ازای صفحه و سرویس) | آمار استفاده | `postmeta` `_tpas_clicks` + گزینهٔ `tpas_stats`؛ اگر `analytics_enabled` روشن باشد ردیف رویداد با زمان | تعیین‌نشده (با حذف نوشته پاک می‌شود) | خیر | AI `class-tp-ai-tracker.php:33-60` · `:96-98` · `class-tp-ai-db.php:275-287` |
| A4 | درخواست مرورگر به `tiles.openfreemap.org` (سبک، کاشی، قلم، نماد نقشه) | نمایش نقشهٔ تعاملی — **از مرورگر بازدیدکننده، نه از سرور ما**؛ فقط پس از دیده‌شدن نقشه؛ IP، User-Agent و Referer را میزبان نقشه می‌بیند | سمت OpenFreeMap؛ سیاست آن‌ها [سنجیده‌نشده] | تعیین‌نشده | **بله — OpenFreeMap** | `topersia-core/includes/topersia-services.php:94` · R `assets/topersia-g-zones.js:2096-2105` |
| A5 | درخواست آزمایشی «آنلاین است؟» (همان درخواست سبک OpenFreeMap) و نتیجه‌اش | انتخاب نقشهٔ آنلاین یا پشتیبان آفلاین | `sessionStorage` کلید `tp-map-online` | تا بستن برگه | همان A4 | R `topersia-g-zones.js:2098-2116` |
| A6 | پیش‌تنظیم جایگزین: PMTiles خودمیزبان `tiles/iran.pmtiles` (درخواست Range به سرور خودمان) | نقشهٔ آفلاین | سرور خودمان؛ لاگ وب‌سرور [سنجیده‌نشده] | تعیین‌نشده | خیر | C `topersia-services.php:96` |
| A7 | سبک نقشهٔ ماهواره‌ای MapTiler — **پیش‌فرض خاموش** تا کلید گذاشته شود؛ کلید در نشانی کاشی به مرورگر می‌رسد | نقشهٔ ماهواره | سمت MapTiler | تعیین‌نشده | **بله — MapTiler (فقط اگر روشن شود)** | C `topersia-services.php:95` |
| A8 | انتخاب‌های نمایش: حالت رنگ، اندازه و فاصلهٔ متن، پهنای ستون، کنتراست، کاهش رنگ، توقف حرکت، پخش خودکار | دسترس‌پذیری و ترجیح بازدیدکننده | `localStorage` با پیشوند `tp-` در همان مرورگر (برای بی‌نام جایی ارسال نمی‌شود) | تا پاک‌کردن توسط کاربر | خیر | R `assets/topersia-display.js:20-34` · UC `assets/js/display-sync.js:23,30-35` |
| A9 | نام آخرین دکمهٔ مسیریابی انتخاب‌شده | مرتب‌کردن دکمه‌ها | `localStorage` کلید `tp-nav-last` | تا پاک‌کردن | خیر (سایت موقعیت نمی‌خواهد؛ پیوند به اپ مقصد است) | R `topersia-g-zones.js:1467-1485` |
| A10 | سبک نقشهٔ برگزیده | ترجیح | `localStorage` کلید `tp-map-style` | تا پاک‌کردن | خیر | R `topersia-g-zones.js:2291` · `:2616` |
| A11 | نشانهٔ «جلوهٔ هوا قبلاً دیده شد» | یک‌بار نمایش جلوه | `sessionStorage` کلید `tp-wx-fx-<وضعیت>` | تا بستن برگه | خیر | R `assets/topersia-g-weather.js:254-262` |
| A12 | پیوند بیرونی دکمه‌های مسیریابی (Google Maps · Waze · نشان و ...) | بازکردن مقصد در اپ دیگر | با کلیک کاربر مرورگر به آن سرویس می‌رود؛ از سایت ما داده‌ای نمی‌رود جز مختصات مقصد در نشانی | — | **بله — با کلیک کاربر** | C `topersia-services.php:110-111` |
| A13 | کوکی زبان WPML و کوکی‌های کش/نشست | تشخیص زبان صفحه | در افزونهٔ شخص ثالث؛ نام و مدت [سنجیده‌نشده] | [سنجیده‌نشده] | خیر | `geo/` فقط‌خواندنی؛ در کد ما نیست |
| A14 | `Rank Math` ← ماژول Analytics با `gtag` (Google Analytics) — **اگر مدیر در پیشخوان وصل کرده باشد** | آمار بازدید | سمت Google | [سنجیده‌نشده] | **بله — Google (شرطی)** | `geo/wp-content/plugins/seo-by-rank-math/includes/modules/analytics/class-gtag.php:340` |
| A15 | آواتار `get_avatar_url` در پنل حساب (فقط واردشده؛ نشانی ایمیل ساختگی است) | نمایش تصویر کاربر | اگر آواتار فعال باشد، درخواست مرورگر به Gravatar | [سنجیده‌نشده] (`show_avatars`) | **بله — Gravatar (شرطی)** | UC `class-topersia-dashboard.php:88` · `class-topersia-privacy.php:197-211` |

نبود: `Site Kit` و `Hotjar`/`Clarity`/`Matomo`/`fbq` در پوشهٔ `geo/wp-content/plugins` و کد ما نیست (جست‌وجوی رشته؛ تنها `gtag` در Rank Math و WPML/Duplicator بالا ذکر شد). اگر در پیشخوان زنده افزونهٔ دیگری نصب شده باشد، اینجا دیده نمی‌شود — پرسش Q4.

سرویس‌های **سمت سرور** (مرورگر بازدیدکننده به آن‌ها درخواست نمی‌دهد و داده‌ای از بازدیدکننده نمی‌برند): هوا (`api.met.no`، `api.open-meteo.com`) و کیفیت هوا (`air-quality-api.open-meteo.com`) با مختصات *موجودیت*، گرد‌شده به شبکهٔ ۰٫۲۵ درجه — نه مختصات بازدیدکننده (R `includes/topersia-g-air.php:61-62` · `:90-105` · `topersia-g-weather.php:826` · `:911-914`).

### ۱٫۲ آنچه از کاربر واردشده (ورود با موبایل) جمع می‌شود

| # | داده | چرا | کجا | مدت | شخص ثالث؟ | شاهد |
|---|---|---|---|---|---|---|
| B1 | شمارهٔ موبایل ایران (`09…`) در درخواست کد | ارسال کد یک‌بارمصرف و ورود | **به ارائه‌دهندهٔ پیامک Mediana** (`api.mediana.ir/sms/v1/send/otp`، همراه شمارهٔ گیرنده و کد) | سمت Mediana [سنجیده‌نشده] | **بله — Mediana** | UC `class-topersia-auth.php:133-135` · `class-topersia-mediana-sms.php:11-25` |
| B2 | تطبیق کد: **هش HMAC شمارهٔ موبایل** (نه خود شماره) در ترنزینت؛ شمارهٔ مرحلهٔ بررسی از ورودی همان درخواست می‌آید و با هش تطبیق می‌خورد | تطبیق کد | `options` (ترنزینت `tp_otp_<توکن>`؛ کد و شماره فقط به‌صورت هش) | ۳۰۰ ثانیه | خیر | UC `class-topersia-auth.php` (`mobile_h`، `hash_equals`) — ۱٫۱۲٫۰ · P5-D375 |
| B3 | سابقهٔ تلاش: **هش HMAC شمارهٔ موبایل** (۳۲ نویسهٔ نخست HMAC-SHA256 با نمک سایت؛ شمارهٔ متن ساده در جدول نیست)، هدف، زمان، شناسهٔ درخواست پیامک، تعداد تلاش، تأییدشده یا نه، هش IP | حسابرسی و محدودیت | جدول `{prefix}topersia_otp_attempts` | هر ردیف با پایان مهلتش (و یک ساعت مهلت) پاک می‌شود — کرون روزانه؛ ردیف‌های متن‌سادهٔ پیشین یک‌بار هش شدند | خیر | UC `class-topersia-db.php` (`mobile_hash`، `prune`، `maybe_hash_otp_rows`) — ۱٫۱۲٫۰ · P5-D375 |
| B4 | شمارش محدودسازی به‌ازای شماره (هش sha256 شماره با نمک) و سقف کل سایت | سقف ساعتی | ترنزینت `tp_otp_rl_<هش>` · `tp_otp_global` | ۱ ساعت | خیر | UC `class-topersia-auth.php:177-252` |
| B5 | شمارهٔ موبایل حساب | شناسهٔ ورود؛ نمایش به خود کاربر در پنل | `usermeta` `_topersia_mobile` (متن ساده) | تا حذف حساب یا حذف افزونه؛ مدت دیگری در کد نیست | خیر | UC `class-topersia-auth.php:562` · `templates/dashboard.php:67` · `uninstall.php` |
| B6 | حساب وردپرس: نام ورود، نام‌مک و ایمیل **تصادفی** و نام نمایشی خنثی («مسافر ۴۸۲۱»)؛ شماره هرگز در نام عمومی نیست | حریم‌سازی (P5-D354) | `users` و `usermeta` | تا حذف حساب | خیر | UC `class-topersia-auth.php:525-562` · `class-topersia-privacy.php:133-200` |
| B7 | نام، نام خانوادگی، کشور، زبان (fa/en/ar)، سبک سفر | نمایه (اختیاری) | `usermeta`: `first_name` · `last_name` · `_topersia_country` · `_topersia_language` · `_topersia_travel_style`؛ نام نمایشی از نام و نام خانوادگی ساخته می‌شود (و در صورت خالی‌بودن، خنثی) | تا حذف حساب | خیر | UC `class-topersia-profile.php:13-37` |
| B8 | مکان‌های ذخیره‌شده | فهرست دلخواه کاربر | جدول `{prefix}topersia_saved` + `usermeta` `_topersia_saved_entities` | تا حذف حساب یا افزونه | خیر | UC `class-topersia-db.php:45-57` · `:260-274` · `class-topersia-jetengine.php:233-253` |
| B9 | موجودیت‌های دنبال‌شده | دنبال‌کردن خبر | `usermeta` `_topersia_followed` | تا حذف حساب | خیر | UC `class-topersia-follow.php:26` · `:144` |
| B10 | ترجیح‌های نمایش (هفت کنترل B/A8) همگام با حساب | همگام‌سازی بین دستگاه‌ها | `usermeta` `_topersia_display_prefs` | تا حذف حساب | خیر | UC `class-topersia-display-prefs.php:35` · `:94` · `:166` |
| B11 | کوکی ورود وردپرس (`wp_set_auth_cookie`؛ «مرا به خاطر بسپار» اختیاری) | حفظ نشست | مرورگر کاربر؛ مدت = پیش‌فرض وردپرس (هسته) و با گزینهٔ «به خاطر بسپار» بلندتر [سنجیده‌نشده] | [سنجیده‌نشده] | خیر | UC `class-topersia-auth.php:503-506` |
| B12 | نظر کاربر: امتیاز معیارها، عنوان، متن (۱۰ تا ۳۰۰۰ نویسه)، تاریخ، شناسهٔ کاربری نویسنده؛ **همیشه در انتظار تأیید مدیر**؛ یک نظر برای هر کاربر و هر مکان | نظر بازدیدکنندگان (P5-D318) | جدول‌های JetReviews | تا حذف توسط مدیر؛ مدت در کد نیست | خیر | UC `class-topersia-review-form.php:107-170` · `:205` · `docs/SPRINT-4.4-PHASES.md:153` |
| B13 | شمارندهٔ روزانهٔ خلاصه‌ساز AI به‌ازای کاربر (تاریخ و تعداد) | سقف مصرف | `usermeta` `_topersia_ai_daily` (در `uninstall.php` پاک نمی‌شود) | تعیین‌نشده | خیر | R `includes/topersia-g-ai.php:89-110` |
| B14 | پیوند «موتور هوش مصنوعی» (ChatGPT/Claude/Gemini/Copilot) فقط برای واردشده: پرسش شامل **نام موجودیت و نشانی صفحه** | ادامه در دستیار دلخواه | با کلیک، مرورگر کاربر به سایت دستیار می‌رود؛ داده‌ای از حساب کاربر در پرسش نیست | — | **بله — با کلیک کاربر** | R `includes/topersia-g-zones.php:5092-5102` · `:5230-5262` |
| B15 | ردیف لاگ فنی: زمان، سطح، پیام، شناسهٔ کاربر، نشانی صفحه (برای رویداد ورود فقط شناسهٔ کاربر، نه شماره) | عیب‌یابی | جدول `{prefix}topersia_log` | قابل تنظیم `log_retain_days` و `log_max_rows`؛ مقدار پیش‌فرض [سنجیده‌نشده] | خیر | C `topersia-core.php:1072-1096` · `:1387-1404` · UC `class-topersia-auth.php:114-117` · `:477` |

### ۱٫۳ خلاصه‌ساز هوش مصنوعی — آیا دادهٔ بازدیدکننده به ارائه‌دهنده می‌رود؟

**بر پایهٔ کد: نه.**
- ایجاد خلاصه فقط با مجوز ویرایش همان نوشته و نانس کار می‌کند و `wp_ajax_nopriv_tpas_ai_generate` ثبت نشده (P5-D358): AI `class-tp-ai-summary.php:23-28` · `:110-124`. پیش‌فرض `visitor_requests = 0` (`class-tp-ai-options.php:72`).
- درخواست‌های خروجی به ارائه‌دهنده (`wp_remote_post`، `class-tp-ai-api.php:185,281,389`) فقط متن نوشته و پارامتر زبان/نوع را می‌فرستند؛ در آن پرونده و `class-tp-ai-summary.php` ورودی بازدیدکننده‌ای (متن آزاد، IP، شناسه) در بدنهٔ درخواست نیست (جست‌وجوی `$_POST` و `$_SERVER` تنها پارامترهای post/lang/type و IP برای سقف نرخ را نشان داد).
- ارائه‌دهنده‌های ممکن (انتخاب مدیر): DeepSeek، OpenAI، xAI، Perplexity، Anthropic، Google (AI `class-tp-ai-options.php:324-354`). نام ارائه‌دهندهٔ **فعال** در زنده [سنجیده‌نشده] — Q6.
- هشدار: اگر روزی `visitor_requests` روشن شود، IP همچنان خام به ارائه‌دهنده نمی‌رود، ولی نیاز به بازبینی دوباره دارد.

### ۱٫۴ شکاف‌های مهم در پشتیبانی از حقوق کاربر (از روی کد)

- **هیچ مسیر «حذف حساب» برای خود کاربر در کد ما نیست** (جست‌وجوی `wp_delete_user` · `delete_user` · `delete_account` در `topersia-user-core` · `topersia-core` · `topersia-render` نتیجه‌ای نداد). پنل حساب فقط ذخیرهٔ نمایه دارد (UC `class-topersia-profile.php`).
- **هیچ برون‌بر/پاک‌کنندهٔ سفارشی برای ابزار حریم‌خصوصی وردپرس ثبت نشده** (`wp_privacy_personal_data_exporters/erasers` در کد ما نیست) — پس داده‌های سفارشی B3 · B5 · B7 تا B13 با ابزار هستهٔ وردپرس خودکار صادر یا پاک نمی‌شوند؛ حذف باید دستی به دست مدیر انجام شود.
- ~~جدول OTP شمارهٔ موبایل را متن ساده نگه می‌دارد~~ — حل شد (۱٫۱۲٫۰ · P5-D375): فقط هش HMAC و پاک‌سازی روزانهٔ ردیف منقضی. شمارهٔ حساب در `usermeta` تا حذف حساب می‌ماند (شناسهٔ ورود است و به خود کاربر نشان داده می‌شود).
- `uninstall.php` متای `_topersia_ai_daily` را پاک نمی‌کند و جدول‌های JetReviews را عمداً نگه می‌دارد (نظر کاربر پس از حذف افزونه می‌ماند).
- ورود فقط با شمارهٔ ایران (`^09[0-9]{9}$`): کاربر خارج از ایران نمی‌تواند حساب بسازد (R62).
- اعلان ایمیلی/پیامکی خارج از OTP نیست و `mail()` غیرفعال است (R40): مسیر تماس باید کانال دیگری باشد.

---

## بخش ۲ — متن سیاست حریم خصوصی

> برای انتشار هر سه نسخه باید بخش [تکمیل به دست کاربر] پر و بازبینی حقوقی انجام شود. دو بند «حذف حساب» و «صدور داده» که پیش‌تر ⚠ بودند با ۱٫۱۲٫۰ (P5-D375) خودکارند: دکمهٔ درخواست در پنل + ابزار حریم خصوصی وردپرس؛ جزئیات رفتار در بندهای حقوق کاربر هر سه نسخه.

### فارسی (fa)

# سیاست حریم خصوصی

**ناشر:** [تکمیل به دست کاربر — نام حقوقی ناشر]
**تماس:** [تکمیل به دست کاربر — نشانی ایمیل یا فرم تماس]
**آخرین به‌روزرسانی:** [تکمیل به دست کاربر]

این صفحه می‌گوید ToPersia چه اطلاعاتی را جمع می‌کند، برای چه، تا کی نگه می‌دارد و با چه کسانی به اشتراک می‌گذارد.

## چه چیزی جمع می‌کنیم
**اگر فقط صفحه‌ها را می‌خوانید (بدون حساب):** برای محدود‌کردن سوءاستفاده، از نشانی IP شما فقط یک «هش» (نشانهٔ غیرقابل‌برگشت) در حافظهٔ موقت می‌ماند، و آن هم هنگام درخواست کد ورود. ترجیح‌های نمایش (مثلاً حالت تیره، اندازهٔ متن)، نام آخرین دکمهٔ مسیریابی و سبک نقشه فقط در مرورگر خودتان ذخیره می‌شود و از آنجا نمی‌رود. شمارش بی‌نام کلیک روی دکمه‌های دستیار هوش مصنوعی انجام می‌شود، بدون IP و بدون اطلاعات مرورگر.

**اگر حساب می‌سازید:** شمارهٔ موبایل ایران، که برای ارسال کد ورود لازم است؛ نامی که خودتان می‌نویسید (نام، نام خانوادگی)، کشور، زبان و سبک سفر (اختیاری)؛ مکان‌های ذخیره‌شده و موجودیت‌هایی که دنبال می‌کنید؛ ترجیح‌های نمایش همگام‌شده؛ نظرهایی که می‌نویسید (امتیاز، عنوان، متن). نام ورود و ایمیل حساب تصادفی ساخته می‌شود و شمارهٔ شما هرگز در نام عمومی ظاهر نمی‌شود؛ نام نمایشی شما نام خودتان است یا اگر ننوشته باشید، یک نام خنثی مانند «مسافر ۴۸۲۱».

## چرا جمع می‌کنیم
برای ورود امن با کد یک‌بارمصرف، جلوگیری از سوءاستفاده و ارسال انبوه پیامک، نمایش نمایه و فهرست‌های شخصی، انتشار نظرها پس از تأیید مدیر، و حفظ ترجیح‌های نمایش شما بین دستگاه‌ها.

## چه مدت نگه می‌داریم
- کد یک‌بارمصرف: ۵ دقیقه.
- سابقهٔ تلاش ورود (شامل شماره): اگر تأیید نشود ۱ روز، اگر تأیید شود ۷ روز؛ سپس حذف خودکار.
- شمارنده‌های محدودسازی: تا یک ساعت.
- شمارهٔ موبایل حساب، نمایه، مکان‌های ذخیره‌شده، دنبال‌شده‌ها و ترجیح‌ها: تا زمانی که حساب باقی است. [تکمیل به دست کاربر — مدت نگه‌داری پس از حذف یا بی‌فعالی]
- نظرها: تا زمانی که مدیر حذف کند. [تکمیل به دست کاربر]
- گزارش‌های فنی (فقط شناسهٔ کاربر، نه شماره): [تکمیل به دست کاربر — مدت]

## با چه کسانی به اشتراک می‌گذاریم
- **سرویس پیامک Mediana**: شمارهٔ موبایل و کد، تنها برای ارسال کد ورود.
- **OpenFreeMap**: وقتی نقشهٔ تعاملی نمایش داده می‌شود، مرورگر شما مستقیماً به این سرویس درخواست می‌فرستد و نشانی IP و اطلاعات مرورگر شما را می‌بیند. اگر در دسترس نباشد، نقشهٔ ذخیره‌شده روی سرور خودمان استفاده می‌شود.
- **سرویس‌های هوا (MET Norway، Open-Meteo)**: سرور ما برای مکان‌های سایت (نه مکان شما) داده می‌گیرد؛ چیزی از شما نمی‌رود.
- **پیوندهای بیرونی** (مسیریابی در Google Maps، Waze، نشان؛ دستیارهای ChatGPT، Claude، Gemini، Copilot برای کاربران واردشده): فقط وقتی خودتان کلیک کنید به آن سایت می‌روید و قواعد آن‌ها اعمال می‌شود. [تکمیل به دست کاربر — فهرست نهایی دکمه‌های فعال]
- **خلاصه‌ساز هوش مصنوعی**: متن صفحه‌ها را مدیر سایت برای ساخت خلاصه به ارائهٔ‌دهندهٔ هوش مصنوعی می‌فرستد؛ اطلاعات بازدیدکنندگان یا کاربران به آن‌ها نمی‌رود. [تکمیل به دست کاربر — نام ارائه‌دهندهٔ فعال]
- **[تکمیل به دست کاربر — ابزار آمار (مثلاً Google Analytics) و Gravatar، در صورت فعال‌بودن]**
- اطلاعات شما فروخته نمی‌شود. (این جمله را مشاور حقوقی تأیید کند.)

## حقوق شما
شما می‌توانید نمایه و نام خود را در پنل حساب ویرایش کنید. برای دریافت نسخه‌ای از داده‌هایتان یا حذف حساب، در «تنظیمات» پنل دکمهٔ «درخواست نسخه از داده‌ها» یا «درخواست حذف حساب» را بزنید و یک بار دیگر تأیید کنید؛ درخواست برای مدیر ثبت می‌شود و پس از بررسی او اجرا می‌شود (نسخهٔ داده‌ها با دانلود؛ در حذف، حساب و داده‌هایتان پاک و نظرهایتان با نام «کاربر حذف‌شده» ناشناس می‌شود: متن می‌ماند و هر شناسه — شناسهٔ کاربر، ایمیل، IP، شماره — پاک می‌شود). برای اصلاح اطلاعات یا اگر درخواست پاسخ نگرفت، با ما تماس بگیرید: [تکمیل به دست کاربر — نشانی تماس]؛ مهلت پاسخ: [تکمیل به دست کاربر].

## کوکی و ذخیره در مرورگر
- **کوکی ورود وردپرس**: برای نگه‌داشتن نشست شما؛ با گزینهٔ «مرا به خاطر بسپار» بیشتر ماندگار است.
- **کوکی زبان و کش** (WPML و افزونه‌های وردپرس): [تکمیل به دست کاربر — نام و مدت پس از سنجش زنده].
- **ذخیره در مرورگر** (`localStorage`، `sessionStorage`): ترجیح‌های نمایش، آخرین دکمهٔ مسیریابی، سبک نقشه، وضعیت آنلاین‌بودن نقشه، و نشانهٔ دیده‌شدن جلوهٔ هوا؛ می‌توانید هر زمان از تنظیمات مرورگر پاک کنید.

## تماس
[تکمیل به دست کاربر — نام، ایمیل یا فرم تماس]

---

### English (en)

# Privacy Policy

**Publisher:** [to be completed by the owner — legal name of the publisher]
**Contact:** [to be completed by the owner — email address or contact form]
**Last updated:** [to be completed by the owner]

This page explains what information ToPersia collects, why, how long it is kept and who it is shared with.

## What we collect
**If you only read pages (no account):** to limit abuse, we keep only a hash (an irreversible token) of your IP address in temporary storage, and only when a login code is requested. Your display preferences (for example dark mode, text size), the name of the last navigation button you used and your map style are stored only in your own browser and do not leave it. We count clicks on the AI-assistant buttons anonymously, without IP address or browser information.

**If you create an account:** an Iranian mobile number, which is needed to send you a login code; the name you choose to give (first and last name), country, language and travel style (optional); places you save and entities you follow; synchronised display preferences; and reviews you write (ratings, title, text). The account's login name and email are generated randomly and your number never appears in your public name; your display name is the name you entered or, if you did not, a neutral one such as "Traveller 4821".

## Why we collect it
To sign you in securely with a one-time code, to prevent abuse and mass SMS sending, to show your profile and personal lists, to publish reviews after moderation, and to keep your display preferences across devices.

## How long we keep it
- One-time code: 5 minutes.
- Login attempt record (including the number): 1 day if not verified, 7 days if verified; then deleted automatically.
- Rate-limit counters: up to one hour.
- Account mobile number, profile, saved places, followed entities and preferences: as long as the account exists. [to be completed by the owner — retention after deletion or inactivity]
- Reviews: until an administrator removes them. [to be completed by the owner]
- Technical logs (user ID only, never the number): [to be completed by the owner — duration]

## Who we share it with
- **SMS provider Mediana**: your mobile number and the code, only to send the login code.
- **OpenFreeMap**: when the interactive map is shown, your browser contacts this service directly, and it sees your IP address and browser information. If it is unreachable, a map stored on our own server is used instead.
- **Weather services (MET Norway, Open-Meteo)**: our server requests data for places on the site (not your location); nothing about you is sent.
- **External links** (navigation in Google Maps, Waze, Neshan; ChatGPT, Claude, Gemini, Copilot assistants for signed-in users): you go to those sites only when you click, and their own rules apply. [to be completed by the owner — final list of active buttons]
- **AI summariser**: the site administrator sends page text to an AI provider to create summaries; no visitor or user information is sent to them. [to be completed by the owner — name of the active provider]
- **[to be completed by the owner — analytics tool (e.g. Google Analytics) and Gravatar, if enabled]**
- We do not sell your information. (To be confirmed by legal counsel.)

## Your rights
You can edit your profile and name in your account panel. To get a copy of your data or delete your account, use the "Request a copy of my data" or "Request account deletion" button under Settings in your account panel and confirm once more; the request is sent to an administrator and carried out after review (a copy is provided as a download; on deletion your account and data are erased and your reviews are made anonymous under the name "Deleted user": the text stays and every identifier — user ID, email, IP, phone number — is removed). To correct information, or if a request goes unanswered, contact us: [to be completed by the owner — contact address]; response time: [to be completed by the owner].

## Cookies and browser storage
- **WordPress login cookie**: keeps your session; it lasts longer if you choose "remember me".
- **Language and cache cookies** (WPML and WordPress plugins): [to be completed by the owner — names and duration after live check].
- **Browser storage** (`localStorage`, `sessionStorage`): display preferences, last navigation button, map style, map online status, and a flag that the weather effect was already shown; you can clear them at any time in your browser settings.

## Contact
[to be completed by the owner — name, email or contact form]

---

### العربية (ar)

# سياسة الخصوصية

**الناشر:** [يُستكمل من المالك — الاسم القانوني للناشر]
**للتواصل:** [يُستكمل من المالك — البريد الإلكتروني أو نموذج التواصل]
**آخر تحديث:** [يُستكمل من المالك]

توضح هذه الصفحة ما المعلومات التي يجمعها ToPersia، ولماذا، ومدة الاحتفاظ بها، ومع من تُشارَك.

## ما الذي نجمعه
**إذا كنت تتصفح الصفحات فقط (دون حساب):** للحد من إساءة الاستخدام نحتفظ فقط ببصمة مُجزّأة (hash) غير قابلة للعكس من عنوان IP الخاص بك في ذاكرة مؤقتة، وذلك فقط عند طلب رمز الدخول. تُحفَظ تفضيلات العرض (مثل الوضع الداكن وحجم النص) واسم آخر زر ملاحة استخدمته ونمط الخريطة في متصفحك أنت فقط ولا تغادره. نُحصي النقرات على أزرار المساعد الذكي دون الكشف عن الهوية، ودون عنوان IP أو معلومات المتصفح.

**إذا أنشأت حسابًا:** رقم هاتف محمول إيراني، وهو لازم لإرسال رمز الدخول؛ والاسم الذي تدخله بنفسك (الاسم والكنية)، والبلد واللغة ونمط السفر (اختياري)؛ والأماكن التي تحفظها والجهات التي تتابعها؛ وتفضيلات العرض المتزامنة؛ والتقييمات التي تكتبها (التقدير والعنوان والنص). يُنشأ اسم الدخول والبريد الإلكتروني للحساب عشوائيًا، ولا يظهر رقمك أبدًا في الاسم العام؛ اسم العرض هو الاسم الذي كتبته، أو اسم محايد مثل «مسافر 4821» إن لم تكتب اسمًا.

## لماذا نجمعه
لتسجيل دخولك بأمان برمز لمرة واحدة، ومنع إساءة الاستخدام والإرسال الجماعي للرسائل النصية، وعرض ملفك وقوائمك الشخصية، ونشر التقييمات بعد موافقة المشرف، والحفاظ على تفضيلات العرض بين أجهزتك.

## مدة الاحتفاظ
- رمز المرة الواحدة: 5 دقائق.
- سجل محاولة الدخول (ويشمل الرقم): يوم واحد إن لم يُؤكَّد، و7 أيام إن أُكِّد؛ ثم يُحذف تلقائيًا.
- عدّادات تحديد المعدل: حتى ساعة واحدة.
- رقم الهاتف والملف الشخصي والأماكن المحفوظة والمتابَعات والتفضيلات: ما دام الحساب قائمًا. [يُستكمل من المالك — مدة الاحتفاظ بعد الحذف أو الخمول]
- التقييمات: حتى يحذفها المشرف. [يُستكمل من المالك]
- السجلات التقنية (معرّف المستخدم فقط، دون الرقم): [يُستكمل من المالك — المدة]

## مع من نشاركها
- **مزوّد الرسائل النصية Mediana**: رقم الهاتف والرمز، فقط لإرسال رمز الدخول.
- **OpenFreeMap**: عند عرض الخريطة التفاعلية يتصل متصفحك مباشرة بهذه الخدمة فترى عنوان IP ومعلومات متصفحك. إن تعذّر الوصول إليها تُستخدم خريطة مخزّنة على خادمنا.
- **خدمات الطقس (MET Norway وOpen-Meteo)**: يطلب خادمنا بيانات أماكن الموقع (لا موقعك)؛ ولا يُرسَل أي شيء عنك.
- **الروابط الخارجية** (الملاحة في Google Maps وWaze ونشان؛ ومساعدو ChatGPT وClaude وGemini وCopilot للمستخدمين المسجّلين): لا تنتقل إلى تلك المواقع إلا عندما تنقر بنفسك، وتسري سياساتها. [يُستكمل من المالك — القائمة النهائية للأزرار المفعّلة]
- **ملخّص الذكاء الاصطناعي**: يرسل مشرف الموقع نص الصفحات إلى مزوّد الذكاء الاصطناعي لإنشاء الملخصات؛ ولا تُرسَل إليهم معلومات الزوار أو المستخدمين. [يُستكمل من المالك — اسم المزوّد المفعّل]
- **[يُستكمل من المالك — أداة الإحصاء (مثل Google Analytics) وGravatar إن كانا مفعّلين]**
- لا نبيع معلوماتك. (يؤكد ذلك المستشار القانوني.)

## حقوقك
يمكنك تعديل ملفك واسمك من لوحة الحساب. للحصول على نسخة من بياناتك أو حذف حسابك اضغط «طلب نسخة من بياناتي» أو «طلب حذف الحساب» ضمن «الإعدادات» في لوحة الحساب وأكِّد مرة أخرى؛ يُرسل الطلب إلى المسؤول وينفَّذ بعد مراجعته (تُقدَّم النسخة للتنزيل؛ وعند الحذف تُمحى بياناتك وحسابك وتصبح مراجعاتك مجهولة باسم «مستخدم محذوف»: يبقى النص وتُزال كل المعرّفات — معرّف المستخدم والبريد وعنوان IP ورقم الهاتف). لتصحيح المعلومات أو إذا لم يُجَب طلبك تواصل معنا: [يُستكمل من المالك — عنوان التواصل]؛ مهلة الرد: [يُستكمل من المالك].

## ملفات تعريف الارتباط والتخزين في المتصفح
- **ملف الدخول في ووردبريس**: للحفاظ على جلستك؛ ويدوم أطول إن اخترت «تذكّرني».
- **ملفات اللغة والتخزين المؤقت** (WPML وإضافات ووردبريس): [يُستكمل من المالك — الأسماء والمدة بعد الفحص على الموقع الحي].
- **التخزين في المتصفح** (`localStorage` و`sessionStorage`): تفضيلات العرض، وآخر زر ملاحة، ونمط الخريطة، وحالة اتصال الخريطة، وعلامة أن مؤثر الطقس عُرض سابقًا؛ ويمكنك مسحها في أي وقت من إعدادات المتصفح.

## التواصل
[يُستكمل من المالك — الاسم، البريد الإلكتروني أو نموذج التواصل]

---

## بخش ۳ — پرسش‌های باز برای کاربر

| # | پرسش | چرا مهم است |
|---|---|---|
| Q1 | نام حقوقی ناشر، نشانی تماس (ایمیل/فرم) و مهلت پاسخ به درخواست داده | متن سه‌زبانه بی آن‌ها ناقص است |
| Q2 | مدت نگه‌داری پس از حذف یا بی‌فعالی حساب، نظرها و لاگ فنی (`log_retain_days`، `log_max_rows`) | کد برای این‌ها مدتی تعیین نمی‌کند |
| Q3 | ~~حذف حساب و صدور داده~~ — **پاسخ داده شد و ساخته شد** (۱٫۱۲٫۰ · P5-D375): دکمهٔ درخواست در پنل (تأیید دومرحله‌ای) ← ابزار حریم خصوصی وردپرس با صدورکننده/پاک‌کنندهٔ ToPersia؛ مدیر در «ابزارها» اجرا می‌کند | — |
| Q4 | فهرست افزونه‌های نصب‌شدهٔ **زنده** و آیا Rank Math Analytics/Google Analytics وصل است؟ آیا ابزار آمار دیگری هست؟ (`wp plugin list` + نگاه به سورس صفحه) | کد این‌جا فقط `gtag` شرطی را نشان می‌دهد؛ `Site Kit` در مخزن نیست |
| Q5 | آیا آواتار (Gravatar) در زنده روشن است؟ `wp option get show_avatars` | درخواست مرورگر به شخص ثالث |
| Q6 | ارائه‌دهندهٔ AI فعال کدام است و `visitor_requests` خاموش مانده؟ | بند «اشتراک» |
| Q7 | نام و مدت کوکی‌های زندهٔ WPML/کش/وردپرس (`curl -I` و ابزار توسعهٔ مرورگر) | بند کوکی |
| Q8 | آیا سیاست‌های Mediana و OpenFreeMap (و MapTiler اگر روشن شود) نگه‌داری IP/شماره را چطور اعلام می‌کنند؟ و آیا قرارداد پردازش داده لازم است؟ | مسئولیت مشاور حقوقی |
| Q9 | ~~هش‌کردن یا کوتاه‌کردن شمارهٔ OTP~~ — **هر دو** (۱٫۱۲٫۰): HMAC ۳۲ نویسهٔ هگز + پاک‌سازی روزانهٔ ردیف منقضی | — |
| Q10 | نظر کاربر پس از حذف حساب چه شود (حذف یا ناشناس‌سازی)؟ و آیا JetReviews در حذف افزونه نگه داشته شود؟ | بند «نگه‌داری» |
| Q11 | آیا ورود فقط با شمارهٔ ایران برای مخاطب خارج از کشور در سیاست توضیح داده شود؟ (R62) | شفافیت |
| Q12 | کدام چارچوب قانونی (ایران، اتحادیهٔ اروپا، ...) روی مخاطبان اعمال می‌شود و آیا رضایت صریح/اعلان کوکی لازم است؟ | در این سند هیچ ادعای قانونی نیامده؛ فقط مشاور حقوقی می‌تواند بگوید |

---

## تغییرات لازم در کد (پیشنهاد، نه انجام‌شده)
۱) ~~مسیر «حذف حساب» + برون‌بر/پاک‌کننده~~ — انجام شد (۱٫۱۲٫۰). ۲) ~~`_topersia_ai_daily` در `uninstall.php`~~ — انجام شد (خلاصه‌ساز ۱٫۳٫۱ و پاک‌کنندهٔ user-core). ۳) ~~هش/کوتاه‌کردن OTP~~ — انجام شد. ۴) پیوند سیاست در فوتر و فرم ورود (LAUNCH L-2) — باقی.
