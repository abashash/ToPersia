# راهنمای پیکربندی استک وردپرسی topersia.com
## JetEngine + WPML + Elementor + Woodmart — نسخه‌های جاری (جولای ۲۰۲۶)

> **توجه سراسری:** زبان پیش‌فرض سایت **فارسی (fa)** در ریشهٔ دامنه است؛ **انگلیسی (en)** و **عربی (ar)** زبان‌های ثانویه در زیرشاخه (/en/، /ar/) هستند. پنل مدیریت به فارسی و توسط کاربر غیربرنامه‌نویس استفاده می‌شود (الزام no-code).
> **نسخه‌های مرجع (تأییدشده از منابع رسمی، جولای ۲۰۲۶):** WPML نسخهٔ **4.9.5** (منتشر ۱۰ ژوئن ۲۰۲۶؛ سازگاری کامل با PHP 8.5) — نسخهٔ 4.9.4 «سازگاری کامل با WordPress 7.0» را افزود. Elementor نسخهٔ **4.3.0** (Editor V4، پس از ۴.۰ در آوریل ۲۰۲۶). JetEngine نسخهٔ **3.8.4** (صفحهٔ رسمی سازگاری WPML، آخرین تست ۲۶ فوریهٔ ۲۰۲۶). Woodmart نسخهٔ **8.5** (سازگار با WordPress 7.0+ و WooCommerce 10+).
> **منابع:** ادعاهای «رسمی» از crocoblock.com، wpml.org، elementor.com، xtemos.com؛ ادعاهای «انجمنی/فوروم» جداگانه علامت‌گذاری شده‌اند.

---

## بخش ۰ — ترتیب اجرا (ترتیب پیکربندی)

۱. **زیرساخت وردپرس:** ساختار پیوندهای یکتا (Permalinks) را روی `%postname%` بگذارید — پیش‌نیاز رسمی WPML برای «زبان‌ها در دایرکتوری». (آپاچی 2.4.68 این محیط، ماژول rewrite را پشتیبانی می‌کند.)
۲. **ابتدا WPML:** زبان پیش‌فرض فارسی، افزودن en/ar، فرمت URL دایرکتوری، غیرفعال‌سازی کامل ترجمهٔ خودکار. WPML باید قبل از ساخت ساختار محتوا نصب شود، چون حالت ترجمهٔ انواع‌پست و تنظیمات فیلدها بر رفتار CPTها اثر می‌گذارد.
۳. **Woodmart:** انتخاب Elementor به‌عنوان سازندهٔ صفحه، غیرفعال‌سازی مسیرهای WooCommerce، بارگذاری فونت محلی.
۴. **Elementor:** فعال‌سازی Container، روش چاپ CSS، فونت‌ها، آزمایش‌ها.
۵. **JetEngine:** فعال‌سازی ماژول‌های حداقلی، ساخت CPTها، تعریف Relations و محل ذخیره، سپس Query Builder و Listing.
۶. ساخت قالب‌ها (Listing/Single/Archive) و در پایان کش و CDN.

**دلیل ترتیب:** هم Crocoblock و هم Woodmart تأکید دارند که تصمیمات معماری (زبان پیش‌فرض، فرمت URL، اسلاگ CPT، محل ذخیرهٔ متا، انتخاب سازندهٔ صفحه) باید پیش از تولید محتوا قفل شوند، چون تغییر بعدی‌شان مخرب یا پرهزینه است.

---

## بخش ۱ — هشدارهای بحرانی (تنظیمات غیرقابل‌بازگشت یا پرهزینه در صورت تنظیم اشتباه)

۱. **زبان پیش‌فرض WPML (فارسی)** — پس از تولید محتوا تقریباً غیرقابل تغییر.
۲. **فرمت URL زبان (دایرکتوری) + گزینهٔ «Use directory for default language»** — تغییر بعدی، ساختار URL و سئو را می‌شکند و نیازمند ریدایرکت‌های گسترده است.
۳. **اسلاگ CPTها** — تغییر بعدی پیوندهای یکتا را خراب می‌کند.
۴. **CPT در برابر CCT** — CCTها با WPML **قابل ترجمه نیستند** (رسمی)؛ تصمیمی بنیادی و برگشت‌ناپذیر است.
۵. **Custom Meta Storage (جدول سفارشی متا)** — دادهٔ قبلی خودکار منتقل نمی‌شود؛ فعال‌سازی بعدی نیازمند مهاجرت دستی و پرریسک است.
۶. **Register separate DB table در Relations** — تغییر بعدی محل ذخیرهٔ دادهٔ رابطه را عوض می‌کند.
۷. **پارادایم Container در برابر Section در Elementor** — ساخت با Section و مهاجرت بعدی پرهزینه است.
۸. **انتخاب سازندهٔ صفحه در Woodmart (Elementor)** — رسماً «دائمی»؛ سوییچ بعدی یعنی بازسازی محتوا از صفر.
۹. **JetEngine Relations × WPML** — بزرگ‌ترین ریسک معماری پروژه؛ حتماً پیش از تولید انبوه پایلوت شود (بخش ۳).

---

## بخش ۲ — Elementor + Elementor Pro (پیکربندی صفحه‌به‌صفحه)

