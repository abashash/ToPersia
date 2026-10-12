# AI — ممیزی ۲د: افزونۀ هوش مصنوعی (`topersia-ai-summarizer` ۱٫۲٫۱)

> ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ · فقط‌خواندنی (P5-D287) · هیچ کد، پایگاه‌داده، تنظیم یا افزونه‌ای عوض نشده.
> مسیرها نسبت به `ToPersia Claud/workspace/` هستند. شمارۀ خط روی درخت `plugin/` (نسخۀ workspace) است؛ نسخۀ نصب‌شدۀ `geo/wp-content/plugins/topersia-ai-summarizer` در سه پرونده (`readme.txt` و دو آزمون) فرق دارد، نه در `includes/` (R44، گزارش جدا در SYSTEM.md).
> برچسب‌ها: VERIFIED فقط با مسیر و خط · INFERRED (از کد استنباط شد، اجرا نشد) · ASSUMED (فرض بی شاهد) · UNKNOWN (نیازمند ماشین محلی).
> هیچ کلید، رمز یا دادۀ کاربر در این سند نیست؛ فقط نام و شمار.

## ۰ — خلاصه

| | |
|---|---|
| اندازه | ۱۸ پروندۀ `includes/` + ۴ پروندۀ JS + ۲ CSS؛ ۵٬۷۰۰ خط PHP در `includes/` (۵٬۸۴۳ با پروندۀ اصلی و `uninstall.php`) |
| سه کار افزونه | (۱) دکمه‌های پیوند به ۷ دستیار بیرونی با پرامت آماده · (۲) پنل درون‌صفحه با تولید سمت سرور (فقط کاربر واردشده) · (۳) موتور داخلی قطعی بی API (`TP_AI_Internal`) |
| تولید متن | سه نوع خروجی: `summary` · `keypoints` · `plan` (`includes/class-tp-ai-prompt-manager.php:28`) |
| صدا (TTS)، پادکست، سفر، Router، Policy، دروازۀ گردشگری، `ai_runs`، اعتبار | **در این افزونه نیستند** (جدول شکاف، بخش ۱۱) |
| حکم کلی P5-D288 | قابل نجات و قابل‌گسترش در همین افزونه، با سه بازسازی ضروری پیش از لایه‌بندی (بخش ۱۳) |

## ۱ — قابلیت‌ها

| قابلیت | شاهد | برچسب |
|---|---|---|
| دکمه‌های دستیار بیرونی: chatgpt · perplexity · claude · grok · copilot · gemini · deepseek، هر کدام با پرامت ساخته‌شده | `includes/class-tp-ai-services.php:24-60`؛ پیش‌فرض فعال در `includes/class-tp-ai-options.php:42` | **VERIFIED** |
| پیش‌فرض کد: `position = after_thumbnail` ← دکمه‌های بیرونی خودکار در `the_content` تزریق می‌شوند مگر مدیر `manual` کند یا متای `_tpas_hide_buttons` | `includes/class-tp-ai-options.php:44` · `includes/class-tp-ai-frontend.php:109-110` | **VERIFIED** |
| مقدار زندۀ `services` · `position` · `post_types` روی سایت | فقط در گزینۀ `tpas_settings` پایگاه‌داده | **UNKNOWN** — محلی: `wp eval 'foreach(["services","position","panel_position","post_types","internal_enabled"] as $k){echo $k,"=",wp_json_encode(TP_AI_Options::get_field($k)),"\n";}'` (کلید چاپ نمی‌شود) |
| پنل درون‌صفحه: سه زبانه (خلاصه · نکات · برنامه)، دکمۀ ساخت و ساخت دوباره | `includes/class-tp-ai-render.php:273-323` | **VERIFIED** |
| تولید فقط کاربر واردشده؛ `nopriv` ثبت نشده، و بررسی `is_user_logged_in()` دوباره در متد | `includes/class-tp-ai-summary.php:19-24` و `:88` | **VERIFIED** |
| نانس `tpas_ai` + بررسی منتشرشده بودن پست (P5-D247) + مجاز بودن نوع پست | `includes/class-tp-ai-summary.php:94` · `:108` · `:112` | **VERIFIED** |
| پاک‌سازی خروجی مدل: `wp_strip_all_tags` ← `esc_html` ← زیرمجموعۀ کوچک مارک‌داون ← `wp_kses` با چهار برچسب | `includes/class-tp-ai-render.php:433-495` | **VERIFIED** |
| موتور داخلی بی API: خلاصه، نکات، برنامه (فقط از رابطه‌های JetEngine) | `includes/class-tp-ai-internal.php:44` · `:73` · `:128` · `:172` | **VERIFIED** |
| خواندن دادۀ واقعی ToPersia (`entity_uid`، فیلدهای مشترک، رابطه‌ها) فقط‌خواندنی و با `function_exists` | `includes/class-tp-ai-context.php:43-100`، ثابت‌های فیلد `:23-30` | **VERIFIED** |
| شورت‌کد `tpas` · `topersia_ai` · `tpas_ai` · `topersia_ai_panel`؛ بلوک `tpas/buttons` و `tpas/panel`؛ ویجت المنتور | `includes/class-tp-ai-shortcode.php:19-23` · `includes/class-tp-ai-block.php:34,50` · `includes/class-tp-ai-elementor.php` | **VERIFIED** |
| ردیاب کلیک دکمه‌های بیرونی (کاربر مهمان هم می‌تواند) | `includes/class-tp-ai-tracker.php:24-25` | **VERIFIED** |
| دروازۀ سقف روزانه در render پیش از تولید (`wp_ajax_tpas_ai_generate` با اولویت ۱) | `plugin/topersia-render/includes/topersia-g-ai.php:163` | **VERIFIED** |
| صفحۀ ToPersia نتیجۀ ذخیره‌شده را از `TP_AI_Summary::get_cached()` می‌خواند و برای کاربر واردشده دکمۀ تولید می‌گذارد | `plugin/topersia-render/includes/topersia-g-zones.php:4568-4576` و `:4723-4724` | **VERIFIED** |

