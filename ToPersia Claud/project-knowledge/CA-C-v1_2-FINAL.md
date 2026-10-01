# ToPersia — Phase 3: Content Architecture
## CA-C-v1.2-FINAL
### Category C — Culture & Heritage
### نسخه نهایی — تمام ۱۰ موجودیت
#### مرجع: IA-v1.2.1-FINAL (Frozen) | ادغام: CA-C-EXTRACTION · CA-C-FINDINGS · CA-C-PART1 · CA-C-PART2A · CA-C-PART2B

> **Changelog v1.0 → v1.1:** AUDIT-PATCH-006 — فعال‌سازی `travel_impact` روی C10 (حذف برچسب Deferred؛ شرط فعال‌سازی — انتشار CA-P — محقق شده بود)، در ۳ موضع.
> **Changelog v1.1 → v1.2:** CA-C-D015 — تبدیل هر ۲۰ فهرست Required/Optional (۱۰ موجودیت × ۲) از prose میان‌نقطه‌ای به جدول سه‌ستونهٔ استاندارد، هم‌قالب با G/A/D/E/P. صحت تبدیل با Parity Check خودکارِ نام‌به‌نام (صفر اختلاف، صفر فیلد گم/اضافه) تأیید شد. جزئیات: PHASE3-VALIDATION-LOG-v1_2.md.

---

## سند Metadata

| فیلد | مقدار |
|------|-------|
| پروژه | ToPersia |
| فاز | Phase 3 — Content Architecture |
| Category | C — Culture & Heritage |
| نسخه | 1.2-FINAL |
| وضعیت | **APPROVED — FINAL** |
| مرجع IA | IA-v1.2.1-FINAL (Frozen) |
| مراجع CA | CA-G-v1.0-FINAL · CA-A-v1.0-FINAL · CA-D-v1.0-FINAL |
| تصمیمات پیش‌تأیید | C-DES-001 · C-DES-002 · C-DES-003 |
| موجودیت‌های پوشش‌داده‌شده | ۱۰ / ۱۰ |
| C11 | Content Type — بدون template (DC-R-001) |
| Architecture Freeze | INTACT |

---

## بخش ۱ — تصمیمات نهایی مالک (اعمال‌شده در این سند)

| شناسه | موضوع | تصمیم مالک |
|--------|--------|-------------|
| **CA-C-013** | C2 ∉ R20 — عناصر ICH غذایی (لواش) بدون پوشش گراف | **Deferred** — بدون تغییر IA؛ ثبت به‌عنوان Future IA Review Item |
| **CA-C-014** | C6 ∉ R20 — عناصر ICH موسیقایی (ردیف، دوتار) بدون پوشش گراف | **Deferred** — بدون تغییر IA؛ ثبت به‌عنوان Future IA Review Item |
| **CA-C-015** | C8 ∉ R07 — رابطه زبان↔قوم فقط Content Layer | **نگهداری وضع فعلی** — بدون تغییر R07؛ رویکرد Content Layer دائمی (نه موقت) |

---

## بخش ۲ — ثبت Future IA Review Items

### Item FIR-C-001 (از CA-C-013 + CA-C-014)

**عنوان:** بازبینی جامع R20 Sources در برابر فهرست کامل ICH ایران

**توضیح:** دو دسته از عناصر واقعی ثبت‌شده یونسکو (ICH) به‌ترتیب به C2 (غذا — لواش ۲۰۱۶) و C6 (موسیقی — ردیف ۲۰۰۹، دوتار ۲۰۱۹) نگاشت می‌شوند اما هیچ‌کدام در Sources R20 نیستند. صفحه D2 Hub نسبت به فهرست واقعی ICH ایران ناقص می‌ماند و پاسخ AEO گراف‌محور به سؤالاتی مانند "Is lavash a UNESCO heritage?" ممکن نیست.

**پیشنهاد برای بازبینی:** افزودن "C2 instances" و "C6 instances" به Sources R20 در بازنگری بعدی IA — تغییر حداقلی، بدون entity یا رابطه جدید.

**اولویت:** متوسط — تا رفع این gap، پوشش editorial در صفحات D2 Hub و صفحات C2/C6 مرتبط کافی است.

### Item FIR-C-002 (از CA-C-015)

**عنوان:** بررسی افزودن C8 به Sources R07

**توضیح:** رابطه زبان↔قوم در گراف از هیچ‌سو قابل پیمایش نیست. این پیوند بدیهی‌ترین ارتباط فرهنگی (کردی↔کردها) است که C9 Hub برای کامل بودن به آن نیاز دارد.

**پیشنهاد برای بازبینی:** افزودن "C8" به Sources R07 — سازگار با الگوی موجود، بدون رابطه جدید.

