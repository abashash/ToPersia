# ToPersia — Architecture Index
## IA-v1.2.1 | Quick Reference

---

## CATEGORIES

| Code | Name | Entities | Status |
|------|------|----------|--------|
| G | Geography | 13 | Frozen |
| A | Attractions | 8 | Frozen |
| D | Designations | 4 programme nodes | Frozen |
| C | Culture & Heritage | 10 | Frozen |
| E | Events | 4 | Frozen |
| P | Practical Travel Info | 7 | Frozen |

---

## ENTITIES

### Category G — Geography

| Code | Entity |
|------|--------|
| G1 | Country |
| G2 | Geographic Region |
| G3 | Province |
| G4 | County *(Infrastructure Entity)* |
| G5 | City |
| G6 | District / Neighborhood |
| G7 | Village |
| G8 | Island |
| G9 | Mountain / Range |
| G10 | Water Body |
| G11 | Desert |
| G12 | Forest / Plain |
| G13 | Border Crossing |

### Category A — Attractions

| Code | Entity | Note |
|------|--------|------|
| A1 | Tourist Attraction | Abstract — never instantiated |
| A2 | Historical Monument | venue_eligible attribute |
| A3 | Religious Site | |
| A7 | Museum | |
| A9 | National Park / Protected Area | Managed Area subtype |
| A10 | Natural Feature | |
| A12 | Archaeological Site | |
| A13 | Cultural Landscape | Composite Entity |

### Category D — Designations

| Code | Entity | Type |
|------|--------|------|
| D1 | UNESCO World Heritage | Programme Node |
| D2 | UNESCO Intangible Heritage | Programme Node |
| D3 | UNESCO Global Geopark Programme | Programme Node |
| D4 | UNESCO Biosphere Reserve Programme | Programme Node |
| D5 | UNESCO Creative City | Attribute on G5 |
| D6 | National Natural Monument | Attribute on natural entities |
| — | National Registration | Attribute |
| — | Free Zone / Special Economic Zone | Attribute on G5/G8 |

### Category C — Culture & Heritage

| Code | Entity |
|------|--------|
| C1 | Handicraft |
| C2 | Local Food / Dish |
| C5 | Traditional Costume |
| C6 | Traditional Music |
| C7 | Traditional Dance |
| C8 | Language / Dialect |
| C9 | Ethnicity / Culture Group |
| C10 | Ritual / Ceremony |
| C12 | Historical Figure |
| C13 | Historical Period / Dynasty |

### Category E — Events

| Code | Entity |
|------|--------|
| E1 | Festival / Event |
| E3 | Sports Event |
| E5 | Public Religious Ceremony |
| E6 | Seasonal / Natural Event |

### Category P — Practical Travel Information

| Code | Entity |
|------|--------|
| P1 | Visa & Entry Information |
| P2 | Currency & Payment |
| P4 | Health & Safety |
| P5 | Travel Etiquette |
| P6 | Calendar & Public Holidays |
| P7 | Public Transport Information |
| P9 | Tourism Route |

---

## CLASSIFICATIONS

### Under A1 (Tourist Attraction)
Persian Garden · Traditional Bazaar · Coast/Beach ·
Modern Landmark · Tourism Target Village *(on G7)*

### Under A2 (Historical Monument)
Caravanserai · Historic Bridge · Historic Tower ·
Palace · Historic Bath · Historic School · Mausoleum

### Under A3 (Religious Site)
Mosque · Church · Temple · Fire Temple ·
Synagogue · Imamzadeh · Sufi Lodge

### Under A7 (Museum)
Art Gallery · House Museum

### Under A10 (Natural Feature)
Waterfall · Cave · Spring · Canyon/Gorge ·
Rock Formation · Salt Formation/Kalut

### Under C1 (Handicraft)
Carpet/Kilim · Tilework · Miniature · Inlaid Work ·
Filigree · Pottery · Traditional Textile · Block Print

### Under C2 (Local Food)
Soup/Stew · Khoresh · Kebab · Traditional Bread ·
Sweet/Confection · Preserve/Pickle ·
Ceremonial Food · **Local Beverage** *(C3 absorbed)*

