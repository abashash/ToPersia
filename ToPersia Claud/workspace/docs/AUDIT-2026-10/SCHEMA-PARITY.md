# SCHEMA-PARITY — هم‌خوانی JSON-LD با محتوای دیدنی (GEO/AEO)

> ممیزی فقط‌خواندنی، جلسهٔ ابری G، ۱۵ اکتبر ۲۰۲۶ · مبنای کد: `main` `47015840` (topersia-schema 1.3.0 · topersia-render 4.5.1) + خواندن شاخهٔ `origin/claude/clever-mendel-dnee8k` (`0d89c7d2`، schema 1.3.1). هیچ کد، داده یا تنظیمی عوض نشد.
> برچسب‌ها: **[VERIFIED]** پرونده:خط · **[INFERRED]** · **[UNKNOWN]** + فرمان محلی.
> مسیر کوتاه: `S:` = `plugin/topersia-schema/topersia-schema.php` · `R:` = `plugin/topersia-render/includes/topersia-g-zones.php` · `RR:` = `plugin/topersia-render/includes/topersia-g-relreg.php`.
> نشان‌های جدول: **د** دیدنی (همان مقدار روی صفحه) · **ن** ناپیدا (منتشر می‌شود، روی صفحه نیست — ریسک دادهٔ پنهان/ساختگی) · **ن¹** فقط در صفحهٔ en/ar ناپیدا (§۳) · **—** این نوع منتشر نمی‌کند.

---

## ۱ — گراف هر صفحه (مشترک همهٔ ۴۱ نوع)

[VERIFIED] نقشه دقیقاً ۴۱ کلید دارد: `tp_route` + ۱۲ G + ۷ A + ۱۰ C + ۴ E + ۷ P/D (`S:266-382`). سازنده‌ها: `topersia_schema_place` (۱۹ نوع G/A)، `topersia_schema_thing` (۱۸ نوع C/P/D + E6)، `topersia_schema_event` (E1·E3·E5)، `topersia_schema_route` (P9). Rank Math JSON-LD کامل خاموش است (`S:219-241`)، پس تنها منبع JSON-LD همین افزونه است. گراف از صافی `topersia_g_schema_graph` در render می‌گذرد که فقط BreadcrumbList، `containedInPlace` و `itinerary` را از دروازهٔ انتشار عبور می‌دهد (`R:1689-1840`).

