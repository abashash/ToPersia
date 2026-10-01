# ToPersia Information Architecture
## IA-v1.2.1-FINAL
## Single Source of Truth — Phase 2

---

## 1. Document Metadata

| Field | Value |
|-------|-------|
| **Project Name** | ToPersia |
| **Document Title** | Information Architecture — Master Reference |
| **Version** | 1.2.1-FINAL |
| **Status** | APPROVED AND FROZEN |
| **Phase** | Phase 2 — Information Architecture |
| **Validation** | Passed — all counts, references, and decisions verified |
| **Scope** | Conceptual Information Architecture — Phase 1 product |
| **Next Phase** | Phase 3 — Content Architecture |

### Validation Summary

| Check | Result |
|-------|--------|
| Entity counts per category | ✓ Verified |
| Total entity count (42) | ✓ Verified |
| Relationship count (27) | ✓ Verified |
| Broken reference check | ✓ No broken references |
| Decision log alignment | ✓ All 38 entries verified |
| Constraint count (10) | ✓ Verified |
| Inheritance rules (5) | ✓ Verified |
| Classification items (70) | ✓ Verified |
| Freeze status consistency | ✓ Verified |

### Version History

| Version | Key Changes |
|---------|-------------|
| v1.0 | Categories G, A, D defined |
| v1.1 | Categories C, E, P added |
| v1.2 | Relationship Architecture R01–R26 added |
| v1.2.1 | C11 removed from R13 · R27 added · A2 restored in R15 with venue_eligible constraint · P7 dual-level defined · R05 generalized · D-028 removed |

---

## 2. Architectural Principles

### P-01 — Geography First
Every non-geographic entity must connect to at least one
geographic reference point in Category G.

Spatial Scope values:
- **Point** — single coordinates (A2, A3, A12, G5)
- **Area** — defined boundary (A9, D3/D4 instances, G11)
- **Multi-Area** — multiple regions (C8, C9, P9)
- **Country-Wide** — entire Iran (P1, P2, C13)
- **Transnational** — beyond Iran's borders (Nowruz C10)

No entity may exist in the Knowledge Graph without a
geographic anchor.

### P-02 — One Physical Place = One Primary Node
One physical location has exactly one primary node.
Designations, classifications, and roles are expressed
as relationships or attributes — not additional nodes.

### P-03 — Entity Inflation Prevention
Three conditions required simultaneously for entity status:
1. Independent search volume — users search for it directly
2. Unique information — data not capturable in any existing entity
3. Distinct relationships — connects differently than parent entity

If any condition fails → Classification, Attribute, or Content Type.

### P-04 — Layered Model
Structure: Abstract parent → concrete child entities →
classifications → attributes.

### P-05 — Conceptual Flexibility
All decisions are conceptual. Revision requires documented
evidence of a real implementation blocker.

### P-06 — Single Source of Truth
One authoritative data location per fact. Derived
relationships are computed, not stored as independent facts.

### P-07 — Abstract Entity Rule
A1 (Tourist Attraction) is abstract. Never directly
instantiated. Every attraction carries a concrete child
type or a classification under A1.

### P-08 — Designation as Qualified Relationship
Designations are qualified relationships with metadata
properties on the relationship itself — never new nodes.

### P-09 — Transnational Entity Rule
Entities with Spatial Scope = Transnational:
- Primary Jurisdiction: G1 (Iran)
- External recognition: R27 → External URIs only
- No new G1 nodes for other countries within ToPersia

### P-10 — No Designation Event Node
Every designation act is a relationship. No exceptions.

### P-11 — Architecture Loop Prevention
Debates exceeding two iterations without resolution
default to Classification status.

---

## 3. Category Model

---

### Category G — Geography
**13 entities | Foundation layer | G1 = root node**