### Under C5 (Traditional Costume)
Men's Traditional Dress · Women's Traditional Dress ·
Ceremonial Dress · Traditional Wedding Dress

### Under C6 (Traditional Music)
Classical/Dastgah · Regional/Ethnic Music ·
Ceremonial Music · Traditional Instrument

### Under C10 (Ritual / Ceremony)
Nowruz Rituals · Mourning Ceremony ·
Seasonal Celebration · Traditional Wedding ·
Ethnic Ritual

### Under C12 (Historical Figure)
Poet · Scientist/Scholar · King/Ruler ·
Military Commander · Artist · Philosopher/Mystic

### Under E1 (Festival / Event)
Cultural/Arts Festival · One-Time Cultural Event ·
Specialized Exhibition · Local Celebration · Conference

### Under P9 (Tourism Route)
Historical Route · Natural Route · Thematic Route

---

## RELATIONSHIPS

### Spatial Relationships

| Code | Name | Direction | Type |
|------|------|-----------|------|
| R01 | LOCATED_IN | Child → Parent G | Primary |
| R02 | CONTAINS | Parent → Child | Derived |
| R03 | PASSES_THROUGH | P9 → G | Primary |
| R04 | BORDERS | G1/G3 ↔ G1/G3 | Primary |
| R05 | CONNECTS | P9 → G *(general-purpose)* | Primary |
| R26 | LOCATED_IN_MANAGED_AREA | A10/A9 → ManagedArea | Primary |

### Cultural Relationships

| Code | Name | Direction | Type |
|------|------|-----------|------|
| R06 | PRACTICED_IN | C → G | Primary |
| R07 | ASSOCIATED_WITH_ETHNICITY | C → C9 | Primary |
| R08 | SPOKEN_IN | C8 → G | Primary |
| R09 | PRODUCED_IN | C1/C2 → G | Primary |
| R10 | WORN_BY | C5 → C9 | Primary |
| R27 | ALSO_RECOGNIZED_IN | C10 Transnational → External URI | Primary |

### Historical Relationships

| Code | Name | Direction | Type |
|------|------|-----------|------|
| R11 | BELONGS_TO_PERIOD | A/C → C13 | Primary |
| R12 | BUILT_BY / COMMISSIONED_BY | A2/A12 → C12 | Primary |
| R13 | ASSOCIATED_WITH_FIGURE | A/C/G → C12 | Primary |
| R14 | BORN_IN / DIED_IN / BURIED_IN | C12 → G5/A2 | Primary |

### Event Relationships

| Code | Name | Direction | Type |
|------|------|-----------|------|
| R15 | HELD_AT | E → A/G *(A2 venue_eligible only)* | Primary |
| R16 | HELD_IN | E → G *(+ venue_name property)* | Primary |
| R17 | INSTANCE_OF | E → C10 | Primary |
| R18 | CELEBRATES / COMMEMORATES | E → C | Primary |
| R19 | OCCURS_DURING | E6 → Attribute | Attribute |

### Designation Relationships

| Code | Name | Direction | Type |
|------|------|-----------|------|
| R20 | DESIGNATED_BY | Entity → D Programme | Primary (Qualified) |
| R21 | HAS_DESIGNATED | D → Entity | Derived |

*R20 Properties: designation_type · year · criteria · status · review_date · awarding_body*

### Travel Information Relationships

| Code | Name | Direction | Type |
|------|------|-----------|------|
| R22 | APPLIES_TO | P → G | Primary |
| R23 | AFFECTS | C10/E → P6 | Primary |
| R24 | REQUIRES_COMPLIANCE_WITH | A3 → P5 | Primary |
| R25 | BEST_VISITED_DURING | Entity → Attribute | Attribute |

---

## CONSTRAINTS

| Code | Rule |
|------|------|
| C1 | One Primary LOCATED_IN per entity |
| C2 | No Circular Geography in LOCATED_IN |
| C3 | LOCATED_IN targets Category G only |
| C4 | No Event Designation — E cannot receive R20 |
| C5 | Venue Eligibility — A2 as R15 target requires venue_eligible: true |
| C6 | No Designation Event Node — R20 is always a relationship, never a node |
| C7 | INSTANCE_OF is one level only — E → C10 only |
| C8 | Transnational Primary Jurisdiction — G1 Iran always primary |
| C9 | DESIGNATED_BY Non-Inheritance — containers do not pass designation to contents |
| C10 | R05 CONNECTS is general-purpose — currently P9, future-ready without revision |

