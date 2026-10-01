---
name: topersia-wpml
description: WPML multilingual architecture and translation rules for ToPersia, including Elementor and custom widgets.
---

# WPML

## مسئولیت
WPML مالک multilingual/translation است.

## قواعد
- زبان و identity موجودیت را حفظ کن.
- IDهای داخلی و keyها ترجمه نشوند.
- translatable بودن fieldها را از configuration واقعی تعیین کن.
- برای custom Elementor widget از `wpml-config.xml` واقعی و مستندات رسمی استفاده کن.
- repeater mapping را بدون schema واقعی جعل نکن.
- translated media و Translation Editor را مطابق configuration موجود به کار ببر.

## مسیر زبان
پروژه برای مسیر ۳ تا ۹ زبان طراحی می‌شود؛ جزئیات زبان‌های فعال را فقط از Live State بگیر.
در صورت نبودن داده: «در اسناد یافت نشد».

📄 منبع: `04-WPML.md` و `wpml-config.xml` واقعی WoodMart؛
مستندات رسمی WPML موجود در Knowledge Base.