| Code | Entity (EN) | Entity (FA) | Note |
|------|-------------|-------------|------|
| G1 | Country | کشور | Root — no LOCATED_IN |
| G2 | Geographic Region | منطقه جغرافیایی | Classification: Natural/Historical/Cultural/Climate |
| G3 | Province | استان | Most referenced entity in system |
| G4 | County | شهرستان | Infrastructure Entity — no content pages for most instances |
| G5 | City | شهر | Highest SEO and content priority |
| G6 | District / Neighborhood | محله / منطقه شهری | Only when independent tourism identity exists |
| G7 | Village | روستا | Carries Tourism Target Village classification when applicable |
| G8 | Island | جزیره | May contain G5, G7, A9 |
| G9 | Mountain / Range | کوه / رشته‌کوه | |
| G10 | Water Body | پهنه آبی | Sea, lake, river, wetland |
| G11 | Desert | کویر / بیابان | |
| G12 | Forest / Plain | جنگل / دشت | |
| G13 | Border Crossing | گذرگاه مرزی | Informational only |

---

### Category A — Attractions
**8 entities | A1 is abstract | Layered model**

| Code | Entity (EN) | Entity (FA) | Note |
|------|-------------|-------------|------|
| A1 | Tourist Attraction | جاذبه گردشگری | Abstract — P-07 applies — never instantiated directly |
| A2 | Historical Monument | بنای تاریخی | Attribute: venue_eligible (true/false) |
| A3 | Religious Site | مکان مذهبی | Dual audience: cultural tourists + pilgrims |
| A7 | Museum | موزه | Institutional entity — may reside within A2 |
| A9 | National Park / Protected Area | پارک ملی / منطقه حفاظت‌شده | ManagedArea subtype for internationally designated instances |
| A10 | Natural Feature | عارضه طبیعی | |
| A12 | Archaeological Site | محوطه باستانی | Distinct from A2: no standing structure required |
| A13 | Cultural Landscape | منظر فرهنگی | Composite Entity — may span multiple G3, A2, A10 |

---

### Category D — Designations & Official Titles
**4 Programme Nodes (Named Individuals) + 2 Attributes**

| Code | Entity (EN) | Entity (FA) | Type |
|------|-------------|-------------|------|
| D1 | UNESCO World Heritage | میراث جهانی یونسکو | Programme Node |
| D2 | UNESCO Intangible Heritage | میراث ناملموس یونسکو | Programme Node |
| D3 | UNESCO Global Geopark Programme | ژئوپارک جهانی | Programme Node — instances in Category G |
| D4 | UNESCO Biosphere Reserve Programme | ذخیره‌گاه زیست‌کره | Programme Node — instances in Category G |
| D5 | UNESCO Creative City | شهر خلاق یونسکو | Attribute on G5 |
| D6 | National Natural Monument | اثر طبیعی ملی | Attribute on natural entities |
| — | National Registration | ثبت ملی | Attribute on eligible entities |
| — | Free Zone | منطقه آزاد | Attribute on G5/G8 |

**Content model for D:**
- D1/D2/D3/D4/D6: One aggregation page each (Faceted + unique editorial content)
- D5: Section within G5 page + one aggregation page
- All designation data lives on target entity pages

---

### Category C — Culture & Heritage
**10 entities | C9 is central hub | Geography via PRACTICED_IN**

| Code | Entity (EN) | Entity (FA) | Spatial Scope |
|------|-------------|-------------|--------------|
| C1 | Handicraft | صنایع دستی | Area |
| C2 | Local Food / Dish | غذای محلی | Point / Area |
| C5 | Traditional Costume | لباس سنتی | Multi-Area |
| C6 | Traditional Music | موسیقی محلی | Multi-Area |
| C7 | Traditional Dance | رقص محلی | Multi-Area |
| C8 | Language / Dialect | زبان / گویش | Multi-Area |
| C9 | Ethnicity / Culture Group | قوم / فرهنگ | Multi-Area |
| C10 | Ritual / Ceremony | آیین / مراسم سنتی | Point to Transnational |
| C12 | Historical Figure | شخصیت تاریخی | Point |
| C13 | Historical Period / Dynasty | دوره / سلسله تاریخی | Country-Wide |

**Boundary rule — C vs A:**
If a concept has a physical location → Category A.
If a concept is a practice, knowledge, or tradition → Category C.

