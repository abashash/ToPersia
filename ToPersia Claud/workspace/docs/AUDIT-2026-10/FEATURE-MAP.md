# FEATURE-MAP.md — نقشۀ قابلیت‌ها در سه سند

> فاز ۲د · ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ — **فقط‌خواندنی**.
> هر قابلیتِ سه سند ورودی: **A** = `docs/ToPersia-AI-Core-Master-Spec.md` (بند) · **M** = `reference/ToPersia_Master_Architecture_Audit_and_Expansion.md` (بند) · **P** = `reference/ToPersia_Map_Engine_Claude_Decision_Brief_v2.md` (بند).
> ستون «هست؟» وضعیت امروز در **کد** است؛ ستون «برچسب» برچسب همان «هست؟» است (`VERIFIED` با مسیر و خط · `INFERRED` · `ASSUMED` · `UNKNOWN` با فرمان محلی). طبقه‌بندی حکم ممیز است و از همان شاهد استنباط شده؛ با ARCH-RECONCILIATION هم‌خوانی یا اختلاف در ستون آخر آمده.
> نام‌اختصار مسیر: `AI` = `plugin/topersia-ai-summarizer` · `CORE` = `plugin/topersia-core` · `RENDER` = `plugin/topersia-render` · `UC` = `plugin/topersia-user-core` · `SCHEMA` = `plugin/topersia-schema` · `DOC` = `docs` · `REF` = `reference`.
> **معیار پنج طبقه:**
> **READY** = امروز هست و کار می‌کند؛ **READY WITH MODIFICATIONS** = با معماری فعلی (همان افزونه/جدول/صف) و تغییر محدود ساخته می‌شود؛ **NEEDS INFRASTRUCTURE** = سرویس بیرونی، کارگر/کرون سیستمی، ذخیرۀ رسانه یا زیرساخت تازه می‌خواهد؛ **FUTURE** = ایدۀ مناسب ولی وابسته به چیزی که هنوز نیست؛ **NOT RECOMMENDED** = با اصول پروژه یا تصمیم‌های ثبت‌شده در تعارض است.
> ستون «نسبت به ARCH»: ✓ هم‌خوان · ⚠ **ناهم‌خوان با شاهد** (توضیح در همان سطر).

---

## بخش ۱ — لایۀ هوش مصنوعی (سند A)

