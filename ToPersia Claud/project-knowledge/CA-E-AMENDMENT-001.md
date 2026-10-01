# CA-E-AMENDMENT-001

## اصلاحیهٔ رسمی — مدل دادهٔ چهار موجودیت دستهٔ E

### وضعیت: DRAFT — در انتظار ممیزی مستقل NotebookLM و تأیید مالک

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| شناسه | CA-E-AMENDMENT-001 |
| تاریخ | ۳۰ اوت ۲۰۲۶ |
| نسخه | 1.0 |
| وضعیت | DRAFT — ساخت در محیط زنده انجام شده؛ حاکمیت هنوز بسته نشده |
| سند هدف | `CA-E-v1.2-FINAL` — بخش‌های **E1**، **E3**، **E5**، **E6** → زیربخش‌های ۳ (Required Fields)، ۴ (Optional Fields)، ۵ (Internal Links) و ۹ (Schema Readiness) |
| نوع اصلاحیه | ارتقای نوع فیلد + گسترش مدل داده |
| مرجع IA | `IA-v1.2.1-FINAL` (Frozen) |
| اثر بر Architecture Freeze | **هیچ** — صفر موجودیت تازه · صفر رابطهٔ تازه · صفر محدودیت تازه |
| تصمیمات پوشش‌داده‌شده | CA-E-D003 (تأیید مجدد) · CA-E-D004 (ارتقا) · CA-E-D005 (تقویت) |
| تصمیمات تازهٔ این سند | P5-D123 · P5-D124 · P5-D125 (وضعیت: APPROVED — در انتظار ممیزی) |
| تصمیمات ارجاع‌شده | P5-D119 · P5-D121 ← `BC-AMENDMENT-001` |
| سند پشتیبان | `Phase5-HANDOVER-v12.0` — بخش‌های ۴، ۵، ۷، ۸ |
| آزمون پشتیبان | P5-T008 (راستی‌آزمایی wpml-config v1.12) |
| اصلاحیهٔ خواهر | `CA-P-AMENDMENT-003` — هم‌زمان و وابسته |

---

## بخش ۱ — خلاصهٔ اجرایی

سند `CA-E-v1.2-FINAL` برای چهار موجودیت E1، E3، E5 و E6 مجموعاً **۲۴ فیلد اختصاصی** تجویز کرده بود. ساخت زندهٔ ۳۰ اوت ۲۰۲۶ این چهار موجودیت را با **۵۶ فیلد سطح‌بالا و ۱۰ زیرفیلد در ۴ Repeater** پیاده کرد.

سه نوع انحراف رخ داده و هر سه در این سند رسمی می‌شوند:

**یک — ارتقای Text به Select با واژه‌نامه.** چهار فیلد که `CA-E` آن‌ها را `Text` تعریف کرده بود (`event_frequency`، `sport_type`) یا `Enum` بدون فهرست بسته (`competition_level`) اکنون Select با واژه‌نامهٔ کنترل‌شده‌اند. این مهم‌ترین بخش اصلاحیه است، چون تفاوت بین «رشتهٔ آزاد» و «واژه‌نامه» در یک دانشنامهٔ نُه‌زبانه تفاوت بین صفحهٔ تجمیع کارآمد و آشغال داده‌ای است.

**دو — ارتقای ارجاع متنی به Repeater ساختاریافته.** `traditional_sports_reference` و `nearby_park_reference` که `Text (نام + URL)` بودند اکنون Repeater‌اند. **قصد معماری تغییر نکرده**: هر دو همچنان Content Layer‌اند و هیچ‌کدام رابطهٔ گراف نشدند — یعنی `CA-E-D003` و `CA-E-D001` هر دو دست‌نخورده باقی مانده‌اند.

**سه — بیست‌وپنج فیلد تازه.** عمدتاً در E5 و E6 که در `CA-E` سبک‌ترین مدل داده را داشتند: تاریخ شروع و پایان، سیاست عکاسی، مقیاس جمعیت، پنجرهٔ اوج پدیدهٔ طبیعی، وابستگی به آب‌وهوا و مانند این‌ها.

افزون بر این سه، اصلاحیه یک **بدهی معماری قدیمی را رسماً می‌بندد**: متن `CA-E` فیلد `venue_name` را «`DC-R-003` روی R16» توصیف کرده بود، یعنی داده روی یال رابطه. این با قاعدهٔ تثبیت‌شدهٔ `CA-P-AMENDMENT-001` بخش ۷ در تعارض مستقیم است. پیاده‌سازی زنده `venue_name` را postmeta ساخته و این سند آن توصیف را رسماً کنار می‌گذارد.

