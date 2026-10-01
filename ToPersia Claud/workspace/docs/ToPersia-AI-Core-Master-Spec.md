# ToPersia AI Core — Master Architecture & Claude Code Implementation Specification

**Document status:** Implementation Master Specification  
**Project:** ToPersia  
**Repository:** `https://github.com/abashash/ToPersia`  
**Target:** WordPress + Elementor Pro + JetEngine/Crocoblock + JetReviews + WPML + MySQL + WordPress REST API + PHP/JavaScript  
**Primary goal:** Turn ToPersia into a structured, entity-first, AI-powered tourism platform for Iran.

---

# 0. How to use this document

This document is the implementation contract for Claude Code.

It combines:

1. The architecture research and critique developed during this conversation.
2. The additional Gemini research supplied by the project owner.
3. Product patterns researched from Mindtrip and iPlan.ai.
4. Corrections made where the supplied research was too certain, unsafe, or over-engineered.
5. A phase-by-phase implementation contract designed for the actual ToPersia WordPress environment.

**This document is NOT permission to immediately rewrite the site.**

Claude Code MUST first inspect the real current ToPersia environment and produce a verified audit. Any statement that cannot be verified from the repository, local WAMP installation, WordPress database, or plugin runtime MUST be marked `UNKNOWN` rather than inferred.

---

# 1. Core product decision

ToPersia is NOT a generic AI chatbot.

The product should become:

> **A structured, entity-first, AI-powered tourism platform for Iran.**

AI is a capability layer behind the product.

The primary product surfaces are:

```text
Explore
Search
Near Me
Save
Collections
Trips
Maps
Recommendations
Audio
Travel Planning
AI-assisted discovery
```

The user should normally experience **ToPersia AI**, not ChatGPT, Claude, Gemini, or another vendor brand.

Provider names belong primarily in Admin diagnostics, benchmarks, and technical transparency—not in the default user experience.

---

# 2. Non-negotiable engineering rules

## 2.1 Audit before implementation

Before changing code:

- inspect Git branch and current commit
- inspect repository tree
- inspect local WordPress/WAMP installation
- inspect active and inactive plugins
- inspect themes and child themes
- inspect mu-plugins
- inspect plugin custom code
- inspect REST routes
- inspect AJAX handlers
- inspect hooks
- inspect cron and scheduled jobs
- inspect custom database tables
- inspect relevant options
- inspect transients/object cache usage
- inspect authentication/user implementation
- inspect user meta
- inspect JetEngine configuration
- inspect CPTs/CCTs/meta/relations/taxonomies/listings/query builder/forms/profile builder/maps/dynamic visibility
- inspect WPML configuration
- inspect Elementor integrations
- inspect JetReviews
- inspect all current AI-related plugins/features
- inspect existing audio/summary/planner/recommendation/content generation code
- inspect current geo/location architecture
- inspect current plugin ZIPs and internal source where available

Do not delete anything during Phase 0.

## 2.2 Preserve functionality

No current AI plugin, feature, endpoint, CPT, CCT, relation, profile field, or user workflow may be removed simply because AI Core is being introduced.

Migration order:

```text
Inventory
→ Dependency Map
→ Feature Map
→ Data Map
→ API Map
→ Storage Map
→ Compatibility Test
→ Parity Plan
→ Migration
→ Verification
→ Disable
→ Observe
→ Remove only when safe
```

## 2.3 No big-bang rewrite

Implement small phases with:

```text
Inspect
→ Plan
→ Implement
→ Test
→ Review
→ Document
→ Commit
```

## 2.4 WordPress-native first

The production architecture MUST remain compatible with:

- WordPress
- Elementor Pro
- JetEngine/Crocoblock
- JetReviews
- WPML
- MySQL
- WordPress REST API
- PHP
- JavaScript

Node.js is not a production requirement unless future evidence proves it necessary.

## 2.5 Do not over-engineer

Do not introduce without measurable justification:

- microservices
- Kubernetes
- Kafka
- multiple databases
- external AI orchestration
- vector database
- Elasticsearch/OpenSearch
- autonomous agent frameworks
- second queue system

## 2.6 AI is never the source of truth

AI may interpret, summarize, personalize, rank, generate a grounded script, or create a travel plan proposal.

AI must NOT become the canonical source for:

- tourism entities
- coordinates
- relations
- official taxonomy
- translation state
- WordPress user identity
- canonical content
- verified opening hours
- real availability
- official prices
- booking status

---

# 3. Architecture principles

The request pipeline should generally follow:

```text
Permission / Security
        ↓
Rules / Gate
        ↓
Database / Entity Retrieval
        ↓
Cache
        ↓
Deterministic Processing
        ↓
Cheap AI only if needed
        ↓
Strong AI only if justified
        ↓
Structured Validation
        ↓
Persist / Cache Artifact
```

This is the core economic and reliability principle of ToPersia.

More precise than simply saying `AI-when-needed`:

> **Rules-first → Data-first → Cache-first → Deterministic-first → AI-when-needed.**

---

# 4. Source of Truth boundaries

| Domain | Source of Truth |
|---|---|
| Tourism entities | JetEngine / WordPress data model as verified by Audit |
| CPT/CCT schema | Existing JetEngine configuration |
| Meta fields | JetEngine / WordPress data model |
| Relations | JetEngine |
| Taxonomies | WordPress / JetEngine |
| Listings / Query Builder | JetEngine |
| User identity | WordPress |
| User travel profile | Existing JetEngine/user-meta implementation after Audit |
| Reviews / UGC | JetReviews / current verified implementation |
| Translation state | WPML |
| Page presentation | Elementor Pro / current theme |
| Audio binary | WordPress Media Library |
| AI execution state | ToPersia AI Core custom tables |
| AI cache | ToPersia AI Core custom tables |
| AI run/usage/cost telemetry | ToPersia AI Core custom table(s) |
| Credits ledger | ToPersia AI Core |
| Trips and trip versions | ToPersia AI Core |
| Background queue | Action Scheduler |
| Geo index, if required | Derived index only; JetEngine remains canonical |

AI Core must reference canonical entities rather than duplicate them.

---

# 5. Final component architecture

```text
                           TO PERSIA
                               │
              ┌────────────────┼────────────────┐
              │                │                │
          WordPress         JetEngine          WPML
              │                │                │
          Elementor       Entity Graph       Languages
          JetReviews      Profiles/Relations
              │                │
              └────────────────┼────────────────┘
                               │
                        WordPress REST
                               │
                               ▼
                    ┌────────────────────────┐
                    │   ToPersia AI Core     │
                    ├────────────────────────┤
                    │ Access / Security      │
                    │ Tourism Gate           │
                    │ Intent Engine          │
                    │ Entity Resolver        │
                    │ Context Builder        │
                    │ Candidate Engine       │
                    │ Scoring Engine         │
                    │ Itinerary Planner      │
                    │ Route Optimizer        │
                    │ AI Router + Policy     │
                    │ Provider Adapters      │
                    │ Validation             │
                    │ Cache                  │
                    │ Audio Engine           │
                    │ Trips / Collections    │
                    │ Recommendations        │
                    │ Quality                │
                    │ Moderation             │
                    │ Translation bridge     │
                    │ Credits                │
                    │ AI Runs / Cost        │
                    └───────────┬────────────┘
                                │
                     ┌──────────┼──────────┐
                     │          │          │
                  OpenAI    Anthropic   Gemini
                     │          │          │
                     └──────────┼──────────┘
                                │
                        ┌───────┴────────┐
                        │                │
                   Google TTS      ElevenLabs
                        │                │
                        └───────┬────────┘
                                ▼
                         Media Library

 Action Scheduler = background execution
 MySQL Custom Tables = durable AI state
 Redis = optional performance layer
 Vector DB = deferred
 Elasticsearch = deferred
```

---

# 6. Architecture choice

## Architecture A — Simple WordPress AI Core

Characteristics:
- synchronous provider calls
- basic Transients/object cache
- little persistent AI state
- minimal background processing

Decision: **NO-GO for full ToPersia AI platform.**

Useful only as a minimal prototype.

