# CA-P-AMENDMENT-002

## اصلاحیهٔ رسمی — مدل دادهٔ موجودیت P1 (ویزا و شرایط ورود)

### وضعیت: RATIFIED — ممیزی مستقل NotebookLM گذشت · آزمون P5-T005 گذشت

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| شناسه | CA-P-AMENDMENT-002 |
| تاریخ | ۲۷ اوت ۲۰۲۶ |
| نسخه | 1.1 |
| وضعیت | RATIFIED |
| سند هدف | `CA-P-v1.2-FINAL` — بخش **P1 — Visa & Entry Requirements** → زیربخش‌های Required Fields، Optional Fields و Schema Readiness |
| نوع اصلاحیه | گسترش مدل داده بر پایهٔ پژوهش منبع اولیه |
| مرجع IA | `IA-v1.2.1-FINAL` (Frozen) |
| اثر بر Architecture Freeze | **هیچ** — صفر موجودیت تازه · صفر رابطهٔ تازه · صفر محدودیت تازه · صفر دستهٔ تازه |
| تصمیمات مرتبط | P5-D081 تا P5-D109 |
| پژوهش پشتیبان | `61-RESEARCH-VISA-MFA` — پژوهش عمیق روی سامانه‌های وزارت امور خارجه، ۲۵ اوت ۲۰۲۶ |
| آزمون وابسته | P5-T005 — **PASS** در ۲۷ اوت ۲۰۲۶ |

---

## بخش ۱ — خلاصهٔ اجرایی

سند `CA-P-v1.2-FINAL` برای موجودیت P1 ده فیلد الزامی و چهار فیلد اختیاری تجویز کرده بود. این طراحی در فاز ۳ و بدون دسترسی به منابع اولیهٔ وزارت امور خارجه انجام شده بود.

پژوهش مستقیم روی `evisa.mfa.ir`، `evisatraveller.mfa.ir`، `evisabusiness.mfa.ir` و `mfa.gov.ir` در ۲۵ اوت ۲۰۲۶ نشان داد نظام روادید ایران **شش بُعد ساختاری** دارد که مدل چهارده‌فیلدی هیچ‌کدام را نمی‌تواند نگه دارد.

این اصلاحیه مدل داده را به **۲۸ فیلد سطح‌بالا و ۳۶ زیرفیلد** (شمارندهٔ `Meta fields` باید دقیقاً ۲۸ نشان دهد) گسترش می‌دهد، بدون افزودن حتی یک موجودیت یا رابطهٔ تازه. تمام گسترش در قالب Structured Field انجام می‌شود — دقیقاً همان الگویی که بند ۷ گزارش بازبینی `CA-P` تجویز کرده بود:

> «هیچ Sub-Entity ساخته نشد (VisaType، HolidayInstance، RouteStop و...) — همه این‌ها Structured Fields هستند، نه Entity مستقل.»

---

## بخش ۲ — شش یافتهٔ پژوهش که مدل اصلی از دست می‌داد

