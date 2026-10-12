# EVENTS.md — رویداد، تقویم، خبر و پایش (ممیزی فقط‌خواندنی)

> فاز ۲د · ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱. هیچ پایگاه‌داده‌ای در دسترس نبود؛ ساختار از **برون‌بری JetEngine** و **کد افزونه‌ها** خوانده شد.
> برچسب‌ها: `VERIFIED` (مسیر و خط) · `INFERRED` · `ASSUMED` · `UNKNOWN` (فرمان محلی کنارش).
> نام‌اختصار: `REF` = `reference` · `DOC` = `docs` · `RENDER` = `plugin/topersia-render` · `SCHEMA` = `plugin/topersia-schema` · `CORE` = `plugin/topersia-core` · `UC` = `plugin/topersia-user-core`.
> **هشدار تازگی:** `REF/Jetengine_Skin.json` برون‌بری **۹ سپتامبر ۲۰۲۶** است (`REF/TP-G-FIELDMAP.json:_meta.source`) و ۱۳۵ رابطه دارد؛ ممیزی ۲و٫۱ (۱۲ اکتبر) روی جت‌انجین زنده‌ی محلی ۱۳۹ رابطه شمرد (`DOC/AUDIT-RELATIONS-2F.md:13`). پس هرجا از «اسکیما» می‌گویم یعنی برون‌بری ۹ سپتامبر؛ وضعیت امروز `UNKNOWN` تا اجرای فرمان محلی.
> پرونده‌های JSON مرجع تک‌خطی‌اند؛ آدرس‌دهی با مسیر JSON و «خط ۱».

---

## ۱ — خلاصۀ حکم

| پرسش | پاسخ | برچسب |
|---|---|---|
| رویداد در سیستم چیست؟ | **نوع پست‌های جدا** در JetEngine: E1 جشنواره، E3 ورزشی، E5 مناسک مذهبی، E6 رویداد طبیعی/فصلی، و P6 تقویم/مناسبت رسمی. E2 و E4 در برون‌برین نیستند | `[VERIFIED]` `REF/Jetengine_Skin.json` (خط ۱؛ `post_types[slug in tp_festival,tp_sports_event,tp_religious_event,tp_seasonal_event,tp_calendar]`) |
| «یک سیستم رویداد و تقویم» (P5-D295)؟ | **نیست**؛ پنج نوع پست مستقل با متاباکس مستقل، بی جدول مشترک، بی تقویم، بی نمای کاربر | `[VERIFIED]` (جستجوی `calendar`/`event` در `RENDER`, `CORE`, `UC` فقط نام نوع و برچسب می‌دهد) |
| تاریخ ساخت‌یافته؟ | **جزئی**: `event_start_date`/`event_end_date` (نوع `date`) در E1، E3، E5 هست؛ E6 و P6 تاریخ ماشینی ندارند. فیلدهای `next_start_date`/`next_end_date` (P5-D214) **در هیچ‌جا نیستند** | `[VERIFIED]` جدول §۳ · `grep next_start_date` در `REF/Jetengine_Skin.json` و `UC/includes/jetengine-schema.php` = ۰ |
| حکم ARCH-RECONCILIATION §۳ («تاریخ ساخت‌یافته نه؛ P5-D214 منتظر ممیزی») | **با شاهد نمی‌خواند**: بخشی از تاریخ‌ها (شروع/پایان) از قبل در اسکیما هست؛ آنچه نیست «رویداد بعدی»ِ محاسبه‌شده و تکرار ساخت‌یافته و ساعت و شهر مستقیم است | `[VERIFIED]` `REF/Jetengine_Skin.json` (`meta_boxes["meta-39".."meta-41"]`) |
| داده‌ای واقعی هست؟ | محلی (۱۲ اکتبر): ۱ جشنواره و ۱ تقویم، **صفر** ورزشی/مذهبی/فصلی؛ ۱۸۳ پست منتشرشدۀ زبان پیش‌فرض در ۲۰ نوع | `[INFERRED]` `DOC/GEO-QUALITY-2F.json` (`tp_festival.posts=1`, `tp_calendar.posts=1`) · `DOC/GEO-BENCH-2F.md:23-25` |
| نیاز به ۶ب (پایش منابع)؟ | **صفر کد**: ثبت منبع، دریافت، تشخیص تغییر، درخواست تغییر — هیچ‌کدام نیست | `[VERIFIED]` (نبود: جستجوی `fetch_feed\|simplepie\|source_registry\|change_request\|news_source` در `CORE`, `RENDER`, `UC`, `AI` فقط دو فیلد نمایشی `news_source_name/url` را می‌دهد، `RENDER/includes/topersia-g-news.php:513-514`) |

