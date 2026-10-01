در پروژه فعلی WordPress سایت ToPersia، قابلیت موجود «روایت صوتی» را بررسی و بازطراحی کن.

### هدف اصلی

روایت صوتی نباید متن صفحه را کلمه‌به‌کلمه بخواند.

محتوای صفحه فقط باید به‌عنوان **منبع اطلاعات و مرجع اصلی** استفاده شود و Gemini باید بر اساس همان اطلاعات، یک **پادکست واقعی، داستانی، طبیعی و چنددقیقه‌ای** بسازد.

خروجی باید شبیه یک برنامه کوتاه Travel Podcast / Audio Guide باشد، نه Text-to-Speech ساده.

---

# 1. رفتار مورد انتظار

فرض کن صفحه مربوط به یک مقصد، شهر، جاذبه، موزه یا بنای تاریخی است.

اطلاعات صفحه می‌تواند شامل این موارد باشد:

* عنوان
* خلاصه
* متن اصلی
* تاریخچه
* توضیحات
* معماری
* فرهنگ
* اطلاعات سفر
* نکات بازدید
* موقعیت
* سایر فیلدهای مرتبط JetEngine
* محتوای زبان مربوطه در WPML

Gemini باید این اطلاعات را دریافت کند و یک سناریوی کاملاً جدید برای پادکست تولید کند.

### ممنوع

هرگز نباید:

* متن صفحه را عیناً بخواند.
* پاراگراف‌های صفحه را پشت سر هم به صدا تبدیل کند.
* محتوای جدید و تأییدنشده درباره مقصد اختراع کند.
* اطلاعات تاریخی، آماری یا جغرافیایی خارج از منبع را بدون وجود در محتوای مرجع اضافه کند.
* پادکست را به شکل مقاله رسمی اجرا کند.

### مجاز

Gemini می‌تواند:

* ترتیب اطلاعات را تغییر دهد.
* جملات را طبیعی‌تر کند.
* بین موضوعات transition ایجاد کند.
* مقدمه داستانی بسازد.
* سؤال و جواب بین دو گوینده ایجاد کند.
* اطلاعات صفحه را به شکل داستان و گفتگو ارائه کند.
* برای جذابیت، از اطلاعات موجود در صفحه یک narrative بسازد.
* لحن محاوره‌ای، طبیعی و گردشگری داشته باشد.
* در صورت مناسب بودن، با یک سؤال جذاب شروع کند.

---

# 2. فرمت پادکست

پادکست باید به شکل **دو گوینده** باشد.

### Speaker 1

Host / مجری

نقش:

* شروع جذاب
* پرسیدن سؤال
* ایجاد کنجکاوی
* وصل کردن بخش‌های مختلف
* حفظ ریتم گفتگو

### Speaker 2

Travel Guide / راهنمای سفر

نقش:

* توضیح اطلاعات
* تعریف داستان
* ارائه جزئیات تاریخی و فرهنگی
* پاسخ به سؤال‌های مجری
* ایجاد حس حضور در مقصد

گفتگو باید طبیعی باشد، نه اینکه هر دو گوینده صرفاً اطلاعات را به صورت نوبتی بخوانند.

مثال ساختار:

Host:
«تصور کنید صبح وارد یکی از معروف‌ترین مکان‌های تاریخی ایران شده‌اید... اما چیزی که اینجا می‌بینید فقط چند ساختمان قدیمی نیست.»

Guide:
«دقیقاً. این مکان بخشی از داستانی است که قرن‌ها از تاریخ ایران را در خودش نگه داشته...»

Host:
«پس بیایید ببینیم چرا اینجا تا این اندازه مهم شده است.»

Guide:
«برای فهمش باید کمی به گذشته برگردیم...»

این فقط نمونه ساختار است و نباید عیناً در تمام صفحات استفاده شود.

---

# 3. مدت زمان

مدت پیش‌فرض:

**3 تا 5 دقیقه**

در UI امکان انتخاب مدت داشته باشد:

* 2 دقیقه
* 4 دقیقه
* 6 دقیقه

