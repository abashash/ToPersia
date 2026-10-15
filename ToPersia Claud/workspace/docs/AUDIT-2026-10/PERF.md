# PERF — طرح اجرایی فاز ۹ (کارایی) · P5-D263

> ممیزی فقط‌خواندنی، جلسهٔ ابری G، ۱۵ اکتبر ۲۰۲۶ · مبنای کد: `main` `47015840`. هیچ کد، تنظیم یا آزمونی عوض نشد.
> برچسب‌ها: **[VERIFIED]** با پرونده:خط · **[INFERRED]** استنتاج از کد · **[UNKNOWN]** + فرمان محلی برای روشن‌کردن.
> اندازه‌ها «روی دیسک» همین مخزن‌اند (`wc -c` و `gzip -9c | wc -c` در این جلسه). **هیچ عددِ Lighthouse، LCP، TBT یا CLS در این سند در ابر اندازه‌گرفته نشده**؛ عددهای کارایی فقط از `tests/RESULT-lighthouse.txt` نقل شده‌اند و «اثر برآوردی» هر اقدام برآورد است، نه سنجش.
> مسیرها: `plugin/…` یعنی `ToPersia Claud/workspace/plugin/…`؛ `geo/…` از ریشهٔ مخزن.

---

## ۰ — مبنا

| | مقدار | منبع |
|---|---|---|
| آخرین ضبط (B، v4.5.0 · core 4.13.0، ۵ اکتبر) | استان موبایل **47** · دسکتاپ **81** · شهر موبایل 50 · دسکتاپ 85 | [VERIFIED] `tests/RESULT-lighthouse.txt:37-40` |
| همان ضبط — موبایل استان | LCP 13848 · FCP 7970 · TBT 422 · CLS 0.028 | [VERIFIED] `tests/RESULT-lighthouse.txt:37` |
| همان ضبط — دسکتاپ استان | LCP 2716 · FCP 1354 · TBT 57 · CLS 0.001 | [VERIFIED] `tests/RESULT-lighthouse.txt:38` |
| «۴۹ موبایل» در دستور | عدد v4.4.0 در جفت ۲۸ سپتامبر است، نه آخرین ضبط | [VERIFIED] `tests/RESULT-lighthouse.txt:119` |
| سئو ۶۶ | از `blog_public=0` محلی (همه noindex) — مسئلهٔ کارایی نیست | [VERIFIED] `tests/RESULT-lighthouse.txt:101` |
| TTFB محلی | استان ~3.3 ثانیه (WAMP)؛ Lighthouse روی نسخهٔ ایستا اجرا می‌شود و «پاسخ» ~15–20ms است، پس TTFB در امتیاز نیست | [VERIFIED] `tests/RESULT-lighthouse.txt:4-5,33` |
| عکس‌های نمونهٔ محلی | 138 پاسخ `picsum.photos` با وضعیت 403 در هر ضبط | [VERIFIED] `tests/RESULT-lighthouse.txt:29` |
| هدف فاز ۹ | ≥ ۹۵ در چهار ردهٔ Lighthouse، موبایل و دسکتاپ | [VERIFIED] `docs/SPRINT-4.4-PHASES.md:1232` |

**پیامد مهم برای تفسیر مبنا:**
1. [INFERRED] چون تصویرهای نمونه 403 می‌گیرند، عنصر LCP در ضبط‌های محلی به احتمال زیاد **تصویر سربرگ نیست** (متن یا پس‌زمینهٔ سربرگ است). پس LCP موبایل 13.8s عمدتاً از زنجیرهٔ CSS/JS مسدودکننده و قلم است، و اقدام‌های تصویر LCP فقط روی دادهٔ واقعی (یا زنده) اثرشان دیده می‌شود. [UNKNOWN] — محلی: `node tests/lighthouse.js --snapshot=tests/.lh-snapshots/v4.5.0 --runs=1` و در JSON خروجی `audits["largest-contentful-paint-element"].details.items[0]` را بخوان.
2. [VERIFIED] ضبط مبنا 4 پاسخ `fonts.googleapis.com` دارد (`tests/RESULT-lighthouse.txt:29`)؛ مسدودسازی Google Fonts (P5-D359) بعداً در core 4.13.1 آمد (`plugin/topersia-core/topersia-core.php:4228-4262`). پس **مبنا کهنه است** و پیش از هر اقدام باید دوباره ضبط شود (اقدام A0).

---

## ۱ — فهرست CSS و JS صفحهٔ استان و شهر (از کد ثبت)

