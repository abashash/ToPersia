# ToPersia — Phase 3: Content Architecture
## Category A — Attractions
### Version 1.2 | FINAL
#### مرجع: IA-v1.2.1-FINAL (Frozen) | Phase 3 Approved Decisions Applied

> **Changelog v1.0 → v1.1:** AUDIT-PATCH-001 (A9 `safety_info`→`safety_link` Content Layer، ۳ موضع) و AUDIT-PATCH-004 (اصلاح ارجاعات نادرست `R15←E6` به `R16←E6` طبق CA-E-D001، ۳ موضع) اجرا و با grep تأیید شد. جزئیات کامل: PHASE3-VALIDATION-LOG-v1_1.md.
> **Changelog v1.1 → v1.2:** بدون تغییر محتوا — هم‌ترازسازی نسخهٔ مجموعه با CA-C-v1.2.

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| پروژه | ToPersia |
| فاز | Phase 3 — Content Architecture |
| Category | A — Attractions |
| نسخه | 1.2-FINAL |
| وضعیت | APPROVED |
| مرجع IA | IA-v1.2.1-FINAL (Frozen) |
| مرجع CA | CA-G-v1.0-FINAL |
| تصمیمات اعمال‌شده | CA-A-D001 · CA-A-D002 · CA-A-D003 · CA-A-D004 · CA-A-D005 |
| موجودیت‌های پوشش‌داده‌شده | ۸ / ۸ |
| Architecture Freeze | INTACT |

---

## بخش ۱ — استخراج رسمی از IA-v1.2.1 پیش از طراحی

### ۱.۱ موجودیت‌های Category A — تأیید از IA

| کد | نام EN | نام FA | یادداشت IA | Spatial Scope |
|----|--------|--------|-----------|--------------|
| A1 | Tourist Attraction | جاذبه گردشگری | Abstract — P-07 — never instantiated directly | N/A |
| A2 | Historical Monument | بنای تاریخی | Attribute: venue_eligible (true/false) | Point |
| A3 | Religious Site | مکان مذهبی | Dual audience: cultural tourists + pilgrims | Point |
| A7 | Museum | موزه | Institutional entity — may reside within A2 | Point |
| A9 | National Park / Protected Area | پارک ملی / منطقه حفاظت‌شده | ManagedArea subtype for internationally designated instances | Area |
| A10 | Natural Feature | عارضه طبیعی | — | Point |
| A12 | Archaeological Site | محوطه باستانی | Distinct from A2: no standing structure required | Point / Area |
| A13 | Cultural Landscape | منظر فرهنگی | Composite Entity — may span multiple G3, A2, A10 | Area |

**تعداد از IA: ۸ موجودیت ✅**

### ۱.۲ Classifications رسمی زیر Category A — تأیید از IA

**Under A1 — Tourist Attraction:**
Persian Garden (باغ ایرانی) · Traditional Bazaar (بازار سنتی) · Coast / Beach (ساحل) · Modern Landmark (سازه مدرن) · Tourism Target Village (روستای هدف گردشگری) *[applied to G7 only]*

**Under A2 — Historical Monument:**
Caravanserai (کاروانسرا) · Historic Bridge (پل تاریخی) · Historic Tower (برج تاریخی) · Palace (کاخ) · Historic Bath (حمام تاریخی) · Historic School (مدرسه تاریخی) · Mausoleum (آرامگاه)

**Under A3 — Religious Site:**
Mosque (مسجد) · Church (کلیسا) · Temple (معبد) · Fire Temple (آتشکده) · Synagogue (کنیسه) · Imamzadeh (امامزاده) · Sufi Lodge (خانقاه)

**Under A7 — Museum:**
Art Gallery (گالری هنری) · House Museum (خانه موزه)

**Under A10 — Natural Feature:**
Waterfall (آبشار) · Cave (غار) · Spring (چشمه) · Canyon / Gorge (تنگه / دره) · Rock Formation (صخره) · Salt Formation / Kalut (کلوت)

### ۱.۳ روابط مرتبط با Category A — تأیید از IA

| کد | رابطه | جهت | Sources در A | Targets در A | یادداشت |
|----|--------|-----|-------------|-------------|---------|
| R01 | LOCATED_IN | A → G | همه A entities | — | Targets: G1–G8. C1. |
| R02 | CONTAINS | A → child | A9، A13، ManagedArea | همه A entities | Derived از R01. I3. |
| R11 | BELONGS_TO_PERIOD | A → C13 | A2، A3، A12 | — | Foundation تاریخی |
| R12 | BUILT_BY / COMMISSIONED_BY | A → C12 | A2، A12 | — | فقط اگر مستند |
| R13 | ASSOCIATED_WITH_FIGURE | A → C12 | A2، A3، A12 | — | DC-R-001 |
| R14 | BORN_IN / DIED_IN / BURIED_IN | C12 → A | — | A2 (tomb/mausoleum) | سه رابطه جداگانه |
| R15 | HELD_AT | E → A | — | A2(venue_eligible:true)، A3، A7، A9 | C5 |
| R20 | DESIGNATED_BY | A → D | A2، A3، A9، A10، A12، A13 | — | Qualified. C6. **A7 در لیست نیست.** |
| R25 | BEST_VISITED_DURING | A → Attribute | همه A entities | — | Stored as attribute |
| R26 | LOCATED_IN_MANAGED_AREA | A → ManagedArea | A10، A9 | — | Exception به C3 |

**نکته مهم:** A7 در Sources R20 نیست. این Intentional است — IA این را به فاز آینده defer کرده.

### ۱.۴ Constraints مرتبط با Category A

| Constraint | متن IA | اثر روی A |
|-----------|--------|----------|
| C1 | One Primary LOCATED_IN | هر A entity دقیقاً یک LOCATED_IN → G دارد |
| C3 | LOCATED_IN → Category G Only | PROHIBITED: A2 → LOCATED_IN → A9 |
| C5 | Venue Eligibility | A2 فقط با venue_eligible:true Target R15 می‌شود — per instance |
| C6 | No Designation Event Node | R20 همیشه relationship است، نه node |
| C9 | DESIGNATED_BY Non-Inheritance | Designation از A13 به A2/A10 داخلش ارث نمی‌رسد |

### ۱.۵ Principles و Decisions IA مرتبط

| منبع | مفاد | اثر |
|------|------|-----|
| P-01 | Geography First | هر A باید به G متصل باشد |
| P-02 | One Physical Place = One Primary Node | A7 داخل A2 → دو node مستقل |
| P-07 | Abstract Entity Rule | A1 هرگز مستقیم instantiate نمی‌شود |
| I2 | DESIGNATED_BY Non-Inheritance | = C9 |
| I3 | CONTAINS as Derived Inverse | R02 از R01 مشتق است |
| I5 | Abstract Entity Non-Instantiation | هیچ entity نمی‌تواند فقط نوع A1 داشته باشد |
| D-004 | Layered model for Category A | Abstract→Concrete→Classification→Attribute |
| D-005 | 5 types → Classifications | Persian Garden, Bazaar, Coast, Modern Landmark, Tourism Village |
| D-006 | 5 types → Entities | A2، A3، A7، A12، A13 |
| D-013 | A1 Abstract Entity | Non-Revisable — هرگز صفحه مستقل |
| D-026 | Aggregation pages | Unique editorial content اجباری |
| DC-R-004 | A2 در R15 | venue_eligible per instance |

---

## بخش ۲ — تصمیمات Phase 3 اعمال‌شده

### CA-A-D001 — Classification under A1: قانون اعمال روی Concrete Entities
**Decision:** Classifications under A1 به concrete types اعمال می‌شوند:
- Persian Garden → A13 (اگر multi-component) یا A2 (اگر single building)
- Traditional Bazaar → A2
- Coast / Beach → A10
- Modern Landmark → A2
- Tourism Target Village → G7 (از IA مشخص است)
**Rationale:** P-02 یک مکان فیزیکی = یک primary node. بدون این قانون، Classification مبهم باقی می‌ماند.
**Impact:** Editorial Guideline اجباری — هر Classification باید قبل از assign به entity تأیید شود.
**Revisable:** Yes

### CA-A-D002 — A7 housed_in A2: مدل Content Layer
**Decision:** A7 → LOCATED_IN → G5 (در گراف). رابطه با A2 فقط در Content Layer: `housed_in: Text` (نام A2 + لینک). نه Graph Relationship.
**Rationale:** C3 صریح است: LOCATED_IN فقط → G. PROHIBITED: A7 → LOCATED_IN → A2. رابطه از طریق content field و internal link نمایش داده می‌شود.
**Impact:** گراف سالم. صفحه A7 لینک به A2 parent دارد. صفحه A2 لینک به A7 های داخل خودش دارد.
**Revisable:** Yes — اگر در Phase 4 رابطه graph-level لازم شد.
**IA Basis:** C3، R01، P-02، IA Note on A7

