# ToPersia — بستهٔ ساخت P1 (ویزا و شرایط ورود)

**شناسه:** `P1-BUILD-PACKAGE-v2_0` · **تاریخ:** ۲۵ اوت ۲۰۲۶ · **وضعیت:** ساخت ساختاری انجام شد — ورود داده مسدود تا P5-T005

**مبنا:** `CA-P-v1.2-FINAL` بخش P1 · `CA-P-AMENDMENT-002` · `61-RESEARCH-VISA-MFA` · `Phase5-HANDOVER-v9.0` بخش ۱۰ فاز ۲

---

## بخش ۰ — چرا نسخهٔ ۲

نسخهٔ ۱ این بسته صرفاً متن `CA-P` را به دستور ساخت ترجمه می‌کرد. پژوهش مستقیم روی سامانه‌های وزارت امور خارجه در ۲۵ اوت ۲۰۲۶ نشان داد آن مدل شش بُعد ساختاری واقعیت را از دست می‌دهد. جزئیات کامل در `CA-P-AMENDMENT-002` بخش ۲.

نتیجه: مدل از ۱۴ فیلد به **۲۷ فیلد سطح‌بالا و ۳۶ زیرفیلد** گسترش یافت، **بدون افزودن حتی یک موجودیت یا رابطهٔ تازه.**

---

## بخش ۱ — شناسنامهٔ CPT ✅ ساخته شد

| تنظیم | مقدار |
|---|---|
| Post Type Name | P1 — ویزا و شرایط ورود |
| Post Type Slug | `tp_visa` |
| Rewrite Slug | `visa` |
| Menu Icon | `dashicons-id-alt` |
| Has Archive | Off |
| Hierarchical | Off |
| Is Public | On |
| Publicly Queryable | On |
| Show Admin UI / Admin Menu / Nav Menu | On |
| Show in Rest API | On |
| Register Query Var | On |
| Rewrite | On · Rewrite With Front: Off |
| Capability Type | post |
| Map Meta Cap | Off |
| Custom Meta Storage | Off |
| Supports | Title فقط |
| Menu Position | Crocoblock Section |

`entity_uid` خودکار تولید می‌شود: `https://topersia.com/e/visa/{اسلاگ}`. بخش نوع از `rewrite['slug']` خوانده می‌شود و تابع `topersia_is_entity()` فقط پیشوند `tp_` را چک می‌کند — پس **صفر تغییر در `topersia-core.php` لازم بود** (راستی‌آزمایی مستقیم از سطر ۱۳۱ و ۳۹۳ فایل).

---

## بخش ۲ — Labels ✅ ساخته شد

| فیلد UI | مقدار |
|---|---|
| Singular name | ویزا و شرایط ورود |
| Add New | افزودن تازه |
| Add New Item | افزودن راهنمای ویزای تازه |
| New Item | راهنمای ویزای تازه |
| Edit Item | ویرایش راهنمای ویزا |
| View Item | نمایش راهنماهای ویزا |
| All Items | فهرست ویزا و شرایط ورود |
| Search for items | جست‌وجوی ویزا و شرایط ورود |
| Not found | راهنمای ویزایی یافت نشد |
| Not found in trash | راهنمای ویزایی در زباله‌دان یافت نشد |
| Admin Menu | ویزا و ورود |
| Add New on Toolbar | افزودن راهنمای ویزا |
| Featured Image | تصویر شاخص |
| Set Featured Image | تعیین تصویر شاخص |
| Remove Featured Image | حذف تصویر شاخص |
| Use Featured Image | استفاده به‌عنوان تصویر شاخص |
| Archive label in nav menus | ناوبری فهرست ویزا |
| Insert into post | درج در راهنمای ویزا |
| Uploaded to this post | بارگذاری‌شده در این راهنمای ویزا |

قاعدهٔ `TA-PROC-D008` رعایت شد: All Items = «فهرست X»، نه «همهٔ X». درس `P5-D049` رعایت شد: مقدار Search از All Items کپی نشد.

---

## بخش ۳ — اتصال متاباکس مشترک ✅ انجام شد

`tp_visa` به فهرست **Enable For Post Types** متاباکس مشترک افزوده شد. نوزده فیلد و یازده زیرفیلد از آنجا تأمین می‌شوند. فهرست کامل و راستی‌آزمایی‌شده در `SHARED-METABOX-VERIFIED-v1_0`.

