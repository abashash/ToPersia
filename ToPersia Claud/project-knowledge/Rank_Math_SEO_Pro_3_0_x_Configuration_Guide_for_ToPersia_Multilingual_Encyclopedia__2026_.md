# راهنمای کاملِ تنظیم‌به‌تنظیم Rank Math SEO **PRO** (شاخهٔ ۳.۰.x، نسخهٔ ۲۰۲۶) برای پروژهٔ چندزبانهٔ «ToPersia»

> این راهنما را کنار پنجرهٔ مدیریت وردپرس باز کنید و مرحله‌به‌مرحله جلو بروید: اول فعال‌سازی لایسنس، سپس ویزارد نصب، سپس هر صفحهٔ ادمین به همان ترتیبی که در منوی Rank Math ظاهر می‌شود. موارد مخصوص Pro با نشان **[PRO]** مشخص شده‌اند. هدف نهایی: بیشترین قابلیت استناد برای موتورهای جستجو و موتورهای پاسخ AI (SEO + GEO + AEO)، بدون آنکه Rank Math با خروجی JSON-LD سفارشیِ افزونهٔ core شما تداخل کند.

---

## TL;DR (سه نکتهٔ اصلی)

- **سه ماژول باید خاموش بمانند: Schema، Analytics و Local SEO.** خاموش‌کردن ماژول Schema به‌تنهایی کافی **نیست**، چون شِمای Organization/Person از ماژول Local SEO می‌آید و شِمای WebSite/Breadcrumb از مسیرهای دیگر؛ بنابراین باید هم این دو ماژول را خاموش کنید و هم هر سه فیلتر سفارشیِ خودتان (`rank_math/json_ld` → آرایهٔ خالی، `breadcrumbs_enabled` → false، `disable_search` → true) را نگه دارید. مؤثرترین اهرم، همان فیلتر `rank_math/json_ld` است که طبق تیم پشتیبانی Rank Math «تمام خروجی JSON-LD افزونه را غیرفعال می‌کند».
- **ماژول Analytics بزرگ‌ترین تصمیم Pro است و باید خاموش شود.** جدول‌های `rank_math_analytics_*` روی سایت بزرگ به‌شدت رشد می‌کنند — طبق تیکت رسمی پشتیبانی Rank Math، حجم پایگاه دادهٔ Analytics «به‌ویژه در سایت‌های پرمحتوا از ۱۰۰ مگابایت فراتر می‌رود و ما داده‌ها را دوبل ذخیره می‌کنیم»؛ کاربران حجم‌های ۱۷۶ و حتی بیش از ۳۵۰ مگابایت را گزارش کرده‌اند. در مقیاس ۱۹۴٬۰۰۰ پست × ۹ زبان این تورم فاجعه‌بار است؛ داده را مستقیم از Google Search Console بگیرید.
- **عنوان و توضیح متا را با متغیر سفارشیِ ثبت‌شده از فیلد JetEngine بخوانید، نه با `%customfield()%`.** خودِ Rank Math تأیید کرده که «اگر از JetEngine استفاده می‌کنید، ممکن است `%customfield%` آن‌طور که انتظار دارید کار نکند و باید یک متغیر سفارشی بسازید».

---

## Key Findings (یافته‌های کلیدی)

1. **معماری Schema شما با Rank Math سازگار است، اما به دو ماژولِ خاموش + سه فیلتر نیاز دارد.** خاموش‌کردن صرفِ ماژول Schema، شِمای سراسری (Organization/Person/WebSite/Sitelinks Search/Breadcrumb) را حذف نمی‌کند. Schema Templates ([PRO]) فقط وقتی ماژول Schema روشن باشد شلیک می‌کنند؛ با خاموش‌بودن ماژول، کل این قابلیت غیرفعال است.
2. **فعال‌سازی Pro بدون کلید لایسنس است و از طریق اتصال حساب Rank Math انجام می‌شود؛** فعال‌کردن روی کلون Duplicator یک اسلات مصرف می‌کند و باید مدیریت شود.
3. **hreflang و سایت‌مپِ چندزبانه را WPML تولید می‌کند، نه Rank Math؛** برای سازگاری کامل به افزونهٔ «WPML SEO glue» نیاز دارید.
4. **بات‌های جستجوی AI را باز بگذارید تا استناد بگیرید؛** جدایی «بات آموزش» از «بات جستجو» تصمیم مستقل است.
5. **تعارض‌های مستند Rank Math Pro با Elementor** (فریز ادیتور، خطای HTTP 500) واقعی‌اند و راه‌حل‌شان به‌روزنگه‌داشتن نسخه‌هاست.

---

## Details (جزئیات مرحله‌به‌مرحله)

### مرحلهٔ ۰ — پیش از هر کاری: پشتیبان‌گیری و نکتهٔ حیاتی Elementor
با Duplicator Pro یک بکاپ کامل بگیرید. **علت:** تنظیمات Rank Math در دیتابیس ذخیره می‌شود و پیش از تغییرات گسترده باید نقطهٔ بازگشت داشته باشید.

نسخهٔ Rank Math Pro را به آخرین نسخهٔ پایدار شاخهٔ ۳.۰.x به‌روزرسانی کنید (نمونه‌های مستند در ۲۰۲۶: 3.0.107 در ۱۱ فوریه، 3.0.113 در ۱۴ مه، تا 3.0.119). **علت:** تعارض‌های Elementor (فریز ادیتور در 3.0.54، خطای ۵۰۰ در 3.0.48، خطای کلاس `Elementor\Core\Schemes\Typography` در ویجت Breadcrumbs) در نسخه‌های قدیمی رخ می‌دهند و پشتیبانی Rank Math صریحاً می‌گوید «نسخهٔ شما بسیار قدیمی است؛ به‌روزرسانی کنید».

---

### مرحلهٔ ۱ — فعال‌سازی لایسنس و حساب (بخش B)