### Settings → General
- **Post Types (فعال‌سازی Elementor روی انواع‌پست):** فقط `Pages` و انواع قالب لازم فعال بماند. CPTهای مرجع که با قالب‌های JetEngine/Theme Builder رندر می‌شوند در ابتدا از ویرایش مستقیم Elementor خارج بمانند (کاهش سطح حمله و بار ادمین). **مقدار:** حداقلی.
- **Disable Default Colors:** ✅ فعال (تیک بزنید). سند رسمی Elementor: «Select both Disable Default Colors and Disable Default Fonts… This will disable the Elementor defaults and make your theme’s colors and fonts the default.» چون Woodmart مالک سیستم طراحی است.
- **Disable Default Fonts:** ✅ فعال. همان دلیل — جلوگیری از بارگذاری دوگانهٔ فونت و تعارض استایل.
- **Improve Elementor > Usage Data Tracking:** خاموش (اختیاری، حریم خصوصی).

### Settings → Performance
- **CSS Print Method:** **External File** (فایل مستقل). سند رسمی: «Choose if your CSS will be included as an independent stylesheet (recommended) or on each page.» با کش کامل صفحه + CDN، فایل CSS خارجی با TTL بلند قابل‌کش است و Elementor هنگام Regenerate CSS نام فایل را عوض می‌کند (cache-busting امن). «Internal Embedding» حجم HTML را بالا می‌برد و کش را ناکارآمد می‌کند. **نیازمند تصمیم/تست:** روی سرورهای بسیار کم‌منبع Internal گاهی لازم است؛ در این محیط (512M) External توصیه می‌شود.
- **Optimized Image Loading:** فعال — سند رسمی: «applying fetchpriority=”high” on LCP images and loading=”lazy” on images below the fold.»
- **Optimized Gutenberg Loading:** فعال — «Dequeues unused Gutenberg block editor scripts and styles.»
- **Lazy Load Background Images:** فعال — به‌جز تصویر اول (LCP): «applying lazy loading to all background images except the first one.»

### Settings → Advanced
- **Google Fonts Load:** `Swap` (نمایش فونت جایگزین تا بارگذاری فونت اصلی؛ بهبود FCP).
- **Load Font Awesome 4 Support:** `No`.
- برای سایت سه‌زبانهٔ fa/ar/en فونت‌ها را **محلی** میزبانی و بارگذاری ریموت Google را قطع کنید.

### فونت‌ها (fa/ar/en) — مالک واحد
فونت‌ها را فقط از **یک** مالک بارگذاری کنید تا بارگذاری دوگانه رخ ندهد. **توصیه: Woodmart مالک فونت باشد** (Theme Settings → Performance → Fonts & Icons → **Load Google Fonts locally**؛ و برای فونت فارسی مثل Vazirmatn از Theme Settings → Typography → **Custom Font** با فایل‌های woff/woff2). گزینهٔ جایگزین رسمی: Elementor Pro → **Custom Fonts** (Elementor → Custom Fonts → Add New → آپلود woff2/ttf در وزن‌های موردنیاز، هر وزن یک فایل مجزا). نکته: در Elementor 4.3.0 ویژگی «Load Google Fonts locally» به هستهٔ Elementor ادغام شده است، اما برای اجتناب از بارگذاری دوگانه فقط **یک** مالک را انتخاب کنید.

### Settings → Features / Experiments (نسخهٔ ۲۰۲۶)
- **Flexbox Container:** فعال. اکنون روش پیش‌فرض و استاندارد ساخت است (DOM سبک‌تر، بهبود ۱۵–۲۵٪ در Core Web Vitals طبق مستندات Elementor). **بحرانی:** باید قبل از ساخت هر قالبی فعال شود.
- **Nested Elements:** فعال (نیازمند Container؛ برای تب/آکاردئون تودرتو).
- **Optimized Markup:** فعال — wrapperهای اضافهٔ `<div>` را حذف و HTML را سبک می‌کند. در Elementor 4.3.0 این ویژگی «برای سایت‌های جدید فعال» شده است. توجه توسعه‌دهنده: کلاس `.elementor-widget-container` حذف می‌شود.
- **Improved CSS Loading:** فعال — «minimizes the default CSS code loaded on the page.»
- **Inline Font Icons:** فعال — «Renders icons as inline SVG without loading the Font-Awesome and the eicons libraries.»
- **Element Caching:** ⚠️ **خاموش به‌صورت سراسری / با احتیاط شدید.** (توجه: در Elementor 4.3.0 این ویژگی به وضعیت Stable ارتقا یافته، اما ماهیت محدودیتش تغییر نکرده.) سند رسمی صریح است: «Element Caching cannot be used on elements that contain dynamic tags or any other type of dynamic information, such as shortcodes… Activating Element Caching on an element that uses dynamic tags can cause issues with dynamic content.» چون کل سایت مبتنی بر Listing Grid و Dynamic Field جت‌انجین (محتوای پویا) و چندزبانه (WPML) است، فعال‌سازی سراسری می‌تواند محتوای زبان اشتباه یا کش‌شدهٔ نادرست نشان دهد. **فقط** روی المان‌های کاملاً استاتیک (بدون dynamic tag و بدون متن ترجمه‌پذیر) به‌صورت موردی فعال شود. **نیازمند تصمیم/تست روی صفحات هاب.**
- **Additional Custom Breakpoints:** پیش‌فرض (فقط در صورت نیاز؛ تا ۶ نقطه‌شکست اضافه).

