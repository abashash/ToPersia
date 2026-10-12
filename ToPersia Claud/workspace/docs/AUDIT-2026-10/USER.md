# USER.md — ممیزی افزونۀ کاربر (`topersia-user-core` ‏1.10.4)

> فاز ۲د · ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ — **فقط‌خواندنی**، بی پایگاه‌داده.
> برچسب‌ها: `VERIFIED` (مسیر و خط) · `INFERRED` · `ASSUMED` · `UNKNOWN` (فرمان محلی کنارش).
> نام‌اختصار: `UC` = `plugin/topersia-user-core` · `AI` = `plugin/topersia-ai-summarizer` · `CORE` = `plugin/topersia-core` · `RENDER` = `plugin/topersia-render` · `DOC` = `docs` · `WS` = ریشۀ workspace.
> **هیچ شماره موبایل، نام کاربری، کلید یا دادۀ کاربر در این پرونده نیست**؛ فقط نام و شمار.

---

## ۱ — خلاصۀ حکم

| پرسش | پاسخ | برچسب |
|---|---|---|
| افزونه چه می‌دهد؟ | ورود/ثبت‌نام با کد یک‌بارمصرف پیامکی، پروفایل، مکان‌های ذخیره‌شده، دنبال‌کردن موجودیت، ثبت نظر (در انتظار تأیید)، ترجیح‌های نمایش، پنل کاربر (۸ تب) | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:15-25` |
| PHP لازم | ۸٫۰ (بالاتر از ۷٫۴ بقیۀ افزونه‌ها)؛ ملاحظۀ فاز ۱۰ | `[VERIFIED]` `UC/topersia-user-core.php:8` |
| مجموعه (Collections)، سفر، اعلان، کشور از سیستم | **ساخته نشده‌اند**؛ تب‌های «سفرهای من» و «اعلان‌ها» فقط پوستۀ خالی‌اند | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:35-41,124` |
| بزرگ‌ترین شکاف امنیتی | کلید پیامک متن ساده در option و در HTML صفحۀ تنظیمات؛ خارج از لایۀ «سرویس‌های خارجی» | `[VERIFIED]` `UC/includes/class-topersia-admin.php:529-534` · `UC/includes/class-topersia-mediana-sms.php:6` |

---

## ۲ — جریان ورود (OTP)