**اولویت:** پایین — Content Layer فعلی عملیاتی است.

---

## بخش ۳ — Decision Log — کامل Category C

### تصمیمات پیش‌تأیید (C-DES)

| کد | تصمیم | اثر |
|----|--------|------|
| **C-DES-001** | C10 named elements = Instance Node؛ D2 فقط به instanceها؛ بدون subclass | حل CA-C-008 — C10 در دو Context (R17 و R20) از یک سطح عمل می‌کند |
| **C-DES-002** | spatial_scope برای C10 = Content Metadata (National/Regional/Provincial/Local) | ابهام Transnational در CA-C-017 — مکانیزم موقت CA-C-D011 |
| **C-DES-003** | جغرافیای C9 = Derived — بدون رابطه مستقیم | Geography از موجودیت‌های متصل computed می‌شود |

### تصمیمات طراحی (CA-C-D)

| کد | تصمیم | Revisable |
|----|--------|-----------|
| CA-C-D001 | لنگر جغرافیایی: R09 الزامی برای C1/C2؛ R06 الزامی برای C5/C6/C7/C10؛ R08 انحصاری برای C8 | Yes |
| CA-C-D002 | بدون فیلد spatial_scope برای C1/C2/C5/C6/C7/C8/C12/C13 | Yes |
| CA-C-D003 | D2 فقط روی C1 و C10 — instance-level only | No (تا تغییر R20 IA) |
| CA-C-D004 | Deferred Rendering: R07/R10 داده از امروز — رندر لینک پس از انتشار C9 | Yes — بسته شد با انتشار C9 در Part 2B |
| CA-C-D005 | Aggregation دولایه: پیش‌فرض (Classification) + شرطی (جغرافیایی با آزمون D-007) | Yes |
| CA-C-D006 | Schema = Baseline — Binding در Phase 4 | Yes |
| CA-C-D007 | dance_type برای C7 = Controlled Editorial Vocabulary | Yes — با IA Revision منسوخ می‌شود |
| CA-C-D008 | Cross-Entity Content Reference: فاقد رابطه IA → Text (نام + URL) در Content Layer | Yes |
| CA-C-D009 | R08 انحصاری برای C8 — R06 ممنوع | No (تا تغییر R08 IA) |
| CA-C-D010 | Derived Geography C9: computed در render از R06/R09 موجودیت‌های متصل | Yes |
| CA-C-D011 | Transnational trigger موقت: داده R27 مصوب | Yes — با پاسخ CA-C-017 جایگزین |
| CA-C-D012 | R14 fields اختیاری — مکان خارجی فقط prose (P-09) | Yes |
| CA-C-D013 | C13 بدون Relation Outbound — پایتخت/توالی در Content Layer | No (تا تغییر IA) |
| CA-C-D014 | external_reference_uri: Content Layer → sameAs در markup | Yes |
| CA-C-D015 | Field-list Normalization: تبدیل فهرست‌های prose Required/Optional به جدول ۳ستونه هم‌قالب با سایر Categoryها — برای استخراج‌پذیری ماشینی یکنواخت در Phase 4 | Yes |

---

## بخش ۴ — Findings Log — کامل Category C

| Finding ID | موضوع | سطح | وضعیت |
|-----------|--------|------|--------|
| CA-C-001 | C7 فاقد Classification | بالا | Open — IA Revision Request (FIR-C-001 مرتبط) |
| CA-C-002 | C10 Spatial Scope = Range | بالا | Mitigated — C-DES-002 + CA-C-D011 |
| CA-C-003 | R25 فاقد C — شکاف زمانی | پایین | Accepted — calendar_timing در C10 |
| CA-C-004 | C9 فاقد مکانیزم جغرافیایی مستقیم | بالا | Resolved — C-DES-003 + CA-C-D010 |
| CA-C-005 | C13 فاقد Outbound + Country-Wide ضمنی | متوسط | Accepted — Reference Entity خالص |
| CA-C-006 | C5 ∉ R09 | متوسط | Accepted — فقط R06 (محافظه‌کارانه) |
| CA-C-007 | Boundary C/A فاقد معیار عملیاتی | متوسط | Editorial Guideline الزامی |
| CA-C-008 | C10 دوگانگی class/instance | بحرانی | **Resolved** — C-DES-001 |
| CA-C-009 | R20: C→D1/D3/D4 صریحاً منع نشده | متوسط | Accepted — editorial discipline |
| CA-C-010 | D-020 فاقد Structural Enforcement | متوسط | Phase 4 item |
| CA-C-011 | ناسازگاری سند استخراج (7.3 vs Ambiguity 6) | پایین | Corrected در این سند |
| CA-C-012 | ناسازگاری برچسب C3/C4 در تسک | — | Resolved — Option A |
| CA-C-013 | C2 ∉ R20 — ICH غذایی | متوسط | **Deferred** — FIR-C-001 |
| CA-C-014 | C6 ∉ R20 — ICH موسیقایی | متوسط | **Deferred** — FIR-C-001 |
| CA-C-015 | C8 ∉ R07 — زبان↔قوم | متوسط | **Resolved** — Content Layer دائمی |
| CA-C-016 | برچسب انحرافی Part 2B | — | Resolved — تأیید مالک |
| CA-C-017 | Transnational ∉ C-DES-002 | متوسط | Open — CA-C-D011 موقت |

