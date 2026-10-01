# ToPersia — Information Architecture
## Executive Summary
### Version 1.2.1 | Phase 2 Completion

---

## What Was Built

ToPersia's Information Architecture defines the conceptual
knowledge model for Iran's most comprehensive tourism and
cultural reference platform. This document covers the complete
entity taxonomy, classification system, and relationship
architecture that will power the platform's Knowledge Graph.

**Scope:** Conceptual architecture only. No CMS, database,
URL structure, or implementation decisions are included.

---

## Architecture at a Glance

| Component | Count | Status |
|-----------|-------|--------|
| Categories | 6 | Frozen |
| Independent Entities | 42 | Frozen |
| D Programme Nodes (Named Individuals) | 4 | Frozen |
| Classification Groups | 13 | Frozen |
| Individual Classifications | 70 | Frozen |
| Relationships | 27 | Frozen |
| Constraints | 10 | Frozen |
| Inheritance Rules | 5 | Frozen |
| Decision Log Entries | 38 | Frozen |

---

## The Six Categories

**G — Geography (13 entities)**
The foundation. Every other entity connects here.
From Country (G1) down to Border Crossing (G13).
G5 (City) carries the highest SEO and content priority.

**A — Attractions (8 entities)**
All visitable locations. A1 is abstract — never used directly.
Concrete types: Historical Monument, Religious Site, Museum,
National Park, Natural Feature, Archaeological Site,
Cultural Landscape. Supported by 37 classifications.

**D — Designations (4 programme nodes + 2 attributes)**
UNESCO and domestic official titles. Expressed as qualified
relationships on existing entities — never as new nodes.
UNESCO World Heritage, Intangible Heritage, Geopark,
Biosphere Reserve, Creative City, Natural Monument.

**C — Culture & Heritage (10 entities)**
Intangible and material cultural expressions.
Handicraft, Local Food, Traditional Costume, Music,
Dance, Language, Ethnicity, Ritual, Historical Figure,
Historical Period. C9 (Ethnicity) is the central hub.

**E — Events (4 entities)**
Time-bounded occurrences. Festival/Event, Sports Event,
Public Religious Ceremony, Seasonal/Natural Event.
Clear boundary with C10: cultural phenomena vs. event instances.

**P — Practical Travel Information (7 entities)**
Actionable trip planning content. Visa, Currency, Health,
Etiquette, Calendar, Transport, Tourism Routes.
Strictly informational — no commercial services in Phase 1.

---

## Seven Governing Principles

1. **Geography First** — Every entity connects to geography.
   No entity exists without a geographic anchor.

2. **One Physical Place = One Primary Node** — One location,
   one node. Designations and roles are relationships, not nodes.

3. **Entity Inflation Prevention** — New concepts require
   independent search volume, unique information, AND distinct
   relationships. All three. Always.

4. **Layered Model** — Abstract parent → concrete children →
   classifications → attributes. No flat structures.

5. **Single Source of Truth** — One authoritative data location
   per fact. Derived relationships computed, not stored.

6. **Designation as Qualified Relationship** — Every official
   title is a relationship with metadata properties, never a
   new graph node.

7. **Transnational Entity Rule** — Cross-border cultural
   entities (e.g. Nowruz) have Iran as Primary Jurisdiction.
   External recognition uses External URIs only.

---

## Knowledge Graph Highlights

**27 Relationships** from spatial (LOCATED_IN, CONTAINS)
to cultural (PRACTICED_IN, ASSOCIATED_WITH_ETHNICITY)
to historical (BELONGS_TO_PERIOD, BUILT_BY) to
designations (DESIGNATED_BY with full qualification).

**Critical for AEO:** R01, R11, R20, R13, R06, R17 power
direct answers to AI queries about Iran's geography,
history, UNESCO sites, cultural traditions, and events.

**Critical for GEO:** R01, R04, R03 enable map-based
discovery and spatial queries across all content.

**Critical for SEO:** Aggregation pages derived from R20,
internal linking chains from R01 and R11, classification
filtering from 70 classification labels.

---

## What Is NOT in This Architecture

**Future scope (requires commercial layer):**
Hotels, restaurants, tour operators, tour packages,
travel agencies, booking systems.

**Content types (not entities):**
Itinerary, Travel Guide, FAQ sections, Local Legends (C11).

**Technical decisions (Phase 4):**
WordPress, JetEngine, CPT structure, taxonomy design,
URL architecture, database schema, Schema.org mapping.

---

## Phase 2 Closure

Architecture Freeze v1.2.1 is approved and closed.
All 38 decisions are documented and traceable.
Five iterations of review produced a stable, implementable
model with no unresolved critical issues.

**Next:** Phase 3 — Content Architecture
Define page templates, content fields, minimum viable content
per entity, internal linking rules, and editorial guidelines.

---

*ToPersia IA-v1.2.1 | Phase 2 Complete*
*Validated: Architecture Freeze Package*
