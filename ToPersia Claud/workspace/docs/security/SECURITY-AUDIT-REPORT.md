# گزارش ممیزی امنیتی ToPersia — کشف، اسرار، وابستگی‌ها، تاریخچهٔ Git (۲۰ نوامبر ۲۰۲۶)

## دامنه و قیدها

| بخش پرامپت | اجرا |
|---|---|
| ۱ تا ۴ — زمینه، قواعد، استانداردها، کشف | ✅ — نقشهٔ کامل در `SECURITY-ARCHITECTURE.md` |
| ۷ — اسرار | ✅ |
| ۱۸ — وابستگی‌ها | ✅ فقط ممیزی؛ هیچ به‌روزرسانی یا حذف |
| ۱۹ — تاریخچهٔ Git | ✅ فقط طرح پاک‌سازی؛ بازنویسی تاریخچه نه |
| ۵، ۶ و ۸ تا ۱۷ | اجرا نشد (خواست کاربر) |

**قیدها:**
- هیچ تغییری در کد یا داده. فقط این دو سند ساخته شد (یک استثنا در «یادداشت روش» آمده است).
- هیچ درخواستی به سایت زنده (`topersia.com`) نرفت. همهٔ درخواست‌های HTTP فقط GET بودند و فقط به `topersia.local`.
- هیچ مقدار سرّی در این سند، در ترمینال یا در لاگ چاپ نشد. هر مورد با قاعده، طول و اثرانگشت کوتاه sha256 شناسایی شده است.
- بنا بر قاعدهٔ پروژه push به مخزن خصوصی مجاز است و استقرار ممنوع.

**استاندارد مرجع:** OWASP ASVS 5.0.0 (هدف L2)، فقط در سطح فصل؛ هیچ ادعای انطباقی نمی‌شود. فصل‌های مرتبط با این دامنه:
- V13 Configuration (به‌ویژه مدیریت اسرار)
- V15 Secure Coding & Architecture (وابستگی‌ها)
- V14 Data Protection
- V16 Security Logging & Error Handling
- V4 API · V6 Authentication · V8 Authorization (فقط فهرست سطح حمله)

همچنین WSTG-INFO و WSTG-CONF، و راهنمای امنیت وردپرس.

## خلاصهٔ اجرایی

1. **هیچ سرّ واقعی در git پیدا نشد** — نه در درخت کاری، نه در ۴۴۹ commit همهٔ شاخه‌ها و reflog، نه در شیءهای آویزان.
   - تنها موردهای واقعی در تاریخچه، wp-config نسخهٔ نخست با salt و یک کلید TLS خودامضا است. هر دو فقط در شاخهٔ محلی `backup-current-main` اند و روی هیچ remote نیستند.
   - salt آن wp-config با salt کنونی یکی نیست، یعنی چرخیده است.
2. **خطر اصلی استقرار است، نه کد** (SEC-01):
   - ریشهٔ مخزن همان ریشهٔ وب است.
   - روی نسخهٔ محلی این‌ها با HTTP خوانده می‌شوند: `.git/`، کل `ToPersia Claud/` (سند، اسکریپت، آزمون؛ از جمله `tests/local-login.php` بی گارد CLI)، `debug.log` (حدود ۴۰ مگابایت) و `http-probe.log`.
   - نسخهٔ محلی با `Require local` بسته است. اگر سایت زنده همین‌طور بارگذاری شود، این یافته بحرانی است.
3. **کلید OpenAI خلاصه‌ساز به‌صورت متن آشکار در پایگاه‌داده است** و autoload آن هم خاموش نشده است (SEC-02). پس در هر پشتیبان Duplicator هم هست.
4. **زنجیرهٔ تأمین** (SEC-03):
   - Jet Plugin Pro (فعال) و abzarwp-auto-update (غیرفعال) از فروشندهٔ واسط AbzarWP آمده‌اند و با SourceGuardian رمزگذاری شده‌اند. پس ممیزی‌پذیر نیستند.
   - افزونه‌های پریمیوم از مسیر همان واسط به‌روز شده‌اند.

## جدول یافته‌ها