---

## ۲ — نوع‌ها

| دسته | نوع پست | شناسه | متاباکس | شاهد | برچسب |
|---|---|---|---|---|---|
| E1 | `tp_festival` | 222 | «E1 — جشنواره و رویداد» (`meta-39`) | `REF/Jetengine_Skin.json` | `[VERIFIED]` |
| E3 | `tp_sports_event` | 223 | «E3 — رویداد ورزشی» (`meta-40`) | همان | `[VERIFIED]` |
| E5 | `tp_religious_event` | 224 | «E5 — مراسم مذهبی عمومی» (`meta-41`) | همان | `[VERIFIED]` |
| E6 | `tp_seasonal_event` | 225 | «E6 — رویداد طبیعی و فصلی» (`meta-42`) | همان | `[VERIFIED]` |
| P6 | `tp_calendar` | 220 | «P6 — تقویم و مناسبت‌های رسمی» (`meta-37`) | همان | `[VERIFIED]` |
| خبر | `tpc_news` | — | **در برون‌بری نیست** (۰ تطابق)؛ فقط در کد رندر با نگهبان `post_type_exists` | `REF/Jetengine_Skin.json` (۰ تطابق) · `RENDER/includes/topersia-g-news.php:29-32` | `[VERIFIED]` |
| وجود نوع `tpc_news` و میزان خبر در پایگاه | — | — | — | — | `[UNKNOWN]` محلی: `wp post-type list --fields=name,count --format=csv \| grep tpc` و `wp post list --post_type=tpc_news --format=count` |

همۀ پنج نوع از **متاباکس مشترک** (`meta-1`، ۲۰ فیلد: نام سه‌زبانه، خلاصه، `last_verified_date`، `content_status`، `entity_uid`، `source_references`…) هم می‌گیرند: `[VERIFIED]` `REF/Jetengine_Skin.json` (`meta_boxes["meta-1"].args.allowed_post_type` شامل هر پنج).

---

## ۳ — فیلدهای هر نوع

| فیلد | E1 | E3 | E5 | E6 | P6 | نوع | برچسب |
|---|---|---|---|---|---|---|---|
| `event_start_date` · `event_end_date` | ✓ | ✓ | ✓ | — | — | `date` | `[VERIFIED]` |
| `calendar_timing` (زمان‌بندی تقویمی، متن آزاد) | ✓ | ✓ | ✓ | ✓ | — | `textarea` | `[VERIFIED]` |
| `is_recurring` · `event_frequency` (واژه‌نامه 213) | ✓ | ✓ | — | — | — | `switcher` · `select` | `[VERIFIED]` |
| `calendar_system` (واژه‌نامه 204) · `annual_date_rule` (متن) | — | — | ✓ | — | — | `select` · `text` | `[VERIFIED]` |
| `best_season` (واژه‌نامه 34) | ✓ | ✓ | ✓ | ✓ | — | `select` | `[VERIFIED]` |
| `season` · `peak_start_week` · `peak_end_week` | — | — | — | ✓ | — | `select` · `number` | `[VERIFIED]` |
| `venue_name` · `organizer_name` · `official_event_url` | ✓ | ✓ (venue/organizer/url) | venue | — | — | `text` | `[VERIFIED]` |
| `program_highlights` (ریپیتر) | ✓ | — | — | — | — | `repeater` | `[VERIFIED]` |
| `national_holidays` (ریپیتر با `nh_date_rule` متن، `nh_duration_days`، `nh_is_public_holiday`، `nh_closure_level`، `nh_travel_impact`، `nh_row_status`) | — | — | — | — | ✓ | `repeater` | `[VERIFIED]` |
| `calendar_systems` (ریپیتر) · `annual_review_month` | — | — | — | — | ✓ | `repeater` · `number` | `[VERIFIED]` |
| منبع/منشأ | `source_references` (ریپیتر: نام، نشانی، تاریخ دسترسی، وضعیت ردیف) و `last_verified_date` و `content_status` از متاباکس مشترک |  |  |  |  |  | `[VERIFIED]` |
| ساعت شروع/پایان | **هیچ‌جا** |  |  |  |  | — | `[VERIFIED]` (نبود) |
| مختصات رویداد | از متاباکس مشترک نه؛ رویداد مختصات خود ندارد (برگرفته از رابطه‌ها) |  |  |  |  | — | `[INFERRED]` |
| شهر (فیلد مستقیم) | نه؛ فقط از راه رابطه |  |  |  |  | — | `[VERIFIED]` |

