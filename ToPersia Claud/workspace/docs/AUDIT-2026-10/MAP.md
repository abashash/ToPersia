# MAP — ممیزی ۲د (بخش کدخوانی): زنجیرۀ نقشه

> ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ · فقط‌خواندنی (P5-D287). این ماشین `iran.pmtiles`، پایگاه‌داده، مرورگر و WP-CLI ندارد.
> مسیرها نسبت به `ToPersia Claud/workspace/plugin/topersia-render/` (مگر دیگر ذکر شود). برچسب‌ها: VERIFIED · INFERRED · ASSUMED · UNKNOWN.
> بخش‌هایی که امروز تکمیل نمی‌شوند (پایگاه‌داده‌ای/pmtiles): ۸ (پوشش `name:*` در `iran.pmtiles`)، ۹ (آزمون Range/206/CORS زنده)، ۱۰ (شمار نقطۀ نمایه) — در «در انتظار تأیید محلی» CURRENT.md.

## ۰ — خلاصه

| | |
|---|---|
| پشته | MapLibre GL JS **5.24.0** + PMTiles + سبک Protomaps (بستۀ `basemaps.js`) + افزونۀ RTL (`mapbox-gl-rtl-text`) — همه خودمیزبان در `assets/map/` |
| منبع کاشی | یک پرونده `uploads/tiles/iran.pmtiles`؛ در مخزن نیست (`.gitignore`) و روی این ماشین **نیست** |
| نشانگرها | REST `/map-points` (GeoJSON) از نمایۀ مشتق `topersia_geo` — **فقط محلی** |
| بنچمارک A (DECIMAL) / B (POINT) | **اجرا شده** (۱۲ اکتبر، محلی، `docs/GEO-BENCH-2F.md`)؛ انتخاب A |
| سه یافتۀ مهم | (۱) برچسب آب/دریا/جزیره/رود با قلم Italic که گلیف عربی/فارسی ندارد؛ (۲) `letter-spacing` روی برچسب‌های فارسی؛ (۳) سقف ثابت ۴۰۰ نشانگر (خلاف حکم «بی عدد ثابت») |

## ۱ — زنجیرۀ فعلی

```
[صفحه استان/شهر/…] ── topersia_g_map_attr()  (zones.php:2656-2681)
        │               data-tp-map-cfg = { name, base, tiles, attribution, zoom, lang, locale, points }
        ▼
[topersia-g-zones.js] ── load(): css+js(maplibre-gl, pmtiles, basemaps) ← base + 'maplibre-gl.js'… (js:2044-2072)
        │               ml.addProtocol('pmtiles') ; ml.setRTLTextPlugin(base+'mapbox-gl-rtl-text.js', lazy=true)
        ▼
[style()] ── glyphs: base+'fonts/{fontstack}/{range}.pbf' · sprite: base+'sprites/light'
        │       source protomaps: 'pmtiles://' + cfg.tiles · layers: basemaps.layers('protomaps', flavor, {lang})
        ▼
[markers] ── fetch(cfg.points) → GeoJSON cluster:true (radius 46, maxZoom 11) → popup با textContent
```

| حلقه | شاهد | برچسب |
|---|---|---|
| آداپتور نقشه با فیلتر `topersia_g_map_provider` | `includes/topersia-g-zones.php:2590-2635` (نام `maplibre-pmtiles`) | **VERIFIED** |
| تنظیم از «سرویس‌های خارجی» `map.tiles` (فعال/غیرفعال و مسیر) | `topersia-g-zones.php:2613-2617` · `includes/topersia-g-core.php:65-68` · `plugin/topersia-core/includes/topersia-services.php:59` (`'map.tiles'`، `pmtiles`، `enabled=true`) | **VERIFIED** |
| اگر پرونده نیست ← آرایۀ خالی، کارت SVG مرجع می‌ماند | `topersia-g-zones.php:2617-2626` (`is_file( $file )`) | **VERIFIED** |
| `zoom` آغازین به نوع موجودیت (کشور ۴ … محله ۱۳) | `topersia-g-zones.php:2640-2652` | **VERIFIED** |
| مقیاس رنگ‌ها: flavor `light` / `dark` از `data-tp-mode` (روز=روشن؛ عصر و شب=تیره) | `assets/topersia-g-zones.js:2078-2081` (`flavorOf`) | **VERIFIED** |
| ساخت `new ml.Map` با `locale` MapLibre | `topersia-g-zones.js:2250-2258` | **VERIFIED** |
| رشته‌های رابط نقشه به زبان صفحه (`Map.Title`، `NavigationControl.*`) | `topersia-g-zones.php:2683-2700` | **VERIFIED** |
| خطای بارگذاری ← `ready = null` تا تلاش دوباره | `topersia-g-zones.js:2072` | **VERIFIED** |

