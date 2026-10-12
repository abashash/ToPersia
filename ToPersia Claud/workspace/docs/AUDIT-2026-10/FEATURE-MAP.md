# FEATURE-MAP — ممیزی ۲د: هر قابلیت در سه سند

> ۱۲ اکتبر ۲۰۲۶ · جلسۀ ابری ۱ · فقط‌خواندنی (P5-D287).
> سه سند: **A** = `docs/ToPersia-AI-Core-Master-Spec.md` · **M** = `reference/ToPersia_Master_Architecture_Audit_and_Expansion.md` · **P** = `reference/ToPersia_Map_Engine_Claude_Decision_Brief_v2.md`. عدد بعد از حرف = بند همان سند (— یعنی در آن سند نیست).
> هر قابلیت **دقیقاً یکی** از این پنج طبقه را دارد، با تعریف سند M بند ۴۱: `READY` (با معماری فعلی) · `READY WITH MODIFICATIONS` (اصلاح محدود) · `NEEDS INFRASTRUCTURE` (Worker، سرویس بیرونی، صف یا زیرساخت تازه) · `FUTURE` (ایده درست، زمانش نیست) · `NOT RECOMMENDED` (با Vision یا معماری ناسازگار؛ دلیل نوشته شده).
> ستون «هست؟»: وضعیت امروز در کد (workspace). برچسب شاهد: **VERIFIED** (مسیر/خط) · **INFERRED** · **ASSUMED** · **UNKNOWN**. طبقه‌بندی خودش داوری است؛ برچسب شاهد فقط به ستون «هست؟» می‌خورد.
> هیچ‌چیز اینجا حکم اجرا نیست (P5-D287)؛ حکم‌های `ARCH-RECONCILIATION` در ستون آخر هر جا مغایرت یا تأیید شد نوشته شده.

## ۱ — هوش مصنوعی: تولید، خلاصه، پرامت