| مورد | مقدار توصیه‌شده | علت (یک خطی) |
|---|---|---|
| روش فعال‌سازی | اتصال حساب Rank Math (بدون کلید لایسنس) | Rank Math از کلید استفاده نمی‌کند؛ فعال‌سازی از طریق `Rank Math SEO → Dashboard → Help → Connect Now` است. |
| ترتیب نصب | اول افزونهٔ Free، سپس آپلود zip افزونهٔ Pro | Pro روی Free سوار می‌شود؛ غیرفعال‌کردن Free، Pro را هم غیرفعال می‌کند. |
| انتخاب اشتراک | از منوی کشویی PRO را انتخاب کنید و «OK, ACTIVATE NOW» | اگر چند اشتراک دارید باید اشتراک درست انتخاب شود وگرنه امکانات Pro ظاهر نمی‌شوند. |
| دادهٔ ارسالی | تأیید اشتراک + (اختیاری) Usage Tracking ناشناس | برای فعال‌سازی فقط تأیید اشتراک لازم است؛ Usage Tracking کاملاً اختیاری است. |
| تعداد سایت‌های مجاز | طبق صفحهٔ رسمی قیمت‌گذاری Rank Math، پلن PRO روی «تعداد نامحدودی وب‌سایت شخصی» قابل استفاده است، مشمول سیاست استفادهٔ منصفانه؛ نمی‌توانید آن را روی سایت مشتری یا اشتراکی با دیگران به کار ببرید. PRO رهگیری کلمهٔ کلیدی را نیز به ۵۰۰ تا ۱٬۰۰۰ کلیدواژه محدود می‌کند. | ToPersia سایت شخصیِ شماست، پس مجاز است. |
| **staging (کلون Duplicator)** | روی کلون **وصل نشوید**؛ یا اگر لازم شد، سایت staging را بعداً از بخش **Client Management** در ناحیهٔ حساب Rank Math حذف کنید | فعال‌سازی روی هر دامنه یک اسلات در فهرست سایت‌های حساب اشغال می‌کند؛ روی کلون می‌توانید بدون اتصال حساب هم کار کنید. |
| پس از رفتن به لایو | داده Analyt  استیجینگ را پاک و سایت لایو را دوباره در GSC تأیید کنید | آدرس‌ها بین staging و لایو فرق دارند. |

---

### مرحلهٔ ۲ — ویزارد نصب (بخش C، تفاوت‌های Pro)

| گام ویزارد | مقدار | علت |
|---|---|---|
| حالت (Mode) | **Advanced** | همهٔ تنظیمات را نشان می‌دهد؛ حالت **Custom** ([PRO]) فقط برای بارگذاری فایل تنظیمات ذخیره‌شده است و اکنون لازم نیست. |
| Compatibility Check | بگذارید اجرا شود | افزونه‌های متعارض (SEO دیگر) را نشان می‌دهد. |
| Import | اگر افزونهٔ SEO قبلی نداشتید، Skip | داده‌ای برای ایمپورت وجود ندارد. |
| Your Site → Your Website is | **Organization** | ToPersia یک دایرةالمعارف/سازمان است، نه بلاگ شخصی؛ اما چون شِمای شما سفارشی است، این فقط برای فرم است و خروجی‌اش را با فیلترها خنثی می‌کنیم. |
| Website Name / Alternate Name | نام سایت و مخفف | Google از `WebSite.name` برای Site Name استفاده می‌کند (هرچند شِمای نهایی را افزونهٔ core شما می‌دهد). |
| Logo / Person or Organization | پر کنید | بی‌ضرر؛ خروجی JSON-LD آن با فیلتر حذف می‌شود. |
| **Analytics** | **Connect Google Services را انجام ندهید → Skip** | ماژول Analytics را خاموش نگه می‌داریم (بخش H). |
| Sitemap | فقط پست‌تایپ‌های عمومی روشن؛ `tp_county` خاموش | tp_county فقط graph-only است و باید noindex و خارج از سایت‌مپ باشد. |
| Optimization (Noindex archives / nofollow external) | طبق جدول Titles & Meta تنظیم شود | در گام‌های بعدی دقیق می‌شود. |
| Ready | Finish | پایان ویزارد. |

---

### مرحلهٔ ۳ — Dashboard → Modules (بخش D)

هر ماژولی که خاموش است بار پردازشی و ریسک نوشتن در جدول‌های سفارشی را کم می‌کند — که در مقیاس ~۱۹۴٬۰۰۰ پست × ۹ زبان حیاتی است.

| ماژول | وضعیت | جدول دیتابیس؟ | علت |
|---|---|---|---|
| **Schema (Structured Data)** | **OFF** | خیر (metaها در postmeta) | تمام شِما را افزونهٔ core شما می‌دهد؛ ماژول نباید رقابت کند. |
| **Analytics** [بخشی PRO] | **OFF** | **بله — `rank_math_analytics_gsc`, `_objects`, `_inspections`, `_ga`, `keyword_manager`** (رشد بسیار زیاد) | خطر تورم شدید دیتابیس؛ داده را از GSC بگیرید. |
| **Local SEO & Knowledge Graph** [بخشی PRO] | **OFF** | خیر | منبع شِمای Organization/Person است؛ باید خاموش شود تا در JSON-LD سفارشی نشت نکند. |
| **Sitemap** | **ON** | خیر (transient/کش) | برای XML sitemap لازم است؛ WPML آن را per-language می‌کند. |
| **Redirections** | **ON** | **بله — `rank_math_redirections`, `_cache`** (کنترل‌شده) | برای ریدایرکت‌ها لازم است؛ ولی ریدایرکت 301 پست‌تایپ tp_county را با کد سفارشی انجام می‌دهید، نه اینجا. |
| **404 Monitor** | **OFF** (پس از رفع خطاها) | **بله — `rank_math_404_logs`** (رشد سریع با ترافیک بات) | روی سایت بزرگ با بات‌ها، هر ۴۰۴ یک ردیف می‌سازد و ادمین را کند می‌کند. |
| **Link Counter** | **ON** | **بله — `rank_math_internal_links`, `_internal_meta`** | برای شمارش لینک داخلی مفید و سبک است. |
| **Role Manager** | **ON** | خیر | برای کنترل دسترسی تیم چندنفره در سایت بزرگ. |
| **Instant Indexing (IndexNow)** | اختیاری ON | خیر | برای ایندکس سریع Bing/Yandex؛ برای Google افزونهٔ جدا لازم است. |
| **Image SEO** [بخشی PRO] | **ON** (فقط Add missing ALT) | خیر | افزودن alt خودکار برای سئوی تصویر مفید است؛ واترمارک را خاموش. |
| **ACF** | OFF | خیر | شما از JetEngine استفاده می‌کنید نه ACF. |
| **Content AI** [PRO] | **OFF** | خیر (تماس API خارجی) | بخش K. |
| **Rank Tracker** [PRO] | **OFF** | بله (جدول‌های Analytics) | بخشی از Analytics است که خاموش می‌کنیم. |
| **Video Sitemap / News Sitemap / Podcast** [PRO] | **OFF** | خیر | سایت دایرةالمعارفی است؛ این‌ها لازم نیستند. |
| **WooCommerce** | OFF | خیر | فروشگاه ندارید. |
| **AMP / bbPress / BuddyPress** | OFF | خیر | استفاده نمی‌شوند. |
| **LLMS.txt** [ماژول جدید] | اختیاری ON | خیر | فایل llms.txt مسیر محتوای مهم را به ابزارهای AI نشان می‌دهد؛ با هدف AEO/GEO سایت هم‌راستاست. |

