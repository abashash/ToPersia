# CA-P-AMENDMENT-003

## اصلاحیهٔ رسمی — مدل دادهٔ سه موجودیت P2، P6 و P7

### وضعیت: DRAFT — در انتظار ممیزی مستقل NotebookLM و تأیید مالک

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| شناسه | CA-P-AMENDMENT-003 |
| تاریخ | ۳۰ اوت ۲۰۲۶ |
| نسخه | 1.0 |
| وضعیت | DRAFT — ساخت در محیط زنده انجام شده؛ حاکمیت هنوز بسته نشده |
| سند هدف | `CA-P-v1.2-FINAL` — بخش‌های **P2 — Currency & Payment**، **P6 — Calendar & Public Holidays**، **P7 — Public Transport Information** → زیربخش‌های Required Fields، Optional Fields، Internal Links، Schema Readiness و Notes |
| نوع اصلاحیه | گسترش مدل داده بر پایهٔ تغییر واقعیت بیرونی + انحراف پیاده‌سازی |
| مرجع IA | `IA-v1.2.1-FINAL` (Frozen) |
| اثر بر Architecture Freeze | **هیچ** — صفر موجودیت تازه · صفر رابطهٔ تازه · صفر محدودیت تازه · صفر دستهٔ تازه |
| تصمیمات پوشش‌داده‌شده | P5-D111 · P5-D112 · P5-D113 · P5-D114 · P5-D115 · P5-D116 |
| تصمیمات تازهٔ این سند | P5-D120 · P5-D122 (وضعیت: APPROVED — در انتظار ممیزی) |
| تصمیمات ارجاع‌شده به سند دیگر | P5-D110 ← `CA-G-AMENDMENT-003` · P5-D117 / P5-D118 / P5-D119 / **P5-D121** ← `BC-AMENDMENT-001` |
| سند پشتیبان | `Phase5-HANDOVER-v12.0` — بخش‌های ۴ تا ۱۱ |
| آزمون پشتیبان | P5-T007 (Title Field از نوع Text) · P5-T008 (راستی‌آزمایی wpml-config v1.12) |
| اصلاحیهٔ خواهر | `CA-E-AMENDMENT-001` — هم‌زمان و وابسته (کلیدهای مشترک دستهٔ E) |

---

## بخش ۱ — خلاصهٔ اجرایی

سند `CA-P-v1.2-FINAL` برای سه موجودیت P2، P6 و P7 مجموعاً **۱۱ فیلد اختصاصی** تجویز کرده بود (بدون فیلدهای مشترکی که بعداً به متاباکس مشترک منتقل شدند). ساخت زندهٔ ۳۰ اوت ۲۰۲۶ این سه موجودیت را با **۵۰ فیلد سطح‌بالا و ۶۶ زیرفیلد در ۱۳ Repeater** پیاده کرد.

این فاصله سه ریشهٔ متفاوت دارد و هر سه باید جدا از هم دیده شوند:

**یک — تغییر واقعیت بیرونی پس از نگارش سند.** بازتعریف واحد پول ایران (قانون ۸ آبان ۱۴۰۴، لازم‌الاجرا از ۱۶ آذر ۱۴۰۴) و اتصال شتاب به سامانهٔ میر، دو فرض بنیادی متن P2 را باطل کرده‌اند. متن اصلی می‌گوید «نسبت ۱ به ۱۰» و «کارت‌های بین‌المللی کار نمی‌کنند» — هر دو جمله امروز نادرست‌اند. این بخش از اصلاحیه اختیاری نیست؛ بدون آن، مهم‌ترین صفحهٔ غیرویزایی پروژه از روز انتشار غلط است.

**دو — ارتقای فیلد متنی به فیلد ساختاریافته.** `national_holidays_list` و `transport_modes_summary` در متن اصلی به‌صورت `Structured[]` با سه یا چهار ویژگی توصیف شده بودند. پیاده‌سازی، همان قصد را با Repeaterهای غنی‌تر محقق کرده است. اینجا **قصد معماری تغییر نکرده**؛ فقط عمق ساختار بیشتر شده است.

**سه — فیلدهای تازه‌ای که در فاز ۳ پیش‌بینی نشده بودند.** یازده فیلد در P6 و P7 (مانند `annual_review_month`، `lunar_date_disclaimer`، `accessibility_note`، `route_reference_note`) هیچ متناظری در متن اصلی ندارند. این‌ها گسترش‌اند، نه اصلاح.

**هیچ‌کدام از این سه دسته موجودیت، رابطه یا محدودیت تازه‌ای نمی‌سازد.** تمام گسترش در قالب Structured Field انجام شده — همان الگویی که بند ۷ گزارش بازبینی `CA-P` تجویز کرده و `CA-P-AMENDMENT-002` برای P1 اجرا کرده است.

---

## بخش ۲ — دامنه و تفکیک مسیر حاکمیتی

سند `Phase5-HANDOVER-v12.0` در بخش ۱۲ نوشته بود این اصلاحیه باید «تصمیمات P5-D110 تا P5-D116» را پوشش دهد. این دامنه در دو جهت اصلاح می‌شود:

### ۲.۱ سه تصمیم از دامنهٔ این سند خارج می‌شوند

