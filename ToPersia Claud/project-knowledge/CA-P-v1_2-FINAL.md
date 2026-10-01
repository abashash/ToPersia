# ToPersia — Phase 3: Content Architecture
## CA-P-FINAL-v1.2
### Category P — Practical Travel Information
### نسخه نهایی — تمام ۷ موجودیت
#### مرجع: IA-v1.2.1-FINAL (Frozen) | ورودی: CA-P-EXTRACTION · CA-P-FINDINGS · CA-P-DECISIONS · CA-P-PATCH-REPORT

> **Changelog v1.0 → v1.1:** بدون تغییر معماری. فقط تصحیح متنی Finding CA-P-F009 — موجودیت دوم به‌اشتباه «E3» ثبت شده بود، درست «E5» است؛ وضعیت از «Pending» به «Resolved» به‌روزرسانی شد (پس از اجرای AUDIT-PATCH-002/003 روی CA-E-v1.1). جزئیات: PHASE3-VALIDATION-LOG-v1_1.md.
> **Changelog v1.1 → v1.2:** بدون تغییر محتوا — هم‌ترازسازی نسخهٔ مجموعه با CA-C-v1.2.

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| پروژه | ToPersia |
| فاز | Phase 3 — Content Architecture |
| Category | P — Practical Travel Information |
| نسخه | 1.2-FINAL |
| وضعیت | APPROVED — FINAL |
| مرجع IA | IA-v1.2.1-FINAL (Frozen) |
| مراجع CA | CA-G-v1.0-FINAL (patched) · CA-A-v1.0-FINAL (patched) · CA-C-v1.0-FINAL · CA-E-v1.0-FINAL (patched) |
| تصمیمات پیش‌تأیید | DEC-003 (P7 Dual-Level) · DEC-004 (R22 Matrix) · DEC-005 (P6 Model A) · DEC-008 (P9→G13 Deferred) |
| موجودیت‌های پوشش‌داده‌شده | ۷ / ۷ |
| Architecture Freeze | INTACT |

---

## بخش ۱ — یادداشت پیش از طراحی: کشف یک الگوی سیستماتیک

پیش از ورود به طراحی، لازم است یک یافته جدید ثبت شود که حین بررسی دقیق CA-E-v1.0-FINAL (برای طراحی P1، P4، P5) کشف شد.

### Finding CA-P-F009 — تکرار الگوی «Relation بدون مبنای IA» در CA-E (فراتر از دامنه P-PATCH قبلی)

> **✅ به‌روزرسانی وضعیت (این نسخه v1.1):** این Finding با اجرای AUDIT-PATCH-002 و AUDIT-PATCH-003 (روی CA-E-v1.1-FINAL) کاملاً رفع شد. متن اصلی Finding به‌عنوان سابقه حفظ شده اما با یک تصحیح مهم: موضع دوم که در متن اصلی به‌اشتباه **«E3»** ثبت شده بود، در واقع **«E5» (Public Religious Ceremony)** بود — E3 (Sports Event) هیچ فیلد P-محور ندارد. یک فیلد چهارم (`etiquette_link | Relation → P5` روی همان E5) نیز در بررسی مستقل بعدی کشف و رفع شد که در این Finding اصلاً ثبت نشده بود. جزئیات کامل: PHASE3-VALIDATION-LOG-v1_1.md.

**Finding ID:** CA-P-F009
**Severity:** MEDIUM — همان نوع نقصی که در CA-P-F006/P-PATCH-003 برای A9 اصلاح شد، در سه موضع دیگر CA-E تکرار شده
**Category:** Cross-Category Conflict — P ↔ E (تکمیلی)

**Description:**
P-PATCH-003 فقط `safety_info: Relation → P4` در **A9** را اصلاح کرد. اما همان الگوی نادرست (ادعای Graph Edge بدون مبنای IA) در سه فیلد دیگر CA-E-v1.0-FINAL باقی مانده:

| موضع | متن فعلی (پیش از پچ) | مبنای IA | وضعیت |
|------|----------|----------|--------|
| E1 — Optional Fields | `visa_info_link \| Relation → P1` | ❌ هیچ رابطه E→P1 در R01–R27 | ✅ رفع شد (AUDIT-PATCH-002) |
| **E5** *(اصلاح‌شده — متن اصلی این Finding به‌اشتباه «E3» نوشته بود)* — Optional Fields | `safety_link \| Relation → P4` | ❌ هیچ رابطه E5→P4 در R01–R27 | ✅ رفع شد (AUDIT-PATCH-003) |
| E6 — Optional Fields | `safety_link \| Relation → P4` | ❌ هیچ رابطه E6→P4 در R01–R27 | ✅ رفع شد (AUDIT-PATCH-002) |

**Evidence:** R22 Sources (عین IA): "P1, P2, P4, P5, P6" — این یعنی P entities به G اعمال می‌شوند، نه این‌که A/E به P وصل شوند. تنها رابطه مجاز A/E→P در کل IA همان **R24 (A3→P5)** است که در CA-A دست‌نخورده باقی مانده و صحیح است.

**Impact Analysis:** این سه فیلد اگر در Phase 4 به‌عنوان Graph Edge پیاده شوند، سه Relationship Inflation جدید ایجاد می‌کردند — دقیقاً همان ریسکی که P-PATCH-003 قصد رفعش را داشت. **این ریسک اکنون خنثی شده است.**

**Recommendation *(تاریخی — اجراشده)*:** این‌ها در زمان نگارش این سند **خارج از دامنه این سند** بودند. به‌عنوان **P-PATCH-004 (پیشنهادی، Pending)** ثبت شده بود و بعداً با شناسه‌های AUDIT-PATCH-002/003 اجرا شد:
- E1: `visa_info_link` → `Text (URL به P1) | Content Layer` ✅
- E5: `safety_link` → `Text (URL به P4) | Content Layer` ✅ *(نه E3 — تصحیح‌شده)*
- E6: `safety_link` → `Text (URL به P4) | Content Layer` ✅

**این Finding مسدودکننده Go/No-Go این سند نیست** — طراحی P1، P4، P5 در ادامه این سند با فرض صحیح (این لینک‌ها Content Layer هستند، نه Relation) پیش می‌رود، مستقل از این‌که CA-E کی patch شود.

---

## بخش ۲ — Decision Log (تصمیمات پیش‌تأیید + تصمیمات جدید این سند)

### تصمیمات پیش‌تأیید (ورودی این تسک)

| Decision ID | موضوع | تصمیم | Revisable |
|------------|--------|--------|-----------|
| **DEC-003** | P7 Dual-Level Model | Level 1 = یک P7 entity؛ Level 2 = `transport_access: Text` روی G5؛ بدون Relation جدید | Yes |
| **DEC-004** | R22 Application Matrix | جدول کامل زیر — Editorial Policy، نه IA Rule | Yes |
| **DEC-005** | P6 Model A | یک Entity واحد Country-Wide؛ R23 از C10+E1 feed می‌کند؛ بدون P6 مستقل استانی/شهری | Yes |
| **DEC-008** | P9 → G13 | Deferred تا شفاف‌سازی ماهیت G13 | Yes |

**R22 Application Matrix (DEC-004 — عین ورودی تسک):**

| P Entity | G1 | G3 | G5 |
|---------|----|----|-----|
| P1 | Full | Link | Link |
| P2 | Full | Regional Notes | Link |
| P4 | Full | Regional | Local |
| P5 | Full | Regional | Link |
| P6 | Full | Regional Events | Local Events |

### تصمیمات جدید این سند (CA-P-D###)