| گره · ویژگی | منبع داده | روی صفحه | حکم | منبع |
|---|---|---|---|---|
| Organization `name` · `url` | `get_bloginfo('name')` · `https://topersia.com` | نام سایت در سربرگ وودمارت [INFERRED] | د | `S:530-538` |
| WebSite `name` · `description` · `inLanguage` | bloginfo · زبان WPML | توضیح سایت دیده نمی‌شود [INFERRED] | ن (کم‌خطر) | `S:541-552` |
| WebPage `name` | `name_{lang}` یا عنوان | تیتر سربرگ از همان `name_{lang}` | د | `S:1159` · `R:1086-1091` |
| WebPage `description` | `meta_description`، وگرنه `short_summary` | `meta_description` در render خوانده نمی‌شود (grep: ۰)؛ `short_summary` لید سربرگ است | **ن** وقتی `meta_description` پر است · د در غیر این صورت | `S:1180-1187` · `R:1316` |
| WebPage `abstract` | متای دستی `ai_summary` | باکس AI **اول خروجی ذخیره‌شدهٔ خلاصه‌ساز** را نشان می‌دهد و فقط در نبودش `ai_summary` | **ن** وقتی خلاصه‌ساز خروجی دارد (دو متن متفاوت) | `S:1189-1193` · `R:4775-4776` |
| WebPage `dateModified` | `post_modified` | صفحه فقط `last_verified_date` را با `<time>` نشان می‌دهد | **ن** (تاریخ دیگری دیده می‌شود) | `S:1166-1170` · `R:5333-5349` |
| WebPage `inLanguage` | `wpml_current_language` | `<html lang>` [INFERRED] | د | `S:1163` |
| BreadcrumbList — نیاکان | زنجیرهٔ مسیر راهنمای HTML (D338، بی کشور) | `[tp_crumbs]` | د | `R:1765-1790` · `R:1662-1685` |
| BreadcrumbList — «خانه» | name `__('خانه')`، item ثابت `https://topersia.com` | HTML: متن «ToPersia»، href `home_url('/')` (در /en/ و /ar/ خانهٔ همان زبان [INFERRED]) | **ناهم‌خوان** (نام و URL) | `S:570-575` · `R:1670` |
| entity `@id` | `entity_uid` (copy در WPML)؛ نبودش ← `permalink#entity` | `entity_uid` در بلوک شناسه | د | `S:411-421` · `R:1487` |
| entity `url` · `subjectOf` | permalink زبان جاری | — | د | `S:1130-1135` |
| entity `name` | `name_{lang}` | تیتر | د | `S:285,500-505` |
| entity `description` | `short_summary` خام | لید سربرگ از `topersia_g_text` (دروازهٔ زبان) | د · **ن¹** | `S:286` · `R:1316` |
| entity `image` (ImageObject url/width/height) | `hero_image` full | `<img>` سربرگ | د | `S:437-458` · `R:1319-1336` |
| ImageObject `caption` | alt پیوست (خام) | alt دیدنی نیست؛ در صفحهٔ en/ar alt فارسی با نام موجودیت عوض می‌شود | ن · **ن¹** | `S:459-462` · `R:1330-1333` |
| entity `sameAs` | `external_reference_uri` | «مرجع خارجی» با پیوند (نام میزبان) | د | `S:893,910` · `R:5322,5353-5361` |
| FAQPage | `faq_items` فعال، خام | `[tp_faq]` از `topersia_g_rows` (دروازهٔ زبان) | د · **ن¹** | `S:642-688` · `R:4469-4485` |

## ۲ — جدول نوع × ویژگی (ویژگی‌های ویژهٔ هر سازنده)

ستون‌ها فقط آنچه بالاتر از §۱ می‌آید. «geo» روی صفحه در `.tp-map-coord` زیر نقشه است (`R:2922`) [VERIFIED]. `additionalProperty` (جمعیت، مساحت) در کارت آمار کشور و شهر (`R:5428-5440`) [VERIFIED]؛ برای بقیهٔ نوع‌ها از کارت آمار/حقایق «هر نوع آنچه دارد» [INFERRED]. `containedInPlace` = نزدیک‌ترین نیای منتشرشدهٔ مسیر راهنما (`R:1793-1800`) [VERIFIED].

| # | نوع پست | @type (additionalType) | geo | additionalProperty | containedInPlace | ویژه |
|---|---|---|---|---|---|---|
| 1 | tp_route | TouristTrip | — | — | — | touristType **د** (`R:5455-5475`) · itinerary نام/URL **د** (`RR:383-400`) · توضیح ایستگاه `note` **ن** (`S:772-774`؛ کاشی فقط برچسب/نام/URL) |
| 2 | tp_country | Country (TD) | د | د | — | — |
| 3 | tp_region | Place (TD) | د | د* | — | — |
| 4 | tp_province | State (TD) | د | د* | د (کشور) | — |
| 5 | tp_city | City (TD) | د | د | د | — |
| 6 | tp_district | AdministrativeArea (TD) | د | د* | د | — |
| 7 | tp_village | Place (TD) | د | د* | د | — |
| 8 | tp_island | Landform (TD) | د | د* | د | — |
| 9 | tp_mountain | Mountain (TD) | د | د* | د | — |
| 10 | tp_water_body | BodyOfWater (TD) | د | د* | د | — |
| 11 | tp_desert | Landform (TD) | د | د* | د | — |
| 12 | tp_forest_plain | Landform (TD) | د | د* | د | — |
| 13 | tp_border_crossing | Place | د | د* | د | — |
| 14 | tp_monument | LandmarksOrHistoricalBuildings (TA) | د | د* | د | — |
| 15 | tp_religious_site | PlaceOfWorship (TA) | د | د* | د | — |
| 16 | tp_museum | Museum (TA) | د | د* | د | — |
| 17 | tp_protected_area | TouristAttraction | د | د* | د | — |
| 18 | tp_natural_feature | Landform (TA) | د | د* | د | — |
| 19 | tp_ancient_site | LandmarksOrHistoricalBuildings (TA) | د | د* | د | — |
| 20 | tp_landscape | TouristAttraction | د | د* | د | — |
| 21–30 | tp_handicraft · tp_local_food · tp_costume · tp_music · tp_dance · tp_language (Language) · tp_ethnic_group · tp_ritual · tp_figure (Person) · tp_historical_era | Thing / Language / Person | — | — | — | فقط §۱ |
| 31 | tp_festival | Festival / Thing+`additionalType` | — | — | — | جدول رویداد ↓ |
| 32 | tp_sports_event | SportsEvent / Thing | — | — | — | جدول رویداد ↓ |
| 33 | tp_religious_event | Event / Thing | — | — | — | جدول رویداد ↓ |
| 34 | tp_seasonal_event | Thing (`additionalType` Event) | — | — | — | فقط §۱ |
| 35–41 | tp_visa · tp_currency · tp_health_safety · tp_travel_etiquette · tp_calendar · tp_transport · tp_designation | Thing | — | — | — | فقط §۱ |