| تصمیم | سند هدف واقعی | چرا در CA-P جا نمی‌گیرد |
|--------|----------------|--------------------------|
| **P5-D110** — سقف سخت روی `currency_note` | `CA-G-v1.2-FINAL` § **G1 → Required Fields** (سطر `currency_note`) | این فیلد در CA-P تعریف **نشده** است. تعریفش در CA-G سطر ۱۰۵ است و یادداشت معماری P-PATCH-001 هم آنجاست. اگر سقف را در CA-P بنویسیم، ممیز آیندهٔ CA-G هرگز آن را پیدا نمی‌کند. مسیر درست: `CA-G-AMENDMENT-003` |
| **P5-D117** — تحویل همه‌چیز به‌صورت فایل import | ثبت قرارداد ساخت (`BC-AMENDMENT-001`) | یک تصمیم دربارهٔ **روش تحویل** است، نه دربارهٔ معماری محتوا. هیچ فیلد، رابطه یا موجودیتی را تغییر نمی‌دهد |
| **P5-D118** — `Hide meta field names` روشن | `SHARED-METABOX-VERIFIED-v1.0` + `BC-AMENDMENT-001` | یک تنظیم UI روی همهٔ ۳۵ متاباکس است. ضمناً مستقیماً با سطر «Hide meta field names: خاموش» در `SHARED-METABOX-VERIFIED-v1.0` **تناقض دارد** و آن سند باید به v1.1 برود |
| **P5-D119** — برچسب جعبهٔ رابطه باید معنا حمل کند | `BC-AMENDMENT-001` | قرارداد نام‌گذاری UI روی ۱۳۶ رابطه است؛ مصداقش در دستهٔ E بروز کرده ولی قاعده سراسری است |

**دلیل این تفکیک:** قاعدهٔ حاکمیتی پروژه این است که پیشوند شناسهٔ اصلاحیه با سند هدف یکی باشد (`CA-G-AMENDMENT-001/002` روی CA-G، `CA-A-AMENDMENT-001` روی CA-A، `CA-D-AMENDMENT-001` روی CA-D). شکستن این قاعده یعنی ممیز سال آینده که فقط CA-G را می‌خواند، از وجود سقف پنجاه‌کلمه‌ای بی‌خبر می‌ماند. در افق پنج‌ساله و با نُه زبان، این نوع گم‌شدن سند از هر صرفه‌جویی امروز گران‌تر است.

### ۲.۲ دو موجودیت به دامنه اضافه می‌شوند

سند تحویل فقط «انحراف مدل P2» را نام برده بود. اما مقابلهٔ سطر‌به‌سطر نشان می‌دهد **P6 و P7 هم از متن `CA-P` انحراف دارند** — P6 با شش فیلد تازه و یک تغییر نام، P7 با پنج فیلد تازه و دو ارتقای نوع. اگر این دو در اصلاحیه نیایند، Architecture Freeze برای آن‌ها همچنان نقض‌شده می‌ماند و بدهی حاکمیتی بسته نمی‌شود. بنابراین دامنهٔ این سند: **P2 + P6 + P7**.

---

## بخش ۳ — متن اصلی سند هدف

