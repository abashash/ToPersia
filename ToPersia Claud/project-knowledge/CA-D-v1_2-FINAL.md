# ToPersia — Phase 3: Content Architecture
## Category D — Designations & Official Titles
### Version 1.2 | FINAL
#### مرجع: IA-v1.2.1-FINAL (Frozen) | Phase 3 Approved Decisions Applied

> **Changelog v1.0 → v1.1:** بدون تغییر محتوا. صرفاً هم‌ترازسازی شماره نسخه. با grep کامل تأیید شد صفر Relationship Inflation. سه Open Item از پیش (CA-D-F001، CA-D-F003، CA-D-F005) بدون تغییر باقی می‌مانند — نیازمند تصمیم مالک/IA Revision، نه ویرایش این سند. جزئیات: PHASE3-VALIDATION-LOG-v1_1.md.
> **Changelog v1.1 → v1.2:** بدون تغییر محتوا — هم‌ترازسازی نسخهٔ مجموعه با CA-C-v1.2.

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| پروژه | ToPersia |
| فاز | Phase 3 — Content Architecture |
| Category | D — Designations & Official Titles |
| نسخه | 1.2-FINAL |
| وضعیت | APPROVED |
| مرجع IA | IA-v1.2.1-FINAL (Frozen) |
| مرجع CA | CA-G-v1.0-FINAL · CA-A-v1.0-FINAL |
| تصمیمات اعمال‌شده | CA-D-D001 تا CA-D-D006 |
| موجودیت‌های پوشش‌داده‌شده | ۴ Programme Node + ۲ Attribute (طبق IA) |
| Architecture Freeze | INTACT |

---

## بخش ۱ — استخراج رسمی از IA-v1.2.1 پیش از طراحی

### ۱.۱ موجودیت‌های Category D — تأیید از IA

| کد | نام EN | نام FA | نوع | یادداشت IA |
|----|--------|--------|-----|-----------|
| D1 | UNESCO World Heritage | میراث جهانی یونسکو | Programme Node | — |
| D2 | UNESCO Intangible Heritage | میراث ناملموس یونسکو | Programme Node | — |
| D3 | UNESCO Global Geopark Programme | ژئوپارک جهانی | Programme Node | instances in Category G |
| D4 | UNESCO Biosphere Reserve Programme | ذخیره‌گاه زیست‌کره | Programme Node | instances in Category G |
| D5 | UNESCO Creative City | شهر خلاق یونسکو | Attribute | on G5 |
| D6 | National Natural Monument | اثر طبیعی ملی | Attribute | on natural entities |
| — | National Registration | ثبت ملی | Attribute | on eligible entities |
| — | Free Zone | منطقه آزاد | Attribute | on G5/G8 |

**تعداد از IA:** ۴ Programme Node (Named Individuals) + ۴ Attribute (شامل دو مورد بدون کد رسمی)

**Content model برای D (مستقیماً از IA):**
> "D1/D2/D3/D4/D6: One aggregation page each (Faceted + unique editorial content)
> D5: Section within G5 page + one aggregation page
> All designation data lives on target entity pages"

این عبارت مستقیم IA، **پایه تعیین‌کننده** برای کل بخش ۳ این سند است.

### ۱.۲ روابط مرتبط با Category D — تأیید از IA

| کد | رابطه | جهت | Sources | Targets | یادداشت |
|----|--------|-----|---------|---------|---------|
| R20 | DESIGNATED_BY *(Qualified)* | Entity → D | A2, A3, A9, A10, A12, A13, C1 instances, C10 instances, ManagedArea | **D1, D2, D3 Programme, D4 Programme** | Properties: designation_type, year, criteria, status, review_date, awarding_body. C6. |
| R21 | HAS_DESIGNATED | D → Entity | D1, D2, D3 Programme, D4 Programme | همه R20-eligible entities | Derived — معکوس R20. R20 مرجع است. |
| R26 | LOCATED_IN_MANAGED_AREA | A → ManagedArea | A10, A9 | ManagedArea (D3/D4 instances در Category G) | Exception به C3 |
| R27 | ALSO_RECOGNIZED_IN | C10 → External URI | C10 instances با Spatial Scope=Transnational | External URIs | مرتبط با C8 — نه مستقیماً D |

**نکته حیاتی معماری:** R20 Targets دقیقاً چهار مورد است: D1, D2, D3 Programme, D4 Programme. **D5 و D6 در Targets R20 نیستند.** این یعنی هیچ entity ای نمی‌تواند مستقیماً `DESIGNATED_BY → D5` یا `DESIGNATED_BY → D6` داشته باشد. D5 و D6 به‌جای رابطه، به‌صورت attribute مستقیم روی entity هدف ذخیره می‌شوند.

### ۱.۳ Constraints مستقیماً مرتبط با Category D

| Constraint | متن IA | اثر روی D |
|-----------|--------|----------|
| C4 | No Event Designation | Category E هرگز نمی‌تواند Target R20 باشد |
| C6 | No Designation Event Node | هیچ node جدیدی برای "رویداد designation" — همه چیز در properties رابطه R20 |
| C9 | DESIGNATED_BY Non-Inheritance | Designation یک container به اجزای داخلش ارث نمی‌رسد |

### ۱.۴ Principles مستقیماً مرتبط با Category D

| اصل | متن IA | اثر روی D |
|-----|--------|----------|
| P-02 | One Physical Place = One Primary Node | Designations به‌صورت relationship/attribute بیان می‌شوند، نه node اضافه |
| P-08 | Designation as Qualified Relationship | Designations رابطه‌ای با metadata روی خودِ رابطه‌اند — هرگز node جدید |
| P-10 | No Designation Event Node | هر عمل designation یک relationship است، بدون استثنا |

### ۱.۵ Inheritance Rules مرتبط

| Rule | متن IA | اثر روی D |
|------|--------|----------|
| I2 | DESIGNATED_BY Non-Inheritance | = C9 |
| I4 | HAS_DESIGNATED as Derived Inverse | R21 از R20 مشتق است — R20 مرجع |

### ۱.۶ Decisions IA مستقیماً مرتبط با Category D

