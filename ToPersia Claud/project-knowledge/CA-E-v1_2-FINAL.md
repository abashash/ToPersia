# ToPersia — Phase 3: Content Architecture
## CA-E-v1.2-FINAL
### Category E — Events
### نسخه نهایی — تمام ۴ موجودیت
#### مرجع: IA-v1.2.1-FINAL (Frozen) | تصمیمات اعمال‌شده: CA-E-D001 تا CA-E-D005

> **Changelog v1.0 → v1.1:** AUDIT-PATCH-002 (E1 `visa_info_link`، E6 `safety_link` → Content Layer) و AUDIT-PATCH-003 (E5 `etiquette_link` + `safety_link` → Content Layer، شامل تصحیح شماره موجودیت از E3 اشتباه به E5 درست) و AUDIT-PATCH-005 (فعال‌سازی `event_travel_impact` روی E1 — R23→P6) اجرا و با grep تأیید شد. جزئیات کامل: PHASE3-VALIDATION-LOG-v1_1.md.
> **Changelog v1.1 → v1.2:** بدون تغییر محتوا — هم‌ترازسازی نسخهٔ مجموعه با CA-C-v1.2.

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| پروژه | ToPersia |
| فاز | Phase 3 — Content Architecture |
| Category | E — Events |
| نسخه | 1.2-FINAL |
| وضعیت | APPROVED — FINAL |
| مرجع IA | IA-v1.2.1-FINAL (Frozen) |
| مراجع CA | CA-G-v1.0-FINAL · CA-A-v1.0-FINAL · CA-C-v1.0-FINAL · CA-D-v1.0-FINAL |
| تصمیمات اعمال‌شده | CA-E-D001 · CA-E-D002 · CA-E-D003 · CA-E-D004 · CA-E-D005 |
| موجودیت‌های پوشش‌داده‌شده | ۴ / ۴ |
| Architecture Freeze | INTACT |

---

## بخش ۱ — اقدام اصلاحی CA-E-D001: Patch CA-A-v1.0-FINAL

طبق CA-E-D001 (گزینه A تأییدشده)، پیش از استفاده از این سند باید سه موضع در CA-A-v1.0-FINAL اصلاح شود:

| موضع | از | به |
|------|----|----|
| خط ۷۳۴ | "...venue رویدادها باشد (E6 — Seasonal/Natural Event)." | "...venue رویدادها باشد (E1/E3/E5). E6 از طریق R16→G3/G5 متصل می‌شود — نه R15." |
| خط ۷۷۳ | "— R15 ← E6: رویدادهای طبیعی/فصلی در پارک" | "— R16 ← E6: رویدادهای فصلی/طبیعی (از طریق G3/G5 مجاور پارک)" |
| خط ۸۲۹ | "R15 ← E6 \| رویدادهای طبیعی \| Conditional section" | *حذف کامل این سطر* |

**Rationale:** IA Header کاتاگوری E صریح می‌گوید "All connect to G via HELD_AT **or** HELD_IN" — E6 از طریق R16 (HELD_IN) به G وصل می‌شود. R15 Sources در IA: "E1, E3, E5" — E6 غایب و این غیبت intentional است.

---

## بخش ۲ — Decision Log Category E

| Decision ID | Finding | تصمیم | Revisable |
|------------|---------|--------|-----------|
| CA-E-D001 | CA-E-F001 | گزینه A: اصلاح CA-A — E6 هرگز R15 ندارد | No |
| CA-E-D002 | CA-E-F002 | E2/E4: بدون entity — FIR-E-001 | Yes |
| CA-E-D003 | CA-E-F003 | E3∉R17/R18: Intentional — FIR-E-002؛ بازی‌های بومی از Content Layer | Yes |
| CA-E-D004 | CA-E-F004 | is_recurring (Required) + event_frequency (Optional) در E1/E3 | Yes |
| CA-E-D005 | CA-E-F005 | Substitution Rule: R15 OR venue_name — هرگز هم‌زمان | Yes |

### جدول نهایی E entity × Relationship

| Entity | R15 | R16 | R17 | R18 | R19 | R25 | is_recurring |
|--------|-----|-----|-----|-----|-----|-----|--------------|
| E1 | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ Required |
| E3 | ✅ | ✅ | ❌ | ❌ | — | ✅ | ✅ Required |
| E5 | ✅ | ✅ | ✅ | ✅ | — | ✅ | — (همیشه Annual) |
| E6 | **❌** | ✅ | ❌ | ❌ | ✅ | ✅ | — (همیشه Seasonal) |

---

## بخش ۳ — Content Architecture — ۴ موجودیت

---

## E1 — Festival / Event | جشنواره / رویداد

### ۱. Purpose

E1 گسترده‌ترین موجودیت Category E است — همه جشنواره‌های فرهنگی، رویدادهای هنری، نمایشگاه‌ها و رویدادهای گردشگری را پوشش می‌دهد. دو ویژگی معماری منحصربه‌فرد:

اول، **دامنه Temporal:** "Recurring or one-time — defined date." این تنها Category E entity است که می‌تواند یک‌بار یا هر چند سال برگزار شود — و این تمایز باید در داده‌های ساختاریافته بازتاب یابد (CA-E-D004).

دوم، **غنی‌ترین مجموعه روابط:** R15 (venue) + R16 (area) + R17 (instance of C10) + R18 (celebrates C10/C12/C13) + R25 (best_season). E1 می‌تواند نمونه یک آیین فرهنگی پایدار باشد (R17→C10) و هم‌زمان با شخصیت تاریخی یا دوره‌ای مرتبط باشد (R18→C12/C13).

**Constraint C4 — قطعی:** E1 هرگز R20 ندارد — هیچ designation یونسکو یا هر designation دیگری.

### ۲. Page Template: Festival / Event

```
[Hero Section]
  — تصویر جشنواره در اوج برگزاری
  — نام (FA / EN) + نام محلی
  — وضعیت: سالانه / دوسالانه / یک‌بار
  — بازه زمانی برگزاری

[Identity Block — Structured — AEO-critical]
  — نام رسمی | نام‌های دیگر
  — is_recurring: سالانه / دوسالانه / یک‌بار (CA-E-D004)
  — event_frequency: توضیح دوره (اگر recurring)
  — بازه برگزاری (ماه شمسی/میلادی)
  — مکان اصلی ← R15 → A2/A3/A7/A9/G7
    یا venue_name روی R16 (Substitution Rule — CA-E-D005)
  — استان/شهر ← R16 → G3/G5/G7
  — بهترین فصل ← R25 (best_season)
  — آیین مرتبط ← R17 → C10 (اگر applicable)

[UNESCO / Cultural Heritage Note — ⚠️ صرفاً Editorial]
  — اگر رویداد با C10 ثبت‌شده مرتبط است: اشاره به آیین پایدار، نه designation رویداد
  — هیچ فیلد designation برای E1 — C4

[Overview — 400+ کلمه]
  — تاریخچه | محور اصلی | اهمیت فرهنگی/گردشگری
  — پاسخ AEO در ۱۵۰ کلمه اول

[Program Highlights — Content Layer]
  — فعالیت‌ها، بخش‌ها، رویدادهای جانبی

[When & Where]
  — تقویم دقیق (best از R25 + calendar_timing)
  — لینک venue ← R15 → A entity
  — نقشه G3/G5

[Cultural & Historical Connection — Conditional]
  — R17 → C10: «این رویداد نمونه‌ای از [آیین] است» — با لینک
  — R18 → C10/C12/C13: «به یاد [شخص/دوره]» — با لینک

[Practical Information]
  — لینک اجباری P1 (ویزا) برای بین‌المللی
  — لینک اختیاری P4 (ایمنی) اگر رویداد outdoor است

[Schema Block]
```

### ۳. Required Fields