| # | قابلیت | سند | هست؟ — کجا؟ — افزونه | طبقه‌بندی | برچسب | نسبت به ARCH |
|---|---|---|---|---|---|---|
| 1 | هستۀ AI Core به‌صورت افزونۀ تازه | A§5 | نه؛ پایه در `topersia-ai-summarizer` (آداپتور پروتکل، بافت، موتور داخلی، کش) | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:72-101` | ✓ حکم #1 (اصلاح) |
| 2 | Router (انتخاب بر پایۀ کار) | A§11-12 | نه؛ فقط سوئیچ پروتکل یک موتور | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:94-101` | ✓ #3 |
| 3 | Policy (سیاست مصرف/ارائه‌دهنده) | A§12 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` نبود `policy` در `AI/includes/` | ✓ #3 |
| 4 | ProviderAdapter (۴ پروتکل) | A§71 | بله | READY | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:125,234,336` | ✓ |
| 5 | Failover به ارائه‌دهندۀ دوم | A§75 · ARCH P5-D317 | **نه** در خلاصه‌ساز؛ فقط لایۀ سرویس‌های هسته (TTS/هوا) | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:72-101` · `CORE/includes/topersia-services.php:298-307` | ⚠ P5-D317 فرض می‌کند پیاده است |
| 6 | دروازۀ گردشگری (Tourism Gate) | A§8-9 | نه؛ مسیر `post=0&url=` ورودی نیمه‌آزاد است | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:101-112,214` | ⚠ #5 («با نخستین ورودی آزاد») — ورودی آزاد همین حالا هست |
| 7 | خلاصۀ رسمی یکتا (summary/keypoints/plan) | A§13 | بله (همزمان در AJAX) | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-options.php:196-198` | ✓ #7 |
| 8 | شناسۀ کش SHA-256 + زبان + نسخۀ پرامت | A§14 | نه؛ SHA-1، بی نسخۀ پرامت/ارائه‌دهنده | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-cache.php:275-288` | ⚠ #8 (پذیرفته ولی پیاده نیست) |
| 9 | نام ارائه‌دهنده فقط در پیشخوان | A§13 | **نه**: پاسخ AJAX `provider` و `model` می‌دهد | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:209-210,229-230` | ⚠ #7 |
| 10 | صدای خواندنی (Read Aloud) | A§15 | نه؛ آداپتورها بی مصرف‌کننده | NEEDS INFRASTRUCTURE | `[VERIFIED]` `CORE/includes/topersia-services.php:56,87-88` | ✓ #9 |
| 11 | Storytelling (روایت زمینه‌دار) | A§15-16 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 12 | پادکست | A§15 · ARCH P5-D256 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ #9 |
| 13 | Google TTS / ElevenLabs | A§17 | ثبت و «آزمون اتصال» دارند | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-services.php:87-88,466-470` | ✓ #10 |
| 14 | دسترسی سرور زنده به API گوگل (محدودیت IP) | A§17 · ARCH #10 | — | NEEDS INFRASTRUCTURE | `[UNKNOWN]` زنده: `curl -sS -o /dev/null -w "%{http_code}\n" https://generativelanguage.googleapis.com/` | ✓ فاز ۱۰ |
| 15 | فایل صدا در Media Library با منشأ در جدول | A§44 · ARCH #11 | نه | NEEDS INFRASTRUCTURE | `[VERIFIED]` (نبود) | ✓ |
| 16 | سفر کاربر + نسخه + آیتم | A§19-25 | نه؛ شمارندۀ `_topersia_trips_count` بی نویسنده | FUTURE | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:35-41` | ✓ #12–13 (user-core مالک) |
| 17 | بهینه‌ساز مسیر قطعی (نه LLM) | A§23 | نه؛ آداپتورهای Neshan بی مصرف‌کننده | NEEDS INFRASTRUCTURE | `[VERIFIED]` `CORE/includes/topersia-services.php:93-95` | ✓ #21 |
| 18 | ویرایش تدریجی و نسخه‌گذاری سفر (patch) | A§24-25 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 19 | مجموعه‌ها (Collections) | A§26 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` (نبود) · پایه: `UC/includes/class-topersia-db.php:45-56` | ✓ #14 |
| 20 | ذخیرۀ مکان (Save Places) | A§27 | بله، جدول و ارجاع بی کپی | READY | `[VERIFIED]` `UC/includes/class-topersia-db.php:45-56` | ✓ #14 |
| 21 | Save ← Collection ← Trip | A§28 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 22 | Start Anywhere | A§29 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ #22 |
| 23 | Magic Camera / Lens | A§30 · M§31 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 24 | تعامل صوتی | A§31 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 25 | سفر مشارکتی | A§32 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 26 | توصیه برای مهمان/کاربر + کش توصیه | A§33-34 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 27 | Near Me (بی LLM) | A§35 · P§18 | نه؛ پایه (نمایۀ مکانی) هست | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:8-9` | ✓ #20 |
| 28 | نمایۀ مکانی مشتق | A§36 · P§9 | بله (محلی) | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:63-77` | ✓ |
| 29 | جست‌وجو (تاکردن «ۀ» و «ه»، پنجرۀ فهرست) | A§37 | بله، جدول قاعده؛ دروازۀ محلی | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-fold.php:40,169-171` | ✓ #20 |
| 30 | پروفایل کاربر (زبان، کشور، سبک سفر) | A§38 | بله، سه فیلد متن | READY WITH MODIFICATIONS | `[VERIFIED]` `UC/includes/class-topersia-profile.php:24-28` | ✓ |
| 31 | موتور کیفیت (چک‌های قطعی) | A§39 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-render.php:433-437` (فقط گریز) | ✓ |
| 32 | کنترل کیفیت معنایی (AI-judge) | A§39 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 33 | مدیریت محتوای کاربر (نظرها) | A§40 | بله، دستی (`approved=0`) | READY | `[VERIFIED]` `UC/includes/class-topersia-review-form.php:161` | ✓ |
| 34 | مدیریت محتوای AI | A§40 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 35 | پل WPML | A§41 | بله (کش زبان‌محور، ترجمه جدا، دروازۀ زبان) | READY | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:125,223` · `RENDER/includes/topersia-g-ai.php:142` | ✓ |
| 36 | صف واحد (Action Scheduler) | A§42 | بله، 3.9.3 بسته‌بندی‌شده؛ مصرف‌کننده فقط گالری | READY | `[VERIFIED]` `CORE/includes/topersia-queue.php:45-59` | ✓ #16 (اما سنجاق نسخه اجرایی نیست) |
| 37 | idempotency کار هوش مصنوعی | A§43 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:125-214` | ✓ |
| 38 | کش چندلایه (L1–L3 + رسانه) | A§44 | تنها لایۀ ترنزینت/جدول مرکزی | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-cache.php:329-331` | ✓ |
| 39 | Redis (اختیاری) | A§45 | نه؛ بی `object-cache.php` | FUTURE | `[VERIFIED]` `WP/wp-content/` بی درآپین · زنده `[UNKNOWN]` محلی/زنده: `wp cache type` | ✓ |
| 40 | جدول `ai_runs` / تلهمتری | A§46,72 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-db.php:85-94` (فقط کلیک) | ✓ #17 (توصیه: جدول افزونه) |
| 41 | پرامت‌ها در فایل نسخه‌دار | A§49 | نه؛ رشتۀ درون کلاس | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-localize.php:143-147` | ✓ #18 |
| 42 | امنیت REST/ورودی/تزریق/ابزار/SSRF | A§50-52 | بخشی: نانس و ورود هست؛ بی دروازۀ ورودی؛ بی ابزار | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:88-112` | ✓ #19 |
| 43 | حریم خصوصی و مکان (PII) | A§53 | IP هش‌شده در OTP؛ AI IP را در ترنزینت با `md5` | READY WITH MODIFICATIONS | `[VERIFIED]` `UC/includes/class-topersia-auth.php:145-157` · `AI/includes/class-tp-ai-summary.php:375` | ✓ |
| 44 | ایمنی خروجی AI | A§54 | بله با یک نشت (پیام خطا در `innerHTML`) | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/assets/js/topersia-ai.js:128` | ✓ |
| 45 | اعتبار (Credits) + رزرو | A§55-56 | نه؛ سقف روزانۀ ساده | FUTURE | `[VERIFIED]` `RENDER/includes/topersia-g-ai.php:147-158` | ✓ #15 (ثبت هزینۀ عملیات فعلاً نیست ← ⚠) |
| 46 | ضدسوءاستفاده (نرخ، سقف) | A§57 | بله (سه سازوکار هم‌پوشان) | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:362-405` | ✓ |
| 47 | Smart Prompt Pills / رابط سفر | A§58-61 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 48 | PWA / آفلاین | A§62 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ #22 |
| 49 | ماتریس قابلیت محتوا / موجودیت-اول | A§64-66 | بله (JetEngine + `entity_uid` + فقط منتشرشده) | READY | `[VERIFIED]` `AI/includes/class-tp-ai-context.php:409-422` | ✓ |
| 50 | رابط مدیریت AI | A§67 | پیشخوان «هوش مصنوعی» (تنظیمات، فهرست خلاصه‌ها، آزمون کلید) | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-admin.php:56-95` | ✓ |
| 51 | REST معماری A§68 | A§68 | **هیچ** مسیر REST در افزونۀ AI | READY WITH MODIFICATIONS | `[VERIFIED]` جستجوی `register_rest_route` در `AI/` | ✓ |
| 52 | تست‌های خصمانه (Tourism Gate، تزریق، سفر…) | A§81 | ۱۰ فایل تست؛ بی خصمانه | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/tests/` (test-01…test-10) | ✓ |
| 53 | رجیستر ریسک | A§82 | همین ممیزی (`RISKS.md`) | READY | `[VERIFIED]` `DOC/AUDIT-2026-10/RISKS.md` | ✓ |
| 54 | ۱۰ زبان با چینی ساده | A§41 | کد ۹ کد دارد (شامل `zh`) ولی رشتۀ رابط فقط ۳ زبان | READY WITH MODIFICATIONS | `[VERIFIED]` `AI/includes/class-tp-ai-localize.php:25-27` | پرسش باز ARCH §۷ |
| 55 | عامل خودمختار مرکزی | A§7 | نه | NOT RECOMMENDED | `[VERIFIED]` (نبود) | ✓ #4 |
| 56 | چت‌بات عمومی بی‌محدودیت | A§83 | نه | NOT RECOMMENDED | `[VERIFIED]` (نبود؛ P5-D255) | ✓ |
| 57 | فرستادن هم‌زمان به سه ارائه‌دهنده | A§83 | نه | NOT RECOMMENDED | `[VERIFIED]` `AI/includes/class-tp-ai-api.php:72-101` | ✓ #7 |
| 58 | LLM به‌عنوان جست‌وجوی جغرافیایی/مسیریاب اصلی | A§83 | نه | NOT RECOMMENDED | `[VERIFIED]` (نبود) | ✓ |
| 59 | موتور ترجمۀ مستقل جایگزین WPML | A§83 | نه | NOT RECOMMENDED | `[VERIFIED]` (نبود) | ✓ |
| 60 | صف کامل دوم | A§83 | نه | NOT RECOMMENDED | `[VERIFIED]` `CORE/includes/topersia-queue.php:13` | ✓ |
| 61 | فراخوانی مستقیم مرورگر به ارائه‌دهندۀ LLM | A§83 | نه؛ همه سمت سرور | NOT RECOMMENDED | `[VERIFIED]` `AI/assets/js/topersia-ai.js:158` (فقط `admin-ajax`) | ✓ |
| 62 | ساخت خودکار موجودیت canonical توسط AI | A§83 | نه | NOT RECOMMENDED | `[VERIFIED]` `CORE/includes/topersia-pack.php` (درون‌ریزی فقط پیش‌نویس) · CLAUDE.md P5-D272 | ✓ |
| 63 | Jev | A§10 | نامعلوم | FUTURE | `[UNKNOWN]` محلی: — (پرسش از کاربر، ARCH §۷ بند ۲) | ✓ #6 |
| 64 | قیمت‌یابی، رزرو، اکوسیستم سازندگان، ارکستراتور بیرونی، Elasticsearch، Vector DB | A§83 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |

---

## بخش ۲ — رویداد، تقویم، پایش، اعلان، تجربه (سند M)

| # | قابلیت | سند | هست؟ — کجا؟ — افزونه | طبقه‌بندی | برچسب | نسبت به ARCH |
|---|---|---|---|---|---|---|
| 65 | موجودیت واحد با شناسۀ جهانی | M§1 | بله: `entity_uid` (هسته) | READY | `[VERIFIED]` `CORE/topersia-core.php:1-60` (بخش ۳) | ✓ |
| 66 | تقویم و رویداد واحد (تقویم اصلی + تقویم کاربر) | M§2-3 | نه؛ ۵ نوع پست مستقل (E1,E3,E5,E6,P6) | READY WITH MODIFICATIONS | `[VERIFIED]` `REF/Jetengine_Skin.json` (خط ۱؛ `meta-37,39–42`) | ✓ P5-D295 |
| 67 | تاریخ ساخت‌یافته رویداد | M§3 | **جزئی**: `event_start_date`/`event_end_date` در E1,E3,E5 | READY WITH MODIFICATIONS | `[VERIFIED]` همان | ⚠ ARCH: «تاریخ ساخت‌یافته نه» |
| 68 | ساعت، تکرار ماشینی، «رویداد بعدی» | M§3 | نه (`next_start_date` ۰ تطابق) | READY WITH MODIFICATIONS | `[VERIFIED]` EVENTS.md §۳ | ✓ P5-D214 |
| 69 | نمایش پویای رویداد و چرخۀ عمر | M§4-5 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 70 | یکپارچگی تقویم فارسی | M§6 | تبدیل نمایشی فقط | FUTURE | `[VERIFIED]` `RENDER/includes/topersia-g-zones.php:1100-1101` | ✓ P5-D335 |
| 71 | ایجاد دستی رویداد و اتصال به موجودیت | M§7 | بله (ویرایشگر + بستۀ محتوا؛ ۳۸ رابطه) | READY WITH MODIFICATIONS | `[VERIFIED]` `REF/Jetengine_Skin.json` (خط ۱) | ✓ |
| 72 | عامل پایش وب (API و RSS) | M§8,11 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` EVENTS.md §۵ (نبود) | ✓ P5-D296 |
| 73 | خزش صفحه‌های JS | M§8 | نه | NEEDS INFRASTRUCTURE | `[VERIFIED]` (نبود) | ✓ |
| 74 | ثبت منبع و فرکانس پایش | M§9-10 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` (نبود) | ✓ |
| 75 | استخراج، تشخیص تغییر، تکراری‌یابی | M§12-14 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` (نبود) | ✓ |
| 76 | درخواست تغییر + تأیید انسانی | M§15-16 | نه؛ پایه: `needs_verification` و `last_verified_date` | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-pack.php` (۲۱ مورد) | ✓ |
| 77 | منشأ و منبع (Provenance) | M§17 | بله: `source_references`، `content_status` | READY | `[VERIFIED]` `REF/Jetengine_Skin.json` (`meta-1`) | ✓ |
| 78 | منابع متعارض | M§18 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 79 | کشور کاربر | M§19 | متن آزاد پروفایل | READY WITH MODIFICATIONS | `[VERIFIED]` `UC/includes/class-topersia-profile.php:21,27` | ✓ (در ARCH «۲د») |
| 80 | پروفایل علاقه | M§20 | `travel_style` متن آزاد | READY WITH MODIFICATIONS | `[VERIFIED]` `UC/includes/class-topersia-profile.php:22` | ✓ |
| 81 | فرصت سفر شخصی‌شده | M§21 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 82 | فرصت سفر بر پایۀ رویداد | M§22 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ P5-D297 |
| 83 | پیامک (کانال اعلان) | M§23-24 | فقط OTP؛ ارائه‌دهنده Mediana | READY WITH MODIFICATIONS | `[VERIFIED]` `UC/includes/class-topersia-mediana-sms.php:11-22` | ✓ P5-D297 |
| 84 | ایمیل اعلان | M§25 | نه؛ `mail()` غیرفعال | NEEDS INFRASTRUCTURE | `[VERIFIED]` `UC/includes/class-topersia-review-form.php:20` | — |
| 85 | قواعد اعلان و زمان‌بندی | M§25-26 | نه | FUTURE | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:124` | ✓ |
| 86 | پنل کاربر + تقویم | M§27 | پنل ۸ تب؛ بی تقویم | READY WITH MODIFICATIONS | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:15-25` | ✓ P5-D303 |
| 87 | Iran, Right Now | M§28 | فقط هوا و کیفیت هوا | NEEDS INFRASTRUCTURE | `[VERIFIED]` `RENDER/includes/topersia-g-weather.php:1006` | ✓ P5-D298 |
| 88 | Compare Iran | M§29 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 89 | Ask Iran | M§30 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 90 | Food Lens | M§32 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 91 | تجربۀ شخصی‌شده | M§33 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 92 | چندزبانگی | M§36 | سه‌زبانه (fa/en/ar) با WPML | READY | `[VERIFIED]` `WS/CLAUDE.md` (بند «سه‌زبانه») · `UC/includes/class-topersia-profile.php:26` | ✓ |
| 93 | SEO/GEO/AEO | M§37 | بله: افزونۀ شِما | READY | `[VERIFIED]` `SCHEMA/topersia-schema.php:255,333-343` | ✓ |
| 94 | امنیت و حریم | M§39 | بخشی | READY WITH MODIFICATIONS | `[VERIFIED]` RISKS.md | ✓ |
| 95 | مجوز داده‌های بیرونی | M§40 | بی ثبت منبع/مجوز | NEEDS INFRASTRUCTURE | `[VERIFIED]` `DOC/SPRINT-4.4-PHASES.md:159` (P5-D335) | ✓ |
| 96 | خبر هر موجودیت (فاز ۶ سند فازها) | M§8 (پایه‌ای که ۶ب روی آن می‌نشیند) | بله، محلی (`tpc_news`) | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/includes/topersia-g-news.php:29-32` | ✓ |

---

## بخش ۳ — نقشه (سند P)

| # | قابلیت | سند | هست؟ — کجا؟ — افزونه | طبقه‌بندی | برچسب | نسبت به ARCH |
|---|---|---|---|---|---|---|
| 97 | MapLibre 5 + PMTiles + Protomaps + فونت/اسپرایت محلی | P§5 | بله | READY | `[VERIFIED]` `RENDER/assets/map/maplibre-gl.js:3,46` | ✓ KEEP |
| 98 | `TopersiaMap` آداپتور، بارگذاری تنبل، مودال، SVG مرجع | P§5,15 | بله | READY | `[VERIFIED]` `RENDER/assets/topersia-g-zones.js:2018-2296` | ✓ |
| 99 | لایۀ Map Query با پشتیبان قابل‌تعویض | P§5,24 | نه؛ تابع مستقیم | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:84` | ✓ پذیرفته، پیاده نیست |
| 100 | بنچمارک A (DECIMAL) در برابر B (POINT) | P§22-23 | اجرا شده (۲۰۰ هزار نقطۀ ساختگی) | READY | `[INFERRED]` `DOC/GEO-BENCH-2F.md:1-21` | ⚠ ARCH: «پیش از ساخت جدول» گذشته |
| 101 | نمایۀ مشتق + همگام‌سازی + بازسازی | P§9-10 | بله، محلی | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:284-344` | ✓ |
| 102 | ستون `entity_uid` و شناسه‌های محدوده (فراتر از استان/شهر) | P§9 | نه؛ فقط `province_id` و `city_id` | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:63-77` | ⚠ حکم §۴ |
| 103 | قرارداد `FeatureCollection` + `meta` | P§12 | بی `meta` | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:101-116` | ⚠ حکم §۴ |
| 104 | `/map-points` (GET بر پایۀ زمینه) | P§11 | بله، محلی | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:157-164` | ✓ |
| 105 | bbox و بارگذاری بر پایۀ viewport | P§16 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/assets/topersia-g-zones.js:2231-2243` | ✓ |
| 106 | خوشه‌بندی GeoJSON | P§16 | بله (سمت مرورگر) | READY | `[VERIFIED]` `RENDER/assets/topersia-g-zones.js:2189` | ✓ |
| 107 | «اطراف من» با POST | P§18 | نه | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:8-9` | ✓ |
| 108 | سقف عددی ثابت نشانگر | P§4.4 | بله: ۴۰۰ | READY WITH MODIFICATIONS | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:80` | ⚠ حکم «بی عدد ثابت» |
| 109 | مختصات جعلی از والد ممنوع | P§8 | رعایت شده | READY | `[VERIFIED]` `RENDER/includes/topersia-g-zones.php:2707-2711` | ✓ |
| 110 | برچسب سه‌زبانه از PMTiles | P§13 | منطق هست؛ پوشش | READY WITH MODIFICATIONS | `[UNKNOWN]` محلی: `LQ-SCRIPT-NAMES` در `LOCAL-QUERIES.md` | ✓ |
| 111 | پیوند ناوبری (Google/Waze؛ Neshan/Balad بی الگو) | P§19 | آداپتور خاموش | READY WITH MODIFICATIONS | `[VERIFIED]` `CORE/includes/topersia-services.php:96-101` | ✓ قاعدۀ ۱۱ |
| 112 | Neshan مسیریابی/ماتریس/آدرس | P§19 · M§— | آداپتور، بی مصرف‌کننده | NEEDS INFRASTRUCTURE | `[VERIFIED]` `CORE/includes/topersia-services.php:93-95` | ✓ |
| 113 | آزمون Range/206/CORS/CDN برای PMTiles | P§20 | نه | NEEDS INFRASTRUCTURE | `[UNKNOWN]` محلی: `curl -sI -H "Range: bytes=0-16383" <نشانی کاشی>` | ✓ فاز ۱۰ |
| 114 | حذف افزونۀ RTL نقشه | P§4.6 | نه (استفاده می‌شود) | FUTURE | `[VERIFIED]` `RENDER/assets/topersia-g-zones.js:2065` | ✓ |
| 115 | مهاجرت به MapLibre v6 | P§4.7 | نه | FUTURE | `[VERIFIED]` `RENDER/assets/map/maplibre-gl.js:46` | ✓ |
| 116 | تحویل موجودیت به‌صورت MVT/PMTiles | P§17 | نه | FUTURE | `[VERIFIED]` (نبود) | ✓ |
| 117 | موتور مسیریابی، ترافیک، ناوبری گام‌به‌گام | P§5 | نه | NOT RECOMMENDED | `[VERIFIED]` (نبود؛ P5-D253) | ✓ |
| 118 | ورود Places بیرونی؛ موجودیت‌های فقط‌نقشه | P§5 | نه | NOT RECOMMENDED | `[VERIFIED]` (نبود) | ✓ |
| 119 | ادعای «بلد/نشان راستی‌آزمایی‌شده» | P§4.3 | رد شده در کد | NOT RECOMMENDED | `[VERIFIED]` `CORE/includes/topersia-services.php:99-100` | ✓ |
| 120 | `/map-points` روی زنده | P§11 | با دروازۀ محلی خاموش | NEEDS INFRASTRUCTURE | `[UNKNOWN]` زنده: `wp eval 'echo wp_get_environment_type();'` | ✓ |

---

## جمع‌بندی

| طبقه‌بندی | شمار قابلیت |
|---|---|
| READY | 16 |
| READY WITH MODIFICATIONS | 51 |
| NEEDS INFRASTRUCTURE | 11 |
| FUTURE | 31 |
| NOT RECOMMENDED | 11 |
| جمع | 120 |

## ناهم‌خوانی‌ها با حکم ARCH-RECONCILIATION (فقط ثبت؛ موافقت لازم نیست)

| # | حکم ARCH | شاهد مخالف | برچسب |
|---|---|---|---|
| 1 | #5 دروازۀ گردشگری «با نخستین ورودی آزاد» | ورودی نیمه‌آزاد `post=0&url=` همین حالا هست — `AI/includes/class-tp-ai-summary.php:101-112,214` | `[VERIFIED]` |
| 2 | #7 نام ارائه‌دهنده فقط در پیشخوان | پاسخ AJAX `provider`/`model` را به مرورگر می‌دهد — `AI/includes/class-tp-ai-summary.php:209-210,229-230` | `[VERIFIED]` |
| 3 | #8 SHA-256 بر بستۀ منبعِ نرمال‌شده + نسخۀ پرامت | SHA-1، بی نسخۀ پرامت/ارائه‌دهنده — `CORE/includes/topersia-cache.php:275-288` | `[VERIFIED]` |
| 4 | #16 / P5-D290 سنجاق Action Scheduler روی 3.9.3 | بالاترین نسخۀ ثبت‌شده برنده است؛ سنجاق اجرایی نیست — `CORE/vendor/action-scheduler/classes/ActionScheduler_Versions.php:73-80` | `[VERIFIED]` |
| 5 | #17 کانال `ai` لاگ یا جدول | توصیه: جدول افزونه — AI.md §۸ | `[INFERRED]` |
| 6 | P5-D255 «هیچ پیوند بیرونی به ChatGPT و مانند آن» | دکمه‌های بیرونی پیش‌فرض روشن‌اند — `AI/includes/class-tp-ai-options.php:42,44` | `[VERIFIED]` |
| 7 | P5-D317 «ارائه‌دهندۀ دوم پشتیبان خودکار» | در افزونۀ AI پیاده نیست — `AI/includes/class-tp-ai-api.php:72-101` | `[VERIFIED]` |
| 8 | §۳ «تاریخ ساخت‌یافته نه» | شروع/پایان برای E1، E3، E5 هست — EVENTS.md §۳ | `[VERIFIED]` |
| 9 | §۴ «بنچمارک A/B در آغاز فاز ۳» | اجرا شده — `DOC/GEO-BENCH-2F.md:1-21` | `[INFERRED]` |
| 10 | §۴ ستون‌های نمایه با `entity_uid` | ندارد — `CORE/includes/topersia-geo-index.php:63-77` | `[VERIFIED]` |
| 11 | §۴ قرارداد GeoJSON با `meta` | ندارد — `RENDER/includes/topersia-g-mappoints.php:116` | `[VERIFIED]` |
| 12 | §۴ «هیچ عدد ثابت برای شمار نشانگر» | سقف ۴۰۰ — `RENDER/includes/topersia-g-mappoints.php:80` | `[VERIFIED]` |
| 13 | P5-D294/P5-D346 دروازۀ «فقط محلی تا ممیزی الف» | پیوست الف منسوخ است؛ دروازه بی محرک — `DOC/SPRINT-4.4-PHASES.md:1284` | `[VERIFIED]` |