---

### مرحلهٔ ۴ — General Settings (بخش E)

#### 4.1 Links
| فیلد | مقدار | علت |
|---|---|---|
| Strip Category Base | OFF | WPML با حذف category base دچار مشکل ترجمهٔ URL می‌شود (خطای مستند WPML). |
| Redirect Attachments | ON | صفحهٔ اتصال فایل به پست اصلی ریدایرکت شود تا محتوای نازک ایندکس نشود. |
| Redirect Orphan… | ON | همان دلیل بالا. |
| Nofollow External Links | OFF (یا انتخابی) | برای دایرةالمعارف، لینک‌های استنادی معتبر بهتر است follow بمانند. |
| Open External in New Tab | اختیاری | تجربهٔ کاربری. |

#### 4.2 Breadcrumbs
| فیلد | مقدار | علت |
|---|---|---|
| Enable Breadcrumbs Function | OFF | Breadcrumb شما از JetEngine relations و کد سفارشی می‌آید؛ شِمای Breadcrumb هم سفارشی است. فیلتر `rank_math/json_ld/breadcrumbs_enabled=false` را هم نگه دارید. |

#### 4.3 Webmaster Tools
| فیلد | مقدار | علت |
|---|---|---|
| کدهای تأیید | خالی بگذارید مگر لازم شود | تأیید GSC را از طریق DNS/فایل انجام دهید تا وابسته به افزونه نباشد. |

#### 4.4 Edit robots.txt (تنظیم کلیدی برای GEO/AEO)
هاست شما در هلند است و به APIهای Google/Microsoft و بات‌ها دسترسی دارد؛ پس سیاست باز برای بات‌های جستجوی AI درست است. طبق مستندات رسمی کرالر OpenAI، «وبمستر می‌تواند OAI-SearchBot را مجاز کند تا در نتایج جستجو ظاهر شود، در حالی که GPTBot را مسدود کند تا محتوایش برای آموزش استفاده نشود» — یعنی جستجو و آموزش دو تصمیم مستقل‌اند.

توصیه برای سایتی که **خواهان استناد AI است**: همهٔ بات‌های جستجو و استناد را مجاز کنید؛ دربارهٔ بات‌های آموزش سیاست‌تان را انتخاب کنید. چون ToPersia می‌خواهد «مرجع قابل‌استناد» شود، توصیه می‌شود بات‌های آموزش را هم مجاز کنید (حضور در دانش پایه‌ایِ مدل‌ها = استناد بیشتر) و فقط Bytespider را در سطح WAF مسدود کنید، چون طبق راهنماهای امنیتی «Bytespider سابقهٔ نادیده‌گرفتن robots.txt را دارد و تنها دفاع مؤثر برای بات‌های ناسازگار در سطح سرور یا WAF است».

محتوای توصیه‌شدهٔ robots.txt:

```
# --- موتورهای جستجوی سنتی ---
User-agent: Googlebot
Allow: /
User-agent: Bingbot
Allow: /

# --- بات‌های جستجو و استناد AI (مجاز برای GEO/AEO) ---
User-agent: OAI-SearchBot
Allow: /
User-agent: ChatGPT-User
Allow: /
User-agent: PerplexityBot
Allow: /
User-agent: Perplexity-User
Allow: /
User-agent: Claude-SearchBot
Allow: /
User-agent: Claude-User
Allow: /

# --- بات‌های آموزش AI (توصیه: مجاز، برای حضور در دانش مدل‌ها) ---
User-agent: GPTBot
Allow: /
User-agent: ClaudeBot
Allow: /
User-agent: Google-Extended
Allow: /
User-agent: Applebot-Extended
Allow: /
User-agent: Amazonbot
Allow: /

# --- کرال‌گرهای انبوه/داده‌کاوی که ارزش استناد ندارند ---
User-agent: CCBot
Disallow: /
User-agent: Bytespider
Disallow: /

# --- پیش‌فرض ---
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: https://topersia.com/sitemap_index.xml
```

جدول تصمیم برای هر بات:

| بات | تصمیم | نوع | علت |
|---|---|---|---|
| Googlebot / Bingbot | Allow | جستجو | حضور در جستجوی سنتی. |
| OAI-SearchBot | Allow | جستجوی ChatGPT | ظهور در نتایج جستجوی ChatGPT. |
| ChatGPT-User | Allow | واکشی بلادرنگ کاربر | وقتی کاربر از ChatGPT صفحه را می‌خواهد، استناد زنده. |
| PerplexityBot / Perplexity-User | Allow | جستجو | Perplexity منبع مهم استناد است. |
| Claude-SearchBot / Claude-User | Allow | جستجو/واکشی | استناد در پاسخ‌های Claude. |
| GPTBot | Allow (توصیه) | آموزش OpenAI | حضور در دانش مدل = برند بیشتر در پاسخ‌ها؛ اگر نگران کپی محتوا بودید Disallow. |
| ClaudeBot | Allow (توصیه) | آموزش Anthropic | همان منطق. |
| Google-Extended | Allow (توصیه) | آموزش Gemini | روی رتبهٔ Google بی‌اثر است؛ فقط آموزش AI. |
| Applebot-Extended | Allow | آموزش Apple | بی‌اثر بر ایندکس Applebot. |
| Amazonbot | Allow | آموزش/دستیار | بی‌ضرر برای سایت مرجع. |
| CCBot | Disallow | Common Crawl (آموزش) | داده‌کاوی انبوه بدون استناد مستقیم. |
| Bytespider | Disallow + WAF | آموزش ByteDance | robots.txt را نادیده می‌گیرد؛ باید در WAF هم مسدود شود. |

> نکته: robots.txt کنترل دسترسی واقعی نیست؛ فقط راهنماست. مسدودسازی سخت را در WAF انجام دهید.

#### 4.5 Edit .htaccess
| فیلد | مقدار | علت |
|---|---|---|
| ویرایش از این‌جا | فقط در صورت لزوم | ویرایش اشتباه سایت را می‌شکند؛ چون هاست cPanel/Apache دارید، هدر `X-Robots-Tag: noindex` برای tp_county را با کد سفارشی core یا .htaccess بگذارید. برای استثناکردن سایت‌مپ از کش هم می‌توان rewrite افزود. |

#### 4.6 Others
| فیلد | مقدار | علت |
|---|---|---|
| RSS Before/After Content | خالی/اختیاری | جلوگیری از سرقت محتوا با افزودن لینک به منبع. |
| Remove `?replytocom` | ON | جلوگیری از URLهای اضافی. |
| Frontend Stats Bar / Usage Tracking | OFF | بار اضافی و حریم خصوصی. |

---

### مرحلهٔ ۵ — Titles & Meta (بخش F)

#### 5.1 Global Meta
| فیلد | مقدار | علت |
|---|---|---|
| Robots Meta | **Index** فعال (بقیه خاموش) | محتوا باید ایندکس شود. |
| Advanced Robots Meta | **max-snippet:-1 ، max-video-preview:-1 ، max-image-preview:large** | «‎-1» یعنی نامحدود؛ این‌ها اسنیپت کامل و پیش‌نمایش تصویر بزرگ می‌دهند و موتورهای پاسخ AI هم همین سقف را رعایت می‌کنند — برای AEO/استناد حیاتی است. |
| **Separator Character (RTL)** | **خط تیرهٔ ساده `-`** (یا `|`) | برای فارسی/عربیِ راست‌به‌چپ، خط تیرهٔ ساده خنثی و بی‌مشکل است؛ کاراکترهای تزئینی در بافت RTL می‌توانند جهت‌شان جابه‌جا شود. توجه: Rank Math با فارسی در تست تراکم کلیدواژه (به‌ویژه با خط تیره در کلیدواژه) باگ شناخته‌شده دارد؛ روی جداکنندهٔ عنوان بی‌اثر است. |
| Capitalize Titles | OFF | برای فارسی/عربی بی‌معناست. |
| Open Graph Thumbnail | تصویر پیش‌فرض ۱۲۰۰×۶۳۰ | برای اشتراک‌گذاری اجتماعی. |

#### 5.2 Local SEO / Knowledge Graph
| فیلد | مقدار | علت |
|---|---|---|
| کل بخش | **دست نزنید / ماژول Local SEO خاموش** | این بخش منبع شِمای Organization/Person است که باید از خروجی حذف شود. |

#### 5.3 Social Meta
| فیلد | مقدار | علت |
|---|---|---|
| Facebook/Twitter defaults | پر کنید | OG/Twitter Card متا هستند نه JSON-LD؛ با فیلترهای schema حذف نمی‌شوند و برای اشتراک‌گذاری مفیدند. Twitter Card: Summary with large image. |

#### 5.4 Homepage
| فیلد | مقدار | علت |
|---|---|---|
| Homepage Title / Description | قالب دستی با متغیرها | صفحهٔ اصلی مهم‌ترین صفحه است. |
| Homepage Robots | Index | باید ایندکس شود. |

#### 5.5 Authors
| فیلد | مقدار | علت |
|---|---|---|
| Author Archives | **noindex** (یا خاموش‌کردن آرشیو نویسنده) | در دایرةالمعارف چندنویسنده، آرشیو نویسنده محتوای تکراری/نازک می‌سازد. |

#### 5.6 Misc Pages
| فیلد | مقدار | علت |
|---|---|---|
| Search Results / 404 | noindex | صفحات جستجو و ۴۰۴ نباید ایندکس شوند. |

#### 5.7 Posts / Pages
| فیلد | مقدار | علت |
|---|---|---|
| Robots Meta | Index | محتوای اصلی. |
| Add SEO Controls | ON | برای دسترسی به متاباکس در ادیتور کلاسیک. |
| Link Suggestions / Watermark | OFF | لازم نیست. |
| Schema Type (Default) | **None / Off** | شِما را افزونهٔ core می‌دهد. |

#### 5.8 Media / Attachments
| فیلد | مقدار | علت |
|---|---|---|
| Redirect Attachment URLs | ON (از Links) | صفحهٔ پیوست ایندکس نشود. |

#### 5.9 پست‌تایپ‌های سفارشی (۳۳ عدد با پیشوند `tp_`) — استراتژی مقیاس‌پذیر
به‌جای تنظیم دستیِ ۳۳ پست‌تایپ، الگوی واحد زیر را برای هرکدام اعمال کنید:

| فیلد (برای هر پست‌تایپ tp_) | مقدار | علت |
|---|---|---|
| Robots Meta | Index (به‌جز `tp_county`) | همه ایندکس‌پذیر جز county. |
| **`tp_county`** | **noindex + Include in Sitemap = OFF + هدر X-Robots-Tag: noindex** | این پست‌تایپ graph-only است، با کد سفارشی 301 به استان می‌رود؛ باید در Rank Math هم سازگار باشد. |
| Title Format | `%custom_meta_title% %sep% %sitename%` | از متغیر سفارشیِ ثبت‌شده (پایین) استفاده می‌کند. |
| Description Format | `%custom_meta_description%` | همان. |
| Schema Type | None | شِما سفارشی است. |
| Add SEO Controls | ON | برای ویرایش تکی. |

**راه‌حل درست برای خواندن فیلد JetEngine (بخش F، تصمیم کلیدی):**
مستندات و پشتیبانی Rank Math تأیید می‌کنند که «اگر از JetEngine استفاده می‌کنید ممکن است `%customfield()%` درست کار نکند و باید متغیر سفارشی بسازید». JetEngine فقط فیلدهای Text/Textarea/WYSIWYG/Repeater را برای پردازش پشتیبانی می‌کند. چون عنوان/توضیح شما در متاباکسِ مشترک JetEngine است و با WPML ترجمه می‌شود، **متغیر سفارشی ثبت کنید** و در فایل `topersia-core.php` این کد را بگذارید:

```php
add_action( 'rank_math/vars/register_extra_replacements', function () {
    rank_math_register_var_replacement(
        'custom_meta_title',
        [
            'name'        => esc_html__( 'ToPersia Meta Title', 'rank-math' ),
            'description' => esc_html__( 'meta_title از متاباکس JetEngine', 'rank-math' ),
            'variable'    => 'custom_meta_title',
            'example'     => 'عنوان نمونه',
        ],
        'topersia_meta_title_cb'
    );
    rank_math_register_var_replacement(
        'custom_meta_description',
        [
            'name'        => esc_html__( 'ToPersia Meta Description', 'rank-math' ),
            'description' => esc_html__( 'meta_description از متاباکس JetEngine', 'rank-math' ),
            'variable'    => 'custom_meta_description',
            'example'     => 'توضیح نمونه',
        ],
        'topersia_meta_desc_cb'
    );
});

function topersia_meta_title_cb() {
    global $post;
    if ( ! $post ) { return ''; }
    if ( function_exists( 'jet_engine' ) ) {
        $v = jet_engine()->listings->data->get_meta( 'meta_title', $post->ID );
        return esc_html( wp_strip_all_tags( (string) $v ) );
    }
    return esc_html( wp_strip_all_tags( (string) get_post_meta( $post->ID, 'meta_title', true ) ) );
}
function topersia_meta_desc_cb() {
    global $post;
    if ( ! $post ) { return ''; }
    if ( function_exists( 'jet_engine' ) ) {
        $v = jet_engine()->listings->data->get_meta( 'meta_description', $post->ID );
        return esc_html( wp_strip_all_tags( (string) $v ) );
    }
    return esc_html( wp_strip_all_tags( (string) get_post_meta( $post->ID, 'meta_description', true ) ) );
}
```

**علت:** استفاده از `jet_engine()->listings->data->get_meta()` مقدار درست را در بافت WPML برمی‌گرداند و چون WPML خودِ فیلد را ترجمه می‌کند، هر زبان مقدار زبانِ خودش را می‌گیرد؛ برخلاف `%customfield()%` که با JetEngine غیرقابل‌اعتماد است. سپس در Title/Description Format پست‌تایپ‌ها از `%custom_meta_title%` و `%custom_meta_description%` استفاده کنید.

> **هشدار مقیاس:** فیلدهای JetEngine را در `JetEngine → SEO tab` برای تحلیل محتوا فعال کنید، اما فقط برای پست‌تایپ‌هایی که واقعاً نیاز دارید — روشن‌کردن همه در ۳۳ پست‌تایپ بار تحلیل را زیاد می‌کند.

#### 5.10 Taxonomies
| فیلد | مقدار | علت |
|---|---|---|
| Categories | Index (اگر ساختاریافته‌اند) | آرشیو دسته می‌تواند ارزش داشته باشد. |
| Tags / Formats | noindex | معمولاً محتوای تکراری. |
| Schema/Snippet Data | Remove | شِما سفارشی است. |

---

### مرحلهٔ ۶ — Sitemap Settings (بخش G)

| فیلد | مقدار | علت |
|---|---|---|
| **Links Per Sitemap** | **200** (پیش‌فرض توصیه‌شدهٔ Rank Math) | «Google به صفحات کوچک‌تر بهتر پاسخ می‌دهد»؛ در مقیاس بزرگ، سایت‌مپ‌های کوچک‌تر تولید و کش بهتری دارند. تغییر این عدد، کش سایت‌مپ را هم رفرش می‌کند. |
| Include Images | ON | برای جستجوی تصویر. |
| Include Featured Images | ON | توصیهٔ Rank Math. |
| Include Images from JetEngine fields | ON (فیلدهای تصویری را در Image Custom Fields وارد کنید) | JetEngine فیلد تصویر می‌سازد؛ برای ورود به سایت‌مپ باید نامشان ثبت شود. |
| Ping Search Engines | ON | اطلاع به موتورها هنگام به‌روزرسانی. |
| Post Types → `tp_county` | Include = **OFF** | graph-only و noindex. |
| **Video Sitemap** [PRO] | OFF | سایت ویدیومحور نیست. |
| **News Sitemap** [PRO] | OFF | ناشر Google News نیستید (و سقف ۱٬۰۰۰ URL و پنجرهٔ ۲روزه دارد). |
| HTML Sitemap | اختیاری | برای کاربران. |