| مرحله | یافته | شاهد | برچسب |
|---|---|---|---|
| نقاط AJAX | `topersia_send_otp` و `topersia_verify_otp` با `wp_ajax_nopriv_` و `wp_ajax_`؛ `topersia_logout` فقط واردشده | `UC/includes/class-topersia-auth.php:56-60` | `[VERIFIED]` |
| نانس | `topersia_user_core`؛ در JS محلی‌سازی‌شده برای همۀ بازدیدکنندگان صادر می‌شود (برای مهمان نانس uid=0 است، پس سپر ضد‌ربات نیست، فقط ضد‌CSRF) | `UC/includes/class-topersia-auth.php:120-123` · `UC/includes/class-topersia-assets.php:49` | `[VERIFIED]` |
| تولید کد | ۶ رقم با `wp_rand(100000, 999999)`؛ هش با `wp_hash_password`؛ توکن ۳۲ نویسه؛ ماندگاری ۳۰۰ ثانیه در ترنزینت `tp_otp_{token}` | `UC/includes/class-topersia-auth.php:377-408` · `UC/includes/class-topersia-auth.php:47` | `[VERIFIED]` |
| سقف‌ها | هر شماره ۵ در ساعت · هر IP ۱۰ در ساعت · هر IP حداکثر ۳ شمارۀ متفاوت · کل سایت ۱۲۰ پیامک در ساعت · سردشدن ۴۵ ثانیه · حداکثر ۵ حدس برای هر کد | `UC/includes/class-topersia-auth.php:29-47` | `[VERIFIED]` |
| پیاده‌سازی سقف | با ترنزینت (خواندن‌افزایش‌نوشتن غیراتمی)؛ در شکست پیامک برگشت می‌خورد (`refund_rate_limit`) | `UC/includes/class-topersia-auth.php:169-305` | `[VERIFIED]` |
| IP | `REMOTE_ADDR`؛ `X-Forwarded-For` فقط با ثابت `TOPERSIA_TRUST_PROXY`؛ ذخیره‌شده به‌صورت `HMAC-SHA256` با نمک `auth` | `UC/includes/class-topersia-auth.php:145-157` | `[VERIFIED]` |
| سقف کل سایت (۱۲۰/ساعت) | یک مهاجم با چند IP می‌تواند ظرفیت ورود کل سایت را پر کند (DoS ورود) یا هزینۀ پیامک بسازد | استنتاج از سقف‌ها | `[INFERRED]` |
| پشتیبان پیامک | یک کلاس `ToPersia_Mediana_SMS` پشت رابط `ToPersia_SMS_Provider` (یک متد `send_otp`)؛ **ساخت کلاس سخت‌کد** است، نه کارخانه/فیلتر | `UC/includes/class-topersia-sms-provider.php:4-7` · `UC/includes/class-topersia-auth.php:381` | `[VERIFIED]` |
| ارائه‌دهندۀ پیامک | Mediana، نقطۀ `https://api.mediana.ir/sms/v1/send/otp` با کد الگو (`patternCode`)، مهلت ۱۵ ثانیه | `UC/includes/class-topersia-mediana-sms.php:11-22` | `[VERIFIED]` |
| ذخیرۀ کلید پیامک | option `topersia_mediana_api_key` و `topersia_mediana_pattern_code`، متن ساده، autoload پیش‌فرض (ثبت با `register_setting`) | `UC/includes/class-topersia-mediana-sms.php:6` · `UC/includes/class-topersia-auth.php:382` · `UC/includes/class-topersia-admin.php:245-263` | `[VERIFIED]` |
| **تناقض در صفحۀ تنظیمات** | متن راهنما می‌گوید «کلید فقط روی سرور استفاده می‌شود و هرگز به مرورگر نمی‌رود» ولی فیلد `<input type=password value="…کلید…">` مقدار را در HTML صفحه چاپ می‌کند | `UC/includes/class-topersia-admin.php:529,534` | `[VERIFIED]` |
| خارج از لایۀ سرویس‌ها | پیامک در کاتالوگ `topersia_api()` نیست؛ P5-D249 (رمز sodium + ثابت wp-config + آزمون اتصال + سقف) شامل آن نمی‌شود | `CORE/includes/topersia-services.php:53-65` (کاتالوگ: ۹ سرویس، بی پیامک) | `[VERIFIED]` |
| ساخت حساب | نام کاربری `tp_{شماره}`؛ ایمیل `{شماره}@users.topersia.com` (دامنۀ ساختگی با صافی `topersia_user_email_domain`)؛ نقش `subscriber`؛ رمز تصادفی ۳۲ نویسه | `UC/includes/class-topersia-auth.php:529-559` | `[VERIFIED]` |
| پیامد | شمارۀ موبایل خودِ `user_login` و بخشی از ایمیل است؛ به همین دلیل در هر خروجی وردپرس که نام ورود را بیاورد (نمایش مدیر، لاگ، `display_name` پیش‌فرض، نامک نویسنده) می‌نشیند | `UC/includes/class-topersia-auth.php:531,543` | `[VERIFIED]` (ساخت) · پراکندگی `[INFERRED]` |
| **شمارۀ موبایل به‌عنوان نام نمایشی عمومی** | `wp_create_user` فقط نام ورود/رمز/ایمیل می‌گیرد؛ وردپرس در نبود نام و نام‌خانوادگی `display_name` را برابر `user_login` (یعنی `tp_{شماره}`) می‌گذارد. بلوک عمومی نظرهای صفحۀ موجودیت نام نویسنده را از `display_name` می‌خواند و چاپ می‌کند؛ فرم ثبت نظر نام نمی‌خواهد. پس نظر تأییدشدۀ کاربری که پروفایلش را پر نکرده شمارۀ او را علنی نشان می‌دهد | `UC/includes/class-topersia-auth.php:545` · `WP/wp-includes/user.php:2470-2483` · `RENDER/includes/topersia-g-zones.php:4957-4966` · `UC/includes/class-topersia-reviews.php:70-74` | `[VERIFIED]` (مسیر کد) |
| وقوع واقعی آن روی داده | — | — | `[UNKNOWN]` محلی: `wp user list --field=display_name \| grep -c '^tp_[0-9]'` (فقط شمار) و `wp db query "SELECT COUNT(*) FROM $(wp db prefix)jet_reviews r JOIN $(wp db prefix)users u ON u.ID=CAST(r.author AS UNSIGNED) WHERE r.approved>0 AND u.display_name=u.user_login"` |
| مسدود کردن شمارش کاربران | `/wp/v2/users` برای مهمان حذف و `?author=` ریدایرکت می‌شود؛ ولی مسیر `/author/{nicename}/` در این بررسی نیست | `CORE/topersia-core.php:4410-4437` | `[VERIFIED]` (کد) · رفتار واقعی `[UNKNOWN]` محلی: `curl -sI "http://topersia.local/author/tp_test_430/" \| head -3` (حساب آزمون، نه کاربر واقعی) |
| حساب‌های دارای دسترسی | ورود با OTP برای کسی که `manage_options` · `edit_posts` · `edit_pages` · `upload_files` دارد رد می‌شود (دو بار: ارسال و تأیید) | `UC/includes/class-topersia-auth.php:331-339,362,479` | `[VERIFIED]` |
| ایمیل سامانه | `mail()` روی این هاست غیرفعال است؛ اعلان ایمیلی نیست | `UC/includes/class-topersia-review-form.php:20` | `[VERIFIED]` (کامنت) · `[UNKNOWN]` محلی/زنده: `php -r 'var_dump(ini_get("disable_functions"));'` روی هاست زنده |

