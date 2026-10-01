# ToPersia — Phase 3: Content Architecture
## Category G — Geography
### Version 1.2 | FINAL
#### مرجع: IA-v1.2.1-FINAL (Frozen) | Phase 3 Approved Decisions Applied

> **Changelog v1.0 → v1.1:** بدون تغییر محتوا. صرفاً هم‌ترازسازی شماره نسخه با بقیهٔ Category‌ها برای آپلود یکجا. با grep کامل روی الگوهای `Relation → P#` و بررسی P-PATCH-001 (`currency_note`) دوباره تأیید شد که این سند نیازی به اصلاح نداشت.
> **Changelog v1.1 → v1.2:** بدون تغییر محتوا — هم‌ترازسازی نسخهٔ مجموعه با CA-C-v1.2.

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| پروژه | ToPersia |
| فاز | Phase 3 — Content Architecture |
| Category | G — Geography |
| نسخه | 1.2-FINAL |
| وضعیت | APPROVED |
| مرجع IA | IA-v1.2.1-FINAL (Frozen) |
| تصمیمات اعمال‌شده | CA-G-F003 · CA-G-F004 · CA-G-D003 |
| موجودیت‌های پوشش‌داده‌شده | ۱۳ / ۱۳ |

---

## بخش ۱ — تصمیمات Phase 3 اعمال‌شده

### CA-G-F003 — Multi-Province Geographic Entities
**Decision:** `provinces_spanning` تأیید شد به عنوان `Text[]` در Content Layer.
**Layer:** Content Layer Only — NOT a Graph Relationship.
**Applied to:** G9، G10، G11، G12.
**IA Basis:** C1 از واژه "primary" استفاده می‌کند نه "only." I1 ذخیره transitive relationships را ممنوع می‌کند.

### CA-G-F004 — Events Section on G7
**Decision:** Events Section = Optional.
**Rendering Rule:** فقط زمانی render می‌شود که حداقل یک E entity با R15→G7 یا R16→G7 وجود داشته باشد.
**IA Basis:** R15 Targets شامل G7 است. R16 Targets شامل G7 است. IA الزام template را تعریف نکرده.

### CA-G-D003 — Primary LOCATED_IN for G5 (City)
**Decision:** سلسله‌مراتب اداری کامل حفظ می‌شود.
**Graph Model:** G5 → LOCATED_IN → G4 → LOCATED_IN → G3 → LOCATED_IN → G1
**IA Basis:** R01 Targets: G1–G8. G4 در محدوده مجاز است. Infrastructure Entity بودن G4 بر content priority تأثیر می‌گذارد، نه graph integrity.
**Revisable:** Yes

---

## بخش ۲ — Content Architecture — ۱۳ موجودیت Category G

---

### G1 — Country | کشور

**هدف موجودیت:**
ریشه Knowledge Graph. یک نمونه در کل سیستم. لنگر معماری، صفحه ورودی سطح بالا، و primary reference برای AI Engines.

**Page Template: Country Hub**

```
[Hero Section]
  — تصویر نمادین ایران
  — نام رسمی (FA / EN)
  — خلاصه یک‌پاراگرافی — AEO-ready

[Identity Block — Structured]
  — نام رسمی | پایتخت | جمعیت | مساحت
  — زبان رسمی | ارز | منطقه زمانی
  — موقعیت جغرافیایی

[Quick Facts Panel]
  — دین | حکومت | کد کشور | دامنه اینترنتی

[Overview — 800+ words]
  — تاریخ | جغرافیا | فرهنگ | گردشگری

[Geographic Breakdown]
  — لیست استان‌ها (G3) با لینک
  — نقشه تعاملی

[Category Highlights]
  — برترین جاذبه‌ها → A
  — میراث یونسکو → D1، D2
  — فرهنگ و هنر → C
  — رویدادها → E
  — اطلاعات سفر → P

[Practical Travel Entry]
  — ویزا (P1) | ارز (P2) | سلامت (P4) | آداب (P5)

[Best Time to Visit — R25]

[Schema Block]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| official_name_en | Text | — |
| official_name_fa | Text | — |
| capital_city | Relation → G5 | R02 |
| geographic_coordinates | GeoShape | P-01 |
| population | Number | — |
| area_km2 | Number | — |
| official_language | Text | — |
| currency_note | Text (اطلاعات ارز از P2 نمایش داده می‌شود) | Content Layer — **نه Graph Edge** |
| timezone | Text | — |
| overview_text | LongText (800+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع |
|------|-----|
| alternative_names | Text[] |
| neighboring_countries | Text[] (informational — خارج از گراف) |
| government_type | Text |
| internet_tld | Text |
| calling_code | Text |
| featured_image_gallery | Image[] |

**Internal Links (از روابط IA):**

| رابطه | هدف | نوع |
|--------|-----|-----|
| R02 (CONTAINS) | تمام G3ها | ناوبری اصلی |
| R22 ← (P1, P2, P4, P5, P6) | P entities | بخش اطلاعات سفر |

> **یادداشت معماری (P-PATCH-001):** G1 هیچ Outbound Graph Edge به P entities ندارد. اطلاعات ارز، ویزا، سلامت و آداب از طریق R22 ← (Inbound از P) در G1 نمایش داده می‌شوند. فیلد `currency_note` در Required Fields یک Content Layer field است — نه یک رابطه گراف. IA R22 Direction: P → G. G1 = Target، نه Source.
| R20 ← (روی A entities) | صفحات D1، D2 | بخش میراث |

**Aggregation Pages:** G1 خود بالاترین سطح است — aggregation page تولید نمی‌کند.

**SEO Purpose:**
Target: "Iran travel guide" | "visiting Iran" | "Iran tourism" | "Iran country overview"
نقش: Topical Authority anchor — همه صفحات سایت به G1 وصل هستند.

**AEO Purpose — سؤالات هدف:**
- "What is the capital of Iran?"
- "What language do they speak in Iran?"
- "What is the official name of Iran?"
- "What currency does Iran use?"
- "What religion is in Iran?"
پاسخ‌های AEO باید در اولین ۱۵۰ کلمه صفحه قابل استخراج باشند.

**Schema Readiness:**
```
@type: Country
name: "Iran"
alternateName: ["Persia", "Islamic Republic of Iran"]
capital: { @type: City, name: "Tehran" }
geo: { @type: GeoShape }
areaTotal: 1648195
officialLanguage: "Persian"
```

**MVC — AEO-First:**
- ✅ تمام Required Fields
- ✅ overview_text ≥ 800 کلمه
- ✅ لینک به تمام G3ها
- ✅ لینک به P1، P2، P4، P5
- ✅ Schema.org Country کامل
- ✅ پاسخ ۵ سؤال AEO در متن
- ✅ best_season
- ✅ hero_image

---

### G2 — Geographic Region | منطقه جغرافیایی

**هدف موجودیت:**
مناطق جغرافیایی مفهومی — غیر اداری. طبق D-011: Classification داخلی Natural/Historical/Cultural/Climate. رفتار KG متفاوت از G3.

**Page Template: Regional Concept Page**

```
[Hero Section]
  — نام منطقه
  — classification badge