---

## بخش ۲ — متن اصلی سند هدف

### ۲.۱ از `CA-E-v1.2-FINAL` بخش E1 — Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| event_frequency | Text | CA-E-D004 — اگر is_recurring:true |
| held_at | Relation → A2(venue_eligible:true)/A3/A7/A9/G7 | R15 — اگر venue مشخص و A entity دارد |
| venue_name | Text | DC-R-003 روی R16 — **فقط اگر held_at خالی است** (CA-E-D005) |
| calendar_timing | Text | زمان دقیق (ماه / هفته) |
| name_local | Text | — |
| featured_image_gallery | Image[] | — |
| visa_info_link | Text (نام+URL به P1) | Content Layer — اختیاری، برای رویدادهای بین‌المللی |
| event_travel_impact | Relation → P6 | R23 — فعال |

### ۲.۲ از `CA-E-v1.2-FINAL` بخش E3 — Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| event_frequency | Text | CA-E-D004 |
| sport_type | Text | Content Layer |
| competition_level | Enum (Local/National/International) | Content Layer |
| traditional_sports_reference | Text (نام + URL به C10) | **Content Layer** — CA-E-D003؛ نه Relation |

### ۲.۳ از `CA-E-v1.2-FINAL` بخش E5 — Required / Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| calendar_timing | Text | تقویم قمری یا شمسی — Required برای E5 |
| venue_name | Text | DC-R-003 — فقط اگر held_at خالی (CA-E-D005) |
| etiquette_link | Text (نام+URL به P5) | Content Layer |
| safety_link | Text (نام+URL به P4) | Content Layer — اگر تجمع بزرگ |

> **فیلدهای عمداً غایب:** is_recurring (ضمنی Annual) · event_frequency · designation (C4)

### ۲.۴ از `CA-E-v1.2-FINAL` بخش E6 — Required / Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| season | Enum (spring/summer/autumn/winter) | R19 — stored attribute |
| calendar_timing | Text | هفته‌های دقیق‌تر در فصل |
| optimal_conditions | Text | Content Layer |
| nearby_park_reference | Text (نام + URL به A9) | **Content Layer — نه R15** — CA-E-D001 |
| safety_link | Text (نام+URL به P4) | Content Layer — مناطق دورافتاده |

> **فیلدهای عمداً غایب:** `held_at` / R15 **ممنوع** — `venue_name` **ممنوع** — `is_recurring` ضمنی Seasonal — `instance_of_ritual`, `celebrates` — designation

---

## بخش ۳ — متن جایگزین مصوب

### ۳.۰ ده کلید مشترک دستهٔ E

این ده متاکلید عمداً در چند موجودیت تکرار شده‌اند و **در همهٔ متاباکس‌ها نوع، عرض و واژه‌نامهٔ کاملاً یکسان دارند**. این یکسانی با اعتبارسنجی برنامه‌ای پیش از تحویل تضمین شد تا ناهنجاری `geo_coordinates` تکرار نشود.

| متاکلید | نوع | واژه‌نامه | کلاس WPML | حاضر در |
|---------|-----|-----------|-----------|----------|
| `best_season` | select | `best_season` (۳۴ — بازاستفادهٔ واژه‌نامهٔ موجود) | copy | E1 · E3 · E5 · E6 |
| `calendar_timing` | textarea | — | **translate** | E1 · E3 · E5 · E6 |
| `safety_link` | textarea | — | translate | E5 · E6 |
| `venue_name` | text | — | translate | E1 · E3 · E5 |
| `is_recurring` | switcher | — | copy | E1 · E3 |
| `event_frequency` | select | `e_event_frequency` (۲۱۳) | copy | E1 · E3 |
| `event_start_date` | date | — | copy | E1 · E3 · E5 |
| `event_end_date` | date | — | copy | E1 · E3 · E5 |
| `organizer_name` | text | — | translate | E1 · E3 |
| `official_event_url` | text/url | — | copy | E1 · E3 |

**قاعدهٔ الزامی برای هر موجودیت رویدادی آینده:** هر یک از این ده کلید که استفاده شود، باید دقیقاً همین نوع و همین واژه‌نامه را داشته باشد. تعریف دوبارهٔ یک متاکلید مشترک با نوع متفاوت، `wpml-config` را از یک کلید واحد به دو رفتار متناقض می‌برد و خطا خاموش می‌ماند.

