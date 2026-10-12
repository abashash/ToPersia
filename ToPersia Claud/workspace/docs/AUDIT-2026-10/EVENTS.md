# EVENTS — ممیزی ۲د: رویداد، تقویم، خبر

> ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ · فقط‌خواندنی (P5-D287). منبع حقیقت ساختار: `reference/Jetengine_Skin.json` (export JetEngine؛ **۱۳۵ رابطه** — نسخۀ محلی امروز ۱۳۹ رابطه دارد طبق `docs/AUDIT-RELATIONS-2F.md`، پس export کمی عقب است) + ممیزی محلی قبلی `docs/AUDIT-*-2F.*` (اجرای ۱۲ اکتبر، پایگاه‌داده محلی) که اینجا نقل می‌شود، نه بازتولید.
> برچسب‌ها: VERIFIED (مسیر + خط/شناسه) · INFERRED · ASSUMED · UNKNOWN. شمارش‌ها از خروجی PHP روی همان JSON.
> بخش‌های پایگاه‌داده‌ای: ۷ (شمار پست و داده‌های رویداد واقعی) — در «در انتظار تأیید محلی».

## ۰ — خلاصه

| | |
|---|---|
| نوع‌های رویداد | ۴ نوع دستۀ E (`tp_festival` E1 · `tp_sports_event` E3 · `tp_religious_event` E5 · `tp_seasonal_event` E6) + `tp_calendar` (P6) — همه `public`، `show_in_rest`، **VERIFIED** |
| فیلد تاریخ ساخت‌یافته | **هست** (پرچم P5-D214 «منتظر ساخت» کهنه است): `event_start_date`, `event_end_date` نوع `date` در E1 · E3 · E5؛ E6 هفتۀ اوج؛ P6 جدول تکرارشونده — **VERIFIED**. ولی: **نه رندر می‌شوند، نه به JSON-LD می‌روند** (بخش ۴) |
| رابطۀ رویداد ↔ موجودیت | ۳۸ رابطه در export؛ فقط ۲ رابطه (۲۳۲، ۲۳۳: استان/شهر ← جشنواره) در پایگاه محلی ردیف دارند (۳ ردیف هرکدام) — **VERIFIED** (export) + **INFERRED** (شمارش از ممیزی محلی ۱۲ اکتبر) |
| خبر | مدل `tpc_news` فقط محلی؛ بی نوع‌پست = هیچ قلاب یا مسیر فعال نیست؛ ثبت منبع و دریافت (۶ب) **ساخته نشده** |
| تقویم واحد، کاربر، پایش وب، درخواست تغییر، فرصت سفر | هیچ‌کدام در کد نیست؛ طبقه‌بندی در FEATURE-MAP.md |

## ۱ — نوع‌های رویداد و فیلدهایشان

همه از `reference/Jetengine_Skin.json`، `post_types[]` و `meta_boxes[]`. شمار فیلد = فیلدهای جعبۀ اختصاصی + ۲۰ فیلد مشترک `متاباکس مشترک` (`meta-1`: `name_fa name_en name_ar name_local alternative_names short_summary overview_text hero_image featured_image_gallery video_sample last_verified_date content_status entity_uid external_reference_uri audio_narration faq_items meta_title meta_description ai_summary source_references`).

| نوع | شناسه | اختصاصی | فیلدها (نام : نوع) |
|---|---|---|---|
| `tp_festival` E1 | ۲۲۲ | ۱۴ | `is_recurring`:switcher · `event_frequency`:select(واژه‌نامۀ ۲۱۳) · `calendar_timing`:textarea · `event_start_date`:date · `event_end_date`:date · `best_season`:select · `venue_name`:text · `organizer_name`:text · `official_event_url`:text · `program_highlights`:repeater · `event_travel_impact`:switcher (پرچم R23 به P6) · `visa_info_link`:text · `admission_note`:textarea · `tourist_suitability_note`:textarea |
| `tp_sports_event` E3 | ۲۲۳ | ۱۳ | `is_recurring` · `event_frequency` · `calendar_timing` · `event_start_date` · `event_end_date` · `best_season` · `venue_name` · `sport_type`:select · `competition_level`:select · `traditional_sports_reference`:repeater · `organizer_name` · `official_event_url` · `spectator_access_note`:textarea |
| `tp_religious_event` E5 | ۲۲۴ | ۱۶ | `calendar_timing` · `calendar_system`:select(قمری/شمسی) · `annual_date_rule`:text · `event_start_date` · `event_end_date` · `best_season` · `venue_name` · `ceremony_duration_days`:number · `crowd_scale`:select · `public_participation_note`:wysiwyg · `visitor_etiquette_note`:wysiwyg · `photography_policy`:select · `photography_note` · `dress_code_note` · `etiquette_link` · `safety_link` |
| `tp_seasonal_event` E6 | ۲۲۵ | ۱۳ | `season`:select · `best_season` · `natural_phenomenon_type`:select · `peak_start_week`:number · `peak_end_week`:number · `peak_window_note` · `calendar_timing` · `optimal_conditions`:wysiwyg · `weather_dependency`:select · `predictability_note` · `viewing_points`:repeater · `nearby_park_reference`:repeater · `safety_link` |
| `tp_calendar` P6 | ۲۲۰ | ۹ | `calendar_systems`:repeater · `calendar_system_note`:wysiwyg · `national_holidays`:repeater · `closure_impact_note` · `crowding_and_price_note` · `best_worst_time_note` · `official_source_link`:text · `annual_review_month`:number · `lunar_date_disclaimer`:textarea |

