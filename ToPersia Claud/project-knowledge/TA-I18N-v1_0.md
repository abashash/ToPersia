# ToPersia — Technical Architecture: Field-Level Multilingual Matrix
## TA-I18N-v1.0
### مرحله ۴.۲ فاز ۴ — ماتریس چندزبانگی سطح فیلد برای ۹ زبان
#### مرجع: IA-v1.2.1-FINAL (Frozen) · CA-G/A/D/C/E/P v1.2 · TA-VOL-v1.0 (Audited) · TA-ROADMAP-v1.0

---

## ۰. متادیتا و انطباق

| بُعد | وضعیت |
|------|--------|
| Architecture Freeze | ✅ هیچ Entity/Relationship/Constraint/فیلد جدید — این سند فقط فیلدهای موجود CA را طبقه‌بندی می‌کند |
| روش استخراج | ✅ اسکریپت Fail-Loud (میراث CA-C-D015) — هیچ حدس بی‌صدا؛ هر نوع ناشناخته = توقف |
| لنگر اعتبارسنجی تاریخی | ✅ CA-C = **۱۵۸ فیلد** — دقیقاً برابر Parity Check مستند در PHASE3-VALIDATION-LOG-v1.2 |
| پوشش | ✅ **۶۹۷ فیلد** از ۴۱ Entity دارای فیلد + ساختارهای D1–D6؛ A1 طبق I5/P-07 جدول فیلد ندارد |
| انواع ناشناخته | ✅ صفر — همه ۶۹۷ فیلد با قاعده صریح طبقه‌بندی شدند |
| تصمیم سرمقاله‌ای جدید | ✅ هیچ — پارامترهای برآوردی به‌صورت F-I18N-xx ثبت شدند |

---

## ۱. روش‌شناسی

**استخراج:** پیمایش خطی شش سند CA؛ شناسایی موجودیت جاری از عناوین `### X# —`؛ شناسایی بخش Required/Optional از هر دو الگوی موجود (بولد و هدینگ)؛ استخراج ردیف‌های جداول `| فیلد | نوع |`. جدول متادیتای ابتدای اسناد (`| فیلد | مقدار |`) به‌صورت ساختاری حذف شد.

**طبقه‌بندی:** هر فیلد بر اساس ستون «نوع» با قواعد صریح و ترتیب‌دار به یکی از ۱۰ کلاس نگاشت شد. قاعده Fail-Loud: هر نوعی که با هیچ قاعده‌ای match نشود، به‌جای حدس، فرآیند را متوقف می‌کند (در اجرای نهایی: صفر مورد).

**واحد اندازه‌گیری جدید این سند:** «رکورد فیلد×زبان» — دقیق‌تر از واحد «صفحه×زبان» در TA-VOL، چون حجم واقعی جدول ترجمه در هر مدل ذخیره‌سازی را نشان می‌دهد.

## ۲. تعریف ده کلاس و قاعده ذخیره‌سازی هرکدام

| کلاس | تعریف | قاعده ذخیره‌سازی ۹زبانه | تعداد کل |
|-------|--------|--------------------------|-----------|
| **STRUCT** — رابطه گرافی — زبان‌مستقل | | یک نسخه — هرگز ترجمه نمی‌شود (یال گراف) | 82 |
| **KEY** — کلید کنترل‌شده — مقدار مستقل، لیبل واژه‌نامه‌ای | | مقدار: یک نسخه؛ لیبل نمایشی: واژه‌نامه مرکزی ×۹ (نه per-record) | 103 |
| **VALUE** — مقداری — زبان‌مستقل | | یک نسخه | 64 |
| **URI** — شناسه/پیوند — زبان‌مستقل | | یک نسخه (استثنای منابع بومی‌شده: override اختیاری) | 10 |
| **MEDIA** — رسانه — فایل مستقل + متادیتای وابسته | | فایل: یک نسخه؛ alt/caption: ×۹ (سیاست F003) | 60 |
| **NAME** — نام (جفت fa/en فعلی) — خانواده ترجمه‌پذیر | | خانواده ترجمه‌پذیر — تعمیم جفت fa/en به ۹ زبان (یافته F001) | 80 |
| **SEO** — متادیتای SEO — زبان‌وابسته ×۹ | | ×۹ — تولید مستقل per-locale | 92 |
| **TEXT** — متن محتوایی — زبان‌وابسته ×۹ | | ×۹ — هسته بار ترجمه | 187 |
| **HYBRID_CL** — قرارداد Content Layer (نام+URL) — ترکیبی | | جزء نام: ×۹؛ جزء URL: یک نسخه | 15 |
| **HYBRID_ST** — Structured مرکب — اجزای مخلوط | | کلیدها/مقادیر: یک نسخه؛ اجزای توصیفی: ×۹ (یافته F005) | 4 |
| **جمع** | | | **697** |

**نسبت کلیدی:** از ۶۹۷ فیلد، **378 فیلد (54٪)** دارای مؤلفه زبان‌وابسته‌اند و **319 فیلد (45٪)** زبان‌مستقل (یا فایل) هستند. این تفکیک، همان دیواری است که الگوی خطای Relation/Content-Layer Drift (ریسک ۱ Handover) باید در سطح Field-Type پیاده‌سازی به آن بخورد.