| کد | تصمیم | Rationale | Revisable |
|----|--------|-----------|-----------|
| D-002 | A16 (UNESCO به‌عنوان entity) حذف شد | همپوشانی با D1 | Yes |
| D-003 | مدل Hybrid برای Designation | بر اساس غنای اطلاعاتی | Yes |
| D-014 | D1 → فقط یک Aggregation URL | جلوگیری از Cannibalization | Yes |
| D-015 | One Primary Node — Designations = relationships | Single Source of Truth | **No** |
| D-016 | نمونه‌های جغرافیایی D3/D4 → Category G به‌عنوان ManagedArea | هویت جغرافیایی اصلی | Yes |
| D-017 | D5 → Attribute روی G5 | اطلاعات فراتر از context G5 وجود ندارد | Yes |
| D-019 | DESIGNATED_BY = Qualified Relationship | دقت گراف | Yes |
| D-020 | D2 → به C instances متصل می‌شود، نه C classes | صحت آنتولوژیک | Yes |
| D-021 | D1/D2/D6 = Programme Nodes ⚠️ | دقت آنتولوژیک | Yes — **ر.ک Finding CA-D-F001** |
| D-022 | D3/D4 = Hybrid Entities | هر دو بُعد واقعی هستند | Yes |
| D-023 | D6 → Attribute | اطلاعات منحصربه‌فرد کافی نیست | Yes |
| D-024 | پراپرتی‌های DESIGNATED_BY تعریف شد | قطعیت کامل | Yes |
| D-025 | بدون Designation Event Node — مطلق | جلوگیری از انفجار Entity | **No** |
| D-026 | Aggregation pages نیاز به محتوای ادیتوریال یکتا دارند | جلوگیری از Doorway Page | Yes |
| D-029 | سیاست CONTAINS Tier در ManagedArea | جلوگیری از انفجار رابطه | Yes |
| D-030 | سیاست Reification برای designations تکراری | رفع ابهام رویدادهای چندگانه | Yes |
| D-031 | جزئیات سیاست CONTAINS Tier رسمی شد | یکپارچگی گراف | Yes |

---

## بخش ۲ — Phase 3 Findings برای Category D

### Finding CA-D-F001 — تعارض D-021 با شواهد ساختاری درباره وضعیت D6
*(جزئیات کامل پیش از این بخش ارائه شد)*

**خلاصه:** D-021 متن "D1/D2/D6 = Programme Nodes" دارد، اما جدول موجودیت‌ها، Executive Summary، D-023، و غیبت D6 از R20 Targets همگی D6 = Attribute را تأیید می‌کنند.
**وضعیت:** مستند شد. طراحی بر اساس D6 = Attribute انجام می‌شود (شواهد ساختاری ارجحیت دارند).
**اقدام لازم از صاحب پروژه:** تصحیح متن D-021 در نسخه بعدی IA (پیشنهاد: "D1/D2 = Programme Nodes").

### Finding CA-D-F002 — D3/D4 به‌عنوان Hybrid Entity: ریسک Dual-Node

**Finding:**
D-022 می‌گوید D3/D4 "Hybrid Entities" هستند — دو بُعد دارند:
۱. **بُعد Programme:** D3 (UNESCO Global Geopark Programme) خودش یک node است که Target رابطه R20 قرار می‌گیرد.
۲. **بُعد جغرافیایی:** هر نمونه مشخص (مثلاً "ژئوپارک قشم") طبق D-016 در Category G به‌عنوان ManagedArea ظاهر می‌شود — یک node جداگانه با LOCATED_IN خودش.

**Impact Analysis:**
این یعنی برای یک مکان فیزیکی واحد (ژئوپارک قشم)، دو node در گراف وجود دارد: (الف) ManagedArea instance در G، و (ب) رابطه R20 از آن ManagedArea به D3 (Programme Node سراسری). این در نگاه اول ممکن است P-02 را نقض‌شده به‌نظر برساند، اما با بررسی دقیق:

- D3 (Programme Node) خودِ "برنامه ژئوپارک یونسکو" است — یک مفهوم سراسری، نه یک مکان فیزیکی.
- ManagedArea instance (ژئوپارک قشم) خودِ مکان فیزیکی است.
- این دو موجودیت متفاوت‌اند: یکی برنامه (Programme)، دیگری مکان (Place). P-02 می‌گوید "یک مکان فیزیکی = یک node اصلی" — و اینجا دقیقاً همین رعایت شده: ژئوپارک قشم فقط **یک** node فیزیکی دارد (ManagedArea در G)، که از طریق رابطه R20 به D3 (که خودش مکان نیست) وصل می‌شود.

**Recommendation:**
هیچ نقض P-02 ای رخ نداده — اما این تمایز ظریف باید به‌صراحت در Editorial Guideline مستند شود تا تیم محتوا اشتباهاً برای هر ژئوپارک یک صفحه D3 جداگانه نسازد. صفحه D3 فقط **یک صفحه سراسری** برای کل برنامه است (طبق D-014 مشابه با D1). صفحات مکان‌محور (مثل ژئوپارک قشم) در Category G طراحی می‌شوند، نه در D.

### Finding CA-D-F003 — National Registration و Free Zone: غیبت از Content Model صریح IA

**Finding:**
بخش "Content model for D" در IA صریحاً Aggregation Page را برای D1, D2, D3, D4, D5, D6 تعریف کرده، اما **هیچ اشاره‌ای به National Registration و Free Zone ندارد** — با اینکه این دو در جدول موجودیت‌های D حضور دارند.

**Impact Analysis:**
اگر این دو Attribute بدون هیچ صفحه یا Aggregation Page باقی بمانند، اطلاعاتشان فقط در صفحات Target (مثلاً یک A2 با national_registration:true) قابل مشاهده است — بدون امکان کشف از طریق یک صفحه فهرستی. این می‌تواند یک فرصت SEO/AEO از‌دست‌رفته باشد (مثلاً "لیست آثار ثبت‌ملی ایران" یک query واقعی است) اما IA این را صراحتاً تعریف نکرده است.

**Recommendation:**
این دو Attribute بدون Aggregation Page مستقل طراحی می‌شوند — دقیقاً مطابق سکوت صریح IA (که برخلاف D1-D6 برایشان aggregation تعریف نشده). اگر در آینده نیاز SEO به فهرست این دو احساس شد، باید به‌عنوان یک IA Revision Request رسمی (نه یک تصمیم Phase 3) مطرح شود، چون اضافه‌کردن aggregation page برای این دو به معنای گسترش صریح Content Model تعریف‌شده در IA است.

---

## بخش ۳ — تصمیمات Phase 3 اعمال‌شده

### CA-D-D001 — وضعیت نهایی D6 = Attribute
**Decision:** D6 در سراسر این سند به‌عنوان Attribute طراحی می‌شود — بدون صفحه مستقل، بدون Programme Node.
**Rationale:** شواهد ساختاری (غیبت از R20 Targets) + رجحان عددی منابع (۳ به ۱) در Finding CA-D-F001.
**Impact:** D6 هیچ Page Template، هیچ Required/Optional Fields مستقل، و یک Aggregation Page (طبق دستور صریح IA که D6 را در لیست aggregation قرار داده) دارد.
**Revisable:** Yes — مشروط به تصحیح رسمی D-021 توسط صاحب پروژه.

### CA-D-D002 — صفحات D3/D4 محدود به سطح Programme
**Decision:** صفحه D3 و D4 هرکدام **فقط یک صفحه سراسری** (Programme-level) هستند. صفحات مکان‌محور ژئوپارک‌ها/ذخیره‌گاه‌های زیست‌کره مشخص، در Category G (ManagedArea) طراحی می‌شوند — نه در D.
**Rationale:** D-014 (الگوی یک Aggregation URL برای D1) + Finding CA-D-F002.
**Impact:** جلوگیری از ایجاد ده‌ها صفحه D3 تکراری برای هر ژئوپارک. حفظ Single Source of Truth.
**Revisable:** Yes
**IA Basis:** D-014، D-016، D-022، P-02