| # | قابلیت | A | M | P | هست؟ (کجا) | طبقه | یادداشت / مغایرت با ARCH |
|---|---|---|---|---|---|---|---|
| ۱ | خلاصۀ رسمی درون‌صفحه (یک ارائه‌دهنده، سه نوع خلاصه/نکات/برنامه) | ۱۳ | ۳۵ | — | بله، `topersia-ai-summarizer` `class-tp-ai-summary.php:87-232` — **VERIFIED** | READY WITH MODIFICATIONS | حکم #۷ پذیرش؛ نیازمند جداسازی `handle_ajax` و قفل هم‌زمانی (AI.md ۳، ۱۳) |
| ۲ | شناسۀ کش SHA-256 روی بستۀ منبع + نسخۀ پرامت/طرح/سیاست | ۱۴ | — | — | نیمه: `sha1` بی پرامت/مدل — `topersia-cache.php:279` — **VERIFIED** | READY WITH MODIFICATIONS | ARCH #۸ پذیرش، ولی شکاف در کد (AI.md ۳) |
| ۳ | Router · Policy · ProviderAdapter بر پایۀ کار | ۵، ۶۹، ۷۱ | — | — | نیمه: یک کلاس `TP_AI_API` با سه پروتکل — `class-tp-ai-api.php:72-105` — **VERIFIED** | READY WITH MODIFICATIONS | #۳ پذیرش |
| ۴ | ارائه‌دهندۀ دوم و fallback خودکار | ۱۱، ۷۵ | ۳۵ | — | برای `ai.text` نیست (هسته فقط TTS/هوا) — `class-tp-ai-core-bridge.php:5-8` — **VERIFIED** | READY WITH MODIFICATIONS | تصمیم ۴٫۱۲٫۱ تولید متن را از لایۀ هسته بیرون برد |
| ۵ | انتخاب تولید متن بدون مدل: موتور داخلی قطعی | ۲ | — | — | بله `TP_AI_Internal` — **VERIFIED** | READY | وابسته به `mbstring` (SYSTEM.md ۱) |
| ۶ | دروازۀ گردشگری (Tourism Gate) | ۸، ۹ | — | — | نه — ورودی آزاد نیست — **VERIFIED** | FUTURE | #۵ «پذیرش با زمان‌بندی» ✓ (با نخستین ورودی آزاد: ویرایش سفر/Ask Iran) |
| ۷ | Jev | ۱۰ | — | — | نه | FUTURE | #۶ تعویق ✓ (پرسش از کاربر) |
| ۸ | تلهمتری واحد `ai_runs` (توکن، هزینه، تأخیر، کد خطا) | ۷۲–۷۳، ۴۶ | — | — | نه — **VERIFIED** (AI.md ۱۰) | READY WITH MODIFICATIONS | #۱۷ → جدول در افزونۀ هوش مصنوعی |
| ۹ | پرامت در فایل نسخه‌دار | ۴۹ | — | — | نیمه: آرایۀ PHP + override بی نسخه — **VERIFIED** | READY WITH MODIFICATIONS | #۱۸ پذیرش |
| ۱۰ | امنیت: `permission_callback`، SSRF، تزریق پرامت، ابزار فقط از فهرست | ۱۹، ۵۰–۵۴ | ۳۹ | — | نیمه: AJAX احراز و نانس دارد، تزریق/SSRF مرزبندی ندارد — **VERIFIED** | READY WITH MODIFICATIONS | #۱۹ قاعدۀ دائمی |
| ۱۱ | ذخیرۀ کلید رمزشده سمت سرور | ۵۰ | ۳۹ | — | نیمه: کلید متن‌ساده در گزینه (AI و پیامک) — **VERIFIED** | READY WITH MODIFICATIONS | مغایر P5-D249 |
| ۱۲ | اعتبار (Credits) و رزرو | ۵۵–۵۶ | — | — | نه؛ فقط سقف روزانه | FUTURE | #۱۵ تعویق ✓؛ «هزینۀ عملیات» را تلهمتری ثبت کند |
| ۱۳ | سقف روزانه کاربر و سایت | ۵۷ | ۳۹ | — | بله، سه سازوکار هم‌پوشان — **VERIFIED** (AI.md ۸) | READY WITH MODIFICATIONS | یک منبع لازم؛ دورزدن با خروجی کهنه |
| ۱۴ | کش سه‌لایه L1/L2/L3 | ۴۴ | ۳۸ | — | نیمه: کش مرکزی + جدول (محلی) — `topersia-cache.php:291-293` — **VERIFIED** | READY WITH MODIFICATIONS | جدول کش فقط محلی (پیوست الف) |
| ۱۵ | Action Scheduler تنها صف | ۴۲ | ۸–۱۰ | — | بله `topersia-queue.php` — **VERIFIED** | READY | #۱۶ پذیرش ✓؛ مصرف‌کنندۀ هوش مصنوعی نیست |
| ۱۶ | Redis اختیاری | ۴۵ | — | — | نه | FUTURE | #۲ «Redis اختیاری» ✓ |
| ۱۷ | Elasticsearch / Vector DB | ۸۳ | — | — | نه | FUTURE | — |
| ۱۸ | معماری C: سرویس هماهنگ‌کنندۀ بیرونی | ۶، ۸۳ | — | — | نه | FUTURE | #۲ معماری B پذیرفته شد |
| ۱۹ | ده زبان با چینی ساده | ۴۱ | ۳۶ | — | نه (نه زبان در پرامت: `fa en ar fr de es ru tr zh`، `class-tp-ai-localize.php:402-420`) — **VERIFIED** | FUTURE | #۲۳ پرسش باز: «نُه زبان پروژه کدام؟» |
| ۲۰ | فهرست‌های «ابزار» برای مدل (مثل tool calls) | ۵۱ | — | — | نه | NOT RECOMMENDED | A خودش «arbitrary model-controlled server tools» را حذف کرده؛ ابزار فقط از فهرست مجاز و بی اجرای دلخواه |