**رفتار WPML و مقیاس (~۱۹۴٬۰۰۰ URL × ۹ زبان):** سایت‌مپِ چندزبانه را **WPML** مدیریت می‌کند نه Rank Math؛ Rank Math فقط URLهای زبان اصلی را می‌گذارد و بقیه را افزونهٔ ترجمه هندل می‌کند. پس از فعال‌کردن WPML SEO glue، سایت‌مپ را با تغییر «Links per Sitemap» و ذخیره، بازتولید کنید. **مهم:** سایت‌مپ را از کش (LiteSpeed/Cloudflare) مستثنا کنید تا همیشه به‌روز بماند. اگر تایم‌اوت رخ داد، در Site Health مقادیر PHP (`max_input_vars`، `memory_limit`، `time_limit`) را با هاست هلندی بالا ببرید — پشتیبانی Rank Math صریحاً می‌گوید سایت‌های بزرگ به منابع سرور بیشتری برای تولید سایت‌مپ نیاز دارند.

---

### مرحلهٔ ۷ — تصمیم Analytics [PRO] (بخش H)

| مورد | مقدار | علت |
|---|---|---|
| ماژول Analytics | **OFF** | جدول‌های `rank_math_analytics_gsc/_objects/_inspections/_ga/_keyword_manager` روی سایت بزرگ به‌شدت رشد می‌کنند. |
| حجم مورد انتظار | طبق تیکت رسمی پشتیبانی Rank Math، «حجم پایگاه دادهٔ Analytics به‌ویژه در سایت‌های پرمحتوا از ۱۰۰ مگابایت فراتر می‌رود و ما داده‌ها را دوبل ذخیره می‌کنیم»؛ کاربران ۱۷۶ و بیش از ۳۵۰ مگابایت گزارش کرده‌اند | در ۱۹۴٬۰۰۰ پست × ۹ زبان این چند صد مگابایت تا گیگابایت می‌شود. |
| Data Retention | اگر (برخلاف توصیه) روشن کردید: کمترین بازه (مثلاً ۹۰ روز) | محدودکردن رشد جدول. |
| **جایگزین** | Google Search Console + GA4 مستقیم | همان داده بدون تورم دیتابیس؛ Index Status را هم مستقیم در GSC ببینید. |

**آنچه از دست می‌رود:** داشبورد یکپارچهٔ داخل وردپرس، Rank Tracker، Site Analytics، AI Traffic report و Index Status Pro. **چرا ارزشش را دارد:** روی این مقیاس، پایداری و سرعت دیتابیس مهم‌تر از راحتیِ گزارش داخل وردپرس است؛ همهٔ این داده‌ها در GSC/GA4 موجودند.

---

### مرحلهٔ ۸ — خنثی‌سازی کامل Schema (بخش I) — بحرانی‌ترین بخش

**خاموش‌کردن ماژول Schema کافی نیست.** طبق دانش‌نامهٔ رسمی Rank Math، حتی با خاموش‌بودن ماژول Schema، «انواع شِما مانند Organization، Sitelinks Search Box و Breadcrumbs ممکن است همچنان ظاهر شوند چون بر پایهٔ تنظیمات سراسری Schema اضافه می‌شوند». شِمای Organization/Person از **ماژول Local SEO** می‌آید.

روش رسمی و مطمئن (ترکیب UI + فیلتر):

| اقدام | مقدار | علت |
|---|---|---|
| ماژول Schema | OFF | حذف شِمای پست‌محور و Schema Templates. |
| ماژول Local SEO | OFF | حذف شِمای Organization/Person. |
| فیلتر ۱ | `add_filter('rank_math/json_ld', '__return_empty_array');` (بهتر: با priority 99) | مؤثرترین اهرم؛ تیم Rank Math می‌گوید «این کار تمام خروجی JSON-LD افزونه را غیرفعال می‌کند». priority بالا تضمین می‌کند بعد از ساخت گراف اجرا شود. |
| فیلتر ۲ | `rank_math/json_ld/breadcrumbs_enabled` → false | مسیر جدای JSON-LD مسیر خرده‌نان را می‌بندد. |
| فیلتر ۳ | `rank_math/json_ld/disable_search` → true | SearchAction/Sitelinks Searchbox را حذف می‌کند. |
| (اختیاری) فیلتر ۴ | `rank_math/schema/add_global_entities` → false | حذف صریح موجودیت‌های سراسری (کمربند ایمنی). |
| Bulk Action | Set Schema: None [PRO] | برای پست‌های موجود. |

**آیا Schema Templates ([PRO]) باز هم شلیک می‌کنند؟** نه — Schema Templates کاملاً وابسته به روشن‌بودن ماژول Schema هستند؛ با خاموش‌بودن ماژول، کل این قابلیت و منویش ناپدید می‌شود.

**آیا ادغام‌های ACF/Elementor شِما تزریق می‌کنند؟** ادغام Rank Math با ACF/Elementor برای شِما، از همان پایپ‌لاین `rank_math/json_ld` عبور می‌کند و با فیلتر آرایهٔ خالی حذف می‌شود. **اما** ویجت‌های خودِ Elementor (مثل FAQ یا Breadcrumbs) شِمای مستقلِ خودشان را می‌دهند که هیچ فیلتر Rank Math کنترلش نمی‌کند — این‌ها را باید در خودِ Elementor خاموش کنید. مورد مستند: تکرار FAQ Schema به‌دلیل تداخل ویجت Elementor و Rank Math.

**تأیید (روش رسمی):**
1. **Google Rich Results Test** — دانش‌نامهٔ Rank Math می‌گوید حذف شِما را با این ابزار تأیید کنید.
2. **View Source** و جستجوی `application/ld+json` — فقط بلاک JSON-LD سفارشی شما (با `@id` برابرِ `entity_uid`) باید بماند.
3. پیش از تست، **همهٔ کش‌ها** (صفحه، CDN، LiteSpeed) را پاک کنید — کش کهنه باعث می‌شود شِما «حذف‌شده اما هنوز سرو‌شده» به‌نظر برسد.

---

### مرحلهٔ ۹ — ادغام WPML (بخش J)

