# USER — ممیزی ۲د: `topersia-user-core` ۱٫۱۰٫۴

> ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ · فقط‌خواندنی (P5-D287). جلسۀ ابری ۲ هم‌زمان روی همین افزونه کار می‌کند؛ این ممیزی هیچ پروندۀ آن را عوض نکرد. اگر جلسۀ ۲ پس از ۱۲ اکتبر ساعت ۱۷ پرونده‌ای در `plugin/topersia-user-core/` عوض کند، شمارۀ خط‌های زیر ممکن است جابه‌جا شده باشند.
> مسیرها نسبت به `ToPersia Claud/workspace/plugin/topersia-user-core/` (مگر ذکر دیگر). نسخۀ نصب‌شدۀ `geo/wp-content/plugins/topersia-user-core` در دو پرونده (`CHANGELOG.md`، `CLAUDE.md`) با workspace فرق دارد، نه در کد (R44، SYSTEM.md).
> برچسب‌ها: VERIFIED (مسیر + خط) · INFERRED · ASSUMED · UNKNOWN. هیچ شمارۀ موبایل، کلید یا نام کاربری در این سند نیست.

## ۰ — خلاصه

| | |
|---|---|
| اندازه | ۱۷ پروندۀ `includes/` + ۲ قالب؛ ۴٬۶۹۲ خط PHP بی آزمون (۴٬۰۲۷ خط در `includes/`) |
| نیاز | PHP ۸٫۰ (`topersia-user-core.php:6`)، وردپرس ۶٫۴ — ولی افزونۀ همخانواده `topersia-ai-summarizer` PHP ۷٫۲ اعلام می‌کند؛ نسخۀ PHP زنده **UNKNOWN** (SYSTEM.md) |
| ورود | فقط پیامک یک‌بارمصرف از راه Mediana، فقط شمارۀ ایران `^09[0-9]{9}$` |
| ذخیره | جدول سفارشی `topersia_saved` (یک سطر برای هر مکان) |
| دنبال‌کردن | متای کاربر `_topersia_followed` (حداکثر ۵۰۰ شناسه) |
| نظر | داخل JetReviews با API خودش (نه SQL)، همیشه «در انتظار تأیید» |
| سفر، مجموعه، اعلان، کشور-محور بودن ورود، ورود غیرایرانی | **نیستند** (بخش ۹) |
| حکم | هسته سالم و امنیتی دقیق است؛ دو ایراد واقعی: کلید پیامک متن‌ساده و در HTML پیشخوان، و نرخ‌سنج IP که پشت پروکسی ممکن است همه را یکی ببیند |

## ۱ — جریان ورود

| گام | رفتار | شاهد |
|---|---|---|
| ۱ | `wp_ajax_nopriv_topersia_send_otp` و نسخۀ واردشده، نانس `topersia_user_core` | `includes/class-topersia-auth.php:56-57,120-124` — **VERIFIED** |
| ۲ | شماره نرمال می‌شود (`+98`/`0098` ← `0`، بقیۀ نویسه‌های نامعدد حذف) و باید `^09[0-9]{9}$` باشد | `:126-135` — **VERIFIED** |
| ۳ | اگر شماره به حسابی با یکی از توانایی‌های `manage_options` · `edit_posts` · `edit_pages` · `upload_files` تعلق دارد، پیش از پیامک رد می‌شود | `:331-339` و بررسی در `send_otp` (`:359-370`) — **VERIFIED** |
| ۴ | نرخ‌سنجی چهارلایه: هر شماره ۵ در ساعت · فاصله ۴۵ ثانیه · هر IP ۱۰ در ساعت و حداکثر ۳ شمارۀ متمایز · کل سایت ۱۲۰ در ساعت؛ شکست‌بسته؛ پس‌دادن سهمیه در شکست پیامک | `:29-47,169-300,371-389` — **VERIFIED** |
| ۵ | کد ۶ رقمی `wp_rand(100000, 999999)`؛ فقط هش (`wp_hash_password`) در ترنزینت `tp_otp_{token}` با TTL ۳۰۰ ثانیه، شمارندۀ حدس داخل همان ترنزینت (سقف ۵) | `:377-378,399-409,436-457` — **VERIFIED** |
| ۶ | ثبت تلاش در جدول `topersia_otp_attempts` (شماره، هدف، انقضا، شناسۀ درخواست پیامک، شمار تلاش، تأییدشده، `ip_hash`) | `includes/class-topersia-db.php:27-42` و `:34-62` (ریپازیتوری) — **VERIFIED** |
| ۷ | تأیید: بررسی دوباره توانایی‌ها، سپس ساخت حساب (در نخستین ورود) با نقش `subscriber` و نام کاربری `tp_{شماره}`، ایمیل `{شماره}@users.topersia.com`، رمز تصادفی ۳۲ نویسه | `:475-499,529-558` — **VERIFIED** |
| ۸ | `wp_set_auth_cookie` + `do_action( 'wp_login' )` + بازگشت به صفحۀ مبدأ با `safe_redirect` | `:506-517` — **VERIFIED** |
| ۹ | خروج با نانس | `:565-574` — **VERIFIED** |

