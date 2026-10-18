# LOCAL-QUERIES.md — فرمان‌های محلی برای هر UNKNOWN

> فاز ۲د · جلسۀ ابری ۱. **این پرونده از روی ردیف‌های `[UNKNOWN]` شش‌فایل ممیزی خودکار ساخته شده** (شمارۀ خط = خط همان ردیف). هیچ‌کدام در ابر اجرا نشده است.
> اجرا فقط روی نسخۀ محلی (`topersia.local`) یا، جایی که نوشته «زنده»، روی هاست زنده با دسترسی کاربر. **خروجی را بی مقدار کلید/رمز/شماره/نام کاربر گزارش بده؛ فقط شمار و نام.**
> فرمان‌ها از ریشۀ مخزن (`C:\wamp64\www\topersia`) با WP-CLI تنظیم‌شده اجرا می‌شوند: `php wp-cli.phar --path="C:/wamp64/www/topersia/geo" --url=http://topersia.local …`


## SYSTEM.md

### LQ-SYSTEM-01 — PHP زنده
- مرجع: `SYSTEM.md:21` · محل اجرا: محلی/زنده
  - `php -v`
  - `wp eval 'echo PHP_VERSION," ",php_sapi_name();'`

### LQ-SYSTEM-02 — موتور/نسخۀ پایگاه‌داده زنده
- مرجع: `SYSTEM.md:23` · محل اجرا: محلی/زنده
  - `wp db query "SELECT VERSION(), @@innodb_version, @@sql_mode"`

### LQ-SYSTEM-03 — محلی در برابر زنده — جدول تفاوت
- مرجع: `SYSTEM.md:24` · محل اجرا: محلی/زنده
  - `wp core version --extra`

### LQ-SYSTEM-04 — مقدار محیط روی زنده
- مرجع: `SYSTEM.md:27` · محل اجرا: محلی/زنده
  - `wp eval 'echo wp_get_environment_type();'`

### LQ-SYSTEM-05 — فعال‌ها (active_plugins، پوستۀ فعال)
- مرجع: `SYSTEM.md:56` · محل اجرا: محلی
  - `wp plugin list --fields=name,status,version,update --format=csv`
  - `wp theme list --fields=name,status,version`

### LQ-SYSTEM-06 — به‌روزرسانی‌های موجود
- مرجع: `SYSTEM.md:57` · محل اجرا: محلی
  - `wp plugin list --update=available --fields=name,version,update_version`

### LQ-SYSTEM-07 — کدام نسخه/مسیر امروز بار شده
- مرجع: `SYSTEM.md:81` · محل اجرا: محلی
  - `wp eval 'print_r(ActionScheduler_Versions::instance()->get_sources()); echo ActionScheduler_Versions::instance()->latest_version(),"\n"; echo ActionScheduler::plugin_path("");'`

### LQ-SYSTEM-08 — داده‌ی صف
- مرجع: `SYSTEM.md:86` · محل اجرا: محلی
  - `wp action-scheduler action list --status=pending --group=topersia --format=count`
  - `wp action-scheduler action list --status=failed --format=count`

### LQ-SYSTEM-09 — فهرست زندۀ مسیرها (شامل وردپرس، Elementor، JetEngine، WPML)
- مرجع: `SYSTEM.md:100` · محل اجرا: محلی
  - `wp eval 'foreach(array_keys(rest_get_server()->get_routes()) as $r) echo $r,"\n";'`
  - `curl http://topersia.local/geo/wp-json/`

### LQ-SYSTEM-10 — فهرست زنده (شامل Elementor/WPML/JetEngine)
- مرجع: `SYSTEM.md:122` · محل اجرا: محلی
  - `wp eval 'global $wp_filter; foreach(array_keys($wp_filter) as $h) if (strpos($h,"wp_ajax_")===0) echo $h,"\n";' | sort`

### LQ-SYSTEM-11 — کرون‌های درون SourceGuardian
- مرجع: `SYSTEM.md:134` · محل اجرا: محلی
  - `wp cron event list --format=csv | grep -i abzar`

### LQ-SYSTEM-12 — وضعیت واقعی WP-Cron
- مرجع: `SYSTEM.md:136` · محل اجرا: محلی/زنده
  - `wp config get DISABLE_WP_CRON`
  - `wp cron event list --fields=hook,next_run_relative,recurrence`
  - `wp cron test`

