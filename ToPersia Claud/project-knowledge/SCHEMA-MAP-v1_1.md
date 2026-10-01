# SCHEMA-MAP-v1.1 — نگاشت موجودیت به Schema.org

**وضعیت:** DRAFT — نیازمند اعتبارسنجی و تصویب
**تاریخ:** ۲ سپتامبر ۲۰۲۶ · بازنگری‌شده پس از مقابله با export زنده
**تصمیم:** `P5-D142`
**جایگاه در نقشهٔ راه:** گام ۰ — پیش از ساخت قالب و تولید محتوا

---

## ۱ — این سند چه چیزی هست و چه چیزی نیست

**هست:** یک تصمیم معماری. برای هر موجودیت مشخص می‌کند به کدام نوع Schema.org نگاشت می‌شود و کدام ویژگی‌ها از کدام فیلد تغذیه می‌شوند.

**نیست:** دستور پیاده‌سازی. موتور `topersia-schema` تا پایان فاز قالب فریز می‌ماند — طبق تصمیم ثبت‌شده در `Phase5-HANDOVER-v9.0` بخش ۱۰:

> شِما یک تصویر نهایی از مدل داده است؛ ساختن نگاشت روی مدل ناقص یعنی کار دوباره.

### چرا با این حال الان نوشته می‌شود

اگر نگاشت پس از تولید محتوا تعیین شود، ممکن است رکوردهایی نوشته شوند که فیلدهای لازم برای نگاشتشان خالی مانده — و آن‌وقت باید ۴۲ رکورد دوباره باز شوند.

نمونهٔ عینی از همین پروژه: صفحهٔ سمنان با `geo_coordinates` و `population` خالی ساخته شد. گرهٔ `City` عملاً تهی منتشر شد. اگر پیش از نوشتن آن رکورد می‌دانستیم `City` مختصات می‌خواهد، همان بار اول پر می‌شد.

**پس این سند دو کاربر دارد:** موتور شِما در فاز آینده، و **ویرایشگر محتوا از همین حالا** — به‌عنوان چک‌لیست «چه چیزی باید حتماً پر شود».

---

## ۲ — سه قاعدهٔ حاکم بر انتخاب نوع

### قاعدهٔ ۱ — شاخهٔ ارث‌بری پیش از انتخاب راستی‌آزمایی شود

درس نسخهٔ ۲.۰.۱: پنج ویژگی روی `TouristTrip` گذاشته شده بود که هیچ‌کدام روی شاخهٔ `Trip ← Intangible ← Thing` معتبر نبودند. `distance` و `estimatedDuration` وجود ندارند و اعتبارسنج ردشان کرد.

**الزام:** هر ویژگی در ستون «ویژگی» این سند، پیش از پیاده‌سازی باید با Schema Markup Validator آزموده شود. این سند تصمیم است، نه تأیید.

### قاعدهٔ ۲ — نوع دقیق‌تر بر نوع عام‌تر ترجیح دارد، مگر اینکه ویژگی از دست برود

`City` بر `Place` ترجیح دارد چون دقت جغرافیایی می‌دهد. اما اگر نوع دقیق‌تر ویژگی مهمی را از دست بدهد، نوع عام‌تر با `additionalType` انتخاب می‌شود — همان الگویی که برای G5 به‌کار رفت:

```
"@type": "City",
"additionalType": "https://schema.org/TouristDestination"
```

### قاعدهٔ ۳ — گرهٔ بدون داده ساخته نمی‌شود

اگر همهٔ ویژگی‌های الزامی یک نوع خالی باشند، آن گره اصلاً منتشر نمی‌شود. یک گرهٔ تهی به موتور پاسخ می‌گوید «این موجودیت وجود دارد ولی چیزی دربارهٔ آن نمی‌دانم» — که از سکوت بدتر است.

---

## ۳ — جدول نگاشت

ستون «وضعیت داده» بر پایهٔ export زندهٔ ۲ سپتامبر ۲۰۲۶ است.

### دستهٔ G — جغرافیا