| شناسه | شدت | عنوان | ASVS | وضعیت |
|---|---|---|---|---|
| SEC-01 | **بالا** (روی زنده اگر همین‌طور بارگذاری شود: **بحرانی**) | مخزن = ریشهٔ وب: `.git/` · `ToPersia Claud/` · لاگ‌ها · فهرست پوشهٔ uploads سرو می‌شوند | V13 · V16 | FAIL (محلی) · NOT TESTED (زنده) |
| SEC-02 | متوسط | کلید موتور هوش مصنوعی خلاصه‌ساز آشکار در `topersia_ai_settings`، با `autoload=auto` | V13 · V14 | FAIL |
| SEC-03 | بالا | کد رمزگذاری‌شدهٔ ممیزی‌ناپذیر از فروشندهٔ واسط · به‌روزرسانی پریمیوم بیرون از فروشندهٔ اصلی | V15 | FAIL |
| SEC-04 | کم | تاریخچه: `debug.log` روی remote · wp-config، salt و کلید TLS در شاخهٔ محلی | V13 · V14 | PARTIAL — طرح پاک‌سازی پایین |
| SEC-05 | کم | پایگاه‌داده محلی: کاربر root با گذرواژهٔ خالی | V13 | PARTIAL (درگاه گوش‌دادن سنجیده نشد) |
| SEC-06 | کم | افشای اطلاعات: `ServerTokens Full`، `X-Powered-By`، `expose_php`؛ بی سرآیند امنیتی؛ `Options +Indexes`؛ مسیر نشست cPanel زنده در `.htaccess` | V13 · V3 | FAIL (محلی) · NOT TESTED (زنده) |
| SEC-07 | کم | شمارش نویسنده: `/?author=1` به نامک نویسنده بازمی‌گردد (مقدار اینجا نیامده) | V8 · V13 | FAIL |
| SEC-08 | اطلاع | جدول‌ها و cron یتیم از افزونه‌های حذف‌شده یا غیرفعال (AI Scribe، AI Core، Article Builder، AbzarWP) | V14 · V15 | FAIL |
| SEC-09 | اطلاع | composer audit: یک advisory بالا (CVE-2026-24765، phpunit) در `composer.lock` موتور Patchstack در wp-toolkit — فقط require-dev و نصب‌نشده | V15 | NOT APPLICABLE (بهره‌برداری‌پذیر نیست) |
| SEC-10 | اطلاع | سطح بی‌نام: ۶ مسیر REST عمومی و ۴ اقدام AJAX بی‌نام (OTP، کد ایمیل، تولید AI، ردگیری) | V4 · V6 | NOT TESTED (بخش ۵ به بعد) |

### SEC-01 — مخزن به‌عنوان ریشهٔ وب

**مدرک:** پاسخ GET روی `http://topersia.local`:

| نشانی | پاسخ |
|---|---|
| `/.git/HEAD` | 200 |
| `/ToPersia%20Claud/workspace/CLAUDE.md` | 200 |
| `/ToPersia%20Claud/workspace/tests/env.json` | 200 |
| `/geo/wp-content/debug.log` | 200، حدود ۴۰ مگابایت |
| `/http-probe.log` | 200، حدود ۴ مگابایت |
| `/geo/wp-content/uploads/` | 200، «Index of» |
| `/geo/wp-config.php` | 200 با بدنهٔ تهی (اجرا شد، نشت نکرد) |

**زمینه:**
- میزبان مجازی `Options +Indexes` و `AllowOverride All` دارد. هیچ `.htaccess` بازدارنده‌ای در `ToPersia Claud/` یا `.git/` نیست.
- `tests/local-login.php` و دیگر یاری‌گرهای `tests/` (`local-ai-reset.php`، `local-ai-used.php`، `local-state.php`، `canary-posts.php`) گارد `PHP_SAPI` ندارند.
- `local-login.php` از راه وب هم اجرا می‌شود. روی نسخهٔ محلی برای مشترک آزمون کوکی ورود تازه می‌سازد. نگهبان آن فقط `wp_get_environment_type()` و میزبان است، نه CLI بودن.

**اثر:**
- محلی: کم، چون Apache با `Require local` فقط از همین ماشین می‌پذیرد.
- زنده، اگر مخزن به همین شکل بارگذاری شود:
  - کل تاریخچهٔ git (و از آن، کد و `debug.log` تاریخچه) دانلودشدنی است.
  - سندهای معماری، پیکربندی و لاگ‌ها خواندنی‌اند.
  - اسکریپت‌های PHP پوشهٔ workspace اجرا می‌شوند. نگهبان «فقط local» بیشترشان را می‌بندد، ولی همه بررسی نشده‌اند.

**یادآوری:** همان LAUNCH P0-2 (`docs/AUDIT-2026-10/LAUNCH.md`) است که هنوز «باقی» است.