اما مدت نهایی باید با حجم و اهمیت محتوای صفحه متناسب باشد.

اگر محتوای صفحه کوتاه است، Gemini نباید با اطلاعات ساختگی مدت را پر کند.

---

# 4. ساخت سناریو

برای تولید سناریو از مدل:

`gemini-3.8-flash`

استفاده کن.

مدل باید ابتدا محتوای واقعی صفحه را دریافت کند و سپس Podcast Script تولید کند.

Prompt داخلی Gemini باید تقریباً این منطق را داشته باشد:

"You are a professional travel documentary podcast writer.

Use the supplied webpage content as the authoritative source.

Do NOT read or reproduce the webpage verbatim.

Create an original conversational travel podcast based strictly on the supplied facts.

The podcast must feel like a real conversation between a host and a knowledgeable travel guide.

Use storytelling, curiosity, natural transitions, questions, answers, context and atmosphere.

Do not invent historical facts, dates, names, statistics, locations or claims that are not supported by the source content.

You may reorganize and paraphrase the information.

Do not mention that you are an AI.

Do not say 'according to the webpage'.

Do not sound like an article being read aloud.

Make the conversation natural and engaging.

Target duration: 3-5 minutes.

Create a clear beginning, middle and ending."

این Prompt را متناسب با زبان خروجی dynamically تنظیم کن.

---

# 5. خروجی ساختاریافته

سناریو را به‌صورت JSON معتبر دریافت کن.

ساختار پیشنهادی:

{
"title": "...",
"language": "fa",
"estimated_duration_seconds": 240,
"speakers": [
{
"id": "host",
"name": "Host"
},
{
"id": "guide",
"name": "Travel Guide"
}
],
"segments": [
{
"speaker": "host",
"text": "...",
"style": "warm, curious, natural"
},
{
"speaker": "guide",
"text": "...",
"style": "knowledgeable, warm, documentary"
}
]
}

حتماً JSON را validate کن و در صورت malformed بودن، خطای کنترل‌شده داشته باش.

---

# 6. سه زبان

این سیستم باید برای سه زبان کار کند:

* Persian / fa
* English / en
* Arabic / ar

محتوای همان زبان را از WPML دریافت کن.

یعنی برای نسخه فارسی، محتوای فارسی منبع باشد.

برای English، محتوای انگلیسی منبع باشد.

برای Arabic، محتوای عربی منبع باشد.

سناریوها نباید صرفاً ترجمه لفظ‌به‌لفظ یکدیگر باشند.

برای هر زبان یک Podcast Script طبیعی و متناسب با همان زبان تولید کن.

---

# 7. تولید صدا

بعد از ساخته شدن Script، آن را به Gemini TTS ارسال کن.

مدل:

`gemini-3.8-flash-tts`

از Multi-Speaker TTS استفاده کن.

هر segment باید speaker مشخص داشته باشد.

Host و Travel Guide باید صدای متفاوت داشته باشند.

برای هر turn، style مناسب ارسال کن.

برای مثال:

Host:
"warm, curious, friendly travel podcast host, natural conversational delivery"

Guide:
"knowledgeable travel guide, warm documentary tone, clear and engaging delivery"

از لحن خشک و robotic جلوگیری کن.

Gemini 3.8 Flash TTS برای multi-speaker از دو گوینده پشتیبانی می‌کند و برای podcast/long-form narration مناسب است. از API رسمی فعلی Gemini استفاده کن.

---

# 8. API

API Key نباید هرگز داخل JavaScript frontend قرار گیرد.

API Key فقط server-side باشد.

در WordPress از Backend PHP و `wp_remote_post()` یا abstraction موجود پروژه برای فراخوانی Gemini استفاده کن.

به‌هیچ‌وجه وابستگی به Node.js ایجاد نکن.

API Key را از configuration امن دریافت کن.

ترجیحاً:

`GEMINI_API_KEY`

در محیط/configuration.

اگر پروژه سیستم Settings دارد، یک فیلد:

`Gemini API Key`

