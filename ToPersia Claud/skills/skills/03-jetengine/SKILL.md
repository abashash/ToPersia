---
name: topersia-jetengine
description: Exact JetEngine data architecture rules, CPT/meta boxes, relations, queries, listings and dynamic data for ToPersia.
---

# JetEngine

## مسئولیت
JetEngine مالک Structured/Dynamic Data است.

## اصل
قبل از ساخت Entity جدید، ساختار موجود را جست‌وجو کن.
CPT/CCT/Meta/Taxonomy/Relation/Query/Listing جدید فقط با اثبات ناکافی‌بودن موجودی.

## Snapshot واقعی
از schema استخراج‌شده از `topersia-user-core/includes/jetengine-schema.php`:
- 42 CPT
- 42 Meta Box
- 135 Relation
- 74 Glossary
- 6 Query
- 0 Taxonomy
- 0 Listing
- 0 Options Page
در SQL snapshot، inventory جدول post types ممکن است فقط بخشی از schema ذخیره‌شده را نشان دهد؛ برای وضعیت فعلی schema، source live مقدم است.

## CPTهای ثبت‌شده در schema
`tp_country`, `tp_province`, `tp_county`, `tp_city`, `tp_region`, `tp_island`,
`tp_desert`, `tp_village`, `tp_mountain`, `tp_water_body`, `tp_district`,
`tp_forest_plain`, `tp_border_crossing`, `tp_monument`, `tp_religious_site`,
`tp_ancient_site`, `tp_natural_feature`, `tp_museum`, `tp_protected_area`,
`tp_landscape`, `tp_handicraft`, `tp_local_food`, `tp_costume`, `tp_music`,
`tp_dance`, `tp_language`, `tp_ethnic_group`, `tp_ritual`, `tp_figure`,
`tp_historical_era`, `tp_health_safety`, `tp_travel_etiquette`, `tp_designation`,
`tp_route`, `tp_visa`, `tp_currency`, `tp_calendar`, `tp_transport`, `tp_festival`,
`tp_sports_event`, `tp_religious_event`, `tp_seasonal_event`.

نمونهٔ ثبت‌شده:
`tp_city` با Rewrite Slug=`city`.

## Queries واقعی
D1 World Heritage
D2 Intangible Heritage
D3 Geopark
D4 Biosphere Reserve
D5 Creative City
D6 National Monument

Meta flags و post type scopes را فقط از snapshot بخوان؛ مقدار جدید حدس نزن.

## Widgets
Dynamic Field/Image/Link/Meta/Repeater/Terms، Listing Grid و Query Builder را بر اساس دادهٔ واقعی استفاده کن.
Relation Meta را با field معمولی اشتباه نگیر.

📄 منبع: `03-JETENGINE.md` و `references/site-snapshot/jetengine-db-post-types.json`,
`database-snapshot.json` و schema واقعی ToPersia.