روش: خواندن `wp_enqueue_*`/`wp_register_*` و شرطشان. «مسدودکننده» = `<link rel=stylesheet>` در `<head>` یا `<script>` بی `defer/async` در `<head>`. «مصرف قالب ما» = آیا markup یا JS ما (`topersia-render`) کلاس، تابع یا داده‌ای از آن می‌خواهد.
فهرست واقعیِ صف (و شرط‌های وابسته به تنظیمات پایگاه‌داده) فقط با صفحهٔ زنده قطعی می‌شود: [UNKNOWN] — محلی:
```
curl -s http://topersia.local/province/isfahan/ | grep -oE '<(link[^>]+rel=.stylesheet[^>]*|script[^>]+src=[^>]*)>' | sed -E 's/.*(href|src)=.([^"'\'' ]+).*/\2/' | sort | uniq -c
```
(با کوکی ورود، چون مهمان صفحهٔ «به‌زودی» می‌گیرد.)

### ۱-الف — افزونه‌های خودمان

| دسته | handle · پرونده | خام / gzip (بایت) | جا | مسدودکننده؟ | مصرف قالب ما | منبع |
|---|---|---|---|---|---|---|
| render | `topersia-tokens` · `assets/topersia-tokens.css` (+ inline: `@font-face` و متغیرها) | 28,422 / 8,052 | head | **بله** | بله (همهٔ `--tp-*`) | [VERIFIED] `plugin/topersia-render/topersia-render.php:424-429,454` |
| render | `topersia-theme-bridge` · `assets/topersia-theme-bridge.css` | 8,439 / 2,337 | head | **بله** | بله (پل وودمارت/المنتور) | [VERIFIED] `…/topersia-render.php:432-439` |
| render | `topersia-g-template` · `assets/topersia-g-template.css` | 122,844 / 26,983 | head | **بله** | بله (کل قالب G) | [VERIFIED] `…/topersia-render.php:447-452` |
| render | `topersia-display` · `assets/topersia-display.js` | 11,497 / 3,742 | footer | خیر | بله | [VERIFIED] `…/topersia-render.php:456-462` |
| render | `topersia-g-zones` · `assets/topersia-g-zones.js` (+ `TopersiaG`) | 101,926 / 28,827 | footer | خیر | بله | [VERIFIED] `…/topersia-render.php:464-487` |
| render | `topersia-g-weather` + `-fx` | 10,851/4,302 + 9,943/3,523 | footer | خیر | بله (اگر ناحیهٔ هوا روشن) | [VERIFIED] `…/topersia-render.php:500-520` |
| render (تنبل) | `map/maplibre-gl.js` · `maplibre-gl.css` · `pmtiles.js` · `basemaps.js` · `mapbox-gl-rtl-text.js` | 1,056,837/275,098 · 70,024/10,059 · 20,229/7,956 · 38,680/7,006 · 133,355/37,042 | پویا | خیر | بله | §۴ |
| user-core | `topersia-user-core` · `assets/css/user-core.css` | 23,331 / — | head | **بله** | جزئی (دکمهٔ ذخیره `data-tp-save` — [INFERRED]) | [VERIFIED] `plugin/topersia-user-core/includes/class-topersia-assets.php:32-37,109-111` (هر نوع `tp_`) |
| user-core | `topersia-user-core` · `assets/js/user-core.js` | 13,481 / — | footer | خیر | جزئی | [VERIFIED] همان `:39-45` |
| user-core | `topersia-display-sync` · `display-sync.js` | 5,070 / — | footer | خیر | فقط کاربر واردشده | [VERIFIED] `…/class-topersia-display-prefs.php:61-69` |
| AI | `topersia-ai` · `assets/css/topersia-ai.css` | 15,134 / — | head | **بله** | بله (باکس AI پنل چپ) | [VERIFIED] `plugin/topersia-ai-summarizer/includes/class-tp-ai-frontend.php:35-40,63-77` (هر `is_singular()`) |
| AI | `topersia-ai` · `assets/js/topersia-ai.js` | 7,698 / — | footer | خیر | بله | [VERIFIED] همان `:42-48` |
| core | حذف‌ها: CSS بلوک (`wp-block-library`، `-theme`، `classic-theme-styles`)، `wp-embed`، ایموجی، Google Fonts | — | — | — | — | [VERIFIED] `plugin/topersia-core/topersia-core.php:4433-4442 · 4287-4289 · 4269-4281 · 4240-4262` |

نکته‌ها:
- [VERIFIED] سه شیوه‌نامهٔ render و دو JS پایه **بی هیچ شرط نوع صفحه** در همهٔ صفحه‌های سایت صف می‌شوند (`topersia-render.php:413-427` فقط `topersia_render_ready()` را می‌سنجد).
- [INFERRED] `topersia-g-template.css` کمینه نیست: حذف توضیح و فاصله از 122,844 به ~84,893 بایت می‌رسد (شمارش این جلسه با regex ساده)؛ `topersia-tokens.css` از 28,422 به ~15,973.
- [VERIFIED] `topersia-g-zones.js` (101,926 بایت) کمینه نیست و jQuery نمی‌خواهد (۰ رخداد `$(`/`jQuery`).

### ۱-ب — شخص ثالث (geo/، فقط خواندنی)