| مورد | مقدار/اقدام | علت |
|---|---|---|
| افزونهٔ WPML SEO glue | **نصب و فعال (الزامی)** | صفحهٔ رسمی سازگاری WPML: «این افزونه برای سازگاری کامل به WPML SEO glue نیاز دارد. Rank Math SEO Pro نیز با WPML سازگار است». |
| نسخهٔ WPML SEO | حداقل **2.2.5** (مارس ۲۰۲۶) | WPML اعلام کرد «WPML SEO 2.2.5 مشکلات استفادهٔ همزمان RankMath و Yoast با WPML را رفع می‌کند: رندر سریع‌تر سایت‌مپ و رفع نمایش URL نادرست صفحهٔ اصلی در زبان‌های فرعی». |
| ترجمهٔ عنوان/توضیح | با WPML Classic Translation Editor | انتخاب عمدی شما درست است؛ ATE ساختار HTML فیلدهای بلندمتن را می‌شکند. |
| رشته‌های سراسری Rank Math | از `WPML → String Translation → admin_texts_rank-math-options-general` | برچسب‌های سراسری (مثل Home در breadcrumb) این‌جا ترجمه می‌شوند. |
| hreflang | **فقط WPML** (Rank Math نباید تکرار کند) | با WPML SEO glue، hreflang خودکار مدیریت می‌شود؛ می‌توانید آن را به `<head>` منتقل کنید تا از سایت‌مپ حذف شود. |
| رفتار امکانات Pro در چندزبانه | Schema Templates خاموش، Analytics خاموش، Redirections با URLهای هر زبان جداگانه | خطاهای مستند WPML دربارهٔ سایت‌مپِ homepage ترجمه‌شده و category base با این پیکربندی دور زده می‌شوند. |

> خطای مستند: حذف category base در Rank Math باعث می‌شود WPML نتواند URLها را درست ترجمه کند — بنابراین Strip Category Base را OFF نگه دارید (مطابق مرحلهٔ 4.1).

---

### مرحلهٔ ۱۰ — Content AI [PRO] (بخش K)

| مورد | مقدار | علت |
|---|---|---|
| ماژول Content AI | **OFF** | برای این سایت لازم نیست و تماس API خارجی دارد. |
| مدل استفاده | Rank Math از سیستم اعتبار (credits) به **مدل محدودیت ماهانهٔ مبتنی بر ویژگی** مهاجرت کرده است؛ طبق دانش‌نامهٔ رسمی «Content AI Migration: Credits to Feature-Based Monthly Limits»، سه پلن (Starter/Creator/Expert) وجود دارد، کاربران رایگان «یک تا پنج استفاده به‌ازای هر ویژگی» می‌گیرند و «سقف ویژگی‌های استفاده‌نشده منتقل نمی‌شود». | تصمیم فعال/غیرفعال. |
| تماس API خارجی | بله (کلیدواژه، متن، تولید alt، sync پرامپت‌ها به سرور Rank Math ارسال می‌شود) | ملاحظهٔ حریم خصوصی و وابستگی. |
| پشتیبانی زبان | فارسی و عربی پشتیبانی می‌شوند (Content AI بیش از ۳۰ زبان دارد) | از نظر زبانی مانعی نیست، اما برای محتوای دایرةالمعارفیِ تخصصی و انسانی توصیه نمی‌شود. |
| توصیهٔ نهایی | **خاموش** | تولید محتوای دایرةالمعارفی باید انسانی/تخصصی و قابل استناد باشد؛ اعتبار AI ارزش افزوده‌ای برای این پروژه ندارد. |

---

### مرحلهٔ ۱۱ — موارد اجتناب (بخش L)

- **Elementor:** تعارض‌های مستند Rank Math Pro با Elementor: فریز ادیتور در 3.0.54، خطای HTTP 500 در 3.0.48، خطای `Elementor\Core\Schemes\Typography` در ویجت Breadcrumbs. راه‌حل رسمی: به آخرین نسخهٔ Pro به‌روزرسانی کنید و در صورت بروز مشکل با Troubleshooting Mode افزونه‌ها را تک‌به‌تک تست کنید. **از ویجت Breadcrumbs مربوط به Rank Math در Elementor استفاده نکنید** (breadcrumb شما سفارشی است).
- **JetEngine:** از `%customfield()%` استفاده نکنید (باگ مستند)؛ فقط از متغیر سفارشی ثبت‌شده استفاده کنید. فقط فیلدهای Text/Textarea/WYSIWYG/Repeater قابل پردازش‌اند.
- **سایت‌های بزرگ:** Analytics و 404 Monitor را خاموش نگه دارید؛ Links per Sitemap را ۲۰۰ بگذارید؛ سایت‌مپ را از کش مستثنا کنید؛ منابع PHP را با هاست بالا ببرید.
- **WPML:** بدون WPML SEO glue، ورودی‌های اشتباه در سایت‌مپِ homepageهای ترجمه‌شده و breadcrumbهای WooCommerce رخ می‌دهد (خطاهای مستند WPML).
- **آپدیت افزونه:** اگر آپدیت Pro خطای `PCLZIP_ERR_BAD_FORMAT` داد، zip را دوباره از حساب دانلود و دستی آپلود کنید.

---

## Recommendations (اقدامات مرحله‌بندی‌شده)

**فاز ۱ — پایه (همین حالا):**
1. بکاپ Duplicator بگیرید و Pro را به آخرین نسخهٔ ۳.۰.x به‌روزرسانی کنید.
2. لایسنس را وصل کنید؛ روی کلون staging وصل نشوید.
3. سه ماژول Schema، Analytics، Local SEO را خاموش کنید؛ سه فیلتر schema را در `topersia-core.php` بگذارید (+ فیلتر چهارم اختیاری).
4. کد متغیر سفارشی JetEngine را اضافه و در Title/Description Format پست‌تایپ‌ها به کار ببرید.

