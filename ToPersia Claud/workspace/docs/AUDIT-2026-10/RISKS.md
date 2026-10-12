# RISKS — ممیزی ۲د: ثبت ریسک

> ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ · فقط‌خواندنی (P5-D287). هیچ کلید، رمز، نام مدیر یا دادۀ کاربر در این سند نیست؛ فقط نام و شمار.
> ساختار: (الف) ۳۵ ریسک سند AI Core §۸۲ با همان شماره · (ب) ریسک‌های ما، شمارۀ `S01…` · (پ) دو افزودۀ بخش ۲د: **S30** افزونه‌های تجاری از توزیع‌کنندۀ واسط · **S31** نام کاربری مدیر در کد.
> برای هر ریسک: **شدت** (بحرانی · بالا · متوسط · پایین) · **احتمال** (بالا · متوسط · پایین · نامعلوم) · **کاهش** (آنچه هست + آنچه باید) · **تشخیص** (چطور بفهمیم) · **وضعیت** (باز · نیمه‌کاهش‌یافته · کاهش‌یافته · موکول (ساخته‌نشده) · UNKNOWN). سند AI Core فیلد مالک را هم می‌خواهد؛ مالک از §۵ ARCH-RECONCILIATION (P5-D289) آمده.
> برچسب شاهد: **VERIFIED** (مسیر + خط) · **INFERRED** · **ASSUMED** · **UNKNOWN**. مسیرها نسبت به `ToPersia Claud/workspace/`؛ `ریشه:` = ریشۀ مخزن.

## الف — ۳۵ ریسک سند AI Core (§۸۲)

