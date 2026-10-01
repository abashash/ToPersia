---
name: topersia-ultimate-wordpress
description: >
  Ultimate Claude Skill for the ToPersia WordPress stack: Elementor + Elementor Pro +
  WoodMart/XTemos + JetEngine/Crocoblock + WPML. Use for UI/UX design, screenshot/Figma/
  HTML-to-Elementor conversion, Elementor JSON generation/repair, WoodMart widgets and theme
  settings, JetEngine CPT/meta/relations/query/listings/dynamic data, WPML multilingual
  architecture/translation, responsive layouts, accessibility, performance, and debugging.
---

# ToPersia Ultimate WordPress Skill

## Mission

Behave as if the following stack is already installed and understood:

- WordPress
- Elementor
- Elementor Pro
- WoodMart / XTemos
- WooCommerce when relevant
- JetEngine / Crocoblock
- WPML
- ToPersia's tourism content architecture

The skill has two responsibilities:

A. Understand the stack accurately.
B. Generate implementation-ready artifacts without inventing undocumented settings.

## The critical rule

"Understand the plugin" does NOT mean hallucinating every option.

For exact settings, widgetTypes, control IDs, meta keys, query parameters, or version-specific JSON:
1. prefer actual ToPersia exports/configuration;
2. then official documentation;
3. then the reference files in this skill;
4. then verified third-party examples.

Record assumptions whenever exact schema is not available.

## Product responsibility map

### Elementor
Presentation, page composition, containers, widgets, controls, responsive settings,
global colors/fonts, page settings, templates, Theme Builder.

Official data model:
- page root
- page_settings
- content tree
- element nodes
- container nodes
- widget nodes
- repeaters
- responsive values
- global references

Elementor's official developer docs say the JSON root uses `title`, `type`, `version`,
`page_settings`, and `content`; content is recursive and elements contain common fields.
See `references/elementor-data-model.md`.

### Elementor Pro
Use when Pro is actually active for:
- Theme Builder
- Site Logo / Site Title / Page Title
- Nav Menu
- Form
- Posts / Portfolio
- WooCommerce widgets
- marketing widgets
- dynamic/theme features supported by the installed release

Never label a feature Pro unless it is known to be Pro in the target installation/version.

### WoodMart / XTemos
WoodMart owns:
- theme-wide appearance
- header builder
- page settings
- product/shop/archive presentation
- theme-specific Elementor widgets
- WooCommerce enhancements
- presets/conditional theme settings
- reusable HTML Blocks where the theme requires them

Use `references/woodmart-stack.md`.

### JetEngine / Crocoblock
JetEngine owns structured/dynamic data:
- CPT
- CCT
- custom fields / meta
- meta boxes
- taxonomies
- relations and relation meta
- Query Builder
- Listings / Listing Grid
- Dynamic widgets
- Dynamic Visibility
- Components
- Options Pages
- REST API
- Map Listing
- Data Stores
- dynamic functions where enabled

Use `references/jetengine-stack.md`.

### WPML
WPML owns multilingual content and translation workflows:
- languages
- translation management
- translation editor
- translated media
- string translation
- custom fields translation
- page-builder widget translation
- `wpml-config.xml`
- translation of custom/third-party Elementor widgets

Use `references/wpml-stack.md`.

## Architecture rule for ToPersia

Presentation:
Elementor / Elementor Pro / WoodMart

Data:
JetEngine / WordPress / WooCommerce

Language:
WPML

Never flatten structured JetEngine data into hard-coded Elementor text just to produce a
pretty mockup.

Never solve a WoodMart Header Builder request by reproducing the entire header as an HTML
widget.

Never solve a WPML translation problem by duplicating language pages manually when WPML's
supported workflow can handle it.

## Elementor: complete conceptual model

### Root
```json
{
  "title": "Template Title",
  "type": "page",
  "version": "0.4",
  "page_settings": [],
  "content": []
}
```

### General element
```json
{
  "id": "a1b2c3d4",
  "elType": "element",
  "isInner": false,
  "settings": {},
  "elements": []
}
```

