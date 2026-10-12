# MAP.md — ممیزی نقشه (بخش کدخوانی؛ بخش پایگاه‌داده‌ای و PMTiles محلی می‌ماند)

> فاز ۲د · ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ — **فقط‌خواندنی**. `iran.pmtiles` و پایگاه‌داده در این ماشین نیست؛ پس هر بند وابسته به آن‌ها `UNKNOWN` با فرمان محلی است.
> برچسب‌ها: `VERIFIED` (مسیر و خط) · `INFERRED` · `ASSUMED` · `UNKNOWN`.
> نام‌اختصار: `RENDER` = `plugin/topersia-render` · `CORE` = `plugin/topersia-core` · `DOC` = `docs` · `REF` = `reference` · `ROOT` = ریشۀ مخزن (`C:\wamp64\www\topersia`).
> `MAP/` = `plugin/topersia-render/assets/map`.
> **بخش نیازمند محلی:** پوشش `name:fa|ar|en`، سرآیند/متادیتای PMTiles، آزمون Range/206، ردیف‌های نمایه، پاسخ زندۀ `/map-points`.

---

## ۱ — خلاصۀ حکم

| پرسش | پاسخ | برچسب |
|---|---|---|
| زنجیره | `[tp_map]` ← پیکربندی سرور (`data-tp-map-cfg`) ← `TopersiaMap` (تنبل، با IntersectionObserver یا مودال) ← MapLibre + PMTiles + Protomaps basemaps ← کاشی از `uploads/tiles/iran.pmtiles`؛ SVG مرجع تا آماده‌شدن و در نبود کاشی | `[VERIFIED]` §۲ |
| نسخۀ MapLibre | **5.24.0** | `[VERIFIED]` `MAP/maplibre-gl.js:3,46` |
| افزونۀ RTL | `mapbox-gl-rtl-text.js` محلی و تنبل (`setRTLTextPlugin(…, true)`)، نه از CDN | `[VERIFIED]` `RENDER/assets/topersia-g-zones.js:2062-2067` |
| `/map-points` | **هست** (GET، `context` = شناسۀ استان/شهر، GeoJSON، کش مرکزی)؛ نمایۀ مشتق `topersia_geo` **هست** — هر دو **فقط محلی** (`wp_get_environment_type() === 'local'`) | `[VERIFIED]` `RENDER/includes/topersia-g-mappoints.php:153-165` · `CORE/includes/topersia-geo-index.php:26-28` |
| مبنای حکم ARCH-RECONCILIATION | بند «پس از ۲د: بنچمارک پیش از ساخت جدول» **منسوخ است**: جدول ساخته و بنچمارک A/B روی MariaDB اجرا و A انتخاب شده | `[VERIFIED]` `DOC/GEO-BENCH-2F.md:1-21` (اجرا و ثبت‌شده) |
| `entity_uid` در نمایه | **نیست**؛ فقط `post_id`، `post_type`، مختصات، `province_id`، `city_id`، `index_version` — حکم ARCH §۴ («ستون‌ها: `entity_uid`، شناسه‌های محدوده، `index_version`») با کد نمی‌خواند | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:63-77` |
| پیشنهاد برای معمار | شرط «فقط محلی تا ممیزی پیوست الف بند ۶» (کد و CLAUDE.md) محرکش را از دست داده، چون پیوست الف از ۱۱ اکتبر منسوخ است؛ برداشتن دروازه تصمیم صریح لازم دارد | `[VERIFIED]` `CORE/includes/topersia-geo-index.php:11-12` · `DOC/SPRINT-4.4-PHASES.md:1284` |

---

## ۲ — زنجیرۀ فعلی (از HTML تا کاشی)

| گام | چه می‌کند | شاهد | برچسب |
|---|---|---|---|
| ۱ | `[tp_map]` مختصات پست را می‌خواند؛ **اگر مختصات خالی باشد هیچ نقشه‌ای ساخته نمی‌شود** (مختصات جعلی از والد نمی‌گیرد) | `RENDER/includes/topersia-g-zones.php:2707-2711` | `[VERIFIED]` |
| ۲ | `topersia_g_map_provider()` مسیر کاشی را از لایۀ سرویس `map.tiles` (`extra.path`) یا پیش‌فرض `tiles/iran.pmtiles` می‌خواند؛ **فقط اگر فایل در `uploads` هست** پیکربندی می‌دهد، وگرنه آرایۀ خالی ← SVG | `RENDER/includes/topersia-g-zones.php:2604-2625` · `RENDER/includes/topersia-g-core.php:66-67` | `[VERIFIED]` |
| ۳ | پیکربندی روی عنصر: `data-tp-map-cfg` = JSON: `name`، `base` (نشانی `assets/map/`)، `tiles`، `attribution`، `zoom` (به نوع)، `lang`، `locale`، و `points` (نشانی REST) | `RENDER/includes/topersia-g-zones.php:2655-2674` | `[VERIFIED]` |
| ۴ | `TopersiaMap.load(cfg)` تنبل: CSS و `maplibre-gl.js` و `pmtiles.js` و `basemaps.js` را با `<script async=false>` می‌گذارد؛ `Protocol` پی‌ام‌تایلز را ثبت می‌کند؛ افزونۀ RTL را تنبل | `RENDER/assets/topersia-g-zones.js:2044-2075` | `[VERIFIED]` |
| ۵ | کارت کناری: **غیرتعاملی**، وقتی دیده شد (`IntersectionObserver`، حاشیۀ ۲۰۰ پیکسل)؛ مودال: **تعاملی** با کلیک «باز کردن نقشۀ کامل» | `RENDER/assets/topersia-g-zones.js:2313-2351,2354-2381` | `[VERIFIED]` |
| ۶ | سبک: `glyphs: {base}fonts/{fontstack}/{range}.pbf`، `sprite: {base}sprites/light`، منبع `pmtiles://{tiles}`؛ لایه‌ها از `basemaps.layers('protomaps', flavor, {lang})`؛ حالت عصر/شب ← سبک `dark` | `RENDER/assets/topersia-g-zones.js:2077-2101` | `[VERIFIED]` |
| ۷ | نشانگرها: `fetch(cfg.points)` یک بار؛ `GeoJSON` منبع خوشه‌ای MapLibre (`clusterRadius 46`, `clusterMaxZoom 11`)، پنج آیکون خانواده | `RENDER/assets/topersia-g-zones.js:2103-2243` | `[VERIFIED]` |
| ۸ | بارگذاری بر پایۀ viewport **نیست** (کل زمینه یک بار) | `RENDER/assets/topersia-g-zones.js:2231-2243` | `[VERIFIED]` |
| ۹ | ارائه‌دهندۀ جایگزین از صافی `topersia_g_map_provider`؛ رابط صفحه تنها `TopersiaMap` (آداپتور) | `RENDER/includes/topersia-g-zones.php:2627-2629` · `RENDER/assets/topersia-g-zones.js:2296` | `[VERIFIED]` |