## ۲ — نسخۀ MapLibre و وابستگی‌ها

| کتابخانه | نسخه | شاهد | برچسب |
|---|---|---|---|
| MapLibre GL JS | **5.24.0** (سرآیند باندل: `…/blob/v5.24.0/LICENSE.txt`؛ رشتۀ `var i="5.24.0"`) | `assets/map/maplibre-gl.js:3,46` | **VERIFIED** |
| PMTiles (کتابخانۀ مرورگر) | شمارۀ نسخه در باندل کوچک‌شده نیست؛ مجوز BSD-3 | `assets/map/pmtiles.js` · `LICENSE-pmtiles.txt` | **UNKNOWN** — محلی: `grep -c "pmtiles" package.json` در منبع بسته یا `npm view pmtiles version` و مقایسۀ اندازۀ 20229 بایت |
| basemaps (Protomaps themes) | ۷۱ لایه، ۱۴ لایۀ symbol (اجرای واقعی `basemaps.js` با Node، پرچم `lang:'fa'`) | `assets/map/basemaps.js` + `LICENSE-basemaps.txt` | **VERIFIED** (اجرا) — شمارۀ نسخه UNKNOWN |
| افزونۀ RTL | `mapbox-gl-rtl-text.js` (WASM جاسازی‌شده، ۱۳۳ کیلوبایت)، مجوز BSD-3 «Mapbox 2026» | `assets/map/mapbox-gl-rtl-text.js` · `LICENSE-mapbox-gl-rtl-text.txt` | **VERIFIED** (وجود)؛ شمارۀ نسخه **UNKNOWN** |
| قلم‌ها | Noto Sans: `Regular` · `Medium` · `Italic` | `assets/map/fonts/*` | **VERIFIED** |
| اسپرایت | `sprites/light.{json,png}` و `@2x`؛ نسخۀ تیره **نیست** (کامنت کد) | `assets/map/sprites` · `topersia-g-zones.js:2090-2091` | **VERIFIED** |

درباره‌ی حکم ARCH-RECONCILIATION «MapLibre v6: تعویق، کار جدا» ← هم‌خوان: باندل ۵٫۲۴٫۰ است و `setRTLTextPlugin` را صدا می‌زند (`js:2062-2067`)؛ ماتریس RTL برای مهاجرت هنوز نیست — **VERIFIED**.

## ۳ — افزونۀ RTL

- بار می‌شود **تنبل** (`setRTLTextPlugin( url, true )`) فقط اگر `getRTLTextPluginStatus() === 'unavailable'` (`topersia-g-zones.js:2062-2067`) — **VERIFIED**. در `try { } catch ( e ) {}`: شکست بی‌صدا؛ نقشه بدون شکل‌دهی حروف می‌ماند (حروف جدا) — **INFERRED**.
- نشانی افزونه از `cfg.base` (همان پوشۀ `assets/map/`)، نه CDN — **VERIFIED**.
- راست‌به‌چپ بودن واقعی برچسب‌ها وابسته به: (الف) داشتن گلیف‌های اشکال نمایشی عربی (U+FB50–FDFF و FE70–FEFF) در قلم، (ب) خوانده‌شدن `name:fa`. حالت (الف): قلم `Regular` و `Medium` بازه‌های `64256-65279` را دارند (۱۰۴–۲۳۲ کیلوبایت هر بازه)؛ **`Italic` ندارد** (بخش ۶).

