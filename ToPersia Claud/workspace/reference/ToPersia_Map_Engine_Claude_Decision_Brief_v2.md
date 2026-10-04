# ToPersia Map Engine — Claude Final Decision Brief v2

**Purpose:** Give Claude a decision framework for implementing the production ToPersia Map Engine from the live repository, while explicitly comparing the existing ToPersia architecture proposal with the second proposal supplied as the Gemini proposal.

**Important:** This document is a decision brief, not a blind implementation script. Claude MUST inspect the live repository and production environment before changing code. Claude is authorized — and expected — to reject, simplify, or replace any recommendation below when repository evidence, database capability, WPML behavior, or benchmarks justify it.

---

## 1. Executive Decision

### Final recommendation

Build the Map Engine as a **geospatial access/rendering layer over the existing ToPersia Entity Graph**.

Do **not** create a second canonical Places database.

Do **not** replace the existing MapLibre + PMTiles + Protomaps foundation.

Use this architecture:

```text
WordPress + JetEngine
        |
        | canonical entities / relations / identity
        v
ToPersia Entity + Geo Service
        |
        +---- WPML-aware language projection
        |
        +---- relation-aware geographic scope
        |
        v
Map Query Layer
        |
        +---- Option A: optimized canonical coordinate access
        |
        +---- Option B: derived Geo Index
        |
        v
ToPersia Map Data API
        |
        +---- entity
        +---- scope
        +---- bbox / viewport
        +---- nearby
        +---- filters
        |
        v
Stable Map Data Contract
        |
        v
Map Source Adapter
        |
        +---- GeoJSON first
        +---- MVT/PMTiles later only if benchmarks require it
        |
        v
TopersiaMap
        |
        v
MapLibre GL JS
        |
        +---- entity layers / clusters / selection
        |
        +---- Basemap Adapter
                  |
                  v
               PMTiles
                  |
                  v
        Protomaps / OSM-derived basemap
```

### Critical architectural refinement

The correct decision is **not**:

> "Always create a spatial index immediately."

Nor is it:

> "Always query JetEngine/postmeta directly."

The correct decision is:

> **Create a stable Map Query Layer and make its backing store replaceable. Benchmark Option A and Option B in the real production database. Use Option B as the production default when the target scale, query patterns, or measured latency make postmeta insufficient.**

For a platform expected to grow to tens or hundreds of thousands of mapped entities, Claude should expect the derived Geo Index to become necessary, but it must prove the choice against the actual database first.

---

# 2. Non-Negotiable Principles

These rules override convenience:

1. **JetEngine/WordPress remains the canonical entity source.**
2. **WPML remains the canonical localization system for ToPersia entities.**
3. **`entity_uid` remains stable and language-independent.**
4. **Coordinates and geometry remain entity data, not map-only duplicate entities.**
5. **A Geo Index, when used, is a derived performance structure — never a second source of truth.**
6. **OSM/Protomaps data is basemap/context, not the ToPersia entity graph.**
7. **Google/Waze/Neshan/Balad are navigation endpoints only, not entity providers.**
8. **The browser renderer must not know JetEngine internals.**
9. **The browser must not receive the full WordPress post/meta/relation graph.**
10. **There must be no unlimited DOM-marker architecture.**
11. **A country/province/city relation must never silently invent coordinates for an entity that has no physical location.**
12. **No undocumented provider URL/API may be treated as verified.**
13. **No major MapLibre upgrade should be bundled into the first Map Engine refactor.**
14. **Every performance-sensitive architectural decision must be benchmark-driven.**

---

# 3. Source Comparison

## 3.1 What both proposals get right

Both the existing Claude brief and the supplied Gemini proposal agree on the core strategic direction:

- Keep WordPress + JetEngine.
- Keep WPML.
- Keep stable ToPersia entity identity.
- Keep MapLibre.
- Keep PMTiles.
- Keep Protomaps/OSM-derived basemap delivery.
- Move from single-entity DOM injection toward API-driven multi-entity mapping.
- Use GeoJSON sources/layers and clustering for multi-entity maps.
- Add viewport/bbox loading for large scopes.
- Add Nearby using browser geolocation.
- Keep routing outside ToPersia.
- Avoid an external Places database.
- Preserve a staged migration rather than a big-bang rewrite.

The existing repository audit identifies the current single-entity pipeline as:

```text
JetEngine
 -> geo_coordinates
 -> ToPersia PHP map helpers
 -> data-tp-map-cfg
 -> TopersiaMap
 -> MapLibre
 -> PMTiles / Protomaps
```

and identifies the missing abstraction between entity storage and map rendering. fileciteturn0file0L222-L256

The Gemini proposal reaches the same high-level conclusion that the current DOM-centric approach must evolve into an API + clustered WebGL architecture. fileciteturn0file1L34-L42

---

# 4. Where This Brief Disagrees With the Gemini Proposal

## 4.1 Spatial querying against `geo_coordinates`

### Gemini proposal

The supplied Gemini proposal says the REST API should query JetEngine entities and perform geospatial filtering against the existing `geo_coordinates` field, while later recommending MySQL spatial indexing for Nearby. fileciteturn0file1L82-L91 fileciteturn0file1L160-L168

### Problem

The repository audit shows that `geo_coordinates` is a JetEngine **text** field, not a native spatial database column. fileciteturn0file0L447-L465

A native spatial index therefore cannot simply be placed on the existing text field.

### Decision

Claude must introduce an abstraction:

```text
Map Query Layer
```

with two possible implementations:

```text
Option A
canonical post/meta coordinate access

Option B
derived Geo Index
```

The API and frontend must not care which implementation is active.

For a large production dataset, the derived Geo Index is the preferred target because postmeta text is not a purpose-built structure for bbox, radius, nearest-neighbor, or distance ordering. fileciteturn0file0L617-L629

---

## 4.2 Do not hard-code the first REST URL

The Gemini proposal specifies:

```text
/wp-json/topersia/v1/map-entities
```

The architecture is correct, but the exact path should not be treated as existing or final.

### Decision

Claude must inspect current REST namespace conventions and select the exact production endpoint.

The API concept is:

```text
entity
scope
bbox
nearby
```

The endpoint path is an implementation decision.

---

## 4.3 Do not claim "verified" Neshan/Balad URLs without verification

The Gemini proposal labels specific Neshan and Balad URLs as verified. The supplied material does not provide official verification evidence for those claims. fileciteturn0file1L170-L195

The earlier Claude brief intentionally took the safer position: Neshan/Balad adapters remain disabled until current official documentation or observed provider behavior is verified.

### Decision

Use this policy:

```text
Google Maps: implement after official URL verification.

Waze: implement after official/current URL verification.

Neshan: adapter only after official/current verification.

Balad: adapter only after official/current verification.
```

Do not build a feature around a URL copied from an old blog post, forum, or another AI response.

Google Maps' current official Maps URL documentation confirms `api=1` is required and that destination coordinates can be used. citeturn868800search3

---

## 4.4 Do not use arbitrary numeric marker thresholds as architecture rules

The Gemini proposal uses approximately **250 DOM markers** as a threshold. fileciteturn0file1L197-L205

That number should not become a hard architectural constant.

The actual decision depends on:

```text
DOM nodes
layout/reflow cost
symbol density
GeoJSON size
worker processing
network latency
mobile memory
device GPU
interaction performance
```

The earlier architecture brief correctly treats performance as a measurement problem rather than a mythical fixed marker limit. fileciteturn0file0L1315-L1379

### Decision

Use:

```text
single entity -> existing marker path

multi entity -> MapLibre source/layers

large scope -> viewport loading + clustering

very large dataset -> benchmark MVT/PMTiles entity delivery
```

Do not make "250" or any other number the product rule.

---

## 4.5 Do not promise a specific FPS result

The Gemini proposal states that the architecture can maintain 60 FPS with tens of thousands of entities.

That is a target/measurement outcome, not something the architecture can guarantee independent of device, style complexity, symbol collision, geometry density, and payload size.

### Decision

Benchmark real devices.

Accept/reject based on measured interaction quality.

---

## 4.6 Do not remove RTL support blindly

Current MapLibre documentation states that MapLibre itself shapes Arabic and reorders bidirectional text, and that `setRTLTextPlugin()` is deprecated. citeturn868800search0

However:

```text
deprecated != safe to remove without testing
```

### Decision

Run the ToPersia RTL matrix first:

```text
fa
ar
fa + Latin
ar + Latin
numbers
coordinates
popup text
basemap labels
```

Only then remove:

```text
setRTLTextPlugin()
mapbox-gl-rtl-text.js
```

---

## 4.7 MapLibre v6 is a separate migration