**Boundary rule — C10 vs E:**
C10 = persistent cultural phenomenon (Nowruz as concept).
E entities = time-bounded occurrences (Nowruz celebration 1404).

---

### Category E — Events
**4 entities | All connect to G via HELD_AT or HELD_IN**

| Code | Entity (EN) | Entity (FA) | Temporal Scope |
|------|-------------|-------------|----------------|
| E1 | Festival / Event | جشنواره / رویداد | Recurring or one-time — defined date |
| E3 | Sports Event | رویداد ورزشی | Recurring or one-time — defined date |
| E5 | Public Religious Ceremony | مراسم مذهبی عمومی | Annual — variable date (lunar/solar) |
| E6 | Seasonal / Natural Event | رویداد طبیعی / فصلی | Seasonal — recurring annually |

---

### Category P — Practical Travel Information
**7 entities | Informational only — no commercial services**

| Code | Entity (EN) | Entity (FA) | Spatial Scope |
|------|-------------|-------------|--------------|
| P1 | Visa & Entry Information | اطلاعات ویزا و ورود | Country-Wide |
| P2 | Currency & Payment | ارز و روش‌های پرداخت | Country-Wide |
| P4 | Health & Safety | سلامت و ایمنی | Country-Wide |
| P5 | Travel Etiquette | فرهنگ سفر و آداب | Country-Wide |
| P6 | Calendar & Public Holidays | تقویم و مناسبت‌ها | Country-Wide |
| P7 | Public Transport Information | حمل‌ونقل عمومی | Two levels: one entity page + G5 attributes |
| P9 | Tourism Route | مسیر گردشگری | Multi-Area |

**P7 dual-level model:**
Level 1: One P7 entity page — country-level transport overview.
Level 2: Transport attributes on each G5 — access details per city.
Individual routes are not independent entities.

**P9 vs Itinerary:**
P9 = persistent named route with geographic identity.
Itinerary = editorial content derived from P9 and other entities.
Itinerary is a Content Type, not an entity.

---

## 4. Classification Model

### Under A1 — Tourist Attraction
Persian Garden (باغ ایرانی) ·
Traditional Bazaar (بازار سنتی) ·
Coast / Beach (ساحل) ·
Modern Landmark (سازه مدرن) ·
Tourism Target Village (روستای هدف گردشگری) *[applied to G7]*

### Under A2 — Historical Monument
Caravanserai (کاروانسرا) ·
Historic Bridge (پل تاریخی) ·
Historic Tower (برج تاریخی) ·
Palace (کاخ) ·
Historic Bath (حمام تاریخی) ·
Historic School (مدرسه تاریخی) ·
Mausoleum (آرامگاه)

### Under A3 — Religious Site
Mosque (مسجد) ·
Church (کلیسا) ·
Temple (معبد) ·
Fire Temple (آتشکده) ·
Synagogue (کنیسه) ·
Imamzadeh (امامزاده) ·
Sufi Lodge (خانقاه)

### Under A7 — Museum
Art Gallery (گالری هنری) ·
House Museum (خانه موزه)

### Under A10 — Natural Feature
Waterfall (آبشار) ·
Cave (غار) ·
Spring (چشمه) ·
Canyon / Gorge (تنگه / دره) ·
Rock Formation (صخره) ·
Salt Formation / Kalut (کلوت)

### Under C1 — Handicraft
Carpet / Kilim (فرش و گلیم) ·
Tilework (کاشی‌کاری) ·
Miniature (مینیاتور) ·
Inlaid Work (معرق و خاتم) ·
Filigree (ملیله) ·
Pottery (سفالگری) ·
Traditional Textile (نساجی سنتی) ·
Block Print (چاپ قلمکار)

### Under C2 — Local Food / Dish
Soup / Stew (آش و سوپ) ·
Khoresh (خورش) ·
Kebab (کباب) ·
Traditional Bread (نان سنتی) ·
Sweet / Confection (شیرینی و حلوا) ·
Preserve / Pickle (ترشی و مربا) ·
Ceremonial Food (غذای مناسبتی) ·
Local Beverage (نوشیدنی محلی) *[C3 absorbed here]*

