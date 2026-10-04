# ToPersia — Master Architecture Audit & Expansion

## نقش تو

تو به‌عنوان **Lead Software Architect + Senior Full-Stack Engineer + Product Architect** پروژه ToPersia عمل می‌کنی.

تو پروژه فعلی ToPersia را خودت ساخته و توسعه داده‌ای و بنابراین قبل از هر تصمیم جدید باید **Vision، معماری فعلی، کد، ساختار داده، قابلیت‌های موجود و تصمیم‌های قبلی پروژه را بررسی کنی**.

این Prompt برای شروع توسعه فوری نیست.

هدف این مرحله این است که ابتدا مشخص کنی:

- چه چیزهایی همین حالا در معماری موجود وجود دارند.
- چه چیزهایی قابلیت توسعه روی معماری فعلی را دارند.
- چه چیزهایی نیاز به تغییر معماری دارند.
- چه چیزهایی نیازمند سرویس یا Worker خارجی هستند.
- چه چیزهایی در حال حاضر زود است و باید برای آینده نگه داشته شوند.
- چه چیزهایی با Vision فعلی ToPersia ناسازگارند.

---

# قانون بسیار مهم

**هیچ‌چیز را فقط به این دلیل که در این Prompt آمده پیاده‌سازی نکن.**

ابتدا پروژه فعلی را Audit کن.

تو باید بر اساس معماری واقعی فعلی تصمیم بگیری، نه بر اساس فرضیات این Prompt.

بنابراین:

- ساختار فعلی پروژه را دوباره از صفر طراحی نکن.
- سیستم‌های موجود را بدون دلیل بازنویسی نکن.
- قابلیت‌های تکراری ایجاد نکن.
- Data Sourceهای موازی نساز.
- برای هر قابلیت Plugin یا سیستم جداگانه ایجاد نکن مگر اینکه واقعاً لازم باشد.
- اگر سیستم موجود قابلیت توسعه دارد، همان را توسعه بده.
- اگر معماری فعلی برای یک قابلیت مناسب نیست، ابتدا مشکل معماری را توضیح بده.
- اگر قابلیت نیازمند Worker یا سرویس خارجی است، آن را به WordPress تحمیل نکن.
- اگر چیزی با Vision فعلی ناسازگار است، صریحاً اعلام کن.

---

# وضعیت معماری

ToPersia یک پلتفرم اطلاعاتی و گردشگری درباره ایران است که در مرحله فعلی تمرکز آن روی **Iran Geo** است.

هدف، ساخت یک منبع جامع، معتبر، چندزبانه و ساختاریافته درباره ایران است که علاوه بر کاربران، برای Search Engineها و سیستم‌های AI نیز قابل فهم و قابل استناد باشد.

زبان‌های فعلی:

- فارسی
- انگلیسی
- عربی

معماری باید از ابتدا برای توسعه آینده ToPersia به یک Tourism Platform بزرگ‌تر آماده باشد، اما نباید قابلیت‌های آینده بدون نیاز واقعی زودتر از موعد پیاده شوند.

---

# اصل معماری

ToPersia باید به‌عنوان یک **Data-driven Tourism Platform** طراحی شود.

WordPress می‌تواند هسته مدیریتی و Application Core باشد، اما نباید تمام پردازش‌های سنگین به WordPress تحمیل شوند.

برای کارهای سنگین مانند:

- Web Monitoring
- Crawling
- Data Extraction
- AI Processing
- Background Jobs
- Queue Processing
- Scheduled Research
- External Data Synchronization

در صورت نیاز از Worker / Background Service / External Processing استفاده کن.

اما WordPress همچنان باید در صورت مناسب بودن معماری، منبع اصلی مدیریت و نمایش داده‌های ToPersia باقی بماند.

---

# اصل مهم: یک Source of Truth

هیچ سیستم موازی و Duplicate برای داده‌های اصلی ایجاد نکن.

به‌خصوص:

- Entity
- Content
- Event
- User
- User Interest
- Notification
- Calendar

باید تا حد امکان از همان سیستم‌های موجود استفاده کنند.

---

# 1. Unified Entity Architecture

ToPersia دارای سیستم Entity است.

هر Entity دارای یک شناسه جهانی دائمی است که پس از انتشار تغییر نمی‌کند و در تمام زبان‌ها یکسان باقی می‌ماند.

معماری فعلی Entity را بررسی کن و آن را حفظ کن.

هر قابلیتی که با Entity کار می‌کند باید تا حد امکان به همین Entity Architecture متصل شود.

از ایجاد Entity Duplicate جلوگیری کن.

اگر یک سیستم خارجی Entity مشابهی را پیدا کرد، ابتدا باید تلاش کند آن را با Entity موجود Match کند.

اگر Match قطعی نیست، برای بررسی انسانی ارسال شود.

---

# 2. Unified Calendar & Event System

ToPersia باید فقط **یک سیستم مرکزی Calendar & Event** داشته باشد.

دو Calendar جداگانه ایجاد نکن.

این سیستم یک Data Source مشترک خواهد داشت و حداقل دو Interface خواهد داشت:

### A. Main Calendar

صفحه اصلی Calendar / Events که کاربران می‌توانند رویدادهای ایران را مشاهده و بررسی کنند.

### B. User Calendar

Calendar داخل User Panel که همان Eventهای سیستم اصلی را نمایش می‌دهد و رویدادهای مرتبط با کاربر را برجسته می‌کند.

این دو فقط دو View از یک Event System هستند.

---

# 3. Event System

Event باید یک داده ساختاریافته واقعی باشد.

Event می‌تواند شامل مواردی مانند:

- عنوان
- توضیح
- تاریخ شروع
- تاریخ پایان
- ساعت
- مکان
- شهر
- برگزارکننده
- نوع Event
- وضعیت
- منبع
- وضعیت تأیید
- Recurrence در صورت نیاز
- تاریخ آخرین تغییر
- اطلاعات Source
- روابط با Entityها

باشد.

اما ساختار نهایی را با توجه به معماری فعلی پروژه تعیین کن.

Eventها باید بتوانند به یک یا چند Entity مرتبط شوند.

مثلاً:

```text
Event
↓
Related Entity
↓
Content Page
```

اگر Event مربوط به یک شهر، موزه، سالن، منطقه، جاذبه یا مقصد باشد، باید بتواند از طریق Relation موجود به آن Entity متصل شود.

---

# 4. Dynamic Event Display

وقتی Event به یک Entity مرتبط شد، اطلاعات Event نباید دوباره به‌صورت دستی داخل صفحه Content وارد شود.

صفحه Entity باید بتواند Eventهای مرتبط را به‌صورت Dynamic نمایش دهد.

مثلاً:

صفحه شیراز

→ رویدادهای مرتبط

→ نمایشگاه‌ها

→ کنسرت‌ها

→ فستیوال‌ها

→ مسابقات

و غیره.

همه این اطلاعات باید از Event System واحد خوانده شوند.

---

# 5. Event Lifecycle

Event باید بتواند وضعیت‌هایی مانند این را در صورت نیاز پشتیبانی کند:

- Upcoming
- Active
- Past
- Cancelled
- Rescheduled
- Changed
- Recurring
- Imported
- Manual
- Pending Review

اگر Event تغییر کرد یا لغو شد، سیستم‌های وابسته نیز باید بتوانند تغییر را تشخیص دهند.

مثلاً:

Event لغو شد

→ Calendar به‌روزرسانی شود

→ Notification مربوطه بررسی شود

→ پیشنهاد سفر مرتبط بررسی شود

→ اطلاعات صفحات مرتبط به‌روز شود.

---

# 6. Persian Calendar Integration

Repository مربوط به Persian Calendar که در Context پروژه مشخص شده است را بررسی کن.

قبل از هر تصمیم:

- ساختار واقعی Repository را بررسی کن.
- داده‌های قابل استفاده را مشخص کن.
- Holidayها را بررسی کن.
- Eventها را بررسی کن.
- Recurrence را بررسی کن.
- نحوه Update شدن داده را بررسی کن.
- License را بررسی کن.
- Attribution موردنیاز را بررسی کن.
- مشخص کن آیا بهتر است Import شود، Sync شود یا فقط Reference شود.

هیچ فرضی درباره API یا License نداشته باش.

از Repository خارجی چیزی را تغییر نده.

هدف این است که Calendar ToPersia بتواند در صورت امکان از این منبع استفاده کند، اما همچنان **یک Event System واحد** داشته باشد.

---

# 7. Admin Event Creation

Admin باید بتواند Eventهای جدید را به‌صورت دستی ایجاد کند.

Eventها فقط محدود به Holiday نباشند.

نمونه‌ها:

- Football
- Sports
- Concert
- Exhibition
- Festival
- Cultural Event
- Conference
- Local Event
- Museum Event
- Tourism Event
- Seasonal Event

Admin باید بتواند Event را به Entityهای موجود متصل کند.

Eventهای Manual و Imported باید در همان Event System قرار بگیرند.

---

# 8. Web Research / Monitoring Agent

یک سیستم **Web Research & Monitoring Agent** طراحی کن.

هدف این سیستم این است که Admin بتواند Sourceهای خارجی را تعریف کند و سیستم به‌صورت دوره‌ای آنها را بررسی کند.

نمونه Sourceها:

- سایت‌های رسمی
- سایت‌های بلیت
- سایت‌های کنسرت
- سایت‌های ورزشی
- سایت‌های فوتبال
- سایت‌های نمایشگاه
- سایت‌های فستیوال
- سایت‌های موزه
- سایت‌های گردشگری
- سایت‌های دولتی
- اطلاعات مرز
- اطلاعات Visa
- اطلاعات حمل‌ونقل
- فرودگاه‌ها
- هر منبع دیگری که اطلاعات قابل تغییر درباره ایران دارد.

---

# 9. Source Configuration

Admin باید بتواند Sourceها را تعریف و مدیریت کند.

برای هر Source در صورت نیاز اطلاعاتی مانند:

- Source Name
- Website
- Source Type
- Data Type
- Monitoring Frequency
- Reliability
- Extraction Method
- API availability
- RSS availability
- Structured Data availability
- Last Check
- Last Successful Check
- Status
- Notes
- Attribution
- Legal / Usage Notes

نگهداری شود.

اما ساختار نهایی را بر اساس معماری فعلی تعیین کن.

---

# 10. Monitoring Frequency

همه Sourceها نباید با یک Frequency بررسی شوند.

سیستم باید در صورت نیاز Frequencyهای مختلف را پشتیبانی کند:

- چند بار در روز
- روزانه
- هفتگی
- ماهانه
- On Demand

Frequency باید بر اساس نوع داده و میزان تغییرپذیری قابل تنظیم باشد.

مثلاً:

اطلاعات مسابقات فوتبال → Frequent

اطلاعات ویزا → بر اساس اهمیت و نیاز

اطلاعات عمومی موزه → کمتر

---

# 11. API First / Responsible Monitoring

اگر Source دارای:

- API
- RSS
- Structured Data
- Official Feed

است، تا حد امکان از آن به جای Scraping شکننده استفاده کن.

قبل از طراحی Crawler موارد زیر را بررسی کن:

- robots.txt
- Terms of Service
- Rate Limits
- API limits
- Authentication
- Dynamic Rendering
- Access restrictions
- Licensing
- Data usage permissions

این سیستم نباید تبدیل به یک Scraper بی‌قاعده شود.

---

# 12. Data Extraction & Normalization

Agent باید اطلاعات Source را استخراج و به داده ساختاریافته تبدیل کند.

مثلاً:

```text
Source
↓
Extraction
↓
Normalization
↓
Structured Data
```

مثلاً برای یک Concert:

- Title
- Artist
- Venue
- City
- Date
- Time
- Organizer
- Ticket Information
- Source
- Source Reference
- Collection Time

---

# 13. Change Detection

Agent باید اطلاعات جدید را با داده فعلی ToPersia مقایسه کند.

حداقل این وضعیت‌ها باید قابل تشخیص باشند:

- New
- Changed
- Removed
- Unchanged
- Ambiguous

مثال:

```text
Existing:
Concert X
18:00

Source:
Concert X
20:00

Result:
Changed
```

---

# 14. Duplicate Detection

یکی از مهم‌ترین بخش‌ها جلوگیری از Duplicate است.

اگر Agent یک Event جدید پیدا کرد، ابتدا باید بررسی کند که آیا Event موجود مشابهی وجود دارد.

Matching می‌تواند بر اساس ترکیبی از:

- Title
- Date
- Venue
- City
- Organizer
- Source ID
- Entity Relation
- External ID

باشد.

اگر Confidence پایین باشد:

**Automatic Match ممنوع.**

برای Human Review ارسال شود.

---

# 15. Change Request System

Agent نباید مستقیماً Production Content را تغییر دهد.

هر تغییر مهم باید بتواند یک **Change Request** ایجاد کند.

Change Request در صورت نیاز باید شامل موارد زیر باشد:

- Source
- Source Reference
- Detection Time
- Data Type
- Existing Value
- New Value
- Diff
- Proposed Change
- Confidence
- Related Entity
- Related Event
- Status
- Reviewer
- Review Time
- Approval Result

---

# 16. Human Approval

Admin باید بتواند قبل از Apply:

- Approve
- Edit
- Reject

کند.

اما Review فقط Yes / No نباشد.

Admin باید بتواند:

- اطلاعات استخراج‌شده را اصلاح کند.
- Entity مرتبط را تغییر دهد.
- Event موجود را انتخاب کند.
- بخشی از پیشنهاد را قبول کند.
- بخشی را رد کند.
- متن یا مقدار نهایی را اصلاح کند.

سپس فقط نسخه نهایی تأییدشده وارد Production شود.

---

# 17. Provenance

برای اطلاعاتی که از منابع خارجی وارد ToPersia می‌شوند باید تا حد امکان مشخص باشد:

- از کجا آمده‌اند.
- چه زمانی دریافت شده‌اند.
- آخرین بار چه زمانی بررسی شده‌اند.
- چه کسی تأیید کرده است.
- چه تغییری نسبت به قبل داشته‌اند.

به‌خصوص برای اطلاعات حساس مانند:

- Visa
- Border
- Entry Requirements
- Travel Restrictions
- Government Information

این موضوع اهمیت بیشتری دارد.

---

# 18. Conflicting Sources

اگر دو Source معتبر اطلاعات متفاوتی ارائه کردند، سیستم نباید خودش یکی را بدون دلیل انتخاب کند.

باید Conflict را مشخص کند و برای Human Review ارسال کند.

Source Reliability باید در معماری قابل مدیریت باشد.

---

# 19. User Country

سیستم User باید کشور کاربر را در صورت وجود این قابلیت در معماری فعلی بشناسد.

از سیستم فعلی استفاده کن.

Duplicate Country/Profile System نساز.

این داده در Personalization، Notification و Travel Opportunity می‌تواند استفاده شود.

---

# 20. User Interest Profile

ToPersia باید از سیستم موجود User Interest/Profile استفاده کند.

علایق می‌توانند شامل مواردی مانند:

- تاریخ
- فرهنگ
- غذا
- هنر
- فوتبال
- ورزش
- طبیعت
- عکاسی
- معماری
- موسیقی
- شهرهای خاص
- نوع سفر

باشند.

اما اگر سیستم Interest فعلی وجود دارد، آن را دوباره ایجاد نکن.