> **قاعدهٔ سراسری:** هر Repeater دستهٔ E، افزون بر زیرفیلدهای فهرست‌شده، یک زیرفیلد `<پیشوند>_row_status` طبق `BC-AMENDMENT-001` بخش ۵ دارد. این زیرفیلد فنی است، در `custom-fields-texts` اعلام نمی‌شود و در شمارندهٔ Meta fields تأثیر ندارد.

### ۳.۱ E1 — جشنواره و رویداد

Post Type Slug: **`tp_festival`** · Rewrite Slug: **`festival`** · شمارندهٔ Meta fields: **۱۴**

| متاکلید | نوع | واژه‌نامه (شناسه) | کلاس WPML | نسبت با متن اصلی |
|---------|-----|-------------------|-----------|-------------------|
| `is_recurring` | switcher | — | copy | بدون تغییر — CA-E-D004 |
| `event_frequency` | **select** | `e_event_frequency` (۲۱۳) | copy | **ارتقا از `Text`** |
| `calendar_timing` | textarea | — | translate | بدون تغییر |
| `event_start_date` | date | — | copy | **تازه** |
| `event_end_date` | date | — | copy | **تازه** |
| `best_season` | select | `best_season` (۳۴) | copy | بدون تغییر — R25 |
| `venue_name` | text | — | translate | **محل ذخیره اصلاح شد** — postmeta، نه متای یال R16 |
| `organizer_name` | text | — | translate | **تازه** |
| `official_event_url` | text/url | — | copy | **تازه** |
| `program_highlights` | repeater (`ph_title` · `ph_description`) | — | translate | **تازه** |
| `event_travel_impact` | **select** | `tp_impact_level` (۲۰۷) | copy | **بازتعریف** — از «Relation → P6» به «درجهٔ اثر» |
| `visa_info_link` | textarea | — | translate | بدون تغییر — Content Layer |
| `admission_note` | textarea | — | translate | **تازه** |
| `tourist_suitability_note` | textarea | — | translate | **تازه** |

**دربارهٔ `event_travel_impact` — مهم‌ترین بازتعریف این سند:** متن `CA-E` این را `Relation → P6` می‌نوشت، یعنی خودِ فیلد را یال گراف می‌گرفت. در ساخت زنده دو چیز جدا وجود دارد:

| جزء | نقش |
|-----|------|
| یال `affects_p6_e1` (R23) | رابطهٔ گراف — پاسخ به «کدام رویدادها تقویم سفر را متأثر می‌کنند» |
| فیلد `event_travel_impact` (select) | **درجهٔ** اثر — پاسخ به «چقدر متأثر می‌کند» |

این دو مکمل‌اند نه بدیل. اما داشتن دو چیز، دو منبع حقیقت می‌سازد → تصمیم پیشنهادی **P5-D123**.

### ۳.۲ E3 — رویداد ورزشی

Post Type Slug: **`tp_sports_event`** · Rewrite Slug: **`sports-event`** · شمارندهٔ Meta fields: **۱۳**

| متاکلید | نوع | واژه‌نامه (شناسه) | کلاس WPML | نسبت با متن اصلی |
|---------|-----|-------------------|-----------|-------------------|
| `is_recurring` | switcher | — | copy | بدون تغییر |
| `event_frequency` | **select** | `e_event_frequency` (۲۱۳) | copy | **ارتقا از `Text`** |
| `calendar_timing` | textarea | — | translate | بدون تغییر |
| `event_start_date` | date | — | copy | **تازه** |
| `event_end_date` | date | — | copy | **تازه** |
| `best_season` | select | `best_season` (۳۴) | copy | بدون تغییر |
| `venue_name` | text | — | translate | محل ذخیره اصلاح شد |
| `sport_type` | **select** | `e3_sport_type` (۲۱۴ — ۱۳ مقدار) | copy | **ارتقا از `Text`** |
| `competition_level` | **select** | `e3_competition_level` (۲۱۵ — ۴ مقدار) | copy | **ارتقا از `Enum` توصیفی** |
| `traditional_sports_reference` | **repeater** (`ts_name` · `ts_url` · `ts_note`) | — | translate | **ارتقا از `Text`** — همچنان Content Layer |
| `organizer_name` | text | — | translate | **تازه** |
| `official_event_url` | text/url | — | copy | **تازه** |
| `spectator_access_note` | textarea | — | translate | **تازه** |