---

## بخش ۵ — Content Architecture — ۱۰ موجودیت

---

### C1 — Handicraft | صنایع دستی

**هدف:** موجودیت دوماهیتی — هم practice هم محصول مادی. تنها C entity دارای هر دو R06 و R09. تنها موجودیت در Part 1 با مجوز D2.
**Spatial Scope:** Area | **Classifications:** 8 (Carpet/Kilim · Tilework · Miniature · Inlaid Work · Filigree · Pottery · Traditional Textile · Block Print)

**Page Template:** Identity Block → UNESCO Conditional → Overview (500+) → Craft & Technique → Where Produced (R09) → Ethnic Connection (R07) → Editorial A2 references

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| classification | Classification | طبق Classificationهای رسمی IA |
| produced_in | Relation → G | R09, ≥1 |
| overview_text | LongText | 500+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| practiced_in | Relation → G | R06 — فقط اگر ≠ produced_in |
| associated_ethnicity | Relation → C9 | R07 — C9 link |
| intangible_heritage_designation | Boolean | — |
| intangible_heritage_year | Number | آینه D-024 |
| intangible_heritage_criteria | Text | آینه D-024 |
| intangible_heritage_status | Enum | Active / Under Review / Threatened — آینه D-024 |
| name_local | Text | — |
| materials | Text | — |
| technique_summary | Text | — |
| external_reference_uri | URI | CA-C-D014 — sameAs |
| featured_image_gallery | Image[] | — |

**Internal Links:** R09→G3/G5/G7 (primary) · R06→G (secondary) · R07→C9 · R20→D2 (conditional) · Content→A2 (editorial)

**Aggregation Pages:** «صنایع‌دستی ایران» Hub (default) · ۸ Classification pages (default) · «صنایع‌دستی [استان]» (conditional D-007) · «صنایع‌دستی یونسکو» (default — designation filter)

**SEO:** "Persian carpet weaving" · "Isfahan tilework" · "Iranian pottery" · «خاتم‌کاری شیراز»
**AEO:** What is [craft]? · Where made? · UNESCO status? · Materials? · Which ethnic group?
**Schema:** `@type: Thing / additionalType: TraditionalHandicraft` + sameAs (UNESCO URI)
**MVC:** name·classification·produced_in·overview(500+)·D2 status·Schema·hero_image

---

### C2 — Local Food / Dish | غذای محلی

**هدف:** کل آشپزی و نوشیدنی سنتی ایران — C3 (Local Beverage) از طریق Classification پوشش داده می‌شود. **C2 ∉ R20** — هیچ designation. ICH غذایی: پوشش editorial فقط (FIR-C-001 Deferred).
**Spatial Scope:** Point / Area | **Classifications:** 8 (Soup/Stew · Khoresh · Kebab · Traditional Bread · Sweet/Confection · Preserve/Pickle · Ceremonial Food · Local Beverage)

**Page Template:** Identity Block → Overview (400+) → Ingredients & Character → Recipe (conditional) → Where to Taste (R09) → Ceremonial Context (conditional/Content Layer) → Ethnic Connection (R07)

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| classification | Classification | طبق Classificationهای رسمی IA |
| produced_in | Relation → G | R09, ≥1 |
| overview_text | LongText | 400+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| practiced_in | Relation → G | R06 |
| associated_ethnicity | Relation → C9 | R07 |
| name_local | Text | — |
| main_ingredients | Text | — |
| ritual_context_reference | Text | Text — Ceremonial Food only |
| recipe_content | LongText | →Schema Recipe |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:** designation (C2 ∉ R20) · best_season · هیچ Relation به C10

**Internal Links:** R09→G3/G5/G7 · R06→G (secondary) · R07→C9 · Content→C10 (Text, conditional)

**Aggregation Pages:** «آشپزی ایرانی» Hub · ۸ Classification pages · «نوشیدنی‌های سنتی ایران» (جایگاه C3) · «غذاهای [استان]» (conditional)

