# چهار خانهٔ ثابت زیر سربرگ — P5-D395 (پیاده شد؛ پیشینه: P5-D390)

> **پیاده شد — دستهٔ ۲۴ (۲۰ اکتبر ۲۰۲۶).** متن پرامت دستهٔ ۲۴ ملاک است؛ جایگزین P5-D275 («کمتر از چهار، بی جای خالی») و P5-D390. پایین‌تر، از «پیشینه» به بعد، تحلیل پیش‌نویس دستهٔ ۲۰ برای سابقه مانده است.

## قاعده

- هر نوع از ۴۱ نوع همیشه **دقیقاً همان ۴ خانه، به همان ترتیب** — نه کمتر، نه بیشتر؛ شهر با شهر، استان با استان. تنها منبع: `topersia_g_stat_cells()` در `plugin/topersia-render/includes/topersia-g-zones.php` (کلید، برچسب سه‌زبانه از کاتالوگ، قالب نمایش)؛ ستون `stats` ماتریس نوع از همان ساخته می‌شود.
- اولویت انتخاب: (الف) مشتق مکانی (استان · شهرستان · شهر · فاصله تا مرکز استان) · (ب) فیلد الزامی بستهٔ درون‌ریزی (بخش ج) · (ج) پرکاربردترین فیلد کوتاه نوع · خانهٔ عمومی (آخرین بازبینی · شمار منبع‌ها · شمار زبان‌ها · شمار مکان‌های مرتبط). هرگز نام نوع، بهترین فصل یا کد استان.
- **نبود مقدار:** خانه می‌ماند با «—» و `role="img" aria-label="نامشخص"` (fa · en · ar از کاتالوگ)؛ یک خط با «…» برای مقدار بلند (متن کامل در `title`) — ارتفاع ردیف ثابت. «—» فقط در لایهٔ نمایش (`[tp_stats]`، ثابت `TOPERSIA_STAT_NONE`) ساخته می‌شود: `topersia_g_stat_cell()` برای نبود `null` می‌دهد، پس به داده، JSON-LD، متا، ورودی هوش مصنوعی یا نمایهٔ جستجو نمی‌رسد (`tests/stats4-local.php` با جهش).
- **الزامی در بسته:** خانه‌های ● همان بخش (ج) — `topersia_pack_schema()` آن‌ها را از صافی `topersia_pack_required_fields` الزامی می‌کند؛ «الزامی» فقط برای پست تازه (create) سنجیده می‌شود، پست‌های موجود دست نمی‌خورند.
- بی فیلد، جدول یا ستون تازه؛ ارتفاع و تقویم ساخته نمی‌شود — فقط خانه‌های مشتق از رابطه‌ها، مختصات، `source_references`، WPML و `last_verified_date` موجود.

## جدول نهایی — ۴۱ نوع × ۴ خانه (● = الزامی در بستهٔ درون‌ریزی تازه)