## ۲ — جدول‌ها و طرحشان

دو جدول، با `dbDelta` در `plugins_loaded` وقتی `tpas_db_version` ≠ `1.2.1` (`topersia-ai-summarizer.php:66-70` · `includes/class-tp-ai-db.php:19,58,130`). پیشوند پایگاه‌داده زنده **UNKNOWN**؛ نام کامل `{prefix}topersia_ai_summaries` و `{prefix}topersia_ai_events`.

### `topersia_ai_summaries` — `includes/class-tp-ai-db.php:68-83`

| ستون | نوع |
|---|---|
| `id` | BIGINT UNSIGNED AI PK |
| `post_id` | BIGINT UNSIGNED |
| `type` | VARCHAR(20) پیش‌فرض `summary` |
| `language` | VARCHAR(10) |
| `content` | LONGTEXT |
| `provider` | VARCHAR(50) NULL |
| `model` | VARCHAR(100) NULL |
| `source` | VARCHAR(20) پیش‌فرض `ai` (`ai` یا `internal`) |
| `created_at` · `updated_at` | DATETIME |
| کلیدها | `UNIQUE (post_id, type, language)` · `KEY post` · `KEY source` |

### `topersia_ai_events` — `includes/class-tp-ai-db.php:85-94`

`id` · `post_id` · `event_type` VARCHAR(30) · `provider` NULL · `created_at`؛ کلید `(post_id, event_type)` و `provider`. فقط رویداد `assistant_click` نوشته می‌شود و فقط وقتی `analytics_enabled` روشن است (پیش‌فرض ۰) — `includes/class-tp-ai-tracker.php:59-61` · `includes/class-tp-ai-options.php:73`. **VERIFIED**.

### یادداشت‌ها

- `dbDelta` کلید قدیمی `post_language` را نمی‌افتاد؛ کد جدا `ALTER TABLE … DROP INDEX` می‌زند (`:112-123`) — **VERIFIED**. این یعنی مهاجرت ساختار روی ورود به هر درخواست `plugins_loaded` وابسته است، نه افزونه‌ای چون `register_activation_hook` تنها.
- هیچ جدول `cache`، `runs`، `prompts`، `credits`، `trips` وجود ندارد — **VERIFIED** (فقط دو `CREATE TABLE` در افزونه؛ `grep CREATE TABLE` روی `plugin/topersia-ai-summarizer`).
- حذف افزونه: `uninstall.php:15-35` گزینۀ `tpas_settings` · `tpas_stats`، متاها و ترنزینت‌های `tpas_rt_*` را پاک می‌کند اما **نه** دو جدول، **نه** `tpas_db_version` و **نه** `tpas_prompt_overrides` (`includes/class-tp-ai-prompt-manager.php:20`) — **VERIFIED**. دادۀ مصنوعی خلاصه‌ها بعد از حذف می‌ماند (INFERRED تعمدی: «بایگانی»؛ سندی نیست).
- ستون `content` متن خام مدل است، بی نسخۀ پرامت، بی هش منبع، بی زبان مبدأ جدا از `language` — **VERIFIED** (`:68-83`).

## ۳ — شناسۀ کش

