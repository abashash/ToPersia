# راهنمای پیکربندی صفحه‌به‌صفحه ToPersia — Elementor 4.1.5 (معماری کلاسیک V3) و Woodmart 8.5

> این راهنما بر پایه مستندات رسمی (elementor.com/help، developers.elementor.com، xtemos.com/docs-topic، xtemos.com/forums، Google Search Central) تهیه شده است. هر موردی که در مستندات رسمی یافت نشد یا مختص نسخه فعلی قابل تأیید نبود، صریحاً با برچسب **«نیازمند تست مستقیم»** مشخص شده و حدس زده نشده است.

---

## ترتیب اجرا (Execution Order)

1. **بخش الف — Elementor Site Settings** به این ترتیب: Theme Style → Typography → Buttons → Images → Form Fields، سپس Settings → Layout → Background → Lightbox → Page Transitions → Site Identity → Additional Settings.
2. **بخش ب — Woodmart Theme Settings** به این ترتیب: General → Layout، سپس Search / Sticky navigation / Mobile bottom navbar / Cookie law info، سپس Page title → Footer → Performance → Maintenance، و در پایان Social profiles / API integrations / White label / Other.
3. **مواردِ پرخطر و تست‌محور را در انتها** انجام دهید: تست کد HTTP در Maintenance (۵۰۳)، خالی‌گذاشتن Background، و افزودن override دارک‌مود برای Page title/Footer.

### نکته بنیادی درباره تقدم استایل (باید قبل از شروع خوانده شود)
طبق مستند رسمی Elementor «Set a Theme Style»، Theme Style فقط یک **لایه fallback** برای تگ‌های HTML (مثل `<h1>`، `<body>`، `<label>`) است و «به‌صورت پیش‌فرض فقط روی عناصر غیرِ-Elementor اعمال می‌شود» (مثل فیلدهای فرم شخص‌ثالث یا تیترهای تم). Global Colors/Global Fonts که با CSS Variables کار می‌کنند، **بالاتر** از Theme Style قرار می‌گیرند و اولویت دارند. برای اعمال Theme Style روی خودِ ویجت‌های Elementor باید Default Colors و Default Fonts در Elementor → Settings غیرفعال شوند. در سایتی مثل ToPersia که تایپوگرافی و رنگ‌ها را Woodmart اداره می‌کند، نقش Theme Style عمدتاً پشتیبان است — پس مقادیر آن را دقیقاً هم‌تراز Woodmart قرار دهید تا در حالت fallback ناهماهنگی رخ ندهد.

---

# بخش الف — Elementor 4.1.5 (Site Settings کلاسیک، چون آزمایش‌های V4/Atomic خاموش‌اند)

## ۱) Theme Style → Typography
**دامنه کنترل (منبع: elementor.com/help — Set a Theme Style / How theme style and design system work together):** برخلاف تصور رایج، این صفحه فقط H1 نیست؛ رنگ و تایپوگرافیِ **Body/Paragraph**، همهٔ شش سطح تیتر **H1–H6**، و رنگ **Link / Link Hover** را کنترل می‌کند.

| فیلد | مقدار پیشنهادی | توجیه |
|---|---|---|
| Body → Family / Weight / Size / Line-height | Vazirmatn (نقش global «Text») / 400 / 16px / 1.8 | مطابق سیستم برند؛ 16px برای خوانایی فارسی/عربی مناسب است |
| Body → Text Color | #243E57 | رنگ متن برند |
| H1–H6 → Family / Weight | Vazirmatn (نقش global «Primary») / 700 | تیترها با وزن بولد |
| H1–H6 → Color | #243E57 | یکدستی با متن |
| اندازه‌های پیشنهادی | H1≈40px، H2≈32px، H3≈26px، H4≈22px، H5≈19px، H6≈17px | مقیاس تیتر با نیاز خوانایی خط فارسی/عربی؛ **این اعداد برند-محور و پیشنهادی‌اند، نه از مستند رسمی — قابل تنظیم** |