---

## ۳ — نسخه‌ها و کتابخانه‌ها (همه محلی در `MAP/`)

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| MapLibre GL JS | 5.24.0 (BSD-3؛ پروندۀ مجوز کنارش) | `MAP/maplibre-gl.js:3,46` · `MAP/LICENSE-maplibre-gl.txt` | `[VERIFIED]` |
| PMTiles (پروتکل) | `pmtiles.js` (BSD-3؛ مشخصات PMTiles دامنۀ عمومی)؛ **نسخه در پرونده درج نشده** | `MAP/LICENSE-pmtiles.txt` | `[VERIFIED]` (مجوز) · نسخه `[UNKNOWN]` محلی: `sha256sum plugin/topersia-render/assets/map/pmtiles.js` و مقایسه با `dist/index.js` بستۀ npm `pmtiles` (فرمان: `npm pack pmtiles@<نسخه>` سپس `tar xzf` و `sha256sum package/dist/index.js`) |
| Protomaps basemaps (سبک) | `basemaps.js`، ۴۰ زبان از جمله `fa` و `ar` (`script: Arabic`) و `en` | `MAP/basemaps.js:15` (جدول `language_script_pairs`) | `[VERIFIED]` |
| افزونۀ RTL | `mapbox-gl-rtl-text.js` (۱۳۳ کیلوبایت؛ حق نشر ۲۰۲۶ Mapbox، BSD)؛ `lazy=true`؛ نشانی از `base` | `MAP/LICENSE-mapbox-gl-rtl-text.txt` · `RENDER/assets/topersia-g-zones.js:2065` | `[VERIFIED]` |
| فونت | سه دسته Noto Sans (`Regular`، `Medium`، `Italic`)، هرکدام ۱۵ بازه PBF؛ بازه‌های عربی **هست**: ۱۵۳۶–۱۷۹۱ (U+0600–06FF)، ۱۷۹۲–۲۰۴۷ (تکمیلی)، ۶۴۲۵۶–۶۵۲۷۹ (شکل‌های ارائه) | `MAP/fonts/Noto Sans Regular/` (فهرست پرونده) · `MAP/LICENSE-fonts.txt` (SIL OFL) | `[VERIFIED]` |
| اسپرایت | فقط `light` (۱× و ۲×)؛ سبک `dark` هم از همین استفاده می‌کند | `MAP/sprites/` · `RENDER/assets/topersia-g-zones.js:2090-2091` | `[VERIFIED]` |
| ارجاع به CDN/خارج | **هیچ**: همۀ کتابخانه‌ها، فونت‌ها و اسپرایت از افزونه؛ تنها وابستگی بیرونی در زمان اجرا همان فایل کاشی خودمیزبان و `/map-points` | `RENDER/assets/topersia-g-zones.js:2052,2085-2091` | `[VERIFIED]` |
| نسخۀ MapLibre v6 | تعویق (کار جدا) — کد هنوز به API `setRTLTextPlugin` وابسته است | `RENDER/assets/topersia-g-zones.js:2065` | `[VERIFIED]` |

