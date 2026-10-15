# نوبت جاری — رلهٔ محلی ⇄ ابری

> «چوب امدادی» میان Claude Code محلی و جلسهٔ ابری (OPS-D003). **هر طرف فقط بخش خودش را ویرایش می‌کند** تا ادغام squash بی‌تداخل بماند. جدول lane را فقط طرف محلی (هنگام برنامه‌ریزی) عوض می‌کند.
> عامل محلی بخش محلی را در **هر نقطهٔ عطف** به‌روز نگه می‌دارد، نه فقط هنگام تحویل — اگر سهمیه ناگهان تمام شود، کاربر بی هوش مصنوعی تحویل می‌دهد و جلسهٔ ابری از «قدم بعدی» همین بخش ادامه می‌دهد.
> تاریخچهٔ تحویل‌ها: `HISTORY.md` · گزارش گیت‌ها: `GATES.md` · راهنما: `RELAY.md`.

## مسیرها (lane) و مالکیت پرونده‌ها

| طرف | مسیرهای در مالکیت | توضیح |
|---|---|---|
| ابری B | `docs/AUDIT-2026-10/` | ممیزی ۲د — تمام شد؛ فعلاً جلسهٔ فعال نیست |
| ابری A / C (تمام‌شده) | `plugin/topersia-user-core/` · `tests/assert-uc.php` | امنیت و سخت‌سازی خلاصه‌ساز — ادغام شد (۱٫۱۱٫۰ · ۱٫۲٫۳)؛ فعلاً جلسهٔ فعال نیست |
| ابری E (تمام‌شده) | `plugin/topersia-ai-summarizer/` | خلاصه‌ساز ۱٫۳٫۰ — ادغام شد؛ فعلاً جلسهٔ فعال نیست |
| ابری D (تمام‌شده) | `plugin/topersia-schema/` | رویداد با تاریخ واقعی — ادغام شد (۱٫۳٫۰)؛ فعلاً جلسهٔ فعال نیست |
| محلی | `plugin/topersia-render` · `plugin/topersia-core` · `tests` (جز `tests/assert-uc.php`) · `scripts` (جز `scripts/relay`) | جابه‌جایی باکس ToPersia AI در پنل چپ فقط در `topersia-render`؛ بخش محلی ۲د (LOCAL-QUERIES · DATA.md · inventory.json) شروع نشده؛ شاخهٔ `claude/tender-volta-apf3de` ادغام نمی‌شود |

> دو طرف هم‌زمان روی یک پرونده کار نمی‌کنند. `from-cloud` هم‌پوشانی را گزارش می‌کند.

---

## بخش محلی — فقط عامل محلی

### وضعیت و انجام‌شده