| موجودیت | نوع Schema | نوع تکمیلی | ویژگی‌های کلیدی ← فیلد | وضعیت داده |
|---|---|---|---|---|
| **G1** کشور | `Country` | — | `name` · `description` · `image` · `additionalProperty` ← `population`، `area_km2` | ✅ فیلدها هست |
| **G2** منطقهٔ جغرافیایی | `Place` | `LandmarksOrHistoricalBuildings`؟ | `name` · `description` · `containsPlace` ← رابطه | ⚠️ بدون مختصات |
| **G3** استان | `AdministrativeArea` | — | `name` · `containedInPlace` ← G1 · `additionalProperty` ← `population`، `area_km2` | ✅ |
| **G4** شهرستان | **بدون شِما** | — | — | ✅ عمدی — `noindex` |
| **G5** شهر | `City` | `TouristDestination` | `geo` · `containedInPlace` · `additionalProperty` | ✅ **پیاده شده** |
| **G6** محله | `Place` | — | `geo` · `containedInPlace` ← G5 | ⚠️ فقط ۳ فیلد |
| **G7** روستا | `City` | `TouristDestination` | `geo` · `containedInPlace` · `additionalProperty` ← `population`، `elevation_m` | ✅ |
| **G8** جزیره | `Landform` | `TouristDestination` | `geo` · `additionalProperty` ← `area_km2` | ✅ |
| **G9** کوه / رشته‌کوه | `Mountain` | — | `geo` · `elevation` ← `elevation_m` | ✅ |
| **G10** پهنهٔ آبی | `BodyOfWater` | — | `geo`؟ · `additionalProperty` ← `area_km2` | 🔴 `geo_coordinates` ندارد |
| **G11** کویر | `Landform` | — | `additionalProperty` ← `area_km2` | 🔴 بدون مختصات |
| **G12** جنگل / دشت | `Landform` | — | `additionalProperty` ← `area_km2` | 🔴 بدون مختصات |
| **G13** گذرگاه مرزی | `Place` | — | `geo` · `containedInPlace` | ✅ مختصات دارد |

### دستهٔ A — جاذبه‌ها

| موجودیت | نوع Schema | نوع تکمیلی | ویژگی‌های کلیدی ← فیلد | وضعیت داده |
|---|---|---|---|---|
| **A2** بنای تاریخی | `LandmarksOrHistoricalBuildings` | ← از `classification` | `geo` · `address` · `openingHours` ← `visiting_hours` · `isAccessibleForFree` ← `admission_fee` | ✅ کامل‌ترین |
| **A3** مکان مذهبی | `PlaceOfWorship` | ← از `classification` | همان A2 | ✅ |
| **A7** موزه | `Museum` | — | `geo` · `address` · `openingHours` · `isAccessibleForFree` | ✅ |
| **A9** پارک ملی | `Park` | — | `additionalProperty` ← `area_km2` · `openingHours` | 🔴 بدون مختصات |
| **A10** عارضهٔ طبیعی | `Landform` | ← از `classification` | `geo` · `address` · `additionalProperty` ← `area_km2` | ✅ |
| **A12** محوطهٔ باستانی | `LandmarksOrHistoricalBuildings` | — | `address` | 🔴 بدون مختصات |
| **A13** منظر فرهنگی | `LandmarksOrHistoricalBuildings` | — | — | 🔴 بدون مختصات و نشانی |

### دستهٔ C — فرهنگ و میراث

| موجودیت | نوع Schema | نوع تکمیلی | ویژگی‌های کلیدی ← فیلد | وضعیت داده |
|---|---|---|---|---|
| **C1** صنایع دستی | `Thing` | ← از `classification` | `name` · `description` · `sameAs` ← `external_reference_uri` | ✅ |
| **C2** غذای محلی | `Thing` | — | `name` · `description` | ⚠️ فقط ۴ فیلد · بدون `sameAs` |
| **C5** لباس سنتی | `Thing` | ← از `classification` | `name` · `description` · `sameAs` | ✅ |
| **C6** موسیقی محلی | `Thing` | ← از `classification` | `name` · `description` · `sameAs` | ✅ |
| **C7** رقص محلی | `Thing` | — | `name` · `description` | ⚠️ بدون `classification` — عمدی طبق `CA-C-001` |
| **C8** زبان / گویش | `Language` | — | `name` · `alternateName` | ✅ |
| **C9** قوم / فرهنگ | `Thing` | — | `name` · `description` · `sameAs` | ⚠️ فقط ۳ فیلد |
| **C10** آیین سنتی | `Thing` | ← از `classification` | `name` · `description` · `sameAs` | ✅ |
| **C12** شخصیت تاریخی | `Person` | — | `name` · `birthDate` · `deathDate` · `sameAs` | 🔴 **فیلد سال تولد و وفات ندارد** |
| **C13** دوره / سلسله | `Thing` | — | `name` · `description` · `sameAs` | ⚠️ بدون فیلد سال آغاز و پایان |

