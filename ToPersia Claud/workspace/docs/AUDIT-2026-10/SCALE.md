# ممیزی مقیاس‌پذیری بک‌اند — ابری I (فقط‌خواندنی، ۱۸ اکتبر ۲۰۲۶)

> هیچ کد، تنظیم، داده یا آزمونی عوض نشد. پایه: `main` در `800550ae` (fast-forward از `0e8b5d54`؛ دو commit کدکس روی `tp-boundaries`).
> هیچ عددی در این سند **اندازه‌گیری‌شده** نیست؛ این‌جا وردپرس، پایگاه‌داده و WP-CLI نیست. هر عدد یا از سند مخزن نقل شده (`[VERIFIED]` با پرونده:خط)، یا حساب سرانگشتی است (`[INFERRED]`)، یا باید محلی سنجیده شود (`[UNKNOWN]` + فرمان؛ فهرست کامل در §۸ با شناسهٔ `LQ-SCALE-*`).
> مسیرها: `RENDER` = `plugin/topersia-render` · `CORE` = `plugin/topersia-core` · `SCHEMA` = `plugin/topersia-schema` · `AIS` = `plugin/topersia-ai-summarizer` · `UC` = `plugin/topersia-user-core` · `JE` = `geo/wp-content/plugins/jet-engine` · `RM` = `geo/wp-content/plugins/seo-by-rank-math` · `WSM` = `geo/wp-content/plugins/wp-seo-multilingual`.
> نام جدول‌ها با `$(wp db prefix)` نوشته شده‌اند؛ پیشوند واقعی پایگاه‌داده در این سند نیست (RECONCILE، یافتهٔ پیشوند).

---

## ۰ — مقیاس هدف و فرض‌ها

| ادعا | برچسب |
|---|---|
| هدف: ۱۹۴٬۰۰۰ صفحه | `[VERIFIED]` `docs/HANDOVER-Template-G-v2_0.md:348` · `docs/CODEX-BRIEF.md:102` · `CORE/topersia-core.php:978` |
| نُه زبان | `[VERIFIED]` `docs/ARCHITECT-BRIEF.md:11` |
| سقف هزینه و نیروی کار: «≤۲۰h فنی در هفته، ≤۲۰۰$/ماه، Managed-first» (REQ-O-01) | `[VERIFIED]` `docs/DECISIONS-ARCHITECT-2026-10-11.md:139` |
| معیار نمایهٔ مکانی ۱۰۰٬۰۰۰ نقطه | `[VERIFIED]` `docs/DECISIONS-ARCHITECT-2026-10-11.md:144,154` |
| ۱۳۹ رابطهٔ جت‌انجین؛ ۲۴ رابطه ردیف دارند؛ ۱٬۵۷۸ ردیف در دادهٔ محلی ۱۲ اکتبر | `[VERIFIED]` `docs/AUDIT-RELATIONS-2F.md:4,16` · جمع ستون `rows` در `docs/AUDIT-RELATIONS-2F.csv` |
| رابطه‌هایی که هر نوع یک سرشان است: استان ۳۵ · شهر ۳۳ · روستا ۲۱ · کشور ۷ | `[VERIFIED]` شمارش ستون‌های `parent`/`child` در `docs/AUDIT-RELATIONS-2F.csv` |
| ۴۳ نوع پست با متا؛ میانهٔ ۲۹ فیلد برای هر نوع (کمینه ۲۰، بیشینه ۵۱) | `[VERIFIED]` شمارش `docs/AUDIT-META-2F.csv` |
| **تفسیر:** ۱۹۴ هزار = جمع صفحه‌ها در هر نُه زبان ← حدود **۲۱٫۶ هزار موجودیت مبدأ** (فارسی) و تا ۹ نسخه برای هر کدام | `[INFERRED]` — سند صریح نمی‌گوید «جمع» یا «به ازای هر زبان». اگر به ازای هر زبان باشد، همهٔ برآوردهای زیر ×۹ می‌شوند |
| ردیف‌های `postmeta`: حدود ۱۹۴k × (۲۹ فیلد + حدود ۱۰ متای WPML/Rank Math/ToPersia) ≈ **۷ تا ۸ میلیون ردیف** | `[INFERRED]` — حساب سرانگشتی؛ `LQ-SCALE-01` |
| ردیف‌های رابطه: هر یال روی پست فارسی است، ولی همگام‌ساز WPML جت‌انجین «ترجمه ← طرف مقابل» هم می‌سازد | `[VERIFIED]` توضیح `RENDER/includes/topersia-g-zones.php:757-761` · اندازه `[UNKNOWN]` `LQ-SCALE-02` |

---

## ۱ — کار سرور در یک صفحهٔ موجودیت (استان، شهر، جاذبه)

### ۱-الف — زنجیرهٔ فراخوانی

صفحه یک شورت‌کد است: `[tp_page]` ← `topersia_g_sc_page` (`RENDER/includes/topersia-g-zones.php:5857`). ستون اصلی به ترتیب ثابت (`:5717-5732`): خلاصهٔ AI · درباره · گالری · جاذبه‌ها · شهرها · استان‌ها · محله‌ها · برنامه‌های نشان · فرهنگ · اخبار · پرسش‌ها · نظرها؛ ستون کناری از ثبت‌نامهٔ پنل. سپس مسیر راهنما، hero، آمار، نوار موبایل و **مودال فهرستِ هر اسلایدر** (`:5979-5993`). **کل خروجی هیچ کشی ندارد** (§۲) و کش عنصر المنتور هم عمداً برایش خاموش است (`RENDER/topersia-render.php:537-544`, `[VERIFIED]`).

### ۱-ب — هر پرس‌وجو و هر فراخوانی جت‌انجین/WPML