**دربارهٔ چهار مقدار `e3_competition_level`:** متن اصلی سه مقدار نوشته بود (Local/National/International). مقدار چهارم `not_applicable` است که طبق قاعدهٔ سراسری واژه‌نامه‌های پروژه به هر واژه‌نامه افزوده می‌شود. این افزودن یک مقدار معنایی تازه نیست و انحراف محسوب نمی‌شود.

**تأیید مجدد CA-E-D003:** `traditional_sports_reference` علی‌رغم ارتقا به Repeater، همچنان **رابطهٔ گراف نیست**. E3 هیچ یال R17 یا R18 ندارد و در ساخت زنده هم نگرفت. `ts_url` یک ارجاع متنی به صفحهٔ C10 است.

### ۳.۳ E5 — مراسم مذهبی عمومی

Post Type Slug: **`tp_religious_event`** · Rewrite Slug: **`religious-ceremony`** · شمارندهٔ Meta fields: **۱۶** · Repeater: **صفر**

| متاکلید | نوع | واژه‌نامه (شناسه) | کلاس WPML | نسبت با متن اصلی |
|---------|-----|-------------------|-----------|-------------------|
| `calendar_timing` | textarea | — | translate | بدون تغییر — Required برای E5 |
| `calendar_system` | **select** | `p6_calendar_system` (۲۰۴) | copy | **تازه** — بازاستفادهٔ واژه‌نامهٔ P6 |
| `annual_date_rule` | textarea | — | translate | **تازه** |
| `event_start_date` | date | — | copy | **تازه** |
| `event_end_date` | date | — | copy | **تازه** |
| `best_season` | select | `best_season` (۳۴) | copy | بدون تغییر |
| `venue_name` | text | — | translate | محل ذخیره اصلاح شد |
| `ceremony_duration_days` | number | — | copy | **تازه** |
| `crowd_scale` | **select** | `e5_crowd_scale` (۲۱۷) | copy | **تازه** |
| `public_participation_note` | textarea | — | translate | **تازه** |
| `visitor_etiquette_note` | textarea | — | translate | **تازه** |
| `photography_policy` | **select** | `e5_photography_policy` (۲۱۶) | copy | **تازه** |
| `photography_note` | textarea | — | translate | **تازه** |
| `dress_code_note` | textarea | — | translate | **تازه** |
| `etiquette_link` | textarea | — | translate | بدون تغییر — Content Layer |
| `safety_link` | textarea | — | translate | بدون تغییر — Content Layer |

**انطباق تأییدشده:** `is_recurring` و `event_frequency` در E5 **غایب‌اند** — دقیقاً همان‌طور که متن `CA-E` صریحاً تجویز کرده بود. ساخت زنده این قید را رعایت کرده است.

**بازاستفادهٔ واژه‌نامهٔ `p6_calendar_system` روی یک موجودیت دستهٔ E** یک انحراف از قاعدهٔ پیشوند `RB-D020` است، اما انحراف مطلوبی است: تقویم قمری در P6 و E5 دقیقاً یک مفهوم است و دو واژه‌نامه یعنی دو فهرست که با گذشت زمان واگرا می‌شوند. این همان استدلالی است که `P5-D092` برای `country_iso` پذیرفت.

**مرز محتوایی که باید صریح بماند:** `visitor_etiquette_note` و `dress_code_note` روی E5 نباید محتوای P5 (فرهنگ سفر و آداب) را تکرار کنند. E5 آداب **این مراسم خاص** را می‌گوید؛ P5 آداب عمومی سفر به ایران را. `etiquette_link` پل بین این دو است.

### ۳.۴ E6 — رویداد طبیعی و فصلی

Post Type Slug: **`tp_seasonal_event`** · Rewrite Slug: **`seasonal-event`** · شمارندهٔ Meta fields: **۱۳**

| متاکلید | نوع | واژه‌نامه (شناسه) | کلاس WPML | نسبت با متن اصلی |
|---------|-----|-------------------|-----------|-------------------|
| `season` | select | `best_season` یا معادل | copy | بدون تغییر — R19 stored attribute |
| `best_season` | select | `best_season` (۳۴) | copy | بدون تغییر — R25 |
| `natural_phenomenon_type` | **select** | `e6_phenomenon_type` (۲۱۸ — ۱۰ مقدار) | copy | **تازه** |
| `peak_start_week` | number | — | copy | **تازه** |
| `peak_end_week` | number | — | copy | **تازه** |
| `peak_window_note` | textarea | — | translate | **تازه** |
| `calendar_timing` | textarea | — | translate | بدون تغییر |
| `optimal_conditions` | textarea | — | translate | بدون تغییر |
| `weather_dependency` | select/switcher | — | copy | **تازه** |
| `predictability_note` | textarea | — | translate | **تازه** |
| `viewing_points` | **repeater** (`vp_name` · `vp_url` · `vp_note`) | — | translate | **تازه** |
| `nearby_park_reference` | **repeater** (`np_name` · `np_url`) | — | translate | **ارتقا از `Text`** — همچنان Content Layer |
| `safety_link` | textarea | — | translate | بدون تغییر |