**نبود تکرار ساخت‌یافته:** `is_recurring` فقط سوئیچ است و قاعدۀ تکرار (روز هفته/ماه/فاصله) یا `RRULE` نیست؛ برای E5 تکرار فقط در متن `annual_date_rule` و `calendar_timing` است. → `[VERIFIED]` (فیلدها بالا) ؛ اثر: تقویم ماشینی «رویداد بعدی» ممکن نیست مگر با تجزیۀ متن.

**نبود تقویم فارسی:** تنها تبدیل جلالی در کد ما تابعی برای نمایش تاریخ است (`topersia_g_jalali`)، نه منبع تعطیلات یا قاعدۀ قمری؛ هیچ کتابخانۀ تقویم فارسی دیگری در ۵ افزونه نیست. → `[VERIFIED]` `RENDER/includes/topersia-g-zones.php:1100-1101,1203` · حکم ARCH-RECONCILIATION §۳ («مخزن تقویم فارسی: پرسش — کدام مخزن؟») همچنان پرسش باز است و **P5-D335** ورود کد/دادۀ آن را تا راستی‌آزمایی مجوز ممنوع کرده `DOC/SPRINT-4.4-PHASES.md:159`.

---

## ۴ — رابطۀ رویداد و موجودیت

| گروه | رابطه‌ها (شناسه) | شمار | برچسب |
|---|---|---|---|
| E1 جشنواره | 232 استان · 233 شهر · 234 روستا (`held_in`) · 235 بنا · 236 جایگاه مذهبی · 237 موزه · 238 منطقۀ حفاظت‌شده · 239 روستا (`held_at`) · 240 مناسک (`instance`) · 241 مناسک (`celebrates`) · 242 چهره · 243 دورۀ تاریخی · 244 تقویم (`affects`) | 13 | `[VERIFIED]` |
| E3 ورزشی | 245 استان · 246 شهر · 247 روستا (`held_in`) · 248 بنا · 249 جایگاه مذهبی · 250 موزه · 251 منطقۀ حفاظت‌شده · 252 روستا (`held_at`) | 8 | `[VERIFIED]` |
| E5 مذهبی | 253 استان · 254 شهر · 255 روستا (`held_in`) · 256 بنا · 257 جایگاه مذهبی · 258 روستا (`held_at`) · 259–260 مناسک · 261 چهره · 262 دورۀ تاریخی | 10 | `[VERIFIED]` |
| E6 فصلی | 263 استان · 264 شهر · 265 روستا (`held_in`) | 3 | `[VERIFIED]` |
| P6 تقویم | 228 کشور · 229 استان · 230 شهر (`applies`) · 231 مناسک (`affects`) | 4 | `[VERIFIED]` |
| جمع | ۳۸ از ۱۳۵ رابطۀ برون‌بری؛ همه `many_to_many` | 38 | `[VERIFIED]` `REF/Jetengine_Skin.json` (خط ۱؛ `relations[].args` با `parent_object\|child_object` در بالا) |

| مورد | یافته و شاهد | برچسب |
|---|---|---|
| داده‌ی واقعی (ردیف) | فقط رابطه‌های 232 و 233 (هر کدام ۳ ردیف)؛ بقیۀ ۳۶ رابطۀ رویداد صفر ردیف — ممیزی ۱۲ اکتبر: `DOC/AUDIT-RELATIONS-2F.csv:103-104` | `[INFERRED]` |
| داده‌ی واقعی امروز | — | `[UNKNOWN]` محلی: `wp eval 'global $wpdb; foreach($wpdb->get_results("SELECT rel_id, COUNT(*) c FROM {$wpdb->prefix}jet_rel_default GROUP BY rel_id") as $r) echo $r->rel_id,"=",$r->c,"\n";'` |
| تقویم ← رویداد | فقط P6 ← E1 (244) و P6 ← مناسک (231)؛ نه به E3، E5، E6 — `REF/Jetengine_Skin.json` (خط ۱؛ `relations` با شناسه 231 و 244) | `[VERIFIED]` |
| اسکیمای SEO | `tp_festival` → `Thing`+`additionalType Festival`؛ `tp_sports_event` → `SportsEvent`؛ `tp_religious_event` و `tp_seasonal_event` → `Event`؛ همه با ساختار `Thing` (نه `Event`) چون تاریخ ماشین‌خوان نیست؛ `tp_calendar` → `Thing` — `SCHEMA/topersia-schema.php:255,333-336,343` | `[VERIFIED]` |
| نمایش در صفحه | نوع‌های رویداد جایگاه رابطه در صفحه ندارند (۳۰ از ۴۲ نوع بی جایگاه؛ `child_type_has_relmap=n` برای جشنواره) — `DOC/AUDIT-RELATIONS-2F.md:12` · `DOC/AUDIT-RELATIONS-2F.csv:103-104`؛ آمار نوع: `tp_festival` با `event_frequency`، `tp_calendar` بی آمار — `RENDER/includes/topersia-g-zones.php:5370-5373` | `[VERIFIED]` |