| لایه | چگونه ساخته می‌شود | برچسب |
|---|---|---|
| کلید منطقی ردیف | `(post_id, type, language)` یکتا | **VERIFIED** `class-tp-ai-db.php:80` |
| پوستۀ کش مرکزی هسته | `topersia_cache_remember( 'ai.text', [post, type], … )` با `expensive=true`، `rebuild=true`، `stale_on_error=false`، و `store` = همین جدول | **VERIFIED** `class-tp-ai-summary.php:175-199` · `class-tp-ai-core-bridge.php:57-82` |
| کلید `sha1` هسته | `sha1( json{ service, provider, model, version, lang, key_parts, hash } )`؛ برای `expensive` عنصر `hash` تهی است | **VERIFIED** `plugin/topersia-core/includes/topersia-cache.php:275-288` |
| `provider/model/version` در کلید | از `topersia_api( 'ai.text' )` می‌آید؛ `ai.text` در فهرست سرویس‌های هسته **نیست** (کاتالوگ ۹ سرویس بی `ai.text`) ← این سه تهی‌اند | **VERIFIED** `plugin/topersia-core/includes/topersia-services.php:47-57` |
| هش تازگی | `topersia_content_hash` = `sha1` عنوان + متاهای فهرست‌شده + شناسۀ رابطه‌های منتشرشده + زبان؛ **`post_content` و گزیده در آن نیستند** | **VERIFIED** `topersia-cache.php:76-107` |
| فهرست متاهای هش | فیلتر `topersia_content_hash_fields`؛ خلاصه‌ساز فیلدهای ورودی خودش را اضافه می‌کند | **VERIFIED** `class-tp-ai-core-bridge.php:21,31-33` |
| تازگی | هش ذخیره‌شده در متای `_tpas_hash_{type}_{lang}` در برابر هش امروز؛ ناهمخوان ← «کهنه»، و با کلیک کاربر مجاز بازسازی می‌شود | **VERIFIED** `class-tp-ai-core-bridge.php:48-50,85-94` |

### شکاف با بند ۱۴ سند AI Core («SHA-256 روی بستۀ منبع نرمال‌شده + زبان + نسخۀ پرامت + طرح + سیاست»)

| جزء سند | امروز | |
|---|---|---|
| SHA-256 | `sha1` | **VERIFIED** `topersia-cache.php:279,101` |
| نسخۀ پرامت در شناسه | نیست؛ ویرایش پرامت (`tpas_prompt_overrides`) هیچ خروجی را کهنه نمی‌کند | **VERIFIED** — `class-tp-ai-prompt-manager.php:38-49` فقط متن را می‌دهد، کلید `topersia_cache_key` آن را نمی‌بیند |
| مدل/ارائه‌دهنده در شناسه | تهی (`ai.text` ثبت نیست)؛ عوض‌کردن موتور خروجی قدیمی را «تازه» نگه می‌دارد | **VERIFIED** |
| `post_content` در هش | نیست؛ برای نوع‌های `tp_*` محتوا در متا (`overview_text`) است و پوشش دارد، برای هر پستی که متنش `post_content` است (صفحه، پست معمولی) ویرایش متن هیچ خروجی را کهنه نمی‌کند | **VERIFIED** (هش) + **INFERRED** (اثر روی نوع‌های غیر `tp_*`: `default_post_types()` در `class-tp-ai-options.php:88` اگر هسته نباشد `post`/`page` می‌دهد) |
| هش جدا برای سیاست/طرح | نیست | **VERIFIED** |
| هش برای دو ردیف «internal» | ردیف موتور داخلی هش ندارد و `get_cached` آن را (چون متای هش ندارد) تازه می‌شمارد: `is_stale` با هش تهی `false` برمی‌گرداند | **VERIFIED** `class-tp-ai-core-bridge.php:91-93` (`'' !== $hash &&` در `topersia_cache_is_stale`، `topersia-cache.php:501-504`) |

**قفل هم‌زمانی ندارد:** `topersia_cache_remember` پیش از `call_user_func($fetch)` قفل یا «پرواز تکی» نمی‌گیرد (`topersia-cache.php:405-440`) ← دو کلیک هم‌زمان (یا دو کاربر روی یک پست کهنه) دو فراخوانی پولی می‌سازد. **VERIFIED**؛ همان ریسک ۱۲ سند AI Core («duplicate jobs»).

## ۴ — ارائه‌دهنده‌ها و مدل‌ها

از `TP_AI_Options::providers()` (`includes/class-tp-ai-options.php:300-349`) — ۸ پیش‌تنظیم، هر موتور یک ردیف در `internal_engines` با `id` سروری:

| ارائه‌دهنده | پروتکل | مدل پیش‌فرض کد |
|---|---|---|
| deepseek | openai | `deepseek-chat` |
| openai | openai | `gpt-4o-mini` |
| grok | openai | `grok-4-fast` |
| perplexity | openai | `sonar` |
| claude | anthropic (`/messages`) | `claude-sonnet-4-5` |
| gemini | gemini (`:generateContent`) | `gemini-2.0-flash` |
| azure | azure | تهی؛ نشانی کامل استقرار در ردیف |
| custom | openai | تهی؛ `base_url` و `model` در ردیف |