### دستهٔ D — تعیین رسمی

| موجودیت | نوع Schema | ویژگی‌های کلیدی | وضعیت داده |
|---|---|---|---|
| **D1–D6** تعیین رسمی | `CollectionPage` | `name` · `description` · `hasPart` ← روابط · `sameAs` | ✅ یک متاباکس مشترک برای هر شش |

### دستهٔ E — رویدادها

| موجودیت | نوع Schema | ویژگی‌های کلیدی ← فیلد | وضعیت داده |
|---|---|---|---|
| **E1** جشنواره | `Festival` | `startDate` · `endDate` · `location` ← `venue_name` + رابطه · `eventSchedule` | ✅ |
| **E3** رویداد ورزشی | `SportsEvent` | همان E1 | ✅ |
| **E5** مراسم مذهبی | `Event` | همان E1 | ✅ |
| **E6** رویداد فصلی | `Event` | `eventSchedule` ← `calendar_timing` | 🔴 **فیلد تاریخ آغاز و پایان ندارد** |

### دستهٔ P — اطلاعات عملی

| موجودیت | نوع Schema | ویژگی‌های کلیدی | وضعیت داده |
|---|---|---|---|
| **P1** ویزا | `GovernmentService` | `name` · `serviceType` · `areaServed` ← G1 · `provider` | ✅ ۲۸ فیلد |
| **P2** ارز و پرداخت | `Thing` | `name` · `description` | ✅ ۳۱ فیلد |
| **P4** سلامت و ایمنی | `Thing` | `name` · `description` | ✅ |
| **P5** فرهنگ سفر | `Thing` | `name` · `description` | ✅ |
| **P6** تقویم | `Thing` | `name` · `description` | ✅ |
| **P7** حمل‌ونقل | `Thing` | `name` · `description` | ✅ |
| **P9** مسیر گردشگری | `TouristTrip` | `itinerary` · `touristType` | ✅ **پیاده شده** |

---

## ۴ — گره‌های سراسری روی هر صفحه

اینها مستقل از نوع موجودیت‌اند و از قبل پیاده شده‌اند:

| گره | منبع |
|---|---|
| `Organization` | ثابت |
| `WebSite` | ثابت |
| `WebPage` | `meta_description` · `ai_summary` → `abstract` · `dateModified` |
| `BreadcrumbList` | زنجیرهٔ روابط JetEngine |
| `FAQPage` | `faq_items` با فیلتر `row_status` |

---

## ۵ — دفتر شکاف داده — بازنگری‌شده

⚠️ **نسخهٔ ۱.۰ این جدول هفت شکاف اعلام کرده بود. پس از مقابلهٔ تک‌به‌تک با export زنده، فقط دو مورد شکاف واقعی بودند.**

علت خطا: جدول اولیه از ماتریس نام فیلد ساخته شده بود، نه از خواندن هر متاباکس. جست‌وجوی `birth_year` نتیجه نداد، در حالی که فیلد `birth_year_approx` وجود داشت. همین اشتباه با `char_limit` در برابر `max_length` هم تکرار شد.

**درس ثبت‌شده:** جست‌وجوی نام دقیق کافی نیست. برای ادعای «فیلد وجود ندارد»، باید کل فهرست فیلدهای آن متاباکس خوانده شود.