### Under C5 — Traditional Costume
Men's Traditional Dress (لباس مردانه) ·
Women's Traditional Dress (لباس زنانه) ·
Ceremonial Dress (لباس مناسبتی) ·
Traditional Wedding Dress (لباس عروسی)

### Under C6 — Traditional Music
Classical / Dastgah (موسیقی دستگاهی) ·
Regional / Ethnic Music (موسیقی محلی) ·
Ceremonial Music (موسیقی مراسمی) ·
Traditional Instrument (ساز سنتی)

### Under C10 — Ritual / Ceremony
Nowruz Rituals (آیین‌های نوروزی) ·
Mourning Ceremony (مراسم عزاداری) ·
Seasonal Celebration (جشن فصلی) ·
Traditional Wedding (مراسم عروسی سنتی) ·
Ethnic Ritual (آیین قومی)

### Under C12 — Historical Figure
Poet (شاعر) ·
Scientist / Scholar (دانشمند) ·
King / Ruler (پادشاه) ·
Military Commander (سردار) ·
Artist (هنرمند) ·
Philosopher / Mystic (فیلسوف و عارف)

### Under E1 — Festival / Event
Cultural / Arts Festival (جشنواره فرهنگی) ·
One-Time Cultural Event (رویداد فرهنگی یک‌بار) ·
Specialized Exhibition (نمایشگاه تخصصی) ·
Local Celebration (جشن محلی) ·
Conference (همایش)

### Under P9 — Tourism Route
Historical Route (مسیر تاریخی) ·
Natural Route (مسیر طبیعی) ·
Thematic Route (مسیر موضوعی)

---

## 5. Relationship Architecture

**27 total: 20 Primary · 4 Derived/Attribute · 3 Qualified**

---

### Spatial Relationships

**R01 — LOCATED_IN**
Definition: Entity resides within geographic boundary of another.
Direction: Child → Parent G.
Cardinality: Many-to-One (one primary per entity).
Sources: All entities except G1.
Targets: G1–G8.
Notes: Geography First enforcement. G1 has no LOCATED_IN.
Type: Primary.

**R02 — CONTAINS**
Definition: Geographic entity encompasses other entities.
Direction: Parent → Child.
Cardinality: One-to-Many.
Sources: G1–G6, G8, A9, A13, ManagedArea.
Targets: All entities except G1.
Notes: Derived — inverse of R01. R01 is authoritative.
Type: Derived.

**R03 — PASSES_THROUGH**
Definition: Route traverses geographic entities.
Direction: P9 → G.
Cardinality: Many-to-Many.
Sources: P9.
Targets: G3, G5, G7, G8.
Notes: Ordered sequence storable as relationship properties.
Type: Primary.

**R04 — BORDERS**
Definition: Two geographic entities share a boundary.
Direction: Bidirectional.
Cardinality: Many-to-Many.
Sources: G1, G3.
Targets: G1, G3.
Notes: Critical for GEO adjacent-region queries.
Type: Primary.

**R05 — CONNECTS**
Definition: Route or network links two geographic locations.
Direction: Route → Place.
Cardinality: Many-to-Many.
Sources: P9 (current). Future: F6, enhanced P7 (no revision needed).
Targets: G5, G8, G13.
Notes: General-purpose. Constraint C10 governs future use.
Type: Primary.

**R26 — LOCATED_IN_MANAGED_AREA**
Definition: Natural entity situated within a managed geographic zone.
Direction: A10/A9 → ManagedArea.
Cardinality: Many-to-One.
Sources: A10, A9.
Targets: ManagedArea (D3/D4 instances in Category G).
Notes: Exception to C3. Limited to managed areas only.
Type: Primary.

---

### Cultural Relationships

**R06 — PRACTICED_IN**
Definition: Cultural phenomenon traditionally practiced in a region.
Direction: C → G.
Cardinality: Many-to-Many.
Sources: C1, C2, C5, C6, C7, C10.
Targets: G3, G5, G7.
Notes: For cultural entities without fixed physical location.
Type: Primary.