| # | کجا | چه می‌کند | به ازای | رشد | برچسب |
|---|---|---|---|---|---|
| Q1 | `RENDER/includes/topersia-g-zones.php:810-855` `topersia_g_rel_ids` | برای هر (پست، رابطه) یک `get_children`/`get_parents` جت‌انجین | هر spec | خطی در شمار رابطه | `[VERIFIED]` |
| Q2 | `JE/includes/components/relations/relation.php:674-699` | `SELECT * FROM {jet_rel_default|jet_rel_N} WHERE rel_id=… AND parent_object_id=…` با `wp_cache` گروه `jet_engine_rel`؛ **نتیجهٔ تهی در wp_cache نوشته نمی‌شود** (`if ( ! $children )` در `:697`) — با کش شیء پایدار هم رابطهٔ تهی هر درخواست دوباره پرس‌وجو می‌شود. درون یک درخواست، `storage/db.php:280-354` تهی را هم نگه می‌دارد | هر (پست، رابطه، جهت) | خطی | `[VERIFIED]` |
| Q3 | `JE/.../storage/manager.php:70` | شاخص جدول رابطه: `KEY parent_id (rel_id, parent_object_id)` و `KEY child_id (rel_id, child_object_id)` — هر Q2 با شاخص است | — | ثابت در هر پرس‌وجو | `[VERIFIED]` |
| Q4 | `RENDER/includes/topersia-g-zones.php:697-725` `topersia_g_chain_ids` | زنجیره: پلهٔ بعد روی **تک‌تک** نتیجه‌های پلهٔ قبل؛ استان ← شهرستان (۷۲) ← شهر (۷۳) = ۱ + (شمار شهرستان) پرس‌وجو. جت‌انجین `get_children( array )` با `IN` را می‌پذیرد (`relation.php:681-686`) ولی استفاده نشده | هر گره میانی | خطی در شمار گره میانی | `[VERIFIED]` |
| Q5 | `:766-795` `topersia_g_published` | دروازهٔ انتشار: برای هر شناسه `get_post` (`:732-740`) + `wpml_object_id` + برای ترجمه دوباره `get_post` | **هر عضو** | N+1 | `[VERIFIED]` |
| Q6 | `:641-680` `topersia_g_sort_entities` | برای هر عضو `wpml_object_id` (`view_id` `:857-873`) و سه متا (`display_priority` · `population` · نام) ← بار کامل متای هر پست (`update_meta_cache` یک پرس‌وجو برای هر پست) | **هر عضو** | N+1 | `[VERIFIED]` |
| Q7 | `:3335-3380` `topersia_g_rel_items` | **همهٔ** اعضای گروه را می‌گیرد، از Q5 و Q6 می‌گذراند، بعد ۸ تا را برمی‌دارد (`topersia_g_slider_max` `:3418-3421`) | هر بخش رابطه | خطی در **کل** اعضا، نه ۸ | `[VERIFIED]` |
| Q8 | `:6413` به بعد `topersia_g_list_modal` + `:5979-5993` | برای هر اسلایدر با بیش از ۸ عضو: دوباره همان گروه را می‌پیماید و **همهٔ** اعضا (نام، برچسب، `get_permalink`) را در HTML سرور می‌گذارد | هر بخش | خطی؛ اندازهٔ HTML بی‌سقف | `[VERIFIED]` |
| Q9 | `:2302-2309` `topersia_g_fact_count` | شمار حقیقت در زبان پیش‌فرض: همان گروه بار سوم (با `wpml_switch_language`) | هر کارت آمار | خطی | `[VERIFIED]` |
| Q10 | `:2935` (پاپ‌آپ نقشه) | `places` بار چهارم | استان | خطی | `[VERIFIED]` |
| Q11 | `RENDER/includes/topersia-g-relreg.php:124-235` `place_parents`/`place_chain` | مسیر راهنما و `containedInPlace`: تا ۶ پله؛ هر پله `topersia_g_relreg()` را **از نو** می‌سازد (حلقه روی ۱۳۹ رابطه، بی حافظه، `:30-64`) + Q2 + `topersia_g_public_url` (`get_post_type_object` · `get_post_status` · `get_permalink`) + برای ترجمه `topersia_g_default_id` (`zones:103-117`، `wpml_object_id`) و دوباره Q2 | هر پله | ثابت در عمق (≤۶) | `[VERIFIED]` |
| Q12 | `RENDER/includes/topersia-g-zones.php:3248-3330` | «اطلاعات سفر» با ارث: پیمودن نیاکان (۷۳ ← ۷۲ ← ۷۱) و برای هر دسته Q1 | صفحه | ثابت | `[VERIFIED]` |
| Q13 | `SCHEMA/topersia-schema.php:597,615,871-879,1013-1029` | اسکیما دوباره زنجیرهٔ والد را با `get_parents` و `wpml_object_id` می‌پیماید — مستقل از render | صفحه | ثابت در عمق | `[VERIFIED]` |
| Q14 | `RENDER/includes/topersia-g-news.php:205-240` | اخبار: Q1 + `get_posts( posts_per_page => -1, meta_key => _tp_news_ancestor, meta_value => id )` + Q5 روی همه + `WP_Query post__in` صفحه‌بندی‌شده | صفحه | خطی در شمار خبرِ مکان | `[VERIFIED]` |
| Q15 | `RENDER/includes/topersia-g-zones.php:3562-3606` `topersia_g_programmes` (فقط کشور، `:5727-5728`) | همهٔ `tp_designation` با `numberposts -1` + برای هر برنامه `get_posts( numberposts -1, meta_query key=… value='true' )` روی **همهٔ** نوع‌ها + Q5/Q6 روی همه. حافظه فقط درون درخواست (`static $memo`) | صفحهٔ کشور × ۹ زبان | خطی در شمار **سراسری** اعضای هر برنامه | `[VERIFIED]` |
| Q16 | `RENDER/includes/topersia-g-ai.php:152` ← `AIS/includes/class-tp-ai-summary.php:390` ← `AIS/includes/class-tp-ai-core-bridge.php:128-140` ← `CORE/includes/topersia-cache.php:47-68` | جعبهٔ AI در هر بازدید `is_stale` را می‌پرسد ← `topersia_content_hash`؛ اگر متای `_tp_content_hash` پاک شده باشد (§۲)، **همین درخواست GET** هش را می‌سازد: همهٔ متای پست + برای **هر رابطهٔ فعال نوع** (استان ۳۵، شهر ۳۳) یک Q2 + `get_post` برای هر همسایه (`:106-147`) + `update_post_meta` (نوشتن در خواندن) | بازدید نخست پس از هر ابطال | خطی در شمار همسایه | `[VERIFIED]` |
| Q17 | `RENDER/includes/topersia-g-gallery-agg.php:170-200` | گالری تجمیعی در بازدید: فقط یک متا (`_tp_gallery_agg`) روی پست زبان اصلی — **ثابت** (P5-D300) | صفحه | O(1) ✓ | `[VERIFIED]` |
| Q18 | `RENDER/includes/topersia-g-zones.php:2705-2710` · `topersia-g-air.php:230-237` | هوا و کیفیت هوا در HTML فقط جای‌نگهدار است؛ داده با REST سمت مرورگر (§۲) — در زمان تولید HTML صفر پرس‌وجو | صفحه | O(1) ✓ | `[VERIFIED]` |
| Q19 | `RENDER` و `CORE` | **هیچ** `update_meta_cache` · `_prime_post_caches` · `update_post_caches` · `wp_cache_get/set` در render و core نیست — هیچ پیش‌بارگذاری دسته‌ای | — | — | `[VERIFIED]` (`grep` بی نتیجه، شاخهٔ `800550ae`) |

### ۱-ج — کدام‌ها با تعداد پست رشد می‌کنند؟

| الگو | نمونه‌ها | رشد |
|---|---|---|
| **N+1 در هر عضو** (پست + متا + WPML) | Q5 · Q6 · Q8 · Q15 · Q16 | خطی در شمار اعضای گروه؛ بزرگ‌ترین روی استان (شهرها و جاذبه‌ها از راه زنجیره) و کشور (برنامه‌ها) |
| **پیمودن تکراری یک گروه در یک درخواست** | Q7 + Q8 + Q9 + Q10 (`places` استان تا ۴ بار) | ضریب ۲ تا ۴ روی Q4؛ کش شیء درون‌درخواستی Q5/Q6 را از بار دوم ارزان می‌کند، ولی حلقهٔ PHP و `wpml_object_id` تکرار می‌شوند |
| **`numberposts -1` در مسیر بازدید** | Q14 (خبر)، Q15 (برنامه‌ها) | خطی؛ Q15 سراسری |
| **`meta_query` برابری روی `meta_value`** | Q14 (`_tp_news_ancestor`)، Q15 (`… = 'true'`) | پویش همهٔ ردیف‌های آن `meta_key` (شاخص فقط `meta_key(191)`، §۳-ث) |
| **LIKE روی متا** | جستجو (§۴) — نه در صفحهٔ موجودیت | پویش کامل |
| **orderby روی متا** | در SQL نیست؛ مرتب‌سازی در PHP پس از بار همهٔ متاها (Q6) | — |
| **زنجیرهٔ بازگشتی گالری (P5-D274)** | خواندن O(1)؛ ساختن در صف (§۳-ج) | خواندن ✓ |
| **مسیر راهنما** | Q11 · Q13: عمق ≤۶، هر پله ثابت | ثابت در عمق، نه در شمار پست ✓ |

### ۱-د — برآورد پرس‌وجو در یک صفحه `[INFERRED]`

مدل: `Q ≈ R + Σ_g (C_g) + M × (۱ متا + ۱ پست + ~۱ WPML) + K`؛ R = شمار (پست، رابطه)، C_g = گره‌های میانی زنجیره، M = اعضای یکتای همهٔ گروه‌ها، K = ثابت‌های قالب، وردپرس، المنتور، Rank Math و WPML.

| صفحه (فرضی) | M | برآورد پرس‌وجوی ToPersia | ملاحظه |
|---|---|---|---|
| جاذبه | ۱۰–۳۰ | ۵۰–۱۵۰ | سبک |
| شهر بزرگ (۵۰ روستا/محله + ۱۵۰ جاذبه/طبیعت) | ۲۰۰ | ۶۰۰–۸۰۰ | + مودال‌ها |
| استان (۲۰ شهرستان، ۱۵۰ شهر، ۳۰۰ جاذبه) | ۴۵۰ | ۱٬۴۰۰–۱٬۸۰۰ | + Q16 با ۳۵ رابطه اگر هش کثیف است |
| کشور (۳۱ استان + برنامه‌های نشان سراسری با چند هزار عضو) | ۲٬۰۰۰+ | ۶٬۰۰۰+ | Q15 بزرگ‌ترین؛ فقط ۹ نشانی |

نقطهٔ شکست برآوردی `[INFERRED]`: با ۰٫۳ تا ۱ میلی‌ثانیه برای هر پرس‌وجوی ساده، استان ۰٫۵ تا ۲ ثانیه فقط زمان پایگاه‌داده دارد؛ با خزش هم‌زمان (چند درخواست در ثانیه از Googlebot و خزنده‌های AI روی ۱۹۴ هزار نشانی) کارگرهای PHP میزبان اشتراکی/مدیریت‌شده پر می‌شوند. **سنجش:** `LQ-SCALE-03` (شمار پرس‌وجو و زمان هر نوع صفحه با `$wpdb->num_queries`)، `LQ-SCALE-04` (Query Monitor: پرس‌وجوهای تکراری و کندترین‌ها).

---

## ۲ — کش مرکزی (P5-D250) و ابطال

### ۲-الف — چه کش می‌شود و چه نه