**تعامل و خطر تعارض با Woodmart Typography:** Woodmart از قبل Vazirmatn را ست کرده است. طبق پاسخ رسمی پشتیبانی XTemos در فروم («بهتر است از تنظیمات تم استفاده کنید نه Site Settings المنتور»)، برای محتوای native تم، **Woodmart برنده** است؛ برای ویجت‌های Elementor، مقدار ویجت یا Global Font برنده است. توصیه: تعریف دوگانهٔ متناقض ایجاد نکنید؛ مقادیر Theme Style را عیناً هم‌تراز Woodmart بگذارید.

## ۲) Theme Style → Buttons
**فیلدهای رسمی (منبع: elementor.com/help — Set a Theme Style):** Typography، Text Color، Background Color، Border Type (None/Solid/Double/Dotted/Dashed/Groove)، Border Radius، Box Shadow، Padding؛ و در تب Hover: Text Color، Background Color، Border Color، Transition Duration (ms).

| فیلد | مقدار برند | توجیه |
|---|---|---|
| Background Color | #0E4681 | آبی اصلی برند |
| Text Color | #FFFFFF | کنتراست روی آبی |
| Typography | Vazirmatn، Weight 700 | یکدستی برند |
| Border Radius | ≈6px (نیمه‌گرد) | هماهنگ با `btns_default_style: "semi-rounded"` وودمارت |
| Padding | ≈12px عمودی / 28px افقی | اندازهٔ معقول کلیک |
| Hover → Background | #083461 | آبی تیره‌تر برند |
| Hover → Text | #FFFFFF | حفظ کنتراست |
| Transition Duration | ≈300ms | انتقال نرم |

**دامنهٔ اعمال:** طبق مستند رسمی، Theme Style Buttons به‌صورت پیش‌فرض فقط روی دکمه‌های غیرِ-Elementor اعمال می‌شود؛ برای اعمال روی ویجت دکمهٔ Elementor باید Default Colors/Fonts غیرفعال باشد، و ویجت‌هایی که رنگ صریح دارند از آن ارث نمی‌برند. **«نیازمند تست مستقیم»** برای رفتار دقیق در ترکیب با CSS دارک‌مود موجود.

## ۳) Theme Style → Images
**فیلدهای رسمی (منبع: elementor.com/help — Set a Theme Style):** Border Type (در دو حالت Normal و Hover)، Border Radius (Normal و Hover)، Opacity، Box Shadow و CSS Filters.
**توصیه:** پیش‌فرض‌ها کافی‌اند؛ در صورت تمایل به یکدستی، Border Radius ≈6–8px هماهنگ با دکمه‌ها. این بخش حیاتیِ رنگی نیست.

## ۴) Theme Style → Form Fields (نیازمند Elementor Pro Forms)
**فیلدهای رسمی (منبع: elementor.com/help — Set a Theme Style):** Typography، Text Color، Background Color، Border Type، Border Radius، Box Shadow، Transition Duration (ms) (فقط Focus)، Padding؛ و برای Label: Color و Typography.

| فیلد | مقدار برند |
|---|---|
| Border Color | #70869E |
| Focus (border فعال) | #0E4681 |
| Background Color | #FFFFFF |
| Text Color | #243E57 |
| Label Color | #243E57 |
| Border Radius | ≈6px |

حتی بدون فرم فعلی، این تنظیم باعث می‌شود هر Form Widget آیندهٔ Elementor Pro خودکار با برند هماهنگ شود.

## ۵) Settings → Site Identity
**دامنه (منبع: elementor.com/help — Configure Site Settings):** Site Name، Site Description، Logo، Favicon.
**منبع حقیقت:** این مقادیر با WordPress Customizer و لوگوی Woodmart هم‌پوشانی دارند. **توصیهٔ قطعی:** منبع حقیقت را **WordPress Customizer / Woodmart** قرار دهید و Site Identity المنتور را **خالی/پیش‌فرض** بگذارید تا از ورود Elementor از WordPress ارث ببرد و لوگوی متناقض ایجاد نشود. **«نیازمند تست مستقیم»** برای تأیید اینکه خالی‌گذاشتن باعث override ناخواسته نمی‌شود.