| # | یافته | شواهد | پیامد بر مدل داده |
|---|-------|--------|--------------------|
| ۱ | **محور نوع پاسپورت مستقل از ملیت است** | ده‌ها کشور فقط برای دارندگان پاسپورت دیپلماتیک، رسمی، خدمت یا ویژه معافیت دارند | فیلد مستقل `special_passport_note` |
| ۲ | **رژیم مناطق آزاد یک نظام جداگانه است** | معافیت ۱۴ روزه در کیش، قشم، اروند، ارس، چابهار، انزلی و ماکو — که **اجازهٔ ورود به سرزمین اصلی نمی‌دهد** | گروه فیلد مستقل با سوییچر صریح `free_zone_mainland_allowed` |
| ۳ | **مدت معافیت درون یک دسته یکسان نیست** | ترکیه ۹۰ · گرجستان ۴۵ · عراق و مالزی و تاجیکستان ۳۰ · چین و هنگ‌کنگ و ماکائو ۲۱ · مصر ۲۰ · گروه بیست‌وهفت‌کشوره ۱۵ · قزاقستان و آذربایجان ۱۴ | ماتریس گروهی ناکافی است — باید **یک ردیف به‌ازای هر کشور** باشد |
| ۴ | **محل دریافت هم هزینه و هم پنجرهٔ اعتبار را تغییر می‌دهد** | دریافت فرودگاهی حدود ۹۰ روز اعتبار و گران‌تر؛ دریافت سفارتی حدود ۶۰ روز و ارزان‌تر | زیرفیلد `vt_channels` روی هر نوع روادید |
| ۵ | **شرط‌های ورود به‌ازای کشور متفاوت‌اند** | سوریه فقط پرواز مستقیم دمشق · تاجیکستان خروج از دوشنبه و ورود به فرودگاه امام خمینی · گروه پانزده‌روزه یک‌بار در هر ۱۸۰ روز و غیرقابل تمدید | واژه‌نامهٔ `p1_entry_condition` |
| ۶ | **بیمهٔ اجباری به ملیت وابسته است** | دوازده ملیت (ارمنستان، آذربایجان، بولیوی، مصر، گرجستان، عراق، لبنان، مالزی، عمان، سوریه، ترکیه، ونزوئلا) از الزام بیمهٔ ایرانی معاف‌اند | سوییچر `pc_insurance_required` به‌ازای کشور |

افزون بر این شش مورد، پژوهش دو عدد قطعی به دست داد که پیش‌تر حدسی بودند: **۱۳ نوع روادید رسمی** با کدهای A تا T به‌علاوهٔ Service و Relatives، و **۶ دستهٔ سیاست ملیتی**.

---

## بخش ۳ — متن اصلی سند هدف

از `CA-P-v1.2-FINAL`، بخش P1:

### Required Fields (متن اصلی)

| فیلد | نوع |
|------|-----|
| name_en · name_fa | Text |
| visa_types | Structured[] (type, duration, eligible_nationalities, cost_range) |
| visa_exempt_countries | Text[] |
| application_process_summary | LongText |
| last_verified_date | Date (CA-P-D004) |
| overview_text | LongText (500+ کلمه) |
| hero_image | Image |
| meta_title · meta_description | Text |

### Optional Fields (متن اصلی)

| فیلد | نوع |
|------|-----|
| official_application_portal | URL |
| processing_time_estimate | Text |
| extension_info | Text |
| entry_points_reference | Text[] — Content Layer (CA-P-D002) |

### Schema Readiness (متن اصلی)

نوع `GovernmentService` با ویژگی‌های `name`، `description`، `serviceType`، `areaServed` و `dateModified`.

---

## بخش ۴ — متن جایگزین مصوب

### ۴.۱ فیلدهایی که به متاباکس مشترک منتقل می‌شوند

هفت فیلد از فهرست الزامی `CA-P` در معماری بازسازی‌شده (`RB-D001`) از متاباکس مشترک تأمین می‌شوند و در متاباکس اختصاصی P1 تکرار **نمی‌شوند**:

`name_en` · `name_fa` · `overview_text` · `hero_image` · `meta_title` · `meta_description` · `last_verified_date`

افزون بر این، `name_ar`، `short_summary`، `ai_summary`، `entity_uid`، `content_status`، `source_references`، `faq_items`، `name_local`، `alternative_names`، `featured_image_gallery`، `video_sample` و `audio_narration` نیز از همان متاباکس می‌آیند. فهرست نوزده‌فیلدی با اسکرین‌شات پنل در ۲۵ اوت ۲۰۲۶ راستی‌آزمایی شد.

**بلوک «پاسخ سریع»** که الگوی صفحهٔ `CA-P` آن را AEO-critical خوانده، فیلد تازه نمی‌گیرد و به `short_summary` نگاشت می‌شود (**P5-D084**).

### ۴.۲ جدول Required Fields — جایگزین