| محاسبه | کش؟ | کجا | برچسب |
|---|---|---|---|
| هوا (Met Norway / Open-Meteo) | ✓ `weather`، کلید سلول ۰٫۰۱° یا سلول شبکه، `lang` ثابت `fa` (مشترک بین زبان‌ها) | `RENDER/includes/topersia-g-weather.php:756-764,870-879` | `[VERIFIED]` |
| کیفیت هوا | ✓ `air_quality` تا پایان ساعت | `RENDER/includes/topersia-g-air.php:138-147` | `[VERIFIED]` |
| نقاط نقشه `/map-points` | ✓ `map.points` با کلید (زمینه، زبان، **گواه سراسری نمایه**)، TTL ۱۵ دقیقه | `RENDER/includes/topersia-g-mappoints.php:139-147` | `[VERIFIED]` |
| خلاصهٔ AI | ✓ `ai.text` گران، انبارهٔ جدول خلاصه‌ساز، هش کنار مقدار | `AIS/includes/class-tp-ai-summary.php:275-293` | `[VERIFIED]` |
| مسیریابی/فاصله/آدرس | ✓ جدول `topersia_cache` — **فقط محلی** | `CORE/includes/topersia-cache.php:290-293,329-331` | `[VERIFIED]` |
| گالری تجمیعی | ✓ متای مشتق، نه کش مرکزی | `RENDER/includes/topersia-g-gallery-agg.php:17-21` | `[VERIFIED]` |
| **کل HTML `[tp_page]`** | ✗ | `topersia_g_sc_page` بی کش؛ کش عنصر المنتور خاموش (`RENDER/topersia-render.php:537-544`) | `[VERIFIED]` |
| **پیمودن رابطه‌ها، دروازهٔ انتشار، مرتب‌سازی، مودال‌ها، شمار حقیقت** | ✗ (فقط wp_cache درون‌درخواستی جت‌انجین) | Q1–Q10 | `[VERIFIED]` |
| **برنامه‌های نشان کشور** | ✗ (فقط `static` درون درخواست) | `zones:3564-3570` | `[VERIFIED]` |
| **زنجیرهٔ اسکیما** | ✗ | Q13 | `[VERIFIED]` |
| **هش محتوا** | نیمه: در متا، ولی در **خواندن** ساخته می‌شود | `CORE/includes/topersia-cache.php:47-68` | `[VERIFIED]` |

جمع‌بندی: لایهٔ P5-D250 برای **فراخوانی سرویس بیرونی** طراحی شده (`topersia-cache.php:6`، «هر فراخوانی سرویس بیرونی از این‌جا می‌گذرد») — گران‌ترین کار سرور در این مقیاس، یعنی **پیمودن گراف و ساختن HTML**، بیرون آن است.

### ۲-ب — ابطال

| رخداد | چه کثیف می‌شود | برچسب |
|---|---|---|
| `save_post` و هر متای بی‌زیرخط | فقط خود پست (`delete_post_meta _tp_content_hash`) | `CORE/includes/topersia-cache.php:195-208` `[VERIFIED]` |
| تغییر وضعیت و حذف | خود پست + **همهٔ همسایه‌ها در همهٔ رابطه‌های فعال** (`topersia_content_hash_neighbours` — برای هر رابطه یک Q2، هر وضعیتی) | `:171-193,210-220` `[VERIFIED]` |
| تغییر ردیف رابطه | دو سر یال | `:222-228` `[VERIFIED]` |
| پایان ترجمهٔ WPML | خود ترجمه | `:231-233` `[VERIFIED]` |
| هر نوشتن در نمایهٔ مکانی | **گواه سراسری** `topersia_geo_index_stamp` ← همهٔ کلیدهای `map.points` همهٔ زمینه‌ها و زبان‌ها بی‌اعتبار | `CORE/includes/topersia-geo-index.php:140-147,150-168` `[VERIFIED]` |
| گالری تجمیعی | خود پست + نیاکان تا کشور، در صف AS با ادغام تکراری؛ پله‌ها با تأخیر ۳۰ ثانیه | `RENDER/includes/topersia-g-gallery-agg.php:335-385,520-576` `[VERIFIED]` |

### ۲-ج — P5-D301: پاک‌کردن صفحهٔ والدها

- متن تصمیم: «کش صفحهٔ فاز ۹ باید با تغییر وضعیت یا محتوای هر فرزند، صفحهٔ والدهایش را پاک کند» (`docs/SPRINT-4.4-PHASES.md:125`) `[VERIFIED]`.
- **امروز هیچ پاک‌کنندهٔ صفحه‌ای در کد نیست** (`grep -i purge|rocket_|litespeed` در render/core خالی) و کش صفحه‌ای هم نیست؛ تنها پیاده‌سازی، خاموش‌کردن کش عنصر المنتور است `[VERIFIED]`.
- **چند کلید باید پاک شود** `[INFERRED]`:
  - تغییر یک **فرزند برگ** (جاذبه): نیاکان (شهر، استان، کشور = ۳) + هم‌گروه‌هایی که آن را در کاشی/اسلایدر دارند (هم‌مرزها، «اطراف») × ۹ زبان ≈ **۳۰ تا ۶۰ نشانی**.
  - تغییر **نام یا وضعیت استان**: نام استان در مسیر راهنما، کاشی جغرافیا و `containedInPlace` **همهٔ نوادگان** است ← چند صد تا چند هزار پست × ۹ زبان ≈ **۵ تا ۳۰ هزار نشانی** برای یک ویرایش.
  - تغییر **کشور**: عملاً کل سایت.
- پیامد: پاک‌کردن «نشانی به نشانی» در این مقیاس شکننده است؛ راه پایدار برچسب‌گذاری کش (surrogate key / cache tag) است (§۷، SC2).

### ۲-د — کلید به ازای زبان

- کلید کش مرکزی بخش `l` (زبان) دارد (`CORE/includes/topersia-cache.php:283`) `[VERIFIED]`؛ `map.points` زبان صفحه را می‌دهد (`mappoints:141,145`)؛ هوا عمداً `fa` ثابت است و بین زبان‌ها مشترک ✓؛ خلاصهٔ AI در انبارهٔ خود با (پست، نوع، زبان) (`class-tp-ai-summary.php:276-291`) ✓.
- هش محتوا زبان پست را درون خود دارد (`topersia-cache.php:96-103`) ✓.

### ۲-ه — کش شیء پایدار (Redis/Memcached) لازم است؟

**بله، در این مقیاس لازم است** `[INFERRED]`:
1. بی آن، همهٔ کش‌های مرکزی غیرجدولی transient در `wp_options` هستند (`topersia-cache.php:382`) — همان R45 (`docs/AUDIT-2026-10/RISKS.md:67`).
2. wp_cache گروه `jet_engine_rel`، کش ترجمهٔ WPML و `alloptions` فقط درون درخواست زنده‌اند؛ هر بازدید همهٔ Q2/Q5/Q6 را از صفر می‌پرسد.
3. **ولی کافی نیست:** نتیجهٔ تهی رابطه کش نمی‌شود (Q2، `JE/.../relation.php:697`)، ۱۱۵ از ۱۳۹ رابطه ردیف ندارند (`docs/AUDIT-RELATIONS-2F.md:16`) — پس با Redis هم بخش بزرگی از Q2 هر بار به پایگاه‌داده می‌رود؛ و Redis جای کش HTML را نمی‌گیرد.
4. هشدار: با Redis، `update_option` روی گزینهٔ autoload (§۳-الف، کلیک AI) هر بار `alloptions` را باطل می‌کند.

سنجش: `LQ-SCALE-05` (`wp cache type`، نرخ hit)، `LQ-SCALE-03` با و بی Redis.

---

## ۳ — رشد داده

### ۳-الف — autoload در `wp_options`

| گزینه | autoload | کجا | برچسب |
|---|---|---|---|
| `topersia_cache_schema` · `topersia_geo_index_*` · `topersia_gallery_agg_last` · `topersia_news_rules` · `TOPERSIA_SERVICES_*` · `TOPERSIA_PACK_LAST_RUN` · `topersia_log_schema` · گزینه‌های UC | `false` ✓ | `CORE/includes/topersia-cache.php:322` · `topersia-geo-index.php:79,144` · `RENDER/includes/topersia-g-gallery-agg.php:477,502` · `topersia-g-news.php:390` · `CORE/includes/topersia-services.php:377,598,644,704` · `topersia-pack.php:2266,3418,3447` | `[VERIFIED]` |
| `TOPERSIA_OPTION_KEY` | `true` | `CORE/topersia-core.php:4728` | `[VERIFIED]`؛ اندازه `[UNKNOWN]` `LQ-SCALE-06` |
| `TOPERSIA_COMMERCIAL_OPTION` | `true` (بولی) | `CORE/includes/topersia-services.php:728` | `[VERIFIED]` |
| آمار کلیک AI (`STATS_KEY`) | پیش‌فرض (auto) و **در هر کلیک نوشته می‌شود** | `AIS/includes/class-tp-ai-tracker.php:50-56` | `[VERIFIED]` |
| `topersia_services_usage` | `false`، ولی **در هر فراخوانی بیرونی** خواندن-تغییر-نوشتن (بی قفل) | `CORE/includes/topersia-services.php:360-378` | `[VERIFIED]` |
| `tpas_settings` (کلید AI) | autoload (یافتهٔ پیشین RECONCILE) | — | ارجاع به `RECONCILE.md` §۳-ج |

کد ToPersia autoload را کم نگه داشته؛ خطر اصلی autoload از افزونه‌های ثالث (WPML، Rank Math، Elementor، JetEngine) است `[INFERRED]` — `LQ-SCALE-06` (`wp option list --autoload=on --format=total_bytes` و ۲۰ گزینهٔ بزرگ).

### ۳-ب — transientها به ازای پست و زبان `[INFERRED]` (تعداد)

