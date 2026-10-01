---
name: topersia-debugging
description: Conservative debugging workflow for ToPersia Elementor, JetEngine, WoodMart and WPML issues.
---

# Debugging

## Workflow
1. Reproduce and classify: Presentation / Data / Language / Integration.
2. Check Live State.
3. Check exact source/export.
4. Check Snapshot.
5. Check official docs.
6. Isolate one variable at a time.
7. Make the smallest reversible change.
8. Validate desktop/tablet/mobile and multilingual behavior.
9. Record whether failure is silent or visible.

## Common silent-failure targets
- missing/incorrect dynamic key
- relation direction mismatch
- translation configuration mismatch
- query returning empty data
- responsive key ignored by unsupported control
- invalid Elementor JSON setting silently ignored

These are diagnostic categories, not claims that a particular current defect exists.

## Code
Custom PHP/JS/CSS only in the project-approved architecture; PHP custom code belongs in `topersia-core.php`.

📄 منبع: `00-CORE-INSTRUCTIONS.md`, `06-JSON-GENERATION.md`,
`03-JETENGINE.md`, `04-WPML.md`.
