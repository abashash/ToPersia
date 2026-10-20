# پیش‌نویس سیاست حریم خصوصی — ToPersia

> **پیش‌نویس — پیش از انتشار باید به دست مشاور حقوقی بازبینی شود.**

- مانع: `L-2` در `docs/AUDIT-2026-10/LAUNCH.md` · مبنا: `main` `50b8dc4f` · ابری M، ۱۹ اکتبر ۲۰۲۶.
- دستهٔ ۱۹ (P5-D376 · P5-D377، ۲۰ اکتبر ۲۰۲۶): فهرست داده و متن سه‌زبانه با رفتار واقعی user-core 1.13.0 (آواتار محلی، غیرفعال‌سازی ۲۴ ماهه، OTP تأییدشده ۲۴ ساعت) به‌روز شد؛ پاسخ‌های کاربر به Q1–Q12 اعمال شد (بخش ۳). `geo/` فقط خوانده شد. `abzarwp` کنار گذاشته شد.
- دستهٔ ۲۱ (P5-D391، ۲۰ اکتبر ۲۰۲۶): ورود با کد ایمیلی (user-core 1.14.0) — ردیف‌های B18–B22 و بندهای ایمیل در هر سه متن؛ پردازندهٔ ایمیل = SMTP میزبان (cPanel)، نام و کشور میزبان `[تکمیل به دست کاربر]`.
- هر جا داده یا مدرک نیست، جای خالی `[تکمیل به دست کاربر]` مانده است. هیچ ادعای حقوقی یا قانونی بی‌شاهد ساخته نشده.
- شاهد = `پرونده:خط` نسبت به `ToPersia Claud/workspace/plugin/` (در جدول‌ها کوتاه‌شده: UC = `topersia-user-core`، AI = `topersia-ai-summarizer`، R = `topersia-render`، C = `topersia-core`). `[سنجیده‌نشده]` یعنی در کد ما نیست یا به تنظیم زندهٔ سایت بسته است.

---

## بخش ۱ — فهرست داده (از روی کد)

### ۱٫۱ آنچه از بازدیدکنندهٔ بی‌نام جمع می‌شود

| # | داده | چرا | کجا نگه داشته می‌شود | مدت | شخص ثالث؟ | شاهد |
|---|---|---|---|---|---|---|
| A1 | نشانی IP (فقط به‌صورت هش HMAC با نمک سایت) هنگام درخواست کد ورود | محدودسازی نرخ درخواست OTP و جلوگیری از سوءاستفاده | کلید ترنزینت `tp_otp_ip_<هش>` و ستون `ip_hash` جدول OTP | ترنزینت ۱ ساعت؛ ردیف جدول: تأییدنشده ≤ ۱ روز، تأییدشده ≤ ۲۴ ساعت (۱٫۱۳٫۰؛ پیشتر ۷ روز) | خیر | UC `class-topersia-auth.php:146-157` · `:197-252` · `class-topersia-db.php:36` · `:100-112` |
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
| A13 | کوکی‌های وردپرس، WPML، وودمارت، LiteSpeed (جدول کامل: بخش ۲، «کوکی و ذخیره در مرورگر») | نشست، زبان، پذیرش نوار کوکی، تنوع کش | مرورگر بازدیدکننده؛ نام و مدت از کد خودِ افزونه‌ها (فقط‌خواندنی): `pluggable.php:1082-1091` (ورود ۲ و ۱۴ روز) · `option.php:1740` (`wp-settings-*` ۱ سال) · WPML `CookieLanguage.php` (`wp-wpml_current_language` تا ۱ روز) · وودمارت `cookiesPopup.js` (`woodmart_cookies_<نسخه>` ۶۰ روز). مشاهدهٔ محلی بازدیدکنندهٔ بی‌نام: فقط `wp-wpml_current_language` (نشست). `_lscache_vary` وابسته به افزونهٔ LiteSpeed Cache [سنجیده‌نشده در زنده — Q7] | جدول بخش ۲ | خیر | `geo/` فقط‌خواندنی |
| A14 | **Google Analytics 4 از راه Site Kit** (تصمیم کاربر؛ هنوز نصب نشده — `LAUNCH.md` ه) با کوکی‌های `_ga` و `_ga_<شناسه>` (هر دو ۲ سال)؛ Google signals و شخصی‌سازی تبلیغ خاموش | آمار بازدید | سمت Google | ۱۴ ماه در GA | **بله — Google** | تصمیم کاربر؛ Rank Math Analytics استفاده نمی‌شود |
| A15 | ~~Gravatar~~ **برداشته شد** (۱٫۱۳٫۰ · P5-D377): `pre_get_avatar_data` پیش از ساخت نشانی gravatar نشانی محلی می‌دهد؛ شاهد: هیچ «gravatar» در HTML مهمان و واردشدهٔ ۷ صفحه (`tests/pagecache-witness.js`) و هیچ درخواست در `user-flows.js` | نمایش تصویر کاربر | شمایل ایستای افزونه یا عکس بارگذاری‌شده (بند B16) | — | خیر | UC `class-topersia-avatar.php` |