| افزونه | handle · پرونده | خام / gzip | جا | مسدودکننده؟ | مصرف قالب ما | منبع |
|---|---|---|---|---|---|---|
| WordPress 7.1.2 | `jquery-core` · `wp-includes/js/jquery/jquery.min.js` | 87,553 / — | **head** (پیش‌فرض) | **بله** | **خیر** (JS ما بی jQuery) | [VERIFIED] `geo/wp-includes/script-loader.php:907-909` |
| WordPress | `jquery-migrate.min.js` | 13,577 / — | head | **بله** | خیر | همان |
| Elementor 4.3.2 | `elementor-frontend` · `assets/css/frontend.min.css` | 54,666 / 7,229 | head | **بله** | **خیر** — صفحهٔ ما یک ویجت `[tp_page]` است | [VERIFIED] `geo/wp-content/plugins/elementor/includes/frontend.php:558-559,671` |
| Elementor | `elementor-icons` (eicons css 22,305 + woff2 108,152) | — | head | **بله** (اگر `e_font_icon_svg` خاموش) | خیر | [VERIFIED] `…/elementor/includes/frontend.php:667-669` — وضعیت آزمایه [UNKNOWN]: `wp option get elementor_experiment-e_font_icon_svg` |
| Elementor | `elementor-webpack-runtime` · `frontend-modules.min.js` · `frontend.min.js` | 103 · 53,086/16,297 · 60,022/18,715 | footer | خیر | خیر | [VERIFIED] `…/elementor/includes/frontend.php:360-455` |
| Elementor | `swiper` v8 (`swiper.min.js`) | 143,709 / 39,565 | footer (فقط اگر ویجتی بخواهد) | خیر | خیر (اسلایدر ما خودی است — [INFERRED]) | [VERIFIED] `…/frontend.php:380-386` |
| Elementor | `post-<kit>.css` · `post-<page>.css` در `uploads/elementor/css` | [UNKNOWN] — در git نیست | head | **بله** | جزئی (فاصله‌های بخش) | `ls -la geo/wp-content/uploads/elementor/css` |
| Elementor Pro | `elementor-pro-frontend` (`frontend.min.js` 23,939) · `elements-handlers.min.js` 43,922 · `webpack-pro.runtime` 6,242 · `jquery.sticky` 3,721 | — | footer | خیر | خیر | [VERIFIED] `geo/wp-content/plugins/elementor-pro/plugin.php:215` |
| Woodmart 8.5.7 | بخش‌های CSS جدا `wd-*` (`css/parts/` 565 پرونده؛ پایه: `base-rtl.css` 32,773/7,881 · `base-deferred.css` 6,650 · `header-base-rtl.css` 11,782) | — | head + `<link>` درون بدنه | **بله** (پایه و سربرگ) | سربرگ و پاورقی سایت: بله؛ بقیه: خیر | [VERIFIED] `geo/wp-content/themes/woodmart/inc/modules/parts-css-files/class-parts-css-files.php:87-88,361,443` · `inc/enqueue.php:662-664` |
| Woodmart | حالت ترکیبی `style-rtl-elementor.min.css` (609,707 / 100,040) | فقط اگر صافی `woodmart_enqueue_combined_css` | head | بله | خیر | [VERIFIED] `…/woodmart/inc/enqueue.php:29-31,618-628` |
| Woodmart | `woodmart-theme` (اسکریپت‌های جدا؛ ترکیبی `js/scripts/combine.min.js` 333,472/72,582) · `wd-libraries` (ترکیبی 602,191/183,475) · `imagesloaded` | — | footer | خیر | خیر (فقط سربرگ وودمارت) | [VERIFIED] `…/woodmart/inc/enqueue.php:133-170,233` |
| JetEngine | `jet-engine-frontend` · `assets/css/frontend.css` | 61,405 / 8,708 | head (پیش‌فرض) | **بله** | **خیر** — ما Query Builder را فقط سمت سرور می‌خوانیم | [VERIFIED] `geo/…/jet-engine/includes/components/listings/frontend.php:177-190` · مصرف: `plugin/topersia-render/includes/topersia-g-zones.php:3471-3475` |
| JetReviews | `jet-reviews` CSS (`assets/css/jet-reviews.css` 40,282/4,321) | — | head | **بله** | **خیر** — ما جدول نظرها را مستقیم می‌خوانیم و فرم خودمان را داریم | [VERIFIED] `geo/…/jet-reviews/includes/assets.php:80-90` · مصرف: `plugin/topersia-render/includes/topersia-g-zones.php:4926-4958` |
| JetReviews | `jet-reviews-frontend` 59,466 + `jet-vue` 93,670/33,934 + `photoswipe` 31,191 + ui 9,671 + `wp-api-fetch` (و وابستگی‌های wp-*) | — | footer، **روی هر صفحهٔ المنتوری** | خیر (ولی TBT) | **خیر** | [VERIFIED] `geo/…/jet-reviews/includes/assets.php:152-163` (قلاب `elementor/frontend/before_enqueue_scripts`) |
| WPML 4.x | قالب زبان‌گزین (CSS/JS هر قالب فعال) + پرچم‌های SVG | کوچک | head | بله (CSS) | زبان‌گزین سربرگ: بله | [VERIFIED] `geo/…/sitepress-multilingual-cms/classes/language-switcher/class-wpml-ls-assets.php:40-56` · ضبط: 4 پرچم (`tests/RESULT-lighthouse.txt:150`) |
| Rank Math (+Pro) | بی CSS/JS جلویی؛ فقط JSON-LD و متا | — | — | — | — | [VERIFIED] شمار پرونده‌های دارای `wp_enqueue_scripts` در `seo-by-rank-math*`: ۰ (grep این جلسه) |
| Duplicator Pro · hello-elementor | ۲ پرونده هر کدام قلاب جلویی دارند؛ hello-elementor پوستهٔ فعال نیست [INFERRED] | — | — | — | — | [UNKNOWN] `wp theme list --status=active` |