همه **VERIFIED** (پرچم‌ها و توضیح فیلدها: `meta_boxes[].meta_fields`، برای مثال شناسۀ فیلد `event_start_date` در E1 = ۸۹۵۵، E3 = ۵۴۱۶، E5 = ۶۵۸۰).

قواعد تعبیه‌شده در توضیح فیلدها (VERIFIED، از متن توضیح JSON):
- **CA-E-D005:** `venue_name` فقط وقتی پر شود که رابطۀ `held_at` خالی است؛ هرگز هر دو (E1 · E3 · E5).
- **CA-E-D001:** E6 هیچ `held_at` و هیچ `venue_name` ندارد؛ `viewing_points` و `nearby_park_reference` عمداً لایۀ محتواست، نه رابطه.
- **CA-E-D004:** `is_recurring` الزامی؛ خاموش یعنی «رویداد یک‌بار برگزارشده» که پس از پایان باید به آرشیو «رویداد گذشته» برود — **آرشیو رویداد گذشته در کد نیست** (بخش ۵).
- P6: «تاریخ‌های قمری هر سال جابه‌جا می‌شوند»؛ ردیف تعطیلی حذف نشود؛ لغوشده با «سطح تعطیلی = بدون تعطیلی» (P5-D109).
- E1/E3/E5: «تاریخ میلادی آغاز آخرین یا آتی‌ترین دوره… برای رویداد قمری هر سال باید به‌روزرسانی شود» ← **به‌روزرسانی دستی سالانه**؛ هیچ سازوکار خودکار نیست.

## ۲ — مدل تاریخ و تقویم

| پرسش | یافته | برچسب |
|---|---|---|
| تاریخ ساخت‌یافته (P5-D214) | موجود: `event_start_date`/`event_end_date` (نوع `date` جت‌انجین، تاریخ میلادی). **نام فیلدها با آنچه SPRINT می‌گوید فرق دارد**: پیوست ج می‌نویسد `next_start_date`/`next_end_date` (`docs/SPRINT-4.4-PHASES.md:1266`)؛ در کد و export هیچ‌جا `next_*` نیست (جست‌وجو در کل مخزن) | **VERIFIED** |
| نوع ذخیره‌سازی | `date` جت‌انجین بدون پرچم `is_timestamp` در export ← مقدار `Y-m-d` رشته (پیش‌فرض جت‌انجین) | **INFERRED** (JSON پرچم نمی‌دهد؛ تأیید محلی: `wp post meta get <id> event_start_date`) |
| تکرارشونده | `is_recurring` + `event_frequency` (واژه‌نامه) فقط برای E1/E3؛ E5 همیشه سالانه (بی `is_recurring`، با `annual_date_rule` متن)؛ E6 با هفتۀ سال (`peak_*_week`) | **VERIFIED** |
| تقویم شمسی/قمری | `calendar_system` (E5) و `calendar_systems` (P6) فقط برچسب؛ تبدیل شمسی ← میلادی در رندر هست (`topersia_g_jalali( $gy,$gm,$gd )` — `plugin/topersia-render/includes/topersia-g-zones.php:1100-1103,1203`) فقط برای **نمایش** تاریخ گریگوری، نه برای محاسبۀ رویداد قمری | **VERIFIED** |
| محاسبۀ تاریخ قمری/شمسی هر سال | ندارد؛ کتابخانۀ هجری قمری یا مخزن تقویم نیست (`grep -i "hijri\|IntlDateFormatter\|jalali" plugin/` فقط همان تبدیل نمایشی) | **VERIFIED** |
| «مخزن تقویم فارسی که Master Audit به آن اشاره می‌کند» (پرسش ۱ ARCH-RECONCILIATION) | در سند و کد **اسم مخزن** نیامده؛ پرسش باز می‌ماند | **UNKNOWN** — ورودی کاربر لازم |
| بایگانی «رویداد گذشته» | وضعیتی/نمایی در کد نیست؛ نه `post_status` سفارشی، نه جست‌وجو بر پایۀ `event_end_date < today` | **VERIFIED** (`grep event_end_date` در `plugin/` صفر) |
| منطقۀ زمانی / ساعت | فیلد ساعت وجود ندارد؛ فقط تاریخ | **VERIFIED** |