---

# 21. Personalized Travel Opportunity

یک سیستم Opportunity Detection طراحی کن که بتواند از ترکیب:

```text
Calendar
+
Events
+
User Country
+
User Interests
+
User Context
+
ToPersia Data
```

فرصت‌های سفر را شناسایی کند.

این سیستم نباید صرفاً بگوید:

«فردا تعطیل است، SMS بفرست.»

باید بتواند فرصت سفر را تشخیص دهد.

مثلاً:

### مثال 1

دو روز تعطیلی متوالی وجود دارد.

سیستم بررسی می‌کند:

- User چه علایقی دارد؟
- چه اطلاعاتی از User در دسترس است؟
- چه مقصدهایی در ToPersia وجود دارند؟
- چه Eventهایی در آن بازه وجود دارند؟

اگر اطلاعات کافی وجود داشته باشد، یک پیشنهاد Personalized ایجاد شود.

---

# 22. Event-Based Travel Opportunity

Opportunity فقط Holiday نیست.

مثلاً:

User به فوتبال علاقه دارد.

یک مسابقه مهم در شهر دیگری پیدا می‌شود.

سیستم می‌تواند تشخیص دهد:

```text
Football Interest
+
Relevant Event
+
Different City
+
Suitable Date
=
Potential Travel Opportunity
```

یا:

User علاقه‌مند به Art / Photography است.

یک Exhibition مرتبط در شهر دیگری برگزار می‌شود.

این نیز می‌تواند Travel Opportunity باشد.

---

# 23. SMS Architecture

SMS باید **Channel سیستم Notification** باشد، نه یک سیستم مستقل.

Flow:

```text
Event / Holiday
↓
Opportunity Detection
↓
User Context
↓
Personalization
↓
Notification
↓
SMS
↓
User Panel
↓
AI Travel Planning
```

SMS فقط باید کاربر را وارد User Panel کند.

نباید کل برنامه سفر را داخل SMS تولید کند.

---

# 24. SMS Personalization

اگر داده کافی برای Personalization وجود دارد:

پیام می‌تواند متناسب با علایق User باشد.

مثلاً:

> دو روز تعطیلی پیش روست و با توجه به علایق شما، شاید یک سفر کوتاه برایتان مناسب باشد. وارد پنل ToPersia شوید تا برنامه سفر را بررسی کنید.

اگر اطلاعات کافی وجود ندارد:

پیام Generic ارسال شود.

مثلاً:

> تعطیلات پیش روست. وارد پنل ToPersia شوید و سفر خود را برنامه‌ریزی کنید.

اما سیستم نباید اطلاعاتی را که درباره User نمی‌داند، جعل کند.

---

# 25. Notification Rules

Notification باید موارد زیر را در نظر بگیرد:

- Consent
- Opt-in / Opt-out
- Notification Preferences
- Frequency Limits
- Duplicate Prevention
- Relevance Threshold
- Event Changes
- Event Cancellation
- User Context
- Timing

برای Eventهای تغییرکرده یا لغوشده، Notificationهای آینده باید بررسی و در صورت نیاز اصلاح یا Suppress شوند.

---

# 26. Timing

Notification باید بر اساس Event Date زمان‌بندی شود.

مثلاً برای Event مهم:

تقریباً یک هفته قبل

یا بسته به نوع Event:

- چند روز قبل
- یک روز قبل
- نزدیک Event

اما Timing باید Configurable باشد و Hard-code نشود.

---

# 27. User Panel

User Panel باید به مرور تبدیل به مرکز تعامل User با ToPersia شود.

در صورت سازگاری با معماری فعلی، می‌تواند شامل:

- Profile
- Interests
- User Calendar
- Saved Content
- Saved Events
- Travel Planning
- Notifications
- Personalized Recommendations
- Travel Opportunities

باشد.

---

# 28. Iran, Right Now

یک بخش/سیستم با مفهوم:

**Iran, Right Now**