| فیلد | نوع | منبع |
|------|-----|------|
| name_en | Text | — |
| name_fa | Text | — |
| is_recurring | Boolean | CA-E-D004 |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | R16 |
| best_season | Enum (spring/summer/autumn/winter) | R25 |
| overview_text | LongText (400+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### ۴. Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| event_frequency | Text | CA-E-D004 — اگر is_recurring:true |
| held_at | Relation → A2(venue_eligible:true)/A3/A7/A9/G7 | R15 — اگر venue مشخص و A entity دارد |
| venue_name | Text | DC-R-003 روی R16 — **فقط اگر held_at خالی است** (CA-E-D005) |
| calendar_timing | Text | زمان دقیق (ماه / هفته) |
| instance_of_ritual | Relation → C10 | R17 — اگر رویداد نمونه یک آیین C10 است |
| celebrates | Relation[] → C10/C12/C13 | R18 |
| name_local | Text | — |
| featured_image_gallery | Image[] | — |
| visa_info_link | Text (نام+URL به P1) | Content Layer — اختیاری، برای رویدادهای بین‌المللی |
| event_travel_impact | Relation → P6 | R23 — فعال (CA-P-FINAL منتشر شده؛ الگو مطابق travel_impact در C10) |

**فیلدهای عمداً غایب:** designation هر نوع (C4 — قطعی)

### ۵. Internal Links

| رابطه | هدف | نوع |
|--------|-----|-----|
| R16 → G3/G5/G7 | صفحه استان/شهر | لنگر جغرافیایی اصلی |
| R15 → A2/A3/A7/A9/G7 | صفحه venue | Conditional — اگر held_at مقدار دارد |
| R17 → C10 | آیین پایدار | Conditional |
| R18 → C10/C12/C13 | گرامیداشت | Conditional |
| Content → P1 | ویزا | برای رویدادهای بین‌المللی |
| R23 → P6 | تقویم و مناسبت‌های سفر | فعال |

### ۶. Aggregation Pages

| صفحه | نوع | مبنا |
|------|-----|------|
| «جشنواره‌ها و رویدادهای ایران» — Hub | پیش‌فرض | همه E1 instances |
| «جشنواره‌های [استان/شهر]» | شرطی | R16→G3/G5 + آزمون D-007 |
| «جشنواره‌های فرهنگی ایران» | شرطی | R17→C10 filter |
| «تقویم رویدادهای ایران» | پیش‌فرض | همه E entities + best_season |

### ۷. SEO Purpose

- کوئری‌های هدف: "Iran festivals"، "Tehran film festival"، "جشنواره فیلم فجر"، "Iran cultural events [year]"
- صفحه تقویم رویدادها: یکی از پربازدیدترین صفحات پروژه برای گردشگر برنامه‌ریز
- Temporal SEO: محتوای «upcoming events» رتبه‌بندی فصلی بالا دارد

### ۸. AEO Purpose

- "What festivals are held in Iran?"
- "When is [festival] in Iran?"
- "What is the [festival] about?"
- "Is [festival] suitable for tourists?"
- "What events are on in [city] in [month]?"

### ۹. Schema Readiness *(baseline — Phase 4 نهایی)*

```json
{
  "@type": "Event",
  "name": "[name_en]",
  "alternateName": "[name_fa]",
  "description": "[چکیده ۱۵۰ کلمه]",
  "location": {
    "@type": "Place",
    "name": "[G5 name یا venue_name]",
    "address": { "@type": "PostalAddress", "addressCountry": "IR" }
  },
  "eventSchedule": "[اگر is_recurring:true → Schedule با repeatFrequency]",
  "startDate": "[تاریخ — اگر مشخص]",
  "endDate": "[تاریخ — اگر مشخص]",
  "organizer": { "@type": "Organization", "name": "[برگزارکننده]" },
  "superEvent": "[URL C10 — اگر R17 دارد]"
}
```

**نکته `superEvent`:** قرینه دقیق R17 در لایه markup — رویداد E1 به آیین پایدار C10 اشاره می‌کند.

### ۱۰. MVC Requirements — AEO-First

- ✅ name_en + name_fa + is_recurring
- ✅ held_in → حداقل G5/G3
- ✅ best_season
- ✅ overview_text ≥ 400 کلمه
- ✅ calendar_timing (ماه برگزاری)
- ✅ Schema.org Event کامل
- ✅ hero_image

### ۱۱. Editorial Notes

- **Substitution Rule (CA-E-D005):** اگر رویداد در یک A entity برگزار می‌شود → held_at (R15) را مقداردهی کن و venue_name را خالی بگذار. اگر venue هنوز A entity نشده → venue_name روی R16 بنویس و held_at خالی بگذار. هر دو با هم = داده تکراری و نقض P-06.
- **C4 — ممنوعیت مطلق designation:** حتی اگر منبعی از «ثبت» جشنواره نام ببرد، هیچ R20 ثبت نشود. اگر جشنواره با C10 ثبت‌شده مرتبط است، R17→C10 را مقداردهی کن و در prose از آیین پایدار نام ببر — این راه درست است.
- **is_recurring: False = آرشیو trigger:** رویدادهای یک‌بار برگزارشده (is_recurring:false + endDate در گذشته) باید در Editorial Workflow به «Past Event» تبدیل شوند — از لیست «upcoming» خارج و در بایگانی تاریخی باقی می‌مانند.
- **بازی‌های بومی (CA-E-D003):** اگر E1 یک رویداد با زمینه فرهنگی/آیینی است، R17→C10 مجاز است. اگر رویداد ورزشی-فرهنگی مشترک است → بررسی کن آیا بیشتر E1 است یا E3، سپس از Content Layer reference برای لینک به C10 استفاده کن.

---

## E3 — Sports Event | رویداد ورزشی

### ۱. Purpose

E3 رویدادهای ورزشی ایران را پوشش می‌دهد — از مسابقات بین‌المللی تا رقابت‌های منطقه‌ای. از نظر ساختار رابطه، ساده‌ترین E entity است: فقط R15 + R16 + R25. **E3 ∉ R17/R18** (CA-E-D003 — Intentional): ورزش ذاتاً secular است و در مدل IA به C entities وصل نمی‌شود — موارد مرزی (بازی‌های بومی با زمینه فرهنگی) از Content Layer پوشش می‌یابند.

مانند E1، دامنه Temporal «Recurring or one-time» دارد — `is_recurring` Required است (CA-E-D004).

**Constraint C4:** E3 هرگز R20 ندارد.

### ۲. Page Template: Sports Event

```
[Hero Section]
  — تصویر رویداد در حین برگزاری
  — نام رویداد (FA / EN)
  — رشته ورزشی | سطح (محلی/ملی/بین‌المللی)
  — دوره‌ای / یک‌بار

[Identity Block — Structured — AEO-critical]
  — نام رسمی | نام‌های دیگر
  — رشته ورزشی
  — is_recurring + event_frequency (CA-E-D004)
  — بازه برگزاری (ماه)
  — مکان اصلی ← R15 یا venue_name (CA-E-D005)
  — استان/شهر ← R16 → G3/G5/G7
  — بهترین فصل ← R25

[Overview — 400+ کلمه]
  — تاریخچه | اهمیت | سطح رقابت
  — پاسخ AEO در ۱۵۰ کلمه اول

[Event Details — Content Layer]
  — رشته‌ها | سطح مشارکت | رکوردها و افتخارات

[Traditional Sports Connection — Conditional — Content Layer]
  — فقط اگر رویداد ریشه فرهنگی/بومی دارد
  — اشاره متنی (نام + URL به C10 مرتبط) — CA-E-D003؛ نه Relation

[When & Where]
  — تقویم + لینک venue (R15 → A entity)

[Visitor Information]
  — لینک P1 (ویزا) اگر بین‌المللی
  — لینک P4 (ایمنی) اگر outdoor

[Schema Block]
```

### ۳. Required Fields

| فیلد | نوع | منبع |
|------|-----|------|
| name_en | Text | — |
| name_fa | Text | — |
| is_recurring | Boolean | CA-E-D004 |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | R16 |
| best_season | Enum | R25 |
| overview_text | LongText (400+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### ۴. Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| event_frequency | Text | CA-E-D004 |
| held_at | Relation → A2/A3/A7/A9/G7 | R15 — C5 برای A2 |
| venue_name | Text | DC-R-003 — فقط اگر held_at خالی (CA-E-D005) |
| calendar_timing | Text | — |
| sport_type | Text | Content Layer |
| competition_level | Enum (Local/National/International) | Content Layer |
| traditional_sports_reference | Text (نام + URL به C10) | **Content Layer** — CA-E-D003؛ نه Relation |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:** instance_of_ritual (E3∉R17) · celebrates (E3∉R18) · designation (C4)

### ۵. Internal Links

| رابطه | هدف | نوع |
|--------|-----|-----|
| R16 → G3/G5/G7 | صفحه منطقه | لنگر جغرافیایی |
| R15 → A2/A3/A7/A9/G7 | venue | Conditional |
| Content → C10 | آیین ورزش بومی | اشاره متنی — CA-E-D003 |

### ۶. Aggregation Pages

| صفحه | نوع | مبنا |
|------|-----|------|
| «رویدادهای ورزشی ایران» — Hub | پیش‌فرض | همه E3 instances |
| «رویدادهای ورزشی [استان]» | شرطی | R16→G3 + D-007 |

### ۷. SEO Purpose

- کوئری‌های هدف: "Iran sports events"، "Iran wrestling championship"، «مسابقات چوگان ایران»
- رویدادهای بین‌المللی: بالاترین پتانسیل ترافیک ورودی

### ۸. AEO Purpose

- "What sports events are held in Iran?"
- "When is [sport] championship in Iran?"
- "Where is [sport event] held?"

### ۹. Schema Readiness

```json
{
  "@type": "SportsEvent",
  "name": "[name_en]",
  "description": "[چکیده]",
  "sport": "[sport_type]",
  "location": { "@type": "Place", "name": "[venue یا G5]" },
  "startDate": "[تاریخ]",
  "eventSchedule": "[اگر is_recurring:true]"
}
```

`SportsEvent` type بومی Schema.org است — مزیت ساختاری مستقیم.

### ۱۰. MVC Requirements

- ✅ name_en + name_fa + is_recurring + sport_type
- ✅ held_in → حداقل G5
- ✅ best_season
- ✅ overview_text ≥ 400 کلمه
- ✅ Schema.org SportsEvent
- ✅ hero_image

### ۱۱. Editorial Notes

- **Substitution Rule (CA-E-D005):** همان قاعده E1.
- **بازی‌های بومی (CA-E-D003):** چوگان، کشتی گیله‌مردی، زورخانه — اگر رویداد E3 این ریشه را دارد، `traditional_sports_reference` (Text + URL به C10) را مقداردهی کن. هیچ R17/R18 ثبت نشود.
- **مرز E3 با E1:** اگر رویداد ورزشی بخش مهمی از یک جشنواره فرهنگی است → E1. اگر ورزش محور اصلی است → E3.

---

## E5 — Public Religious Ceremony | مراسم مذهبی عمومی

### ۱. Purpose

E5 مراسم مذهبی عمومی ایران را پوشش می‌دهد — رویدادهایی که هر سال برگزار می‌شوند اما تاریخ آن‌ها بر اساس تقویم قمری/شمسی متغیر است. از نظر ساختار رابطه، E5 با E1 شباهت دارد: R15 + R16 + R17 + R18 + R25.

**تفاوت کلیدی از E1:** E5 همیشه Annual است — `is_recurring` ضمنی و نیازی به فیلد ندارد. `calendar_timing` باید تقویم قمری/شمسی را منعکس کند.

**حساسیت بالا:** محتوای مرتبط با مراسم مذهبی در ۹ زبان به لحن دقیق نیاز دارد.

**Constraint C4:** E5 هرگز R20 ندارد — حتی اگر مراسم به یک C10 ثبت‌شده مرتبط باشد. پیوند فقط از طریق R17→C10 است.

### ۲. Page Template: Public Religious Ceremony

```
[Hero Section]
  — تصویر مراسم با احترام به محدودیت‌های تصویربرداری
  — نام (FA / EN)
  — بازه تقویمی (قمری/شمسی)

[Identity Block — Structured — AEO-critical]
  — نام رسمی | نام‌های محلی
  — تقویم برگزاری (calendar_timing — قمری یا شمسی)
  — تکرار: سالانه (Annual — ثابت)
  — مکان اصلی ← R15 → A2/A3/G7
    یا venue_name روی R16 (CA-E-D005)
  — استان‌ها و شهرها ← R16 → G3/G5/G7
  — آیین پایدار مرتبط ← R17 → C10

[Overview — 500+ کلمه]
  — ریشه مذهبی | اهمیت | مناسک اصلی
  — لحن مستند، بی‌طرف، محترمانه
  — پاسخ AEO در ۱۵۰ کلمه اول

[Ritual Elements]
  — مراحل برگزاری | اشیاء و نمادها | موسیقی (C6 — Content Layer)

[Regional Variations]
  — تفاوت برگزاری در مناطق/اقوام مختلف

[Cultural Connection — R17/R18]
  — R17 → C10: لینک به آیین پایدار مرتبط
  — R18 → C12/C13: اگر مراسم با شخصیت/دوره مرتبط است

[Visitor Guidance]
  — رفتار احترامی | محدودیت تصویربرداری | پوشش
  — لینک P5 (آداب سفر) — مهم

[When — Timing Complexity]
  — تقویم محاسبه‌ای: «در ایران این مراسم در [ماه قمری] برگزار می‌شود که معمولاً با [ماه میلادی] همپوشانی دارد»

[Schema Block]
```

### ۳. Required Fields

| فیلد | نوع | منبع |
|------|-----|------|
| name_en | Text | — |
| name_fa | Text | — |
| calendar_timing | Text | — تقویم قمری یا شمسی — Required برای E5 |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | R16 |
| instance_of_ritual | Relation → C10 | R17 — Required برای E5 |
| best_season | Enum | R25 |
| overview_text | LongText (500+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**یادداشت:** `is_recurring` برای E5 Required نیست — E5 همیشه Annual است و این در calendar_timing مستقیم بیان می‌شود.

### ۴. Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| held_at | Relation → A2/A3/G7 | R15 — C5 برای A2 |
| venue_name | Text | DC-R-003 — فقط اگر held_at خالی (CA-E-D005) |
| celebrates | Relation[] → C10/C12/C13 | R18 |
| name_local | Text[] | نام‌های محلی متعدد |
| etiquette_link | Text (نام+URL به P5) | Content Layer — لینک آداب سفر |
| safety_link | Text (نام+URL به P4) | Content Layer — اگر تجمع بزرگ |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:** is_recurring (ضمنی Annual) · event_frequency · designation (C4)

### ۵. Internal Links

| رابطه | هدف | نوع |
|--------|-----|-----|
| R16 → G3/G5/G7 | مناطق برگزاری | لنگر جغرافیایی |
| R15 → A2/A3/G7 | مکان اصلی | Conditional |
| R17 → C10 | آیین پایدار | **Required — قلب معماری E5** |
| R18 → C10/C12/C13 | گرامیداشت | Conditional |
| Content → P5 | آداب سفر | اختیاری اما توصیه‌شده |
| Content → P4 | ایمنی | اختیاری — اگر تجمع بزرگ |

### ۶. Aggregation Pages

| صفحه | نوع | مبنا |
|------|-----|------|
| «مراسم مذهبی ایران» — Hub | پیش‌فرض | همه E5 instances |
| «مراسم مذهبی [استان]» | شرطی | R16→G3 + D-007 |

### ۷. SEO Purpose

- کوئری‌های هدف: "Ashura in Iran"، "Moharram ceremonies Iran"، «مراسم محرم ایران» — evergreen با جستجوی جهانی
- صفحه مرتبط با C10 (نوروز-E5 / E1): پل مستقیم بین محتوای پایدار C10 و محتوای زمان‌بند E5

### ۸. AEO Purpose

- "When is Ashura in Iran?"
- "What happens during Moharram in Iran?"
- "How do Iranians observe [ceremony]?"
- "Is it safe to visit Iran during [ceremony]?"
- "What is the significance of [ceremony]?"

### ۹. Schema Readiness

```json
{
  "@type": "Event",
  "additionalType": "ReligiousCeremony",
  "name": "[name_en]",
  "description": "[چکیده]",
  "eventSchedule": {
    "@type": "Schedule",
    "repeatFrequency": "P1Y",
    "scheduleTimezone": "Asia/Tehran"
  },
  "location": { "@type": "Country", "name": "Iran" },
  "superEvent": "[URL C10 — از R17]"
}
```

### ۱۰. MVC Requirements

- ✅ name_en + name_fa + calendar_timing
- ✅ instance_of_ritual → C10 (Required)
- ✅ held_in → حداقل G3
- ✅ overview_text ≥ 500 کلمه
- ✅ لحن مستند و محترمانه در همه ۹ زبان
- ✅ Schema.org Event + superEvent
- ✅ hero_image

### ۱۱. Editorial Notes

- **لحن و حساسیت:** E5 حساس‌ترین entity Category E است. محتوای فارسی و ۸ زبان دیگر باید از زاویه مردم‌شناختی-فرهنگی باشد، نه تفسیر مذهبی. هیچ قضاوت ارزشی.
- **R17 الزامی است:** هر E5 باید به یک C10 instance وصل باشد. مراسم عزاداری→C10 «مراسم عزاداری»، سیزده‌بدر→C10 «نوروز Rituals». بدون R17، E5 orphaned است.
- **Substitution Rule:** مانند E1/E3.
- **تصویربرداری:** hero_image باید احترامی و بدون تهاجم به حریم مراسم باشد. در editorial guideline مستقل تعریف شود.
- **C4:** اگر C10 مرتبط ثبت یونسکو دارد (مثلاً نوروز)، designation را روی همان C10 اشاره کن — نه روی این E5 instance.

---

## E6 — Seasonal / Natural Event | رویداد طبیعی / فصلی

### ۱. Purpose

E6 ساده‌ترین و در عین حال منحصربه‌فردترین موجودیت Category E است. **سه ویژگی ساختاری که E6 را از سه entity دیگر جدا می‌کند:**

**یک — فقط R16:** E6 هرگز R15 ندارد (CA-E-D001). ماهیت رویداد طبیعی/فصلی (شکوفه، مهاجرت پرندگان، پدیده‌های اقلیمی) با «برگزاری در venue مشخص» سازگار نیست — فقط Geographic Area.

**دو — R19 (OCCURS_DURING):** E6 تنها entity ای است که season attribute رسمی دارد (spring/summer/autumn/winter). این **attribute ذخیره‌شده روی entity** است، نه رابطه گراف.

**سه — بدون R17/R18:** E6 به هیچ C entity وصل نمی‌شود — رویداد طبیعی ذاتاً فرهنگی-آیینی نیست.

**Constraint C4:** E6 هرگز R20 ندارد.

### ۲. Page Template: Seasonal / Natural Event

```
[Hero Section]
  — تصویر طبیعی شاخص (landscape، حیوان، گیاه)
  — نام (FA / EN)
  — فصل | استان‌های اصلی

[Identity Block — Structured — AEO-critical]
  — نام رسمی | نام‌های محلی
  — فصل ← R19 (season attribute)
  — بهترین فصل ← R25 (best_season — معمولاً همان R19)
  — بازه زمانی دقیق‌تر (calendar_timing — هفته‌ها)
  — مناطق اصلی ← R16 → G3/G5/G7
  — ⚠️ هیچ venue مشخص — این یک پدیده طبیعی است (CA-E-D001)

[Overview — 400+ کلمه]
  — توضیح علمی-طبیعی | زمینه فرهنگی | ارزش گردشگری
  — پاسخ AEO در ۱۵۰ کلمه اول

[Natural Character]
  — ویژگی‌های گونه/پدیده | شرایط بهینه مشاهده

[Where to Experience — R16]
  — G3/G5 با لینک — چندین منطقه اغلب
  — نقشه توزیع جغرافیایی

[Best Time & Conditions]
  — R19 season + calendar_timing دقیق‌تر
  — شرایط آب‌وهوایی | نکات عکاسی

[Visitor Information]
  — لینک A9 مرتبط (پارک ملی) — اشاره editorial، نه R15
  — لینک P4 (ایمنی) برای مناطق دورافتاده

[Schema Block]
```

### ۳. Required Fields

| فیلد | نوع | منبع |
|------|-----|------|
| name_en | Text | — |
| name_fa | Text | — |
| season | Enum (spring/summer/autumn/winter) | R19 — stored attribute |
| held_in | Relation[] → G3/G5/G7 — حداقل ۱ | R16 — **تنها لنگر جغرافیایی** |
| best_season | Enum | R25 |
| overview_text | LongText (400+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### ۴. Optional Fields

| فیلد | نوع | منبع |
|------|-----|------|
| calendar_timing | Text | هفته‌های دقیق‌تر در فصل |
| name_local | Text | — |
| optimal_conditions | Text | Content Layer |
| nearby_park_reference | Text (نام + URL به A9) | **Content Layer — نه R15** — CA-E-D001 |
| safety_link | Text (نام+URL به P4) | Content Layer — مناطق دورافتاده |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:**
- `held_at` / R15: **ممنوع** — CA-E-D001
- `venue_name`: **ممنوع** — E6 venue ندارد
- `is_recurring`: ضمنی Seasonal — نیازی نیست
- `instance_of_ritual`, `celebrates`: E6∉R17/R18
- designation: C4

### ۵. Internal Links

| رابطه | هدف | نوع |
|--------|-----|-----|
| R16 → G3/G5/G7 | مناطق | **تنها لینک گراف‌محور E6** |
| Content → A9 | پارک ملی/منطقه حفاظت‌شده مجاور | اشاره editorial — نه R15 |
| Content → P4 | ایمنی | اختیاری |

### ۶. Aggregation Pages

| صفحه | نوع | مبنا |
|------|-----|------|
| «پدیده‌های طبیعی و فصلی ایران» — Hub | پیش‌فرض | همه E6 instances |
| «بهار ایران» / «پاییز ایران» / ... | شرطی | R19 season filter |
| «طبیعت [استان] در [فصل]» | شرطی | R16→G3 × season — D-007 |

### ۷. SEO Purpose

- کوئری‌های هدف: "spring in Iran"، "Iran cherry blossom"، "bird migration Iran"، «شکوفه‌های بادام ایران»
- این category بالاترین پتانسیل Visual Search دارد — تصاویر طبیعی ایران با جستجوی جهانی بالا
- فصل-محور: AEO زمانی ("best time to visit Iran for nature")

### ۸. AEO Purpose

- "When do flowers bloom in Iran?"
- "Where to see [natural phenomenon] in Iran?"
- "What season is best to visit [region] in Iran?"
- "When do birds migrate through Iran?"

### ۹. Schema Readiness

```json
{
  "@type": "Event",
  "additionalType": "SeasonalEvent",
  "name": "[name_en]",
  "description": "[چکیده]",
  "eventSchedule": {
    "@type": "Schedule",
    "repeatFrequency": "P1Y",
    "startDate": "[تقریبی — بازه فصل]"
  },
  "location": {
    "@type": "AdministrativeArea",
    "name": "[G3/G5 name]"
  }
}
```

**نکته:** `location` اینجا G3 یا G5 است — نه A9 (پارک) — چون E6 فقط R16→G دارد.

### ۱۰. MVC Requirements

- ✅ name_en + name_fa + season (R19)
- ✅ held_in → حداقل G3/G5 (R16)
- ✅ best_season
- ✅ overview_text ≥ 400 کلمه
- ✅ **هیچ held_at / venue_name** (CA-E-D001)
- ✅ Schema.org Event + SeasonalEvent
- ✅ hero_image طبیعی‌محور

### ۱۱. Editorial Notes

- **قاعده سخت E6:** هیچ held_at و هیچ venue_name. اگر یک ادیتور بخواهد «پارک ملی X» را به‌عنوان venue اضافه کند → او را به nearby_park_reference (Content Layer) هدایت کن.
- **تفاوت با A9:** پارک ملی = A9 (مکان دائمی). فصل شکوفه در آن پارک = E6. یک link editorial از E6 به A9 کافی است — نه R15.
- **granularity:** «بهار ایران» به‌عنوان کل یک E6 نیست (خیلی کلی)؛ «شکوفه‌های بادام شاهرود» یک instance خوب است اگر سه‌شرطی D-007 برقرار باشد.
- **تصاویر:** E6 بالاترین ارزش تصویری Category E دارد — featured_image_gallery اولویت‌دار است.

---

## بخش ۴ — Cross-Category Review

### E ↔ G

| بررسی | نتیجه | شرح |
|--------|--------|------|
| R16→G3/G5/G7 در CA-G | ✅ | G3/G5 بخش Events دارند؛ G7 Conditional (CA-G-F004) |
| R15→G7 | ✅ | G7 targets هم R15 هم R16 دارد |
| E6 فقط R16 | ✅ | CA-E-D001 اعمال شد — هیچ R15 |
| هر ۴ E entity به G متصل | ✅ | IA header: "All connect to G via HELD_AT or HELD_IN" |
| Cannibalization | ✅ کنترل‌شده | aggregation جغرافیایی E با آزمون D-007 |

### E ↔ A

| بررسی | نتیجه | شرح |
|--------|--------|------|
| R15→A2 (venue_eligible:true) | ✅ | Constraint C5 رعایت |
| R15→A3/A7/A9 از E1/E3/E5 | ✅ | درست — E6 حذف شد (CA-E-D001) |
| E6 ∉ R15 | ✅ | CA-E-D001 — Patch CA-A |
| Substitution Rule | ✅ | CA-E-D005 — R15 OR venue_name |
| E6 reference به A9 = Content Layer | ✅ | nearby_park_reference Text |
| C5 رعایت | ✅ | venue_eligible در A2 — هیچ E entity آن را bypass نکرده |

### E ↔ C

| بررسی | نتیجه | شرح |
|--------|--------|------|
| R17 (E1/E5→C10) | ✅ | C10 Deferred sections فعال می‌شوند |
| R18 (E1/E5→C10/C12/C13) | ✅ | C12/C13 Deferred sections فعال |
| C7: One level only | ✅ | E→C10 مجاز؛ C10→X ممنوع |
| E3∉R17/R18 | ✅ | CA-E-D003 — Content Layer |
| E6∉R17/R18 | ✅ | IA طراحی |
| Deferred link activation | ✅ | انتشار CA-E تمام Deferred های CA-C را فعال می‌کند |
| مرز E/C10 | ✅ | E = time-bounded؛ C10 = persistent |

### E ↔ D

| بررسی | نتیجه | شرح |
|--------|--------|------|
| C4: هیچ E→D | ✅ | قطعی — هیچ E entity فیلد designation ندارد |
| D-025: No Designation Event Node | ✅ | هیچ node designation در CA-E |
| هیچ E در R20 Sources | ✅ | صریح در IA |

### هشت بررسی استاندارد

| بررسی | نتیجه |
|--------|--------|
| Entity Inflation | ✅ 0 — E2/E4 FIR-E-001 |
| Relationship Inflation | ✅ 0 — بازی‌های بومی Content Layer؛ A9 reference Content Layer |
| Duplicate Modeling | ✅ — E ≠ C10؛ R15 OR venue_name هرگز هر دو |
| Circular Dependencies | ✅ — E صفر Inbound دارد؛ هیچ چرخه ممکن نیست |
| Geography First (P-01) | ✅ — هر ۴ entity R16→G دارند؛ E1/E3/E5 اختیاری R15 هم دارند |
| One Physical Place = One Primary Node (P-02) | ✅ — Substitution Rule از node تکراری venue جلوگیری کرد |
| AEO Readiness | ✅ — Identity Block ساختاریافته + ≥ 4 سؤال per entity |
| Schema Readiness | ✅ — SportsEvent (E3)؛ Event + superEvent (E1/E5)؛ SeasonalEvent (E6) |

---

## بخش ۵ — Future IA Review Items (از CA-E-DECISIONS)

### FIR-E-001 — E2/E4 Clarification

یادداشت توضیحی غیبت E2/E4 در IA افزوده شود — نه entity جدید.
**اولویت:** پایین.

### FIR-E-002 — E3 و R17/R18 برای بازی‌های بومی

بررسی افزودن E3 به Sources R17/R18 برای پوشش رویدادهای ورزشی با زمینه فرهنگی (چوگان، زورخانه).
**اولویت:** متوسط. تا تصمیم، Content Layer reference کافی است.

---

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CATEGORY E — EVENTS
CONTENT ARCHITECTURE FINAL STATUS
Phase 3 | ToPersia | IA-v1.2.1-FINAL Reference
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Entities Designed:             4 / 4    ✅
  E1  Festival / Event         ✅ (is_recurring، R17/R18)
  E3  Sports Event             ✅ (is_recurring، بدون R17/R18)
  E5  Public Religious Ceremony ✅ (Annual، R17 Required)
  E6  Seasonal / Natural Event  ✅ (فقط R16، بدون R15)

Decisions Applied:
  CA-E-D001  E6∉R15 + Patch CA-A       ✅
  CA-E-D002  E2/E4 بدون entity         ✅
  CA-E-D003  E3∉R17/R18 intentional    ✅
  CA-E-D004  is_recurring در E1/E3     ✅
  CA-E-D005  Substitution Rule         ✅

Cross-Category Review:
  E ↔ G                         PASS   ✅
  E ↔ A                         PASS   ✅ (Patch CA-A)
  E ↔ C                         PASS   ✅ (Deferred فعال)
  E ↔ D                         PASS   ✅ (C4 قطعی)
  ۸ بررسی استاندارد              PASS   ✅

Constraint Compliance:
  C4 (No Event Designation)     ✅
  C5 (venue_eligible)           ✅
  C7 (INSTANCE_OF one level)    ✅

Architecture Freeze:            INTACT ✅
New Entities:                   0      ✅
New Relationships:              0      ✅
New Constraints:                0      ✅

Milestone: انتشار CA-E تمام Deferred links
کتاگوری C را فعال می‌کند (C10/C12/C13)

Pending Action:
  → Patch CA-A-v1.0-FINAL (خطوط ۷۳۴/۷۷۳/۸۲۹)

NEXT: Category P — Practical Travel
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

*ToPersia CA-E-v1.2-FINAL*
*Phase 3: Content Architecture — Category E*
*مرجع: IA-v1.2.1-FINAL (Frozen)*
*وضعیت: COMPLETE — FINAL*