`د*` = منتشر فقط اگر فیلد پر باشد؛ دیدنی‌بودن در کارت این نوع [INFERRED] — [UNKNOWN]: `php tests/render-local.php` را با یک پست از هر نوع که `population`/`area_km2` دارد اجرا و `grep -c` مقدار در HTML بدنه را با JSON-LD مقایسه کن (شمار پست‌ها: `wp post list --post_type=tp_island --meta_key=population --format=count` برای هر نوع).

### جدول رویداد (E1 · E3 · E5، فقط وقتی تاریخ معتبر و نگذشته است)

| ویژگی | منبع | روی صفحه | حکم | منبع |
|---|---|---|---|---|
| `startDate` · `endDate` | `event_start_date`/`event_end_date` (ترجمهٔ بی‌تاریخ از اصل) | «بازه برگزاری» سربرگ | د | `S:964-990,1091-1095` · `R:1248-1275` |
| `eventStatus` · `eventAttendanceMode` | ثابت `EventScheduled` · `Offline` | — | ن (ثابت؛ ریسک کم، ولی «لغو/تعویق» هرگز منعکس نمی‌شود) | `S:1097-1098` |
| `location.name` ← `venue_name` | متای پست | **در render خوانده نمی‌شود** (grep `venue_name` در render: ۰) | **ن** | `S:1039-1045` |
| `location.@id/name` ← شهر/استان (اگر `venue_name` خالی) | والد منتشرشده | مسیر راهنما | د | `S:1046-1049` |
| `location.address` (Locality · Region · `IR`) | نام شهر/استان · ثابت | مسیر راهنما (کشور ثابت) | د (کشور: ن کم‌خطر) | `S:1051-1056` |
| `organizer` ← `organizer_name` (شاخهٔ 1.3.1) | متای همان پست | render `main`: ۰ رخداد | **ن** — تأیید P5-D361 | `clever-mendel:topersia-schema.php` تابع `topersia_schema_event_organizer` · `git grep organizer_name origin/main -- …/topersia-render` تهی |
| `sameAs` ← `official_event_url` (شاخهٔ 1.3.1) | متا؛ ترجمه از اصل | render `main`: ۰ رخداد | **ن** — تأیید P5-D361 | همان شاخه، `topersia_schema_event_official_url` |

[VERIFIED] تأیید P5-D361: در شاخهٔ `claude/clever-mendel-dnee8k` (`0d89c7d2`) هر دو ویژگی فقط روی گرهٔ Event و فقط از دادهٔ پست افزوده شده‌اند؛ در `main` هیچ‌کدام در render نیست. کار دوباره‌ای انجام نشد.