**انطباق تأییدشده با CA-E-D001 — قاعدهٔ سخت:** E6 در ساخت زنده **هیچ یال `held_at` (R15) ندارد** و **هیچ فیلد `venue_name` ندارد**. تنها لنگر جغرافیایی‌اش سه یال `held_in` (R16) به G3، G5 و G7 است. این دقیقاً همان چیزی است که `CA-E-D001` تجویز کرده و بخش ۱ سند `CA-E` برای اصلاح `CA-A` به‌خاطرش نوشته شد.

**دربارهٔ `season` و `best_season` که هر دو حاضرند:** این تکرار نیست. `season` یک ویژگی ذخیره‌شدهٔ R19 است («این پدیده در کدام فصل رخ می‌دهد») و `best_season` یک توصیهٔ سفر است («بهترین فصل بازدید»). این دو می‌توانند متفاوت باشند — مثلاً یک پدیدهٔ زمستانی که بهترین زمان بازدیدش اواخر پاییز است. باید در راهنمای ادیتوریال صریح شود، وگرنه ویرایشگر محتوا هر دو را یکسان پر می‌کند و یکی از دو صفحهٔ تجمیع بی‌معنا می‌شود.

### ۳.۵ اصلاح Schema Readiness

هر چهار موجودیت به گرهٔ `Event` نگاشت می‌شوند (برخلاف P6 که `ItemList` است). فیلدهای تازه، نگاشت‌های تازه‌ای ممکن می‌کنند که در متن اصلی نبودند:

| متاکلید | ویژگی Schema.org | یادداشت |
|---------|-------------------|----------|
| `event_start_date` · `event_end_date` | `startDate` · `endDate` | مهم‌ترین دستاورد Schema این اصلاحیه — بدون این دو، گرهٔ `Event` ناقص است |
| `event_frequency` | `eventSchedule` → `Schedule.repeatFrequency` | نگاشت مقدار واژه‌نامه به `ISO 8601 duration` در زمان رندر |
| `venue_name` | `location` → `Place.name` | **فقط اگر یال `held_at` خالی است** — CA-E-D005 |
| یال `held_at` | `location` → `@id` گرهٔ A2/A3/A7/A9/G7 | ارجحیت بر `venue_name` |
| `organizer_name` | `organizer` → `Organization.name` | — |
| `official_event_url` | `sameAs` | نه `url` — `url` متعلق به صفحهٔ خود ToPersia است |
| `admission_note` | `offers` → `Offer.description` | بدون `price` — قیمت ذخیره نمی‌شود |
| `sport_type` | `Event` → `SportsEvent` + `about` | `SportsEvent` زیرنوع `Event` است |
| `crowd_scale` · `photography_policy` | `additionalProperty` → `PropertyValue` | `additionalProperty` روی `Event` معتبر نیست؛ باید روی گرهٔ `Place` یا در متن بیاید — **نیازمند راستی‌آزمایی در فاز ۶** |

**قید اجباری:** پیش از افزودن هر ویژگی، شاخهٔ ارث‌بری آن نوع در Schema.org راستی‌آزمایی شود — همان درسی که در `CA-P-AMENDMENT-002` بند ۴.۶ با `dateModified` روی `GovernmentService` گرفته شد.

---

## بخش ۴ — دلیل فنی

### ۴.۱ چرا `Text` به `Select` ارتقا یافت

این تصمیم بیشترین اثر بلندمدت را در کل این اصلاحیه دارد و دلیلش صرفاً «تمیزتر بودن» نیست:

| بُعد | فیلد `Text` آزاد | فیلد `Select` با واژه‌نامه |
|------|-------------------|-----------------------------|
| **چندزبانگی** | هر مقدار در سه (و بعداً نُه) زبان جدا ترجمه می‌شود؛ «کشتی» ممکن است در انگلیسی یک‌بار `Wrestling` و بار دیگر `Traditional Wrestling` نوشته شود | مقدار حروف‌کوچک لاتین ذخیره می‌شود و کلاس WPML آن **Copy** است — صفر واحد ترجمه، صفر واگرایی |
| **صفحهٔ تجمیع** | «رویدادهای کشتی ایران» با فیلتر روی رشتهٔ آزاد ساخته نمی‌شود | فیلتر روی مقدار بسته، مبنای مطمئن Aggregation Page طبق D-007 |
| **AEO** | موتور پاسخ نمی‌تواند تشخیص دهد دو رویداد هم‌نوع‌اند | مقدار بسته به `about` یا `additionalType` نگاشت می‌شود |
| **هزینهٔ نگهداری** | با ۲۱٬۶۰۰ موجودیت و نیروی به‌روزرسانی، رشتهٔ آزاد قطعاً واگرا می‌شود | افزودن مقدار تازه یک ویرایش واژه‌نامه است |
| **بازگشت‌پذیری** | تبدیل Text به Select بعد از ورود محتوا نیازمند نگاشت دستی هزاران مقدار است | — |

بند آخر تعیین‌کننده است: این ارتقا **الان تقریباً رایگان است و بعداً بسیار گران**. محتوا هنوز صفر است.

### ۴.۲ چرا ارجاع متنی به Repeater تبدیل شد — و چرا این رابطه نیست

`CA-E-D003` و `CA-E-D001` هر دو صریح‌اند: `traditional_sports_reference` و `nearby_park_reference` نباید رابطهٔ گراف باشند، چون هیچ‌کدام مبنای IA ندارند (E3∉R17/R18 و E6∉R15).

ارتقا به Repeater این قید را نقض نمی‌کند. تفاوت Text و Repeater در **محل ذخیره** نیست — هر دو postmeta‌اند — بلکه در **قابلیت شمارش** است. یک فیلد Text با محتوای «چوگان — /fa/chogan/ · زورخانه — /fa/zurkhaneh/» برای انسان خواناست و برای رندر و Schema بی‌فایده. Repeater همان داده را قابل‌پیمایش می‌کند.

**آنچه ساخته نشد و نباید ساخته شود:** هیچ یال گرافی بین E3 و C10، و هیچ یال گرافی بین E6 و A9. ساخت زنده این را رعایت کرده — E3 هیچ یال R17/R18/R23 نگرفت و E6 هیچ یال R15 نگرفت.

### ۴.۳ بستن بدهی `venue_name` روی یال R16

متن `CA-E` سه بار `venue_name` را با توصیف «`DC-R-003` روی R16» نوشته است. این یعنی داده روی **یال رابطه** بنشیند.

آزمون `P5-T003` ثابت کرد متادیتای یال رابطه در جدول‌های `wp_jet_rel_{id}_meta` ذخیره می‌شود، این جدول‌ها ستون زبان ندارند، WPML هیچ قلابی به آن‌ها ندارد و در `wpml-config.xml` قابل اعلام نیستند. `CA-P-AMENDMENT-001` بخش ۷ بر این پایه یک قاعدهٔ سراسری تثبیت کرد:

> متادیتای یال رابطه در این پروژه استفاده نمی‌شود.

ساخت زنده `venue_name` را postmeta با کلاس `translate` ساخته است. این سند توصیف «روی R16» را رسماً کنار می‌گذارد. **هیچ تغییر عملیاتی لازم نیست** — این صرفاً هم‌ترازکردن متن سند با واقعیت و با قاعدهٔ سراسری است.

با این بند، مورد باز «انتقال `venue_name` در R16 از edge metadata به postmeta» بسته می‌شود.

### ۴.۴ چرا E5 و E6 بیشترین فیلد تازه را گرفتند

`CA-E` برای E5 هفده فیلد و برای E6 پانزده فیلد تعریف کرده بود که اغلبشان مشترک بودند. مدل اختصاصی هر دو سبک بود، در حالی که این دو بیشترین **پرسش عملیاتی بی‌پاسخ** را تولید می‌کنند:

| پرسش واقعی گردشگر | فیلدی که پاسخ می‌دهد | موجودیت |
|--------------------|----------------------|----------|
| «می‌توانم در عاشورا عکس بگیرم؟» | `photography_policy` + `photography_note` | E5 |
| «چه بپوشم؟» | `dress_code_note` | E5 |
| «به‌عنوان غیرمسلمان می‌توانم شرکت کنم؟» | `public_participation_note` | E5 |
| «چقدر شلوغ است؟» | `crowd_scale` | E5 |
| «دقیقاً کدام هفته شکوفه‌ها هستند؟» | `peak_start_week` + `peak_end_week` | E6 |
| «اگر یک هفته دیرتر بروم چه؟» | `predictability_note` + `weather_dependency` | E6 |
| «از کجا ببینم؟» | `viewing_points` | E6 |