### Container
```json
{
  "id": "a1b2c3d4",
  "elType": "container",
  "isInner": false,
  "settings": {},
  "elements": []
}
```

### Widget
```json
{
  "id": "b2c3d4e5",
  "elType": "widget",
  "widgetType": "heading",
  "isInner": false,
  "settings": {},
  "elements": []
}
```

### Repeater
When a widget exposes a repeater control, preserve its exact array shape from the target
widget/export. Never invent item keys.

### Responsive
Elementor officially stores responsive values as separate keys beside the base value, e.g.
`control_name`, `control_name_tablet`, `control_name_mobile`, when that control supports
responsive values.

### Global styles
Elementor global styles are kit-level. References are stored under `settings.__globals__`
using strings such as:
`globals/colors?id=primary`
`globals/typography?id=secondary`

Use global references when the target site uses a design system.

## Elementor controls

Know the control families:
- data controls
- multi-value controls
- unit controls
- UI controls
- group controls
- regular controls
- responsive controls
- group controls
- repeaters
- conditional display
- selectors
- dynamic content
- global style controls

Use `references/elementor-controls.md`.

## Native widget policy

Native widget first.

Examples:
- heading -> heading
- paragraph -> text-editor
- CTA -> button
- image -> image
- icon+copy -> icon-box
- list -> icon-list
- image+copy -> image-box
- testimonial -> testimonial
- FAQ -> accordion/toggle
- gallery -> image-gallery
- carousel -> image-carousel
- form -> Pro Form when available
- navigation -> Pro Nav Menu / WoodMart Header Builder when appropriate
- dynamic listing -> JetEngine Listing Grid / Listing Item

HTML widget is not a generic page renderer.

## WoodMart policy

When `[XTemos] Elements` offers a better native component, prefer it over a homemade
Elementor composition.

When the request affects the global header, inspect WoodMart Header Builder first.

When the request affects theme-wide colors, typography, buttons, page titles, footer,
WooCommerce, shop/archive, or presets, inspect WoodMart Theme Settings first.

WoodMart theme settings can be global and then overridden by presets; do not blindly hard-code
local styles that fight the theme system.

## JetEngine policy

Use JetEngine when the requirement is data-driven.

### Content model
For every dynamic feature define:
- object type
- CPT/CCT
- fields
- field types
- taxonomy
- relations
- relation meta
- query
- listing item
- front-end component
- language behavior

### Meta fields

Know the standard field types and verify exact availability in the installed version:
Text, Date, Time, Datetime, Textarea, WYSIWYG, Switcher, Checkbox, Iconpicker, Media,
Gallery, Radio, Repeater, Select, Number, Colorpicker, Posts, HTML, Advanced Date, Map.

Important:
- field Label
- field Name/ID
- object/layout type
- Description
- required state where supported
- validation/range/options appropriate to field type

### Relations

Model relationships using JetEngine Relations rather than duplicated fields when the
relationship itself has meaning.

Relation meta is allowed and has its own field constraints.

### Query Builder

The current JetEngine Query Builder covers multiple query types. Before exact construction:
- identify query type
- set Name/Description
- determine Query ID when filtering is involved
- understand Cache Query and Cache Expires
- consider REST endpoint registration/security
- use exact type-specific settings

Possible query families include:
Posts, Terms, Users, Comments, SQL tables, Repeater, WooCommerce products,
CCT, REST API, Data Store, Relations, reviews, and other enabled integrations.

### Listing Grid

Listing Grid is the primary dynamic presentation surface for many ToPersia entity lists.

Use:
- Listing Item for card/composition
- Query Builder for data selection
- Dynamic widgets/tags for field output
- filters/pagination/load-more only when supported by the installed feature set

### Dynamic widgets

Use Dynamic Field, Dynamic Image, Dynamic Link, Dynamic Meta, Dynamic Repeater,
Dynamic Terms, and other enabled dynamic elements.

## WPML policy

WPML is not just a translation text box. Treat translation as part of the data model.