**پیشنهاد (اجرا نشد):**
- در زنده فقط `geo/` و پرونده‌های ریشه بارگذاری شوند.
- در محلی، در میزبان مجازی، `Require all denied` برای `ToPersia Claud`، `.git` و `*.log` بیاید، و `Options -Indexes`.
- `WP_DEBUG_LOG` به مسیری بیرون از ریشهٔ وب برود.
- یاری‌گرهای `tests/` و `scripts/` گارد `if ( 'cli' !== PHP_SAPI ) exit;` بگیرند.

### SEC-02 — کلید آشکار خلاصه‌ساز

**مدرک:**
- در گزینهٔ `topersia_ai_settings`، فیلد `openai_api_key` رشته‌ای ۱۶۴ نویسه‌ای با الگوی توکن است، بی پیشوند رمز `v1:`.
- مقدار `autoload` در جدول `auto` است. هدف `TP_AI_Options::no_autoload()` (`class-tp-ai-options.php:156-167`) خاموش‌کردن آن بود و عمل نکرده است.
- ثابت `TOPERSIA_AI_API_KEY` در wp-config محلی تعریف نشده است.
- کلیدهای `topersia_api()` در هسته همه رمزشده‌اند (P5-D249). خلاصه‌ساز از این قاعده بیرون است. P5-D249 خودش بیرون‌گذاشتن `ai.text` از هسته را ثبت کرده است، ولی رمزگذاری را نه.

**در git:** نیست. قاعدهٔ `openai` در هیچ پرونده یا شیئی از تاریخچه یافته‌ای نداد.

**اثر:** هر کس به پایگاه‌داده یا بستهٔ Duplicator دسترسی بیابد کلید را می‌خواند. با autoload، کلید در هر بارگذاری صفحه در حافظه هم هست.

**چرخش کلید: در انتظار (PENDING) — دستی.** ممیزی به پیشخوان OpenAI دسترسی ندارد و ابطال را ادعا نمی‌کند. اگر همین کلید روی زنده یا در پشتیبانی که از این ماشین بیرون رفته است:
1. در پیشخوان OpenAI کلید تازه بسازید.
2. کلید تازه را ترجیحاً به‌صورت ثابت wp-config بگذارید، نه در گزینه.
3. کلید کهنه را ابطال کنید.
4. بسته‌های Duplicator کهنه را جزو رسانهٔ حساس بشمارید.

**پیشنهاد:** خلاصه‌ساز از همان رمزگذاری `topersia_services_encrypt` یا ثابت wp-config بخواند. علت عمل‌نکردن `no_autoload()` هم پیدا شود.

### SEC-03 — زنجیرهٔ تأمین

**مدرک:**
- `jet-plugin-pro` (فعال، نویسنده AbzarWP.Com): ۷۸ ارجاع SourceGuardian دارد.
- `abzarwp-auto-update` (غیرفعال): ۳۸۲ ارجاع دارد، همراه پرونده‌های `LoginWithoutPassword*.php`.
- بارگذار SourceGuardian روی PHP فعال است.
- پوشهٔ `wp-content/abzarwp-backups/` نسخه‌های قبلی Duplicator Pro، Elementor Pro، JetReviews، Rank Math Pro و WPML را از همین مسیر به‌روزرسانی دارد.
- رویدادهای cron AbzarWP هنوز زمان‌بندی‌شده‌اند.

**اثر:** کد اجراشونده‌ای که نه خوانده می‌شود و نه با امضای فروشندهٔ اصلی سنجیده می‌شود. یکپارچگی افزونه‌های پریمیوم را نمی‌شود اثبات کرد.

**پیشنهاد (تصمیم کاربر):**
- نقش `jet-plugin-pro` روشن شود؛ اگر لازم نیست، غیرفعال و حذف.
- `abzarwp-auto-update` حذف شود.
- افزونه‌های پریمیوم از فروشندهٔ اصلی با پروانهٔ معتبر نصب شوند.
- درهم‌سازی پرونده‌ها با بستهٔ رسمی مقایسه شود.

### SEC-04 — تاریخچهٔ Git

به جزئیات بخش ۱۹ پایین نگاه کنید.

### SEC-05 تا SEC-08

- **SEC-05:**
  - کاربر پایگاه‌داده در wp-config محلی ۴ نویسه است (root) و گذرواژه خالی است.
  - پیشنهاد: کاربر جدا با کمینهٔ مجوز و گذرواژه، و اطمینان از `bind-address=127.0.0.1`. این مورد سنجیده نشد.