---

## ۳ — هم‌خوانی چندزبانه

| موضوع | وضعیت | حکم | منبع |
|---|---|---|---|
| `@id` میان زبان‌ها | `entity_uid` کلاس copy ← یک `@id` در fa/en/ar | د | `S:405-421` (توضیح و کد) |
| `@id` بی `entity_uid` | پشتیبان `permalink#entity` ← **هر زبان `@id` جدا** و پیوند میان‌زبانی گم | ریسک | `S:419-420` · شمار: [UNKNOWN] `wp post list --post_type=any --post_status=publish --meta_key=entity_uid --meta_compare=NOT EXISTS --format=count` |
| `url` · `inLanguage` | permalink ترجمه · زبان WPML روی WebPage و WebSite | د | `S:1130-1163` |
| **دروازهٔ زبان (P5-D278)** | render هر فیلد ترجمه‌پذیرِ ترجمه‌نشده را در صفحهٔ غیرفارسی خالی می‌کند (`R:241-280,366-380`)؛ موتور شِما `get_post_meta` خام می‌خواند (`S:489-527,642-688,1180-1193,459-462,1039`) ← در /en/ و /ar/ متن فارسیِ `short_summary`، `meta_description`، `ai_summary`، پاسخ‌های FAQ، alt و `venue_name` در JSON-LD می‌آید ولی روی صفحه نیست، آن هم با `inLanguage: en` | **ن¹ — مهم‌ترین شکاف** | همان‌ها · وقوع: [UNKNOWN] `php tests/render-local.php` روی /en/ استان اصفهان و `grep -c '[\x{0600}-\x{06FF}]'` در بلوک ld+json |
| containedInPlace در ترجمه | والد از پست اصلی (P5-D349) و سپس دروازهٔ render | د | `S:866-874` · `R:1793-1800` |
| BreadcrumbList در ترجمه | نام‌ها از `wpml_object_id` + `name_{lang}`؛ «خانه» ثابت به دامنهٔ اصلی | ناهم‌خوان (فقط ریشه) | `S:570-575,604-618` |
| hreflang | کد ما هیچ hreflang نمی‌نویسد (grep: ۰). WPML آن را با `head_langs` در `wp_head` می‌نویسد (`geo/wp-content/plugins/sitepress-multilingual-cms/classes/seo/class-wpml-seo-headlangs.php:22-56`، با `x-default`)، **ولی** WPML SEO وقتی Rank Math فعال است `head_langs` را خاموش و hreflang را به نقشهٔ سایت می‌برد (`geo/wp-content/plugins/wp-seo-multilingual/classes/Shared/Upgrade/Commands/DisableHeadLangs.php:11-27`) | [UNKNOWN] کدام فعال است | `wp option get icl_sitepress_settings --format=json \| grep -o '"head_langs":[0-9]'` · `curl -s http://topersia.local/en/province/isfahan/ \| grep -c hreflang` · `curl -s http://topersia.local/sitemap_index.xml \| grep -c hreflang` (هر دو با کوکی ورود) |
| canonical | از Rank Math به ازای زبان [INFERRED] | — | [UNKNOWN] `curl … \| grep -o '<link rel="canonical"[^>]*>'` در سه زبان |

---

## ۴ — واجد بودن نتیجهٔ غنی گوگل و ارزش استناد هوش مصنوعی