نبود (در مخزن): `Site Kit` — قرار است نصب شود (A14) — و `Hotjar`/`Clarity`/`Matomo`/`fbq` در پوشهٔ `geo/wp-content/plugins` و کد ما نیست (جست‌وجوی رشته؛ تنها `gtag` در Rank Math و WPML/Duplicator بالا ذکر شد). اگر در پیشخوان زنده افزونهٔ دیگری نصب شده باشد، اینجا دیده نمی‌شود — پرسش Q4.

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
| B16 | **عکس نمایه** (اختیاری): فایل WebP مربع ۲۵۶ و ۹۶ (EXIF/GPS پاک) | نمایش در پنل و نظرها | `uploads/topersia-avatars/<۲۴هگز-تصادفی>-{256,96}.webp` (بیرون از کتابخانهٔ رسانه) + `usermeta` `_topersia_avatar` (توکن و زمان) | تا حذف عکس یا حساب؛ جایگزینی فایل قبلی را پاک می‌کند | خیر | UC `class-topersia-avatar.php` |
| B17 | **زمان آخرین ورود** و پرچم «غیرفعال» | قاعدهٔ حساب بی‌فعالیت ۲۴ ماهه | `usermeta` `_topersia_last_login` · `_topersia_inactive`؛ مبنای نصب: گزینهٔ بی‌autoload `topersia_uc_inactivity_since` | تا حذف حساب | خیر | UC `class-topersia-inactivity.php` |
| B18 | **نشانی ایمیل** (ورود با کد ایمیلی، یا ایمیل دوم حساب پیامکی پس از تأیید) | ورود؛ اعلان امنیتی تغییر شناسه | `users.user_email` + `usermeta` `_topersia_email_verified` (زمان تأیید)؛ هرگز در نام ورود، نامک، نام نمایشی، نظر، REST یا JSON-LD | تا حذف حساب | خیر | UC `class-topersia-email-code.php` (`create_user` · `check`) · `class-topersia-privacy.php` (`looks_like_email`) |
| B19 | کد ایمیلی: **HMAC کد با نمک سایت** و HMAC نشانی (نه نشانی، نه کد) در ترنزینت؛ پنج حدس | تطبیق کد | ترنزینت `tp_ec_<توکن>` · `tp_ec_latest_<HMAC>` | ۶۰۰ ثانیه؛ پس از استفاده حذف | خیر | UC `class-topersia-email-code.php` · `class-topersia-auth.php` (`code_hash`) |
| B20 | شمارنده‌های نرخ ایمیل (کلید HMAC نشانی؛ IP هش‌شده) و سقف ارسال سایت | جلوگیری از بمباران ایمیل | ترنزینت `tp_ec_rl_*` · `tp_ec_ip_*` · `tp_ec_channel` · `tp_mail_cap_h/d` | ۱ ساعت تا ۱ روز | خیر | UC `class-topersia-email-code.php` (`rate`) · `class-topersia-mailer.php` (`reserve`) |
| B21 | متن نامهٔ کد (کد، زبان) به سرور SMTP میزبان | تحویل نامه | **SMTP میزبان (cPanel)** `[تکمیل به دست کاربر — نام و کشور میزبان، DPA]`؛ بی پیکسل، بی پیوند ردیابی، بی تصویر | سمت میزبان [سنجیده‌نشده] | **بله — میزبان ایمیل** | UC `class-topersia-mailer.php` · `templates/email/` |
| B22 | زمان تأیید موبایل `_topersia_mobile_verified` | قاعدهٔ ۳۰ روز میان دو تغییر شناسه | `usermeta` | تا حذف حساب | خیر | UC `class-topersia-auth.php` (`change_too_soon`) |
| B15 | ردیف لاگ فنی: زمان، سطح، پیام، شناسهٔ کاربر، نشانی صفحه (برای رویداد ورود فقط شناسهٔ کاربر، نه شماره) | عیب‌یابی | جدول `{prefix}topersia_log` | قابل تنظیم `log_retain_days` و `log_max_rows`؛ مقدار پیش‌فرض [سنجیده‌نشده] | خیر | C `topersia-core.php:1072-1096` · `:1387-1404` · UC `class-topersia-auth.php:114-117` · `:477` |

### ۱٫۳ خلاصه‌ساز هوش مصنوعی — آیا دادهٔ بازدیدکننده به ارائه‌دهنده می‌رود؟

**بر پایهٔ کد: نه.**
- ایجاد خلاصه فقط با مجوز ویرایش همان نوشته و نانس کار می‌کند و `wp_ajax_nopriv_tpas_ai_generate` ثبت نشده (P5-D358): AI `class-tp-ai-summary.php:23-28` · `:110-124`. پیش‌فرض `visitor_requests = 0` (`class-tp-ai-options.php:72`).
- درخواست‌های خروجی به ارائه‌دهنده (`wp_remote_post`، `class-tp-ai-api.php:185,281,389`) فقط متن نوشته و پارامتر زبان/نوع را می‌فرستند؛ در آن پرونده و `class-tp-ai-summary.php` ورودی بازدیدکننده‌ای (متن آزاد، IP، شناسه) در بدنهٔ درخواست نیست (جست‌وجوی `$_POST` و `$_SERVER` تنها پارامترهای post/lang/type و IP برای سقف نرخ را نشان داد).
- ارائه‌دهنده‌های پشتیبانی‌شده (همه در متن نام برده شد؛ در هر زمان یکی فعال): DeepSeek، OpenAI، xAI، Perplexity، Anthropic، Google (AI `class-tp-ai-options.php:324-354`). Q6 بسته شد.
- هشدار: اگر روزی `visitor_requests` روشن شود، IP همچنان خام به ارائه‌دهنده نمی‌رود، ولی نیاز به بازبینی دوباره دارد.

### ۱٫۴ شکاف‌های پشتیبانی از حقوق کاربر — وضعیت پس از دستهٔ ۱۹

- ~~حذف حساب برای خود کاربر~~ و ~~برون‌بر/پاک‌کنندهٔ ابزار حریم خصوصی وردپرس~~ — انجام شد (۱٫۱۲٫۰؛ پاک‌کننده اکنون فایل آواتار را هم پاک می‌کند و برون‌بر آواتار و آخرین ورود را می‌آورد).
- ~~جدول OTP شمارهٔ موبایل متن ساده~~ — هش HMAC و پاک‌سازی روزانه (۱٫۱۲٫۰)؛ ردیف تأییدشده ≤ ۲۴ ساعت (۱٫۱۳٫۰؛ هیچ محدودیت نرخی بیش از یک ساعت از جدول نمی‌خواند — همه ترنزینت).
- ~~Gravatar~~ — برداشته شد (۱٫۱۳٫۰).
- شمارهٔ حساب در `usermeta` تا حذف حساب می‌ماند (شناسهٔ ورود است). حساب بی‌فعالیت ۲۴ ماه ← غیرفعال (نه حذف).
- `uninstall.php` جدول‌های JetReviews را عمداً نگه می‌دارد (نظر کاربر پس از حذف افزونه می‌ماند).
- ورود با موبایل فقط با شمارهٔ ایران (`^09[0-9]{9}$`)؛ از ۱٫۱۴٫۰ ورود با کد ایمیلی هم هست (هر نشانی معتبر؛ ثبت‌نام فقط با ایمیل مجاز).
- ایمیل‌های سایت: فقط تراکنشی — کد ورود، «از ورود عادی استفاده کنید» (حساب دارای دسترسی)، «کسی خواست این نشانی را وصل کند»، و اعلان تغییر ایمیل/موبایل. بی بازاریابی. بی ثابت‌های SMTP (نسخهٔ محلی) هیچ نامه‌ای نمی‌رود. برون‌بر ایمیل واقعی و زمان‌های تأیید را می‌آورد؛ پاک‌کننده ترنزینت‌های ایمیل را هم پاک می‌کند. مسیر تماس `privacy@topersia.com` (صندوق باید ساخته شود).