[Region Definition Block]
  — تعریف و محدوده — تفاوت با تقسیمات اداری
  — نقشه محدوده

[Provinces in Region — R02]
  — لیست G3های داخل منطقه

[Key Characteristics]
  — ویژگی‌های Natural / Historical / Cultural / Climate

[Top Attractions — Contextual links to A]

[Cultural Highlights — Contextual links to C]
```

**⚠️ Editorial Rule (از Finding CA-G-F001):**
G2 باید به عنوان "Regional Concept" نوشته شود — نه Destination Guide. هدف: جلوگیری از Cannibalization با G3.

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| region_classification | Enum (Natural/Historical/Cultural/Climate) | D-011 |
| geographic_boundary | GeoShape | P-01 |
| provinces_included | Relation[] → G3 | R02 |
| overview_text | LongText (400+ words) | — |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع |
|------|-----|
| climate_overview | Text |
| historical_context | Text |
| best_season | Enum |

**Internal Links:**

| رابطه | هدف | نوع |
|--------|-----|-----|
| R01 → G1 | صفحه ایران | Breadcrumb |
| R02: CONTAINS → G3 | استان‌های داخل منطقه | بخش اصلی |
| R06 ← (C entities) | فرهنگ منطقه | بخش فرهنگ |

**Schema:**
```
@type: Place
additionalType: "GeographicRegion"
containedInPlace: Iran (G1)
containsPlace: [G3 list]
geo: GeoShape
```

**MVC:**
- ✅ region_classification
- ✅ overview_text ≥ 400 کلمه
- ✅ لیست G3های منطقه با لینک
- ✅ Schema.org Place

---

### G3 — Province | استان

**هدف موجودیت:**
پُرارجاع‌ترین موجودیت در کل سیستم (IA). تقریباً هر موجودیت از طریق R01 به G3 متصل است. Hub جغرافیایی و مقصد محتوایی مستقل.

**Page Template: Province Hub**

```
[Hero Section]
  — نام استان | مرکز | منطقه | جمعیت

[Province Identity Block]
  — کد استان | مرکز (G5) | مساحت | جمعیت
  — موقعیت روی نقشه
  — استان‌های همجوار (R04)

[Overview — 600+ words]

[Cities & Towns — R02 → G5]

[Key Attractions]
  — A2، A3، A7، A9، A10، A12، A13 از R01

[UNESCO & Designations]
  — D1، D2 روی موجودیت‌های داخل G3

[Culture & Heritage]
  — C1 (R09) | C2 (R09) | C6 (R06) | C7 (R06)

[Historical Figures — R13، R14]

[Events — R16]

[Tourism Routes — R03]

[Practical Info]
  — P7 Level 2 | R25

