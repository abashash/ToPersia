# نوبت جاری — رلۀ محلی ⇄ ابری

> «چوب امدادی» میان Claude Code محلی و جلسۀ ابری (OPS-D003). **هر طرف فقط بخش خودش را ویرایش می‌کند** تا ادغام squash بی‌تداخل بماند. جدول lane را فقط طرف محلی (هنگام برنامه‌ریزی) عوض می‌کند.
> عامل محلی بخش محلی را در **هر نقطۀ عطف** به‌روز نگه می‌دارد، نه فقط هنگام تحویل — اگر سهمیه ناگهان تمام شود، کاربر بی هوش مصنوعی تحویل می‌دهد و جلسۀ ابری از «قدم بعدی» همین بخش ادامه می‌دهد.
> تاریخچۀ تحویل‌ها: `HISTORY.md` · گزارش گیت‌ها: `GATES.md` · راهنما: `RELAY.md`.

## مسیرها (lane) و مالکیت پرونده‌ها

| طرف | مسیرهای در مالکیت | توضیح |
|---|---|---|
| ابری | — | |
| محلی هم‌زمان | — | |

> دو طرف هم‌زمان روی یک پرونده کار نمی‌کنند. `from-cloud` هم‌پوشانی را گزارش می‌کند.

---

## بخش محلی — فقط عامل محلی

### وضعیت و انجام‌شده

-

### قدم بعدی (دقیق و اجراشدنی)

1.

### قیدها و تصمیم‌های مرتبط

-

---

## بخش ابری — فقط جلسۀ ابری (دستور را `to-cloud` می‌نویسد)

### دستور این جلسۀ ابری

<!-- relay:task -->
(خالی) — ادامۀ «قدم بعدی» بخش محلی.
<!-- /relay:task -->

### انجام‌شده

ممیزی ۲د — بخش کدخوانی (جلسۀ ابری ۱، ۱۲ اکتبر ۲۰۲۶)؛ فقط `docs/AUDIT-2026-10/`، هیچ کد/پایگاه‌داده/تنظیم/افزونه‌ای عوض نشد. گیت‌ها سبز (GATES.md).

| پرونده | وضعیت | برچسب‌ها (تکرار در متن؛ ±۱ برای خط راهنما) V · I · A · U |
|---|---|---|
| `AI.md` | کامل | ۸۷ · ۹ · ۱ · ۹ |
| `USER.md` | کامل | ۴۴ · ۷ · ۲ · ۷ |
| `EVENTS.md` | کامل | ۳۵ · ۱۰ · ۱ · ۱۰ |
| `FEATURE-MAP.md` | کامل (۹۴ قابلیت، هر کدام یک طبقه) | ۳۹ · ۱ · ۲ · ۸ |
| `RISKS.md` | کامل (۳۵ ریسک AI Core + S01…S29 + S30 abzarwp + S31 نام مدیر) | ۶۹ · ۷ · ۲ · ۲۰ |
| `SYSTEM.md` | فقط بخش کدخوانی | ۴۱ · ۱۳ · ۲ · ۲۱ |
| `MAP.md` | فقط بخش کدخوانی | ۵۸ · ۱۰ · ۳ · ۱۰ |

- ساخته نشد (طبق دستور): `DATA.md` و `inventory.json`.
- R44 (برابری نسخۀ نصب‌شده با workspace): بخش ۱۱ `SYSTEM.md` — `schema` یکسان؛ AI و user-core فقط پرونده‌های مستند/آزمون فرق دارند؛ `core` (4.13.0 ← 4.13.1) و `render` (4.5.0 ← 4.5.1) عقب‌ترند.
- سه یافتۀ مهم: (۱) قلم `Noto Sans Italic` نقشه بازۀ گلیف عربی/فارسی را ندارد (فایل‌های ۲۶–۲۸ بایتی) و پنج لایۀ آب/دریا/جزیره/رود/آدرس از آن استفاده می‌کنند، به‌علاوۀ `letter-spacing` روی برچسب‌ها — `MAP.md` ۶؛ (۲) کلیدها متن‌ساده‌اند: کلید AI در `tpas_settings` (autoload) و کلید پیامک Mediana حتی در HTML پیشخوان چاپ می‌شود — `AI.md` ۴، `USER.md` ۲؛ (۳) فیلد تاریخ رویداد (`event_start_date/end_date`) در export هست ولی رندر و JSON-LD آن را نمی‌خوانند و پرچم P5-D214 «منتظر ساخت» کهنه است — `EVENTS.md` ۲ و ۴.

### قدم بعدی (دقیق و اجراشدنی)