یافته‌ها:

- **ورود فقط ایرانی** است: هر شمارۀ غیر `09…` با ۴۲۲ رد می‌شود (`:133-135`). با خواستۀ ثبت‌شده در `ROADMAP.md` («ورود گوگل و ایمیل برای عربی و انگلیسی»، فاز ۳ آن سند) و با P5-D257 (`auth.google` در کاتالوگ هسته با `enabled=false`) سازگار است؛ هنوز پیاده نشده — **VERIFIED**.
- **نرخ‌سنج IP پشت پروکسی:** `ip_hash()` فقط `REMOTE_ADDR` را می‌خواند مگر ثابت `TOPERSIA_TRUST_PROXY` تعریف شده باشد (`:145-157`). ثابت در هیچ پروندۀ workspace یا `mu-plugins` نیست (جست‌وجوی کل مخزن بیرون از `geo/`: فقط همین `if`). روی میزبان زنده (cPanel/LiteSpeed با لایۀ پراکسی/کش یا Cloudflare) `REMOTE_ADDR` ممکن است IP پراکسی باشد ← «۱۰ در ساعت» و «۱۲۰ در ساعت» برای همۀ کاربران مشترک می‌شود و ورود قانونی را می‌بندد. تعریف ثابت در `wp-config.php` زنده **UNKNOWN**؛ اثر **INFERRED**. محلی/زنده: `grep -c TOPERSIA_TRUST_PROXY wp-config.php` و آزمون `curl -sI` برای دیدن `X-Forwarded-For`/`CF-Connecting-IP`.
- حساب ساخته‌شده ایمیل واقعی ندارد؛ `mail()` روی هاست غیرفعال گزارش شده (`includes/class-topersia-review-form.php:20`) ← بازیابی رمز و هر اعلان ایمیلی ناممکن است — **VERIFIED** (اظهار کد) / ادعای «mail() غیرفعال» **ASSUMED** از توضیح توسعه‌دهنده؛ تأیید روی میزبان: `php -r 'var_dump(function_exists("mail"));'` و سیاست ارسال میزبان **UNKNOWN**.
- جست‌وجوی کاربر بر پایۀ شماره با `get_users(meta_key/meta_value)` (`:308-320`): `meta_value` در `wp_usermeta` ایندکس ندارد (ایندکس فقط روی `meta_key` و `user_id`) ← با رشد کاربران جست‌وجو کند می‌شود — **INFERRED**، مقیاس فعلی کم.

## ۲ — ارائهدهندۀ پیامک