### LQ-SYSTEM-13 — کرون سیستمی (cPanel)
- مرجع: `SYSTEM.md:137` · محل اجرا: زنده
  - `crontab -l`

### LQ-SYSTEM-14 — وجود، حجم و ردیف واقعی جدول‌ها (محلی/زنده)
- مرجع: `SYSTEM.md:155` · محل اجرا: محلی/زنده
  - `wp db query "SELECT table_name, table_rows, ROUND((data_length+index_length)/1048576,2) AS mb FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name REGEXP 'topersia|actionscheduler|jet_' ORDER BY (data_length+index_length) DESC"`

### LQ-SYSTEM-15 — حجم کل autoload و بزرگ‌ترین optionها
- مرجع: `SYSTEM.md:178` · محل اجرا: محلی/زنده
  - `wp db query "SELECT option_name, LENGTH(option_value) b FROM $(wp db prefix)options WHERE autoload IN ('yes','on','auto','auto-on') ORDER BY b DESC LIMIT 30"`
  - `wp db query "SELECT COUNT(*), SUM(LENGTH(option_value)) FROM $(wp db prefix)options WHERE autoload IN ('yes','on','auto','auto-on')"`

### LQ-SYSTEM-16 — شمار ترنزینت‌ها و سایز
- مرجع: `SYSTEM.md:179` · محل اجرا: محلی/زنده
  - `wp db query "SELECT COUNT(*) FROM $(wp db prefix)options WHERE option_name LIKE '\_transient\_%'"`

### LQ-SYSTEM-17 — درآپین روی زنده / Redis / Memcached / LSCache
- مرجع: `SYSTEM.md:189` · محل اجرا: زنده
  - `wp cache type`
  - `wp plugin list | grep -i "cache|litespeed"`
  - `ls -l wp-content/object-cache.php`

### LQ-SYSTEM-18 — قابلیت‌های ماژول‌ها (نام‌ها)
- مرجع: `SYSTEM.md:212` · محل اجرا: محلی
  - `php -m | grep -i sourceguardian`

### LQ-SYSTEM-19 — دیدن رفتار واقعی
- مرجع: `SYSTEM.md:213` · محل اجرا: محلی
  - `php -m | grep -i sourceguardian`
  - `wp cron event list | grep -i abzar`
  - `mitmproxy`


## MAP.md

### LQ-MAP-01 — PMTiles (پروتکل)
- مرجع: `MAP.md:46` · محل اجرا: محلی
  - `sha256sum plugin/topersia-render/assets/map/pmtiles.js`
  - `dist/index.js`
  - `pmtiles`
  - `npm pack pmtiles@<نسخه>`
  - `tar xzf`
  - `sha256sum package/dist/index.js`

### LQ-MAP-02 — وجود فایل iran.pmtiles و اندازه/هدر/حداکثر زوم/کران
- مرجع: `MAP.md:63` · محل اجرا: محلی
  - `ls -l geo/wp-content/uploads/tiles/iran.pmtiles`
  - `pmtiles show geo/wp-content/uploads/tiles/iran.pmtiles`

### LQ-MAP-03 — لایه‌ها و کلیدهای برچسب داخل کاشی
- مرجع: `MAP.md:64` · محل اجرا: محلی
  - `pmtiles show geo/wp-content/uploads/tiles/iran.pmtiles --metadata`
  - `vector_layers`

### LQ-MAP-04 — پاسخ هدر کاشی
- مرجع: `MAP.md:66` · محل اجرا: محلی
  - `curl -sI -H "Range: bytes=0-16383" "$(wp eval 'echo wp_upload_dir()["baseurl"];')/tiles/iran.pmtiles"`
  - `206`
  - `Content-Range`
  - `Accept-Ranges: bytes`

### LQ-MAP-05 — پوشش name:fa · name:ar · name:en در iran.pmtiles
- مرجع: `MAP.md:77` · محل اجرا: محلی
  - `LQ-SCRIPT-NAMES`
  - `LOCAL-QUERIES.md`

### LQ-MAP-06 — نمایه در پایگاه: شمار ردیف، توزیع نوع، پوشش province_id/city_id
- مرجع: `MAP.md:105` · محل اجرا: محلی
  - `wp topersia geo-index report`
  - `wp db query "SELECT post_type, COUNT(*) n, SUM(province_id>0) p, SUM(city_id>0) c FROM $(wp db prefix)topersia_geo GROUP BY 1"`