---

## ۴ — مسیر PMTiles، فونت و sprite

| مورد | مسیر | شاهد | برچسب |
|---|---|---|---|
| کاشی | `{uploads}/tiles/iran.pmtiles`؛ نشانی `set_url_scheme( baseurl + rel )`؛ پرونده خارج از git (در `.gitignore` ریشه) | `RENDER/includes/topersia-g-zones.php:2613-2621` · `ROOT/.gitignore:42` | `[VERIFIED]` |
| فونت | `{plugin}/assets/map/fonts/{fontstack}/{range}.pbf` | `RENDER/assets/topersia-g-zones.js:2089` | `[VERIFIED]` |
| sprite | `{plugin}/assets/map/sprites/light(.json/.png/@2x.*)` | `RENDER/assets/topersia-g-zones.js:2091` | `[VERIFIED]` |
| وجود فایل `iran.pmtiles` و اندازه/هدر/حداکثر زوم/کران | — | — | `[UNKNOWN]` محلی: `ls -l geo/wp-content/uploads/tiles/iran.pmtiles` و `pmtiles show geo/wp-content/uploads/tiles/iran.pmtiles` (go-pmtiles) |
| لایه‌ها و کلیدهای برچسب داخل کاشی | — | — | `[UNKNOWN]` محلی: `pmtiles show geo/wp-content/uploads/tiles/iran.pmtiles --metadata` (فهرست `vector_layers`) |
| Range/206، `Accept-Ranges`، فشرده‌سازی، کش، CORS برای آن فایل (فاز ۱۰ حکم ARCH §۴) | تست‌های مخزن فقط **وجود فایل‌ها** را می‌سنجند نه Range | — | `[VERIFIED]` `WS/tests/assert-430.php:319` (فقط `is_file`) |
| پاسخ هدر کاشی | — | — | `[UNKNOWN]` محلی: `curl -sI -H "Range: bytes=0-16383" "$(wp eval 'echo wp_upload_dir()["baseurl"];')/tiles/iran.pmtiles"` (انتظار: `206`, `Content-Range`, `Accept-Ranges: bytes`)؛ روی زنده نیز همین با آدرس نهایی |

---