- ۱۳ اکتبر: دستهٔ ۱ پنل چپ `149c79e2` (D347/D348) · OPS-D011 `65f1b1a3` · ادغام ابری: ممیزی `e1ca63e0`، امنیت/خلاصه‌ساز (cherry-pick `1c889de4`…`a77d2053` + آزمون `7ded731a`)، اسکیمای رویداد `e91eb2bb` · mirror نصب‌شده `d8710f38` · آزمون‌های محلی A/D `3919405e`.
- P5-D358 `3076dee2` (+ mirror `9205b1d0`) — AI به جای پیشین؛ assert 506/0 · panel-order 63/0 · user-flows 41/0.
- دستهٔ ۲ (map) `079616a7` (+ mirror `c0962b39`): مسیریابی D350 · مرز D351 (کد آماده؛ دادهٔ OSM بی VPN از ایران نیامد — `match`/`build` منتظر اتصال) · pois/Italic D352؛ assert 514/0 · map-2j 27/0 · گیت‌ها سبز.
- دستهٔ ۳ (map) `701082c7` (+ mirror `c9a40467`): نقشهٔ آنلاین اول (OpenFreeMap) با پشتیبان آفلاین فوری، سبک‌ها، tp-tiles-update، sprite تیره D353؛ assert 522/0 · map-3 کروم ۱۸/۰ و فایرفاکس ۱۸/۰ · گیت‌ها سبز. **بی VPN ممکن نشد:** برش تازهٔ Protomaps (build.protomaps.com timeout) و ضبط Lighthouse (fonts.googleapis.com) — فایل PMTiles فعلی می‌ماند؛ «دریای خزر» تا برش با bbox ۴۲٫۵ دیده نمی‌شود.
- دستهٔ ۴ (i18n) `47bd6444` (+ mirror `1f029166`): ایران en/ar، شمارندهٔ هم‌ارزی، زنجیرهٔ مکانی /en/ و /ar/ (D349)؛ assert 526/0 · lang-equiv-local 9/0.
- «پایان»: behaviour کامل ۳۷۹ ✅ و ۱ ❌ آزمون-ساز (نقطهٔ زیر انتساب بلندتر آنلاین؛ اصلاح آزمون ← map ۱۴/۰)؛ acceptance ۰ ناهمخوان (۵۱۹ جفت × سه اجرا)؛ alltypes --all-php ۸٫۰ تا ۸٫۵ بی مشکل؛ PHPCompatibilityWP نصب نیست (گزارش)؛ HANDOVER فاز ۱۰ نوشته شد.
- ممنوع: abzarwp (هیچ کاری) · ادغام `claude/tender-volta-apf3de` (تکراری؛ فقط cherry-pick ۵ commit انجام شد) · بخش محلی ۲د.

### قدم بعدی (دقیق و اجراشدنی)

۱۵ اکتبر — دستهٔ نقشه و مرز انجام شد (P5-D363 · P5-D364):
- مرز: `scripts/tp-boundaries.php` بازنویسی شد (fetch ← match آفلاین ← build دسته‌ای)؛ ۲۹ استان auto، ۲ استان suggested (البرز، خراسان شمالی — برچسب ISO در OSM با فهرست رسمی نمی‌خواند)، ۴۳ شهر auto، ۷۱ شهر none. **منتظر کاربر:** تأیید دو استان suggested (`confirmed` در `reference/osm-boundary-map.csv`)؛ ۲۰ شهر none مهم‌تر (سرخس، سلطانیه، …) — چرا هیچ نامزدی نداشتند (نام دیگر در OSM؟).
- خلیج فارس: override در سبک نقشه (D364)؛ گزارش جزیره‌ها در سند. تصمیم: نام عربی تنب بزرگ در داده نیست.
- بعدی: ادغام ابری F (P5-D361، برگزارکننده)؛ سپس دستهٔ ۴ (مورد منوی مقاصد).

### قیدها و تصمیم‌های مرتبط

- P5-D347…D353 فقط (متن پرامت)؛ کلید در کد/مخزن ممنوع؛ ارسال به زنده ممنوع؛ شاخه: `main`.

---

## بخش ابری — فقط جلسهٔ ابری (دستور را `to-cloud` می‌نویسد)

### دستور این جلسهٔ ابری

<!-- relay:task -->
(خالی) — ادامهٔ «قدم بعدی» بخش محلی.
<!-- /relay:task -->

### انجام‌شده