**R07 — ASSOCIATED_WITH_ETHNICITY**
Definition: Cultural phenomenon associated with ethnic/cultural group.
Direction: C → C9.
Cardinality: Many-to-Many.
Sources: C1, C2, C5, C6, C7, C10.
Targets: C9.
Type: Primary.

**R08 — SPOKEN_IN**
Definition: Language or dialect spoken in a geographic area.
Direction: C8 → G.
Cardinality: Many-to-Many.
Sources: C8.
Targets: G3, G5.
Notes: Specific to C8. Use R06 for other cultural entities.
Type: Primary.

**R09 — PRODUCED_IN**
Definition: Material cultural product traditionally produced in a region.
Direction: C → G.
Cardinality: Many-to-Many.
Sources: C1, C2.
Targets: G3, G5, G7.
Notes: For material products. R06 for practices and arts.
Type: Primary.

**R10 — WORN_BY**
Definition: Traditional costume worn by a specific ethnic group.
Direction: C5 → C9.
Cardinality: Many-to-Many.
Sources: C5.
Targets: C9.
Type: Primary.

**R27 — ALSO_RECOGNIZED_IN**
Definition: Transnational cultural entity also recognized beyond Iran.
Direction: C10 Instance → External URI.
Cardinality: Many-to-Many.
Sources: C10 instances with Spatial Scope = Transnational.
Targets: External authority URIs (e.g. Wikidata).
Notes: Primary Jurisdiction always G1 (Iran). Constraint C8.
Type: Primary.

---

### Historical Relationships

**R11 — BELONGS_TO_PERIOD**
Definition: Entity originates from or is associated with a historical period.
Direction: A/C → C13.
Cardinality: Many-to-Many.
Sources: A2, A3, A12, C12.
Targets: C13.
Notes: Foundation of historical content chains.
Type: Primary.

**R12 — BUILT_BY / COMMISSIONED_BY**
Definition: Structure built or commissioned by a historical figure.
Direction: A → C12.
Cardinality: Many-to-Many.
Sources: A2, A12.
Targets: C12.
Notes: Only when builder is historically documented.
Type: Primary.

**R13 — ASSOCIATED_WITH_FIGURE**
Definition: Place or cultural entity has meaningful association with a historical figure.
Direction: A/C/G → C12.
Cardinality: Many-to-Many.
Sources: A2, A3, A12, G5, C10.
Targets: C12.
Notes: C11 removed from sources (DC-R-001).
Type: Primary.

**R14 — BORN_IN / DIED_IN / BURIED_IN**
Definition: Biographical location events for a historical figure.
Direction: C12 → G5 or A2.
Cardinality: Many-to-One per relationship type.
Sources: C12.
Targets: G5 (city), A2 (tomb/mausoleum).
Notes: Three named relationships sharing this entry.
Type: Primary.

---

### Event Relationships

**R15 — HELD_AT**
Definition: Event takes place at a specific named venue or site.
Direction: E → A or G.
Cardinality: Many-to-One.
Sources: E1, E3, E5.
Targets: A2 (venue_eligible: true only), A3, A7, A9, G7.
Notes: A2 requires venue_eligible attribute. Constraint C5.
Type: Primary.

**R16 — HELD_IN**
Definition: Event takes place within a geographic area.
Direction: E → G.
Cardinality: Many-to-One.
Sources: E1, E3, E5, E6.
Targets: G3, G5, G7.
Notes: Optional property on relationship: venue_name (String).
Type: Primary.

**R17 — INSTANCE_OF**
Definition: Time-bounded event is specific occurrence of persistent cultural phenomenon.
Direction: E → C10.
Cardinality: Many-to-One.
Sources: E5, E1.
Targets: C10.
Notes: One level only. C10 is not INSTANCE_OF anything. Constraint C7.
Type: Primary.

**R18 — CELEBRATES / COMMEMORATES**
Definition: Event honors a cultural phenomenon, figure, or historical period.
Direction: E → C.
Cardinality: Many-to-Many.
Sources: E1, E5.
Targets: C10, C12, C13.
Type: Primary.