## ۲ — صدا و رسانه

| # | قابلیت | A | M | P | هست؟ | طبقه | یادداشت |
|---|---|---|---|---|---|---|---|
| ۲۱ | موتور صدای واحد (read_aloud · storytelling · podcast) | ۱۵–۱۸ | — | — | نه؛ سرویس `ai.tts` در کاتالوگ هسته با دو آداپتور خاموش — `topersia-services.php:56,87-88` — **VERIFIED** | NEEDS INFRASTRUCTURE | #۹ پذیرش؛ صف + ذخیره + پروژه TTS + جدول |
| ۲۲ | Read Aloud (خبر و مقاله) | ۱۵ | — | — | نه | NEEDS INFRASTRUCTURE | زیرمجموعۀ ۲۱ |
| ۲۳ | روایت (Grounded Storytelling) | ۱۵ | — | — | نه | NEEDS INFRASTRUCTURE | — |
| ۲۴ | پادکست (P5-D256) | ۱۵ | — | — | نه؛ مشخصات در `reference/PODCAST-SPEC.md` | NEEDS INFRASTRUCTURE | حالت سوم همان موتور |
| ۲۵ | Google TTS پایه + ElevenLabs ممتاز | ۱۷ | — | — | آداپتور هر دو هست — **VERIFIED** | READY WITH MODIFICATIONS | #۱۰ اصلاح: Gemini TTS پیش‌فرض؛ بنچمارک فارسی/عربی/انگلیسی؛ دسترسی IP ایران UNKNOWN |
| ۲۶ | فایل صدا در Media Library؛ منشأ در جدول | ۱۸ | — | — | `audio_narration` repeater در متاباکس مشترک — `Jetengine_Skin.json meta-1` — **VERIFIED**؛ جدول منشأ نه | READY WITH MODIFICATIONS | #۱۱ پذیرش |

## ۳ — ذخیره، مجموعه، سفر

| # | قابلیت | A | M | P | هست؟ | طبقه | یادداشت |
|---|---|---|---|---|---|---|---|
| ۲۷ | ذخیرۀ مکان (Saved Places) | ۲۷ | ۲۷ | — | بله، جدول `topersia_saved` — `class-topersia-db.php:45-56` — **VERIFIED** | READY | #۱۴ ✓ ذخیره = ارجاع |
| ۲۸ | دنبال‌کردن موجودیت | — | ۲۷ | — | بله، `_topersia_followed` سقف ۵۰۰ — **VERIFIED** | READY | اعلان ندارد |
| ۲۹ | مجموعه (Collections) | ۲۶ | ۲۷ | — | نه — **VERIFIED** | READY WITH MODIFICATIONS | #۱۴: گسترش همان ذخیره، در user-core |
| ۳۰ | سفر (مدل، نسخه، آیتم) | ۱۹–۲۵ | ۲۷ | — | نه؛ تب «سفرهای من» تهی — `class-topersia-dashboard.php:21,35-41` — **VERIFIED** | NEEDS INFRASTRUCTURE | #۱۲–۱۳ و P5-D299 «پیشنهادی»؛ ناهماهنگ با ROADMAP user-core (USER.md ۹) |
| ۳۱ | بهینه‌ساز مسیر قطعی (ترتیب بازدید) | ۲۳ | — | ۱۸ | نه؛ سرویس‌های مسیریابی/ماتریس خاموش — `topersia-services.php:60-61` | NEEDS INFRASTRUCTURE | #۲۱ پذیرش؛ نیازمند ماتریس فاصله (Neshan)، کلید، سقف |
| ۳۲ | ویرایش تدریجی سفر با وصله (patch) و نسخه | ۲۴–۲۵ | — | — | نه | FUTURE | بعد از ۳۰ |
| ۳۳ | ویرایش دستی سفر بی هوش مصنوعی | ۱۲ | — | — | نه | FUTURE | #۱۲: مالک user-core |
| ۳۴ | سفر مشارکتی (Collaboration) | ۳۲ | — | — | نه | FUTURE | #۲۲ تعویق ✓ (P5-D298) |
| ۳۵ | Start Anywhere | ۲۹ | — | — | نه | FUTURE | #۲۲ ✓ |
| ۳۶ | تعامل صوتی | ۳۱ | — | — | نه | FUTURE | #۲۲ ✓ |
| ۳۷ | PWA / آفلاین | ۶۲ | — | — | نه | FUTURE | #۲۲ ✓ |
| ۳۸ | دوربین جادویی / ToPersia Lens / Food Lens | ۳۰ | ۳۱–۳۲ | — | نه | FUTURE | هزینه و حریم تصویر؛ P5-D298 |