| نوع | خانهٔ ۱ | خانهٔ ۲ | خانهٔ ۳ | خانهٔ ۴ |
|---|---|---|---|---|
| `tp_country` | شمار استان‌ها `count:provinces` | جمعیت `population` ● | مساحت `area_km2` ● | زبان رسمی `official_language` ● |
| `tp_city` | استان `parent:province` | شهرستان `parent:county` | مساحت `area_km2` ● | جمعیت `population` ● |
| `tp_region` | نوع منطقه `region_classification` ● | شمار استان‌ها `count:provinces` | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` |
| `tp_province` | مرکز `capital` | مساحت `area_km2` ● | جمعیت `population` ● | نوع اقلیم `climate_type` ● |
| `tp_district` | استان `parent:province` | شهر `parent:city` | نوع محله `neighborhood_type` ● | فاصله تا مرکز استان `distance:capital` |
| `tp_village` | استان `parent:province` | فاصله تا مرکز استان `distance:capital` | جمعیت `population` ● | ارتفاع از سطح دریا `elevation_m` ● |
| `tp_island` | استان `parent:province` | نوع جزیره `island_type` ● | مساحت `area_km2` ● | فاصله تا مرکز استان `distance:capital` |
| `tp_mountain` | استان `parent:province` | ارتفاع از سطح دریا `elevation_m` ● | نوع عارضه `mountain_type` ● | دشواری صعود `trekking_difficulty` ● |
| `tp_water_body` | استان `parent:province` | فاصله تا مرکز استان `distance:capital` | نوع پهنۀ آبی `water_body_type` ● | مساحت `area_km2` ● |
| `tp_desert` | استان `parent:province` | نوع کویر `desert_type` ● | مساحت `area_km2` ● | حداکثر دمای ثبت‌شده `max_temperature_c` ● |
| `tp_forest_plain` | استان `parent:province` | نوع پهنه `area_type` ● | نوع جنگل `forest_type` ● | مساحت `area_km2` ● |
| `tp_border_crossing` | استان `parent:province` | نوع گذرگاه `crossing_type` ● | کشور مقابل `border_country` ● | وضعیت `status` ● |
| `tp_monument` | گونه `classification` ● | سال ساخت `construction_year` ● | مدت بازدید `suggested_duration` ● | وضعیت مرمت `restoration_status` ● |
| `tp_religious_site` | استان `parent:province` | شهر `parent:city` | گونه `classification` ● | دین `religion` ● |
| `tp_ancient_site` | استان `parent:province` | فاصله تا مرکز استان `distance:capital` | وضعیت کاوش `excavation_status` ● | سبک معماری `architectural_style` ● |
| `tp_natural_feature` | گونه `classification` ● | ارتفاع `height_m` ● | عمق `depth_m` ● | طول `length_km` ● |
| `tp_museum` | شهر `parent:city` | گونه `classification` ● | سال بنیان‌گذاری `founded_year` ● | مدت بازدید `suggested_duration` ● |
| `tp_protected_area` | استان `parent:province` | نوع حفاظت `park_type` ● | مساحت `area_km2` ● | مدت بازدید `suggested_duration` ● |
| `tp_landscape` | استان `parent:province` | نوع منظر `landscape_type` ● | مساحت `total_area_km2` ● | مدت بازدید `suggested_duration` ● |
| `tp_handicraft` | گونه `classification` ● | سال ثبت یونسکو `intangible_heritage_year` ● | وضعیت ثبت `intangible_heritage_status` ● | مکان‌های مرتبط `meta:related` |
| `tp_local_food` | گونه `classification_` ● | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` |
| `tp_costume` | گونه `classification` ● | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` |
| `tp_music` | گونه `classification` ● | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` |
| `tp_dance` | گونه `dance_type` ● | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` |
| `tp_language` | خانوادۀ زبانی `language_family` ● | خط `script` ● | گویشوران در ایران `speaker_estimate_iran` ● | نام بومی `name_endonym` ● |
| `tp_ethnic_group` | جمعیت تخمینی `population_estimate` ● | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` |
| `tp_ritual` | گونه `classification` ● | گستره `spatial_scope` ● | سال ثبت یونسکو `intangible_heritage_year` ● | وضعیت ثبت `intangible_heritage_status` ● |
| `tp_figure` | گونه `classification` ● | سال تولد `birth_year_approx` ● | سال درگذشت `death_year_approx` ● | مکان‌های مرتبط `meta:related` |
| `tp_historical_era` | آغاز `start_year_approx` ● | پایان `end_year_approx` ● | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` |
| `tp_designation` | سال آغاز برنامه `programme_start_year` ● | مکان‌های مرتبط `meta:related` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` |
| `tp_route` | مسافت `total_distance_km` ● | مدت پیشنهادی `estimated_duration_days` ● | دشواری `difficulty_level` ● | گونه `classification` ● |
| `tp_visa` | حداکثر اقامت `max_total_stay_days` ● | اعتبار گذرنامه `passport_validity_months` ● | اقامت در منطقۀ آزاد `free_zone_stay_days` ● | آخرین بازبینی `meta:verified` |
| `tp_currency` | کد ISO `currency_iso_code` ● | نماد `currency_symbol` ● | کارت بین‌المللی `international_card_status` ● | بازتعریف واحد پول `redenomination_status` ● |
| `tp_health_safety` | پلیس `emergency_police` ● | اورژانس `emergency_ambulance` ● | آتش‌نشانی `emergency_fire` ● | آخرین بازبینی `meta:verified` |
| `tp_travel_etiquette` | آخرین بازبینی `meta:verified` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` | مکان‌های مرتبط `meta:related` |
| `tp_calendar` | آخرین بازبینی `meta:verified` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` | مکان‌های مرتبط `meta:related` |
| `tp_transport` | آخرین بازبینی `meta:verified` | منبع‌ها `meta:sources` | زبان‌ها `meta:languages` | مکان‌های مرتبط `meta:related` |
| `tp_festival` | تکرار `event_frequency` ● | تاریخ آغاز `event_start_date` | تاریخ پایان `event_end_date` | مکان‌های مرتبط `meta:related` |
| `tp_sports_event` | رشته `sport_type` ● | سطح `competition_level` ● | تکرار `event_frequency` ● | تاریخ آغاز `event_start_date` |
| `tp_religious_event` | تقویم `calendar_system` ● | مدت مراسم `ceremony_duration_days` ● | گسترۀ حضور `crowd_scale` ● | تاریخ آغاز `event_start_date` |
| `tp_seasonal_event` | پدیده `natural_phenomenon_type` ● | فصل `season` ● | وابستگی به هوا `weather_dependency` ● | مکان‌های مرتبط `meta:related` |

## گزارش «خانهٔ خالی» — نسخهٔ محلی، ۲۰ اکتبر ۲۰۲۶ (پس از دادهٔ پایهٔ دستهٔ ۲۲ب)

