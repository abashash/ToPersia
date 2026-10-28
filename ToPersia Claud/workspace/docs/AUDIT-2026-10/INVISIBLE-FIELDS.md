# ممیزی «متا یا رابطه‌ای که در محتوا دیده نمی‌شود» — دستهٔ 33-ب · P5-D463 (۲۸ اکتبر ۲۰۲۶)

> فقط‌خواندنی: هیچ کد یا داده‌ای عوض نشد (ابزار ممیزی خارج از مخزن است). منبع: صفحهٔ fa نمونهٔ هر ۴۱ نوع (دستهٔ ۳۰ — `scripts/tp-samples.php seen`، تازه‌اجرا پس از دسته‌های ۳۲ و ۳۳)، FIELDMAP (`topersia_g_map()`)، `docs/AUDIT-2026-10/SAMPLES-SEEN.md`، `META-DISPLAY.csv` (دستهٔ ۲۷) و `RELATIONS-2026-10-25.csv`. جدول کامل ردیف‌به‌ردیف: [INVISIBLE-FIELDS.csv](INVISIBLE-FIELDS.csv).
> روش: نمونه‌ها همهٔ فیلدهای غیرفنی را پر و هر رابطه را با ۲–۳ موجودیت دارند، پس «دیده‌نشده» یعنی صفحه نمایشش نمی‌دهد، نه اینکه داده نباشد. جست‌وجوی «دیده‌شدن» ابتکاری است (نام یا مقدار در متن صفحه، بی script/style).

## خلاصه

| | شمار |
|---|---|
| فیلد عمومی دیده‌نشده (همهٔ ۴۱ نوع) | **184** |
| ↳ خالی در نمونه | 41 |
| ↳ جا ندارد | 93 |
| ↳ پنهان به‌خاطر قاعده | 47 |
| ↳ باگ | 3 |
| رابطهٔ (نوع × سو) دیده‌نشده | **102 از 278** |
| ↳ جا ندارد (نوع بی ناحیهٔ رابطه) | 92 |
| ↳ پنهان به‌خاطر قاعده (مکانی: زنجیره/مرکزیت) | 7 |
| ↳ خالی در نمونه (یال ندارد) | 3 |
| ↳ باگ | 0 |
| رابطهٔ دیده‌شده (دست‌کم یک نام روی صفحه) | 176 |

رابطه‌ها از **هر دو سو** سنجیده شدند: هر رابطه در فهرست هر دو نوعِ طرفینش می‌آید (سوی والد = `children`، سوی فرزند = `parents`). از 143 رابطه: هر دو سو دیده **78**، فقط یک سو **20**، هیچ‌کدام **45** (جدول پایین).

## یافته‌های اصلی

1. **سه فیلد «باگ»:** `tp_monument.venue_capacity`، `tp_currency.redenomination_ratio` و `tp_festival.is_recurring` — پر در نمونه، بی هیچ کلید FIELDMAP و بی هیچ جایی در صفحه، و دستهٔ ۲۷ هم «نه» نوشته بود؛ در دستهٔ ۲۷ پیشنهاد «لازم نیست» نداشتند و در فهرست پذیرفته نیستند. پیشنهاد: تصمیم کاربر — یا کلید FIELDMAP + کاشی (ظرفیت و نسبت تبدیل و تکراری‌بودن) یا ثبت «لازم نیست» در META-DISPLAY. (`plugin/topersia-render/includes/topersia-g-map.php:51`)
2. **93 فیلد «جا ندارد»:** در FIELDMAP نیستند (یا ناحیه‌شان این فیلد را نمی‌چیند) و دستهٔ ۲۷ «لازم نیست تا داده بیاید» نوشته بود. با دادهٔ نمونه «تا داده بیاید» دیگر دلیل نیست. بیشترین: `tp_currency` (18)، `tp_visa` (16)، `tp_health_safety` (6)، `tp_protected_area` (5)، `tp_religious_event` (5)، `tp_transport` (5)، `tp_calendar` (4)، `tp_festival` (4) — در 21 نوع.
3. **92 رابطهٔ (نوع × سو) بی ناحیه:** `topersia_g_relmap()` فقط ۱۲ نوع مکانی را دارد؛ نوع‌های دیگر (فرهنگی، رویداد، مرجع، شخص، …) یال دارند ولی صفحه‌شان بخش «مرتبط» ندارد — جز کاشی «مکان‌های مرتبط» (استان، سپس شهر) و کاشی‌های «اطلاعات جغرافیایی». بیشترین: `tp_figure` (10)، `tp_historical_era` (10)، `tp_monument` (9)، `tp_festival` (8)، `tp_religious_site` (6)، `tp_ancient_site` (5)، `tp_religious_event` (5)، `tp_ritual` (5).
4. **7 رابطهٔ مکانی «پنهان به‌خاطر قاعده»** (`tp_border_crossing` rel82 parents، `tp_city` rel95 parents، `tp_city` rel96 parents، `tp_mountain` rel101 parents، `tp_province` rel71 parents، `tp_village` rel75 parents، `tp_village` rel76 parents): والد و مرکزیت فقط از زنجیرهٔ مکانی R01 می‌آیند، نه از خودِ یال. نمونه‌های ما یال R01 واقعی نمی‌سازند، پس والد روی مسیر راهنما نمی‌آید — قاعده است، نه باگ (`plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240`).

## فیلدها — به تفکیک نوع و علت