## ۴ — پروفایل، شخصی‌سازی، اعلان

| # | قابلیت | A | M | P | هست؟ | طبقه | یادداشت |
|---|---|---|---|---|---|---|---|
| ۳۹ | ورود با پیامک (OTP) | — | ۲۳ | — | بله — `class-topersia-auth.php:345-517` — **VERIFIED** | READY | فقط شماره‌های `09…` |
| ۴۰ | ورود برای کاربر غیرایرانی (گوگل/ایمیل) | — | — | — | نه؛ `auth.google` خاموش در کاتالوگ — **VERIFIED** | READY WITH MODIFICATIONS | P5-D257؛ ROADMAP user-core فاز ۳ (دروازۀ تصمیم) |
| ۴۱ | کشور کاربر | — | ۱۹ | — | بله، متن آزاد `_topersia_country` — `class-topersia-profile.php:27` — **VERIFIED** | READY WITH MODIFICATIONS | ARCH §۳ «نامعلوم» ← اصلاح (هست، ولی بی ISO) |
| ۴۲ | پروفایل علاقه | ۳۸ | ۲۰ | — | نیمه: `_topersia_travel_style` رشتۀ آزاد | READY WITH MODIFICATIONS | P5-D258؛ نگاشت ورزش/موسیقی به نوع پست |
| ۴۳ | ترجیحات نمایش (حالت، اندازه، حرکت) | — | — | — | بله — `class-topersia-display-prefs.php:35-50` | READY | — |
| ۴۴ | داشبورد کاربر (تب‌ها) | — | ۲۷ | — | نیمه: ۸ تب، ۲ تب خالی (سفر، اعلان) — **VERIFIED** | READY WITH MODIFICATIONS | P5-D303 سه‌ستونه |
| ۴۵ | اعلان (پنل · پیامک · ایمیل) | — | ۲۳–۲۶ | — | نه — `notification_count` ثابت ۰ — **VERIFIED** | NEEDS INFRASTRUCTURE | ایمیل: `mail()` غیرفعال (ASSUMED)؛ پیامک عمومی نیست |
| ۴۶ | پیامک پس از OTP: کانال اعلان با رضایت و سقف | — | ۲۳–۲۴ | — | نه؛ ارائه‌دهنده سخت‌کد Mediana | NEEDS INFRASTRUCTURE | ARCH: RWM؛ شواهد کد: انتزاع ارائه‌دهنده لازم است |
| ۴۷ | توصیۀ شخصی و کش توصیه (مهمان/ثبت‌نام‌شده) | ۳۳–۳۴ | ۳۳ | — | نه | FUTURE | — |
| ۴۸ | فرصت سفر شخصی و رویدادمحور | — | ۲۱–۲۲ | — | نه | FUTURE | P5-D297 |
| ۴۹ | زمان‌بندی و قواعد اعلان | — | ۲۵–۲۶ | — | نه | FUTURE | — |

## ۵ — رویداد، تقویم، پایش