**فاز ۲ — پیکربندی صفحات (این هفته):**
5. General Settings را طبق جداول تنظیم کنید؛ robots.txt توصیه‌شده را وارد کنید و Bytespider را در WAF مسدود کنید.
6. Titles & Meta: جداکنندهٔ `-`، Advanced Robots `-1,-1,large`، `tp_county` را noindex + خارج از سایت‌مپ + هدر X-Robots-Tag.
7. WPML SEO glue را نصب کنید (نسخهٔ ≥ 2.2.5) و سایت‌مپ را بازتولید کنید.

**فاز ۳ — تأیید و پایش (پس از پیکربندی):**
8. Rich Results Test و View Source را اجرا کنید؛ فقط JSON-LD سفارشی باید بماند.
9. GSC و GA4 را مستقیم وصل کنید (به‌جای ماژول Analytics).

**آستانه‌هایی که تصمیم را عوض می‌کنند:**
- اگر بعداً محتوای ویدیویی جدی اضافه شد → Video Sitemap [PRO] را روشن کنید.
- اگر بخش خبری روزانه راه افتاد و ناشر Google News شدید → News Sitemap [PRO].
- اگر تیم به داشبورد داخلی داده نیاز داشت **و** هاست به منابع بیشتر ارتقا یافت → می‌توان Analytics را با Data Retention کوتاه روشن کرد و رشد جدول‌ها را با WP-Optimize پایید.
- اگر تولید سایت‌مپ تایم‌اوت داد → Links per Sitemap را به ۱۰۰ کاهش دهید و منابع PHP را افزایش دهید.

---

## Caveats (هشدارها و محدودیت‌ها)

- **شماره نسخه‌ها:** رفتار خنثی‌سازی Schema در چنجلاگِ Rank Math به یک نسخهٔ خاص ۳.۰.x گره نخورده است؛ یافته‌ها بر پایهٔ دانش‌نامه/داکس/تیکت‌های پشتیبانی معتبر ۲۰۲۴–۲۰۲۶‌اند. نسخه‌های Pro مشاهده‌شده در ۲۰۲۶: 3.0.107 (۱۱ فوریه)، 3.0.113 (۱۴ مه)، تا 3.0.119.
- **نقل‌قول «تمام خروجی JSON-LD را غیرفعال می‌کند»** از پاسخ خودکار ربات پشتیبانی Rank Math است، اما با داکس توسعه‌دهنده و راهنمایی کارشناسان انسانی هم‌خوان است.
- **robots.txt کنترل دسترسی واقعی نیست** و بات‌های ناسازگار (Bytespider، اسکرپرها) آن را نادیده می‌گیرند؛ برای مسدودسازی سخت به WAF نیاز دارید. همچنین لایهٔ CDN می‌تواند سیاست robots.txt را بازنویسی کند — این دو باید هم‌خوان باشند.
- **تصمیم آموزش‌دادن به AI (GPTBot/ClaudeBot/Google-Extended)** یک انتخاب سیاستی است: توصیهٔ این راهنما «مجاز» است چون هدف سایت حداکثر استناد است؛ اگر نگران کپی‌شدن محتوای اختصاصی بودید می‌توانید بات‌های آموزش را Disallow کنید بدون آنکه به استناد جستجوی AI (OAI-SearchBot و…) آسیب بزنید.
- **باگ فارسی Rank Math:** پشتیبانی تأیید کرده که تحلیل‌گر با فارسی (به‌ویژه تراکم کلیدواژه با خط تیره) مشکلاتی دارد؛ به امتیاز SEO داخلی برای محتوای فارسی بیش از حد تکیه نکنید.
- **مقادیر وابسته به شرایط ناشناخته:** انتخاب دقیق سیاست بات‌های آموزش، روشن/خاموش‌بودن آرشیو دسته‌ها (بسته به کیفیت ساختار تاکسونومی)، و اینکه آیا بعداً به داشبورد Analytics داخلی نیاز پیدا می‌کنید، به تصمیم‌های محتوایی و منابع سرور شما بستگی دارد؛ قواعد تصمیم در بخش Recommendations آمده است.

---

## چک‌لیست نهاییِ تأیید (پس از اتمام پیکربندی)

1. ☐ `view-source` یک صفحهٔ نمونه از هر پست‌تایپ: فقط JSON-LD سفارشی با `@id = entity_uid` دیده شود، بدون بلاک Organization/WebSite/Breadcrumb از Rank Math.
2. ☐ Google Rich Results Test روی نمونه‌های فارسی/انگلیسی/عربی: هیچ شِمای Rank Math گزارش نشود.
3. ☐ نسخهٔ سه‌زبانهٔ یک entity، `@id` یکسان داشته باشد.
4. ☐ `topersia.com/robots.txt`: بلوک‌های AI درست، ارجاع Sitemap موجود؛ با یک شبیه‌ساز robots.txt اعتبارسنجی شود (خطای نحوی نباید Googlebot را مسدود کند).
5. ☐ Bytespider در WAF مسدود شده باشد.
6. ☐ `tp_county`: هدر `X-Robots-Tag: noindex` برگردد، 301 به استان کار کند، در سایت‌مپ نباشد.
7. ☐ `sitemap_index.xml` با کد ۲۰۰ باز شود؛ URLهای هر زبان توسط WPML درست تفکیک شده باشند؛ hreflang تکراری وجود نداشته باشد.
8. ☐ عنوان/توضیح یک پست از فیلد JetEngine خوانده شود و در هر زبان مقدار همان زبان را نشان دهد.
9. ☐ ادیتور Elementor روی یک صفحهٔ نمونه بدون فریز/خطای ۵۰۰ باز و ذخیره شود.
10. ☐ در `Rank Math → Dashboard` تأیید شود که Schema، Analytics، Local SEO، Content AI، 404 Monitor، Video/News Sitemap همه **خاموش**‌اند.
11. ☐ WPML SEO glue (≥ 2.2.5) فعال باشد و رشته‌های سراسری Rank Math ترجمه شده باشند.
12. ☐ حجم دیتابیس پیش/پس از پیکربندی مقایسه شود تا مطمئن شوید جدول‌های `rank_math_analytics_*` رشد نمی‌کنند.