---

## INHERITANCE RULES

| Code | Rule |
|------|------|
| I1 | LOCATED_IN Transitivity — computed at query time, not stored |
| I2 | DESIGNATED_BY Non-Inheritance — no propagation through containment |
| I3 | CONTAINS as Derived Inverse of LOCATED_IN |
| I4 | HAS_DESIGNATED as Derived Inverse of DESIGNATED_BY |
| I5 | Abstract Entity Non-Instantiation — A1 never used as sole type |

---

## DECISION LOG INDEX

| Code | Topic | Decision |
|------|--------|---------|
| D-001 | Itinerary | → Content Type |
| D-002 | A16 (UNESCO as entity) | → Removed |
| D-003 | Designation model | → Hybrid |
| D-004 | Category A model | → Layered |
| D-005 | 5 types → Classifications | Caravanserai, Persian Garden, Bazaar, Gallery, Modern Landmark |
| D-006 | 5 types → Entities | A2, A3, A7, A12, A13 |
| D-007 | Entity Inflation Prevention | → Formal principle |
| D-008 | All decisions are conceptual | → Flexibility rule |
| D-009 | A15 Tourism Village | → Classification on G7 |
| D-010 | G4 | → Infrastructure Entity |
| D-011 | G2 internal classification | → Natural/Historical/Cultural/Climate |
| D-012 | Geographic Feature Parent | → Backlog |
| D-013 | A1 Abstract | → No direct instantiation |
| D-014 | D1 URL | → One aggregation page only |
| D-015 | One Primary Node | → Designations are relationships |
| D-016 | D3/D4 instances | → Category G as ManagedArea |
| D-017 | D5 Creative City | → Attribute on G5 |
| D-018 | AN-007 exception | → Removed, replaced by Spatial Scope |
| D-019 | DESIGNATED_BY | → Qualified Relationship |
| D-020 | D2 connections | → To C instances, not C classes |
| D-021 | D1/D2/D6 | → Programme Nodes (Named Individuals) |
| D-022 | D3/D4 | → Hybrid Entities |
| D-023 | D6 | → Attribute |
| D-024 | DESIGNATED_BY properties | → 6 properties defined |
| D-025 | No Designation Event Node | → Absolute rule |
| D-026 | Aggregation pages | → Must have unique editorial content |
| D-027 | Transnational model | → Primary Jurisdiction + External URIs |
| D-028 | located_in_province on relationship | → REMOVED |
| D-029 | CONTAINS Tier Policy | → Tier 1/2/3 defined |
| D-030 | Reification for repeated designations | → Policy defined |
| D-031 | CONTAINS Tier Policy detail | → Formalized |
| D-032 | Dual-Source Consistency | → Single Data Layer |
| DC-C-001 | C entity count | → 10 (not 9), C7 confirmed |
| DC-R-001 | R13 sources | → C11 removed |
| DC-R-002 | R27 | → Added to relationship layer |
| DC-R-003 | venue_name | → Property on R16 |
| DC-R-004 | R15 + A2 | → A2 restored with venue_eligible constraint |
| DC-R-005 | P7 dual-level | → Entity page + G5 attributes |
| DC-R-006 | R05 scope | → General-purpose, P9 current |

---

## FUTURE SCOPE (Outside Phase 1)

**Commercial entities deferred:**
F1 Accommodation · F2 Hotel · F3 Boutique Hotel ·
F4 Hostel · F5 Restaurant/Cafe · F6 Tour Package ·
F7 Tour Guide · F8 Travel Agency · F9 Tourism Info Center

**Content Types (not entities in any phase):**
Itinerary · Travel Guide · FAQ · Local Legends (C11)

---

## FREEZE STATUS

```
Architecture Freeze v1.2.1 — APPROVED AND FROZEN
Phase 2: Information Architecture — COMPLETE
Phase 3: Content Architecture — NEXT
```

*ToPersia IA Index | v1.2.1*