| # | قابلیت | A | M | P | هست؟ | طبقه | یادداشت |
|---|---|---|---|---|---|---|---|
| ۵۰ | موجودیت واحد با شناسۀ جهانی (`entity_uid`) | ۴ | ۱ | ۶ | بله — فیلد مشترک `entity_uid` | READY | — |
| ۵۱ | نوع‌های رویداد E1/E3/E5/E6 + P6 | — | ۳ | — | بله، با فیلد تاریخ ساخت‌یافته — `Jetengine_Skin.json` — **VERIFIED** | READY | P5-D214 «منتظر» کهنه (EVENTS.md ۲) |
| ۵۲ | نمایش تاریخ رویداد و JSON-LD `Event` با `startDate` | — | ۴، ۳۷ | — | نه — بی‌جا در رندر؛ شِما `Thing` — `topersia-schema.php:255-257` — **VERIFIED** | READY WITH MODIFICATIONS | مانع: داده پر نیست؟ (UNKNOWN) |
| ۵۳ | آرشیو «رویداد گذشته» و چرخۀ عمر | — | ۵ | — | نه — **VERIFIED** | READY WITH MODIFICATIONS | CA-E-D004 |
| ۵۴ | تقویم اصلی (نمای ماهانه/فهرست) | — | ۲ | — | نه | READY WITH MODIFICATIONS | P5-D295 |
| ۵۵ | تقویم کاربر | — | ۲، ۲۷ | — | نه | FUTURE | «نمای دیگری از همان داده» |
| ۵۶ | تقویم فارسی (شمسی ← قمری) | — | ۶ | — | تبدیل نمایشی میلادی←شمسی فقط — `topersia-g-zones.php:1100` — **VERIFIED**؛ مخزن UNKNOWN | READY WITH MODIFICATIONS | پرسش ۱ ARCH |
| ۵۷ | رویداد دستی + اتصال به موجودیت | — | ۷ | — | بله، ۳۸ رابطه | READY | نمایش جهت معکوس ضعیف |
| ۵۸ | عامل پایش وب: API و RSS | — | ۸–۱۴ | — | نه — **VERIFIED** | NEEDS INFRASTRUCTURE | ARCH: RWM؛ شاهد: کرون سیستمی زنده UNKNOWN و ثبت منبع نیست |
| ۵۹ | خزش صفحه‌های JS | — | ۸، ۱۱ | — | نه | NEEDS INFRASTRUCTURE | ARCH ✓ |
| ۶۰ | درخواست تغییر + تأیید انسانی + منشأ | — | ۱۵–۱۷ | — | نیمه: بستۀ محتوا (draft + برگرداندن) — `topersia-pack.php` | READY WITH MODIFICATIONS | تغییر مستقیم محتوای منتشرشده ممنوع |
| ۶۱ | خبر هر موجودیت (`tpc_news`) | — | — | — | بله، فقط محلی — `topersia-g-news.php:30-32` — **VERIFIED** | READY WITH MODIFICATIONS | پیش از زنده ممیزی NotebookLM |
| ۶۲ | تضاد منابع | — | ۱۸ | — | نه | FUTURE | — |

## ۶ — نقشه و جغرافیا