## Architecture B — WordPress + AI Core + Queue + Cache

Characteristics:
- WordPress remains application runtime
- centralized AI Core plugin
- Action Scheduler for background work
- custom MySQL tables for durable AI state/cache/telemetry
- optional Redis
- provider abstraction
- deterministic domain engines
- structured output

Decision: **FINAL ARCHITECTURE.**

## Architecture C — External AI Orchestration Service

Characteristics:
- separate application/server
- WordPress becomes client/backend source
- independent worker scaling
- additional networking/security/deployment boundary

Decision: **DEFER.**

Introduce only when measurable scale or operational requirements justify it.

---

# 7. Agent decision

## Central autonomous AI agent: REMOVE

Do not build an unrestricted agent that can choose arbitrary tools/providers/actions.

Reasons:

- unpredictable cost
- unpredictable latency
- harder debugging
- larger security surface
- harder reproducibility
- harder policy control
- unnecessary for the current product

## Replace with

> **AI Router + Policy Engine + bounded feature workflows**

The Travel Planner can be multi-step, but every step is explicit and constrained.

```text
Feature
→ Policy
→ Context
→ Provider
→ Structured Output
→ Validator
→ Domain Engine
→ Persist
```

---

# 8. Tourism Gate

Every interactive AI workflow must pass through a gate before expensive generation.

```text
User Request
    ↓
Normalize
    ↓
Authentication / Permission
    ↓
Hard Safety Rules
    ↓
Tourism Gate
    ↓
Cheap Intent Classification
    ↓
Structured Intent
    ↓
Feature Router
```

For clearly non-tourism requests:

```text
Paid LLM generation = 0
```

Return a fixed response.

Example:

> من دستیار سفر و گردشگری ToPersia هستم و فقط می‌توانم درباره سفر، گردشگری، مقصدها و برنامه‌ریزی سفر کمک کنم.

---

# 9. Tourism Gate classifier strategy

Do not use keyword matching alone.

Use layered classification:

### Layer 1 — deterministic

- exact intent patterns
- regex
- request shape
- hard block rules
- feature route patterns

### Layer 2 — cheap semantic classifier

Use a low-cost model only when necessary.

Potential categories:

```text
tourism_search
destination_question
entity_question
recommendation
near_me
trip_create
trip_modify
trip_optimize
travel_profile
audio
content_question
start_anywhere
other
unsafe
```

### Layer 3 — ambiguous semantic classification

Only ambiguous requests receive additional model evaluation.

### Layer 4 — generation

Only accepted, structured tourism tasks enter the expensive generation layer.

---

# 10. Jev decision

Jev must NOT be a required core dependency until independently benchmarked.

Benchmark against simpler alternatives using a real Persian dataset.

Minimum benchmark set:

- obvious tourism requests
- obvious non-tourism requests
- ambiguous Persian requests
- colloquial Persian
- spelling errors
- mixed Persian/English
- travel plan edits
- adversarial prompts
- injection attempts

Measure:

- precision
- recall
- false positive rate
- false negative rate
- p50 latency
- p95 latency
- cost/request
- stability

Decision rule:

```text
If Jev materially improves accuracy, latency, or cost:
    keep it in a narrowly defined classifier role.
Else:
    do not add the dependency.
```

Jev must never become the content generator or central brain.

---

# 11. Provider architecture

Candidate text providers:

- OpenAI
- Anthropic
- Google Gemini

Voice providers:

- Google Cloud Text-to-Speech
- ElevenLabs

Do not hard-code provider selection into feature modules.

Use:

```text
ProviderRegistry
ModelRegistry
ProviderAdapter
RouterPolicy
TaskPolicy
FallbackPolicy
```

Route by task, not brand.

---

# 12. Provider policy

Initial policy only; benchmark and configure rather than hard-code:

| Task | Default policy | Fallback |
|---|---|---|
| Tourism Gate | cheap classifier | alternate cheap classifier |
| Summary | one canonical production provider | alternate provider |
| Travel Planner | strong structured model | alternate strong model |
| Complex planner | premium reasoning/generation | alternate strong model |
| Storytelling | strong grounded generation | alternate strong model |
| Quality semantic checks | cheap model first | stronger model if needed |
| Recommendation explanation | cheap/medium model | premium only when justified |
| Moderation | deterministic + moderation/classifier | second classifier |
| Translation | WPML-controlled workflow | configured provider |
| Read Aloud | Google TTS baseline | ElevenLabs |
| Story/Podcast Voice | ElevenLabs premium candidate | Google TTS |

The exact model IDs, prices, and availability MUST be verified at implementation time from official provider documentation.

---

# 13. Summary architecture

Do NOT fan out every public request to Claude + OpenAI + Gemini.

Create:

> **Canonical ToPersia AI Summary**

The provider is an internal implementation detail.

Normal frontend:

```text
AI Summary

AI-generated
```

Admin sees:

- provider
- model
- prompt version
- source hash
- schema version
- cost
- latency
- cache hit

---

# 14. Summary cache identity

Do not use only `MD5(post_content)`.

Build a canonical source payload containing only fields relevant to the task.

Potential components:

```text
site_version
entity_type
entity_id
language
source_version
content_hash
prompt_version
schema_version
policy_version
model_family
```

Generate a SHA-256 cache identity over a canonical normalized representation.

Relevant source data may include:

- title
- content
- excerpt
- relevant meta
- taxonomies
- relations
- geographic fields
- source facts
- entity status

When relevant data changes:

```text
old source hash != new source hash
```

A new artifact is generated.

Prompt/model/schema/policy version changes create a new identity.

---

# 15. Audio system

Use one Audio Engine with three modes:

```text
read_aloud
storytelling
podcast
```

## Read Aloud

For:
- News
- Article
- Report
- Guide

Pipeline:

```text
Published Text
→ Text Normalizer
→ TTS
→ Media Library
```

Do not turn news/articles into stories.

## Storytelling

For:
- Province
- City
- Attraction
- Historical Site
- UNESCO
- Museum
- Nature
- Culture
- Food
- Heritage
- selected Restaurant/Hotel/Event/Guide

Pipeline:

```text
Verified Source Context
→ Grounded Script Generation
→ Fact Validation
→ Approved Script
→ TTS
→ Media Library
```

## Podcast

Use the same script engine with a richer podcast profile.

Do not create a completely separate AI infrastructure stack for podcasts.

---

# 16. Audio grounding rules

The LLM must not invent:

- dates
- historical claims
- UNESCO status
- coordinates
- operating hours
- prices
- availability
- official facts
- citations

If a generated statement cannot be supported by supplied trusted context, mark it for review or omit it.

---

# 17. TTS strategy

Use:

### Google TTS
Operational/default candidate for:
- bulk read-aloud
- news/articles
- predictable cost
- broad deployment

### ElevenLabs
Premium candidate for:
- expressive storytelling
- premium podcast voice
- higher emotional/characterful narration

Final selection must be benchmarked using the actual ToPersia corpus:

- Persian
- English
- Arabic
- place names
- historical names
- numbers
- dates
- mixed-script text
- long-form consistency

Do not claim one provider is universally better before benchmark evidence.

---

# 18. TTS cache identity

Use:

```text
entity_id
language
content_hash
script_hash
provider
voice_id
voice_model
voice_style
audio_schema_version
```

If identical, reuse audio.

Audio binary belongs in WordPress Media Library.

AI Core stores its metadata and provenance.

---

# 19. Travel Planner product model

Travel Planner is a structured product, not an open chat window.

User inputs:

- destination
- start date
- end date
- duration
- budget
- travel style
- interests
- companion
- walking tolerance
- food preferences
- accommodation preferences
- transportation preference
- preferred time of day
- local experience preference
- trip pace
- accessibility needs where relevant

Persistent profile may additionally contain:

- language
- dietary restrictions
- transport preference
- time-of-day preference
- accessibility preference
- children age range profile where explicitly useful

Trip-specific values must remain in the Trip object instead of permanently contaminating user profile state.

---

# 20. Travel Planner scope

Allowed domain:

- travel
- tourism
- destination
- itinerary
- hotels
- restaurants
- attractions
- transportation
- culture
- local experiences
- travel logistics
- trip modification

Unsupported examples:

- write arbitrary poetry
- write PHP
- solve unrelated mathematics
- general programming assistance
- unrelated politics/news analysis
- unrestricted general knowledge chat

Unsupported request:
- should be rejected before expensive generation whenever the system can determine that deterministically.

---

# 21. Travel Planner architecture

```text
User
 ↓
Auth / Credits
 ↓
Tourism Gate
 ↓
Trip Intent Parser
 ↓
Entity Retrieval
 ↓
Candidate Generator
 ↓
Constraint Engine
 ↓
LLM Planner
 ↓
Structured JSON
 ↓
Entity Validation
 ↓
Itinerary Optimizer
 ↓
Time/Geo Validation
 ↓
Trip Version
 ↓
Frontend
```

The LLM answers primarily:

> What is appropriate?

The deterministic itinerary engine answers:

> How can these choices be assembled into a feasible schedule?

---

# 22. Entity references inside AI output

The model must never be trusted to invent real WordPress/JetEngine IDs.

Use internal candidate references:

```json
{
  "candidate_ref": "candidate_17"
}
```

Server maps:

```text
candidate_17
→ verified ToPersia entity ID
```

If no verified match exists:

```text
editorial_review_required = true
```

AI must never auto-create tourism entities.

---

# 23. Itinerary optimization engine

Build a deterministic, testable domain service.

Inputs:

- candidate entities
- latitude/longitude
- day start/end
- time windows
- visit duration
- travel time when available
- walking tolerance
- transport mode
- priority
- meal windows
- pinned items
- locked items
- accessibility constraints

Outputs:

- ordered items
- time ranges
- travel segments
- warnings
- conflicts

Required optimization concepts:

- geographic clustering
- attraction grouping
- reasonable travel sequence
- opening window compatibility where verified
- meal placement
- duration capacity
- walking compatibility
- pinned-place preservation
- conflict detection

Do not ask the LLM to be the route engine.

---

# 24. Incremental itinerary editing

This is mandatory.

Example:

> روز سوم یک رستوران سنتی اضافه کن.

Convert to structured operation:

```json
{
  "operation": "modify_itinerary",
  "scope": {
    "type": "day",
    "day": 3
  },
  "action": "insert",
  "entity_type": "restaurant"
}
```

Recompute only affected scope where possible.

Do not regenerate all four days by default.

Supported operations:

- add
- remove
- replace
- move
- reorder
- pin
- unpin
- make day lighter
- optimize walking
- optimize route
- replace meal
- add restaurant
- replace attraction
- change day theme

Manual UI edits cost 0 AI credits.

AI-intensive changes may consume credits.

---

# 25. Trip versioning

Trips are first-class domain objects.

Use:

```text
Trip
 ├── current version
 ├── version history
 ├── items
 ├── saved places
 ├── collections
 ├── notes
 └── future booking metadata
```

Each AI or major structural modification should create a new version rather than destroying history.

---

# 26. Collections

Build Collections as a first-class feature.

Examples:

- Romantic Isfahan
- Persian Architecture
- Iranian Food
- Photography Spots
- Night Experiences

Collection can contain references to existing entities.

Do not duplicate entities.

Collection fields:

```text
id
user_id
title
description
destination_id nullable
visibility
cover_image
created_at
updated_at
```

Collection item:

```text
id
collection_id
entity_type
entity_id
note
position
added_by
created_at
```

---

# 27. Save Places

Saving is a reference, not a copy.

Use:

```text
user_id
entity_type
entity_id
collection_id nullable
saved_at
```

Support:

```text
Save
Unsave
Add to Collection
Move Collection
Add to Trip
```

These operations do NOT require AI and should cost 0 credits.

---

# 28. Save → Collection → Trip

Required user flow:

```text
Discover Entity
 ↓
Save
 ↓
Collection
 ↓
Select saved entities
 ↓
Create Trip
 ↓
Planner organizes them
```

This is a core product pattern inspired by leading AI travel products.

---

# 29. Start Anywhere

Phase 2.

Support future entry points such as:

- pasted URL
- selected text
- screenshot
- image
- PDF
- social/inspiration content where technically/legal permitted

Pipeline:

```text
Input
 ↓
Extraction
 ↓
Candidate Place Detection
 ↓
Entity Matching
 ↓
Confidence
 ↓
User Confirmation
 ↓
Save / Collection / Trip
```

Never auto-create a canonical entity from imported content.

Imported content is untrusted.

---

# 30. Magic Camera

DEFER.

Future capability:

```text
Camera
→ OCR / Vision
→ Entity Matching
→ Entity Page / Summary / Audio
```

Potential use cases:

- identify Persian monuments
- translate menus
- identify local foods
- interpret signage

Design domain interfaces now so this can be added without redesigning AI Core.

---

# 31. Voice interaction

DEFER for first release.

Future architecture:

```text
Voice Input
 ↓
Speech-to-text
 ↓
Tourism Gate
 ↓
Structured Intent
 ↓
Trip/Search/Recommendation workflow
 ↓
Text or audio response
```

Do not build unrestricted voice chat.

---

# 32. Collaborative trips

Phase 2.

Support:

- shared collections
- shared trips
- member invitations
- owner/editor/viewer roles
- add/remove/reorder places
- notes
- activity history

Later, optionally add voting.

Model:

```text
trip_id
user_id
role
status
joined_at
```

---

# 33. Guest vs registered recommendation system

## Guest

General recommendations driven by:

- current entity
- destination
- category
- relations
- taxonomy
- editorial priority
- popularity
- proximity
- context

Usually no LLM.

## Registered

Personalized recommendations driven by:

- destination
- profile
- saved places
- collections
- previous trips
- interests
- style
- walking preference
- companion context
- current entity

Pipeline:

```text
Candidate Generation
 ↓
Deterministic Scoring
 ↓
Cache
 ↓
Optional AI Rerank / Explanation
 ↓
Cache
```

Do not send hundreds/thousands of candidates to an LLM.

Example reduction:

```text
1000 candidates
→ 100
→ 20
→ AI only if needed
```

---

# 34. Recommendation cache

Recommended identity:

```text
user_id
profile_version
destination_id
language
entity_graph_version
algorithm_version
policy_version
context_version
```

Optional when relevant:

- time bucket
- current entity
- seasonal context

Use versioning to invalidate logically rather than deleting huge cache volumes.

When profile changes:

```text
profile_version++
```

When recommendation algorithm changes:

```text
algorithm_version++
```

---

# 35. Near Me

Near Me is primarily a geo/database feature.

```text
Browser Permission
 ↓
Latitude/Longitude
 ↓
ToPersia REST
 ↓
Geo Search
 ↓
Entity Filters
 ↓
Distance
 ↓
Results
```

Do not send precise location to the AI provider unless explicitly needed for a supported task.

AI may optionally answer:

> Which of these nearby places best matches my interests?

Primary navigation is delegated to configured external navigation apps:

- Google Maps
- Waze
- Apple Maps
- other supported apps

AI must not be the primary route calculator.

---

# 36. Geo index

During Phase 0 determine whether current JetEngine location fields support required geo queries at scale.

If not, create a derived index such as:

`wp_topersia_geo_index`

Suggested fields:

```text
id
entity_type
entity_id
latitude
longitude
geohash
city_id
province_id
updated_at
```

This is an index, not the source of truth.

Do not duplicate entire entities.

---

# 37. Search architecture

Example:

> رستوران سنتی نزدیک نقش جهان

Pipeline:

```text
Normalize
 ↓
Intent/category detection
 ↓
Location resolution
 ↓
Geo query
 ↓
JetEngine/ToPersia filters
 ↓
Taxonomy / relation filters
 ↓
Distance sort
 ↓
Results
```

No LLM in normal operation.

LLM may be invoked for:

- semantic ambiguity
- explanation
- comparison
- personalized ranking
- itinerary integration

---

# 38. User profile

Existing proposed interests:

- History
- Architecture
- Nature
- Food
- Culture
- Art
- Shopping
- Adventure
- Photography
- Museums
- Nightlife
- Local Experiences

Travel style:

- Relaxed
- Balanced
- Intensive

Companion:

- Solo
- Couple
- Family
- Friends

Budget:

- Budget
- Medium
- Comfortable
- Luxury

Walking:

- Low
- Medium
- High

Local Experience:

- Low
- Medium
- High

Potential additions after audit validation:

- Preferred transport
- Preferred time of day
- Dietary restrictions
- Accessibility requirements
- Trip pace
- Preferred language
- children age range when relevant

Do not add fields simply because they exist in another product. Each field must have a measurable personalization benefit.

---

# 39. AI Quality Engine

Admin/editor-facing feature.

Split checks into two systems.

## Deterministic checks

- required fields
- coordinates
- image existence
- relation integrity
- taxonomy presence
- translations
- links
- URLs
- schema requirements
- SEO fields
- audio existence
- stale data
- duplicate references
- invalid entity IDs

## Semantic AI checks

- factual consistency against trusted sources
- clarity
- readability
- editorial quality
- entity consistency
- source completeness
- GEO/AEO suggestions
- multilingual quality

Do not send everything to an LLM.

---

# 40. Moderation architecture

For:

- reviews
- comments
- user submissions

Use:

```text
Rate limiting
 ↓
Deterministic spam rules
 ↓
Duplicate heuristics
 ↓
Moderation classifier
 ↓
Human review if ambiguous
```

Avoid AI-only moderation.

---

# 41. WPML integration

WPML remains the translation authority.

AI Core does not replace WPML.

AI Core may:

- request translation work
- prepare translation payloads
- orchestrate provider execution where appropriate
- track costs/status
- validate translation output

WPML remains responsible for:

- language state
- translation relationships
- translation publishing workflow
- source/target language ownership

Supported project languages:

- Persian
- English
- Arabic
- French
- German
- Italian
- Russian
- Spanish
- Turkish
- Simplified Chinese

Do not assume specific WPML hook names without checking the current installed WPML version and official documentation.

---

# 42. Queue architecture

Use **Action Scheduler** as the WordPress-native background queue.

WP-Cron is a trigger mechanism, not the domain queue model.

Do not create a second general-purpose AI queue.

Typical jobs:

```text
generate_summary
generate_story_script
generate_podcast_script
generate_tts
quality_check
translation_request
recommendation_refresh
entity_reindex
geo_reindex
moderation_check
trip_ai_generation
trip_ai_patch
trip_ai_optimize
```

Every job must support:

- idempotency
- retries
- exponential backoff
- timeout awareness
- locking
- status
- manual retry
- failure reason

Publishing content must not wait for AI jobs.

---

# 43. AI job idempotency

Use a deterministic job identity based on relevant dimensions such as:

```text
task
entity_id
language
source_hash
prompt_version
schema_version
policy_version
```

If an equivalent job already completed successfully, do not repeat paid work.

---

# 44. Cache architecture

## L1

Per-request/runtime object cache.

## L2

Redis if available.

## L3

Durable AI Core MySQL cache.

## Media

WordPress Media Library for audio.

Do not use Transients as the primary durable storage for large AI results.

Redis is not a source of truth.

If Redis disappears, the system must continue using MySQL.

---

# 45. Redis decision

Shared hosting:

- not required
- optional

VPS:

- recommended as a performance layer if metrics justify it

Redis may support:

- hot cache
- object cache
- rate-limit counters
- short-lived locks

Do not store the canonical AI artifact only in Redis.

---

# 46. Database architecture

Use custom tables for AI operational state, not canonical tourism content.

Recommended baseline:

## `wp_topersia_ai_cache`

Suggested fields:

```text
id
cache_key
feature
task
entity_type
entity_id
user_id nullable
language
source_version
content_hash
prompt_version
schema_version
policy_version
provider
model
status
payload
payload_format
expires_at
created_at
updated_at
```

Indexes should be chosen after query profiling.

`cache_key` should be uniquely constrained where semantics allow.

## `wp_topersia_ai_runs`

Use one table for execution telemetry rather than duplicating logs and usage tables unnecessarily.

```text
id
request_id
feature
task
user_id nullable
entity_type nullable
entity_id nullable
provider
model
status
cache_hit
input_tokens
cached_input_tokens
output_tokens
estimated_cost
latency_ms
attempt
error_code
created_at
```

This is the source for:

- usage
- cost dashboards
- execution logs
- diagnostics

## `wp_topersia_ai_credits_ledger`

```text
id
user_id
transaction_type
operation
amount
balance_after
request_id
trip_id nullable
status
created_at
```

Use ledger semantics rather than a single mutable balance field.

## `wp_topersia_ai_prompts`

```text
id
task
language
version
status
prompt_hash
provider_scope
metadata
created_at
updated_at
```

Default prompt source should remain in version-controlled plugin files.

Database may hold:

- active version
- override
- metadata
- hash

## `wp_topersia_ai_trips`

```text
id
user_id
destination_entity_id
start_date
end_date
status
current_version_id
created_at
updated_at
```

## `wp_topersia_ai_trip_versions`

```text
id
trip_id
version_number
parent_version_id
operation
patch_json
snapshot_hash
created_at
```

## `wp_topersia_ai_trip_items`

```text
id
version_id
day_number
entity_type
entity_id
start_time
end_time
sequence
duration_minutes
travel_mode
source
notes
status
```

## `wp_topersia_geo_index`

Only add after Phase 0 proves the existing data model needs a derived geo index.

---

# 47. Avoid redundant AI tables

Do NOT automatically create all of these as separate systems:

```text
ai_logs
ai_usage
ai_jobs
ai_cache
```

Instead:

- Action Scheduler = queue
- `ai_runs` = execution telemetry
- `ai_cache` = durable cached artifacts

Create another table only when a distinct domain state requires it.

---

# 48. Trip model vs JetEngine

Canonical tourism entities and persistent user profile remain in JetEngine/WordPress.

Trips may live in AI Core because they require:

- versioning
- patch history
- AI provenance
- credit linkage
- structured operations
- immutable revisions

AI Core must expose read/write services that the frontend can render through Elementor/REST.

Do not duplicate tourism entity definitions inside trip tables.

---

# 49. Prompt management

Prompts belong in version-controlled files by default:

```text
prompts/
├── gate/
├── summary/
├── storytelling/
├── podcast/
├── planner/
├── recommendation/
├── quality/
└── moderation/
```

Version explicitly:

```text
summary_v1
summary_v2
planner_v1
planner_v2
```

Cache identities must include prompt version.

Prompt changes should naturally produce a new cache generation rather than require destructive deletion of all prior artifacts.

---

# 50. Security architecture

## API keys

Never expose provider credentials to frontend JavaScript.

Preferred:

- environment/server config on VPS
- secure/encrypted server-side settings on constrained hosting

## REST

All protected routes must implement proper `permission_callback` logic.

Authentication and authorization are separate concerns.

Nonces provide CSRF protection where applicable; they are not a replacement for authorization.

## Input validation

Validate:

- type
- size
- encoding
- allowed values
- schema
- authentication
- ownership

Sanitization is not a complete prompt-injection defense.

## Prompt injection

Treat content as data, never instructions.

Trust boundary:

```text
SYSTEM POLICY
DEVELOPER POLICY
TOOL POLICY
UNTRUSTED CONTEXT
USER DATA
```

Never put provider secrets into model context.

---

# 51. Tool security

If the model can trigger tools, tools must be allowlisted and domain-specific.

Allowed examples:

```text
search_entity
get_entity
get_related_entities
get_geo_candidates
get_user_profile
get_saved_places
get_collection
get_trip
update_trip_patch
```

Never expose generic model-controlled tools for:

- shell
- PHP execution
- arbitrary SQL
- filesystem access
- arbitrary HTTP fetch
- WordPress admin actions
- plugin installation
- arbitrary external API invocation

---

# 52. SSRF protection

LLM must never be allowed to fetch arbitrary URLs directly.

External retrieval must pass through deterministic server-side connectors with:

- URL scheme allowlist
- hostname restrictions where appropriate
- private-network blocking
- response size limits
- timeout
- content-type validation
- redirect control
- logging/redaction

---

# 53. PII and location privacy

Only send required user data to external providers.

Do not send exact location when a nearby entity query can answer the request locally.

Minimize:

- exact location
- unnecessary profile data
- unnecessary trip history
- raw user content

Provide deletion paths for user-owned AI state.

---

# 54. AI output safety

Every generation feature should have:

```text
Schema
→ Parse
→ Validate
→ Sanitize
→ Domain rules
→ Persist
```

AI-generated HTML must be sanitized.

AI-generated entity references must be validated server-side.

AI output must never automatically become executable code.

---

# 55. Credits architecture

Users see:

> Travel Credits: 8

Do not expose raw token billing as the primary product abstraction.

Use operation-based credits.

Example configurable baseline:

| Operation | Credit cost |
|---|---:|
| Initial itinerary | 2–3 |
| Simple AI day modification | 1 |
| Complex re-plan | 2–3 |
| AI optimization | 1 |
| Manual edit | 0 |
| Save | 0 |
| Collection | 0 |
| Map | 0 |
| Search | 0 |
| Near Me | 0 |
| Cached public result | 0 additional |

Exact pricing MUST be configurable and later calibrated against actual API cost.

---

# 56. Credit reservation

Use:

```text
Eligibility
 ↓
Reserve
 ↓
Execute
 ↓
Success → Commit
Failure → Release/Refund
```

The system must be safe under concurrent requests.

Use request IDs and transactional/atomic logic where supported.

---

# 57. Abuse protection

Implement configurable:

- per-user rate limit
- per-IP rate limit
- per-route rate limit
- concurrency limit
- daily limit
- monthly limit
- idempotency
- suspicious-pattern detection
- CAPTCHA only when warranted
- configurable account-age policy

Do not make arbitrary hard-coded account-age rules unless product policy explicitly requires them.

---

# 58. Frontend UX

The UI must not resemble a generic chatbot.

Primary controls:

```text
AI Summary
Listen
Listen to the Story
Plan My Trip
Recommended for You
Near Me
Save
Add to Collection
Add to Trip
Optimize My Day
```

Provider brands remain hidden by default.

---

# 59. Smart Prompt Pills

Use structured intent shortcuts.

Examples on a city page:

```text
Traditional restaurants
History itinerary
Best photo spots
Family activities
Walkable places nearby
Night experiences
Local experiences
```

Each pill maps to a deterministic intent object.

Example:

```json
{
  "intent": "recommendation",
  "destination": 123,
  "interest": "food",
  "style": "local"
}
```

Do not implement prompt pills as raw string interpolation only.

---

# 60. Trip UI

Trip should feel like an editable travel workspace.

```text
Trip
├── Overview
├── Map
├── Saved Ideas
├── Collections
├── Day 1
├── Day 2
├── Day 3
├── Day 4
├── Recommendations
├── Audio
└── Notes
```

User actions:

- drag/reorder
- remove
- add
- pin
- lock
- modify
- optimize
- save
- share later

Manual changes must not require AI unless AI intelligence is explicitly requested.

---

# 61. Responsive and mobile-first behavior

Mindtrip/iPlan-style patterns matter primarily because travel is a mobile activity.

Ensure:

- trip cards are touch-friendly
- map/list switching is fast
- day timeline is easy to scan
- saved places are fast to access
- audio controls remain persistent
- navigation handoff is obvious
- AI generation has clear loading state

---

# 62. PWA / offline readiness

DEFER full offline synchronization.

But keep APIs/state structures compatible with future cached trip snapshots:

```text
trip snapshot
saved entity snapshot
audio references
photo references
basic map metadata
```

Do not implement full offline merge/conflict resolution in V1.

---

# 63. Global travel product lessons

Use the following patterns as architecture inspiration, not UX/copy/code cloning.

## Mindtrip lessons

Adopt where useful:

- persistent trip context
- collections
- saved places
- map-centric discovery
- nearby discovery
- trip editing
- collaborative planning later
- Start Anywhere
- inspiration-to-trip flow
- voice overview later
- creator guides later
- rich trip container

Avoid copying:

- autonomous behavior without clear user control
- unnecessary vendor integrations in V1
- generic AI chat as the center of the product

## iPlan.ai lessons

Adopt where useful:

- editable itinerary
- reorder/move/remove
- map timeline
- route-aware planning
- restaurant along route
- external navigation handoff
- group/road-trip concepts
- offline-ready model later

Critical lesson:

A generated itinerary is not enough.

It must be **editable, geographically coherent, time-aware, and operationally feasible**.

---

# 64. Content capability matrix

| Content type | Summary | Story | Read Aloud | Podcast | Recommendation | Translation | Quality |
|---|---|---|---|---|---|---|---|
| Province | YES | YES | YES | YES | YES | YES | YES |
| City | YES | YES | YES | YES | YES | YES | YES |
| Attraction | YES | YES | YES | YES | YES | YES | YES |
| Historical Site | YES | YES | YES | YES | YES | YES | YES |
| UNESCO | YES | YES | YES | YES | YES | YES | YES |
| Museum | YES | YES | YES | YES | YES | YES | YES |
| Nature | YES | YES | YES | YES | YES | YES | YES |
| Culture | YES | YES | YES | YES | YES | YES | YES |
| Food | YES | YES | YES | YES | YES | YES | YES |
| Heritage | YES | YES | YES | YES | YES | YES | YES |
| Restaurant | YES | LIMITED | YES | LIMITED | YES | YES | YES |
| Hotel | YES | LIMITED | YES | LIMITED | YES | YES | YES |
| Event | YES | LIMITED | YES | OPTIONAL | YES | YES | YES |
| Guide | YES | YES | YES | YES | YES | YES | YES |
| Article | YES | NO | YES | OPTIONAL | YES | YES | YES |
| News | YES | NO | YES | OPTIONAL | LIMITED | YES | YES |
| Report | YES | NO | YES | OPTIONAL | LIMITED | YES | YES |

Feature availability must remain configurable by content type.

---

# 65. Entity-first content rule

Core entities:

- Province
- City
- Attraction
- Historical Site
- UNESCO
- Heritage
- Museum
- Nature
- Culture
- Food
- Restaurant
- Hotel
- Event
- Article
- News
- Report
- Guide

AI can enrich these entities but cannot redefine their identity.

---

# 66. Quality and publication policy

AI-generated results should normally be asynchronous.

Publishing flow:

```text
Editor publishes
 ↓
WordPress save completes
 ↓
Action Scheduler jobs created
 ↓
Summary / Audio / Quality / Translation etc.
```

Do not block content publication waiting for multiple external APIs.

---

# 67. Admin UX

WordPress-native menu:

```text
ToPersia AI
├── Dashboard
├── Features
│   ├── Summary
│   ├── Audio
│   ├── Travel Planner
│   ├── Recommendations
│   ├── Quality
│   └── Moderation
├── AI Infrastructure
│   ├── Providers
│   ├── Models
│   ├── Router
│   ├── Prompts
│   └── Settings
├── Operations
│   ├── Jobs
│   ├── Cache
│   ├── Runs
│   └── Errors
├── Finance
│   ├── Usage
│   ├── Costs
│   └── Credits
└── Diagnostics
    ├── Health
    ├── Tests
    └── System Checks
```

Use granular capabilities such as:

```text
manage_topersia_ai
manage_ai_providers
manage_ai_models
manage_ai_router
view_ai_usage
view_ai_costs
manage_ai_credits
manage_ai_prompts
review_ai_quality
review_ai_moderation
manage_ai_settings
```

Do not assume every administrator-facing feature should be available to all editors.

---

# 68. REST API architecture

Example public endpoints:

```text
GET  /topersia/v1/summary/{entity}
GET  /topersia/v1/audio/{entity}
GET  /topersia/v1/recommendations
POST /topersia/v1/search-intent
POST /topersia/v1/near-me
```

Authenticated endpoints:

```text
GET  /topersia/v1/profile
PUT  /topersia/v1/profile

GET  /topersia/v1/saved
POST /topersia/v1/saved

GET  /topersia/v1/collections
POST /topersia/v1/collections
POST /topersia/v1/collections/{id}/items

GET  /topersia/v1/trips
POST /topersia/v1/trips
GET  /topersia/v1/trips/{id}
POST /topersia/v1/trips/{id}/modify
POST /topersia/v1/trips/{id}/optimize
```

Admin examples:

```text
GET /topersia/v1/admin/runs
GET /topersia/v1/admin/cache
GET /topersia/v1/admin/jobs
GET /topersia/v1/admin/providers
GET /topersia/v1/admin/models
GET /topersia/v1/admin/costs
```

Every protected route MUST use correct authentication and server-side permission checks.

---

# 69. Plugin architecture

Recommended structure:

```text
topersia-ai-core/
├── topersia-ai-core.php
├── composer.json
├── uninstall.php
├── readme.md
│
├── src/
│   ├── Core/
│   ├── Config/
│   ├── Contracts/
│   ├── Providers/
│   │   ├── OpenAI/
│   │   ├── Anthropic/
│   │   ├── Gemini/
│   │   ├── GoogleTTS/
│   │   └── ElevenLabs/
│   ├── Router/
│   ├── Gate/
│   ├── Context/
│   ├── Entity/
│   ├── Search/
│   ├── Geo/
│   ├── Scoring/
│   ├── Itinerary/
│   ├── Audio/
│   ├── Collections/
│   ├── Trips/
│   ├── Recommendations/
│   ├── Cache/
│   ├── Jobs/
│   ├── Credits/
│   ├── Persistence/
│   ├── Security/
│   ├── Validation/
│   ├── Features/
│   │   ├── Summary/
│   │   ├── Audio/
│   │   ├── TravelPlanner/
│   │   ├── Recommendations/
│   │   ├── Collections/
│   │   ├── SavedPlaces/
│   │   ├── NearMe/
│   │   ├── Quality/
│   │   ├── Moderation/
│   │   ├── Translation/
│   │   └── StartAnywhere/
│   ├── Integrations/
│   │   ├── JetEngine/
│   │   ├── WPML/
│   │   ├── Elementor/
│   │   ├── JetReviews/
│   │   └── ActionScheduler/
│   ├── REST/
│   ├── Admin/
│   └── Support/
│
├── prompts/
├── assets/
├── migrations/
├── tests/
└── docs/
```

Use an explicit contract between layers.

Feature modules must not instantiate provider SDKs directly.

---

# 70. Layering rules

Enforce:

```text
Frontend / REST
      ↓
Application Services
      ↓
Domain Services
      ↓
Repositories / Adapters
      ↓
WordPress / JetEngine / WPML / MySQL / Providers
```

UI code must never contain API keys.

Feature modules must not bypass Router/Policy unless a documented exception exists.

---

# 71. Provider contracts

Create provider interfaces such as:

```php
interface TextGenerationProviderInterface {}
interface ClassificationProviderInterface {}
interface TtsProviderInterface {}
```

Provider adapters normalize provider-specific APIs into internal data structures.

The rest of the application should not depend directly on provider SDK response shapes.

---

# 72. AI run telemetry

Every paid or meaningful AI operation gets a request ID.

Track:

- request_id
- feature
- task
- user_id when applicable
- entity
- provider
- model
- cache_hit
- usage
- estimated cost
- latency
- status
- retry count
- error code

Never store secrets in logs.

---

# 73. Observability metrics

Minimum metrics:

```text
cache_hit_rate
cost_per_task
cost_per_user
cost_per_trip
latency_p50
latency_p95
error_rate
fallback_rate
structured_output_failure_rate
repair_rate
summary_regeneration_rate
queue_depth
job_failure_rate
credit_failure_rate
TTS_failure_rate
```

Optimize based on measurements, not assumptions.

---

# 74. Cost architecture

Priority order:

```text
Hard rules
 ↓
Database
 ↓
Existing entity graph
 ↓
Cache
 ↓
Deterministic scoring/optimization
 ↓
Cheap AI
 ↓
Strong AI only when necessary
```

High-cost operations generally include:

- full itinerary generation
- complex itinerary revisions
- long storytelling scripts
- large semantic quality checks
- repeated expensive regeneration

Low-cost operations:

- classification
- normalization
- extraction
- moderation classification

Nearly zero AI cost:

- database search
- geo search
- save/remove
- collection management
- manual trip editing
- cached summary/audio playback

---

# 75. Provider fallback

Bounded fallback:

```text
Primary
 ↓ fail
Fallback
 ↓ fail
Graceful error
```

No infinite loops.

Record fallback reason and cost.

Provider outage must never create uncontrolled retry storms.

---

# 76. Model benchmark framework

Create a benchmark system before hard-locking provider policies.

Tasks:

- Persian tourism classification
- summary
- travel planning
- incremental modification
- storytelling
- extraction
- recommendation explanation
- quality checks
- moderation

Metrics:

- quality
- factuality
- schema validity
- latency
- cost
- failure rate

Benchmark results should be documented and versioned.

---

# 77. Access model

## Public

- AI Summary
- Read Aloud
- Story Audio
- General Recommendations
- Search Intent
- Near Me
- Save may be offered only after login or optionally use temporary browser state, but persistent save requires account

## Registered User

- Travel Planner
- Saved Places
- Collections
- Personalized Recommendations
- Travel Profile
- Saved Trips
- Trip modification
- Trip optimization
- Credits
- Future collaboration

## Content Manager / Editor

- content quality tools
- moderation review
- translation review
- editorial AI tools according to capability

## Administrator

- providers
- secrets
- models
- router policies
- prompt versions/overrides
- costs
- credits
- system settings
- health
- diagnostics

---

# 78. Feature flags

Every major AI feature should be independently controllable:

```text
ai_summary_enabled
audio_enabled
storytelling_enabled
podcast_enabled
planner_enabled
personalization_enabled
recommendations_enabled
near_me_enabled
quality_enabled
moderation_enabled
translation_enabled
start_anywhere_enabled
collaboration_enabled
voice_enabled
magic_camera_enabled
```

Feature flags must not be hidden in arbitrary code constants.

---

# 79. Error model

Normalize provider failures into:

```text
retryable
non_retryable
quota
timeout
validation
unavailable
unsupported
security_blocked
```

Users receive human-readable errors.

Admins receive diagnostics without secrets.

---

# 80. Testing strategy

Use:

- unit tests
- integration tests
- REST tests
- database tests
- provider adapter mocks
- queue tests
- cache tests
- security tests
- AI adversarial tests
- browser tests where appropriate
- performance tests
- migration tests

Critical domain logic must be testable without making paid AI calls.

---

# 81. Required adversarial tests

## Tourism Gate

- obvious tourism
- obvious non-tourism
- ambiguous
- mixed language
- colloquial Persian
- spelling errors
- unsupported domain
- prompt injection

## Travel Planner

- 1 day
- long trip
- missing budget
- conflicting preferences
- low walking
- family travel
- multiple destinations
- closed entity
- duplicate candidate
- invalid entity reference

## Incremental updates

- Day 3 add restaurant
- nonexistent day
- impossible insert
- route conflict
- pinned-item conflict
- over-capacity day

## Summary

- same content
- changed content
- changed relation
- changed meta
- changed language
- changed prompt
- changed policy
- changed model family

## Audio

- same script/voice
- new voice
- changed script
- TTS failure
- long text
- multilingual text

## Security

- API key extraction
- prompt injection
- SSRF
- XSS
- malicious URL
- malicious PDF/image content
- privilege escalation
- arbitrary tool attempt

---

# 82. Risk register

Maintain at least these risks:

1. hallucinated historical facts
2. stale travel data
3. provider outage
4. model behavior drift
5. provider pricing changes
6. runaway AI cost
7. prompt injection
8. API key exposure
9. SSRF
10. PII leakage
11. location leakage
12. duplicate jobs
13. queue backlog
14. credit race condition
15. malformed structured output
16. invalid entity references
17. inaccurate geo data
18. translation mismatch
19. audio storage growth
20. TTS provider change
21. JetEngine schema drift
22. WPML integration drift
23. plugin conflict
24. XSS from AI output
25. moderation false positives
26. shared-hosting timeout
27. vendor lock-in
28. stale cache
29. duplicate SEO content
30. editorial overtrust of AI
31. external data licensing restrictions
32. unsupported source formats
33. accidental privacy retention
34. bad itinerary optimization
35. user confusion between AI suggestion and verified fact

Each risk must have:

- severity
- likelihood
- mitigation
- detection
- status
- owner

---

# 83. Build / Defer / Remove / Research More

| Feature / Technology | Decision |
|---|---|
| ToPersia AI Core | BUILD |
| Router + Policy | BUILD |
| Tourism Gate | BUILD |
| Structured Retrieval | BUILD |
| Entity Resolver | BUILD |
| Candidate Engine | BUILD |
| Deterministic Scoring | BUILD |
| Itinerary Optimizer | BUILD |
| Summary | BUILD |
| Read Aloud | BUILD |
| Grounded Storytelling | BUILD |
| Podcast | BUILD |
| Saved Places | BUILD |
| Collections | BUILD |
| Trips | BUILD |
| Trip Versioning | BUILD |
| Incremental Trip Patching | BUILD |
| Personalized Recommendations | BUILD |
| Near Me | BUILD |
| Quality Engine | BUILD |
| Moderation | BUILD |
| WPML bridge | BUILD |
| Credits Ledger | BUILD |
| Action Scheduler | BUILD |
| Custom AI tables | BUILD |
| Start Anywhere | DEFER / Phase 2 |
| Collaboration | DEFER / Phase 2 |
| Voice Interaction | DEFER |
| Magic Camera | DEFER |
| PWA/offline synchronization | DEFER |
| Booking marketplace | DEFER |
| Price tracking | DEFER |
| Creator ecosystem | DEFER |
| External AI orchestrator | DEFER |
| Redis | DEFER / optional |
| Elasticsearch/OpenSearch | DEFER |
| Vector DB | DEFER |
| Autonomous central agent | REMOVE |
| Generic unrestricted chatbot | REMOVE |
| Three-provider public Summary fan-out | REMOVE |
| LLM as primary geo search | REMOVE |
| LLM as primary navigation route calculator | REMOVE |
| Independent translation engine replacing WPML | REMOVE |
| Second full queue system | REMOVE |
| Direct client-to-LLM provider calls | REMOVE |
| Automatic AI-created canonical entities | REMOVE |
| Jev | RESEARCH MORE before dependency |

---

# 84. Mindtrip/iPlan-inspired future roadmap

## V1

- entity discovery
- search
- save
- collections
- trips
- map-aware itinerary
- AI planner
- day patching
- recommendation engine
- summary
- audio
- near me

## V2

- collaboration
- Start Anywhere
- screenshot/PDF inspiration import
- social inspiration extraction where permitted
- richer trip workspace
- voice input
- better proactive travel assistance

## V3

- Magic Camera
- offline/PWA
- live travel data integrations
- price/availability integrations
- booking ecosystem
- creator travel guides

All V2/V3 additions must reuse the same AI Core domain boundaries.

---

# 85. Phase 0 — READ-ONLY AUDIT

This phase MUST NOT change production behavior.

Create:

```text
workspace/docs/AI-CORE-AUDIT/
├── REPOSITORY.md
├── WORDPRESS.md
├── PLUGINS.md
├── THEMES.md
├── MU-PLUGINS.md
├── REST.md
├── AJAX.md
├── HOOKS.md
├── CRON.md
├── DATABASE.md
├── OPTIONS.md
├── TRANSIENTS.md
├── USERS.md
├── JETENGINE.md
├── WPML.md
├── ELEMENTOR.md
├── JETREVIEWS.md
├── EXISTING-AI.md
├── EXISTING-AUDIO.md
├── EXISTING-PLANNER.md
├── EXISTING-RECOMMENDATIONS.md
├── DEPENDENCIES.md
├── GEO.md
├── ENTITY-MAP.md
├── FEATURE-MAP.md
└── RISK-REGISTER.md
```

Also create:

```text
schema-map.json
dependency-map.json
feature-map.json
entity-map.json
api-map.json
storage-map.json
```

Phase 0 must answer:

- What exists?
- Where does it live?
- Which plugin owns it?
- Which data is stored where?
- Which APIs are called?
- Which current features must be preserved?
- Which code is reusable?
- Which code must be rewritten?
- What is duplicate?
- What is incompatible?
- What is unknown?

Do NOT remove plugins during Phase 0.

---

# 86. Phase 1 — AI Core Foundation

Build:

- plugin bootstrap
- OOP structure
- autoloading
- contracts
- configuration
- migration system
- provider registry
- model registry
- router
- policy engine
- tourism gate foundation
- security layer
- cache abstraction
- AI run telemetry
- Action Scheduler integration
- feature flags

No major user-facing AI feature migration yet.

Acceptance:

- plugin activates cleanly
- existing site still works
- database migration works
- rollback exists
- health screen exists
- provider secrets never reach frontend

---

# 87. Phase 2 — Summary

Build:

- canonical source builder
- content hash
- cache identity
- provider adapter
- structured summary output
- validation
- REST
- frontend summary component
- admin diagnostics

Acceptance:

- unchanged source causes zero new provider call
- changed source creates new artifact
- language isolation works
- prompt version isolation works
- cache hit works

---

# 88. Phase 3 — Audio

Order:

1. Read Aloud
2. Storytelling
3. Podcast

Acceptance:

- no duplicate generation
- deterministic audio identity
- Media Library artifact
- correct language/voice
- grounded storytelling
- no story transformation of ordinary articles/news

---

# 89. Phase 4 — Saved Places + Collections

Build:

- save/unsave
- collections
- add/remove entity
- notes
- position
- visibility

Acceptance:

- entity data is never duplicated
- ownership enforced
- fast queries
- mobile UI

---

# 90. Phase 5 — Travel Planner

Build:

- user profile integration
- trip domain
- trip versioning
- itinerary schema
- entity resolver
- candidate generator
- constraint engine
- planner
- validator
- credits

Acceptance:

- login required
- tourism scope enforced
- entity references verified
- structured output valid
- credits safe
- no generic chatbot behavior

---

# 91. Phase 6 — Incremental Planner + Optimization

Build:

- day patching
- add/remove/replace/move
- restaurant along route
- walking optimization
- day optimization
- route optimization
- conflict detection
- pinned items
- warnings

Acceptance:

- affected scope only is recomputed where possible
- prior versions remain available
- no impossible schedules pass validation

---

# 92. Phase 7 — Personalization + Recommendations

Build:

- profile_version
- candidate scoring
- saved-place signals
- collection signals
- trip history signals
- guest recommendation mode
- personalized mode
- optional AI reranking
- cache

Acceptance:

- guest/user modes differ
- profile changes invalidate logically
- no cross-user leakage

---

# 93. Phase 8 — Near Me + Geo

Build:

- location permission UI
- geo index if required
- nearby search
- category filters
- distance sorting
- external navigation handoff

Acceptance:

- location denied path works
- AI not required for basic result
- privacy rules enforced

---

# 94. Phase 9 — Quality + Moderation + WPML

Build:

- deterministic quality checks
- semantic quality checks
- moderation workflow
- WPML orchestration bridge
- editor review UI

Acceptance:

- deterministic checks remain deterministic
- ambiguous moderation reaches human review
- WPML remains authoritative

---

# 95. Phase 10 — Start Anywhere

Build:

- URL extraction
- screenshot/image extraction
- PDF extraction
- candidate entity matching
- confirmation workflow
- Save/Collection/Trip conversion

Acceptance:

- imported content remains untrusted
- no auto-created canonical entities
- malicious content cannot change system policy

---

# 96. Phase 11 — Collaboration / Voice / PWA

Only after the core is stable.

Build selectively based on metrics and product priority.

---

# 97. Phase 12 — Optimization