| نوع/گره | نتیجهٔ غنی گوگل | اجباری کم | پیشنهادی کم | برای استناد AI |
|---|---|---|---|---|
| Event (E1·E3·E5 تاریخ‌دار) | **واجد** (Event) | هیچ (name · startDate · location+address هست) | `offers`، `performer`؛ `organizer` تا ادغام 1.3.1؛ `image` هست | `organizer`/`sameAs` رسمی (پس از دیدنی‌شدن) |
| Event بی تاریخ / گذشته / E6 | غیرواجد (Thing — عمدی) | — | — | additionalType Event کمک کم |
| BreadcrumbList | واجد (فقط دسکتاپ — [INFERRED] گوگل از ژانویهٔ ۲۰۲۵ مسیر راهنما را در نتیجهٔ موبایل نشان نمی‌دهد) | — | — | زنجیرهٔ مکانی |
| FAQPage | **عملاً غیرواجد** — [INFERRED] گوگل از اوت ۲۰۲۳ نتیجهٔ غنی FAQ را فقط برای سایت‌های دولتی/سلامت معتبر نشان می‌دهد | — | — | ارزش AEO بالا، به شرط هم‌خوانی زبان |
| ImageObject (سربرگ) | Image metadata (نشان «Licensable») | — | `license`، `acquireLicensePage`، `creditText`، `creator`، `copyrightNotice` | منبع عکس |
| Organization (سراسری) | Organization (Knowledge Panel) | — | `logo`، `sameAs` (ویکی‌داده/شبکه‌ها)، `contactPoint` | **هویت ناشر — اکنون فقط name و url** |
| Place/City/State/Country/Landform/… · TouristAttraction · Museum · PlaceOfWorship | نوع نتیجهٔ غنی گوگل ندارند | — | — | `geo` ✓ · `containedInPlace` ✓ · `sameAs` ویکی‌داده (یک مقدار) |
| TouristTrip | ندارد | — | — | itinerary ✓ |
| Person (tp_figure) | ندارد (ProfilePage لازم است) | — | — | `sameAs` ویکی‌داده مهم |
| Thing/Language | ندارد | — | — | `sameAs` |

استناد AI (همهٔ نوع‌ها):
- `sameAs` ویکی‌داده: فقط یک مقدار از `external_reference_uri` (`S:891-898`)؛ چند مرجع (ویکی‌داده + ویکی‌پدیا + مرجع رسمی) پشتیبانی نمی‌شود. پوشش داده: [UNKNOWN] `wp post list --post_type=any --meta_key=external_reference_uri --meta_value=wikidata --meta_compare=LIKE --format=count`.
- تاریخ به‌روزرسانی: `dateModified` هست ولی تاریخ دیدنی `last_verified_date` است (§۱) — `WebPage.lastReviewed` (ویژگی معتبر WebPage) برای آن تعریف نشده.
- منبع/نویسنده: WebPage نه `author` دارد نه `publisher` (ناشر فقط روی WebSite، `S:550`)؛ فهرست منابع دیدنی (`R:5289-5316`) به `citation` نگاشت نمی‌شود.
- خلاصه: `abstract` هست ولی منبعش با باکس دیدنی یکی نیست (§۱). `speakable` نیست — [INFERRED] گوگل آن را فقط برای خبر انگلیسی آمریکا (بتا) پشتیبانی می‌کند؛ توصیه نمی‌شود.

---

## ۵ — اقدام‌های رتبه‌بندی‌شده