### LQ-MAP-07 — موتور و نسخۀ پایگاه‌داده (MariaDB، نه MySQL)
- مرجع: `MAP.md:106` · محل اجرا: محلی/زنده
  - `wp db query "SELECT VERSION()"`

### LQ-MAP-08 — پاسخ واقعی /map-points برای یک استان
- مرجع: `MAP.md:107` · محل اجرا: محلی
  - `curl -s "http://topersia.local/geo/wp-json/topersia/v1/map-points?context=<ID_استان>" | head -c 600`

### LQ-MAP-09 — وضعیت مختصات امروز
- مرجع: `MAP.md:120` · محل اجرا: محلی
  - `wp topersia geo-index report`


## AI.md

### LQ-AI-01 — 2.4 — مقدار واقعی tpas_settings.position و .services روی محلی/زنده
- مرجع: `AI.md:30` · محل اجرا: محلی
  - `wp eval '$o=(array)get_option("tpas_settings"); echo json_encode(array_intersect_key($o,array_flip(["position","services","internal_enabled","internal_engine","panel_position"])));'`

### LQ-AI-02 — تعداد ردیف‌ها، توزیع provider/model/source، حجم content
- مرجع: `AI.md:49` · محل اجرا: محلی
  - `wp db query "SELECT source, provider, model, language, type, COUNT(*) n, SUM(LENGTH(content)) bytes FROM $(wp db prefix)topersia_ai_summaries GROUP BY 1,2,3,4,5"`

### LQ-AI-03 — وجود جدول روی زنده (نسخۀ tpas_db_version)
- مرجع: `AI.md:50` · محل اجرا: محلی
  - `wp option get tpas_db_version`
  - `wp db query "SHOW TABLES LIKE '%topersia_ai%'"`

### LQ-AI-04 — ارائه‌دهنده و مدل واقعاً انتخاب‌شده روی محلی/زنده
- مرجع: `AI.md:79` · محل اجرا: محلی
  - `wp eval 'foreach((array)(get_option("tpas_settings")["internal_engines"]??[]) as $e){echo $e["id"]," ",$e["provider"]," ",$e["model"],"\n";}'`

### LQ-AI-05 — دسترسی سرور زنده به API گوگل/OpenAI از IP ایران
- مرجع: `AI.md:80` · محل اجرا: محلی/زنده
  - `curl -sS -o /dev/null -w "%{http_code}\n" https://generativelanguage.googleapis.com/`

### LQ-AI-06 — حجم/رشد فعلی topersia_ai_events و tpas_stats
- مرجع: `AI.md:131` · محل اجرا: محلی
  - `wp db query "SELECT event_type, provider, COUNT(*) FROM $(wp db prefix)topersia_ai_events GROUP BY 1,2"`
  - `wp option get tpas_stats --format=json`

### LQ-AI-07 — نرخ برخورد کش ai.text و هزینۀ ماه
- مرجع: `AI.md:132` · محل اجرا: محلی
  - `wp option get topersia_services_usage --format=json`


## USER.md

### LQ-USER-01 — وقوع واقعی آن روی داده
- مرجع: `USER.md:40` · محل اجرا: محلی
  - `wp user list --field=display_name | grep -c '^tp_[0-9]'`
  - `wp db query "SELECT COUNT(*) FROM $(wp db prefix)jet_reviews r JOIN $(wp db prefix)users u ON u.ID=CAST(r.author AS UNSIGNED) WHERE r.approved>0 AND u.display_name=u.user_login"`

### LQ-USER-02 — مسدود کردن شمارش کاربران
- مرجع: `USER.md:41` · محل اجرا: محلی
  - `curl -sI "http://topersia.local/author/tp_test_430/" | head -3`

### LQ-USER-03 — ایمیل سامانه
- مرجع: `USER.md:43` · محل اجرا: محلی/زنده
  - `php -r 'var_dump(ini_get("disable_functions"));'`