همۀ **VERIFIED**. توضیح: «مدل پیش‌فرض کد» همان است که در کد آمده؛ مدلِ ذخیره‌شده در تنظیم زنده **UNKNOWN** (محلی: فرمان بند ۱).

- انتخاب موتور: `engine_is_internal()` — اگر `internal_engine` = `auto` و API پیکربندی‌شده باشد، API؛ وگرنه موتور داخلی (`class-tp-ai-summary.php:66-82`). **VERIFIED**.
- پروتکل‌ها: `class-tp-ai-api.php:94-104`؛ OpenAI `Authorization: Bearer`، Azure `api-key`، Anthropic `x-api-key` + `anthropic-version: 2023-06-01`، Gemini `x-goog-api-key` در سرآیند، نه نشانی (`:156,260,370`). **VERIFIED**.
- مهلت ۶۰ ثانیه، بدون تلاش مجدد، بدون پشتیبان (fallback) میان ارائه‌دهنده‌ها (`class-tp-ai-api.php:163,255,364`). **VERIFIED**.
- لایۀ «ارائه‌دهندۀ دوم» هسته (`TOPERSIA_SERVICES_ALT`) برای `ai.text` استفاده **نمی‌شود** چون `ai.text` از لایۀ هسته بیرون است (`class-tp-ai-core-bridge.php:5-8`). **VERIFIED**.

### ذخیرۀ کلید — مغایر با P5-D249

| | |
|---|---|
| کلید موتور در `tpas_settings.internal_engines[].api_key` | متن ساده: `sanitize_text_field` سپس ذخیره، بی رمزنگاری (`class-tp-ai-options.php:553-567`) — **VERIFIED** |
| خلاف قاعدۀ `P5-D249` («کلید رمزشده با sodium، ثابت wp-config برنده، option بی autoload») | هستۀ ۴٫۱۲٫۱ عمداً `ai.text` را از لایۀ خود برداشت (خواستۀ کاربر ۲۹ سپتامبر، `class-tp-ai-core-bridge.php:5-8`)؛ پس کلید تولید متن تنها کلیدی است که از رمزنگاری سروری هسته بیرون است — **VERIFIED** |
| `autoload` گزینۀ `tpas_settings` | `add_option( KEY, defaults )` پیش‌فرض autoload=yes (`topersia-ai-summarizer.php:104-105`) ← کلیدها در هر درخواست به حافظه می‌آیند — **VERIFIED** (فراخوانی بی پارامتر autoload) |
| کلید در فیلتر عمومی | `apply_filters( 'tpas_api_payload', $body, $endpoint, $key )` کلید را به هر افزونۀ شنونده می‌دهد (`class-tp-ai-api.php:144,248,353`) — **VERIFIED** |
| بازنمایی کلید در HTML | فیلد کلید «فقط‌نوشتنی» است و در نمایش مجدد خالی می‌ماند (`class-tp-ai-options.php:554-566`) — **VERIFIED** |
| `test_key` کلید ذخیره‌شده را به `base_url` ارسالی POST می‌فرستد | فقط مدیر (`manage_options`) و با نانس (`class-tp-ai-admin.php:833-835,850-854`)، با `wp_remote_post` نه `wp_safe_remote_post` — **VERIFIED**؛ شدت پایین چون مدیر کلید را از پیش دارد |

## ۵ — REST و AJAX

افزونه **REST ندارد** (`grep register_rest_route` روی `plugin/topersia-ai-summarizer` صفر) — **VERIFIED**.

| کنش AJAX | دسترسی | حفاظت | شاهد |
|---|---|---|---|
| `tpas_ai_generate` | کاربر واردشده (بی `nopriv`) | نانس `tpas_ai` + منتشرشده + نوع مجاز + نرخ + دروازۀ سقف render | `class-tp-ai-summary.php:19,88-120` · `topersia-g-ai.php:163` — **VERIFIED** |
| `tpas_track` | مهمان و واردشده (`nopriv`) | فقط نانس `tpas_ai`؛ بی نرخ؛ نوشتن در `_tpas_clicks` پست دلخواه و `update_option( 'tpas_stats' )` در هر کلیک | `class-tp-ai-tracker.php:24-25,34,45-56` — **VERIFIED** |
| `tpas_test_key` | مدیر | نانس `tpas_admin` + `manage_options` | `class-tp-ai-admin.php:24,833-835` — **VERIFIED** |

یافته‌ها (بخش «نامعتبر» در RISKS.md هم هست):