جمع‌بندی: [INFERRED] **حداقل ۵ دارایی مسدودکنندهٔ head که قالب ما مصرف نمی‌کند**: jQuery(+migrate)، `elementor-frontend`، `elementor-icons` (شرطی)، `jet-engine-frontend`، CSS `jet-reviews`؛ و در پاورقی ~۲۰۰ کیلوبایت gzipِ بی‌مصرف (Vue + PhotoSwipe + JetReviews + Elementor frontend/pro) که مستقیم به TBT موبایل (422ms) می‌افزاید.

---

## ۲ — مسیر تصویر LCP سربرگ

| ویژگی | وضعیت | منبع |
|---|---|---|
| تولید | `wp_get_attachment_image( $hero_id, 'full', … )` | [VERIFIED] `plugin/topersia-render/includes/topersia-g-zones.php:1336` |
| `loading` | `eager` | [VERIFIED] `…/topersia-g-zones.php:1327` |
| `fetchpriority` | `high` | [VERIFIED] `…/topersia-g-zones.php:1328` |
| `srcset` | خودکار وردپرس از اندازه‌های ثبت‌شده (اگر پیوست اندازه‌های میانی داشته باشد) | [INFERRED] رفتار `wp_get_attachment_image`؛ کد ما `add_image_size` ندارد (grep: ۰) |
| `sizes` | پیش‌فرض وردپرس `(max-width: Wpx) 100vw, Wpx` برای اندازهٔ `full`؛ سربرگ واقعاً تمام‌عرض است (`position:absolute; inset:0; width:100%`) — برای موبایل درست، ولی روی دسکتاپ بزرگ‌تر از ظرف ممکن است بکشد | [VERIFIED] CSS `plugin/topersia-render/assets/topersia-g-template.css:849,854` · [INFERRED] مقدار `sizes` |
| `preload` | **ندارد** — هیچ `<link rel=preload>` در کد ما | [VERIFIED] grep `preload` در `plugin/`: فقط `audio preload="none"` (`topersia-g-zones.php:4405`) |
| قالب پرونده | همان پروندهٔ بارگذاری‌شده (JPEG/PNG)؛ هیچ تبدیل WebP/AVIF (`image_editor_output_format`) | [VERIFIED] grep: ۰ رخداد |
| جای DOM | درون ویجت المنتور، پس از سربرگ وودمارت؛ کشف‌شدن به HTML و CSS قالب وابسته است، ولی `<img>` در HTML اولیه است (نه پس‌زمینهٔ CSS) | [INFERRED] |
| کش عنصر المنتور | خاموش برای ویجت ما (پویا) | [VERIFIED] `plugin/topersia-render/topersia-render.php:527-` (P5-D301) |

---

## ۳ — قلم Peyda