---

## بخش ۲ — متن سیاست حریم خصوصی

> سه نسخهٔ زیر با رفتار واقعی نسخه‌های user-core 1.13.0 · summarizer 1.3.2 نوشته شد (دستهٔ ۱۹، P5-D376). جملهٔ بالای سند همچنان برقرار است: **پیش از انتشار باید به دست مشاور حقوقی بازبینی شود.** بند Google Analytics فقط پس از نصب Site Kit با تنظیم‌های `LAUNCH.md` (بخش «کارهای مالک»، ه) درست است؛ تا آن موقع آن بند را منتشر نکن.
> ناشر در این پیش‌نویس شخص است (سایت شخصی `bashash.ir`)؛ شرکت ثبت نشده. هنگام ثبت شرکت فقط سطر «ناشر» عوض می‌شود. نشانی پستی عمداً نیامده.

### فارسی (fa)

# سیاست حریم خصوصی

**ناشر:** امیرعباس بشاش (وب‌سایت شخصی bashash.ir)
**تماس:** privacy@topersia.com
**آخرین به‌روزرسانی:** ۲۰ اکتبر ۲۰۲۶

این صفحه می‌گوید ToPersia چه اطلاعاتی را جمع می‌کند، برای چه، تا کی نگه می‌دارد و با چه کسانی به اشتراک می‌گذارد.

## چه چیزی جمع می‌کنیم
**اگر فقط صفحه‌ها را می‌خوانید (بدون حساب):** برای محدود‌کردن سوءاستفاده، از نشانی IP شما فقط یک «هش» (نشانهٔ غیرقابل‌برگشت) در حافظهٔ موقت می‌ماند، و آن هم هنگام درخواست کد ورود. ترجیح‌های نمایش (مثلاً حالت تیره، اندازهٔ متن)، نام آخرین دکمهٔ مسیریابی و سبک نقشه فقط در مرورگر خودتان ذخیره می‌شود و از آنجا نمی‌رود. شمارش بی‌نام کلیک روی دکمه‌های دستیار هوش مصنوعی انجام می‌شود، بدون IP و بدون اطلاعات مرورگر. برای آمار بازدید از Google Analytics 4 استفاده می‌کنیم (بخش «با چه کسانی به اشتراک می‌گذاریم» و جدول کوکی‌ها).

**اگر حساب می‌سازید:** شمارهٔ موبایل ایران یا نشانی ایمیل (یا هر دو)، که برای ارسال کد ورود لازم است؛ نامی که خودتان می‌نویسید (نام، نام خانوادگی)، کشور، زبان و سبک سفر (اختیاری)؛ عکس نمایه (اختیاری)؛ مکان‌های ذخیره‌شده و موجودیت‌هایی که دنبال می‌کنید؛ ترجیح‌های نمایش همگام‌شده؛ نظرهایی که می‌نویسید (امتیاز، عنوان، متن)؛ و زمان آخرین ورودتان (برای بند «حساب بی‌فعالیت»). نام ورود حساب تصادفی ساخته می‌شود و شماره و ایمیل شما هرگز در نام عمومی، نظرها یا دادهٔ ساخت‌یافتهٔ صفحه ظاهر نمی‌شود؛ نام نمایشی شما نام خودتان است یا اگر ننوشته باشید، یک نام خنثی مانند «مسافر ۴۸۲۱». ورود با موبایل فعلاً فقط با شمارهٔ ایران ممکن است؛ ورود با ایمیل برای هر نشانی معتبر. می‌توانید ایمیل و موبایل هر دو را به حساب بیفزایید؛ هر کدام با کدی که به خودِ آن فرستاده می‌شود تأیید می‌شود و هر ۳۰ روز یک بار قابل تغییر است.

**ایمیل‌هایی که می‌فرستیم:** فقط ایمیل‌های لازم برای حساب — کد ورود، و هشدار امنیتی وقتی ایمیل یا موبایل حساب عوض می‌شود. هیچ ایمیل تبلیغاتی. در ایمیل‌ها پیکسل ردیابی، پیوند ردیابی‌شونده یا تصویر از جای دیگر نیست.

**عکس نمایه:** اگر بگذارید، فایل روی همین سایت ذخیره می‌شود، نه در Gravatar یا سرویس دیگر. فقط JPG، PNG یا WebP تا ۲ مگابایت پذیرفته می‌شود و به یک تصویر مربع کوچک WebP تبدیل می‌شود؛ اطلاعات پنهان عکس (مثل موقعیت جغرافیایی و مشخصات دوربین) در این تبدیل پاک می‌شود. اگر عکس نگذارید، یک شمایل ساده با رنگی که از شناسهٔ حساب مشتق می‌شود نشان داده می‌شود.

## چرا جمع می‌کنیم
برای ورود امن با کد یک‌بارمصرف، جلوگیری از سوءاستفاده و ارسال انبوه پیامک، نمایش نمایه و فهرست‌های شخصی، انتشار نظرها پس از تأیید مدیر، حفظ ترجیح‌های نمایش شما بین دستگاه‌ها، و فهمیدن اینکه کدام صفحه‌ها خوانده می‌شوند.