---

## ۳ — محل ذخیره و مدل داده

| داده | محل | شاهد | برچسب |
|---|---|---|---|
| تلاش‌های OTP | جدول `{prefix}topersia_otp_attempts`: `mobile`، `purpose`، `attempts`، `verified`، `request_id`، `ip_hash`، `expires_at`؛ هرس روزانه (تأییدنشده ۱ روز، تأییدشده ۷ روز) با کرون `topersia_user_core_prune` | `UC/includes/class-topersia-db.php:27-41,60-61,94-107` | `[VERIFIED]` |
| ذخیره‌ها | جدول `{prefix}topersia_saved`: `user_id`، `post_id` (فقط شناسۀ زبان پیش‌فرض)، `post_type`، کلید یکتای `(user_id, post_id)`؛ مهاجرت تنبل از متای قدیمی | `UC/includes/class-topersia-db.php:45-56` · `UC/includes/class-topersia-jetengine.php:162-254,344-347` | `[VERIFIED]` |
| دنبال‌کردن | **متای کاربر** `_topersia_followed` (آرایۀ شناسه‌ها، سقف ۵۰۰) — در جدول نیست | `UC/includes/class-topersia-follow.php:26,29,144` | `[VERIFIED]` |
| پروفایل | متاهای `first_name`، `last_name`، `_topersia_language` (فهرست سفید `fa`/`en`/`ar`)، `_topersia_country`، `_topersia_travel_style`، `_topersia_mobile` | `UC/includes/class-topersia-profile.php:24-28` · `UC/includes/class-topersia-auth.php:556` | `[VERIFIED]` |
| ترجیح‌های نمایش | متای `_topersia_display_prefs` | `UC/includes/class-topersia-display-prefs.php:35,166` | `[VERIFIED]` |
| نظرها | جدول JetReviews (`jet_reviews`) با `approved = 0`؛ مدیر در JetReviews تأیید می‌کند | `UC/includes/class-topersia-review-form.php:161` | `[VERIFIED]` |
| استفادۀ AI هر کاربر | متای `_topersia_ai_daily` را `topersia-render` می‌نویسد (نه user-core) | `RENDER/includes/topersia-g-ai.php:88,106` | `[VERIFIED]` |
| مرز مالکیت (P5-D289) | `topersia-render` مستقیم متای کاربر را می‌نویسد و `TP_AI_Summary` را مستقیم صدا می‌زند؛ P5-D289 می‌خواهد «فقط از تابع عمومی یا REST مالک» | `RENDER/includes/topersia-g-ai.php:106` · `RENDER/includes/topersia-g-zones.php:4568-4576` | `[VERIFIED]` |
| پیشوند جدول | پیشوند واقعی پایگاه‌داده در یک توضیح کد آمده است (مقدار نوشته نمی‌شود) | `UC/uninstall.php:35` | `[VERIFIED]` |
| uninstall | دو جدول (`otp_attempts`، `saved`) و شش option را پاک می‌کند؛ مسیرش درست | `UC/uninstall.php:36-57` | `[VERIFIED]` |
| شمار کاربران، شمار ردیف‌های ذخیره/دنبال، حجم جدول OTP | — | — | `[UNKNOWN]` محلی: `wp user list --role=subscriber --format=count` · `wp db query "SELECT COUNT(*) FROM $(wp db prefix)topersia_saved"` · `wp db query "SELECT COUNT(*) FROM $(wp db prefix)topersia_otp_attempts"` |
| شمار کاربرانی که `_topersia_followed` دارند و میانگین طول | — | — | `[UNKNOWN]` محلی: `wp db query "SELECT COUNT(*) FROM $(wp db prefix)usermeta WHERE meta_key='_topersia_followed'"` |