| Decision ID | موضوع | تصمیم | Rationale | Revisable |
|------------|--------|--------|-----------|-----------|
| **CA-P-D001** | P9 Classification Model | ۳ Classification رسمی IA (Historical/Natural/Thematic Route) به‌عنوان Required field؛ هر Instance دقیقاً یک Classification | Sources: IA Section 4 — بدون ابهام | No |
| **CA-P-D002** | Content Layer Convention برای P | هر ارجاع P↔A یا P↔E که مبنای IA ندارد (جز R24، R23) → `Text (نام + URL) \| Content Layer` — هرگز `Relation` | تعمیم مستقیم P-PATCH-001/003 به کل Category P؛ جلوگیری از تکرار F001/F006/F009 | No |
| **CA-P-D003** | P7 Level 1 Scope | صفحه P7 (Level 1) یک **Overview کشوری حمل‌ونقل** است (هوایی/ریلی/جاده‌ای/داخل‌شهری) — نه فهرست خطوط یا جدول‌های زمانی (که به‌سرعت stale می‌شوند) | جلوگیری از Thin/Stale Content؛ محتوای evergreen در Level 1، جزئیات عملیاتی در Level 2 (G5) | Yes |
| **CA-P-D004** | P1/P2 Volatility Policy | داده‌های به‌شدت ناپایدار (نرخ ارز زنده، جدول ویزا کشور به کشور) در محتوای static قرار نگیرند؛ فقط مکانیزم‌های evergreen (قوانین، فرآیندها) + `last_verified_date` + لینک به منبع رسمی زنده | AEO/GEO: محتوای stale اعتماد AI/موتور جستجو را از بین می‌برد؛ ریسک واقعی برای مسافر (اطلاعات نادرست ویزا/ارز پیامد مستقیم دارد) | Yes |
| **CA-P-D005** | P9 vs Itinerary Boundary | P9 = مسیر نام‌دار با هویت جغرافیایی پایدار (مثلاً «جاده ابریشم ایران»)؛ Itinerary = محتوای ادیتوریال ترکیبی (چند P9 + A + G) — Itinerary صفحه مستقل با Content Type می‌سازد، نه از P9 template | تفکیک دقیق طبق IA: "Itinerary is a Content Type, not an entity" | No |

---
# بخش ۳ — Content Architecture

---

## P6 — Calendar & Public Holidays | تقویم و مناسبت‌های رسمی

### Entity Overview

**Purpose:** P6 مرجع واحد و معتبر تقویم ایران است — تعطیلات رسمی، مناسبت‌های ملی/مذهبی، و اثر آن‌ها بر برنامه‌ریزی سفر (بسته‌شدن اماکن، ازدحام، نوسان قیمت). طبق **DEC-005**، P6 یک Entity واحد Country-Wide است که از دو منبع گراف‌محور (C10 و E1 از طریق R23) تغذیه می‌شود.

**Scope:** Country-Wide — یک صفحه اصلی؛ بدون نمونه مستقل استانی/شهری (DEC-005 صریح).

**Search Intent:** Planning-critical — کاربر معمولاً پیش از رزرو سفر این صفحه را می‌بیند تا بداند آیا تاریخ سفرش با تعطیلی بزرگ (مثل نوروز) تداخل دارد. نیت غالب: «آیا زمان سفر من با یک مناسبت مهم هم‌پوشانی دارد و این خوب است یا بد؟»

### Page Template: Calendar & Public Holidays

```
[Hero Section]
  — عنوان: تقویم رسمی و مناسبت‌های ایران
  — نمای کلی سال جاری (تصویری/گرافیکی)

[Identity Block — Structured — AEO-critical]
  — نوع تقویم: شمسی (رسمی) + قمری (مذهبی) + میلادی (مرجع بین‌المللی)
  — آخرین به‌روزرسانی (last_verified_date)

[National Holidays — Required — Structured List]
  — فهرست کامل: نام، تاریخ/قاعده محاسبه، مدت، نوع (رسمی/مذهبی/ملی)
  — برای هرکدام: Closure Impact (بانک‌ها/ادارات/برخی جاذبه‌ها بسته‌اند؟)

[Major Cultural & Religious Observances — R23 ← C10]
  — نوروز، یلدا، عاشورا و... — با لینک مستقیم به C10 instance
  — این بخش از R23 (C10 instances با travel_impact:true) تغذیه می‌شود

[Festivals Affecting Travel — R23 ← E1]
  — رویدادهایی که ظرفیت/ترافیک/قیمت را تحت تأثیر قرار می‌دهند
  — از R23 (E1 instances با event_travel_impact:true) تغذیه می‌شود

[Regional Events — Conditional — G3 context]
  — رندر فقط وقتی صفحه در context یک G3 (استان) دیده می‌شود
  — طبق DEC-004: G3 = "Regional Events"

[Local Events — Conditional — G5 context]
  — رندر فقط وقتی صفحه در context یک G5 (شهر) دیده می‌شود
  — طبق DEC-004: G5 = "Local Events"

[Best/Worst Times to Visit — Editorial Synthesis]
  — ترکیب best_season (R25 از entities دیگر) با تعطیلات — نه یک رابطه گراف جدید،
    صرفاً synthesis ادیتوریال در سطح محتوا

[Schema Block]
```

### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| calendar_system_note | Text (توضیح شمسی/قمری/میلادی) | — |
| national_holidays_list | Structured[] (name, date_rule, duration, type) | — |
| last_verified_date | Date | **CA-P-D004** — الزامی برای اعتماد AEO |
| overview_text | LongText (500+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| closure_impact_notes | Text[] | Content Layer |
| official_source_link | URL | برای تأیید نهایی — CA-P-D004 |
| regional_event_filter_tag | Enum (به G3 مرتبط) | برای رندر Conditional |

**فیلدهای عمداً غایب:** هیچ فیلد ذخیره‌شده جداگانه برای هر استان/شهر (نقض DEC-005 خواهد بود — رندر Conditional کافی است، نه Entity/Instance جدید).

### Internal Links

| رابطه | جهت | نوع |
|--------|-----|-----|
| R22 → G1/G3/G5 | Outbound | لنگر نمایش — Full/Regional/Local طبق DEC-004 |
| R23 ← C10 | Inbound | آیین‌های پایدار با travel_impact=true |
| R23 ← E1 | Inbound | رویدادهای با event_travel_impact=true (اکنون **Active** — نه Deferred، چون P6 اکنون طراحی شده) |

### Aggregation Sources
*(چه چیزی داده را به این صفحه تغذیه می‌کند)*

- **R23 ← C10 instances** (طبق CA-C-v1.0-FINAL: فیلد `travel_impact`)
- **R23 ← E1 instances** (طبق CA-E-v1.0-FINAL patched: فیلد `event_travel_impact`)

هر دو Source از IA مصوب‌اند ("R23 Sources: C10, E1"). با انتشار این سند، Deferred marker در هر دو سند مرجع می‌تواند به **Active** تغییر یابد.

### Aggregation Targets
*(این صفحه محتوایش را به کجا می‌فرستد/اعمال می‌کند)*

| G Level | حالت طبق DEC-004 | توضیح |
|---------|-------------------|--------|
| G1 | Full | کل تقویم ملی در صفحه کشور |
| G3 | Regional Events | فقط رویدادهای مرتبط با آن استان (فیلتر‌شده، نه صفحه مستقل) |
| G5 | Local Events | فقط رویدادهای محلی آن شهر |

### SEO Purpose

- کوئری‌های هدف: "Iran public holidays"، "Nowruz dates [year]"، "best time to visit Iran"، «تعطیلات رسمی ایران»
- این صفحه معمولاً در چرخه تصمیم‌گیری سفر (Awareness → Consideration) نقش کلیدی دارد — باید در ساختار Silo به G1 و به صفحات C10/E1 مرتبط لینک قوی بدهد.

### AEO Purpose

- "What are Iran's public holidays?"
- "When is Nowruz [year]?"
- "Are banks closed during [holiday] in Iran?"
- "Is it a good time to visit Iran during [month]?"

### Schema Readiness *(baseline — Phase 4 نهایی)*

```json
{
  "@type": "Thing",
  "additionalType": "TravelCalendar",
  "name": "[name_en]",
  "description": "[چکیده]",
  "dateModified": "[last_verified_date]"
}
```
نکته: `Event` type مناسب نیست چون P6 خودش یک رویداد نیست — یک صفحه مرجع تجمیعی است. هر آیتم فهرست تعطیلات می‌تواند در سطح جزء (nested) `Event`/`PublicHolidays` باشد — این تصمیم در Phase 4 نهایی می‌شود.

### Notes

- **مهم‌ترین ریسک محتوایی:** اگر تاریخ‌های قمری (عاشورا و...) هرساله به‌روزرسانی نشوند، این صفحه اشتباه‌ترین صفحه سایت می‌شود. `last_verified_date` باید به یک چرخه ادیتوریال سالانه اجباری متصل شود — نه صرفاً یک فیلد تزئینی.
- **بدون Duplicate با C10/E1:** این صفحه محتوای C10 (آیین) یا E1 (جشنواره) را تکرار نمی‌کند — فقط effect بر سفر را تجمیع می‌کند؛ شرح کامل هرکدام در صفحه خودشان است (طبق اصل P-06 که در کل پروژه رعایت شده).
- **مدل A (DEC-005) به‌درستی انتخاب شد:** با Country-Wide بودن Spatial Scope و R23 Notes ("Enables traveler alerts")، یک صفحه با Authority بالا بسیار بهتر از ده‌ها صفحه نازک استانی/شهری عمل می‌کند.

---

## P1 — Visa & Entry Requirements | ویزا و شرایط ورود

### Entity Overview

**Purpose:** مرجع رسمی و اقدام‌محور برای شرایط ورود به ایران — انواع ویزا، فرآیند e-visa، کشورهای معاف، نکات مرزی. این صفحه بالاترین «هزینه خطا» را در کل Category P دارد: اطلاعات نادرست ویزا می‌تواند سفر واقعی کسی را خراب کند.

**Scope:** Country-Wide — یک صفحه اصلی (طبق R22 Sources، P1 غیرقابل‌تقسیم به سطح استان/شهر است — ویزا مفهوماً یک قاعده کشوری است، نه محلی).

**Search Intent:** Highly Transactional/Planning — کاربر دقیقاً می‌داند دنبال چه می‌گردد: «آیا نیاز به ویزا دارم؟ چطور بگیرم؟» این صفحه معمولاً اولین صفحه‌ای است که یک گردشگر بالقوه پیش از هر تصمیم دیگری می‌بیند.

### Page Template: Visa & Entry Requirements

```
[Hero Section]
  — عنوان: ویزا و ورود به ایران
  — آخرین به‌روزرسانی (اعتماد‌سازی فوری)

[Quick Answer Block — AEO-critical, در ۱۵۰ کلمه اول]
  — «آیا نیاز به ویزا دارم؟» بر اساس تابعیت (اشاره به جدول کامل زیر)

[Visa Types — Required — Structured]
  — Tourist e-Visa | Visa on Arrival | Embassy Visa | ...
  — برای هرکدام: مدت اعتبار، هزینه تقریبی (بازه، نه عدد قطعی)، زمان پردازش

[Visa-Exempt & Visa-on-Arrival Countries — Structured List]

[Application Process — Step by Step]
  — لینک به official_application_portal

[Entry Points — Content Layer]
  — ارجاع متنی به فرودگاه‌ها/مرزهای اصلی (G7/G8) — نه رابطه گراف
    (الگوی مصوب در CA-G: «G13/G8 = مکان خاص، P1 = اطلاعات کشوری — لینک، نه تکرار»)

[Extension & Overstay Rules]

[Related Practical Info]
  — لینک به P2 (ارز)، P5 (آداب) برای ادامه برنامه‌ریزی

[Schema Block]
```

### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| visa_types | Structured[] (type, duration, eligible_nationalities, cost_range) | — |
| visa_exempt_countries | Text[] | — |
| application_process_summary | LongText | — |
| last_verified_date | Date | **CA-P-D004** — الزامی، سیاست‌های ویزا مکرراً تغییر می‌کنند |
| overview_text | LongText (500+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| official_application_portal | URL | — |
| processing_time_estimate | Text | — |
| extension_info | Text | — |
| entry_points_reference | Text[] (نام G7/G8 + URL) | **Content Layer — CA-P-D002** — نه Relation؛ هیچ رابطه R01–R27 برای P1→G7/G8 وجود ندارد |

**فیلدهای عمداً غایب:** هیچ نرخ ارز یا محتوای مالی (این‌ها به P2 تعلق دارند — Boundary روشن).

### Internal Links

| رابطه | جهت | نوع |
|--------|-----|-----|
| R22 → G1 (Full) | Outbound | صفحه کامل در G1 |
| R22 → G3/G5 (Link only) | Outbound | فقط لینک — طبق DEC-004، تکرار محتوای ویزا در سطح استان/شهر بی‌معناست |
| Content → G7/G8 | Editorial | فرودگاه‌ها/مرزها — Content Layer، نه Relation (CA-P-D002) |

### Aggregation Sources

هیچ رابطه Inbound رسمی در IA برای P1 وجود ندارد. *(یادداشت: CA-E-v1.1-FINAL فیلد `visa_info_link` را برای E1 دارد — پیش‌تر Relation بود، با AUDIT-PATCH-002 به Text (URL) | Content Layer اصلاح شد. این یک ارجاع Content Layer از E1 است، نه رابطه گراف رسمی.)*

### Aggregation Targets

| G Level | حالت طبق DEC-004 | توضیح |
|---------|-------------------|--------|
| G1 | Full | تنها سطحی که محتوای کامل ویزا معنا دارد |
| G3 | Link only | صرفاً لینک به P1 — ویزا مفهوم استانی ندارد |
| G5 | Link only | همان — جلوگیری از Thin/Duplicate Content در ۱۰۰۰+ صفحه شهر |

### SEO Purpose

- کوئری‌های هدف: "Iran visa requirements"، "Iran e-visa"، "[nationality] visa Iran"، «ویزای ایران»
- این صفحه یکی از بالاترین Search Volume های کل پروژه را دارد — اولویت Technical SEO بالا (سرعت، Core Web Vitals) توصیه می‌شود.

### AEO Purpose

- "Do I need a visa to visit Iran?"
- "How to apply for an Iran e-visa?"
- "Is Iran visa-free for [nationality] citizens?"
- "How long does an Iran visa take to process?"

### Schema Readiness *(baseline)*

```json
{
  "@type": "GovernmentService",
  "name": "[name_en]",
  "description": "[چکیده]",
  "serviceType": "Visa Application",
  "areaServed": { "@type": "Country", "name": "Iran" },
  "dateModified": "[last_verified_date]"
}
```

### Notes

- **بالاترین ریسک محتوایی در کل Category P:** سیاست ویزا در ایران به‌دلیل شرایط ژئوپلیتیک متغیر می‌تواند تغییر کند. توصیه می‌کنم `last_verified_date` + لینک صریح به official_application_portal همیشه بالای صفحه و برجسته باشد — این هم یک تصمیم AEO است (اعتبار منبع) و هم یک تصمیم مسئولیت‌پذیری (کاربر نباید بر اساس اطلاعات قدیمی تصمیم بگیرد).
- **بدون واریانت استانی/شهری (CA-P-D004 uyumlu):** برخلاف P4 که در G5 محتوای Local دارد، P1 در هیچ سطحی جز G1 محتوای کامل ندارد — این طبق ماهیت خودِ مفهوم ویزا است (قانون کشوری)، نه یک محدودیت فنی.
- **ارتباط با G8/G7 (مرزها/فرودگاه‌ها):** طبق پیشینه‌ای که خودِ CA-G برای G13 ثبت کرده («G13 = مکان خاص، P1 = اطلاعات کشوری»)، این تفکیک نقش این‌جا هم تکرار و رعایت شده.

---
## P2 — Currency & Payment | ارز و روش‌های پرداخت

### Entity Overview

P2 مرجع مکانیزم‌های مالی سفر به ایران است. این موجودیت به یک دلیل واقعی و ساختاری، حساس‌ترین صفحه غیرویزایی پروژه است: **نظام دوگانه نام‌گذاری ارز** (ریال رسمی در برابر تومان رایج در گفتار روزمره — نسبت ۱ به ۱۰) و **عدم کارکرد کارت‌های بین‌المللی Visa/Mastercard به‌دلیل تحریم‌ها** دو واقعیتی هستند که تقریباً هیچ گردشگری از قبل نمی‌داند و بدون دانستن آن‌ها سفرش عملاً مختل می‌شود.

**Scope:** Country-Wide — یک صفحه اصلی؛ تفاوت‌های منطقه‌ای (مثل رواج بیشتر صرافی در شهرهای مرزی) به‌صورت یادداشت درون همان صفحه، نه صفحه مستقل.

### Search Intent

Planning-critical با یک لایه «شوک اطلاعاتی»: کاربر معمولاً با فرض «کارت اعتباری‌ام کار می‌کند» وارد این صفحه می‌شود و باید متوجه شود این فرض غلط است. نیت غالب: «چطور در ایران پول خرج کنم؟» — این صفحه باید این فرض غلط را در همان ۱۵۰ کلمه اول اصلاح کند، نه در انتهای متن.

### Page Template: Currency & Payment

```
[Hero Section]
  — عنوان: ارز و پرداخت در ایران
  — هشدار برجسته: کارت‌های بین‌المللی کار نمی‌کنند

[Critical Fact Block — AEO-critical, در ۱۵۰ کلمه اول]
  — «آیا می‌توانم از کارت اعتباری در ایران استفاده کنم؟» → پاسخ صریح: خیر
  — راه‌حل‌های عملی: نقد، کارت‌های پیش‌پرداخت مخصوص گردشگر (در صورت وجود)

[Rial vs Toman — Required — این بخش هرگز نباید حذف شود]
  — توضیح نسبت ۱:۱۰ | چرا قیمت‌ها گاهی گیج‌کننده نوشته می‌شوند
  — نکته عملی: همیشه از فروشنده بپرسید «ریال یا تومان؟»

[Cash Culture & Bringing Money]
  — ارز قابل قبول برای تبدیل (یورو/دلار عموماً)
  — میزان نقد پیشنهادی برای طول سفر (بازه کلی، نه عدد قطعی)

[Where to Exchange — Content Layer]
  — صرافی‌های مجاز | فرودگاه در برابر مرکز شهر
  — ارجاع متنی به G5 (بدون رابطه گراف)

[Payment Methods Landscape]
  — نقد | کارت‌های محلی (Shetab — غیرقابل‌استفاده برای خارجی‌ها) | راهکارهای جایگزین

[Regional Notes — Conditional — G3 context]
  — طبق DEC-004: تفاوت دسترسی صرافی در شهرهای مرزی/کمتر گردشگرپذیر

[Related: Tipping Customs]
  — لینک به P5 — این صفحه فقط مکانیزم پرداخت را توضیح می‌دهد، نه فرهنگ انعام (Boundary روشن)

[Schema Block]
```

### Required Fields

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

### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| cash_recommendation_note | Text | Content Layer — بازه‌ای، نه عدد قطعی |
| exchange_locations_reference | Text[] (نام G5 + نوع صرافی + URL) | Content Layer — نه Relation |
| prepaid_tourist_card_note | Text | در صورت وجود چنین سرویسی |
| regional_exchange_notes | Text | برای G3 — طبق DEC-004 |

**فیلدهای عمداً غایب:** `live_exchange_rate` — طبق **CA-P-D004** هیچ نرخ زنده در محتوای static ذخیره نمی‌شود (استدلال کامل در Notes).

### Internal Links

| رابطه | جهت | نوع |
|--------|-----|-----|
| R22 → G1 (Full) | Outbound | صفحه کامل |
| R22 → G3 (Regional Notes) | Outbound | یادداشت‌های منطقه‌ای — طبق DEC-004 |
| R22 → G5 (Link only) | Outbound | صرفاً لینک |
| Content → P5 | Editorial | تفکیک از فرهنگ انعام |

### Aggregation Sources

هیچ رابطه Inbound رسمی در IA برای P2 وجود ندارد.

### Aggregation Targets

| G Level | حالت طبق DEC-004 |
|---------|-------------------|
| G1 | Full |
| G3 | Regional Notes |
| G5 | Link only |

### SEO Purpose

- کوئری‌های هدف: "Iran currency"، "Rial to Toman"، "credit card Iran"، «ارز ایران»
- سؤال «آیا کارت اعتباری در ایران کار می‌کند» یکی از پرتکرارترین جستجوهای پیش‌سفر است — پاسخ صریح و سریع، فرصت Featured Snippet قوی است.

### AEO Purpose

- "What currency does Iran use?"
- "Can I use a credit card in Iran?"
- "What is the difference between Rial and Toman?"
- "How much cash should I bring to Iran?"

### Schema Readiness *(baseline)*

```json
{
  "@type": "Thing",
  "additionalType": "CurrencyGuide",
  "name": "[name_en]",
  "description": "[چکیده]",
  "dateModified": "[last_verified_date]"
}
```

### Notes

- **تصمیم معمارانه مهم (بخشی از CA-P-D004):** عمداً از ذخیره نرخ ارز زنده خودداری شد. این یک انتخاب آگاهانه است، نه محدودیت: نرخ ارز ایران (به‌ویژه در بازار آزاد) نوسان روزانه/ساعتی دارد. محتوای static که نرخ اشتباه نشان دهد، دقیقاً همان چیزی است که اعتماد AEO/GEO را نابود می‌کند — یک موتور پاسخ‌گوی هوش مصنوعی که یک نرخ غلط را نقل کند، به کل دامنه بی‌اعتماد می‌شود. **رتبه‌بندی گزینه‌ها:** (۱) عدم درج نرخ + لینک به ابزار خارجی زنده — بهترین برای اعتماد بلندمدت؛ (۲) درج نرخ با تاریخ به‌روزرسانی مشخص — قابل قبول اما نیازمند نگهداری مداوم؛ (۳) درج نرخ بدون تاریخ — رد می‌شود، مستقیماً به Trust Signal آسیب می‌زند.
- **Boundary با P5:** این صفحه مکانیزم است (چطور پول جابه‌جا کنم)؛ P5 فرهنگ است (چقدر و کِی انعام بدهم). این تفکیک باید در هر دو صفحه صریح باشد تا محتوای تکراری ایجاد نشود.
- **اولویت محتوایی:** بخش "Rial vs Toman" و "Card Acceptance" باید در بالای صفحه و به‌صورت بصری برجسته (نه صرفاً یک پاراگراف در میان متن) باشند — این دو واقعیت بیشترین اثر عملی بر تجربه سفر را دارند.

---

## P4 — Health & Safety | سلامت و ایمنی

### Entity Overview

P4 تنها موجودیت Category P است که در ماتریس DEC-004 تا سطح **Local (G5)** محتوای کامل دارد — منعکس‌کننده این واقعیت که اطلاعات ایمنی، برخلاف ویزا یا ارز، ذاتاً وابسته به مکان است (نزدیک‌ترین بیمارستان یک شهر با شهر دیگر فرق دارد). P4 همچنین **بیشترین تعداد ارجاع ورودی editorial** از میان همه موجودیت‌های P را دارد — از پارک‌های ملی (A9) تا غارها (A10) تا مراسم مذهبی عمومی (E5) و رویدادهای طبیعی (E6). *(E3/Sports Event هیچ ارجاعی به P4 ندارد — تصحیح‌شده در v1.1)*

### Search Intent

Reassurance + Preparedness — نیت غالب کاربر آماده‌سازی پیشگیرانه است: «چه چیزی باید بدانم تا در امان باشم؟» این صفحه هم باید نگرانی‌های واقعی (مثل زلزله) را صادقانه پوشش دهد و هم بیش از حد نگران‌کننده جلوه نکند — تعادل بین صداقت و اطمینان‌بخشی.

### Page Template: Health & Safety

```
[Hero Section]
  — عنوان: سلامت و ایمنی در ایران

[Emergency Numbers — Required — Structured, همیشه در دسترس]
  — پلیس | اورژانس | آتش‌نشانی (شماره‌های کشوری)

[Vaccination & Health Prep]
  — توصیه‌های عمومی پیش از سفر
  — دسترسی به داروخانه و مراکز درمانی

[Natural Hazards — Required]
  — زلزله: ایران در منطقه فعال زمین‌لرزه قرار دارد — راهنمای عملی مواجهه
  — سایر: گرمای شدید تابستان در مناطق کویری/جنوبی

[Water & Food Safety]

[Regional Health Notes — Conditional — G3 context]
  — طبق DEC-004: ارتفاع (کوهستان)، آب‌وهوای شدید منطقه‌ای

[Local Emergency Info — Conditional — G5 context]
  — طبق DEC-004: نزدیک‌ترین بیمارستان/درمانگاه به آن شهر

[Activity-Specific Safety — Conditional]
  — برای مناطق طبیعی پرخطر (غار، دره) — رندر شرطی بر اساس Classification منبع ارجاع‌دهنده

[Schema Block]
```

### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| emergency_numbers | Structured (police, ambulance, fire) | — |
| natural_hazard_summary | LongText (شامل زلزله) | — |
| general_safety_notes | LongText | — |
| last_verified_date | Date | **CA-P-D004** |
| overview_text | LongText (400+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| vaccination_recommendations | Text | — |
| water_safety_note | Text | — |
| regional_health_notes | Text | برای G3 — ارتفاع/آب‌وهوا |
| local_emergency_facilities | Text[] (نام G5 + نوع مرکز درمانی) | Content Layer برای G5 |
| activity_specific_warnings | Text[] | برای رندر شرطی از A9/A10 |

### Internal Links

| رابطه | جهت | نوع |
|--------|-----|-----|
| R22 → G1 (Full) | Outbound | — |
| R22 → G3 (Regional) | Outbound | طبق DEC-004 |
| R22 → G5 (Local) | Outbound | تنها P entity با محتوای Local — طبق DEC-004 |

### Aggregation Sources
*(مهم‌ترین بخش این entity — بیشترین پیچیدگی ورودی در کل Category P)*

تمام موارد زیر **Content Layer References** هستند — **هیچ‌کدام Graph Relation نیستند** (طبق **CA-P-D002** و اصلاح P-PATCH-003):

| منبع | نوع ارجاع | یادداشت |
|------|-----------|---------|
| A9 (National Park) | `safety_link: URL` | اصلاح‌شده در P-PATCH-003 — الگوی صحیح |
| A10 (Natural Feature) | Editorial prose | اجباری برای Cave/Gorge Classification |
| A12 (Archaeological Site) | Editorial prose | برای سایت‌های فعال |
| A13 | Editorial prose | — |
| **E5** *(Public Religious Ceremony — اصلاح‌شده از «E3» نادرست)* | `safety_link: Text (URL به P4) \| Content Layer` | ✅ اصلاح‌شده — AUDIT-PATCH-003 |
| E6 (Seasonal/Natural Event) | `safety_link: Text (URL به P4) \| Content Layer` | ✅ اصلاح‌شده — AUDIT-PATCH-002 |

### Aggregation Targets

| G Level | حالت طبق DEC-004 |
|---------|-------------------|
| G1 | Full |
| G3 | Regional |
| G5 | Local — بیشترین granularity در کل Category P |

### SEO Purpose

- کوئری‌های هدف: "Is Iran safe to visit"، "Iran earthquake risk"، "emergency numbers Iran"، «ایمنی سفر به ایران»
- سؤال «آیا ایران امن است؟» یکی از پرتکرارترین سؤالات هر گردشگر بالقوه است و باید با صداقت و شواهد پاسخ داده شود، نه فقط تبلیغاتی.

### AEO Purpose

- "Is Iran safe to visit?"
- "What is the emergency number in Iran?"
- "Is Iran an earthquake zone?"
- "What vaccinations do I need for Iran?"

### Schema Readiness *(baseline)*

```json
{
  "@type": "Thing",
  "additionalType": "SafetyInformation",
  "name": "[name_en]",
  "description": "[چکیده]",
  "dateModified": "[last_verified_date]"
}
```

### Notes

- **صداقت به‌جای بیش‌ازحد اطمینان‌بخشی:** پوشش زلزله نباید بازاریابی‌گونه کم‌رنگ شود — این نوع صداقت خودش یک Trust Signal برای AEO/GEO است؛ موتورهای پاسخ‌گو محتوای متوازن را به محتوای صرفاً تبلیغاتی ترجیح می‌دهند.
- **پاک‌سازی الگوی Finding CA-P-F009 (اجراشده):** چون P4 بیشترین تعداد ارجاع‌های ناقص (E5، E6) را داشت، این دو مورد در اولویت رفع قرار گرفتند و با AUDIT-PATCH-002/003 اصلاح شدند.
- **Boundary با P1/P5:** مسائل امنیتی سیاسی/اجتماعی (نه سلامت جسمی) در حوزه P5 (آداب و هنجارهای اجتماعی) یا خارج از این معماری هستند — P4 بر سلامت جسمی و بلایای طبیعی تمرکز دارد.

---

## P5 — Travel Etiquette | فرهنگ سفر و آداب

### Entity Overview

P5 تنها موجودیت Category P است که **یک رابطه ورودی کاملاً معتبر و اجباری (R24 از A3)** دارد — یعنی هر مکان مذهبی (A3) به این صفحه لینک می‌دهد. این جایگاه ویژه، P5 را به یکی از پرترافیک‌ترین صفحات پروژه از منظر internal linking تبدیل می‌کند. محتوای این صفحه پوشاک، رفتار در اماکن مذهبی، هنجارهای اجتماعی (تعارف)، و آداب عکاسی را در بر می‌گیرد.

### Search Intent

Cultural Preparedness — نیت کاربر: «چطور رفتار کنم که به کسی بی‌احترامی نشود و خودم هم در دردسر نیفتم؟» این صفحه باید عملی و بدون قضاوت باشد — واقعیت‌های رفتاری را شرح دهد، نه توجیه یا نقد آن‌ها.

### Page Template: Travel Etiquette

```
[Hero Section]
  — عنوان: فرهنگ سفر و آداب معاشرت در ایران

[Dress Code — Required — AEO-critical]
  — الزامات پوشش برای زنان و مردان (عمومی و در اماکن مذهبی)
  — تفاوت بین شهرهای بزرگ و شهرهای مذهبی (Regional نکته مهم)

[Religious Site Etiquette — R24 ← A3]
  — این بخش مستقیماً هدف رابطه R24 است
  — درآوردن کفش | جداسازی جنسیتی | محدودیت عکاسی

[Social Customs]
  — تعارف: توضیح این پدیده فرهنگی منحصربه‌فرد و چگونگی مواجهه با آن
  — مهمان‌نوازی و انتظارات متقابل

[Tipping — بدون تکرار P2]
  — چه زمانی، چقدر، به چه کسانی — این صفحه «فرهنگ» را می‌گوید، P2 «مکانیزم پرداخت» را

[Photography Etiquette]
  — محدودیت‌های اماکن دولتی/نظامی | عکاسی از افراد

[Regional Etiquette Notes — Conditional — G3 context]
  — طبق DEC-004: هنجارهای محافظه‌کارانه‌تر در شهرهای مذهبی (قم، مشهد) در برابر شهرهای بزرگ

[Schema Block]
```

### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| dress_code_summary | LongText | — |
| religious_site_etiquette | LongText | تغذیه‌شده مفهوماً از R24 |
| social_customs_summary | LongText (شامل تعارف) | — |
| overview_text | LongText (400+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| tipping_guidance | Text | Boundary روشن با P2 |
| photography_restrictions | Text | — |
| regional_etiquette_notes | Text | برای G3 — طبق DEC-004 |

### Internal Links

| رابطه | جهت | نوع |
|--------|-----|-----|
| R24 ← A3 | **Inbound — تنها رابطه کاملاً معتبر ورودی در کل Category P** | هر A3 instance این صفحه را لینک اجباری می‌دهد |
| R22 → G1 (Full) | Outbound | — |
| R22 → G3 (Regional) | Outbound | طبق DEC-004 |
| R22 → G5 (Link only) | Outbound | طبق DEC-004 |
| Content → P2 | Editorial | تفکیک انعام از مکانیزم پرداخت |

### Aggregation Sources

**R24 ← A3 (Religious Site)** — تنها Source واقعاً معتبر از منظر IA در کل Category P. هر A3 instance با فیلد `etiquette_compliance: Relation → P5` (که در CA-A دست‌نخورده باقی مانده و صحیح است) به این صفحه اشاره می‌کند.

### Aggregation Targets

| G Level | حالت طبق DEC-004 |
|---------|-------------------|
| G1 | Full |
| G3 | Regional |
| G5 | Link only |

### SEO Purpose

- کوئری‌های هدف: "what to wear in Iran"، "dress code Iran"، "Iranian customs"، «حجاب برای گردشگر خارجی»
- سؤال پوشاک زنان یکی از پرجستجوترین سؤالات پیش از سفر به ایران در سطح جهانی است.

### AEO Purpose

- "What should women wear in Iran?"
- "What is Taarof in Iranian culture?"
- "Can I take photos in Iran?"
- "How much should I tip in Iran?"

### Schema Readiness *(baseline)*

```json
{
  "@type": "Thing",
  "additionalType": "TravelEtiquetteGuide",
  "name": "[name_en]",
  "description": "[چکیده]"
}
```

### Notes

- **لحن حیاتی است:** این صفحه باید کاملاً واقع‌بینانه، عملی و بدون قضاوت ارزشی نوشته شود — شرح رفتار مورد انتظار، نه توجیه یا نقد سیاسی/مذهبی آن. این یک اصل ثابت در سراسر این معماری بوده (مشابه لحن A3 dual-audience و C10 مراسم مذهبی).
- **بالاترین ارزش Internal Linking:** چون R24 اجباری است، تقریباً هر A3 instance (که ممکن است ده‌ها مورد باشد) به این صفحه لینک می‌دهد — این صفحه باید از نظر UX/Speed بسیار بهینه باشد چون ترافیک ورودی بالایی خواهد داشت.
- **Boundary سه‌گانه:** P2 = مکانیزم پرداخت، P5 = فرهنگ انعام/رفتار، A3 = مکان فیزیکی مذهبی با قوانین خاص خودش (که ممکن است در template A3 هم به‌صورت خلاصه بیاید، اما مرجع کامل همیشه P5 است — طبق اصل P-06 که در کل پروژه رعایت شده).

---
## P7 — Public Transport Information | حمل‌ونقل عمومی

### Entity Overview

P7 تنها موجودیت Category P است که مدل **Dual-Level** دارد (**CA-P-DEC-003**، پیش‌تأیید): Level 1 این صفحه مستقل (نمای کلی کشوری حمل‌ونقل) و Level 2 فیلد `transport_access: Text` روی هر G5 (جزئیات دسترسی شهر به شهر) است. نکته معماری مهم: **P7 در Sources یا Targets هیچ رابطه‌ای از R01–R27 ندارد** — حتی R22 (که P1/P2/P4/P5/P6 دارند) شامل P7 نیست. این یعنی ماتریس DEC-004 اساساً برای P7 اعمال‌پذیر نیست؛ P7 یک مکانیزم اتصال متفاوت و مستقل دارد.

### Search Intent

Logistics-execution — این صفحه معمولاً *پس از* تصمیم‌گیری قطعی سفر (پس از حل ویزا و ارز) مشورت می‌شود: «حالا که تصمیم گرفتم بیایم، چطور جابه‌جا شوم؟»

### Page Template: Public Transport Information (Level 1 — Country Overview)

```
[Hero Section]
  — عنوان: حمل‌ونقل در ایران — نمای کلی

[Transport Modes Landscape — Required]
  — هوایی: فرودگاه‌های اصلی، شرکت‌های هواپیمایی داخلی (فقط نام، بدون قیمت/برنامه)
  — ریلی: خطوط اصلی بین‌شهری (مثلاً تهران–مشهد، تهران–اصفهان)
  — اتوبوس بین‌شهری: **نکته مثبت متمایز** — سیستم اتوبوس VIP ایران کیفیت بالایی دارد
    و اغلب گزینه راحت‌تری از پرواز داخلی برای مسافت‌های متوسط است
  — حمل‌ونقل درون‌شهری: کدام کلان‌شهرها مترو/BRT دارند (فقط فهرست، نه نقشه خط)

[How to Choose Between Modes]
  — راهنمای انتخاب بر اساس مسافت/بودجه/زمان (evergreen، نه جدول قیمت)

[Booking Practicalities — Content Layer]
  — پلتفرم‌های رزرو رایج (ارجاع متنی، نه رابطه گراف)

[City-Level Access — Level 2 Pointer]
  — «برای جزئیات دسترسی به هر شهر، به بخش Getting There & Around
    همان صفحه شهر (G5) مراجعه کنید» — لینک editorial ساده، نه رابطه

[Scenic & Route-Based Travel]
  — لینک به P9 (Tourism Routes) برای کسانی که مسیر را بخشی از تجربه می‌خواهند

[Schema Block]
```

### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| transport_modes_summary | Structured[] (mode, description, general_note) | — |
| overview_text | LongText (400+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| intercity_bus_note | Text | برجسته‌سازی کیفیت VIP bus |
| rail_network_note | Text | — |
| cities_with_metro | Text[] | فقط نام شهرها |
| booking_platforms_reference | Text[] (نام + URL) | Content Layer — نه Relation |

**فیلدهای عمداً غایب:** هیچ جدول زمان‌بندی، قیمت بلیت، یا برنامه حرکت (طبق **CA-P-D003** — این‌ها به‌سرعت stale می‌شوند و اعتبار AEO/GEO را تضعیف می‌کنند).

### Internal Links

| رابطه | جهت | نوع |
|--------|-----|-----|
| — | — | **P7 در R01–R27 هیچ رابطه‌ای ندارد** (تأیید مجدد از CA-P-EXTRACTION) |
| Content → G5 (`transport_access`) | Level 2 | Text مستقل روی G5 — بدون Relation (DEC-003) |
| Content → P9 | Editorial | برای مسیرهای گردشگری |

### Aggregation Sources

هیچ‌کدام — P7 هیچ رابطه Inbound در IA ندارد.

### Aggregation Targets

هیچ‌کدام از طریق R22 — **P7 در Sources R22 نیست** (برخلاف P1/P2/P4/P5/P6). اتصال P7 به G صرفاً از طریق مدل Dual-Level (**DEC-003**) است: Level 2 یک فیلد Text مستقل روی هر G5 است، نه یک Aggregation Target رسمی از این صفحه.

### SEO Purpose

- کوئری‌های هدف: "Iran train travel"، "Iran domestic flights"، "Iran intercity bus"، «حمل‌ونقل در ایران»
- برجسته‌سازی سیستم اتوبوس VIP به‌عنوان یک نقطه تمایز واقعی و کمتر شناخته‌شده — فرصت محتوایی یکتا در برابر رقبا.

### AEO Purpose

- "How do I get around Iran?"
- "Is there a train system in Iran?"
- "What is the bus system like in Iran?"
- "Which Iranian cities have a metro?"

### Schema Readiness *(baseline)*

```json
{
  "@type": "Thing",
  "additionalType": "TransportGuide",
  "name": "[name_en]",
  "description": "[چکیده]"
}
```

### Notes

- **CA-P-D003 (تکرار تأکیدی):** این صفحه باید عمداً «همیشه‌سبز» (evergreen) بماند — نمای کلی حالت‌های حمل‌ونقل، نه جدول برنامه/قیمت. اگر در آینده نیاز به اطلاعات عملیاتی زنده (قیمت بلیت، برنامه دقیق) احساس شد، این دقیقاً حوزه F6 (Tour Package — Future Scope IA) است، نه توسعه این صفحه.
- **ارتباط Level 1 ↔ Level 2 صرفاً Editorial است:** چون هیچ رابطه گراف بین P7 و G5 وجود ندارد (طبق DEC-003)، توصیه می‌کنم یک **قاعده نگارشی** (نه معماری) تعریف شود: هر فیلد `transport_access` در G5 با یک جمله لینک‌دار به P7 شروع شود. این یک Convention ادیتوریال است، نه یک Decision معماری.
- **بدون Aggregation Page مستقل:** چون P7 در R22 نیست، هیچ صفحه Aggregation جغرافیایی (مثل «حمل‌ونقل استان X») برای آن ساخته نمی‌شود — این با مدل Dual-Level کاملاً سازگار است.

---

## P9 — Tourism Route | مسیر گردشگری

### Entity Overview

P9 تنها موجودیت Category P با **Classification رسمی** (۳ مقدار: Historical Route، Natural Route، Thematic Route — **CA-P-D001**) و تنها موجودیتی است که از طریق R03 (PASSES_THROUGH) و R05 (CONNECTS) مستقیماً با چهار سطح مختلف G (G3/G5/G7/G8) در ارتباط است. تمایز حیاتی: **P9 ≠ Itinerary**. طبق **CA-P-D005**، P9 یک موجودیت با هویت جغرافیایی پایدار است («جاده ابریشم در ایران»)؛ Itinerary یک Content Type ادیتوریال ترکیبی است که چندین P9 + A + G را برای یک برنامه سفر خاص کنار هم می‌چیند — Itinerary هرگز از این template استفاده نمی‌کند.

### Search Intent

Inspiration-to-Planning — مرحله میانی تا پیشرفته سفر: کاربر می‌داند به ایران می‌آید و اکنون دنبال ساختاردهی به مسیر حرکت است. نیت: «چه مسیری برای دیدن [موضوع/منطقه] پیشنهاد می‌شود؟»

### Page Template: Tourism Route

```
[Hero Section]
  — تصویر شاخص مسیر
  — نام (FA / EN) | Classification badge
  — مدت تخمینی | مسافت تقریبی

[Identity Block — Structured — AEO-critical]
  — Classification (Historical / Natural / Thematic — CA-P-D001)
  — بهترین فصل ← R25
  — سطح دشواری (اگر applicable — برای Natural Routes)

[Route Sequence — R03 — Required, Ordered]
  — توالی ایستگاه‌ها (G3/G5/G7/G8) با ترتیب ذخیره‌شده در property رابطه
  — برای هر ایستگاه: چرا این توقف مهم است (خلاصه)

[Geographic Connections — R05]
  — نقاط اتصال کلیدی ← G5/G8
  — اتصال به G13: **Deferred (DEC-008)** — تا شفاف‌سازی ماهیت G13

[Highlights Along the Route — Content Layer]
  — اشاره متنی به جاذبه‌ها/سنت‌های مرتبط (A/C entities) — نه رابطه گراف
    (هیچ رابطه IA بین P9 و Category A یا C وجود ندارد)

[Practical Logistics]
  — لینک به P7 برای چگونگی طی مسیر

[Related Itineraries — Editorial, Content Type]
  — لینک به محتوای Itinerary که از این P9 استفاده می‌کند — بدون رابطه گراف

[Schema Block]
```

### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| classification | Enum (Historical / Natural / Thematic) | **CA-P-D001** |
| route_sequence | Relation[] → G3/G5/G7/G8 با property `sequence_order` | R03 |
| best_season | Enum | R25 |
| overview_text | LongText (500+ کلمه) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| total_distance_km | Number | — |
| estimated_duration_days | Number | — |
| difficulty_level | Enum | برای Natural Route |
| connects_to | Relation[] → G5/G8 | R05 |
| connects_to_g13 | Relation → G13 | R05 — **Deferred (DEC-008)** |
| highlight_references | Text[] (نام A/C entity + URL) | **Content Layer — CA-P-D002** — نه Relation |
| transport_guidance_reference | Text (URL به P7) | Content Layer |

**فیلدهای عمداً غایب:** هیچ Relation مستقیم به A یا C entities (هیچ رابطه IA این را مجاز نمی‌داند).

### Internal Links

| رابطه | جهت | نوع |
|--------|-----|-----|
| R03 → G3/G5/G7/G8 | Outbound | توالی سفارشی — ordered sequence در property رابطه |
| R05 → G5/G8 | Outbound | اتصال نقطه‌به‌نقطه |
| R05 → G13 | Outbound | **Deferred — DEC-008** |
| Content → A/C entities | Editorial | نکات برجسته مسیر — بدون رابطه |
| Content → P7 | Editorial | راهنمای عملی جابه‌جایی |

### Aggregation Sources

هیچ‌کدام — P9 هیچ رابطه Inbound در R01–R27 ندارد.

### Aggregation Targets

R03/R05 دوطرفه قابل نمایش‌اند: صفحات G3/G5/G7/G8 می‌توانند بخش «این مکان بخشی از این مسیرهای گردشگری است» را از همین رابطه (نه رابطه جدید) نمایش دهند — این صرفاً نمایش معکوس همان R03/R05 است.

| صفحه Aggregation | فیلتر |
|-------------------|-------|
| «مسیرهای گردشگری ایران» — Hub | همه P9 instances |
| «مسیرهای تاریخی» / «مسیرهای طبیعی» / «مسیرهای موضوعی» | Classification (CA-P-D001) |

### SEO Purpose

- کوئری‌های هدف: "Iran Silk Road route"، "Persian Garden route"، "best road trip Iran"، «مسیر گردشگری تاریخی ایران»
- فرصت SEO منحصربه‌فرد: مسیرهای موضوعی (Thematic) می‌توانند مستقیماً به خوشه‌های محتوایی C1 (صنایع‌دستی) یا C6 (موسیقی) گره بخورند — مثلاً «مسیر صنایع‌دستی ایران» — از طریق Content Layer، بدون رابطه گراف.

### AEO Purpose

- "What are the best tourism routes in Iran?"
- "How long does the [route name] take?"
- "What cities does the [route name] pass through?"
- "What is the best time to travel the [route name]?"

### Schema Readiness *(باشد — تطابق بومی واقعی)*

```json
{
  "@type": "TouristTrip",
  "name": "[name_en]",
  "description": "[چکیده]",
  "itinerary": [
    { "@type": "Place", "name": "[G stop 1]" },
    { "@type": "Place", "name": "[G stop 2]" }
  ],
  "touristType": "[classification]"
}
```
نکته مثبت: `TouristTrip` یک type بومی واقعی در Schema.org است که دقیقاً با ماهیت P9 (توالی جغرافیایی مرتب) تطابق دارد — مشابه کشف‌های مثبت قبلی مثل `Language` برای C8.

### Notes

- **مرز حیاتی P9 در برابر Itinerary (تکرار CA-P-D005):** این مهم‌ترین نکته کل موجودیت P9 است. اگر تیم محتوا یک برنامه سفر شخصی‌سازی‌شده («۱۰ روز در ایران») بسازد، **هرگز** آن را با این template نسازد — آن Itinerary است (Content Type ادیتوریال که چند P9 + A + G را ترکیب می‌کند)، نه یک P9 instance جدید. نقض این مرز مستقیماً Entity Inflation ایجاد می‌کند.
- **Ordered Sequence در Phase 4:** IA صراحتاً گفته "Ordered sequence storable as relationship properties" — یعنی توالی توقف‌ها باید یک property (`sequence_order: Integer`) روی خودِ رابطه R03 ذخیره شود، نه یک فیلد جداگانه روی P9. این نکته باید مستقیماً به تیم Technical Architecture (Phase 4) منتقل شود.
- **G13 Deferred (DEC-008):** template باید طوری طراحی شود که غیبت این فیلد صفحه را نشکند — رندر Conditional، نه Required.
- **غنای روایی بدون Relationship Inflation:** P9 وسوسه‌انگیزترین موجودیت برای افزودن رابطه مستقیم به C1/C6/C9 (برای مسیرهای موضوعی) است — اما طبق CA-P-D002، این ارتباطات همیشه Content Layer باقی می‌مانند مگر IA رسماً بازنگری شود.

---
# CA-P-REVIEW-REPORT

---

## ۱. IA Compliance

| موجودیت | Spatial Scope مطابق IA | Classification مطابق IA | نتیجه |
|---------|--------------------------|---------------------------|--------|
| P1 | Country-Wide ✅ | ندارد (صحیح — IA هیچ Classification برای P1 تعریف نکرده) | ✅ PASS |
| P2 | Country-Wide ✅ | ندارد | ✅ PASS |
| P4 | Country-Wide ✅ | ندارد | ✅ PASS |
| P5 | Country-Wide ✅ | ندارد | ✅ PASS |
| P6 | Country-Wide ✅ | ندارد | ✅ PASS |
| P7 | Two levels (Dual-Level) ✅ | ندارد | ✅ PASS |
| P9 | Multi-Area ✅ | ۳ مقدار (Historical/Natural/Thematic) ✅ | ✅ PASS |

**نتیجه کلی: ✅ PASS — تمام ۷ موجودیت دقیقاً مطابق تعاریف IA-v1.2.1-FINAL طراحی شدند.**

---

## ۲. R22 Compliance

| بررسی | نتیجه |
|--------|--------|
| Sources صحیح (فقط P1, P2, P4, P5, P6) | ✅ |
| Targets صحیح (فقط G1, G3, G5) | ✅ |
| جهت رابطه: P → G در همه موارد (نه معکوس) | ✅ — اصلاح‌شده طبق P-PATCH-001، بدون تکرار خطا در این سند |
| P7 صریحاً از R22 مستثنا شده | ✅ — تأیید شد؛ P7 مکانیزم Dual-Level مستقل خودش را دارد (DEC-003) |
| P9 صریحاً از R22 مستثنا شده | ✅ — P9 از R03/R05 استفاده می‌کند، نه R22 |
| DEC-004 Matrix به‌طور یکنواخت اعمال شده | ✅ — هر ۵ موجودیت دقیقاً طبق جدول مصوب |

**نتیجه: ✅ PASS کامل**

---

## ۳. R23 Compliance

| بررسی | نتیجه |
|--------|--------|
| Sources صحیح (فقط C10, E1) | ✅ |
| Target صحیح (فقط P6) | ✅ |
| P6 این دو Source را به‌درستی در Aggregation Sources مستند کرده | ✅ |
| هیچ P entity دیگری ادعای R23 نکرده | ✅ (بررسی P1,P2,P4,P5,P7,P9 — هیچ‌کدام) |
| Deferred markers در CA-C/CA-E اکنون قابل فعال‌سازی‌اند | ✅ — با انتشار این سند |

**نتیجه: ✅ PASS کامل**

---

## ۴. R24 Compliance

| بررسی | نتیجه |
|--------|--------|
| Source صحیح (فقط A3) | ✅ |
| Target صحیح (فقط P5) | ✅ |
| P5 این را به‌عنوان تنها Source «کاملاً معتبر» کل Category P مستند کرده | ✅ |
| هیچ P entity دیگری ادعای R24 نکرده | ✅ |
| A3→P5 در CA-A دست‌نخورده باقی مانده (تأیید صریح کاربر) | ✅ |

**نتیجه: ✅ PASS کامل**

---

## ۵. Geography First Compliance

| موجودیت | مکانیزم لنگر جغرافیایی | نتیجه |
|---------|--------------------------|--------|
| P1, P2, P4, P5, P6 | R22 → G1 (حداقل Full در G1) | ✅ |
| P7 | Dual-Level — Level 2 مستقیماً روی G5 (DEC-003) | ✅ — مکانیزم متفاوت اما معتبر |
| P9 | R03 (route_sequence) — Required field، حداقل یک توالی G | ✅ |

**نتیجه: ✅ PASS — همه موجودیت‌ها بدون استثنا لنگر جغرافیایی دارند.**

---

## ۶. One Physical Place = One Primary Node

| بررسی | نتیجه |
|--------|--------|
| P7 Level 2 روی G5 موجود (نه node مستقل جدید) | ✅ |
| P9 route_sequence به nodeهای موجود G3/G5/G7/G8 اشاره می‌کند (بدون تکرار) | ✅ |
| P1 entry_points_reference: Content Layer به G7/G8 موجود، بدون node جدید | ✅ |
| P4 local_emergency_facilities: Content Layer متن، بدون node «بیمارستان» جدید (چنین entity در IA تعریف نشده) | ✅ |
| P2 exchange_locations_reference: Content Layer، بدون node جدید | ✅ |

**نتیجه: ✅ PASS — هیچ موجودیت P یک مکان فیزیکی را تکرار نکرده.**

---

## ۷. Entity Inflation Check

| بررسی | نتیجه |
|--------|--------|
| تعداد نهایی موجودیت‌های طراحی‌شده | ۷ (P1, P2, P4, P5, P6, P7, P9) — دقیقاً مطابق IA |
| P3, P8 معرفی نشدند | ✅ — همچنان غایب، FIR-P-001 پابرجاست |
| هیچ Sub-Entity ساخته نشد (VisaType، HolidayInstance، RouteStop و...) | ✅ — همه این‌ها Structured Fields هستند، نه Entity مستقل |
| P6 بدون نمونه استانی/شهری مستقل (DEC-005) | ✅ |

**نتیجه: ✅ PASS — صفر Entity Inflation.**

---

## ۸. Relationship Inflation Check

| بررسی | نتیجه |
|--------|--------|
| فقط روابط مصوب IA استفاده شدند (R22, R23, R24, R03, R05) | ✅ |
| هر ارجاع بدون مبنای IA → Content Layer (Text+URL)، نه Relation | ✅ — طبق CA-P-D002 در سراسر سند |
| هیچ Relation جدید بین P و A/C/E بدون مبنای IA ساخته نشد | ✅ |
| **یادداشت (رفع‌شده در v1.1):** Finding CA-P-F009 — سه فیلد در CA-E (`visa_info_link`, دو `safety_link` روی E1/E5/E6) قبلاً به‌اشتباه `Relation` بودند | ✅ رفع شد — AUDIT-PATCH-002/003 |

**نتیجه: ✅ PASS برای این سند — با یک بدهی معماری شناخته‌شده و مستند در CA-E که مسدودکننده نیست.**

---

## ۹. Circular Dependency Check

| مسیر | نتیجه |
|------|--------|
| P1/P2/P4/P5/P6 → G (R22) | یکطرفه — بدون بازگشت |
| C10 → P6 و E1 → P6 (R23) | یکطرفه — P6 هیچ Outbound به C10/E1 ندارد |
| A3 → P5 (R24) | یکطرفه — P5 هیچ Outbound به A3 ندارد |
| P9 → G (R03/R05) | یکطرفه — نمایش معکوس در G همان رابطه است، نه رابطه جدید |
| P7 | صفر رابطه — بدون امکان چرخه |

**نتیجه: ✅ PASS — هیچ Circular Dependency در کل Category P یافت نشد.**

---

## جمع‌بندی نهایی Review Report

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CA-P-REVIEW-REPORT — SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

IA Compliance                  ✅ PASS
R22 Compliance                 ✅ PASS
R23 Compliance                 ✅ PASS
R24 Compliance                 ✅ PASS
Geography First Compliance     ✅ PASS
One Physical Place = One Node  ✅ PASS
Entity Inflation Check         ✅ PASS (0 new entities)
Relationship Inflation Check   ✅ PASS (0 new relationships;
                                   1 known non-blocking debt: F009)
Circular Dependency Check      ✅ PASS (0 cycles)

Architecture Freeze:            INTACT ✅
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

# Decision Log — تجمیعی و نهایی

| Decision ID | موضوع | Rationale | Impact | وضعیت |
|------------|--------|-----------|--------|--------|
| **CA-P-DEC-003** *(پیش‌تأیید)* | P7 Dual-Level Model | جلوگیری از Thin Content per route؛ تفکیک نمای کلی کشوری از جزئیات شهری | P7 بدون رابطه گراف به G5؛ Level 2 = Text مستقل | Revisable |
| **CA-P-DEC-004** *(پیش‌تأیید)* | R22 Application Matrix | جلوگیری از SEO Cannibalization و Thin Content در سطوح G نامرتبط | هر P entity فقط در سطوح معنادار G محتوای کامل دارد | Revisable |
| **CA-P-DEC-005** *(پیش‌تأیید)* | P6 Model A — Entity واحد | Spatial Scope=Country-Wide + R23 Notes منطبق بر یک صفحه با Authority بالا | یک صفحه P6؛ رندر Conditional برای G3/G5 | Revisable |
| **CA-P-DEC-008** *(پیش‌تأیید)* | P9 → G13 Deferred | ماهیت G13 نامشخص است | template P9 بدون شکستن، منتظر شفاف‌سازی | Revisable |
| **CA-P-D001** | P9 Classification = Required Field | ۳ مقدار رسمی IA بدون ابهام | فیلتر ساختاریافته مسیرها از روز اول | Non-Revisable |
| **CA-P-D002** | Content Layer Convention برای کل P | تعمیم درس‌آموخته از P-PATCH-001/003 به کل Category؛ جلوگیری از تکرار F001/F006/F009 | هر ارجاع بدون مبنای IA = Text+URL، هرگز Relation | Non-Revisable |
| **CA-P-D003** | P7 Level 1 = محتوای Evergreen | جدول زمانی/قیمت به‌سرعت stale می‌شود و اعتماد AEO/GEO را از بین می‌برد | P7 بدون فیلد schedule/price؛ این حوزه F6 آینده است | Revisable |
| **CA-P-D004** | Volatility Policy (P1/P2) | اطلاعات پرنوسان (نرخ ارز زنده، جداول ویزا) در محتوای static خطرناک است | `last_verified_date` الزامی؛ عدم ذخیره نرخ زنده | Revisable |
| **CA-P-D005** | مرز P9 ≠ Itinerary | IA صریحاً Itinerary را Content Type اعلام کرده، نه Entity | هیچ Itinerary هرگز از template P9 استفاده نمی‌کند | Non-Revisable |

---

## Future IA Review Items (تجمیعی)

| کد | موضوع | اولویت |
|----|--------|--------|
| FIR-P-001 | P3/P8 غایب بدون توضیح در IA | پایین |
| **رفع‌شده:** P-PATCH-004 (پیشنهادی) | اصلاح سه فیلد در CA-E (`visa_info_link`، `safety_link` روی E5، `safety_link` روی E6) از Relation به Content Layer — طبق Finding CA-P-F009 | ✅ اجراشده به‌عنوان AUDIT-PATCH-002/003 (به‌همراه یک فیلد چهارم کشف‌شده: `etiquette_link` روی E5) |

---

# وضعیت نهایی Category P

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CATEGORY P — PRACTICAL TRAVEL INFORMATION
CONTENT ARCHITECTURE FINAL STATUS
Phase 3 | ToPersia | IA-v1.2.1-FINAL Reference
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Entities Designed:              7 / 7    ✅
  P6  Calendar & Public Holidays  ✅ (Model A — DEC-005)
  P1  Visa & Entry Requirements   ✅ (Highest error-cost page)
  P2  Currency & Payment          ✅ (Rial/Toman + card limitation)
  P4  Health & Safety             ✅ (Most complex Aggregation Sources)
  P5  Travel Etiquette            ✅ (Only entity with valid Inbound — R24)
  P7  Public Transport            ✅ (Dual-Level — DEC-003; zero IA relations)
  P9  Tourism Route               ✅ (TouristTrip native Schema fit)

Decisions Logged:               9 total (4 pre-approved + 5 new)
Findings Carried Forward:       CA-P-F009 (✅ RESOLVED in v1.1 — see AUDIT-PATCH-002/003)
Future IA Review Items:         1 (FIR-P-001 only — P-PATCH-004 executed)

Review Report:                  9/9 checks PASS ✅
Architecture Freeze:            INTACT ✅
New Entities:                   0
New Relationships:              0
New Constraints:                0
New Categories:                 0

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STATUS: ✅ CATEGORY P CONTENT ARCHITECTURE — FINAL
Documentation Ready | Phase 4 Ready
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

*ToPersia CA-P-FINAL-v1.2*
*Phase 3: Content Architecture — Category P*
*مرجع: IA-v1.2.1-FINAL (Frozen)*
*وضعیت: COMPLETE — FINAL*