### LQ-USER-04 — شمار کاربران، شمار ردیف‌های ذخیره/دنبال، حجم جدول OTP
- مرجع: `USER.md:61` · محل اجرا: محلی
  - `wp user list --role=subscriber --format=count`
  - `wp db query "SELECT COUNT(*) FROM $(wp db prefix)topersia_saved"`
  - `wp db query "SELECT COUNT(*) FROM $(wp db prefix)topersia_otp_attempts"`

### LQ-USER-05 — شمار کاربرانی که _topersia_followed دارند و میانگین طول
- مرجع: `USER.md:62` · محل اجرا: محلی
  - `wp db query "SELECT COUNT(*) FROM $(wp db prefix)usermeta WHERE meta_key='_topersia_followed'"`

### LQ-USER-06 — میزان پر بودن و مقادیر متنوع
- مرجع: `USER.md:73` · محل اجرا: محلی
  - `wp db query "SELECT meta_value, COUNT(*) c FROM $(wp db prefix)usermeta WHERE meta_key='_topersia_country' GROUP BY 1 ORDER BY c DESC LIMIT 30"`

### LQ-USER-07 — U5 — PHP 8.0
- مرجع: `USER.md:117` · محل اجرا: محلی/زنده
  - `php -v`
  - `wp eval 'echo PHP_VERSION;'`


## EVENTS.md

### LQ-EVENTS-01 — وجود نوع tpc_news و میزان خبر در پایگاه
- مرجع: `EVENTS.md:34` · محل اجرا: محلی
  - `wp post-type list --fields=name,count --format=csv | grep tpc`
  - `wp post list --post_type=tpc_news --format=count`

### LQ-EVENTS-02 — داده‌ی واقعی امروز
- مرجع: `EVENTS.md:79` · محل اجرا: محلی
  - `wp eval 'global $wpdb; foreach($wpdb->get_results("SELECT rel_id, COUNT(*) c FROM {$wpdb->prefix}jet_rel_default GROUP BY rel_id") as $r) echo $r->rel_id,"=",$r->c,"\n";'`

### LQ-EVENTS-03 — وابستگی صف
- مرجع: `EVENTS.md:96` · محل اجرا: محلی/زنده
  - `wp config get DISABLE_WP_CRON`


## FEATURE-MAP.md

### LQ-FEATURE-01 — 14 — دسترسی سرور زنده به API گوگل (محدودیت IP)
- مرجع: `FEATURE-MAP.md:30` · محل اجرا: زنده
  - `curl -sS -o /dev/null -w "%{http_code}\n" https://generativelanguage.googleapis.com/`

### LQ-FEATURE-02 — 39 — Redis (اختیاری)
- مرجع: `FEATURE-MAP.md:55` · محل اجرا: محلی/زنده
  - `wp cache type`

### LQ-FEATURE-03 — 63 — Jev
- مرجع: `FEATURE-MAP.md:79` · محل اجرا: محلی
  - بی فرمان (پرسش از کاربر / تصمیم)

### LQ-FEATURE-04 — 110 — برچسب سه‌زبانه از PMTiles
- مرجع: `FEATURE-MAP.md:140` · محل اجرا: محلی
  - `LQ-SCRIPT-NAMES`
  - `LOCAL-QUERIES.md`

### LQ-FEATURE-05 — 113 — آزمون Range/206/CORS/CDN برای PMTiles
- مرجع: `FEATURE-MAP.md:143` · محل اجرا: محلی
  - `curl -sI -H "Range: bytes=0-16383" <نشانی کاشی>`

### LQ-FEATURE-06 — 120 — /map-points روی زنده
- مرجع: `FEATURE-MAP.md:150` · محل اجرا: زنده
  - `wp eval 'echo wp_get_environment_type();'`


## RISKS.md

### LQ-RISKS-01 — R13 — صف انباشته
- مرجع: `RISKS.md:28` · محل اجرا: محلی/زنده
  - `wp action-scheduler action list --status=pending --group=topersia --format=count`
  - `wp config get DISABLE_WP_CRON`

### LQ-RISKS-02 — R22 — رانش WPML
- مرجع: `RISKS.md:37` · محل اجرا: محلی/زنده
  - `wp option get wpml-tm-custom-xml | head -c 200`

### LQ-RISKS-03 — R26 — زمان‌بُر بودن در هاست اشتراکی
- مرجع: `RISKS.md:41` · محل اجرا: زنده
  - `php -r 'echo ini_get("max_execution_time");'`
  - `curl -s -o /dev/null -w "%{time_total}\n" -X POST <admin-ajax>`