| # | ادعای نسخهٔ ۱.۰ | واقعیت | وضعیت |
|---|---|---|---|
| `SG-01` | C12 سال تولد و وفات ندارد | `birth_year_approx` · `death_year_approx` موجودند | ✅ شکاف نبود |
| `SG-02` | E6 تاریخ رویداد ندارد | `peak_start_week` · `peak_end_week` · `calendar_timing` موجودند | ✅ شکاف نبود — تصمیم نگاشت لازم دارد |
| `SG-03` | شش موجودیت مختصات ندارند | درست بود | ✅ **ساخته شد** |
| `SG-04` | C13 سال آغاز و پایان ندارد | `start_year_approx` · `end_year_approx` موجودند | ✅ شکاف نبود |
| `SG-05` | `official_website` نیست | ریپیتر `source_references` روی هر ۴۱ موجودیت — بهتر از یک فیلد تک | ✅ شکاف نبود |
| `SG-06` | پیوند مرجع خارجی فقط روی ۸ موجودیت | درست بود | ✅ **ساخته شد** |
| `SG-07` | ثبت ملی نگاشت ندارد | پنج خانوادهٔ فیلد کامل موجودند | ✅ شکاف نبود — نگاشت لازم دارد |

### دو ساختی که انجام شد

**`SG-06`** — `external_reference_uri` از ۸ متاباکس اختصاصی به متاباکس مشترک منتقل شد. حالا هر ۴۱ موجودیت `sameAs` دارند. متاباکس مشترک: ۱۹ → ۲۰ فیلد.

**`SG-03`** — `geo_coordinates` به G10، G11، G12، A9، A12، A13 اضافه شد. حالا ۱۶ متاباکس مختصات دارند، همه با تعریف یکسان و الزامی.

هیچ‌کدام ویرایش `wpml-config` لازم نداشتند — هر دو کلید از قبل `copy` اعلام شده بودند.

---

## ۵-ب — سه تصمیم نگاشت که از این بررسی درآمدند

### تصمیم ۱ — سال تقریبی به `birthDate` نمی‌رود مگر خالص باشد

`birth_year_approx` عمداً از نوع `text` است، نه `number` — چون باید «حدود ۵۸۰ ق.م» و «۳۲۹–۳۳۰ ق.م» را بپذیرد. این تصمیم درستی در مدل داده است.

اما `birthDate` در Schema.org انتظار `Date` در قالب ISO دارد. متن تقریبی آنجا معتبر نیست.

| شرط | مقصد |
|---|---|
| مقدار عدد چهاررقمی خالص باشد | `birthDate` · `deathDate` |
| هر مقدار دیگر | `additionalProperty` با نام `birthYearApprox` |

همین قاعده برای C13 و `temporalCoverage` هم برقرار است.

### تصمیم ۲ — E6 فعلاً `Thing` می‌شود، نه `Event`

E6 عمداً تاریخ ثابت ندارد. شکوفهٔ بهاری یا مهاجرت پرندگان، هفتهٔ اوج دارد نه تاریخ.

اما `Event` بدون `startDate` معتبر نیست و اعتبارسنج ردش می‌کند. سه راه بررسی شد:

| # | راهکار | حکم |
|---|---|---|
| الف | نگاشت به `Thing` | ✅ معتبر، کم‌ادعا |
| ب | ساختن `startDate` از هفتهٔ اوج سال جاری | ❌ **رد** |
| ج | `Event` با `eventSchedule` به‌جای `startDate` | ⬜ نیازمند تأیید |

**چرا گزینهٔ ب رد شد:** ساختن تاریخی که در داده وجود ندارد، همان دروغ ساختاریافته‌ای است که کل معماری شِمای این پروژه علیه آن بنا شده. یک گرهٔ معتبر و کم‌ادعا از یک گرهٔ دقیق‌نما و ساختگی بهتر است.

گزینهٔ ج از نظر معنایی درست‌ترین است. اگر تأیید شد که `Schedule` بدون `startDate` معتبر است، E6 به آن مهاجرت می‌کند.

### تصمیم ۳ — `source_references` به `citation` می‌رود، نه `url`

ریپیتر منابع روی هر ۴۱ موجودیت هست و چهار زیرفیلد دارد: نام منبع، نشانی، تاریخ مراجعه، وضعیت ردیف.

این ساختار از یک فیلد تک‌نشانی بهتر است — چند منبع می‌گیرد و تاریخ مراجعه دارد که برای یک دانشنامه سیگنال اعتماد است.