| | وضعیت | منبع |
|---|---|---|
| پرونده‌ها روی دیسک | ۹ وزن `PeydaWeb-*.woff2` در `geo/wp-content/uploads/fonts/` (Thin…Black)، هر کدام 39,940–42,964 بایت | [VERIFIED] `ls` این جلسه |
| وزن‌های پشتیبانی‌شده در کد | ثابت: ۶ وزن 300…800 (`font_w300`…`font_w800`) یا یک پروندهٔ متغیر | [VERIFIED] `plugin/topersia-render/topersia-render.php:338-361` |
| وزن‌های **واقعاً پیکربندی‌شده** | در پایگاه‌داده | [UNKNOWN] `wp option get topersia_render_settings --format=json \| php -r '$o=json_decode(stream_get_contents(STDIN),1); foreach($o as $k=>$v) if(strpos($k,"font_")===0) echo $k," ",($v!==""?"set":"-"),"\n";'` (فقط نام کلید، نه مقدار) |
| `font-display` | `swap` در هر `@font-face` | [VERIFIED] `…/topersia-render.php:345,359` |
| preload | **ندارد** | [VERIFIED] grep |
| زیرمجموعه | پروندهٔ Regular: 652 نقطه‌کد (لاتین پایه 95 · عربی پایه U+0600–06FF: 107 · شکل‌های نمایشی U+FB50–FEFF: 204 · دیگر 246)، ایستا (بی `fvar`) | [VERIFIED] fontTools روی `PeydaWeb-Regular.woff2` در این جلسه |
| `unicode-range` | تعریف نمی‌شود | [VERIFIED] `…/topersia-render.php:343-360` (فقط بلوک توضیحی در `assets/topersia-tokens.css:17-26`) |
| قلم دوم | `Vazirmatn` فقط در پشته؛ پرونده‌ای از ما بار نمی‌شود | [VERIFIED] `assets/topersia-tokens.css:29` |
| قلم‌های دیگر صفحه | `eicons.woff2` 108,152 (اگر `elementor-icons` صف شود) · `woodmart-font-*.woff2` (~12KB هر کدام) | [VERIFIED] اندازه؛ [UNKNOWN] بارشدن واقعی — فرمان §۱ |

[INFERRED] ۲۰۴ شکل نمایشی عربی برای موتور شکل‌دهی مرورگرهای امروز لازم نیستند (شکل‌دهی از جدول GSUB است)؛ زیرمجموعه‌سازی به U+0600–06FF + U+200C–200F + لاتین پایه + ارقام فارسی ۲۰–۳۰٪ اندازه را کم می‌کند — باید با `pyftsubset` و مقایسهٔ چشمی (`acceptance`) سنجیده شود.

---

## ۴ — MapLibre و PMTiles

| | وضعیت | منبع |
|---|---|---|
| کارت کناری | **فقط با رسیدن به دید**: `IntersectionObserver` با `rootMargin: '200px 0px'`؛ تا آن موقع SVG مرجع | [VERIFIED] `plugin/topersia-render/assets/topersia-g-zones.js:2621-2660` |
| بی IO | همه را فوراً سوار می‌کند (مرورگر قدیمی) | [VERIFIED] `…/topersia-g-zones.js:2644-2647` |
| مودال تمام‌صفحه | فقط با کلیک «باز کردن نقشهٔ کامل» | [VERIFIED] `…/topersia-g-zones.js:2663-2680` |
| بارگذار | تزریق پویا: `maplibre-gl.css` + `maplibre-gl.js` + `pmtiles.js` + `basemaps.js` (یک‌بار، Promise مشترک) | [VERIFIED] `…/topersia-g-zones.js:2025-2056` |
| افزونهٔ RTL | `setRTLTextPlugin( mapbox-gl-rtl-text.js, true )` — تنبل | [VERIFIED] `…/topersia-g-zones.js:2069` |
| اندازه JS/CSS نقشه | maplibre-gl.js 1,056,837 (gzip 275,098) · pmtiles.js 20,229 (7,956) · basemaps.js 38,680 (7,006) · rtl-text 133,355 (37,042) · css 70,024 (10,059) → **جمع gzip ≈ 337KB** | [VERIFIED] اندازه‌گیری این جلسه |
| قلم‌های نقشه (glyph) | `assets/map/fonts/` 3.8MB روی دیسک، بازه‌ای و تنبل | [VERIFIED] `du` این جلسه |
| کاشی | آنلاین اول (OpenFreeMap — **درخواست شخص ثالث**) با پشتیبان PMTiles خودمیزبان | [VERIFIED] `plugin/topersia-render/includes/topersia-g-zones.php:2696-2706` · `assets/topersia-g-zones.js:2089` |
| جای کارت در موبایل نسبت به اولین دید | — | [UNKNOWN] در کروم محلی ۳۹۰px: `document.querySelector('[data-tp-map]').getBoundingClientRect().top` با `innerHeight` مقایسه شود |

[INFERRED] اگر کارت نقشه در موبایل درون ۲۰۰px زیر اولین دید باشد، Lighthouse (که اسکرول نمی‌کند) آن را در همان بارِ نخست سوار می‌کند و ~1MB JS خام (275KB gzip) به TBT و LCP اضافه می‌شود. این مهم‌ترین «UNKNOWN» این سند است.

---

## ۵ — اندازهٔ DOM (تقریبی)

روش: شمارش برچسب‌های آغازین با `html.parser` پایتون روی قالب مرجع `reference/ToPersia-G3-Province-Template.html` (258,694 بایت). [VERIFIED] نتیجه: **1,377 عنصر** (۹۴ درون SVG)، عمق بیشینه ~13، ۴۴ `section/article`.
[INFERRED] صفحهٔ واقعی = این + سربرگ/پاورقی/منوی موبایل وودمارت + پوسته‌های المنتور + کارت‌های تکرارشونده از داده (رابطه‌ها، گالری، رویداد) → به احتمال زیاد **بالای ۱,۵۰۰** (آستانهٔ هشدار Lighthouse ۸۰۰، خطای ۱,۴۰۰). [UNKNOWN] — کروم محلی: `document.querySelectorAll('*').length` و `audits["dom-size"]` در JSON Lighthouse.