طراحی و امکان‌سنجی کن.

هدف نمایش اطلاعات جاری ایران یا شهرها است.

مثلاً:

- Weather
- Today's Events
- Exhibitions
- Festivals
- Closures
- Restrictions
- Road Conditions
- Important Local Happenings

منابع و Providerها را خودت بعد از Audit مشخص کن.

---

# 29. Compare Iran

سیستم مقایسه مقصدها و مکان‌های ایران.

مثلاً:

- Shiraz vs Isfahan
- Yazd vs Kashan
- Caspian Sea vs Persian Gulf

User بتواند Criteria انتخاب کند.

مقایسه باید بر اساس داده واقعی ToPersia انجام شود.

از Scoreهای بی‌منبع و Rankingهای ساختگی AI استفاده نکن.

---

# 30. Ask Iran

یک سیستم Q&A اختصاصی برای دانش ToPersia.

این نباید صرفاً یک Chatbot عمومی باشد.

پاسخ باید تا حد امکان بر اساس:

- ToPersia Content
- Entities
- Relations
- Events
- Trusted Sources
- Current Data

باشد.

در صورت نیاز، پاسخ باید بتواند Entityهای مرتبط را نیز برای Discovery نمایش دهد.

---

# 31. ToPersia Lens

یک قابلیت Visual Recognition برای شناسایی مواردی مانند:

- مکان‌ها
- بناها
- جاذبه‌ها
- اشیا
- غذا
- عناصر فرهنگی

را امکان‌سنجی و طراحی کن.

هدف فقط تشخیص تصویر نیست.

هدف این است:

```text
Image
↓
Recognition
↓
Entity Matching
↓
Existing ToPersia Entity
```

اگر Entity موجود است، همان Entity استفاده شود.

Duplicate Entity ایجاد نشود.

اگر Confidence پایین باشد، نتیجه باید با Uncertainty نمایش داده شود.

Privacy، API Cost و Performance را بررسی کن.

---

# 32. Food Lens

برای غذا نیز در صورت سازگاری با ToPersia Lens، امکان شناسایی بصری غذاهای ایرانی بررسی شود.

مثلاً:

```text
Image
↓
Food Recognition
↓
Existing Food Entity
↓
Food Content
```

دوباره تأکید:

Entity جدید بدون نیاز و بدون بررسی ایجاد نشود.

---

# 33. Personalized Experience

هدف نهایی این است که ToPersia برای User یک سایت ثابت و یکسان نباشد.

در صورت وجود داده کافی، سیستم بتواند:

- Destinationهای مرتبط
- Eventهای مرتبط
- Content مرتبط
- Travel Opportunities
- Upcoming Events
- Saved Items
- Calendar
- Recommendations

را متناسب با User نمایش دهد.

اما Personalization نباید بر اساس حدس یا اطلاعات ساختگی باشد.

---

# 34. Integration Between Systems

تمام سیستم‌ها باید در نهایت بتوانند از Data مشترک استفاده کنند.

معماری مفهومی:

```text
CONTENT
   │
ENTITY
   │
EVENT
   │
CALENDAR
   │
USER CONTEXT
   │
INTERESTS
   │
OPPORTUNITY DETECTION
   │
NOTIFICATION
   │
SMS
   │
USER PANEL
   │
AI TRAVEL PLANNING
```

و در کنار آن:

```text
EXTERNAL SOURCES
       ↓
MONITORING AGENT
       ↓
DATA EXTRACTION
       ↓
NORMALIZATION
       ↓
CHANGE DETECTION
       ↓
CHANGE REQUEST
       ↓
HUMAN APPROVAL
       ↓
CORE DATA
```

هدف این است که همه سیستم‌ها به جای ساختن دیتابیس‌های جداگانه، تا حد امکان از یک Data Model مشترک استفاده کنند.

---

# 35. ToPersia AI

یک سیستم مستقل **ToPersia AI** از قبل در پروژه تعریف شده است.