| # | ریسک | شدت | احتمال | کاهش (هست ← باید) | تشخیص | وضعیت · مالک | شاهد |
|---|---|---|---|---|---|---|---|
| ۱ | واقعیت تاریخی ساختگی (hallucination) | بالا | متوسط | هست: پرامت سیستم «فقط از متن داده‌شده» + برچسب سلب مسئولیت. باید: فقط‌خواندن منبع با ارجاع، بررسی خروجی با فیلدهای منبع | نمونه‌گیری سردبیر از ردیف‌های `topersia_ai_summaries`؛ گزارش کاربر | نیمه‌کاهش‌یافته · AI | `plugin/topersia-ai-summarizer/includes/class-tp-ai-localize.php:402-420` · `class-tp-ai-render.php:323` — **VERIFIED**؛ نبود اعتبارسنجی خروجی **VERIFIED** (AI.md ۱۱) |
| ۲ | دادۀ سفر کهنه | بالا | بالا | هست: `content_status` · `last_verified_date`؛ هش محتوا خروجی AI را «کهنه» می‌کند. باید: رندر تاریخ رویداد، یادآور بازبینی (`annual_review_month`)، پایش منبع | فیلد `last_verified_date` قدیمی؛ گزارش «کهنه» ناشی از هش | نیمه‌کاهش‌یافته · core/render | `Jetengine_Skin.json meta-1` · `topersia-core/includes/topersia-cache.php:501-504` — **VERIFIED**؛ تاریخ رویداد رندر نمی‌شود (EVENTS.md ۴) |
| ۳ | قطع ارائه‌دهنده | متوسط | متوسط | هست: خطای ساده به کاربر، موتور داخلی اگر API نباشد. باید: fallback میان موتورها، مدار شکسته | نرخ خطای `http_5xx` در تلهمتری | باز · AI | `class-tp-ai-api.php:173-196` · `class-tp-ai-summary.php:155` — **VERIFIED**؛ بی fallback **VERIFIED** |
| ۴ | رانش رفتار مدل | متوسط | بالا | هست: دما ۰٫۳، خروجی ذخیره می‌شود. باید: نسخۀ مدل/پرامت در شناسۀ کش + مجموعۀ ارزیاب | مقایسۀ نمونه‌های هفتگی؛ بنچمارک | باز · AI | `class-tp-ai-options.php:63`؛ مدل در کلید نیست (AI.md ۳) — **VERIFIED** |
| ۵ | تغییر قیمت ارائه‌دهنده | متوسط | متوسط | هست: سقف‌ها (بی ثبت هزینه). باید: `estimated_cost` در `runs` | داشبورد هزینه | باز · AI | AI.md ۱۰ — **VERIFIED** |
| ۶ | هزینۀ بی‌کنترل | بالا | متوسط | هست: نرخ ساعتی (۱۰)، سقف روزانه (۵ کاربر / ۱۰۰ سایت)، فقط کاربر واردشده. باید: یک سقف، بستن دورزدن خروجی کهنه، سقف ماهانۀ سخت‌افزاری ارائه‌دهنده | شمار روزانۀ `tp-ai-site-{day}`؛ صورتحساب | نیمه‌کاهش‌یافته · AI/render | `topersia-g-ai.php:142-158` · `topersia-settings.php:55-56` · `class-tp-ai-summary.php:127` — **VERIFIED** (دورزدن) |
| ۷ | تزریق پرامت | متوسط | پایین | هست: دستور سیستم؛ ورودی فقط محتوای ویراستار (نه نظر کاربر). باید: مرزبندی متن منبع، حذف دستورهای درون محتوا، ابزار ممنوع | آزمون‌های خصمانه §۸۱ سند | نیمه‌کاهش‌یافته · AI | `class-tp-ai-summary.php:167` — **VERIFIED** (چسباندن ساده) |
| ۸ | افشای کلید API | بحرانی | متوسط | هست: سمت سرور، سرآیند (نه URL) برای Gemini؛ گارد git برای مقدار کلید. باید: رمزنگاری کلید AI و پیامک (sodium)، فیلد فقط‌نوشتنی پیامک، حذف کلید از فیلتر | گارد commit؛ `grep` HTML پیشخوان؛ آزمون `core-local.php` با کلید ساختگی | **باز** (دو نشت) · AI + user-core | کلید AI متن‌ساده `class-tp-ai-options.php:553-567` و فیلتر `class-tp-ai-api.php:144`؛ کلید پیامک متن‌ساده و چاپ‌شده در HTML پیشخوان `class-topersia-admin.php:534` — **VERIFIED** |
| ۹ | SSRF | پایین | پایین | هست: فقط مدیر نشانی پایه را تعیین می‌کند. باید: `wp_safe_remote_post` یا فهرست مجاز میزبان، رد IP خصوصی | لاگ `http-probe` محلی | نیمه‌کاهش‌یافته · AI | `class-tp-ai-api.php:173,269,377` (`wp_remote_post`) · `class-tp-ai-admin.php:850-854` — **VERIFIED** |
| ۱۰ | نشت اطلاعات شخصی (PII) | بالا | پایین | هست: AI فقط محتوا می‌خورد؛ `ip_hash` با HMAC؛ پاک‌سازی OTP. باید: اطمینان از اجرای کرون؛ حذف شمارۀ موبایل از لاگ‌ها | بازرسی جدول OTP و `topersia_log` | نیمه‌کاهش‌یافته · user-core/AI | `class-topersia-auth.php:157` · `class-topersia-db.php:94-107` — **VERIFIED**؛ اجرای کرون زنده **UNKNOWN** |
| ۱۱ | نشت مکان کاربر | متوسط | پایین | هست: هیچ دریافت مختصات کاربر نیست؛ مختصات REST مربوط به موجودیت است. باید: «اطراف من» با POST (حکم معمار) و بی ثبت مختصات در لاگ | لاگ دسترسی LiteSpeed (جست‌وجوی `lat=`) | موکول (ساخته‌نشده) · core | `topersia-g-mappoints.php` (فقط `context`) — **VERIFIED**؛ بی کد «نزدیک من» **VERIFIED** (MAP.md ۹) |
| ۱۲ | کار تکراری (duplicate jobs) | متوسط | متوسط | هست: ادغام کار AS برای گالری. باید: قفل پرواز تکی در `topersia_cache_remember` برای خروجی گران | دو ردیف هزینۀ هم‌زمان برای یک پست | باز · AI/core | `topersia-cache.php:405-440` (بی قفل) · `topersia-queue.php:53-56` (ادغام فقط AS) — **VERIFIED** |
| ۱۳ | انباشت صف | متوسط | نامعلوم | هست: ادغام کارها؛ سقف تأخیر پله‌ای. باید: پایش وضعیت `failed/pending`، هشدار | `wp action-scheduler status` | UNKNOWN · core | `topersia-queue.php:41-56` — **VERIFIED**؛ اجرای صف زنده **UNKNOWN** |
| ۱۴ | رقابت اعتبار | — | — | اعتبار ساخته نشده (P5-D293 تعویق)؛ ولی شمارش سقف روزانه اتمی نیست (`get_transient` ثم `set_transient`) | — | موکول (ساخته‌نشده) · AI | `topersia-g-ai.php:95-108` — **VERIFIED** |
| ۱۵ | خروجی ساخت‌یافتۀ ناقص | پایین | پایین | خروجی متن آزاد است؛ هیچ JSON ساخت‌یافته‌ای پذیرفته نمی‌شود | — | موکول (ساخته‌نشده) · AI | `class-tp-ai-api.php:198-216` — **VERIFIED** |
| ۱۶ | ارجاع نامعتبر به موجودیت | متوسط | پایین | AI ارجاع موجودیت تولید نمی‌کند؛ موتور داخلی از رابطه‌ها می‌خواند و `published_ids` می‌سنجد | آزمون ارجاع | موکول (ساخته‌نشده) · AI | `class-tp-ai-internal.php:172-216` · `class-tp-ai-context.php:409-426` — **VERIFIED** |
| ۱۷ | مختصات نادرست | بالا | بالا | هست: اعتبارسنج مرکزی، رد بیرون از کادر ایران. باید: کادر دقیق‌تر از مرز، گزارش کیفیت دوره‌ای | `wp topersia geo-index report`؛ `GEO-QUALITY-2F.json` | نیمه‌کاهش‌یافته · core | `topersia-geo.php:23-26,114-224` — **VERIFIED**؛ محلی: ۵۹ معتبر از ۱۸۳ پست، ۱ ناخوانا، ۱۲۳ غایب (`docs/GEO-BENCH-2F.md`) — **INFERRED** |
| ۱۸ | ناهمخوانی ترجمه | متوسط | بالا | هست: `language` در کلید خلاصه، دروازۀ «دادۀ به زبان دیگر هم نه»، WPML ignore در 1.13. باید: هماهنگی پیکربندی WPML زنده (1.12 copy) با محلی | گزارش شکاف ترجمه `wp topersia i18n-gaps` | نیمه‌کاهش‌یافته · core/render | `topersia-i18n-gaps.php:378` · `CLAUDE.md` (P5-D308) — **VERIFIED** |
| ۱۹ | رشد حجم صدا | پایین | — | صدا ساخته نشده | حجم `uploads/` | موکول (ساخته‌نشده) · AI | AI.md ۹ — **VERIFIED** |
| ۲۰ | تغییر ارائه‌دهنده TTS | متوسط | متوسط | آداپتور دو ارائه‌دهنده + شکاف دوم برای `ai.tts` | آزمون اتصال | موکول (ساخته‌نشده) · core | `topersia-services.php:87-88` — **VERIFIED** |
| ۲۱ | رانش اسکیمای JetEngine | بالا | بالا | هست: شناسه‌های رابطه در `topersia_schema_map()` (عددی) + `schema-local.php`. باید: کلید نمادین به‌جای شناسۀ عددی؛ export تازه (۱۳۵ در برابر ۱۳۹ رابطه) | `php tests/schema-local.php` | باز · schema/core | `topersia-schema.php:334-336` (`$via_prov(232)`) — **VERIFIED**؛ فاصلۀ export و محلی (`docs/AUDIT-RELATIONS-2F.md`) — **VERIFIED** |
| ۲۲ | رانش WPML | بالا | متوسط | هست: پیکربندی 1.13 محلی در برابر 1.12 زنده؛ قلاب `icl_pro_translation_completed` در هش. باید: هم‌ترازی پیش از انتقال | آزمون `wpml-editor-local.js` | باز · core | `topersia-cache.php:223` · `CLAUDE.md` — **VERIFIED** |
| ۲۳ | تداخل افزونه | متوسط | متوسط | هست: Action Scheduler دوگانه با نسخۀ یکسان؛ نگهبان کلاس. باید: پایش ارتقای Rank Math به AS 4.x | `wp eval ActionScheduler_Versions::instance()->latest_version()` | نیمه‌کاهش‌یافته · core | SYSTEM.md ۳؛ `topersia-queue.php:9-14` — **VERIFIED** |
| ۲۴ | XSS از خروجی AI | بالا | پایین | هست: `wp_strip_all_tags` ← `esc_html` ← زیرمجموعۀ مارک‌داون ← `wp_kses` چهار برچسب | آزمون خصمانه | **کاهش‌یافته** · AI | `class-tp-ai-render.php:433-495` — **VERIFIED** |
| ۲۵ | موارد مثبت کاذب نظارت | پایین | — | نظارت خودکار AI نیست؛ نظرها همیشه «در انتظار تأیید» | — | موکول (ساخته‌نشده) · user-core | `class-topersia-review-form.php:7-22` — **VERIFIED** |
| ۲۶ | مهلت هاست اشتراکی | متوسط | پایین | هست: `max_execution_time 600` در `.htaccess` زنده (کهنه‌پذیر)، تولید همزمان ۶۰ ثانیه. باید: پشت صف + polling | زمان پاسخ `tpas_ai_generate` | نیمه‌کاهش‌یافته · AI | `ریشه:.htaccess:53` — **INFERRED**؛ `class-tp-ai-api.php:163` — **VERIFIED** |
| ۲۷ | قفل‌شدن به ارائه‌دهنده | پایین | پایین | هشت پیش‌تنظیم و سه پروتکل؛ `custom` OpenAI-سازگار | — | کاهش‌یافته · AI | `class-tp-ai-options.php:300-349` — **VERIFIED** |
| ۲۸ | کش کهنه | متوسط | بالا | هست: هش محتوا؛ AI.md ۳. باید: `post_content` در هش برای نوع‌های غیر `tp_*`؛ نسخۀ پرامت/مدل | بررسی `is_stale` در نمونه | نیمه‌کاهش‌یافته · core/AI | `topersia-cache.php:76-107` — **VERIFIED** |
| ۲۹ | محتوای SEO تکراری | متوسط | متوسط | هست: خلاصۀ ذخیره‌شده در صفحه برای مهمان هم نمایش می‌یابد. باید: بررسی ایندکس‌شدن، `noindex` برای صفحه‌های ترکیبی آینده (Compare) | Search Console | UNKNOWN · render | `topersia-g-zones.php:4568-4624` — **VERIFIED** (نمایش)؛ ایندکس **UNKNOWN** |
| ۳۰ | اعتماد بیش‌ازحد ویراستار به AI | متوسط | متوسط | هست: خروجی AI در جدول جدا از `ai_summary` سردبیری؛ دستور «ai_summary را ننویس» | — | کاهش‌یافته · AI | `docs/ai-integration-notes.md` — **VERIFIED** |
| ۳۱ | محدودیت مجوز دادۀ بیرونی | بالا | متوسط | هست: پرچم `topersia_site_is_commercial` که سرویس‌های غیرتجاری را خاموش می‌کند؛ نشانۀ MET/CAMS. باید: ثبت ToS هر منبع، Open-Meteo تجاری | صفحۀ سرویس‌ها: هشدار | نیمه‌کاهش‌یافته · core | `topersia-services.php:39-42,86-97` — **VERIFIED** |
| ۳۲ | قالب‌های منبع ناپشتیبانی | پایین | — | دریافت خبر ساخته نشده | — | موکول (ساخته‌نشده) · core | EVENTS.md ۶ — **VERIFIED** |
| ۳۳ | نگهداری ناخواستۀ اطلاعات | بالا | متوسط | هست: پاک‌سازی OTP/لاگ با کرون. باید: اطمینان از کرون زنده؛ `uninstall.php` AI/هسته | شمار ردیف‌های قدیمی OTP | UNKNOWN · user-core/core | `class-topersia-db.php:94-107` · `topersia-core.php:1837-1840` — **VERIFIED**؛ کرون زنده **UNKNOWN** |
| ۳۴ | بهینه‌سازی نادرست مسیر | متوسط | — | مسیر ساخته نشده | — | موکول (ساخته‌نشده) · AI | FEATURE-MAP.md ۳۱ — **VERIFIED** |
| ۳۵ | اشتباه کاربر میان پیشنهاد AI و واقعیت | بالا | متوسط | هست: «سلب مسئولیت»، برچسب منبع، `needs_verification`. باید: برچسب بصری دائمی و غیرقابل‌پنهان | آزمون کاربرپذیری | نیمه‌کاهش‌یافته · render/AI | `class-tp-ai-render.php:323` — **VERIFIED** |