## ۳ — رابطۀ رویداد ↔ موجودیت

۳۸ رابطه از ۱۳۵ در export یک سرشان نوع رویداد/تقویم است (**VERIFIED**، شمارش PHP روی `relations[]`؛ همه `many_to_many`). همگی با `rest_get_enabled=false` و بی کنترل مدیریتی `parent_manager/child_manager`.

| الگوی نام | معنا | رابطه‌ها (شناسه) | نوع والد |
|---|---|---|---|
| `held_in_g3/g5/g7_e{1,3,5,6}` | رویداد در استان/شهر/روستا برگزار می‌شود | ۲۳۲ · ۲۳۳ · ۲۳۴؛ ۲۴۵ · ۲۴۶ · ۲۴۷؛ ۲۵۳ · ۲۵۴ · ۲۵۵؛ ۲۶۳ · ۲۶۴ · ۲۶۵ | `tp_province` · `tp_city` · `tp_village` |
| `held_at_a2/a3/a7/a9/g7_e{1,3,5}` | رویداد در بنا/مکان‌مذهبی/موزه/پارک/روستا | ۲۳۵–۲۳۹؛ ۲۴۸–۲۵۲؛ ۲۵۶–۲۵۸ | `tp_monument` · `tp_religious_site` · `tp_museum` · `tp_protected_area` · `tp_village` |
| `celebrates_c{10,12,13}_e{1,5}` | رویداد گرامی‌دار آیین/شخصیت/دوره | ۲۴۱ · ۲۴۲ · ۲۴۳؛ ۲۶۰ · ۲۶۱ · ۲۶۲ | `tp_ritual` · `tp_figure` · `tp_historical_era` |
| `instance_c10_e{1,5}` | رویداد نمونه‌ای از آیین | ۲۴۰ · ۲۵۹ | `tp_ritual` |
| `affects_p6_e1` | تقویم رسمی بر رویداد اثر دارد | ۲۴۴ | `tp_calendar` ← `tp_festival` |
| `affects_p6_c10` | تقویم بر آیین اثر دارد | ۲۳۱ | `tp_calendar` ← `tp_ritual` |
| `applies_g{1,3,5}_p6` | تقویم برای کشور/استان/شهر | ۲۲۸ · ۲۲۹ · ۲۳۰ | `tp_country` · `tp_province` · `tp_city` |

(**VERIFIED**: export. `held_in_g7_e6`: روستا، نوع‌های `tp_seasonal_event` تنها `held_in` دارد، `held_at` ندارد — مطابق CA-E-D001.)