### LQ-RISKS-04 — R37 — شمارۀ موبایل به‌عنوان display_name عمومی در بلوک نظرها (کاربر OTP بی نام)
- مرجع: `RISKS.md:59` · محل اجرا: محلی
  - `wp user list --field=display_name | grep -c '^tp_[0-9]'`

### LQ-RISKS-05 — R39 — افزونه‌های تجاری از توزیع‌کنندۀ واسط (پوشۀ abzarwp، خطای React #200 در پیشخوان): لودر رمزگذاری‌شدۀ SourceGuardian در ۱۱ افزونه و پوستۀ
- مرجع: `RISKS.md:61` · محل اجرا: محلی
  - `wp plugin deactivate abzarwp-auto-update`

### LQ-RISKS-06 — R41 — نام کاربری مدیر که در کد/مخزن به‌صورت رشتۀ عمومی آمده: «هست» (یافتۀ امنیتی ۲ج)
- مرجع: `RISKS.md:63` · محل اجرا: محلی
  - `git grep -nF -c -- "$TP_GUARD_ADMIN_USER"`

### LQ-RISKS-07 — R45 — کش و شمارنده‌ها در ترنزینت (ردیف wp_options) بی کش شیء پایدار؛ فضای کلید هوا (گرد ۰٫۰۱ درجه) بزرگ
- مرجع: `RISKS.md:67` · محل اجرا: محلی
  - `wp db query "SELECT COUNT(*) FROM $(wp db prefix)options WHERE option_name LIKE '\_transient\_%'"`


## اسکریپت پیشنهادی `LQ-SCRIPT-NAMES` — پوشش `name:fa · name:ar · name:en` در `iran.pmtiles` (اجرا نشده)

> فقط شمار ویژگی‌ها با/بی هر کلید؛ نام خاص چاپ نمی‌شود. پیش‌نیاز: Node ≥ 18 و `npm i pmtiles @mapbox/vector-tile pbf` در پوشۀ موقت بیرون از مخزن. کاشی‌ها نمونه‌ای از مرکز چند شهر بزرگ در سه زوم‌اند (مختصات عمومی). اگر کاشی gzip باشد، `PMTiles.getZxy` خودش بازش می‌کند (Node ≥ 18 دارای `DecompressionStream` است).

```js
// node lq-script-names.js geo/wp-content/uploads/tiles/iran.pmtiles
const fs = require('fs');
const { PMTiles } = require('pmtiles');
const { VectorTile } = require('@mapbox/vector-tile');
const Protobuf = require('pbf').default || require('pbf');

class FileSrc {                       // منبع فایل برای Node
  constructor(p) { this.p = p; this.fd = fs.openSync(p, 'r'); }
  getKey() { return this.p; }
  async getBytes(offset, length) {
    const buf = Buffer.alloc(length);
    fs.readSync(this.fd, buf, 0, length, offset);
    return { data: buf.buffer.slice(buf.byteOffset, buf.byteOffset + length) };
  }
}
const pts = [[51.39,35.69],[51.67,32.65],[52.53,29.61],[59.6,36.3],[46.29,38.08],[48.67,31.32],[50.84,34.64],[57.07,30.28]]; // lon,lat
const zoomOf = [6, 8, 10];
const tile = (lon, lat, z) => { const n = 2 ** z, r = lat * Math.PI / 180;
  return [Math.floor((lon + 180) / 360 * n), Math.floor((1 - Math.log(Math.tan(r) + 1 / Math.cos(r)) / Math.PI) / 2 * n)]; };
(async () => {
  const pm = new PMTiles(new FileSrc(process.argv[2]));
  const stat = {};
  for (const z of zoomOf) for (const [lon, lat] of pts) {
    const [x, y] = tile(lon, lat, z);
    const t = await pm.getZxy(z, x, y); if (!t) continue;
    const vt = new VectorTile(new Protobuf(new Uint8Array(t.data)));
    for (const name of ['places', 'pois', 'roads', 'water', 'natural', 'landuse']) {
      const L = vt.layers[name]; if (!L) continue;
      const s = (stat[name] ||= { features: 0, name: 0, fa: 0, ar: 0, en: 0 });
      for (let i = 0; i < L.length; i++) { const p = L.feature(i).properties; s.features++;
        if (p.name) s.name++; if (p['name:fa']) s.fa++; if (p['name:ar']) s.ar++; if (p['name:en']) s.en++; }
    }
  }
  console.table(stat);
})();
```