- `tpas_track` وجود/انتشار پست را نمی‌سنجد؛ هر بازدیدکننده‌ای با نانس همگانی می‌تواند برای هر شناسۀ پست (حتی پیش‌نویس یا نامعلوم) متا و شمارنده بسازد. **VERIFIED** (`:36-47`). شدت پایین.
- نانس `tpas_ai` برای مهمان به صفحه‌ای که ممکن است در حافظۀ پنهان (LiteSpeed) نشسته باشد وصل است (`class-tp-ai-frontend.php:50-54`). **INFERRED**: نانس مهمان تا ۱۲ تا ۲۴ ساعت معتبر است؛ کش صفحه طولانی‌تر باشد، ردیاب بی‌صدا شکست می‌خورد. تأیید نیازمند تنظیم کش زنده: **UNKNOWN** — محلی/زنده: بررسی TTL کش صفحه.
- `client_ip()` اول `HTTP_X_FORWARDED_FOR` را باور می‌کند (`class-tp-ai-summary.php:517-522`) ← لایۀ نرخ‌سنج IP با هدر جعلی دور زده می‌شود؛ لایۀ کاربر (`:369`) می‌ماند. در مقابل user-core همین کار را فقط پشت ثابت `TOPERSIA_TRUST_PROXY` می‌کند (`plugin/topersia-user-core/includes/class-topersia-auth.php:148`). **VERIFIED** (دو منطق متفاوت).

## ۶ — کار پس‌زمینه

- هیچ کرون، هیچ کار Action Scheduler، هیچ `wp_schedule_*` در افزونه نیست — **VERIFIED** (`grep` در `plugin/topersia-ai-summarizer`: صفر).
- تولید **هم‌زمان درون درخواست `admin-ajax.php`** است: تماس ۶۰ ثانیه‌ای تا پاسخ (`class-tp-ai-api.php:163`)، بی صف، بی پیگیری وضعیت — **VERIFIED**. ریسک ۲۶ سند (shared-hosting timeout): `ریشه:.htaccess:53` مقدار `max_execution_time 600` را برای زنده نشان می‌دهد (**INFERRED**، فایل مربوط به ۱۳ اوت)، پس مهلت PHP مشکل نیست؛ ولی زمان‌آمدن پروکسی/LiteSpeed در برابر یک درخواست طولانی و تعداد کارگرهای PHP همزمان **UNKNOWN** است (زنده: تنظیم LiteSpeed و `php -i`).
- هسته صف واحد دارد (`plugin/topersia-core/includes/topersia-queue.php:58`)، ولی هیچ مصرف‌کنندۀ هوش مصنوعی ندارد؛ تنها مصرف‌کننده گالری تجمیعی render است (`topersia-g-gallery-agg.php:377,496,583`) — **VERIFIED**.

## ۷ — پرامت‌ها

| | |
|---|---|
| متن پیش‌فرض | آرایه‌های PHP در `includes/class-tp-ai-localize.php:171` (`strings()`)، با نه زبان `fa en ar fr de es ru tr zh` (`:402-440`) — **VERIFIED** |
| پرامت سیستم | `TP_AI_Localize::context_system()` `:402-420`؛ «فقط بر اساس محتوای داده‌شده، هیچ دانش بیرونی، هیچ HTML» + فیلتر `tpas_system_prompt` — **VERIFIED** |
| بازنویسی مدیر | گزینۀ `tpas_prompt_overrides` (lang → type → قالب) با `wp_kses_post` (`class-tp-ai-prompt-manager.php:20,38-49,97`) — **VERIFIED** |
| نسخه‌گذاری پرامت (فایل نسخه‌دار، شناسۀ نسخه، آمیختن در شناسۀ کش) | **نیست** — **VERIFIED** (بخش ۳) |
| ساخت پرامت کاربر | `TP_AI_Render::build_prompt()` `class-tp-ai-render.php:387` + بافت `page_context()` (`class-tp-ai-summary.php:418-461`، بریدن با `internal_context_chars`) — **VERIFIED** |
| دفاع تزریق پرامت | تنها دو لایه: دستور سیستم «فقط از متن» و پاک‌سازی HTML خروجی؛ مرزبندی متن منبع (delimiter/برچسب‌گذاری «داده، نه دستور») و فهرست ابزار مجاز نیست — **VERIFIED** (`:167` پرامت و متن صفحه را با `\n\n` می‌چسباند) |

## ۸ — سقف‌ها

سه سازوکار هم‌پوشان، هیچ‌کدام دیگری را نمی‌شناسد:

| سازوکار | کجا | واحد | پیش‌فرض کد | برچسب |
|---|---|---|---|---|
| نرخ‌سنج ساعتی خلاصه‌ساز | `class-tp-ai-summary.php:362-405`، ترنزینت `tpas_rt_u_{user}_{type}` و `tpas_rt_ip_{md5}` | در ساعت، به ازای کاربر×نوع؛ IP پنج‌برابر | `internal_throttle = 10` (`class-tp-ai-options.php:67`) | **VERIFIED** |
| سقف روزانۀ render | `plugin/topersia-render/includes/topersia-g-ai.php:45-56,142-158` (متای `_topersia_ai_daily` + ترنزینت `tp-ai-site-{day}`) | در روز، کاربر / کل سایت | تنظیم render: ۵ و ۱۰۰ (`topersia-settings.php:55-56`)؛ پشتیبان کد ۱۰ و ۲۰۰ | **VERIFIED** |
| «سقف روزانه (کاربر · سایت)» صفحۀ سرویس‌های هسته | `plugin/topersia-core/includes/topersia-services.php:285-288,525-526,957-958`؛ render آن را برای `ai.text` می‌خواند (`topersia-g-ai.php:39-40`) | روز | خالی | **VERIFIED** — اما `ai.text` از کاتالوگ بیرون است ← این مسیر مرده است (**INFERRED** از غیبت `ai.text` در `topersia_services_catalog`) |

**دورزدن سقف روزانه (VERIFIED):** دروازۀ render پیش از شمارش، اگر `TP_AI_Summary::get_cached()` چیزی بدهد (حتی ردیف «کهنه»)، بی شمارش رد می‌شود (`topersia-g-ai.php:142-144`). ولی `handle_ajax` خروجی کهنه را **بازمی‌سازد** (`class-tp-ai-summary.php:127`: فقط وقتی `empty($cached['stale'])` از کش جواب می‌دهد) ← بازسازی پولی بعد از ویرایش پست بی کم‌شدن از سقف انجام می‌شود. شدت متوسط؛ هر ویرایش یک بازسازی رایگان برای نخستین کلیک‌کننده.

دیگر: شمارش پیش از تولید انجام می‌شود (`topersia-g-ai.php:158`) و در شکست API پس‌داده نمی‌شود (**VERIFIED**: فراخوانی `count` بی مسیر بازگشت). خواندن/نوشتن `get_transient`/`set_transient` برای سقف سایت اتمی نیست (**VERIFIED** `:96`) — در رقابت، سقف سایت کمی بیشتر می‌شود.

## ۹ — صدا

- **هیچ تولید صدا در افزونه نیست**: `grep -ril 'tts\|audio\|speech\|voice'` فقط `class-tp-ai-shortcode.php` و `class-tp-ai-services.php` را می‌دهد و هر دو بی ارتباطاند (نام دکمه‌ها / ویژگی‌های HTML) — **VERIFIED**.
- سرویس `ai.tts` در کاتالوگ هسته با دو آداپتور ثبت است: `gemini-tts` (مدل `gemini-3.8-flash-tts`) و `elevenlabs` (`eleven_v3`)، `enabled=false` (`plugin/topersia-core/includes/topersia-services.php:56,87-88`) — **VERIFIED**. هیچ کد مصرف‌کننده‌ای `topersia_api( 'ai.tts' )` را صدا نمی‌زند — **VERIFIED** (`grep "topersia_api( '"` تنها `air_quality` را نشان می‌دهد؛ دو مورد در `topersia-g-air.php:74,97`).
- فیلد `audio_narration` (repeater) در متاباکس مشترک هست (`reference/Jetengine_Skin.json`، meta-1) — **VERIFIED**؛ برای «محل ذخیرۀ صدا» این تنها نقطۀ مدل‌شده است: متاباکس، نه جدول و نه Media Library (جدول `topersia_ai_*` ندارد).
- کتابخانۀ رسانه، مسیر آپلود، سازوکار صف صدا، اندازۀ ذخیره: ندارد/نامعلوم — محلی: بررسی `uploads/` برای پوشۀ صدا بعد از ساخت نخستین نمونه.
- دسترسی سرور زنده به API گوگل (محدودیت IP ایران): **UNKNOWN** — زنده: `curl -sS -o /dev/null -w "%{http_code}\n" https://generativelanguage.googleapis.com/` از خود میزبان.

## ۱۰ — تلهمتری

| | |
|---|---|
| جدول `ai_runs` / کانال لاگ `ai` | ندارد؛ لاگ هسته (`topersia_log`) کانال‌های `js` `queue` `relation` … دارد و هیچ فراخوانی `TP_AI_*` در آن نمی‌نویسد — **VERIFIED** (`grep topersia_log` در افزونه: صفر) |
| هزینه / توکن / تأخیر / کد خطا | ثبت نمی‌شود؛ `TP_AI_API::chat` فقط `{ok,data,model,provider,finish}` برمی‌گرداند و `finish` هرگز ذخیره نمی‌شود — **VERIFIED** (`class-tp-ai-api.php:210-216`، `class-tp-ai-summary.php:221-223`) |
| آمار کلیک | `_tpas_clicks` (متای پست) + `tpas_stats` (گزینه) + جدول `events` (اختیاری) — سه جای هم‌پوشان — **VERIFIED** |
| ثبت موفقیت/شکست سرویس | `topersia_api_record()` از کش هسته برای `ai.text` فراخوانی می‌شود (`topersia-cache.php:449-450,485-486`) اما برای سرویس بی سطر در کاتالوگ بی اثر است — **INFERRED** |

