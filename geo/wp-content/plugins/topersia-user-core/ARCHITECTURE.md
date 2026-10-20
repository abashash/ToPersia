# معماری — ToPersia User Core

## فایل‌ها

```
topersia-user-core.php          بوت‌استرپ، ثابت‌ها، فعال‌سازی، کرون هرس
includes/
  class-topersia-auth.php       ورود با کد یک‌بارمصرف · محدودیت نرخ · ساخت حساب
  class-topersia-db.php         جدول تلاش‌ها · هرس · ارتقای نسخه
  class-topersia-mediana-sms.php  تنها نقطهٔ تماس با سرویس پیامک
  class-topersia-sms-provider.php رابط ارائه‌دهنده
  class-topersia-dashboard.php  شورت‌کد پنل · گردآوری داده
  class-topersia-profile.php    ذخیرهٔ پروفایل
  class-topersia-jetengine.php  خواندن موجودیت‌ها · ذخیره و حذف مکان
  class-topersia-follow.php     دنبال‌کردن خبرهای موجودیت · وضعیت دکمه‌ها (۱٫۱۰٫۰)
  class-topersia-review-form.php  ثبت نظر از فرم صفحه — در انتظار تأیید (۱٫۱۰٫۰)
  class-topersia-jetreviews-setup.php  نوع‌های نظر JetReviews به ازای خانواده
  class-topersia-i18n.php       رشته‌های سه‌زبانه
  class-topersia-admin.php      صفحهٔ پیشخوان · تنظیمات · تب‌ها
  class-topersia-assets.php     بارگذاری CSS و JS فرانت
  jetengine-schema.php          کپی ساختار، فقط برای توسعه
templates/
  login.php                     فرم ورود
  dashboard.php                 پنل کاربری
assets/css/user-core.css        سبک فرانت
assets/js/user-core.js          تعامل پنل · تمام‌صفحه · ذخیرهٔ مکان
assets/js/admin.js              ساخت تب سفارشی در پیشخوان
```

`assets/css/admin.css` حذف شد؛ پیشخوان از `topersia_admin_css()` هسته می‌خواند.

---

## نقاط AJAX

| اکشن | ورود لازم | کار |
|---|---|---|
| `topersia_send_otp` | نه | اعتبارسنجی شماره، محدودیت نرخ، ارسال پیامک، ساخت توکن |
| `topersia_verify_otp` | نه | سقف حدس، بررسی کد، ورود یا ساخت حساب |
| `topersia_logout` | بله | خروج |
| `topersia_save_place` · `topersia_remove_saved_place` | بله | افزودن یا برداشتن مکان از فهرست ذخیره — شناسهٔ زبان پیش‌فرض |
| `topersia_follow_entity` | بله | دنبال‌کردن یا لغو (`mode`) — متای `_topersia_followed` (۱٫۱۰٫۰) |
| `topersia_entity_state` | بله | ذخیره‌شده؟ دنبال‌شده؟ — برای دکمه‌های صفحهٔ کش‌پذیر (۱٫۱۰٫۰) |
| `topersia_submit_review` | بله | ثبت نظر در JetReviews با `approved = 0` (۱٫۱۰٫۰) |
| `topersia_save_profile` | بله | ذخیرهٔ فیلدهای پروفایل |
| `topersia_save_display_prefs` | بله | همگام‌سازی «نمایش و خوانایی» با متای کاربر |

همه با نانس `topersia_user_core` بررسی می‌شوند. ساخت نوع‌های نظر JetReviews
از پیشخوان است، نه AJAX: `admin-post.php?action=topersia_setup_jetreviews`.