### Elementor Pro — تقسیم کار
- **Theme Builder** برای قالب‌های Single و Archive انواع‌پست سفارشی مفید است؛ اما موتور اصلی نمایش دادهٔ رابطه‌ای، **Listing Grid + Custom Query** جت‌انجین است. توصیه: Single/Archive با Theme Builder یا قالب‌های JetEngine؛ آیتم‌های تکرارشونده با Listing Template جت‌انجین.
- **هدر/فوتر:** طبق مستندات رسمی Woodmart، از **Header Builder خود Woodmart** استفاده کنید (نه Theme Builder المنتور)؛ توضیح ترجمهٔ چندزبانه در بخش ۵.

### RTL در Elementor
Elementor کلاس/استایل RTL را بر اساس زبان سایت سوییچ می‌کند؛ زیر WPML برای fa/ar حالت RTL و برای en حالت LTR به‌صورت خودکار اعمال می‌شود. برای CSS اختصاصی هر زبان از کلاس زبانی که WPML به `<body>` می‌افزاید استفاده کنید.

---

## بخش ۳ — JetEngine (پیکربندی عمیق، نسخهٔ ۳.۸.۴)

### JetEngine → JetEngine → Modules
اصل رسمی Crocoblock: «We recommend disabling unnecessary modules to improve website performance… Users can enable only the modules they need to avoid overloading websites.»

**Default Modules — توصیه:**
| ماژول | توصیه | دلیل (یک‌خطی) |
|---|---|---|
| Maps Listings | خاموش | سایت مرجع، نیاز اولیه ندارد |
| Data Stores | خاموش | بی‌نیاز + تعارض کش: «Data Stores that use the Session Store type may conflict with caching plugins» |
| REST API Listings | خاموش | رندر کلاسیک، بدون headless |
| Forms (Legacy) | خاموش | منسوخ؛ در صورت نیاز JetFormBuilder جداگانه |
| Custom Content Type (CCT) | خاموش | با WPML ترجمه‌ناپذیر (پایین) |
| Profile Builder | خاموش | بی‌نیاز |
| Grid/Slider Gallery | خاموش | مگر گالری متا لازم شود |
| QR Code | خاموش | بی‌نیاز |
| Listing Grid injections | خاموش | فقط اگر قالب‌های جایگزین در یک Grid لازم شد |
| Dynamic Visibility | خاموش در ابتدا | در صورت نیاز به شرط نمایش، روشن |
| FullWidth Block Editor | خاموش | گوتنبرگ استفاده نمی‌شود |

**External Modules** (افزونهٔ جدا): Dynamic Tables Builder، Dynamic Charts Builder، Custom Visibility Conditions، Attachment file link by ID، Trim String، Post Expiration — همه نصب‌نشده در ابتدا.

**هدف حداقلی فعال:** Custom Post Types، Meta Fields، Relations، Query Builder، Listing Grid. این‌ها بخشی از **هستهٔ JetEngine**‌اند و در تب Modules نیازی به فعال‌سازی جداگانه ندارند؛ فقط ماژول‌های اضافه را خاموش نگه دارید.

### JetEngine → Performance
- **Optimized DOM:** فعال (حذف wrapperهای اضافه). هشدار رسمی: «after enabling this option you need to restyle all JetEngine widgets that were used.» → قبل از استایل‌دهی فعال شود.
- **Elementor Views:** فعال (رندر با Elementor).
- **Blocks Views:** خاموش (گوتنبرگ استفاده نمی‌شود → ویجت/تگ پویای بلاک خاموش).
- **Bricks Views:** خاموش.
- **Timber/Twig Views:** **نیازمند تصمیم/تست** — برای هاب‌های ۳۰۰+ آیتم، Crocoblock رسماً Timber/Twig را سبک‌تر می‌داند («create them in the new Timber/Twig view for JetEngine»)، اما نیازمند نصب Timber و دانش قالب است (تعارض با اصل no-code). در ابتدا خاموش.

### JetEngine → Advanced
- **Disable legacy User Meta processing:** پیش‌فرض (خاموش) مگر نیاز خاص.
- **Disable Frontend Query Editor:** **فعال** — قفل امنیتی: «prevents users or editors from modifying JetEngine-powered queries on the front end.»
- **Force LTR on JetEngine pages:** **خاموش** — چون پنل فارسی/RTL است و UI طبیعی RTL مطلوب است. این فقط UI ادمین جت‌انجین را تحت‌تأثیر می‌گذارد.

### JetEngine → MCP Server
خاموش در ابتدا (کاهش سطح حمله؛ فقط برای اتوماسیون AI).

### Relations (بحرانی‌ترین بخش)