- **ابری G — دو ممیزی فقط‌خواندنی (۱۵ اکتبر ۲۰۲۶، مبنا `main` `47015840`).** هیچ کد، تنظیم، داده یا آزمونی عوض نشد؛ فقط دو سند تازه:
  - `docs/AUDIT-2026-10/PERF.md` — طرح اجرایی فاز ۹ (P5-D263): فهرست CSS/JS صفحهٔ استان و شهر از کد ثبت (ما + المنتور، وودمارت، جت‌انجین، جت‌ریویوز، WPML، Rank Math) با اندازهٔ روی دیسک، مسدودکننده‌بودن و مصرف قالب؛ مسیر تصویر LCP؛ قلم Peyda (۹ وزن روی دیسک، بی preload، swap، ۶۵۲ نقطه‌کد)؛ MapLibre/PMTiles (تنبل با IntersectionObserver، ~۳۳۷KB gzip)؛ DOM مرجع ۱٬۳۷۷ عنصر؛ جدول اقدام در سه دسته (کار ما A0–A9 · تنظیم افزونه B1–B6 · ناممکن بی تغییر پایه C1–C4).
  - `docs/AUDIT-2026-10/SCHEMA-PARITY.md` — ۴۱ نوع `topersia_schema_map()`: جدول ویژگی × دیدنی/ناپیدا، هم‌خوانی چندزبانه، واجد بودن نتیجهٔ غنی و استناد AI، اقدام S1–S13. P5-D361 تأیید شد (organizer و official_event_url شاخهٔ `clever-mendel-dnee8k` در render `main` نیستند).
  - یافته‌های مهم: (۱) JSON-LD دروازهٔ زبان P5-D278 را ندارد ← متن فارسی ترجمه‌نشده در /en/ و /ar/ منتشر می‌شود ولی روی صفحه نیست؛ (۲) `WebPage.abstract` از `ai_summary` و باکس دیدنی از خروجی خلاصه‌ساز؛ (۳) `venue_name` ناپیدا؛ (۴) JetReviews (Vue + PhotoSwipe) و CSS جت‌انجین روی هر صفحهٔ المنتوری بی‌مصرف؛ (۵) مبنای Lighthouse پیش از P5-D359 است و با تصویرهای 403 ضبط شده — باید دوباره ضبط شود.
  - گیت‌های ابری (`gates --record`) سبز؛ نتیجه در `GATES.md`.

- **ممیزی ۲د — تطبیق دو دور (ابری B، فقط سند، ۱۲ اکتبر ۲۰۲۶).** دو ممیزی مستقل (الف `main` `9ae7c73e` · ب `claude/tender-volta-apf3de` `c3488880`، فقط خوانده شد) در کد `main` دوباره سنجیده شد؛ هیچ کدی عوض نشد.
  - خروجی تازه: `docs/AUDIT-2026-10/RECONCILE.md` — ۲۴ یافتۀ «هر دو»، ۱۳ «فقط الف» تأییدشده + ۲ رد، ۱۹ «فقط ب» تأییدشده + ۲ رد، ۱۶ ناسازگاری با حکم، جدول «رد شده» (۶)، ۲۰ اقدام با مالک و اثر SEO · GEO · AEO · چندزبانه.
  - افزوده‌های «دور دوم» به پرونده‌های `main`: `RISKS.md` (R51…R66 + اصلاح R14 · R41 · R44 · R49) · `MAP.md` §۹ · `EVENTS.md` · `AI.md` §۱۱ · `SYSTEM.md`.
  - قطعی‌شده: (۱) قلم `Noto Sans Italic` در ۵ لایه (`water_waterway_label` · `water_label_ocean` · `water_label_lakes` · `earth_label_islands` · `address_label`) بازه‌های عربی ۲۶–۲۸ بایتی (تهی) دارد؛ اصلاح یک‌خطی `italic: 'Noto Sans Regular'` در flavor با Node آزموده شد (پیشنهاد برای کدکس). (۲) `event_start_date`/`event_end_date` نوع `date`، رشتۀ `Y-m-d`، فقط E1·E3·E5؛ P5-D214 در نام و فرض کهنه است. (۳) کلید AI در `tpas_settings` متن ساده، autoload (`auto`) و در صافی عمومی `tpas_api_payload`.
  - یافتۀ تازۀ دور دوم: پیشوند پایگاه‌داده در ۶ پرونده (از جمله لاگ `.txt` در `reference/`)؛ R44 برای core/render کهنه است (`b1ec34bb`).