---

## ۶ — جدول اقدام‌ها (رتبه‌بندی‌شده)

اثر برآوردی: ↓↓ زیاد · ↓ متوسط · ~ ناچیز/نامعلوم — برآورد، نه سنجش. آزمون محلی پایهٔ هر اقدام: `php tests/assert.php` · `php tests/acceptance.php --url=…` · `node tests/lighthouse.js --ab=…` (بودجهٔ P5-D264).

### ۶-الف — کار خود ما (lane محلی: render/core)

| # | اقدام | LCP | TBT | CLS | پرونده:خط | ریسک | آزمون محلی لازم | SEO · GEO · AEO · چندزبانه · نگه‌داری |
|---|---|---|---|---|---|---|---|---|
| **A0** | ضبط دوبارهٔ مبنا پس از P5-D359 و با **یک تصویر واقعی** به‌جای picsum برای سربرگ؛ ثبت عنصر LCP | — | — | — | `tests/lighthouse.js` | هیچ | `--record` + `--runs=5` | پیش‌شرط تفسیر همهٔ اقدام‌ها |
| **A1** | dequeue شرطی روی صفحهٔ موجودیت (`is_singular('tp_*')`): `jet-reviews` (CSS+JS+Vue+PhotoSwipe)، `jet-engine-frontend`، `elementor-icons` | ↓↓ (FCP/LCP موبایل) | ↓↓ | ~ | نو در `plugin/topersia-core/topersia-core.php` کنار `topersia_drop_block_styles` (`:4431-4442`) | متوسط: هر صفحهٔ دیگری که ویجت JetReviews/JetEngine دارد نباید دست بخورد؛ فقط روی نوع‌های `tp_` | `alltypes.php`، `user-flows` (فرم نظر ما)، `acceptance` کروم/فایرفاکس، `contrast` | SEO/GEO/AEO خنثی (محتوا سمت سرور است)؛ چندزبانه خنثی؛ نگه‌داری: فهرست handleها با به‌روزرسانی افزونه‌ها باید بازبینی شود (assert ادعای وجود handle) |
| **A2** | preload تصویر سربرگ (`<link rel=preload as=image imagesrcset imagesizes fetchpriority=high>`) در `wp_head` برای صفحهٔ موجودیت | ↓↓ (وقتی LCP تصویر است) | ~ | ~ | `plugin/topersia-render/includes/topersia-g-zones.php:1319-1336` (همان شناسهٔ پیوست) | کم؛ خطر دوبار بارگیری اگر srcset/sizes با `<img>` نخواند | `assert` (یک ادعای الگو)، Lighthouse «preload LCP image» | SEO مثبت (CWV)؛ GEO/AEO خنثی؛ چندزبانه: پیوست ترجمه‌شده WPML باید همان شناسه را بدهد |
| **A3** | `sizes` صریح برای سربرگ (`100vw`) و اندازهٔ میانی تمام‌عرض (مثلاً 1600/1200/800) + خروجی WebP (`image_editor_output_format`) | ↓ | ~ | ~ | `…/topersia-g-zones.php:1323-1336` · core (صافی خروجی) | متوسط: بازسازی بندانگشتی‌ها (`wp media regenerate`)؛ فضای دیسک | `acceptance` (برش `object-position`)، بازدید چشمی سربرگ | SEO مثبت (تصویر سبک‌تر در Google Images هم می‌آید)؛ نگه‌داری: پس از هر درون‌ریزی |
| **A4** | preload یک وزن Peyda (Regular یا وزن تیتر) + `unicode-range` + زیرمجموعه‌سازی (حذف شکل‌های نمایشی) | ↓ (FCP/LCP متنی) | ~ | ↓ (جهش قلم) | `plugin/topersia-render/topersia-render.php:338-361` · بلوک `assets/topersia-tokens.css:17-26` | کم/متوسط: زیرمجموعهٔ ناقص = جعبهٔ خالی در en/ar | `acceptance` سه زبان، `contrast`، بازدید چشمی عربی | چندزبانه: زیرمجموعه باید ارقام و نشانه‌های عربی/لاتین را نگه دارد؛ GEO/AEO خنثی |
| **A5** | محدودکردن دارایی‌های render به صفحه‌های نیازمند (نوع `tp_`، شورت‌کدها) به‌جای همهٔ صفحه‌ها | ~ (روی استان/شهر)؛ ↓↓ روی بقیهٔ صفحه‌ها | ~ | ~ | `plugin/topersia-render/topersia-render.php:413-427,524` | متوسط: صفحه‌های المنتوری با شورت‌کد `tp_` باید شناسایی شوند | `alltypes.php` هر شورت‌کد | نگه‌داری: یک تابع «نیاز دارد؟» برای render و user-core |
| **A6** | کمینه‌سازی `topersia-g-template.css` (~-31٪ خام) و `topersia-g-zones.js` در ساخت (`tp-template-gen.php`)، نگه‌داشتن نسخهٔ خوانا در مخزن | ↓ (کم) | ~ | ~ | `assets/topersia-g-template.css` (مولد: `scripts/tp-template-gen.php`) | کم؛ آزمون‌های `assert` الگوی CSS را در پروندهٔ خوانا می‌جویند — باید مسیر را بدانند | `assert`، `acceptance` | نگه‌داری: دو پرونده؛ gzip سرور اثر را کوچک می‌کند |
| **A7** | CSS بحرانی سربرگ درون‌خطی، بقیهٔ `topersia-g-template.css` غیرمسدودکننده (`media=print onload`) | ↓↓ (FCP) | ~ | **ریسک ↑** | `…/topersia-render.php:447-452` | **زیاد**: FOUC و جهش چیدمان؛ کالیبراسیون `acceptance` | `acceptance` + `header-gap.js` + CLS در Lighthouse | SEO مثبت اگر CLS صفر بماند؛ نگه‌داری سنگین (دو منبع CSS) — پس از A1/A2/A4 تصمیم بگیر |
| **A8** | نقشه: اگر §۴ نشان داد کارت در موبایل نزدیک دید است، `rootMargin` به 0 و/یا سوارشدن پس از `requestIdleCallback`/تعامل؛ حذف درخواست شخص ثالث OpenFreeMap در بار نخست (PMTiles اول) | ↓↓ (اگر در دید) | ↓↓ | ~ | `assets/topersia-g-zones.js:2649-2656,2089` · `includes/topersia-g-zones.php:2696-2706` | کم؛ تصمیم «آنلاین اول» (دستهٔ ۳) با کاربر است | `map-3` کروم/فایرفاکس، `behaviour` | GEO خنثی (SVG مرجع و مختصات در HTML می‌ماند)؛ «صفر شخص ثالث» هدف فاز ۹ |
| **A9** | کش صفحه (LiteSpeed Cache روی زنده) با پاک‌کردن والدها (P5-D301) و بیرون‌ماندن دادهٔ زنده | TTFB ↓↓ (زنده) | ~ | ~ | سیاست فاز ۹ `docs/SPRINT-4.4-PHASES.md:1240` | متوسط: دادهٔ شخصی در HTML کش‌شده | آزمون پاک‌شدن والد پس از پیش‌نویس‌کردن فرزند | SEO مثبت؛ چندزبانه: کلید کش به ازای زبان |