1. **محلی:** اجرای فرمان‌های جدول زیر و تکمیل ردیف‌های UNKNOWN (هر ردیف شمارۀ بند و فایل مقصد دارد). خروجی عددی/بی‌دادۀ شخصی در `DATA.md` و `inventory.json` که **محلی** می‌سازد (WP-CLI، پایگاه‌داده).
2. **معمار:** حکم روی ناهم‌خوانی‌های ثبت‌شده با ARCH-RECONCILIATION: شناسۀ کش (#۸)، دکمه‌های دستیار بیرونی (P5-D255)، «بی عدد ثابت» نشانگر (Brief)، رمزنگاری کلید AI/پیامک (P5-D249)، ROADMAP user-core در برابر P5-D299، پرچم P5-D214.
3. **اصلاح‌های مستقل (کد؛ نه در این lane):** قلم Italic/`letter-spacing` در `style()` نقشه؛ `mb_strtolower` بی گارد (`class-tp-ai-internal.php:346`)؛ دورزدن سقف روزانۀ AI با خروجی کهنه — فقط با تصمیم.
4. **کاربر:** سه پرسش بازِ مانده از ARCH-RECONCILIATION §۷ (مخزن تقویم فارسی، Jev، نُه زبان) + تصمیم `abzarwp` (RISKS S30).

### در انتظار تأیید محلی

| آزمون / فرمان | چرا لازم است | وضعیت |
|---|---|---|
| `php tests/assert-uc.php` و `php plugin/topersia-ai-summarizer/tests/run.php` | ابر فقط گیت ابری را اجرا کرد؛ آزمون‌های افزونه‌ها روی محلی | در انتظار |
| **DATA.md · inventory.json** | ساخته نشد؛ پایگاه‌داده و WP-CLI لازم | در انتظار — محلی بسازد |
| `wp db query "SELECT VERSION(), @@version_comment"` (محلی + زنده) | SYSTEM ۱ — موتور و نسخۀ DB | در انتظار |
| `wp --info`، `php -m \| grep mbstring`، `php -i \| grep -E "max_execution_time\|log_errors"` (زنده) | SYSTEM ۱؛ PHP ۸٫۲ و `mbstring` فقط از `.htaccess`/کامنت استنباط شد | در انتظار |
| `wp plugin list --status=active --fields=name,version,update` | SYSTEM ۲ — افزونه‌های فعال | در انتظار |
| `wp config get DISABLE_WP_CRON`؛ `wp cron event list`؛ `crontab -l` (میزبان) | SYSTEM ۳–۴ — اجرای کرون و صف | در انتظار |
| `wp eval 'echo ActionScheduler_Versions::instance()->latest_version();'`؛ `wp action-scheduler list --status=pending --group=topersia --format=count` | SYSTEM ۳ — کدام AS و وضعیت صف | در انتظار |
| `wp cache type` | SYSTEM ۵ — کش شیء | در انتظار |
| شمار و حجم جدول‌های `topersia_*` و `actionscheduler_*` (پرس‌وجوی `information_schema` در SYSTEM ۸) | SYSTEM ۸ | در انتظار |
| حجم autoload و بزرگ‌ترین گزینه‌ها (پرس‌وجو در SYSTEM ۹) | SYSTEM ۹ | در انتظار |
| `wp plugin list --fields=name,version` (محلی/زنده) و `diff -rq` | SYSTEM ۱۱ (R44) — نسخۀ واقعاً نصب‌شده | در انتظار |
| `ls -l geo/wp-content/uploads/tiles/` · `npx pmtiles show …` · اسکریپت شمارش `name:fa/ar/en` | MAP ۴ و ۸ — وجود و پوشش `iran.pmtiles` | در انتظار |
| `curl -sI -H "Range: bytes=0-16383" <نشانی کاشی>` (محلی + زنده) | MAP ۴، ۱۰ — Range/206/CORS | در انتظار |
| نمایش چشمی «خلیج فارس»/«دریای خزر»/«دریاچۀ ارومیه» در کروم و فایرفاکس | MAP ۶ — ایراد قلم Italic | در انتظار |
| `wp topersia geo-index report`؛ `SELECT post_type, COUNT(*) FROM {prefix}topersia_geo GROUP BY post_type` | MAP ۱۰ | در انتظار |
| `wp eval 'foreach(["services","position","panel_position","post_types","internal_enabled"] as $k){echo $k,"=",wp_json_encode(TP_AI_Options::get_field($k)),"\n";}'` (محلی + زنده، بی کلید) | AI ۱ — دکمه‌های دستیار بیرونی روشن‌اند؟ | در انتظار |
| شمار ردیف `topersia_ai_summaries`/`events` و هش‌های `_tpas_hash_%` | AI ۱۴ | در انتظار |
| `wp option get tpas_settings --format=json \| wc -c` و همین برای `topersia_mediana_api_key` (فقط طول) | AI ۱۴، USER ۱۲ — کلید هست/نیست | در انتظار |
| `curl` از میزبان زنده به API ارائه‌دهندگان (Gemini، OpenAI، …) | AI ۹ — دسترسی IP ایران | در انتظار |
| `grep -c TOPERSIA_TRUST_PROXY wp-config.php` و سرآیندهای IP پراکسی | USER ۱ و ۱۲، RISKS S08 | در انتظار |
| کاربران: شمار نقش subscriber، `_topersia_country` پر، توزیع کشور، شمار `topersia_saved`/`topersia_otp_attempts` | USER ۱۲ | در انتظار |
| پست‌های رویداد (شمار)، پر بودن `event_start_date`/`end_date`/`calendar_timing`، قالب ذخیرۀ تاریخ، وجود `tpc_news` | EVENTS ۱۰ | در انتظار |
| export تازۀ JetEngine و diff با `reference/Jetengine_Skin.json` (۱۳۵ ← ۱۳۹ رابطه) | EVENTS ۱۰، RISKS S29 | در انتظار |
| `$n=(git grep -I -c -F -- $env:TP_GUARD_ADMIN_USER -- . ':!geo'\|Measure-Object).Count` (فقط شمار) | RISKS S31 — نام مدیر در کد هست/نیست | در انتظار |
| `wp plugin list` + لاگ `http-probe.log` برای میزبان‌های `abzarwp`؛ خطای React #200 | RISKS S30 | در انتظار |
| **بخش‌های پایگاه‌داده‌ای SYSTEM و MAP** (موتور/نسخه DB، افزونۀ فعال، کرون زنده، کش شیء، حجم autoload، شمار جدول، پوشش `name:*`، Range/CORS) | هر دو سند تنها «بخش کدخوانی» را دارند | در انتظار |
