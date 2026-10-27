# نمونهٔ کامل ۴۱ نوع — «پر ولی دیده‌نشده» (دستهٔ ۳۰ بند ۳)

> خروجی `scripts/tp-samples.php seen` روی صفحهٔ fa هر نمونه. «پذیرفته» = سطر META-DISPLAY.csv (دستهٔ ۲۷) با «لازم نیست — فنی…» یا «FIELDMAP: skip». «جا ندارد» = سطری که دستهٔ ۲۷ «لازم نیست تا داده بیاید» نوشته بود؛ با دادهٔ نمونه دیگر دلیل نیست و جایی در صفحه ندارد. «یافته» = هیچ‌کدام. جست‌وجو ابتکاری است (عدد کوچک یا برچسب پرتکرار ممکن است «دیده» شمرده شود).

| نوع | فیلد | برچسب | گونه | وضع |
|---|---|---|---|---|
| `tp_country` | `name_en` | نام (انگلیسی) | text | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_country` | `name_ar` | نام (عربی) | text | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_country` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_city` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_region` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_province` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_province` | `province_code` | کد استان | text | پذیرفته — نمایش در کارت «اطلاعات جغرافیایی/کلیدی» به‌صورت کاشی |
| `tp_district` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_village` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_island` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_mountain` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_water_body` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_desert` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_forest_plain` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_border_crossing` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_monument` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_monument` | `venue_eligible` | قابلیت میزبانی رویداد | switcher | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_monument` | `a1_classification` | طبقه‌بندی عمومی جاذبه | checkbox | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_monument` | `world_heritage_ref_number` | شمارهٔ مرجع یونسکو | text | یافته — META-DISPLAY: نه |
| `tp_monument` | `venue_capacity` | ظرفیت میزبانی | number | یافته — META-DISPLAY: نه |
| `tp_religious_site` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_ancient_site` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_ancient_site` | `has_standing_structure` | دارای سازهٔ ایستاده | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_ancient_site` | `related_museum` | موزهٔ مرتبط | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_ancient_site` | `visiting_permit` | نیاز به مجوز بازدید | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_ancient_site` | `world_heritage_ref_number` | شمارهٔ مرجع یونسکو | text | یافته — META-DISPLAY: نه |
| `tp_natural_feature` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_natural_feature` | `water_temperature` | دمای آب | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_natural_feature` | `safety_notes` | نکات ایمنی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_natural_feature` | `activities` | فعالیت‌های مجاز | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_museum` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_museum` | `housed_in` | مستقر در بنای | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_museum` | `collection_categories` | دسته‌های مجموعه | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_museum` | `permanent_highlights` | آثار شاخص | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_protected_area` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_protected_area` | `managed_area` | منطقهٔ مدیریت‌شده | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_protected_area` | `entry_permit_required` | نیاز به مجوز ورود | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_protected_area` | `key_species` | گونه‌های شاخص | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_protected_area` | `geopark` | ژئوپارک جهانی | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_protected_area` | `world_heritage_ref_number` | شمارهٔ مرجع یونسکو | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_protected_area` | `activities` | فعالیت‌های مجاز | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_landscape` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_landscape` | `world_heritage_ref_number` | شمارهٔ مرجع یونسکو | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_handicraft` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_handicraft` | `intangible_heritage_designation` | ثبت میراث ناملموس | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_handicraft` | `intangible_heritage_criteria` | معیارهای ثبت ناملموس | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_local_food` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_local_food` | `recipe_content` | دستور پخت | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_costume` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_costume` | `garment_components` | اجزای لباس | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_music` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_dance` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_dance` | `ritual_context_reference` | زمینهٔ آیینی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_language` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_language` | `is_official_language` | زبان رسمی | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_language` | `dialect_variants` | گونه‌های گویشی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_language` | `ethnicity_reference` | اقوام گویشور | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_ethnic_group` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_ritual` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_ritual` | `intangible_heritage_designation` | ثبت میراث ناملموس | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_ritual` | `intangible_heritage_criteria` | معیارهای ثبت ناملموس | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_ritual` | `ceremonial_food_reference` | غذاهای مناسبتی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_ritual` | `external_recognition_uris` | شناسایی فراملی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_figure` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_historical_era` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_historical_era` | `predecessor_reference` | سلسلهٔ پیشین | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_designation` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_route` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_visa` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_visa` | `guided_tour_note` | قواعد تور اجباری | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `free_zone_mainland_allowed` | اجازهٔ ورود به سرزمین اصلی؟ | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `free_zone_excluded_iso` | کشورهای مستثنا (کد ISO) | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `free_zone_note` | توضیح رژیم مناطق آزاد | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `application_process_summary` | خلاصهٔ فرآیند درخواست | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `voa_entry_points` | مبادی صدور روادید فرودگاهی | repeater | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `entry_points_reference` | سایر مبادی ورودی (ارجاع محتوایی) | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `insurance_required` | بیمهٔ سلامت ایرانی اجباری؟ | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `insurance_note` | توضیح بیمهٔ اجباری | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `passport_stamping_note` | سیاست مهر پاسپورت | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `dual_nationality_note` | قواعد دوتابعیتی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `restricted_entry_note` | محدودیت‌های مطلق ورود | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `special_passport_note` | معافیت پاسپورت‌های دیپلماتیک/خدمت | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `extension_info` | تمدید و اضافه‌اقامت | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `fee_currency` | واحد پول رسمی هزینه | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_visa` | `fee_policy_note` | سیاست هزینه و پرداخت | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_currency` | `unit_confusion_explainer` | توضیح ریال در برابر تومان | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `price_reading_rule` | قاعدهٔ خواندن قیمت | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `redenomination_ratio` | نسبت تبدیل بازتعریف | number | یافته — META-DISPLAY: نه |
| `tp_currency` | `redenomination_end_date` | پایان دورهٔ گذار | date | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `dual_pricing_active` | برچسب‌گذاری دوگانهٔ قیمت فعال؟ | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `redenomination_note` | شرح کامل بازتعریف | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `denominations` | اسکناس و مسکوکات | repeater | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `card_acceptance_summary` | پاسخ صریح دربارهٔ کارت | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `atm_usage_note` | استفاده از خودپرداز | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `domestic_network_note` | شبکهٔ کارت داخلی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `tourist_card_available` | کارت گردشگری موجود است؟ | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `banknote_condition_rule` | قاعدهٔ سلامت اسکناس | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `exchange_channel_notes` | کانال‌های تبدیل و رسید | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `declaration_rules_note` | قواعد اظهار ارز در گمرک | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `rate_regime_note` | رژیم نرخ ارز | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `cash_recommendation_note` | توصیهٔ میزان پول نقد | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `daily_budget_bands` | بازه‌های بودجهٔ روزانه | repeater | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `digital_payment_note` | پرداخت دیجیتال داخلی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_currency` | `regional_notes` | یادداشت‌های استانی | repeater | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_health_safety` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_health_safety` | `natural_hazard_summary` | مخاطرات طبیعی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_health_safety` | `general_safety_notes` | نکات کلی ایمنی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_health_safety` | `water_safety_note` | ایمنی آب آشامیدنی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_health_safety` | `regional_health_notes` | نکات سلامت منطقه‌ای | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_health_safety` | `local_emergency_facilities` | مراکز درمانی محلی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_health_safety` | `activity_specific_warnings` | هشدارهای فعالیت‌محور | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_travel_etiquette` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_travel_etiquette` | `religious_site_etiquette` | آداب اماکن مذهبی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_travel_etiquette` | `social_customs_summary` | آداب اجتماعی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_calendar` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_calendar` | `calendar_system_note` | توضیح نظام تقویمی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_calendar` | `closure_impact_note` | اثر تعطیلات بر خدمات | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_calendar` | `crowding_and_price_note` | ازدحام و نوسان قیمت | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_calendar` | `best_worst_time_note` | بهترین و بدترین زمان سفر | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_calendar` | `lunar_date_disclaimer` | هشدار تاریخ‌های قمری | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_transport` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_transport` | `mode_choice_guide` | راهنمای انتخاب حالت سفر | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_transport` | `intercity_bus_note` | اتوبوس بین‌شهری | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_transport` | `air_travel_note` | پرواز داخلی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_transport` | `transport_etiquette_note` | آداب حمل‌ونقل عمومی | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_transport` | `route_reference_note` | ارجاع به مسیرهای گردشگری | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_festival` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_festival` | `is_recurring` | رویداد تکرارشونده است؟ | switcher | یافته — META-DISPLAY: نه |
| `tp_festival` | `program_highlights` | بخش‌های برنامه | repeater | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_festival` | `event_travel_impact` | اثرگذار بر برنامه‌ریزی سفر؟ | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_festival` | `admission_note` | شرایط ورود و بلیت | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_festival` | `tourist_suitability_note` | مناسب‌بودن برای گردشگر | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_sports_event` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_sports_event` | `is_recurring` | رویداد تکرارشونده است؟ | switcher | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_sports_event` | `spectator_access_note` | دسترسی تماشاگر | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_religious_event` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_religious_event` | `annual_date_rule` | قاعدهٔ تاریخ سالانه | text | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_religious_event` | `public_participation_note` | مشارکت عمومی و حضور بازدیدکننده | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_religious_event` | `visitor_etiquette_note` | آداب حضور | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_religious_event` | `photography_note` | یادداشت عکاسی | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_religious_event` | `dress_code_note` | پوشش و ظاهر | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_seasonal_event` | `ai_summary` | خلاصه برای هوش مصنوعی | textarea | پذیرفته — لازم نیست — فنی/سئو/داخلی (متا، شناسه، وضعیت) |
| `tp_seasonal_event` | `peak_start_week` | هفتهٔ آغاز اوج | number | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_seasonal_event` | `peak_end_week` | هفتهٔ پایان اوج | number | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_seasonal_event` | `optimal_conditions` | شرایط بهینهٔ مشاهده | wysiwyg | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |
| `tp_seasonal_event` | `predictability_note` | پایداری سال‌به‌سال | textarea | جا ندارد — دستهٔ ۲۷: «تا داده بیاید» |

دیده‌نشده: 147 · پذیرفته: 47 · جا ندارد (دستهٔ ۲۷): 95 · یافته: 5