[Border Info — if applicable — G13]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| province_code | Text | — |
| capital_city | Relation → G5 | R02 |
| geographic_region | Relation → G2 | R01 |
| located_in | Relation → G1 | R01 — C1 |
| area_km2 | Number | — |
| population | Number | — |
| geo_coordinates | GeoShape | P-01 |
| overview_text | LongText (600+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع |
|------|-----|
| bordering_provinces | Relation[] → G3 (R04) |
| bordering_countries | Text[] (informational) |
| climate_type | Text |
| free_zone | Boolean |

**Internal Links:**

| رابطه | هدف | نوع |
|--------|-----|-----|
| R01 → G1 | صفحه ایران | Breadcrumb |
| R04: BORDERS ↔ G3 | استان‌های همجوار | Contextual |
| R02 → G5 | شهرهای استان | Hub section |
| R01 ← (A entities) | جاذبه‌ها | Content section |
| R06 ← (C entities) | فرهنگ | Content section |
| R16 ← (E entities) | رویدادها | Content section |
| R03 ← (P9) | مسیرهای گردشگری | Content section |
| R22 ← (P1–P6) | اطلاعات سفر | Practical section |

**Schema:**
```
@type: AdministrativeArea
containedInPlace: { @type: Country, name: "Iran" }
containsPlace: [G5 list]
geo: GeoShape
population: [value]
```

**MVC:**
- ✅ تمام Required Fields
- ✅ overview_text ≥ 600 کلمه
- ✅ لیست شهرهای اصلی با لینک (G5)
- ✅ حداقل ۳ جاذبه با لینک (A)
- ✅ حداقل ۱ آیتم فرهنگی با لینک (C)
- ✅ Schema.org AdministrativeArea
- ✅ R04 (استان‌های همجوار)
- ✅ best_season

---

### G4 — County | شهرستان

**هدف موجودیت:**
Infrastructure Entity (D-010). نقش اصلی: حفظ سلسله‌مراتب اداری در گراف. صفحه محتوایی برای اکثر نمونه‌ها وجود ندارد.

**Graph Role:**
G4 به عنوان intermediate node در زنجیره:
`G5 → LOCATED_IN → G4 → LOCATED_IN → G3`
عمل می‌کند (CA-G-D003).

**سه حالت موجودیتی G4:**

**حالت ۱ — Infrastructure Node (اکثریت):**
فقط در گراف. بدون صفحه محتوایی.

**حالت ۲ — Redirect:**
درخواست URL مربوط به G4 → redirect به G3.

**حالت ۳ — Exception Page (نادر — نیاز به تأیید Editorial):**
معیار: جاذبه A-level مستقل بدون G5 + حجم جستجوی مستقل قابل اثبات.

**Required Fields (برای گراف — همه حالات):**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 | R01 — C1 |
| capital_city | Relation → G5 | R02 |

**صفحه Exception (حالت ۳ — حداقلی):**
- نام و موقعیت
- استان مربوطه (لینک G3)
- شهرهای داخل (لینک G5)
- Schema.org AdministrativeArea minimal

**MVC Exception Page:**
- ✅ located_in → G3
- ✅ نام مرکز شهرستان
- ✅ Schema minimal

---

### G5 — City | شهر

**هدف موجودیت:**
بالاترین اولویت SEO و محتوا در کل پروژه (IA). اصلی‌ترین مقصد جستجوی گردشگران. غنی‌ترین template در Category G.

**Graph Chain (CA-G-D003):**
`G5 → LOCATED_IN → G4 → LOCATED_IN → G3 → LOCATED_IN → G1`

**Page Template: City Destination Hub**

```
[Hero Section]
  — نام شهر (FA / EN)
  — Province | Population | Elevation | tagline

[City Identity Block — Structured]
  — نام رسمی | استان (G3) | شهرستان (G4)
  — جمعیت | مساحت | ارتفاع
  — مختصات (Spatial Scope: Point)

[Overview — Why Visit — 800+ words]

[Top Attractions]
  — A2، A3، A7، A9، A10، A12، A13 از R01

[UNESCO & Designations]
  — D1 (از R20 روی A entities داخل G5)
  — D2 (از R20)
  — D5 Creative City (اگر attribute روی این G5 باشد)

[Culture & Heritage]
  — C2 (R09) | C1 (R09) | C6 (R06) | C7 (R06) | C10 (R06)

[Historical Figures]
  — C12 از R14 (متولد/متوفی در شهر)
  — C12 از R13 (مرتبط با شهر)

[Events & Festivals — R16]

[Neighborhoods & Districts — G6 — if applicable]

[Getting There & Around — P7 Level 2 attributes]

[Tourism Routes — P9 از R03 و R05]

[Best Time to Visit — R25]

[Practical Info]
  — P1 | P2 | P4 | P5 از R22
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G4 | R01 — C1 — CA-G-D003 |
| geo_coordinates | GeoCoordinates (Point) | P-01 |
| population | Number | — |
| area_km2 | Number | — |
| elevation_m | Number | — |
| overview_text | LongText (800+ words) | — |
| best_season | Enum | R25 |
| transport_access | Text | P7 Level 2 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| alternative_names | Text[] | — |
| founded_year | Text | — |
| historical_period | Relation → C13 | R11 |
| associated_figures | Relation[] → C12 | R13 |
| airport_name | Text | — |
| airport_iata | Text | — |
| railway_station | Boolean | — |
| unesco_creative_city | Boolean | D5 attribute |
| free_zone | Boolean | D attribute |
| featured_image_gallery | Image[] | — |

**Internal Links:**

| رابطه | هدف | نوع |
|--------|-----|-----|
| R01 → G4 → G3 → G1 | زنجیره سلسله‌مراتبی | Breadcrumb |
| R02 → A entities | جاذبه‌ها | اصلی‌ترین لینک‌ها |
| R02 → G6 | محله‌های مهم | Optional section |
| R14 ← C12 | شخصیت‌های متولد/متوفی | بخش تاریخی |
| R13 ← C12 | شخصیت‌های مرتبط | بخش تاریخی |
| R16 ← E | رویدادها | بخش رویدادها |
| R06 ← C | فرهنگ | بخش فرهنگ |
| R09 ← C1/C2 | محصولات محلی | بخش فرهنگ |
| R03/R05 ← P9 | مسیرهای گردشگری | بخش مسیرها |
| R22 ← P1–P6 | اطلاعات سفر | بخش عملی |

**Schema:**
```
@type: City
name: [نام شهر]
containedInPlace: { @type: AdministrativeArea (G4) }
geo: { @type: GeoCoordinates, latitude: ..., longitude: ... }
population: [value]
elevation: [value]
```

**MVC:**
- ✅ تمام Required Fields
- ✅ overview_text ≥ 800 کلمه
- ✅ located_in → G4 (CA-G-D003)
- ✅ ≥ 5 جاذبه با لینک
- ✅ transport_access
- ✅ غذای محلی با لینک (C2)
- ✅ best_season
- ✅ Schema.org City
- ✅ ≥ 1 رویداد یا جشنواره

---

### G6 — District / Neighborhood | محله / منطقه شهری

**هدف موجودیت:**
فقط زمانی صفحه مستقل می‌گیرد که هویت گردشگری مستقل داشته باشد (IA Note).

**Gate — سه معیار P-03:**
۱. حجم جستجوی مستقل قابل اثبات
۲. اطلاعات unique که در G5 نمی‌گنجد
۳. روابط متمایز با سایر موجودیت‌ها
هر سه باید تأیید شوند. در غیر این صورت → بخشی از صفحه G5.

**Page Template: Neighborhood Feature**

```
[Hero Section]
  — نام محله | City (G5) | Province

[Identity Block]
  — موقعیت در شهر | نوع محله

[Overview — 300+ words]
  — چرا این محله منحصربه‌فرد است؟

[Attractions in This Area — R01 → A entities]

[Cultural Character — C entities — contextual]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G5 | R01 — C1 |
| neighborhood_type | Enum | — |
| overview_text | LongText (300+ words) | — |
| geo_coordinates | GeoCoordinates | P-01 |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع |
|------|-----|
| historical_period | Relation → C13 |
| best_season | Enum (R25) |

**MVC:**
- ✅ located_in → G5
- ✅ overview_text ≥ 300 کلمه
- ✅ ≥ 2 جاذبه unique (نه تکرار G5)
- ✅ Schema.org Place

---

### G7 — Village | روستا

**هدف موجودیت:**
دو حالت: Tourism Target Village (classification D-009) یا روستای معمولی.

**قانون صفحه G7:**
- Tourism Target Village: صفحه کامل — اولویت بالا
- روستای معمولی با ≥ 1 جاذبه A-level: صفحه حداقلی
- روستای معمولی بدون جاذبه: فقط در گراف

**Page Template: Village Feature**

```
[Hero Section]
  — نام روستا | County | Province
  — Tourism Target Village badge (اگر applicable)

[Village Identity]
  — جمعیت | موقعیت | ارتفاع
  — فاصله از نزدیک‌ترین G5

[Why Visit — 250+ words]

[Attractions — A entities از R01]

[Cultural Character]
  — C entities از R06، R09

[Events — OPTIONAL — CA-G-F004]
  — فقط اگر ≥ 1 E entity با R15→G7 یا R16→G7 وجود داشته باشد

[Getting There — از نزدیک‌ترین G5]

[Best Time — R25]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 یا G4 | R01 — C1 |
| nearest_city | Relation → G5 | informational |
| geo_coordinates | GeoCoordinates | P-01 |
| tourism_target_village | Boolean | D-009 |
| overview_text | LongText (250+ words) | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع |
|------|-----|
| population | Number |
| elevation_m | Number |
| best_season | Enum (R25) |

**MVC — Tourism Target Village:**
- ✅ tourism_target_village: true
- ✅ nearest_city مشخص
- ✅ overview_text ≥ 250 کلمه
- ✅ ≥ 1 جاذبه با لینک
- ✅ Events section فقط اگر E entity وجود داشته باشد (CA-G-F004)
- ✅ Schema.org Place

---

### G8 — Island | جزیره

**هدف موجودیت:**
جزایر ایران (کیش، قشم، هرمز، ...). می‌تواند G5، G7، A9 را در خود داشته باشد (IA Note).

**Page Template: Island Destination Hub**

```
[Hero Section]
  — نام جزیره | دریا / خلیج

[Island Identity]
  — مساحت | جمعیت | موقعیت
  — نوع: خلیج فارس / دریای خزر / دریای عمان

[Overview — 500+ words]

[Cities & Villages on Island — R02 → G5، G7]

[Attractions — A entities از R02]

[Designations]
  — Free Zone attribute
  — D4 Biosphere (اگر applicable)

[Getting There]
  — کشتی / هواپیما / پل

[Best Season — R25]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 | R01 — C1 |
| island_type | Enum (Persian Gulf / Caspian / Oman Sea) | — |
| area_km2 | Number | — |
| geo_coordinates | GeoCoordinates | P-01 |
| overview_text | LongText (500+ words) | — |
| best_season | Enum | R25 |
| access_method | Text | — |
| free_zone | Boolean | D attribute |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Schema:**
```
@type: LandForm (Island subtype)
containedInPlace: { G3 }
containsPlace: [G5/G7 list]
geo: GeoCoordinates
```

**MVC:**
- ✅ overview_text ≥ 500 کلمه
- ✅ access_method
- ✅ ≥ 3 جاذبه با لینک
- ✅ Schema.org

---

### G9 — Mountain / Range | کوه / رشته‌کوه

**هدف موجودیت:**
کوه‌ها و رشته‌کوه‌های ایران (دماوند، البرز، زاگرس).

**CA-G-F003 Applied:**
رشته‌کوه‌های چند استانی → یک primary LOCATED_IN (G3 با بزرگ‌ترین سهم) + `provinces_spanning: Text[]` در Content Layer.

**Page Template: Mountain Feature**

```
[Hero Section]
  — نام | ارتفاع | استان

[Mountain Identity]
  — نوع | ارتفاع | رشته‌کوه parent (اگر applicable)
  — استان(های) مرتبط

[Overview — 400+ words]

[Trekking & Activities]
  — مسیرها | فصل مناسب | سطح دشواری

[Natural Features on Mountain — A9، A10 از R01/R02]

[Protected Status]
  — D3، D4، D6 (اگر applicable)

[Getting There — از نزدیک‌ترین G5]

[Best Season — R25]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 (primary) | R01 — C1 |
| mountain_type | Enum (Peak / Range / Volcano) | — |
| elevation_m | Number | — |
| geo_coordinates | GeoCoordinates | P-01 |
| overview_text | LongText (400+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | منبع |
|------|-----|------|
| provinces_spanning | **Text[]** | CA-G-F003 — Content Layer Only |
| range_parent | Relation → G9 | — |
| trekking_difficulty | Enum | — |
| volcanic | Boolean | — |
| national_monument | Boolean | D6 attribute |

**MVC:**
- ✅ located_in → G3 (primary)
- ✅ overview_text ≥ 400 کلمه
- ✅ elevation_m
- ✅ best_season
- ✅ Schema.org Place

---

### G10 — Water Body | پهنه آبی

**هدف موجودیت:**
دریا، دریاچه، رودخانه، تالاب.

**CA-G-F003 Applied:** همان قانون G9 برای پهنه‌های آبی چند استانی.

**Page Template: Water Body Feature**

```
[Hero Section]
  — نام | نوع | استان

[Identity Block]
  — نوع | مساحت یا طول | عمق
  — استان(های) مرتبط

[Overview — 400+ words]

[Tourism Activities]
  — شنا | قایقرانی | ماهیگیری | bird-watching

[Ecological Importance]
  — D3، D4 | D6

[Getting There]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 (primary) | R01 — C1 |
| water_body_type | Enum (Sea/Lake/River/Wetland) | IA Note G10 |
| geo_coordinates | GeoCoordinates یا GeoShape | P-01 |
| overview_text | LongText (400+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | منبع |
|------|-----|------|
| provinces_spanning | **Text[]** | CA-G-F003 — Content Layer Only |
| area_km2 | Number | — |
| length_km | Number | — |
| max_depth_m | Number | — |
| salinity_type | Enum | — |
| biosphere_reserve | Boolean | D4 |

**Schema:**
```
@type: LakeBodyOfWater / RiverBodyOfWater / SeaBodyOfWater
containedInPlace: { G3 }
```

**MVC:**
- ✅ water_body_type
- ✅ located_in → G3
- ✅ overview_text ≥ 400 کلمه
- ✅ Schema.org

---

### G11 — Desert | کویر / بیابان

**هدف موجودیت:**
کویرها و بیابان‌های ایران — جاذبه گردشگری بسیار بالا. دشت لوت = D1.

**CA-G-F003 Applied:** کویرهای چند استانی → primary + `provinces_spanning: Text[]`.

**Page Template: Desert Feature**

```
[Hero Section]
  — نام | مساحت | استان

[Desert Identity]
  — نوع: Salt / Sandy / Mixed
  — مساحت | استان(های) مرتبط

[Overview — 500+ words]

[Natural Features — A10 از R02]
  — کلوت | صخره | چشمه

[Designations]
  — D1 (مانند دشت لوت) | D4 Biosphere

[Tourism Activities]
  — کمپینگ | ستاره‌شناسی | کاروان‌سرا

[Getting There & Safety — P4 link]

[Best Season — R25 — معمولاً پاییز/زمستان]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 (primary) | R01 — C1 |
| desert_type | Enum (Salt/Sandy/Mixed) | — |
| area_km2 | Number | — |
| geo_coordinates | GeoShape | P-01 |
| overview_text | LongText (500+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | منبع |
|------|-----|------|
| provinces_spanning | **Text[]** | CA-G-F003 — Content Layer Only |
| world_heritage | Boolean | D1 |
| biosphere_reserve | Boolean | D4 |
| national_monument | Boolean | D6 |
| max_temperature_c | Number | — |

**MVC:**
- ✅ desert_type
- ✅ located_in → G3
- ✅ overview_text ≥ 500 کلمه
- ✅ ≥ 2 Natural Feature با لینک
- ✅ لینک P4 (ایمنی)
- ✅ Schema.org Place

---

### G12 — Forest / Plain | جنگل / دشت

**هدف موجودیت:**
جنگل‌های هیرکانی (D1)، ارسباران، و دشت‌های مهم ایران.

**CA-G-F003 Applied:** جنگل‌های چند استانی → primary + `provinces_spanning: Text[]`.

**Page Template: Forest / Plain Feature**

```
[Hero Section]
  — نام | نوع | استان

[Identity Block]
  — نوع: Forest / Plain
  — مساحت | استان(های) مرتبط

[Overview — 400+ words]
  — اهمیت اکولوژیک و گردشگری

[Biodiversity]
  — گونه‌های شاخص

[Designations]
  — D1 (جنگل هیرکانی) | D3 Geopark | D4 Biosphere

[Activities]
  — کمپینگ | طبیعت‌گردی | حیات‌وحش

[Best Season — R25]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 (primary) | R01 — C1 |
| area_type | Enum (Forest/Plain) | — |
| area_km2 | Number | — |
| geo_coordinates | GeoShape | P-01 |
| overview_text | LongText (400+ words) | — |
| best_season | Enum | R25 |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | منبع |
|------|-----|------|
| provinces_spanning | **Text[]** | CA-G-F003 — Content Layer Only |
| forest_type | Enum (Hyrcanian / Zagros / ...) | — |
| world_heritage | Boolean | D1 |
| biosphere_reserve | Boolean | D4 |
| national_monument | Boolean | D6 |

**MVC:**
- ✅ area_type
- ✅ located_in → G3
- ✅ overview_text ≥ 400 کلمه
- ✅ designation status اگر applicable
- ✅ Schema.org Place

---

### G13 — Border Crossing | گذرگاه مرزی

**هدف موجودیت:**
Informational only (IA Note). پاسخ به سؤالات عملی گردشگران درباره ورود/خروج زمینی. هیچ محتوای تجاری ندارد (CA-G-005).

**Page Template: Border Crossing Info**

```
[Header]
  — نام گذرگاه | کشور مقابل | استان | وضعیت

[Identity Block]
  — نوع: زمینی / ریلی
  — ساعات کاری | وضعیت (Active/Limited/Closed)

[Entry Requirements]
  — ملیت‌های مجاز
  — اسناد مورد نیاز
  — لینک اجباری به P1

[Practical Info]
  — حمل‌ونقل تا مرز
  — ارز → P2 | سلامت → P4

[Getting There — از نزدیک‌ترین G5]
```

**Required Fields:**

| فیلد | نوع | منبع IA |
|------|-----|---------|
| name_en | Text | — |
| name_fa | Text | — |
| located_in | Relation → G3 | R01 — C1 |
| crossing_type | Enum (Road/Rail) | — |
| border_country | Text | — |
| status | Enum (Active/Limited/Closed) | — |
| operating_hours | Text | — |
| geo_coordinates | GeoCoordinates | P-01 |
| nearest_city | Relation → G5 | informational |
| overview_text | LongText (200+ words) | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Internal Links:**

| رابطه | هدف | نوع |
|--------|-----|-----|
| R01 → G3 | استان | Breadcrumb |
| R05 ← P9 | مسیرهایی که از این مرز می‌گذرند | Contextual |
| R22 ← P1 | اطلاعات ویزا | اجباری |
| R22 ← P2 | ارز | اجباری |

**MVC:**
- ✅ وضعیت (status) مشخص
- ✅ ساعات کاری
- ✅ لینک اجباری به P1
- ✅ overview_text ≥ 200 کلمه
- ✅ Schema.org Place

---

## بخش ۳ — Decision Log Updates — Phase 3

### CA-G-D001
**موضوع:** Editorial boundary بین G2 و G3
**تصمیم:** G2 به عنوان Regional Concept نوشته می‌شود — نه Destination Guide. جلوگیری از Keyword Cannibalization.
**Rationale:** G2 و G3 هر دو صفحه می‌گیرند اما برای query های مختلف. G2 = "شمال ایران چه ویژگی‌هایی دارد" vs G3 = "سفر به مازندران."
**Impact:** Editorial guideline الزامی است برای نویسندگان.
**Revisable:** Yes

### CA-G-D002
**موضوع:** G4 Exception Page — معیارها
**تصمیم:** G4 صفحه استثنا فقط با سه معیار: ۱) جاذبه A-level مستقل بدون G5، ۲) حجم جستجوی مستقل، ۳) تأیید Editorial.
**Rationale:** جلوگیری از thin content و dilution سایت.
**Impact:** بدون معیار دقیق، صدها صفحه G4 بی‌ارزش ایجاد می‌شود.
**Revisable:** Yes