| # | قابلیت | A | M | P | هست؟ | طبقه | یادداشت |
|---|---|---|---|---|---|---|---|
| ۶۳ | MapLibre + PMTiles + Protomaps خودمیزبان | ۳۷ | — | ۵ | بله، MapLibre 5.24.0 — `assets/map/maplibre-gl.js:3` — **VERIFIED** | READY | PMTiles محلی/زنده UNKNOWN |
| ۶۴ | افزونۀ RTL (حفظ) | — | — | ۴٫۶ | بله، تنبل — `topersia-g-zones.js:2062-2067` | READY | ARCH ✓ |
| ۶۵ | حذف افزونۀ RTL / مهاجرت MapLibre v6 | — | — | ۴٫۶–۴٫۷ | نه | FUTURE | ARCH: تعویق ✓ |
| ۶۶ | برچسب نقشه به فارسی (زنجیرۀ بازگشت `name:fa`) | — | — | ۱۳ | نیمه: زنجیره هست؛ قلم Italic گلیف ندارد — MAP.md ۶ — **VERIFIED** | READY WITH MODIFICATIONS | ایراد واقعی |
| ۶۷ | لایۀ Map Query با پشتیبان قابل‌تعویض | ۳۶ | — | ۹–۱۰ | نیمه: توابع `topersia_geo_index_*` بی رابط رسمی | READY WITH MODIFICATIONS | ARCH §۴ پذیرش |
| ۶۸ | نمایۀ مشتق مکانی A (DECIMAL + نمایۀ ترکیبی) | ۳۶ | — | ۹ | بله، **فقط محلی** — `topersia-geo-index.php:26-28,63-76` — **VERIFIED** | READY WITH MODIFICATIONS | بنچمارک A انجام و انتخاب شد |
| ۶۹ | نمایۀ مکانی B (POINT + SPATIAL) | — | — | ۱۷ | نه | FUTURE | بنچمارک: بی برتری روی پرس‌وجوی زمینه |
| ۷۰ | قرارداد GeoJSON `FeatureCollection` + `meta` | — | — | ۱۲ | نیمه: بی `meta`، سقف ثابت ۴۰۰ — `topersia-g-mappoints.php:80,116` — **VERIFIED** | READY WITH MODIFICATIONS | ARCH: «بی عدد ثابت» ← ناهم‌خوان |
| ۷۱ | خوشه‌بندی GeoJSON + بارگذاری viewport | — | — | ۱۶ | خوشه‌بندی بله (`js:2189`)، viewport نه | READY WITH MODIFICATIONS | — |
| ۷۲ | «اطراف من» (POST، بی LLM) | ۳۵ | — | ۱۸ | نه — فقط TTL رزروشده `nearby` — `topersia-cache.php:244` | READY WITH MODIFICATIONS | #۲۰ پذیرش؛ حریم مختصات |
| ۷۳ | جست‌وجو بی LLM (تاکردن فارسی) | ۳۷ | — | — | نیمه: `topersia_search_fold_rules` + پنجرۀ فهرست؛ نقطۀ جست‌وجوی عمومی سراسری نه | READY WITH MODIFICATIONS | #۲۰ |
| ۷۴ | اعتبارسنج مختصات (DMS، دهدهی، GeoJSON) + «بیرون از ایران» | — | — | ۸ | بله — `topersia-geo.php:23-26,114-224` | READY | کادر ایران گسترده (MAP.md ۷) |
| ۷۵ | مختصات جعلی از والد ممنوع | — | — | ۸ | رعایت می‌شود — `topersia-geo-index.php:103-125` | READY | — |
| ۷۶ | پیوند مسیریابی (Google · Waze · Neshan · Balad) | — | — | ۱۹ | آداپتور `deep-links` خاموش — `topersia-services.php:63,96-101` — **VERIFIED** | READY WITH MODIFICATIONS | فاز ۳؛ فقط مستند رسمی |
| ۷۷ | مسیریاب/ترافیک/ناوبری گام‌به‌گام خودی | — | — | ۵ | نه | NOT RECOMMENDED | بیرون از Vision: مسیریابی بیرونی (P5-D253)، هزینه/حقوق دادۀ ترافیک؛ Brief هم DEFER کرده |
| ۷۸ | آزمون Range/206/CORS/CDN برای PMTiles | — | — | ۲۰ | نه — UNKNOWN | READY WITH MODIFICATIONS | فاز ۱۰ |
| ۷۹ | درون‌ریزی مکان‌های خارجی (Places) | — | — | ۵ | نه | NOT RECOMMENDED | موجودیت نقشه‌ای تکراری + حقوق داده؛ P5-D253 |
| ۸۰ | ویرایش هندسۀ کامل / تحویل MVT موجودیت | — | — | ۵ | نه | FUTURE | — |
| ۸۱ | بنچمارک A و B | — | — | ۲۲ | **انجام شده** (محلی) — `docs/GEO-BENCH-2F.md` | READY | MAP.md ۹ |