**تداخل با متاباکس اختصاصی: صفر.** هیچ‌کدام از ۲۷ فیلد بخش ۴ در متاباکس مشترک وجود ندارد.

نگاشت‌های مهم:

| بخش الگوی صفحه در CA-P | فیلد واقعی |
|---|---|
| بلوک «پاسخ سریع» (۱۵۰ کلمهٔ اول، AEO-critical) | `short_summary` — متاباکس مشترک |
| خلاصهٔ موتور پاسخ | `ai_summary` — متاباکس مشترک |
| منابع رسمی | `source_references` — متاباکس مشترک |
| آخرین به‌روزرسانی در Hero | `last_verified_date` — متاباکس مشترک |

---

## بخش ۴ — متاباکس اختصاصی P1

**اتصال به:** `tp_visa` · **۲۷ فیلد سطح‌بالا + ۳۶ زیرفیلد**

**تنظیم عمومی همهٔ فیلدها:** Show in Rest API = On · فیلدهای الزامی Is Required = On · شناسهٔ فیلد **دستی تایپ شود** (برچسب فارسی است و تولید خودکار خراب می‌شود) · Description فارسی اجباری است.

### گروه الف — انواع روادید

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۱ | انواع روادید | `visa_types` | Repeater | ۱۰۰٪ | الزامی | Translate |
| ۱٫۱ | کد و نوع روادید | `vt_code` | Select ← `p1_visa_type` | ۳۳٪ | الزامی | Copy |
| ۱٫۲ | حداکثر اقامت (روز) | `vt_stay_days` | Number · Min 0 · Step 1 | ۳۳٪ | الزامی | Copy |
| ۱٫۳ | اعتبار روادید (روز) | `vt_validity_days` | Number · Min 0 · Step 1 | ۳۳٪ | اختیاری | Copy |
| ۱٫۴ | چندورودی؟ | `vt_multiple_entry` | Switcher | ۳۳٪ | اختیاری | Copy |
| ۱٫۵ | قابل تمدید؟ | `vt_extendable` | Switcher | ۳۳٪ | اختیاری | Copy |
| ۱٫۶ | کانال‌های دریافت | `vt_channels` | Checkbox ← `p1_visa_channel` | ۱۰۰٪ | الزامی | Copy |
| ۱٫۷ | زمان پردازش | `vt_processing_note` | Text | ۵۰٪ | اختیاری | Translate |
| ۱٫۸ | مدارک لازم | `vt_required_docs` | Textarea | ۱۰۰٪ | اختیاری | Translate |
| ۱٫۹ | شرایط و محدودیت‌ها | `vt_note` | Textarea | ۱۰۰٪ | اختیاری | Translate |

### گروه ب — ماتریس سیاست ملیتی

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۲ | ماتریس سیاست بر پایهٔ تابعیت | `visa_policy_matrix` | Repeater | ۱۰۰٪ | الزامی | Translate |
| ۲٫۱ | کشور | `pc_country` | Select ← `country_iso` | ۳۳٪ | الزامی | Copy |
| ۲٫۲ | دستهٔ سیاست | `pc_policy_code` | Select ← `p1_policy_category` | ۳۳٪ | الزامی | Copy |
| ۲٫۳ | مدت اقامت مجاز (روز) | `pc_stay_days` | Number · Min 0 | ۳۳٪ | اختیاری | Copy |
| ۲٫۴ | شرط ورود | `pc_condition` | Select ← `p1_entry_condition` | ۳۳٪ | اختیاری | Copy |
| ۲٫۵ | واجد شرایط روادید فرودگاهی؟ | `pc_voa_eligible` | Switcher | ۳۳٪ | اختیاری | Copy |
| ۲٫۶ | بیمهٔ ایرانی اجباری؟ | `pc_insurance_required` | Switcher | ۳۳٪ | اختیاری | Copy |
| ۲٫۷ | حداقل هزینه (یورو) | `pc_fee_min_eur` | Number · Min 0 | ۳۳٪ | اختیاری | Copy |
| ۲٫۸ | حداکثر هزینه (یورو) | `pc_fee_max_eur` | Number · Min 0 | ۳۳٪ | اختیاری | Copy |
| ۲٫۹ | تاریخ اعتبار این وضعیت | `pc_effective_date` | Date | ۳۳٪ | اختیاری | Copy |
| ۲٫۱۰ | یادداشت ویژهٔ این کشور | `pc_note` | Textarea | ۱۰۰٪ | اختیاری | Translate |
| ۳ | قواعد تور اجباری | `guided_tour_note` | WYSIWYG | ۱۰۰٪ | اختیاری | Translate |