**SEO:** "Persian food guide" · "Ghormeh Sabzi" · «غذاهای محلی گیلان» | Recipe Schema → Rich Result
**AEO:** What is [dish]? · Where from? · Ingredients? · What do Iranians drink?
**Schema:** `@type: Thing / additionalType: TraditionalDish` + Recipe (conditional)
**MVC:** name·classification·produced_in·overview(400+)·main_ingredients·Schema·hero_image

---

### C5 — Traditional Costume | لباس سنتی

**هدف:** پوشاک سنتی اقوام. R10 (WORN_BY→C9) منحصربه‌فردترین رابطه Category C. **C5 ∉ R09** (CA-C-006) · **C5 ∉ R20**.
**Spatial Scope:** Multi-Area | **Classifications:** 4 (Men's · Women's · Ceremonial · Wedding)

**Page Template:** Identity Block → Overview (400+) → Garment Components → Ethnic Identity R10 (primary) → Where Worn R06

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| classification | Classification | طبق Classificationهای رسمی IA |
| worn_by | Relation → C9 | R10, ≥1 C9 |
| practiced_in | Relation → G | R06, ≥1 |
| overview_text | LongText | 400+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| associated_ethnicity | Relation → C9 | R07 |
| name_local | Text | — |
| fabric_type | Text | — |
| occasion | Text | — |
| garment_components | Text | — |
| external_reference_uri | URI | CA-C-D014 — sameAs |
| featured_image_gallery | Image[] | — |

**Internal Links:** R10→C9 (primary identity link) · R06→G3/G5 · R07→C9 · Content→A7 (editorial)

**Aggregation Pages:** «پوشاک سنتی ایران» Hub · ۴ Classification pages · «پوشاک اقوام [قوم]» (conditional — post C9)

**SEO:** "Iranian traditional clothing" · "Kurdish dress" · «لباس محلی بختیاری»
**AEO:** Traditional dress of [ethnic group]? · What worn at wedding? · What is [costume]?
**Schema:** `@type: Thing / additionalType: TraditionalCostume`
**MVC:** name·classification·worn_by(C9)·practiced_in·overview(400+)·garment components·Schema·hero_image

---

### C6 — Traditional Music | موسیقی محلی

**هدف:** سنت‌های موسیقایی ایران. **C6 ∉ R20** — ICH موسیقایی (ردیف، دوتار) فقط editorial (FIR-C-001 Deferred). فقط R06 + R07.
**Spatial Scope:** Multi-Area | **Classifications:** 4 (Classical/Dastgah · Regional/Ethnic · Ceremonial · Traditional Instrument)

**Page Template:** Identity Block → Overview (400+) → Musical Character → Where Practiced (R06) → Ethnic R07 → Ceremonial Context (Content Layer) → Audio sample

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| classification | Classification | طبق Classificationهای رسمی IA |
| practiced_in | Relation → G | R06, ≥1 |
| overview_text | LongText | 400+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| associated_ethnicity | Relation → C9 | R07 |
| name_local | Text | — |
| related_instruments_reference | Text[] | — |
| ritual_context_reference | Text | — |
| audio_sample | Audio | — |
| external_reference_uri | URI | CA-C-D014 — sameAs |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:** designation (C6 ∉ R20 — FIR-C-001) · best_season · هیچ Relation به C10/C12/E

**Internal Links:** R06→G3/G5/G7 · R07→C9 · Content→C6 Instrument instances · Content→C10 (Ceremonial)

**Aggregation Pages:** «موسیقی سنتی ایران» Hub · ۴ Classification pages · «موسیقی [استان]» (conditional) · «موسیقی [قوم]» (conditional — post C9)

**SEO:** "Persian classical music" · "Dastgah" · "Iranian traditional instruments"
**AEO:** What is Radif? · What are traditional Iranian instruments? · Music of [region]?
**Schema:** `@type: Thing / additionalType: TraditionalMusic` + audio
**MVC:** name·classification·practiced_in·overview(400+)·Schema·hero_image

---

### C7 — Traditional Dance | رقص محلی

**هدف:** رقص‌های سنتی اقوام. فاقد Classification رسمی (CA-C-001 Open) — Controlled Vocabulary (CA-C-D007). فقط R06 + R07.
**Spatial Scope:** Multi-Area | **Classification:** بدون Classification رسمی — dance_type: Controlled Vocabulary