## ۵ — برچسب نقشه به زبان (`name:fa` · `name:ar` · `name:en`)

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| زبان سبک | `lang = topersia_g_page_lang()` ← `basemaps.layers(…, { lang })` (پیش‌فرض `fa`) | `RENDER/includes/topersia-g-zones.php:2665` · `RENDER/assets/topersia-g-zones.js:2099` | `[VERIFIED]` |
| زنجیرۀ بازگشت در کتابخانه | برچسب کشور: `coalesce(name:{lang}, name:en)`؛ برچسب مکان‌ها از `get_multiline_name` (شرط `is_not_in_target_script` ← `name:{lang}` ← `name:en` ← `name`) | `MAP/basemaps.js:15` | `[VERIFIED]` (ساختار) · رفتار روی داده `[INFERRED]` |
| توضیح کد ما | «نبود ← name» (کامنت) — با زنجیرۀ کتابخانه (`name:en` پیش از `name`) **دقیقاً یکی نیست** | `RENDER/assets/topersia-g-zones.js:2015` | `[VERIFIED]` |
| پوشش `name:fa` · `name:ar` · `name:en` در `iran.pmtiles` | — | — | `[UNKNOWN]` محلی: اسکریپت نمونه‌گیری شمارشی `LQ-SCRIPT-NAMES` در `LOCAL-QUERIES.md` (۳ زوم × ۲۰ کاشی نمونه؛ شمار ویژگی‌ها با/بی هر کلید؛ بی نام خاص در گزارش) |
| رابط MapLibre (دکمه‌ها، aria) | ۸ کلید `locale` از کاتالوگ سرور | `RENDER/includes/topersia-g-zones.php:2682-2695` | `[VERIFIED]` |
| زبان نشانگرها | نام، برچسب نوع و نشانی هر نشانگر به **نسخۀ همان زبان** پست (شناسۀ نسخه) | `RENDER/includes/topersia-g-mappoints.php:98-111` | `[VERIFIED]` |

---