---

## ۴ — کشور کاربر

| پرسش | یافته | شاهد | برچسب |
|---|---|---|---|
| کشور از کجا می‌آید؟ | **فقط ورودی آزاد کاربر** (`<input name="country">`) → `sanitize_text_field` → متای `_topersia_country`؛ بی فهرست ISO، بی تشخیص خودکار | `UC/templates/dashboard.php:81` · `UC/includes/class-topersia-profile.php:21,27` | `[VERIFIED]` |
| تشخیص بر پایۀ IP/سرآیند (GeoIP، `CF-IPCountry`، `Accept-Language`) | **در هیچ‌یک از ۵ افزونه نیست** | جستجوی `geoip\|IPCOUNTRY\|maxmind\|ACCEPT_LANGUAGE` بی نتیجه | `[VERIFIED]` (نبود) |
| حکم Master Audit بند ۱۹ (کشور کاربر) | امروز **ناکافی برای شخصی‌سازی ماشینی** است (متن آزاد، بی کد ISO). برای پیامک/فرصت سفر (P5-D297) باید به کد ISO-3166 عادی‌سازی شود | `REF/ToPersia_Master_Architecture_Audit_and_Expansion.md` بخش ۱۹ | `[INFERRED]` |
| میزان پر بودن و مقادیر متنوع | — | — | `[UNKNOWN]` محلی: `wp db query "SELECT meta_value, COUNT(*) c FROM $(wp db prefix)usermeta WHERE meta_key='_topersia_country' GROUP BY 1 ORDER BY c DESC LIMIT 30"` (فقط شمار؛ بی شناسه) |

---

## ۵ — REST و AJAX

| نقطه | ورود لازم | شاهد | برچسب |
|---|---|---|---|
| REST | **هیچ مسیر RESTی در user-core نیست** | جستجوی `register_rest_route` در `UC/` بی نتیجه | `[VERIFIED]` |
| `topersia_send_otp` · `topersia_verify_otp` | نه (مهمان) | `UC/includes/class-topersia-auth.php:56-59` | `[VERIFIED]` |
| `topersia_logout` | بله | `UC/includes/class-topersia-auth.php:60` | `[VERIFIED]` |
| `topersia_save_place` · `topersia_remove_saved_place` | بله + نانس | `UC/includes/class-topersia-jetengine.php:20-21,319-322` | `[VERIFIED]` |
| `topersia_follow_entity` · `topersia_entity_state` | بله + نانس | `UC/includes/class-topersia-follow.php:36-37,93-100` | `[VERIFIED]` |
| `topersia_submit_review` | بله | `UC/includes/class-topersia-review-form.php:40` | `[VERIFIED]` |
| `topersia_save_profile` · `topersia_save_display_prefs` | بله + نانس | `UC/includes/class-topersia-profile.php:9-15` · `UC/includes/class-topersia-display-prefs.php:50` | `[VERIFIED]` |
| `admin_post_topersia_setup_jetreviews` | مدیر | `UC/includes/class-topersia-jetreviews-setup.php:46` | `[VERIFIED]` |
| کرون | `topersia_user_core_prune` (روزانه) | `UC/includes/class-topersia-db.php:60-61` | `[VERIFIED]` |

---

## ۶ — شکاف با مجموعه، سفر، اعلان