| فیلد | نوع | تغییر نسبت به CA-P |
|------|-----|---------------------|
| `visa_types` | Repeater با ۹ زیرفیلد | گسترش‌یافته — بخش ۴.۳ |
| `visa_policy_matrix` | Repeater با ۱۰ زیرفیلد | **جایگزین `visa_exempt_countries`** — بخش ۴.۴ |
| `application_process_summary` | WYSIWYG | بدون تغییر |
| `application_steps` | Repeater با ۳ زیرفیلد | **تازه** |
| `passport_validity_months` | Number | **تازه** |
| `insurance_required` | Switcher | **تازه** |
| ~~`visa_exempt_countries`~~ | ~~Text[]~~ | **حذف — جذب‌شده در `visa_policy_matrix`** |

### ۴.۳ گسترش `visa_types`

| زیرفیلد | نوع | کلاس WPML | نقش |
|---------|-----|-----------|------|
| `vt_code` | Select ← `p1_visa_type` | Copy | **کلید پایدار ردیف** — اعمال P5-D074 |
| `vt_stay_days` | Number | Copy | حداکثر اقامت بر حسب روز |
| `vt_validity_days` | Number | Copy | اعتبار خود روادید |
| `vt_multiple_entry` | Switcher | Copy | چندورودی |
| `vt_extendable` | Switcher | Copy | قابل تمدید |
| `vt_channels` | Checkbox ← `p1_visa_channel` | Copy | سفارت / الکترونیکی / فرودگاهی |
| `vt_processing_note` | Text | Translate | زمان پردازش |
| `vt_required_docs` | Textarea | Translate | مدارک لازم |
| `vt_note` | Textarea | Translate | شرایط و محدودیت‌ها |

**دو حذف عمدی:**

- `eligible_nationalities` حذف و به `visa_policy_matrix` منتقل شد (**P5-D086**). داشتن دادهٔ تابعیت در دو محل، روی صفحه‌ای که خودِ `CA-P` آن را «بالاترین هزینهٔ خطا در کل Category P» نامیده، دو منبع حقیقتِ قابل‌واگرایی می‌سازد.
- `cost_range` از سطح نوع روادید به سطح تابعیت منتقل شد. پژوهش نشان داد هزینهٔ روادید ایران بر پایهٔ **اصل عمل متقابل سیاسی** تعیین می‌شود و به تابعیت وابسته است، نه به نوع روادید — بریتانیا حدود ۲۰۰ یورو، بلاروس حدود ۱۲ یورو، برای یک نوع روادید یکسان.

### ۴.۴ جایگزینی `visa_exempt_countries` با `visa_policy_matrix`

| زیرفیلد | نوع | کلاس WPML |
|---------|-----|-----------|
| `pc_country` | Select ← `country_iso` | Copy |
| `pc_policy_code` | Select ← `p1_policy_category` | Copy |
| `pc_stay_days` | Number | Copy |
| `pc_condition` | Select ← `p1_entry_condition` | Copy |
| `pc_voa_eligible` | Switcher | Copy |
| `pc_insurance_required` | Switcher | Copy |
| `pc_fee_min_eur` | Number | Copy |
| `pc_fee_max_eur` | Number | Copy |
| `pc_effective_date` | Date | Copy |
| `pc_note` | Textarea | Translate |

**یک ردیف به‌ازای هر کشور — حدود ۲۰۰ ردیف.**

**چرا این Entity Inflation نیست:** هیچ Post Type، هیچ رکورد، هیچ صفحه و هیچ URL تازه‌ای ساخته نمی‌شود. کل ساختار یک Structured Field روی موجودیت موجود P1 است — همان الگویی که `CA-P` برای RouteStop و HolidayInstance تجویز کرده بود.

**چرا بار ترجمه ندارد:** نُه زیرفیلد از ده زیرفیلد کلاس Copy دارند و هرگز به مترجم نشان داده نمی‌شوند. تنها `pc_note` ترجمه‌شدنی است و انتظار می‌رود روی حدود ۲۵ کشور پر شود. یعنی ماتریس دویست‌ردیفی حدود بیست‌وپنج رشتهٔ ترجمه تولید می‌کند، نه دویست.