وضعیت زنده (ممیزی محلی ۱۲ اکتبر، `docs/AUDIT-RELATIONS-2F.csv`، نقل نه بازتولید — **INFERRED**):
- از ۳۸ رابطۀ مرتبط با رویداد، **فقط ۲** ردیف دارند: ۲۳۲ (استان ← جشنواره) و ۲۳۳ (شهر ← جشنواره)، هرکدام ۳ ردیف.
- جهت «فرزند-در-صفحۀ-والد»: برای رابطه‌های ۲۳۲/۲۳۳ در فایل `children_in_parent_page = culture` (صفحۀ استان/شهر، بخش فرهنگ، جشنواره را نشان می‌دهد)، ولی جهت معکوس (والد-در-صفحۀ-رویداد) نمایش داده نمی‌شود: «جشن گلاب‌گیری کاشان» (#۱۹۶۷) شش مرتبط منتشرشده دارد و صفر پیوند در HTML (`docs/AUDIT-SAMPLES-2F.json`: `related_published: 6, linked: 0`) — **INFERRED** (نمونۀ محلی).
- هیچ نوع رویداد/تقویم در `topersia_g_relmap()` ثبت‌نامه ندارد (`child_type_has_relmap = n` برای همۀ ۳۸ رابطه؛ شمارش از CSV) ← رویداد از اکثر روابطش در صفحه چیزی نشان نمی‌دهد.
- رابطه‌های روی E6: `held_in` فقط به استان/شهر/روستا؛ نقشۀ شِما هر چهار نوع E را از راه استان می‌خواند (`$via_prov(232|245|253|263)` در `plugin/topersia-schema/topersia-schema.php:334-336`) — **VERIFIED**.

## ۴ — رندر و JSON-LD

| | |
|---|---|
| فیلدهای تاریخ رویداد در رندر | **جا ندارند.** در ممیزی محلی `docs/AUDIT-META-2F.csv`: برای E1 `is_recurring`, `calendar_timing`, `event_start_date`, `event_end_date`؛ E3 همان‌ها؛ E5 `calendar_timing`, `event_start_date`, `event_end_date`؛ E6 `calendar_timing`؛ P6 `national_holidays` — همه «بی‌جا». در `reference/TP-G-FIELDMAP.json` و در `plugin/` هیچ ارجاعی به `event_start_date`/`event_end_date`/`is_recurring`/`national_holidays` نیست | **VERIFIED** (grep) + **INFERRED** (CSV محلی) |
| تعداد متای بی‌جا در هر نوع | E1 ۱۲ · E3 ۹ · E5 ۱۱ · E6 ۹ · P6 ۹ (در برابر ۱۸ تا ۲۳ متای «جا دارد» برای هر نوع) | **INFERRED** (شمارش CSV) |
| JSON-LD | هر چهار نوع E `Thing` با `additionalType` (`Festival`، `SportsEvent`، `Event`، `Event`)، **بی `startDate`**؛ دلیل در کد: «Event بی startDate در Search Console نامعتبر است و دادۀ ما تاریخ ماشین‌خوان ندارد (calendar_timing متن است)» | `plugin/topersia-schema/topersia-schema.php:255-257,334-336` — **VERIFIED** |
| تناقض | این استدلال با export امروز **نمی‌خواند**: `event_start_date`/`event_end_date` نوع `date` هستند. شرط راه‌اندازی `Event` + `startDate` عملاً از نظر مدل داده مهیاست؛ مانع واقعی: (۱) داده پر شده؟ (UNKNOWN، بخش ۷)، (۲) خواندن از دروازۀ `topersia_g_raw`، (۳) قاعدۀ «Event بی startDate نامعتبر» برای رویدادهای بی تاریخ | **VERIFIED** (تناقض) + **UNKNOWN** (وضعیت داده) |
| صفحۀ تقویم / «رویداد آینده» / فیلتر زمانی | نیست | **VERIFIED** |
| اسلایدر یا کارت «رویدادها» روی صفحۀ استان/شهر | بخش `culture` فهرست فرزندان را نشان می‌دهد (از رابطۀ ۲۳۲/۲۳۳) بی تاریخ | **INFERRED** (CSV محلی) |

## ۵ — چرخۀ عمر رویداد (Master Audit §۵)

| مرحله | امروز | |
|---|---|---|
| پیشنهاد/ایجاد | درون‌ریزی بسته: پست تازه `draft` + `needs_verification` (P5-D272) — برای رویداد هم صدق می‌کند | **VERIFIED** (`plugin/topersia-core/includes/topersia-pack.php`، قاعده در `CLAUDE.md`) |
| تأیید انسانی | `content_status` (واژه‌نامۀ ۳۷: تأییدشده / نیازمند بازبینی / منسوخ / دادۀ اولیه) و `last_verified_date` | **VERIFIED** (JSON) |
| انتشار | وردپرس | — |
| جاری/آینده/گذشته | محاسبه نمی‌شود | **VERIFIED** |
| انقضا/بایگانی | نیست | **VERIFIED** |
| ردپای تغییر (provenance) | `source_references` (repeater) و `external_reference_uri`؛ بی سابقۀ تغییر | **VERIFIED** |
| مقصد WPML | `content_status` و فیلدهای copy/translate طبق `wpml-config` | **VERIFIED** (ref. در CLAUDE.md) |

## ۶ — خبر، ثبت منبع و وضعیت ۶ب

| | |
|---|---|
| مدل خبر | `tpc_news` (نوع‌پست)، `news_source_name`, `news_source_url`، چهار رابطه با استان/شهر/روستا/جزیره، taxonomy `tpc_news_topic` — **فقط محلی**؛ CHANGE-PACKAGE-001 بخش ۷ | **VERIFIED** (کامنت `plugin/topersia-render/includes/topersia-g-news.php:3-12`) |
| کد رندر خبر | کاملاً پشت `topersia_g_news_on()` = `post_type_exists( 'tpc_news' )`؛ روی نصب‌شده/زنده بی اثر | `topersia-g-news.php:30-32` — **VERIFIED** |
| `tpc_news` در export | **نیست** (`post_types[]` فقط ۴۲ نوع `tp_*`؛ جست‌وجوی `tpc_` در `Jetengine_Skin.json` صفر) ← مدل خبر فقط در پایگاه محلی است و export را عقب‌تر نشان می‌دهد | **VERIFIED** |
| ثبت منبع (نام، نوع RSS/API/sitemap، نشانی، بازه، آخرین دریافت، خطا) | **ساخته نشده**: نه صفحۀ پیشخوان، نه گزینه، نه جدول، نه صف | **VERIFIED** (`grep -rniE "rss|feed_url|fetch_feed|monitor"` در هسته: بی نتیجۀ مرتبط) |
| دریافت زمان‌بندی‌شده | نیست؛ صف AS (`topersia_queue_add`) فقط مصرف‌کنندۀ گالری دارد | **VERIFIED** (SYSTEM.md بخش ۳) |
| خراش HTML | عمداً ساخته نمی‌شود (P5-D273) | **VERIFIED** (سند) |
| تشخیص تکراری / تغییر / اختلاف منابع | نیست؛ تاکردن جست‌وجو (`topersia_search_fold_rules`) پایۀ آماده برای «شباهت عنوان تاشده» | `plugin/topersia-core/includes/topersia-fold.php` — **VERIFIED** (وجود پایه) |
| درخواست تغییر با تأیید انسانی (P5-D296) | نیست؛ بستۀ محتوا (P5-D272) «پیش‌نویس + برگرداندن» دارد: نزدیک‌ترین بخش ساخته‌شده | `topersia-pack.php` — **VERIFIED** |
| `ai.text` برای «پیش‌نویس خلاصه» | در کد مصرف‌کننده ندارد | **VERIFIED** |
| تقویم کاربر | تب داشبورد «تقویم و روز» در طرح P5-D303؛ کد ندارد؛ `ToPersia_Dashboard` تب‌های `overview profile saved following trips reviews notifications settings` | `plugin/topersia-user-core/includes/class-topersia-dashboard.php:15-26` — **VERIFIED** |

وضعیت ۶ب برای معمار: **۰٪ ساخته**. پیش‌نیازهای موجود: صف AS، کش مرکزی، لاگ، مدل `tpc_news` محلی، نمایۀ تاکردن. پیش‌نیازهای ناموجود: ثبت منبع، آداپتور دریافت، کرون سیستمی زنده (UNKNOWN)، جدول/نوع «درخواست تغییر»، مجوز/ToS هر منبع.

## ۷ — داده (فقط ارجاع؛ DATA.md ساخته نمی‌شود)

- از `docs/GEO-QUALITY-2F.json` (اجرای محلی، منتشرشدۀ زبان پیش‌فرض): `tp_festival` ۱ پست؛ `tp_calendar` ۱ پست؛ `tp_sports_event` · `tp_religious_event` · `tp_seasonal_event` در فهرست نیستند ← صفر پست منتشرشده — **INFERRED** (فایل فقط نوع‌های دارای پست را دارد).
- مقدار واقعی `event_start_date` در این دو پست و ردیف‌های `national_holidays` پر شده یا نه: **UNKNOWN**. محلی: `wp post list --post_type=tp_festival,tp_calendar --fields=ID,post_title,post_status` و برای هر کدام `wp post meta get <id> event_start_date` و `wp post meta get <id> national_holidays --format=json` (اولی فقط تاریخ، دومی فقط شمار سطر لازم است).
- ردیف‌های دو رابطۀ پرداده ۲۳۲/۲۳۳ (۶ ردیف) — **INFERRED** (CSV محلی)؛ تازه‌سازی: `wp db query "SELECT rel_id, COUNT(*) FROM {prefix}jet_rel_default GROUP BY rel_id"` (نام جدول رابطه به تنظیم `db_table` وابسته است).

## ۸ — شکاف با Master Audit & Expansion

| بخش سند | امروز |
|---|---|
| ۱ موجودیت واحد | `entity_uid` (فیلد مشترک) — **هست** |
| ۲ تقویم و رویداد واحد (اصلی + کاربر) | تقویم اصلی نیست؛ تقویم کاربر نیست |
| ۳ سیستم رویداد | ۴ نوع E + P6 — **هست** (ساختار)؛ نمایش و بازیابی زمانی نیست |
| ۴ نمایش پویا | نیست |
| ۵ چرخۀ عمر | بخش ۵: بخشی (`content_status`) |
| ۶ تقویم فارسی | تبدیل نمایشی فقط؛ مخزن UNKNOWN |
| ۷ ایجاد رویداد توسط مدیر | از راه رابط جت‌انجین؛ فرم اختصاصی/بسته: درون‌ریزی بسته |
| ۸–۱۸ پایش وب | نیست (بخش ۶) |
| ۱۹ کشور کاربر | USER.md |
| ۲۰–۲۲ پروفایل علاقه، فرصت سفر | نیست |
| ۲۳–۲۶ پیامک و اعلان | نیست؛ تنها پیامک OTP (USER.md) |
| ۲۷ پنل کاربر | نیمه (تب‌های خالی) |
| ۲۸–۳۲ Iran Right Now / Compare / Ask / Lens / Food | نیست |
| ۳۶–۴۰ چندزبانگی، سئو، کارایی، امنیت، حقوقی | WPML-aware، JSON-LD بی تاریخ، کش مرکزی؛ حقوقی UNKNOWN |

## ۹ — حکم ARCH-RECONCILIATION §۳

| قابلیت | حکم معمار | شاهد |
|---|---|---|
| تقویم و رویداد واحد | READY WITH MODIFICATIONS؛ «تاریخ ساخت‌یافته پیش‌نیاز است» | **اصلاح**: مدل تاریخ هست (بخش ۲)؛ پیش‌نیاز واقعی «پر کردن داده + رندر + JSON-LD + آرشیو» است، نه «ساخت فیلد» |
| رویداد دستی و اتصال به موجودیت | READY WITH MODIFICATIONS | **هم‌خوان**: ۳۸ رابطه؛ مشکل نمایش جهت معکوس است |
| عامل پایش وب | API/RSS: RWM؛ خزش JS: NEEDS INFRASTRUCTURE | **هم‌خوان**: صف و کش هست؛ ثبت منبع نیست |
| درخواست تغییر + تأیید انسانی + منشأ | READY WITH MODIFICATIONS | **هم‌خوان با تذکر**: بستۀ محتوا نزدیک‌ترین بخش است |
| پایش مشترک با خبر | یک ثبت منبع | **هم‌خوان** — هنوز هیچ‌کدام نیست |
| مخزن تقویم فارسی | پرسش | **UNKNOWN** |

## ۱۰ — UNKNOWN و فرمان محلی

| # | موضوع | فرمان |
|---|---|---|
| ۱ | شمار پست هر نوع رویداد (فارسی + ترجمه) | `wp post list --post_type=tp_festival,tp_sports_event,tp_religious_event,tp_seasonal_event,tp_calendar --post_status=any --fields=ID,post_type,post_status --format=count` و تفکیک با WPML |
| ۲ | پر بودن `event_start_date`/`event_end_date` و `calendar_timing` | `wp db query "SELECT meta_key, COUNT(*) FROM {prefix}postmeta WHERE meta_key IN ('event_start_date','event_end_date','calendar_timing','is_recurring','annual_date_rule') AND meta_value<>'' GROUP BY meta_key"` |
| ۳ | قالب ذخیرۀ تاریخ | `wp post meta get <id> event_start_date` روی یک رویداد پرشده |
| ۴ | وجود `tpc_news` در محلی و شمار | `wp post-type list --fields=name \| grep tpc`; `wp post list --post_type=tpc_news --format=count` |
| ۵ | مخزن تقویم فارسی (سؤال معمار) | پاسخ کاربر |
| ۶ | همگامی export با پایگاه محلی (۱۳۵ در برابر ۱۳۹ رابطه) | export تازۀ جت‌انجین (رابط) و diff با `reference/Jetengine_Skin.json` |