---

## PERF.md §۸ · SCHEMA-PARITY.md §۶ — اجرا شد ۱۸ اکتبر ۲۰۲۶ (فقط خواندنی، محلی)

اجراکننده‌ها: `scripts/local/tp-lq-perf-sch.php` (پایگاه‌داده) · `tests/lq-http.js` (مرورگر) · عنصر LCP از `tests/lighthouse.js` (A0). نتیجهٔ هر ردیف، همان لحظه.

| شناسه | پرسش | نتیجه |
|---|---|---|
| LQ-PERF-01 | فهرست link/script استان و شهر | هر دو صفحه ۳۲ شیوه‌نامه و ۴۲ اسکریپت با src. مسدودکنندهٔ بی‌مصرف برای قالب ما: `jet-engine/frontend.css` · `jet-reviews.css` · `jet-reviews/photoswipe.css` · `elementor/frontend.min.css` (لازم) · jQuery و migrate در head؛ در پاورقی: `vue.min.js` · `photoswipe.min.js` · `jet-reviews-frontend.js` · `api-fetch` و وابستگی‌ها · Elementor Pro (frontend + elements-handlers) |
| LQ-PERF-02 | عنصر LCP | در گزارش A0 همین دسته (`tests/lighthouse.js` اکنون «عنصر LCP» چاپ می‌کند) |
| LQ-PERF-03 | آزمایهٔ `e_font_icon_svg` | `default` (یعنی تنظیم‌نشده) |
| LQ-PERF-04 | وزن‌های Peyda | کلیدها پرشده: `font_w300 · w400 · w500 · w600 · w700 · w800`، `font_name`، `font_variable`، `font_stack`؛ `font_woff2` خالی |
| LQ-PERF-05 | CSS المنتور | ۲ پرونده، ۱٬۳۰۹ بایت (`post-68.css` ۱٬۲۳۱ بایت) |
| LQ-PERF-06 | جای کارت نقشه در ۳۹۰ | استان: top=۱۳٬۱۱۹px · شهر: top=۳٬۶۶۸px، innerHeight=۸۴۴ ← هر دو بیرون از ۲۰۰px؛ MapLibre در بار نخست سوار نمی‌شود |
| LQ-PERF-07 | اندازهٔ DOM | استان ۱٬۷۲۳ عنصر · شهر ۸۹۸ (دسکتاپ و موبایل یکی) |
| LQ-PERF-08 | پوسته و گزینه‌های وودمارت | Woodmart 8.5.7 (stylesheet=woodmart)؛ `font_awesome_css='not_use'` · `lazy_loading=false` · `styles_always_use=[]`؛ کلید `combined_*` نیست (CSS/JS ترکیبی خاموش) |
| LQ-SCH-01 | نویسهٔ عربی‌خط در JSON-LD استان | fa ۱٬۳۳۸ از ۶٬۵۵۳ · en **۰** از ۳٬۴۴۴ · ar ۱۵۲ از ۳٬۴۰۷ (در ar عربیِ واقعی است — تفکیک فارسی/عربی در دستهٔ ۸) |
| LQ-SCH-02 | hreflang و canonical | هیچ‌کدام در head هیچ زبانی (`link[rel=alternate][hreflang]` و `link[rel=canonical]` صفر؛ سایت محلی noindex است و Rank Math بی‌خروجی) |
| LQ-SCH-03 | منتشرشده بی `entity_uid` | ۰ از ۲۱۹ پست `tp_` |
| LQ-SCH-04 | پوشش `external_reference_uri` | ۴۲ از ۲۱۹ پر؛ ویکی‌داده ۳۶ |
| LQ-SCH-05 | `population` / `area_km2` | population: city=۱۷ · country=۳ · province=۳۳ · village=۱ — area_km2: city=۱۲ · country=۳ · province=۳۳ · water_body=۱ (غیر کشور/شهر: روستا و پهنهٔ آبی هر کدام یک) |
| LQ-SCH-06 | دارای هر دو (meta_description و خلاصه‌ساز) | ۵۳ دارای meta_description؛ ۳ دارای هر دو |