| | |
|---|---|
| رابط | `ToPersia_SMS_Provider::send_otp( $recipient, $otp, $pattern_code )` — `includes/class-topersia-sms-provider.php` |
| پیاده‌سازی | `ToPersia_Mediana_SMS` — `POST https://api.mediana.ir/sms/v1/send/otp`، سرآیند `X-API-KEY`، بدنه `patternCode`/`recipient`/`otpCode`، مهلت ۱۵ ثانیه — `includes/class-topersia-mediana-sms.php:5-22` — **VERIFIED** |
| انتخاب ارائه‌دهنده | سخت‌کد: `new ToPersia_Mediana_SMS()` در `send_otp` (`class-topersia-auth.php:381`)؛ فیلتر یا ثبت‌کنندۀ ارائه‌دهنده نیست ← دومین ارائه‌دهنده یعنی دست‌کاری Auth — **VERIFIED** |
| کلید و کد الگو | گزینه‌های `topersia_mediana_api_key` و `topersia_mediana_pattern_code` — **متن ساده** در `wp_options` (autoload پیش‌فرض) | `class-topersia-mediana-sms.php:6` · `class-topersia-auth.php:382` · `class-topersia-admin.php:534-560` — **VERIFIED** |
| نمایش کلید در پیشخوان | `value="<?php echo esc_attr( get_option( 'topersia_mediana_api_key', '' ) ); ?>"` — مقدار کلید در HTML صفحۀ تنظیمات چاپ می‌شود، بر خلاف قاعدۀ «فقط‌نوشتنی» که خلاصه‌ساز و سرویس‌های هسته رعایت می‌کنند | `includes/class-topersia-admin.php:534` — **VERIFIED** |
| مغایرت با P5-D249 | کلید سرویس بیرونی باید رمزشده، از `topersia_api()` و فقط‌نوشتنی باشد؛ پیامک جزو کاتالوگ سرویس‌های هسته نیست (`topersia_services_catalog()` ← بدون `sms`) | `plugin/topersia-core/includes/topersia-services.php:47-57` — **VERIFIED** |
| خطا | پیام‌ها از `ToPersia_I18n::t()`؛ پاسخ ارائه‌دهنده `errorMessage` را پس از `sanitize_text_field` به کاربر برمی‌گرداند | `class-topersia-mediana-sms.php:31-36` — **VERIFIED** (نشت متن خطای ارائه‌دهنده به کاربر نهایی: شدت پایین) |
| «کد یک‌بارمصرف فاز ۵ → کانال سیستم اعلان» (ARCH-RECONCILIATION §۳، «پیامک») | ارائه‌دهنده فقط OTP می‌فرستد؛ پیامک عمومی/اعلان/رضایت/سقف بسامد ندارد | **VERIFIED** (`send_otp` تنها متد رابط است) |

## ۳ — ذخیره (Saved Places)

| | |
|---|---|
| جدول | `{prefix}topersia_saved`: `id` · `user_id` · `post_id` · `post_type` · `created_at`؛ کلید یکتای `(user_id, post_id)` و `KEY user_id`, `KEY post_id` — `includes/class-topersia-db.php:45-56` — **VERIFIED** |
| ثبت | AJAX `topersia_save_place` / `topersia_remove_saved_place` (فقط واردشده + نانس)، شرایط: پست زنده (`ToPersia_JetEngine::is_live`)، نوع `tp_*`، `post_type` ارسالی با واقعی بخواند — `includes/class-topersia-jetengine.php:20-21,319-349` — **VERIFIED** |
| WPML | هر نسخۀ زبان به شناسۀ زبان پیش‌فرض نگاشت می‌شود (`canonical_id`) ← فارسی/انگلیسی/عربی یک مکان یک ردیف — `:341-347` — **VERIFIED** |
| مهاجرت | از متای قدیمی `_topersia_saved_entities` یک‌بار به جدول (`class-topersia-db.php:251-269`، علامت `_topersia_saved_wpml_normalized`) — **VERIFIED** |
| ارجاع، نه کپی | فقط `post_id` و `post_type` ذخیره می‌شود؛ نام/تصویر هنگام نمایش از پست می‌آید — **VERIFIED** (ساختار جدول) ← با بند ۱۴ سند AI Core («ذخیره و مجموعه به‌عنوان ارجاع») هم‌خوان است |
| سقف | سقف تعداد ذخیره در کد پیدا نشد؛ در مقابل «دنبال‌کردن» سقف ۵۰۰ دارد — **VERIFIED** (جست‌وجوی `MAX` در `class-topersia-jetengine.php` و `class-topersia-db.php`: صفر) |
| پاکسازی هنگام حذف پست | هیچ قلاب `before_delete_post` برای ردیف‌های ذخیره دیده نشد؛ ردیف یتیم با `is_live` در نمایش حذف می‌شود، در جدول می‌ماند — **INFERRED** از غیبت قلاب (`grep before_delete_post` در user-core: صفر) |