### گروه ج — رژیم مناطق آزاد

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۴ | مدت معافیت در مناطق آزاد (روز) | `free_zone_stay_days` | Number · Min 0 | ۳۳٪ | اختیاری | Copy |
| ۵ | اجازهٔ ورود به سرزمین اصلی؟ | `free_zone_mainland_allowed` | Switcher | ۳۳٪ | اختیاری | Copy |
| ۶ | کشورهای مستثنا (کد ISO) | `free_zone_excluded_iso` | Textarea | ۱۰۰٪ | اختیاری | Copy |
| ۷ | توضیح رژیم مناطق آزاد | `free_zone_note` | WYSIWYG | ۱۰۰٪ | اختیاری | Translate |

### گروه د — فرآیند درخواست

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۸ | خلاصهٔ فرآیند درخواست | `application_process_summary` | WYSIWYG | ۱۰۰٪ | الزامی | Translate |
| ۹ | مراحل گام‌به‌گام | `application_steps` | Repeater | ۱۰۰٪ | الزامی | Translate |
| ۹٫۱ | شمارهٔ گام | `step_order` | Number · Min 1 · Step 1 | ۲۵٪ | الزامی | Copy |
| ۹٫۲ | عنوان گام | `step_title` | Text | ۷۵٪ | الزامی | Translate |
| ۹٫۳ | شرح گام | `step_text` | Textarea | ۱۰۰٪ | الزامی | Translate |
| ۱۰ | درگاه رسمی اصلی | `official_application_portal` | Text | ۵۰٪ | اختیاری | Copy |
| ۱۱ | سایر درگاه‌های رسمی | `portal_links` | Repeater | ۱۰۰٪ | اختیاری | Translate |
| ۱۱٫۱ | نشانی درگاه | `portal_url` | Text | ۵۰٪ | الزامی | Copy |
| ۱۱٫۲ | عنوان درگاه | `portal_label` | Text | ۵۰٪ | الزامی | Translate |
| ۱۱٫۳ | حوزهٔ کاربرد | `portal_scope` | Text | ۱۰۰٪ | اختیاری | Translate |
| ۱۲ | مشخصات فنی مدارک | `document_specs` | Repeater | ۱۰۰٪ | اختیاری | Translate |
| ۱۲٫۱ | نوع مدرک | `doc_type` | Select ← `p1_doc_spec_type` | ۳۳٪ | الزامی | Copy |
| ۱۲٫۲ | حداقل ابعاد (پیکسل) | `doc_min_px` | Text | ۳۳٪ | الزامی | Copy |
| ۱۲٫۳ | قالب و حجم فایل | `doc_format` | Text | ۳۳٪ | اختیاری | Copy |
| ۱۲٫۴ | توضیح | `doc_note` | Textarea | ۱۰۰٪ | اختیاری | Translate |

### گروه ه — مبادی ورودی

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۱۳ | مبادی صدور روادید فرودگاهی | `voa_entry_points` | Repeater | ۱۰۰٪ | اختیاری | Translate |
| ۱۳٫۱ | کد مبدأ (IATA یا اسلاگ) | `ep_code` | Text | ۳۳٪ | الزامی | Copy |
| ۱۳٫۲ | نوع مبدأ | `ep_type` | Select ← `p1_entry_point_type` | ۳۳٪ | الزامی | Copy |
| ۱۳٫۳ | نام مبدأ | `ep_name` | Text | ۳۳٪ | الزامی | Translate |
| ۱۴ | سایر مبادی ورودی (ارجاع محتوایی) | `entry_points_reference` | Textarea | ۱۰۰٪ | اختیاری | Translate |