**Page Template:** Identity Block (با dance_type) → Overview (400+) → Movement & Form → Where Practiced (R06) → Ethnic R07 → Ceremonial Context (Content Layer) · Video sample

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| dance_type | Controlled Vocabulary | Controlled Vocab: Regional-Folk/Ceremonial/Ritual/Wedding |
| practiced_in | Relation → G | R06, ≥1 |
| overview_text | LongText | 400+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| associated_ethnicity | Relation → C9 | R07 |
| name_local | Text | — |
| formation | Text | — |
| occasion | Text | — |
| music_accompaniment_reference | Text[] | — |
| ritual_context_reference | Text | — |
| video_sample | Video | — |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:** classification رسمی (CA-C-001) · designation · best_season · هیچ Relation به C6/C10/E

**Internal Links:** R06→G3/G5/G7 · R07→C9 · Content→C6 (music) · Content→C10 (ritual)

**Aggregation Pages:** «رقص‌های محلی ایران» Hub (تنها صفحه default) · «رقص‌های [dance_type]» (conditional) · «رقص‌های [قوم]» (conditional — post C9)

**SEO:** "Iranian folk dance" · "Kurdish dance" · «رقص بختیاری»
**AEO:** What is [dance]? · Which people perform [dance]? · Dance at Iranian weddings?
**Schema:** `@type: Thing / additionalType: TraditionalDance` + video
**MVC:** name·dance_type·practiced_in·overview(400+)·formation·Schema·hero_image

---

### C8 — Language / Dialect | زبان / گویش

**هدف:** لایه زبانی ایران. **تنها کاربر R08** — اهداف محدود به G3/G5 (بدون G7). **C8 ∉ R07** — زبان↔قوم = Content Layer (CA-C-015 دائمی). type بومی Schema.org.
**Spatial Scope:** Multi-Area | بدون Classification رسمی

**Page Template:** Identity Block → Overview (400+) → Where Spoken (R08) → Dialects & Variants → Language & People (Content Layer) → Audio sample

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| spoken_in | Relation → G3/G5 | R08, ≥1 G3/G5 |
| language_family | Text | — |
| overview_text | LongText | 400+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_endonym | Text | — |
| script | Text | — |
| speaker_estimate_iran | Number | همراه سال + منبع |
| official_language | Boolean | — |
| dialect_variants | Text[] | — |
| ethnicity_reference | Text[] | Text[] — Content Layer |
| audio_sample | Audio | — |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:** practiced_in/R06 (ممنوع — CA-C-D009) · Relation به C9 (CA-C-015) · designation · R27 (C10 Transnational منحصراً)

**Internal Links:** R08→G3/G5 (تنها link graph) · Content→C9 (Text, دائمی) · Content→C12 (prose)

**Aggregation Pages:** «زبان‌ها و گویش‌های ایران» Hub (AEO بالا) · «زبان‌های [خانواده زبانی]» (conditional) · «زبان‌های [استان]» (conditional)

**SEO:** "languages spoken in Iran" · "Kurdish language Iran" · "Gilaki language"
**AEO:** What languages spoken in Iran? · How many speak [language]? · What script? · Where spoken?
**Schema:** `@type: Language` ← type بومی کامل — مزیت AEO ساختاری
**MVC:** name_en+name_fa(+endonym)·spoken_in·language_family·overview(400+)·Schema.Language·hero_image

---

### C9 — Ethnicity / Culture Group | قوم / فرهنگ

**هدف:** Central Hub — Inverse Hub (صفر Outbound). تمام محتوا از Inbound (R07/R10 ←). جغرافیا Derived (C-DES-003 + CA-C-D010).
**Spatial Scope:** Multi-Area | بدون Classification رسمی

**Page Template:** Identity Block → Overview (600+) → Where They Live (Derived — conditional ≥3 entities) → Handicrafts (R07←C1) → Cuisine (R07←C2) → Costume (R10←C5) → Music/Dance (R07←C6/C7) → Rituals (R07←C10) → Language (Content Layer)

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| overview_text | LongText | 600+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**دروازه انتشار:** حداقل ۳ موجودیت فرهنگی متصل زنده — Hub خالی منتشر نمی‌شود.

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| alternative_names | Text[] | — |
| population_estimate | Number | همراه سال + منبع |
| language_reference | Text[] | Text[] — Content Layer, CA-C-015 |
| external_reference_uri | URI | CA-C-D014 |
| featured_image_gallery | Image[] | — |

**فیلدهای عمداً غایب:** هر فیلد جغرافیایی ذخیره‌شده (C-DES-003) · designation · best_season · هر Relation به C8/C12

**Internal Links:** R07←C1/C2/C5/C6/C7/C10 (Inbound) · R10←C5 (Inbound) · Content↔C8 (دوطرفه متنی) · Derived→G3/G5 (computed, بدون edge)

