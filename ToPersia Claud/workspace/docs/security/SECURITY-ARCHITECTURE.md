# معماری امنیتی ToPersia — نقشهٔ سطح حمله (۲۰ نوامبر ۲۰۲۶)

> خروجی بخش‌های ۱ تا ۴ پرامپت ممیزی امنیتی (کشف). فقط خواندن؛ هیچ کد یا داده‌ای عوض نشد.
> یافته‌ها و وضعیت آزمون‌ها در `SECURITY-AUDIT-REPORT.md` همین پوشه است.
> در این سند هیچ کلید، گذرواژه، توکن، مشخصات پایگاه‌داده، شناسهٔ نشست، نام کاربری مدیر یا دادهٔ شخصی نیامده است.

## ۱. محیط و خط مبنا

| | مقدار |
|---|---|
| مخزن | `C:\wamp64\www\topersia` (هم‌ارز `public_html`) · شاخهٔ `main` · HEAD `567580f5` |
| remote | `github.com/abashash/ToPersia` (خصوصی از ۵ اکتبر) |
| وضعیت کار هنگام ممیزی | ۳۹ پروندهٔ تغییریافته، ۲۴۰ حذف‌شده و ۳۷۶ ردیابی‌نشده — همه از جلسه‌های دیگر؛ دست نخوردند |
| وردپرس | 7.1.2 در `geo/` · `siteurl` `/geo` · `home` ریشه · محیط `local` |
| PHP | 8.3.28 (ZTS، WAMP) · بارگذار SourceGuardian فعال |
| پایگاه‌داده | MariaDB 10.11.19 روی درگاه ۳۳۰۷ (MySQL 8.4.7 روی ۳۳۰۶ برای بازگشت) |
| وب‌سرور | Apache 2.4.65 (Win64) · mod_fcgid · میزبان مجازی `topersia.local` با `Require local` |
| کاربران | ۴ حساب: ۳ مدیر (یکی مدیر آزمون با نشان `_tp_test_account`) و ۱ مشترک · ثبت‌نام عمومی وردپرس بسته · نقش پیش‌فرض subscriber · گذرواژهٔ برنامه برای هیچ کاربری نیست |
| پشتیبان | Duplicator Pro 5.0.3 (`wp-content/duplicator-backups/`، بیرون از git) · `scripts/tp-backup.php` · `docs/ops/BACKUP-RESTORE.md` |
| سایت زنده | پشت صفحهٔ «به‌زودی». در این ممیزی هیچ درخواستی به `topersia.com` نرفت |

## ۲. لایه‌ها و مرز اعتماد

```
بازدیدکننده (بی‌نام) ──► Apache (.htaccess ریشه: /assets ← wp-content · /core ← wp-includes)
                        │
                        ├─► WordPress (geo/) ─► پوستهٔ WoodMart + Elementor/JetEngine/WPML/RankMath
                        │        └─► افزونه‌های ToPersia (render · core · user-core · ai-summarizer · schema · ads)
                        │                 ├─► REST  topersia/v1 · topersia-ads/v1
                        │                 ├─► admin-ajax (wp_ajax_* و wp_ajax_nopriv_*)
                        │                 └─► سرویس‌های بیرونی (فقط سمت سرور، کلید در سرآیند)
                        │
                        └─► ⚠ هر پرونده‌ای زیر ریشهٔ مخزن هم سرو می‌شود: .git/ · ToPersia Claud/ · لاگ‌ها (SEC-01)
```

**مرزهای اعتماد:**

| مرز | چه چیزی از آن می‌گذرد |
|---|---|
| ۱. اینترنت ⇄ Apache | روی نسخهٔ محلی با `Require local` بسته است. سایت زنده سنجیده نشد |
| ۲. بی‌نام ⇄ واردشده | کوکی وردپرس و nonce. ورود با OTP پیامکی یا کد ایمیل (user-core) |
| ۳. مشترک ⇄ مدیر | `current_user_can`. پیشخوان ToPersia زیر منوی `topersia` |
| ۴. وردپرس ⇄ سرویس بیرونی | `topersia_api()`: کلید رمزشده با sodium در گزینه، یا ثابت wp-config. کلید در سرآیند می‌رود |
| ۵. مخزن ⇄ GitHub | گارد pre-commit (`scripts/git-guard`) نصب است (`.git/hooks/pre-commit`) |