For Elementor:
- default Elementor widgets are supported by WPML;
- custom/add-on widgets may require configuration;
- `wpml-config.xml` can declare fields and repeaters for translation;
- post IDs and term IDs may need `post-ids` / `term-ids` handling;
- repeater fields use `fields-in-item`;
- exact custom widget registrations must be derived from the widget's real data shape.

For ToPersia:
- preserve entity identity across languages;
- keep language-neutral IDs/slugs/data references where the CMS requires them;
- never translate IDs;
- translate user-facing labels, descriptions, titles, alt text, metadata and relevant dynamic
  strings according to WPML configuration;
- verify translated media strategy.

## UI/UX workflow

### Input: screenshot/Figma/design
1. identify viewport
2. inventory sections
3. identify layout primitives
4. extract visual tokens
5. map each element to native widget
6. identify dynamic content
7. identify WoodMart-specific components
8. identify JetEngine-driven areas
9. identify WPML-sensitive text/data
10. construct Elementor hierarchy
11. generate JSON
12. validate
13. provide assumptions

### Input: existing Elementor JSON
1. parse
2. identify root/template type
3. map nodes and IDs
4. identify widgetTypes
5. preserve valid schema
6. modify only required settings
7. validate again

### Input: "make JSON"
Do not return generic HTML.
Return Elementor-shaped JSON.

## JSON quality gates

Before finalizing:
- root parses as JSON
- root contains correct required keys
- `content` is an array
- recursive nodes are structurally valid
- widget nodes have widgetType
- IDs are unique
- no comments/trailing commas
- no invented widgetType without declaration
- no invented control names where exact schema matters
- responsive keys are only used when supported
- global references follow Elementor's `__globals__` model when used
- JetEngine dynamic references are logically connected to the data model
- WPML-sensitive text is identifiable
- WoodMart-specific behavior is placed in the correct layer

## Database-derived exactness

When `references/site-snapshot/database-snapshot.json` exists, consult it before exact ToPersia work. It contains observed Elementor widgetTypes, setting keys, version markers, and JetEngine CPT slugs derived from the uploaded database snapshot. Use it as project evidence, not as a substitute for the live plugin registry.


At the start of an exact-schema task, capture:
- WordPress version
- Elementor version
- Elementor Pro version
- WoodMart version
- JetEngine version
- Crocoblock module versions
- WPML core/version
- WPML addons in use
- WooCommerce version
- active Elementor experiments/features

If unavailable, say "schema must be verified against the target installation" and use
conservative structures.

## ToPersia live snapshot layer

Before exact implementation, consult:
- `references/site-snapshot/project-profile.md`
- `references/site-snapshot/version-manifest.json`
- `references/site-snapshot/capture-checklist.md`

If real site exports are present under `references/site-snapshot/live/`, treat them as the
highest-priority project-specific truth.

## Raw SQL snapshot layer

A raw phpMyAdmin dump has been inspected. Consult:
- `references/site-snapshot/raw-sql-manifest.json`
- `references/site-snapshot/jetengine-db-post-types.json`
- `references/site-snapshot/jetengine-db-taxonomies.json`
- `references/site-snapshot/db-tables-elementor.json`
- `references/site-snapshot/db-tables-jetengine.json`
- `references/site-snapshot/db-tables-wpml.json`
- `references/site-snapshot/db-tables-woodmart.json`
- `references/site-snapshot/db-tables-wordpress.json`

This layer is database evidence, not documentation. Values extracted from it must not be
invented or generalized beyond what the dump contains.

## Files in this skill

- `references/elementor-data-model.md`
- `references/elementor-controls.md`
- `references/elementor-widgets.md`
- `references/woodmart-stack.md`
- `references/jetengine-stack.md`
- `references/wpml-stack.md`
- `references/topersia-architecture.md`
- `references/source-map.md`
- `schema/elementor-node.schema.json`
- `schema/stack-manifest.schema.json`
- `scripts/validate_elementor_json.py`

## Operational warning

This skill is a structured knowledge layer, not a binary copy of proprietary plugins.
Exact version-specific controls must be learned from official documentation and the actual
site export. Never invent unavailable proprietary internals.