## چه مدت نگه می‌داریم
- کد یک‌بارمصرف: پیامک ۵ دقیقه، ایمیل ۱۰ دقیقه؛ خودِ کد فقط به‌صورت هش نگه داشته می‌شود. شمارهٔ شما در ثبت تلاش‌های ورود فقط به‌صورت هش (غیرقابل‌برگشت) می‌ماند؛ برای ورود ایمیلی سابقهٔ تلاشی در پایگاه‌داده نوشته نمی‌شود.
- سابقهٔ تلاش ورود: اگر تأیید نشود حداکثر ۱ روز؛ اگر تأیید شود حداکثر ۲۴ ساعت؛ سپس حذف خودکار.
- شمارنده‌های محدودسازی: تا یک ساعت.
- شمارهٔ موبایل حساب، نمایه، عکس، مکان‌های ذخیره‌شده، دنبال‌شده‌ها و ترجیح‌ها: تا زمانی که حساب باقی است.
- **حساب بی‌فعالیت:** اگر ۲۴ ماه وارد نشوید، حساب «غیرفعال» می‌شود، نه حذف: در جای عمومی دیده نمی‌شود (عکس و نامتان خنثی می‌شود؛ نظرهای منتشرشده با نام خنثی می‌مانند) و اعلان یا پیامکی نمی‌گیرد. با یک ورود دوباره فوراً فعال می‌شود. حذف کامل فقط با دکمهٔ «حذف حساب» در پنل یا درخواست ایمیلی شما انجام می‌شود.
- نظرها: تا زمانی که مدیر حذف کند؛ با حذف حساب، نظر ناشناس می‌شود (متن می‌ماند، هر شناسه پاک می‌شود).
- گزارش‌های فنی (فقط شناسهٔ کاربر، نه شماره): [تکمیل به دست کاربر — مدت؛ پرسش Q2]
- داده در Google Analytics: ۱۴ ماه.

## با چه کسانی به اشتراک می‌گذاریم
- **سرویس پیامک Mediana (ایران)**: شمارهٔ موبایل و کد، تنها برای ارسال کد ورود.
- **سرور ایمیل میزبان سایت** ([تکمیل به دست کاربر — نام و کشور میزبان]): نشانی ایمیل و متن نامهٔ کد یا هشدار امنیتی، تنها برای تحویل همان نامه.
- **Google Analytics 4** (از راه افزونهٔ Site Kit): آمار بازدید با کوکی‌های `_ga` و `_ga_<شناسه>`؛ داده‌ها ۱۴ ماه در Google می‌ماند. «Google signals» و شخصی‌سازی تبلیغ خاموش است.
- **OpenFreeMap**: وقتی نقشهٔ تعاملی نمایش داده می‌شود، مرورگر شما مستقیماً به این سرویس درخواست می‌فرستد و نشانی IP و اطلاعات مرورگر شما را می‌بیند. اگر در دسترس نباشد، نقشهٔ ذخیره‌شده روی سرور خودمان استفاده می‌شود.
- **سرویس‌های هوا (MET Norway، Open-Meteo)**: سرور ما برای مکان‌های سایت (نه مکان شما) داده می‌گیرد؛ چیزی از شما نمی‌رود.
- **پیوندهای بیرونی** (مسیریابی در Google Maps، Waze، نشان؛ دستیارهای ChatGPT، Claude، Gemini، Copilot برای کاربران واردشده): فقط وقتی خودتان کلیک کنید به آن سایت می‌روید و قواعد آن‌ها اعمال می‌شود.
- **خلاصه‌ساز هوش مصنوعی**: مدیر سایت متن عمومی نوشته‌ها را برای ساخت خلاصه به یکی از ارائه‌دهنده‌های پشتیبانی‌شده می‌فرستد: DeepSeek، OpenAI، xAI، Perplexity، Anthropic یا Google. در هر زمان فقط یکی فعال است. فقط متن عمومی نوشته و به دست ویرایشگر فرستاده می‌شود؛ هیچ داده‌ای از بازدیدکنندگان یا کاربران به آن‌ها نمی‌رود.
- اطلاعات شما فروخته نمی‌شود. (این جمله را مشاور حقوقی تأیید کند.)

## حقوق شما
شما می‌توانید نمایه، نام و عکس خود را در پنل حساب ویرایش یا عکس را حذف کنید. برای دریافت نسخه‌ای از داده‌هایتان یا حذف حساب، در «تنظیمات» پنل دکمهٔ «درخواست نسخه از داده‌ها» یا «درخواست حذف حساب» را بزنید و یک بار دیگر تأیید کنید؛ درخواست برای مدیر ثبت می‌شود و پس از بررسی او اجرا می‌شود (نسخهٔ داده‌ها با دانلود؛ در حذف، حساب، عکس و داده‌هایتان پاک و نظرهایتان با نام «کاربر حذف‌شده» ناشناس می‌شود: متن می‌ماند و هر شناسه — شناسهٔ کاربر، ایمیل، IP، شماره — پاک می‌شود). می‌توانید همین را با ایمیل هم بخواهید، و برای اصلاح اطلاعات یا اگر درخواست پاسخ نگرفت، به privacy@topersia.com بنویسید. به درخواست شما حداکثر ظرف ۳۰ روز پاسخ می‌دهیم؛ اگر درخواست پیچیده یا زیاد باشد، این مهلت را با اطلاع شما تمدید می‌کنیم (مادهٔ ۱۲(۳) مقررات GDPR).

## کوکی و ذخیره در مرورگر
نوار کوکی سایت به همین صفحه پیوند دارد. این‌ها کوکی‌هایی‌اند که سایت می‌گذارد:

| نام | هدف | مدت | طرف |
|---|---|---|---|
| `wordpress_logged_in_<هش>` | نگه‌داشتن نشست ورود (فقط واردشده‌ها) | ۲ روز؛ با «مرا به خاطر بسپار» ۱۴ روز | اول |
| `wordpress_sec_<هش>` | نشست امن بخش مدیریت (فقط مدیر و نویسنده) | مانند بالا | اول |
| `wordpress_test_cookie` | بررسی این‌که مرورگر کوکی می‌پذیرد (صفحهٔ ورود مدیریت) | نشست مرورگر | اول |
| `wp-settings-<شماره>` · `wp-settings-time-<شماره>` | ترجیح‌های رابط پیشخوان (فقط واردشده در بخش مدیریت) | ۱ سال | اول |
| `wp-wpml_current_language` | یادآوری زبان صفحه (WPML) | تا ۱ روز | اول |
| `woodmart_cookies_<نسخه>` | یادآوری این‌که نوار کوکی را پذیرفته‌اید | ۶۰ روز | اول |
| `_ga` | Google Analytics: تشخیص بازدیدکنندهٔ یکتا | ۲ سال | اول (کوکی)؛ داده به Google |
| `_ga_<شناسه>` | Google Analytics: وضعیت نشست | ۲ سال | اول (کوکی)؛ داده به Google |
| `_lscache_vary` | تنوع کش صفحه (فقط اگر افزونهٔ LiteSpeed Cache فعال باشد) | نشست مرورگر | اول |

ذخیره در مرورگر (`localStorage`، `sessionStorage`) فقط در مرورگر خودتان می‌ماند و جایی ارسال نمی‌شود: ترجیح‌های نمایش (کلیدهای `tp-…`)، آخرین دکمهٔ مسیریابی، سبک نقشه، وضعیت آنلاین‌بودن نقشه، و نشانهٔ دیده‌شدن جلوهٔ هوا. هر زمان می‌توانید از تنظیمات مرورگر پاک کنید.

## تماس
privacy@topersia.com

---

### English (en)

# Privacy Policy

**Publisher:** Amirabbas Bashash (personal website bashash.ir)
**Contact:** privacy@topersia.com
**Last updated:** 20 October 2026

This page explains what information ToPersia collects, why, how long it is kept and who it is shared with.

## What we collect
**If you only read pages (no account):** to limit abuse, we keep only a hash (an irreversible token) of your IP address in temporary storage, and only when a login code is requested. Your display preferences (for example dark mode, text size), the name of the last navigation button you used and your map style are stored only in your own browser and do not leave it. We count clicks on the AI-assistant buttons anonymously, without IP address or browser information. For visit statistics we use Google Analytics 4 (see "Who we share it with" and the cookie table).

**If you create an account:** an Iranian mobile number or an email address (or both), which is needed to send you a login code; the name you choose to give (first and last name), country, language and travel style (optional); a profile photo (optional); places you save and entities you follow; synchronised display preferences; reviews you write (ratings, title, text); and the time of your last login (for the "inactive account" rule below). The account's login name is generated randomly and your number and email never appear in your public name, your reviews or the page's structured data; your display name is the name you entered or, if you did not, a neutral one such as "Traveller 4821". Signing in with a mobile number currently works only with Iranian numbers; signing in with email works with any valid address. You can add both an email and a mobile number to your account; each is confirmed with a code sent to it and can be changed once every 30 days.

**Emails we send:** only emails your account needs — the login code, and a security notice when the account's email or mobile number changes. No marketing email. Our emails contain no tracking pixel, no tracked link and no remote image.

**Profile photo:** if you add one, the file is stored on this site, not on Gravatar or any other service. Only JPG, PNG or WebP up to 2 MB is accepted and it is converted into a small square WebP image; hidden photo data (such as location and camera details) is removed in the conversion. If you add no photo, a plain icon whose colour is derived from your account ID is shown.

## Why we collect it
To sign you in securely with a one-time code, to prevent abuse and mass SMS sending, to show your profile and personal lists, to publish reviews after moderation, to keep your display preferences across devices, and to understand which pages are read.

## How long we keep it
- One-time code: 5 minutes by SMS, 10 minutes by email; the code itself is kept only as a hash. In login-attempt records your number is kept only as a hash (irreversible); no attempt record is written to the database for email login.
- Login attempt record: at most 1 day if not verified; at most 24 hours if verified; then deleted automatically.
- Rate-limit counters: up to one hour.
- Account mobile number, profile, photo, saved places, followed entities and preferences: as long as the account exists.
- **Inactive accounts:** if you do not sign in for 24 months your account becomes "inactive", not deleted: it is no longer shown publicly (your photo and name become neutral; published reviews stay under a neutral name) and receives no notifications or SMS. Signing in again re-activates it immediately. Complete deletion happens only through the "delete account" button in your panel or on your request by email.
- Reviews: until an administrator removes them; when an account is deleted the review is made anonymous (the text stays, every identifier is removed).
- Technical logs (user ID only, never the number): [to be completed by the owner — duration; question Q2]
- Data in Google Analytics: 14 months.

## Who we share it with
- **SMS provider Mediana (Iran)**: your mobile number and the code, only to send the login code.
- **Our hosting provider's email server** ([to be completed by the owner — host name and country]): your email address and the text of the code or security notice, only to deliver that email.
- **Google Analytics 4** (through the Site Kit plugin): visit statistics using the cookies `_ga` and `_ga_<ID>`; data stays in Google for 14 months. Google signals and ad personalisation are off.
- **OpenFreeMap**: when the interactive map is shown, your browser contacts this service directly, and it sees your IP address and browser information. If it is unreachable, a map stored on our own server is used instead.
- **Weather services (MET Norway, Open-Meteo)**: our server requests data for places on the site (not your location); nothing about you is sent.
- **External links** (navigation in Google Maps, Waze, Neshan; ChatGPT, Claude, Gemini, Copilot assistants for signed-in users): you go to those sites only when you click, and their own rules apply.
- **AI summariser**: the site administrator sends the public text of posts to one of the supported providers to create summaries: DeepSeek, OpenAI, xAI, Perplexity, Anthropic or Google. Only one is active at a time. Only public post text is sent, and it is sent by an editor; no visitor or user data goes to them.
- We do not sell your information. (To be confirmed by legal counsel.)

