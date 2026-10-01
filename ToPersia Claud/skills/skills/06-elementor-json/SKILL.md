---
name: topersia-elementor-json
description: Exact, conservative Elementor JSON generation and repair for ToPersia.
---

# Exact Elementor JSON

## Pipeline
1. Identify target template/page.
2. Read current ToPersia snapshot/export.
3. Identify actual widgetType.
4. Identify actual control keys from source/export.
5. Build valid hierarchy.
6. Validate unique IDs.
7. Validate responsive keys.
8. Validate dynamic references.
9. Compare against project patterns.
10. Output import-ready JSON only when exact enough.

## Root
`title`, `type`, `version`, `page_settings`, `content`.

## Node
`id`, `elType`, `isInner`, `settings`, `elements`.
Widget additionally requires `widgetType`.

## Never
Do not invent:
- widgetType
- control ID
- repeater item key
- dynamic tag schema
- global reference
- WoodMart setting
- JetEngine query parameter

## Native-first
Use native Elementor/WoodMart/JetEngine widgets whenever the source supports the requested UI.

📄 منبع: `06-JSON-GENERATION.md` و `references/site-snapshot/EXACT-JSON-GENERATION-PROTOCOL.md`.