| کلید | کاردینالیتی در مقیاس هدف | ملاحظه |
|---|---|---|
| `tpc_*` هوا | ≈ شمار مختصات یکتای موجودیت‌ها (تا ~۲۱k) + هر مختصاتی که کاربر/ربات به REST بدهد (سلول ۰٫۰۱° ایران: میلیون‌ها) | مشترک بین زبان‌ها ✓؛ ورودی کاربر سقف ندارد (R45) |
| `tpc_*` کیفیت هوا | مثل بالا، سلول درشت‌تر | تا پایان ساعت |
| `tpc_*` `map.points` | زمینه‌ها (استان + شهر) × ۹ زبان × **هر گواه** — هر ویرایش گواه را عوض می‌کند و کلیدهای کهنه تا انقضا (`keep` یک ساعت) در `wp_options` می‌مانند | فقط محلی امروز (`topersia_geo_index_enabled`) |
| `topersia_pack_*` | یک transient برای هر بارگذاری، **تا ۱۰ مگابایت**، ۱۲ ساعت؛ در **هر دسته** دوباره نوشته می‌شود | `CORE/includes/topersia-pack.php:51-52,3086,3417,3441` `[VERIFIED]` |
| Rank Math sitemap | در حالت `db`: یک transient برای هر صفحهٔ نقشه، ۱۰۰ روز | `RM/includes/modules/sitemap/class-cache.php:51-57,143` `[VERIFIED]` |

### ۳-ج — صف Action Scheduler

| ادعا | برچسب |
|---|---|
| core نسخهٔ 3.9.3 را بسته‌بندی کرده؛ Rank Math هم AS دارد و بالاترین نسخه بار می‌شود | `CORE/vendor/action-scheduler/action-scheduler.php:8` `[VERIFIED]`؛ نسخهٔ بارشده `[UNKNOWN]` (`LQ-SYSTEM` پیشین) |
| گروه واحد `topersia`؛ پیش از هر افزودن یک `as_get_scheduled_actions( hook, args, status=pending, per_page=-1 )` برای ادغام تکراری | `CORE/includes/topersia-queue.php:25-26,45-84` `[VERIFIED]` |
| شاخص‌های جدول AS: `hook_status_scheduled_date_gmt` و `args(191)` — جستجوی تکراری با شاخص است اگر args کوتاه بماند | `CORE/vendor/action-scheduler/classes/schema/ActionScheduler_StoreSchema.php:76-85` `[VERIFIED]` |
| پاک‌کن پیش‌فرض: نگهداری ۳۰ روز، دسته‌های ۲۰تایی | `.../classes/ActionScheduler_QueueCleaner.php:45-61` `[VERIFIED]` |
| خلاصه‌ساز گروهی: یک کار برای هر (پست، نوع، زبان)، با سقف روزانه اگر موتور بیرونی است | `AIS/includes/class-tp-ai-generator.php:217-262` `[VERIFIED]` |
| گالری تجمیعی: برای هر تغییر ≤۳ کار (شهر، استان، کشور)؛ «بازسازی همه» یک کار برای هر شهر/استان/کشور | `RENDER/includes/topersia-g-gallery-agg.php:366-385,488-505` `[VERIFIED]` |
| برآورد: درون‌ریزی یا خلاصه‌سازی کل سایت = ۲۱٫۶k × نوع‌ها × ۹ زبان ≈ **۲۰۰ تا ۶۰۰ هزار** کار و **۳ ردیف لاگ برای هر کار** در ۳۰ روز | `[INFERRED]` |
| گزینهٔ `unique` خود AS فقط hook و group را می‌سنجد، نه args — برای ادغام به ازای پست مناسب نیست؛ روش فعلی درست است | `CORE/vendor/action-scheduler/functions.php:14` `[VERIFIED]` |

### ۳-د — جدول‌ها و شاخص‌های رابطهٔ جت‌انجین

- جدول پیش‌فرض یا جدول جدا برای هر رابطه (`db_table`)؛ شاخص `(rel_id, parent_object_id)` و `(rel_id, child_object_id)` (`JE/.../storage/manager.php:58-81`؛ `relation.php:289-300`) `[VERIFIED]` — برای Q2 کافی.
- ردیف‌ها: یال‌های فارسی + نسخه‌های همگام‌شدهٔ WPML (`zones:757-761`) — تا ×۹ `[INFERRED]`؛ `LQ-SCALE-02`.
- `SELECT *` (`storage/db.php:287`) با ستون‌های بی‌استفاده — کم‌اثر.

### ۳-ه — شاخص `wp_postmeta` برای کلیدهای پرس‌وجوشده

شاخص هسته فقط `post_id` و `meta_key(191)` است (`geo/wp-admin/includes/schema.php:156-157`) `[VERIFIED]` — هر شرط روی `meta_value` همهٔ ردیف‌های آن کلید را می‌پوید.

| کلید | پرس‌وجو | کجا | ردیف‌ها در مقیاس هدف `[INFERRED]` |
|---|---|---|---|
| `entity_uid` | برابری (`meta_value = uid`) در یافتن پست درون‌ریزی؛ و در `tp-boundaries` | `CORE/includes/topersia-pack.php:1373-1400` · `scripts/tp-boundaries.php:291` | تا ۱۹۴k (اگر به ترجمه کپی شود) |
| `name_{lang}` · `official_name_{lang}` | `IN (…)` | `CORE/includes/topersia-pack.php:1446-1451` | ۲۱k–۱۹۴k برای هر کلید |
| `_tp_news_ancestor` | برابری در هر صفحهٔ مکان | `RENDER/includes/topersia-g-news.php:208-217` | به شمار خبرها |
| کلیدهای برنامهٔ نشان | `= 'true'` | `RENDER/includes/topersia-g-zones.php:3592-3599` | به شمار پست‌های آن نوع‌ها |
| `_tp_search_fold` | `LIKE '%…%'` | `CORE/includes/topersia-fold.php:225-240` | ۱۹۴k |

سنجش: `LQ-SCALE-07` (`EXPLAIN` هر چهار الگو + شمار ردیف هر کلید).

---

## ۴ — جستجو (P5-D221 و P5-D265)

| ادعا | برچسب |
|---|---|
| جستجوی سایت: بند `EXISTS ( SELECT 1 FROM postmeta WHERE meta_key = '_tp_search_fold' AND meta_value LIKE '%…%' ) OR` جلوی بند LIKE خود وردپرس روی `post_title`/`post_excerpt`/`post_content` | `CORE/includes/topersia-fold.php:212-241` `[VERIFIED]` |
| فقط محلی (`topersia_search_fold_enabled` = محیط local) | `CORE/includes/topersia-fold.php:169-171` `[VERIFIED]` |
| LIKE با `%` آغازین هیچ شاخصی نمی‌گیرد ← پویش کامل `wp_posts` (همهٔ زبان‌ها) + زیرپرس‌وجوی هم‌بسته روی متا برای هر ردیف | `[INFERRED]` (رفتار شناخته‌شدهٔ MariaDB) — `LQ-SCALE-08` |
| مودال فهرست (P5-D221): جستجو فقط در مرورگر روی HTML سرور («فقط پنهان و پیدا می‌کند») — صفر پرس‌وجو، ولی HTML بی‌سقف (Q8) | `RENDER/includes/topersia-g-zones.php:6398-6411` `[VERIFIED]` |
| نقطهٔ شکست: با ۱۹۴k پست و ۷–۸M ردیف متا، هر جستجو صدها میلی‌ثانیه تا چند ثانیه؛ چند جستجوی هم‌زمان (یا ربات) پایگاه‌داده را قفل نمی‌کند ولی CPU را می‌خورد | `[INFERRED]` |
| **نمایهٔ جدا لازم است.** MariaDB 10.11 (پایگاه محلی، `CLAUDE.md` محیط) FULLTEXT InnoDB دارد ولی **تجزیه‌گر ngram ندارد** (برخلاف MySQL)؛ فارسی با فاصله جدا می‌شود پس تجزیه‌گر پیش‌فرض واژه‌ها را می‌گیرد، ولی `innodb_ft_min_token_size` پیش‌فرض ۳ واژه‌های دوحرفی را می‌اندازد و نیم‌فاصله باید پیش از نمایه تا شود (همان `topersia_search_fold`) | `[INFERRED]` — `LQ-SCALE-09` |

---

## ۵ — نقشهٔ سایت و hreflang در ۹ زبان