- **SEC-06:**
  - سرآیند `Server` نسخهٔ دقیق Apache، PHP و mod_fcgid را می‌دهد. `X-Powered-By` هم فرستاده می‌شود.
  - هیچ‌یک از HSTS، CSP، `X-Frame-Options`، `X-Content-Type-Options`، `Referrer-Policy` و `Permissions-Policy` روی پاسخ صفحهٔ اول نیست.
  - `.htaccess` ریشه مسیر نشست PHP سرور cPanel زنده را دارد.
  - سرآیندهای سایت زنده سنجیده نشد (NOT TESTED؛ بازرسی منفعل زنده در دامنهٔ این اجرا نبود).
- **SEC-07:**
  - `/?author=1` با کد 301 به نامک نویسنده می‌رود.
  - `wp/v2/users` بسته است (404) و `xmlrpc.php` هم 404 است.
- **SEC-08:**
  - هشت جدول بی افزونهٔ فعال وجود دارد، از جمله `ai_scribe_conversations` (احتمالاً دادهٔ گفت‌وگو) و `woodmart_unsubscribed_emails` (ایمیل).
  - رویدادهای cron AbzarWP بی handler مانده‌اند.
  - پیشنهاد: پس از پشتیبان، با تصمیم کاربر بازبینی و حذف.

## بخش ۷ — اسرار

### روش

- ابزارهای Gitleaks و TruffleHog نصب نیستند (BLOCKED؛ پایین‌تر).
- به‌جایشان یک اسکنر PHP فقط‌خواندنی به کار رفت (در scratchpad، بیرون از مخزن). مقدار را هرگز چاپ نمی‌کند و فقط قاعده، مسیر، سطر، طول و ۱۰ نویسهٔ اول sha256 را می‌نویسد.
- قاعده‌ها:
  - Google API (`AIza…`) · OpenAI · Anthropic · GitHub · AWS AKID · Slack · Stripe live · Telegram bot
  - JWT · کلید خصوصی PEM · ElevenLabs (`xi-api-key` با مقدار)
  - salt وردپرس · `DB_PASSWORD` · `define( 'TOPERSIA_…_KEY', '…' )`
  - انتساب عمومی (api_key، secret، token، password با مقدار ۲۰ نویسه یا بیشتر)

### پوشش

| هدف | گستره | وضعیت |
|---|---|---|
| درخت کاری (ردیابی‌شده و ردیابی‌نشدهٔ غیرنادیده) | ۳۶٬۶۲۹ پرونده، هر کدام تا ۳ مگابایت | PASS |
| تاریخچه: همهٔ blobهای متنی همهٔ ref‌ها، remoteها و reflog | ۳۲٬۳۰۸ blob، هر کدام زیر ۲ مگابایت؛ دودویی‌ها (تصویر، قلم، zip، mo، pdf) کنار گذاشته شد | PASS |
| شیءهای آویزان (`git fsck`) | ۱۵ blob | PASS |
| لاگ‌ها و پشتیبان‌های نادیده روی دیسک (`debug.log`، `http-probe.log`، بسته‌های Duplicator) | — | NOT TESTED (فقط `debug.log` تاریخچه نمایه شد) |
| پایگاه‌داده: گزینه‌های `topersia*` · `tpas*` · `tp_*` (فقط شکل مقدار) | — | FAIL — SEC-02 |
| source map و خروجی ساخت در کد ToPersia | ساخت یا source map ندارد | NOT APPLICABLE |
| `.env` و نمونهٔ پیکربندی | هیچ `.env` در مخزن · `wp-config-additions.txt` فقط ثابت‌های غیرسرّی دارد · `docs/ops/SECRETS-KEYS.md` بی مقدار | PASS |

### نتیجهٔ دسته‌بندی‌شده