Current MapLibre documentation confirms that v6 is ESM-only and removes the old UMD/CommonJS distribution model. citeturn868800search1turn868800search2

### Decision

Do not combine:

```text
Map Engine architecture refactor
+
MapLibre major-version migration
```

First stabilize the existing runtime.

Then evaluate v6 as a separate project.

---

# 5. Final Technology Decision

## KEEP

```text
WordPress
JetEngine
WPML
ToPersia Entity UID
MapLibre GL JS
PMTiles
Protomaps / OSM-derived basemap
local fonts
local sprites
TopersiaMap
IntersectionObserver lazy loading
modal maps
SVG fallback
existing centralized cache
```

The existing map foundation is already sound; the repository audit specifically identifies MapLibre, PMTiles, local assets, lazy loading, modal behavior, and the `TopersiaMap` adapter as assets worth preserving. fileciteturn0file0L585-L613

## REFACTOR

```text
JetEngine
    ->
ToPersia Entity / Geo Service
    ->
Map Query Layer
    ->
Map Data API
    ->
Map Source Adapter
    ->
TopersiaMap
    ->
MapLibre
```

## ADD

```text
Map Entity DTO
coordinate validator
map-capability policy
relation-aware scope resolver
Map Query Layer
derived Geo Index capability
index builder
incremental invalidation
full rebuild
Map REST API
bbox querying
nearby querying
clustered entity source/layers
viewport loading
index/version metadata
navigation adapter
benchmark suite
regression test suite
```

## REMOVE LATER, ONLY AFTER TESTING

```text
setRTLTextPlugin()
mapbox-gl-rtl-text.js dependency
```

## DEFER

```text
MapLibre v6
MVT/PMTiles entity delivery
routing engine
traffic
turn-by-turn navigation
full geometry editing
external Places ingestion
duplicate map-only entities
```

---

# 6. Canonical Entity Model

ToPersia already has a large JetEngine entity graph with geographic CPTs and real relations.

The repository audit confirms that the geographic hierarchy includes relationships such as:

```text
country -> province
province -> county
county -> city
city -> district
county/province -> village
city/village -> monument
city/village -> religious site
city -> museum
province -> protected area
province/city -> natural feature
province -> mountain
province -> island
```

along with additional many-to-many geographic/knowledge relationships. fileciteturn0file0L379-L445

### Rule

The Map Engine must use the actual relation graph.

Never infer geography merely from `post_type`.

---

# 7. Map Capability Policy

A post type is not automatically map-visible.

A mapped entity must satisfy:

```text
physical geographic meaning
+
public/published according to ToPersia policy
+
valid coordinate or explicit supported geometry
+
map representation intentionally enabled
```

Examples of coordinate-bearing physical entities:

```text
city
village
museum
monument
religious site
natural feature
mountain
island
water body
protected area
border crossing
ancient site
```

An entity can have a geographic relationship without having its own point.

Never do:

```text
Museum
(no coordinate)
    |
    v
copy city coordinate
    |
    v
fake museum marker
```

---

# 8. Coordinate and Geometry Strategy

Current canonical data:

```text
JetEngine
geo_coordinates (text)
```

Target direction:

```text
canonical editorial geometry
+
derived query-optimized representation
```

For point entities, the preferred derived representation is:

```text
POINT
SRID 4326
+
lat
+
lng
```

while preserving scalar latitude/longitude columns for diagnostics and portability.

MySQL documents that spatial indexes can be used on spatial columns and that SRID-restricted geometry columns are important for correct indexed spatial comparisons. citeturn868800search5

### Important

Claude MUST inspect the real production database:

```text
MySQL or MariaDB
exact version
storage engine
spatial feature support
existing schema conventions
backup/restore process
```

before committing to the exact index DDL.

---

# 9. Derived Geo Index

If benchmark results justify it, create a custom table such as:

```text
wp_topersia_geo_index
```

The exact name must follow the repository naming conventions.

Possible fields:

```text
id
canonical_post_id
entity_uid
post_type

lat
lng
geometry

country_id
region_id
province_id
county_id
city_id
district_id
village_id

visibility
post_status

source_version
updated_at
```

### Important design choice

Do not blindly put language variants into the spatial row model.

Geometry and identity are language-independent.

Language belongs primarily to:

```text
API projection
WPML resolution
localized title/url payload
```

If translated IDs are cached or denormalized later for performance, that must be a measured optimization rather than the canonical identity model.

---