### گروه و — الزامات و محدودیت‌ها

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۱۵ | حداقل اعتبار پاسپورت (ماه) | `passport_validity_months` | Number · Min 0 | ۳۳٪ | الزامی | Copy |
| ۱۶ | بیمهٔ سلامت ایرانی اجباری؟ | `insurance_required` | Switcher | ۳۳٪ | الزامی | Copy |
| ۱۷ | توضیح بیمهٔ اجباری | `insurance_note` | WYSIWYG | ۱۰۰٪ | اختیاری | Translate |
| ۱۸ | سیاست مهر پاسپورت | `passport_stamping_note` | Textarea | ۱۰۰٪ | اختیاری | Translate |
| ۱۹ | قواعد دوتابعیتی | `dual_nationality_note` | WYSIWYG | ۱۰۰٪ | اختیاری | Translate |
| ۲۰ | محدودیت‌های مطلق ورود | `restricted_entry_note` | Textarea | ۱۰۰٪ | اختیاری | Translate |
| ۲۱ | معافیت پاسپورت‌های دیپلماتیک/خدمت | `special_passport_note` | WYSIWYG | ۱۰۰٪ | اختیاری | Translate |

### گروه ز — تمدید، اضافه‌اقامت و هزینه

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۲۲ | حداکثر دفعات تمدید | `extension_max_count` | Number · Min 0 | ۳۳٪ | اختیاری | Copy |
| ۲۳ | مدت هر تمدید (روز) | `extension_days_each` | Number · Min 0 | ۳۳٪ | اختیاری | Copy |
| ۲۴ | حداکثر اقامت کل (روز) | `max_total_stay_days` | Number · Min 0 | ۳۳٪ | اختیاری | Copy |
| ۲۵ | تمدید و اضافه‌اقامت | `extension_info` | WYSIWYG | ۱۰۰٪ | اختیاری | Translate |
| ۲۶ | واحد پول رسمی هزینه | `fee_currency` | Text | ۳۳٪ | اختیاری | Copy |
| ۲۷ | سیاست هزینه و پرداخت | `fee_policy_note` | WYSIWYG | ۱۰۰٪ | اختیاری | Translate |

### گروه ح — حاکمیت داده

| # | برچسب | متاکلید | نوع | عرض | الزام | WPML |
|---|---|---|---|---|---|---|
| ۲۸ | تاریخچهٔ تغییرات سیاست | `policy_change_log` | Repeater | ۱۰۰٪ | اختیاری | Translate |
| ۲۸٫۱ | تاریخ تغییر | `chg_date` | Date | ۳۳٪ | الزامی | Copy |
| ۲۸٫۲ | دامنهٔ تغییر | `chg_scope` | Text | ۳۳٪ | الزامی | Copy |
| ۲۸٫۳ | شرح تغییر | `chg_summary` | Textarea | ۱۰۰٪ | الزامی | Translate |
| ۲۸٫۴ | منبع | `chg_source_url` | Text | ۱۰۰٪ | اختیاری | Copy |

---

## بخش ۵ — واژه‌نامه‌ها

هفت واژه‌نامه · همه با **Data Source = Set items manually** (قاعدهٔ ثابت `P5-D040`)

### `p1_visa_type` — ۱۳ مقدار (کدهای رسمی MFA)

| Value | Label |
|---|---|
| `a_entry_business` | A — ورود / تجاری |
| `b_tourist` | B — توریستی |
| `c_pilgrimage` | C — زیارتی |
| `d_diplomatic` | D — دیپلماتیک |
| `e_education` | E — تحصیلی |
| `f_temporary_work` | F — کار موقت |
| `g_transit` | G — ترانزیت |
| `h_media` | H — رسانه‌ای / مطبوعاتی |
| `i_investment` | I — سرمایه‌گذاری |
| `m_marital` | M — ازدواج / همسری |
| `t_medical` | T — درمانی |
| `service` | خدمت |
| `relatives` | ورود بستگان |

### `p1_policy_category` — ۶ مقدار

| Value | Label |
|---|---|
| `visa_free` | بدون نیاز به روادید |
| `visa_free_zone_only` | معافیت فقط در مناطق آزاد |
| `voa` | روادید فرودگاهی |
| `e_visa_required` | ملزم به روادید الکترونیکی پیشین |
| `guided_tour_required` | ملزم به تور هدایت‌شده |
| `prohibited` | ممنوع‌الورود |