آن سیستم را دوباره طراحی نکن.

Duplicate AI Architecture ایجاد نکن.

قابلیت‌های جدید AI باید در صورت نیاز از همان سیستم موجود استفاده کنند.

قبل از هر توسعه جدید بررسی کن که آیا قابلیت موردنظر قبلاً در ToPersia AI پیش‌بینی یا پیاده‌سازی شده است.

---

# 36. Multilingual Architecture

تمام قابلیت‌های جدید باید با معماری چندزبانه فعلی سازگار باشند.

Languages:

- Persian
- English
- Arabic

Event و Entity هویت منطقی واحد خود را در تمام زبان‌ها حفظ کنند.

ترجمه نباید باعث ایجاد Event یا Entity منطقی جدید شود.

---

# 37. SEO / GEO / AEO

برای تمام قابلیت‌های جدید بررسی کن:

- SEO
- GEO
- AEO
- Structured Data
- Canonical
- Internal Linking
- Duplicate Content
- Entity Recognition
- Event Structured Data
- AI Search Visibility

به‌خصوص:

- Event Pages
- Destination Pages
- Ask Iran
- Compare Iran
- Iran, Right Now
- Lens
- External Data
- Event → Entity relationships

---

# 38. Performance

برای هر Feature بررسی کن:

- Database Queries
- API Calls
- AI API Cost
- Background Jobs
- Scheduled Tasks
- Caching
- Search
- Calendar Queries
- Dashboard Queries
- Notifications
- SMS
- Crawling
- Data Extraction
- Image Processing
- External Services

هیچ Feature جدیدی نباید فقط به دلیل اینکه از نظر منطقی ممکن است، بدون بررسی Performance وارد Production شود.

---

# 39. Security & Privacy

موارد زیر را بررسی کن:

- User Profile
- User Interests
- Phone Number
- SMS Consent
- Notification History
- Uploaded Images
- AI Requests
- Personalization Data
- External API Credentials
- Monitoring Credentials
- Source Data
- Crawled Data
- Approval History

برای هر مورد مشخص کن چه سطحی از Security و Privacy لازم است.

---

# 40. Legal / Licensing

برای External Data بررسی کن:

- API License
- Content License
- Scraping Permission
- Terms of Service
- Attribution
- Robots
- Rate Limits
- Data Retention
- Redistribution Rights

به‌خصوص برای:

- Government Data
- Calendar Data
- Sports Data
- Ticketing Data
- Event Data
- Tourism Data

---

# 41. Architecture Classification

هر قابلیت را در یکی از این دسته‌ها قرار بده:

### READY
با معماری فعلی قابل اجراست.

### READY WITH MODIFICATIONS
قابل اجراست ولی نیاز به اصلاح محدود دارد.

### NEEDS INFRASTRUCTURE
نیاز به Worker، سرویس خارجی، Queue یا زیرساخت جدید دارد.

### FUTURE
ایده مناسب است ولی هنوز زمان اجرای آن نیست.

### NOT RECOMMENDED
با Vision یا معماری فعلی سازگار نیست.

اگر چیزی NOT RECOMMENDED است، دلیل فنی و محصولی آن را توضیح بده.

---

# 42. مهم‌ترین محدودیت توسعه

در این مرحله:

**IMPLEMENTATION ممنوع است.**

یعنی:

- DB را تغییر نده.
- Migration نساز.
- Plugin نصب نکن.
- Production را تغییر نده.
- Feature جدید را فعال نکن.
- Schema جدید را بدون تأیید ایجاد نکن.
- Codebase را Refactor نکن.
- Architecture را بدون Approval تغییر نده.

این مرحله فقط:

**AUDIT + ARCHITECTURE + FEASIBILITY + ROADMAP**

است.

---

# 43. Required Audit Output

خروجی تو باید دقیقاً شامل این بخش‌ها باشد:

## A. Current Architecture Findings

معماری فعلی ToPersia را بررسی کن و توضیح بده:

- چه سیستم‌هایی وجود دارند.
- چه سیستم‌هایی آماده توسعه‌اند.
- چه سیستم‌هایی ناقص‌اند.
- چه وابستگی‌هایی وجود دارد.
- چه Technical Debtهایی وجود دارد.

---

## B. Feature-by-Feature Assessment

برای تک‌تک قابلیت‌های این Prompt مشخص کن:

- وضعیت فعلی
- Architecture Fit
- Required Changes
- Dependencies
- Existing Systems That Can Be Reused
- External Services
- Performance Risk
- Cost Risk
- Security/Privacy Risk
- Legal Risk
- Classification

---

## C. Unified Data Flow

یک Data Flow کامل طراحی کن که نشان دهد:

```text
Content
Entity
Event
External Sources
Monitoring Agent
Change Requests
Users
Interests
Calendar
Notifications
SMS
User Panel
Travel Planning
AI
```

چطور با هم ارتباط دارند.

---

## D. External Services

برای هر External Service پیشنهادی مشخص کن:

- Purpose
- Data
- Update Frequency
- Cost
- Reliability
- Legal/Licensing
- Alternatives
- Whether it is actually necessary

از اضافه کردن سرویس غیرضروری خودداری کن.

---

## E. Monitoring Architecture

برای Web Monitoring به‌صورت جداگانه توضیح بده:

- Source Registration
- Monitoring
- Scheduling
- Extraction
- Normalization
- Matching
- Duplicate Detection
- Change Detection
- Confidence
- Conflict Detection
- Change Requests
- Admin Review
- Approval
- Application
- Rejection
- History
- Provenance

و مشخص کن کدام بخش بهتر است داخل WordPress باشد و کدام بخش باید Worker / Background Service باشد.

---

## F. Development Order

یک ترتیب منطقی برای توسعه پیشنهاد بده.

ترتیب را بر اساس Dependency و Architecture تعیین کن، نه بر اساس جذابیت Feature.

---

## G. Risks

حداقل این موارد را بررسی کن:

- Architecture
- Performance
- Scalability
- Cost
- Data Quality
- Duplicate Data
- Privacy
- Security
- Legal
- Licensing
- AI Hallucination
- External API Dependency
- Web Monitoring Reliability
- Maintenance

---

## H. Final Proposed Roadmap

در پایان یک Roadmap مرحله‌ای ارائه بده.

Roadmap باید نشان دهد:

1. چه چیزی باید اول ساخته شود.
2. چه چیزی وابسته به آن است.
3. چه چیزی باید بعداً ساخته شود.
4. چه چیزی نیازمند Infrastructure جدید است.
5. چه چیزی فعلاً نباید ساخته شود.

---

# Final Goal

هدف این پروژه ساخت مجموعه‌ای از Featureهای جداگانه نیست.

هدف ساخت یک **یکپارچه‌ترین و قابل توسعه‌ترین اکوسیستم ToPersia** است.

معماری نهایی باید بتواند این جریان را پشتیبانی کند:

```text
REAL WORLD
     ↓
External Sources
     ↓
Research / Monitoring
     ↓
Verified Data
     ↓
ToPersia Entities & Events
     ↓
Content / Calendar
     ↓
User Context & Interests
     ↓
Opportunity Detection
     ↓
Notifications / SMS
     ↓
User Panel
     ↓
AI Travel Planning
```

در این معماری:

- Event فقط یک Calendar Item نیست.
- Entity فقط یک صفحه نیست.
- User فقط یک Account نیست.
- Notification فقط SMS نیست.
- AI فقط Chatbot نیست.
- Monitoring فقط Scraping نیست.

همه این‌ها باید روی یک Data Architecture مشترک کار کنند.

**اول پروژه فعلی را بررسی کن. سپس Audit کامل را ارائه بده. هیچ تغییری در پروژه ایجاد نکن تا این Audit بررسی و تأیید شود.**