### CA-G-D003 ★ NEW
**Decision ID:** CA-G-D003
**موضوع:** Primary LOCATED_IN for G5 — Full Administrative Hierarchy
**تصمیم:** G5 → LOCATED_IN → G4. زنجیره کامل: G5 → G4 → G3 → G1.
**Rationale:**
- سازگار با سلسله‌مراتب اداری رسمی ایران
- R01 Targets: G1–G8 — G4 در محدوده مجاز است
- Infrastructure Entity بودن G4 (D-010) بر content priority تأثیر می‌گذارد، نه graph integrity
- هیچ استثنایی در IA که G5 → G3 مستقیم را مجاز کند وجود ندارد
- I1 (LOCATED_IN Transitivity) تضمین می‌کند که G5 از طریق G4 به G3 و G1 متصل است
**Impact:**
- گراف سلسله‌مراتبی کامل
- G4 nodes در گراف active هستند (فقط ساختاری — بدون صفحه)
- Query های transitivity از I1 استفاده می‌کنند
**Revisable:** Yes
**IA Basis:** R01, C1, D-010, I1

### CA-G-D004
**موضوع:** provinces_spanning — نوع فیلد
**تصمیم:** `provinces_spanning: Text[]` — Content Layer Only. NOT a Graph Relationship.
**Rationale:** C1 از "primary" استفاده می‌کند. Relation[] → G3 یعنی multiple stored relationships — نقض روح C1 و P-06.
**Impact:** گراف یکپارچه می‌ماند. صفحه محتوا اطلاعات informational نمایش می‌دهد.
**Revisable:** Yes
**Applied to:** G9، G10، G11، G12