## Your rights
You can edit your profile, name and photo in your account panel, and remove the photo. To get a copy of your data or delete your account, use the "Request a copy of my data" or "Request account deletion" button under Settings in your account panel and confirm once more; the request is sent to an administrator and carried out after review (a copy is provided as a download; on deletion your account, photo and data are erased and your reviews are made anonymous under the name "Deleted user": the text stays and every identifier — user ID, email, IP, phone number — is removed). You can also ask by email, and to correct information, or if a request goes unanswered, write to privacy@topersia.com. We answer within 30 days; for complex or numerous requests we may extend this period and will tell you (Article 12(3) GDPR).

## Cookies and browser storage
The site's cookie bar links to this page. These are the cookies the site sets:

| Name | Purpose | Duration | Party |
|---|---|---|---|
| `wordpress_logged_in_<hash>` | keeps your login session (signed-in users only) | 2 days; 14 days with "remember me" | first |
| `wordpress_sec_<hash>` | secure session for the admin area (admins and authors only) | as above | first |
| `wordpress_test_cookie` | checks that the browser accepts cookies (admin login page) | browser session | first |
| `wp-settings-<number>` · `wp-settings-time-<number>` | dashboard interface preferences (signed-in users in the admin area only) | 1 year | first |
| `wp-wpml_current_language` | remembers the page language (WPML) | up to 1 day | first |
| `woodmart_cookies_<version>` | remembers that you accepted the cookie bar | 60 days | first |
| `_ga` | Google Analytics: tells unique visitors apart | 2 years | first (cookie); data goes to Google |
| `_ga_<ID>` | Google Analytics: session state | 2 years | first (cookie); data goes to Google |
| `_lscache_vary` | page-cache variation (only if the LiteSpeed Cache plugin is active) | browser session | first |

Browser storage (`localStorage`, `sessionStorage`) stays in your own browser and is not sent anywhere: display preferences (`tp-…` keys), last navigation button, map style, map online status, and a flag that the weather effect was already shown. You can clear it at any time in your browser settings.

## Contact
privacy@topersia.com

---

### العربية (ar)

# سياسة الخصوصية

**الناشر:** أمير عباس بشاش (الموقع الشخصي bashash.ir)
**للتواصل:** privacy@topersia.com
**آخر تحديث:** 20 أكتوبر 2026

توضح هذه الصفحة ما المعلومات التي يجمعها ToPersia، ولماذا، ومدة الاحتفاظ بها، ومع من تُشارَك.

## ما الذي نجمعه
**إذا كنت تتصفح الصفحات فقط (دون حساب):** للحد من إساءة الاستخدام نحتفظ فقط ببصمة مُجزّأة (hash) غير قابلة للعكس من عنوان IP الخاص بك في ذاكرة مؤقتة، وذلك فقط عند طلب رمز الدخول. تُحفَظ تفضيلات العرض (مثل الوضع الداكن وحجم النص) واسم آخر زر ملاحة استخدمته ونمط الخريطة في متصفحك أنت فقط ولا تغادره. نُحصي النقرات على أزرار المساعد الذكي دون الكشف عن الهوية، ودون عنوان IP أو معلومات المتصفح. ولإحصاءات الزيارة نستخدم Google Analytics 4 (انظر «مع من نشاركها» وجدول ملفات تعريف الارتباط).

**إذا أنشأت حسابًا:** رقم هاتف محمول إيراني أو عنوان بريد إلكتروني (أو كلاهما)، وهو لازم لإرسال رمز الدخول؛ والاسم الذي تدخله بنفسك (الاسم والكنية)، والبلد واللغة ونمط السفر (اختياري)؛ وصورة الملف (اختيارية)؛ والأماكن التي تحفظها والجهات التي تتابعها؛ وتفضيلات العرض المتزامنة؛ والتقييمات التي تكتبها (التقدير والعنوان والنص)؛ ووقت آخر تسجيل دخول (لقاعدة «الحساب الخامل» أدناه). يُنشأ اسم الدخول للحساب عشوائيًا، ولا يظهر رقمك ولا بريدك أبدًا في الاسم العام أو التقييمات أو البيانات المنظمة للصفحة؛ اسم العرض هو الاسم الذي كتبته، أو اسم محايد مثل «مسافر 4821» إن لم تكتب اسمًا. تسجيل الدخول بالهاتف المحمول يعمل حاليًا بالأرقام الإيرانية فقط، وبالبريد الإلكتروني بأي عنوان صالح. يمكنك إضافة البريد والهاتف كليهما إلى حسابك؛ ويُؤكَّد كل منهما برمز يُرسل إليه، ويمكن تغيير كل منهما مرة كل 30 يومًا.

**الرسائل التي نرسلها:** فقط ما يحتاجه حسابك — رمز الدخول، وتنبيه أمني عند تغيير بريد الحساب أو هاتفه. لا رسائل تسويقية. لا تحتوي رسائلنا على بكسل تتبع أو رابط متتبَّع أو صورة من مصدر خارجي.

**صورة الملف:** إن أضفتها تُحفَظ على هذا الموقع، لا على Gravatar ولا أي خدمة أخرى. تُقبل فقط صيغ JPG وPNG وWebP حتى 2 ميغابايت، وتُحوَّل إلى صورة WebP مربعة صغيرة، وتُزال في هذا التحويل البيانات الخفية للصورة (كالموقع الجغرافي ومواصفات الكاميرا). وإن لم تضف صورة تظهر أيقونة بسيطة يُشتق لونها من معرّف حسابك.

## لماذا نجمعه
لتسجيل دخولك بأمان برمز لمرة واحدة، ومنع إساءة الاستخدام والإرسال الجماعي للرسائل النصية، وعرض ملفك وقوائمك الشخصية، ونشر التقييمات بعد موافقة المشرف، والحفاظ على تفضيلات العرض بين أجهزتك، ومعرفة الصفحات التي تُقرأ.