هر سطر این جدول یک کوئری long-tail با نیت بالا است. نبودشان یعنی صفحه برای انسان و برای موتور پاسخ، ناقص است.

---

## بخش ۵ — انطباق با معماری

| بررسی | نتیجه | شرح |
|--------|--------|------|
| Entity Inflation | ✅ صفر | چهار Post Type ساخته شد که هر چهار در IA موجودیت مصوب‌اند. `ProgramHighlight`، `ViewingPoint` و `TraditionalSport` همه Structured Field ماندند |
| Relationship Inflation | ✅ صفر | ۳۵ یال تازهٔ دستهٔ E، همه از شش الگوی مصوب R15، R16، R17، R18، R23 و R22 |
| انطباق با ماتریس CA-E بخش ۲ | ✅ کامل | E6 هیچ R15 ندارد (D001) · E3 هیچ R17/R18/R23 ندارد (D003) · E5 هیچ R23 ندارد · هر چهار R16 دارند |
| CA-E-D001 (E6 هرگز R15) | ✅ حفظ شد | صفر یال `held_at` برای E6 · صفر `venue_name` روی E6 |
| CA-E-D003 (بازی‌های بومی Content Layer) | ✅ حفظ شد | `traditional_sports_reference` علی‌رغم ارتقای ساختار، همچنان Content Layer |
| CA-E-D004 (`is_recurring` + `event_frequency`) | ✅ ارتقا یافت | ساختار حفظ شد؛ فقط نوع فیلد دوم بسته شد |
| CA-E-D005 (Substitution Rule) | ⚠️ بدون مکانیزم | `venue_name` و یال `held_at` هر دو بدون هیچ مانعی هم‌زمان قابل پرکردن‌اند → P5-D124 |
| CA-P-D002 (Content Layer Convention) | ✅ منطبق | `visa_info_link`، `etiquette_link`، `safety_link`، `ts_url`، `np_url` همه Text |
| قاعدهٔ متای یال | ✅ رفع شد | توصیف «`DC-R-003` روی R16» کنار گذاشته شد · `Register Relation Meta Fields` روی هر ۳۵ یال خاموش |
| One Physical Place = One Primary Node (P-02) | ⚠️ نیازمند قید | `vp_url` و `np_url` باید فقط به گره‌های موجود اشاره کنند → P5-D122 در سند خواهر |
| P5-D109 (ممنوعیت حذف ردیف) | ✅ رفع شد | هر چهار Repeater طبق `BC-AMENDMENT-001` بخش ۵ زیرفیلد `row_status` می‌گیرند |
| Architecture Freeze | ✅ INTACT | صفر موجودیت · صفر رابطه · صفر محدودیت · صفر دسته |

---

## بخش ۶ — تصمیمات

### ۶.۱ تصمیمات پوشش‌داده‌شده

| ID | وضعیت پس از این سند |
|----|----------------------|
| **CA-E-D001** | تأیید مجدد و تقویت — E6 در ساخت زنده صفر یال R15 و صفر `venue_name` دارد |
| **CA-E-D003** | تأیید مجدد — ارتقای ساختار، Content Layer بودن را تغییر نداد |
| **CA-E-D004** | ارتقا — `event_frequency` از `Text` به `Select` با واژه‌نامهٔ `e_event_frequency` |
| **CA-E-D005** | حفظ‌شده در طراحی، بدون مکانیزم اجرایی → P5-D124 |

### ۶.۲ تصمیمات تازهٔ این سند — وضعیت PROPOSED