| ادعا | برچسب |
|---|---|
| Rank Math 1.0.279؛ نقشه‌ها صفحه‌بندی با `sitemap.items_per_page` (پیش‌فرض تنظیمات ۲۰۰؛ پیش‌فرض کد ۱۰۰) | `RM/rank-math.php:12` · `RM/includes/modules/sitemap/settings/general.php:28-32` · `class-generator.php:152` `[VERIFIED]` |
| کش نقشه: فایل اگر نوشتنی، وگرنه transient ۱۰۰ روزه | `RM/includes/modules/sitemap/class-cache.php:50-57,143` `[VERIFIED]` |
| ابطال: هر `save_post` کش **کل آن نوع** و نمایه را پاک می‌کند | `RM/includes/modules/sitemap/class-cache-watcher.php:61,94-102,220` `[VERIFIED]` |
| WPML SEO 2.2.4 برای **هر ورودی** نقشه پیوندهای `xhtml:link` همهٔ زبان‌ها را می‌سازد: ترجمه‌ها + `wpml_permalink` برای هر زبان | `WSM/plugin.php:8` · `WSM/classes/RankMathSEO/Sitemap/AlternateLangHooks.php:25-30` · `WSM/classes/Shared/Sitemap/BaseAlternateLangHooks.php:58-61,74-110` `[VERIFIED]` |
| Rank Math نقشهٔ هستهٔ وردپرس را خاموش می‌کند (`wp_sitemaps_enabled` ← false) | `RM/includes/modules/sitemap/class-redirect-core-sitemaps.php:39` `[VERIFIED]` |
| **یافته:** حذف `tp_county` از نقشه با صافی هسته `wp_sitemaps_post_types` است (`CORE/topersia-core.php:2563-2569`) — وقتی ماژول نقشهٔ Rank Math فعال است، این صافی **اثری ندارد** و شهرستان (صفحهٔ گراف‌فقط با ریدایرکت G4) ممکن است در نقشهٔ Rank Math بیاید | `[INFERRED]` — `LQ-SCALE-10` |
| حساب: ۱۹۴k نشانی ÷ ۲۰۰ ≈ **۹۷۰ پروندهٔ نقشه**؛ هر ورودی ~۱۰ پیوند (۹ زبان + x-default) ← هر پرونده ~۲٬۲۰۰ خط، چند صد کیلوبایت؛ سقف ۵۰٬۰۰۰ نشانی/۵۰MB در هر پرونده **نزدیک نمی‌شود**؛ نمایه ~۹۷۰ ورودی | `[INFERRED]` (حساب) — `LQ-SCALE-11` |
| هزینهٔ ساخت سرد هر پرونده: ۲۰۰ ورودی × (پرس‌وجوی ترجمه + ۹ `wpml_permalink`) + استخراج تصویر Rank Math؛ پس از درون‌ریزی گروهی، هر `save_post` کش آن نوع را پاک می‌کند ← خزندهٔ بعدی کل نوع را سرد می‌سازد | `[INFERRED]` |
| hreflang در `<head>` هر صفحه: WPML برای ۹ زبان ترجمه‌ها را می‌خواند — ثابت در هر صفحه | `[INFERRED]` |

---

## ۶ — اسکریپت‌ها و ابزارهای گروهی

| ابزار | حافظه | زمان | دسته‌بندی | برچسب |
|---|---|---|---|---|
| `scripts/tp-boundaries.php match` | همهٔ پست‌های استان، شهر و ۸ نوع پهنه **به‌صورت `WP_Post` کامل** (بی `fields => ids`) + کل `overpass-iran.json` با `json_decode` در حافظه | O(شهر × عنصر Overpass): برای هر شهر کل فهرست عنصرها با `tpb_norm` پیموده می‌شود؛ GeoJSON استان والد برای **هر شهر** دوباره از دیسک خوانده و decode می‌شود | فقط شبکهٔ `build` دسته‌ای (۳ استان یا ۶ شهر در هر پرس‌وجو، `sleep 2`)؛ `match` یک‌جا | `scripts/tp-boundaries.php:72,100-112,135,151,229-240` · `scripts/lib/tp-boundaries-lib.php:172-244` `[VERIFIED]` |
| ↳ مقیاس | فقط فارسی (`wpml_switch_language fa` `:100`) و فقط نوع‌های مکانی ← چند هزار پست، نه ۱۹۴k؛ فقط محلی (`:20`) | دقیقه‌ها `[INFERRED]` | — | اندازهٔ کش `[UNKNOWN]` `LQ-SCALE-12` |
| `scripts/tp-tiles-update.php` | مستقل از شمار پست: `pmtiles extract` با `passthru` | به اندازهٔ دانلود | — | `scripts/tp-tiles-update.php:92,108,121-129` `[VERIFIED]` |
| درون‌ریزی بسته (پیشخوان، AJAX — **WP-CLI نیست**) | کل پرونده (≤۱۰MB) decode و در transient؛ هر دسته کل آن را می‌خواند و **دوباره می‌نویسد** | برای هر پست: `entity_uid` (پویش `meta_value`) و نام‌ها (`IN` روی `meta_value`) ← O(N) در هر مورد، O(N²) برای درون‌ریزی بزرگ `[INFERRED]` | ✓ دسته‌های ۴ (تا ۲۰) برنامه، ۳ (تا ۱۰) اجرا، ۱۰ (تا ۵۰) بازگشت | `CORE/includes/topersia-pack.php:51-52,1275-1290,1373-1460,2108-2124,3383,3411-3441,3474` `[VERIFIED]` |
| `wp topersia geo-index rebuild` | همهٔ شناسه‌های **همهٔ زبان‌ها** (`suppress_filters => true`) در حافظه (اعداد — کم) | `TRUNCATE` و سپس درج **ردیف به ردیف**؛ برای هر پست فارسی زنجیرهٔ Q11 | یک‌جا؛ **نااتمی**: در طول بازسازی جدول خالی است و `/map-points` تهی برمی‌گرداند | `CORE/includes/topersia-geo-index.php:174-205,103-137` `[VERIFIED]` |
| `wp topersia gallery-agg --all` | فهرست شناسه‌ها | هم‌زمان، برای هر استان همهٔ شهرها و Q5/Q6 روی همه | یک‌جا (CLI)؛ دکمهٔ پیشخوان در صف | `RENDER/includes/topersia-g-gallery-agg.php:438-481,488-505` `[VERIFIED]` |
| `wp topersia gallery-agg --queue` | — | حداکثر ۸ دور اجرای کارهای در انتظار | — آبشار بلندتر از ۸ پله نیمه می‌ماند | `:645-658` `[VERIFIED]` |
| `wp topersia i18n-gaps` | `numberposts -1` برای هر نوع | خطی | یک‌جا | `CORE/includes/topersia-i18n-gaps.php:132-140` `[VERIFIED]` |

---

## ۷ — جدول ریسک‌های رتبه‌بندی‌شده

اثر: `SEO` · `GEO` (پاسخ موتورهای مولد) · `AEO` (پاسخ مستقیم) · `i18n` · `نگه` (نگه‌داری) · `$` (هزینهٔ میزبانی). ↑ = بهتر می‌شود اگر اصلاح شود؛ ⚠ = خطر اگر نشود.