**Aggregation Pages:** «اقوام و فرهنگ‌های ایران» Hub · «اقوام [استان]» (conditional — post Phase 4)

**SEO:** "Kurds in Iran" · "Bakhtiari people" · «قوم بلوچ» | topical hub برای تجمیع Authority
**AEO:** What ethnic groups in Iran? · Who are [group]? · Where do [group] live? · [Group] culture?
**Schema:** `@type: Thing / additionalType: EthnicGroup` + sameAs (Wikidata)
**MVC:** name_en+name_fa·overview(600+)·≥3 entities متصل·Schema·hero_image

---

### C10 — Ritual / Ceremony | آیین / مراسم سنتی

**هدف:** پیچیده‌ترین C entity — ۶ Outbound + ۲ Inbound. تنها C entity دارای مجوز D2 در این Part. طبق C-DES-001: هر element نام‌دار (نوروز، یلدا) = Instance Node. مرز با E: C10=پدیده پایدار؛ E=رخداد زمان‌دار.
**Spatial Scope:** Point to Transnational (per instance — C-DES-002 + CA-C-D011)
**Classifications:** 5 (Nowruz Rituals · Mourning · Seasonal Celebration · Traditional Wedding · Ethnic Ritual)

**Page Template:** Identity Block (با spatial_scope + calendar_timing) → UNESCO Conditional → International Recognition (conditional R27/CA-C-D011) → Overview (600+) → Ritual Elements → Regional Variations → Ceremonial Foods (Content→C2) → Figure R13 (conditional) → Events Inbound (deferred E) → Travel Impact R23 (فعال — CA-P published)

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| classification | Classification | طبق Classificationهای رسمی IA |
| spatial_scope | Enum | Enum: National/Regional/Provincial/Local |
| calendar_timing | Text | — |
| practiced_in | Relation → G | R06, ≥1 |
| overview_text | LongText | 600+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| associated_ethnicity | Relation → C9 | R07→C9 |
| associated_figure | Relation → C12 | R13→C12 |
| intangible_heritage_designation | Boolean | Boolean — instance only |
| intangible_heritage_year | Number | آینه D-024 |
| intangible_heritage_criteria | Text | آینه D-024 |
| intangible_heritage_status | Enum | Active / Under Review / Threatened — آینه D-024 |
| external_recognition_uris | URI[] | URI[] — R27 + CA-C-D011 |
| travel_impact | Relation → P6 | R23→P6 — فعال، CA-P-FINAL منتشر شده |
| name_local | Text[] | — |
| ceremonial_food_reference | Text[] | Text[] — D008 |
| external_reference_uri | URI | CA-C-D014 |

**فیلدهای عمداً غایب:** best_season (≠ calendar_timing) · مقدار «Transnational» در spatial_scope (CA-C-017 Open)

**Internal Links:** R06→G · R07→C9 · R13→C12 · R20→D2 (conditional) · R27→External (conditional) · R23→P6 (فعال) · R17/R18←E (deferred) · Content→C2 (D008)

**Aggregation Pages:** «آیین‌های سنتی ایران» Hub · ۵ Classification pages · «آیین‌های UNESCO» (designation filter) · «آیین‌های [استان]» (conditional)

**SEO:** "Nowruz" · "Yalda night" · "Iranian wedding" — بالاترین search volume Category C
**AEO:** What is Nowruz? · Is Nowruz UNESCO? · How celebrate [ceremony]? · Foods at [ceremony]?
**Schema:** `@type: Event` + eventSchedule (P1Y) + sameAs (R27 URIs + UNESCO URL)
**MVC:** name·classification·spatial_scope·calendar_timing·practiced_in·overview(600+)·D2 status·Schema·hero_image

---

### C12 — Historical Figure | شخصیت تاریخی

**هدف:** مشاهیر تاریخی ایران. R11→C13 الزامی. R14 اختیاری (CA-C-D012 — مکان خارجی فقط prose). C12 عمدتاً Target روابط (R12←/R13←/R18←).
**Spatial Scope:** Point | **Classifications:** 6 (Poet · Scientist/Scholar · King/Ruler · Military Commander · Artist · Philosopher/Mystic)

**Page Template:** Identity Block (R11+R14 — با prose fallback) → Biography (700+) → Works & Legacy → Life & Places (R14 ±prose) → Associated Places (R12←/R13←) → Commemorations (deferred E)

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| classification | Classification | طبق Classificationهای رسمی IA |
| historical_period | Relation → C13 | R11→C13 |
| overview_text | LongText | 700+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| born_in | Relation → G5 | R14→G5 |
| died_in | Relation → G5 | R14→G5 |
| buried_in | Relation → A2 | R14→A2 |
| birth_year_approx | Text | تقریبی — قرن/بازه مجاز؛ نرمال‌سازی عددی در Phase 4 |
| death_year_approx | Text | تقریبی — قرن/بازه مجاز؛ نرمال‌سازی عددی در Phase 4 |
| alternative_names | Text[] | هم‌قالب C9 |
| major_works | Text | فهرست prose |
| external_reference_uri | URI | CA-C-D014 — Wikidata |

