# جدول فیلدهای موج ۱ — دستهٔ 40 · P5-D484 · P5-D485

> نمایش لایهٔ قالب است؛ نام، نوع و کلاس WPML فیلد قفل است (`docs/ops/STRUCTURE-LOCK.json`). فقط محلی ساخته و سنجیده شد؛ چیزی روی سایت زنده نرفته.
> دستهٔ 40: سه فیلد باگ و برق کشور. دستهٔ 41: `tp_currency` و `tp_visa` (بخش ۵).

## ۱ — پنج فیلد این دسته

| کلید | نوع پست | نوع داده (جت‌انجین) | عددی/متنی | WPML پیش از این دسته | WPML پیشنهادی ← اعمال‌شده | ناحیهٔ نمایش | واحد |
|---|---|---|---|---|---|---|---|
| `is_recurring` | `tp_festival` · `tp_sports_event` | switcher | سوییچ (بله/خیر) | copy (۱۳٫۱) | copy ← **copy** | card (کارت اطلاعات؛ فقط روشن) | — |
| `redenomination_ratio` | `tp_currency` | number | عددی | copy | copy ← **copy** | travel | — |
| `venue_capacity` | `tp_monument` | number | عددی | copy | copy ← **copy** | visit | نفر |
| `electric_voltage` | `tp_country` | number (**تازه**، P5-D470) | عددی | — | copy ← **copy** | travel | ولت |
| `electric_frequency` | `tp_country` | number (**تازه**، P5-D470) | عددی | — | copy ← **copy** | travel | هرتز |

قاعده: عدد و سوییچ = copy، متن = translate. هیچ‌یک از پنج فیلد متن آزاد نیست.

## ۲ — هم‌پوشانی

- `electric_voltage` و `electric_frequency` کنار `electric_plug` (P5-D450، `tp_country`، choice) در همان کاشی «اطلاعات سفر» می‌نشینند؛ یک گروه «برق»، سه فیلد، هیچ فیلد تکراری.
- `redenomination_ratio` عضو گروه `redenomination_*` در `tp_currency` است (وضعیت، قانون، تاریخ‌ها، `dual_pricing_active`، یادداشت). گروه کامل در دستهٔ 41 با `tp_currency` یک بلوک می‌شود؛ این دسته فقط خودِ نسبت را به کاشی می‌آورد.
- `venue_capacity` با `venue_eligible` (switcher، همان نوع) هم‌خانواده است؛ آن در این دسته نیست.

## ۳ — رندر

رندر داده‌محور است و برای هر فیلد کدی ندارد: افزودن کلید به `topersia_g_map()` با `zone` کافی است. ناحیه‌ها (کاشی `card` · `travel` · `visit`، نشان `badge`، بخش `details`، جدول `repeater`) از پیش وجود دارند؛ خروجی HTML سرور است و مقدار خالی، صفر و سوییچ خاموش هیچ کاشی و هیچ «—» نمی‌سازد (`topersia_g_zone_fields`).

آزمون جهشی `tests/assert-40.php`: نقشه، کلاس WPML، قفل ساختار، کاتالوگ سه‌زبانه، رندر پر و خالی، و «هیچ نامِ فیلد در کد رندر».

## ۴ — سقف‌ها (وضع فعلی)

| سقف | وضع |
|---|---|
| آکاردئون (`<details>`) ≤ ۶ در صفحه | بخش `details` فعلی `h3 + p` است، نه آکاردئون؛ پنج فیلد این دسته آکاردئون نمی‌سازند. تنها `<details>` های صفحه: پرسش‌های متداول (قاعدهٔ خودش) و «همهٔ منابع». دستهٔ 41 هم آکاردئون نساخت (متن غنی زیر h3 و جدول)، پس سقف بی‌موضوع ماند. |
| کاشی رابطه ۱۲ + «همه» فقط با آرشیو | **اعمال شد (P5-D490، دستهٔ 41):** ۱۲ کاشی دیدنی، بقیه در HTML پنهان با «بیشتر»؛ هیچ نوعی الان بایگانی ندارد، پس پیوندی هم نیست. |

## ۵ — دستهٔ 41: ارز و روادید · P5-D491

همهٔ فیلدها از قبل در متاباکس بودند؛ فیلد تازه‌ای ساخته نشد.

