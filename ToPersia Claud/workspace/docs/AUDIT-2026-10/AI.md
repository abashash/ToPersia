# AI.md — ممیزی افزونۀ هوش مصنوعی (`topersia-ai-summarizer`)

> فاز ۲د · ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ — **فقط‌خواندنی**. هیچ کدی عوض نشده است.
> روش: خواندن کد بدون پایگاه‌داده و بدون سرور. هر بندی که به پایگاه‌داده، WP-CLI یا نسخۀ زنده نیاز دارد `UNKNOWN` است و فرمان محلی‌اش کنارش.
> برچسب‌ها: `VERIFIED` (مسیر و خط) · `INFERRED` (استنباط از کد/سند) · `ASSUMED` (فرض آگاهانه) · `UNKNOWN` (فقط با دسترسی محلی/زنده).
> نام‌اختصار مسیر: `AI` = `plugin/topersia-ai-summarizer` · `CORE` = `plugin/topersia-core` · `RENDER` = `plugin/topersia-render` · `UC` = `plugin/topersia-user-core` · `DOC` = `docs` · `REF` = `reference` (همه نسبت به `ToPersia Claud/workspace/`).
> هیچ مقدار کلید، رمز یا دادۀ کاربر در این پرونده نیست؛ فقط نام و شمار.

---

## ۱ — خلاصۀ حکم