| قابلیت (سند) | امروز | شکاف | برچسب |
|---|---|---|---|
| ذخیرۀ مکان (AI Core §27) | هست؛ جدول، ارجاع به موجودیت (بی کپی)، کلید زبان پیش‌فرض | حکم #14 («ارجاع، بی کپی») **با کد می‌خواند** و ✓ | `[VERIFIED]` |
| مجموعه (AI Core §26؛ حکم #14: گسترش همین، نه سیستم دوم) | **نیست** (نه جدول، نه تب) | جدول `collections` + `collection_items` (ارجاع به `post_id` زبان پیش‌فرض) و تب | `[VERIFIED]` (نبود) |
| سفر با نسخه و آیتم (§19–25؛ P5-D299) | تب «سفرهای من» + شمارندۀ `_topersia_trips_count` که هیچ‌جا نوشته نمی‌شود (همیشه صفر) | سه جدول؛ مالکیت user-core (حکم #12) | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:35-41` |
| اعلان (Master Audit §25، P5-D297) | تب «اعلان‌ها» و `notification_count = 0` ثابت؛ ایمیل غیرفعال؛ فقط کانال پیامکِ OTP | سیستم اعلان، رضایت، سقف بسامد | `[VERIFIED]` `UC/includes/class-topersia-dashboard.php:124` |
| دنبال‌کردن (خبر/موجودیت) | هست، ولی در **متای کاربر** نه جدول؛ سقف ۵۰۰؛ بدون زمان‌بندی/رویداد | برای «فرصت سفر» و اعلان باید جدول شود (پرس‌وجوی معکوس «چه کسی این موجودیت را دنبال می‌کند» روی متای سریال‌شده ممکن نیست) | `[VERIFIED]` (ذخیره) · `[INFERRED]` (پیامد) |
| تقویم کاربر (P5-D303/D295) | **نیست** | وابسته به سیستم رویداد | `[VERIFIED]` (نبود) |
| پروفایل علاقه (P5-D258) | فقط `travel_style` (متن آزاد) | فهرست علاقه با نگاشت به نوع پست | `[VERIFIED]` `UC/includes/class-topersia-profile.php:22,28` |
| سه‌زبانگی | نسخۀ `fa/en/ar` با کلاس `ToPersia_I18n` و اثرانگشت رشته‌ها | هدف ۹ زبان؛ فقط ۳ | `[VERIFIED]` `UC/includes/class-topersia-i18n.php:171` · `UC/includes/class-topersia-profile.php:26` |
| مستندات داخلی | `ARCHITECTURE.md` هنوز می‌گوید ذخیره‌ها در `_topersia_saved_entities` و «نیازمند مهاجرت»؛ کد از ۱٫۰ به جدول رفته | مستند کهنه | `[VERIFIED]` `UC/ARCHITECTURE.md` (بخش «داده») در برابر `UC/includes/class-topersia-db.php:10-19` |

---

## ۷ — خطر و پیشنهاد (فقط ثبت؛ اجرا نشده)

| # | موضوع | پیشنهاد | برچسب |
|---|---|---|---|
| U1 | کلید پیامک متن ساده + چاپ در HTML | ثبت `sms.otp` در کاتالوگ سرویس‌ها، کلید با sodium یا ثابت wp-config، فیلد «فقط‌نوشتنی» | `[INFERRED]` |
| U2 | سقف‌های OTP با ترنزینت | پس از ورود Redis/object-cache یا جدول شمارنده اتمی؛ تا آن موقع پذیرفته | `[ASSUMED]` |
| U3 | شمارۀ موبایل = `user_login` | تغییر قالب نام ورود (مثلاً شناسۀ تصادفی) برای حساب‌های تازه؛ حساب‌های موجود مهاجرت جدا | `[INFERRED]` |
| U4 | دنبال‌شده‌ها در متا | جدول `topersia_follow(user_id, entity_uid/post_id, created_at)` پیش از اعلان | `[INFERRED]` |
| U5 | PHP 8.0 | تأیید نسخۀ PHP هاست زنده پیش از انتشار فاز ۵ | `[UNKNOWN]` محلی/زنده: `php -v` روی هاست زنده و `wp eval 'echo PHP_VERSION;'` |