---

## ۵ — خبر (فاز ۶ سند فازها)

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| مدل | `tpc_news` به‌عنوان Content Type (نه موجودیت) با ۴ رابطه به استان/شهر/روستا/جزیره | `RENDER/includes/topersia-g-news.php:3-9,41-43` | `[VERIFIED]` |
| فقط محلی تا ممیزی | بله؛ همۀ منطق پشت `post_type_exists('tpc_news')`، پس نصب روی زنده بی اثر است | `RENDER/includes/topersia-g-news.php:10-12,29-32` | `[VERIFIED]` |
| کار موجود | تجمیع رو به بالا (`_tp_news_ancestor`)، بخش «اخبار» با ۴ خبر ۱۲ ماه اخیر، صفحۀ `/{rewrite}/{slug}/news/` با `noindex` زیر ۳ خبر، عنوان/کاننیکال/Rank Math | `RENDER/includes/topersia-g-news.php:14-22,310-348,358-482` | `[VERIFIED]` |
| فیلدهای منبع خبر | `news_source_name` و `news_source_url` (متای ساده؛ منشأ در `meta-1.source_references` نیست) | `RENDER/includes/topersia-g-news.php:513-514` | `[VERIFIED]` |
| دریافت خودکار خبر (۶ب، P5-D273) | **نیست**؛ ثبت منبع، RSS/Atom، GET شرطی، تکراری‌یابی، صف بازبینی | جستجوی بخش ۱ | `[VERIFIED]` (نبود) |
| تاکسونومی `tpc_news_topic` | در برون‌بری نیست (`taxonomies: []`)؛ در کد هم ثبت نشده | `REF/Jetengine_Skin.json` (`taxonomies=[]`) · جستجوی `tpc_news_topic` | `[VERIFIED]` |
| وابستگی صف | دریافت باید از `topersia_queue_add()` برود؛ زنده `DISABLE_WP_CRON` روشن فرض شده و صف به کرون سیستمی فاز ۱۰ وابسته است | `CORE/includes/topersia-queue.php:13-15` · `DOC/SPRINT-4.4-PHASES.md:1161` | `[VERIFIED]` (توضیح) · مقدار واقعی `[UNKNOWN]` محلی/زنده: `wp config get DISABLE_WP_CRON` |

---

## ۶ — وضعیت ۶ب و شکاف با Master Audit

> جدول بندهای `REF/ToPersia_Master_Architecture_Audit_and_Expansion.md`. «حکم» از ARCH-RECONCILIATION §۳. ستون «کد» یعنی چه چیزی امروز هست.