| دسته | یافته | داوری |
|---|---|---|
| کد شخص ثالث (`geo/wp-content/plugins/*`، `wp-includes/pluggable.php`) | سرآیند PEM در phpseclib؛ فهرست salt پیش‌فرض در `pluggable.php`؛ نام فیلد در Elementor و AWS SDK؛ کلید عمومی PostHog در WPML؛ نمونهٔ آزمون opauth | مثبت کاذب یا کلید عمومی‌شدهٔ فروشنده |
| fixture آزمون ToPersia | `tests/assert-55.php` (پروندهٔ ساختگی گارد)؛ `test-12-security-123.php` تاریخچه (مقدار ساختگی از دو تکه، OPS-D012)؛ شیءهای آویزان از `scripts/git-guard-test` (رشتهٔ پرشده با x) | ساختگی |
| `TOPERSIA_OPTION_KEY` | نام گزینه است، نه کلید | مثبت کاذب |
| wp-config نسخهٔ نخست (تاریخچه) | ۸ salt (۶۴ نویسه) · `DB_PASSWORD` خالی | **واقعی ولی چرخیده** — SEC-04 |
| `tests/.lh-snapshots/v4.4.0` و `v4.4.1/tls-key.pem` (تاریخچه) | کلید خصوصی گواهی خودامضای `CN=tp-lh-snapshot` | واقعی، کم‌ارزش (فقط سرور ایستای Lighthouse) — SEC-04 |
| کلید موتور هوش مصنوعی در پایگاه‌داده | یک کلید | **واقعی، بیرون از git** — SEC-02 |

**دادهٔ شخصی در مخزن:**
- شماره‌های موبایل در آزمون‌ها و قالب ورود همه جای‌نگهدارِ پشت‌سرهم‌اند (`0912…01`، `…02`، …).
- ایمیل‌ها نشانی نقش‌محور دامنهٔ پروژه‌اند.
- ایمیل نویسندهٔ commit‌ها دادهٔ شخصی ذاتی git است.

## بخش ۱۸ — وابستگی‌ها (فقط ممیزی)

| ابزار | وضعیت | توضیح |
|---|---|---|
| composer audit | PASS (اجرا شد) | تنها `composer.lock` موجود (`mu-plugins/wp-toolkit/vendor/patchstack/engine-php`) → SEC-09 |
| npm audit | NOT APPLICABLE | هیچ `package-lock.json` در کد ToPersia نیست. وابستگی‌های آزمون از کش `npx` خوانده می‌شوند و بخشی از محصول نیستند |
| Gitleaks · TruffleHog · OSV-Scanner · Semgrep · PHPCS-WPCS · PHPStan | BLOCKED | نصب نیستند. نصب یعنی دانلود و اجرای باینری بیرونی، که بی اجازهٔ صریح کاربر کم‌خطر شمرده نشد. جایگزین برای اسرار: اسکنر بالا |
| WPScan | BLOCKED | نصب نیست و پایگاه آسیب‌پذیری‌اش توکن API می‌خواهد |
| تطبیق نسخهٔ هر افزونه با advisoryهای جاری (Wordfence، Patchstack، WPScan، CISA KEV) | NOT TESTED | بی ابزارها بالا ممکن نشد. فهرست نسخه‌ها در `SECURITY-ARCHITECTURE.md` بخش ۳ آمادهٔ تطبیق است. موتور Patchstack درون wp-toolkit هست، ولی وضعیت فعال‌بودن قواعدش سنجیده نشد |

**یافته‌های دستی:**
- **رمزگذاری‌شده یا ممیزی‌ناپذیر:** Jet Plugin Pro و abzarwp-auto-update (SEC-03).
- **غیرضروری یا تکراری:**
  - abzarwp-auto-update، Akismet و Hello Elementor (همه غیرفعال)
  - Classic Widgets: کاربردش سنجیده نشد
  - ثابت نشان `WP_HTTP_BLOCK_EXTERNAL` در wp-config نسخهٔ نخست بود، ولی در نسخهٔ کنونی سنجیده نشد
- **کتابخانهٔ بسته‌بندی‌شده در کد ToPersia:**
  - Action Scheduler 3.9.3 (`topersia-core/vendor/`)
  - pmtiles (`topersia-core/vendor/pmtiles`)
  - نسخهٔ MapLibre و pmtiles سمت مرورگر سنجیده نشد
- **lock file:** هیچ‌کدام دست نخورد.

## بخش ۱۹ — تاریخچهٔ Git

**مشخصات:**
- ۴۴۹ commit
- شاخه‌های محلی: `main`، `backup-before-claude`، `backup-current-main`، `review/claude-lucid-hawking-59yidv` و `wip/36`
- ۱۴ شاخهٔ remote
- بستهٔ ۸۳۹ مگابایت
- تاریخچه از درون‌ریزی ۱ اکتبر (`a16131b2`، و ریشهٔ جدای `a73d4c72`) شروع می‌شود

