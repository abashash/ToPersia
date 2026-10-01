---
name: topersia-elementor
description: Exact Elementor and Elementor Pro implementation rules for ToPersia, including native widgets, responsive settings and JSON.
---

# Elementor

## مسئولیت
Presentation و page composition با Elementor/Elementor Pro.

## Native-first
Native Elementor/WoodMart widget را به HTML سفارشی ترجیح بده.
از HTML widget به عنوان راه‌حل پیش‌فرض استفاده نکن.

## JSON
ریشهٔ معتبر Elementor شامل:
`title`, `type`, `version`, `page_settings`, `content`.
Widget node باید `elType: "widget"` و `widgetType` معتبر داشته باشد.
Container hierarchy باید حفظ شود.
IDها یکتا باشند.
Responsive keys فقط برای controlهایی که واقعاً responsive هستند.
Global styleها با `settings.__globals__` سازگار باشند.

## Widget inventory مشاهده‌شده در Snapshot SQL
`shortcode`, `heading`, `button`, `image`, `icon-list`, `text-editor`, `icon`, `html`, `search`
از نظر تعداد مشاهده‌شده در DB پرتکرارند. این فهرست، فهرست کامل widgetهای نصب‌شده نیست.

## Widget source inventory پروژه
در source inventory پروژه، Elementor core و Pro widget files جداگانه بررسی شده‌اند.
برای widgetType دقیق، source/export واقعی را مرجع قرار بده.

## نگاشت پایه
heading→heading
paragraph→text-editor
CTA→button
image→image
icon+copy→icon-box
list→icon-list
image+copy→image-box
testimonial→testimonial

## ممنوع
- جعل control ID
- ساخت JSON با تنظیمات حدسی
- استفاده از version marker دیتابیس به‌عنوان نسخهٔ قطعی نصب‌شده
- جایگزینی Dynamic Data با متن hard-coded

📄 منبع: `01-ELEMENTOR.md`، `06-JSON-GENERATION.md` و
`references/site-snapshot/EXACT-JSON-GENERATION-PROTOCOL.md`؛
همچنین `references/site-snapshot/database-snapshot.json`.