## ۴ — دنبال‌کردن

- متا: `_topersia_followed` (فهرست شناسه‌های زبان پیش‌فرض)، سقف `MAX = 500` — `includes/class-topersia-follow.php:26,29,136` — **VERIFIED**.
- AJAX: `topersia_follow_entity`, `topersia_entity_state` (فقط واردشده) — `:36-37,107,159` — **VERIFIED**.
- خبر هر مورد از این افزونه نمی‌آید؛ فیلتر `topersia_entity_latest_news` را «هستۀ نمایش» پر می‌کند و مدل خبر (`tpc_news`) فعلاً فقط محلی است (`plugin/topersia-render/includes/topersia-g-news.php:3-12`) — **VERIFIED**. یعنی «دنبال‌کردن» امروز **اعلانی نمی‌سازد**؛ فقط فهرست شخصی است.

## ۵ — متای پروفایل

| متا | محتوا | نوشتن | شاهد |
|---|---|---|---|
| `first_name`, `last_name` | نام | AJAX `topersia_save_profile` | `includes/class-topersia-profile.php:9,24-25` |
| `_topersia_mobile` | شماره | ساخت حساب | `class-topersia-auth.php:556` |
| `_topersia_language` | `fa`/`en`/`ar` (سفید‌فهرست) | پروفایل | `class-topersia-profile.php:26` |
| `_topersia_country` | **متن آزاد** (`sanitize_text_field`، بی فهرست مجاز یا کد ISO) | پروفایل | `class-topersia-profile.php:21,27` |
| `_topersia_travel_style` | رشتۀ آزاد | پروفایل | `class-topersia-profile.php:28` |
| `_topersia_display_prefs` | `mode · scale · spacing · measure · contrast · calm · motion` با سفید‌فهرست | AJAX `topersia_save_display_prefs` | `includes/class-topersia-display-prefs.php:35-50` |
| `_topersia_followed` | شناسه‌ها | دنبال‌کردن | بخش ۴ |
| `_topersia_saved_wpml_normalized`, `_topersia_saved_entities`, `_topersia_saved_count` | مهاجرت قدیمی | یک‌بار | `class-topersia-db.php:251-269` |
| `_topersia_trips_count` | **هیچ‌جا نوشته نمی‌شود** | — | `includes/class-topersia-dashboard.php:35-41` («همیشه صفر») |

همه **VERIFIED**. نکات:

- **«کشور کاربر»** (ARCH-RECONCILIATION §۳: «نامعلوم · ۲د») ← **هست**، به شکل متای متن آزاد `_topersia_country`. ولی: (۱) داده را کاربر تایپ می‌کند، نه از ISO؛ (۲) از IP یا ثبت‌نام استنتاج نمی‌شود (ورود فقط ایرانی است)؛ (۳) برای شخصی‌سازی بر پایۀ کشور (بند ۱۹ Master Audit) قابل‌اتکا نیست. گزینۀ تعریف‌شده «اجباری ← ISO ۳۱۶۶ از فهرست» تصمیم معمار است.
- تمام متای کاربر با پیشوند `_` است، پس از `get_user_meta` بدون واسط REST دیده نمی‌شود؛ REST خودِ وردپرس `users` این متاها را عرضه نمی‌کند چون `register_meta` ندارند — **INFERRED** (جست‌وجوی `register_meta`/`register_rest_field` در افزونه: صفر).
- شمارۀ موبایل = دادۀ شخصی؛ در `wp_usermeta` (`_topersia_mobile`) و در جدول `topersia_otp_attempts.mobile` (نگهداری ۲۴ ساعت تا ۷ روز، بسته به `prune`) ذخیره می‌شود. `ip_hash` با HMAC و salt ذخیره است، نه IP خام — **VERIFIED** (`class-topersia-auth.php:157`). `prune()` ردیف‌های تأییدنشدۀ بالای ۲۴ ساعت و تأییدشدۀ بالای ۷ روز را از `topersia_otp_attempts` حذف می‌کند (`class-topersia-db.php:94-107`) — **VERIFIED**؛ ولی به شرط اجرای کرون (بخش ۸).

## ۶ — اعلان

- تب «اعلان‌ها» در داشبورد هست (`includes/class-topersia-dashboard.php:23` · `templates/dashboard.php:220`) ولی `notification_count` ثابت ۰ است (`class-topersia-dashboard.php:124`) و مدل دادۀ اعلان وجود ندارد: نه جدول، نه متا، نه کانال، نه صف — **VERIFIED**.
- `ROADMAP.md` همین را تأیید می‌کند: «اعلان‌ها مسئلۀ جداست… پس از سفر پیاده شود» — **VERIFIED**.
- ایمیل: ممکن نیست (بخش ۱)؛ پیامک: فقط OTP (بخش ۲). یعنی سه کانال (`panel`, `sms`, `email`) از ARCH-RECONCILIATION §۳ همه **از صفر** هستند.

## ۷ — REST و AJAX

افزونه **REST ندارد** (`grep register_rest_route` صفر) — **VERIFIED**. فهرست AJAX:

| کنش | دسترسی | حفاظت | شاهد |
|---|---|---|---|
| `topersia_send_otp` | مهمان + واردشده | نانس `topersia_user_core` + نرخ‌سنجی ۴لایه | `class-topersia-auth.php:56-57` |
| `topersia_verify_otp` | مهمان + واردشده | نانس + سقف حدس | `:58-59` |
| `topersia_logout` | واردشده | نانس | `:60` |
| `topersia_save_profile` | واردشده | نانس | `class-topersia-profile.php:9,14` |
| `topersia_save_display_prefs` | واردشده | نانس + سفید‌فهرست | `class-topersia-display-prefs.php:50` |
| `topersia_save_place` · `topersia_remove_saved_place` | واردشده | نانس + زنده بودن پست | `class-topersia-jetengine.php:20-21,319-349` |
| `topersia_follow_entity` · `topersia_entity_state` | واردشده | نانس | `class-topersia-follow.php:36-37` |
| `topersia_submit_review` | واردشده | نانس + نوع مجاز + یک نظر برای هر کاربر/مکان + `approved=0` | `class-topersia-review-form.php:40,166` |
| `admin_post_topersia_setup_jetreviews` | مدیر | — | `class-topersia-jetreviews-setup.php:46` |

همه **VERIFIED**. نانس مهمان روی صفحۀ کش‌شده (**INFERRED**، ریسک مشترک با AI.md بخش ۵): ورود هم نانس `topersia_user_core` دارد که برای مهمان باید در HTML نشسته باشد؛ TTL کش صفحه از نانس بلندتر باشد ورود شکست می‌خورد. **UNKNOWN**: TTL کش صفحه/نانس روی میزبان زنده.

## ۸ — کرون و جدول‌ها