## ۴ — مسیر PMTiles

| | |
|---|---|
| مسیر پیش‌فرض | `{uploads}/tiles/iran.pmtiles`؛ قابل‌تغییر با `map.tiles.extra.path` | `topersia-g-zones.php:2613` · `topersia-g-core.php:65-68` — **VERIFIED** |
| نشانی عمومی | `set_url_scheme( uploads_baseurl . 'tiles/iran.pmtiles' )` (در پیکربندی `tiles`) | `topersia-g-zones.php:2621` — **VERIFIED**. نشانی از مسیر آپلود وردپرس می‌آید، نه از `/assets` (نگاشت `.htaccess` ریشه) |
| در git | نه: `.gitignore:42` `/geo/wp-content/uploads/tiles/iran.pmtiles` | **VERIFIED** |
| روی این ماشین | نیست (`find / -name "*.pmtiles"` تهی) | **VERIFIED** |
| وجودش روی محلی/زنده | **UNKNOWN** — محلی: `ls -l geo/wp-content/uploads/tiles/`؛ زنده: همان در `public_html` یا `curl -sI` نشانی بالا |
| Range / 206 / CORS / کش CDN | **UNKNOWN**: کد نگهبان ندارد؛ `.htaccess` ریشه (`.htaccess:1-77`) هیچ قاعدۀ `Accept-Ranges`/`Header`/`AddType` برای `.pmtiles` ندارد (**VERIFIED**)؛ رفتار پیش‌فرض LiteSpeed/Apache برای فایل ایستا Range را می‌پذیرد (**ASSUMED**) |
| پرچم مصرف | `data-tp-map-cfg.tiles` با `pmtiles://` + نشانی کامل (`topersia-g-zones.js:2095`) — **VERIFIED** |

## ۵ — فونت و اسپرایت

| | |
|---|---|
| قالب نشانی گلیف | `base + 'fonts/{fontstack}/{range}.pbf'` | `topersia-g-zones.js:2089` — **VERIFIED** |
| پشته‌های موجود | `Noto Sans Regular` · `Noto Sans Medium` · `Noto Sans Italic` (پوشۀ هر کدام با نام فاصله‌دار) | **VERIFIED** (فهرست پوشه) |
| پوشش بازه‌ها: `Regular`/`Medium` | `0-255 … 2048-2303 · 8192-8703 · 64256-65279` همه با اندازۀ عادی | **VERIFIED** (`ls -l`) |
| پوشش بازه‌ها: `Italic` | `1536-1791` · `1792-2047` · `2048-2303` → **۲۶ بایت** (فایل تهی)؛ `64512-64767` و `64768-65023` → **۲۸ بایت** (تهی)؛ `64256-64511` → ۵٫۵ کیلوبایت و `65024-65279` → ۲٫۵ کیلوبایت (بخشی) | **VERIFIED** (اندازۀ فایل) |
| اسپرایت | `sprites/light` (+`@2x`) برای هر دو flavor؛ آیکون‌ها سفید-روی-روشن | `topersia-g-zones.js:2090-2091` — **VERIFIED** |
| قلم Devanagari | سبک به «Noto Sans Devanagari Regular v1» ارجاع می‌دهد که **در بسته نیست** — فقط برای برچسب‌های اسکریپت دواناگری (خارج از ایران) | **VERIFIED** (جستجو در خروجی `layers()`) |

## ۶ — برچسب فارسی روی نقشه: دو ایراد واقعی

اجرای `basemaps.layers('protomaps', namedFlavor('light'), { lang: 'fa' })` با Node روی همان `assets/map/basemaps.js`:

| یافته | جزئیات | برچسب |
|---|---|---|
| **قلم Italic گلیف فارسی/عربی ندارد** | پنج لایۀ symbol از `Noto Sans Italic` استفاده می‌کنند: `water_waterway_label` (رود/آبراه) · `water_label_ocean` (دریا، خلیج، تنگه) · `earth_label_islands` (جزیره) · `water_label_lakes` (دریاچه) · `address_label` (شمارۀ ساختمان) | **VERIFIED** (خروجی اجرا) + اندازۀ بازه‌ها در بخش ۵ |
| پیامد | برچسب‌هایی مثل «خلیج فارس»، «دریای خزر»، «دریاچۀ ارومیه»، «کیش» ممکن است نمایش داده نشوند یا مربع/خالی دربیایند؛ گلیف‌ها از بازه‌های خالی نمی‌آیند و MapLibre به قلم دیگر بازنمی‌گردد (پشتۀ تک‌قلمی) | **INFERRED** (رفتار MapLibre؛ آزمون چشمی محلی لازم) |
| **`text-letter-spacing` روی برچسب‌ها** | `water_waterway_label=0.2`، `water_label_ocean=0.1`، `earth_label_islands=0.1`، `water_label_lakes=0.1`، `places_subplace=0.1` | **VERIFIED** (خروجی اجرا) |
| پیامد | `letter-spacing` روی متن فارسی ممنوع است (`CLAUDE.md`، قواعد کد) و در شکل‌دهی WASM اتصال حروف را می‌شکند | **VERIFIED** (قاعدۀ پروژه) + **INFERRED** (اثر بصری) |
| انتخاب پیکربندی برچسب `lang=fa` | `["coalesce", ["get","name:fa"], ["get","pgf:name"], ["get","name"]]` با شاخۀ جدا برای `script ≠ Arabic` که `name:en` را نشان می‌دهد | **VERIFIED** (خروجی اجرا) |
| پیامد | هر جا `name:fa` در کاشی نباشد، `name` (نام محلی OSM، معمولاً فارسی در ایران) نشان داده می‌شود؛ برای مکان‌های غیرایرانی با `script≠Arabic` انگلیسی | **VERIFIED** (قاعده) |

**دور زدن بدون دست بردن در بسته:** این ممیزی هیچ اصلاحی نمی‌کند؛ گزینه‌ها برای تصمیم (INFERRED): (الف) بازنویسی `text-font` لایه‌های Italic به `Noto Sans Regular` در `style()` پیش از `map.addLayer`، (ب) افزودن بازه‌های کامل Italic، (ج) حذف `letter-spacing` فقط برای `lang ∈ {fa, ar}`.

## ۷ — `/map-points` و نمایۀ مکانی (از کد)