حکم ۱۷ ARCH-RECONCILIATION («یا کانال `ai` لاگ هسته یا جدول افزونه؛ ۲د تعیین می‌کند. نه هر دو»): افزونه جدول دارد (`events`) و کانال `ai` در لاگ ندارد؛ گزینۀ بی‌هزینه‌تر **جدول `runs` در افزونۀ هوش مصنوعی** است، چون لاگ هسته متن آزاد است (`message TEXT` + `context LONGTEXT`، `topersia-core.php:1085-1102`) و ستون‌های عددی/ایندکس‌شده (`input_tokens`، `latency_ms`، `estimated_cost`) ندارد و شاخص `channel_time` فقط بر کانال و زمان است — پیشنهاد **INFERRED**.

## ۱۱ — شکاف با سند AI Core (بند به بند)

| بند سند | موضوع | امروز | |
|---|---|---|---|
| ۵، ۶۹ | لایه‌های Router · Policy · ProviderAdapter | یک کلاس `TP_AI_API` با سه تابع پروتکل؛ انتخاب با `engine_by_id` / پیش‌فرض، نه بر پایۀ کار | **VERIFIED** `class-tp-ai-api.php:72-105` |
| ۷، ۸، ۹ | دروازۀ گردشگری | ندارد؛ ورودی آزاد هم ندارد (فقط دکمه) | **VERIFIED** (ورودی‌های AJAX: `post`, `type`, `lang`, `url`، `class-tp-ai-summary.php:96-99`) |
| ۱۱، ۱۲ | سیاست ارائه‌دهنده، fallback | ندارد (بخش ۴) | **VERIFIED** |
| ۱۴ | شناسۀ کش SHA-256 | sha1 بی پرامت/مدل (بخش ۳) | **VERIFIED** |
| ۱۵–۱۸ | موتور صدا | ندارد (بخش ۹) | **VERIFIED** |
| ۱۹–۲۶ | سفر | ندارد؛ فقط `TP_AI_Internal::itinerary` که از رابطه‌ها «برنامه» می‌سازد (۴ نقطه) | **VERIFIED** `class-tp-ai-internal.php:172-216` |
| ۳۹–۴۰ | اعتبارسنجی/کیفیت خروجی | فقط پاک‌سازی HTML؛ نه اعتبارسنجی طرح، نه ارجاع موجودیت، نه بررسی «فقط از منبع» | **VERIFIED** (بخش ۱) |
| ۴۲، ۴۳ | صف و idempotency | ندارد؛ هم‌زمان درون AJAX و بی قفل | **VERIFIED** (بخش‌های ۳ و ۶) |
| ۴۴ | کش L1/L2/L3 | یک لایه: جدول خلاصه‌ها + متای هش؛ کش شیء/Redis ندارد | **VERIFIED** |
| ۴۶ | `ai_cache` · `ai_runs` · `prompts` · `credits` · `trips` | هیچ‌کدام (بخش ۲) | **VERIFIED** |
| ۴۹ | پرامت در فایل نسخه‌دار | آرایه در کد + override در گزینه، بی نسخه | **VERIFIED** |
| ۵۰–۵۴ | امنیت (کلید، SSRF، تزریق) | کلید متن ساده؛ `wp_remote_post` به نشانی ردیف؛ تزریق بخش ۷ | **VERIFIED** |
| ۵۵–۵۷ | اعتبار و رزرو | ندارد؛ فقط سقف روزانه | **VERIFIED** (توافق با حکم ۱۵ ARCH-RECONCILIATION: تعویق) |
| ۶۸ | REST معماری | ندارد؛ فقط `admin-ajax` | **VERIFIED** |
| ۷۲–۷۳ | تلهمتری و متریک‌ها | ندارد (بخش ۱۰) | **VERIFIED** |
| ۷۸ | feature flags | چند سوییچ در `tpas_settings` (`internal_enabled`, `analytics_enabled`, …)؛ نه سازوکار flag | **VERIFIED** |

## ۱۲ — حکم ARCH-RECONCILIATION در برابر شواهد