### CA-G-D005
**موضوع:** Events Section در G7 — Optional
**تصمیم:** Events Section در template G7 Optional است. فقط زمانی render می‌شود که ≥ 1 E entity با R15→G7 یا R16→G7 وجود داشته باشد.
**Rationale:** IA G7 را در Targets R15 و R16 قرار داده. اما الزام template تعریف نکرده. Render شرطی از thin content جلوگیری می‌کند.
**Impact:** صفحات G7 بدون رویداد → بدون بخش خالی. صفحات G7 با رویداد → Events section نمایش داده می‌شود.
**Revisable:** Yes
**IA Basis:** R15 Targets include G7; R16 Targets include G7

---

## بخش ۴ — Cross-Category Consistency Review

### بررسی ۱ — Entity Inflation

| Category | بررسی | نتیجه |
|---------|--------|--------|
| G | آیا entity جدیدی در CA ایجاد شد؟ | ❌ خیر — ۱۳/۱۳ از IA |
| G | آیا G4 Exception Page entity جدید ایجاد می‌کند؟ | ❌ خیر — همان G4 entity با حالت محتوایی متفاوت |
| G | آیا provinces_spanning entity جدید است؟ | ❌ خیر — Content field، نه entity |
| G vs A | آیا Classification های A1 (مانند Persian Garden) با G entities تداخل دارند؟ | ✅ ندارند — Persian Garden به عنوان Classification روی A entities است، نه G |
| G vs D | آیا D5 (Creative City) با G5 تداخل entity دارد؟ | ✅ ندارد — D5 attribute روی G5 است (D-017) |
| G vs C | آیا G entities با C entities boundary درستی دارند؟ | ✅ دارند — مکان فیزیکی = G، پدیده فرهنگی = C |
| G vs E | آیا event venues با G entities تداخل دارند؟ | ✅ ندارند — E entities به G از طریق R15/R16 متصل‌اند |
| **نتیجه کل** | | ✅ **PASS — No Entity Inflation** |

