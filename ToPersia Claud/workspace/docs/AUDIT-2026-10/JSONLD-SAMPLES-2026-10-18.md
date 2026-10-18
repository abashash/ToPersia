# JSON-LD استان اصفهان در سه زبان — پیش و پس از ابری H (۱۸ اکتبر ۲۰۲۶)

«پیش» = `topersia-g-zones.php` و `topersia-schema.php` نسخهٔ HEAD (render 4.5.1 · schema 1.3.1)، «پس» = با cherry-pick `98cdfb8e` (render 4.5.2 · schema 1.3.2). نمونه از `/province/isfahan/`، `/en/…`، `/ar/…` همین نسخهٔ محلی.

## LQ-SCH-01 — نویسهٔ مخصوص فارسی (پ چ ژ گ ک ی) در JSON-LD

| زبان | پیش | پس |
|---|---|---|
| fa | 164 | 164 |
| en | 0 | 0 |
| ar | 0 | 0 |

در en و ar هر دو ۰ بود و ۰ ماند؛ fa همان ۱۶۴ (کل نویسه‌های عربی‌خط ۱٬۳۳۸ ← ۱٬۳۳۴ به‌سبب حذف «خانه» ریشهٔ BreadcrumbList). عربی-خط مجاز در ar (نه فارسی): پیش ۱۵۲ ← پس ۱۴۴ نویسه (ریشهٔ «الرئيسية» حذف شد).

## تفاوت (diff گراف)

### fa

```diff
35a36
>             "lastReviewed": "2026-09-22",
46,47c47,48
<                     "name": "خانه",
<                     "item": "https:\/\/topersia.com"
---
>                     "name": "ToPersia",
>                     "item": "http:\/\/topersia.local\/"
```

### en

```diff
35a36
>             "lastReviewed": "2026-09-22",
45,46c46,47
<                     "name": "Home",
<                     "item": "https:\/\/topersia.com"
---
>                     "name": "ToPersia",
>                     "item": "http:\/\/topersia.local\/en\/"
```

### ar

```diff
35a36
>             "lastReviewed": "2026-09-22",
45,46c46,47
<                     "name": "الرئيسية",
<                     "item": "https:\/\/topersia.com"
---
>                     "name": "ToPersia",
>                     "item": "http:\/\/topersia.local\/ar\/"
```

تغییرهای S4 (`WebPage.lastReviewed` = تاریخ بلوک منابع) و S5 (ریشهٔ BreadcrumbList «ToPersia» + نشانی خانهٔ هر زبان به‌جای `topersia.com` ثابت) در هر سه زبان یکسان‌اند. در این نمونه از S1 و S2 اثری در گراف دیده نشد (علت بررسی نشد)؛ رفتار آن‌ها با داده‌های ساختگی در `tests/assert-parity.php` سنجیده می‌شود.

نشانی `http://topersia.local/…` فقط محلی است؛ در زنده `wpml_home_url` همان دامنهٔ زنده را می‌دهد.