- **ممیزی ۲د — بخش کدخوانی (فقط‌خواندنی، ۱۲ اکتبر ۲۰۲۶).** پوشۀ تازۀ `docs/AUDIT-2026-10/` با ۸ پرونده؛ هیچ کد، پایگاه‌داده یا تنظیمی عوض نشد:
  `AI.md` · `USER.md` · `EVENTS.md` · `FEATURE-MAP.md` · `RISKS.md` (کامل) · `SYSTEM.md` · `MAP.md` (فقط بخش کدخوانی) · `LOCAL-QUERIES.md` (کمکی؛ فهرست خودکار UNKNOWNها با فرمان محلی).
- **شمار برچسب‌ها به تفکیک پرونده** (شمارش `grep` روی نشان‌های `[VERIFIED]` و … در ردیف‌ها):

| پرونده | VERIFIED | INFERRED | ASSUMED | UNKNOWN | جمع |
|---|---|---|---|---|---|
| `AI.md` | 69 | 8 | 2 | 7 | 86 |
| `USER.md` | 51 | 7 | 1 | 7 | 66 |
| `EVENTS.md` | 60 | 5 | 0 | 3 | 68 |
| `FEATURE-MAP.md` (۱۲۰ قابلیت) | 114 | 1 | 0 | 6 | 121 |
| `RISKS.md` (۳۵ + ۱۵) | 49 | 2 | 1 | 7 | 59 |
| `SYSTEM.md` (کدخوانی) | 101 | 6 | 1 | 19 | 127 |
| `MAP.md` (کدخوانی) | 55 | 10 | 1 | 9 | 75 |
| **جمع** | 499 | 39 | 6 | 58 | 602 |