### بررسی ۲ — Relationship Inflation

| بررسی | نتیجه |
|--------|--------|
| آیا relationship جدیدی در CA ایجاد شد؟ | ❌ خیر — فقط از R01–R27 |
| آیا provinces_spanning relationship جدید است؟ | ❌ خیر — Text[] field، نه graph relationship |
| آیا county_reference (G5→G4) relationship تکراری ایجاد می‌کند؟ | ❌ خیر — یک R01 primary |
| آیا bordering_provinces از R04 درست استفاده می‌کند؟ | ✅ بله — R04 Sources: G1,G3 / Targets: G1,G3 |
| **نتیجه کل** | ✅ **PASS — No Relationship Inflation** |

### بررسی ۳ — Duplicate Modeling

| سناریو | بررسی | نتیجه |
|--------|--------|--------|
| G2 و G3 — هر دو منطقه را cover می‌کنند | Editorial boundary تعریف شد (CA-G-D001) | ✅ |
| G5 و G6 — شهر و محله | G6 فقط با ۳ معیار P-03 صفحه می‌گیرد | ✅ |
| G7 Tourism Target Village و G7 معمولی | یک entity، دو حالت محتوایی | ✅ |
| D5 (Creative City) و G5 | D5 = attribute روی G5، نه صفحه جداگانه | ✅ |
| G13 (Border Crossing) و P1 (Visa Info) | G13 = مکان خاص، P1 = اطلاعات کشوری — لینک، نه تکرار | ✅ |
| **نتیجه کل** | | ✅ **PASS — No Duplicate Modeling** |