| بند ARCH-RECONCILIATION | هم‌خوانی | توضیح |
|---|---|---|
| #۱ «افزونۀ دوم نه؛ توسعۀ `topersia-ai-summarizer`» (P5-D288) | **هم‌خوان با اصلاح** | افزونه قابل‌گسترش است ولی سه بدهی دارد (بخش ۱۳)؛ «سرچ ساختار لایه‌ای داخل همین افزونه» ممکن است، به شرط بازنویسی `class-tp-ai-summary.php` به لایه‌های جدا |
| §۱ «کلید فقط سمت سرور» (قاعدۀ ۴) | **نیمه‌هم‌خوان** | سمت سرور بله؛ ولی رمزنگاری P5-D249 برای `ai.text` اعمال نمی‌شود (بخش ۴) |
| §۱ «هیچ پیوند به سرویس بیرونی هوش مصنوعی» (P5-D255) | **ناهم‌خوان در کد** | دکمه‌های ۷ دستیار بیرونی هنوز پیش‌فرض فعال و تزریق‌شونده‌اند (`class-tp-ai-options.php:42,44` · `class-tp-ai-frontend.php:109-110`)؛ حذف/خاموشی نهایی به مقدار زندۀ گزینه بسته است: **UNKNOWN** (فرمان بخش ۱) |
| #۸ شناسۀ کش | **ناهم‌خوان** | بخش ۳ |
| #۱۷ `ai_runs` یا کانال لاگ | **تعیین شد** | جدول `runs` در افزونه (بخش ۱۰) |
| #۱۶ Action Scheduler تنها صف | **هم‌خوان** | هسته آماده است؛ خلاصه‌ساز مصرف‌کننده نیست |
| قاعدۀ «تولید متن در لایۀ هسته نیست» (۴٫۱۲٫۱) | **هم‌خوان با هزینه** | کلید/موتور درون افزونه ← باید تصمیم شود لایۀ رمزنگاری هسته را به ارث ببرد (پیشنهاد، تصمیم با معمار) |

## ۱۳ — قابل نجات بودن برای P5-D288

حکم **INFERRED** (بر پایۀ ۵٬۷۰۰ خط `includes/` (بخش‌های کلیدی کامل خوانده شد؛ `class-tp-ai-admin.php`، JS و آرایۀ رشته‌های `localize` نمونه‌وار) و ۱۱ پروندۀ آزمون در `tests/`؛ آزمون‌ها در این جلسه با گیت اجرا می‌شوند، نتیجه در GATES.md):

نجات‌پذیر و نگه‌داشتنی: `TP_AI_Context` (فقط‌خواندنی، محافظت‌شده)، `format_ai_text` (امن)، جدول `summaries` و کلید یکتا، پل کش مرکزی، دروازۀ منتشرشده و سقف، پروتکل‌های سه‌گانه.

بازسازی پیش از افزودن لایه‌ها:

1. **شناسۀ کش و نسخۀ پرامت** — کلید باید `prompt_version` + `provider`/`model` را بشناسد؛ سازگار با جدول کنونی با افزودن ستون و نه جدول دوم.
2. **رمزنگاری کلید** — ادغام با `topersia_services_encrypt()` (هسته) یا معادل داخلی.
3. **جداسازی `handle_ajax`** — یک تابع ۱۵۰ خطی (`class-tp-ai-summary.php:87-232`) که احراز، سقف، کش، تولید و پاسخ را قاطی کرده؛ برای Router/Policy باید به مراحل مجزا شکسته شود. قفل «پرواز تکی» و ثبت اجرا (`runs`) همین‌جا اضافه می‌شود.

بدهی کوچک: `uninstall.php` جدول‌ها را نمی‌افتد، ردیاب بی نرخ، `client_ip` متفاوت از user-core، سه سقف هم‌پوشان.

## ۱۴ — UNKNOWN و فرمان محلی

| # | موضوع | فرمان / پرس‌وجوی پیشنهادی |
|---|---|---|
| ۱ | مقادیر زندۀ تنظیم: سرویس‌ها، موقعیت، نوع‌های پست، موتور پیش‌فرض | `wp eval` بخش ۱ (بی کلید) |
| ۲ | شمار ردیف‌های جدول‌ها و تفکیک `source` | `wp db query "SELECT source, COUNT(*) FROM {prefix}topersia_ai_summaries GROUP BY source"` و `… events` |
| ۳ | ردیف‌های دارای هش و بی هش (ماندۀ پیش از ۱٫۲٫۰) | `wp db query "SELECT COUNT(*) FROM {prefix}postmeta WHERE meta_key LIKE '_tpas_hash_%'"` |
| ۴ | حجم autoload گزینۀ `tpas_settings` | `wp db query "SELECT LENGTH(option_value), autoload FROM {prefix}options WHERE option_name='tpas_settings'"` (فقط طول، نه مقدار) |
| ۵ | مقدار مؤثر حدّ PHP و زمان‌آمدن پروکسی/LiteSpeed در برابر تولید ۶۰ ثانیه‌ای | `php -i` و تنظیم LiteSpeed میزبان |
| ۶ | دسترسی میزبان زنده به API ارائه‌دهنده‌ها (ایران) | `curl` از میزبان (بخش ۹) |
| ۷ | اجرای آزمون‌های افزونه (`tests/run.php`) | در این جلسه از راه `assert-uc.php`/گیت‌ها نیستند؛ خود `php plugin/topersia-ai-summarizer/tests/run.php` را محلی بزن |
