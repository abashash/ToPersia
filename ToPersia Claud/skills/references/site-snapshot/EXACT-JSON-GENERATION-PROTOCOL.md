# Exact JSON Generation Protocol — ToPersia

## 1. Read project snapshot first
Read:
- `database-snapshot.json`
- `project-profile.md`
- official references

## 2. Treat observed widgetTypes as verified examples
Observed in ToPersia `_elementor_data`:
- shortcode
- heading
- button
- image
- icon-list
- text-editor
- icon
- html
- search

Do not infer that this is the complete widget registry.

## 3. Native widget priority
When the requested design can be represented with a native widget, use the native widget.
The presence of HTML widgets in an existing legacy/current page does not make HTML the default.

## 4. Real ToPersia styling
The SQL contains actual page settings and Elementor data. Extract style patterns only when
they are explicitly needed. Do not copy private content values into generic generated templates.

## 5. Version conflict
The database contains Elementor metadata markers for multiple versions:
- Elementor 4.2.3 / 4.2.4
- Elementor Pro 4.2.0 / 4.2.2

This means different pages/meta records can have been written by different releases. For
new JSON, prefer the currently installed plugin/export version once verified.

## 6. JetEngine
Use actual ToPersia CPT slugs from `database-snapshot.json` when the user asks to build
dynamic tourism entities. Never replace an existing CPT architecture with generic placeholder
post types without a reason.

## 7. Sensitive values
Never expose or reuse:
- passwords
- API keys
- license keys
- session cookies
- private customer data
- private database values

The snapshot intentionally stores structural facts only.