### `p1_visa_channel` — ۳ مقدار

| Value | Label |
|---|---|
| `embassy` | سفارت / کنسولگری |
| `e_visa` | روادید الکترونیکی |
| `voa` | فرودگاهی |

### `p1_entry_condition` — ۶ مقدار

| Value | Label |
|---|---|
| `none` | بدون شرط ویژه |
| `air_only` | فقط ورود هوایی مستقیم |
| `specific_port` | فقط از مبدأ/مقصد مشخص |
| `once_per_180days` | یک‌بار در هر ۱۸۰ روز |
| `non_extendable` | غیرقابل تمدید |
| `annual_cap` | سقف سالانه دارد |

### `p1_entry_point_type` — ۳ مقدار

| Value | Label |
|---|---|
| `airport` | فرودگاه بین‌المللی |
| `land_border` | مرز زمینی |
| `seaport` | بندر دریایی |

### `p1_doc_spec_type` — ۲ مقدار

| Value | Label |
|---|---|
| `photo` | عکس شخصی |
| `passport_scan` | تصویر صفحهٔ پاسپورت |

### `country_iso` — ۲۴۹ مقدار (سراسری)

`Value` = کد ISO 3166-1 alpha-2 · `Label` = نام فارسی کشور

**استثنای مصوب `RB-D020`** — بدون پیشوند موجودیت، چون سراسری است و P2، G13، `neighboring_countries` و `bordering_countries` هم از آن استفاده خواهند کرد (`P5-D092`).

فهرست کامل در فایل جداگانهٔ `73-GLOSSARY-COUNTRY-ISO`. نام‌ها از دادهٔ CLDR استخراج و کاراکتر «هٔ» به کدپوینت یگانهٔ `U+06C0` نرمال‌سازی شده (`TA-PROC-D007`).

---

## بخش ۶ — رابطه ✅ ساخته شد

**شناسهٔ ثبت‌شده در سرور: ۱۸۵**

| ردیف UI | تنظیم | مقدار |
|---|---|---|
| Name | | `applies_g1_p1` |
| Parent object | | Posts: G1 — کشور |
| Child object | | Posts: P1 — ویزا و شرایط ورود |
| Relation type | | Many to many |
| Parent relation | | (خالی) |
| Register controls for parent object | | On |
| Require at least one child | | Off |
| Allow to create new children from parent | | Off |
| Allow to delete children from parent | | Off |
| Register controls for child object | | On |
| Require at least one parent | | Off |
| Allow to create new parents from children | | Off |
| Allow to delete parents from children | | Off |
| Register separate DB table | | Off |
| Register get items/item REST API Endpoint | | Off |
| Register update REST API Endpoint | | Off |
| Meta Fields | | ۰ — قاعدهٔ ثابت `P5-D068` |

**Labels:**

| فیلد | مقدار |
|---|---|
| Parent Object: label of relation box | راهنمای ویزا و شرایط ورود |
| Child Object: label of relation box | کشور |
| سایر فیلدهای Label | خالی — اختیاری و بی‌خطر |

**فقط یک رابطه.** طبق `DEC-004` سطوح G3 و G5 برای P1 «Link only» هستند — یعنی لینک ایستا در قالب Elementor، نه یال گراف. تکرار محتوای روادید در ۳۱ استان و بیش از هزار شهر، Thin و Duplicate Content تولید می‌کند. این همان الگوی `P5-D045` است.

---

## بخش ۷ — wpml-config

نسخهٔ جاری: **`v1.10`** — ۱۸۵ کلید (۹۰ Copy + ۹۵ Translate)

بیست‌وهشت کلید P1 (۱۰ Copy + ۱۸ Translate) و هفت بلوک تازهٔ `custom-fields-texts` در آن ثبت شده‌اند.

**دو خطای کشف‌شده و رفع‌شده در این نسخه:**

۱. در نسخهٔ کاری پیشین، هر ۲۸ عنصر `custom-field` مربوط به P1 **داخل بلوک `custom-fields-texts`** پیست شده بودند. WPML عنصر `custom-field` را فقط داخل `custom-fields` می‌خواند — یعنی هیچ‌کدام از کلیدهای ویزا ثبت نمی‌شدند و خطا کاملاً خاموش بود.