**R19 — OCCURS_DURING**
Definition: Seasonal event occurs in a defined time of year.
Direction: E6 → Attribute.
Cardinality: Many-to-One.
Sources: E6.
Targets: Attribute: season (spring/summer/autumn/winter).
Notes: Stored as entity attribute — not a relationship.
Type: Attribute.

---

### Designation Relationships

**R20 — DESIGNATED_BY** *(Qualified Relationship)*
Definition: Entity has received official designation from a recognized authority.
Direction: Entity → D Programme Node.
Cardinality: Many-to-Many.
Sources: A2, A3, A9, A10, A12, A13, C1 instances, C10 instances, ManagedArea.
Targets: D1, D2, D3 Programme, D4 Programme.
Properties on relationship: designation_type · year · criteria ·
status · review_date · awarding_body.
Notes: P-10 — no new nodes for designation events. Constraint C6.
Type: Primary (Qualified).

**R21 — HAS_DESIGNATED**
Definition: Programme has recognized a set of entities.
Direction: D → Entity.
Cardinality: One-to-Many.
Sources: D1, D2, D3 Programme, D4 Programme.
Targets: All R20-eligible entities.
Notes: Derived — inverse of R20. R20 is authoritative.
Type: Derived.

---

### Travel Information Relationships

**R22 — APPLIES_TO**
Definition: Travel information entity is valid for a geographic scope.
Direction: P → G.
Cardinality: Many-to-Many.
Sources: P1, P2, P4, P5, P6.
Targets: G1, G3, G5.
Type: Primary.

**R23 — AFFECTS**
Definition: Cultural phenomenon or event has practical travel planning impact.
Direction: C10/E → P6.
Cardinality: Many-to-Many.
Sources: C10, E1.
Targets: P6.
Notes: Enables traveler alerts for holiday periods.
Type: Primary.

**R24 — REQUIRES_COMPLIANCE_WITH**
Definition: Visiting a site requires adherence to specific rules.
Direction: A3 → P5.
Cardinality: Many-to-One.
Sources: A3.
Targets: P5.
Type: Primary.

**R25 — BEST_VISITED_DURING**
Definition: Recommended season or period for visiting an entity.
Direction: Entity → Attribute.
Cardinality: Many-to-One.
Sources: A, G, E entities.
Targets: Attribute: best_season.
Notes: Stored as entity attribute — not a relationship.
Type: Attribute.

---

## 6. Constraints

**C1 — One Primary LOCATED_IN**
Every entity except G1 has exactly one primary LOCATED_IN.
In border cases, official administrative assignment is primary.

**C2 — No Circular Geography**
LOCATED_IN always points toward higher geographic hierarchy.
Circular paths are prohibited.
```
PROHIBITED: A → LOCATED_IN → B → LOCATED_IN → A
```

**C3 — LOCATED_IN Targets Category G Only**
Cross-category spatial relationships use R26, R15, or R16.
```
PROHIBITED: A2 → LOCATED_IN → A9
CORRECT:    A2 → LOCATED_IN → G5
```

**C4 — No Event Designation**
Category E entities may not be targets of R20.
```
PROHIBITED: E1 → DESIGNATED_BY → D1
```

**C5 — Venue Eligibility Rule**
A2 as target of R15 requires:
```
venue_eligible: true
```
Set per instance, not per class.
A2 without this attribute uses R16 → G5.

**C6 — No Designation Event Node**
No new node for any designation act.
All designation metadata stored as R20 relationship properties.
No exceptions.
```
PROHIBITED: Node("UNESCO listing of Persepolis 1979")
CORRECT:    Persepolis → DESIGNATED_BY {year:1979} → D1
```

**C7 — INSTANCE_OF is One Level Only**
R17 connects E entities to C10 only.
C10 entities are not INSTANCE_OF anything.
```
PROHIBITED: C10 → INSTANCE_OF → anything
CORRECT:    E5 → INSTANCE_OF → C10
```