## مدة الاحتفاظ
- رمز المرة الواحدة: 5 دقائق بالرسائل النصية و10 دقائق بالبريد؛ ولا يُحفَظ الرمز نفسه إلا على شكل بصمة مُجزّأة. وفي سجلات محاولات الدخول يُحفَظ رقمك على شكل بصمة مُجزّأة (غير قابلة للعكس) فقط؛ ولا يُكتب سجل محاولة في قاعدة البيانات للدخول بالبريد.
- سجل محاولة الدخول: يوم واحد على الأكثر إن لم يُؤكَّد، و24 ساعة على الأكثر إن أُكِّد؛ ثم يُحذف تلقائيًا.
- عدّادات تحديد المعدل: حتى ساعة واحدة.
- رقم الهاتف والملف الشخصي والصورة والأماكن المحفوظة والمتابَعات والتفضيلات: ما دام الحساب قائمًا.
- **الحساب الخامل:** إن لم تسجّل دخولك 24 شهرًا يصبح حسابك «خاملًا» لا محذوفًا: لا يظهر علنًا (تصبح صورتك واسمك محايدين وتبقى التقييمات المنشورة باسم محايد) ولا يتلقى إشعارات أو رسائل نصية. ويعود نشطًا فورًا بتسجيل دخول جديد. ولا يحدث الحذف الكامل إلا بزر «حذف الحساب» في لوحتك أو بطلبك عبر البريد الإلكتروني.
- التقييمات: حتى يحذفها المشرف؛ وعند حذف الحساب يصبح التقييم مجهولًا (يبقى النص وتُزال كل المعرّفات).
- السجلات التقنية (معرّف المستخدم فقط، دون الرقم): [يُستكمل من المالك — المدة؛ السؤال Q2]
- البيانات في Google Analytics: 14 شهرًا.

## مع من نشاركها
- **مزوّد الرسائل النصية Mediana (إيران)**: رقم الهاتف والرمز، فقط لإرسال رمز الدخول.
- **خادم البريد لدى مزوّد استضافة الموقع** ([يُستكمل من المالك — اسم المزوّد وبلده]): عنوان بريدك ونص رسالة الرمز أو التنبيه الأمني، فقط لتسليم تلك الرسالة.
- **Google Analytics 4** (عبر إضافة Site Kit): إحصاءات الزيارة باستخدام ملفي `_ga` و`_ga_<المعرّف>`؛ وتبقى البيانات في Google مدة 14 شهرًا. إشارات Google (Google signals) وتخصيص الإعلانات متوقفان.
- **OpenFreeMap**: عند عرض الخريطة التفاعلية يتصل متصفحك مباشرة بهذه الخدمة فترى عنوان IP ومعلومات متصفحك. إن تعذّر الوصول إليها تُستخدم خريطة مخزّنة على خادمنا.
- **خدمات الطقس (MET Norway وOpen-Meteo)**: يطلب خادمنا بيانات أماكن الموقع (لا موقعك)؛ ولا يُرسَل أي شيء عنك.
- **الروابط الخارجية** (الملاحة في Google Maps وWaze ونشان؛ ومساعدو ChatGPT وClaude وGemini وCopilot للمستخدمين المسجّلين): لا تنتقل إلى تلك المواقع إلا عندما تنقر بنفسك، وتسري سياساتها.
- **ملخّص الذكاء الاصطناعي**: يرسل مشرف الموقع النص العام للمقالات إلى أحد المزوّدين المدعومين لإنشاء الملخصات: DeepSeek أو OpenAI أو xAI أو Perplexity أو Anthropic أو Google. يعمل مزوّد واحد فقط في كل وقت. يُرسَل النص العام للمقال فقط، ويرسله محرّر؛ ولا تُرسَل إليهم أي بيانات عن الزوار أو المستخدمين.
- لا نبيع معلوماتك. (يؤكد ذلك المستشار القانوني.)

## حقوقك
يمكنك تعديل ملفك واسمك وصورتك من لوحة الحساب، وحذف الصورة. للحصول على نسخة من بياناتك أو حذف حسابك اضغط «طلب نسخة من بياناتي» أو «طلب حذف الحساب» ضمن «الإعدادات» في لوحة الحساب وأكِّد مرة أخرى؛ يُرسل الطلب إلى المسؤول وينفَّذ بعد مراجعته (تُقدَّم النسخة للتنزيل؛ وعند الحذف تُمحى بياناتك وحسابك وصورتك وتصبح مراجعاتك مجهولة باسم «مستخدم محذوف»: يبقى النص وتُزال كل المعرّفات — معرّف المستخدم والبريد وعنوان IP ورقم الهاتف). يمكنك أيضًا طلب ذلك بالبريد الإلكتروني، ولتصحيح المعلومات أو إذا لم يُجَب طلبك راسلنا على privacy@topersia.com. نرد خلال 30 يومًا؛ وإذا كان الطلب معقدًا أو كثيرًا فقد نمدّد المهلة ونُعلمك بذلك (المادة 12(3) من اللائحة العامة لحماية البيانات GDPR).

## ملفات تعريف الارتباط والتخزين في المتصفح
يرتبط شريط ملفات تعريف الارتباط في الموقع بهذه الصفحة. هذه هي الملفات التي يضعها الموقع:

| الاسم | الغرض | المدة | الجهة |
|---|---|---|---|
| `wordpress_logged_in_<بصمة>` | الحفاظ على جلسة الدخول (للمسجّلين فقط) | يومان؛ و14 يومًا مع «تذكّرني» | الموقع |
| `wordpress_sec_<بصمة>` | جلسة آمنة للوحة الإدارة (للمدير والكاتب فقط) | كما سبق | الموقع |
| `wordpress_test_cookie` | التحقق من أن المتصفح يقبل الملفات (صفحة دخول الإدارة) | جلسة المتصفح | الموقع |
| `wp-settings-<رقم>` · `wp-settings-time-<رقم>` | تفضيلات واجهة لوحة التحكم (للمسجّلين في الإدارة فقط) | سنة واحدة | الموقع |
| `wp-wpml_current_language` | تذكّر لغة الصفحة (WPML) | حتى يوم واحد | الموقع |
| `woodmart_cookies_<الإصدار>` | تذكّر أنك قبلت شريط الملفات | 60 يومًا | الموقع |
| `_ga` | Google Analytics: التمييز بين الزوار | سنتان | الموقع (الملف)؛ والبيانات تذهب إلى Google |
| `_ga_<المعرّف>` | Google Analytics: حالة الجلسة | سنتان | الموقع (الملف)؛ والبيانات تذهب إلى Google |
| `_lscache_vary` | تنويع التخزين المؤقت للصفحة (فقط إن كانت إضافة LiteSpeed Cache مفعّلة) | جلسة المتصفح | الموقع |