| رتبه | ریسک | شاهد | نقطهٔ شکست برآوردی `[INFERRED]` | اصلاح پیشنهادی | مالک | آزمون/سنجش محلی | اثر |
|---|---|---|---|---|---|---|---|
| **SC1** | صفحهٔ موجودیت بی کش و با N+1: هر بخش کل اعضا را می‌پیماید، می‌سنجد و مرتب می‌کند، بعد ۸ تا را نشان می‌دهد؛ یک گروه تا ۴ بار در یک درخواست؛ بی پیش‌بارگذاری | Q4–Q10، Q19 (`zones:641-680,697-725,766-795,3335-3380,5979-5993`) | استان با ~۴۰۰+ عضو: ۱٬۵۰۰+ پرس‌وجو، TTFB بالای ۱ تا ۲ ثانیه؛ خزش هم‌زمان کارگرهای PHP را پر می‌کند | (۱) حافظهٔ درون‌درخواستی برای `topersia_g_group_ids` و `topersia_g_relreg`؛ (۲) `_prime_post_caches` + `update_meta_cache` روی کل اعضا پیش از Q5/Q6؛ (۳) پلهٔ زنجیره با `get_children( array )` (یک `IN`)؛ (۴) مرتب‌سازی و شمار در **لایهٔ مشتق** (جدول یا متای مرتب‌شده مثل گالری تجمیعی) تا بازدید فقط ۸ شناسه بخواند | render | `LQ-SCALE-03` · `LQ-SCALE-04` پیش و پس؛ آزمون واحد «شمار پرس‌وجوی `[tp_page]` استان ≤ X» | SEO ↑ (TTFB/CWV)، GEO/AEO ↑ (خزندهٔ AI صبور نیست)، i18n ⚠ (×۹ صفحه)، نگه: متوسط، $ ↑↑ |
| **SC2** | کش صفحه نیست و P5-D301 پیاده نشده؛ وقتی کش بیاید، ابطال «نشانی به نشانی» برای ویرایش استان/کشور هزاران نشانی می‌شود | §۲-ج؛ `docs/SPRINT-4.4-PHASES.md:125`؛ `RENDER/topersia-render.php:537-544` | بی کش صفحه: همان SC1 روی هر بازدید. با کش بی برچسب: یا محتوای کهنه (فرزند پیش‌نویس‌شده دیده می‌شود — یافتهٔ ۲ج٫۱) یا طوفان پاک‌کردن | کش HTML با **برچسب** (cache tag / surrogate key): هر صفحه برچسب `p{ID}` همهٔ پست‌هایی را که خوانده می‌گیرد (از همان دروازهٔ `topersia_g_published` جمع شود)؛ ابطال با برچسب. میزبان: LiteSpeed tags، Varnish xkey یا Cloudflare Cache-Tag. موقت: TTL کوتاه + stale-while-revalidate | render (برچسب‌ها) + تنظیم سرور | `LQ-SCALE-13`: ویرایش یک جاذبه ← فهرست نشانی‌هایی که باید تازه شوند در برابر آنچه تازه شد | SEO ↑↑، GEO/AEO ↑ (تازگی پاسخ)، i18n ↑ (برچسب مشترک ۹ زبان)، نگه: بالا یک‌بار، $ ↑↑↑ |
| **SC3** | هش محتوا در **GET** ساخته و نوشته می‌شود؛ تغییر وضعیت همهٔ همسایه‌ها را کثیف می‌کند ← پس از درون‌ریزی/انتشار گروهی، خزش نخست هزاران نوشتن و ۳۵ پرس‌وجوی رابطه برای هر استان | Q16؛ `CORE/includes/topersia-cache.php:47-68,106-147,171-220` | درون‌ریزی ۱۰۰۰ پست ← تا چند هزار هش کثیف؛ نخستین خزش = نوشتن هم‌زمان در `postmeta` (قفل ردیف، binlog) | ساختن هش فقط در صف (AS) پس از کثیف‌شدن؛ مسیر بازدید هش ذخیره را «نامعلوم = تازه» بخواند؛ همسایه‌ها با یک `IN` | core (+ AIS برای `is_stale`) | `LQ-SCALE-14`: شمار `_tp_content_hash` خالی پس از یک درون‌ریزی؛ `num_queries` بازدید نخست | AEO ↔، نگه ↑، $ ↑ |
| **SC4** | `meta_value` بی شاخص: `entity_uid`، نام‌ها، `_tp_news_ancestor`، کلیدهای برنامه | §۳-ه | ۷–۸M ردیف متا: هر یافتن `entity_uid` پویش ده‌ها هزار ردیف؛ درون‌ریزی O(N²) | (۱) شاخص ترکیبی `(meta_key(191), meta_value(64))` روی `postmeta` (یک‌بار، با پشتیبان)؛ (۲) جدول جستجوی مشتق `entity_uid → post_id, lang`؛ (۳) `_tp_news_ancestor` به رابطه یا جدول | تنظیم سرور (شاخص) + core (جدول) | `LQ-SCALE-07` (`EXPLAIN` پیش و پس) | SEO ↔، i18n ↑ (یافتن ترجمه)، نگه ↑، $ ↑ |
| **SC5** | جستجو با `LIKE '%…%'` روی پست و متا | §۴؛ `CORE/includes/topersia-fold.php:212-241` | ۱۹۴k پست: ثانیه‌ها برای هر جستجو؛ ربات جستجو = CPU | جدول مشتق `tp_search(post_id, lang, type, fold_title, fold_body)` با FULLTEXT و تاکردن P5-D265 پیش از نمایه؛ جستجوی پیشوندی با `MATCH … AGAINST ('واژه*' IN BOOLEAN MODE)` | core | `LQ-SCALE-08` · `LQ-SCALE-09` | SEO ↔ (صفحهٔ جستجو noindex)، AEO ↑ (جستجوی درون‌سایت)، i18n ↑ (ستون lang)، $ ↑ |
| **SC6** | transient در `wp_options` بی کش شیء پایدار؛ گواه سراسری نمایه همهٔ `map.points` را با هر ویرایش باطل می‌کند؛ کلید هوا از ورودی کاربر | §۲-الف، §۳-ب؛ `CORE/includes/topersia-geo-index.php:140-147`؛ R45 | میلیون‌ها ردیف transient؛ `delete_expired_transients` کند | Redis (معمولاً در میزبان مدیریت‌شده)؛ گواه **به ازای زمینه** (استان/شهر)؛ گرد کردن و محدود کردن مختصات REST هوا به مختصات موجودیت‌ها | تنظیم سرور + core + render | `LQ-SCALE-05` · `LQ-SCALE-15` | $ ↑↑، نگه ↑ |
| **SC7** | HTML بی‌سقف: مودال فهرست همهٔ اعضا؛ برنامه‌های نشان کشور با `numberposts -1` و `meta_value = 'true'` سراسری | Q8، Q15 | مودال استان با چند صد کارت ← HTML چند صد کیلوبایت؛ صفحهٔ کشور هزاران پست | سقف مودال (مثلاً ۱۰۰ همان P5-D276 برنامه‌ها) و بیش از آن صفحهٔ هاب ایندکس‌پذیر با صفحه‌بندی؛ برنامه‌ها از لایهٔ مشتق (شمار + ۸ شناسه) | render | `LQ-SCALE-03` (اندازهٔ HTML) | SEO ↑ (صفحهٔ هاب ایندکس‌پذیر بهتر از HTML پنهان)، GEO ↑، $ ↑ |
| **SC8** | نقشهٔ سایت: حذف `tp_county` با صافی هسته که در Rank Math بی‌اثر است؛ ~۹۷۰ پرونده × ۹ پیوند؛ ابطال کل نوع در هر ذخیره | §۵ | پس از درون‌ریزی، ساخت سرد صدها پرونده هنگام خزش | حذف `tp_county` در تنظیمات نوع پست Rank Math (یا `rank_math/sitemap/exclude_post_type`)؛ حالت فایل؛ گرم‌کردن پس از درون‌ریزی با حلقهٔ `curl` روی نمایه | core (صافی) + تنظیم سرور | `LQ-SCALE-10` · `LQ-SCALE-11` | SEO ↑↑ (بودجهٔ خزش، صفحهٔ ریدایرکت در نقشه)، i18n ↑ |
| **SC9** | درون‌ریزی بسته: transient ۱۰MB در هر دسته بازنویسی می‌شود؛ یافتن O(N) | §۶؛ `CORE/includes/topersia-pack.php:3417,3441,1373-1460` | پرونده‌های نزدیک ۱۰MB: هر دسته چند ثانیه فقط سریال‌سازی؛ `max_allowed_packet` | دادهٔ پرونده در uploads خصوصی یا جدول؛ transient فقط مکان‌نما (offset)؛ SC4 برای یافتن | core | `LQ-SCALE-16` (زمان هر دسته با پروندهٔ ۵ و ۱۰MB) | نگه ↑، $ ↔ |
| **SC10** | بازسازی نمایهٔ مکانی نااتمی (`TRUNCATE` سپس درج تک‌تک) و پیمودن همهٔ زبان‌ها | `CORE/includes/topersia-geo-index.php:174-205` | ۲۱k ردیف × زنجیره: دقیقه‌ها نقشهٔ تهی در زنده | ساختن در جدول سایه و `RENAME TABLE` اتمی؛ فقط شناسه‌های زبان پیش‌فرض؛ درج دسته‌ای | core | `LQ-SCALE-17` (زمان rebuild و بازهٔ تهی) | GEO ↑ (نقشه)، نگه ↑ |
| **SC11** | رشد صف AS: صدها هزار کار و لاگ در خلاصه‌سازی/درون‌ریزی سراسری؛ جستجوی ادغام برای هر افزودن | §۳-ج | جدول‌های AS چند میلیون ردیف در ۳۰ روز؛ صفحهٔ «کارها» کند | `action_scheduler_retention_period` به ۷ روز برای گروه `topersia`؛ افزودن گروهی با یک جستجوی ادغام؛ اجرای صف با WP-CLI/کرون سیستم (`DISABLE_WP_CRON`) | core + summarizer + تنظیم سرور | `LQ-SCALE-18` | نگه ↑، $ ↑ |
| **SC12** | نوشتن در خواندن: آمار کلیک AI در گزینهٔ autoload و `services_usage` در هر فراخوانی بیرونی (خواندن-تغییر-نوشتن بی قفل) | `AIS/includes/class-tp-ai-tracker.php:44-56` · `CORE/includes/topersia-services.php:360-378` | با Redis هر کلیک `alloptions` را باطل می‌کند؛ شمارش‌ها در هم‌زمانی گم می‌شوند | جدول شمارنده با `UPDATE … SET n = n + 1` یا autoload `false` | summarizer + core | `LQ-SCALE-06` | نگه ↑، $ ↑ |
| **SC13** | رابطهٔ تهی کش نمی‌شود (جت‌انجین)؛ ردیف‌های همگام WPML تا ×۹ | `JE/.../relation.php:697` · `zones:757-761` | ۱۱۵ رابطهٔ تهی × هر بازدید = پرس‌وجوی بی‌حاصل حتی با Redis | در render: فقط رابطه‌هایی که برای نوع ردیف دارند (فهرست از `topersia_g_relreg` + شمار ردیف در گزینه)؛ گزارش به سازنده؛ بررسی خاموش‌کردن همگام WPML برای رابطه‌هایی که render از زبان پیش‌فرض می‌خواند (P5-D349) | render | `LQ-SCALE-02` · `LQ-SCALE-04` | $ ↑، i18n ↔ |
| **SC14** | اسکریپت مرز O(شهر × عنصر)، `WP_Post` کامل، GeoJSON والد برای هر شهر | §۶ | فقط محلی؛ چند هزار شهر و ده‌ها هزار عنصر ← دقیقه‌ها، حافظهٔ JSON بزرگ | نمایهٔ نام ← عنصر یک‌بار؛ `fields => ids`؛ حافظهٔ GeoJSON والد | scripts (کدکس) | `LQ-SCALE-12` | نگه ↑ |

### ۷-الف — راهکارهای جایگزین، رتبه‌بندی‌شده با مقیاس‌پذیری ۵ ساله

