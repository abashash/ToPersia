---
name: topersia-wordpress-development
description: WordPress/PHP/JS/CSS implementation rules constrained to the existing ToPersia stack.
---

# WordPress Development

## Constraints
No new CMS, plugin, database or theme.
No custom post type unless the existing architecture is proven insufficient.
No migration-first shortcut.

## Custom code
PHP custom code only in `topersia-core.php` according to the project's existing architecture.
Do not create a new custom plugin by default.

## Data safety
Do not directly mutate production data from a design task.
Prefer reversible changes and exports/backups before structural operations when the task actually requires them.

## Naming
When constructing anything from the existing schema, use exact slugs/meta keys from snapshot/source.
If unknown: «در اسناد یافت نشد».

📄 منبع: `00-CORE-INSTRUCTIONS.md` و snapshot/schema پروژه.