## ب — ریسک‌های ما (S01…S29)

| # | ریسک | شدت | احتمال | کاهش | تشخیص | وضعیت · مالک | شاهد |
|---|---|---|---|---|---|---|---|
| S01 | دکمه‌های ۷ دستیار بیرونی پیش‌فرض روشن (مغایر P5-D255) | متوسط | نامعلوم | باید: `position=manual`، `services=[]` در تنظیم زنده؛ یا حذف دکمه‌ها | `wp eval` بخش ۱ AI.md | UNKNOWN · AI | `class-tp-ai-options.php:42,44` · `class-tp-ai-frontend.php:109-110` — **VERIFIED** |
| S02 | کلید پیامک Mediana در HTML پیشخوان و متن‌ساده | بالا | متوسط | باید: فیلد فقط‌نوشتنی + رمزنگاری | ذخیرۀ HTML صفحۀ تنظیمات | باز · user-core | `class-topersia-admin.php:534` — **VERIFIED** |
| S03 | کلیدهای AI در `tpas_settings` با autoload=yes | بالا | متوسط | باید: autoload خاموش، رمزنگاری، ثابت wp-config | `wp option get tpas_settings --format=json \| wc -c` | باز · AI | `topersia-ai-summarizer.php:104-105` — **VERIFIED** |
| S04 | قلم Italic بی گلیف فارسی برای برچسب آب/جزیره/رود + `letter-spacing` | بالا | بالا | باید: جایگزینی `text-font` یا بازه‌های کامل؛ حذف letter-spacing برای fa/ar | بازرسی چشمی «خلیج فارس» | باز · render | MAP.md ۶ — **VERIFIED** (اجرای basemaps + اندازۀ فایل) |
| S05 | `/map-points`: سقف ثابت ۴۰۰ و حذف خانوادۀ آخر در استان بزرگ | متوسط | بالا | باید: قرارداد با `meta`، سقف بر پایۀ viewport/تعداد | شمار `features` استان تهران | باز · render | `topersia-g-mappoints.php:80-90` — **VERIFIED** |
| S06 | نمایۀ مکانی و جدول کش فقط محلی؛ نصب‌شده بی فایل‌های جدید (R44) | بالا | بالا | باید: ممیزی پیوست الف، همگام‌سازی برنامه‌ریزی‌شده | `diff -rq` (SYSTEM.md ۱۱) | باز · core/render | `topersia-geo-index.php:26-28` · SYSTEM.md ۱۱ — **VERIFIED** |
| S07 | رویداد: فیلد تاریخ هست ولی رندر/JSON-LD ندارد؛ فرض «داده ماشین‌خوان نیست» کهنه | متوسط | بالا | باید: بازنگری `topersia-schema.php:255-257` پس از پر شدن داده | EVENTS.md ۷ | باز · render/schema | `topersia-schema.php:255-257` · `AUDIT-META-2F.csv` — **VERIFIED**/**INFERRED** |
| S08 | نرخ‌سنج OTP بر اساس `REMOTE_ADDR` پشت پراکسی/LiteSpeed | بالا | نامعلوم | باید: تعریف `TOPERSIA_TRUST_PROXY` با هدر درست | `grep -c TOPERSIA_TRUST_PROXY wp-config.php`؛ ۴۲۹ در لاگ | UNKNOWN · user-core | `class-topersia-auth.php:145-157` — **VERIFIED** |
| S09 | کرون: `DISABLE_WP_CRON` زنده؛ دو کرون وردپرس + AS بی runner تأییدشده | بالا | نامعلوم | باید: کرون سیستمی cPanel با `wp-cron.php` یا AS runner | `wp cron event list`، `crontab -l` | UNKNOWN · core | `topersia-queue.php:14-16` — **VERIFIED** (اعلام) |
| S10 | `mb_strtolower` بی گارد در موتور داخلی (میزبان بی mbstring) | متوسط | نامعلوم | باید: `function_exists` یا `preg` یونیکد | `php -m \| grep mbstring` | UNKNOWN · AI | `class-tp-ai-internal.php:346` — **VERIFIED** |
| S11 | `log_errors Off` و `display_errors Off` در PHP زنده | بالا | بالا | باید: لاگ PHP روشن (فایل خصوصی) یا تکیه به `topersia_log` | `php -i \| grep log_errors` | باز · میزبان/core | `ریشه:.htaccess:50-52` — **INFERRED** |
| S12 | mu-pluginهای `tp-local-*` در مخزن (خطر انتقال به زنده) | متوسط | پایین | هست: همه گیت «local» دارند؛ باید: `.gitignore` یا جداسازی از `geo/` | `diff` درخت زنده | کاهش‌یافته · core | `geo/wp-content/mu-plugins/tp-local-*.php` — **VERIFIED** |
| S13 | `core` و `render` بی `uninstall.php`؛ AI جدول‌ها را نمی‌افتد | پایین | پایین | باید: سیاست حذف داده | — | باز · همه | `ls plugin/topersia-core plugin/topersia-render` — **VERIFIED** |
| S14 | دورزدن سقف روزانۀ AI با خروجی کهنه | متوسط | بالا | باید: شمارش بازسازی کهنه | مقایسۀ شمارنده با ردیف‌های جدول | باز · render/AI | `topersia-g-ai.php:142-144` · `class-tp-ai-summary.php:127` — **VERIFIED** |
| S15 | `tpas_track` (nopriv) بی نرخ؛ نوشتن `update_option` در هر کلیک؛ هر شناسۀ پست | پایین | متوسط | باید: نرخ، وجود پست | حجم `tpas_stats` | باز · AI | `class-tp-ai-tracker.php:34-56` — **VERIFIED** |
| S16 | `client_ip()` AI، `X-Forwarded-For` را باور می‌کند؛ user-core نه | متوسط | پایین | باید: یک منطق مشترک | — | باز · AI | `class-tp-ai-summary.php:517-522` — **VERIFIED** |
| S17 | دو منطق «منتشرشده» (render و user-core) | متوسط | پایین | باید: یک تابع عمومی هسته | آزمون `assert-uc.php` | نیمه‌کاهش‌یافته · core | `class-topersia-jetengine.php:88` · `topersia-g-zones.php` — **VERIFIED** |
| S18 | پیشوند جدول پایگاه‌داده در کامنت کد | پایین | پایین | حذف از کامنت | `grep -rn` | باز · user-core | `class-topersia-reviews.php:38,48` — **VERIFIED** (مقدار عمداً چاپ نشد) |
| S19 | `AS` ارتقا به 4.x (مثلاً با ارتقای Rank Math) جدول مشترک را تغییر می‌دهد | بالا | پایین | باید: پایش نسخۀ Rank Math | `ActionScheduler_Versions::latest_version()` | نیمه‌کاهش‌یافته · core | `topersia-queue.php:9-14` — **VERIFIED** |
| S20 | بستۀ `vendor/action-scheduler` در مخزن (حجم) و بی پایش امنیتی | پایین | پایین | وابستگی با نسخۀ ثابت | — | کاهش‌یافته · core | `topersia-core/vendor` — **VERIFIED** |
| S21 | کشور کاربر متن آزاد | متوسط | بالا | باید: ISO ۳۱۶۶ | شمارش مقادیر | باز · user-core | `class-topersia-profile.php:21,27` — **VERIFIED** |
| S22 | ورود فقط شمارۀ ایران؛ کاربر غیرایرانی راهی ندارد | متوسط | بالا | باید: P5-D257 | — | باز (تصمیم) · user-core | `class-topersia-auth.php:133-135` — **VERIFIED** |
| S23 | `mail()` غیرفعال؛ حساب بی ایمیل واقعی | متوسط | بالا | باید: SMTP یا فقط پیامک | آزمون `wp_mail` زنده | UNKNOWN · user-core | `class-topersia-review-form.php:20` — **ASSUMED** |
| S24 | کش صفحه (LiteSpeed) و نانس مهمان | متوسط | نامعلوم | باید: بررسی TTL، تازه‌سازی نانس با JS | ۴۰۳ در `admin-ajax` | UNKNOWN · AI/user-core | `class-tp-ai-frontend.php:50-54` · `class-topersia-auth.php:120-124` — **INFERRED** |
| S25 | PMTiles زنده: وجود، Range/206، CORS | بالا | نامعلوم | باید: آزمون فاز ۱۰ | `curl -sI -H "Range: …"` | UNKNOWN · render | MAP.md ۴ — **UNKNOWN** |
| S26 | کادر ایران (۲۵–۴۰٫۵ × ۴۴–۶۳٫۵) نقاط همسایگان را می‌پذیرد | پایین | متوسط | کادر چندضلعی | — | باز · core | `topersia-geo.php:23-26` — **VERIFIED** |
| S27 | ترجمۀ پیشخوان هسته ناقص (~۶۰۰ رشته) | پایین | — | پیوست ج | — | باز · core | `CLAUDE.md` (متن) — **VERIFIED** |
| S28 | شناسۀ رابطه‌ها در `topersia-schema` عددی و کدگذاری‌شده | متوسط | متوسط | باید: نگاشت نمادین | `schema-local.php` | باز · schema | `topersia-schema.php:322-345` — **VERIFIED** |
| S29 | export JetEngine عقب‌تر از پایگاه محلی (۱۳۵ در برابر ۱۳۹) و فاقد `tpc_news` | متوسط | بالا | export تازه + diff | `wp jet-engine export`/رابط | باز · core | `docs/AUDIT-RELATIONS-2F.md` · EVENTS.md ۶ — **VERIFIED** |

## پ — دو افزودۀ بخش ۲د

### S30 — افزونه‌های تجاری از توزیع‌کنندۀ واسط (`abzarwp`)

| | |
|---|---|
| شدت · احتمال | **بالا** · **بالا** (حضور سازوکار توزیع‌کننده **VERIFIED**؛ اثر امنیتی/حقوقی **نامعلوم**) |
| یافته | افزونۀ `abzarwp-auto-update` ۱٫۳٫۲۱ نصب است و **در ده افزونۀ دیگر و یک پوسته** زیرپوشۀ `abzarwp/` (با `abzarwp.php`، `update.php`، `license/` شامل کنترلرهای Cron · News · License · Update و مدل `PluginDeactivation`) و ارجاع در پروندۀ اصلی تعبیه شده است: `elementor-pro` (۵۹ فایل) · `jet-plugin-pro` (۴۶) · `sitepress-multilingual-cms` (۴۴) · `wpml-string-translation` (۴۳) · `woodmart-core` (۴۳) · `seo-by-rank-math-pro` (۴۳) · `jet-reviews` (۴۳) · `duplicator-pro` (۴۳) · `wpml-media-translation` (۸) · `wp-seo-multilingual` (۸) · پوستۀ `woodmart` (۴). `jet-engine`، `elementor` (رایگان)، `akismet`، `safe-svg` ندارند. شمار = پرونده‌هایی که رشتۀ `abzarwp` دارند (`grep -rIl`، بی تفاوت حروف). `.gitignore:82` پوشۀ `geo/wp-content/abzarwp-backups/` را بیرون می‌گذارد. |
| برچسب | **VERIFIED** (فهرست‌های بالا؛ محتوای پرونده‌ها خوانده نشد — تصمیم کاربر: به آن‌ها دست نزن) |
| ریسک‌ها | (۱) **حقوقی/مجوز**: نسخه از فروشندۀ اصلی نیست؛ ToS و پشتیبانی؛ (۲) **زنجیرۀ تأمین**: کد افزودۀ شخص ثالث با دسترسی کامل وردپرس و تماس شبکه‌ای (کنترلرهای Cron/News/License/Update)؛ (۳) **به‌روزرسانی**: وصلۀ امنیتی از کانال واسط، نه فروشنده؛ (۴) **ناپایداری**: خطای React #200 در پیشخوان که کاربر گزارش داده (ارتباطش با این لایه **UNKNOWN**)؛ (۵) **نشت داده**: تماس License/News به میزبان توزیع‌کننده. |
| کاهش (باید) | تهیۀ مجوز رسمی؛ مقایسۀ هش هر افزونه با بستۀ فروشنده؛ ثبت میزبان‌هایی که افزونه‌ها تماس می‌گیرند (`tp-local-http-probe.php` محلی دارد)؛ جداسازی این پوشه‌ها از مخزن ما؛ تصمیم کاربر دربارۀ ادامه |
| تشخیص (محلی) | `wp plugin list --fields=name,version,update`؛ لاگ `http-probe.log` برای میزبان‌های `abzarwp`؛ `php -d display_errors=1` / کنسول مرورگر پیشخوان برای React #200 |
| وضعیت · مالک | باز · **تصمیم کاربر** (نه کد ما) |

### S31 — نام کاربری مدیر در کد پروژه (فقط هست/نیست)

| | |
|---|---|
| پاسخ | **UNKNOWN از ابر.** نام واقعی مدیر در دسترس این ماشین نیست (فقط در متغیر محیطی `TP_GUARD_ADMIN_USER` محلی)، پس نمی‌توان «هست/نیست» را قطعی گفت. |
| شواهد | **VERIFIED**: گارد commit وجود دارد و نام را از همان متغیر می‌خواند و چاپ نمی‌کند (`scripts/git-guard`، `scripts/git-guard-test:78-80`، `CLAUDE.md` بخش گارد). **VERIFIED**: جست‌وجوی الگوهای `--user=` / `user_login = '…'` / `get_user_by('login', …)` در `tests/`, `scripts/`, `plugin/` فقط حساب‌های آزمون `tp_test_*` و متغیرها را می‌دهد؛ هیچ نام ثابت دیگری پیدا نشد. **INFERRED**: تنها شناسۀ شخصی در مخزن نام مخزن GitHub است (عمومی، نه نام ورود وردپرس). |
| سطح | بالا اگر «هست» (شناسایی مدیر برای حملۀ گذرواژه)؛ مخزن خصوصی است (P5-D284) ولی `geo/` و docs ممکن است کپی/منتشر شوند |
| تشخیص (محلی، بی چاپ نام) | `$n = (git grep -I -c -F -- $env:TP_GUARD_ADMIN_USER -- . ':!geo' \| Measure-Object).Count; "hits=$n"` — فقط شمار؛ و برای تاریخچه: `git log -S"$env:TP_GUARD_ADMIN_USER" --oneline \| Measure-Object -Line` |
| کاهش | گارد دارد؛ اگر «هست»: حذف از کد، `git filter-repo` فقط با تصمیم کاربر، عوض‌کردن نام ورود مدیر |
| وضعیت | UNKNOWN · کاربر/محلی |