| ریسک | ۱ (بهترین ۵ ساله) | ۲ | ۳ |
|---|---|---|---|
| SC1 | **لایهٔ مشتق «گراف خوانا»**: جدول `tp_graph(post_id, group, member_id, rank, lang)` که با همان صف AS (مثل گالری تجمیعی) ساخته می‌شود؛ بازدید فقط `LIMIT 8` + `COUNT` — هزینهٔ بازدید ثابت در هر مقیاس؛ «پاک ← بازسازی ← همان خروجی» مثل P5-D334 | پیش‌بارگذاری + حافظهٔ درون‌درخواستی + `IN` (کم‌هزینه، فوری؛ هنوز خطی در M) | فقط کش HTML (SC2) — مشکل را برای بازدید سرد و خزندهٔ پس از ابطال باز می‌گذارد |
| SC2 | کش HTML با برچسب در لایهٔ میزبان/CDN (LiteSpeed یا Cloudflare Cache-Tag) | کش شیء پایدار + کش بخش (fragment) هر zone با کلید (پست، زبان، هش گروه) | TTL ثابت کوتاه بی ابطال — ساده ولی کهنگی و بار خزش |
| SC3 | هش در صف + خواندن بی محاسبه | حذف همسایه‌ها از هش (فقط FIELDMAP + شناسهٔ رابطه‌های خود پست) | وضع فعلی |
| SC4 | جدول‌های جستجوی مشتق برای کلیدهای شناسه‌ای (`entity_uid`، اجداد خبر) | شاخص ترکیبی `postmeta` (سریع، ولی جدول بزرگ را بزرگ‌تر می‌کند و با به‌روزرسانی وردپرس باید پایش شود) | وضع فعلی |
| SC5 | جدول FULLTEXT مشتق در MariaDB (درون سقف REQ-O-01) | موتور بیرونی (Meilisearch/OpenSearch) — بهترین کیفیت فارسی، ولی خارج از سقف هزینه و عملیات (همان استدلال `DECISIONS-ARCHITECT-2026-10-11.md:144-148`) | LIKE فعلی |
| SC6 | Redis مدیریت‌شده + گواه به ازای زمینه | Memcached (بی ماندگاری، بی گروه‌پاک‌کردن) | جدول کش هسته برای همه (فقط محلی امروز) |
| SC7 | صفحهٔ هاب ایندکس‌پذیر با صفحه‌بندی سرور + مودال تا سقف | بارگذاری تنبل مودال با REST (برای خزنده بدتر) | وضع فعلی |
| SC8 | نوع‌های بی‌صفحه در تنظیمات Rank Math + حالت فایل + گرم‌کردن | نقشهٔ ایستای تولیدشده با WP-CLI در uploads | وضع فعلی |
| SC9 | پرونده روی دیسک + مکان‌نما | جدول مرحله‌بندی (staging) | وضع فعلی |
| SC10 | جدول سایه + `RENAME` | قفل نوشتن و درج دسته‌ای | وضع فعلی |
| SC11 | نگهداری کوتاه + اجرای صف با کرون سیستم | جدول کار اختصاصی | وضع فعلی |
| SC12 | جدول شمارنده | autoload `false` | وضع فعلی |
| SC13 | فهرست رابطه‌های پُر برای هر نوع | وصلهٔ بالادستی جت‌انجین | وضع فعلی |
| SC14 | نمایهٔ نام یک‌بار | — | وضع فعلی (محلی؛ قابل‌تحمل) |

**۷ ریسک برتر:** SC1 · SC2 · SC3 · SC4 · SC5 · SC6 · SC7.

---

## ۸ — در انتظار تأیید محلی (`LQ-SCALE-*`)

> هیچ مقدار کلید، رمز، نام کاربر یا پیشوند در خروجی نیاید؛ فقط شمار و اندازه. `P=$(wp db prefix)`؛ فرمان‌ها با `--path` و `--url` محیط محلی (`tests/env.json`).

| شناسه | چه | فرمان |
|---|---|---|
| LQ-SCALE-01 | ردیف و اندازهٔ `posts`/`postmeta`/جدول‌های رابطه، WPML و AS | `wp db query "SELECT table_name, table_rows, ROUND((data_length+index_length)/1048576) mb FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY (data_length+index_length) DESC LIMIT 25" --skip-column-names` (نام جدول‌ها پیش از درج در سند بی پیشوند شود) |
| LQ-SCALE-02 | ردیف رابطه به ازای `rel_id` و زبان دو سر | `wp db query "SELECT rel_id, COUNT(*) FROM ${P}jet_rel_default GROUP BY rel_id ORDER BY 2 DESC"` (و جدول‌های `jet_rel_N` اگر هست) |
| LQ-SCALE-03 | شمار پرس‌وجو، زمان و اندازهٔ HTML هر نوع صفحه، در fa و en | `wp eval 'global $wpdb; do_action("wpml_switch_language","fa"); $n=$wpdb->num_queries; $t=microtime(true); $h=do_shortcode("[tp_page id=ID]"); printf("%d q · %.3f s · %d KB\n",$wpdb->num_queries-$n,microtime(true)-$t,strlen($h)/1024);'` برای یک جاذبه، یک شهر بزرگ، اصفهان (استان) و ایران |
| LQ-SCALE-04 | پرس‌وجوهای تکراری و کند | Query Monitor روی همان چهار صفحه (برگهٔ Duplicate Queries و Queries by Component)؛ یا `SAVEQUERIES` در `wp-config.php` محلی |
| LQ-SCALE-05 | کش شیء | `wp cache type`؛ اگر Redis: `wp redis status` |
| LQ-SCALE-06 | اندازهٔ autoload | `wp option list --autoload=on --format=total_bytes` و `wp option list --autoload=on --fields=option_name,size_bytes --orderby=size_bytes --order=desc \| head -20` |
| LQ-SCALE-07 | `EXPLAIN` چهار الگوی `meta_value` | `wp db query "EXPLAIN SELECT post_id FROM ${P}postmeta WHERE meta_key='entity_uid' AND meta_value='X'"` و همین برای `_tp_news_ancestor`، `name_fa IN (…)`، کلید یک برنامه؛ و `SELECT meta_key, COUNT(*) FROM ${P}postmeta WHERE meta_key IN (…) GROUP BY meta_key` |
| LQ-SCALE-08 | هزینهٔ جستجو | `wp eval '$t=microtime(true); $q=new WP_Query(["s"=>"اصفهان","posts_per_page"=>10]); printf("%.3f s · %d\n",microtime(true)-$t,$q->found_posts);'` و `EXPLAIN` همان SQL (`$q->request`) |
| LQ-SCALE-09 | FULLTEXT در MariaDB | `wp db query "SHOW VARIABLES LIKE 'innodb_ft_min_token_size'"` و `SHOW PLUGINS` (نبود `ngram`) |
| LQ-SCALE-10 | `tp_county` در نقشهٔ Rank Math | `curl -s http://topersia.local/sitemap_index.xml \| grep -c tp_county` و `wp option get rank-math-options-sitemap --format=json \| grep -o '"pt_tp_county[^,]*'` |
| LQ-SCALE-11 | اندازه و زمان ساخت یک پروندهٔ نقشه | `curl -s -o /dev/null -w "%{size_download} %{time_total}\n" http://topersia.local/tp_city-sitemap.xml` (پس از پاک‌کردن کش نقشه) |
| LQ-SCALE-12 | اندازهٔ کش Overpass و زمان `match` | `ls -l geo/wp-content/uploads/tiles/boundaries-cache/` و `/usr/bin/time -v wp eval-file scripts/tp-boundaries.php match` (حافظهٔ بیشینه) |
| LQ-SCALE-13 | نشانی‌هایی که یک ویرایش باید تازه کند | ویرایش متای یک جاذبه؛ فهرست نیاکان و صفحه‌هایی که شناسه‌اش را در HTML دارند (`grep` روی خروجی `LQ-SCALE-03`) |
| LQ-SCALE-14 | هش‌های کثیف پس از یک درون‌ریزی | `wp db query "SELECT COUNT(*) FROM ${P}posts p WHERE p.post_type LIKE 'tp\\_%' AND p.post_status='publish' AND NOT EXISTS (SELECT 1 FROM ${P}postmeta m WHERE m.post_id=p.ID AND m.meta_key='_tp_content_hash')"` |
| LQ-SCALE-15 | شمار transientهای کش مرکزی | `wp db query "SELECT COUNT(*), ROUND(SUM(LENGTH(option_value))/1024) kb FROM ${P}options WHERE option_name LIKE '\\_transient\\_tpc\\_%'"` |
| LQ-SCALE-16 | زمان هر دستهٔ درون‌ریزی بسته | پروندهٔ آزمایشی ۵ و ۱۰ مگابایتی با نشانگر (قاعدهٔ ۱۶)؛ زمان هر درخواست AJAX در ابزار شبکهٔ مرورگر |
| LQ-SCALE-17 | بازسازی نمایهٔ مکانی | `time wp topersia geo-index rebuild` و در همان زمان `curl` روی `/wp-json/topersia/v1/map-points?context=ID` |
| LQ-SCALE-18 | اندازهٔ صف AS | `wp action-scheduler status` (یا `wp db query "SELECT status, COUNT(*) FROM ${P}actionscheduler_actions GROUP BY status"`) و شمار `actionscheduler_logs` |

---

## ۹ — سنجش محلی در برابر برآورد سند (۱۸ اکتبر ۲۰۲۶، دستهٔ ۹)