### CA-A-D003 — A13 Multi-G3: قانون LOCATED_IN
**Decision:** همان قانون CA-G-F003 برای A13. Primary LOCATED_IN = G3 اصلی (بزرگ‌ترین بخش). `provinces_spanning: Text[]` در Content Layer — نه Graph Relationship.
**Rationale:** C1 از "primary" استفاده می‌کند نه "only." A13 می‌تواند Multi-Area باشد اما یک primary LOCATED_IN دارد.
**Impact:** گراف یکپارچه. صفحه A13 می‌تواند extent جغرافیایی کامل را نمایش دهد.
**Revisable:** Yes
**IA Basis:** C1، CA-G-F003، P-06

### CA-A-D004 — A2 vs A12: معیار تمایز
**Decision:** معیار تمایز قاطع: وجود standing structure.
- A2: بنای تاریخی با ساختار فیزیکی باقی‌مانده و قابل بازدید
- A12: محوطه باستانی که ممکن است هیچ standing structure نداشته باشد
هر دو می‌توانند R11، R12، R13، R20 داشته باشند.
**Rationale:** IA صریح است: "Distinct from A2: no standing structure required."
**Impact:** Editorial: در هر مورد مشکوک، بررسی وجود standing structure الزامی است.
**Revisable:** Yes

### CA-A-D005 — A9 ManagedArea و R26: مدل dual-relationship
**Decision:** اگر A9 designation D3/D4 دارد → ManagedArea subtype فعال می‌شود (D-016). A10 و A9 های داخل می‌توانند R26 → ManagedArea داشته باشند علاوه بر R01 → G3.
**Rationale:** R26 صریحاً exception به C3 است. "Limited to managed areas only." این یک second stored relationship است که توسط IA مجاز شمرده شده.
**Impact:** A10 داخل ManagedArea دارای دو مسیر اتصال است: R01 → G3 (primary geography) + R26 → ManagedArea (managed area membership). هر دو در گراف stored هستند — این استثنای صریح IA است.
**Revisable:** Yes
**IA Basis:** R26، D-016، C3 Exception

---

## بخش ۳ — Content Architecture — ۸ موجودیت Category A

---

### A1 — Tourist Attraction | جاذبه گردشگری

#### هدف موجودیت

A1 یک **Abstract Entity** است (D-013، P-07، I5). این به معنای دقیق است که:

**A1 هرگز instantiate نمی‌شود.** هیچ صفحه‌ای با نوع "Tourist Attraction" در سیستم وجود ندارد. هیچ node در Knowledge Graph نمی‌تواند فقط نوع A1 داشته باشد (I5).

A1 در Content Architecture سه کارکرد دقیق دارد:

**کارکرد ۱ — Classification Container:**
پنج Classification زیر A1 هستند که به concrete entities اعمال می‌شوند (نه به A1 خودش). این Classifications از طریق field `a1_classification` روی A2، A10، A13 ثبت می‌شوند — طبق CA-A-D001.

**کارکرد ۲ — Attribute Inheritance:**
طبق I5، attributes A1 به concrete child entities جریان می‌یابند. هر entity از Category A به طور implicit از A1 ارث می‌برد — بدون instantiation مستقیم.

**کارکرد ۳ — Aggregation Source:**
A1 پایه conceptual برای aggregation pageهای cross-type است. مثال: "برترین جاذبه‌های اصفهان" یک aggregation از A2+A3+A7+A10+A12+A13 است — نه یک page از نوع A1.

#### Page Template
**A1 هیچ Page Template ندارد — Non-Revisable (D-013).**

هر صفحه‌ای که جاذبه گردشگری را نمایش می‌دهد، باید از یک concrete type استفاده کند.

#### Aggregation Pages از A1

Aggregation pages از ترکیب A1 + G entities ایجاد می‌شوند — نه از instantiation A1:

| Aggregation Page | ترکیب منطقی | Editorial Requirement |
|----------------|------------|----------------------|
| "Attractions in [Province]" | G3 + R01 ← (A2+A3+A7+A10+A12+A13) | Unique editorial intro اجباری (D-026) |
| "Attractions in [City]" | G5 + R01 ← (A2+A3+A7+A10+A12+A13) | Unique editorial intro اجباری |
| "Persian Gardens of Iran" | Classification "Persian Garden" روی A instances | Editorial اجباری |
| "Historic Bazaars of Iran" | Classification "Traditional Bazaar" روی A2 instances | Editorial اجباری |
| "Coastal Attractions of Iran" | Classification "Coast/Beach" روی A10 instances | Editorial اجباری |

#### SEO Purpose
A1 مستقیماً SEO value ندارد. اما aggregation pages از A1 head keywords را target می‌کنند:
- "Iran tourist attractions" | "things to see in Iran" | "Iran historical sites"

#### AEO Purpose
Aggregation pages پایه پاسخ‌های سطح بالا برای AI Engines هستند:
- "What are the main types of attractions in Iran?"
- "What should I visit in Isfahan?"

#### MVC
**A1 هیچ MVC ندارد — صفحه وجود ندارد.**

---

### A2 — Historical Monument | بنای تاریخی

#### هدف موجودیت

A2 پُرتنوع‌ترین و پُرارجاع‌ترین concrete entity در Category A است. شامل تمام بناهای تاریخی با **standing structure** است. دارای دو ویژگی معماری منحصربه‌فرد:

۱. **venue_eligible attribute** — برخی A2 instances می‌توانند venue رویدادها باشند (R15)
۲. **Target R14** — C12 می‌تواند BURIED_IN یا BORN_IN یا DIED_IN یک A2 باشد

هفت Classification زیر A2: Caravanserai · Historic Bridge · Historic Tower · Palace · Historic Bath · Historic School · Mausoleum

#### Page Template: Historical Monument

```
[Hero Section]
  — تصویر اصلی بنا
  — نام (FA / EN)
  — Classification badge
  — City | Province | Historical Period

[Identity Block — Structured]
  — نام رسمی | نام تاریخی (اگر متفاوت)
  — Classification (از IA)
  — دوره تاریخی ← R11 → C13
  — سازنده / کارفرما ← R12 → C12 (فقط اگر مستند)
  — موقعیت: GeoCoordinates (Point)
  — venue_eligible status
  — وضعیت: فعال / در حال مرمت / دسترسی محدود

[Designation Block — Structured]
  — D1 UNESCO World Heritage (R20 → D1): سال + معیار + status
  — National Registration (attribute)
  — venue_eligible (اگر true — با توضیح)

[Overview — 600+ words]
  — تاریخچه | معماری | مصالح | اهمیت فرهنگی

[Historical Context]
  — R11 → C13: دوره تاریخی
  — R12 → C12: سازنده (اگر مستند)
  — R13 → C12: شخصیت‌های مرتبط
  — R14 ← C12: مدفونین (فقط برای Mausoleum classification)

[Museum Inside — Section Conditional]
  — فقط اگر A7 با housed_in → این A2 وجود دارد
  — لینک به صفحه A7

[Events Hosted — Section Conditional]
  — فقط اگر venue_eligible: true
  — E entities از R15 → این A2

[Visitor Information]
  — ساعات بازدید | بلیت | دسترسی معلولین
  — R25: best_season
  — نزدیک‌ترین حمل‌ونقل

[Nearby Attractions — Contextual]
  — A entities با R01 → همان G5، از طریق R02

[Getting There]
  — از G5 parent

[Schema Block]
```

#### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G5 یا G7 | R01 — C1 — C3 |
| classification | Enum (Caravanserai / Historic Bridge / Historic Tower / Palace / Historic Bath / Historic School / Mausoleum) | IA Classifications under A2 |
| geo_coordinates | GeoCoordinates (Point) | P-01 |
| historical_period | Relation → C13 | R11 |
| venue_eligible | Boolean | C5 — DC-R-004 |
| overview_text | LongText (600+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_historical | Text | نام تاریخی اگر متفاوت است |
| builder | Relation → C12 | R12 — فقط اگر مستند |
| associated_figures | Relation[] → C12 | R13 |
| buried_figures | Relation[] → C12 | R14 — فقط Mausoleum |
| world_heritage | Boolean | D1 via R20 |
| world_heritage_year | Number | R20 property: year |
| world_heritage_criteria | Text | R20 property: criteria |
| national_registration | Boolean | attribute |
| visiting_hours | Text | — |
| admission_fee | Text | — |
| accessibility | Text | — |
| restoration_status | Enum (Active / Restored / Restricted) | — |
| a1_classification | Text[] | Classifications under A1 اگر applicable (Persian Garden، Traditional Bazaar) |
| featured_image_gallery | Image[] | — |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R01 → G5 | صفحه شهر | Breadcrumb |
| R01 → G3 (transitively via I1) | صفحه استان | Breadcrumb سطح ۲ |
| R11 → C13 | دوره تاریخی | بخش تاریخی |
| R12 → C12 | سازنده | بخش تاریخی |
| R13 → C12 | شخصیت‌های مرتبط | بخش تاریخی |
| R14 ← C12 | مدفونین (Mausoleum) | بخش تاریخی |
| R15 ← E1/E3/E5 | رویدادهای مستقر | Conditional section |
| R20 → D1 | صفحه UNESCO | بخش designations |
| housed_in ← A7 | موزه داخل بنا | Conditional section |

#### Aggregation Pages از A2

| Aggregation Page | فیلتر |
|----------------|-------|
| "Historical Monuments in [Province/City]" | G3/G5 + R01 ← A2 |
| "UNESCO World Heritage Sites in Iran" | D1 + R20 ← A2 |
| "Caravanserais of Iran" | Classification = Caravanserai |
| "Palaces of Iran" | Classification = Palace |
| "Mausoleums of Iran" | Classification = Mausoleum |

هر aggregation page باید unique editorial intro داشته باشد (D-026).

#### SEO Purpose
- Target Queries: "Golestan Palace Tehran", "Ali Qapu Isfahan", "caravanserais Iran travel"
- A2 بالاترین تراکم D1 sites را دارد → کلیدی‌ترین entity برای UNESCO content cluster
- Long-tail: "[monument name] history", "who built [monument]", "visiting [monument]"
- Classification-based clusters: هر classification یک topic cluster مستقل

#### AEO Purpose
- "When was [monument] built?"
- "Who built [monument]?"
- "Is [monument] a UNESCO World Heritage Site?"
- "What historical period does [monument] belong to?"
- "Is [monument] open to visitors?"
- "Who is buried in [mausoleum]?"

پاسخ‌های AEO باید در Identity Block (اولین ۱۵۰ کلمه صفحه) قابل استخراج باشند.

#### Schema Readiness

```json
{
  "@type": "LandmarksOrHistoricalBuildings",
  "name": "[نام EN]",
  "alternateName": "[نام FA]",
  "description": "[overview excerpt — 150 words]",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "[lat]",
    "longitude": "[lon]"
  },
  "containedInPlace": {
    "@type": "City",
    "name": "[G5 name]"
  },
  "isAccessibleForFree": "[Boolean]",
  "publicAccess": "[Boolean]",
  "openingHoursSpecification": "[visiting_hours]",
  "additionalProperty": {
    "@type": "PropertyValue",
    "name": "historicalPeriod",
    "value": "[C13 name]"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ name_en + name_fa
- ✅ located_in → G5 (C1، C3 رعایت شده)
- ✅ classification از IA مشخص
- ✅ historical_period → C13
- ✅ venue_eligible: true یا false (C5)
- ✅ overview_text ≥ 600 کلمه
- ✅ geo_coordinates (Point)
- ✅ world_heritage status مشخص
- ✅ best_season
- ✅ Schema.org LandmarksOrHistoricalBuildings کامل
- ✅ حداقل یک پاسخ AEO در اولین ۱۵۰ کلمه

---

### A3 — Religious Site | مکان مذهبی

#### هدف موجودیت

A3 دارای **dual audience** است که IA صریحاً ذکر کرده: گردشگران فرهنگی + زائران مذهبی. این تمایز در ساختار محتوا باید منعکس شود. A3 دارای یک رابطه منحصربه‌فرد است که هیچ entity دیگری ندارد: **R24 — REQUIRES_COMPLIANCE_WITH → P5** (Travel Etiquette). این رابطه در IA Required است.

هفت Classification زیر A3: Mosque · Church · Temple · Fire Temple · Synagogue · Imamzadeh · Sufi Lodge

#### Page Template: Religious Site

```
[Hero Section]
  — تصویر اصلی
  — نام (FA / EN)
  — Classification badge
  — City | Province | Religion

[Identity Block — Structured]
  — نام رسمی | نام مذهبی/تاریخی
  — Classification (از IA)
  — دین / مذهب
  — دوره تاریخی ← R11 → C13
  — شخصیت مرتبط ← R13 → C12 (اگر applicable)
  — Spatial Scope: Point
  — وضعیت: فعال عبادی / فعال زیارتی / تاریخی / ترکیبی

[Designation Block — Structured]
  — D1 (R20 → D1): سال + معیار
  — National Registration (attribute)

[Overview — 500+ words]
  — تاریخچه | معماری | اهمیت مذهبی | اهمیت فرهنگی

[For Cultural Visitors — بخش گردشگران فرهنگی]
  — معماری و تزئینات | آثار هنری | نکات بازدید
  — best_season (R25)

[For Pilgrims — بخش زائران]
  — اهمیت مذهبی | آداب زیارت
  — R15 ← E5: مراسم مذهبی برگزارشده در این مکان

[Visitor Etiquette — بخش اجباری]
  — R24 → P5: لینک الزامی به صفحه Travel Etiquette
  — پوشش اجباری | محدودیت‌های عکاسی | زمان‌های محدودیت

[Getting There]
  — از G5 parent

[Nearby Attractions — Contextual]

[Schema Block]
```

#### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G5 یا G7 | R01 — C1 |
| classification | Enum (Mosque / Church / Temple / Fire Temple / Synagogue / Imamzadeh / Sufi Lodge) | IA Classifications under A3 |
| religion | Text | — |
| geo_coordinates | GeoCoordinates (Point) | P-01 |
| etiquette_compliance | Relation → P5 | R24 — **اجباری** |
| overview_text | LongText (500+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_religious | Text | نام مذهبی اگر متفاوت است |
| historical_period | Relation → C13 | R11 |
| associated_figures | Relation[] → C12 | R13 |
| world_heritage | Boolean | D1 via R20 |
| national_registration | Boolean | attribute |
| active_worship | Boolean | آیا فعالانه محل عبادت است |
| active_pilgrimage | Boolean | آیا زیارتگاه فعال است |
| dress_code_details | Text | جزئیات پوشش اجباری |
| visiting_hours | Text | — |
| admission_fee | Text | — |
| photography_policy | Enum (Allowed / Restricted / Prohibited) | — |
| featured_image_gallery | Image[] | — |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R01 → G5 | صفحه شهر | Breadcrumb |
| R11 → C13 | دوره تاریخی | بخش تاریخی |
| R13 → C12 | شخصیت‌های مرتبط | بخش تاریخی |
| R15 ← E5 | مراسم مذهبی برگزارشده | بخش زائران |
| **R24 → P5** | آداب سفر | **لینک اجباری در بخش Etiquette** |
| R20 → D1 | UNESCO (اگر applicable) | بخش designations |

#### Aggregation Pages از A3

| Aggregation Page | فیلتر |
|----------------|-------|
| "Religious Sites in [City/Province]" | G5/G3 + R01 ← A3 |
| "Mosques of Iran" | Classification = Mosque |
| "Churches of Iran" | Classification = Church |
| "Fire Temples of Iran" | Classification = Fire Temple |
| "Imamzadehs of Iran" | Classification = Imamzadeh |

#### SEO Purpose
- Target: "Shah Mosque Isfahan", "Imam Reza Shrine Mashhad", "Vank Cathedral Isfahan"
- Dual-audience → دو cluster keyword مستقل: "Iran religious tourism" + "Iran pilgrimage sites"
- Classification-based clusters با search volume بالا

#### AEO Purpose

**برای گردشگران فرهنگی:**
- "What is the architecture of [mosque] like?"
- "Is [religious site] open to non-Muslims?"
- "What religion is [site]?"

**برای زائران:**
- "Is [site] an important pilgrimage destination?"
- "What ceremonies are held at [site]?"

**برای هر دو:**
- "What is the dress code at [site]?"
- "Do I need to cover my hair at [mosque]?"

R24 → P5 تضمین می‌کند پاسخ سؤالات آداب رفتاری از صفحه P5 مرجع می‌گیرد.

#### Schema Readiness

```json
{
  "@type": "PlaceOfWorship",
  "name": "[نام EN]",
  "alternateName": "[نام FA]",
  "description": "[overview excerpt]",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "[lat]",
    "longitude": "[lon]"
  },
  "containedInPlace": {
    "@type": "City",
    "name": "[G5 name]"
  },
  "isAccessibleForFree": "[Boolean]",
  "additionalProperty": {
    "@type": "PropertyValue",
    "name": "religion",
    "value": "[religion]"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ name_en + name_fa
- ✅ classification از IA مشخص
- ✅ located_in → G5
- ✅ religion مشخص
- ✅ etiquette_compliance → P5 (R24 — **اجباری**)
- ✅ overview_text ≥ 500 کلمه
- ✅ بخش گردشگر فرهنگی + بخش زائر هر دو موجود
- ✅ visiting_hours یا access_info
- ✅ dress_code_details
- ✅ Schema.org PlaceOfWorship کامل

---

### A7 — Museum | موزه

#### هدف موجودیت

A7 یک **Institutional Entity** است. موزه به عنوان نهاد (institution) تعریف می‌شود، نه صرفاً یک بنا. این تمایز اساسی با A2 است:

| معیار | A2 (Historical Monument) | A7 (Museum) |
|-------|--------------------------|-------------|
| ماهیت | بنا / ساختار | نهاد / institution |
| محتوا | معماری، تاریخ ساختمان | مجموعه آثار، نمایشگاه |
| کارکرد اصلی | بازدید از بنا | نمایش مجموعه |
| Designation R20 | ✅ مجاز | ❌ A7 در Sources R20 نیست |
| standing structure | ✅ اجباری | ممکن است داخل A2 باشد |

**سناریوی A7 داخل A2:** IA صریحاً می‌گوید "may reside within A2." در این صورت طبق CA-A-D002: A7 → LOCATED_IN → G5 (در گراف) + `housed_in: Text` (در content layer — لینک به A2).

IA این note را در Future Scope دارد: "Museum Institution vs Museum Building split (future phase)" — این تأیید می‌کند که split این دو در Phase 1 intentionally یک entity ساده است.

دو Classification زیر A7: Art Gallery · House Museum

#### Page Template: Museum

```
[Hero Section]
  — تصویر اصلی (ترجیحاً interior با آثار)
  — نام موزه (FA / EN)
  — Classification badge
  — City | Province

[Identity Block — Structured]
  — نام رسمی
  — Classification (Art Gallery / House Museum / General)
  — Founded year (اگر documented)
  — Spatial Scope: Point
  — housed_in (اگر applicable): "[A2 name]" + لینک

[Housed In — Section Conditional]
  — فقط اگر A7 داخل A2 قرار دارد
  — "This museum is located within [A2 name]"
  — لینک به صفحه A2
  — توضیح رابطه

[Collections Overview — 400+ words]
  — دسته‌های اصلی مجموعه
  — آثار شاخص و نمایشگاه‌های دائمی
  — نمایشگاه‌های موقت (اگر applicable)

[Events Hosted — Section Conditional]
  — R15 ← E1/E3/E5 (رویدادهایی که در موزه برگزار می‌شوند)

[Visitor Information]
  — ساعات بازدید | تعطیلی‌ها
  — بلیت | تخفیف‌ها
  — R25: best_season
  — دسترسی

[Getting There]
  — از G5 parent

[Nearby Attractions — Contextual]

[Schema Block]
```

#### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G5 | R01 — C1 — C3 |
| classification | Enum (Art Gallery / House Museum / General Museum) | IA Classifications under A7 |
| geo_coordinates | GeoCoordinates (Point) | P-01 |
| overview_text | LongText (400+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| housed_in | Text (نام A2 + URL) | CA-A-D002 — Content Layer Only |
| founded_year | Text | — |
| visiting_hours | Text | — |
| admission_fee | Text | — |
| collection_categories | Text[] | دسته‌های اصلی مجموعه |
| permanent_highlights | Text[] | آثار شاخص دائمی |
| featured_image_gallery | Image[] | — |

#### ⚠️ Finding CA-A-F001 — A7 در R20 Sources غایب است

**Finding:**
A7 در Sources R20 (DESIGNATED_BY) نیست. Sources: A2, A3, A9, A10, A12, A13.

**Impact Analysis:**
موزه‌ها به عنوان institution designation UNESCO نمی‌گیرند. اگر موزه‌ای داخل یک بنای تاریخی D1-designated باشد، designation روی A2 است (نه A7). C9 نیز تأکید می‌کند که designation از A2 به A7 داخلش ارث نمی‌رسد.

**Recommendation:**
در template A7، هیچ designation field تعریف نمی‌شود. اگر موزه داخل A2 قرار دارد، بخش "Housed In" لینک به A2 می‌دهد و کاربر می‌تواند designation را از صفحه A2 مشاهده کند. این با I2 و C9 سازگار است. این Finding با IA سازگار است و نیاز به تغییر ندارد.

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R01 → G5 | صفحه شهر | Breadcrumb |
| housed_in (content) | صفحه A2 parent | Conditional section |
| R15 ← E1/E3/E5 | رویدادهای موزه | Conditional section |

#### Aggregation Pages از A7

| Aggregation Page | فیلتر |
|----------------|-------|
| "Museums in [City]" | G5 + R01 ← A7 |
| "Art Galleries of Iran" | Classification = Art Gallery |
| "House Museums of Iran" | Classification = House Museum |

#### SEO Purpose
- Target: "National Museum of Iran Tehran", "Tehran Museum of Contemporary Art"
- Art Gallery و House Museum هر کدام cluster کلیدی مجزا دارند
- Long-tail: "what is in [museum] collection", "[museum] opening hours", "[museum] tickets"

#### AEO Purpose
- "What museums are in Tehran?"
- "What is in [Museum Name]'s collection?"
- "When is [Museum] open?"
- "Is [Museum] free to enter?"
- "What type of art does [gallery] show?"

#### Schema Readiness

```json
{
  "@type": "Museum",
  "name": "[نام EN]",
  "alternateName": "[نام FA]",
  "description": "[collection overview]",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "[lat]",
    "longitude": "[lon]"
  },
  "containedInPlace": {
    "@type": "City",
    "name": "[G5 name]"
  },
  "isAccessibleForFree": "[Boolean]",
  "openingHoursSpecification": "[visiting_hours]"
}
```

#### MVC — AEO-First Checklist

- ✅ name_en + name_fa
- ✅ classification از IA
- ✅ located_in → G5
- ✅ overview_text ≥ 400 کلمه (شامل collection highlights)
- ✅ visiting_hours + admission_fee
- ✅ housed_in → A2 اگر applicable (CA-A-D002)
- ✅ Schema.org Museum کامل

---

### A9 — National Park / Protected Area | پارک ملی / منطقه حفاظت‌شده

#### هدف موجودیت

A9 دارای **ManagedArea subtype** است — برای نمونه‌هایی که designation بین‌المللی D3 (Geopark) یا D4 (Biosphere) دارند، A9 instance در Category G نیز به عنوان ManagedArea ظاهر می‌شود (D-016). این dual-presence یک ویژگی معماری منحصربه‌فرد است.

**R26 Exception:** A10 و A9 های داخل یک ManagedArea می‌توانند علاوه بر R01 → G3، رابطه R26 → ManagedArea نیز داشته باشند. این یک استثنای صریح به C3 است که IA آن را مجاز کرده.

**R15 Target:** A9 می‌تواند venue رویدادها باشد (E1/E3/E5). E6 از طریق R16→G3/G5 متصل می‌شود — نه R15 (طبق IA: R15 Sources = E1,E3,E5 — CA-E-D001).

#### Page Template: National Park / Protected Area

```
[Hero Section]
  — تصویر طبیعی (landscape scale)
  — نام پارک | Province(s) | نوع

[Identity Block — Structured]
  — نام رسمی | نام محلی
  — نوع: National Park / Wildlife Refuge / Protected Area
  — مساحت (km²)
  — Province(s) — provinces_spanning: Text[]
  — Spatial Scope: Area
  — ManagedArea status (اگر D3/D4 دارد)
  — وضعیت حفاظتی

[Designation Block — Structured]
  — D3 Geopark (R20 → D3): سال + معیار
  — D4 Biosphere (R20 → D4): سال + معیار
  — D6 National Monument (attribute)
  — D1 (R20 → D1): اگر applicable

[Overview — 500+ words]
  — اهمیت اکولوژیک | تاریخچه تأسیس | چشم‌اندازهای اصلی

[Natural Features Inside — R02]
  — A10 entities داخل پارک (R01 ← A10 / R26 ← A10)
  — لیست با لینک به صفحه‌های A10

[Wildlife & Biodiversity]
  — گونه‌های شاخص جانوری و گیاهی

[Activities & Zones]
  — hiking | wildlife watching | camping | photography
  — مناطق مجاز vs ممنوع

[Events — Section Conditional]
  — R16 ← E6: رویدادهای فصلی/طبیعی (از طریق G3/G5 مجاور پارک)
  — R15 ← E1: festival های مرتبط

[Visitor Information]
  — ورودی | مجوزها | guide اجباری
  — R25: best_season
  — لینک اجباری P4 (ایمنی)

[Getting There]
  — از نزدیک‌ترین G5

[Schema Block]
```

#### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 (primary) | R01 — C1 |
| park_type | Enum (National Park / Wildlife Refuge / Protected Area / Nature Reserve) | — |
| area_km2 | Number | — |
| geo_coordinates | GeoShape (Area) | P-01 |
| overview_text | LongText (500+ words) | — |
| best_season | Enum | R25 |
| safety_link | Text (نام+URL به P4) | Content Layer — editorial link اجباری |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| provinces_spanning | Text[] | CA-A-D003 — Content Layer Only |
| managed_area | Boolean | D-016 |
| geopark | Boolean | D3 via R20 |
| geopark_year | Number | R20 property |
| biosphere_reserve | Boolean | D4 via R20 |
| biosphere_year | Number | R20 property |
| world_heritage | Boolean | D1 via R20 |
| national_monument | Boolean | D6 attribute |
| entry_permit_required | Boolean | — |
| visiting_hours | Text | — |
| key_species | Text[] | گونه‌های شاخص |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R01 → G3 | صفحه استان (primary) | Breadcrumb |
| R02: A9 CONTAINS → A10 | عارضه‌های طبیعی داخل | بخش Natural Features |
| R20 → D3 | صفحه Geopark aggregation | بخش designations |
| R20 → D4 | صفحه Biosphere aggregation | بخش designations |
| R20 → D1 | صفحه UNESCO WHC | بخش designations |
| R26 → ManagedArea | structural — نه display لینک | Graph only |
| P4 (safety_link) | اطلاعات ایمنی — لینک editorial به P4 | Content Layer — نه Graph Edge |

#### Aggregation Pages از A9

| Aggregation Page | فیلتر |
|----------------|-------|
| "National Parks of Iran" | park_type = National Park + R01 |
| "UNESCO Geoparks in Iran" | D3 via R20 ← A9 |
| "UNESCO Biosphere Reserves in Iran" | D4 via R20 ← A9 |

#### SEO Purpose
- Target: "Golestan National Park Iran", "Kavir National Park", "Arasbaran Biosphere Reserve"
- Geopark/Biosphere designation → high-authority content signal برای AI Engines
- Long-tail: "wildlife in [park]", "best time to visit [park]", "permit for [park]"

#### AEO Purpose
- "What national parks are in Iran?"
- "Is [park] a UNESCO Biosphere Reserve?"
- "What animals live in [park]?"
- "When is the best time to visit [park]?"
- "Do I need a permit for [park]?"

#### Schema Readiness

```json
{
  "@type": ["Park", "Place"],
  "name": "[نام EN]",
  "alternateName": "[نام FA]",
  "description": "[overview excerpt]",
  "geo": {
    "@type": "GeoShape",
    "polygon": "[boundary coordinates]"
  },
  "containedInPlace": {
    "@type": "AdministrativeArea",
    "name": "[G3 name]"
  },
  "additionalProperty": [
    {
      "@type": "PropertyValue",
      "name": "parkType",
      "value": "[park_type]"
    },
    {
      "@type": "PropertyValue",
      "name": "area",
      "value": "[area_km2] km²"
    }
  ]
}
```

#### MVC — AEO-First Checklist

- ✅ name_en + name_fa + park_type
- ✅ located_in → G3 (primary)
- ✅ area_km2
- ✅ overview_text ≥ 500 کلمه
- ✅ designation status مشخص (D3/D4/D1/D6)
- ✅ حداقل ۳ A10 با لینک (اگر داخل پارک وجود دارند)
- ✅ safety_link (URL به P4) — لینک editorial اجباری — Content Layer
- ✅ best_season
- ✅ Schema.org Park کامل

---

### A10 — Natural Feature | عارضه طبیعی

#### هدف موجودیت

A10 شامل عارضه‌های طبیعی منفرد (Spatial Scope: Point) است که به عنوان جاذبه گردشگری مطرح هستند. شش Classification: Waterfall · Cave · Spring · Canyon/Gorge · Rock Formation · Salt Formation/Kalut. هر Classification ویژگی‌های محتوایی متفاوتی دارد.

A10 می‌تواند داخل A9 (National Park) باشد — در این صورت علاوه بر R01 → G3، رابطه R26 → ManagedArea نیز دارد (CA-A-D005).

#### ویژگی‌های محتوایی per Classification

| Classification | محتوای خاص | نیاز P4 |
|---------------|-----------|---------|
| Waterfall | ارتفاع | پایین |
| Cave | عمق | **بالا — اجباری** |
| Spring | دمای آب | پایین |
| Canyon / Gorge | طول، عمق | متوسط |
| Rock Formation | ارتفاع، شکل | پایین |
| Salt Formation / Kalut | مساحت | متوسط |

#### Page Template: Natural Feature

```
[Hero Section]
  — تصویر نمادین عارضه
  — نام | Classification | Province

[Identity Block — Structured]
  — نام رسمی | نام محلی
  — Classification (از IA)
  — ابعاد (بسته به نوع: ارتفاع / طول / عمق)
  — Spatial Scope: Point
  — موقعیت در: G3 یا داخل A9/ManagedArea
  — National Monument status (D6 attribute)

[Designation Block — Conditional]
  — D6 National Monument (attribute)
  — D1 (R20 → D1 — نادر اما ممکن)

[Inside Managed Area — Section Conditional]
  — فقط اگر R26 → ManagedArea وجود دارد
  — لینک به صفحه A9 parent

[Overview — 400+ words]
  — شرح طبیعی | شکل‌گیری | ویژگی‌های خاص | اهمیت گردشگری

[Activities]
  — بسته به Classification: hiking | photography | spelunking | swimming

[Safety Information — Section Conditional]
  — **اجباری برای Cave و Gorge**
  — توصیه‌شده برای سایر classifications
  — لینک به P4

[Getting There]
  — از نزدیک‌ترین G5
  — مسیر دسترسی

[Best Season — R25]

[Schema Block]
```

#### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 یا G5 | R01 — C1 |
| classification | Enum (Waterfall / Cave / Spring / Canyon-Gorge / Rock Formation / Salt Formation-Kalut) | IA Classifications under A10 |
| geo_coordinates | GeoCoordinates (Point) | P-01 |
| overview_text | LongText (400+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| managed_area_ref | Relation → A9 | R26 — اگر داخل ManagedArea |
| national_monument | Boolean | D6 attribute |
| world_heritage | Boolean | D1 via R20 |
| height_m | Number | برای Waterfall و Rock Formation |
| depth_m | Number | برای Cave و Spring |
| length_km | Number | برای Canyon/Gorge |
| area_km2 | Number | برای Salt Formation/Kalut |
| water_temperature | Text | برای Spring |
| safety_notes | Text | Cave و Gorge — strongly recommended |
| featured_image_gallery | Image[] | — |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R01 → G3/G5 | صفحه استان/شهر | Breadcrumb |
| R26 → ManagedArea / A9 | صفحه پارک ملی parent | Conditional section |
| R20 → D1/D6 | designations | بخش designations |
| P4 | ایمنی | Conditional — اجباری برای Cave/Gorge |

#### Aggregation Pages از A10

| Aggregation Page | فیلتر |
|----------------|-------|
| "Waterfalls of Iran" | Classification = Waterfall |
| "Caves of Iran" | Classification = Cave |
| "Natural Features in [Province]" | G3 + R01 ← A10 |
| "Salt Formations / Kaluts of Iran" | Classification = Salt Formation-Kalut |

#### SEO Purpose
- Target: "Margoon Waterfall Iran", "Alisadr Cave Iran", "Lut Desert Kaluts"
- هر Classification یک topic cluster مستقل با search volume مجزا
- Long-tail: "how to visit [waterfall]", "[cave] Iran tour", "best time for [feature]"

#### AEO Purpose
- "What is the tallest waterfall in Iran?"
- "What caves can I visit in Iran?"
- "What is a Kalut?"
- "How was [Rock Formation] formed?"
- "Is [cave] safe to visit?"

#### Schema Readiness

```json
{
  "@type": "LandForm",
  "name": "[نام EN]",
  "alternateName": "[نام FA]",
  "description": "[overview excerpt]",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "[lat]",
    "longitude": "[lon]"
  },
  "containedInPlace": {
    "@type": "AdministrativeArea",
    "name": "[G3 name]"
  },
  "additionalProperty": {
    "@type": "PropertyValue",
    "name": "featureType",
    "value": "[classification]"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ name_en + name_fa + classification
- ✅ located_in → G3 (primary)
- ✅ geo_coordinates (Point)
- ✅ overview_text ≥ 400 کلمه
- ✅ best_season
- ✅ managed_area_ref اگر داخل A9 (R26)
- ✅ safety_notes اگر Cave یا Canyon
- ✅ Schema.org LandForm

---

### A12 — Archaeological Site | محوطه باستانی

#### هدف موجودیت

A12 با A2 تمایز اساسی دارد که IA صریحاً ذکر کرده: **"no standing structure required."** این یعنی:

| معیار | A2 | A12 |
|-------|-----|------|
| Standing Structure | **اجباری** | **اجباری نیست** |
| نمونه | قصر گلستان | تخت جمشید (+ standing)، تپه سیلک (بدون standing) |
| R11 | ✅ | ✅ |
| R12 | ✅ | ✅ |
| R13 | ✅ | ✅ |
| R20 | ✅ | ✅ |
| R14 (BURIED_IN target) | ✅ | ❌ |

A12 می‌تواند Spatial Scope = Point (یک نقطه کاوش) یا Area (یک محوطه گسترده) داشته باشد.

#### Page Template: Archaeological Site

```
[Hero Section]
  — تصویر سایت (aerial یا panoramic ترجیحی)
  — نام | Province | Historical Period

[Identity Block — Structured]
  — نام رسمی | نام تاریخی
  — دوره تاریخی ← R11 → C13
  — تمدن / فرهنگ مرتبط
  — وضعیت کاوش: Active / Completed / Ongoing
  — Spatial Scope: Point یا Area
  — حضور standing structure: Yes / Partial / No

[Designation Block — Structured]
  — D1 UNESCO (R20 → D1): سال + معیار + OUV
  — National Registration (attribute)

[Overview — 600+ words]
  — تاریخچه کشف | یافته‌های اصلی | اهمیت باستان‌شناختی
  — چرا این سایت مهم است؟

[Historical Context]
  — R11 → C13: دوره / تمدن
  — R12 → C12: سازنده (اگر مستند)
  — R13 → C12: شخصیت‌های مرتبط

[What to See — در سایت]
  — ویژگی‌های قابل بازدید

[Artifacts — Contextual]
  — اگر A7 ای آثار این سایت را نگهداری می‌کند
  — لینک به موزه مرتبط (content field)

[Visitor Information]
  — دسترسی | permit | guide | عکاسی
  — R25: best_season
  — لینک P4 (برای سایت‌های فعال)

[Getting There]
  — از نزدیک‌ترین G5

[Schema Block]
```

#### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G5 یا G3 | R01 — C1 |
| geo_coordinates | GeoCoordinates یا GeoShape | P-01 |
| historical_period | Relation → C13 | R11 |
| excavation_status | Enum (Active / Completed / Ongoing / Unexcavated) | — |
| has_standing_structure | Boolean | CA-A-D004 |
| overview_text | LongText (600+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_historical | Text | نام تاریخی |
| builder | Relation → C12 | R12 |
| associated_figures | Relation[] → C12 | R13 |
| world_heritage | Boolean | D1 via R20 |
| world_heritage_year | Number | R20 property |
| world_heritage_criteria | Text | R20 property: Outstanding Universal Value |
| national_registration | Boolean | attribute |
| related_museum | Text (نام A7 + URL) | Content Layer — نه Graph |
| visiting_permit | Boolean | — |
| photography_policy | Enum | — |
| featured_image_gallery | Image[] | — |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R01 → G5/G3 | صفحه شهر/استان | Breadcrumb |
| R11 → C13 | دوره تاریخی | بخش تاریخی |
| R12 → C12 | سازنده | بخش تاریخی |
| R13 → C12 | شخصیت‌های مرتبط | بخش تاریخی |
| R20 → D1 | UNESCO WHC | بخش designations |
| related_museum (content) | موزه نگهدارنده آثار | Contextual section |

#### Aggregation Pages از A12

| Aggregation Page | فیلتر |
|----------------|-------|
| "Archaeological Sites of Iran" | R01 ← A12 |
| "UNESCO World Heritage Archaeological Sites" | D1 + A12 |
| "Sites from [Historical Period]" | R11 → C13 |
| "Achaemenid Sites in Iran" | R11 → C13(Achaemenid) |

#### SEO Purpose
- Target: "Persepolis Iran", "Pasargadae Iran", "Chogha Zanbil", "Shahr-e Sukhteh"
- A12 + D1 = highest authority pages در category تاریخی
- Long-tail: "what civilization built [site]", "when was [site] excavated", "[site] UNESCO"

#### AEO Purpose
- "What civilization built [site]?"
- "Is [site] a UNESCO World Heritage Site?"
- "How old is [site]?"
- "What was found at [site]?"
- "Can I visit [site]?"
- "Which period does [site] belong to?"

#### Schema Readiness

```json
{
  "@type": "LandmarksOrHistoricalBuildings",
  "additionalType": "https://schema.org/ArchaeologicalSite",
  "name": "[نام EN]",
  "alternateName": "[نام FA]",
  "description": "[overview excerpt]",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "[lat]",
    "longitude": "[lon]"
  },
  "containedInPlace": {
    "@type": "City",
    "name": "[G5 name]"
  },
  "additionalProperty": {
    "@type": "PropertyValue",
    "name": "historicalPeriod",
    "value": "[C13 name]"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ name_en + name_fa
- ✅ located_in → G5 یا G3
- ✅ historical_period → C13
- ✅ excavation_status
- ✅ has_standing_structure (CA-A-D004)
- ✅ overview_text ≥ 600 کلمه
- ✅ world_heritage status
- ✅ Schema.org با additionalType ArchaeologicalSite

---

### A13 — Cultural Landscape | منظر فرهنگی

#### هدف موجودیت

A13 پیچیده‌ترین entity در Category A است — یک **Composite Entity** که می‌تواند شامل:
- چندین G3 (Multi-Province)
- چندین A2 (Historical Monuments)
- چندین A10 (Natural Features)

باشد.

**معماری کلیدی A13:**
- A13 → LOCATED_IN → G3 (primary) — C1 رعایت می‌شود
- A13 → CONTAINS → A2 entities (R02)
- A13 → CONTAINS → A10 entities (R02)
- A13 → DESIGNATED_BY → D1 (R20) — اگر applicable
- هر A2/A10 داخل A13 باید R01 → G5/G3 مستقل خودش را داشته باشد (C3)
- **C9 اجباری:** designation از A13 به A2/A10 داخلش ارث نمی‌رسد

**تمایز A13 از A2:**
- A2: یک بنای تاریخی منفرد
- A13: یک landscape که ممکن است چندین بنا، ویژگی طبیعی، و بافت فرهنگی را در بر بگیرد

#### Page Template: Cultural Landscape

```
[Hero Section]
  — تصویر panoramic (landscape scale — aerial ترجیحی)
  — نام | Spatial Extent | Province(s)

[Landscape Identity Block — Structured]
  — نام رسمی
  — محدوده جغرافیایی (GeoShape)
  — Province(s) مرتبط: provinces_spanning: Text[]
  — Spatial Scope: Area
  — نوع: Natural / Cultural / Mixed

[Designation Block — Structured]
  — D1 UNESCO (R20 → D1): سال + معیار + OUV
  — ⚠️ توجه: designation روی Landscape به عنوان کل است
  — National Registration (attribute)

[Overview — 700+ words]
  — تعریف landscape | اهمیت فرهنگی-طبیعی | تاریخچه شکل‌گیری
  — چه چیزی این landscape را یکپارچه می‌کند؟

[Components — بخش اصلی]
  — A2 entities داخل (R02: A13 CONTAINS A2)
    → هر A2 با نام + خلاصه + لینک مستقل
    → designation status مستقل هر A2 (C9)
  — A10 entities داخل (R02: A13 CONTAINS A10)
    → هر A10 با نام + خلاصه + لینک مستقل

[Cultural Significance]
  — R11 → C13 (اگر applicable)
  — ارتباط با C entities (از طریق G3 parent)

[Visitor Information]
  — نقاط ورود اصلی | مسیر بازدید پیشنهادی
  — R25: best_season
  — لینک P4

[Getting There]
  — نقطه شروع از G5 اصلی‌ترین province

[Schema Block]
```

#### Required Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 (primary) | R01 — C1 — CA-A-D003 |
| geo_coordinates | GeoShape (Area) | P-01 |
| landscape_type | Enum (Natural / Cultural / Mixed) | — |
| overview_text | LongText (700+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع | منبع IA |
|------|-----|---------|
| provinces_spanning | Text[] | CA-A-D003 — Content Layer Only |
| world_heritage | Boolean | D1 via R20 |
| world_heritage_year | Number | R20 property |
| world_heritage_criteria | Text | R20 property |
| component_monuments | Relation[] → A2 | R02: A13 CONTAINS A2 |
| component_features | Relation[] → A10 | R02: A13 CONTAINS A10 |
| historical_period | Relation → C13 | R11 (اگر applicable) |
| national_registration | Boolean | attribute |
| total_area_km2 | Number | — |

#### ⚠️ Finding CA-A-F002 — C9 در A13: Designation Non-Inheritance

**Finding:**
اگر A13 (مانند "Persian Gardens" یا "Shushtar Historical Hydraulic System") designation D1 داشته باشد و داخلش چندین A2 وجود داشته باشد، باید مشخص باشد که هر A2 چه designation status مستقلی دارد.

**Impact Analysis:**
C9 صریح است: "A designation on a container does not apply to entities inside it." اگر template A13 در بخش Components ادعا کند که A2 های داخلش "UNESCO هستند" صرفاً به دلیل membership در A13، این C9 را نقض می‌کند.

**Recommendation:**
در بخش Components، هر A2/A10 باید designation status مستقل خودش را نمایش دهد. یک A2 داخل A13 که خودش R20 → D1 ندارد، نمی‌تواند به عنوان "UNESCO site" معرفی شود. Editorial guideline: در صفحه A13، designation روی "Landscape به عنوان کل" است — نه روی اجزاء.

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R01 → G3 (primary) | صفحه استان اصلی | Breadcrumb |
| R02: A13 CONTAINS → A2 | صفحات A2 های داخل | بخش Components — اصلی |
| R02: A13 CONTAINS → A10 | صفحات A10 های داخل | بخش Components |
| R20 → D1 | صفحه UNESCO WHC | بخش designations |
| R11 → C13 | دوره تاریخی (اگر applicable) | بخش تاریخی |
| P4 | ایمنی | Recommended |

#### Aggregation Pages از A13

| Aggregation Page | فیلتر |
|----------------|-------|
| "Cultural Landscapes of Iran" | R01 ← A13 |
| "UNESCO World Heritage Landscapes" | D1 + A13 filter |

#### SEO Purpose
- Target: "Persian Gardens UNESCO Iran", "Shushtar Historical Hydraulic System"
- A13 = صفحات بزرگ با potential برای featured snippets و AI citations
- Long-tail: "what sites are in [landscape]", "[landscape] UNESCO criteria", "visiting [landscape]"

#### AEO Purpose
- "What is [Cultural Landscape]?"
- "What sites are included in [Landscape] UNESCO designation?"
- "How many gardens are in the Persian Gardens UNESCO site?"
- "What provinces does [landscape] cover?"

#### Schema Readiness

```json
{
  "@type": "LandmarksOrHistoricalBuildings",
  "additionalType": "CulturalLandscape",
  "name": "[نام EN]",
  "alternateName": "[نام FA]",
  "description": "[overview excerpt]",
  "geo": {
    "@type": "GeoShape",
    "polygon": "[boundary coordinates]"
  },
  "containedInPlace": {
    "@type": "AdministrativeArea",
    "name": "[G3 primary name]"
  },
  "hasPart": [
    { "@type": "LandmarksOrHistoricalBuildings", "name": "[A2 name]" },
    { "@type": "LandForm", "name": "[A10 name]" }
  ]
}
```

#### MVC — AEO-First Checklist

- ✅ name_en + name_fa + landscape_type
- ✅ located_in → G3 (primary)
- ✅ geo_coordinates (Area / GeoShape)
- ✅ overview_text ≥ 700 کلمه
- ✅ حداقل ۳ component با لینک (A2 یا A10)
- ✅ world_heritage status مشخص
- ✅ C9 compliance: هر component designation status مستقل دارد
- ✅ Schema.org با hasPart

---

## بخش ۴ — Decision Log — Category A

### CA-A-D001
**Decision ID:** CA-A-D001
**Subject:** Classification under A1 — قانون اعمال روی Concrete Entities
**Decision:** Classifications under A1 به concrete types اعمال می‌شوند: Persian Garden → A13/A2؛ Traditional Bazaar → A2؛ Coast/Beach → A10؛ Modern Landmark → A2؛ Tourism Target Village → G7.
**Rationale:** P-02 یک مکان فیزیکی = یک primary node. بدون این قانون Classification مبهم باقی می‌ماند و P-02 در خطر است.
**Impact:** Editorial Guideline اجباری. هر Classification assign قبل از تأیید باید با این قانون بررسی شود.
**Revisable:** Yes

### CA-A-D002
**Decision ID:** CA-A-D002
**Subject:** A7 housed_in A2 — مدل Content Layer
**Decision:** A7 → LOCATED_IN → G5 (گراف). رابطه با A2: `housed_in: Text` در Content Layer. نه Graph Relationship.
**Rationale:** C3 صریح است: LOCATED_IN → G only. A7 → LOCATED_IN → A2 ممنوع است. P-02 تضمین می‌کند هر دو node مستقل باشند.
**Impact:** گراف سالم. صفحه A7 لینک content به A2 دارد. صفحه A2 لینک به A7 های داخل خودش دارد.
**Revisable:** Yes
**IA Basis:** C3، R01، P-02، IA Note on A7

### CA-A-D003
**Decision ID:** CA-A-D003
**Subject:** A13 Multi-Province — قانون LOCATED_IN و provinces_spanning
**Decision:** A13 primary LOCATED_IN = G3 اصلی. `provinces_spanning: Text[]` در Content Layer — نه Graph Relationship. همان قانون CA-G-F003 از Category G.
**Rationale:** C1 از "primary" استفاده می‌کند نه "only." I1 ذخیره transitive relationships را ممنوع می‌کند. یکپارچگی با CA-G-F003.
**Impact:** گراف یکپارچه. صفحه A13 extent کامل را نمایش می‌دهد.
**Revisable:** Yes
**IA Basis:** C1، I1، CA-G-F003

### CA-A-D004
**Decision ID:** CA-A-D004
**Subject:** A2 vs A12 — معیار تمایز Editorial
**Decision:** معیار قاطع: وجود standing structure. A2 = standing structure موجود. A12 = ممکن است هیچ standing structure نداشته باشد. فیلد `has_standing_structure: Boolean` در A12 Required است.
**Rationale:** IA صریح است: "Distinct from A2: no standing structure required." بدون معیار کمّی، misclassification اتفاق می‌افتد.
**Impact:** Field اجباری در A12. Editorial checklist: اگر standing structure وجود داشت → A2 را بررسی کن.
**Revisable:** Yes

### CA-A-D005
**Decision ID:** CA-A-D005
**Subject:** A9 ManagedArea و R26 — مدل Dual Relationship
**Decision:** اگر A9 designation D3/D4 دارد → ManagedArea subtype فعال می‌شود. A10 و A9 های داخل می‌توانند R26 → ManagedArea داشته باشند علاوه بر R01 → G3.
**Rationale:** R26 صریحاً exception به C3 است. IA این را "Limited to managed areas only" تعریف کرده. این استثنای صریح IA است — نه تخطی.
**Impact:** A10 داخل ManagedArea دو مسیر graph-level دارد: R01 → G3 (primary geography) + R26 → ManagedArea (managed area membership). هر دو stored در گراف — طبق IA مجاز است.
**Revisable:** Yes
**IA Basis:** R26، D-016، C3 Exception Note

---

## بخش ۵ — Findings — Category A

### Finding CA-A-F001
**Finding ID:** CA-A-F001
**موضوع:** A7 در Sources R20 غایب است
**وضعیت:** Noted — سازگار با IA — نیاز به تغییر ندارد
**IA Reference:** R20 Sources: A2, A3, A9, A10, A12, A13. A7 غایب است.
**Impact Analysis:** A7 به عنوان institution designation UNESCO نمی‌گیرد. اگر A7 داخل A2 باشد، designation از طریق A2 accessible است (لینک content). C9 تأکید می‌کند که designation از A2 به A7 ارث نمی‌رسد.
**Resolution:** Template A7 هیچ designation field ندارد. IA این را با Future Scope note "Museum Institution vs Museum Building split (future phase)" تأیید کرده.

### Finding CA-A-F002
**Finding ID:** CA-A-F002
**موضوع:** A13 و C9 — Designation Non-Inheritance در Components
**وضعیت:** Noted — Editorial Rule تعریف شد
**IA Reference:** C9، I2، R20
**Impact Analysis:** A13 با designation D1 نباید designation را به A2/A10 داخلش ارث بدهد. هر component باید designation status مستقل خودش را نمایش دهد.
**Resolution:** در template A13 بخش Components، هر A2/A10 وضعیت designation مستقل خودش را نمایش می‌دهد. Editorial rule: صفحه A13 designation را "روی Landscape به عنوان کل" معرفی می‌کند.

---

## بخش ۶ — Cross-Category Consistency Review

### بررسی ۱ — Entity Inflation

| بررسی | نتیجه |
|--------|--------|
| آیا entity جدیدی در CA ایجاد شد؟ | ❌ خیر — ۸/۸ از IA |
| آیا `housed_in` برای A7 entity جدید است؟ | ❌ خیر — Content Layer field |
| آیا `provinces_spanning` برای A13 entity جدید است؟ | ❌ خیر — Text[] Content Layer (همان CA-G-F003) |
| آیا Classification ها entity جدید ایجاد کردند؟ | ❌ خیر — Enum values در fields موجود |
| آیا A1 Aggregation pages entity جدید هستند؟ | ❌ خیر — از ترکیب entities موجود |
| **نتیجه کل** | ✅ **PASS — No Entity Inflation** |

### بررسی ۲ — Relationship Inflation

| بررسی | نتیجه |
|--------|--------|
| آیا relationship جدیدی ایجاد شد؟ | ❌ خیر — فقط از R01–R27 |
| آیا `housed_in` graph relationship است؟ | ❌ خیر — Content Layer Text field |
| آیا A7→A2 در گراف stored می‌شود؟ | ❌ خیر — C3 رعایت شد |
| آیا A13→A2 R02 relationship جدید است؟ | ❌ خیر — R02 از IA (Sources: A9، A13، ManagedArea) |
| **نتیجه کل** | ✅ **PASS — No Relationship Inflation** |

### بررسی ۳ — Duplicate Modeling

| سناریو | بررسی | نتیجه |
|--------|--------|--------|
| A2 vs A12 | تمایز standing structure — CA-A-D004 | ✅ |
| A7 داخل A2 | A7 node مستقل + content link — CA-A-D002 | ✅ |
| A9 vs A10 | A9 = managed area، A10 = single feature | ✅ |
| A13 vs A2 | A13 composite، A2 single building | ✅ |
| A2 + D1 vs A13 + D1 | هر کدام designation مستقل از R20 — C9 | ✅ |
| A1 Aggregation vs G entity Aggregation | A1 aggregation = cross-type؛ G aggregation = geographic filter | ✅ |
| **نتیجه کل** | | ✅ **PASS — No Duplicate Modeling** |

### بررسی ۴ — Circular Dependencies

```
تمام A entities → LOCATED_IN → G (یکطرفه — C2 رعایت)
هیچ A → LOCATED_IN → A وجود ندارد (C3)

زنجیره‌های تأیید‌شده:
A2 → LOCATED_IN → G5 → LOCATED_IN → G4 → LOCATED_IN → G3 → G1
A3 → LOCATED_IN → G5 (همان زنجیره)
A7 → LOCATED_IN → G5 (C3 — نه A7→A2)
A9 → LOCATED_IN → G3
A10 → LOCATED_IN → G3 یا G5
A12 → LOCATED_IN → G5 یا G3
A13 → LOCATED_IN → G3 (primary)

R26: A10/A9 → ManagedArea (not LOCATED_IN — Exception به C3)
R02: A13 CONTAINS A2/A10 (Derived از R01 — یکطرفه)

هیچ circular path وجود ندارد.
```

**نتیجه: ✅ PASS — No Circular Dependencies**

### بررسی ۵ — Geography First Compliance (P-01)

| Entity | geo_coordinates | located_in | نتیجه |
|--------|----------------|-----------|-------|
| A1 | N/A (Abstract) | N/A (exempt) | ✅ |
| A2 | ✅ GeoCoordinates (Point) | ✅ → G5/G7 (Required) | ✅ |
| A3 | ✅ GeoCoordinates (Point) | ✅ → G5/G7 (Required) | ✅ |
| A7 | ✅ GeoCoordinates (Point) | ✅ → G5 (Required) | ✅ |
| A9 | ✅ GeoShape (Area) | ✅ → G3 (Required) | ✅ |
| A10 | ✅ GeoCoordinates (Point) | ✅ → G3/G5 (Required) | ✅ |
| A12 | ✅ GeoCoordinates یا GeoShape | ✅ → G5/G3 (Required) | ✅ |
| A13 | ✅ GeoShape (Area) | ✅ → G3 primary (Required) | ✅ |

**نتیجه: ✅ PASS — Geography First Compliant — 7/7 concrete entities**

### بررسی ۶ — One Physical Place = One Primary Node (P-02)

| سناریو | بررسی | نتیجه |
|--------|--------|--------|
| A7 داخل A2 | هر دو دارای node مستقل + LOCATED_IN مستقل → G5 | ✅ |
| A13 که A2 داخلش دارد | A13 و هر A2 node مستقل. R02 CONTAINS derived. | ✅ |
| A9 ManagedArea dual-presence | A9 یک node. ManagedArea در Category G نوع متفاوت. D-016. | ✅ |
| A12 با A2 overlap مکانی | دو entity مستقل با geo مستقل — P-02 محترم | ✅ |
| **نتیجه کل** | | ✅ **PASS** |

### بررسی ۷ — Category G ↔ Category A Consistency

| بررسی | نتیجه |
|--------|--------|
| A2 → LOCATED_IN → G5: G5 از CA-G مشخص است | ✅ |
| A9 → LOCATED_IN → G3: G3 از CA-G مشخص است | ✅ |
| A13 provinces_spanning rule: همان CA-G-F003 | ✅ |
| A10 داخل G11 (Desert): A10 می‌تواند → G3/G5 باشد. G11 host است. | ✅ |
| A10 داخل G9 (Mountain): همان منطق | ✅ |
| آیا A entities در G entity templates ذکر شده‌اند؟ | ✅ G3، G5 templates بخش Attractions دارند |
| آیا G5 template از A2، A3، A7، A10، A12، A13 استفاده می‌کند؟ | ✅ CA-G-v1.0-FINAL بخش Top Attractions |
| **نتیجه کل** | ✅ **PASS — G↔A Consistent** |

### بررسی ۸ — SEO Architecture Consistency

| بررسی | نتیجه |
|--------|--------|
| آیا A aggregation pages با G aggregation pages cannibalize می‌کنند؟ | — |
| "Attractions in Isfahan" (A1 aggregation) vs "Isfahan travel guide" (G5) | ✅ تمایز: A1 aggregation = attraction list؛ G5 = city hub |
| "Mosques of Iran" (A3 aggregation) vs "Iran Religious Tourism" (G1) | ✅ تمایز: classification-specific vs country-level |
| "UNESCO Sites in Iran" (D1 aggregation) vs "Iran Historical Sites" (A2 aggregation) | ✅ تمایز: designation-based vs type-based |
| آیا هر A entity دارای unique meta_title است؟ | ✅ Required field |
| **نتیجه کل** | ✅ **PASS — No SEO Cannibalization Risk** |

### بررسی ۹ — AEO Readiness

| Entity | Schema تعریف شد | روابط در MVC | Identity Block ≤150 کلمه | نتیجه |
|--------|----------------|-------------|--------------------------|-------|
| A1 | N/A (Abstract) | N/A | Aggregation pages | ✅ |
| A2 | ✅ LandmarksOrHistoricalBuildings | ✅ | ✅ ۶+ سؤال | ✅ |
| A3 | ✅ PlaceOfWorship | ✅ | ✅ ۵+ سؤال (dual audience) | ✅ |
| A7 | ✅ Museum | ✅ | ✅ ۴+ سؤال | ✅ |
| A9 | ✅ Park | ✅ | ✅ ۴+ سؤال | ✅ |
| A10 | ✅ LandForm | ✅ | ✅ ۳+ سؤال per classification | ✅ |
| A12 | ✅ LandmarksOrHistoricalBuildings + ArchaeologicalSite | ✅ | ✅ ۵+ سؤال | ✅ |
| A13 | ✅ CulturalLandscape + hasPart | ✅ | ✅ ۳+ سؤال | ✅ |

**نتیجه: ✅ PASS — AEO-Ready 7/7 concrete entities**

### Cross-Category Risks شناسایی‌شده

| ریسک | سطح | اقدام |
|------|-----|--------|
| A2 vs A12 misclassification | متوسط | CA-A-D004: has_standing_structure field اجباری |
| A13 designation inheritance (C9) | متوسط | Finding CA-A-F002: editorial rule تعریف شد |
| A7 housed_in A2 — link inconsistency | پایین | CA-A-D002: bidirectional content links اجباری |
| A1 Classification misuse | پایین | CA-A-D001: Editorial Guideline اجباری |
| A9 ManagedArea dual-node confusion | پایین | D-016 + CA-A-D005: در editorial documentation واضح |

---

## بخش ۷ — Entity Inventory Validation

| بررسی | نتیجه |
|--------|--------|
| Entity Count از IA: ۸ | ✅ ۸/۸ طراحی شد |
| A1 (Abstract): documented، بدون template | ✅ |
| A2: تمام Classifications، venue_eligible، R15 | ✅ |
| A3: R24، dual audience، تمام Classifications | ✅ |
| A7: housed_in model، R15، CA-A-D002 | ✅ |
| A9: ManagedArea، R26، D3/D4، CA-A-D005 | ✅ |
| A10: تمام ۶ Classifications، R26 | ✅ |
| A12: standing structure، CA-A-D004، تمایز A2 | ✅ |
| A13: Composite، Multi-G3، C9، CA-A-D003 | ✅ |
| Relationships R01,R02,R11,R12,R13,R14,R15,R20,R25,R26 | ✅ همه پوشش داده شد |
| Constraints C1,C3,C5,C6,C9 | ✅ همه رعایت شد |
| Principles P-01,P-02,P-07,I2,I3,I5 | ✅ همه رعایت شد |
| Decisions D-004,D-005,D-006,D-013,D-026,DC-R-004 | ✅ همه اعمال شد |
| Architecture Freeze | ✅ INTACT |
| New Entities Introduced | ❌ صفر |
| New Relationships Introduced | ❌ صفر |
| New Constraints Introduced | ❌ صفر |

---

## بخش ۸ — Category A Final Status

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CATEGORY A — ATTRACTIONS
CONTENT ARCHITECTURE FINAL STATUS
Phase 3 | ToPersia | IA-v1.2.1-FINAL Reference
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Entities Designed:             8 / 8      ✅
  A1  Tourist Attraction       Abstract — documented  ✅
  A2  Historical Monument      ✅
  A3  Religious Site           ✅
  A7  Museum                   ✅
  A9  National Park            ✅
  A10 Natural Feature          ✅
  A12 Archaeological Site      ✅
  A13 Cultural Landscape       ✅

Decisions Logged:              5          ✅
  CA-A-D001  Classification Application Rule
  CA-A-D002  A7 housed_in A2 Content Layer
  CA-A-D003  A13 Multi-Province Rule
  CA-A-D004  A2 vs A12 Standing Structure
  CA-A-D005  A9 ManagedArea Dual Relationship

Findings Documented:           2          ✅
  CA-A-F001  A7 excluded from R20
  CA-A-F002  A13 C9 Non-Inheritance Rule

Architecture Freeze:           INTACT     ✅
New Entities Introduced:       0          ✅
New Relationships Introduced:  0          ✅
New Constraints Introduced:    0          ✅

Cross-Category Reviews:
  Entity Inflation:            PASS       ✅
  Relationship Inflation:      PASS       ✅
  Duplicate Modeling:          PASS       ✅
  Circular Dependencies:       PASS       ✅
  Geography First (P-01):      PASS 7/7   ✅
  One Primary Node (P-02):     PASS       ✅
  G ↔ A Consistency:           PASS       ✅
  SEO Architecture:            PASS       ✅
  AEO Readiness:               PASS 7/7   ✅

Open Risks (all mitigated):
  A2 vs A12 misclassification: CA-A-D004 applied
  A13 C9 Non-Inheritance:      CA-A-F002 + editorial rule
  A7 housed_in links:          CA-A-D002 applied
  A1 Classification misuse:    CA-A-D001 applied

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STATUS: ✅ COMPLETE
Category A Content Architecture is finalized.
All 8 entities designed. A1 documented as abstract.
All decisions recorded. All findings documented.
All cross-category checks passed.
Architecture Freeze intact throughout.

Ready to proceed to Category D — Designations.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

*ToPersia CA-A-v1.2-FINAL*
*Phase 3: Content Architecture*
*مرجع: IA-v1.2.1-FINAL (Frozen)*
*مرجع CA: CA-G-v1.0-FINAL*
*وضعیت: COMPLETE*