Only after telemetry proves need:

- Redis
- stronger caching
- batching
- prompt compression
- context compression
- database indexing
- queue tuning
- provider policy tuning

Do not add infrastructure just because the architecture can support it.

---

# 98. Phase 13 — Hardening

Run:

- security testing
- prompt injection testing
- SSRF testing
- rate-limit testing
- credit concurrency testing
- provider failover testing
- queue recovery testing
- load tests
- multilingual tests
- accessibility tests
- regression tests
- migration recovery tests

---

# 99. Required implementation documentation

Maintain:

```text
workspace/docs/
├── AI-CORE-ARCHITECTURE.md
├── AI-CORE-DATA-MODEL.md
├── AI-CORE-SECURITY.md
├── AI-CORE-COST.md
├── AI-CORE-PROVIDER-POLICY.md
├── AI-CORE-CACHE.md
├── AI-CORE-QUEUE.md
├── AI-CORE-TRAVEL-PLANNER.md
├── AI-CORE-RECOMMENDATIONS.md
├── AI-CORE-AUDIO.md
├── AI-CORE-WPML.md
├── AI-CORE-JETENGINE.md
├── AI-CORE-REST.md
├── AI-CORE-TESTING.md
├── AI-CORE-MIGRATION.md
├── AI-CORE-ROADMAP.md
└── ARCHITECTURE-DECISIONS.md
```

---

# 100. Git and change control

Before each phase:

- verify current branch
- verify clean or intentionally dirty state
- record current commit
- create branch if project workflow requires it

During phase:

- small commits
- descriptive messages
- no unrelated refactor
- no unrelated theme redesign
- no content cleanup mixed with infrastructure work

After phase:

- run tests
- inspect diff
- document DB changes
- document REST changes
- document migration
- document rollback
- report known issues

---

# 101. Phase report format

Claude Code must report after every phase:

```text
PHASE
STATUS
CURRENT COMMIT
FILES CHANGED
DB CHANGES
API CHANGES
FEATURES ADDED
FEATURES PRESERVED
MIGRATIONS
TEST RESULTS
SECURITY
PERFORMANCE
COST IMPACT
KNOWN ISSUES
ROLLBACK
NEXT PHASE
```

---

# 102. Final architecture acceptance criteria

The architecture is not considered complete until all are true:

1. WordPress remains the application runtime.
2. JetEngine remains canonical for tourism entities.
3. WPML remains translation authority.
4. Elementor remains presentation layer.
5. JetReviews remains review system unless audit proves otherwise.
6. AI Core is an isolated plugin domain layer.
7. Provider calls are centralized.
8. Tourism Gate precedes expensive generation.
9. Clearly unsupported requests can consume zero paid generation tokens.
10. Database-first search avoids unnecessary LLM usage.
11. Near Me avoids unnecessary AI use.
12. Travel Planner is login-protected.
13. Travel Planner is domain-bounded.
14. Travel Planner output is structured.
15. Entity references are verified.
16. Itinerary optimization is deterministic/testable.
17. Incremental trip edits avoid full regeneration when scope allows.
18. User credits are ledger-based and concurrency-safe.
19. Public outputs are cacheable.
20. Audio is deduplicated by script/content/voice identity.
21. AI runs are traceable.
22. Costs are measurable.
23. Jobs are retryable and idempotent.
24. Provider failover is bounded.
25. Redis is optional, not authoritative.
26. Vector DB is not required for V1.
27. Elasticsearch is not required for V1.
28. Node.js is not required for production.
29. API keys never reach frontend.
30. Prompt injection cannot directly execute privileged actions.
31. External URLs are not arbitrary model-controlled fetch targets.
32. AI cannot create canonical entities automatically.
33. WPML and JetEngine remain intact.
34. Existing AI features are migrated only after parity verification.
35. AI Core can be disabled without destroying the canonical content site.
36. The architecture can later accept Start Anywhere, Voice, Magic Camera, Collaboration, and live travel-data integrations without replacing the core domain model.

---

# 103. Final product architecture in one flow

```text
             DISCOVER
                 │
                 ▼
              SEARCH
                 │
                 ▼
               ENTITY
                 │
      ┌──────────┼──────────┐
      │          │          │
    Read      Listen      Save
      │          │          │
      │          │       Collection
      │          │          │
      └──────────┴──────┬───┘
                         │
                         ▼
                       TRIP
                         │
              ┌──────────┼──────────┐
              │          │          │
            Map       Itinerary   Ideas
              │          │          │
              │       Optimize      │
              │          │          │
              └──────────┼──────────┘
                         │
                         ▼
                    PERSONALIZE
                         │
                         ▼
                    RECOMMEND
                         │
                         ▼
                      NAVIGATE
```

AI operates behind the appropriate steps.

---

# 104. Final technical decisions

## BUILD

- ToPersia AI Core
- Provider abstraction
- Router + Policy
- Tourism Gate
- Structured retrieval
- Entity Resolver
- Candidate Engine
- Deterministic scoring
- Itinerary optimizer
- Summary
- Read Aloud
- Storytelling
- Podcast
- Saved Places
- Collections
- Trips
- Trip versioning
- Incremental editing
- Personalized recommendations
- Near Me
- Quality Engine
- Moderation
- WPML bridge
- Action Scheduler integration
- Custom AI tables
- Credits ledger
- AI run telemetry

## REMOVE

- generic unrestricted chatbot
- autonomous central agent
- public three-provider summary fan-out
- LLM as primary geo engine
- LLM as primary navigation engine
- separate translation system competing with WPML
- direct frontend provider calls
- automatic AI-created canonical entities
- second general-purpose queue
- arbitrary model-controlled server tools

## DEFER

- Redis
- vector database
- Elasticsearch/OpenSearch
- external AI orchestrator
- collaboration
- Start Anywhere
- voice interaction
- Magic Camera
- PWA/offline sync
- booking marketplace
- price alerts
- creator platform

## RESEARCH MORE

- Jev
- live data providers
- booking integrations
- production TTS benchmark
- future semantic search requirements

---

# 105. FINAL INSTRUCTION TO CLAUDE CODE

**Start with Phase 0 only.**

Do NOT implement the AI Core yet.

Do NOT:

- delete plugins
- delete features
- modify JetEngine configuration
- modify WPML configuration
- change canonical content
- create new production tables
- replace current AI plugins
- refactor unrelated code
- change frontend design broadly

First create the verified audit.

Separate every conclusion into:

```text
VERIFIED
INFERRED
ASSUMED
UNKNOWN
```

When the audit is complete, produce a concrete Phase 1 implementation plan grounded in the real current system.

Only then begin implementation.

The final result must remain:

> **A structured, entity-first, AI-powered tourism platform for Iran.**

AI must operate behind the scenes, consume external model APIs only where they create real value, use ToPersia data first, cache aggressively, protect user privacy, preserve WordPress/JetEngine/WPML integrity, and remain extensible for future capabilities without premature infrastructure complexity.

---

# Appendix A — Key architectural rules at a glance

```text
JETENGINE = canonical tourism entities
WPML = translation authority
ELEMENTOR = presentation
JETREVIEWS = reviews/UGC
AI CORE = AI capability + orchestration + cache + jobs + telemetry
ACTION SCHEDULER = background queue
MYSQL = durable AI state
REDIS = optional performance layer
MEDIA LIBRARY = audio artifacts
LLM = bounded intelligence, not system authority
ROUTER = deterministic policy
TOURISM GATE = scope control
ENTITY RESOLVER = verifies AI references
CANDIDATE ENGINE = database-first retrieval
ROUTE OPTIMIZER = deterministic feasibility
TRIP = first-class editable object
COLLECTION = first-class saved context
CREDITS = operation-based ledger
```

# Appendix B — Product philosophy

```text
Do not ask:
"Which AI should answer this?"

Ask:
"Can ToPersia answer this without AI?"

If not:
"What is the cheapest safe AI capability that adds value?"

If AI is required:
"What verified ToPersia context should it receive?"

Then:
"How do we validate the result and cache it?"
```

That sequence is the core design philosophy of ToPersia AI Core.