۲. سربرگ v1.9 مدعی «۱۵۶ کلید» بود اما شمارش برنامه‌ای پس از حذف کامنت‌ها ۱۵۵ کلید واقعی نشان داد. کلید هفتادوهفتم `audio_narration` بود که داخل یک کامنت قرار داشت.

**قاعدهٔ تازه (`P5-D097`):** هر فایل `wpml-config` پیش از پیست‌شدن باید **به‌صورت برنامه‌ای** راستی‌آزمایی شود: صحت ساختار XML، شمارش کلیدها پس از حذف کامنت‌ها، بررسی تکرار، بررسی تضاد کلاس بین Copy و Translate، و بررسی عنصر سرگردان داخل `custom-fields-texts`. شمارش دستی دو بار خطا تولید کرده است.

---

## بخش ۸ — نگاشت Schema (ثبت‌شده — پیاده‌سازی در فاز ۷)

موتور JSON-LD طبق بخش ۱۰ سند تحویل **تا پایان فاز ۶ فریز است**. این جدول فقط ثبت می‌شود.

**نوع اصلی:** `GovernmentService` · شاخهٔ ارث‌بری: `GovernmentService ← Service ← Intangible ← Thing`

| منبع | ویژگی | گره | راستی‌آزمایی ارث‌بری |
|---|---|---|---|
| `name_en` | `name` | GovernmentService | Thing |
| `short_summary` | `description` | GovernmentService | Thing |
| `hero_image` | `image` | GovernmentService | Thing |
| ثابت | `serviceType` = "Visa Application" | GovernmentService | Service |
| G1 از رابطهٔ ۱۸۵ | `areaServed` → Country با `@id` واقعی | GovernmentService | Service |
| ثابت | `provider` → GovernmentOrganization | GovernmentService | Service |
| `official_application_portal` | `availableChannel` → ServiceChannel.serviceUrl | GovernmentService | Service |
| `visa_types[]` | `hasOfferCatalog` → OfferCatalog → Offer.itemOffered | GovernmentService | Service |
| `application_steps[]` | `HowTo` → `HowToStep` با `position` | گرهٔ مستقل | CreativeWork |
| `faq_items` | Question / Answer | گرهٔ FAQPage موجود | — |
| `last_verified_date` | `dateModified` | **WebPage — نه GovernmentService** | خطای CA-P |

**خطای Schema در `CA-P` که تصحیح شد:** بلوک baseline سند، `dateModified` را روی `GovernmentService` گذاشته بود. این نامعتبر است — `dateModified` ویژگی `CreativeWork` است، نه `Thing` و نه `Service`. همان دستهٔ خطایی که بخش ۴ سند تحویل به‌عنوان «درس گران‌قیمت» ثبت کرده بود. → به گرهٔ `WebPage` منتقل شد (`P5-D089`).

`additionalProperty` روی این نوع مجاز نیست — فقط روی `Place` و `Product`.

**`HowTo` یک برد تازه است:** مراحل ساختاریافتهٔ درخواست، امکان نتیجهٔ غنی HowTo در گوگل را می‌دهد — روی صفحه‌ای که `CA-P` آن را «یکی از بالاترین حجم‌های جست‌وجوی کل پروژه» نامیده.

---

## بخش ۹ — 🔴 آزمون مسدودکننده پیش از ورود داده

`visa_policy_matrix` با حدود دویست ردیف، بزرگ‌ترین Repeater تاریخ پروژه است. بزرگ‌ترین آزموده‌شده تاکنون ده ردیف بوده.

**آزمون `P5-T005` باید پیش از ورود هر دادهٔ واقعی اجرا شود.** پروتکل کامل دوازده‌گامی در `P5-T005-T006-PROTOCOL`.

اگر رد شود، راه‌حل جایگزین از پیش تعیین شده: شکستن ماتریس به شش Repeater مجزا به‌ازای دستهٔ سیاست. ساختار زیرفیلدها و کلاس‌های ترجمه یکسان می‌مانند، پس تعویض **بدون بازکاری داده** ممکن است — به شرطی که آزمون پیش از ورود داده اجرا شود.

---

## بخش ۱۰ — چک‌لیست وضعیت