| بند Master Audit | کد امروز | شکاف | برچسب |
|---|---|---|---|
| ۱ موجودیت واحد (`entity_uid`) | هست؛ هسته می‌نویسد (immutable) | — | `[VERIFIED]` `CORE/topersia-core.php:1-60` (فهرست بخش ۳) |
| ۲ تقویم/رویداد واحد با دو نما | نیست | حکم READY WITH MODIFICATIONS درست است؛ پیش‌نیاز: ساختار تاریخ + یک جدول/CPT مرکزی یا نمای مشتق | `[VERIFIED]` |
| ۳ Event ساخت‌یافته (عنوان، تاریخ، ساعت، مکان، برگزارکننده، وضعیت، منبع، تأیید، تکرار، آخرین تغییر) | نیمه (§۳) | ساعت، تکرار ساخت‌یافته، شهر مستقیم، تاریخ آخرین تغییر مستقل | `[VERIFIED]` |
| ۴–۵ نمایش پویا / چرخۀ عمر (آینده، جاری، گذشته، لغو) | نیست | `content_status` وضعیت محتواست نه وضعیت رویداد | `[VERIFIED]` `REF/Jetengine_Skin.json` (`meta-1.content_status`) |
| ۶ تقویم فارسی | تبدیل نمایشی فقط | منبع تعطیلات/قمری نیست؛ P5-D335 ممنوع | `[VERIFIED]` |
| ۷ ایجاد دستی رویداد توسط مدیر | از راه ویرایشگر نوع پست و «بستۀ محتوا» (فقط پیش‌نویس + `needs_verification`) | نیاز ندارد؛ بستۀ محتوا نوع‌های E را هم می‌گیرد (اسکیمای زنده) | `[INFERRED]` `CORE/includes/topersia-pack.php` (۲۱ مورد `content_status\|needs_verification`) |
| ۸–۱۴ عامل پایش، ثبت منبع، فرکانس، API-first، استخراج، تشخیص تغییر، تکراری‌یابی | نیست | همه ۶ب (حکم: API/RSS = READY WITH MODIFICATIONS؛ خزش JS = NEEDS INFRASTRUCTURE) | `[VERIFIED]` (نبود) |
| ۱۵–۱۷ درخواست تغییر، تأیید انسانی، منشأ | بخشی از منشأ هست: `source_references`, `last_verified_date`, `content_status`, ردیف‌های `*_row_status`، و `needs_verification` در درون‌ریزی | «درخواست تغییر» (Diff) و صف تأیید نیست | `[VERIFIED]` |
| ۱۸ منابع متعارض | نیست | — | `[VERIFIED]` (نبود) |
| ۱۹ کشور کاربر | متن آزاد در پروفایل | به کد ISO عادی‌سازی شود | `[VERIFIED]` `UC/includes/class-topersia-profile.php:21,27` (جزئیات `USER.md` §۴) |
| ۲۰ پروفایل علاقه | فقط `travel_style` متن آزاد | — | `[VERIFIED]` `UC/includes/class-topersia-profile.php:22` |
| ۲۱–۲۶ فرصت سفر، پیامک، شخصی‌سازی، قواعد اعلان، زمان‌بندی | نیست؛ تنها کانال پیامکی = کد یک‌بارمصرف ورود | حکم «پیامک: READY WITH MODIFICATIONS؛ فقط یک کانال سیستم اعلان؛ با رضایت و سقف بسامد» درست؛ ولی **ایمیل `mail()` غیرفعال** است | `[VERIFIED]` `UC/includes/class-topersia-review-form.php:20` |
| ۲۷ پنل کاربر + تقویم | سه ستونه (P5-D303) فقط طراحی؛ تب‌ها بی تقویم | — | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:15-25` |
| ۲۸–۳۲ Iran Right Now، Compare، Ask Iran، Lens، Food Lens | نیست؛ هوا و کیفیت هوا (REST `/weather` و `/air`) تنها داده‌ی «همین الان» | حکم‌ها FUTURE/NEEDS INFRASTRUCTURE درست | `[VERIFIED]` `RENDER/includes/topersia-g-weather.php:1006` · `RENDER/includes/topersia-g-air.php:254` |

---

## ۷ — تناقض یا نکتۀ اجرایی برای معمار

| # | یافته | برچسب |
|---|---|---|
| E1 | حکم «تاریخ ساخت‌یافته نیست» (ARCH-RECONCILIATION §۳) را **تصحیح کنید**: شروع/پایان برای ۳ از ۴ نوع رویداد هست. آنچه نیست ساعت، تکرار ماشینی و تاریخ «رویداد بعدی» است | `[VERIFIED]` |
| E2 | `Event` در JSON-LD فعلاً عمداً `Thing` است؛ با تاریخ ماشین‌خوان باید به `Event` برگردد و `SCHEMA/topersia-schema.php:255` آن را می‌گوید | `[VERIFIED]` |
| E3 | رابطۀ تقویم ← رویداد فقط به E1 وصل است؛ «تقویم» نمی‌تواند رویداد مذهبی/ورزشی/فصلی را اعلام کند | `[VERIFIED]` |
| E4 | ۳۶ از ۳۸ رابطۀ رویداد صفر ردیف دارند و فقط ۱ جشنواره در پایگاه محلی بود؛ طراحی «تقویم اصلی» نمی‌تواند روی داده‌ی فعلی تست شود | `[INFERRED]` |
| E5 | برون‌بری مرجع ۹ سپتامبر است؛ با ۴ رابطۀ تازه و احتمالاً فیلدهای تازه فاصله دارد | `[VERIFIED]` |