| ID | تصمیم | دلیل | نیازمند |
|----|--------|-------|----------|
| **P5-D123** | یال `affects_p6_e1` به‌صورت برنامه‌ای از فیلد `event_travel_impact` مشتق می‌شود؛ هر مقدار جز `not_applicable` یال می‌سازد و `not_applicable` یال را حذف می‌کند | امروز دو منبع حقیقت برای یک واقعیت وجود دارد: ویرایشگر می‌تواند درجهٔ اثر را «بالا» بگذارد و یال را نسازد، یا برعکس. تعمیم مستقیم الگوی P5-D114 و `route_stops_order` | تأیید مالک · کد فاز ۶ |
| **P5-D124** | قاعدهٔ جانشینی CA-E-D005 با یک هشدار در صفحهٔ ویرایش اجرا شود: اگر یال `held_at` مقدار دارد و `venue_name` هم پر است، هشدار نمایش داده شود | قاعده‌ای که فقط در سند نوشته شده و در ابزار اجرا نشده، با نیروی محتوای پس از راه‌اندازی نقض خواهد شد. الگوی موجود: `topersia_p9_orphan_notice()` | تأیید مالک |
| **P5-D125** | `season` و `best_season` روی E6 دو معنای متفاوت دارند و راهنمای ادیتوریال باید این تفاوت را صریح کند | بدون این قید، هر دو یکسان پر می‌شوند و یکی از دو خانوادهٔ Aggregation Page (فصل رخداد در برابر فصل بازدید) بی‌معنا می‌شود | تأیید مالک |

---

## بخش ۷ — قاعدهٔ کاری برای تیم محتوا

| قاعده | دلیل |
|--------|-------|
| اگر یال `held_at` پر است، `venue_name` را **خالی بگذارید** | CA-E-D005 · جلوگیری از دو نام برای یک مکان |
| E6 هرگز `venue_name` و هرگز `held_at` نمی‌گیرد | CA-E-D001 — قاعدهٔ سخت |
| `season` = فصل رخداد پدیده · `best_season` = فصل توصیه‌شدهٔ بازدید — این دو لزوماً یکی نیستند | P5-D125 |
| `official_event_url` فقط سایت رسمی برگزارکننده — نه صفحهٔ خبری، نه شبکهٔ اجتماعی | این فیلد به `sameAs` نگاشت می‌شود |
| `visitor_etiquette_note` روی E5 فقط آداب همان مراسم؛ آداب عمومی سفر در P5 می‌ماند | جلوگیری از Duplicate Content · اصل P-06 |
| `peak_start_week` و `peak_end_week` شمارهٔ هفتهٔ سال شمسی‌اند، نه تاریخ | یکدستی محاسبه در سه و بعداً نُه زبان |
| هیچ ردیفی از `program_highlights`، `viewing_points`، `traditional_sports_reference` و `nearby_park_reference` حذف نشود — `row_status` را به `not_applicable` تغییر دهید | P5-T008 ثابت کرد WPML ردیف‌ها را با شمارهٔ موقعیت دنبال می‌کند |
| `ts_url`، `np_url` و `vp_url` فقط به صفحهٔ موجود در سایت لینک شوند | P-02 |
| مقادیر واژه‌نامه هرگز به فارسی وارد نشوند | همهٔ مقادیر حروف‌کوچک لاتین‌اند؛ ترجمهٔ نمایشی در زمان رندر انجام می‌شود |

---

## بخش ۸ — موارد باز

| مورد | وضعیت | مسدودکنندهٔ چه چیزی |
|------|--------|----------------------|
| ممیزی مستقل NotebookLM | انجام نشده | تغییر وضعیت سند از DRAFT به RATIFIED |
| تأیید مالک برای P5-D123 · P5-D124 · P5-D125 | انجام نشده | — |
| نوع دقیق `season` و `weather_dependency` | استنتاج‌شده از کلاس `copy` در `wpml-config v1.12` | راستی‌آزمایی با export متاباکس پیش از RATIFIED |
| واژه‌نامهٔ `season` روی E6 | نامشخص است که از `best_season` (۳۴) استفاده می‌کند یا واژه‌نامهٔ جدا | اگر جدا باشد، دو فهرست فصل موازی خواهیم داشت — باید بررسی شود |
| اعتبار `additionalProperty` روی `Event` | راستی‌آزمایی نشده | نگاشت `crowd_scale` و `photography_policy` در فاز ۶ |
| `topersia_schema_map` برای چهار Post Type دستهٔ E | صفر بلوک | کل خروجی GEO این صفحات |
| قالب Elementor | صفر | نمایش برای انسان |
| برچسب ده جعبهٔ رابطه (P5-D119) | اجرا شده در محیط زنده · ثبت حاکمیتی نشده | `BC-AMENDMENT-001` |

---

*ToPersia — CA-E-AMENDMENT-001 — نسخهٔ ۱.۰*
*اصلاحیهٔ پیشنهادی بر `CA-E-v1.2-FINAL` — بخش‌های E1، E3، E5 و E6*
*مرجع: IA-v1.2.1-FINAL (Frozen) · Architecture Freeze: INTACT*