**چرا کد ISO و نه نام کشور:** نام کشور در `Value` ذخیره نمی‌شود؛ فقط کد. برچسب فارسی، انگلیسی و عربی یک‌بار در واژه‌نامهٔ سراسری `country_iso` تعریف و از راه WPML String Translation ترجمه می‌شود — یک بار برای کل پروژه، نه به‌ازای هر رکورد. همان مکانیزمی که `P5-D040` برای همهٔ فیلدهای Select تثبیت کرده.

### ۴.۵ فیلدهای تازه بر پایهٔ یافته‌های پژوهش

| گروه | فیلدها |
|------|---------|
| فرآیند | `application_steps` (`step_order` · `step_title` · `step_text`) · `portal_links` (`portal_url` · `portal_label` · `portal_scope`) · `document_specs` (`doc_type` · `doc_min_px` · `doc_format` · `doc_note`) |
| مناطق آزاد | `free_zone_stay_days` · `free_zone_mainland_allowed` · `free_zone_excluded_iso` · `free_zone_note` |
| مبادی ورودی | `voa_entry_points` (`ep_code` · `ep_type` · `ep_name`) |
| الزامات | `passport_validity_months` · `insurance_required` · `insurance_note` · `passport_stamping_note` · `dual_nationality_note` · `restricted_entry_note` · `special_passport_note` |
| تمدید و هزینه | `extension_max_count` · `extension_days_each` · `max_total_stay_days` · `fee_currency` · `fee_policy_note` |
| تور اجباری | `guided_tour_note` |
| حاکمیت داده | `policy_change_log` (`chg_date` · `chg_scope` · `chg_summary` · `chg_source_url`) |

**حذف `processing_time_estimate`** از سطح موجودیت (**P5-D087**). زمان پردازش ذاتاً به نوع روادید وابسته است: ده روز کاری برای روادید الکترونیکی عادی در برابر چهل تا شصت روز کاری برای شهروندان آمریکا، بریتانیا و کانادا. یک عدد واحد در سطح صفحه، اطلاعات نادرست تولید می‌کند.

### ۴.۶ اصلاح Schema Readiness

متن اصلی `CA-P` ویژگی `dateModified` را روی گرهٔ `GovernmentService` تجویز کرده بود.

**این نامعتبر است.** `dateModified` در Schema.org روی `CreativeWork` و `DataFeedItem` تعریف شده، نه روی `Thing` و نه روی `Service`. شاخهٔ ارث‌بری `GovernmentService ← Service ← Intangible ← Thing` هیچ‌کدام این ویژگی را ندارند.

این همان دستهٔ خطایی است که سند وضعیت زندهٔ v9.0 بخش ۴ به‌عنوان «درس گران‌قیمت» ثبت کرده بود: `inLanguage` و `abstract` روی `TouristTrip`، و `population` روی `City`.

**متن جایگزین:**

| منبع داده | ویژگی Schema | گره |
|-----------|--------------|------|
| `name_en` | `name` | GovernmentService |
| `short_summary` | `description` | GovernmentService |
| `hero_image` | `image` | GovernmentService |
| ثابت | `serviceType` = "Visa Application" | GovernmentService |
| G1 از رابطهٔ ۱۸۵ | `areaServed` → `Country` با `@id` مبتنی بر `entity_uid` | GovernmentService |
| ثابت | `provider` → `GovernmentOrganization` | GovernmentService |
| `official_application_portal` | `availableChannel` → `ServiceChannel.serviceUrl` | GovernmentService |
| `visa_types[]` | `hasOfferCatalog` → `OfferCatalog` → `Offer.itemOffered` | GovernmentService |
| `application_steps[]` | `HowTo` → `HowToStep` با `position` | گرهٔ مستقل |
| `faq_items` | `Question` / `Answer` | گرهٔ `FAQPage` موجود |
| `last_verified_date` | `dateModified` | **`WebPage` — نه `GovernmentService`** |