| زیرفیلد | ویژگی Schema |
|---|---|
| `source_url` + `source_name` | `citation` |
| `external_reference_uri` | `sameAs` |
| `source_access_date` | داخل متن `citation` |

⚠️ ردیف‌های بازنشسته باید با `topersia_active_rows()` فیلتر شوند، مثل `faq_items`.

### تصمیم ۴ — ثبت ملی و جهانی به `additionalProperty`

پنج خانوادهٔ فیلد موجودند و هیچ‌کدام ویژگی استاندارد Schema ندارند:

| فیلد | متاباکس |
|---|---|
| `world_heritage` · `world_heritage_year` | ۸ |
| `national_monument` · `national_monument_year` | ۵ |
| `biosphere_year` | ۴ |
| `intangible_heritage_year` | ۲ |
| `geopark_year` | ۱ |

همه به `additionalProperty` می‌روند — همان الگویی که برای `population` و `area_km2` به‌کار رفت و روی شاخهٔ `Place` معتبر است.

---

## ۶ — نگاشت `classification` به `additionalType`

هفت موجودیت `classification` دارند و مقادیرشان می‌توانند به نوع دقیق‌تر Schema نگاشت شوند. این جایی است که ۵۳ مقدار یکتای `P5-D141` ارزش عملی پیدا می‌کنند.

| موجودیت | مقدار | نوع Schema پیشنهادی |
|---|---|---|
| A2 | `mausoleum` | `Cemetery`؟ یا `additionalType` متنی |
| A2 | `historic_bridge` | `Bridge` |
| A3 | `mosque` | `Mosque` |
| A3 | `church` | `Church` |
| A3 | `synagogue` | `Synagogue` |
| A3 | `temple` · `fire_temple` | `PlaceOfWorship` + `additionalType` |
| A7 | `art_gallery` | `ArtGallery` |
| A10 | `cave` | `Cave` |
| A10 | `waterfall` | `Waterfall`؟ — نیازمند راستی‌آزمایی |

⚠️ **هیچ‌کدام از این نام‌ها تأیید نشده‌اند.** پیش از پیاده‌سازی باید در واژگان رسمی Schema.org بررسی شوند. این جدول یک **فرضیهٔ کاری** است، نه تصمیم.

بقیهٔ مقادیر که نوع اختصاصی ندارند، در `additionalType` به‌صورت متن می‌آیند.

---

## ۷ — چک‌لیست تولید محتوا

برای هر رکورد، این فیلدها **پیش از انتشار** باید پر باشند:

### همهٔ موجودیت‌ها
`name_fa` · `name_en` · `short_summary` · `meta_title` · `meta_description` · `hero_image` · `entity_uid` (خودکار)

### موجودیت‌های مکانی — G و A
به‌علاوه: `geo_coordinates` · رابطهٔ والد جغرافیایی

### رویدادها — E
به‌علاوه: `event_start_date` · `event_end_date` · `venue_name` یا رابطهٔ مکان

### اشخاص — C12
به‌علاوه: سال تولد و وفات (پس از رفع `SG-01`)

---

## ۸ — تصمیم P5-D142

> نگاشت نوع Schema برای هر ۴۱ موجودیت قابل‌انتشار در این سند تثبیت می‌شود.
>
> پیاده‌سازی در `topersia_schema_map()` تا پایان فاز قالب به تعویق می‌افتد.
>
> اما **چک‌لیست بخش ۷ از همین حالا الزامی است**: هر رکوردی که پس از این تاریخ ساخته می‌شود باید فیلدهای الزامی نوعش را داشته باشد.
>
> هفت شکاف بخش ۵ پیش از شروع تولید محتوا تعیین تکلیف می‌شوند.

---

## ۹ — آنچه هنوز تصمیم نیست

| مورد | چرا معلق |
|---|---|
| نام دقیق انواع Schema.org | نیازمند راستی‌آزمایی با واژگان رسمی و اعتبارسنج |
| نگاشت `classification` به `additionalType` | همان |
| رفع هفت شکاف داده | نیازمند تصمیم معماری محتوا |
| ترتیب پیاده‌سازی ۳۹ موجودیت باقی‌مانده | پس از قالب |

---

*ToPersia — SCHEMA-MAP-v1.1 — DRAFT*