## ۳. آمار تجمیعی به تفکیک دسته

| دسته | STRUCT | KEY | VALUE | URI | MEDIA | NAME | SEO | TEXT | HYBRID_CL | HYBRID_ST | جمع | فیلدهای وابسته/موجودیت (میانگین) |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **G** — جغرافیا | 23 | 38 | 29 | 0 | 11 | 24 | 24 | 39 | 0 | 0 | 188 | 5.8 |
| **A** — جاذبه‌ها | 21 | 38 | 18 | 0 | 12 | 14 | 14 | 32 | 3 | 0 | 152 | 7.8 |
| **D** — عناوین رسمی | 1 | 2 | 7 | 0 | 2 | 0 | 12 | 13 | 0 | 0 | 37 | 4.2 |
| **C** — فرهنگ و میراث | 22 | 13 | 4 | 8 | 20 | 20 | 20 | 51 | 0 | 0 | 158 | 8.1 |
| **E** — رویدادها | 12 | 8 | 0 | 0 | 8 | 8 | 8 | 18 | 6 | 0 | 68 | 8.2 |
| **P** — اطلاعات کاربردی | 3 | 4 | 6 | 2 | 7 | 14 | 14 | 34 | 6 | 4 | 94 | 8.6 |

## ۴. بار ترجمه در افق ۵ ساله — سطح فیلد (اتصال به TA-VOL)

مدل: `رکورد فیلد×زبان = Σ (وزن فیلدهای وابسته هر موجودیت × صفحات مؤثر ۵ساله × ضریب زبانی)`
وزن وابسته = TEXT + SEO + NAME(۱ خانواده) + ۰.۵×HYBRID. صفحات مؤثر = صفحات TA-VOL × نرخ تکمیل (F-I18N-01: ۸۵٪ صفحات کامل؛ G7 حداقلی ۴۰٪).

| سناریو | ضریب زبانی | رکورد فیلد×زبان ۵ساله |
|---------|-------------|------------------------|
| محافظه‌کار | ×۲ | ~262،551 |
| **مبنا (TA-VOL)** | ×۴.۹ | **~643،251** |
| سقف نظری | ×۹ بدون نرخ تکمیل | ~1،292،355 |

**ده موجودیت با بیشترین سهم از بار ترجمه (سناریو مبنا):**

| موجودیت | وزن فیلد وابسته | صفحات ۵س | سهم رکورد (×۴.۹) | ٪ از کل |
|---|---|---|---|---|
| A2 | 10.0 | 4،500 | ~187،425 | 29٪ |
| A3 | 9.0 | 2،500 | ~93،712 | 15٪ |
| A10 | 6.0 | 2،200 | ~54،978 | 9٪ |
| G5 | 9.0 | 1،450 | ~54،353 | 8٪ |
| G7 | 4.0 | 0 | ~45،178 | 7٪ |
| C2 | 8.0 | 1،200 | ~39،984 | 6٪ |
| A7 | 9.5 | 700 | ~27،697 | 4٪ |
| A12 | 6.5 | 1،000 | ~27،073 | 4٪ |
| C12 | 8.0 | 500 | ~16،660 | 3٪ |
| E1 | 8.5 | 450 | ~15،931 | 2٪ |

## ۵. ماتریس کامل — ۶۹۷ فیلد × کلاس زبانی

*ستون‌ها: فیلد · نوع (از CA، عیناً) · کلاس · Required/Optional. مرجع هر ردیف، سند CA همان دسته است.*

### دسته G — جغرافیا