`additionalProperty` روی این نوع مجاز نیست؛ فقط روی `Place` و `Product` تعریف شده.

**قاعدهٔ عمومی تثبیت‌شده:** پیش از افزودن هر ویژگی به هر نوع، شاخهٔ ارث‌بری آن نوع در Schema.org راستی‌آزمایی شود.

---

## بخش ۵ — انطباق با معماری

| بررسی | نتیجه | شرح |
|--------|--------|------|
| Entity Inflation | ✅ صفر | هیچ Post Type یا رکورد تازه‌ای ساخته نشد. VisaType و NationalityPolicy هر دو Structured Field باقی ماندند |
| Relationship Inflation | ✅ صفر | فقط رابطهٔ R22 مصوب (`applies_g1_p1`، شناسهٔ ۱۸۵) |
| Geography First (P-01) | ✅ حفظ شد | لنگر جغرافیایی P1 همان R22 → G1 است |
| One Physical Place = One Primary Node (P-02) | ✅ حفظ شد | `entry_points_reference` و `voa_entry_points` هر دو Content Layer‌اند و گرهٔ فرودگاه یا مرز تازه نمی‌سازند |
| DEC-004 (R22 Application Matrix) | ✅ منطبق | G1 = Full · G3 و G5 = Link only، به‌صورت لینک ایستا در قالب |
| CA-P-D002 (Content Layer Convention) | ✅ منطبق | هر ارجاع بدون مبنای IA به‌صورت Text باقی ماند |
| CA-P-D004 (Volatility Policy) | ✅ تقویت شد | افزون بر `last_verified_date`، فیلدهای `pc_effective_date` و `policy_change_log` عمق زمانی می‌دهند |
| Architecture Freeze | ✅ INTACT | صفر موجودیت · صفر رابطه · صفر محدودیت · صفر دسته |
| قاعدهٔ متای یال | ✅ رعایت شد | `Register Relation Meta Fields` روی رابطهٔ ۱۸۵ خاموش است |

---

## بخش ۶ — نتیجهٔ آزمون مسدودکننده

`visa_policy_matrix` با حدود دویست ردیف، بزرگ‌ترین Repeater تاریخ پروژه است. بزرگ‌ترین آزموده‌شده تاکنون `route_stop_notes` با ده ردیف بوده (P5-T004-B).

`CA-P-AMENDMENT-001` بخش ۴.۳ هشدار داده که errata رسمی و همچنان باز WPML، جابه‌جایی یا حذف ردیف Repeater در زبان پیش‌فرض را عامل ناهم‌گامی ترجمه‌ها می‌داند.

**آزمون `P5-T005` در ۲۷ اوت ۲۰۲۶ اجرا شد و نتیجه PASS بود.**

ماتریس با ۱۹۹ ردیف آزموده شد: ذخیره در ۷۵٬۲۰۲ بایت، رندر جت‌انجین در ۷ ثانیه، ویرایشگر ترجمه بدون خطا باز شد و **دقیقاً ۲۵ واحد ترجمه** نشان داد — نه ۱۹۹. هر ده زیرفیلد کلاس Copy عیناً به نسخهٔ انگلیسی منتقل شدند.

راه‌حل جایگزین (شکستن به شش Repeater) **لازم نشد و کنار گذاشته می‌شود**.

**یک قید:** حذف ردیف در زبان پیش‌فرض به زبان‌های ثانویه منتقل نمی‌شود. این نقص در مکانیزم WPML است، نه در طراحی، و با قاعدهٔ ادیتوریال `P5-D109` مهار می‌شود: ردیف حذف نمی‌شود، `pc_policy_code` به `not_applicable` تغییر می‌کند. مقدار `not_applicable` به واژه‌نامهٔ `p1_policy_category` افزوده شد.

جزئیات کامل در `P5-T005-RESULTS`.

---

## بخش ۷ — تصمیمات ثبت‌شده