| | |
|---|---|
| مسیر | `GET /wp-json/topersia/v1/map-points?context={post_id}` | `includes/topersia-g-mappoints.php:155-166` — **VERIFIED** |
| دسترسی | `permission_callback => '__return_true'` (دادۀ عمومی) | `:160` — **VERIFIED** |
| ورودی | فقط `context` (شناسۀ پست استان/شهر)؛ بدون `bbox`، `types`، `limit`، `index_version` | `:161-163` — **VERIFIED** |
| زمینه‌ها | `tp_province` ← شهر · جاذبه · طبیعت؛ `tp_city` ← جاذبه · محله · روستا؛ نوع دیگر ← نشانگر ندارد | `:38-47` — **VERIFIED** |
| خانواده‌ها | `city`، `attraction` (۵ نوع)، `nature` (۷ نوع)، `district`، `village` | `:24-36` — **VERIFIED** |
| خروجی | `FeatureCollection` از `Point`؛ ویژگی‌ها `id k n t u i` (شناسه، خانواده، نام، برچسب نوع، نشانی، تصویر) به **زبان صفحه** | `:98-116` — **VERIFIED** |
| `meta` در قرارداد | **ندارد** (بدون `index_version`، `count`، `truncated`) | `:116` — **VERIFIED** |
| فقط منتشرشده | `topersia_g_published()` برای هر سطر | `:92-96` — **VERIFIED** |
| سقف | **۴۰۰ ثابت**؛ حلقۀ خانواده‌ها به ترتیب `city → attraction → nature` پس از رسیدن به ۴۰۰ می‌شکند (`break 2`) ← در استان بزرگ خانوادۀ آخر ممکن است هیچ نشانگری نگیرد | `:80,82-90` — **VERIFIED** (کد) |
| کش | `topersia_cache_remember( 'map.points', [ctx, lang, topersia_geo_index_stamp()] )` با TTL ۱۵ دقیقه و `keep` یک ساعت؛ ابطال با تغییر `stamp` در هر نوشتن نمایه | `:139-147` · `topersia-geo-index.php:141-149` — **VERIFIED** |
| دروازه | `topersia_geo_index_enabled()` = `wp_get_environment_type() === 'local'` ← **روی غیرمحلی خروجی همیشه `FeatureCollection` تهی** و حتی نشانی `points` به پیکربندی نمی‌افتد | `topersia-geo-index.php:26-28` · `mappoints.php:51-55,130-132` — **VERIFIED** |
| ساختار نمایه | `topersia_geo`: `post_id` PK · `post_type` · `lat DECIMAL(8,6)` · `lng DECIMAL(9,6)` · `geom_kind` · `province_id` · `city_id` · `index_version` (SMALLINT) + نمایه‌های `ll`, `tll`, `prov`, `city` | `topersia-geo-index.php:63-76` — **VERIFIED** |
| فقط زبان پیش‌فرض | ترجمه‌ها مختصات را کپی می‌کنند ← ردیف جدا ندارند؛ شناسۀ نمایش (`wpml_object_id`) برای خروجی | `topersia-geo-index.php:85-95` · `mappoints.php:74-76` — **VERIFIED** |
| همگام‌سازی | `save_post` اولویت ۳۰ + حذف + تغییر وضعیت + بازسازی کامل با `wp topersia geo-index rebuild` (`TRUNCATE` سپس درج تک‌به‌تک) | `topersia-geo-index.php:284-310` ، `:151-170` — **VERIFIED** |
| مختصات | تابع مرکزی `topersia_geo_parse()`: دهدهی، DMS، JSON/GeoJSON، فهرست نقطه؛ مرکز ثقل برای شکل؛ کادر ایران ۲۵–۴۰٫۵ شمالی و ۴۴–۶۳٫۵ شرقی | `plugin/topersia-core/includes/topersia-geo.php:23-26,114-224` — **VERIFIED** |
| «مختصات جعلی از والد ممنوع» | رعایت می‌شود: نمایه فقط از `geo_coordinates` خود پست می‌سازد؛ بی مختصات ← ردیف نمی‌گیرد | `topersia-geo-index.php:103-125` — **VERIFIED** |
| کادر ایران بسیار گسترده | شامل مناطق همسایه‌ها (بخش‌هایی از عراق، ترکمنستان، آذربایجان…) ← نقطۀ بیرون از ایران ولی داخل کادر پذیرفته می‌شود | `topersia-geo.php:23-26` — **VERIFIED** (کد) + **INFERRED** (اثر) |
| مشتری | `cluster: true`، `clusterRadius: 46`، `clusterMaxZoom: 11`؛ پنجرۀ باز با `setDOMContent` + `textContent` (نه `innerHTML`) و `safeUrl` برای `href` | `assets/topersia-g-zones.js:2136,2189,2222,2237` — **VERIFIED** |
| اجرای گرانی | حلقه برای هر سطر `topersia_g_published([id])` + تصویر + نام + برچسب (چندین پرس‌وجو) — تا ۴۰۰ بار در نخستین بار (کش نشده) | `mappoints.php:92-116` — **INFERRED** (کلید کش سربار را پس از اولی می‌برد) |

> **نصب‌شده نه:** نسخۀ `geo/wp-content/plugins/topersia-render` (۴٫۵٫۰) فایل `topersia-g-mappoints.php` را ندارد (R44، SYSTEM.md بخش ۱۱). یعنی `/map-points` روی نصب‌شدۀ مخزن اصلاً ثبت نیست. **VERIFIED**.

## ۸ — پوشش `name:fa` · `name:ar` · `name:en` در `iran.pmtiles`