### CA-D-D003 — مدل محتوایی یکسان برای D1، D2، D3، D4، D6
**Decision:** این پنج موجودیت — به‌رغم تفاوت در نوع (Programme Node vs Attribute) — همگی یک الگوی محتوایی مشترک می‌گیرند: **یک صفحه Aggregation با محتوای ادیتوریال یکتا** (طبق متن صریح IA).
**Rationale:** متن مستقیم IA: "D1/D2/D3/D4/D6: One aggregation page each."
**Impact:** Page Template برای این پنج مشابه است؛ تفاوت فقط در منبع داده (Programme Node های واقعی برای D1-D4 در برابر فیلتر attribute برای D6).
**Revisable:** Yes

### CA-D-D004 — D5 مدل دو-سطحی
**Decision:** D5 طبق متن صریح IA دو‌جزئی طراحی می‌شود: (الف) یک Section داخل صفحه G5 هدف، (ب) یک صفحه Aggregation سراسری "شهرهای خلاق یونسکوی ایران."
**Rationale:** متن مستقیم IA: "D5: Section within G5 page + one aggregation page."
**Impact:** هیچ صفحه مستقل برای D5 وجود ندارد — صرفاً یک بخش در G5 + یک فهرست سراسری.
**Revisable:** Yes
**IA Basis:** D-017، Content model for D

### CA-D-D005 — National Registration و Free Zone بدون Aggregation Page
**Decision:** این دو Attribute فقط در سطح Target Entity (به‌صورت field) نمایش داده می‌شوند — بدون صفحه مستقل یا Aggregation Page.
**Rationale:** سکوت صریح IA — Content Model فقط D1،D2،D3،D4،D5،D6 را پوشش داده.
**Impact:** هیچ صفحه جدیدی ساخته نمی‌شود. این مطابق Finding CA-D-F003 است.
**Revisable:** Yes — مشروط به IA Revision Request رسمی.

### CA-D-D006 — مدل Reification برای Designations تکراری
**Decision:** اگر یک entity چند بار طی سال‌های مختلف designation دریافت کرده (مثلاً بازنگری وضعیت UNESCO)، طبق D-030 از Reification Policy استفاده می‌شود — یعنی چند instance رابطه R20 با properties متفاوت (year متفاوت)، نه چند node.
**Rationale:** D-030 — "Reification Policy for repeated designations | Disambiguation for multiple events."
**Impact:** صفحه Target Entity می‌تواند چند رکورد designation (با سال‌های متفاوت) را در Designation Block نمایش دهد، همگی از یک منبع R20.
**Revisable:** Yes

---

## بخش ۴ — Content Architecture — موجودیت‌های Category D

---

### D1 — UNESCO World Heritage | میراث جهانی یونسکو

#### هدف موجودیت

D1 یک **Programme Node** است — خودِ برنامه میراث جهانی یونسکو، نه یک مکان فیزیکی. هر A entity (A2, A3, A9, A10, A12, A13) می‌تواند از طریق R20 به D1 متصل شود. D1 پراهمیت‌ترین designation از منظر SEO/AEO پروژه است؛ بیشترین تراکم محتوای A2 و A12 پروژه حول D1 می‌چرخد.

طبق D-014، D1 فقط **یک** صفحه Aggregation دارد — نه صفحه جداگانه برای هر سایت (آن صفحات قبلاً در A2/A12/A9/A13 طراحی شده‌اند).

#### Page Template: UNESCO World Heritage Aggregation Hub

```
[Hero Section]
  — لوگو/نماد یونسکو میراث جهانی
  — عنوان: "Iran's UNESCO World Heritage Sites"
  — تعداد کل سایت‌ها (مشتق از R21 ← R20)

[Programme Overview — 500+ words — Unique Editorial — D-026]
  — یونسکو میراث جهانی چیست؟
  — معیارهای انتخاب (Cultural / Natural / Mixed criteria)
  — جایگاه ایران در فهرست جهانی

[Sites List — Faceted]
  — فهرست تمام entities با R20 → D1
  — فیلتر: نوع (A2/A3/A9/A10/A12/A13) | استان (از طریق R01 آن entity)
  — هر آیتم: نام + تصویر + سال ثبت + لینک به صفحه اصلی entity

[By Province — Contextual Grouping]
  — گروه‌بندی سایت‌ها بر اساس G3

[By Type — Contextual Grouping]
  — Historical Monuments (A2) | Archaeological Sites (A12) |
    Cultural Landscapes (A13) | Natural Sites (A9/A10)

[Recently Added — اگر داده موجود]

[Schema Block]
```

#### Required Fields *(در سطح صفحه Aggregation)*

| فیلد | نوع | منبع IA |
|------|-----|---------|
| page_title | Text | — |
| overview_text | LongText (500+ words، یکتا) | D-026 |
| total_sites_count | Number | مشتق از R21 ← R20 (محاسباتی، I4) |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع |
|------|-----|
| hero_image | Image |
| featured_sites | Relation[] (دستی، برای برجسته‌سازی چند سایت کلیدی) |

#### Designation Properties *(روی خودِ رابطه R20 — نه روی صفحه D1)*

طبق R20، این پراپرتی‌ها روی هر رابطه (نه روی D1) ذخیره می‌شوند:

| پراپرتی | نوع | توضیح |
|---------|-----|--------|
| designation_type | Text | نوع UNESCO criteria |
| year | Number | سال ثبت |
| criteria | Text | معیارهای OUV |
| status | Enum | Active / Under Review / Threatened |
| review_date | Date | اگر applicable |
| awarding_body | Text | UNESCO |

این پراپرتی‌ها در صفحه **Target Entity** (مثلاً صفحه A12 تخت جمشید) نمایش داده می‌شوند، نه در صفحه D1 (طبق "All designation data lives on target entity pages" — متن صریح IA).

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R21 ← (derived از R20) | تمام Target Entities (A2/A3/A9/A10/A12/A13) | فهرست اصلی صفحه |
| → G3 (transitively) | گروه‌بندی استانی | Contextual filter |

#### Aggregation Pages

D1 **خودش** تنها Aggregation Page موجودیتش است (طبق D-014). هیچ Aggregation فرعی دیگری ساخته نمی‌شود — صرفاً فیلترهای داخل همین یک صفحه (By Province, By Type).

#### SEO Purpose
- Target: "Iran UNESCO World Heritage Sites", "UNESCO sites in Iran list", "how many UNESCO sites does Iran have"
- این صفحه بالاترین Authority Signal را برای کل خوشه محتوای تاریخی پروژه حمل می‌کند.