| پرسش | پاسخ | برچسب |
|---|---|---|
| افزونه چیست؟ | نسخۀ `1.2.1`؛ دو چیز در یک بسته: (الف) دکمه‌های باز کردن دستیارهای بیرونی با پرامت آماده، (ب) پنل درون‌صفحه که با موتور داخلی (بی هوش مصنوعی) یا یک LLM سمت سرور خلاصه/نکات/برنامه می‌سازد | `[VERIFIED]` `AI/topersia-ai-summarizer.php:6` · `AI/includes/class-tp-ai-options.php:68` |
| معماری فعلی | هم‌زمان (synchronous) داخل یک درخواست AJAX؛ بی صف، بی کرون، بی REST | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:214` · `AI/includes/class-tp-ai-api.php:163` |
| شکاف اصلی با سند AI Core | Router، Policy، دروازۀ گردشگری، اعتبارسنجی خروجی، جدول `runs`، دفتر اعتبار، صف و idempotency **وجود ندارند** | `[VERIFIED]` (نبود: جستجوی `router`/`policy`/`gate`/`runs` در `AI/includes/*.php` بی نتیجه) |
| قابل نجات برای P5-D288؟ | **بله، با بازآرایی لایه‌ای** — لایه‌های «آداپتور پروتکل»، «بافت»، «موتور داخلی»، «پل کش مرکزی» قابل‌نگه‌داری‌اند؛ مسیر تولید (`handle_ajax`)، ذخیرۀ کلید و تلهمتری باید بازنویسی شود | `[INFERRED]` جدول بخش ۹ |
| یک یافتۀ فوری | مسیر `post=0&url=…` همۀ بررسی‌های «منتشرشده» و «نوع مجاز» و کش را دور می‌زند و پرامت را با نشانی دلخواه به LLM می‌فرستد (بخش ۶) | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:101-112,214` |

---

## ۲ — قابلیت‌ها

| # | قابلیت | شاهد | برچسب |
|---|---|---|---|
| 2.1 | دکمه‌های دستیار بیرونی (ChatGPT، Perplexity، Claude، Grok، Copilot، Gemini، DeepSeek) با نشانی `?q=` و پرامت پرشده؛ شمارش کلیک اختیاری | `AI/includes/class-tp-ai-services.php:22-80` · `AI/includes/class-tp-ai-options.php:42,57` | `[VERIFIED]` |
| 2.2 | پیش‌فرض کد: دکمه‌ها زیر تصویر شاخص نمایش داده می‌شوند (`position = after_thumbnail`) | `AI/includes/class-tp-ai-options.php:44` · `AI/includes/class-tp-ai-frontend.php:106-110` | `[VERIFIED]` |
| 2.3 | **تضاد با P5-D255** («هیچ پیوند بیرونی به ChatGPT و مانند آن»؛ ARCH-RECONCILIATION §۱): در کد، دکمه‌های بیرونی پیش‌فرض روشن‌اند و فقط با `position=manual` یا متای `_tpas_hide_buttons` خاموش می‌شوند. اینکه روی سایت محلی/زنده خاموش‌اند یا نه به مقدار option بستگی دارد | `DOC/SPRINT-4.4-PHASES.md:79` · `AI/includes/class-tp-ai-frontend.php:99-110` | `[VERIFIED]` (کد) |
| 2.4 | مقدار واقعی `tpas_settings.position` و `.services` روی محلی/زنده | — | `[UNKNOWN]` محلی: `wp eval '$o=(array)get_option("tpas_settings"); echo json_encode(array_intersect_key($o,array_flip(["position","services","internal_enabled","internal_engine","panel_position"])));'` (فقط همین کلیدها؛ آرایۀ کامل چاپ نشود چون شامل کلید API است) |
| 2.5 | پنل درون‌صفحه با سه خروجی: `summary` · `keypoints` · `plan`؛ فقط برای کاربر واردشده تزریق می‌شود | `AI/includes/class-tp-ai-options.php:196-198` · `AI/includes/class-tp-ai-frontend.php:113` | `[VERIFIED]` |
| 2.6 | موتور داخلیِ بی هوش مصنوعی (extractive: امتیاز TF + سوگیری آغاز + هم‌پوشانی عنوان؛ خلاصه، نکات، برنامۀ ساده) | `AI/includes/class-tp-ai-internal.php:73,128,172` | `[VERIFIED]` |
| 2.7 | ۴ نقطۀ ورود: شورت‌کد `tpas` · `topersia_ai` (دکمه‌ها) و `tpas_ai` · `topersia_ai_panel` (پنل)، بلوک‌های `tpas/buttons` و `tpas/panel`، ویجت المنتور | `AI/includes/class-tp-ai-shortcode.php:19-23` · `AI/includes/class-tp-ai-block.php:34,50` · `AI/includes/class-tp-ai-elementor-widget.php` | `[VERIFIED]` |
| 2.8 | بلوک هوش مصنوعی صفحۀ موجودیت در `topersia-render` خروجی ذخیره‌شده را از `TP_AI_Summary::get_cached()` می‌خواند؛ مهمان فقط «خلاصه» می‌بیند | `RENDER/includes/topersia-g-zones.php:4564-4586,4632-4636` | `[VERIFIED]` |
| 2.9 | صدا/TTS، سفر، پادکست، دروازۀ گردشگری، Near Me در افزونه **ساخته نشده**؛ فقط سطر کاتالوگ `ai.tts` (خاموش) و دو آداپتور بی مصرف‌کننده در هسته هست | `CORE/includes/topersia-services.php:56,87-88` | `[VERIFIED]` (نبود مصرف‌کننده: جستجوی `ai.tts` در همۀ ۵ افزونه فقط کاتالوگ و جدول TTL را می‌دهد، `CORE/includes/topersia-cache.php:240`) |
| 2.10 | فیلد ویراستاری `ai_summary` و `audio_narration` (ریپیتر) در متاباکس مشترک ۴۲ نوع تعریف شده؛ افزونه عمداً در `ai_summary` نمی‌نویسد | `REF/Jetengine_Skin.json` (JSON: `meta_boxes["meta-1"].meta_fields[name=ai_summary\|audio_narration]`؛ پرونده تک‌خطی است، خط ۱) · `AI/docs/ai-integration-notes.md` بخش «Shared entity meta-box fields» | `[VERIFIED]` |

---

## ۳ — جدول‌ها و طرحشان

| جدول | ستون‌ها و کلیدها | شاهد | برچسب |
|---|---|---|---|
| `{prefix}topersia_ai_summaries` | `id` · `post_id` · `type` (۲۰) · `language` (۱۰) · `content` LONGTEXT · `provider` · `model` · `source` (`ai`\|`internal`) · `created_at` · `updated_at`؛ کلید یکتا `(post_id, type, language)` | `AI/includes/class-tp-ai-db.php:68-83` | `[VERIFIED]` |
| `{prefix}topersia_ai_events` | `id` · `post_id` · `event_type` · `provider` · `created_at`؛ فقط رویداد کلیک (`assistant_click`)؛ بی IP و بی UA | `AI/includes/class-tp-ai-db.php:85-94` · `AI/includes/class-tp-ai-tracker.php:59-60` | `[VERIFIED]` |
| ذخیرۀ دوم/سوم همان خروجی | متای پست `_tpas_ai_{type}_{lang}` (مسیر قدیمی که هنوز نوشته می‌شود) و `_tpas_hash_{type}_{lang}` (هش محتوا هنگام ساخت) — یعنی **سه جا برای یک خروجی** | `AI/includes/class-tp-ai-summary.php:331-341` · `AI/includes/class-tp-ai-core-bridge.php:49,79` | `[VERIFIED]` |
| ستون `source` نادیده گرفته می‌شود | `get_cached()` ردیف را بی توجه به `source` برمی‌گرداند؛ پس متن داخلی (extractive) که یک بار ذخیره شده تا تغییر هش محتوا جای فراخوانی LLM می‌نشیند | `AI/includes/class-tp-ai-summary.php:252-262` · `AI/includes/class-tp-ai-summary.php:125-135` | `[VERIFIED]` (کد) · اثر عملی `[INFERRED]` |
| `uninstall.php` جدول‌ها و گزینه‌های `tpas_db_version` و `tpas_prompt_overrides` و متای `_tpas_hash_*` را حذف نمی‌کند | فقط `tpas_settings`، `tpas_stats`، متاهای `_tpas_*` فهرست‌شده و ترنزینت‌های `tpas_rt_` | `AI/uninstall.php:13-34` | `[VERIFIED]` |
| تعداد ردیف‌ها، توزیع `provider`/`model`/`source`، حجم `content` | — | — | `[UNKNOWN]` محلی: `wp db query "SELECT source, provider, model, language, type, COUNT(*) n, SUM(LENGTH(content)) bytes FROM $(wp db prefix)topersia_ai_summaries GROUP BY 1,2,3,4,5"` |
| وجود جدول روی زنده (نسخۀ `tpas_db_version`) | — | — | `[UNKNOWN]` محلی: `wp option get tpas_db_version` و `wp db query "SHOW TABLES LIKE '%topersia_ai%'"` |

---

## ۴ — شناسۀ کش (Cache identity)

| لایه | تعریف | شاهد | برچسب |
|---|---|---|---|
| ردیف ذخیره | یکتایی `(post_id, type, language)`؛ بی نسخۀ پرامت، بی ارائه‌دهنده/مدل | `AI/includes/class-tp-ai-db.php:80` | `[VERIFIED]` |
| کلید کش مرکزی | `sha1( json{ service, provider, model, version, lang, parts, hash } )`؛ برای خروجی گران (`expensive`) هش محتوا **در کلید نیست** بلکه در پوشه (envelope) می‌نشیند و با آن مقایسه می‌شود | `CORE/includes/topersia-cache.php:275-288,426-437` | `[VERIFIED]` |
| فراخوانی خلاصه‌ساز | سرویس `ai.text`، بخش‌ها `[post, type]`، `lang` در گزینه‌ها، `expensive=true`، `rebuild=true`، انباره = همان جدول خلاصه‌ها | `AI/includes/class-tp-ai-summary.php:179-198` | `[VERIFIED]` |
| ارائه‌دهنده/مدل/نسخه در کلید | چون هستۀ ۴٫۱۲٫۱ سطر `ai.text` را از کاتالوگ برداشته، `topersia_api('ai.text')` مقدار پیش‌فرض خالی برمی‌گرداند و `p`/`m`/`v` کلید خالی‌اند | `CORE/includes/topersia-services.php:53-65,206` · `CORE/topersia-core.php:57-58` | `[VERIFIED]` |
| نتیجه | **عوض‌شدن ارائه‌دهنده، مدل یا پرامت هیچ خروجی ذخیره‌ای را کهنه نمی‌کند**؛ فقط عوض‌شدن هش محتوای پست کهنه می‌کند | استنتاج از دو ردیف بالا | `[INFERRED]` |
| حکم ARCH-RECONCILIATION بند ۸ («SHA-256 بر بستۀ منبعِ نرمال‌شده + زبان + نسخۀ پرامت») | **با کد نمی‌خواند**: هش فعلی SHA-1 است، منبع نرمال‌شده نیست (فیلدهای متا از `topersia_content_hash_fields`)، و نسخۀ پرامت اصلاً وجود ندارد. پذیرش حکم به معنای کار تازه است نه تأیید وضع موجود | `CORE/includes/topersia-cache.php:46-110` · `DOC/ARCH-RECONCILIATION-v1_0.md` (ردیف ۸ جدول §۲) | `[VERIFIED]` |

---

## ۵ — ارائه‌دهنده‌ها و مدل‌ها

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| پیش‌تنظیم‌ها (۸) | DeepSeek (`deepseek-chat`) · OpenAI (`gpt-4o-mini`) · Grok (`grok-4-fast`) · Perplexity (`sonar`) · Claude (`claude-sonnet-4-5`) · Gemini (`gemini-2.0-flash`) · Azure OpenAI (نشانی کامل استقرار) · سفارشی سازگار با OpenAI | `AI/includes/class-tp-ai-options.php:300-351` | `[VERIFIED]` |
| چهار پروتکل | `openai` (`/chat/completions`) · `anthropic` (`/messages`، سرآیند `x-api-key`) · `gemini` (`:generateContent`، سرآیند `x-goog-api-key`) · `azure` (سرآیند `api-key`) | `AI/includes/class-tp-ai-api.php:94-101,125,234,336` | `[VERIFIED]` |
| ردیف‌های موتور | فهرست تکرارشونده `internal_engines` + `internal_default_engine`؛ **یک** موتور در هر درخواست | `AI/includes/class-tp-ai-options.php:61-62` · `AI/includes/class-tp-ai-api.php:72-101` | `[VERIFIED]` |
| بازگشت خودکار (failover) بین موتورها | **نیست**: `chat()` یک نقطه را صدا می‌زند و خطا را برمی‌گرداند. ادعای P5-D317 («ارائه‌دهندۀ دوم فقط پشتیبان خودکار») در همین افزونه پیاده نشده؛ سازوکار `fallback` فقط در لایۀ سرویس‌های هسته (برای TTS/هوا/…) هست | `AI/includes/class-tp-ai-api.php:72-101` · `CORE/includes/topersia-services.php:298-307` | `[VERIFIED]` |
| ذخیرۀ کلید | **متن ساده** در option `tpas_settings` (زیرآرایۀ `internal_engines[].api_key`)، بی رمزنگاری sodium (که فقط در لایۀ سرویس‌های هسته هست) و با autoload پیش‌فرض؛ فیلد پیشخوان `type=password` و مقدار را بازنمایش نمی‌دهد | `AI/includes/class-tp-ai-options.php:18,553-567` · `AI/includes/class-tp-ai-admin.php:570` · مقایسه: `CORE/includes/topersia-services.php:112-157` | `[VERIFIED]` |
| استثنا از P5-D249 | CLAUDE.md خودش می‌گوید «تولید متن در این لایه نیست — کلید و موتورش در تنظیمات خودِ خلاصه‌ساز (خواستۀ کاربر)»؛ پس این **تضاد با P5-D249 تصمیم آگاهانه است**، ولی ریسک نشت (بخش RISKS) برقرار می‌ماند | `WS/CLAUDE.md` (بند «کلید سرویس فقط سمت سرور») | `[VERIFIED]` |
| ثابت wp-config برای کلید LLM | **نیست** (فقط TTS/نشان/… در هسته `key_const` دارند) | `CORE/includes/topersia-services.php:87-95` | `[VERIFIED]` |
| `base_url` سفارشی از پیشخوان (http/https) و دکمۀ «آزمون کلید» با `base_url` ارسالی | مسیر SSRF فقط برای `manage_options` | `AI/includes/class-tp-ai-options.php:569` · `AI/includes/class-tp-ai-admin.php:833-852` | `[VERIFIED]` |
| ارائه‌دهنده و مدل واقعاً انتخاب‌شده روی محلی/زنده | — | — | `[UNKNOWN]` محلی: `wp eval 'foreach((array)(get_option("tpas_settings")["internal_engines"]??[]) as $e){echo $e["id"]," ",$e["provider"]," ",$e["model"],"\n";}'` (کلید چاپ نمی‌شود) |
| دسترسی سرور زنده به API گوگل/OpenAI از IP ایران | — | — | `[UNKNOWN]` محلی/زنده: `curl -sS -o /dev/null -w "%{http_code}\n" https://generativelanguage.googleapis.com/` روی خود سرور (حکم #10 ARCH-RECONCILIATION → فاز ۱۰) |

---

## ۶ — REST، AJAX، کار پس‌زمینه

| نقطه | ورودی | کنترل دسترسی | شاهد | برچسب |
|---|---|---|---|---|
| REST | **هیچ مسیر RESTی در این افزونه نیست** | — | جستجوی `register_rest_route` در `AI/` بی نتیجه | `[VERIFIED]` |
| AJAX `tpas_ai_generate` | `post` · `type` · `lang` · `url` · `nonce` | فقط واردشده (`nopriv` ثبت نشده)؛ نانس `tpas_ai`؛ دروازۀ سقف روزانه در `RENDER` با اولویت ۱ پیش از آن | `AI/includes/class-tp-ai-summary.php:19,88,94` · `RENDER/includes/topersia-g-ai.php:118-163` | `[VERIFIED]` |
| AJAX `tpas_track` | `post` · `service` · `nonce` | **بی ورود** (`nopriv`)؛ نانس `tpas_ai` که همان نانس عمومی صفحه است | `AI/includes/class-tp-ai-tracker.php:24-25,34` · `AI/includes/class-tp-ai-frontend.php:50-52` | `[VERIFIED]` |
| AJAX `tpas_test_key` | موتور موقت | `manage_options` + نانس `tpas_admin` | `AI/includes/class-tp-ai-admin.php:24,833-835` | `[VERIFIED]` |
| کار پس‌زمینه / کرون / Action Scheduler | **هیچ**؛ تولید همان لحظه در درخواست کاربر با `timeout` ۶۰ ثانیه (در هر سه پروتکل) |  | `AI/includes/class-tp-ai-api.php:163,255,364` · جستجوی `wp_schedule_event` و `topersia_queue_add` در `AI/` بی نتیجه | `[VERIFIED]` |
| `post=0` + `url=…` | شرط `if ( ! $post && ! $url )` فقط وقتی هر دو خالی‌اند رد می‌کند؛ با `post=0` و یک `url` دلخواه: (الف) بررسی «منتشرشده» (`$post &&`) و «نوع مجاز» (`$post &&`) اجرا نمی‌شود، (ب) کش مرکزی نیست (`$post &&`)، (ج) مستقیم `TP_AI_API::chat()` با پرامتی که `url` در آن است، (د) نتیجه ذخیره نمی‌شود (`store` با `post_id=0` برمی‌گردد). فقط سقف‌های روزانه/ساعتی جلویش را می‌گیرند. LLMهای دارای مرور وب (مثل `sonar`) می‌توانند آن نشانی را بخوانند | `AI/includes/class-tp-ai-summary.php:101,108,112,175,214,223` · `AI/includes/class-tp-ai-render.php:387` | — | `[VERIFIED]` (جریان) · اثر `[INFERRED]` |
| محتوای صفحه برای `url` | هیچ واکشی از `url` نمی‌شود (فقط برچسب پرامت) → SSRF سمت وردپرس نیست | `AI/includes/class-tp-ai-summary.php:418-461` | — | `[VERIFIED]` |
| بسترساز ورودی | `lang` به `[a-z0-9_-]` و `type` به فهرست سفید محدود است | `AI/includes/class-tp-ai-summary.php:469,504-509` | — | `[VERIFIED]` |
| صف `topersia_queue_add` برای تولید | استفاده نمی‌شود؛ تنها مصرف‌کنندۀ صف هسته گالری تجمیعی است | `RENDER/includes/topersia-g-gallery-agg.php:377,496,583` | — | `[VERIFIED]` |
| idempotency | دو درخواست هم‌زمان برای یک (پست، نوع، زبان) هر دو LLM را صدا می‌زنند؛ قفل/کلید کار نیست | `AI/includes/class-tp-ai-summary.php:125-214` (خواندن کش ← فراخوانی، بی قفل) | — | `[VERIFIED]` |

---

## ۷ — پرامت‌ها، سقف‌ها، محافظت‌ها

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| محل پرامت | رشته‌های `prompt_default` / `prompt_keypoints` / `prompt_plan` در `TP_AI_Localize::strings()` (فقط `fa` · `en` · `ar`)، با رونوشت‌های اختیاری در option `tpas_prompt_overrides`؛ پیام سیستم در `context_system()` با صافی `tpas_system_prompt` | `AI/includes/class-tp-ai-localize.php:143-147,171,198,223,402-424` · `AI/includes/class-tp-ai-prompt-manager.php:20,38-48` | `[VERIFIED]` |
| نسخه‌گذاری پرامت | **ندارد** (نه شمارۀ نسخه، نه فایل جدا؛ نه ثبت در ردیف خلاصه) — بند ۱۸ AI Core («فایل نسخه‌دار») هنوز نیامده | جستجوی `version` در `AI/includes/class-tp-ai-prompt-manager.php` بی نتیجه | `[VERIFIED]` |
| زبان‌ها | `TP_AI_Localize::languages()` ۹ کد دارد (`fa en ar fr de es ru tr zh`) ولی رشتۀ رابط/پرامت فقط سه زبان؛ بقیه به انگلیسی برمی‌گردند. هدف پروژه ۹ زبان است نه ۱۰ (سند AI Core: ۱۰ با چینی ساده) | `AI/includes/class-tp-ai-localize.php:25-27,160-163` · `DOC/ARCH-RECONCILIATION-v1_0.md` §۷ بند ۳ | `[VERIFIED]` |
| سقف ساعتی (داخل افزونه) | `internal_throttle` = ۱۰ در ساعت برای هر کاربر و نوع؛ لایۀ IP ×۵؛ با ترنزینت `tpas_rt_u_*`/`tpas_rt_ip_*` | `AI/includes/class-tp-ai-options.php:67` · `AI/includes/class-tp-ai-summary.php:362-405` | `[VERIFIED]` |
| سقف روزانه (دروازۀ `render`) | پیش‌فرض تنظیمات: کاربر ۵ و کل سایت ۱۰۰ در روز (اگر تابع تنظیمات نباشد ۱۰ و ۲۰۰)؛ کاربر در متای `_topersia_ai_daily`، سایت در ترنزینت `tp-ai-site-{Y-m-d}`؛ **پیش از تولید شمرده می‌شود و در شکست برنمی‌گردد** و خواندن‌افزایش‌نوشتن اتمی نیست | `RENDER/includes/topersia-settings.php:55-56` · `RENDER/includes/topersia-g-ai.php:52-53,86-108,147-158` | `[VERIFIED]` |
| سقف سوم: «سقف روزانه» صفحۀ سرویس‌ها | ستون `limit_user`/`limit_site` ذخیره می‌شود ولی تنها مصرف‌کننده‌اش شاخۀ مرده است (`$layer = null` ← همیشه `legacy`)؛ **هیچ کس نمی‌خواندش** | `CORE/includes/topersia-services.php:285-288,525-526` · `RENDER/includes/topersia-g-ai.php:28-42` · `RENDER/includes/topersia-g-core.php:70-74` | `[VERIFIED]` |
| IP کاربر برای سقف | اولین مقدار `X-Forwarded-For` (قابل جعل) به‌جای `REMOTE_ADDR`؛ برخلاف `topersia-user-core` که فقط با ثابت `TOPERSIA_TRUST_PROXY` می‌خواندش | `AI/includes/class-tp-ai-summary.php:516-531` · `UC/includes/class-topersia-auth.php:145-157` | `[VERIFIED]` |
| سقف توکن/بافت | `internal_max_tokens` ۷۰۰ · `internal_context_chars` ۸۰۰۰ · `internal_cache_hours` ۷۲۰ (سی روز؛ مسیر قدیمی متا) · دما ۰٫۳؛ پیش‌فرض‌های درون‌کد (۱۲۰۰/۴۰۰۰۰/۱۶۸) فقط وقتی option نیست | `AI/includes/class-tp-ai-options.php:63-66` · `AI/includes/class-tp-ai-summary.php:419` · `AI/includes/class-tp-ai-api.php:92` | `[VERIFIED]` |
| بافت فقط از منتشرشده | `published_ids()` (publish + بی‌گذرواژه) برای خود پست و رابطه‌ها و زنجیرۀ جغرافیایی | `AI/includes/class-tp-ai-context.php:409-422,483,554` · `AI/includes/class-tp-ai-summary.php:108` | `[VERIFIED]` |
| دفاع پرامت-تزریق | پیام سیستم می‌گوید «فقط از محتوای زیر» و «بی HTML»؛ **جداسازی/برچسب‌گذاری محتوای غیرقابل‌اعتماد نیست** و دروازۀ ورودی متن آزاد هم نیست. محتوا از ویراستاران سایت می‌آید (ریسک پایین) ولی `url` و عنوان از کاربر | `AI/includes/class-tp-ai-localize.php:407-409` · `AI/includes/class-tp-ai-render.php:387` | `[VERIFIED]` (نبود جداسازی) · شدت `[ASSUMED]` پایین |
| ایمنی خروجی | `format_ai_text()` ابتدا `esc_html` سپس `wp_kses` با فهرست سفید | `AI/includes/class-tp-ai-render.php:433-437,483` | `[VERIFIED]` |
| **مسیر XSS از پیام خطا** | JS پیام خطای سرور را بی‌گریز در `innerHTML` می‌گذارد؛ پیام خطا همان متن خطای ارائه‌دهنده است (`error.message` پاسخ HTTP) که `wp_send_json_error` بی‌گریز برمی‌گرداند | `AI/assets/js/topersia-ai.js:128,171-173` · `AI/includes/class-tp-ai-summary.php:202,217` · `AI/includes/class-tp-ai-api.php:187-190` | `[VERIFIED]` (جریان) · بهره‌برداری نیازمند ارائه‌دهندۀ مخرب/`base_url` دستکاری‌شده `[ASSUMED]` |
| تکرار سقف: سه سازوکار هم‌پوشان | ساعتی (افزونه) + روزانۀ کاربر/سایت (render) + ستون مرده (core) | سه ردیف بالا | `[VERIFIED]` |

---

## ۸ — صدا، تلهمتری، لاگ

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| محل ذخیرۀ صدا | **جایی نیست** — تولید صدا ساخته نشده؛ بند ۱۱ («فایل صدا در Media Library؛ منشأ در جدول») هنوز طراحی است | بخش ۲٫۹ | `[VERIFIED]` (نبود) |
| آداپتورهای TTS در هسته | Gemini TTS (`gemini-3.8-flash-tts`) و ElevenLabs (`eleven_v3`، `voice_id`) فقط ثبت و «آزمون اتصال» دارند؛ سرویس `ai.tts` خاموش است | `CORE/includes/topersia-services.php:56,87-88,466-470` | `[VERIFIED]` |
| تلهمتری هوش مصنوعی | فقط شمار کلیک دکمه‌های بیرونی در `topersia_ai_events` (و `tpas_stats`)؛ **توکن، تأخیر، هزینه، وضعیت اعتبارسنجی و شناسۀ اجرا ثبت نمی‌شود** و خروجی `chat()` اصلاً شمار توکن را برنمی‌گرداند | `AI/includes/class-tp-ai-db.php:85-94` · `AI/includes/class-tp-ai-api.php:211-216` | `[VERIFIED]` |
| لاگ مرکزی | افزونه از `topersia_log()` استفاده نمی‌کند؛ کانال `ai` در هیچ جا نیست (کانال‌های دیده‌شده در فراخوانی‌های کد: `import guard relation user render geo wpml queue log`، و `js` برای خطای مرورگر) | جستجوی `topersia_log` در `AI/` بی نتیجه؛ فهرست کانال از جستجوی همۀ افزونه‌ها | `[VERIFIED]` |
| شمارش موفق/ناموفق به ازای سرویس | `topersia_api_record()` در `topersia_cache_remember` برای `ai.text` هم صدا می‌شود و در option `topersia_services_usage` (بی autoload) جمع می‌شود | `CORE/includes/topersia-cache.php:449,485-487` · `CORE/includes/topersia-services.php:332-364` | `[VERIFIED]` |
| حکم #17 ARCH-RECONCILIATION («کانال `ai` لاگ هسته یا جدول افزونه؛ ۲د تعیین می‌کند») | **توصیۀ ممیزی:** جدول افزونۀ هوش مصنوعی (`topersia_ai_runs`)، نه کانال لاگ — دلیل: کانال لاگ `context` را LONGTEXT بی‌شاخص نگه می‌دارد و با «پاک‌کردن لاگ/هرس» (`topersia_log_prune`) می‌رود؛ شاخص‌های مالی/آماری (توکن، تأخیر) باید ماندگار و قابل‌پرس‌وجو باشند. لاگ فقط برای خطا بماند | `CORE/topersia-core.php:1086-1101,1385-1387` | `[INFERRED]` |
| حجم/رشد فعلی `topersia_ai_events` و `tpas_stats` | — | — | `[UNKNOWN]` محلی: `wp db query "SELECT event_type, provider, COUNT(*) FROM $(wp db prefix)topersia_ai_events GROUP BY 1,2"` و `wp option get tpas_stats --format=json` |
| نرخ برخورد کش `ai.text` و هزینۀ ماه | — | — | `[UNKNOWN]` محلی: `wp option get topersia_services_usage --format=json` (شمار ok/خطا به ازای سرویس) |

---

## ۹ — شکاف با سند AI Core و قابل‌نجات‌بودن (P5-D288)

> ستون «حکم» از `DOC/ARCH-RECONCILIATION-v1_0.md` §۲ است. جایی که شاهد با حکم نمی‌خواند صریح نوشته شده؛ موافقت لازم نیست.

| سند AI Core (بند) | امروز در کد | شکاف | قابل نجات؟ | برچسب |
|---|---|---|---|---|
| Router (۱۱-۱۲، ۷۱) | `TP_AI_API::chat()` یک موتور پیش‌فرض را بر پایۀ `protocol` دسترسی می‌دهد | انتخاب بر پایۀ **کار** (summary/audio/…) نیست؛ failover نیست | سوئیچ پروتکل = **ProviderAdapter** درست است و می‌ماند؛ Router/Policy روی آن اضافه شود | `[INFERRED]` |
| Policy | — | هیچ | افزودنی | `[VERIFIED]` (نبود) |
| دروازۀ گردشگری (۸-۹) | — | ورودی متن آزاد امروز نیست (فقط دکمه)؛ **ولی** مسیر `post=0&url=` یک ورودی نیمه‌آزاد است (بخش ۶) | حکم #5 («با نخستین ورودی آزاد») **با شاهد نمی‌خواند**: نخستین ورودی آزاد همین حالا هست؛ حداقل بستن مسیر `post=0` پیش از فاز ۴ لازم است | `[VERIFIED]` |
| اعتبارسنجی خروجی / Quality Engine (۳۹) | `format_ai_text` فقط گریز و فهرست سفید می‌کند | بی ساختار JSON، بی بررسی ارجاع موجودیت، بی بررسی زبان | افزودنی | `[VERIFIED]` |
| جدول `runs` (۴۶، ۷۲) | — | بخش ۸ | افزودنی (توصیه: جدول افزونه) | `[VERIFIED]` |
| دفتر اعتبار (۵۵-۵۶) | سقف روزانه ساده | حکم #15 = تعویق؛ **ولی** باید «هزینۀ عملیات» ثبت شود و امروز چیزی ثبت نمی‌شود | افزودنی | `[VERIFIED]` |
| صف و idempotency (۴۲-۴۳) | صف هسته هست و کار می‌کند (گالری)؛ تولید AI همزمان است | تولید به صف نرفته؛ دو درخواست هم‌زمان = دو هزینه | مسیر `handle_ajax` ← کار صف + کلید idempotency | `[INFERRED]` |
| شناسۀ کش (۱۴) | بخش ۴ | SHA-256/نسخۀ پرامت/ارائه‌دهنده در کلید نیست | باید بازنویسی شود (حکم #8 پذیرفتنی ولی پیاده نیست) | `[VERIFIED]` |
| پرامت فایل نسخه‌دار (۴۹) | رشته‌های درون کلاس | بخش ۷ | افزودنی | `[VERIFIED]` |
| ۳ ارائه‌دهنده هم‌زمان (حکم #7: رد) | کد این کار را **نمی‌کند** (یک موتور) | هیچ | سازگار با حکم | `[VERIFIED]` |
| بلوک «سه‌بخشی ثابت» (P5-D317) | `summary` · `keypoints` · `plan` | هست، ولی برای کاربر واردشده تولید می‌کند و در `render` فقط بازتاب کش است | با تصمیم جدید (تولید در صف، نه کلیک کاربر) نمی‌خواند — تغییر معماری لازم است | `[VERIFIED]` |
| نام ارائه‌دهنده فقط در پیشخوان (حکم #7) | پاسخ AJAX `provider` و `model` را به مرورگر می‌دهد | **با حکم نمی‌خواند**: نام ارائه‌دهنده و مدل در JSON پاسخ عمومی (کاربر واردشده) می‌آید | باید از پاسخ حذف شود | `[VERIFIED]` `AI/includes/class-tp-ai-summary.php:132-133,209-210,229-230` |

**ماتریس بازآرایی (حکم: بازآرایی لایه‌ای، نه بازنویسی کامل):**

| فایل | تصمیم | دلیل |
|---|---|---|
| `class-tp-ai-api.php` (۴۴۰ خط، ۳ پروتکل) | **نگه‌دار** و پشت رابط `ProviderAdapter` بگذار | پروتکل‌ها درست و آزموده‌اند؛ فقط شمار توکن و کد خطای استاندارد کم است |
| `class-tp-ai-options.php` | **نگه‌دار + مهاجرت کلید** | کلید باید به لایۀ رمزشده یا ثابت wp-config برود |
| `class-tp-ai-context.php` (۶۱۰ خط) | **نگه‌دار** | بافت مبتنی بر `entity_uid`/رابطه‌ها و فقط منتشرشده، با صافی‌های مستند |
| `class-tp-ai-internal.php` | **نگه‌دار** | موتور بی هزینه؛ «کف» کیفیت برای حالت بی کلید |
| `class-tp-ai-core-bridge.php` | **بازنویس سبک** | کلید کش + نسخۀ پرامت + ارائه‌دهنده |
| `class-tp-ai-summary.php::handle_ajax` | **بازنویس** | بستن `post=0`، صف، قفل، حذف نام ارائه‌دهنده از پاسخ، ثبت اجرا |
| `class-tp-ai-db.php` | **گسترش** | جدول `runs`، ستون `prompt_version`/`input_hash` |
| `class-tp-ai-tracker.php` + ‌دکمه‌های بیرونی | **تصمیم کاربر** | با P5-D255 تعارض دارد (بخش ۲٫۳) |

`[INFERRED]` برای کل جدول بالا (قضاوت معماری روی کد خوانده‌شده).

---

## ۱۰ — فهرست UNKNOWN این پرونده (برای اجرا در محلی)

همان بندهای UNKNOWN بالا با فرمان‌هایشان در `LOCAL-QUERIES.md` (پرونده‌ای خودکار از همین ردیف‌ها) جمع شده‌اند.

---

## ۱۱ — دور دوم (تطبیق با ممیزی مستقل ب)

> منبع: **دور دوم** — `RECONCILE.md` بخش‌های ۳-۰ و ۳-ج.

| مورد | یافته | شاهد | برچسب |
|---|---|---|---|
| autoload `tpas_settings` | `add_option` بی پارامتر و Settings API ← وردپرس ۷٫۱٫۲ `auto` می‌نویسد که بارشونده است | `AI/topersia-ai-summarizer.php:104-105` · `WP/wp-includes/option.php:1069,1333-1338,3260` | `[VERIFIED]` |
| کلید در صافی عمومی | `apply_filters( 'tpas_api_payload', $body, $endpoint, $key )` | `AI/includes/class-tp-ai-api.php:144,248,353` | `[VERIFIED]` |
| دورزدن سقف روزانه با کهنه (R53) | دروازۀ render با هر ردیف کش برمی‌گردد؛ تولید کهنه را بازمی‌سازد | `RENDER/includes/topersia-g-ai.php:142-144` · `AI/includes/class-tp-ai-summary.php:125-128` | `[VERIFIED]` |
| ردیف داخلی هرگز کهنه نمی‌شود (R54؛ ادغام با ردیف «ستون source» بخش ۳) | `store(…,'internal')` هش نمی‌نویسد؛ `is_stale` با هش تهی `false` | `AI/includes/class-tp-ai-summary.php:309,326` · `AI/includes/class-tp-ai-core-bridge.php:79,91-93` · `CORE/includes/topersia-cache.php:501-504` | `[VERIFIED]` |
| `post_content` در هش نیست (R55) | عنوان + فیلدهای صافی | `CORE/includes/topersia-cache.php:76-103` | `[VERIFIED]` |
| `mb_strtolower` بی گارد (R57) | — | `AI/includes/class-tp-ai-internal.php:346` | `[VERIFIED]` |
| `tpas_track` بی سنجش پست | — | `AI/includes/class-tp-ai-tracker.php:36-47` | `[VERIFIED]` |