### ۶-ب — تنظیم افزونه (بی کد؛ در پیشخوان، با پشتیبان)

| # | اقدام | LCP | TBT | CLS | جا | ریسک | آزمون محلی | اثر |
|---|---|---|---|---|---|---|---|---|
| **B1** | المنتور ← آزمایه‌ها: «Inline Font Icons» (`e_font_icon_svg`) روشن → `elementor-icons` + eicons.woff2 (108KB) نمی‌آید | ↓ | ~ | ~ | `geo/…/elementor/includes/frontend.php:667-669` | کم؛ آیکون‌های سربرگ وودمارت از قلم خودش‌اند [INFERRED] | `acceptance`، بازدید سربرگ و پاورقی | خنثی |
| **B2** | المنتور ← «Improved Asset Loading» / «Optimized Markup» (اگر در 4.3.2 هنوز آزمایه‌اند) | ↓ | ↓ | ~ | پیشخوان المنتور | متوسط: قالب‌های دیگر سایت | `alltypes`، صفحهٔ اصلی | DOM کمتر |
| **B3** | JetEngine: صافی `jet-engine/listings/styles-in-footer` = true (یا تنظیم معادل) → CSS فقط وقتی ویجت JetEngine هست | ↓ | ~ | ~ | `geo/…/jet-engine/includes/components/listings/frontend.php:177-179` | کم (صافی؛ در core ما یک خط — دستهٔ الف هم می‌شود) | `acceptance` | خنثی |
| **B4** | Woodmart ← Performance: «Combine CSS/JS» خاموش بماند (پیش‌فرض)؛ «styles_always_use» خالی؛ «Font Awesome» = «never/as needed»؛ «jQuery migrate» خاموش؛ «Lazy loading» تصویرهای وودمارت روی سربرگ ما اثری ندارد | ↓ | ↓ | ~ | `geo/…/woodmart/inc/enqueue.php:596-605,618-628,665-670` | کم/متوسط | `acceptance` + `header-gap.js` | خنثی |
| **B5** | WPML ← زبان‌گزین: «Don't load CSS for language switchers» و CSS لازم را در `theme-bridge` بیاور؛ پرچم SVG درون‌خطی/حذف | ~ | ~ | ~ | `geo/…/class-wpml-ls-assets.php:49-56` | کم | `lang-equiv-local`، زبان‌گزین در سه زبان | چندزبانه: باید hreflang دست نخورد (مستقل از CSS است) |
| **B6** | المنتور ← «Google Fonts: Disable» و «Font-display: swap» (P5-D359 در کد هم دارد؛ تنظیم هم یکی شود) | ~ | ~ | ~ | پیشخوان | هیچ | — | — |

