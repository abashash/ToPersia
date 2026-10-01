---
name: topersia-woodmart
description: ToPersia WoodMart/XTemos rules for Elementor widgets, Header Builder, theme settings and presets.
---

# WoodMart

## مسئولیت
WoodMart مسئول Theme-specific UI، Header Builder، theme settings و WooCommerce presentation است.

## Elementor
ویجت‌های WoodMart در Elementor تحت `[XTemos] Elements` قرار می‌گیرند.
widgetType و control دقیق را فقط از source/export واقعی یا documentation معتبر بردار.

## Header
اگر مسئله Header است، از WoodMart Header Builder استفاده کن؛ آن را با یک HTML widget بازسازی نکن.
Desktop/Mobile layout را مطابق ساختار واقعی پروژه در نظر بگیر.

## Theme Settings
Theme Settings را با local Elementor styling یکی نکن.
Presets می‌توانند overrideهای شرطی داشته باشند.

## Widget source inventory مشاهده‌شده
از جمله:
`wd_banner`, `wd_button`, `wd_blog`, `wd_title`, `wd_tabs`, `wd_accordion`,
`wd_products`, `wd_products_tabs`, `wd_product_filters`, `wd_search`,
`wd_slider`, `wd_video`, `wd_images_gallery`, `wd_google_map`,
`wd_page_title`, `wd_element_breadcrumbs`, `woodmart_menu_anchor`.
این inventory برای نام‌گذاری source است؛ control schema باید از منبع دقیق استخراج شود.

## WPML
فایل واقعی `wpml-config.xml` در WoodMart mappings ترجمه برای چند widget را پوشش می‌دهد.
در custom widget translation، mapping واقعی را بررسی کن.

📄 منبع: `02-WOODMart.md` و source inventory پروژه؛
مستندات رسمی WoodMart داخل Knowledge Base؛ `wpml-config.xml` واقعی WoodMart.