## ۳. افزونه‌ها و پوسته‌ها

### کد ToPersia (منبع: `workspace/plugin/`)

| افزونه | نسخه | نقش امنیتی |
|---|---|---|
| topersia-core | 4.14.0 | سرویس‌های خارجی و کلیدها، کش مرکزی، لاگ، بستهٔ محتوا (درون‌ریزی)، Action Scheduler 3.9.3 بسته‌بندی‌شده |
| topersia-render | 4.5.5 | نمایش عمومی، REST هوا، هوا‌کیفیت، مکان و نقطه‌های نقشه |
| topersia-user-core | 1.14.1 | احراز هویت (OTP و کد ایمیل)، نمایه، آواتار، ذخیره و دنبال‌کردن، درخواست داده، نظر |
| topersia-ai-summarizer | 1.5.0 | تولید خلاصه با هوش مصنوعی و ردگیری. کلید موتور در تنظیمات خودش است (SEC-02) |
| topersia-schema | 1.4.1 | JSON-LD |
| topersia-ads | 1.0.0 | جایگاه تبلیغ، شمارش نمایش و کلیک، آمار روزانه |

### شخص ثالث فعال

| افزونه | نسخه |
|---|---|
| Elementor | 4.3.2 |
| Elementor Pro | 4.2.2 |
| JetEngine | 3.8.13.2 |
| JetReviews | 3.1.2.1 |
| **Jet Plugin Pro** (AbzarWP، رمزگذاری SourceGuardian) | 1.6.0 |
| Rank Math | 1.0.279 |
| Rank Math Pro | 3.0.121 |
| WPML | 4.9.7 |
| WPML String Translation | 3.5.4 |
| WPML Media | 3.0.0 |
| WPML SEO | 2.2.4 |
| Duplicator Pro | 5.0.3 |
| Safe SVG | 2.5.1 |
| Classic Widgets | 0.3 |
| WoodMart Core | 1.1.8 |

- **پوسته:** WoodMart 8.5.7 فعال است و Hello Elementor 3.5.1 غیرفعال.
- **غیرفعال ولی نصب‌شده:** `abzarwp-auto-update` 1.3.21 (رمزگذاری SourceGuardian، با ماژول‌های «LoginWithoutPassword») · Akismet 5.7.2.
- **mu-plugin:**
  - `tp-local-http-probe` (لاگ فراخوانی بیرونی)
  - `tp-local-debug`
  - `tp-local-livelike`
  - `tp-local-safety`
  - `wp-toolkit` 6.11.2 (همراه موتور Patchstack)

## ۴. نقطه‌های ورودی

### REST

| مسیر | روش | مجوز | پرونده |
|---|---|---|---|
| `topersia/v1/weather` | GET | عمومی | `topersia-render/includes/topersia-g-weather.php:1006` |
| `topersia/v1/air` | GET | عمومی | `topersia-render/includes/topersia-g-air.php:458` |
| `topersia/v1/places` | GET | عمومی | `topersia-render/includes/topersia-g-loc.php:231` |
| `topersia/v1/map-points` | GET | عمومی | `topersia-render/includes/topersia-g-mappoints.php:197` |
| `topersia/v1/uc-nonce` | GET | عمومی؛ nonce کاربر جاری، بی کش | `topersia-user-core/includes/class-topersia-auth.php:78` |
| `topersia-ads/v1/imp` | POST | عمومی؛ توکن روزانه و سقف نرخ | `topersia-ads/includes/tp-ads-track.php:177` |

`/wp-json/wp/v2/users` روی نسخهٔ محلی ۴۰۴ می‌دهد (بسته است). `/wp-json/` فهرست مسیرها را می‌دهد.

### admin-ajax — بی‌نام (`nopriv`)