**C8 — Transnational Primary Jurisdiction**
Entities with Spatial Scope = Transnational:
Primary Jurisdiction = G1 Iran.
Additional recognition via R27 → External URIs only.
```
PROHIBITED: Nowruz → LOCATED_IN → G1 (Afghanistan — undefined in ToPersia)
CORRECT:    Nowruz → LOCATED_IN → G1 (Iran)
            Nowruz → ALSO_RECOGNIZED_IN → wikidata:Q889
```

**C9 — DESIGNATED_BY Non-Inheritance**
A designation on a container does not apply to entities inside it.
Each entity requires independent designation.

**C10 — R05 CONNECTS General Usage**
R05 is general-purpose. Current authorized source: P9.
Future sources may use R05 without architecture revision.

---

## 7. Inheritance Rules

**I1 — LOCATED_IN Transitivity**
If A → LOCATED_IN → B and B → LOCATED_IN → C,
then A is transitively in C. Computed at query time.
Not stored as additional direct relationships.
R01 is authoritative.

**I2 — DESIGNATED_BY Non-Inheritance**
Designations do not propagate through containment.
See also Constraint C9.

**I3 — CONTAINS as Derived Inverse**
CONTAINS (R02) derived from LOCATED_IN (R01).
R01 is authoritative. R02 may be materialized for performance.

**I4 — HAS_DESIGNATED as Derived Inverse**
HAS_DESIGNATED (R21) derived from DESIGNATED_BY (R20).
R20 is authoritative.

**I5 — Abstract Entity Non-Instantiation**
A1 attributes flow to concrete child entities.
No entity may carry only A1 as its type.

---

## 8. Decision Log

| Code | Decision | Rationale | Revisable |
|------|----------|-----------|-----------|
| D-001 | Itinerary → Content Type | Editorial in nature | Yes |
| D-002 | A16 removed | Overlap with D1 | Yes |
| D-003 | Designation hybrid model | Based on information richness | Yes |
| D-004 | Layered model for Category A | Scalability balance | Yes |
| D-005 | 5 attraction types → Classifications | Insufficient entity justification | Yes |
| D-006 | 5 attraction types → Entities | Unique info and distinct relationships | Yes |
| D-007 | Entity Inflation Prevention principle | Prevent complexity at scale | No |
| D-008 | All decisions are conceptual | Enable evidence-based revision | No |
| D-009 | Tourism Target Village → Classification on G7 | Prevent duplicate content | Yes |
| D-010 | G4 → Infrastructure Entity | No tourism search intent | Yes |
| D-011 | G2 internal classification | Different KG behavior without new entities | Yes |
| D-012 | Geographic Feature Parent → Backlog | Premature abstraction | Yes |
| D-013 | A1 Abstract Entity | Prevent ambiguous pages | No |
| D-014 | D1 → One aggregation URL | Prevent cannibalization | Yes |
| D-015 | One Primary Node. Designations = relationships | Single Source of Truth | No |
| D-016 | D3/D4 geographic instances → Category G as ManagedArea | Geographic primary identity | Yes |
| D-017 | D5 → Attribute on G5 | No information beyond G5 context | Yes |
| D-018 | AN-007 exception removed. Spatial Scope replaces it | Strengthens Geography First | Yes |
| D-019 | DESIGNATED_BY = Qualified Relationship | Graph accuracy | Yes |
| D-020 | D2 → C instances not C classes | Ontological correctness | Yes |
| D-021 | D1/D2/D6 = Programme Nodes | Ontological precision | Yes |
| D-022 | D3/D4 = Hybrid Entities | Both dimensions real | Yes |
| D-023 | D6 → Attribute | Insufficient unique information | Yes |
| D-024 | DESIGNATED_BY properties defined | Complete qualification | Yes |
| D-025 | No Designation Event Node — absolute | Prevent entity explosion | No |
| D-026 | Aggregation pages require unique editorial content | Prevent doorway page classification | Yes |
| D-027 | Transnational model: Primary Jurisdiction + External URIs | Geography First compliance | Yes |
| D-028 | REMOVED — located_in_province on relationship | Inefficient in graph databases | N/A |
| D-029 | CONTAINS Tier Policy in ManagedArea | Prevent relationship explosion | Yes |
| D-030 | Reification Policy for repeated designations | Disambiguation for multiple events | Yes |
| D-031 | CONTAINS Tier Policy detail formalized | Graph consistency | Yes |
| D-032 | Dual-Source Consistency Policy | Single Source of Truth | Yes |
| DC-C-001 | C entity count = 10 (not 9). C7 confirmed. | Documentation correction | No |
| DC-R-001 | C11 removed from R13 sources | C11 is Content Type — not entity | No |
| DC-R-002 | R27 added to Relationship Layer | Transnational entities need external recognition model | Yes |
| DC-R-003 | venue_name as optional property on R16 | Named venues without entity model | Yes |
| DC-R-004 | A2 restored in R15 with Constraint C5 | Real historical venues exist in A2 — full removal was incorrect | Yes |
| DC-R-005 | P7 dual-level model | Prevents thin content per route | Yes |
| DC-R-006 | R05 = general-purpose. P9 current. | Prevents future breaking changes | No |