### بررسی ۴ — Circular Dependencies

بررسی می‌کنیم که هیچ LOCATED_IN دایره‌ای در CA تعریف نشده باشد (C2):

```
زنجیره تأیید‌شده:
G5 → LOCATED_IN → G4 → LOCATED_IN → G3 → LOCATED_IN → G1 (ROOT)

G6 → LOCATED_IN → G5 (City)
G7 → LOCATED_IN → G3 یا G4
G8 → LOCATED_IN → G3
G9–G12 → LOCATED_IN → G3 (primary)
G13 → LOCATED_IN → G3
G2 → LOCATED_IN → G1

هیچ circular path وجود ندارد.
```

**نتیجه: ✅ PASS — No Circular Dependencies**

### بررسی ۵ — Geography First Compliance (P-01)

| Entity | دارای geo_coordinates | دارای LOCATED_IN | نتیجه |
|--------|----------------------|-----------------|-------|
| G1 | ✅ GeoShape | ❌ (Root — مجاز) | ✅ |
| G2 | ✅ GeoShape | ✅ → G1 | ✅ |
| G3 | ✅ GeoShape | ✅ → G1 | ✅ |
| G4 | ✅ (در graph node) | ✅ → G3 | ✅ |
| G5 | ✅ GeoCoordinates (Point) | ✅ → G4 | ✅ |
| G6 | ✅ GeoCoordinates | ✅ → G5 | ✅ |
| G7 | ✅ GeoCoordinates | ✅ → G3/G4 | ✅ |
| G8 | ✅ GeoCoordinates | ✅ → G3 | ✅ |
| G9 | ✅ GeoCoordinates | ✅ → G3 (primary) | ✅ |
| G10 | ✅ GeoCoordinates/Shape | ✅ → G3 (primary) | ✅ |
| G11 | ✅ GeoShape | ✅ → G3 (primary) | ✅ |
| G12 | ✅ GeoShape | ✅ → G3 (primary) | ✅ |
| G13 | ✅ GeoCoordinates | ✅ → G3 | ✅ |