**معماری فعلی:**
- روابط در **جدول‌های سفارشی دیتابیس** ذخیره می‌شوند، نه postmeta. اگر «Register separate DB table» خاموش باشد همه در جدول عمومی `jet_rel_default_meta`؛ اگر روشن باشد جدول اختصاصی مثل `jet_rel_81_meta` ساخته می‌شود («a separate table is created… like jet_rel_81_meta, where 81 is substituted with the Relation ID»).
- سه نوع رابطه: one-to-one، one-to-many، many-to-many. سلسله‌مراتب parent/child و امکان ساخت والد از صفحهٔ فرزند پشتیبانی می‌شود.
- **Relation Meta (ویژگی‌های یال / edge attributes):** می‌توان فیلد متا به خود رابطه افزود — انواع موجود: Text، Date، Time، Datetime، Textarea، Checkbox، Media، Radio، Select، Number. این دقیقاً «edge attributes» موردنیاز ۲۷ رابطهٔ پروژه است.
- **REST endpoints رابطه:** خاموش نگه دارید (بدون headless).

**Relations × WPML — یافته‌های بحرانی:**
- **نگاشت خودکار شناسه بین زبان‌ها مستند نشده / عملاً وجود ندارد.** مستندات رسمی Crocoblock و WPML دربارهٔ نگاشت خودکار شناسهٔ آیتم‌های مرتبط (از طریق `icl_object_id`/`wpml_object_id`) برای ویژگی بومی Relations **سکوت** کرده‌اند. روش رسمی، گردش‌کار **دستی و وابسته به ترتیب** است.
- **روش رسمی Crocoblock:** «Initially, you should translate the items that will be set as child and parent items… Once the relation is built, the items can be connected.» یعنی ابتدا آیتم‌های فرزند/والد ترجمه، سپس اتصال رابطه به‌صورت دستی.
- **باگ مستند رسمی WPML (errata):** هنگام ترجمهٔ والد، رابطه «جابه‌جا» می‌شود: «If a relation is set between those two custom post types in default language, and then the shop post is translated, the relationship is unexpectedly moved to the translated shop post.» راه‌حل رسمی: «Translate your custom post type (coupon) before setting the relationship.»
- **شواهد فوروم WPML (انجمنی):** هنگام ترجمهٔ والد، WPML روابط را «بازتعریف» می‌کند و ممکن است رابطه به آیتم‌های زبان پیش‌فرض اشاره کند یا خالی شود («whenever you translate Parent post (Shop) WPML redefines relations for all Child posts»). تیم سازگاری WPML موضوع را به Crocoblock ارجاع داده و مستندات به‌روزرسانی شده است.
- **گزارش انجمنی (GitHub Crocoblock/suggestions #1663، چند سال باز):** افزودن مجدد آیتم‌های مرتبط در نسخهٔ ترجمه‌شده دشوار است؛ مقادیر checkbox/select در زبان دوم منتقل نمی‌شوند («WPML cannot copy the 'true' or 'false' status for each meta field so we have to manually recheck those checkboxes»).
- **wpml-config.xml برای فیلدهای متای شناسه‌محور:** از **WPML 4.8** الگوی رسمی وجود دارد: `<custom-field action="copy" type="post-ids" sub-type="...">custom_field_with_id</custom-field>`. سند رسمی: «As of WPML 4.8, you can declare IDs located in post custom fields. WPML replaces the IDs with their translated versions on the frontend. You can only do this when the translation option is Copy.» فرمت‌های پشتیبانی‌شده: تک‌شناسه، فهرست جداشده با کاما، آرایه/آرایهٔ کدشده. **هشدار حیاتی:** این مکانیزم برای شناسه‌های ذخیره‌شده در **فیلد متا** است، نه لزوماً جدول رابطهٔ بومی `jet_rel_*` جت‌انجین؛ **هیچ منبع رسمی تأیید نمی‌کند** که جدول رابطه تحت این مکانیزم پوشش داده شود. **نیازمند تصمیم/تست جدی — این بزرگ‌ترین ریسک معماری پروژه است.**

**توصیهٔ عملی Relations:**
1. گردش‌کار اجباری: ابتدا همهٔ آیتم‌های CPT دو طرف رابطه را در هر سه زبان ترجمه کنید؛ سپس رابطه را بسازید؛ در آخر والد را ترجمه کنید (برای اجتناب از باگ جابه‌جایی errata).
2. برای هاب‌های ۳۰۰+ آیتم، رفتار در زبان‌های ثانویه را **حتماً پایلوت کنید** قبل از تولید انبوه.
3. «Register separate DB table» را برای روابط پرترافیک/بزرگ روشن کنید (جداسازی و کوئری سریع‌تر) و این تصمیم را قبل از ساخت رابطه قفل کنید.
4. اگر پایلوت نشان داد آیتم‌های مرتبط در زبان ثانویه خالی برمی‌گردند، جایگزین را بسنجید: نگهداری شناسه‌های مرتبط در یک فیلد متای استاندارد با `action="copy" type="post-ids"` به‌جای اتکای صرف به جدول رابطه.

### CPT در برابر CCT (تصمیم بحرانی)
- CCT داده را در جدول سفارشی خارج از `wp_posts` ذخیره می‌کند و برای «big-data» سریع‌تر است («All data from each CCT is saved to a separate database table… optimal for big-data websites»).
- **اما CCTها با WPML قابل ترجمه نیستند.** سند رسمی صریح: «as of now, it is not possible to translate Custom Content Types (CCTs) with WPML.»
- **نتیجهٔ قطعی:** چون هر ۴۲ نوع موجودیت باید در سه زبان ترجمه شوند، **همهٔ موجودیت‌های ترجمه‌پذیر باید CPT باشند، نه CCT.** CCT فقط برای دادهٔ غیرترجمه‌پذیر (مثلاً لاگ/دادهٔ عددی داخلی) قابل‌بررسی است.

### Custom Meta Storage (ذخیرهٔ فیلدهای متا در جدول سفارشی)
- نسخهٔ فعلی امکان ذخیرهٔ فیلدهای متای CPT در **جدول سفارشی** به‌جای `wp_postmeta` را دارد: General Settings → **Custom Meta Storage** toggle → Custom DB table name. فقط فیلدهای افزوده‌شده از رابط CPT پشتیبانی می‌شوند («As of now, we can't implement storing meta fields in a custom table for the Meta Boxes tool»).
- در مقیاس ۲۱٬۶۰۰ موجودیت × ده‌ها فیلد، این می‌تواند کوئری‌های متا را سریع‌تر و جدول postmeta را سبک‌تر کند.
- **هشدارها:** (۱) دادهٔ قبلی خودکار منتقل نمی‌شود («metadata storage in a separate table is available for meta fields only. The newly created table will display the newly added data, and all previously created data should be transferred separately») — پس باید قبل از تولید محتوا تصمیم گرفته شود. (۲) با Custom Meta Storage گزینهٔ «Save as separate fields» ریپیتر در دسترس نیست. (۳) **سازگاری WPML: نیازمند تصمیم/تست** — کاربران در فوروم رسمی WPML مشکل خواندن داده از جدول متای سفارشی جت‌انجین با WPML را گزارش کرده‌اند؛ منبع رسمی قطعی برای تأیید ترجمه‌پذیری این فیلدها یافت نشد.
- **توصیه:** فیلدهای **ترجمه‌پذیر** را فعلاً در postmeta استاندارد نگه دارید؛ Custom Meta Storage را فقط برای فیلدهای غیرترجمه‌پذیر عددی/فیلترشونده و پس از پایلوت به‌کار ببرید.

---

## بخش ۴ — WPML (پیکربندی عمیق، نسخهٔ ۴.۹.۵)

### راه‌اندازی اولیه (WPML → Languages)
- زبان پیش‌فرض: **فارسی (fa)**. افزودن **English (en)** و **Arabic (ar)** از طریق Setup Wizard.
- **Language URL format:** «Different languages in directories» (زیرشاخهٔ /en/ و /ar/). سند رسمی: «For Different languages in directories to work, your site’s permalink structure needs to be different than the default one… Apache’s rewrite module must be enabled.» دایرکتوری‌های واقعی نسازید — مجازی‌اند («These directories are virtual… if you do this, languages in directories will not work»).
- فارسی در ریشه بماند (بدون /fa/). اگر بعداً /fa/ لازم شد، گزینهٔ «Use directory for default language» + یک root page لازم است — این تصمیم را از ابتدا قفل کنید.
- **SEO options:** WPML به‌صورت خودکار تگ hreflang و x-default تولید می‌کند. موقعیت hreflang را روی پیش‌فرض بگذارید مگر تعارض قالب: «In most cases, it is best to use the default setting. Sometimes, different themes and plugins may cause conflicts.»

### غیرفعال‌سازی کامل ترجمهٔ خودکار (بدون مصرف اعتبار)
در نسخهٔ فعلی (۴.۷+) باید در **سه لایه** خاموش شود:
1. **WPML → Translation Management (بالای صفحه):** گزینهٔ «Translate Everything Automatically» را خاموش کنید. سند رسمی فوروم WPML: «For WPML 4.7 or greater: Go to WPML > Translation Management and disable the option at the very top.»
2. **حالت ترجمه:** «Translate What You Choose» (WPML → Settings → Translation Mode → Change Mode).
3. **Advanced Translation Editor:** تیک «Translate all content automatically when the editor is opened» را بردارید (⚙ Settings در گوشهٔ بالای ویرایشگر).
- علاوه‌براین موتورهای ترجمهٔ خودکار را خاموش و **اعتبارها (credits) را حذف** کنید تا مصرف ناخواسته رخ ندهد. با «Translate What You Choose» + بدون credit، هیچ اعتباری مصرف نمی‌شود.

### WPML → Settings
- **Post Types Translation (برای CPTهای JetEngine):** «Translatable – use translation if available or fallback to default language». دلیل: در سایت مرجع، محتوا تدریجی و زبان‌به‌زبان اضافه می‌شود؛ fallback باعث می‌شود آیتم بدون ترجمه به‌جای ناپدیدشدن، به زبان پیش‌فرض نمایش داده شود. **نیازمند تصمیم:** اگر سیاست محتوایی این است که آیتم‌های بدون ترجمه در زبان ثانویه پنهان شوند، «Translatable – only show translated items» را انتخاب کنید.
- Listing Items و Components جت‌انجین را نیز Translatable کنید («Here, JetEngine Components and Listing Items can also be set to be translatable»).
- **Taxonomies Translation:** تاکسونومی‌های موردنیاز را روی Translate بگذارید.
- **Custom Fields Translation (فیلدهای متای JetEngine):** پیش‌فرض همه «Don’t translate». برای هر فیلد:
  - متن ترجمه‌پذیر → **Translate**.
  - عدد/تاریخ/مقدار مشترک → **Copy** یا **Copy once**.
  - **فیلدهای شناسه‌محور رابطه‌ای** → **Copy** (پیش‌نیاز الگوی `type="post-ids"`).
  - هشدار رسمی: «you do not want to change any Field attributes except the Label. If you make any other changes… the website won’t work correctly.»
- **Custom XML (wpml-config.xml):** برای فیلدهای شناسه‌محور از الگوی رسمی WPML 4.8 (بخش ۳) استفاده کنید.

### WPML String Translation
- برای **~۷۰ برچسب طبقه‌بندی/گلاسری** و برچسب‌های CPT/فیلد، از **String Translation** استفاده کنید. برچسب‌های رابطه، گلاسری و فیلدهای چندگزینه‌ای در WPML → String Translation ترجمه می‌شوند — **فقط Labelها، نه Valueها** (سند رسمی: «Translate only the Labels and not the Values»).
- برچسب‌های ادمین جت‌انجین را از «Translate texts in admin screens» / دامنهٔ رشته‌ها ثبت و ترجمه کنید.
- **نکتهٔ عملکردی (حیاتی برای مقیاس):** از **WPML 4.3** به بعد، String Translation ترجمه‌ها را از **فایل‌های .mo** بارگذاری می‌کند نه دیتابیس. اعلامیهٔ رسمی WPML 4.3: «Instead of reading translations from the database, it generates and loads .mo files. This results in much lighter database usage… the server load due to String Translation has practically dropped to zero.» همچنین «Register strings while the page is rendered» — که «registers only the strings that need translation» — از WPML 4.7 به‌صورت پیش‌فرض فعال است.

### مدیریت رسانه بدون Media Translation
- بدون افزونهٔ Media Translation، تصویر شاخص و پیوست‌ها به‌صورت پیش‌فرض از زبان اصلی **کپی** می‌شوند و در همهٔ زبان‌ها یکسان‌اند. برای این سایت مرجع (تصاویر مشترک بین زبان‌ها) این رفتار مطلوب است و در ابتدا Media Translation لازم نیست.
- Media Translation فقط زمانی لازم می‌شود که بخواهید **تصویر متفاوت** (مثلاً بنر متنی) در هر زبان داشته باشید.

### عملکرد WPML در مقیاس
- String Translation مبتنی بر .mo (بالا) بار DB را در ۲۱K موجودیت × ۳ زبان کاهش می‌دهد.
- Query Monitor برای شناسایی کوئری‌های سنگین WPML توصیهٔ رسمی است.
- تعامل با کش: صفحات را کش کامل کنید اما مسیرهای پویا/سوییچر زبان را در نظر بگیرید.

### WPML + JetEngine — وضعیت سازگاری رسمی
- WPML رسماً: «JetEngine is fully compatible with WPML… Version 3.8.4 (Known issues) Last tested on: February 26, 2026» (شریک رسمی).
- **تأییدشده سازگار:** CPT، Meta Fields/Meta Boxes، Listing Items/Components، Taxonomies، Query Builder، Options Pages meta (از طریق String Translation).
- **دارای اخطار/محدودیت:** Relations (نگاشت خودکار شناسه مستند نشده، باگ جابه‌جایی هنگام ترجمهٔ والد)، Relation Meta، **CCT (اصلاً ترجمه‌پذیر نیست)**، مقادیر checkbox/radio/select (فقط Label ترجمه می‌شود)، Options Pages (فقط از String Translation، ریپیتر ترجمه‌ناپذیر)، Custom Meta Storage (تست‌نشده/گزارش مشکل انجمنی).

### RTL در WPML
WPML برای fa/ar کلاس بدنه و استایل‌شیت RTL را سوییچ می‌کند و برای en به LTR. برای CSS هر زبان از سلکتور کلاس زبانی بدنه استفاده کنید.

---

## بخش ۵ — Woodmart (سایت بدون WooCommerce، نسخهٔ ۸.۵)

### پیش‌نیازها و افزونه‌ها
- **آیا Woodmart بدون WooCommerce کار می‌کند؟** بله از نظر فنی، اما پشتیبانی رسمی XTemos تأکید دارد: «WoodMart theme is based on Woocommerce functionality which is why when you use another e-commerce most functionality is not supported.» بخشی از قابلیت‌ها (آیکون سبد و…) بدون آن در دسترس نیست. برای سایت مرجع این محدودیت‌ها بی‌اهمیت‌اند.
- **Woodmart Core:** **الزامی** است («The main functionality plugin for your theme (marked as “Required”)»). حتماً نصب و فعال بماند.
- **افزونه‌هایی که نباید نصب شوند:** WooCommerce (تصمیم پروژه)، WPBakery Page Builder (چون Elementor انتخاب شده — استفادهٔ همزمان دو سازنده تعارض می‌سازد: «Using 2 builders at the same time (Elementor and WPBakery) may cause conflicts»)، Slider Revolution (سبک‌سازی؛ از اسلایدر خود Woodmart استفاده شود). Safe SVG و Contact Form 7 اختیاری.

### Theme Settings → Other
- **Current builder:** **Elementor**. (این انتخاب رسماً دائمی است — «This choice is permanent – switching between builders after setup requires rebuilding your content from scratch.»)
- Gutenberg blocks / Gutenberg editor for products: خاموش.

### Theme Settings → Performance
- **Include minified CSS / Include minified JS / Combine JS files:** فعال (توصیهٔ رسمی XTemos: «We suggest to Include minified CSS and Include minified JS and Combine JS files in the Theme Settings > Performance»).
- **Fonts & Icons → Load Google Fonts locally:** فعال. سند رسمی: «This improves performance, helps with GDPR compliance… fonts selected in the typography controls… are downloaded to your server.» در نسخه‌های اخیر «Local Google Fonts are now enqueued inline within a style tag.»
- **Icon font در برابر SVG:** SVG را ترجیح دهید و آیکون‌های اضافه را حذف کنید؛ کتابخانهٔ کامل آیکون بار سنگینی دارد.
- **Preload LCP image:** فعال روی صفحات کلیدی.
- **Lazy loading images:** فعال (به‌جز LCP).

### حذف مسیرهای WooCommerce / فروشگاه
چون WooCommerce نصب نمی‌شود، اغلب این مسیرها اصلاً بارگذاری نمی‌شوند. اما این ماژول‌ها را در Theme Settings غیرفعال نگه دارید تا کد فروشگاه لود نشود: AJAX shop/filters، **Wishlist**، **Compare**، **Quick view**، **Mini cart / side cart**، **Login sidebar**، **Promo popups / Floating blocks**. این‌ها عمدتاً در Theme Settings → Shop و بخش‌های مرتبط قرار دارند؛ عنصر Cart را از Header Builder حذف کنید. **نیازمند تصمیم/تست:** محل دقیق برخی سوییچ‌ها بین نسخه‌ها جابه‌جا می‌شود؛ پس از فعال‌سازی تم بررسی شود.

### Woodmart + Elementor
- ویجت‌های اختصاصی Woodmart ([XTemos] Elements) برای Elementor موجودند؛ حداقل استفاده را نگه دارید (سبک‌سازی) — نمایش دادهٔ جت‌انجین با ویجت‌های JetEngine/Elementor Pro انجام شود.
- WPBakery و دارایی‌های مربوطه نصب/فعال نشوند.

### هدر/فوتر Woodmart در برابر Elementor Pro Theme Builder
- **توصیهٔ رسمی XTemos:** از **Header Builder و layout builder خود Woodmart** استفاده کنید (ویجت‌ها و امکانات کامل تم).
- **ترجمهٔ چندزبانهٔ هدر Woodmart:** از طریق WPML → String Translation. سند رسمی: «The header builder has been translated in WPML → String translation… The header string consists of its ID.» گردش‌کار: برای هر زبان یک هدر جدا بسازید، شناسهٔ هدر (مثل `header_528910` از URL) را در String Translation (دامنهٔ `admin_texts_whb_main_header`) قرار دهید. منوها به‌صورت خودکار ترجمه/همگام می‌شوند. متن‌های جایگزین تم در `admin_texts_xts_woodmart_options` قابل ترجمه‌اند.
- **رفع مشکل رسمی:** اگر رنگ/تنظیمات هدر در سایت ترجمه‌شده درست نمایش داده نشد، در WPML → Settings → Custom Fields Translation → Show system fields، فیلد `_woodmart_whb_header` را روی **Copy** بگذارید و صفحهٔ اصلی زبان پیش‌فرض را دوباره ذخیره کنید.

### سازگاری Woodmart + WPML
WPML رسماً Woodmart را در دایرکتوری تم‌های سازگار دارد و XTemos مستندات اختصاصی ارائه می‌دهد: «WPML is the most popular multilingual plugin for WordPress and is fully compatible with WoodMart. Using WPML, you can translate your entire WoodMart website, including header, layouts, widgets, and even texts.»

### تایپوگرافی fa/ar/en در Woodmart
- Woodmart از طریق Theme Settings → Typography → **Custom Font** امکان بارگذاری فونت محلی (woff/woff2) را دارد. سند رسمی: «The theme allows adding any custom font via Theme Settings → Typography → Custom Font… Note that if you are going to replace Google fonts with your local fonts you need to use unique names for them.» پشتیبانی رسمی XTemos همین مسیر را برای فونت فارسی (Vazir/IRANSans) توصیه کرده است. (نکتهٔ رفع خطای آپلود: در صورت خطای امنیتی آپلود woff، افزودن `define('ALLOW_UNFILTERED_UPLOADS', true);` به wp-config.php.)
- **مالک واحد فونت:** برای اجتناب از بارگذاری دوگانه، فقط Woodmart مالک فونت باشد و Default Fonts در Elementor خاموش بماند.
- **فونت متفاوت برای هر زبان (مثلاً فونت لاتین برای /en/):** هیچ مکانیزم بومی رسمی «فونت‌به‌ازای‌زبان» در Woodmart یا WPML وجود ندارد. راهکار: CSS اختصاصی با سلکتور کلاس زبانی بدنه (که WPML می‌افزاید) در Theme Settings → Custom CSS یا استایل‌شیت فرزند. **نیازمند تصمیم/تست.**

---

## بخش ۶ — تصمیمات پیکربندی متقاطع

### مالکیت رنگ/تایپوگرافی سراسری
**Woodmart مالک** رنگ و فونت سراسری باشد (Theme Settings). در Elementor: Disable Default Colors + Disable Default Fonts فعال. دلیل: جلوگیری از بارگذاری دوگانهٔ فونت و تعارض استایل وقتی Woodmart + Elementor کنار هم‌اند.

### مالکیت قالب‌ها (تقسیم کار)
- **Listing Template‌های JetEngine** برای آیتم‌های تکرارشونده (کارت‌ها، فهرست‌های رابطه‌ای).
- **Single/Archive انواع‌پست** با Elementor Pro Theme Builder یا قالب‌های JetEngine.
- **هدر/فوتر** با Woodmart Header Builder.
- **layout builder فروشگاهی Woodmart استفاده نشود** (بدون WooCommerce).

### فهرست «غیرقابل‌بازگشت یا پرهزینه» (تجمیع)
زبان پیش‌فرض WPML؛ فرمت URL زبان + Use-directory؛ اسلاگ CPT؛ CPT در برابر CCT؛ Custom Meta Storage؛ Register separate DB table رابطه؛ Container در برابر Section؛ انتخاب سازندهٔ صفحهٔ Woodmart.

### ترتیب توصیه‌شده (وابستگی‌ها)
WPML (زبان + URL) → Woodmart (builder + فونت) → Elementor (فعال‌سازی Container **قبل از** ساخت قالب) → JetEngine (ماژول‌ها → CPT → Relations/storage → Query → Listing) → قالب‌ها → کش/CDN.

### تعارض‌ها/ناسازگاری‌های جاری (رسمی + انجمنی)
- **Element Caching × WPML/JetEngine:** خطر نمایش محتوای پویا/زبان اشتباه (سند رسمی Elementor: با dynamic tags کار نمی‌کند). خاموش نگه دارید.
- **JetEngine Relations × WPML:** باگ جابه‌جایی رابطه هنگام ترجمهٔ والد (errata رسمی WPML)؛ نگاشت خودکار شناسه مستند نشده؛ گردش‌کار دستی وابسته به ترتیب. **بزرگ‌ترین ریسک — پایلوت اجباری.**
- **CCT × WPML:** اصلاً ترجمه‌پذیر نیست (رسمی).
- **Custom Meta Storage × WPML:** تست‌نشده / گزارش مشکل انجمنی.
- **Woodmart Header × WPML:** نیاز به Copy کردن فیلد سیستمی `_woodmart_whb_header`.
- **کش (WP Rocket) × Elementor/Woodmart CSS:** XTemos هشدار می‌دهد «Optimize CSS delivery» و «Remove unused CSS» ممکن است FOUC/پرش ایجاد کنند («they may badly impact… by adding some jump and flashes of unstyled content»)؛ با احتیاط. Data Stores جت‌انجین (اگر روشن شود) با کش Session تعارض دارد. از Woodmart 7.0 گزینهٔ «WP Rocket delay JS exclusions» در Theme Settings → Performance → Plugins موجود است.

---

## جمع‌بندی مدیریتی (Bottom Line)

- **پایه‌های معماری همین حالا قفل شوند:** WPML با فارسیِ ریشه + دایرکتوری، همهٔ موجودیت‌های ترجمه‌پذیر به‌صورت **CPT** (نه CCT، چون CCT با WPML ترجمه‌پذیر نیست)، و تصمیم storage/relation قبل از هر محتوا.
- **دو ریسک بزرگ که باید پایلوت شوند:** (۱) رفتار **JetEngine Relations در زبان‌های ثانویه** — نگاشت خودکار شناسه مستند نشده و باگ جابه‌جایی هنگام ترجمهٔ والد وجود دارد؛ گردش‌کار دستی وابسته به ترتیب و در صورت لزوم الگوی `type="post-ids" action="copy"` روی فیلد متا. (۲) **Custom Meta Storage × WPML** برای فیلدهای ترجمه‌پذیر — تا زمان تأیید، از postmeta استاندارد استفاده کنید.
- **کش‌پذیری:** CSS Print Method = External، Element Caching سراسری خاموش، String Translation مبتنی بر .mo، فونت محلی از یک مالک واحد (Woodmart).
- **مواردی که باید تست/تصمیم‌گیری شوند** (علامت «نیازمند تصمیم/تست»): حالت Post Type Translation (fallback در برابر only-translated)، Timber/Twig برای هاب‌های بزرگ، فونت‌به‌ازای‌زبان با CSS، محل دقیق سوییچ‌های حذف WooCommerce در Woodmart 8.5، و پوشش `type="post-ids"` بر جدول رابطهٔ JetEngine.