| نوع | خالی در نمونه | جا ندارد | پنهان به‌خاطر قاعده | باگ |
|---|---|---|---|---|
| `tp_ancient_site` | 1 | 3 | 1 | 0 |
| `tp_border_crossing` | 1 | 0 | 1 | 0 |
| `tp_calendar` | 1 | 4 | 2 | 0 |
| `tp_city` | 1 | 0 | 1 | 0 |
| `tp_costume` | 1 | 1 | 1 | 0 |
| `tp_country` | 1 | 0 | 3 | 0 |
| `tp_currency` | 1 | 18 | 1 | 1 |
| `tp_dance` | 1 | 1 | 1 | 0 |
| `tp_desert` | 1 | 0 | 1 | 0 |
| `tp_designation` | 1 | 0 | 1 | 0 |
| `tp_district` | 1 | 0 | 1 | 0 |
| `tp_ethnic_group` | 1 | 0 | 1 | 0 |
| `tp_festival` | 1 | 4 | 1 | 1 |
| `tp_figure` | 1 | 0 | 1 | 0 |
| `tp_forest_plain` | 1 | 0 | 1 | 0 |
| `tp_handicraft` | 1 | 2 | 1 | 0 |
| `tp_health_safety` | 1 | 6 | 1 | 0 |
| `tp_historical_era` | 1 | 1 | 1 | 0 |
| `tp_island` | 1 | 0 | 1 | 0 |
| `tp_landscape` | 1 | 0 | 1 | 0 |
| `tp_language` | 1 | 3 | 1 | 0 |
| `tp_local_food` | 1 | 1 | 1 | 0 |
| `tp_monument` | 1 | 0 | 3 | 1 |
| `tp_mountain` | 1 | 0 | 1 | 0 |
| `tp_museum` | 1 | 3 | 1 | 0 |
| `tp_music` | 1 | 0 | 1 | 0 |
| `tp_natural_feature` | 1 | 3 | 1 | 0 |
| `tp_protected_area` | 1 | 5 | 1 | 0 |
| `tp_province` | 1 | 0 | 2 | 0 |
| `tp_region` | 1 | 0 | 1 | 0 |
| `tp_religious_event` | 1 | 5 | 1 | 0 |
| `tp_religious_site` | 1 | 0 | 1 | 0 |
| `tp_ritual` | 1 | 4 | 1 | 0 |
| `tp_route` | 1 | 0 | 1 | 0 |
| `tp_seasonal_event` | 1 | 4 | 1 | 0 |
| `tp_sports_event` | 1 | 2 | 1 | 0 |
| `tp_transport` | 1 | 5 | 1 | 0 |
| `tp_travel_etiquette` | 1 | 2 | 1 | 0 |
| `tp_village` | 1 | 0 | 1 | 0 |
| `tp_visa` | 1 | 16 | 1 | 0 |
| `tp_water_body` | 1 | 0 | 1 | 0 |

### فهرست کامل فیلدها (نوع · کلید · علت · دلیل — پرونده:خط)