### ۶-ج — ناممکن (یا بسیار پرهزینه) بی تغییر پایهٔ وودمارت/المنتور

| # | مانع | چرا | منبع |
|---|---|---|---|
| **C1** | jQuery در head | `elementor-frontend-modules` و `wd-libraries` به `jquery` وابسته‌اند؛ بردنش به footer با `wp_scripts()->add_data('jquery-core','group',1)` ممکن است، ولی هر اسکریپت درون‌خطیِ head که `jQuery(...)` صدا بزند می‌شکند (Woodmart و Elementor اسکریپت درون‌خطی دارند) — بی بازنویسی پایه تضمین‌پذیر نیست | [VERIFIED] `geo/…/elementor/includes/frontend.php:369-376` (وابستگی jquery) · `geo/…/woodmart/inc/enqueue.php:134` · [INFERRED] اسکریپت درون‌خطی |
| **C2** | `elementor-frontend` (CSS) و `frontend.min.js` روی صفحه‌ای که فقط یک ویجت شورت‌کد دارد | المنتور صفحه را رندر می‌کند و پوسته/بخش‌ها به این CSS برای چیدمان ستون وابسته‌اند؛ حذف = شکستن چیدمان بخش | [VERIFIED] `…/elementor/includes/frontend.php:558-559,671` |
| **C3** | سربرگ و پاورقی وودمارت (DOM + `base-rtl.css` + `header-base`) | سازندهٔ سربرگ وودمارت markup و CSS خودش را دارد؛ کاهش DOM بی بازطراحی سربرگ نیست | [VERIFIED] `…/woodmart/inc/enqueue.php:662-664` |
| **C4** | رسیدن به ۹۵ موبایل با پشتهٔ فعلی | [INFERRED] جمع JS پاورقی شخص ثالث + CSS مسدودکنندهٔ پایه، حتی پس از A1/B1/B3، احتمالاً امتیاز موبایل را زیر ۹۵ نگه می‌دارد؛ طبق `docs/SPRINT-4.4-PHASES.md:1241` «مانع دقیق با هزینهٔ اندازه‌گرفته‌شده» باید با `--ab` پس از هر گام ثبت شود، نه این‌جا | — |

---

## ۷ — پنج اقدام برتر (ترتیب اجرا)

1. **A0** ضبط دوبارهٔ مبنا با تصویر واقعی و ثبت عنصر LCP (بدون آن، اثر هر اقدام نامعلوم است).
2. **A1** حذف شرطی JetReviews (Vue/PhotoSwipe/CSS)، JetEngine CSS و eicons روی نوع‌های `tp_` — بیشترین برش مسدودکننده و TBT.
3. **A2 + A3** preload، `sizes` و WebP برای تصویر سربرگ.
4. **A4** preload یک وزن Peyda + `unicode-range` + زیرمجموعه.
5. **A8** سنجش جای کارت نقشه در موبایل و (در صورت لزوم) تأخیر MapLibre/حذف درخواست شخص ثالث در بار نخست.

## ۸ — UNKNOWNهای این سند (برای `LOCAL-QUERIES.md`)

| شناسه | پرسش | فرمان محلی |
|---|---|---|
| LQ-PERF-01 | فهرست واقعی link/script صفحهٔ استان و شهر | فرمان `curl … grep` در §۱ (با کوکی ورود) |
| LQ-PERF-02 | عنصر LCP در ضبط | JSON Lighthouse: `largest-contentful-paint-element` |
| LQ-PERF-03 | آزمایهٔ `e_font_icon_svg` | `wp option get elementor_experiment-e_font_icon_svg` |
| LQ-PERF-04 | وزن‌های Peyda پیکربندی‌شده | فرمان §۳ (فقط نام کلید) |
| LQ-PERF-05 | CSS پست المنتور (kit و صفحه) | `ls -la geo/wp-content/uploads/elementor/css` |
| LQ-PERF-06 | جای کارت نقشه در ۳۹۰px | §۴ |
| LQ-PERF-07 | اندازهٔ DOM واقعی | `document.querySelectorAll('*').length` · `dom-size` |
| LQ-PERF-08 | پوستهٔ فعال و گزینه‌های عملکرد وودمارت | `wp theme list --status=active` · `wp option get xts-woodmart-options --format=json \| grep -o '"\(combined_[a-z_]*\|font_awesome_css\|styles_always_use\)":[^,]*'` |