## ۷ — محصول و بازار

| # | قابلیت | A | M | P | هست؟ | طبقه | یادداشت |
|---|---|---|---|---|---|---|---|
| ۸۲ | Iran, Right Now (بسته‌بودن، جاده، محدودیت) | — | ۲۸ | — | فقط هوا | NEEDS INFRASTRUCTURE | منبع دادۀ بسته‌بودن/جاده نداریم (ARCH) |
| ۸۳ | Compare Iran | — | ۲۹ | — | نه | FUTURE | فقط فیلدهای واقعی، صفحۀ ایندکس‌پذیر فقط برای جفت‌های گزیده |
| ۸۴ | Ask Iran | — | ۳۰ | — | نه | FUTURE | پس از جستجو و دروازه |
| ۸۵ | بازار رزرو، پیگیری قیمت، هشدار قیمت | ۸۳ | — | — | نه | NOT RECOMMENDED | «ما رزرو نداریم» (SPRINT ۸٫۱)؛ نیازمند قرارداد، مسئولیت مالی و حقوقی؛ بیرون از Vision (محتوای مرجع) |
| ۸۶ | پلتفرم سازندگان (Creator) | ۸۳ | — | — | نه | FUTURE | — |

## ۸ — حذف‌شده‌ها در سندها (و هنوز در کد؟)

| # | قابلیت | A | M | P | هست؟ | طبقه | یادداشت |
|---|---|---|---|---|---|---|---|
| ۸۷ | عامل خودمختار مرکزی | ۷ | — | — | نه | NOT RECOMMENDED | A و ARCH #۴ رد؛ بی کنترل/آزمون‌پذیری، هزینۀ بی‌حد |
| ۸۸ | چت‌بات عمومی بی‌قید | ۸۳ | — | — | نه | NOT RECOMMENDED | P5-D255 |
| ۸۹ | ارسال هم‌زمان خلاصه به سه ارائه‌دهنده | ۷، ۱۳ | — | — | نه؛ ولی **دکمه‌های ۷ دستیار بیرونی پیش‌فرض روشن** در کد — `class-tp-ai-options.php:42,44` | NOT RECOMMENDED | P5-D255 «هیچ پیوند بیرونی»؛ ناهم‌خوان در کد (مقدار زنده UNKNOWN) |
| ۹۰ | LLM به‌عنوان موتور جست‌وجوی مکانی / مسیریابی | ۸۳ | — | ۴٫۱ | نه | NOT RECOMMENDED | خطای واقعیت جغرافیایی، هزینه، عدم‌آزمون‌پذیری |
| ۹۱ | موتور ترجمۀ مستقل در برابر WPML | ۸۳ | — | — | نه | NOT RECOMMENDED | WPML منبع حقیقت ترجمه (ARCH §۱) |
| ۹۲ | صف دوم همه‌منظوره | ۸۳ | — | — | نه (فقط دو کرون وردپرس) | NOT RECOMMENDED | P5-D290؛ دو کرون `topersia_log_prune` و `topersia_user_core_prune` باید به AS بروند (SYSTEM.md ۴) |
| ۹۳ | فراخوانی مستقیم مرورگر ← ارائه‌دهندۀ LLM | ۸۳ | — | — | نه (سمت سرور) — **VERIFIED** | NOT RECOMMENDED | افشای کلید و دور زدن سقف |
| ۹۴ | موجودیت کانونی خودکار از خروجی هوش مصنوعی | ۸۳ | — | — | نه (بسته: draft + `needs_verification`) | NOT RECOMMENDED | P5-D272 |
