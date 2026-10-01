---
name: topersia-snapshot
description: Use the real ToPersia database and schema snapshot as the factual source for current implementation details.
---

# ToPersia Snapshot

## Snapshot database
Source: uploaded `topersia_geo.sql`.
Database: `topersia_geo`.
MariaDB: `10.11.19`.
PHP marker in project snapshot: `8.4.24`.
Site URLs observed:
siteurl=`https://topersia.com/geo`
home=`https://topersia.com/`

## Elementor DB observations
21 `_elementor_data` records
7 `_elementor_page_settings` records
407 total elements
131 containers
276 widgets
Observed common widget types:
shortcode 136, heading 45, button 36, image 24, icon-list 12,
text-editor 9, icon 6, html 5, search 3.

DB contains version markers for multiple versions; do not use them alone to assert current installed version.

## JetEngine schema
The project schema source contains 42 CPT, 42 Meta Box, 135 relations, 74 glossaries and 6 queries.
The SQL DB snapshot is evidence of stored state, not a proof of every filesystem/plugin control.

## Real plugin/source baseline recorded from project backup
WordPress 7.1
Elementor 4.2.4
Elementor Pro 4.2.2
JetEngine 3.8.13.2
WPML 4.9.7
WPML String Translation 3.5.4
WPML Media Translation 3.0.0
WPML SEO 2.2.4
Jet Plugin Pro 1.6.0
WoodMart 8.5.7
WoodMart Core 1.1.8
Rank Math 1.0.278
ToPersia Core 4.10.0
ToPersia Render 3.7.0
ToPersia Schema Engine 1.1.0
ToPersia User Core 1.9.11

Treat this list as project snapshot evidence; if Live State differs, Live State wins.

## Warning
Do not confuse:
- schema source snapshot
- SQL database snapshot
- live filesystem
- current exports
They answer different questions.

📄 منبع: `references/site-snapshot/database-snapshot.json`,
`raw-sql-manifest.json` و snapshot/source inventory پروژه.