**UNKNOWN** — پرونده روی این ماشین نیست (بخش ۴) و پوشش از کد استنباط‌شدنی نیست. نمونه‌گیری شمارشی پیشنهادی (محلی؛ نه اجرا در این جلسه):

```bash
# ۱) سربرگ و فراداده
npx --yes pmtiles show geo/wp-content/uploads/tiles/iran.pmtiles
# ۲) شمارش ویژگی‌های لایۀ places در z4..z8 با @mapbox/vector-tile (اسکریپت یک‌بارمصرف بیرون از مخزن)
node -e '
const {PMTiles,FileSource}=require("pmtiles");const {VectorTile}=require("@mapbox/vector-tile");const Pbf=require("pbf");
(async()=>{const p=new PMTiles(new FileSource("geo/wp-content/uploads/tiles/iran.pmtiles"));
 const c={tot:0,fa:0,ar:0,en:0,name:0};
 for(const z of [4,5,6,7,8]){const n=1<<z;for(let x=Math.floor(n*(44+180)/360);x<=Math.floor(n*(63.5+180)/360);x++)
  for(let y=Math.floor(n*(1-Math.log(Math.tan(40.5*Math.PI/180)+1/Math.cos(40.5*Math.PI/180))/Math.PI)/2);y<=Math.floor(n*(1-Math.log(Math.tan(25*Math.PI/180)+1/Math.cos(25*Math.PI/180))/Math.PI)/2);y++){
   const t=await p.getZxy(z,x,y);if(!t)continue;const L=new VectorTile(new Pbf(new Uint8Array(t.data))).layers.places;if(!L)continue;
   for(let i=0;i<L.length;i++){const f=L.feature(i).properties;c.tot++;if(f["name:fa"])c.fa++;if(f["name:ar"])c.ar++;if(f["name:en"])c.en++;if(f.name)c.name++;}}}
 console.log(JSON.stringify(c));})()'
```

همین شمارش برای لایه‌های `water` و `earth` (جزیره) باید تکرار شود چون ایراد قلم Italic (بخش ۶) به آن‌ها می‌خورد. (قاعدۀ مدل در مخزن: نتیجه در `DATA.md`/ضمیمۀ همین سند ثبت شود.)

## ۹ — طرح بنچمارک A و B (و وضعیت اجرا)

ARCH-RECONCILIATION §۴ «بنچمارک A در برابر B با ۱۰۰ تا ۱۰۰٬۰۰۰ نقطه، در آغاز فاز ۳» و ۲د «طرح (اجرا نه)». **بنچمارک پیش‌تر اجرا شده**: `docs/GEO-BENCH-2F.md` + `scripts/geo-bench.php 200000` روی MariaDB 10.11.19 محلی، ۲۰۰٬۰۰۰ نقطۀ ساختگی، ۶۰ اجرا هر پرس‌وجو؛ نتیجه: **A (DECIMAL + نمایۀ ترکیبی) انتخاب شد** — برای پرس‌وجوی «زمینه» ۱٫۴–۱٫۶ برابر سریع‌تر؛ B فقط در «۲۰ نزدیک‌ترین» برنده است (۰٫۷۴ در برابر ۲٫۳ms). **VERIFIED** (سند وجود دارد، اعداد از خود سند نقل می‌شوند و در این جلسه بازتولید نشد).

شکاف آن اجرا با خواستۀ Brief v2:

| خواستۀ Brief v2 | آنچه اجرا شد | |
|---|---|---|
| ۱۰۰ … ۱۰۰٬۰۰۰ نقطه (مقیاس‌های میانی) | فقط ۲۰۰٬۰۰۰ | **INFERRED** از سند |
| پایان‌به‌پایان: payload GeoJSON، خوشه‌بندی مشتری، اندازۀ پاسخ | پرس‌وجوی پایگاه‌داده فقط | **INFERRED** |
| سناریوی «نزدیک من» با POST | فقط ۲۰ نزدیک‌ترین SQL | **INFERRED** |
| دادۀ واقعی ToPersia | ساختگی (وزن واقع‌گرایانه) | **VERIFIED** (سند) |
| تأیید روی MariaDB، نه MySQL | MariaDB 10.11.19 محلی | **VERIFIED** (سند) |
| موتور زنده | سنجیده نشد؛ زنده MariaDB 10.11 فرض شده | **ASSUMED** |