## ۶) Settings → Background ⚠️ (بحرانی)
**دامنه (منبع: elementor.com/help — Configure Site Settings):** رنگ/تصویر/گرادیان پس‌زمینهٔ سراسری که به تگ `body` اعمال می‌شود، به‌همراه Mobile Background جداگانه.
**تعارض با دارک‌مود:** چون سیستم دارک‌مود سفارشی از قبل `body{ background-color }` را با `!important` هدف گرفته، **توصیهٔ قطعی: این بخش را کاملاً روی Classic / none خالی بگذارید.** تنظیم رنگ اینجا یک قاعدهٔ رقیب می‌سازد؛ هرچند قاعدهٔ `!important` سفارشی معمولاً از نظر specificity برنده می‌شود، اتکا به این «جنگ اولویت» ریسک‌دار است. خالی‌گذاشتن امن‌ترین حالت است.

## ۷) Settings → Layout ⚠️ (بحرانی — هماهنگی عرض)
**فیلدهای رسمی (منبع: elementor.com/help — Control your global layout settings):** Content Width (پیش‌فرض **1140px**، تأییدشده از Knowledge Base)، Widgets Space (پیش‌فرض **20px**، تأییدشده)، Page Title Selector، Default Page Layout، و Stretched Section Fit To.
**Breakpointها:** تعریف breakpointها **در این صفحه نیست**؛ در Experiments / `additional_custom_breakpoints` قرار دارد.

**هماهنگی با Woodmart:** طبق مستند رسمی XTemos (Explanation of Elementor page templates)، wrapper پیش‌فرض Woodmart **1222px** است و «همهٔ محتوای Elementor داخل این wrapper محدود می‌شود، صرف‌نظر از Content Width المنتور» — مگر از قالب صفحهٔ «Elementor Full Width» استفاده شود. اگر گزینهٔ «Elementor sections negative margin» در Woodmart فعال باشد، عرض کانتینرها خودکار 1222px می‌شود.
**توصیه:** Content Width المنتور را روی **1222px** بگذارید تا با هدر/فوتر Woodmart هم‌تراز شود و ناهماهنگی بصری بین نواحی ساخته‌شده با تم و با المنتور رخ ندهد.