| پرونده در تاریخچه | commit | روی remote؟ | الان ردیابی‌شده؟ | محتوا |
|---|---|---|---|---|
| `geo/wp-config.php` | `a73d4c72` | **نه** — فقط شاخهٔ محلی `backup-current-main` | نه | ۸ salt (با کنونی یکی **نیست**) · `DB_PASSWORD` خالی · نام پایگاه‌داده |
| `tests/.lh-snapshots/v4.4.x/tls-key.pem` (۲ عدد) | `a73d4c72` | نه | نه | کلید گواهی خودامضا |
| `geo/wp-content/debug.log` | `a16131b2` (حذف در `ff142ad7`) | **بله** — در همهٔ ۱۴ شاخهٔ remote، از جمله `origin/main` | نه | ۳۲ مگابایت: ۱۳٬۴۵۰ Deprecated، ۵۱ Warning، ۷ Fatal؛ مسیرهای پرونده. بی کلید (۱۴ سطر «Wp-toolkit API token is not defined»)، بی ایمیل، بی IP |
| `geo/wp-content/languages/wpml/queue/installer.php` | `a16131b2` (حذف در `259b0152`) | بله | نه | صف زمان‌اجرای WPML — بی سرّ |
| `project-knowledge/wp-config-additions.txt` | `a16131b2` | بله | بله | فقط ثابت‌های غیرسرّی |
| `docs/ops/SECRETS-KEYS.md` | `60f15859` | بله | بله | راهنما، بی مقدار |
| `geo/wp-content/abzarwp-backups/*.zip` | تاریخچه | بله | — | بستهٔ افزونهٔ پریمیوم؛ بخشی از ۸۳۹ مگابایت |

**`.gitignore`:**
- wp-config، `.env`، کلیدها، `*.sql*`، `*.log`، Duplicator، `abzarwp-backups/`، کش و `.lh-snapshots/` را می‌پوشاند. این وضعیت PASS است.
- گارد pre-commit نصب است.
- کمبودها:
  - `http-probe.log` با `*.log` پوشیده است، ولی در ریشهٔ وب می‌ماند (SEC-01).
  - الگویی برای `*.pem` عمومی نیست؛ فقط `*-key.pem` و `tls-key.pem`.

**طرح پاک‌سازی (اجرا نشد — هر گام تصمیم کاربر است):**
1. **شاخهٔ محلی `backup-current-main`** — تنها جای wp-config و کلیدهای TLS. اگر دیگر لازم نیست: `git branch -D backup-current-main`، سپس `git reflog expire --expire=now --all && git gc --prune=now`. هیچ push یا بازنویسی remote لازم نیست. بازگشت‌ناپذیر است؛ پیش از آن یک `git bundle` بیرون از مخزن ساخته شود.
2. **`debug.log` و zipهای AbzarWP روی remote:** سرّی ندارند و مخزن خصوصی است، پس اولویت کم است. اگر کاربر کاهش اندازه یا افشا را بخواهد: `git filter-repo --path geo/wp-content/debug.log --path geo/wp-content/abzarwp-backups --invert-paths` روی یک کلون جدا، سپس force-push. این کار همهٔ hash‌ها را عوض می‌کند و COMMIT-LOG، relay و شاخه‌های `claude/…` را می‌شکند. فقط با تأیید صریح انجام شود.
3. **چرخش:**
   - salt‌ها و گذرواژهٔ پایگاه‌داده تاریخچه چرخش لازم ندارند (چرخیده و خالی).
   - کلید TLS اسنپ‌شات اگر شاخهٔ ۱ پاک شد کافی است.
   - کلید SEC-02 چرخش دستیِ «در انتظار» دارد.

## یادداشت روش

- یک کاوش (`git fsck --lost-found`) پوشهٔ `.git/lost-found/` را ساخت: ۹۹۰ پرونده، همه از ساعت ۱۲:۳۳ همین روز و نسخهٔ شیءهای آویزانی که در مخزن می‌مانند. دروازهٔ ایمنی جلسه حذف آن را رد کرد، پس سر جایش مانده است.
  - درون `.git` است، نه کد یا داده، و commit نمی‌شود.
  - حذف آن با کاربر است: پوشهٔ `.git/lost-found` در ریشهٔ مخزن.
- هیچ commit، stash، reset یا تغییر شاخه‌ای جز commit همین دو سند انجام نشد.
- درخواست‌های HTTP:
  - ۱۳ GET به `topersia.local`: نشانی‌های بخش SEC-01 و `/xmlrpc.php` · `/wp-json/` · `/wp-json/wp/v2/users` · `/?author=1` · `/readme.html` · `/wp-login.php`.
  - یک `composer audit` به Packagist.
  - هیچ درخواستی به سایت زنده.