## ۶ — `/map-points` و نمایۀ مکانی (از کد)

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| مسیر | `GET /wp-json/topersia/v1/map-points?context={post_id}`؛ `permission_callback = __return_true`؛ `context` اجباری عدد صحیح | `RENDER/includes/topersia-g-mappoints.php:157-164` | `[VERIFIED]` |
| پیش‌نیاز | `topersia_geo_index_enabled()` یعنی `local`؛ روی هر محیط دیگر خروجی **FeatureCollection خالی** و ویژگی `points` به پیکربندی نقشه اضافه نمی‌شود | `RENDER/includes/topersia-g-mappoints.php:50-56,130-132` · `RENDER/includes/topersia-g-zones.php:2669-2671` | `[VERIFIED]` |
| زمینه‌ها | فقط استان (شهر · جاذبه · طبیعت) و شهر (جاذبه · محله · روستا)؛ سایر نوع‌ها نشانگر ندارند | `RENDER/includes/topersia-g-mappoints.php:38-46` | `[VERIFIED]` |
| خانواده‌ها | ۵ خانواده (`city attraction nature district village`) به نوع‌های `tp_` | `RENDER/includes/topersia-g-mappoints.php:24-33` | `[VERIFIED]` |
| سقف | عدد ثابت **۴۰۰** نشانگر در هر پاسخ (`$cap`) — ARCH §۴ «هیچ عدد ثابت برای شمار نشانگر» را می‌گوید؛ با کد نمی‌خواند | `RENDER/includes/topersia-g-mappoints.php:80,88` | `[VERIFIED]` |
| قرارداد پاسخ | `FeatureCollection` با ویژگی‌های `id k n t u i`؛ **بی** شیء `meta` (نسخۀ نمایه، شمار، زمان) که حکم §۴ «قرارداد GeoJSON + meta» می‌خواهد | `RENDER/includes/topersia-g-mappoints.php:101-116` | `[VERIFIED]` |
| فیلتر منتشرشده | هر ردیف نمایه از `topersia_g_published()` می‌گذرد (دروازۀ P5-D247) — **یک‌به‌یک داخل حلقه** (تا ۴۰۰ فراخوانی) | `RENDER/includes/topersia-g-mappoints.php:92` | `[VERIFIED]` (الگو) · هزینه `[INFERRED]` |
| کش | `topersia_cache_remember('map.points', [زمینه, زبان, گواه نمایه], …, ttl 15 دقیقه, keep 1 ساعت)` در ترنزینت | `RENDER/includes/topersia-g-mappoints.php:139-147` · `CORE/includes/topersia-cache.php:329-331` | `[VERIFIED]` |
| گواه تازگی | `topersia_geo_index_stamp(true)` با **هر** ذخیرۀ پست `tp_` (حتی بی‌ربط به مختصات) عدد را عوض می‌کند و همۀ کلیدهای `map.points` را باطل | `CORE/includes/topersia-geo-index.php:141-148,284-295` | `[VERIFIED]` |
| جدول | `{prefix}topersia_geo`: `post_id` (کلید اصلی)، `post_type`، `lat DECIMAL(8,6)`، `lng DECIMAL(9,6)`، `geom_kind`، `province_id`، `city_id`، `index_version`؛ شاخص‌های `(lat,lng)`, `(post_type,lat,lng)`, `(province_id,post_type)`, `(city_id,post_type)` | `CORE/includes/topersia-geo-index.php:63-77` | `[VERIFIED]` |
| ردیف‌ها | فقط پست منتشرشدۀ **زبان پیش‌فرض** با مختصات معتبر و درون ایران؛ ترجمه‌ها ردیف جدا ندارند | `CORE/includes/topersia-geo-index.php:85-116` | `[VERIFIED]` |
| همگام‌سازی | `save_post` (اولویت ۳۰)، `before_delete_post`، `transition_post_status`؛ بازسازی کامل با `wp topersia geo-index rebuild` و `verify` (اثرانگشت) | `CORE/includes/topersia-geo-index.php:284-315,327-344` | `[VERIFIED]` |
| زنجیرۀ استان/شهر | از صافی `topersia_geo_index_chain` (رابطۀ R01) که `render` می‌دهد؛ **ذخیرۀ پست پیش از ثبت رابطه** (JetEngine جدا ذخیره می‌کند) ممکن است زنجیره را کهنه بنویسد و هیچ قلابی روی تغییر رابطه نمایه را تازه نمی‌کند | `RENDER/includes/topersia-g-relreg.php:365` · `CORE/includes/topersia-geo-index.php:118-126,284-295` | `[VERIFIED]` (قلاب‌ها) · اثر `[INFERRED]` |
| `/nearby`، POST، bbox، نمایۀ POINT (B) | **ساخته نشده**؛ «مسیریابی، نشان و نزدیک من در این دسته ساخته نمی‌شوند» | `RENDER/includes/topersia-g-mappoints.php:8-9` | `[VERIFIED]` |
| لایۀ Map Query با پشتیبان قابل‌تعویض (حکم §۴ پذیرش) | **نیست**: تابع مستقیم `topersia_geo_index_context()` که `render` صدا می‌زند؛ تنها نقطۀ انعطاف صافی `topersia_g_map_families` است | `RENDER/includes/topersia-g-mappoints.php:84` · `CORE/includes/topersia-geo-index.php:267-280` | `[VERIFIED]` |
| ناوبری (deep-links) | الگوی Google و Waze با `on=false`؛ الگوی Neshan و Balad **خالی** (مطابق قاعدۀ ۱۱: بدون مستند رسمی، نشانی ساخته نمی‌شود) | `CORE/includes/topersia-services.php:96-101` | `[VERIFIED]` |
| مصرف‌کنندۀ مسیریابی/ماتریس/آدرس نشان | آداپتورها ثبت‌اند؛ هیچ کدی `topersia_api()` برای `routing` یا `distance_matrix` یا `geocoding` یا `navigation` را صدا نمی‌زند | `CORE/includes/topersia-services.php:93-95` | `[VERIFIED]` (نبود مصرف‌کننده) |
| جدول کش هسته `topersia_cache` | فقط محلی؛ سه سرویس (`routing`, `distance_matrix`, `geocoding`) از آن می‌خوانند؛ `map.points` در ترنزینت | `CORE/includes/topersia-cache.php:291-293,329-331` | `[VERIFIED]` |
| نمایه در پایگاه: شمار ردیف، توزیع نوع، پوشش `province_id`/`city_id` | — | — | `[UNKNOWN]` محلی: `wp topersia geo-index report` و `wp db query "SELECT post_type, COUNT(*) n, SUM(province_id>0) p, SUM(city_id>0) c FROM $(wp db prefix)topersia_geo GROUP BY 1"` |
| موتور و نسخۀ پایگاه‌داده (MariaDB، نه MySQL) | محلی طبق CLAUDE.md MariaDB 10.11.19؛ زنده «هم‌ارز» ادعا شده | — | `[INFERRED]` `WS/CLAUDE.md` (جدول محیط) · `[UNKNOWN]` محلی/زنده: `wp db query "SELECT VERSION()"` |
| پاسخ واقعی `/map-points` برای یک استان | — | — | `[UNKNOWN]` محلی: `curl -s "http://topersia.local/geo/wp-json/topersia/v1/map-points?context=<ID_استان>" \| head -c 600` |