# 10. Index Synchronization

The derived index must never become manually maintained data.

Build/update events must cover at minimum:

```text
coordinate added
coordinate changed
coordinate removed
publish
unpublish
trash
delete

relation added
relation removed

translation created
translation unpublished

visibility policy changed
```

The system must support:

```text
initial build
incremental updates
full rebuild
verification
index version
rollback/safe cutover
```

### Source-of-truth invariant

```text
JetEngine = truth
Geo Index = projection
```

Never the reverse.

---

# 11. Map Data API

The API is the contract between ToPersia and the map renderer.

Conceptual operations:

```text
entity
scope
bbox
nearby
```

Possible inputs:

```text
language
scope type
scope id
post types
bbox
lat
lng
radius
limit
page/cursor where appropriate
zoom
```

### Security

Never accept arbitrary browser-provided:

```text
SQL
meta_query
relation query
WP_Query arguments
```

The server must construct the allowed query from validated parameters.

---

# 12. Map Payload Contract

Use a compact GeoJSON `FeatureCollection` as the first delivery contract.

Example:

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [51.6775, 32.6514]
      },
      "properties": {
        "uid": "e-naqsh-e-jahan-square",
        "entity_id": 456,
        "type": "tp_monument",
        "title": "Localized title",
        "url": "Localized permalink"
      }
    }
  ]
}
```

Metadata may include:

```json
{
  "meta": {
    "language": "en",
    "scope_type": "tp_city",
    "scope_id": 123,
    "count": 24,
    "limit": 500,
    "truncated": false,
    "index_version": "..."
  }
}
```

Avoid fields that destroy cacheability or are not necessary for the client.

For example, do not inject an always-changing server timestamp merely because it looks useful.

---

# 13. Localization

There are three distinct language layers.

## Entity language

WPML controls:

```text
title
permalink
localized entity content
```

The stable identity remains:

```text
entity_uid
geometry
canonical identity
```

The repository audit confirms that `entity_uid` and geographic fields are configured as copy/language-independent data in the inspected WPML configuration, while prose/content fields are translated. Claude must still verify the live WPML UI before changing anything. fileciteturn0file0L467-L492

## Basemap language

Use the fields available in the actual PMTiles dataset and establish a deliberate fallback chain.

Do not assume every OSM feature has:

```text
name:fa
name:ar
name:en
```

## UI language

Map controls and accessibility/navigation strings must use the existing WordPress localization mechanism.

Do not hard-code Persian/Arabic/English UI strings inside the map engine.

---

# 14. Scope Resolution

## Country

Do not blindly load every descendant entity.

Use:

```text
explicit country context
+
mapped-type policy
+
viewport
+
server cap
+
clustering
```

## Province

Resolve entities belonging to the province using the actual relation graph and/or derived scope columns.

## City

Resolve the city plus intentionally mapped descendants.

## Entity page

Primary:

```text
current entity
```

Secondary:

```text
nearby ToPersia entities
```

Optional:

```text
related entities
```

The map should not expose the complete knowledge graph unless there is a deliberate product reason.

---

# 15. Client Rendering Architecture

The client must not know:

```text
JetEngine meta field names
WPML internals
relation IDs
WordPress queries
```

Instead it consumes:

```text
Map Data Contract
```

Architecture:

```text
TopersiaMap
    |
Map Source Adapter
    |
+------------------------+
|                        |
GeoJSON                MVT later
```

This makes future migration possible without rewriting the map UI.

---

# 16. Clustering and Viewport Loading

MapLibre currently supports GeoJSON clustering and cluster inspection/expansion APIs. citeturn868800search7turn868800search9

Use:

```text
GeoJSON source
cluster=true
clusterRadius
clusterMaxZoom
circle layer
symbol layer
popup/selection interaction
```

Do not create thousands of independent HTML markers.

### Behavior

```text
wide view
    ->
clusters

zoom in
    ->
fewer/smaller clusters

cluster interaction
    ->
expansion zoom / children

close view
    ->
individual entity features
```

---

# 17. Dataset Scale Strategy

## Level 1 — single entity

Keep the existing marker architecture.

```text
1 entity
1 coordinate
1 marker
```

## Level 2 — city/province

Use:

```text
REST API
+
GeoJSON
+
MapLibre source
+
clustering
```

## Level 3 — country / very large dataset

Use:

```text
viewport bbox
+
server-side cap
+
debounced moveend
+
request deduplication
+
cache
+
clustering
```

Do not download the whole Iran entity graph to the browser.

## Level 4 — if benchmarks prove GeoJSON is insufficient

Introduce:

```text
MVT / vector-tile entity delivery
```

behind the same:

```text
Map Source Adapter
```

Do not redesign the frontend for this migration.

---

# 18. Nearby / Around Me

Correct architecture:

```text
User action
    |