خط پایه: پایگاه محلی ۱٬۶۴۶ ردیف `posts` (۲۱۹ منتشرشدهٔ `tp_`)، ۱۳٬۵۳۱ ردیف `postmeta`، ۱٬۵۷۸ ردیف `jet_rel_default` (۹۴۸ از `rel_id` 73، ۴۸۵ از ۷۲)، ۶۸۸ گزینه — یعنی حدود **یک‌صدوهجدهم** مقیاس هدف در `posts` و **یک‌پانصدوپنجاهم** در `postmeta` (برآورد §۰: ۱۹۴ هزار صفحه، ۷ تا ۸ میلیون ردیف متا). هرچه زیر «مقیاس» نیامده اندازه‌گیری خطی نیست: **پرس‌وجو و ثانیه در این پایگاه کوچک اند، نه پیش‌بینی مقیاس هدف.** اجراکننده `scripts/local/tp-lq-scale.php` (فقط خواندنی؛ `SAVEQUERIES` فقط در همان فرایند)؛ کش شیء پیش از هر صفحه خالی شد (سرد). نتیجهٔ کامل هر LQ در `LOCAL-QUERIES.md`.

### صفحهٔ نمونه (LQ-SCALE-03 · 04)

| صفحه | زبان | پرس‌وجو | زمان کل | از آن پایگاه | HTML | الگوی یکتا / تکرار اضافه |
|---|---|---|---|---|---|---|
| استان اصفهان | fa | **۱٬۲۷۷** | ۲٫۱–۲٫۵ ثانیه | ۰٫۴۵–۰٫۵۱ | ۱۴۷ KB | ۱۶ / ۱٬۲۶۱ |
| استان اصفهان | en | ۱۴۹ | ۰٫۳–۰٫۴ ثانیه | ۰٫۰۶–۰٫۰۷ | ۶۱ KB | ۱۲ / ۱۳۷ |
| شهر اصفهان | fa | ۷۰ | ۰٫۲۷–۰٫۳۹ ثانیه | ۰٫۰۴ | ۴۰ KB | ۱۴ / ۵۶ |
| شهر اصفهان | en | ۵۴ | ۰٫۱۲–۰٫۱۴ ثانیه | ۰٫۰۲–۰٫۰۳ | ۲۰ KB | ۱۱ / ۴۳ |

پرتکرارترین پرس‌وجوها (استان fa): ۶۹۱× `SELECT * FROM jet_rel_default WHERE rel_id=? AND parent_object_id=?` · ۱۶۴× `SELECT * FROM posts WHERE ID=N` · ۱۶۳× `SELECT post_id, meta_key, meta_value FROM postmeta WHERE post_id IN (N)` (یک شناسه در هر `IN`). **بیشتر زمان در PHP است، نه پایگاه‌داده** (حدود ۲۰٪ در پایگاه). کندترین تک‌پرس‌وجو ۴ms است.

### SC1…SC14 — برآورد ↔ اندازه

| رتبه | برآورد سند | اندازهٔ محلی | حکم |
|---|---|---|---|
| **SC1** N+1 | استان با ۴۰۰+ عضو: ۱٬۵۰۰+ پرس‌وجو، TTFB بالای ۱–۲ ثانیه | استان fa ۱٬۲۷۷ پرس‌وجو و ۲٫۱–۲٫۵ ثانیه روی پایگاهی **صدوهجده برابر کوچک‌تر**؛ ۵۴٪ پرس‌وجوها یک الگوی رابطه، ۱۲٫۸٪ `posts WHERE ID` تک‌تک، ۱۲٫۸٪ `postmeta IN (یک شناسه)` | **تأیید شد، و برآورد خوش‌بینانه بود** (اینجا شمار پرس‌وجو خطی با اعضاست؛ شمار اعضا اندازه نشد). شهر ۷۰ و en ۱۴۹ (دروازهٔ زبان بخش‌ها را خالی می‌کند) |
| **SC2** کش صفحه نیست | بی کش: همان SC1 روی هر بازدید | هیچ لایهٔ کش HTML نیست؛ هر بار ۲٫۱ ثانیه PHP (سرد). کش ابطال با برچسب اندازه‌پذیر نیست (کش نیامده) | **تأیید شد (بی کش)**؛ بخش ابطال اندازه نشد |
| **SC3** هش در GET | هش کثیف ← نوشتن در نخستین خزش | **۲۱۹ از ۲۱۹** منتشرشدهٔ `tp_` بی `_tp_content_hash`؛ یعنی هش فقط در بازدید ساخته می‌شود و هیچ‌کدام هنوز ساخته نشده | **سازگار** با برآورد؛ طوفان نوشتن اندازه نشد (درون‌ریزی نبود، پایگاه تغییر نکرد) |
| **SC4** `meta_value` بی شاخص | هر یافتن `entity_uid` ده‌ها هزار ردیف را می‌پوید | `EXPLAIN`: هر چهار الگو `type=ref key=meta_key` با `Using where` — شاخص فقط روی `meta_key`، شرط `meta_value` فیلتر پس از آن؛ ردیف‌های پیموده: `entity_uid` ≈۱٬۵۰۹ · `name_fa` ≈۱٬۵۰۳ · `_tp_news_ancestor` ۶ · کلید برنامه (`world_heritage`) ۱۵ | **تأیید شد (سازوکار)**؛ پیمودن = همهٔ ردیف آن کلید؛ در ۱٬۵۰۹ ردیف بی‌اثر، در ۲۱–۱۹۴k خطی |
| **SC5** جستجوی `LIKE '%…%'` | ۱۹۴k پست: ثانیه‌ها | «اصفهان»: ۰٫۰۱۸–۰٫۰۳۹ ثانیه، ۲۲ یافته؛ `EXPLAIN`: `posts` range ۲۵۱ ردیف + زیرپرس‌وجوی `_tp_search_fold` (`index_subquery`). MariaDB ۱۰٫۱۱٫۱۹، `innodb_ft_min_token_size=1`، **بی ngram** | **فرض ngram‌نبودن تأیید شد**؛ «ثانیه‌ها» در این مقیاس قابل‌سنجش نیست |
| **SC6** transient بی کش پایدار | میلیون‌ها ردیف transient | کش شیء `WP_Object_Cache` (غیرپایدار؛ `wp_using_ext_object_cache` نادرست)؛ transient کش مرکزی `tpc_` ۲۱ ردیف/۲۱ KB؛ کل transient ۱۱۹؛ autoload ۴۰۸ گزینه ۵۱۴ KB (بزرگ‌ترین `_icl_cache` ۱۰۲ KB، `rewrite_rules` ۹۳ KB، `icl_sitepress_settings` ۴۷ KB) | **بی کش پایدار تأیید شد**؛ شمار transient در مقیاس هدف اندازه نشد. اندازهٔ autoload از افزونه‌های ثالث است (WPML چیره) |
| **SC7** HTML بی‌سقف | مودال با چند صد کارت ← صدها KB | استان fa ۱۴۷ KB با شهرهای منتشرشدهٔ استان؛ en ۶۱ KB | **جهت تأیید شد**؛ در مقیاس هدف اندازه نشد |
| **SC8** نقشهٔ سایت | صافی هسته برای `tp_county` در Rank Math بی‌اثر؛ ~۹۷۰ پرونده | نمایهٔ نقشه ۱۹ نقشهٔ فرعی، **صفر `tp_county`**؛ گزینهٔ Rank Math `pt_tp_county_sitemap='off'`. نقشهٔ `tp_city`: ۱۱۶ نشانی، ۳۶٫۶ KB، ۳٫۶ ثانیه (یک درخواست) | **در محلی بازتولید نشد** — حذف از طریق تنظیم Rank Math است، نه صافی؛ وضع زنده اندازه نشد. ۹۷۰ پرونده: مقیاس هدف، نه اندازه |
| **SC9** درون‌ریزی transient ۱۰MB | هر دسته چند ثانیه | **اجرا نشد** (LQ-SCALE-16 درون‌ریزی می‌خواهد = نوشتن) | بی حکم |
| **SC10** نمایهٔ مکانی | rebuild نااتمی، دقیقه‌ها | **اجرا نشد** (LQ-SCALE-17 جدول نمایه را `TRUNCATE` می‌کند) | بی حکم |
| **SC11** صف AS | میلیون‌ها ردیف | `complete=127 · failed=2 · pending=1`؛ لاگ ۳۸۸ ردیف | **رشد در محلی ناچیز**؛ دو کار شکست‌خورده دیده شد (بررسی نشد) |
| **SC12** نوشتن در خواندن | autoload + خواندن-تغییر-نوشتن | کل autoload ۵۱۴ KB؛ گزینهٔ آمار کلیک AI جدا اندازه نشد | بی حکم |
| **SC13** رابطهٔ تهی | ۱۱۵ رابطهٔ تهی × هر بازدید | ۶۹۱ پرس‌وجوی رابطه در یک بازدید استان fa؛ ۱٬۵۷۸ ردیف رابطه کل. شمار پرس‌وجوهایی که نتیجهٔ تهی دادند و شمار رابطهٔ تهی **اندازه نشد** | **جهت تأیید شد** (رابطه ۵۴٪ پرس‌وجوها)، «تهی‌بودن» نه |
| **SC14** اسکریپت مرز | O(شهر × عنصر) | کش Overpass ۱٬۰۶۸٬۲۳۳ بایت؛ زمان/حافظهٔ `match` **اجرا نشد** (`match` فایل مرجع را بازنویسی می‌کند) | بی حکم |

**LQ-SCALE-13** (ویرایش متا) **اجرا نشد** — داده را می‌نویسد.