هم اضافه کن، ولی کلید را در HTML، JS، REST response یا frontend expose نکن.

API requests باید server-side انجام شوند.

---

# 9. TTS API

برای Gemini 3.8 Flash TTS از API فعلی Google استفاده کن.

مدل:

`gemini-3.8-flash-tts`

خروجی صوتی پیش‌فرض WAV است.

از فایل WAV برگشتی مستقیماً استفاده کن و آن را مجدداً WAV wrap نکن.

Audio را Base64 decode کن و فایل را ذخیره کن.

در صورت امکان ساختار ذخیره‌سازی به شکل:

/uploads/topersia-audio/

/post-{POST_ID}/

fa-podcast.wav
en-podcast.wav
ar-podcast.wav

باشد.

ولی قبل از ایجاد مسیر جدید، ساختار موجود پروژه را بررسی کن و اگر سیستم Media/Storage موجود است، از همان استفاده کن.

---

# 10. WordPress Media

فایل صوتی تولیدشده باید به WordPress Media Library اضافه شود.

برای هر زبان:

* Audio URL
* Attachment ID
* Duration
* Language
* Generated At
* Model
* Script

ذخیره شود.

اگر فایل قبلاً برای همان محتوا و همان زبان تولید شده، از تولید مجدد بی‌دلیل جلوگیری کن.

دکمه:

`Regenerate Podcast`

برای تولید نسخه جدید وجود داشته باشد.

---

# 11. UI موجود را خراب نکن

قبل از هر تغییری، قابلیت فعلی «روایت صوتی» را پیدا و بررسی کن.

آن را حذف نکن مگر اینکه واقعاً لازم باشد.

ساختار فعلی:

* UI
* shortcode
* Elementor integration
* JetEngine integration
* WPML
* meta fields
* AJAX/REST
* CSS

را بررسی کن و با معماری فعلی هماهنگ شو.

هدف این است که قابلیت موجود ارتقا پیدا کند، نه اینکه یک سیستم موازی و بی‌ربط ساخته شود.

---

# 12. UI پیشنهادی

در پنل ادمین یا همان محل فعلی Audio Story گزینه‌ای مثل:

### AI Travel Podcast

نمایش بده.

گزینه‌ها:

Language:
[ Persian ] [ English ] [ Arabic ]

Duration:
[ 2 min ] [ 4 min ] [ 6 min ]

Format:
[ Travel Podcast ]

Speakers:
[ Host + Travel Guide ]

دکمه:

`Generate Podcast`

بعد از تولید:

`Preview Script`

`Preview Audio`

`Regenerate`

و وضعیت:

`Generated`
`Generating`
`Failed`

---

# 13. نمایش در Frontend

در صفحه مقصد یک Audio Player ساده و حرفه‌ای نمایش داده شود.

مثلاً:

🎧 **Listen to this story**

[ فارسی ] [ English ] [ العربية ]

▶ Play

مدت زمان فایل نیز نمایش داده شود.

Audio Player باید responsive باشد و در Elementor/WordPress بدون مشکل کار کند.

---

# 14. Cache و جلوگیری از هزینه اضافی

محتوا را هر بار دوباره تولید نکن.

Hash یا version مربوط به محتوای صفحه را ذخیره کن.

اگر محتوای صفحه تغییر نکرده و فایل صوتی موجود است:

از همان Audio استفاده کن.

اگر محتوای صفحه تغییر کرد:

Podcast را stale کن.

مثلاً:

source_content_hash
generated_content_hash
generated_at
model
language

ذخیره شوند.

---

# 15. مدیریت خطا

برای این موارد خطای واضح داشته باش:

* API Key وجود ندارد
* Gemini API unavailable
* invalid response
* malformed JSON
* TTS failure
* audio decoding failure
* WordPress upload failure
* timeout
* quota/rate limit

خطا نباید باعث crash شدن صفحه شود.

خطاهای API را امن log کن ولی API Key را هرگز در log ذخیره نکن.

---

# 16. امنیت WordPress

تمام actionها باید:

* nonce داشته باشند
* capability check داشته باشند
* فقط برای کاربران مجاز اجرا شوند
* API Key server-side بماند
* ورودی‌ها sanitize شوند
* خروجی‌ها escape شوند

هیچ API call مستقیم از browser به Gemini انجام نشود.

---

# 17. معماری

قبل از کدنویسی، معماری موجود پروژه را بررسی کن.

از ساختارهای موجود پروژه استفاده کن.

کد جدید را modular نگه دار.

ترجیحاً بخش‌ها جدا باشند:

Gemini Client
Podcast Script Generator
Podcast Validator
TTS Generator
Audio Storage
WordPress Media Integration
Podcast Metadata
Frontend Player

نام‌گذاری code/API/database به انگلیسی باشد.

کامنت‌های کد و documentation نیز انگلیسی باشند.

---

# 18. نکته بسیار مهم درباره محتوای صفحه

این اصل را در کد و Prompt حفظ کن:

**The webpage is the source of truth.**

Gemini باید از محتوای موجود صفحه برای ساخت داستان استفاده کند، نه اینکه خودش یک مقاله درباره آن مکان بنویسد.

اما متن خروجی باید کاملاً بازنویسی‌شده و conversational باشد.

مثلاً:

SOURCE:

"میدان نقش جهان در دوره صفویه ساخته شد و یکی از مهم‌ترین مجموعه‌های تاریخی اصفهان است."

Podcast:

Host:
«وقتی وارد این میدان می‌شوی، اولین چیزی که توجهت را جلب می‌کند فقط اندازه آن نیست؛ بلکه این است که تقریباً همه چیز اطرافت بخشی از یک داستان بزرگ‌تر است.»

Guide:
«این داستان به دوره صفویه برمی‌گردد؛ زمانی که این میدان به یکی از مهم‌ترین فضاهای شهری اصفهان تبدیل شد...»

این نمونه نشان می‌دهد که **Fact همان Fact است، اما wording و delivery جدید است.**

---

# 19. مهم‌ترین تفاوت با سیستم قبلی

سیستم قبلی:

Page Content
→ TTS
→ Voice

سیستم جدید:

Page Content
→ Content Extraction
→ Gemini Story Writer
→ Podcast Script
→ Validation
→ Multi-Speaker Gemini TTS
→ Audio
→ WordPress Media
→ Frontend Player

این تفاوت را در implementation حفظ کن.

---

# 20. API Model Configuration

مدل‌ها hard-code نشوند مگر به‌عنوان default.

Configuration:

GEMINI_TEXT_MODEL=
gemini-3.8-flash

GEMINI_TTS_MODEL=
gemini-3.8-flash-tts

در صورت تغییر مدل در آینده، امکان تغییر configuration وجود داشته باشد.

---

# 21. قبل از پایان کار

بعد از implementation:

1. کد فعلی مربوط به روایت صوتی را کامل بررسی کن.
2. مسیر واقعی محتوای WordPress/WPML/JetEngine را پیدا کن.
3. integration را با معماری فعلی انجام بده.
4. API را server-side تست کن.
5. سناریوی فارسی تولید کن.
6. سناریوی انگلیسی تولید کن.
7. سناریوی عربی تولید کن.
8. TTS دوگوینده را تست کن.
9. فایل صوتی را در Media Library ذخیره کن.
10. Frontend player را تست کن.
11. nonce/capability/security را تست کن.
12. error handling را تست کن.
13. اگر تستی برای پروژه وجود دارد اجرا کن.
14. در پایان دقیقاً گزارش بده:

* چه فایل‌هایی تغییر کردند
* چه فایل‌هایی ایجاد شدند
* API چگونه متصل شده
* مدل‌های مورد استفاده
* metadata کجا ذخیره می‌شود
* چطور podcast را regenerate کنیم
* چه تست‌هایی موفق شدند
* چه مواردی نیاز به تصمیم یا بررسی انسانی دارند

**قبل از شروع implementation اول repository را بررسی کن و معماری موجود را بفهم. بدون نیاز، ساختار فعلی را بازنویسی نکن.**