navigator.geolocation
    |
temporary lat/lng
    |
ToPersia nearby API
    |
Map Query Layer / Geo Index
    |
nearest ToPersia entities
```

The browser must request location only after explicit user action.

### Privacy

Do not intentionally persist GPS in:

```text
user profile
ToPersia entity database
long-term location history
```

Do not use exact GPS coordinates as long-lived cache keys.

### Logging warning

The statement "the coordinates are never stored because PHP discards them after the request" is incomplete.

Reverse proxies, web servers, WAFs, access logs, analytics systems, or CDN logs may retain request query strings.

Therefore Claude must decide explicitly whether Nearby uses:

```text
POST body
```

or a GET request with a verified log-redaction policy.

Do not claim privacy merely because application memory is ephemeral.

### Distance

Return:

```json
{
  "distance_m": 842
}
```

when the product needs it.

MySQL provides spatial distance functions such as `ST_Distance_Sphere()` for point distance calculations. citeturn868800search4

Use a bounding-box prefilter plus exact distance calculation when appropriate.

---

# 19. Navigation

ToPersia is not a routing engine.

Do not build initially:

```text
traffic
travel-time engine
turn-by-turn
route optimization
```

Use:

```text
Navigation Adapter
```

with provider-specific builders.

### Google Maps

Official current Maps URL documentation supports:

```text
https://www.google.com/maps/dir/?api=1&destination=LAT,LNG
```

and does not require a Google API key for Maps URLs. citeturn868800search3

### Waze / Neshan / Balad

Keep adapters isolated.

Before enabling each provider:

```text
verify official current URL/deep-link behavior
test desktop
test Android
test iOS where applicable
test app installed
test app absent
test fallback behavior
```

Never encode undocumented URLs into core business logic.

---

# 20. Basemap / PMTiles

Keep:

```text
Protomaps / OSM-derived basemap
+
PMTiles
+
self-controlled hosting/CDN
```

PMTiles remains a good packaging/distribution mechanism for static vector-tile data.

The runtime architecture should be:

```text
Basemap Adapter
    |
PMTiles
    |
CDN/object storage
```

not:

```text
WordPress PHP
    |
generate tiles on every request
```

### Production verification

Claude MUST test the actual deployed PMTiles path for:

```text
HTTP Range
206 Partial Content
Content-Range
CORS
ETag
Cache-Control
CDN cache behavior
large-file behavior
```

Do not assume a CDN is correctly configured merely because the file loads in a browser.

---

# 21. Cache Architecture

Extend the existing ToPersia central cache.

Do not create a parallel cache philosophy.

For map data, consider dimensions such as:

```text
service
language
scope
entity types
normalized viewport/cell
index version
```

Do not cache every arbitrary rectangle as a unique key.

Prefer:

```text
viewport
    |
normalized grid cells
    |
cacheable spatial units
```

For scope pages, prefer stable scope keys.

### Index versioning

Every Geo Index rebuild should produce a version.

Then:

```text
old index version
+
new index version
```

can coexist safely during rebuild/cutover.

---

# 22. Performance Decision Framework

Performance must be benchmarked on the real system.

Required dataset sizes:

```text
100
1,000
5,000
10,000
50,000
100,000
```

Measure separately:

```text
database query time
index lookup time
scope resolution time
WPML resolution
serialization
compressed payload size
browser JSON parsing
MapLibre source update
worker processing
memory
interaction responsiveness
mobile behavior
PMTiles range behavior
CDN cache behavior
```

### Initial targets

Treat these as engineering targets, not existing guarantees:

```text
p95 indexed normal viewport query    < 100 ms
p95 normal map API                  < 250 ms
typical compressed viewport payload < 500 KB
good mobile initialization          < 2 s
```

Claude may change these after real measurements.

### Key benchmark question

Compare:

```text
Option A
JetEngine/postmeta-backed query path

versus