| نوع | کلید | برچسب | علت | دلیل | ارجاع |
|---|---|---|---|---|---|
| `tp_ancient_site` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_ancient_site` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_ancient_site,ai_summary)` |
| `tp_ancient_site` | `has_standing_structure` | دارای سازهٔ ایستاده | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_ancient_site` | `related_museum` | موزهٔ مرتبط | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_ancient_site` | `visiting_permit` | نیاز به مجوز بازدید | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_border_crossing` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_border_crossing` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_border_crossing,ai_summary)` |
| `tp_calendar` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_calendar` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_calendar,ai_summary)` |
| `tp_calendar` | `calendar_system_note` | توضیح نظام تقویمی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_calendar` | `closure_impact_note` | اثر تعطیلات بر خدمات | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_calendar` | `crowding_and_price_note` | ازدحام و نوسان قیمت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_calendar` | `best_worst_time_note` | بهترین و بدترین زمان سفر | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_calendar` | `lunar_date_disclaimer` | هشدار تاریخ‌های قمری | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_calendar,lunar_date_disclaimer)` |
| `tp_city` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_city` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_city,ai_summary)` |
| `tp_costume` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_costume` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_costume,ai_summary)` |
| `tp_costume` | `garment_components` | اجزای لباس | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_country` | `name_en` | نام (انگلیسی) | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_country,name_en)` |
| `tp_country` | `name_ar` | نام (عربی) | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_country,name_ar)` |
| `tp_country` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_country` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_country,ai_summary)` |
| `tp_currency` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_currency` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_currency,ai_summary)` |
| `tp_currency` | `unit_confusion_explainer` | توضیح ریال در برابر تومان | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `price_reading_rule` | قاعدهٔ خواندن قیمت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `redenomination_ratio` | نسبت تبدیل بازتعریف | باگ | یافته — META-DISPLAY: نه · کلید در FIELDMAP نیست | `plugin/topersia-render/includes/topersia-g-map.php:51` |
| `tp_currency` | `redenomination_end_date` | پایان دورهٔ گذار | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `dual_pricing_active` | برچسب‌گذاری دوگانهٔ قیمت فعال؟ | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `redenomination_note` | شرح کامل بازتعریف | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `denominations` | اسکناس و مسکوکات | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `card_acceptance_summary` | پاسخ صریح دربارهٔ کارت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `atm_usage_note` | استفاده از خودپرداز | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `domestic_network_note` | شبکهٔ کارت داخلی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `tourist_card_available` | کارت گردشگری موجود است؟ | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `banknote_condition_rule` | قاعدهٔ سلامت اسکناس | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `exchange_channel_notes` | کانال‌های تبدیل و رسید | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `declaration_rules_note` | قواعد اظهار ارز در گمرک | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `rate_regime_note` | رژیم نرخ ارز | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `cash_recommendation_note` | توصیهٔ میزان پول نقد | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `daily_budget_bands` | بازه‌های بودجهٔ روزانه | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `digital_payment_note` | پرداخت دیجیتال داخلی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_currency` | `regional_notes` | یادداشت‌های استانی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_dance` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_dance` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_dance,ai_summary)` |
| `tp_dance` | `ritual_context_reference` | زمینهٔ آیینی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_desert` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_desert` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_desert,ai_summary)` |
| `tp_designation` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_designation` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_designation,ai_summary)` |
| `tp_district` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_district` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_district,ai_summary)` |
| `tp_ethnic_group` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_ethnic_group` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_ethnic_group,ai_summary)` |
| `tp_festival` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_festival` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_festival,ai_summary)` |
| `tp_festival` | `is_recurring` | رویداد تکرارشونده است؟ | باگ | یافته — META-DISPLAY: نه · کلید در FIELDMAP نیست | `plugin/topersia-render/includes/topersia-g-map.php:51` |
| `tp_festival` | `program_highlights` | بخش‌های برنامه | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_festival` | `event_travel_impact` | اثرگذار بر برنامه‌ریزی سفر؟ | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_festival` | `admission_note` | شرایط ورود و بلیت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_festival` | `tourist_suitability_note` | مناسب‌بودن برای گردشگر | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_figure` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_figure` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_figure,ai_summary)` |
| `tp_forest_plain` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_forest_plain` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_forest_plain,ai_summary)` |
| `tp_handicraft` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_handicraft` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_handicraft,ai_summary)` |
| `tp_handicraft` | `intangible_heritage_designation` | ثبت میراث ناملموس | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_handicraft` | `intangible_heritage_criteria` | معیارهای ثبت ناملموس | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_health_safety` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_health_safety` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_health_safety,ai_summary)` |
| `tp_health_safety` | `natural_hazard_summary` | مخاطرات طبیعی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_health_safety` | `general_safety_notes` | نکات کلی ایمنی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_health_safety` | `water_safety_note` | ایمنی آب آشامیدنی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_health_safety` | `regional_health_notes` | نکات سلامت منطقه‌ای | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_health_safety` | `local_emergency_facilities` | مراکز درمانی محلی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_health_safety` | `activity_specific_warnings` | هشدارهای فعالیت‌محور | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_historical_era` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_historical_era` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_historical_era,ai_summary)` |
| `tp_historical_era` | `predecessor_reference` | سلسلهٔ پیشین | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_island` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_island` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_island,ai_summary)` |
| `tp_landscape` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_landscape` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_landscape,ai_summary)` |
| `tp_language` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_language` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_language,ai_summary)` |
| `tp_language` | `is_official_language` | زبان رسمی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_language` | `dialect_variants` | گونه‌های گویشی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_language` | `ethnicity_reference` | اقوام گویشور | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_local_food` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_local_food` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_local_food,ai_summary)` |
| `tp_local_food` | `recipe_content` | دستور پخت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_monument` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_monument` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_monument,ai_summary)` |
| `tp_monument` | `venue_eligible` | قابلیت میزبانی رویداد | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_monument,venue_eligible)` |
| `tp_monument` | `a1_classification` | طبقه‌بندی عمومی جاذبه | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_monument,a1_classification)` |
| `tp_monument` | `venue_capacity` | ظرفیت میزبانی | باگ | یافته — META-DISPLAY: نه · کلید در FIELDMAP نیست | `plugin/topersia-render/includes/topersia-g-map.php:51` |
| `tp_mountain` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_mountain` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_mountain,ai_summary)` |
| `tp_museum` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_museum` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_museum,ai_summary)` |
| `tp_museum` | `housed_in` | مستقر در بنای | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_museum` | `collection_categories` | دسته‌های مجموعه | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_museum` | `permanent_highlights` | آثار شاخص | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_music` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_music` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_music,ai_summary)` |
| `tp_natural_feature` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_natural_feature` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_natural_feature,ai_summary)` |
| `tp_natural_feature` | `water_temperature` | دمای آب | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_natural_feature` | `safety_notes` | نکات ایمنی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_natural_feature` | `activities` | فعالیت‌های مجاز | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_protected_area` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_protected_area` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_protected_area,ai_summary)` |
| `tp_protected_area` | `managed_area` | منطقهٔ مدیریت‌شده | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_protected_area` | `entry_permit_required` | نیاز به مجوز ورود | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_protected_area` | `key_species` | گونه‌های شاخص | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_protected_area` | `geopark` | ژئوپارک جهانی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_protected_area` | `activities` | فعالیت‌های مجاز | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_province` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_province` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_province,ai_summary)` |
| `tp_province` | `province_code` | کد استان | پنهان به‌خاطر قاعده | FIELDMAP zone=skip: فیلد و داده می‌ماند ولی عمداً نمایش داده نمی‌شود | `plugin/topersia-render/includes/topersia-g-map.php:78` |
| `tp_region` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_region` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_region,ai_summary)` |
| `tp_religious_event` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_religious_event` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_religious_event,ai_summary)` |
| `tp_religious_event` | `annual_date_rule` | قاعدهٔ تاریخ سالانه | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_religious_event` | `public_participation_note` | مشارکت عمومی و حضور بازدیدکننده | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_religious_event` | `visitor_etiquette_note` | آداب حضور | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_religious_event` | `photography_note` | یادداشت عکاسی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_religious_event` | `dress_code_note` | پوشش و ظاهر | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_religious_site` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_religious_site` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_religious_site,ai_summary)` |
| `tp_ritual` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_ritual` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_ritual,ai_summary)` |
| `tp_ritual` | `intangible_heritage_designation` | ثبت میراث ناملموس | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_ritual` | `intangible_heritage_criteria` | معیارهای ثبت ناملموس | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_ritual` | `ceremonial_food_reference` | غذاهای مناسبتی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_ritual` | `external_recognition_uris` | شناسایی فراملی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_route` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_route` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_route,ai_summary)` |
| `tp_seasonal_event` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_seasonal_event` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_seasonal_event,ai_summary)` |
| `tp_seasonal_event` | `peak_start_week` | هفتهٔ آغاز اوج | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_seasonal_event` | `peak_end_week` | هفتهٔ پایان اوج | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_seasonal_event` | `optimal_conditions` | شرایط بهینهٔ مشاهده | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_seasonal_event` | `predictability_note` | پایداری سال‌به‌سال | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_sports_event` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_sports_event` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_sports_event,ai_summary)` |
| `tp_sports_event` | `is_recurring` | رویداد تکرارشونده است؟ | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_sports_event` | `spectator_access_note` | دسترسی تماشاگر | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_transport` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_transport` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_transport,ai_summary)` |
| `tp_transport` | `mode_choice_guide` | راهنمای انتخاب حالت سفر | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_transport` | `intercity_bus_note` | اتوبوس بین‌شهری | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_transport` | `air_travel_note` | پرواز داخلی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_transport` | `transport_etiquette_note` | آداب حمل‌ونقل عمومی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_transport` | `route_reference_note` | ارجاع به مسیرهای گردشگری | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_travel_etiquette` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_travel_etiquette` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_travel_etiquette,ai_summary)` |
| `tp_travel_etiquette` | `religious_site_etiquette` | آداب اماکن مذهبی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_travel_etiquette` | `social_customs_summary` | آداب اجتماعی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_village` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_village` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_village,ai_summary)` |
| `tp_visa` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_visa` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_visa,ai_summary)` |
| `tp_visa` | `guided_tour_note` | قواعد تور اجباری | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `free_zone_mainland_allowed` | اجازهٔ ورود به سرزمین اصلی؟ | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `free_zone_excluded_iso` | کشورهای مستثنا (کد ISO) | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `free_zone_note` | توضیح رژیم مناطق آزاد | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `application_process_summary` | خلاصهٔ فرآیند درخواست | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `voa_entry_points` | مبادی صدور روادید فرودگاهی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `entry_points_reference` | سایر مبادی ورودی (ارجاع محتوایی) | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `insurance_required` | بیمهٔ سلامت ایرانی اجباری؟ | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `insurance_note` | توضیح بیمهٔ اجباری | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `passport_stamping_note` | سیاست مهر پاسپورت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `dual_nationality_note` | قواعد دوتابعیتی | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `restricted_entry_note` | محدودیت‌های مطلق ورود | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `special_passport_note` | معافیت پاسپورت‌های دیپلماتیک/خدمت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `extension_info` | تمدید و اضافه‌اقامت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `fee_currency` | واحد پول رسمی هزینه | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_visa` | `fee_policy_note` | سیاست هزینه و پرداخت | جا ندارد | کلید در FIELDMAP نیست؛ هیچ ناحیه‌ای نمی‌خواندش | `plugin/topersia-render/includes/topersia-g-map.php:51 (topersia_g_map)` |
| `tp_water_body` | `external_reference_uri` | نشانی مرجع خارجی | خالی در نمونه | sameAs ساختگی گمراه می‌کند؛ نمونه عمداً خالی | `scripts/tp-samples.php` |
| `tp_water_body` | `ai_summary` | خلاصه برای هوش مصنوعی | پنهان به‌خاطر قاعده | لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) | `docs/AUDIT-2026-10/META-DISPLAY.csv (سطر tp_water_body,ai_summary)` |

## رابطه‌ها — به تفکیک نوع

| نوع | جا ندارد | پنهان به‌خاطر قاعده | خالی در نمونه |
|---|---|---|---|
| `tp_ancient_site` | 5 | 0 | 0 |
| `tp_border_crossing` | 0 | 1 | 0 |
| `tp_calendar` | 2 | 0 | 0 |
| `tp_city` | 1 | 2 | 1 |
| `tp_costume` | 1 | 0 | 0 |
| `tp_dance` | 1 | 0 | 0 |
| `tp_ethnic_group` | 4 | 0 | 0 |
| `tp_festival` | 8 | 0 | 0 |
| `tp_figure` | 10 | 0 | 0 |
| `tp_handicraft` | 1 | 0 | 0 |
| `tp_historical_era` | 10 | 0 | 0 |
| `tp_landscape` | 3 | 0 | 0 |
| `tp_local_food` | 1 | 0 | 0 |
| `tp_monument` | 9 | 0 | 0 |
| `tp_mountain` | 0 | 1 | 1 |
| `tp_museum` | 2 | 0 | 0 |
| `tp_music` | 1 | 0 | 0 |
| `tp_natural_feature` | 3 | 0 | 0 |
| `tp_protected_area` | 3 | 0 | 0 |
| `tp_province` | 2 | 1 | 1 |
| `tp_religious_event` | 5 | 0 | 0 |
| `tp_religious_site` | 6 | 0 | 0 |
| `tp_ritual` | 5 | 0 | 0 |
| `tp_route` | 4 | 0 | 0 |
| `tp_sports_event` | 4 | 0 | 0 |
| `tp_travel_etiquette` | 1 | 0 | 0 |
| `tp_village` | 0 | 2 | 0 |

### ماتریس دو سو (هر رابطه: سوی والد · سوی فرزند)

«دیده» = دست‌کم یک نام موجودیتِ مرتبط روی صفحهٔ نمونهٔ آن نوع؛ «نادیده» = یال هست و نام نیست؛ «بی یال» = نمونه یال ندارد.

| رابطه | برچسب | سوی والد (`children`) | سوی فرزند (`parents`) |
|---|---|---|---|
| 71 | کشور | tp_country · دیده | tp_province · نادیده |
| 72 | شهرستان‌های این استان | tp_province · بی یال | — · — |
| 73 | شهرستان | — · — | tp_city · دیده |
| 74 | شهر | tp_city · دیده | tp_district · دیده |
| 75 | استان | tp_province · دیده | tp_village · نادیده |
| 76 | شهرستان | — · — | tp_village · نادیده |
| 77 | جزیره‌های این استان | tp_province · دیده | tp_island · دیده |
| 78 | کوه‌های این استان | tp_province · دیده | tp_mountain · دیده |
| 79 | استان | tp_province · دیده | tp_water_body · دیده |
| 80 | کویرهای این استان | tp_province · دیده | tp_desert · دیده |
| 81 | جنگل‌ها و دشت‌های این استان | tp_province · دیده | tp_forest_plain · دیده |
| 82 | گذرگاه‌های مرزی این استان | tp_province · نادیده | tp_border_crossing · نادیده |
| 83 | استان‌های این منطقه | tp_region · دیده | tp_province · دیده |
| 84 | شهر | tp_city · دیده | tp_monument · دیده |
| 85 | بناهای تاریخی این روستا | tp_village · دیده | tp_monument · دیده |
| 86 | شهر | tp_city · دیده | tp_religious_site · دیده |
| 87 | مکان‌های مذهبی این روستا | tp_village · دیده | tp_religious_site · دیده |
| 88 | شهر | tp_city · دیده | tp_museum · دیده |
| 89 | پارک‌های ملی این استان | tp_province · دیده | tp_protected_area · دیده |
| 90 | عارضه‌های طبیعی این استان | tp_province · دیده | tp_natural_feature · نادیده |
| 91 | شهر | tp_city · دیده | tp_natural_feature · دیده |
| 92 | محوطه‌های باستانی این استان | tp_province · دیده | tp_ancient_site · نادیده |
| 93 | محوطه‌های باستانی این شهر | tp_city · دیده | tp_ancient_site · دیده |
| 94 | مناظر فرهنگی این استان | tp_province · دیده | tp_landscape · دیده |
| 95 | پایتخت | tp_country · دیده | tp_city · نادیده |
| 96 | مرکز استان | tp_province · دیده | tp_city · نادیده |
| 97 | مرکز این شهرستان | — · — | tp_city · بی یال |
| 98 | نزدیک‌ترین شهر | tp_city · دیده | tp_village · دیده |
| 100 | گذرگاه‌های مرزی نزدیک | tp_city · دیده | tp_border_crossing · دیده |
| 101 | رشته‌کوه مادر | tp_mountain · بی یال | tp_mountain · نادیده |
| 102 | استان‌های هم‌مرز | tp_province · دیده | tp_province · دیده |
| 103 | عارضه‌های طبیعی درون این منطقه | tp_protected_area · نادیده | tp_natural_feature · نادیده |
| 105 | منظر فرهنگی دربرگیرنده | tp_landscape · نادیده | tp_monument · نادیده |
| 106 | منظر فرهنگی دربرگیرنده | tp_landscape · نادیده | tp_natural_feature · نادیده |
| 107 | صنایع دستی رایج در این استان | tp_province · دیده | tp_handicraft · دیده |
| 108 | محل رواج (شهر) | tp_city · دیده | tp_handicraft · دیده |
| 109 | صنایع دستی رایج در این روستا | tp_village · دیده | tp_handicraft · دیده |
| 110 | غذاهای محلی رایج در این استان | tp_province · دیده | tp_local_food · دیده |
| 111 | محل رواج (شهر) | tp_city · دیده | tp_local_food · دیده |
| 112 | غذاهای محلی رایج در این روستا | tp_village · دیده | tp_local_food · دیده |
| 113 | لباس‌های سنتی رایج در این استان | tp_province · دیده | tp_costume · دیده |
| 114 | محل رواج (شهر) | tp_city · دیده | tp_costume · دیده |
| 116 | موسیقی‌های محلی رایج در این استان | tp_province · دیده | tp_music · دیده |
| 117 | محل رواج (شهر) | tp_city · دیده | tp_music · دیده |
| 118 | موسیقی‌های محلی رایج در این روستا | tp_village · دیده | tp_music · دیده |
| 119 | رقص‌های محلی رایج در این استان | tp_province · دیده | tp_dance · دیده |
| 120 | محل رواج (شهر) | tp_city · دیده | tp_dance · دیده |
| 121 | رقص‌های محلی رایج در این روستا | tp_village · دیده | tp_dance · دیده |
| 122 | محل رواج (استان) | tp_province · دیده | tp_ritual · دیده |
| 123 | محل رواج (شهر) | tp_city · دیده | tp_ritual · دیده |
| 124 | آیین‌ها و مراسم رایج در این روستا | tp_village · دیده | tp_ritual · دیده |
| 125 | صنایع دستی تولیدشده در این استان | tp_province · دیده | tp_handicraft · دیده |
| 126 | محل تولید (شهر) | tp_city · دیده | tp_handicraft · دیده |
| 127 | صنایع دستی تولیدشده در این روستا | tp_village · دیده | tp_handicraft · دیده |
| 128 | غذاهای محلی تولیدشده در این استان | tp_province · دیده | tp_local_food · دیده |
| 129 | محل تولید (شهر) | tp_city · دیده | tp_local_food · دیده |
| 130 | غذاهای محلی تولیدشده در این روستا | tp_village · دیده | tp_local_food · دیده |
| 131 | زبان‌ها و گویش‌های رایج در این استان | tp_province · دیده | tp_language · دیده |
| 132 | محل رواج (شهر) | tp_city · دیده | tp_language · دیده |
| 133 | قوم مرتبط | tp_ethnic_group · دیده | tp_handicraft · نادیده |
| 134 | قوم مرتبط | tp_ethnic_group · دیده | tp_local_food · نادیده |
| 135 | قوم مرتبط | tp_ethnic_group · نادیده | tp_music · نادیده |
| 136 | رقص‌های محلی این قوم | tp_ethnic_group · نادیده | tp_dance · نادیده |
| 137 | قوم مرتبط | tp_ethnic_group · نادیده | tp_ritual · نادیده |
| 138 | پوشاک سنتی این قوم | tp_ethnic_group · نادیده | tp_costume · نادیده |
| 139 | دورهٔ تاریخی | tp_historical_era · نادیده | tp_monument · نادیده |
| 140 | دورهٔ تاریخی | tp_historical_era · نادیده | tp_religious_site · نادیده |
| 141 | محوطه‌های باستانی این دوره | tp_historical_era · نادیده | tp_ancient_site · نادیده |
| 142 | شخصیت‌های تاریخی این دوره | tp_historical_era · نادیده | tp_figure · نادیده |
| 143 | شهرها این دوره | tp_historical_era · دیده | tp_city · دیده |
| 144 | محله‌ها این دوره | tp_historical_era · نادیده | tp_district · دیده |
| 145 | دورهٔ تاریخی | tp_historical_era · نادیده | tp_landscape · نادیده |
| 146 | بانی / سازنده | tp_figure · نادیده | tp_monument · نادیده |
| 147 | محوطه‌های ساخته‌شده توسط این شخصیت | tp_figure · نادیده | tp_ancient_site · نادیده |
| 148 | شخصیت مرتبط | tp_figure · نادیده | tp_monument · نادیده |
| 149 | شخصیت مرتبط | tp_figure · نادیده | tp_religious_site · نادیده |
| 150 | محوطه‌های باستانی مرتبط با این شخصیت | tp_figure · نادیده | tp_ancient_site · نادیده |
| 152 | شخصیت مرتبط | tp_figure · نادیده | tp_ritual · نادیده |
| 153 | زادگاه | tp_city · دیده | tp_figure · دیده |
| 154 | محل درگذشت | tp_city · دیده | tp_figure · دیده |
| 156 | معتبر برای (کشور) | tp_country · دیده | tp_health_safety · دیده |
| 157 | اطلاعات سلامت و ایمنی این استان | tp_province · دیده | tp_health_safety · دیده |
| 158 | معتبر برای (شهر) | tp_city · دیده | tp_health_safety · دیده |
| 159 | معتبر برای (کشور) | tp_country · دیده | tp_travel_etiquette · دیده |
| 160 | معتبر برای (استان) | tp_province · دیده | tp_travel_etiquette · دیده |
| 162 | مکان‌های مشمول این قوانین | tp_travel_etiquette · نادیده | tp_religious_site · نادیده |
| 172 | استان‌هایی که این مسیر از آن‌ها می‌گذرد | tp_route · نادیده | tp_province · دیده |
| 173 | شهرهایی که این مسیر از آن‌ها می‌گذرد | tp_route · دیده | tp_city · دیده |
| 174 | مسیرهای گردشگری عبوری از این روستا | tp_route · نادیده | tp_village · دیده |
| 175 | جزیره‌هایی که این مسیر از آن‌ها می‌گذرد | tp_route · نادیده | tp_island · دیده |
| 177 | گذرگاه‌های مرزی که این مسیر به آن‌ها وصل می‌شود | tp_route · نادیده | tp_border_crossing · دیده |
| 185 | کشور | tp_country · دیده | tp_visa · دیده |
| 226 | کشور | tp_country · دیده | tp_currency · دیده |
| 227 | راهنمای ارز و روش‌های پرداخت | tp_province · دیده | tp_currency · دیده |
| 228 | تقویم و مناسبت‌های رسمی | tp_country · دیده | tp_calendar · دیده |
| 229 | تقویم و مناسبت‌های رسمی | tp_province · دیده | tp_calendar · دیده |
| 230 | تقویم و مناسبت‌های رسمی | tp_city · دیده | tp_calendar · دیده |
| 231 | تقویم و مناسبت‌های رسمی | tp_calendar · نادیده | tp_ritual · نادیده |
| 232 | جشنواره و رویداد | tp_province · دیده | tp_festival · دیده |
| 233 | شهر | tp_city · دیده | tp_festival · دیده |
| 234 | جشنواره و رویداد — محل برگزاری | tp_village · دیده | tp_festival · دیده |
| 235 | جشنواره و رویداد — محل برگزاری | tp_monument · نادیده | tp_festival · نادیده |
| 236 | جشنواره و رویداد — محل برگزاری | tp_religious_site · نادیده | tp_festival · نادیده |
| 237 | جشنواره و رویداد — محل برگزاری | tp_museum · نادیده | tp_festival · نادیده |
| 238 | جشنواره و رویداد — محل برگزاری | tp_protected_area · نادیده | tp_festival · نادیده |
| 240 | جشنواره و رویداد — نمونهٔ این آیین | tp_ritual · نادیده | tp_festival · نادیده |
| 242 | جشنواره و رویداد — گرامیداشت | tp_figure · نادیده | tp_festival · نادیده |
| 243 | جشنواره و رویداد — گرامیداشت | tp_historical_era · نادیده | tp_festival · نادیده |
| 244 | تقویم و مناسبت‌های رسمی | tp_calendar · نادیده | tp_festival · نادیده |
| 245 | استان | tp_province · دیده | tp_sports_event · دیده |
| 246 | شهر | tp_city · دیده | tp_sports_event · دیده |
| 247 | رویداد ورزشی — محل برگزاری | tp_village · دیده | tp_sports_event · دیده |
| 248 | بنای تاریخی | tp_monument · نادیده | tp_sports_event · نادیده |
| 249 | مکان مذهبی | tp_religious_site · نادیده | tp_sports_event · نادیده |
| 250 | موزه | tp_museum · نادیده | tp_sports_event · نادیده |
| 251 | پارک ملی | tp_protected_area · نادیده | tp_sports_event · نادیده |
| 253 | استان | tp_province · دیده | tp_religious_event · دیده |
| 254 | شهر | tp_city · دیده | tp_religious_event · دیده |
| 255 | مراسم مذهبی عمومی — محل برگزاری | tp_village · دیده | tp_religious_event · دیده |
| 256 | بنای تاریخی | tp_monument · نادیده | tp_religious_event · نادیده |
| 257 | مراسم مذهبی عمومی — محل برگزاری | tp_religious_site · نادیده | tp_religious_event · نادیده |
| 259 | مراسم مذهبی عمومی — نمونهٔ این آیین | tp_ritual · نادیده | tp_religious_event · نادیده |
| 261 | شخصیت تاریخی | tp_figure · نادیده | tp_religious_event · نادیده |
| 262 | دوره / سلسلهٔ تاریخی | tp_historical_era · نادیده | tp_religious_event · نادیده |
| 263 | استان | tp_province · دیده | tp_seasonal_event · دیده |
| 264 | شهر | tp_city · دیده | tp_seasonal_event · دیده |
| 265 | رویداد طبیعی و فصلی | tp_village · دیده | tp_seasonal_event · دیده |
| 268 | خبر | — · — | tp_province · دیده |
| 269 | خبر | — · — | tp_city · دیده |
| 270 | خبر | — · — | tp_village · دیده |
| 271 | خبر | — · — | tp_island · دیده |
| 293 | فرودگاه‌های اصلی این استان | tp_province · دیده | tp_city · نادیده |
| 294 | شهر آغاز مسیر | tp_city · دیده | tp_route · دیده |
| 295 | شهر پایان مسیر | tp_city · دیده | tp_route · دیده |
| 296 | جای خرید | tp_city · دیده | tp_handicraft · دیده |
| 297 | جای چشیدن | tp_city · دیده | tp_local_food · دیده |
| 298 | جای شنیدن | tp_city · دیده | tp_music · دیده |
| 299 | آرامگاه این شخصیت‌ها | tp_monument · نادیده | tp_figure · نادیده |
| 300 | دورۀ تاریخی | tp_historical_era · نادیده | tp_monument · نادیده |
| 301 | محوطه‌های شاخص این دوره | tp_historical_era · نادیده | tp_ancient_site · نادیده |
| 302 | اقوام ساکن این استان | tp_province · نادیده | tp_ethnic_group · دیده |
| 303 | رقص‌های رایج در این استان | tp_province · دیده | tp_dance · دیده |
| 304 | زبان‌ها و گویش‌های رایج در این استان | tp_province · دیده | tp_language · دیده |

### فهرست کامل رابطه‌های نادیده (نوع · سو · علت · دلیل — پرونده:خط)

| نوع | رابطه · سو | برچسب | نوع رابطه | علت | ارجاع |
|---|---|---|---|---|---|
| `tp_ancient_site` | rel92 parents | استان | one_to_many → `tp_province` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ancient_site` | rel141 parents | دورهٔ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ancient_site` | rel147 parents | بانی / سازنده | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ancient_site` | rel150 parents | شخصیت مرتبط | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ancient_site` | rel301 parents | دورۀ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_border_crossing` | rel82 parents | استان | one_to_many → `tp_province` | پنهان به‌خاطر قاعده | `plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240 · plugin/topersia-render/includes/topersia-g-relreg.php:256 · plugin/topersia-render/includes/topersia-g-relreg.php:344` |
| `tp_calendar` | rel231 children | آیین‌های اثرگذار بر برنامه‌ریزی سفر | many_to_many → `tp_ritual` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_calendar` | rel244 children | رویدادهای اثرگذار بر برنامه‌ریزی سفر | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_city` | rel95 parents | پایتخت کشور | one_to_one → `tp_country` | پنهان به‌خاطر قاعده | `plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240 · plugin/topersia-render/includes/topersia-g-relreg.php:256 · plugin/topersia-render/includes/topersia-g-relreg.php:344` |
| `tp_city` | rel96 parents | مرکز این استان | one_to_one → `tp_province` | پنهان به‌خاطر قاعده | `plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240 · plugin/topersia-render/includes/topersia-g-relreg.php:256 · plugin/topersia-render/includes/topersia-g-relreg.php:344` |
| `tp_city` | rel97 parents | مرکز این شهرستان | one_to_one → `tp_county` | خالی در نمونه | `tests/samples-local.php (کمبود دلیل‌دار)` |
| `tp_city` | rel293 parents | فرودگاه اصلی استان | many_to_many → `tp_province` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_costume` | rel138 parents | قوم پوشنده | many_to_many → `tp_ethnic_group` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_dance` | rel136 parents | قوم مرتبط | many_to_many → `tp_ethnic_group` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ethnic_group` | rel135 children | موسیقی‌های محلی این قوم | many_to_many → `tp_music` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ethnic_group` | rel136 children | رقص‌های محلی این قوم | many_to_many → `tp_dance` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ethnic_group` | rel137 children | آیین‌ها و مراسم این قوم | many_to_many → `tp_ritual` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ethnic_group` | rel138 children | پوشاک سنتی این قوم | many_to_many → `tp_costume` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel235 parents | بنای تاریخی | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel236 parents | مکان مذهبی | many_to_many → `tp_religious_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel237 parents | موزه | many_to_many → `tp_museum` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel238 parents | پارک ملی | many_to_many → `tp_protected_area` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel240 parents | آیین مرجع — این رویداد نمونهٔ آن است | many_to_many → `tp_ritual` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel242 parents | شخصیت تاریخی | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel243 parents | دوره / سلسلهٔ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_festival` | rel244 parents | تقویم و مناسبت‌های رسمی | many_to_many → `tp_calendar` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel142 parents | دورهٔ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel146 children | بناهای ساخته‌شده توسط این شخصیت | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel147 children | محوطه‌های ساخته‌شده توسط این شخصیت | many_to_many → `tp_ancient_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel148 children | بناهای تاریخی مرتبط با این شخصیت | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel149 children | مکان‌های مذهبی مرتبط با این شخصیت | many_to_many → `tp_religious_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel150 children | محوطه‌های باستانی مرتبط با این شخصیت | many_to_many → `tp_ancient_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel152 children | آیین‌ها و مراسم مرتبط با این شخصیت | many_to_many → `tp_ritual` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel242 children | جشنواره و رویداد — گرامیداشت | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel261 children | مراسم مذهبی عمومی — گرامیداشت | many_to_many → `tp_religious_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_figure` | rel299 parents | آرامگاه | one_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_handicraft` | rel133 parents | قوم مرتبط | many_to_many → `tp_ethnic_group` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel139 children | بناهای تاریخی این دوره | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel140 children | مکان‌های مذهبی این دوره | many_to_many → `tp_religious_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel141 children | محوطه‌های باستانی این دوره | many_to_many → `tp_ancient_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel142 children | شخصیت‌های تاریخی این دوره | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel144 children | محله‌ها این دوره | many_to_many → `tp_district` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel145 children | مناظر فرهنگی این دوره | many_to_many → `tp_landscape` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel243 children | جشنواره و رویداد — گرامیداشت | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel262 children | مراسم مذهبی عمومی — گرامیداشت | many_to_many → `tp_religious_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel300 children | بناهای شاخص این دوره | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_historical_era` | rel301 children | محوطه‌های شاخص این دوره | many_to_many → `tp_ancient_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_landscape` | rel105 children | بناهای سازندهٔ این منظر | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_landscape` | rel106 children | عارضه‌های سازندهٔ این منظر | many_to_many → `tp_natural_feature` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_landscape` | rel145 parents | دورهٔ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_local_food` | rel134 parents | قوم مرتبط | many_to_many → `tp_ethnic_group` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel105 parents | منظر فرهنگی دربرگیرنده | many_to_many → `tp_landscape` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel139 parents | دورهٔ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel146 parents | بانی / سازنده | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel148 parents | شخصیت مرتبط | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel235 children | جشنواره و رویداد — محل برگزاری | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel248 children | رویداد ورزشی — محل برگزاری | many_to_many → `tp_sports_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel256 children | مراسم مذهبی عمومی — محل برگزاری | many_to_many → `tp_religious_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel299 children | آرامگاه این شخصیت‌ها | one_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_monument` | rel300 parents | دورۀ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_mountain` | rel101 children | قله‌های این رشته‌کوه | one_to_many → `tp_mountain` | خالی در نمونه | `tests/samples-local.php (کمبود دلیل‌دار)` |
| `tp_mountain` | rel101 parents | رشته‌کوه مادر | one_to_many → `tp_mountain` | پنهان به‌خاطر قاعده | `plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240 · plugin/topersia-render/includes/topersia-g-relreg.php:256 · plugin/topersia-render/includes/topersia-g-relreg.php:344` |
| `tp_museum` | rel237 children | جشنواره و رویداد — محل برگزاری | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_museum` | rel250 children | رویداد ورزشی — محل برگزاری | many_to_many → `tp_sports_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_music` | rel135 parents | قوم مرتبط | many_to_many → `tp_ethnic_group` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_natural_feature` | rel90 parents | استان | one_to_many → `tp_province` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_natural_feature` | rel103 parents | منطقهٔ حفاظت‌شدهٔ دربرگیرنده | one_to_many → `tp_protected_area` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_natural_feature` | rel106 parents | منظر فرهنگی دربرگیرنده | many_to_many → `tp_landscape` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_protected_area` | rel103 children | عارضه‌های طبیعی درون این منطقه | one_to_many → `tp_natural_feature` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_protected_area` | rel238 children | جشنواره و رویداد — محل برگزاری | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_protected_area` | rel251 children | رویداد ورزشی — محل برگزاری | many_to_many → `tp_sports_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_province` | rel71 parents | کشور | one_to_many → `tp_country` | پنهان به‌خاطر قاعده | `plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240 · plugin/topersia-render/includes/topersia-g-relreg.php:256 · plugin/topersia-render/includes/topersia-g-relreg.php:344` |
| `tp_province` | rel72 children | شهرستان‌های این استان | one_to_many → `tp_county` | خالی در نمونه | `tests/samples-local.php (کمبود دلیل‌دار)` |
| `tp_province` | rel82 children | گذرگاه‌های مرزی این استان | one_to_many → `tp_border_crossing` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_province` | rel302 children | اقوام ساکن این استان | many_to_many → `tp_ethnic_group` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_event` | rel256 parents | بنای تاریخی | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_event` | rel257 parents | مکان مذهبی | many_to_many → `tp_religious_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_event` | rel259 parents | آیین مرجع — این مراسم نمونهٔ آن است | many_to_many → `tp_ritual` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_event` | rel261 parents | شخصیت تاریخی | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_event` | rel262 parents | دوره / سلسلهٔ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_site` | rel140 parents | دورهٔ تاریخی | many_to_many → `tp_historical_era` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_site` | rel149 parents | شخصیت مرتبط | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_site` | rel162 parents | قوانین رفتاری مرتبط | many_to_many → `tp_travel_etiquette` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_site` | rel236 children | جشنواره و رویداد — محل برگزاری | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_site` | rel249 children | رویداد ورزشی — محل برگزاری | many_to_many → `tp_sports_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_religious_site` | rel257 children | مراسم مذهبی عمومی — محل برگزاری | many_to_many → `tp_religious_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ritual` | rel137 parents | قوم مرتبط | many_to_many → `tp_ethnic_group` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ritual` | rel152 parents | شخصیت مرتبط | many_to_many → `tp_figure` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ritual` | rel231 parents | تقویم و مناسبت‌های رسمی | many_to_many → `tp_calendar` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ritual` | rel240 children | جشنواره و رویداد — نمونهٔ این آیین | many_to_many → `tp_festival` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_ritual` | rel259 children | مراسم مذهبی عمومی — نمونهٔ این آیین | many_to_many → `tp_religious_event` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_route` | rel172 children | استان‌هایی که این مسیر از آن‌ها می‌گذرد | many_to_many → `tp_province` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_route` | rel174 children | روستاهایی که این مسیر از آن‌ها می‌گذرد | many_to_many → `tp_village` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_route` | rel175 children | جزیره‌هایی که این مسیر از آن‌ها می‌گذرد | many_to_many → `tp_island` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_route` | rel177 children | گذرگاه‌های مرزی که این مسیر به آن‌ها وصل می‌شود | many_to_many → `tp_border_crossing` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_sports_event` | rel248 parents | بنای تاریخی | many_to_many → `tp_monument` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_sports_event` | rel249 parents | مکان مذهبی | many_to_many → `tp_religious_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_sports_event` | rel250 parents | موزه | many_to_many → `tp_museum` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_sports_event` | rel251 parents | پارک ملی | many_to_many → `tp_protected_area` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_travel_etiquette` | rel162 children | مکان‌های مشمول این قوانین | many_to_many → `tp_religious_site` | جا ندارد | `plugin/topersia-render/includes/topersia-g-map.php:856 · plugin/topersia-render/includes/topersia-g-zones.php:7176` |
| `tp_village` | rel75 parents | استان | one_to_many → `tp_province` | پنهان به‌خاطر قاعده | `plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240 · plugin/topersia-render/includes/topersia-g-relreg.php:256 · plugin/topersia-render/includes/topersia-g-relreg.php:344` |
| `tp_village` | rel76 parents | شهرستان | one_to_many → `tp_county` | پنهان به‌خاطر قاعده | `plugin/topersia-render/includes/topersia-g-zones.php:1980 · plugin/topersia-render/includes/topersia-g-relreg.php:240 · plugin/topersia-render/includes/topersia-g-relreg.php:256 · plugin/topersia-render/includes/topersia-g-relreg.php:344` |

## محدودیت‌ها

- «دیده‌شدن» رابطه با تطبیق نام روی متن صفحه سنجیده شد؛ نامِ یک موجودیت که از راه دیگری (مثلاً دو رابطه به یک مقصد) روی صفحه باشد «دیده» شمرده می‌شود، پس شمار «نادیده» کران پایین است.
- فقط صفحهٔ fa سنجیده شد؛ en و ar همان ناحیه‌ها را با دروازهٔ زبان دارند.
- هیچ‌چیز پاک یا اصلاح نشد؛ تصمیم دربارهٔ سه «باگ» و ۹۳ «جا ندارد» با کاربر است.