## ۸) Settings → Lightbox
**فیلدهای رسمی (منبع: elementor.com/help — Adjust the Lightbox settings):** Image Lightbox (روشن/خاموش سراسری)، Background Color (رنگ + Opacity)، UI Color، UI Hover Color، Text Color، Toolbar Icons Size، Navigation Icons Size، و توگل‌های Counter / Fullscreen / Zoom / Share.
**توصیه:** Background تیرهٔ نیمه‌شفاف (مثلاً مشکی با Opacity≈0.9)، UI Color سفید. حیاتیِ رنگیِ برند نیست.
**هشدار:** باگ‌های شناخته‌شدهٔ Elementor در GitHub (#24704 و #28707) نشان می‌دهد رنگ‌های Lightbox گاهی توسط CSS پایه با specificity بالاتر override می‌شوند — پس **«نیازمند تست مستقیم»** در فرانت‌اند برای تأیید اعمال رنگ.

## ۹) Settings → Page Transitions
**ماهیت (منبع: elementor.com/help — Create page transitions for your site):** افکت انیمیشنی هنگام جابه‌جایی بین صفحات + Preloader؛ فیلدها شامل Animation، Animation Duration (ms)، Preloader delay (ms). به‌صورت پیش‌فرض روی همهٔ لینک‌های داخلی اعمال می‌شود و با Custom Attribute `data-e-disable-page-transition|true` برای لینک خاص قابل غیرفعال‌سازی است.
**توصیه: خاموش بماند.** برای سایت مرجعِ محتوامحور با ۲۱٬۶۰۰+ موجودیت که Core Web Vitals در اولویت است، انتقال‌های نمایشی ارزش افزودهٔ کمی دارند. یک تاپیک رسمی پشتیبانی WordPress.org (پلاگین LiteSpeed Cache) نشان می‌دهد با فعال‌بودن caching، انیمیشن انتقال می‌تواند «ناهموار یا freeze» شود — ریسک تعارض با caching برنامه‌ریزی‌شده و محتوای AJAX-محورِ JetEngine.

## ۱۰) Settings → Additional Settings
این لینک به صفحهٔ **Elementor → Settings** در WP Admin می‌رود. فیلدهای فعلی (منبع: elementor.com/help — Settings):
- **General/Style:** CSS Print Method (توصیه: **External**)، Optimized Image Loading (روشن)، Optimized Gutenberg Loading (روشن)، Disable Default Colors، **Disable Default Fonts** (روشن نگه دارید — چون فونت‌ها محلی از طریق Woodmart سرو می‌شوند و Google Fonts المنتور غیرفعال است)، Improve Elementor / Usage Data Tracking.
- **Integrations:** reCAPTCHA و reCAPTCHA v3، Google Fonts (disable)، Font Awesome Pro، TypeKit، Switch Editor Loader Method، Enable Unfiltered File Uploads (فقط با آگاهی از ریسک امنیتی SVG/JSON).

---

# بخش ب — Woodmart (XTemos) 8.5 Theme Settings

## ۱) General → Layout
**فیلدها (منبع: XTemos forums — «change width from Theme Settings → General Layout»):** Site width با گزینه‌های Wide/Full، **Boxed**، **Boxed (hidden overflow)** و **Custom** (px).
**توصیه:** حالت **Wide/Full** (نه Boxed) برای سایت مرجع مدرن. عرض پیش‌فرض wrapper = **1222px** (منبع رسمی: xtemos.com/docs-topic/explanation-of-elementor-page-templates/). همین صفحه محلِ تأیید/تغییر عرض سراسری است.

## ۲) General → Header banner
نوار اعلان بالای هدر اصلی.
**توصیه:** فعلاً **غیرفعال/خالی** چون اعلان یا کمپین فعالی وجود ندارد؛ بعداً از همین‌جا فعال شود.
**«نیازمند تست مستقیم»:** فهرست دقیق فیلدها (متن، پس‌زمینه، لینک، dismiss، زمان‌بندی) در مستند رسمی به‌صورت field-by-field منتشر نشده است.

## ۳) General → Promo popup
سازندهٔ پاپ‌آپ تبلیغاتی (ویرایش در xtemos.com/docs — «How to edit the promo popup»).
**توصیه: غیرفعال.** برای دایرکتوری مرجع مناسب نیست؛ پاپ‌آپ مزاحم/interstitial تجربهٔ کاربری و سیگنال‌های SEO را تضعیف می‌کند.

## ۴) General → Age verify popup
گیت سنی (مخصوص سایت‌های الکل/بزرگسال).
**توصیه: غیرفعال.** کاملاً بی‌ربط به سایت گردشگری/فرهنگی ایران.

## ۵) General → Cookie law info
**فیلدهای موجود (منبع: xtemos.com/docs-topic/gdpr-compliant/ و forums):** متن پیام، متن دکمهٔ Accept، لینک به سیاست حریم خصوصی، موقعیت نمایش.
**محدودیت GDPR (مهم):** طبق پاسخ‌های رسمی پشتیبانی XTemos، این ابزار صرفاً یک «notice» ساده است — «دکمهٔ Reject، پنل Settings و toggle دسته‌بندی کوکی ندارد» و XTemos اعلام کرده «برنامه‌ای برای یکپارچگی با Google Consent Mode ندارد». چون مخاطب سایت بین‌المللی (شامل EU) است، **توصیه: به‌جای اتکای صرف به notice وودمارت از یک افزونهٔ Consent Management اختصاصی (مثل Complianz یا CookieYes) استفاده کنید** که رضایت دسته‌بندی‌شده و مسدودسازی پیش از رضایت را پشتیبانی می‌کند (XTemos خودش در فروم Complianz/CookieYes را ارجاع داده است). متنِ هر زبان نیازمند **WPML String Translation** است، و یک صفحهٔ **Privacy Policy** باید موجود و لینک شود.

## ۶) General → Mobile bottom navbar
**فیلدهای رسمی (منبع: xtemos.com/docs-topic/adding-menu-to-the-site/):** **Enable Sticky navbar**، **Navbar labels** (نمایش/مخفی لیبل زیر آیکون‌ها)، و انتخاب یک منو از Appearance → Menus. آیکون‌های شاپ (cart/wishlist) به WooCommerce وابسته‌اند.
**توصیه:** چون WooCommerce نصب نیست، آیتم‌های شاپ ظاهر نمی‌شوند؛ می‌توانید با یک منوی سفارشی (Home / Search / Menu) فعال کنید تا UX موبایلِ سایت محتوایی بهتر شود.
**«نیازمند تست مستقیم»:** تأیید اینکه بدون WooCommerce دقیقاً کدام آیکون‌ها در دسترس‌اند در مستند رسمی صریح نیست.

## ۷) General → Search
**فیلدهای رسمی:** نوع جستجو **AJAX** (`search_ajax`)، post typeِ قابل جستجو (`search_post_type`)، و در Header Builder گزینهٔ **«Search with AJAX»**. طبق کد رسمی تم، انواع مجاز AJAX فقط `array('post','product','portfolio')` است.
**محدودیت حیاتی:** طبق پاسخ رسمی XTemos، صفحات WordPress و **CPTهای دلخواه (JetEngine) با AJAX Search وودمارت جستجو نمی‌شوند** — «Search only works for Posts, Portfolios and products».
**توصیه:** AJAX live search را برای **Post** فعال کنید (UX بهتر برای سایت مرجع بزرگ). برای گنجاندن ۴۲ نوع موجودیت JetEngine در نتایج، پس از ساخت CPTها به افزونه‌ای مثل **Relevanssi** یا سفارشی‌سازی کد نیاز خواهید داشت — این را به‌عنوان تنظیمِ آینده علامت بزنید.

## ۸) General → Sticky navigation
**فیلد رسمی (منبع: xtemos.com/docs-topic/sticky-navigation/):** انتخاب یک منو از Appearance → Menus؛ توجه که هر آیتم این «sticky navbar» باید تصویر داشته باشد. این با **Sticky Header** متفاوت است.
**توصیه: هدر چسبان را فعال کنید** — استاندارد UX مدرن برای سایت طولانی‌اسکرول با hub pageهای دارای ۳۰۰+ آیتم. مسیر صحیح: **Header Builder → Global Header Settings → Sticky header** با فیلدهای رسمی: Sticky header shadow، Hide when scrolling down، Sticky effect (**Stick on Scroll** یا **Slide after scrolled down**) و Sticky header clone (منبع: xtemos.com/docs-topic/global-header-settings/).

## ۹) Page title
**فیلدهای رسمی (منبع: xtemos.com/docs-topic/options-for-pages/ و پاسخ‌های رسمی فروم):** Page title background color، Text color for the title (**Inherit / Light / Dark**)، Page title size (**Default / Small / Large**)، toggle breadcrumbs، و alignment. breadcrumbsِ صفحات در **Theme Settings → Page title** و breadcrumbsِ صفحهٔ محصول در Single product تنظیم می‌شود.
**توصیه:** breadcrumbs را **فعال** کنید — برای SEO/UX در سلسله‌مراتب عمیق (Country > Province > City > Attraction) حیاتی است. پس‌زمینه: رنگ برند یا خنثای ملایم.
**⚠️ هشدار دارک‌مود:** «Page title background color» یک رنگ hardcoded است؛ اگر آن را ست کنید باید یک **override دارک‌مود** برای آن به CSS سفارشی موجود اضافه شود، وگرنه در حالت تاریک ناهماهنگ می‌شود.

## ۱۰) Footer
**فیلدهای رسمی (منبع: xtemos.com/docs-topic/set-up-footer/):** Footer layout (چند ستون پیش‌فرض — مثلاً «Three Columns» که ۳ widget area در Appearance → Widgets می‌سازد)، Footer Content (**Widgets** یا **HTML Block**)، **Scroll to top button**، **Footer text color** (گزینهٔ **Dark / Light** — این توگلِ per-footer جدا از تنظیم سراسری `dark_version` است)، **Copyrights** (Copyrights layout با ۱ ستون مرکزی یا ۲ ستون + Content area)، و **Prefooter**.
**توصیه ساختار:** ستون‌ها برای (۱) دربارهٔ ما/لینک‌های سریع، (۲) مقاصد محبوب بر اساس دسته، (۳) یادآور language switcher، (۴) آیکون‌های اجتماعی؛ به‌همراه Copyrights.
**⚠️ هشدار دارک‌مود:** رنگ پس‌زمینهٔ فوتر باید با override‌های `.wd-footer` موجود در CSS دارک‌مود هماهنگ شود؛ در صورت نیاز Footer text color = **Dark** انتخاب شود.

## ۱۱) Social profiles
**فیلد (منبع: xtemos.com/docs-topic/theme-settings-panel/):** صرفاً فیلدهای URL برای شبکه‌های اجتماعی که در آیکون‌های هدر/فوتر استفاده می‌شوند.
**توصیه:** فقط حساب‌های واقعی ToPersia وارد شوند — **نیازمند ورودی کاربر است و قابل حدس نیست** (این یک تصمیم پیکربندی نیست، بلکه دادهٔ کاربر).

## ۱۲) API integrations
**فیلدهای رسمی (منبع: XTemos forums — «insert the key into Theme Settings → API connections» + مستندات جانبی):** Google Maps API key، Mailchimp، Instagram API، Facebook/Google login، reCAPTCHA.
**توصیه:** فعلاً خالی. اما **Google Maps API key** را هنگام ساخت نقشه‌های موقعیتِ دستهٔ G (جغرافیا) بازبینی کنید — برای سایت گردشگریِ جغرافیامحور بسیار مرتبط است.
**توجه فنی:** المان نقشهٔ Woodmart فقط با **WPBakery** در دسترس است، نه با Elementor — پس برای نقشه در صفحات Elementor باید از ویجت Google Maps خود Elementor استفاده کنید.

## ۱۳) Performance (نسخهٔ فعلی 8.5)
ساختار زیربخش‌ها طبق مستند رسمی (theme-settings-panel، به‌روزرسانی ۲۷ اوت ۲۰۲۵): **CSS · JS · Fonts & Icons · Lazy Loading · Plugins · Preloader · Other** (به‌علاوهٔ **LCP** و **Image Optimizer**).

**فیلدهای با نامِ دقیقِ تأییدشده (رسمی):**
- **Load Google Fonts locally** (Fonts & Icons): از قبل فعال — نگه دارید. در 8.5 به‌صورت inline داخل style tag لود می‌شود (منبع: xtemos.com/docs-topic/loading-google-fonts-locally/ و بلاگ عملکرد 8.5).
- **"font-display" for Google fonts and icons** و **"font-display" for icon fonts**: مقدار **Swap**.
- **Font Awesome library** با گزینهٔ **Always use** (Fonts & Icons).
- **Lazy loading for images** و **Lazy loading for background images**: فعال؛ **Offset** = ۱۰۰–۲۰۰px؛ **Appearance effect** = **Fade**؛ **Upload a custom placeholder image** (اختیاری)؛ **Disable native WordPress lazy loading** طبق نیاز (منبع: xtemos.com/docs-topic/image-lazy-loading/، به‌روزرسانی ۲۱ اکتبر ۲۰۲۵).
- **Preload LCP Image** (زیربخش LCP): فعال (حالت Automatic یا Manual) — برای Core Web Vitals مفید (منبع: xtemos.com/docs-topic/explanation-of-preload-lcp-image-option/).
- **Inline styles size limit** (CSS — جدید در 8.5): مقدار پیش‌فرض را نگه دارید (منبع: بلاگ Performance improvement in WoodMart 8.5).
- **Remove jQuery Migrate** (JS): طبق توصیهٔ رسمی XTemos پیش‌فرض **غیرفعال** بماند مگر پس از تست.
- **Disable Gutenberg styles** (CSS): اگر Gutenberg برای محتوا استفاده نمی‌شود قابل فعال‌سازی — **«نیازمند تست مستقیم»** برای اطمینان از نشکستن بلاک‌های وودمارت.
- **Image Optimizer**: کیفیت (quality) + تولید WebP (نیازمند افزونهٔ Image Optimizer و لایسنس فعال؛ منبع: xtemos.com/docs-topic/images-optimizer/).

**تغییر مهم نسبت به راهنمای قدیمی:** گزینه‌های قدیمی **«Include minified CSS/JS» / «Combine JS files»** در نسخهٔ فعلی **حذف/منسوخ** شده‌اند؛ تم اکنون فایل‌های از پیش minify‌شده سرو می‌کند و combine به caching plugin واگذار شده است. (پس توصیهٔ قدیمیِ «minify/combine در پنل تم» دیگر معتبر نیست.)

**مواردِ «نیازمند تست مستقیم» (در مستند رسمی نسخهٔ فعلی تأیید نشد):** توگل مستقلِ SVG-vs-font، توگل native Defer/Delay JS (به‌طور رسمی به WP Rocket/LiteSpeed واگذار می‌شود؛ فقط فیلد «WP Rocket delay JS exclusions» در زیربخش Plugins تأیید شده)، پاک‌سازی database/transient، توگل Emoji script، و «Remove unused CSS/JS» بومی.

## ۱۴) Maintenance ⚠️ (بحرانی — SEO)
**فیلدهای رسمی (منبع: xtemos.com/docs-topic/theme-settings-panel/ و forums):** فعال/غیرفعال maintenance mode **بدون افزونه**، انتخاب یک صفحهٔ سفارشی به‌عنوان maintenance page (با template «Maintenance»)، و **Access key** برای bypass (افزوده‌شده در 7.1؛ فقط کلید تعریف می‌شود مثل `?key=...` نه URL کامل).

**⚠️ هشدار حیاتی دربارهٔ کد ۵۰۳:** مستندات رسمی XTemos **تأیید نمی‌کنند** که maintenance mode وودمارت کد HTTP **503** برمی‌گرداند یا 200. این نکته برای SEO حیاتی است:
- طبق پست رسمی **Google Search Central Blog «How to deal with planned site downtime»** (ژانویه ۲۰۱۱، نویسندگان Tomer Honen و Kaspar Szymanski): *«it's better to return a 503 HTTP result code (Service Unavailable) which tells search engine crawlers that the downtime is temporary… the length of the downtime… can be specified in an optional Retry-After header, which Googlebot may use to determine when to recrawl the URL.»*
- طبق **Yoast (HTTP 503 site maintenance SEO)**: صفحه‌ای که با وجود عدم‌دسترسی کد **200** برمی‌گرداند در Google Search Console به‌عنوان **soft 404** تلقی می‌شود؛ و هشدار می‌دهد که *«the cache isn't properly passing on the 503 status, so please make sure you test this properly before actively using this on the live version»* — یعنی لایهٔ caching ممکن است 503 را درست منتقل نکند.
- طبق **Gary Illyes** (تحلیل‌گر Google Webmaster Trends) در SEO Office Hours، به‌نقل از Search Engine Journal: *«Serving a 503 status code for an extended period of time will cause a decrease in crawl rate. Fortunately for you, 10-15 minutes every now and then is not 'extended' by any means, so you should be fine.»* — یعنی 503 کوتاه‌مدت بی‌خطر است اما 503 طولانی (روزها/هفته‌ها) خطر deindex دارد.

**اقدام لازم — «نیازمند تست مستقیم»:** با ابزار بررسی هدر HTTP (یا گزارش Google Search Console → URL Inspection) کدِ بازگشتیِ صفحهٔ maintenance وودمارت را چک کنید. **اگر 200 است**، برای دورهٔ ساختِ طولانیِ فعلی از یک راهکار 503 با هدر Retry-After استفاده کنید (افزونهٔ maintenance که 503 می‌دهد، یا تنظیم سطح سرور)، تا صفحهٔ maintenance به‌اشتباه ایندکس نشود.

## ۱۵) White label
**دامنه (منبع: xtemos.com/docs — rebrand-theme-white-label):** مخفی/جایگزینی برندینگ «WoodMart» و «Xtemos» شامل White label toggle، Theme name، Options logo (۵۰×۵۰)، Sidebar icon logo (۲۰×۲۰)، Dashboard title، Intro text، Appearance screenshot، Theme license tab، و Dummy Content tab. هدفِ این قابلیت: آژانس‌هایی که تم را به کلاینت می‌فروشند.
**توصیه: رد شود (Skip).** برای سایت شخصیِ solo بی‌ربط است — این ارزیابی با توضیح رسمیِ هدف قابلیت تأیید می‌شود.

## ۱۶) Other
**فیلدهای تأییدشده (منبع: xtemos.com/docs-topic/manual-setup/):** **Current page builder** (Elementor / Gutenberg / WPBakery — باید **Elementor** انتخاب شود)، **Gutenberg blocks**، **Gutenberg editor for products**.
**«نیازمند تست مستقیم»:** سایر فیلدهای احتمالی (toggle مربوط به schema.org، back-to-top، جایگاه preloader، تنظیمات خاص RTL) در مستند رسمیِ «Other» به‌صورت field-by-field برای نسخهٔ 8.5 فهرست نشده‌اند و باید مستقیماً در پنل بررسی شوند.

---

# هشدارهای بحرانی (Critical Warnings)

1. **Background (Elementor) را خالی بگذارید.** تنظیم رنگ/تصویر در Settings → Background با قاعدهٔ `!important` سیستم دارک‌مود روی `body` تعارض می‌سازد؛ خالی‌گذاشتن تنها حالت امن است. *(برگشت‌ناپذیر نیست، اما منشأ باگ بصریِ سخت‌ردیابی.)*

2. **کد HTTP در Maintenance (۵۰۳) — بحرانی‌ترین ریسک SEO.** مستندات رسمی XTemos نمی‌گویند maintenance وودمارت 503 می‌دهد یا 200. حتماً هدر HTTP را تست کنید؛ اگر 200 است، صفحهٔ maintenance ممکن است به‌عنوان محتوای واقعی/soft 404 ایندکس شود و در دورهٔ ساختِ طولانی به رتبه آسیب بزند. راهکار درست: 503 + هدر Retry-After.

3. **Page title و Footer در دارک‌مود.** «Page title background color» و رنگ پس‌زمینهٔ فوتر مقادیر hardcoded هستند؛ هر رنگی که ست کنید باید override دارک‌مود متناظر (`.wd-footer` و سلکتور page-title) به CSS سفارشی موجود اضافه شود.

4. **Search + JetEngine CPT.** AJAX Search وودمارت فقط post/product/portfolio را پوشش می‌دهد؛ برای جستجوی ۴۲ نوع موجودیتِ CPT پس از ساخت، باید Relevanssi یا سفارشی‌سازی اضافه شود (تنظیم آینده).

5. **هماهنگی عرض ۱۲۲۲px.** Content Width المنتور را با ۱۲۲۲px (عرض wrapper وودمارت) هماهنگ کنید تا هدر/فوتر و محتوای Elementor هم‌تراز شوند؛ مقدار پیش‌فرض Elementor برابر ۱۱۴۰px است و ناهماهنگی ایجاد می‌کند.

6. **Google Maps API key** را هنگام ساخت نقشه‌های دستهٔ جغرافیا فراموش نکنید (و توجه: المان نقشهٔ خود وودمارت فقط با WPBakery کار می‌کند، نه Elementor).

7. **Cookie consent برای EU.** notice داخلی وودمارت برای GDPR کامل نیست (فاقد Reject/دسته‌بندی/Consent Mode)؛ برای مخاطب بین‌المللی از افزونهٔ Consent Management اختصاصی استفاده کنید و صفحهٔ Privacy Policy را بسازید و لینک کنید (متن هر زبان با WPML String Translation).