- **ناهم‌خوانی‌ها با ARCH-RECONCILIATION** (۱۳ مورد، جدول انتهای `FEATURE-MAP.md`)، از جمله: دروازۀ گردشگری (#5)، نام ارائه‌دهنده در پاسخ (#7)، شناسۀ کش (#8)، سنجاق Action Scheduler (#16)، تاریخ ساخت‌یافته رویداد، ستون `entity_uid` و `meta` در نمایۀ مکانی.
- **سه یافتۀ فوری** (جزئیات در `RISKS.md`): R37 شمارۀ موبایل به‌عنوان `display_name` عمومی در بلوک نظرها · R36 مسیر `post=0&url=` در AJAX هوش مصنوعی · R39 لودر رمزگذاری‌شدۀ `abzarwp` در ۱۱ افزونه و پوستۀ woodmart.
- گیت‌های ابری (`gates --record`) سبز؛ نتیجه در `GATES.md`. هیچ کدی عوض نشده است.
- `DATA.md` و `inventory.json` **ساخته نشدند** (طبق دستور؛ پایگاه‌داده لازم است).

### قدم بعدی (دقیق و اجراشدنی)

- **ابری G ← کدکس محلی (lane render/core):** (الف) PERF A0: ضبط دوبارهٔ مبنای Lighthouse با تصویر واقعی سربرگ و ثبت عنصر LCP؛ سپس A1 (dequeue شرطی JetReviews/JetEngine/eicons روی نوع‌های `tp_`) و A2–A4 با `--ab` و بودجهٔ P5-D264، یکی‌یکی. (ب) SCHEMA S1 (دروازهٔ زبان در صافی `topersia_g_schema_graph`) و S2 (`abstract` از منبع باکس AI) با ادعا و جهش در `assert-442.php`؛ S3 همراه ادغام P5-D361. (ج) UNKNOWNهای `LQ-PERF-01…08` و `LQ-SCH-01…06` را به `LOCAL-QUERIES.md` بیفزا و اجرا کن. (د) کاربر: تنظیم‌های B1–B6 و S6 (hreflang) — فقط با پشتیبان.

0. **همه:** ده اقدام اول `docs/AUDIT-2026-10/RECONCILE.md` §۸ (مالک هر کدام همان‌جا): R37 و کلید پیامک و R60 ← جلسۀ ابری UC؛ R36 · کلید AI · R53/R54 · R24 · نام ارائه‌دهنده ← جلسۀ ابری AI؛ قلم Italic/letter-spacing نقشه و نمایش تاریخ رویداد ← کدکس محلی (RENDER)؛ `Event` با `startDate` ← lane تازه برای `topersia-schema`؛ `TOPERSIA_TRUST_PROXY` زنده، R41، abzarwp، P5-D214 ← کاربر.
1. **محلی:** `LOCAL-QUERIES.md` را از بالا اجرا کن (۵۸ مورد؛ هر کدام شناسۀ `LQ-…` و فرمان دارد) و نتیجه‌ها را در همان ردیف‌های `docs/AUDIT-2026-10/*.md` به‌جای `UNKNOWN` بنویس (برچسب `VERIFIED` با خروجی فرمان). هیچ مقدار کلید، رمز، شماره یا نام کاربر در خروجی نیاید؛ فقط نام و شمار.
2. **محلی:** `DATA.md` و `inventory.json` با یک اسکریپت WP-CLI ساخته شوند (فهرست محتوا در `SPRINT-4.4-PHASES.md` بخش ۲د و افزودۀ ۲ج: نامک‌های ناهمخوان، ۲۵۰ ردیف یتیم WPML، گزارش شکاف ترجمه).
3. **محلی:** بخش‌های پایگاه‌دادهای `SYSTEM.md` (نسخه‌های زنده، افزونه‌های فعال، AS کدام نسخه، WP-Cron، کش شیء، autoload، حجم جدول‌ها) و `MAP.md` (`iran.pmtiles`: سرآیند و متادیتا، پوشش `name:fa|ar|en` با اسکریپت `LQ-SCRIPT-NAMES`، Range/206، نمایه، پاسخ `/map-points`) تکمیل شود.
4. **معمار:** بر ۱۳ ناهم‌خوانی و سه یافتۀ فوری تصمیم بگیرد (رفع‌ها در lane کد‌اند، نه ابر). پیشنهاد ترتیب: R37 ← R36 ← R38 ← تصمیم درباره R39 (افزونه‌های تجاری).
5. پس از ۱–۳: بازنویسی فازهای ۳، ۴، ۵، ۶ب، ۸ بر پایۀ ممیزی (مطابق `ARCH-RECONCILIATION` §۰).

### در انتظار تأیید محلی

> بدون تأیید محلی، هیچ‌کدام از بندهای زیر «انجام‌شده» حساب نمی‌شود. فهرست کامل UNKNOWNها با فرمان: `docs/AUDIT-2026-10/LOCAL-QUERIES.md`.

| آزمون / کار | چرا لازم است | وضعیت |
|---|---|---|
| ابری G — PERF: فهرست واقعی link/script و عنصر LCP | `LQ-PERF-01/02` در `PERF.md` §۸ (curl با کوکی ورود؛ JSON Lighthouse) | منتظر محلی |
| ابری G — PERF: جای کارت نقشه در ۳۹۰px و اندازهٔ DOM واقعی | `LQ-PERF-06/07` — اگر کارت درون ۲۰۰px زیر دید است، MapLibre در بار نخست می‌آید | منتظر محلی |
| ابری G — PERF: آزمایه و گزینه‌ها | `LQ-PERF-03/04/05/08` (eicons، وزن‌های Peyda — فقط نام کلید، CSS المنتور، گزینه‌های وودمارت) | منتظر محلی |
| ابری G — SCHEMA: متن فارسی در JSON-LD /en/ و /ar/ | `LQ-SCH-01` — `php tests/render-local.php` + شمار نویسهٔ عربی‌خط در ld+json | منتظر محلی |
| ابری G — SCHEMA: hreflang، entity_uid، ویکی‌داده، جمعیت/مساحت، meta_description | `LQ-SCH-02…06` در `SCHEMA-PARITY.md` §۶ | منتظر محلی |
| ابری G — هیچ آزمون مرورگری، Lighthouse یا `*-local.php` در ابر اجرا نشد | هر عدد کارایی در `PERF.md` نقل از `tests/RESULT-lighthouse.txt` است، نه سنجش این جلسه | — |
| تطبیق ۲د — نمایش برچسب آب fa/ar | تأیید چشمی R51/R52 (صفحۀ کشور/استان ساحلی، کروم و فایرفاکس؛ `node tests/acceptance-shots.js`) | منتظر محلی |
| تطبیق ۲د — داده و WPML رویداد | `wp post meta get <ID> event_start_date` (قالب) · شمار پرشده‌ها · `wp option get wpml-tm-custom-xml \| grep -c event_start_date` | منتظر محلی |
| تطبیق ۲د — `tpas_settings` | طول و autoload (نه مقدار): پرس‌وجوی `RECONCILE.md` §۳-ج | منتظر محلی |
| تطبیق ۲د — R61 لاگ در `reference/` | تصمیم کاربر: بیرون‌بردن `reference/topersia-log-20260927-0659(1).txt` از git و افزودن الگوی آن به `.gitignore` | منتظر کاربر |
| `LOCAL-QUERIES.md` — SYSTEM (19 مورد: `LQ-SYSTEM-01…19`) | نسخه‌های زنده/محلی، افزونه‌های فعال، AS بارشده (`wp eval 'print_r(ActionScheduler_Versions::instance()->get_sources());'`)، WP-Cron (`wp config get DISABLE_WP_CRON`)، کش شیء (`wp cache type`)، autoload، حجم جدول‌ها | منتظر محلی |
| `LOCAL-QUERIES.md` — MAP (9 مورد) | `pmtiles show geo/wp-content/uploads/tiles/iran.pmtiles` (+ `--metadata`)، سرآیند Range، شمار نمایه (`wp topersia geo-index report`)، پاسخ `/map-points`، پوشش `name:*` (`LQ-SCRIPT-NAMES`) | منتظر محلی |
| `LOCAL-QUERIES.md` — AI (7 مورد) | موتور/مدل واقعی، شمار ردیف خلاصه‌ها و `source`، `tpas_settings.position` (دکمه‌های بیرونی روشن‌اند؟)، `topersia_services_usage`، دسترسی API از IP زنده | منتظر محلی |
| `LOCAL-QUERIES.md` — USER (7 مورد) | **وقوع R37:** `wp user list --field=display_name \| grep -c '^tp_[0-9]'` (فقط شمار)، شمار کاربر/ذخیره/دنبال، پرشدگی `_topersia_country`، `mail()` روی زنده | منتظر محلی |
| `LOCAL-QUERIES.md` — EVENTS (3 مورد) | وجود `tpc_news`، ردیف‌های واقعی رابطه‌های رویداد، `DISABLE_WP_CRON` | منتظر محلی |
| `LOCAL-QUERIES.md` — FEATURE-MAP (6) و RISKS (7) | همان فرمان‌ها از سایر پرونده‌ها + **R41** (`git grep -nF -c -- "$TP_GUARD_ADMIN_USER"` با متغیر محیطی؛ مقدار هرگز چاپ نشود) + بازتولید React #200 (R39) | منتظر محلی |
| `DATA.md` (ساخته نشد) | شمار نوع پست (فارسی/ترجمه)، کیفیت `geo_coordinates`، زنجیرۀ رابطه، پوشش `entity_uid`، رکوردهای D، نامک‌های ناهمخوان، یتیم‌های WPML، شکاف ترجمه | منتظر محلی |
| `inventory.json` (ساخته نشد) | افزونه‌ها، جدول‌ها، مسیرهای REST، قلاب‌های کرون — از اسکریپت WP-CLI، نه خواندن دستی | منتظر محلی |
| بخش‌های پایگاه‌دادهای `SYSTEM.md` و `MAP.md` | در ابر وردپرس/پایگاه‌داده/WP-CLI/`iran.pmtiles` نیست | منتظر محلی |
| بررسی پرسش بی فرمان | `FEATURE-MAP.md` ردیف ۶۳ (Jev چیست؟) — پرسش از کاربر (`ARCH-RECONCILIATION` §۷ بند ۲) | منتظر کاربر |