| # | اقدام | مالک | آزمون لازم | اثر |
|---|---|---|---|---|
| **S1** | دروازهٔ زبان در JSON-LD: در `topersia_g_schema_graph` (یا موتور) `description`، `abstract`، `caption`، FAQ و `location.name` همان دروازهٔ `topersia_g_untranslated` را بگیرند؛ خالی ← حذف | render (صافی، `R:1689-1840`) | `assert-442.php` (ادعای تازه + جهش: دروازهٔ خاموش باید بیفتد)، `render-local.php` /en/ و /ar/ | GEO/AEO: حذف دادهٔ پنهان و زبان ناهم‌خوان با `inLanguage`؛ چندزبانه ++ |
| **S2** | `WebPage.abstract` از همان منبع باکس AI (`topersia_g_ai_outputs` اول، سپس `ai_summary`) | render (صافی) | `render-local.php` با خروجی ذخیره‌شده و بی آن | AEO: چکیدهٔ ماشین‌خوان = متن دیدنی |
| **S3** | دیدنی‌کردن `venue_name` (و پس از ادغام 1.3.1، `organizer_name` و پیوند `official_event_url` — P5-D361) در بلوک هویت/سربرگ رویداد | render | `assert` + `alltypes.php` (پنج حالت داده) + `acceptance` | نتیجهٔ غنی Event کامل‌تر؛ رفع دادهٔ ناپیدا |
| **S4** | تاریخ: نمایش «آخرین به‌روزرسانی» با `<time datetime>` از `post_modified`، یا افزودن `WebPage.lastReviewed` ← `last_verified_date` و نگه‌داشتن هر دو هم‌خوان | render (دیدنی) + schema (`lastReviewed`) | `assert` · `schema-local.php` | AI: تازگی قابل اعتماد |
| **S5** | ریشهٔ BreadcrumbList: نام «ToPersia» و URL خانهٔ زبان جاری (`apply_filters('wpml_home_url', …)`) | schema (`S:570-575`) یا render (صافی، `list[0]`) | `render-local.php` سه زبان | Breadcrumb هم‌خوان با HTML؛ چندزبانه |
| **S6** | روشن‌کردن وضعیت hreflang (head یا sitemap) و یکسان‌سازی؛ x-default | تنظیم WPML SEO / Rank Math (کاربر) | فرمان‌های §۳ | چندزبانه/SEO؛ بی کد |
| **S7** | Organization: `logo` (ImageObject)، `sameAs` شبکه‌ها/ویکی‌داده، و ارجاع `publisher` روی WebPage | schema | `schema-local.php` · Rich Results Test (محلی ممکن نیست؛ پس از راه‌اندازی) | هویت ناشر برای استناد AI |
| **S8** | ImageObject: `creditText`/`creator`/`license`/`copyrightNotice` از متای پیوست؛ `caption` فقط اگر figcaption دیدنی هست | schema + داده | `schema-local.php` | Image metadata؛ کاهش ریسک کپی‌رایت |
| **S9** | `sameAs` چندمقداری (ویکی‌داده + ویکی‌پدیا) — فیلد تکراری یا جداکننده | schema + JetEngine (داده) | `pack-local.php` (JSON Schema بسته) | GEO: گره‌زدن موجودیت به گراف دانش |
| **S10** | `WebPage.citation` از `source_references` دیدنی | schema | `schema-local.php` | AEO: منبع‌دار بودن |
| **S11** | توضیح ایستگاه مسیر: یا در کاشی مسیر دیدنی شود یا از `itinerary` حذف | render یا schema | `alltypes.php` (tp_route) | رفع دادهٔ ناپیدا |
| **S12** | گارد `entity_uid`: هشدار پیشخوان/آزمون برای پست منتشرشدهٔ بی `entity_uid` (پشتیبان `#entity` هر زبان را جدا می‌کند) | core/schema | فرمان §۳ | پیوند میان‌زبانی |
| **S13** | `eventStatus` از فیلد وضعیت (اگر داده دارد: لغو/تعویق) به‌جای ثابت | schema + داده | `schema-local.php` | دقت Event |

**پنج اقدام برتر:** S1 · S2 · S3 · S4 · S5.

## ۶ — UNKNOWNها (برای `LOCAL-QUERIES.md`)

| شناسه | پرسش | فرمان محلی |
|---|---|---|
| LQ-SCH-01 | وقوع متن فارسی در JSON-LD صفحهٔ /en/ و /ar/ | §۳ ردیف «دروازهٔ زبان» |
| LQ-SCH-02 | hreflang در head یا sitemap | §۳ ردیف hreflang |
| LQ-SCH-03 | پست‌های منتشرشدهٔ بی `entity_uid` | §۳ |
| LQ-SCH-04 | پوشش ویکی‌داده در `external_reference_uri` | §۴ |
| LQ-SCH-05 | دیدنی‌بودن `population`/`area_km2` در نوع‌های غیر کشور/شهر | §۲ |
| LQ-SCH-06 | شمار پست‌هایی که هم `meta_description` و هم خروجی خلاصه‌ساز دارند (ناهم‌خوانی description/abstract) | `wp post list --post_type=any --meta_key=meta_description --meta_compare=EXISTS --format=count` |