**فیلدهای عمداً غایب:** designation (C12 ∉ R20) · associated_ethnicity Relation (C12 ∉ R07) · best_season

**Internal Links:** R11→C13 · R14→G5/A2 (optional) · R12←A2/A12 (Inbound) · R13←A2/A3/A12/G5/C10 (Inbound) · R18←E (deferred)

**Aggregation Pages:** «مشاهیر تاریخی ایران» Hub · ۶ Classification pages («شاعران ایران» — بالاترین SEO value)

**SEO:** "Hafez" · "Ferdowsi" · "Avicenna" · «عمر خیام» — evergreen · جهانی
**AEO:** Who was [figure]? · When lived? · Where buried? · What wrote/built? · What period?
**Schema:** `@type: Person` + birthPlace + hasOccupation + sameAs (Wikidata) ← type بومی کامل
**MVC:** name·classification·historical_period(C13)·overview(700+)·years·major_works·Schema.Person·hero_image

---

### C13 — Historical Period / Dynasty | دوره / سلسله تاریخی

**هدف:** Reference Entity خالص — صفر Outbound. ستون زمانی کل پروژه. همه A/C12 با R11 به آن اشاره می‌کنند. پایتخت/توالی = Content Layer (CA-C-D013).
**Spatial Scope:** Country-Wide (G1 ضمنی — CA-C-005 موقت) | بدون Classification رسمی

**Page Template:** Period Identity Block (با capital_reference + succession Content Layer) → Overview (700+) → Key Monuments (R11←A) → Key Figures (R11←C12) → Timeline Context → Commemorations (deferred E)

**Required Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| name_en | Text | — |
| name_fa | Text | — |
| start_year_approx | Text | تقریبی — قرن/بازه مجاز؛ نرمال‌سازی عددی در Phase 4 |
| end_year_approx | Text | تقریبی — قرن/بازه مجاز؛ نرمال‌سازی عددی در Phase 4 |
| overview_text | LongText | 700+ کلمه |
| hero_image | Image | — |
| meta_title | Text | — |
| meta_description | Text | — |

**دروازه انتشار:** حداقل ۳ بنا/محوطه لینک‌شده (R11←A) + حداقل ۲ شخصیت (R11←C12 یا معافیت ادیتوریال)

**Optional Fields:**

| فیلد | نوع | یادداشت |
|------|-----|----------|
| alternative_names | Text[] | هم‌قالب C9 |
| capital_reference | Text[] | Text[] — نام+URL به G5 — CA-C-D013 |
| predecessor_reference | Text | Text — CA-C-D013 |
| successor_reference | Text | Text — CA-C-D013 |
| external_reference_uri | URI | CA-C-D014 |

**فیلدهای عمداً غایب:** هر Relation Outbound (C13 در Sources هیچ رابطه‌ای نیست — CA-C-D013) · designation · فیلد جغرافیایی

**Internal Links:** R11←A2/A3/A12 (Inbound — فعال) · R11←C12 (Inbound) · R18←E (deferred) · Content→G5 (capital) · Content→C13 (succession)

**Aggregation Pages:** «دوره‌های تاریخی ایران» Timeline Hub — AEO بالا

**SEO:** "Achaemenid Empire" · "Safavid dynasty" · «سلسله ساسانی» — evergreen · جهانی
**AEO:** When did [dynasty] rule? · Capital of [dynasty]? · Monuments of [period]? · Famous figures? · What came after?
**Schema:** `@type: Thing / additionalType: HistoricalPeriod` + PropertyValue (startYear/endYear) + sameAs
**MVC:** name·start_year·end_year·overview(700+)·≥3 monuments·≥2 figures·Schema·hero_image

---

### C11 — Local Legends | داستان‌های محلی

**وضعیت:** Content Type — **نه entity**. بدون template، بدون fields، بدون صفحه مستقل.
**مبنا:** IA Future Scope: "Local Legends / C11 — derived from C12, G5, C9" + DC-R-001 (Non-Revisable).
**پوشش محتوایی:** در prose صفحات G5، C12، C9 به‌صورت editorial section.

---

## بخش ۶ — Cross-Category Consistency Review — نهایی

### C ↔ G