| اقدام | افزونه | سطح سوءاستفاده |
|---|---|---|
| `topersia_send_otp` · `topersia_verify_otp` | user-core | هزینهٔ پیامک، حدس کد (جدول `topersia_otp_attempts`) |
| `topersia_email_code_send` · `topersia_email_code_verify` | user-core | هزینه و اسپم ایمیل، حدس کد |
| `tpas_ai_generate` | ai-summarizer / render | هزینهٔ هوش مصنوعی. بنا بر تصمیم کاربر برای مهمان صفر است و آزمون `user-flows.js` همین را می‌سنجد |
| `tpas_track` | ai-summarizer | دستکاری آمار |
| شناسهٔ async در Action Scheduler | core/vendor | استاندارد کتابخانه |

### admin-ajax — واردشده

- **مشترک:**
  - `topersia_logout`
  - `topersia_avatar_upload` و `topersia_avatar_delete` (بارگذاری پرونده)
  - `topersia_data_request`
  - `topersia_save_display_prefs`
  - `topersia_follow_entity` و `topersia_entity_state`
  - `topersia_save_place` و `topersia_remove_saved_place`
  - `topersia_save_profile`
  - `topersia_submit_review`
- **مدیر:**
  - `topersia_pack_plan`، `topersia_pack_run` و `topersia_pack_undo` (درون‌ریزی محتوا)
  - `topersia_service_test`
  - `topersia_log_js`
  - `tpas_test_key` و `tpas_admin_generate`
  - `admin_post_topersia_setup_jetreviews`

در هر پروندهٔ نقطه‌ورود این ممیزی فقط حضور nonce و بررسی توانایی را شمرد. پرونده‌های REST عمومی nonce ندارند، چون عمداً عمومی‌اند. سنجش منطق هر handler در دامنهٔ بخش‌های ۵ به بعد پرامپت است و اجرا نشد.

## ۵. داده

| دسته | جدول‌ها |
|---|---|
| هستهٔ وردپرس | ۱۲ جدول استاندارد |
| ToPersia | `topersia_ai_analytics` · `topersia_ai_events` · `topersia_ai_prompts` · `topersia_ai_summaries` · `topersia_cache` · `topersia_geo` · `topersia_log` · `topersia_otp_attempts` · `topersia_saved` · `tp_ad_stats` · `tp_ad_stats_month` |
| JetEngine / JetReviews | ۱۱ جدول (از جمله `jet_review_guests` — دادهٔ شخصی مهمان) |
| WPML · Rank Math · Elementor · Duplicator · Action Scheduler | ۲۴ · ۹ · ۴ · ۴ · ۴ |
| یتیم (افزونهٔ حذف‌شده) | `ai_core_prompt_groups` · `ai_core_prompts` · `ai_scribe_conversations` · `article_builder` · `crocoblock_workflows` · سه جدول WoodMart (wishlist و unsubscribed emails) — SEC-08 |

**دادهٔ شخصی:**
- users و usermeta: ایمیل و موبایل
- مهمان‌های JetReviews
- `topersia_otp_attempts`
- درخواست‌های داده (user-core)
- `topersia_ai_events`: رویدادهای ردگیری

**بارگذاری:**
- `wp-content/uploads/` روی نسخهٔ محلی فهرست‌پذیر است (`Options +Indexes`).
- کش twig در WPML پرونده‌های PHP را زیر `uploads/cache/wpml/` می‌نویسد.
- هیچ قاعده‌ای اجرای PHP را در uploads منع نمی‌کند.

## ۶. سرویس‌های بیرونی (سمت سرور)

| سرویس | میزبان | کلید |
|---|---|---|
| هوا | api.met.no · api.open-meteo.com | بی کلید |
| کیفیت هوا | air-quality-api.open-meteo.com | بی کلید |
| نقشه | tiles.openfreemap.org (سبک) · pmtiles محلی | بی کلید |
| مسیریابی و جست‌وجوی نشان | api.neshan.org | کلید — `topersia_api()` |
| تولید متن | generativelanguage.googleapis.com · api.deepseek.com · OpenAI | کلید در تنظیمات خلاصه‌ساز (SEC-02) |
| صدای هوش مصنوعی | Gemini TTS · api.elevenlabs.io | کلید — `topersia_api()`، سرآیند `xi-api-key` |
| پیامک و ایمیل OTP | (از تنظیمات user-core) | سنجیده نشد |