---

## ۷ — بنچمارک A در برابر B (طرح و وضعیت)

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| اجرا شده؟ | بله، ۱۲ اکتبر روی MariaDB 10.11.19 محلی با ۲۰۰٬۰۰۰ نقطۀ **ساختگی**، ۶۰ اجرا برای هر پرس‌وجو | `DOC/GEO-BENCH-2F.md:3` · `DOC/GEO-BENCH-2F.json:1-20` | `[INFERRED]` (گزارش؛ این جلسه اجرا نکرد) |
| نتیجه | A (`DECIMAL` + نمایۀ ترکیبی) در قاب+نوع و زمینۀ استان/شهر برنده یا هم‌ارز؛ B فقط در «۲۰ نزدیک‌ترین» (۲٫۳ ← ۰٫۷ میلی‌ثانیه) | `DOC/GEO-BENCH-2F.md:9-21` | `[INFERRED]` |
| مقیاس واقعی داده | سند می‌گوید ≈ ۷ هزار پست؛ ولی ممیزی کیفیت محلی ۱۸۳ پست منتشرشدۀ زبان پیش‌فرض در ۲۰ نوع شمرده (۵۹ مختصات معتبر، ۱۲۳ بی مختصات) — **حدود ۴۰ برابر کوچک‌تر**؛ شهر: ۱۱۴ پست و فقط ۱۲ معتبر | `DOC/GEO-BENCH-2F.md:21,23-25` · `DOC/GEO-QUALITY-2F.json` | `[INFERRED]` |
| پیامد برای حکم ARCH §۴ («۱۰۰ تا ۱۰۰٬۰۰۰ نقطه») | پوشش ۱۰۰٬۰۰۰ در این بنچمارک بدیهی است؛ **کمبود واقعی داده‌ی مختصات است نه کارایی** (۱۰۱ شهر بی مختصات) | `DOC/GEO-BENCH-2F.md:25` | `[INFERRED]` |
| طرح باقی‌مانده (اجرا نه) | (۱) بنچمارک با داده‌ی واقعی پس از پر شدن مختصات؛ (۲) ایجاد POINT کنار A فقط وقتی `/nearby` ساخته شد؛ (۳) آزمون Range/206 روی کاشی؛ (۴) سنجش `name:fa` | — | `[ASSUMED]` |
| وضعیت مختصات امروز | — | — | `[UNKNOWN]` محلی: `wp topersia geo-index report` |

---

## ۸ — تناقض یا نکتۀ اجرایی برای معمار

| # | یافته | برچسب |
|---|---|---|
| M1 | حکم «بنچمارک A/B پیش از ساخت جدول» گذشته و عملی شده؛ سند ARCH باید به‌روز شود | `[VERIFIED]` |
| M2 | نمایه `entity_uid` ندارد؛ برای قرارداد `GeoJSON` با شناسۀ پایدار و همگام‌سازی چندزبانه لازم است | `[VERIFIED]` |
| M3 | سقف ثابت ۴۰۰ و نبود `meta`: با حکم §۴ می‌خواند یا نه؟ — تصمیم معمار | `[VERIFIED]` |
| M4 | گواه نمایه با هر ذخیرۀ `tp_*` عوض می‌شود: کش `map.points` در سایتی با ویرایش‌های مکرر عملاً کارا نیست | `[INFERRED]` |
| M5 | بدون قلاب رابطه، نمایۀ استان/شهر می‌تواند کهنه باشد؛ آزمون `verify` اثرانگشت آن را نمی‌گیرد چون از همان زنجیره می‌سازد | `[INFERRED]` |
| M6 | سبک روشن در حالت عصر/شب (اسپرایت `light` برای هر دو) | `[VERIFIED]` |