**نتیجه: ✅ PASS — Geography First Compliant — 13/13**

### بررسی ۶ — One Physical Place = One Primary Node (P-02)

| بررسی | نتیجه |
|--------|--------|
| آیا هیچ G entity دو LOCATED_IN stored در گراف دارد؟ | ❌ خیر — C1 رعایت شده |
| آیا provinces_spanning به عنوان second LOCATED_IN عمل می‌کند؟ | ❌ خیر — Text[] در Content Layer |
| آیا G4 → G5 یک node تکراری ایجاد می‌کند؟ | ❌ خیر — هر دو node مستقل با LOCATED_IN یکطرفه |
| **نتیجه کل** | ✅ **PASS** |

### بررسی ۷ — AEO Readiness

| Entity | Schema تعریف شد | روابط در MVC | سؤالات AEO پوشش داده شد | نتیجه |
|--------|----------------|-------------|------------------------|-------|
| G1 | ✅ Country | ✅ | ✅ ۵ سؤال | ✅ |
| G2 | ✅ Place+GeographicRegion | ✅ | ✅ | ✅ |
| G3 | ✅ AdministrativeArea | ✅ | ✅ | ✅ |
| G4 | ✅ minimal | ✅ | محدود (Infrastructure) | ✅ |
| G5 | ✅ City | ✅ | ✅ ۶+ سؤال | ✅ |
| G6 | ✅ Place | ✅ | ✅ | ✅ |
| G7 | ✅ Place | ✅ | ✅ | ✅ |
| G8 | ✅ LandForm/Island | ✅ | ✅ | ✅ |
| G9 | ✅ Place | ✅ | ✅ | ✅ |
| G10 | ✅ BodyOfWater subtype | ✅ | ✅ | ✅ |
| G11 | ✅ Place | ✅ | ✅ | ✅ |
| G12 | ✅ Place | ✅ | ✅ | ✅ |
| G13 | ✅ Place | ✅ | ✅ | ✅ |

**نتیجه: ✅ PASS — AEO-Ready — 13/13**

### Cross-Category Risks شناسایی‌شده

| ریسک | سطح | توضیح |
|------|-----|--------|
| G2 vs G3 Cannibalization | متوسط | در صورت عدم رعایت Editorial Rule CA-G-D001 |
| G4 Exception Page overuse | پایین | معیارهای CA-G-D002 باید سخت‌گیرانه اعمال شوند |
| G7 vs G6 overlap | پایین | G7 روستا است، G6 محله شهری — IA تفکیک واضح دارد |
| G13 محتوای تاریخی‌شده | متوسط | وضعیت گذرگاه‌ها تغییر می‌کند — نیاز به review policy |

---

## بخش ۵ — Category G Final Status

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CATEGORY G — GEOGRAPHY
CONTENT ARCHITECTURE FINAL STATUS
Phase 3 | ToPersia | IA-v1.2.1-FINAL Reference
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Entities Designed:           13 / 13     ✅
Phase 3 Decisions Applied:   3 (F003, F004, D003)  ✅
Decision Log Entries:        5 (D001–D005)  ✅
Architecture Freeze:         INTACT      ✅
New Entities Introduced:     0           ✅
New Relationships Introduced: 0          ✅
New Constraints Introduced:  0           ✅

Cross-Category Reviews:
  Entity Inflation:          PASS        ✅
  Relationship Inflation:    PASS        ✅
  Duplicate Modeling:        PASS        ✅
  Circular Dependencies:     PASS        ✅
  Geography First (P-01):    PASS 13/13  ✅
  One Primary Node (P-02):   PASS        ✅
  AEO Readiness:             PASS 13/13  ✅

Open Risks:
  G2 vs G3 Cannibalization:  Mitigated via CA-G-D001
  G4 Exception overuse:      Mitigated via CA-G-D002
  G13 content staleness:     Noted — review policy needed

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STATUS: ✅ COMPLETE
Category G Content Architecture is finalized.
All entities designed. All decisions recorded.
All cross-category checks passed.
Architecture Freeze intact throughout.

Ready to proceed to Category A — Attractions.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

*ToPersia CA-G-v1.2-FINAL*
*Phase 3: Content Architecture*
*مرجع: IA-v1.2.1-FINAL (Frozen)*
*وضعیت: COMPLETE*