ابزار: `php wp-cli.phar … eval-file scripts/tp-stats-empty.php [md] [all]` (فقط خواندنی). **گیت راه‌اندازی (فعلاً فقط گزارش): حداکثر ۱ خانهٔ «—» در هر پست منتشرشده** — ستون «رد گیت».

| نوع | پست | ۰ «—» | ۱ | ۲ | ۳ | ۴ | رد گیت (> ۱) | «—» به تفکیک خانه |
|---|---|---|---|---|---|---|---|---|
| `tp_country` | 1 | 1 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_city` | 114 | 8 | 83 | 23 | 0 | 0 | 23 | مساحت 105 · جمعیت 24 |
| `tp_region` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_province` | 31 | 1 | 5 | 25 | 0 | 0 | 25 | مرکز 26 · نوع اقلیم 29 |
| `tp_district` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_village` | 1 | 1 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_island` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_mountain` | 1 | 0 | 1 | 0 | 0 | 0 | 0 | دشواری صعود 1 |
| `tp_water_body` | 1 | 1 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_desert` | 1 | 0 | 0 | 1 | 0 | 0 | 1 | مساحت 1 · حداکثر دمای ثبت‌شده 1 |
| `tp_forest_plain` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_border_crossing` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_monument` | 11 | 0 | 0 | 1 | 8 | 2 | 11 | گونه 3 · سال ساخت 9 · مدت بازدید 11 · وضعیت مرمت 11 |
| `tp_religious_site` | 1 | 1 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_ancient_site` | 3 | 0 | 0 | 3 | 0 | 0 | 3 | وضعیت کاوش 3 · سبک معماری 3 |
| `tp_natural_feature` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_museum` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_protected_area` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_landscape` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_handicraft` | 3 | 0 | 0 | 1 | 2 | 0 | 3 | گونه 2 · سال ثبت یونسکو 3 · وضعیت ثبت 3 |
| `tp_local_food` | 1 | 1 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_costume` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_music` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_dance` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_language` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_ethnic_group` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_ritual` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_figure` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_historical_era` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_designation` | 6 | 0 | 0 | 6 | 0 | 0 | 6 | مکان‌های مرتبط 6 · منبع‌ها 6 |
| `tp_route` | 1 | 1 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_visa` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_currency` | 2 | 0 | 1 | 1 | 0 | 0 | 1 | نماد 1 · کارت بین‌المللی 1 · بازتعریف واحد پول 1 |
| `tp_health_safety` | 1 | 0 | 1 | 0 | 0 | 0 | 0 | آخرین بازبینی 1 |
| `tp_travel_etiquette` | 1 | 0 | 0 | 1 | 0 | 0 | 1 | آخرین بازبینی 1 · مکان‌های مرتبط 1 |
| `tp_calendar` | 1 | 0 | 0 | 1 | 0 | 0 | 1 | آخرین بازبینی 1 · مکان‌های مرتبط 1 |
| `tp_transport` | 1 | 0 | 0 | 1 | 0 | 0 | 1 | آخرین بازبینی 1 · مکان‌های مرتبط 1 |
| `tp_festival` | 1 | 0 | 0 | 1 | 0 | 0 | 1 | تاریخ آغاز 1 · تاریخ پایان 1 |
| `tp_sports_event` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_religious_event` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| `tp_seasonal_event` | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — |
| **همه** | 183 | 15 | 91 | 65 | 10 | 2 | **77** | |

**پست‌هایی که باید پر شوند** (خانه‌های «—»؛ تا ۸ در هر نوع):

- `tp_city` (106): ری #140 (جمعیت) · سلطانیه #142 (مساحت) · سمنان #146 (مساحت، جمعیت) · اردستان #389 (مساحت) · دولت‌آباد #390 (مساحت) · چادگان #392 (مساحت) · دهاقان #393 (مساحت) · فلاورجان #394 (مساحت) · …
- `tp_province` (30): آذربایجان شرقی #96 (نوع اقلیم) · آذربایجان غربی #97 (مرکز، نوع اقلیم) · اردبیل #98 (مرکز، نوع اقلیم) · البرز #100 (مرکز، نوع اقلیم) · ایلام #101 (مرکز، نوع اقلیم) · بوشهر #102 (مرکز، نوع اقلیم) · چهارمحال و بختیاری #106 (مرکز، نوع اقلیم) · خراسان جنوبی #108 (مرکز، نوع اقلیم) · …
- `tp_mountain` (1): کوه کرکس #1947 (دشواری صعود)
- `tp_desert` (1): کویر مرنجاب #1945 (مساحت، حداکثر دمای ثبت‌شده)
- `tp_monument` (11): میدان نقش جهان #1948 (گونه، سال ساخت، مدت بازدید، وضعیت مرمت) · باغ چهل‌ستون #1950 (گونه، سال ساخت، مدت بازدید، وضعیت مرمت) · باغ فین #1951 (گونه، مدت بازدید، وضعیت مرمت) · کاروانسرای شیخعلی‌خان #1955 (سال ساخت، مدت بازدید، وضعیت مرمت) · کاروانسرای مرنجاب #1956 (مدت بازدید، وضعیت مرمت) · کاروانسرای امین‌آباد #1957 (سال ساخت، مدت بازدید، وضعیت مرمت) · کاروانسرای گبرآباد #1958 (سال ساخت، مدت بازدید، وضعیت مرمت) · کاروانسرای مهیار #1959 (سال ساخت، مدت بازدید، وضعیت مرمت) · …
- `tp_ancient_site` (3): قنات ماه #1952 (وضعیت کاوش، سبک معماری) · قنات وزوان #1953 (وضعیت کاوش، سبک معماری) · قنات مزدآباد #1954 (وضعیت کاوش، سبک معماری)
- `tp_handicraft` (3): خاتم‌کاری #1963 (سال ثبت یونسکو، وضعیت ثبت) · میناکاری #1964 (گونه، سال ثبت یونسکو، وضعیت ثبت) · قلم‌زنی #1965 (گونه، سال ثبت یونسکو، وضعیت ثبت)
- `tp_designation` (6): میراث جهانی یونسکو در ایران #51 (مکان‌های مرتبط، منبع‌ها) · میراث فرهنگی ناملموس یونسکو در ایران #54 (مکان‌های مرتبط، منبع‌ها) · ژئوپارک‌های جهانی یونسکو در ایران #56 (مکان‌های مرتبط، منبع‌ها) · ذخیره‌گاه‌های زیست‌کره یونسکو در ایران #58 (مکان‌های مرتبط، منبع‌ها) · شهرهای خلاق یونسکو در ایران #61 (مکان‌های مرتبط، منبع‌ها) · آثار طبیعی ملی ایران #63 (مکان‌های مرتبط، منبع‌ها)
- `tp_currency` (2): ارز و روش‌های پرداخت در ایران #204 (نماد) · ریال ایران #1968 (کارت بین‌المللی، بازتعریف واحد پول)
- `tp_health_safety` (1): شماره‌های اضطراری ایران #1970 (آخرین بازبینی)
- `tp_travel_etiquette` (1): پوشش در اماکن عمومی ایران #1971 (آخرین بازبینی، مکان‌های مرتبط)
- `tp_calendar` (1): تقویم رسمی ایران #1969 (آخرین بازبینی، مکان‌های مرتبط)
- `tp_transport` (1): شبکۀ ریلی ایران #1972 (آخرین بازبینی، مکان‌های مرتبط)
- `tp_festival` (1): جشن گلاب‌گیری کاشان #1967 (تاریخ آغاز، تاریخ پایان)

---

## پیشینه — پیش‌نویس دستهٔ ۲۰ (P5-D390)


- منبع: `tests/stats4-matrix.php` روی نسخهٔ محلی (۲۰ اکتبر ۲۰۲۶)، همهٔ پست‌های منتشرشدهٔ زبان پیش‌فرض، با همان `topersia_g_stat_cell()` رندر (طول، نوع، پرچم). کد استان نامزد نیست (P5-D386).
- **تکراری ممنوع:** نام نوع (در eyebrow سربرگ هست) و بهترین فصل (جدول فصل‌ها دارد) هیچ‌جا نامزد نیستند — نسخهٔ پیشینِ این سند «نوع (برچسب)» را داشت و برداشته شد.
- خانه‌های **مشتق پیشنهادی** (در رندر هنوز نیستند): «استان»، «شهرستان»، «شهر» (والد R01 از آن نوع)، «شمار مکان‌های زیرمجموعه» (فرزندان R01 منتشرشده، فقط غیرصفر) و «فاصله تا مرکز استان» (خط مستقیم، فقط وقتی خود پست و مرکز استانش مختصات دارند).

### خلاصه

| | شمار |
|---|---|
| پست منتشرشدهٔ محلی (۴۱ نوع، ۲۰ نوع دارای پست) | ۱۸۳ |
| (ب) پست‌هایی که **پس از افزودن همهٔ مشتق‌ها** هنوز کمتر از ۴ خانه دارند | **۱۶۲** |
| نوع‌های دارای پست که فقط با مشتق‌های همیشه‌موجود «هرگز کمتر از ۴» را تضمین می‌کنند | ۰ از ۲۰ |
| نوع‌هایی که با مشتق‌های همیشه‌موجود + فیلدهای الزامیِ (ج) تضمین می‌کنند | ۱۱ از ۲۰ |

**یافتهٔ اصلی:** با دادهٔ امروز هیچ نوعی فقط با مشتق‌ها به ۴ تضمین‌شده نمی‌رسد — بیشترین مشتقِ همیشه‌موجود دو خانه است (شهر: استان و شهرستان). پس «هرگز کمتر از ۴» فقط با **الزامی‌شدن فیلد در بستهٔ درون‌ریزی** (ج) شدنی است؛ بی آن، پستی که داده ندارد کمتر از ۴ می‌ماند.

سه راه برای تصمیم کاربر (همان نسخهٔ پیشین):

۱. **دقیقاً ۴ با خانهٔ مشتق + فیلد الزامی** (پیشنهاد): مشتق‌های بالا در رندر، فیلدهای (ج) در بستهٔ درون‌ریزی الزامی؛ پست قدیمیِ کم‌داده تا پر شدن همان شمار را نشان دهد (P5-D275 فقط برای آن‌ها) و در گزارش داده فهرست شود.
۲. **دقیقاً ۴ فقط با داده**: P5-D275 بماند تا داده پر شود؛ این جدول ترتیب جایگزین‌ها را تعیین می‌کند.
۳. **دقیقاً ۴ با جای خالی دیدنی** («—»): ناهمخوان با قاعدهٔ «نبود ← چیزی نشان نده»؛ پیشنهاد نمی‌شود.

### (الف) خانه‌های مشتقِ همیشه‌موجود و تضمین «هرگز کمتر از ۴»

«همیشه‌موجود» = در **همهٔ** پست‌های محلی آن نوع پر است (تجربی، از داده؛ ساختار جت‌انجین آن را اجبار نمی‌کند — مثلاً «فاصله تا مرکز استان» فقط وقتی مختصات هست). نوع بی پست محلی ستون تضمین «—» دارد.

| نوع | پست | مشتقِ همیشه‌موجود | تضمین فقط با مشتق | + فیلد الزامی (ج) |
|---|---|---|---|---|
| `tp_country` | ۱ | شمار مکان‌های زیرمجموعه | ✗ | ✓ |
| `tp_city` | ۱۱۴ | استان (والد R01) · شهرستان (والد R01) | ✗ | ✓ |
| `tp_region` | ۰ | — | — | — |
| `tp_province` | ۳۱ | — | ✗ | ✗ |
| `tp_district` | ۰ | — | — | — |
| `tp_village` | ۱ | استان (والد R01) · فاصله تا مرکز استان | ✗ | ✓ |
| `tp_island` | ۰ | — | — | — |
| `tp_mountain` | ۱ | استان (والد R01) | ✗ | ✓ |
| `tp_water_body` | ۱ | استان (والد R01) · فاصله تا مرکز استان | ✗ | ✓ |
| `tp_desert` | ۱ | استان (والد R01) | ✗ | ✓ |
| `tp_forest_plain` | ۰ | — | — | — |
| `tp_border_crossing` | ۰ | — | — | — |
| `tp_monument` | ۱۱ | — | ✗ | ✓ |
| `tp_religious_site` | ۱ | استان (والد R01) · شهر (والد R01) | ✗ | ✓ |
| `tp_ancient_site` | ۳ | استان (والد R01) · فاصله تا مرکز استان | ✗ | ✓ |
| `tp_natural_feature` | ۰ | — | — | — |
| `tp_museum` | ۰ | — | — | — |
| `tp_protected_area` | ۰ | — | — | — |
| `tp_landscape` | ۰ | — | — | — |
| `tp_handicraft` | ۳ | — | ✗ | ✗ |
| `tp_local_food` | ۱ | — | ✗ | ✗ |
| `tp_costume` | ۰ | — | — | — |
| `tp_music` | ۰ | — | — | — |
| `tp_dance` | ۰ | — | — | — |
| `tp_language` | ۰ | — | — | — |
| `tp_ethnic_group` | ۰ | — | — | — |
| `tp_ritual` | ۰ | — | — | — |
| `tp_figure` | ۰ | — | — | — |
| `tp_historical_era` | ۰ | — | — | — |
| `tp_designation` | ۶ | — | ✗ | ✗ |
| `tp_route` | ۱ | — | ✗ | ✓ |
| `tp_visa` | ۰ | — | — | — |
| `tp_currency` | ۲ | — | ✗ | ✓ |
| `tp_health_safety` | ۱ | — | ✗ | ✗ |
| `tp_travel_etiquette` | ۱ | — | ✗ | ✗ |
| `tp_calendar` | ۱ | — | ✗ | ✗ |
| `tp_transport` | ۱ | — | ✗ | ✗ |
| `tp_festival` | ۱ | — | ✗ | ✗ |
| `tp_sports_event` | ۰ | — | — | — |
| `tp_religious_event` | ۰ | — | — | — |
| `tp_seasonal_event` | ۰ | — | — | — |

### (ج) فیلدهایی که در بستهٔ درون‌ریزی برای هر نوع «الزامی» شوند

به‌اندازهٔ کمبود تا ۴ (۴ منهای شمار مشتق‌های همیشه‌موجود)، از فیلدهای کوتاه همان نوع به ترتیب ستون‌های جدول پایین. پیشنهاد است، نه پیاده‌سازی؛ الزامی‌شدن در `topersia_pack_schema()` (هسته، بخش ۱۹) پس از تأیید.

| نوع | الزامی پیشنهادی |
|---|---|
| `tp_country` | جمعیت (`population`) · مساحت (`area_km2`) · زبان رسمی (`official_language`) |
| `tp_city` | مساحت (`area_km2`) · جمعیت (`population`) |
| `tp_region` | نوع منطقه (`region_classification`) |
| `tp_province` | مساحت (`area_km2`) · جمعیت (`population`) · نوع اقلیم (`climate_type`) |
| `tp_district` | نوع محله (`neighborhood_type`) |
| `tp_village` | جمعیت (`population`) · ارتفاع از سطح دریا (`elevation_m`) |
| `tp_island` | نوع جزیره (`island_type`) · مساحت (`area_km2`) |
| `tp_mountain` | ارتفاع از سطح دریا (`elevation_m`) · نوع عارضه (`mountain_type`) · دشواری صعود (`trekking_difficulty`) |
| `tp_water_body` | نوع پهنۀ آبی (`water_body_type`) · مساحت (`area_km2`) |
| `tp_desert` | نوع کویر (`desert_type`) · مساحت (`area_km2`) · حداکثر دمای ثبت‌شده (`max_temperature_c`) |
| `tp_forest_plain` | نوع پهنه (`area_type`) · نوع جنگل (`forest_type`) · مساحت (`area_km2`) |
| `tp_border_crossing` | نوع گذرگاه (`crossing_type`) · کشور مقابل (`border_country`) · وضعیت (`status`) |
| `tp_monument` | گونه (`classification`) · سال ساخت (`construction_year`) · مدت بازدید (`suggested_duration`) · وضعیت مرمت (`restoration_status`) |
| `tp_religious_site` | گونه (`classification`) · دین (`religion`) |
| `tp_ancient_site` | وضعیت کاوش (`excavation_status`) · سبک معماری (`architectural_style`) |
| `tp_natural_feature` | گونه (`classification`) · ارتفاع (`height_m`) · عمق (`depth_m`) · طول (`length_km`) |
| `tp_museum` | گونه (`classification`) · سال بنیان‌گذاری (`founded_year`) · مدت بازدید (`suggested_duration`) |
| `tp_protected_area` | نوع حفاظت (`park_type`) · مساحت (`area_km2`) · مدت بازدید (`suggested_duration`) |
| `tp_landscape` | نوع منظر (`landscape_type`) · مساحت (`total_area_km2`) · مدت بازدید (`suggested_duration`) |
| `tp_handicraft` | گونه (`classification`) · سال ثبت یونسکو (`intangible_heritage_year`) · وضعیت ثبت (`intangible_heritage_status`) |
| `tp_local_food` | گونه (`classification_`) |
| `tp_costume` | گونه (`classification`) |
| `tp_music` | گونه (`classification`) |
| `tp_dance` | گونه (`dance_type`) |
| `tp_language` | خانوادۀ زبانی (`language_family`) · خط (`script`) · گویشوران در ایران (`speaker_estimate_iran`) · نام بومی (`name_endonym`) |
| `tp_ethnic_group` | جمعیت تخمینی (`population_estimate`) |
| `tp_ritual` | گونه (`classification`) · گستره (`spatial_scope`) · سال ثبت یونسکو (`intangible_heritage_year`) · وضعیت ثبت (`intangible_heritage_status`) |
| `tp_figure` | گونه (`classification`) · سال تولد (`birth_year_approx`) · سال درگذشت (`death_year_approx`) |
| `tp_historical_era` | آغاز (`start_year_approx`) · پایان (`end_year_approx`) |
| `tp_designation` | سال آغاز برنامه (`programme_start_year`) |
| `tp_route` | مسافت (`total_distance_km`) · مدت پیشنهادی (`estimated_duration_days`) · دشواری (`difficulty_level`) · گونه (`classification`) |
| `tp_visa` | حداکثر اقامت (`max_total_stay_days`) · اعتبار گذرنامه (`passport_validity_months`) · اقامت در منطقۀ آزاد (`free_zone_stay_days`) |
| `tp_currency` | کد ISO (`currency_iso_code`) · نماد (`currency_symbol`) · کارت بین‌المللی (`international_card_status`) · بازتعریف واحد پول (`redenomination_status`) |
| `tp_health_safety` | پلیس (`emergency_police`) · اورژانس (`emergency_ambulance`) · آتش‌نشانی (`emergency_fire`) |
| `tp_travel_etiquette` | — (بی فیلد کوتاه؛ نوع wysiwyg) |
| `tp_calendar` | — (بی فیلد کوتاه؛ نوع wysiwyg) |
| `tp_transport` | — (بی فیلد کوتاه؛ نوع wysiwyg) |
| `tp_festival` | تکرار (`event_frequency`) |
| `tp_sports_event` | رشته (`sport_type`) · سطح (`competition_level`) · تکرار (`event_frequency`) |
| `tp_religious_event` | تقویم (`calendar_system`) · مدت مراسم (`ceremony_duration_days`) · گسترۀ حضور (`crowd_scale`) |
| `tp_seasonal_event` | پدیده (`natural_phenomenon_type`) · فصل (`season`) · وابستگی به هوا (`weather_dependency`) |

### جدول به تفکیک نوع (ترتیب پیشنهادی، «پر» = چند پست آن خانه را دارند)

| نوع | پست | خانهٔ ۱ | خانهٔ ۲ | خانهٔ ۳ | خانهٔ ۴ | جایگزین‌ها به ترتیب (پر) | به جایگزین می‌رسد | کمتر از ۴ |
|---|---|---|---|---|---|---|---|---|
| `tp_country` | ۱ | جمعیت (۱) | مساحت (۱) | شمار provinces (۱) | زبان رسمی (۱) | پیش‌شمارۀ تلفن (۱) · نوع حکومت (۱) · دامنۀ اینترنتی (۱) · شمار مکان‌های زیرمجموعه (۱) | ۰ | ۰ |
| `tp_city` | ۱۱۴ | مساحت (۸) | جمعیت (۱۳) | ارتفاع از سطح دریا (۱) | سال بنیان‌گذاری (۰) | استان (والد R01) (۱۱۴) · شهرستان (والد R01) (۱۱۴) · شمار مکان‌های زیرمجموعه (۶) · فاصله تا مرکز استان (۳) | ۱۰ | ۱۰۴ |
| `tp_region` | ۰ | نوع منطقه | شمار provinces | — | — | — | ۰ | ۰ |
| `tp_province` | ۳۱ | مرکز (۱) | مساحت (۳۱) | جمعیت (۳۱) | نوع اقلیم (۱) | شمار مکان‌های زیرمجموعه (۱) | ۰ | ۳۰ |
| `tp_district` | ۰ | نوع محله | — | — | — | — | ۰ | ۰ |
| `tp_village` | ۱ | جمعیت (۱) | ارتفاع از سطح دریا (۱) | استان (والد R01) (۱) | فاصله تا مرکز استان (۱) | — | ۰ | ۰ |
| `tp_island` | ۰ | نوع جزیره | مساحت | — | — | — | ۰ | ۰ |
| `tp_mountain` | ۱ | ارتفاع از سطح دریا (۱) | نوع عارضه (۱) | دشواری صعود (۰) | استان (والد R01) (۱) | — | ۰ | ۱ |
| `tp_water_body` | ۱ | نوع پهنۀ آبی (۱) | مساحت (۱) | طول (۰) | حداکثر عمق (۰) | نوع شوری (۱) · استان (والد R01) (۱) · فاصله تا مرکز استان (۱) | ۱ | ۰ |
| `tp_desert` | ۱ | نوع کویر (۱) | مساحت (۰) | حداکثر دمای ثبت‌شده (۰) | استان (والد R01) (۱) | — | ۰ | ۱ |
| `tp_forest_plain` | ۰ | نوع پهنه | نوع جنگل | مساحت | — | — | ۰ | ۰ |
| `tp_border_crossing` | ۰ | نوع گذرگاه | کشور مقابل | وضعیت | — | — | ۰ | ۰ |
| `tp_monument` | ۱۱ | گونه (۸) | سال ساخت (۲) | مدت بازدید (۰) | وضعیت مرمت (۰) | سبک معماری (۰) · معیار ثبت میراث جهانی (۹) · استان (والد R01) (۵) · شهر (والد R01) (۵) · فاصله تا مرکز استان (۴) | ۳ | ۸ |
| `tp_religious_site` | ۱ | گونه (۱) | دین (۱) | مدت بازدید (۰) | عکاسی (۰) | معیار ثبت میراث جهانی (۱) · استان (والد R01) (۱) · شهر (والد R01) (۱) | ۱ | ۰ |
| `tp_ancient_site` | ۳ | وضعیت کاوش (۰) | سبک معماری (۰) | مدت بازدید (۰) | معیار ثبت میراث جهانی (۳) | استان (والد R01) (۳) · فاصله تا مرکز استان (۳) · شهر (والد R01) (۲) | ۲ | ۱ |
| `tp_natural_feature` | ۰ | گونه | ارتفاع | عمق | طول | مساحت · مدت بازدید | ۰ | ۰ |
| `tp_museum` | ۰ | گونه | سال بنیان‌گذاری | مدت بازدید | — | — | ۰ | ۰ |
| `tp_protected_area` | ۰ | نوع حفاظت | مساحت | مدت بازدید | — | — | ۰ | ۰ |
| `tp_landscape` | ۰ | نوع منظر | مساحت | مدت بازدید | — | — | ۰ | ۰ |
| `tp_handicraft` | ۳ | گونه (۱) | سال ثبت یونسکو (۰) | وضعیت ثبت (۰) | — | — | ۰ | ۳ |
| `tp_local_food` | ۱ | گونه (۱) | — | — | — | — | ۰ | ۱ |
| `tp_costume` | ۰ | گونه | — | — | — | — | ۰ | ۰ |
| `tp_music` | ۰ | گونه | — | — | — | — | ۰ | ۰ |
| `tp_dance` | ۰ | گونه | — | — | — | — | ۰ | ۰ |
| `tp_language` | ۰ | خانوادۀ زبانی | خط | گویشوران در ایران | نام بومی | — | ۰ | ۰ |
| `tp_ethnic_group` | ۰ | جمعیت تخمینی | — | — | — | — | ۰ | ۰ |
| `tp_ritual` | ۰ | گونه | گستره | سال ثبت یونسکو | وضعیت ثبت | — | ۰ | ۰ |
| `tp_figure` | ۰ | گونه | سال تولد | سال درگذشت | — | — | ۰ | ۰ |
| `tp_historical_era` | ۰ | آغاز | پایان | — | — | — | ۰ | ۰ |
| `tp_designation` | ۶ | سال آغاز برنامه (۶) | — | — | — | — | ۰ | ۶ |
| `tp_route` | ۱ | مسافت (۱) | مدت پیشنهادی (۱) | دشواری (۱) | گونه (۱) | — | ۰ | ۰ |
| `tp_visa` | ۰ | حداکثر اقامت | اعتبار گذرنامه | اقامت در منطقۀ آزاد | — | — | ۰ | ۰ |
| `tp_currency` | ۲ | کد ISO (۲) | نماد (۱) | کارت بین‌المللی (۱) | بازتعریف واحد پول (۱) | — | ۰ | ۲ |
| `tp_health_safety` | ۱ | پلیس (۱) | اورژانس (۱) | آتش‌نشانی (۱) | — | — | ۰ | ۱ |
| `tp_travel_etiquette` | ۱ | — | — | — | — | — | ۰ | ۱ |
| `tp_calendar` | ۱ | — | — | — | — | — | ۰ | ۱ |
| `tp_transport` | ۱ | — | — | — | — | — | ۰ | ۱ |
| `tp_festival` | ۱ | تکرار (۱) | — | — | — | — | ۰ | ۱ |
| `tp_sports_event` | ۰ | رشته | سطح | تکرار | — | — | ۰ | ۰ |
| `tp_religious_event` | ۰ | تقویم | مدت مراسم | گسترۀ حضور | — | — | ۰ | ۰ |
| `tp_seasonal_event` | ۰ | پدیده | فصل | وابستگی به هوا | — | — | ۰ | ۰ |

### پست‌هایی که پس از مشتق‌ها هنوز به ۴ نمی‌رسند (نمونه، تا ۵ در هر نوع)

- `tp_city` (۱۰۴): مهاباد (۲) · زواره (۲) · اردستان (۳) · نوش‌آباد (۲) · سفیدشهر (۲) · …
- `tp_province` (۳۰): استان تهران (۲) · یزد (۲) · همدان (۲) · هرمزگان (۲) · مرکزی (۲) · …
- `tp_mountain` (۱): کوه کرکس (۳)
- `tp_desert` (۱): کویر مرنجاب (۲)
- `tp_monument` (۸): کاروانسرای نیستانک (۲) · کاروانسرای مهیار (۲) · کاروانسرای گبرآباد (۲) · کاروانسرای امین‌آباد (۲) · کاروانسرای مرنجاب (۳) · …
- `tp_ancient_site` (۱): قنات مزدآباد (۳)
- `tp_handicraft` (۳): قلم‌زنی (۰) · میناکاری (۰) · خاتم‌کاری (۱)
- `tp_local_food` (۱): گز (۱)
- `tp_designation` (۶): آثار طبیعی ملی ایران (۱) · شهرهای خلاق یونسکو در ایران (۱) · ذخیره‌گاه‌های زیست‌کره یونسکو در ایران (۱) · ژئوپارک‌های جهانی یونسکو در ایران (۱) · میراث فرهنگی ناملموس یونسکو در ایران (۱) · …
- `tp_currency` (۲): ریال ایران (۲) · ارز و روش‌های پرداخت در ایران (۳)
- `tp_health_safety` (۱): شماره‌های اضطراری ایران (۳)
- `tp_travel_etiquette` (۱): پوشش در اماکن عمومی ایران (۰)
- `tp_calendar` (۱): تقویم رسمی ایران (۰)
- `tp_transport` (۱): شبکۀ ریلی ایران (۰)
- `tp_festival` (۱): جشن گلاب‌گیری کاشان (۱)

---
**برای تأیید کاربر:** راه (۱)، (۲) یا (۳)؛ فهرست مشتق‌ها؛ فیلدهای الزامی (ج). پس از تأیید پیاده‌سازی در `topersia_g_type_matrix()` و `topersia_g_stat_cell()` (و الزامی در بستهٔ درون‌ریزی) با ادعای جهش‌دار.

دادهٔ پایه (دستهٔ ۲۲، ویکی‌داده) مختصات، جمعیت، مساحت و ارتفاع را پر می‌کند و این شمارها پس از آن دوباره گرفته می‌شود.
