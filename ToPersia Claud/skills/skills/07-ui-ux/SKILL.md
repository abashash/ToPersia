---
name: topersia-ui-ux
description: UI/UX implementation discipline for ToPersia using the frozen stack and native Elementor/WoodMart patterns.
---

# UI/UX

## اصول
UI باید از data architecture جدا بماند:
- Data → JetEngine
- Presentation → Elementor/WoodMart
- Language → WPML

## Design system
قبل از افزودن style جدید، pattern/global style موجود را بررسی کن.
از hard-coded design tokens وقتی سیستم موجود قابل استفاده است پرهیز کن.

## Accessibility
Hierarchy heading، readable contrast، keyboard behavior، semantic controls و responsive behavior را بررسی کن؛
اما ادعای compliance عددی را بدون سند نده.

## Dynamic content
محتوای تکرارشونده را در Elementor hard-code نکن اگر دادهٔ ساختاریافتهٔ موجود آن را تأمین می‌کند.

📄 منبع: `07-DESIGN-SYSTEM.md` و `01-ELEMENTOR.md`/`02-WOODMart.md`.