| | |
|---|---|
| کرون | `topersia_user_core_prune` روزانه، `wp_schedule_event` در `activate()` (`class-topersia-db.php:60-62`) و پاک‌کردن در غیرفعال‌سازی (`:74`)؛ عملیات `ToPersia_DB::prune()` — **VERIFIED** |
| `DISABLE_WP_CRON` روی زنده | هسته می‌گوید روشن است (`plugin/topersia-core/includes/topersia-queue.php:14-16`) ← اگر کرون سیستمی تنظیم نشده باشد، `prune` اجرا نمی‌شود؛ **UNKNOWN** (فاز ۱۰) |
| جدول‌ها | `topersia_otp_attempts` · `topersia_saved` — هر دو در `uninstall.php:37-38` حذف می‌شوند — **VERIFIED** (برخلاف خلاصه‌ساز که جدولش را نمی‌افتد) |
| ارتقا ساختار | `maybe_upgrade()` در `plugins_loaded` با `topersia_user_core_db_version` — **VERIFIED** (`topersia-user-core.php:76`، `class-topersia-db.php:81`) |

## ۹ — شکاف با مجموعه، سفر و اعلان

| قابلیت (سند) | امروز | شکاف |
|---|---|---|
| ذخیره‌شده‌ها | هست، جدول، WPML-aware | مجموعه (Collection) روی آن ندارد |
| **مجموعه** (AI Core §۲۶، ARCH #۱۴: «گسترش ذخیره‌شده‌ها، نه سیستم دوم») | نیست — **VERIFIED** (نه جدول، نه متا، نه UI) | جدول `collections` + `collection_items` (ارجاع به `topersia_saved` یا مستقیم به `post_id`) |
| **سفر** (AI Core §۱۹–۲۵، P5-D299: جدول نسخه‌دار) | فقط تب «سفرهای من» تهی و شمارندۀ ۰ — **VERIFIED**؛ `ROADMAP.md` فاز ۲ سه گزینۀ مدل دادۀ سفر را باز گذاشته (نوع‌پست `tp_trip` · جدول `topersia_trips` · CCT) و «پرسش باز: برنامۀ کاربرساخته یا سابقۀ انجام‌شده؟» | **تصمیم P5-D299 با ROADMAP کاربر-core ناهم‌خوان است**: ROADMAP نامزد اول را `tp_trip` گذاشته (نوع‌پست) در حالی که ARCH-RECONCILIATION جدول نسخه‌دار را «پیشنهادی» می‌گذارد؛ هر دو منتظر ممیزی NotebookLM‌اند |
| **اعلان** | تب خالی، بی مدل (بخش ۶) | کامل از صفر؛ نیازمند رویدادی که اعلام شود (سفر/پاسخ نقد/تغییر رویداد) |
| **پروفایل علاقه** (P5-D258) | فقط `_topersia_travel_style` رشتۀ آزاد | علاقه‌مندی ساخت‌یافته نیست |
| ورود غیرایرانی (P5-D257) | نیست (بخش ۱) | OAuth/ایمیل |
| ویرایش دستی سفر بی هوش مصنوعی (ARCH #۱۲) | نیست | وابسته به مدل سفر |
| دامنۀ مالکیت (ARCH §۵: user-core مالک «ورود، پروفایل، سبک سفر، ذخیره، مجموعه، سفر، اعلان») | هم‌خوان برای چهار مورد نخست؛ سه مورد دیگر خالی | — |

## ۱۰ — بازبینی تعامل با افزونه‌های دیگر

- user-core از `ToPersia_JetEngine::is_live()` برای P5-D247 استفاده می‌کند (`class-topersia-jetengine.php:88` تعریف، `:329` کاربرد) — **VERIFIED**؛ ولی منطقی جدا از `topersia_g_published()` رندر دارد (دو پیاده‌سازی «منتشرشده بودن») — **VERIFIED** (مقایسۀ دو تابع؛ آزمون `assert-uc.php` مدعی پوشش است). خطر انحراف دو قاعده در WPML (نسخۀ منتشرشدۀ زبان جاری) **INFERRED**.
- نظرها: JetReviews در جدول خودش (`jet_reviews`) می‌نویسد؛ خواندن مستقیم `SELECT … FROM {prefix}jet_reviews` برای «کاربر نظر دارد؟» (`includes/class-topersia-review-form.php:67-73`)؛ کامنت نام کاملِ جدول شامل **پیشوند واقعی پایگاه‌داده** است (`includes/class-topersia-reviews.php:38,48`) — **VERIFIED**. مخزن خصوصی است (P5-D284)، اما پیشوند جدول نباید در متن مستند بماند؛ شدت پایین. (پیشوند را اینجا تکرار نمی‌کنم.)
- قاعدۀ مالکیت دامنه (ARCH §۵) می‌گوید «هیچ افزونه جدول دیگری را مستقیم نمی‌نویسد»: نوشتن نظر از API خود JetReviews است (رعایت)، خواندن مستقیم SQL از جدول JetReviews هست (خواندن مجاز، ولی وابستگی به ساختار داخلی افزونۀ ثالث) — **VERIFIED**.

## ۱۱ — حکم ARCH-RECONCILIATION در برابر شواهد

| بند | هم‌خوانی |
|---|---|
| §۳ «کشور کاربر: نامعلوم» | **اصلاح لازم**: کشور هست (متن آزاد)؛ مسئله کیفیت داده است نه وجود |
| §۳ «پیامک: کد یک‌بارمصرف فاز ۵ → فقط یک کانال سیستم اعلان» | **ناهم‌خوان در کد**: OTP امروز یک مسیر مستقل و سخت‌کد در `Auth` است؛ تبدیل به «کانال» نیازمند انتزاع ارائه‌دهنده است |
| #۱۴ «مجموعه در user-core» | هم‌خوان، ولی کد ندارد |
| #۱۲/#۱۳ «سفر دادۀ کاربر؛ جدول نسخه‌دار» | با ROADMAP محلی user-core هم‌خوان نیست (نوع‌پست نامزد اول) ← باید ROADMAP هم‌تراز شود (کار جلسۀ ۲) |
| §۱ «کلید فقط سمت سرور» (قاعدۀ ۴) | **نقض کلید پیامک**: متن‌ساده، و در HTML پیشخوان چاپ می‌شود (بخش ۲) |

## ۱۲ — UNKNOWN و فرمان محلی

| # | موضوع | فرمان |
|---|---|---|
| ۱ | شمار کاربران، تفکیک نقش، شمار `_topersia_mobile`، `_topersia_country` پر | `wp user list --role=subscriber --format=count` و `wp db query "SELECT COUNT(*) FROM {prefix}usermeta WHERE meta_key='_topersia_country' AND meta_value<>''"` |
| ۲ | توزیع مقادیر `_topersia_country` (برای ارزیابی نیاز به ISO) | `wp db query "SELECT meta_value, COUNT(*) c FROM {prefix}usermeta WHERE meta_key='_topersia_country' GROUP BY meta_value ORDER BY c DESC LIMIT 30"` (مقدار کشور داده شخصی نیست؛ شمارش گروهی) |
| ۳ | شمار ردیف‌های `topersia_saved` و `topersia_otp_attempts` | `wp db query "SELECT COUNT(*) FROM {prefix}topersia_saved"` و ‌… `otp_attempts` |
| ۴ | تعریف `TOPERSIA_TRUST_PROXY` روی زنده و سرآیند IP واقعی | `grep -c TOPERSIA_TRUST_PROXY wp-config.php` و `curl -sI` با نگاه به هدرهای فوروارد |
| ۵ | تعریف کلید Mediana (هست/نیست، نه مقدار) | `wp option get topersia_mediana_api_key \| wc -c` (فقط طول) |
| ۶ | `mail()` و کرون روی میزبان زنده | `php -r` و تنظیم `DISABLE_WP_CRON`/cron سیستمی میزبان |
| ۷ | اجرای آزمون‌های افزونه | `php tests/assert-uc.php` — در این جلسه با گیت اجرا می‌شود (GATES.md) |