Option B
derived Geo Index
```

using identical:

```text
dataset
filters
viewport
language
scope
device
```

Then compare:

```text
cold cache
warm cache
```

---

# 23. Architecture Decision Gate

Claude must choose among:

## Option A — Minimal evolution

```text
JetEngine
   ->
coordinate service
   ->
Map Query Layer
   ->
REST
   ->
GeoJSON
   ->
MapLibre clustering
```

Use this if the real dataset and query benchmark prove it is sufficient.

## Option B — Production Geo Index

```text
JetEngine
   ->
Entity/Geo Service
   ->
derived Geo Index
   ->
Map Query Layer
   ->
REST
   ->
GeoJSON
   ->
MapLibre
```

Choose this when:

```text
postmeta performance is insufficient
OR
nearby/bbox query volume is high
OR
scope resolution becomes expensive
OR
the dataset approaches the projected large-scale target
OR
measured p95 misses the product SLA
```

### Recommended expectation

For ToPersia's long-term large entity graph, Option B is the likely final production architecture.

But the **API contract, renderer, and UI must be designed so that Claude can reach Option B incrementally without a second rewrite.**

---

# 24. Migration Plan

## Phase 0 — baseline

Before code:

```text
inspect live repository
inspect live DB engine/version
inspect entity counts
inspect coordinate-bearing CPTs
inspect WPML configuration
inspect map asset version
inspect PMTiles behavior
document current screenshots/behavior
```

## Phase 1 — stabilize

Preserve:

```text
existing shortcode
single entity map
modal
lazy loading
fallback
attribution
PMTiles
current CSS behavior
```

## Phase 2 — Entity/Geo Service

Introduce:

```text
JetEngine
    ->
Map DTO
```

without changing the UI.

## Phase 3 — Map Query Layer

Implement the abstraction:

```text
Map Query Interface
```

with:

```text
Option A implementation
```

first if that is the lowest-risk path.

## Phase 4 — Geo Index if justified

Add:

```text
installer
builder
incremental sync
full rebuild
versioning
health checks
```

## Phase 5 — Map API

Expose:

```text
entity
scope
bbox
nearby
```

with a stable GeoJSON contract.

## Phase 6 — renderer refactor

Refactor:

```text
TopersiaMap
```

to consume the Map Source Adapter.

Keep backward compatibility.

## Phase 7 — clustering

Introduce:

```text
GeoJSON source
+
layers
+
cluster interactions
```

## Phase 8 — viewport loading

Use:

```text
moveend
+
debounce
+
bbox
+
request dedupe
```

## Phase 9 — multilingual hardening

Test:

```text
fa
en
ar
```

for:

```text
entity title
entity URL
basemap labels
popup
UI
mixed RTL/LTR
```

## Phase 10 — Nearby

Add:

```text
explicit consent
+
ephemeral location
+
privacy-safe request handling
+
spatial query
```

## Phase 11 — navigation

Enable only verified provider adapters.

## Phase 12 — performance

Benchmark:

```text
100 -> 100,000
```

and decide whether entity MVT is required.

## Phase 13 — optional MapLibre v6

Only after the Map Engine is stable.

MapLibre v6 is an ESM migration project, not a routine asset replacement. citeturn868800search2

---

# 25. Testing Matrix

## Language

```text
fa
en
ar
```

## Entity types

At minimum:

```text
tp_country
tp_province
tp_city
tp_village
tp_monument
tp_museum
tp_religious_site
tp_mountain
tp_island
tp_water_body
tp_protected_area
tp_route
```

## Modes

```text
single entity
city
province
country
viewport
cluster
nearby
popup
navigation
```

## Devices

```text
desktop
Android
iOS
```

## WebGL

```text
supported
unsupported/failure fallback
```

## Geolocation

```text
allowed
denied
unavailable
timeout
```

## RTL

```text
Persian
Arabic
Persian + Latin
Arabic + Latin
numbers
coordinates
popup
map labels
```

## Data integrity

```text
coordinate added
coordinate changed
coordinate removed
publish
unpublish
trash
delete
relation added
relation removed
translation created
translation unpublished
entity UID unchanged
```

---

# 26. Explicit Claims Claude Must NOT Accept Blindly

The following statements from AI-generated architecture documents are hypotheses until verified:

```text
"geo_coordinates can be queried spatially without a derived structure"

"this exact REST endpoint is already the correct production URL"

"this Neshan URL is verified"

"this Balad URL is verified"

"250 markers is the universal DOM limit"

"tens of thousands of entities will always maintain 60 FPS"