**جریان کلید (P5-D249):**
1. ثابت wp-config اگر تعریف شده باشد برنده است.
2. در غیر این صورت گزینهٔ `autoload=no` خوانده می‌شود: پیشوند `v1:`، با `sodium_crypto_secretbox` و کلید مشتق از `wp_salt('auth')`.
3. سرویس چندآداپتوری شکاف دوم (`#2`) دارد.
4. **استثنا:** خلاصه‌ساز کلید موتور را جدا، به‌صورت متن آشکار، در `topersia_ai_settings` نگه می‌دارد (SEC-02).

## ۷. زمان‌بندی، پس‌زمینه، لاگ

- **cron (WP-Cron):**
  - ToPersia: `topersia_ads_daily` · `topersia_log_prune` · `topersia_user_core_inactivity` · `topersia_user_core_prune`
  - `action_scheduler_run_queue` (هر دقیقه)
  - Duplicator: پاک‌سازی، بررسی ذخیره‌گاه هر ۱۵ دقیقه، telemetry
  - WPML · Rank Math · Elementor tracker
  - **رویدادهای یتیم AbzarWP:** `abzarwp_news_sync_cron` · `abzarwp_daily_license_check` · `abzarwp_grace_period_check` · `abzarwp_news_background_sync`
- **پس‌زمینه:** Action Scheduler (صف واحد هسته، P5-D290).
- **لاگ:**
  - جدول `topersia_log` (کانال‌ها، از جمله `import`)
  - `geo/wp-content/debug.log` (WP_DEBUG روشن، نمایش خاموش). حدود ۴۰ مگابایت است و زیر ریشهٔ وب قرار دارد
  - `http-probe.log` در ریشهٔ مخزن: نشانی کامل هر فراخوانی بیرونی
  - لاگ Duplicator
- **پرداخت و ورود شخص ثالث (OAuth/SSO):** هیچ‌کدام فعال نیست. کتابخانهٔ opauth درون WoodMart Core هست، ولی پیکربندی‌ای برایش دیده نشد.

## ۸. پیکربندی و استقرار

- **`.htaccess` ریشه:**
  - بازنویسی `/assets`، `/core` و نشانی‌های ورود زبانی.
  - تنظیم‌های `php_value` میزبان cPanel، با مسیر نشست سرور زنده (افشای زیرساخت، SEC-06).
  - هیچ سرآیند امنیتی یا قاعدهٔ منع نمی‌گذارد.
- **wp-config محلی:**
  - `DISALLOW_FILE_EDIT` خاموش است.
  - `WP_DEBUG` روشن است و `WP_DEBUG_DISPLAY` خاموش.
  - کاربر پایگاه‌داده root است با گذرواژهٔ خالی (فقط محلی).
- **wp-config زنده:** `project-knowledge/wp-config-additions.txt` نیت آن را `DISALLOW_FILE_EDIT` و `DISALLOW_FILE_MODS` روشن ثبت کرده است.
- **php.ini (Apache):** `expose_php On` و `display_errors On`. وردپرس خودش نمایش خطا را خاموش می‌کند.
- **Apache:** `ServerTokens Full`. سرآیند `Server` نسخهٔ دقیق Apache و PHP را می‌دهد و `X-Powered-By` هم فرستاده می‌شود. `Options +Indexes`.
- **استقرار:**
  - روش مستندی در مخزن نیست.
  - LAUNCH P0-2 می‌گوید پوشهٔ `ToPersia Claud` هرگز در `public_html` زنده بارگذاری نشود. این مورد هنوز «باقی» است.
  - مخزن = `public_html`، پس `.git/` و `ToPersia Claud/` هم بخشی از ریشهٔ وب‌اند (SEC-01).
- **متغیر محیطی:**
  - `TP_GUARD_ADMIN_USER` (گارد git)
  - `TP_TIMEOUT_X`، `TP_CHROME`، `TP_LIGHTHOUSE`، `CHROME_PATH` و `NODE_PATH` (آزمون)
  - هیچ `.env` در مخزن نیست.