- [x] اسکرین‌شات متاباکس مشترک دریافت و راستی‌آزمایی شد — ۱۹ فیلد، صفر تداخل
- [x] CPT `tp_visa` ساخته شد
- [x] `tp_visa` به متاباکس مشترک اضافه شد
- [x] رابطهٔ `applies_g1_p1` ساخته شد — شناسه ۱۸۵
- [x] برچسب سمت والد رابطه پر شد
- [x] `wpml-config v1.10` تولید و برنامه‌ای راستی‌آزمایی شد
- [x] زیرفیلدهای `audio_narration` راستی‌آزمایی شدند
- [ ] هفت واژه‌نامه ساخته شود
- [ ] متاباکس اختصاصی با ۲۷ فیلد و ۳۶ زیرفیلد ساخته شود
- [ ] `wpml-config v1.10` در پنل پیست شود
- [ ] ممیزی NotebookLM روی `CA-P-AMENDMENT-002` انجام شود
- [ ] آزمون `P5-T005` اجرا شود
- [ ] رکورد آزمایشی و بکاپ Duplicator
- [ ] ورود دادهٔ واقعی

---

## بخش ۱۱ — پرامپت ممیزی NotebookLM

```
منابع: 35-CA-P-PRACTICAL · 20-IA-FROZEN · 50-LIVE-STATE · 51-BUILD-HISTORY
       · 61-RESEARCH-VISA-MFA · 70-CODE-AND-METABOX

بستهٔ ساخت P1 نسخهٔ ۲.۰ و اصلاحیهٔ CA-P-AMENDMENT-002 پیشنهاد شده‌اند که
به‌طور چشمگیر از متن اصلی CA-P فراتر می‌روند. فقط بر پایهٔ منابع بالا برای
هر بند بگو «منطبق»، «مغایر» یا «خارج از دامنهٔ منابع»:

۱. CA-P برای P1 ده فیلد الزامی و چهار اختیاری تعریف کرده. بسته ۲۷ فیلد
   سطح‌بالا و ۳۶ زیرفیلد دارد. آیا افزودن فیلد به یک موجودیت، بدون افزودن
   موجودیت یا رابطه، Architecture Freeze را نقض می‌کند؟ آیا سابقهٔ افزودن
   فیلد فراتر از CA در پروژه هست (RB-D021، RB-D023، CA-A-AMENDMENT-001)؟

۲. فیلد visa_exempt_countries (Text[]) با یک Repeater حدوداً دویست‌ردیفی
   به‌ازای کشور جایگزین شده. آیا این Entity Inflation است؟ بند ۷ گزارش
   بازبینی CA-P گفته «هیچ Sub-Entity ساخته نشد — همه Structured Field
   هستند». آیا این پیشنهاد با آن جمله سازگار است؟

۳. یک واژه‌نامهٔ سراسری country_iso بدون پیشوند موجودیت پیشنهاد شده،
   برخلاف قاعدهٔ RB-D020. آیا استثنا موجه است؟ آیا سابقهٔ واژه‌نامهٔ بدون
   پیشوند در ساختار بازسازی‌شده وجود دارد؟

۴. گروه فیلد مستقل برای رژیم مناطق آزاد اضافه شده. آیا CA-G یا CA-P جایی
   رژیم روادید مناطق آزاد را به موجودیت دیگری نسبت داده که تداخل کند؟
   فیلد free_zone روی G3 و G5 چه نقشی دارد؟

۵. مدعی‌ام dateModified روی GovernmentService نامعتبر است چون ویژگی
   CreativeWork است، و باید به گرهٔ WebPage برود. آیا سند وضعیت زنده
   سابقهٔ مشابه ثبت کرده؟

۶. فقط یک رابطه (applies_g1_p1، شناسه ۱۸۵) ساخته شده. آیا با DEC-004،
   P5-D045 و ماتریس R22 منطبق است؟ آیا رابطهٔ دیگری برای P1 در R01–R27
   تعریف شده که از قلم افتاده باشد؟

۷. آیا فیلد یا الزامی در بخش P1 سند CA-P هست که در این بسته اصلاً پوشش
   داده نشده؟ فهرست کن.

برای هر پاسخ سند و بخش دقیق منبع را ذکر کن.
```

---

*ToPersia — P1-BUILD-PACKAGE-v2_0*