"removing the RTL plugin is automatically safe"

"all OSM entities have name:fa/name:ar/name:en"

"nearby GET coordinates can never appear in logs"

"all CDNs will automatically preserve PMTiles range behavior"

"MapLibre v6 is just a file replacement"

"every CPT is automatically map-visible"
```

---

# 27. Claude Authority Protocol

Before writing production code Claude MUST:

1. Re-read the current `main` repository tree.
2. Re-open every production-relevant map/core/render/WPML file.
3. Search for map API/index work added after the source audit.
4. Verify the real JetEngine model.
5. Verify the real WPML settings in the live project.
6. Verify the actual DB engine/version.
7. Verify the actual MapLibre runtime version.
8. Verify PMTiles/CDN range behavior.
9. Count real coordinate-bearing entities.
10. Benchmark Option A and Option B where feasible.
11. Decide whether a Geo Index is required now or introduced behind the Map Query Layer.
12. Preserve current map behavior until replacement passes regression tests.
13. Keep the frontend independent from JetEngine implementation details.
14. Do not add external Places data.
15. Document every deliberate deviation from this brief.

### Final decision rule

> **Choose the simplest architecture that satisfies the measured ToPersia production requirements — not the architecture with the most components.**

---

# 28. Final Recommended Production Stack

```text
Canonical entities:
WordPress + JetEngine

Localization:
WPML

Stable identity:
ToPersia Entity UID

Canonical geographic data:
JetEngine coordinates / future explicit geometry

Query layer:
Map Query Abstraction

Derived spatial acceleration:
Custom ToPersia Geo Index when benchmarks justify it

API:
Custom ToPersia Map Data REST API

Phase-A map delivery:
GeoJSON

Large-scale future delivery:
MVT / entity PMTiles if benchmarks justify it

Renderer:
existing MapLibre-based TopersiaMap

Basemap:
Protomaps / OSM-derived vector data

Packaging:
PMTiles

Hosting:
ToPersia-controlled/self-hosted/CDN storage

Client optimization:
IntersectionObserver
+
GeoJSON clustering
+
viewport loading
+
request deduplication

Nearby:
Browser Geolocation
->
ToPersia nearby API
->
Map Query Layer

Navigation:
provider adapters
+
verified Google/Waze/Neshan/Balad deep links as available

Caching:
existing ToPersia central cache
+
normalized spatial keys
+
index versioning
```

---

# 29. Final Architecture Statement

> **ToPersia Map Engine is not another Places database.**
>
> It is the geospatial rendering and access layer over the existing ToPersia Entity Graph.

```text
JetEngine = canonical entity truth
WPML = localization truth
Entity UID = stable identity
Geo Index = optional derived query accelerator
Map API = stable public map contract
Map Source Adapter = delivery abstraction
TopersiaMap = map lifecycle/UI adapter
MapLibre = renderer
PMTiles = static tile packaging
Protomaps/OSM = basemap/context
Google/Waze/Neshan/Balad = navigation endpoints
```

No layer should quietly become another layer's source of truth.

Claude has both permission and obligation to change the implementation when the live repository, production database, verified provider documentation, or benchmarks demonstrate a better solution.

---

# 30. Source / Verification Notes

Repository-derived evidence:

- Existing ToPersia map foundation and current architecture: source audit in `ToPersia_Map_Engine_Claude_Decision_Brief.md`.
- Second architecture proposal supplied in `Pasted markdown(2).md`.

Current external verification used for this brief:

- MapLibre RTL: https://maplibre.org/maplibre-gl-js/docs/API/functions/setRTLTextPlugin/
- MapLibre v6 migration: https://maplibre.org/maplibre-gl-js/docs/guides/v5-to-v6-migration-guide/
- MapLibre clustering: https://maplibre.org/maplibre-style-spec/sources/
- MapLibre GeoJSON cluster inspection: https://maplibre.org/maplibre-gl-js/docs/API/classes/GeoJSONSource/
- MySQL spatial indexes: https://dev.mysql.com/doc/refman/8.0/en/spatial-index-optimization.html
- MySQL spatial distance: https://dev.mysql.com/doc/refman/8.0/en/spatial-convenience-functions.html
- Google Maps URLs: https://developers.google.com/maps/documentation/urls/get-started

**Important:** Provider-specific Neshan/Balad deep-link behavior is intentionally left as a verification gate rather than asserted as fact.