#### AEO Purpose
- "How many UNESCO World Heritage Sites are in Iran?"
- "What are Iran's UNESCO sites?"
- "Which Iranian city has the most UNESCO sites?"

#### Schema Readiness

```json
{
  "@type": "CollectionPage",
  "about": {
    "@type": "Thing",
    "name": "UNESCO World Heritage Sites in Iran"
  },
  "hasPart": [
    { "@type": "LandmarksOrHistoricalBuildings", "name": "[Site 1]" },
    { "@type": "LandmarksOrHistoricalBuildings", "name": "[Site 2]" }
  ]
}
```

#### MVC — AEO-First Checklist

- ✅ overview_text یکتا ≥ 500 کلمه (D-026)
- ✅ فهرست کامل sites با لینک به صفحه اصلی هرکدام
- ✅ total_sites_count محاسباتی و به‌روز
- ✅ گروه‌بندی By Province و By Type
- ✅ Schema.org CollectionPage با hasPart
- ✅ هیچ صفحه تکراری برای sites منفرد (آن‌ها در A زندگی می‌کنند)

---

### D2 — UNESCO Intangible Heritage | میراث ناملموس یونسکو

#### هدف موجودیت

D2 یک **Programme Node** است، اما با یک تفاوت معماری کلیدی نسبت به D1: طبق D-020، D2 به **C instances** متصل می‌شود (نه C classes). یعنی Source های R20 برای D2 شامل C1 instances و C10 instances هستند — مثلاً یک هنر صنایع‌دستی خاص (نه کل Classification "Carpet/Kilim")، یا یک آیین خاص (نه کل مفهوم C10).

این یعنی محتوای D2 با محتوای D1 ماهیتاً متفاوت است: D1 حول مکان‌های فیزیکی (A entities) می‌چرخد؛ D2 حول پدیده‌های فرهنگی ملموس‌نشده (C instances) می‌چرخد.

#### Page Template: UNESCO Intangible Heritage Aggregation Hub

```
[Hero Section]
  — عنوان: "Iran's UNESCO Intangible Cultural Heritage"
  — تعداد کل عناصر ثبت‌شده

[Programme Overview — 500+ words — Unique Editorial — D-026]
  — میراث ناملموس یونسکو چیست؟
  — تفاوت با میراث جهانی (D1): دانش/سنت در برابر مکان فیزیکی

[Heritage Elements List — Faceted]
  — فهرست تمام C1/C10 instances با R20 → D2
  — فیلتر: نوع (صنایع‌دستی / آیین) | قومیت مرتبط (از طریق R07 آن instance)
  — هر آیتم: نام + سال ثبت + لینک به صفحه اصلی C instance

[By Category]
  — Handicraft Elements (C1) | Ritual/Ceremony Elements (C10)

[Schema Block]
```

#### Required Fields *(سطح Aggregation)*

| فیلد | نوع | منبع IA |
|------|-----|---------|
| page_title | Text | — |
| overview_text | LongText (500+ words، یکتا) | D-026 |
| total_elements_count | Number | محاسباتی از R21←R20 |
| meta_title | Text | — |
| meta_description | Text | — |

#### Optional Fields

| فیلد | نوع |
|------|-----|
| hero_image | Image |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R21 ← R20 | C1 instances، C10 instances | فهرست اصلی |
| → C9 (از طریق R07 آن instances) | گروه‌بندی قومیتی | Contextual filter |

#### Aggregation Pages
تنها یک Aggregation Page (طبق همان الگوی D1).

#### ⚠️ Finding CA-D-F004 — D2 و مرز با Category C

**Finding:** از آنجا که D2 به C instances (نه C classes) وصل می‌شود، خطر این وجود دارد که صفحه D2 محتوای صفحه اصلی آن C instance را تکرار کند.

**Impact Analysis:** اگر صفحه D2 شرح کامل یک آیین خاص را بازنویسی کند، این هم Single Source of Truth (P-06) را نقض می‌کند و هم محتوای تکراری ایجاد می‌کند.

**Recommendation:** صفحه D2 فقط باید **لینک + خلاصه یک‌خطی + سال ثبت** برای هر C instance نمایش دهد. شرح کامل فرهنگی فقط در صفحه اصلی C instance (که در Category C طراحی خواهد شد) زندگی می‌کند.

#### SEO Purpose
- Target: "Iran intangible cultural heritage UNESCO", "Iranian traditions UNESCO list"

#### AEO Purpose
- "What Iranian traditions are UNESCO Intangible Heritage?"
- "Is Persian carpet weaving a UNESCO heritage?"

#### Schema Readiness

```json
{
  "@type": "CollectionPage",
  "about": {
    "@type": "Thing",
    "name": "UNESCO Intangible Cultural Heritage of Iran"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ overview_text یکتا ≥ 500 کلمه
- ✅ فهرست کامل با لینک به C instance اصلی (نه محتوای تکراری)
- ✅ تفکیک Handicraft vs Ritual
- ✅ Schema.org CollectionPage

---

### D3 — UNESCO Global Geopark Programme | ژئوپارک جهانی

#### هدف موجودیت

D3 یک **Hybrid Entity** است (D-022) — این پیچیده‌ترین موجودیت Category D است. دو بُعد دارد:

**بُعد ۱ — Programme (در D):** "UNESCO Global Geopark Programme" خودش یک node سراسری است که Source های A9/A10 از طریق R20 به آن وصل می‌شوند.

**بُعد ۲ — Geographic Instance (در G):** هر ژئوپارک مشخص (مثلاً ژئوپارک قشم) طبق D-016 در Category G به‌عنوان یک **ManagedArea** node مستقل با LOCATED_IN خودش زندگی می‌کند.

طبق CA-D-D002 (این سند): صفحه D3 فقط در سطح Programme طراحی می‌شود؛ صفحات هر ژئوپارک خاص متعلق به Category G/A هستند، نه D.

#### Page Template: UNESCO Geopark Programme Hub

```
[Hero Section]
  — عنوان: "UNESCO Global Geoparks of Iran"
  — تعداد کل ژئوپارک‌های ثبت‌شده

[Programme Overview — 500+ words — Unique Editorial — D-026]
  — برنامه ژئوپارک جهانی یونسکو چیست؟
  — معیارهای ارزش زمین‌شناختی

[Geoparks List — Faceted]
  — فهرست تمام ManagedArea instances با R20 → D3
  — هر آیتم: نام + استان + سال ثبت + لینک به صفحه ManagedArea در G

[By Province]