**پنجرهٔ ورود/ثبت‌نام** (۱٫۱۰٫۰) — `ToPersia_Auth::login_modal()` روی
`wp_footer` فقط برای مهمان، فقط جایی که `user-core.js` بار شده و نه روی برگهٔ
حساب. باز شدن: `ToPersiaUserCore.openLogin({reason, redirect})`، رویداد
`topersia:login-required`، یا کلیک روی `[data-tp-login]`. «هستهٔ نمایش» برای
ذخیره، دنبال‌کردن، نظر و هوش مصنوعی همین را صدا می‌زند و خودش هیچ فرم ورودی
نمی‌سازد.

---

## داده

**جدول `{prefix}topersia_otp_attempts`**

| ستون | کار |
|---|---|
| `mobile` · `purpose` | شماره و هدف |
| `attempts` · `verified` | شمار تلاش و وضعیت |
| `expires_at` · `created_at` | انقضا و زمان ساخت |
| `request_id` | شناسهٔ پیامک نزد ارائه‌دهنده |
| `ip_hash` | نشانی هش‌شده، فقط برای حسابرسی |

هرس روزانه: تأییدنشده و تأییدشده هر دو حداکثر ۲۴ ساعت (۱٫۱۳٫۰؛ پیشتر تأییدشده هفت روز).

**متای کاربر**

| کلید | نوع |
|---|---|
| `_topersia_mobile` | شماره، مبنای یافتن کاربر |
| `_topersia_country` · `_topersia_language` · `_topersia_travel_style` | پروفایل |
| `_topersia_saved_entities` | آرایهٔ مکان‌های ذخیره‌شده — **نیازمند مهاجرت، بخش نقشهٔ راه** |
| `_topersia_followed` | شناسه‌های زبان پیش‌فرضِ موجودیت‌های دنبال‌شده، سقف ۵۰۰ (۱٫۱۰٫۰) |

**آپشن‌ها**

`topersia_mediana_api_key` · `topersia_mediana_pattern_code` · `topersia_account_page_url` · `topersia_user_tabs` · `topersia_user_core_db_version`

---

## کلیدهای متای موجودیت

`ToPersia_JetEngine` این کلیدها را از موجودیت‌های دانشنامه می‌خواند. اینها با متاباکس مشترک پروژه هم‌راستا هستند:

| کاربرد | کلیدها |
|---|---|
| نام | `name_fa` · `name_en` · `name_local` — **`name_ar` جا افتاده** |
| تصویر | `hero_image` — `featured_image` کلید ما نیست |
| خلاصه | `short_summary` · `overview_text` — `description` کلید ما نیست |

نوع پست‌های پذیرفته: هر چیزی که با `tp_` شروع شود.

---

## زنجیرهٔ منو

صفحهٔ پیشخوان جای خودش را بر پایهٔ آنچه نصب است انتخاب می‌کند:

```
هسته هست؟        → زیرمنوی topersia با نام «کاربران»
وگرنه نمایش هست؟ → زیرمنوی topersia-render
وگرنه            → منوی سطح‌بالای مستقل
```

صفحه سه زبانه دارد و هر سه در **یک فرم**‌اند. جداکردنشان ممنوع است: `sanitize_tabs()` آرایه را از نو می‌سازد، پس ارسال ناقص یعنی پاک‌شدن مقادیر زبانه‌های دیگر.

---

## پنل تمام‌صفحه

`user-core.js` پنل را به `body` منتقل می‌کند و سه پله دارد:

۱. انتقال و افزودن کلاس‌های `tp-usercore-fullscreen` و `tp-usercore-open`
۲. اگر نچسبید، سبک درون‌خطی با `!important`
۳. اگر باز نچسبید، انتقال زیر `html` — برای وقتی که خود `body` بلوک احاطه‌کننده ساخته باشد

پلهٔ دو و سه به لاگ هسته گزارش می‌دهند. در CSS هم یک قاعدهٔ `html:root` هست که به جاوااسکریپت وابسته نیست.

سنجش «چسبیده» فقط عمودی است: `100vw` عرض نوار پیمایش را می‌شمارد و در RTL چند پیکسل انحراف افقی می‌سازد.