### ۳.۱ از `CA-P-v1.2-FINAL` بخش P2 — Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| official_currency_name | Text (Rial) | — |
| colloquial_unit_name | Text (Toman) + نسبت تبدیل | — |
| card_acceptance_status | Text (وضعیت صریح کارت‌های بین‌المللی) | — |
| last_verified_date | Date | **CA-P-D004** |
| overview_text | LongText (400+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### ۳.۲ از `CA-P-v1.2-FINAL` بخش P2 — Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| cash_recommendation_note | Text | Content Layer — بازه‌ای، نه عدد قطعی |
| exchange_locations_reference | Text[] (نام G5 + نوع صرافی + URL) | Content Layer — نه Relation |
| prepaid_tourist_card_note | Text | در صورت وجود چنین سرویسی |
| regional_exchange_notes | Text | برای G3 — طبق DEC-004 |

> **فیلدهای عمداً غایب:** `live_exchange_rate` — طبق **CA-P-D004** هیچ نرخ زنده در محتوای static ذخیره نمی‌شود.

و در Entity Overview همان بخش:

> ...**نظام دوگانه نام‌گذاری ارز** (ریال رسمی در برابر تومان رایج در گفتار روزمره — نسبت ۱ به ۱۰) و **عدم کارکرد کارت‌های بین‌المللی Visa/Mastercard به‌دلیل تحریم‌ها** دو واقعیتی هستند که تقریباً هیچ گردشگری از قبل نمی‌داند...

### ۳.۳ از `CA-P-v1.2-FINAL` بخش P6 — Required / Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| calendar_system_note | Text (توضیح شمسی/قمری/میلادی) | — |
| national_holidays_list | Structured[] (name, date_rule, duration, type) | — |
| last_verified_date | Date | **CA-P-D004** — الزامی برای اعتماد AEO |
| closure_impact_notes | Text[] | Content Layer |
| official_source_link | URL | برای تأیید نهایی — CA-P-D004 |
| regional_event_filter_tag | Enum (به G3 مرتبط) | برای رندر Conditional |

### ۳.۴ از `CA-P-v1.2-FINAL` بخش P7 — Required / Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| transport_modes_summary | Structured[] (mode, description, general_note) | — |
| intercity_bus_note | Text | برجسته‌سازی کیفیت VIP bus |
| rail_network_note | Text | — |
| cities_with_metro | Text[] | فقط نام شهرها |
| booking_platforms_reference | Text[] (نام + URL) | Content Layer — نه Relation |

> **فیلدهای عمداً غایب:** هیچ جدول زمان‌بندی، قیمت بلیت، یا برنامه حرکت (طبق **CA-P-D003**).

---

## بخش ۴ — متن جایگزین مصوب

### ۴.۰ فیلدهایی که به متاباکس مشترک منتقل می‌شوند

هر سه موجودیت به «متاباکس مشترک» متصل‌اند و این هفت فیلد **نباید** در متاباکس اختصاصی تکرار شوند (همان قاعدهٔ `P5-D082` که برای P1 اجرا شد):

| فیلد | محل واقعی |
|------|-----------|
| `name_fa` · `name_en` · `name_ar` | متاباکس مشترک |
| `overview_text` | متاباکس مشترک — نوع `wysiwyg` |
| `hero_image` · `featured_image_gallery` | متاباکس مشترک |
| `meta_title` · `meta_description` | متاباکس مشترک |
| `last_verified_date` · `content_status` · `entity_uid` | متاباکس مشترک |

### ۴.۱ P2 — جدول جایگزین Required Fields

Post Type Slug: **`tp_currency`** · Rewrite Slug: **`currency`** · شمارندهٔ Meta fields: **۳۱**

| متاکلید | نوع | واژه‌نامه (شناسه) | کلاس WPML | جایگزین چه چیزی |
|---------|-----|-------------------|-----------|------------------|
| `currency_iso_code` | text | — | copy | تازه — لنگر بین‌المللی برای Schema |
| `currency_symbol` | text | — | copy | تازه |
| `currency_units` | repeater (۶ زیرفیلد) | — | translate | ~~`official_currency_name`~~ + ~~`colloquial_unit_name`~~ |
| `unit_confusion_explainer` | textarea | — | translate | بخش «Rial vs Toman» قالب صفحه |
| `price_reading_rule` | textarea | — | translate | تازه |
| `redenomination_status` | select | `p2_redenomination_status` (۱۹۲) | copy | تازه — بازتعریف واحد پول |
| `redenomination_ratio` | text | — | copy | تازه |
| `redenomination_law_date` | date | — | copy | تازه |
| `redenomination_start_date` | date | — | copy | تازه |
| `redenomination_end_date` | date | — | copy | تازه |
| `dual_pricing_active` | switcher | — | copy | تازه |
| `redenomination_note` | textarea | — | translate | تازه |
| `denominations` | repeater (۶ زیرفیلد) | — | translate | تازه — فهرست اسکناس و ایران‌چک |
| `international_card_status` | select | `p2_card_status` (۱۹۵) | copy | ~~`card_acceptance_status`~~ — سرصفحه |
| `card_acceptance_summary` | textarea | — | translate | ~~`card_acceptance_status`~~ — بخش روایی |
| `card_network_status` | repeater (۶ زیرفیلد) | — | translate | ~~`card_acceptance_status`~~ — ماتریس |
| `atm_usage_note` | textarea | — | translate | تازه |
| `domestic_network_note` | textarea | — | translate | تازه |
| `tourist_card_available` | switcher | — | copy | ~~`prepaid_tourist_card_note`~~ |
| `tourist_card_services` | repeater (۷ زیرفیلد) | — | translate | ~~`prepaid_tourist_card_note`~~ |
| `accepted_foreign_currencies` | select | `p2_foreign_currency` (۲۰۱) | copy | تازه |
| `banknote_condition_rule` | textarea | — | translate | تازه |
| `exchange_channel_notes` | textarea | — | translate | تازه |
| `exchange_locations` | repeater (۵ زیرفیلد) | — | translate | ~~`exchange_locations_reference`~~ |
| `declaration_rules_note` | textarea | — | translate | تازه |
| `rate_regime_note` | textarea | — | translate | تازه |
| `rate_reference_links` | repeater (۳ زیرفیلد) | — | translate | تازه — جایگزین ذخیرهٔ نرخ |
| `cash_recommendation_note` | textarea | — | translate | بدون تغییر نام |
| `daily_budget_bands` | repeater (۴ زیرفیلد) | — | translate | تازه |
| `digital_payment_note` | textarea | — | translate | تازه |
| `regional_notes` | repeater (۳ زیرفیلد) | — | translate | ~~`regional_exchange_notes`~~ |

### ۴.۲ P2 — هشت Repeater و چهل زیرفیلد

> **قاعدهٔ سراسری:** هر Repeater این بخش، افزون بر زیرفیلدهای فهرست‌شده، یک زیرفیلد `<پیشوند>_row_status` طبق `BC-AMENDMENT-001` بخش ۵ دارد. این زیرفیلد فنی است، در `custom-fields-texts` اعلام نمی‌شود و در شمارندهٔ Meta fields تأثیر ندارد.

| Repeater | زیرفیلدها | Title Field | نقش |
|----------|-----------|-------------|------|
| `currency_units` | `cu_name` · `cu_role` · `cu_ratio_to_base` · `cu_valid_from` · `cu_valid_until` · `cu_note` | `cu_name` (text — تأییدشده در P5-T007) | مدل چندواحدی — P5-D111 |
| `denominations` | `dn_face_value` · `dn_printed_unit` · `dn_instrument_type` · `dn_toman_equivalent` · `dn_in_circulation` · `dn_note` | `dn_face_value` | تفکیک اسکناس از ایران‌چک |
| `card_network_status` | `cn_network` · `cn_status` · `cn_channel` · `cn_condition_note` · `cn_verified_date` · `cn_source_url` | `cn_network` | ماتریس شبکه‌ای — P5-D112 |
| `tourist_card_services` | `tc_service_name` · `tc_provider_url` · `tc_issuance_channel` · `tc_fee_note` · `tc_status` · `tc_verified_date` · `tc_note` | `tc_service_name` | فقط اطلاعاتی — P5-D116 |
| `exchange_locations` | `ex_city_name` · `ex_city_url` · `ex_area_name` · `ex_channel_type` · `ex_note` | `ex_city_name` | Content Layer — نه Relation |
| `rate_reference_links` | `rr_label` · `rr_url` · `rr_scope` | `rr_label` | جایگزین نرخ — P5-D113 |
| `daily_budget_bands` | `db_band` · `db_min_eur` · `db_max_eur` · `db_note` | `db_band` | فقط یورو — P5-D115 |
| `regional_notes` | `rn_g3_id` · `rn_g3_name` · `rn_note` | `rn_g3_name` | منبع یال‌های `applies_g3_p2` — P5-D114 |

### ۴.۳ P2 — فیلدهایی که همچنان عمداً غایب‌اند

| فیلد | وضعیت |
|------|--------|
| `live_exchange_rate` یا هر عدد نرخ | **ممنوع** — CA-P-D004 و P5-D113، تأیید مجدد |
| هر مبلغ بودجه با واحد ریال یا تومان | **ممنوع** — P5-D115 |
| هر لینک رزرو، افیلیت یا قیمت قطعی سرویس کارت | **ممنوع** — P5-D116 |
| هر گرهٔ تازه برای صرافی یا فرودگاه | **ممنوع** — P-02، `ex_city_url` فقط به گرهٔ موجود G5 اشاره می‌کند |

### ۴.۴ P6 — جدول جایگزین

Post Type Slug: **`tp_calendar`** · Rewrite Slug: **`calendar`** · شمارندهٔ Meta fields: **۹**

| متاکلید | نوع | واژه‌نامه (شناسه) | کلاس WPML | نسبت با متن اصلی |
|---------|-----|-------------------|-----------|-------------------|
| `calendar_systems` | repeater (`cs_system` · `cs_note`) | `p6_calendar_system` (۲۰۴) | translate | ساختاریافتن بخشی از ~~`calendar_system_note`~~ |
| `calendar_system_note` | textarea | — | translate | بدون تغییر |
| `national_holidays` | repeater (۹ زیرفیلد) | — | translate | ~~`national_holidays_list`~~ — تغییر نام + عمق |
| `closure_impact_note` | textarea | — | translate | ~~`closure_impact_notes`~~ (مفرد شد) |
| `crowding_and_price_note` | textarea | — | translate | **تازه** |
| `best_worst_time_note` | textarea | — | translate | **تازه** — بخش Best/Worst قالب صفحه |
| `official_source_link` | text/url | — | copy | بدون تغییر |
| `annual_review_month` | select/number | — | copy | **تازه** — مهار ریسک شمارهٔ یک P6 |
| `lunar_date_disclaimer` | textarea | — | translate | **تازه** |

زیرفیلدهای `national_holidays`: `nh_name` · `nh_calendar` · `nh_date_rule` · `nh_duration_days` · `nh_holiday_type` (`p6_holiday_type`، ۲۰۵) · `nh_is_public_holiday` · `nh_closure_level` (`p6_closure_level`، ۲۰۶) · `nh_travel_impact` (`tp_impact_level`، ۲۰۷) · `nh_note`

**فیلد بازنشسته:** `regional_event_filter_tag` حذف می‌شود. نقشش (رندر Conditional در context استان یا شهر) اکنون با دو رابطهٔ مصوب `applies_g3_p6` و `applies_g5_p6` انجام می‌شود که مبنای IA دارند. یک فیلد Enum جداگانه یعنی دو منبع حقیقت برای یک واقعیت.

### ۴.۵ P7 — جدول جایگزین

Post Type Slug: **`tp_transport`** · Rewrite Slug: **`transport`** · شمارندهٔ Meta fields: **۱۰**

| متاکلید | نوع | واژه‌نامه (شناسه) | کلاس WPML | نسبت با متن اصلی |
|---------|-----|-------------------|-----------|-------------------|
| `transport_modes` | repeater (۵ زیرفیلد) | `p7_transport_mode` (۲۰۸) · `p7_coverage_level` (۲۰۹) · `p7_suitability` (۲۱۰) | translate | ~~`transport_modes_summary`~~ — تغییر نام + عمق |
| `mode_choice_guide` | textarea | — | translate | **تازه** — بخش How to Choose قالب صفحه |
| `intercity_bus_note` | textarea | — | translate | بدون تغییر |
| `rail_network_note` | textarea | — | translate | بدون تغییر |
| `air_travel_note` | textarea | — | translate | **تازه** |
| `cities_with_metro` | repeater (۵ زیرفیلد) | `p7_urban_system` (۲۱۱) | translate | ~~`cities_with_metro: Text[]`~~ — ارتقا به Repeater |
| `booking_platforms` | repeater (۵ زیرفیلد) | `p7_booking_scope` (۲۱۲) | translate | ~~`booking_platforms_reference`~~ |
| `accessibility_note` | textarea | — | translate | **تازه** |
| `transport_etiquette_note` | textarea | — | translate | **تازه** — با مرز صریح نسبت به P5 |
| `route_reference_note` | textarea | — | translate | **تازه** — بخش Scenic & Route قالب صفحه |

زیرفیلدها: `transport_modes` = `tm_mode` · `tm_coverage` · `tm_tourist_suitability` · `tm_description` · `tm_note` — `cities_with_metro` = `cm_city_name` · `cm_city_url` · `cm_system_type` · `cm_line_count` · `cm_note` — `booking_platforms` = `bp_name` · `bp_url` · `bp_scope` · `bp_foreign_card_support` · `bp_note`

**تأیید مجدد CA-P-D003:** هیچ‌کدام از این ده فیلد جدول زمان‌بندی، قیمت بلیت یا برنامهٔ حرکت نگه نمی‌دارد. `bp_foreign_card_support` یک سوییچر است، نه قیمت.

### ۴.۶ اصلاح Schema Readiness

متن اصلی برای P2 نوع `Thing` با `additionalType: "CurrencyGuide"` تجویز کرده بود. این ساختار به‌تنهایی برای موتورهای پاسخ بی‌اثر است چون هیچ ویژگی قابل‌استخراجی ندارد.

جایگزین مصوب برای هر سه موجودیت: یک گرهٔ `WebPage` به‌عنوان میزبان، به‌علاوهٔ یک گرهٔ `FAQPage` مشتق از `faq_items` متاباکس مشترک. `dateModified` روی گرهٔ `WebPage` می‌نشیند، نه روی گرهٔ موجودیت — همان قاعده‌ای که در `CA-P-AMENDMENT-002` بند ۴.۶ تثبیت شد.

| موجودیت | گرهٔ اصلی | ویژگی‌های نگاشت‌شده |
|---------|-----------|---------------------|
| P2 | `WebPage` + `about: Thing` | `currency_iso_code` → `identifier` · هر ردیف `daily_budget_bands` → `MonetaryAmount` با `currency: "EUR"` |
| P6 | `WebPage` + `ItemList` | هر ردیف `national_holidays` → `ListItem` با `item: Event` و `eventSchedule` |
| P7 | `WebPage` + `ItemList` | هر ردیف `transport_modes` → `ListItem` |

**قید اجباری:** پیش از افزودن هر ویژگی، شاخهٔ ارث‌بری آن نوع در Schema.org راستی‌آزمایی شود. پیاده‌سازی واقعی این نگاشت‌ها به فاز ۶ (پرکردن `topersia_schema_map`) موکول است و این جدول ورودی آن فاز است، نه ادعای انجام‌شده.

---

## بخش ۵ — دلیل فنی به تفکیک تصمیم

### ۵.۱ P5-D111 — مدل چندواحدی جایگزین دو فیلد ثابت شد

متن اصلی `official_currency_name` و `colloquial_unit_name` را دو فیلد Text ثابت گرفته بود، با این فرض ضمنی که فقط دو واحد وجود دارد و نسبتشان همیشه ۱ به ۱۰ است.

قانون اصلاح بند الف مادهٔ ۵۸ قانون بانک مرکزی در ۸ آبان ۱۴۰۴ تصویب و در ۱۶ آذر ۱۴۰۴ لازم‌الاجرا شد. هر ریال جدید برابر ده هزار ریال قدیم است و زیرواحد قِران احیا می‌شود (هر ریال جدید = صد قران). دورهٔ آماده‌سازی دوساله تا آذر ۱۴۰۶، آغاز فرایند از ۱۴۰۵، گردش هم‌زمان تا سه سال، و خروج کامل واحد قدیم حدود ۱۴۰۹.

یعنی در بازهٔ ۱۴۰۴ تا ۱۴۰۹ **پنج واحد هم‌زمان معنا دارند**: ریال قدیم، ریال جدید، تومان محاوره‌ای، قران، و دینار تاریخی. یک مدل دوفیلدی حداکثر تا ۱۴۰۶ کار می‌کرد و بعد مهاجرت اجباری تحمیل می‌کرد — دقیقاً همان چیزی که اصل «پرهیز از راهکار کوتاه‌مدت» رد می‌کند.

نکتهٔ محتوایی که باید در `redenomination_note` صریح بیاید: نام واحد در **متن نهایی قانون «ریال» باقی ماند**، در حالی که چند گزارش خبری از تصویب «تومان» خبر داده بودند. این تناقض باید توضیح داده شود و مرجع، متن قانون باشد.

### ۵.۲ P5-D112 — وضعیت کارت از دوحالتی به ماتریس شبکه‌ای

متن اصلی `card_acceptance_status` را یک فیلد Text با «وضعیت صریح» تعریف کرده و در Entity Overview نوشته بود «عدم کارکرد کارت‌های بین‌المللی Visa/Mastercard».

با تکمیل فازهای اتصال شتاب به سامانهٔ میر، کارت‌های میر روسیه در ایران پذیرفته می‌شوند. ویزا و مسترکارت همچنان کار نمی‌کنند. یعنی پاسخ درست دیگر «بله/خیر» نیست، بلکه **به شبکهٔ کارت وابسته است**.

سه‌تکه‌شدن فیلد یک انتخاب AEO است، نه سلیقه: `international_card_status` پاسخ کوتاه برای Featured Snippet می‌دهد، `card_acceptance_summary` متن روایی برای نقل‌قول موتور پاسخ، و `card_network_status` جدول قابل‌استخراج با `cn_verified_date` و `cn_source_url` که سیگنال E-E-A-T می‌سازد. یک فیلد Text هیچ‌کدام را نمی‌داد.

### ۵.۳ P5-D113 — هیچ نرخ ارزی ذخیره نمی‌شود

تأیید دوبارهٔ `CA-P-D004` گزینهٔ یک، بدون تغییر. `rate_reference_links` جایگزین ساختاریافتهٔ آن است و `rr_scope` هر لینک را به سه دستهٔ رسمی، بازار و تجمیع‌کننده طبقه‌بندی می‌کند — چون در ایران «نرخ ارز» یک عدد نیست و لینک بدون دسته، خودش گمراه‌کننده است.

### ۵.۴ P5-D114 — یال‌های استانی برنامه‌ای ساخته می‌شوند

یال‌های `applies_g3_p2` از زیرفیلد `rn_g3_id` در `regional_notes` با هوک `save_post` ساخته و حذف می‌شوند، نه دستی. این تعمیم مستقیم الگوی `route_stops_order` در `CA-P-AMENDMENT-001` است: یک منبع حقیقت واحد، رابطه به‌عنوان ایندکس کوئری معکوس مشتق‌شده.

**قید حیاتی که باید در پیاده‌سازی رعایت شود:** تابع همگام‌ساز فقط روی نسخهٔ زبان پیش‌فرض اجرا شود و شناسه‌ها با `topersia_src_id()` به زبان پیش‌فرض نگاشت شوند — همان دو قاعده‌ای که `P5-D071` و `topersia_p9_sync_relations()` تثبیت کرده‌اند. اجرای بی‌قید روی نسخهٔ انگلیسی، یال تکراری می‌سازد.

**وضعیت:** کد هنوز نوشته نشده است (بدهی باز، بخش ۹).

### ۵.۵ P5-D115 — بودجه فقط با یورو

پرسش «چقدر پول نقد به ایران بیاورم» یکی از چهار پرسش AEO صریح سند `CA-P` برای P2 است و نمی‌توان بی‌پاسخ گذاشت. اما عدد ریالی در تورم فعلی هفتگی بی‌اعتبار می‌شود و — بدتر — در میانهٔ بازتعریف واحد پول، خودِ واحدش هم مبهم می‌شود.

یورو انتخاب شد نه دلار، چون `accepted_foreign_currencies` نشان می‌دهد یورو ارز غالب تبدیل در ایران است و مخاطب اصلی نسخهٔ انگلیسی سایت بیشتر اروپایی است. `db_min_eur` و `db_max_eur` همیشه بازه‌اند، هرگز عدد قطعی — همان قاعدهٔ `pc_fee_min_eur` در P1.

### ۵.۶ P5-D116 — سرویس‌های کارت گردشگری فقط اطلاعاتی

سند IA قید کرده دستهٔ P صرفاً اطلاعاتی است و هیچ سرویس تجاری ارائه نمی‌دهد. `tourist_card_services` نزدیک‌ترین نقطهٔ پروژه به نقض این قید است، چون این سرویس‌ها ذاتاً تجاری‌اند.

مهار در سه لایه: بدون لینک رزرو، بدون افیلیت، بدون قیمت قطعی (`tc_fee_note` متن توصیفی است نه عدد). زیرفیلدهای `tc_status` و `tc_verified_date` اجباری‌اند و **سرویس تعطیل‌شده حذف نمی‌شود، وضعیتش تغییر می‌کند** — این هم قید `P5-D109` را رعایت می‌کند و هم برای کاربر ارزش تاریخی دارد.

### ۵.۷ ارتقای `Structured[]` به Repeater در P6 و P7

متن اصلی هر دو فیلد را `Structured[]` نوشته بود، یعنی خودِ سند فاز ۳ ساختار را تجویز کرده و فقط عمقش را باز گذاشته بود. Repeater پیاده‌سازی JetEngine همان `Structured[]` است. سه ویژگی اضافه شده که هرکدام یک پرسش AEO مشخص را پاسخ می‌دهند:

| زیرفیلد افزوده | پرسش AEO که پاسخ می‌دهد |
|-----------------|--------------------------|
| `nh_is_public_holiday` + `nh_closure_level` | «آیا بانک‌ها در فلان روز در ایران بازند؟» |
| `nh_travel_impact` | «آیا سفر در این تاریخ به ایران خوب است؟» |
| `tm_coverage` + `tm_tourist_suitability` | «بهترین راه جابه‌جایی در ایران برای گردشگر چیست؟» |
| `cm_city_url` | اتصال به گرهٔ موجود G5 — بدون ساخت گرهٔ تازه |

---

## بخش ۶ — انطباق با معماری

| بررسی | نتیجه | شرح |
|--------|--------|------|
| Entity Inflation | ✅ صفر | سه Post Type ساخته شد که هر سه در IA موجودیت مصوب‌اند (P2، P6، P7). هیچ Sub-Entity ساخته نشد — `CurrencyUnit`، `Denomination`، `HolidayInstance`، `TransportMode` همه Structured Field‌اند |
| Relationship Inflation | ✅ صفر | پنج یال R22: `applies_g1_p2` · `applies_g3_p2` · `applies_g1_p6` · `applies_g3_p6` · `applies_g5_p6`. همه از الگوی مصوب R22 |
| انطباق با DEC-004 | ✅ منطبق | ماتریس می‌گوید P2 → G5 = «Link» و P1 → G3/G5 = «Link». در هر دو مورد «Link» یعنی لینک ایستا در قالب، نه یال گراف — همان تفسیری که `P5-D083` برای P1 تثبیت کرد. نبود `applies_g5_p2` انحراف نیست، **انطباق** است |
| انطباق با DEC-005 | ✅ حفظ شد | P6 یک موجودیت واحد Country-Wide ماند؛ هیچ نمونهٔ استانی یا شهری ساخته نشد. رندر Conditional از طریق دو یال R22 انجام می‌شود |
| انطباق با DEC-003 | ✅ حفظ شد | P7 هیچ رابطه‌ای در R01–R27 ندارد و هیچ یالی هم برایش ساخته نشد. اتصال Level 2 همچنان فیلد `transport_access` روی G5 است |
| Geography First (P-01) | ✅ حفظ شد | لنگر جغرافیایی P2 و P6 همان R22 → G1 است |
| One Physical Place = One Primary Node (P-02) | ⚠️ نیازمند قید صریح | `ex_city_url`، `cm_city_url` و `rn_g3_id` همگی به گره‌های موجود اشاره می‌کنند و گرهٔ تازه نمی‌سازند. اما هیچ چیز در ساختار مانع ورود یک نام صرافی به‌عنوان مکان نیست → قاعدهٔ کاری بخش ۸ و تصمیم پیشنهادی P5-D122 |
| CA-P-D002 (Content Layer Convention) | ✅ منطبق | هر ارجاع بدون مبنای IA (`ex_city_url`، `bp_url`، `rr_url`، `tc_provider_url`) به‌صورت Text باقی ماند، نه Relation |
| CA-P-D003 (P7 Evergreen Scope) | ✅ منطبق | صفر جدول زمان‌بندی، صفر قیمت بلیت |
| CA-P-D004 (Volatility Policy) | ✅ تقویت شد | افزون بر `last_verified_date`، فیلدهای `cn_verified_date`، `tc_verified_date`، `annual_review_month` و `redenomination_*_date` عمق زمانی می‌دهند |
| قاعدهٔ متای یال (CA-P-AMENDMENT-001 §۷) | ✅ رعایت شد | `Register Relation Meta Fields` روی هر پنج یال خاموش است. هیچ داده‌ای روی یال ننشسته |
| P5-D109 (ممنوعیت حذف ردیف Repeater) | ✅ رفع شد | هر سیزده Repeater این سند طبق `BC-AMENDMENT-001` بخش ۵ زیرفیلد `row_status` می‌گیرند |
| Architecture Freeze | ✅ INTACT | صفر موجودیت · صفر رابطه · صفر محدودیت · صفر دسته |

---

## بخش ۷ — تصمیمات

### ۷.۱ تصمیمات ثبت‌شده و پوشش‌داده‌شده در این اصلاحیه

| ID | تصمیم | وضعیت |
|----|--------|--------|
| **P5-D111** | مدل چندواحدی `currency_units` جایگزین `official_currency_name` و `colloquial_unit_name` | RATIFIED با این سند |
| **P5-D112** | `card_acceptance_status` به `international_card_status` + `card_acceptance_summary` + `card_network_status` شکسته شد | RATIFIED با این سند |
| **P5-D113** | هیچ نرخ ارزی ذخیره نمی‌شود — `rate_reference_links` با `rr_scope` سه‌دسته‌ای | RATIFIED — تأیید مجدد CA-P-D004 |
| **P5-D114** | یال‌های `applies_g3_p2` برنامه‌ای از `rn_g3_id` مشتق می‌شوند | RATIFIED در طراحی · کد اجرا نشده |
| **P5-D115** | بازه‌های بودجهٔ روزانه فقط با یورو | RATIFIED با این سند |
| **P5-D116** | سرویس‌های کارت گردشگری فقط اطلاعاتی — بدون رزرو، افیلیت یا قیمت قطعی | RATIFIED با این سند |

### ۷.۲ تصمیمات تازهٔ این سند — وضعیت PROPOSED

| ID | تصمیم | دلیل | نیازمند |
|----|--------|-------|----------|
| **P5-D120** | `regional_event_filter_tag` بازنشسته می‌شود؛ نقشش به یال‌های `applies_g3_p6` و `applies_g5_p6` منتقل شد | دو منبع حقیقت برای یک واقعیت، در مقیاس نُه زبان به واگرایی ختم می‌شود | تأیید مالک |
| **P5-D122** | هیچ زیرفیلد URL در هیچ Repeater دستهٔ P حق ندارد به مکانی اشاره کند که گرهٔ متناظر در G یا A ندارد | مهار P-02 در سطح قاعده، نه امید به انضباط ادیتوریال | تأیید مالک |

**قید ویژه روی `regional_notes`:** این Repeater منبع یال است (P5-D114). قاعدهٔ «حذف نکن» با «باید بتوان یک استان را برداشت» تعارض دارد. راه‌حل طبق `BC-AMENDMENT-001` بخش ۵: تابع همگام‌ساز فقط ردیف‌هایی را یال می‌کند که `rn_row_status` آن‌ها `not_applicable` نیست. برداشتن استان یعنی تغییر وضعیت، نه حذف ردیف.

---

## بخش ۸ — قاعدهٔ کاری برای تیم محتوا

| قاعده | دلیل |
|--------|-------|
| ترتیب ردیف‌های هیچ Repeater در این سه موجودیت معنایی ندارد — جابه‌جا نکنید | مرتب‌سازی در زمان رندر از زیرفیلد کلیدی مشتق می‌شود |
| هیچ ردیفی از هیچ Repeater ترجمه‌شده حذف نشود؛ وضعیتش را عوض کنید | P5-D109 · تأیید زندهٔ P5-T008 |
| `cu_ratio_to_base` همیشه نسبت به **واحد پایهٔ فعلی** ثبت شود، نه به تومان | در دورهٔ گذار، پایه عوض می‌شود؛ نسبت‌های زنجیره‌ای در رندر محاسبه می‌شوند |
| ایران‌چک هرگز در `denominations` با `dn_instrument_type = banknote` ثبت نشود | ایران‌چک اسکناس نیست و بیشترین سردرگمی گردشگر را می‌سازد |
| `cn_verified_date` و `tc_verified_date` بدون منبع پر نشوند | ادعای راستی‌آزمایی بدون منبع، سیگنال E-E-A-T منفی است |
| هر مبلغ فقط در `db_min_eur` و `db_max_eur` و فقط یورو | P5-D115 |
| `ex_city_url` و `cm_city_url` فقط به صفحهٔ موجود G5 لینک شوند — نام صرافی مکان نیست | P-02 · P5-D122 |
| `rn_g3_id` پس از ورود داده هرگز تغییر نکند | یال رابطه از همین کلید مشتق می‌شود |
| `annual_review_month` هر سال یک بازبینی اجباری تقویم را ماشه می‌زند | مهم‌ترین ریسک محتوایی P6 طبق متن اصلی سند |
| فیلدهای عددی در ورودی ادمین بدون جداکنندهٔ هزارگان وارد شوند | جداکننده در زمان رندر افزوده می‌شود |
| پیش از هر آزمون ترجمه، یک بار Update بزنید | بستهٔ ترجمهٔ WPML از وضعیت ذخیره‌شده ساخته می‌شود |

---

## بخش ۹ — موارد باز

| مورد | وضعیت | مسدودکنندهٔ چه چیزی |
|------|--------|----------------------|
| ممیزی مستقل NotebookLM روی این اصلاحیه | انجام نشده | تغییر وضعیت سند از DRAFT به RATIFIED |
| تأیید مالک برای P5-D120 · P5-D121 · P5-D122 | انجام نشده | ورود محتوا (P5-D121 به‌طور خاص) |
| کد همگام‌سازی یال‌های `applies_g3_p2` از `rn_g3_id` | نوشته نشده | یال‌های استانی P2 |
| نوع دقیق فیلدها | استنتاج‌شده از `wpml-config v1.12` و سند تحویل، نه از export متاباکس | راستی‌آزمایی نهایی — طبق قاعدهٔ «هرگز به گزارش اعتماد نکن»، پیش از RATIFIED باید با export واقعی متاباکس مقابله شود |
| `topersia_schema_map` برای `tp_currency`، `tp_calendar`، `tp_transport` | صفر بلوک | کل خروجی GEO این سه صفحه — فاز ۶ |
| قالب Elementor | صفر | نمایش این ۵۰ فیلد برای انسان |
| راستی‌آزمایی دوبارهٔ سه یافتهٔ پژوهشی پیش از انتشار | انجام نشده | صحت محتوای P2 در روز انتشار |
| دو رکورد آزمایشی P2 | باید پیش از انتشار پاک یا بازنویسی شوند | — |

---

*ToPersia — CA-P-AMENDMENT-003 — نسخهٔ ۱.۰*
*اصلاحیهٔ پیشنهادی بر `CA-P-v1.2-FINAL` — بخش‌های P2، P6 و P7*
*مرجع: IA-v1.2.1-FINAL (Frozen) · Architecture Freeze: INTACT*