---

## 9. Future Scope

### Commercial Entities — Outside Phase 1

| Code | Entity | Reason |
|------|--------|--------|
| F1 | Accommodation | Requires booking layer |
| F2 | Hotel | Requires booking layer |
| F3 | Boutique Hotel / Eco Lodge | Requires booking layer |
| F4 | Hostel / Guesthouse | Requires booking layer |
| F5 | Restaurant / Cafe | Requires commercial layer |
| F6 | Tour Package | Requires booking layer |
| F7 | Tour Guide | Requires commercial layer |
| F8 | Travel Agency | Requires commercial layer |
| F9 | Tourism Information Center | Requires physical/commercial layer |

### Content Types — Not Entities in Any Phase

- **Itinerary** — derived editorial content from G, A, P9
- **Travel Guide** — composite editorial content
- **FAQ** — section pattern on entity pages
- **Local Legends / C11** — derived from C12, G5, C9

### Deferred Architecture Items

- Schema.org Mapping (Phase 4)
- Internal Linking Model with anchor text standards (Phase 3)
- Aggregation page content specifications (Phase 3)
- Data Layer architecture for Designation data (Phase 4)
- Sync policy for derived relationships (Phase 4)
- Geographic Feature Parent concept (Phase 3 review)
- Museum Institution vs Museum Building split (future phase)

---

## 10. Freeze Status

### Architecture Freeze v1.2.1 — APPROVED AND FROZEN

| Component | Count | Status |
|-----------|-------|--------|
| Categories | 6 | Frozen |
| Independent Entities | 42 | Frozen |
| D Programme Nodes | 4 Named Individuals | Frozen |
| Individual Classifications | 70 | Frozen |
| Relationships | 27 | Frozen |
| Constraints | 10 | Frozen |
| Inheritance Rules | 5 | Frozen |
| Decision Entries | 38 | Frozen |

### Out of Scope for This Document

Content Architecture · WordPress Architecture ·
CPT Design · Taxonomy Design · URL Structure ·
Database Design · Schema.org Mapping ·
UI/UX Design · Internal Linking Model ·
Aggregation Page Templates

---

## 11. Next Phase

### Phase 3 — Content Architecture

**Objective:** Define the content model for each approved entity.
Determine page templates, content fields, editorial guidelines,
and internal linking patterns.

**Key deliverables:**
- Page template per entity type
- Required vs optional fields per entity
- Internal linking rules derived from R01–R27
- Aggregation page specifications
- Minimum Viable Content per entity type
- Content priority matrix (SEO/AEO value)
- Editorial workflow guidelines

**Open items for Phase 3:**
- Schema.org mapping for all entity types
- Internal linking model and anchor text standards
- Aggregation page content requirements
- P7 dual-level content specification
- Data Layer architecture for Designation data

---

*ToPersia Information Architecture v1.2.1-FINAL*
*Validation: PASSED*
*Status: APPROVED AND FROZEN*
*Phase 2: COMPLETE*
*Proceed to Phase 3 — Content Architecture*