[Schema Block]
```

#### Required Fields *(سطح Aggregation)*

| فیلد | نوع | منبع IA |
|------|-----|---------|
| page_title | Text | — |
| overview_text | LongText (500+ words، یکتا) | D-026 |
| total_geoparks_count | Number | محاسباتی |
| meta_title | Text | — |
| meta_description | Text | — |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R21 ← R20 | ManagedArea instances (در G) | فهرست اصلی |
| R26 ← (از A9/A10 داخل هر ManagedArea) | جاذبه‌های طبیعی داخل هر ژئوپارک | فقط در صفحه G/ManagedArea، نه اینجا |

#### Aggregation Pages
تنها یک Aggregation Page سراسری.

#### SEO Purpose
- Target: "UNESCO Geoparks Iran", "Qeshm Geopark UNESCO"

#### AEO Purpose
- "How many UNESCO Geoparks does Iran have?"
- "What is a UNESCO Global Geopark?"

#### Schema Readiness

```json
{
  "@type": "CollectionPage",
  "about": {
    "@type": "Thing",
    "name": "UNESCO Global Geoparks in Iran"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ overview_text یکتا ≥ 500 کلمه
- ✅ فهرست کامل ManagedArea instances با لینک به صفحه G
- ✅ هیچ صفحه D3 جداگانه برای هر ژئوپارک (CA-D-D002)
- ✅ Schema.org CollectionPage

---

### D4 — UNESCO Biosphere Reserve Programme | ذخیره‌گاه زیست‌کره

#### هدف موجودیت

ساختار D4 دقیقاً موازی D3 است — Hybrid Entity (D-022) با همان دو بُعد Programme/Geographic Instance. تمام قواعد معماری D3 (CA-D-D002) عیناً برای D4 اعمال می‌شود.

#### Page Template: UNESCO Biosphere Reserve Programme Hub

```
[Hero Section]
  — عنوان: "UNESCO Biosphere Reserves of Iran"
  — تعداد کل ذخیره‌گاه‌های ثبت‌شده

[Programme Overview — 500+ words — Unique Editorial — D-026]
  — برنامه ذخیره‌گاه زیست‌کره یونسکو چیست؟
  — معیارهای انتخاب (تعادل انسان-طبیعت)

[Reserves List — Faceted]
  — فهرست تمام ManagedArea instances با R20 → D4
  — هر آیتم: نام + استان + سال ثبت + لینک به صفحه ManagedArea در G

[By Province]

[Schema Block]
```

#### Required Fields *(سطح Aggregation)*

| فیلد | نوع | منبع IA |
|------|-----|---------|
| page_title | Text | — |
| overview_text | LongText (500+ words، یکتا) | D-026 |
| total_reserves_count | Number | محاسباتی |
| meta_title | Text | — |
| meta_description | Text | — |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| R21 ← R20 | ManagedArea instances (در G) | فهرست اصلی |

#### Aggregation Pages
تنها یک Aggregation Page سراسری.

#### SEO Purpose
- Target: "UNESCO Biosphere Reserves Iran", "Arasbaran Biosphere Reserve"

#### AEO Purpose
- "How many Biosphere Reserves does Iran have?"
- "What is a UNESCO Biosphere Reserve?"

#### Schema Readiness

```json
{
  "@type": "CollectionPage",
  "about": {
    "@type": "Thing",
    "name": "UNESCO Biosphere Reserves in Iran"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ overview_text یکتا ≥ 500 کلمه
- ✅ فهرست کامل با لینک به صفحه G
- ✅ هیچ صفحه D4 جداگانه برای هر ذخیره‌گاه
- ✅ Schema.org CollectionPage

---

### D5 — UNESCO Creative City | شهر خلاق یونسکو

#### هدف موجودیت

D5 یک **Attribute روی G5** است (D-017) — نه Programme Node، نه Target رابطه R20 (D5 در R20 Targets نیست). طبق D-017، "اطلاعات فراتر از context G5 وجود ندارد" — یعنی این یک عنوان است که مستقیماً به یک شهر خاص می‌چسبد و نیازی به entity مستقل ندارد.

طبق متن صریح Content Model در IA، D5 **دو-جزئی** است: یک Section در صفحه G5 + یک صفحه Aggregation سراسری.

#### مدل دو-سطحی D5

**سطح ۱ — Section در صفحه G5:**
این بخش از قبل در CA-G-v1.0-FINAL برای G5 طراحی شده (فیلد `unesco_creative_city: Boolean`). هیچ طراحی جدیدی لازم نیست — فقط تأیید سازگاری.

**سطح ۲ — صفحه Aggregation سراسری (جدید در این سند):**

#### Page Template: UNESCO Creative Cities of Iran (Aggregation)

```
[Hero Section]
  — عنوان: "Iran's UNESCO Creative Cities"
  — تعداد کل شهرها

[Programme Overview — 400+ words — Unique Editorial — D-026]
  — شبکه شهرهای خلاق یونسکو چیست؟
  — حوزه‌های خلاقیت (صنایع‌دستی، هنر مردمی، گاسترونومی، و...)

[Cities List]
  — فهرست تمام G5 instances با unesco_creative_city: true
  — هر آیتم: نام شهر + حوزه خلاقیت + سال ثبت + لینک به صفحه G5

[Schema Block]
```

#### Required Fields *(سطح Aggregation)*

| فیلد | نوع | منبع IA |
|------|-----|---------|
| page_title | Text | — |
| overview_text | LongText (400+ words، یکتا) | D-026 |
| total_cities_count | Number | محاسباتی |
| meta_title | Text | — |
| meta_description | Text | — |

#### Field روی Target Entity *(در G5 — قبلاً در CA-G طراحی شده)*

| فیلد | نوع | محل |
|------|-----|-----|
| unesco_creative_city | Boolean | صفحه G5 — قبلاً در CA-G-v1.0-FINAL |
| creative_city_domain | Text (اختیاری — پیشنهاد تکمیلی) | صفحه G5 |
| creative_city_year | Number (اختیاری — پیشنهاد تکمیلی) | صفحه G5 |

#### ⚠️ Finding CA-D-F005 — جزئیات D5 در CA-G ناقص بود

**Finding:** در CA-G-v1.0-FINAL، فیلد `unesco_creative_city: Boolean` در G5 تعریف شده، اما `creative_city_domain` (حوزه خلاقیت — مثلاً Crafts & Folk Art) و `creative_city_year` (سال ثبت) در آن سند نبودند.

**Impact Analysis:** بدون این دو فیلد، صفحه Aggregation D5 نمی‌تواند جدول کامل (شهر + حوزه + سال) نمایش دهد — که برای AEO حیاتی است (سؤال رایج: "ایران چند شهر خلاق یونسکو دارد و در چه حوزه‌ای؟").

**Recommendation:** دو فیلد اختیاری `creative_city_domain` و `creative_city_year` به Optional Fields صفحه G5 اضافه شود. این یک تکمیل (نه تغییر) CA-G است و در زمان بازنگری بعدی Category G باید اعمال شود.

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| — (فیلتر attribute، نه relationship) | G5 instances با unesco_creative_city:true | فهرست اصلی |

#### Aggregation Pages
یک صفحه Aggregation سراسری (طبق متن صریح IA).

#### SEO Purpose
- Target: "UNESCO Creative Cities Iran", "Iran creative cities list"

#### AEO Purpose
- "How many UNESCO Creative Cities does Iran have?"
- "What is Iran's UNESCO Creative City in crafts?"

#### Schema Readiness

```json
{
  "@type": "CollectionPage",
  "about": {
    "@type": "Thing",
    "name": "UNESCO Creative Cities of Iran"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ overview_text یکتا ≥ 400 کلمه
- ✅ فهرست کامل شهرها با لینک به صفحه G5
- ✅ Section متناظر در صفحه G5 (از قبل در CA-G)
- ✅ Schema.org CollectionPage

---

### D6 — National Natural Monument | اثر طبیعی ملی

#### هدف موجودیت

طبق Finding CA-D-F001 و تصمیم CA-D-D001، D6 در این سند به‌عنوان **Attribute** طراحی می‌شود — نه Programme Node. D6 یک عنوان داخلی (نه UNESCO) است که روی موجودیت‌های طبیعی (عمدتاً A10، گاهی A9) قرار می‌گیرد.

با این حال، طبق متن صریح Content Model در IA، D6 **یک صفحه Aggregation** دارد — درست مثل D1-D4 — حتی با وجود اینکه Attribute است نه Programme Node. این یک نکته معماری ظریف است: نوع موجودیت (Programme Node vs Attribute) تعیین‌کننده ساختار گراف است، اما الزام Aggregation Page یک تصمیم محتوایی مستقل در IA است که هر دو نوع را پوشش می‌دهد.

#### Page Template: National Natural Monuments of Iran (Aggregation)

```
[Hero Section]
  — عنوان: "Iran's National Natural Monuments"
  — تعداد کل آثار ثبت‌شده

[Programme Overview — 400+ words — Unique Editorial — D-026]
  — اثر طبیعی ملی چیست؟ (عنوان داخلی ایران، نه یونسکو)
  — معیارهای ثبت داخلی

[Monuments List]
  — فهرست تمام A10/A9 instances با national_monument: true
  — هر آیتم: نام + نوع (آبشار/غار/صخره/...) + استان + لینک به صفحه A اصلی

[By Type — Contextual]
  — Waterfalls | Caves | Rock Formations | ...

[Schema Block]
```

#### Required Fields *(سطح Aggregation)*

| فیلد | نوع | منبع IA |
|------|-----|---------|
| page_title | Text | — |
| overview_text | LongText (400+ words، یکتا) | D-026 |
| total_monuments_count | Number | محاسباتی |
| meta_title | Text | — |
| meta_description | Text | — |

#### Field روی Target Entity *(در A10/A9 — قبلاً در CA-A طراحی شده)*

| فیلد | نوع | محل |
|------|-----|-----|
| national_monument | Boolean | صفحه A10/A9 — قبلاً در CA-A-v1.0-FINAL |

#### Internal Links

| رابطه | هدف | نوع لینک |
|--------|-----|----------|
| — (فیلتر attribute) | A10/A9 instances با national_monument:true | فهرست اصلی |

#### Aggregation Pages
یک صفحه Aggregation سراسری (طبق متن صریح IA، با وجود نوع Attribute بودن).

#### SEO Purpose
- Target: "Iran national natural monuments", "protected natural sites Iran"

#### AEO Purpose
- "What are Iran's national natural monuments?"
- "Is [waterfall name] a protected natural monument?"

#### Schema Readiness

```json
{
  "@type": "CollectionPage",
  "about": {
    "@type": "Thing",
    "name": "National Natural Monuments of Iran"
  }
}
```

#### MVC — AEO-First Checklist

- ✅ overview_text یکتا ≥ 400 کلمه
- ✅ فهرست کامل با لینک به صفحه A اصلی
- ✅ تفکیک By Type
- ✅ Schema.org CollectionPage
- ✅ بدون صفحه D6 مستقل برای هر اثر (Attribute-only — CA-D-D001)

---

### National Registration و Free Zone — دو Attribute بدون کد رسمی

#### هدف موجودیت

این دو در جدول موجودیت‌های Category D حضور دارند اما (الف) کد رسمی Dn ندارند، (ب) در Content Model صریح IA برایشان Aggregation Page تعریف نشده (Finding CA-D-F003). بنابراین طبق CA-D-D005، این دو **هیچ صفحه مستقلی** (نه Page، نه Aggregation) دریافت نمی‌کنند.

#### مدل محتوایی

| موجودیت | محل field | نوع | منبع IA |
|---------|-----------|-----|---------|
| National Registration | روی هر entity واجد شرایط (A2، A3، A10، A12، A13، C1 instances، C10 instances) | Boolean (`national_registration`) | "Attribute on eligible entities" |
| Free Zone | روی G5/G8 | Boolean (`free_zone`) | "Attribute on G5/G8" |

این فیلدها **از قبل** در CA-G (G5، G8) و CA-A (A2، A3، A12، A13) تعریف شده‌اند. هیچ کار طراحی جدیدی در این سند لازم نیست — فقط تأیید می‌شود که این دو، طبق سکوت صریح IA، خارج از محدوده Aggregation Page این Category باقی می‌مانند.

#### MVC
ندارد — این دو موجودیت صفحه مستقل تولید نمی‌کنند.

---

## بخش ۵ — Decision Log — Category D

| Decision ID | Subject | Decision | Rationale | Impact | Revisable |
|---|---|---|---|---|---|
| **CA-D-D001** | وضعیت D6 | D6 = Attribute (نه Programme Node) | شواهد ساختاری (غیبت از R20 Targets) + رجحان ۳ منبع مستقل بر ۱ | بدون صفحه مستقل برای D6 خودش؛ فقط یک Aggregation Page | Yes — مشروط به تصحیح D-021 |
| **CA-D-D002** | محدوده صفحات D3/D4 | فقط یک صفحه Programme-level برای هرکدام؛ نمونه‌های جغرافیایی در Category G طراحی می‌شوند | D-014، D-016، Finding CA-D-F002، P-02 | جلوگیری از ده‌ها صفحه تکراری per geopark/reserve | Yes |
| **CA-D-D003** | الگوی محتوایی مشترک D1/D2/D3/D4/D6 | همگی یک Aggregation Page با محتوای ادیتوریال یکتا | متن صریح IA: "One aggregation page each" | Page Template یکسان با منبع داده متفاوت | Yes |
| **CA-D-D004** | مدل دو-سطحی D5 | Section در G5 + یک Aggregation سراسری | متن صریح IA + D-017 | هیچ صفحه مستقل D5؛ دو نقطه محتوایی | Yes |
| **CA-D-D005** | National Registration / Free Zone | بدون Aggregation Page؛ فقط field روی Target | سکوت صریح IA در Content Model (Finding CA-D-F003) | بدون کار طراحی جدید — فیلدها از قبل در CA-G/CA-A موجودند | Yes — مشروط به IA Revision Request |
| **CA-D-D006** | Designations تکراری | استفاده از Reification (چند instance رابطه R20 با year متفاوت) | D-030 | پشتیبانی از بازنگری وضعیت UNESCO طی سال‌ها بدون node اضافه | Yes |

---

## بخش ۶ — Cross-Category Consistency Review

### بررسی ۱ — Entity Inflation

| بررسی | نتیجه |
|--------|--------|
| آیا entity جدیدی برای D3/D4 geographic instances ساخته شد؟ | ❌ خیر — این‌ها از قبل به‌عنوان ManagedArea در طراحی G وجود داشتند (D-016) |
| آیا صفحه مستقل برای D6 (به‌عنوان node) ساخته شد؟ | ❌ خیر — فقط Aggregation Page؛ D6 خودش Attribute است |
| آیا National Registration/Free Zone entity جدید شدند؟ | ❌ خیر — همان field های موجود در G/A |
| آیا Aggregation Pages خودشان entity محسوب می‌شوند؟ | ❌ خیر — صفحه محتوایی، نه node گراف |
| **نتیجه کل** | ✅ **PASS — No Entity Inflation** |

### بررسی ۲ — Relationship Inflation

| بررسی | نتیجه |
|--------|--------|
| آیا relationship جدیدی برای اتصال D5/D6 ایجاد شد؟ | ❌ خیر — D5/D6 از طریق Attribute مستقیم (نه relationship) کار می‌کنند، دقیقاً مطابق R20 Targets که D5/D6 را شامل نمی‌شود |
| آیا National Registration/Free Zone relationship گرفتند؟ | ❌ خیر — Boolean field ساده |
| آیا رابطه‌ای بین D3 (Programme) و ManagedArea instance آن در گراف اضافه شد؟ | ❌ خیر — این از طریق R20 موجود (ManagedArea → D3) پوشش داده می‌شود؛ هیچ رابطه جدیدی لازم نبود |
| **نتیجه کل** | ✅ **PASS — No Relationship Inflation** |

### بررسی ۳ — Duplicate Modeling

| سناریو | بررسی | نتیجه |
|--------|--------|--------|
| D1 Aggregation vs صفحات A2/A12 منفرد | D1 فقط فهرست + لینک می‌دهد؛ شرح کامل در صفحه A زندگی می‌کند | ✅ |
| D2 Aggregation vs صفحات C instances | طبق Finding CA-D-F004، D2 فقط خلاصه+لینک می‌دهد | ✅ |
| D3/D4 Programme page vs صفحات ManagedArea در G | تفکیک کامل: Programme در D، مکان فیزیکی در G (CA-D-D002) | ✅ |
| D5 Aggregation vs Section در G5 | دو سطح مکمل، نه تکراری — یکی فهرست سراسری، دیگری جزئیات محلی | ✅ |
| D6 Aggregation vs صفحات A10 منفرد | فقط فهرست + لینک؛ شرح کامل در A10 | ✅ |
| **نتیجه کل** | | ✅ **PASS — No Duplicate Modeling** |

### بررسی ۴ — Circular Dependencies

```
زنجیره‌های تأیید‌شده:
A2/A3/A9/A10/A12/A13 → DESIGNATED_BY (R20) → D1/D2/D3/D4 (یکطرفه)
D1/D2/D3/D4 → HAS_DESIGNATED (R21) → Entities (Derived، معکوس R20 — I4)
ManagedArea (در G) → DESIGNATED_BY (R20) → D3/D4

هیچ مسیر برگشتی (D → A → D) وجود ندارد.
R21 صرفاً Derived/محاسباتی است، نه یک رابطه دوم stored.

هیچ circular path وجود ندارد.
```

**نتیجه: ✅ PASS — No Circular Dependencies**

### بررسی ۵ — Geography First Compliance (P-01)

| موجودیت D | اتصال جغرافیایی |
|-----------|------------------|
| D1 (Programme) | خودش node جغرافیایی نیست — Target یک رابطه از A entities که خودشان geo دارند. سراسری (Country-Wide به‌طور implicit) |
| D2 (Programme) | مشابه D1 — Target از C instances که خودشان geo (از طریق R06/R08/R09) دارند |
| D3 (Programme) | مشابه — نمونه‌های جغرافیایی‌اش در G (ManagedArea) با geo کامل زندگی می‌کنند |
| D4 (Programme) | مشابه D3 |
| D5 (Attribute) | روی G5 — که خودش Geography First کامل دارد (تأیید‌شده در CA-G) |
| D6 (Attribute) | روی A10/A9 — که خودشان Geography First کامل دارند (تأیید‌شده در CA-A) |

**نتیجه: ✅ PASS — Geography First محترم شمرده شده در تمام سطوح، چه مستقیم چه غیرمستقیم از طریق Target Entities**

### بررسی ۶ — One Physical Place = One Primary Node (P-02)

| سناریو | بررسی | نتیجه |
|--------|--------|--------|
| ژئوپارک قشم: یک مکان، چند node؟ | فقط یک node فیزیکی (ManagedArea در G). D3 یک node مفهومی Programme است، نه فیزیکی. رابطه R20 بین آن‌هاست. | ✅ Finding CA-D-F002 رفع شد |
| یک A2 با چند designation سال‌های مختلف | چند instance رابطه R20 (Reification — D-030)، نه چند node | ✅ |
| D5 روی G5: آیا node دوم ساخته شد؟ | خیر — فقط Boolean field روی همان G5 node موجود | ✅ |
| D6 روی A10: آیا node دوم ساخته شد؟ | خیر — فقط Boolean field روی همان A10 node موجود | ✅ |
| **نتیجه کل** | | ✅ **PASS** |

### بررسی ۷ — مقایسه با Category G (CA-G-v1.0-FINAL)

| بررسی | نتیجه |
|--------|--------|
| آیا D3/D4 ManagedArea instances با G9-G13 تداخل دارند؟ | ✅ ندارند — ManagedArea یک subtype اضافه روی G entities موجود است (طبق D-016)، نه entity موازی |
| آیا D5 field های پیشنهادی (creative_city_domain/year) با ساختار G5 سازگارند؟ | ✅ سازگار — Optional Fields اضافه‌شدنی، بدون تغییر در Required Fields موجود |
| آیا G5 Section مربوط به D5 (در CA-G) با این سند هماهنگ است؟ | ✅ بله — فیلد `unesco_creative_city` در CA-G از قبل تعریف شده بود؛ این سند آن را تکمیل می‌کند نه جایگزین |
| **نتیجه کل** | ✅ **PASS — G ↔ D Consistent** |

### بررسی ۸ — مقایسه با Category A (CA-A-v1.0-FINAL)

| بررسی | نتیجه |
|--------|--------|
| آیا فیلدهای designation در CA-A (world_heritage, geopark, biosphere_reserve, national_monument) با مدل R20 این سند سازگارند؟ | ✅ کاملاً سازگار — همان فیلدها در CA-A به‌عنوان Boolean derived از R20 تعریف شده بودند |
| آیا A7 (Museum) که در CA-A از R20 Sources مستثنی شد، با این سند هم‌راستاست؟ | ✅ بله — Finding CA-A-F001 (در سند قبلی) دقیقاً با غیبت A7 از R20 Sources در این سند هم‌خوانی دارد |
| آیا C9 (Non-Inheritance) در A13 (که در CA-A به‌عنوان Finding CA-A-F002 مطرح شد) با مدل D این سند سازگار است؟ | ✅ بله — این سند صراحتاً C9 را در بخش Constraints تکرار و تأیید کرده |
| **نتیجه کل** | ✅ **PASS — A ↔ D Consistent** |

### بررسی ۹ — SEO/AEO Consistency

| بررسی | نتیجه |
|--------|--------|
| آیا D1 Aggregation با صفحات A2/A12 منفرد در رقابت کلمه کلیدی هستند؟ | ✅ خیر — D1 برای query سطح فهرست ("لیست همه UNESCO sites")؛ A2/A12 برای query سطح مکان خاص ("تخت جمشید") |
| آیا D3/D4 Programme page با صفحه ManagedArea در G رقابت می‌کند؟ | ✅ خیر — تفکیک Programme-level در برابر Place-level دقیقاً مانع Cannibalization می‌شود |
| آیا هر Aggregation Page محتوای ادیتوریال یکتا دارد (D-026)؟ | ✅ بله — هرکدام Overview اختصاصی با کلمه‌شمار مشخص دارند |
| **نتیجه کل** | ✅ **PASS — No SEO/AEO Cannibalization Risk** |

### Cross-Category Risks شناسایی‌شده

| ریسک | سطح | اقدام |
|------|-----|--------|
| سردرگمی تیم محتوا بین D3 Programme page و صفحات ManagedArea مشخص | متوسط | CA-D-D002 باید در Editorial Onboarding به‌صراحت آموزش داده شود |
| D2 Aggregation محتوا را از C instance تکرار کند | متوسط | Finding CA-D-F004 — قانون "فقط خلاصه+لینک" باید در ابزار CMS اعمال شود |
| فقدان فیلدهای creative_city_domain/year در CA-G فعلی | پایین | Finding CA-D-F005 — باید در بازنگری بعدی CA-G اعمال شود |
| تصمیم‌گیری آینده درباره National Registration/Free Zone aggregation | پایین | منوط به IA Revision Request رسمی — فعلاً بدون اقدام |
| متن متناقض D-021 در سند منبع | پایین (مستند شد) | نیازمند تصحیح صاحب پروژه در نسخه بعدی IA |

---

## بخش ۷ — Entity Inventory Validation

| بررسی | نتیجه |
|--------|--------|
| موجودیت‌های Programme Node از IA: ۴ (D1,D2,D3,D4) | ✅ ۴/۴ طراحی شد |
| موجودیت‌های Attribute از IA: ۴ (D5,D6 + ۲ بدون کد) | ✅ ۴/۴ مستند شد |
| R20 Targets (D1,D2,D3,D4) به‌درستی محدود شد؟ | ✅ بله — D5/D6 هرگز Target رابطه طراحی نشدند |
| C6/C9/C4 رعایت شد؟ | ✅ بله در تمام طراحی‌ها |
| P-02/P-08/P-10 رعایت شد؟ | ✅ بله — هیچ Designation Event Node ساخته نشد |
| D-014 (یک Aggregation URL) برای D1 رعایت شد؟ | ✅ بله، و همین الگو برای D2/D3/D4/D6 تعمیم یافت |
| D-016/D-022 (Hybrid D3/D4) به‌درستی مدل شد؟ | ✅ بله — Finding CA-D-F002 رفع شد |
| D-017 (D5 Attribute) رعایت شد؟ | ✅ بله — مدل دو-سطحی پیاده شد |
| D-020 (D2 → C instances) رعایت شد؟ | ✅ بله |
| D-030 (Reification) پوشش داده شد؟ | ✅ بله — CA-D-D006 |
| Architecture Freeze | ✅ INTACT |
| New Entities Introduced | ❌ صفر |
| New Relationships Introduced | ❌ صفر |
| New Constraints Introduced | ❌ صفر |
| New Categories Introduced | ❌ صفر |

---

## بخش ۸ — Category D Final Status

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CATEGORY D — DESIGNATIONS & OFFICIAL TITLES
CONTENT ARCHITECTURE FINAL STATUS
Phase 3 | ToPersia | IA-v1.2.1-FINAL Reference
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Entities Designed:
  D1  UNESCO World Heritage          Programme Node  ✅
  D2  UNESCO Intangible Heritage     Programme Node  ✅
  D3  UNESCO Global Geopark          Programme Node  ✅ (Hybrid)
  D4  UNESCO Biosphere Reserve       Programme Node  ✅ (Hybrid)
  D5  UNESCO Creative City           Attribute       ✅ (Dual-level)
  D6  National Natural Monument      Attribute       ✅
  National Registration              Attribute       ✅ (no page)
  Free Zone                          Attribute       ✅ (no page)

Decisions Logged:              6 (CA-D-D001–D006)   ✅
Findings Raised:               5 (F001–F005)         ✅
Architecture Freeze:           INTACT                ✅
New Entities Introduced:       0                     ✅
New Relationships Introduced:  0                     ✅
New Constraints Introduced:    0                     ✅
New Categories Introduced:     0                     ✅

Cross-Category Reviews:
  Entity Inflation:            PASS       ✅
  Relationship Inflation:      PASS       ✅
  Duplicate Modeling:          PASS       ✅
  Circular Dependencies:       PASS       ✅
  Geography First (P-01):      PASS       ✅
  One Primary Node (P-02):     PASS       ✅
  G ↔ D Consistency:           PASS       ✅
  A ↔ D Consistency:           PASS       ✅
  SEO/AEO Consistency:         PASS       ✅

Open Items (require Project Owner action):
  CA-D-F001  D-021 text contradiction — needs IA correction
  CA-D-F005  CA-G missing creative_city_domain/year fields
  CA-D-F003  National Registration/Free Zone aggregation —
             deferred pending formal IA Revision Request

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STATUS: ✅ COMPLETE
Category D Content Architecture is finalized.
All Programme Nodes and Attributes designed per IA's
explicit Content Model section.
Hybrid D3/D4 architecture resolved without violating P-02.
All decisions recorded. All findings documented.
All cross-category checks passed.
Architecture Freeze intact throughout.

Ready to proceed to Category C — Culture & Heritage.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

*ToPersia CA-D-v1.2-FINAL*
*Phase 3: Content Architecture*
*مرجع: IA-v1.2.1-FINAL (Frozen)*
*مرجع CA: CA-G-v1.0-FINAL · CA-A-v1.0-FINAL*
*وضعیت: COMPLETE*