مدل B که در Brief برای ردیف‌های زیاد پیشنهاد شده بود با داده‌های آزمون فعلاً بی‌برتری است؛ تصمیم معمار (P5-D294) با این شاهد هم‌خوان است.

### قرارداد `/map-points` در برابر Brief v2

| بند Brief | حکم ARCH-RECONCILIATION | کد |
|---|---|---|
| `FeatureCollection` + `meta` | پذیرش | بی `meta` ← **ناهم‌خوان** |
| «اطراف من» با POST | پذیرش | هنوز نیست (فاز ۳) — جست‌وجوی `nearby|نزدیک من|اطراف من` در `plugin/` جز کامنت (`topersia-g-mappoints.php:9`) و یک TTL رزروشده (`nearby` = ۱۰ دقیقه در `plugin/topersia-core/includes/topersia-cache.php:244`) چیزی نمی‌دهد؛ **VERIFIED** |
| بی عدد ثابت برای شمار نشانگر | پذیرش | **ناهم‌خوان** — سقف ۴۰۰ (بخش ۷) |
| ستون‌های نمایه `entity_uid`، شناسه‌های محدوده، `index_version` | پذیرش | `entity_uid` نیست (فقط `post_id`)؛ محدوده فقط استان و شهر؛ `index_version` هست — **ناهم‌خوان جزئی** |
| خوشه‌بندی و بارگذاری بر پایۀ viewport | پذیرش | خوشه‌بندی مشتری هست؛ بارگذاری بر پایۀ viewport نه (یک بار برای کل زمینه) |
| برچسب با زنجیرۀ بازگشت از فیلدهای PMTiles | پذیرش | بخش ۶ — `coalesce(name:fa, pgf:name, name)` هست؛ پوشش UNKNOWN (بخش ۸) |
| حذف افزونۀ RTL | تعویق | هم‌خوان (بخش ۳) |
| آزمون Range/206/CORS/CDN | پذیرش (فاز ۱۰) | UNKNOWN (بخش ۴) |
| نشانی Neshan/Balad | رد ادعا | هم‌خوان: `deep-links` با `enabled=false` و الگوی خالی برای Neshan/Balad — `plugin/topersia-core/includes/topersia-services.php:63,96-101` (**VERIFIED**) |

## ۱۰ — UNKNOWN و فرمان محلی

| # | موضوع | فرمان |
|---|---|---|
| ۱ | وجود و اندازۀ `iran.pmtiles` محلی/زنده | `ls -l geo/wp-content/uploads/tiles/` · `npx pmtiles show …` |
| ۲ | پوشش `name:fa/ar/en` | اسکریپت بخش ۸ |
| ۳ | Range/206/CORS | `curl -sI -H "Range: bytes=0-16383" <نشانی کاشی>` (انتظار: `206`، `Content-Range`، `Accept-Ranges: bytes`)؛ سپس همین از مرورگر دیگر-مبدأ برای CORS |
| ۴ | نمایش واقعی برچسب «خلیج فارس»/«دریای خزر» | باز کردن صفحۀ کشور محلی در کروم + فایرفاکس؛ یا `php tests/acceptance-shots.js` و نگاه به عکس‌ها |
| ۵ | شمار ردیف نمایه و کیفیت مختصات | `wp topersia geo-index report` (پیش‌تر در `docs/GEO-QUALITY-2F.json`)؛ برای ۲د: `wp db query "SELECT post_type, COUNT(*) FROM {prefix}topersia_geo GROUP BY post_type"` |
| ۶ | نسخۀ PMTiles و basemaps و افزونۀ RTL | `npm view pmtiles version`؛ مقایسۀ هش پرونده با بستۀ npm |
| ۷ | عملکرد `/map-points` بدون کش | `time wp eval 'echo count(topersia_g_map_points_build(<شناسه استان>)["features"]);'` |