| بررسی | نتیجه |
|--------|--------|
| اهداف R06/R09 (G3/G5/G7) در CA-G طراحی شده | ✅ |
| اهداف R08 (G3/G5) در CA-G طراحی شده | ✅ |
| هیچ LOCATED_IN روی C entities | ✅ |
| Cannibalization aggregation C vs بخش Culture صفحات G | ✅ — CA-C-D005 |
| تغذیه Derived Geography C9 | ✅ — C-DES-003/D010 |

### C ↔ A

| بررسی | نتیجه |
|--------|--------|
| هیچ رابطه C→A خارج از R14 (C12→A2) | ✅ |
| Boundary practice/مکان | ✅ — Editorial Guideline |
| Cross-references A2/A7 در Content Layer | ✅ — D008 |
| آرامگاه (A2) ≠ شخصیت (C12) | ✅ — P-02 |

### C ↔ D

| بررسی | نتیجه |
|--------|--------|
| D2 فقط C1/C10 instances | ✅ — D-020/CA-C-D003 |
| C2/C5/C6/C7/C8/C9/C12/C13 بدون designation | ✅ |
| FIR-C-001 (ICH gap) | ⚠ Deferred |
| Properties D-024 روی R20 | ✅ |
| سازگاری با D2 Hub در CA-D | ✅ |

### هشت بررسی استاندارد

| بررسی | نتیجه |
|--------|--------|
| Entity Inflation | ✅ 0 — C3/C4/C11 رد شدند |
| Relationship Inflation | ✅ 0 — همه cross-entity در Content Layer |
| Duplicate Modeling | ✅ — C13≠A13؛ C10≠E؛ آرامگاه≠شخصیت |
| Circular Dependencies | ✅ — همه مسیرها خاتمه‌پذیر |
| Geography First (P-01) | ✅ — R06/R08/R09/R14؛ C9 Derived؛ C13 Country-Wide |
| One Physical Place = One Primary Node (P-02) | ✅ — هیچ node مکانی ساخته نشد |
| AEO Readiness | ✅ — Identity Block ساختاریافته + ≥4 سؤال per entity |
| Schema Readiness | ✅ — 2 type بومی (Language، Person) + baseline سایرین |

---

## بخش ۷ — وضعیت نهایی Category C

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CATEGORY C — CULTURE & HERITAGE
CONTENT ARCHITECTURE FINAL STATUS
Phase 3 | ToPersia | IA-v1.2.1-FINAL Reference
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Entities Designed:            10 / 10    ✅
  C1   Handicraft             ✅  (R06+R09, D2-capable)
  C2   Local Food / Dish      ✅  (C3 absorbed, R09)
  C5   Traditional Costume    ✅  (R06+R10, identity link)
  C6   Traditional Music      ✅  (R06, 4 Classifications)
  C7   Traditional Dance      ✅  (R06, Controlled Vocab)
  C8   Language / Dialect     ✅  (R08 exclusive, Schema:Language)
  C9   Ethnicity / Culture    ✅  (Inverse Hub, Derived Geo)
  C10  Ritual / Ceremony      ✅  (Instance Nodes, D2-capable)
  C12  Historical Figure      ✅  (Schema:Person, R14 optional)
  C13  Historical Period      ✅  (Pure Reference, 0 Outbound)
  C11  Local Legends          ✅  (Content Type — no template)

Decisions Logged:             18 total   ✅
  Pre-approved:              C-DES-001/002/003
  Design:                    CA-C-D001 through CA-C-D015

Findings:                     17 total   ✅
  Resolved:                  CA-C-002,004,008,012,015,016
  Open (non-blocking):       CA-C-001,003,005,006,009,010,017
  Deferred (FIR):            CA-C-013, CA-C-014

Future IA Review Items:       2
  FIR-C-001                  R20 Sources: C2+C6 instances
  FIR-C-002                  R07 Sources: C8

Architecture Freeze:          INTACT ✅
New Entities:                 0       ✅
New Relationships:            0       ✅
New Constraints:              0       ✅

Cross-Category:
  C ↔ G                       PASS    ✅
  C ↔ A                       PASS    ✅
  C ↔ D                       PASS    ✅
  8 Standard Checks           PASS    ✅

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STATUS: ✅ COMPLETE — FINAL
Category C Content Architecture is finalized.
All 10 entities designed. All decisions recorded.
Architecture Freeze intact throughout.

NEXT: CA-E-EXTRACTION-v1.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

*ToPersia CA-C-v1.2-FINAL*
*Phase 3: Content Architecture — Category C*
*مرجع: IA-v1.2.1-FINAL (Frozen)*
*وضعیت: COMPLETE — FINAL*