| کلید | نوع پست | جت‌انجین | نوع رندر | ناحیه | WPML | برچسب |
|---|---|---|---|---|---|---|
| `unit_confusion_explainer` | `tp_currency` | wysiwyg | rich | details | translate | ریال و تومان |
| `price_reading_rule` | `tp_currency` | textarea | text | details | translate | خواندن قیمت |
| `redenomination_end_date` | `tp_currency` | date | date | card | copy | پایان دورۀ گذار |
| `dual_pricing_active` | `tp_currency` | switcher | flag | travel | copy | قیمت دوگانه |
| `redenomination_note` | `tp_currency` | wysiwyg | rich | details | translate | بازتعریف پول |
| `denominations` | `tp_currency` | repeater | table | details | translate | اسکناس و مسکوک |
| `card_acceptance_summary` | `tp_currency` | textarea | text | details | translate | پذیرش کارت |
| `atm_usage_note` | `tp_currency` | wysiwyg | rich | details | translate | خودپرداز |
| `domestic_network_note` | `tp_currency` | wysiwyg | rich | details | translate | شبکۀ کارت داخلی |
| `tourist_card_available` | `tp_currency` | switcher | flag | travel | copy | کارت گردشگری |
| `banknote_condition_rule` | `tp_currency` | textarea | text | details | translate | سلامت اسکناس |
| `exchange_channel_notes` | `tp_currency` | wysiwyg | rich | details | translate | کانال‌های تبدیل و رسید |
| `declaration_rules_note` | `tp_currency` | wysiwyg | rich | details | translate | اظهار ارز در گمرک |
| `rate_regime_note` | `tp_currency` | wysiwyg | rich | details | translate | رژیم نرخ ارز |
| `cash_recommendation_note` | `tp_currency` | wysiwyg | rich | details | translate | میزان پول نقد |
| `daily_budget_bands` | `tp_currency` | repeater | table | details | translate | بودجۀ روزانه |
| `digital_payment_note` | `tp_currency` | wysiwyg | rich | details | translate | پرداخت دیجیتال |
| `regional_notes` | `tp_currency` | repeater | table | details | translate | یادداشت‌های استانی |
| `guided_tour_note` | `tp_visa` | wysiwyg | rich | details | translate | تور اجباری |
| `free_zone_mainland_allowed` | `tp_visa` | switcher | flag | travel | copy | ورود به سرزمین اصلی از منطقۀ آزاد |
| `free_zone_excluded_iso` | `tp_visa` | textarea | iso | details | copy | کشورهای مستثنا از منطقۀ آزاد |
| `free_zone_note` | `tp_visa` | wysiwyg | rich | details | translate | رژیم مناطق آزاد |
| `application_process_summary` | `tp_visa` | wysiwyg | rich | details | translate | فرایند درخواست |
| `voa_entry_points` | `tp_visa` | repeater | table | details | translate | مبادی صدور روادید فرودگاهی |
| `entry_points_reference` | `tp_visa` | textarea | text | card | translate | سایر مبادی ورودی |
| `insurance_required` | `tp_visa` | switcher | flag | travel | copy | بیمۀ اجباری |
| `insurance_note` | `tp_visa` | wysiwyg | rich | details | translate | بیمۀ اجباری: توضیح |
| `passport_stamping_note` | `tp_visa` | textarea | text | details | translate | مهر پاسپورت |
| `dual_nationality_note` | `tp_visa` | wysiwyg | rich | details | translate | دوتابعیتی |
| `restricted_entry_note` | `tp_visa` | textarea | text | details | translate | محدودیت‌های ورود |
| `special_passport_note` | `tp_visa` | wysiwyg | rich | details | translate | پاسپورت دیپلماتیک و خدمت |
| `extension_info` | `tp_visa` | wysiwyg | rich | details | translate | تمدید و اضافه‌اقامت |
| `fee_currency` | `tp_visa` | text | latin | travel | copy | واحد پول هزینه |
| `fee_policy_note` | `tp_visa` | wysiwyg | rich | details | translate | سیاست هزینه و پرداخت |

در طرح هستند ولی نگاشت نشدند (نه در ستون DISPLAY پیشنهاد ساختار): `visa_types` · `visa_policy_matrix` · `currency_units` · `card_network_status` · `exchange_locations` · `tourist_card_services` · `rate_reference_links` · `application_steps` · `portal_links` · `document_specs` · `policy_change_log` · `official_application_portal` · `extension_max_count` · `extension_days_each` · `redenomination_law_date` · `redenomination_start_date` · `accepted_foreign_currencies`.