#### G1 — 20 فیلد · وزن وابسته 13.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| official_name_en | Text | TEXT | R |
| official_name_fa | Text | TEXT | R |
| capital_city | Relation → G5 | STRUCT | R |
| geographic_coordinates | GeoShape | VALUE | R |
| population | Number | VALUE | R |
| area_km2 | Number | VALUE | R |
| official_language | Text | TEXT | R |
| currency_note | Text (اطلاعات ارز از P2 نمایش داده می‌شود) | TEXT | R |
| timezone | Text | TEXT | R |
| overview_text | LongText (800+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| alternative_names | Text[] | TEXT | O |
| neighboring_countries | Text[] (informational — خارج از گراف) | TEXT | O |
| government_type | Text | TEXT | O |
| internet_tld | Text | TEXT | O |
| calling_code | Text | TEXT | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### G2 — 12 فیلد · وزن وابسته 6.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| region_classification | Enum (Natural/Historical/Cultural/Climate) | KEY | R |
| geographic_boundary | GeoShape | VALUE | R |
| provinces_included | Relation[] → G3 | STRUCT | R |
| overview_text | LongText (400+ words) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| climate_overview | Text | TEXT | O |
| historical_context | Text | TEXT | O |
| best_season | Enum | KEY | O |

#### G3 — 18 فیلد · وزن وابسته 7.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| province_code | Text | TEXT | R |
| capital_city | Relation → G5 | STRUCT | R |
| geographic_region | Relation → G2 | STRUCT | R |
| located_in | Relation → G1 | STRUCT | R |
| area_km2 | Number | VALUE | R |
| population | Number | VALUE | R |
| geo_coordinates | GeoShape | VALUE | R |
| overview_text | LongText (600+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| bordering_provinces | Relation[] → G3 (R04) | STRUCT | O |
| bordering_countries | Text[] (informational) | TEXT | O |
| climate_type | Text | TEXT | O |
| free_zone | Boolean | KEY | O |

#### G4 — 4 فیلد · وزن وابسته 1.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 | STRUCT | R |
| capital_city | Relation → G5 | STRUCT | R |

#### G5 — 23 فیلد · وزن وابسته 9.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G4 | STRUCT | R |
| geo_coordinates | GeoCoordinates (Point) | VALUE | R |
| population | Number | VALUE | R |
| area_km2 | Number | VALUE | R |
| elevation_m | Number | VALUE | R |
| overview_text | LongText (800+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| transport_access | Text | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| alternative_names | Text[] | TEXT | O |
| founded_year | Text | TEXT | O |
| historical_period | Relation → C13 | STRUCT | O |
| associated_figures | Relation[] → C12 | STRUCT | O |
| airport_name | Text | TEXT | O |
| airport_iata | Text | TEXT | O |
| railway_station | Boolean | KEY | O |
| unesco_creative_city | Boolean | KEY | O |
| free_zone | Boolean | KEY | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### G6 — 10 فیلد · وزن وابسته 4.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G5 | STRUCT | R |
| neighborhood_type | Enum | KEY | R |
| overview_text | LongText (300+ words) | TEXT | R |
| geo_coordinates | GeoCoordinates | VALUE | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| historical_period | Relation → C13 | STRUCT | O |
| best_season | Enum (R25) | KEY | O |

#### G7 — 12 فیلد · وزن وابسته 4.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 یا G4 | STRUCT | R |
| nearest_city | Relation → G5 | STRUCT | R |
| geo_coordinates | GeoCoordinates | VALUE | R |
| tourism_target_village | Boolean | KEY | R |
| overview_text | LongText (250+ words) | TEXT | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| population | Number | VALUE | O |
| elevation_m | Number | VALUE | O |
| best_season | Enum (R25) | KEY | O |

#### G8 — 13 فیلد · وزن وابسته 5.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 | STRUCT | R |
| island_type | Enum (Persian Gulf / Caspian / Oman Sea) | KEY | R |
| area_km2 | Number | VALUE | R |
| geo_coordinates | GeoCoordinates | VALUE | R |
| overview_text | LongText (500+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| access_method | Text | TEXT | R |
| free_zone | Boolean | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |

#### G9 — 16 فیلد · وزن وابسته 5.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 (primary) | STRUCT | R |
| mountain_type | Enum (Peak / Range / Volcano) | KEY | R |
| elevation_m | Number | VALUE | R |
| geo_coordinates | GeoCoordinates | VALUE | R |
| overview_text | LongText (400+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| provinces_spanning | **Text[]** | TEXT | O |
| range_parent | Relation → G9 | STRUCT | O |
| trekking_difficulty | Enum | KEY | O |
| volcanic | Boolean | KEY | O |
| national_monument | Boolean | KEY | O |

#### G10 — 16 فیلد · وزن وابسته 5.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 (primary) | STRUCT | R |
| water_body_type | Enum (Sea/Lake/River/Wetland) | KEY | R |
| geo_coordinates | GeoCoordinates یا GeoShape | VALUE | R |
| overview_text | LongText (400+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| provinces_spanning | **Text[]** | TEXT | O |
| area_km2 | Number | VALUE | O |
| length_km | Number | VALUE | O |
| max_depth_m | Number | VALUE | O |
| salinity_type | Enum | KEY | O |
| biosphere_reserve | Boolean | KEY | O |

#### G11 — 16 فیلد · وزن وابسته 5.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 (primary) | STRUCT | R |
| desert_type | Enum (Salt/Sandy/Mixed) | KEY | R |
| area_km2 | Number | VALUE | R |
| geo_coordinates | GeoShape | VALUE | R |
| overview_text | LongText (500+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| provinces_spanning | **Text[]** | TEXT | O |
| world_heritage | Boolean | KEY | O |
| biosphere_reserve | Boolean | KEY | O |
| national_monument | Boolean | KEY | O |
| max_temperature_c | Number | VALUE | O |

#### G12 — 16 فیلد · وزن وابسته 5.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 (primary) | STRUCT | R |
| area_type | Enum (Forest/Plain) | KEY | R |
| area_km2 | Number | VALUE | R |
| geo_coordinates | GeoShape | VALUE | R |
| overview_text | LongText (400+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| provinces_spanning | **Text[]** | TEXT | O |
| forest_type | Enum (Hyrcanian / Zagros / ...) | KEY | O |
| world_heritage | Boolean | KEY | O |
| biosphere_reserve | Boolean | KEY | O |
| national_monument | Boolean | KEY | O |

#### G13 — 12 فیلد · وزن وابسته 6.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 | STRUCT | R |
| crossing_type | Enum (Road/Rail) | KEY | R |
| border_country | Text | TEXT | R |
| status | Enum (Active/Limited/Closed) | KEY | R |
| operating_hours | Text | TEXT | R |
| geo_coordinates | GeoCoordinates | VALUE | R |
| nearest_city | Relation → G5 | STRUCT | R |
| overview_text | LongText (200+ words) | TEXT | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |

### دسته A — جاذبه‌ها

#### A2 — 26 فیلد · وزن وابسته 10.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G5 یا G7 | STRUCT | R |
| classification | Enum (Caravanserai / Historic Bridge / Historic T… | KEY | R |
| geo_coordinates | GeoCoordinates (Point) | VALUE | R |
| historical_period | Relation → C13 | STRUCT | R |
| venue_eligible | Boolean | KEY | R |
| overview_text | LongText (600+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| name_historical | Text | TEXT | O |
| builder | Relation → C12 | STRUCT | O |
| associated_figures | Relation[] → C12 | STRUCT | O |
| buried_figures | Relation[] → C12 | STRUCT | O |
| world_heritage | Boolean | KEY | O |
| world_heritage_year | Number | VALUE | O |
| world_heritage_criteria | Text | TEXT | O |
| national_registration | Boolean | KEY | O |
| visiting_hours | Text | TEXT | O |
| admission_fee | Text | TEXT | O |
| accessibility | Text | TEXT | O |
| restoration_status | Enum (Active / Restored / Restricted) | KEY | O |
| a1_classification | Text[] | TEXT | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### A3 — 24 فیلد · وزن وابسته 9.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G5 یا G7 | STRUCT | R |
| classification | Enum (Mosque / Church / Temple / Fire Temple / Sy… | KEY | R |
| religion | Text | TEXT | R |
| geo_coordinates | GeoCoordinates (Point) | VALUE | R |
| etiquette_compliance | Relation → P5 | STRUCT | R |
| overview_text | LongText (500+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| name_religious | Text | TEXT | O |
| historical_period | Relation → C13 | STRUCT | O |
| associated_figures | Relation[] → C12 | STRUCT | O |
| world_heritage | Boolean | KEY | O |
| national_registration | Boolean | KEY | O |
| active_worship | Boolean | KEY | O |
| active_pilgrimage | Boolean | KEY | O |
| dress_code_details | Text | TEXT | O |
| visiting_hours | Text | TEXT | O |
| admission_fee | Text | TEXT | O |
| photography_policy | Enum (Allowed / Restricted / Prohibited) | KEY | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### A7 — 17 فیلد · وزن وابسته 9.5

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G5 | STRUCT | R |
| classification | Enum (Art Gallery / House Museum / General Museum) | KEY | R |
| geo_coordinates | GeoCoordinates (Point) | VALUE | R |
| overview_text | LongText (400+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| housed_in | Text (نام A2 + URL) | HYBRID_CL | O |
| founded_year | Text | TEXT | O |
| visiting_hours | Text | TEXT | O |
| admission_fee | Text | TEXT | O |
| collection_categories | Text[] | TEXT | O |
| permanent_highlights | Text[] | TEXT | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### A9 — 23 فیلد · وزن وابسته 7.5

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 (primary) | STRUCT | R |
| park_type | Enum (National Park / Wildlife Refuge / Protected… | KEY | R |
| area_km2 | Number | VALUE | R |
| geo_coordinates | GeoShape (Area) | VALUE | R |
| overview_text | LongText (500+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| safety_link | Text (نام+URL به P4) | HYBRID_CL | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| provinces_spanning | Text[] | TEXT | O |
| managed_area | Boolean | KEY | O |
| geopark | Boolean | KEY | O |
| geopark_year | Number | VALUE | O |
| biosphere_reserve | Boolean | KEY | O |
| biosphere_year | Number | VALUE | O |
| world_heritage | Boolean | KEY | O |
| national_monument | Boolean | KEY | O |
| entry_permit_required | Boolean | KEY | O |
| visiting_hours | Text | TEXT | O |
| key_species | Text[] | TEXT | O |

#### A10 — 20 فیلد · وزن وابسته 6.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 یا G5 | STRUCT | R |
| classification | Enum (Waterfall / Cave / Spring / Canyon-Gorge / … | KEY | R |
| geo_coordinates | GeoCoordinates (Point) | VALUE | R |
| overview_text | LongText (400+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| managed_area_ref | Relation → A9 | STRUCT | O |
| national_monument | Boolean | KEY | O |
| world_heritage | Boolean | KEY | O |
| height_m | Number | VALUE | O |
| depth_m | Number | VALUE | O |
| length_km | Number | VALUE | O |
| area_km2 | Number | VALUE | O |
| water_temperature | Text | TEXT | O |
| safety_notes | Text | TEXT | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### A12 — 23 فیلد · وزن وابسته 6.5

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G5 یا G3 | STRUCT | R |
| geo_coordinates | GeoCoordinates یا GeoShape | VALUE | R |
| historical_period | Relation → C13 | STRUCT | R |
| excavation_status | Enum (Active / Completed / Ongoing / Unexcavated) | KEY | R |
| has_standing_structure | Boolean | KEY | R |
| overview_text | LongText (600+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| name_historical | Text | TEXT | O |
| builder | Relation → C12 | STRUCT | O |
| associated_figures | Relation[] → C12 | STRUCT | O |
| world_heritage | Boolean | KEY | O |
| world_heritage_year | Number | VALUE | O |
| world_heritage_criteria | Text | TEXT | O |
| national_registration | Boolean | KEY | O |
| related_museum | Text (نام A7 + URL) | HYBRID_CL | O |
| visiting_permit | Boolean | KEY | O |
| photography_policy | Enum | KEY | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### A13 — 19 فیلد · وزن وابسته 6.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| located_in | Relation → G3 (primary) | STRUCT | R |
| geo_coordinates | GeoShape (Area) | VALUE | R |
| landscape_type | Enum (Natural / Cultural / Mixed) | KEY | R |
| overview_text | LongText (700+ words) | TEXT | R |
| best_season | Enum | KEY | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| provinces_spanning | Text[] | TEXT | O |
| world_heritage | Boolean | KEY | O |
| world_heritage_year | Number | VALUE | O |
| world_heritage_criteria | Text | TEXT | O |
| component_monuments | Relation[] → A2 | STRUCT | O |
| component_features | Relation[] → A10 | STRUCT | O |
| historical_period | Relation → C13 | STRUCT | O |
| national_registration | Boolean | KEY | O |
| total_area_km2 | Number | VALUE | O |

### دسته D — عناوین رسمی

#### D1 — 7 فیلد · وزن وابسته 4.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| page_title | Text | TEXT | R |
| overview_text | LongText (500+ words، یکتا) | TEXT | R |
| total_sites_count | Number | VALUE | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| hero_image | Image | MEDIA | O |
| featured_sites | Relation[] (دستی، برای برجسته‌سازی چند سایت کلیدی) | STRUCT | O |

#### D2 — 6 فیلد · وزن وابسته 4.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| page_title | Text | TEXT | R |
| overview_text | LongText (500+ words، یکتا) | TEXT | R |
| total_elements_count | Number | VALUE | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| hero_image | Image | MEDIA | O |

#### D3 — 5 فیلد · وزن وابسته 4.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| page_title | Text | TEXT | R |
| overview_text | LongText (500+ words، یکتا) | TEXT | R |
| total_geoparks_count | Number | VALUE | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |

#### D4 — 5 فیلد · وزن وابسته 4.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| page_title | Text | TEXT | R |
| overview_text | LongText (500+ words، یکتا) | TEXT | R |
| total_reserves_count | Number | VALUE | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |

#### D5 — 8 فیلد · وزن وابسته 5.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| page_title | Text | TEXT | R |
| overview_text | LongText (400+ words، یکتا) | TEXT | R |
| total_cities_count | Number | VALUE | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| unesco_creative_city | Boolean | KEY | R |
| creative_city_domain | Text (اختیاری — پیشنهاد تکمیلی) | TEXT | R |
| creative_city_year | Number (اختیاری — پیشنهاد تکمیلی) | VALUE | R |

#### D6 — 6 فیلد · وزن وابسته 4.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| page_title | Text | TEXT | R |
| overview_text | LongText (400+ words، یکتا) | TEXT | R |
| total_monuments_count | Number | VALUE | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| national_monument | Boolean | KEY | R |

### دسته C — فرهنگ و میراث

#### C1 — 19 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| classification | Classification | KEY | R |
| produced_in | Relation → G | STRUCT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| practiced_in | Relation → G | STRUCT | O |
| associated_ethnicity | Relation → C9 | STRUCT | O |
| intangible_heritage_designation | Boolean | KEY | O |
| intangible_heritage_year | Number | VALUE | O |
| intangible_heritage_criteria | Text | TEXT | O |
| intangible_heritage_status | Enum | KEY | O |
| name_local | Text | TEXT | O |
| materials | Text | TEXT | O |
| technique_summary | Text | TEXT | O |
| external_reference_uri | URI | URI | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### C2 — 15 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| classification | Classification | KEY | R |
| produced_in | Relation → G | STRUCT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| practiced_in | Relation → G | STRUCT | O |
| associated_ethnicity | Relation → C9 | STRUCT | O |
| name_local | Text | TEXT | O |
| main_ingredients | Text | TEXT | O |
| ritual_context_reference | Text | TEXT | O |
| recipe_content | LongText | TEXT | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### C5 — 16 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| classification | Classification | KEY | R |
| worn_by | Relation → C9 | STRUCT | R |
| practiced_in | Relation → G | STRUCT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| associated_ethnicity | Relation → C9 | STRUCT | O |
| name_local | Text | TEXT | O |
| fabric_type | Text | TEXT | O |
| occasion | Text | TEXT | O |
| garment_components | Text | TEXT | O |
| external_reference_uri | URI | URI | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### C6 — 15 فیلد · وزن وابسته 7.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| classification | Classification | KEY | R |
| practiced_in | Relation → G | STRUCT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| associated_ethnicity | Relation → C9 | STRUCT | O |
| name_local | Text | TEXT | O |
| related_instruments_reference | Text[] | TEXT | O |
| ritual_context_reference | Text | TEXT | O |
| audio_sample | Audio | MEDIA | O |
| external_reference_uri | URI | URI | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### C7 — 16 فیلد · وزن وابسته 9.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| dance_type | Controlled Vocabulary | KEY | R |
| practiced_in | Relation → G | STRUCT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| associated_ethnicity | Relation → C9 | STRUCT | O |
| name_local | Text | TEXT | O |
| formation | Text | TEXT | O |
| occasion | Text | TEXT | O |
| music_accompaniment_reference | Text[] | TEXT | O |
| ritual_context_reference | Text | TEXT | O |
| video_sample | Video | MEDIA | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### C8 — 16 فیلد · وزن وابسته 9.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| spoken_in | Relation → G3/G5 | STRUCT | R |
| language_family | Text | TEXT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| name_endonym | Text | TEXT | O |
| script | Text | TEXT | O |
| speaker_estimate_iran | Number | VALUE | O |
| official_language | Boolean | KEY | O |
| dialect_variants | Text[] | TEXT | O |
| ethnicity_reference | Text[] | TEXT | O |
| audio_sample | Audio | MEDIA | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### C9 — 11 فیلد · وزن وابسته 6.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| alternative_names | Text[] | TEXT | O |
| population_estimate | Number | VALUE | O |
| language_reference | Text[] | TEXT | O |
| external_reference_uri | URI | URI | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### C10 — 21 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| classification | Classification | KEY | R |
| spatial_scope | Enum | KEY | R |
| calendar_timing | Text | TEXT | R |
| practiced_in | Relation → G | STRUCT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| associated_ethnicity | Relation → C9 | STRUCT | O |
| associated_figure | Relation → C12 | STRUCT | O |
| intangible_heritage_designation | Boolean | KEY | O |
| intangible_heritage_year | Number | VALUE | O |
| intangible_heritage_criteria | Text | TEXT | O |
| intangible_heritage_status | Enum | KEY | O |
| external_recognition_uris | URI[] | URI | O |
| travel_impact | Relation → P6 | STRUCT | O |
| name_local | Text[] | TEXT | O |
| ceremonial_food_reference | Text[] | TEXT | O |
| external_reference_uri | URI | URI | O |

#### C12 — 16 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| classification | Classification | KEY | R |
| historical_period | Relation → C13 | STRUCT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| born_in | Relation → G5 | STRUCT | O |
| died_in | Relation → G5 | STRUCT | O |
| buried_in | Relation → A2 | STRUCT | O |
| birth_year_approx | Text | TEXT | O |
| death_year_approx | Text | TEXT | O |
| alternative_names | Text[] | TEXT | O |
| major_works | Text | TEXT | O |
| external_reference_uri | URI | URI | O |

#### C13 — 13 فیلد · وزن وابسته 10.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| start_year_approx | Text | TEXT | R |
| end_year_approx | Text | TEXT | R |
| overview_text | LongText | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| alternative_names | Text[] | TEXT | O |
| capital_reference | Text[] | TEXT | O |
| predecessor_reference | Text | TEXT | O |
| successor_reference | Text | TEXT | O |
| external_reference_uri | URI | URI | O |

### دسته E — رویدادها

#### E1 — 19 فیلد · وزن وابسته 8.5

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| is_recurring | Boolean | KEY | R |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | STRUCT | R |
| best_season | Enum (spring/summer/autumn/winter) | KEY | R |
| overview_text | LongText (400+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| event_frequency | Text | TEXT | O |
| held_at | Relation → A2(venue_eligible:true)/A3/A7/A9/G7 | STRUCT | O |
| venue_name | Text | TEXT | O |
| calendar_timing | Text | TEXT | O |
| instance_of_ritual | Relation → C10 | STRUCT | O |
| celebrates | Relation[] → C10/C12/C13 | STRUCT | O |
| name_local | Text | TEXT | O |
| featured_image_gallery | Image[] | MEDIA | O |
| visa_info_link | Text (نام+URL به P1) | HYBRID_CL | O |
| event_travel_impact | Relation → P6 | STRUCT | O |

#### E3 — 17 فیلد · وزن وابسته 8.5

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| is_recurring | Boolean | KEY | R |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | STRUCT | R |
| best_season | Enum | KEY | R |
| overview_text | LongText (400+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| event_frequency | Text | TEXT | O |
| held_at | Relation → A2/A3/A7/A9/G7 | STRUCT | O |
| venue_name | Text | TEXT | O |
| calendar_timing | Text | TEXT | O |
| sport_type | Text | TEXT | O |
| competition_level | Enum (Local/National/International) | KEY | O |
| traditional_sports_reference | Text (نام + URL به C10) | HYBRID_CL | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### E5 — 17 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| calendar_timing | Text | TEXT | R |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | STRUCT | R |
| instance_of_ritual | Relation → C10 | STRUCT | R |
| best_season | Enum | KEY | R |
| overview_text | LongText (500+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| held_at | Relation → A2/A3/G7 | STRUCT | O |
| venue_name | Text | TEXT | O |
| celebrates | Relation[] → C10/C12/C13 | STRUCT | O |
| name_local | Text[] | TEXT | O |
| etiquette_link | Text (نام+URL به P5) | HYBRID_CL | O |
| safety_link | Text (نام+URL به P4) | HYBRID_CL | O |
| featured_image_gallery | Image[] | MEDIA | O |

#### E6 — 15 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| season | Enum (spring/summer/autumn/winter) | KEY | R |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | STRUCT | R |
| best_season | Enum | KEY | R |
| overview_text | LongText (400+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| calendar_timing | Text | TEXT | O |
| name_local | Text | TEXT | O |
| optimal_conditions | Text | TEXT | O |
| nearby_park_reference | Text (نام + URL به A9) | HYBRID_CL | O |
| safety_link | Text (نام+URL به P4) | HYBRID_CL | O |
| featured_image_gallery | Image[] | MEDIA | O |

### دسته P — اطلاعات کاربردی

#### P1 — 14 فیلد · وزن وابسته 9.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| visa_types | Structured[] (type, duration, eligible_nationalit… | HYBRID_ST | R |
| visa_exempt_countries | Text[] | TEXT | R |
| application_process_summary | LongText | TEXT | R |
| last_verified_date | Date | VALUE | R |
| overview_text | LongText (500+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| official_application_portal | URL | URI | O |
| processing_time_estimate | Text | TEXT | O |
| extension_info | Text | TEXT | O |
| entry_points_reference | Text[] (نام G7/G8 + URL) | HYBRID_CL | O |

#### P2 — 14 فیلد · وزن وابسته 10.5

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| official_currency_name | Text (Rial) | TEXT | R |
| colloquial_unit_name | Text (Toman) + نسبت تبدیل | TEXT | R |
| card_acceptance_status | Text (وضعیت صریح کارت‌های بین‌المللی) | TEXT | R |
| last_verified_date | Date | VALUE | R |
| overview_text | LongText (400+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| cash_recommendation_note | Text | TEXT | O |
| exchange_locations_reference | Text[] (نام G5 + نوع صرافی + URL) | HYBRID_CL | O |
| prepaid_tourist_card_note | Text | TEXT | O |
| regional_exchange_notes | Text | TEXT | O |

#### P4 — 15 فیلد · وزن وابسته 11.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| emergency_numbers | Structured (police, ambulance, fire) | HYBRID_ST | R |
| natural_hazard_summary | LongText (شامل زلزله) | TEXT | R |
| general_safety_notes | LongText | TEXT | R |
| last_verified_date | Date | VALUE | R |
| overview_text | LongText (400+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| vaccination_recommendations | Text | TEXT | O |
| water_safety_note | Text | TEXT | O |
| regional_health_notes | Text | TEXT | O |
| local_emergency_facilities | Text[] (نام G5 + نوع مرکز درمانی) | HYBRID_CL | O |
| activity_specific_warnings | Text[] | TEXT | O |

#### P5 — 12 فیلد · وزن وابسته 10.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| dress_code_summary | LongText | TEXT | R |
| religious_site_etiquette | LongText | TEXT | R |
| social_customs_summary | LongText (شامل تعارف) | TEXT | R |
| overview_text | LongText (400+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| tipping_guidance | Text | TEXT | O |
| photography_restrictions | Text | TEXT | O |
| regional_etiquette_notes | Text | TEXT | O |

#### P6 — 12 فیلد · وزن وابسته 6.5

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| calendar_system_note | Text (توضیح شمسی/قمری/میلادی) | TEXT | R |
| national_holidays_list | Structured[] (name, date_rule, duration, type) | HYBRID_ST | R |
| last_verified_date | Date | VALUE | R |
| overview_text | LongText (500+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| closure_impact_notes | Text[] | TEXT | O |
| official_source_link | URL | URI | O |
| regional_event_filter_tag | Enum (به G3 مرتبط) | KEY | O |

#### P7 — 11 فیلد · وزن وابسته 8.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| transport_modes_summary | Structured[] (mode, description, general_note) | HYBRID_ST | R |
| overview_text | LongText (400+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| intercity_bus_note | Text | TEXT | O |
| rail_network_note | Text | TEXT | O |
| cities_with_metro | Text[] | TEXT | O |
| booking_platforms_reference | Text[] (نام + URL) | HYBRID_CL | O |

#### P9 — 16 فیلد · وزن وابسته 5.0

| فیلد | نوع | کلاس | R/O |
|------|------|-------|-----|
| name_en | Text | NAME | R |
| name_fa | Text | NAME | R |
| classification | Enum (Historical / Natural / Thematic) | KEY | R |
| route_sequence | Relation[] → G3/G5/G7/G8 با property `sequence_or… | STRUCT | R |
| best_season | Enum | KEY | R |
| overview_text | LongText (500+ کلمه) | TEXT | R |
| hero_image | Image | MEDIA | R |
| meta_title | Text | SEO | R |
| meta_description | Text | SEO | R |
| total_distance_km | Number | VALUE | O |
| estimated_duration_days | Number | VALUE | O |
| difficulty_level | Enum | KEY | O |
| connects_to | Relation[] → G5/G8 | STRUCT | O |
| connects_to_g13 | Relation → G13 | STRUCT | O |
| highlight_references | Text[] (نام A/C entity + URL) | HYBRID_CL | O |
| transport_guidance_reference | Text (URL به P7) | HYBRID_CL | O |

## ۶. یافته‌ها — ورودی TA-REQ (بدون تصمیم در این سند)

| شناسه | یافته | چرا مهم است | مقصد |
|--------|--------|---------------|--------|
| TA-I18N-F001 | الگوی نام دوزبانه `name_fa`/`name_en` در همه ۴۱ موجودیت | در ۹ زبان تعمیم‌پذیر نیست؛ لایه فنی باید «نام» را خانواده ترجمه‌پذیر مدل کند — بدون هیچ تغییری در CA (نگاشت است، نه ویرایش) | TA-REQ |
| TA-I18N-F002 | متن زبان‌وابسته روی یال: `venue_name` (property روی R16، طبق Substitution Rule CA-E-D005) و propertyهای R20 (criteria/status/…) | بسیاری از سیستم‌ها i18n در سطح relationship-property را پشتیبانی نمی‌کنند — معیار رد/قبول در ۴.۶ | TA-REQ |
| TA-I18N-F003 | ~۱۴۴هزار ارجاع رسانه‌ای (TA-VOL) × alt/caption چندزبانه | سیاست متادیتای رسانه باید صریح شود؛ در سناریو مبنا خودش ~۶۰۰هزار رکورد خرد بالقوه است — کاندید واژه‌نامه/الگو، نه ترجمه آزاد | TA-REQ |
| TA-I18N-F004 | ۷۰ Classification رسمی + ~۲۸ فیلد Enum + Boolean ها | لیبل‌ها باید از **واژه‌نامه مرکزی ×۹** بیایند نه per-record — صرفه‌جویی چند ده‌هزار رکوردی و تضمین یکدستی AEO | TA-REQ |
| TA-I18N-F005 | فیلدهای `Structured[]` در P1/P4/P6/P7 (مثل type/duration/description) | اجزای درونی مخلوط‌اند: کلید مستقل + توصیف وابسته — مدل «فیلد مرکب» لازم است | TA-REQ |
| TA-I18N-F006 | Slug/مسیر URL به‌ازای هر زبان | فیلد در CA نیست (درست است — تصمیم URL صراحتاً فاز ۴ است)؛ ثبت شد که ماتریس i18n بدون آن کامل نیست و باید در TA-REQ به‌عنوان فیلد فنی زبان‌وابسته اضافه شود | TA-REQ |

## ۷. فرض‌های ثبت‌شده

| شناسه | فرض | اثر تغییر |
|--------|------|-----------|
| F-I18N-01 | نرخ تکمیل فیلدهای وابسته: ۸۵٪ (صفحات کامل)، ۴۰٪ (G7 حداقلی) | ±۱۰ واحد درصد ≈ ±۱۲٪ کل رکوردها |
| F-I18N-02 | جفت name_fa/name_en به‌عنوان «یک» خانواده ترجمه‌پذیر شمرده شد | شمارش جداگانه، وزن نام را دوبرابر و کل را ~۵٪ متورم می‌کرد |
| F-I18N-03 | ضرایب زبانی از سناریوهای مصوب TA-VOL (F-VOL-03) به ارث رسید | تغییر سناریو در TA-VOL باید همین‌جا هم بازمحاسبه شود |

## ۸. Decision Log این سند

| شناسه | تصمیم | دلیل |
|--------|--------|-------|
| TA-I18N-D001 | طبقه‌بندی ده‌کلاسه با قاعده ذخیره‌سازی صریح per-class | تبدیل «کدام فیلد ×۹؟» از قضاوت موردی به قاعده مکانیکی — پیش‌نیاز جلوگیری از Drift در پیاده‌سازی |
| TA-I18N-D002 | لیبل KEY/Enum/Classification از واژه‌نامه مرکزی، نه رکورد | جلوگیری از ده‌ها هزار رکورد تکراری و ناسازگاری ترجمه در AEO |
| TA-I18N-D003 | واحد گزارش: رکورد فیلد×زبان (نه صفحه×زبان) | معیار واقعی حجم جدول ترجمه در هر فناوری — ورودی مستقیم ۴.۶ |
| TA-I18N-D004 | استخراج فقط اسکریپتی + لنگر ۱۵۸ | قانون طلایی؛ تکرار Parity Check مصوب CA-C-D015 |

## ۹. تحویل به مراحل بعد

| مقصد | ورودی |
|-------|--------|
| 4.3 (TA-QUERY) | فیلدهای STRUCT (نقشه کامل یال‌خیز per entity) به‌عنوان دامنه Traversal |
| 4.4 (TA-OPS) | سه سناریوی رکورد فیلد×زبان برای سنجش ظرفیت solo |
| 4.5 (TA-REQ) | F001–F006 + قاعده ده‌کلاسه به‌عنوان الزام Field-Type System |

---

*ToPersia TA-I18N-v1.0 — مرحله ۴.۲ فاز ۴*
*وضعیت: FINAL — ممیزی NotebookLM: PASS با صفر مغایرت؛ F002 توسط ممیز به‌عنوان «بحرانی‌ترین هشدار فاز ۴» تأیید شد (ریسک Dummy Node در نقض P-10/C6)*