يبقى التخزين في المتصفح (`localStorage` و`sessionStorage`) في متصفحك فقط ولا يُرسل إلى أي جهة: تفضيلات العرض (مفاتيح `tp-…`)، وآخر زر ملاحة، ونمط الخريطة، وحالة اتصال الخريطة، وعلامة أن مؤثر الطقس عُرض سابقًا. ويمكنك مسحه في أي وقت من إعدادات المتصفح.

## التواصل
privacy@topersia.com

---

## بخش ۳ — پرسش‌های باز برای کاربر

پس از دستهٔ ۱۹ (P5-D376) پاسخ‌های کاربر در متن بالا اعمال شد. آنچه بسته شد خط‌خورده است؛ باقی‌مانده‌ها باز.

| # | پرسش | وضعیت |
|---|---|---|
| Q1 | ناشر، تماس، مهلت پاسخ | ~~بسته~~ — امیرعباس بشاش (سایت شخصی `bashash.ir`، شرکت ثبت نشده؛ هنگام ثبت شرکت فقط سطر ناشر عوض می‌شود) · `privacy@topersia.com` · ۳۰ روز تمدیدپذیر با اطلاع (GDPR مادهٔ ۱۲(۳)). **پیش‌نیاز زنده:** صندوق `privacy@topersia.com` باید ساخته شود (`LAUNCH.md` ه) |
| Q2 | مدت نگه‌داری **لاگ فنی** (`log_retain_days` · `log_max_rows`) و نظرها | **باز برای لاگ فنی.** حساب بی‌فعالیت: ۲۴ ماه ← غیرفعال (نه حذف)؛ نظر: تا حذف مدیر و با حذف حساب ناشناس (بسته) |
| Q3 | حذف حساب و صدور داده | ~~بسته~~ (دستهٔ ۱۷) |
| Q4 | افزونه‌های نصب‌شدهٔ زنده و ابزار آمار | **تصمیم:** Google Analytics 4 از راه Site Kit (نصب نشده؛ `LAUNCH.md` ه). Rank Math Analytics نه. **باز:** `wp plugin list` زنده برای اطمینان از نبود ابزار دیگر |
| Q5 | Gravatar | ~~بسته~~ — حذف شد؛ آواتار محلی (دستهٔ ۱۹). `show_avatars` روشن می‌ماند |
| Q6 | ارائه‌دهندهٔ AI | ~~بسته~~ — هر شش ارائه‌دهنده نام برده شد، یکی در هر زمان، فقط متن عمومی، به دست ویرایشگر |
| Q7 | نام و مدت کوکی‌های زنده | **بخش بسته، بخش باز:** جدول از روی کد ساخته شد (وردپرس · WPML · وودمارت · LiteSpeed · GA). **باز:** سنجش یک‌باره روی زنده با ابزار توسعهٔ مرورگر (به‌ویژه وجود `_lscache_vary` و اینکه `wp-wpml_current_language` در زنده چند روز می‌ماند — محلی نشست دیده شد) |
| Q8 | سیاست‌های Mediana و OpenFreeMap و Google دربارهٔ نگه‌داری؛ نیاز به قرارداد پردازش داده | **باز** — مشاور حقوقی |
| Q9 | هش OTP | ~~بسته~~ |
| Q10 | نظر پس از حذف حساب؛ JetReviews در حذف افزونه | ~~بسته~~ — ناشناس‌سازی؛ جدول‌های JetReviews در حذف افزونه می‌مانند (عمدی) |
| Q11 | ورود فقط با شمارهٔ ایران | ~~بسته~~ — در متن صریح نوشته شد؛ از ورود ایمیلی چیزی نیامده تا ساخته شود |
| Q12 | چارچوب قانونی روی مخاطبان (ایران، اتحادیهٔ اروپا، ...) و رضایت/اعلان کوکی | **باز** — مشاور حقوقی. متن فقط به GDPR مادهٔ ۱۲(۳) اشاره دارد (بنا به خواست کاربر) |
| Q13 | **تازه:** نوار کوکی وودمارت فقط «پذیرفتن» دارد (دکمهٔ رد ندارد)، و Site Kit کوکی‌های GA را پیش از پذیرش می‌گذارد | **باز** — مشاور حقوقی باید بگوید آیا برای مخاطبان هدف باید GA تا پذیرش بسته بماند |
| Q14 | **تازه:** بند «بی‌فعالیت ۲۴ ماه» به‌جز پنهان‌شدن در جای عمومی هیچ اعلان یا پیامکی ندارد (در کد نیست) | آگاهی: اگر روزی اعلان ساخته شود باید از `ToPersia_Inactivity::can_notify()` بگذرد |

---

## تغییرات لازم در کد

۱) ~~مسیر «حذف حساب» + برون‌بر/پاک‌کننده~~ — انجام شد (۱٫۱۲٫۰). ۲) ~~`_topersia_ai_daily` در `uninstall.php`~~ — انجام شد. ۳) ~~هش/کوتاه‌کردن OTP~~ — انجام شد. ۴) ~~Gravatar و آواتار محلی~~ — انجام شد (۱٫۱۳٫۰ · P5-D377). ۵) ~~غیرفعال‌سازی ۲۴ ماهه~~ — انجام شد (۱٫۱۳٫۰). ۶) پیوند سیاست در فوتر، فرم ورود و نوار کوکی (LAUNCH L-2 / ه) — باقی، کار مالک روی زنده.
