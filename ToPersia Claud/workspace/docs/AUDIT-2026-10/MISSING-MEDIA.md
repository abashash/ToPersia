# رسانهٔ گم‌شده — نسخهٔ محلی (دستهٔ ۳۰ بند ۱، ۲۶ اکتبر ۲۰۲۶)

> فقط گزارش؛ هیچ پرونده یا پیوستی پاک یا ساخته نشد. ابزار: `php wp-cli.phar … eval-file scripts/tp-media-audit.php md` — همهٔ ارجاع‌های رسانه در پست‌های ما (hero_image، فیلدهای media/gallery متاباکس‌ها، referenced_media_ids، تصویر شاخص، تصویر متن) و سپس همهٔ پیوست‌ها؛ پروندهٔ اصلی و هر اندازهٔ ساخته‌شده روی دیسک.

## نتیجه

- **پروندهٔ اصلی گم‌شده: ۰ · پیوستِ ناموجود: ۰ · نشانیِ گم‌شده در متن: ۰.**
- **۲۳ اندازهٔ ساخته‌شدهٔ jpg** از ۴ پیوست روی دیسک نیست (`uploads/2026/08`؛ همان پرونده‌هایی که `git status` «deleted» نشان می‌دهد). متادیتای پیوست هنوز این اندازه‌ها را می‌شناسد، پس srcset و کارت‌ها ممکن است نشانی مرده بدهند. راه بازسازی: `wp media regenerate <شناسه>` روی همان ۴ پیوست (فقط پس از تأیید؛ این گزارش اجرا نکرد).
- **دو webp گزارش Lighthouse دستهٔ ۲۹** (`Iran-Map-Mega-Menu-300x300.webp` · `saad-al-saltaneh-caravanserai-qazvin-300x169.webp`) **روی دیسک هستند**؛ «بیرون از نسخهٔ ایستا» یعنی هنگام ضبط نسخهٔ ایستا درخواست نشده بودند (بارگذاری تنبل دیرتر از ضبط)، نه گم‌شدن پرونده.

## فهرست

| پست | نوع | فیلد | پیوست | پرونده | وضع |
|---|---|---|---|---|---|
| 171,95 | `tp_route` | _thumbnail_id (medium) | 172 | `2026/08/Silk-Road-in-Iran-300x188.jpg` | size-missing |
| 171,95 | `tp_route` | _thumbnail_id (large) | 172 | `2026/08/Silk-Road-in-Iran-1024x640.jpg` | size-missing |
| 171,95 | `tp_route` | _thumbnail_id (thumbnail) | 172 | `2026/08/Silk-Road-in-Iran-150x150.jpg` | size-missing |
| 171,95 | `tp_route` | _thumbnail_id (medium_large) | 172 | `2026/08/Silk-Road-in-Iran-768x480.jpg` | size-missing |
| 171,95 | `tp_route` | _thumbnail_id (1536x1536) | 172 | `2026/08/Silk-Road-in-Iran-1536x960.jpg` | size-missing |
| 171,95 | `tp_route` | _thumbnail_id (2048x2048) | 172 | `2026/08/Silk-Road-in-Iran-2048x1280.jpg` | size-missing |
| 1978,1979,99,51 | `tp_province,tp_designation` | _thumbnail_id (medium) | 2016 | `2026/08/UNESCO-World-Heritage-Sites-in-Iran-300x188.jpg` | size-missing |
| 1978,1979,99,51 | `tp_province,tp_designation` | _thumbnail_id (large) | 2016 | `2026/08/UNESCO-World-Heritage-Sites-in-Iran-1024x640.jpg` | size-missing |
| 1978,1979,99,51 | `tp_province,tp_designation` | _thumbnail_id (thumbnail) | 2016 | `2026/08/UNESCO-World-Heritage-Sites-in-Iran-150x150.jpg` | size-missing |
| 1978,1979,99,51 | `tp_province,tp_designation` | _thumbnail_id (medium_large) | 2016 | `2026/08/UNESCO-World-Heritage-Sites-in-Iran-768x480.jpg` | size-missing |
| 1978,1979,99,51 | `tp_province,tp_designation` | _thumbnail_id (1536x1536) | 2016 | `2026/08/UNESCO-World-Heritage-Sites-in-Iran-1536x960.jpg` | size-missing |
| 1978,1979,99,51 | `tp_province,tp_designation` | _thumbnail_id (2048x2048) | 2016 | `2026/08/UNESCO-World-Heritage-Sites-in-Iran-2048x1280.jpg` | size-missing |
| (بی ارجاع) | `` | پیوست: attachment (medium) | 3312 | `2026/08/rabat-e-sharaf-caravanserai-iran-01-300x169.jpg` | size-missing |
| (بی ارجاع) | `` | پیوست: attachment (large) | 3312 | `2026/08/rabat-e-sharaf-caravanserai-iran-01-1024x576.jpg` | size-missing |
| (بی ارجاع) | `` | پیوست: attachment (thumbnail) | 3312 | `2026/08/rabat-e-sharaf-caravanserai-iran-01-150x150.jpg` | size-missing |
| (بی ارجاع) | `` | پیوست: attachment (medium_large) | 3312 | `2026/08/rabat-e-sharaf-caravanserai-iran-01-768x432.jpg` | size-missing |
| (بی ارجاع) | `` | پیوست: attachment (1536x1536) | 3312 | `2026/08/rabat-e-sharaf-caravanserai-iran-01-1536x864.jpg` | size-missing |
| 2095 · ایران | `tp_country` | پیوست: attachment (medium) | 77 | `2026/08/islamic-republic-of-iran-300x188.jpg` | size-missing |
| 2095 · ایران | `tp_country` | پیوست: attachment (large) | 77 | `2026/08/islamic-republic-of-iran-1024x640.jpg` | size-missing |
| 2095 · ایران | `tp_country` | پیوست: attachment (thumbnail) | 77 | `2026/08/islamic-republic-of-iran-150x150.jpg` | size-missing |
| 2095 · ایران | `tp_country` | پیوست: attachment (medium_large) | 77 | `2026/08/islamic-republic-of-iran-768x480.jpg` | size-missing |
| 2095 · ایران | `tp_country` | پیوست: attachment (1536x1536) | 77 | `2026/08/islamic-republic-of-iran-1536x960.jpg` | size-missing |
| 2095 · ایران | `tp_country` | پیوست: attachment (2048x2048) | 77 | `2026/08/islamic-republic-of-iran-2048x1280.jpg` | size-missing |