| ID | تصمیم |
|----|--------|
| P5-D081 | CPT `tp_visa` · Rewrite Slug `visa` · آیکون `dashicons-id-alt` |
| P5-D082 | اتصال متاباکس مشترک گام اجباری؛ صفر تکرار فیلد مشترک |
| P5-D083 | R22 فقط به G1؛ G3 و G5 لینک ایستا |
| P5-D084 | بلوک «پاسخ سریع» به `short_summary` نگاشت می‌شود |
| P5-D085 | `visa_exempt_countries` با `visa_policy_matrix` به‌ازای کشور جایگزین می‌شود |
| P5-D086 | `eligible_nationalities` به ماتریس منتقل شد — منبع یگانهٔ حقیقت |
| P5-D087 | `processing_time_estimate` به زیرفیلد `vt_processing_note` تبدیل شد |
| P5-D088 | Repeaterها با کد پایدار کلید می‌خورند، نه موقعیت ردیف |
| P5-D089 | `dateModified` به گرهٔ `WebPage` منتقل می‌شود |
| P5-D090 | افزودنی `wpml-config` یکجا پس از پایان دستهٔ P |
| P5-D091 | مکانیزم تولید نام کشور از کد ISO، تصمیم فاز ۶ است |
| P5-D092 | واژه‌نامهٔ `country_iso` سراسری — استثنای مصوب `RB-D020` |
| P5-D093 | رژیم مناطق آزاد گروه فیلد مستقل |
| P5-D094 | `policy_change_log` به‌عنوان فیلد حاکمیت داده |
| P5-D095 | `application_steps` با نگاشت `HowTo`/`HowToStep` |
| P5-D096 | `audio_file` و `audio_duration` کلاس Translate — روایت شنیداری هر زبان ضبط مستقل است |
| P5-D097 | راستی‌آزمایی برنامه‌ای اجباری `wpml-config` پیش از هر پیست |

---

## بخش ۸ — قاعدهٔ کاری برای تیم محتوا

| قاعده | دلیل |
|--------|-------|
| ترتیب ردیف‌های `visa_types` و `visa_policy_matrix` **هیچ معنایی ندارد** | مرتب‌سازی در زمان رندر از `vt_code` و `pc_country` مشتق می‌شود |
| `vt_code` و `pc_country` پس از ورود داده **هرگز تغییر نکنند** | نگاشت ترجمه و JSON-LD به همین کلیدها وابسته است |
| `pc_country` فقط کد ISO دوحرفی — هرگز نام کشور | نام از واژه‌نامه در زمان رندر تولید می‌شود |
| `pc_fee_min_eur` و `pc_fee_max_eur` همیشه بازه، هرگز عدد قطعی | `CA-P-D004` |
| هر تغییر سیاست، یک ردیف در `policy_change_log` بگیرد | سیگنال E-E-A-T و تازگی برای موتورهای پاسخ |
| `audio_duration` با قالب عددی زبان‌ناوابسته وارد شود (مثل 12:34) | کلاس Copy ندارد ولی قالب باید یکدست بماند |

---

## بخش ۹ — موارد باز

| مورد | وضعیت |
|------|--------|
| ممیزی مستقل NotebookLM | انجام نشده |
| تأیید مالک برای P5-D085 | انجام نشده |
| آزمون P5-T005 | اجرا نشده — مسدودکنندهٔ ورود داده |
| آزمون P5-T006 | اجرا نشده — مسدودکنندهٔ تولید فایل صوتی، نه P1 |
| جدول رسمی هزینه به تفکیک ملیت | منتشر نشده توسط MFA — دادهٔ هزینه از منابع ثالث و با درجهٔ اطمینان پایین‌تر ثبت می‌شود |

---

*ToPersia — CA-P-AMENDMENT-002 — نسخهٔ ۱.۰*
*اصلاحیهٔ پیشنهادی بر `CA-P-v1.2-FINAL` — بخش P1*
*مرجع: IA-v1.2.1-FINAL (Frozen) · Architecture Freeze: INTACT*
