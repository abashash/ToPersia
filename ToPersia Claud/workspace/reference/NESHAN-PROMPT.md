تو در پروژه **ToPersia** مسئول طراحی و پیاده‌سازی کامل زیرساخت:

# ToPersia Location Intelligence Platform

هستی.

ToPersia یک پلتفرم گردشگری ایران است و هدف این پروژه این است که کاربر بتواند **تقریباً تمام تجربه کشف مقصد، مکان‌های اطراف، فاصله، مسیر، برنامه‌ریزی سفر و اطلاعات مکانی را داخل خود ToPersia انجام دهد** و فقط در مرحله اجرای Navigation واقعی، در صورت انتخاب کاربر، به اپلیکیشن‌هایی مثل Google Maps، Waze، Neshan یا سایر سرویس‌های مسیریابی منتقل شود.

---

# اصل کلیدی محصول

این اصل را در تمام معماری، کد، documentation و UI رعایت کن:

## ToPersia owns the content experience.

## Neshan is a location/map infrastructure provider, not the content source.

## External apps are navigation providers only.

یعنی:

**محتوا = ToPersia**

**کشف محتوای اطراف = ToPersia Database**

**تجربه کاربر = ToPersia**

**نقشه داخل سایت = ToPersia UI با provider مکانی مناسب**

**محاسبات مکانی/routing پشت صحنه = سرویس‌های لازم**

**Navigation واقعی = اپی که خود کاربر انتخاب می‌کند**

---

# 1. مهم‌ترین سناریوی محصول

مثلاً کاربر در:

**میدان فردوسی تهران**

قرار دارد.

وارد ToPersia می‌شود.

روی:

# 📍 اطراف من

کلیک می‌کند.

مرورگر از کاربر درخواست Location Permission می‌کند.

اگر کاربر موافقت کرد:

latitude
longitude

گرفته می‌شود.

سپس:

User Location
→
ToPersia Backend
→
ToPersia Database
→
Find Nearby ToPersia Content
→
Sort by Distance
→
Return Results
→
Show in ToPersia UI + Map

---

# 2. Near Me به هیچ عنوان نباید یک Neshan Nearby wrapper باشد

این اشتباه است:

User Location
→
Neshan Nearby Search
→
Show Neshan Places

این معماری را اجرا نکن.

معماری صحیح:

User Location
→
ToPersia Spatial Query
→
Published ToPersia Entities
→
Distance Calculation
→
ToPersia Results

اگر Neshan برای location/routing/map کمک می‌کند، فقط در لایه infrastructure استفاده شود.

---

# 3. مثال واقعی Near Me

کاربر در میدان فردوسی است.

روی:

📍 اطراف من

کلیک می‌کند.

ToPersia در دیتابیس خود بررسی می‌کند.

نتیجه:

### اطراف من

🏛 موزه ملی ایران
1.8 km

🏺 موزه آبگینه و سفالینه
2.2 km

🌳 پارک شهر
2.5 km

🏛 کاخ گلستان
2.8 km

🏛 موزه جواهرات ملی
3.1 km

همه این موارد باید:

* محتوای واقعی ToPersia داشته باشند
* published باشند
* مختصات معتبر داشته باشند
* URL واقعی ToPersia داشته باشند

---

# 4. اگر یک مکان فقط در Neshan وجود دارد ولی در ToPersia محتوا ندارد

در Near Me اصلی:

**نمایش داده نشود.**

یعنی:

Neshan Place
!=
ToPersia Content

مثلاً اگر Neshan یک موزه را دارد ولی ToPersia هنوز صفحه آن را ندارد:

آن موزه در لیست محتوای Near Me نمایش داده نشود.

این قانون بسیار مهم است.

---

# 5. نقش Neshan

Neshan را به‌عنوان location infrastructure در نظر بگیر.

مواردی که در صورت نیاز می‌توان از آن استفاده کرد:

* Interactive map
* Geocoding
* Reverse Geocoding
* Search
* Nearby infrastructure where appropriate
* POI services where appropriate
* Driving routing
* Traffic-aware routing where supported
* No-traffic routing where supported
* Predictive routing where supported
* Typical routing where supported
* Pedestrian routing
* Distance Matrix
* TSP
* Isochrone / Isodistance
* VRP
* Map Matching
* Static Map
* Static Map Arc
* Deep link/navigation handoff

اما هیچ‌کدام نباید ToPersia Database یا ToPersia Editorial Content را جایگزین کنند.

قبل از استفاده از هر service، مستندات رسمی فعلی Neshan را بررسی کن:

https://platform.neshan.org/api/

https://platform.neshan.org/docs/

هیچ endpoint، parameter یا response structure را از حافظه حدس نزن.

---

# 6. همه تجربه اصلی باید داخل ToPersia باشد

کاربر باید در سایت بتواند:

* موقعیت خودش را بدهد
* مکان‌های اطراف را ببیند
* دسته‌بندی کند
* نقشه ببیند
* مکان‌ها را روی نقشه ببیند
* فاصله را ببیند
* زمان تقریبی را ببیند
* route preview ببیند
* مسیر پیاده/خودرو را ببیند
* مقصدها را انتخاب کند
* itinerary بسازد
* ترتیب بازدید را بهینه کند
* مکان‌های قابل دسترسی در یک محدوده زمانی را ببیند
* اطلاعات کامل هر مقصد را بخواند
* Audio Story گوش دهد
* از AI درباره مکان‌های اطراف سؤال کند

و فقط وقتی آماده حرکت شد:

**Navigation**

را به یک app خارجی منتقل کند.

---

# 7. Navigation خارجی

ToPersia نباید navigation turn-by-turn خودش را بسازد.

روی مقصد:

### 🚗 مسیریابی

کلیک شد، UI انتخاب provider نمایش بدهد.

مثلاً:

* Google Maps
* Waze
* Neshan
* Apple Maps where appropriate

کاربر انتخاب می‌کند.

ToPersia فقط destination coordinates / destination information را به provider انتخابی handoff می‌کند.

مثلاً:

ToPersia
→
Google Maps
→
Navigation

یا:

ToPersia
→
Waze
→
Navigation

یا:

ToPersia
→
Neshan
→
Navigation

---

# 8. تفاوت Route Preview و Navigation

این تفکیک را دقیقاً رعایت کن.

## داخل ToPersia:

Route Preview

مثلاً:

موقعیت من
→
موزه ملی ایران

3.4 km

🚶 23 min

🚗 8 min

و route روی map نمایش داده شود.

## خارج از ToPersia:

Navigation

Turn-by-turn
voice navigation
live navigation

توسط app انتخاب‌شده کاربر انجام شود.

---

# 9. انتخاب Navigation Provider

روی هر Destination:

[ مسیریابی ]

کلیک شود.

نمایش:

### مسیریابی با:

🗺 Google Maps
🚗 Waze
📍 Neshan
🍎 Apple Maps

فقط providerهایی را فعال کن که deep-link / navigation handoff واقعی و مستند دارند.

نباید URLها را حدس بزنی.

از مستندات رسمی provider استفاده کن.

---

# 10. Remember Navigation Choice

در صورت مناسب بودن UX:

گزینه:

**آخرین انتخاب من را به خاطر بسپار**

داشته باش.

مثلاً اگر کاربر Google Maps را انتخاب کرد:

دفعه بعد:

Google Maps

به‌عنوان preferred provider پیشنهاد شود.

این preference نیاز به ذخیره location کاربر ندارد.

---

# 11. User Location Privacy

کاربر باید خودش action انجام دهد.

مثلاً:

[ 📍 پیدا کردن موقعیت من ]

تا browser permission درخواست کند.

Location را بدون consent دریافت نکن.

Location واقعی کاربر را در database ذخیره نکن مگر یک قابلیت مشخص آینده واقعاً به آن نیاز داشته باشد.

برای Near Me:

* گرفتن location
* استفاده در request
* نمایش نتیجه

کافی است.

---

# 12. Permission States

سه وضعیت اصلی داشته باش:

## Initial

📍 اطراف من

## Granted

«مکان‌های نزدیک شما»

## Denied

«برای نمایش مکان‌های نزدیک، دسترسی به موقعیت مکانی را فعال کنید.»

اگر browser اجازه راهنمایی تنظیمات بدهد، راهنمای مناسب نمایش بده.

---

# 13. Fallback اگر Location داده نشد

Near Me نباید dead-end شود.

نمایش:

### مکان خود را انتخاب کنید

کاربر بتواند:

* شهر
* آدرس
* مکان
* مختصات

را انتخاب کند.

مثلاً:

«میدان فردوسی تهران»

سپس همین spatial engine استفاده شود.

یعنی:

GPS Location
یا
Selected Location

هر دو وارد یک:

**Nearby Content Engine**

شوند.

---

# 14. Nearby Content Engine

یک سرویس داخلی مستقل ایجاد کن.

مثلاً:

NearbyContentService

وظایف:

* دریافت reference location
* دریافت radius
* دریافت category
* query ToPersia entities
* calculate distance
* sorting
* filtering
* language resolution
* result normalization

این service نباید وابسته مستقیم به frontend باشد.

---

# 15. Spatial Query

قبل از implementation ساختار دیتابیس فعلی را بررسی کن.

مکان‌ها ممکن است در:

* JetEngine meta
* custom fields
* custom tables
* relationships

ذخیره شده باشند.

از ساختار موجود استفاده کن.

Field جدید duplicate نساز.

اگر تعداد entities زیاد است، معماری spatial/indexing بهینه ایجاد کن.

---

# 16. Location Fields

برای هر entity قابل جست‌وجوی مکانی، conceptually نیاز داریم:

latitude
longitude

اما قبل از ساخت field جدید، project audit انجام بده.

همچنین در صورت نیاز:

city
province
region
address

از ساختار فعلی استفاده کن.

---

# 17. Eligible Content

فقط entityهایی که:

* published
* publicly accessible
* valid coordinates
* valid URL
* valid content

هستند در Near Me نمایش داده شوند.

Draft/private/trashed نمایش داده نشوند.

---

# 18. Entity Types

ابتدا CPTها و taxonomyهای واقعی ToPersia را audit کن.

سیستم Near Me باید قابل استفاده برای انواعی مانند:

* Destination
* Attraction
* Historical Site
* UNESCO
* Museum
* Palace
* Mosque
* Nature
* Beach
* Village
* Park
* Hotel
* Restaurant
* Café
* Shopping
* Event
* Tourist Service

باشد.

اما نام دقیق typeها را از پروژه بخوان.

Hard-code نکن مگر architecture موجود چنین چیزی بخواهد.

---

# 19. Distance

Distance را در backend محاسبه کن.

از:

user lat/lng

و:

entity lat/lng

استفاده کن.

برای تعداد زیاد:

Bounding Box
+
Spatial filtering
+
Accurate distance calculation

به کار ببر.

همه رکوردها را بدون index scan نکن.

---

# 20. Radius

Default:

5 km

Options:

1 km
2 km
5 km
10 km
25 km

قابل تنظیم باشند.

در موبایل default را 5 km قرار بده مگر data/UX بررسی خلاف آن را نشان دهد.

---

# 21. Category Filters

Near Me بتواند filter شود:

All
Attractions
Museums
Historical
Nature
Hotels
Restaurants
Cafés
Shopping
Events

ولی categoryها باید از taxonomy واقعی ToPersia استخراج شوند.

---

# 22. Sorting

Default:

Distance ASC

قابل توسعه برای آینده:

Distance
Relevance
Quality
Featured
Open Now

اما MVP را predictable نگه دار.

---

# 23. Content Cards

هر کارت:

Image
Title
Category
Distance

و در صورت وجود:

Excerpt

و actions:

[ مشاهده ]

[ مسیر ]

نمایش دهد.

---

# 24. Card Example

مثلاً:

موزه ملی ایران

موزه

1.8 km

«یکی از مهم‌ترین مجموعه‌های موزه‌ای ایران...»

[ مشاهده صفحه ]

[ مسیریابی ]

تمام content از ToPersia باشد.

---

# 25. Click Result

وقتی کاربر روی:

[ مشاهده صفحه ]

کلیک می‌کند:

باید وارد URL واقعی ToPersia همان entity شود.

نه صفحه Neshan.

---

# 26. Map

داخل ToPersia یک Interactive Map داشته باش.

Map باید:

* user location
* ToPersia locations
* marker
* popup
* zoom
* pan
* bounds
* selected state
* clustering

را پشتیبانی کند.

---

# 27. Map Provider Abstraction

Business logic را مستقیماً به Neshan قفل نکن.

Architecture:

LocationProviderInterface

MapProviderInterface

RoutingProviderInterface

GeocodingProviderInterface

SearchProviderInterface

و سپس implementation:

NeshanMapProvider

NeshanRoutingProvider

NeshanGeocodingProvider

...

این اجازه می‌دهد آینده provider دیگری اضافه شود.

---

# 28. Map Marker

Markerهای ToPersia data-driven باشند.

مثلاً:

destination
museum
hotel
restaurant
nature
heritage
event

اما نوع‌های واقعی از project audit بیایند.

---

# 29. Map/List Synchronization

این behaviour ضروری است:

Marker click
→
Card highlight

Card click
→
Marker active
→
Map focus

---

# 30. Clustering

اگر تعداد locations بالا است:

Marker clustering

فعال شود.

همه markerها را بدون clustering روی map نشان نده.

---

# 31. Desktop UX

روی desktop:

Left:
Nearby Results

Right:
Map

مثلاً:

```text
┌──────────────────────┬──────────────────────────┐
│ Nearby ToPersia      │                          │
│                      │           MAP            │
│ Museum   1.8 km      │       📍      📍         │
│ Palace   2.8 km      │            📍            │
│ Park     2.5 km      │                          │
└──────────────────────┴──────────────────────────┘
```

---

# 32. Mobile UX

در mobile:

Nearby
→
Map/List

یا:

Map
→
Scrollable Results

طراحی باید minimal و fast باشد.

---

# 33. No Results

اگر نتیجه‌ای وجود ندارد:

«هیچ محتوای ToPersia در این محدوده پیدا نشد.»

بعد:

[ افزایش محدوده ]

نمایش بده.

---

# 34. Open Now

اگر ToPersia برای entity ساعات کاری دارد:

Near Me بتواند filter:

**باز است الآن**

داشته باشد.

اما این اطلاعات باید از data واقعی ToPersia بیاید.

---

# 35. Travel Time

در صورت نیاز می‌توان برای بعضی نتایج:

Walking Time
Driving Time

محاسبه کرد.

اما هرگز برای هر card جداگانه route API صدا نزن.

اول:

Nearby Content

بعد فقط top results یا selected result را enrich کن.

---

# 36. API Cost Protection

این pattern ممنوع:

برای 20 مکان
→
20 route calls
→
هر page load

به‌جای آن:

* spatial DB query
* distance calculation
* selective route calculation
* cache

استفاده کن.

---

# 37. Search

Search اصلی ToPersia را حفظ کن.

Search برای:

موزه نزدیک من

باید ابتدا:

User Location
→
ToPersia Search/Nearby Content

را انجام دهد.

مثال:

«موزه نزدیک من»

نتیجه:

موزه ملی ایران
1.8 km

موزه آبگینه
2.2 km

---

# 38. Search + Neshan

Neshan Search می‌تواند برای:

* address discovery
* location lookup
* geocoding
* location matching

استفاده شود.

اما اگر user دنبال editorial ToPersia content است:

ToPersia source of truth remains ToPersia database.

---

# 39. POI Matching

برای آینده architecture ایجاد کن:

Neshan Place
↔
ToPersia Entity

matching بر اساس:

* provider reference
* coordinates
* normalized name
* city
* category

اما matching خودکار فقط با confidence threshold مناسب.

دو مکان هم‌نام ممکن است کاملاً متفاوت باشند.

---

# 40. Admin Matching

در آینده قابلیت admin برای:

Match
Confirm
Remove

در نظر بگیر.

اگر برای MVP لازم نیست UI کامل نساز، ولی architecture را طوری بساز که بعداً اضافه شود.

---

# 41. Geocoding

در WordPress Admin برای location editor امکان:

[ Geocode Address ]

داشته باش.

Address
→
Coordinates

ولی نتیجه را قبل از save قابل بررسی/override قرار بده.

---

# 42. Reverse Geocoding

امکان:

Coordinates
→
Address

برای تأیید location و ساخت آدرس ایجاد کن.

---

# 43. City Map

برای هر City:

Map

*

ToPersia locations

مثلاً تهران:

* Museums
* Attractions
* Historical
* Hotels
* Restaurants

را نمایش بده.

---

# 44. Province Map

برای هر Province:

Province
→
Cities
→
Destinations

روی map نمایش داده شود.

Region API فقط در صورت نیاز برای mapping/validation استفاده شود.

---

# 45. Destination Page Map

هر Destination باید:

* location
* map
* address
* nearby
* route preview
* navigation actions

را داشته باشد.

---

# 46. How to Get There

در Destination:

### How to get there

نمایش:

🚗 Driving
🚶 Walking

و در صورت پشتیبانی واقعی:

Travel Time
Distance

همه داخل ToPersia.

---

# 47. Route Preview

مثلاً:

میدان فردوسی
→
موزه ملی ایران

داخل سایت:

3.4 km

🚶 23 min

🚗 8 min

و route روی نقشه.

---

# 48. Navigation

سپس:

[ مسیریابی ]

→

Select Provider

→

Google Maps / Waze / Neshan / supported provider

→

External app

Navigation واقعی خارج از ToPersia انجام شود.

---

# 49. Trip Planner

یک architecture قابل توسعه برای:

# ToPersia Trip Planner

بساز.

ورودی:

Start
Stops
Date
Time
Travel Mode

خروجی:

* Route
* Distance
* Estimated Time
* Stop Order
* Map

---

# 50. Multi-Destination Planning

مثلاً:

کاربر انتخاب می‌کند:

☑ حافظیه
☑ سعدیه
☑ ارگ کریم‌خان
☑ تخت جمشید
☑ پاسارگاد

ToPersia می‌تواند:

* distance matrix
* travel-time
* route
* stop order

را محاسبه کند.

---

# 51. TSP

اگر کاربر چند مقصد انتخاب کرد، TSP را برای:

Optimal Visit Order

در نظر بگیر.

اما business rules باید در ToPersia باشند.

مثلاً:

* starting location
* ending location
* fixed stop
* priority
* time constraint

از خود ToPersia بیایند.

---

# 52. Opening Hours در Trip Planner

اگر یک Destination ساعت بازدید دارد:

Trip Planner نباید صرفاً بر اساس distance ترتیب بدهد.

در آینده باید بتواند:

Opening Hours
+
Travel Time
+
Duration at Site

را در نظر بگیرد.

---

# 53. Distance Matrix

برای محاسبه فاصله/زمان بین چند destination استفاده کن.

این سرویس را فقط زمانی صدا بزن که واقعاً itinerary نیاز دارد.

Cache کن.

---

# 54. Isochrone

یک قابلیت future-ready:

### What can I reach?

مثلاً:

«در 30 دقیقه از اینجا چه مکان‌هایی قابل دسترسی هستند؟»

ToPersia محدوده را روی map نمایش دهد.

سپس فقط ToPersia content داخل آن محدوده را نشان دهد.

---

# 55. Example Isochrone

کاربر:

میدان فردوسی

Time:

30 minutes

نتیجه:

🟢 مکان‌های قابل دسترسی

موزه ملی
کاخ گلستان
بازار تهران
...

فقط از دیتابیس ToPersia.

---

# 56. Pedestrian

برای مکان‌هایی که نزدیک هستند:

مثلاً:

Metro Station
→
Museum

می‌توان route پیاده را داخل ToPersia نمایش داد.

---

# 57. Predictive / Typical Routing

برای travel planning future trips:

اگر Neshan API رسمی موردنیاز را پشتیبانی کند:

Predictive Routing

Typical Routing

را برای estimate استفاده کن.

این اطلاعات را واضح برچسب‌گذاری کن.

مثلاً:

Estimated Travel Time

و آن را با live navigation اشتباه نگیر.

---

# 58. VRP

VRP را برای public consumer MVP الزامی نکن.

اما architecture را برای آینده نگه دار.

Use cases:

* Tour Operator
* Group Tour
* Bus
* Shuttle
* Multi-vehicle tourism

---

# 59. Map Matching

برای آینده:

GPS Tracks
→
Road Map

architecture آماده باشد.

Use cases:

* tour routes
* bike routes
* hiking
* recorded journeys

---

# 60. Static Maps

برای cardها و صفحات performance-sensitive می‌توان:

Static Map

استفاده کرد.

مثلاً:

Destination Card
→
Static Map

صفحه کامل Destination
→
Interactive Map

---

# 61. Static Map Arc

برای route-oriented editorial content در صورت نیاز:

Tehran
→
Qom
→
Isfahan

Static route visualization

استفاده شود.

---

# 62. SEO

Near Me نباید SEO را خراب کند.

اطلاعات مهم:

* destination title
* address
* city
* province
* coordinates where appropriate

باید بدون وابستگی کامل به JS قابل دسترسی باشند.

Map نباید جایگزین textual content شود.

---

# 63. Accessibility

کاربر نباید مجبور باشد برای فهم location فقط با map کار کند.

حتماً:

* semantic HTML
* accessible labels
* keyboard support
* focus state
* screen reader support
* text alternative
* readable distance
* readable address
* accessible navigation buttons

وجود داشته باشد.

---

# 64. WPML

ToPersia چندزبانه است.

Location:

language-independent

Content:

language-aware

مثلاً:

FA:

موزه ملی ایران

EN:

National Museum of Iran

AR:

...

Coordinates مشترک باقی بمانند.

اما:

title
excerpt
category labels
URL

بر اساس language فعلی انتخاب شوند.

---

# 65. JetEngine

ابتدا architecture واقعی JetEngine را بررسی کن.

اگر coordinates در JetEngine meta هستند:

همان را استفاده کن.

اگر relations موجود هستند:

همان relations را استفاده کن.

Fieldهای duplicate نساز.

---

# 66. Elementor

اگر UI فعلی Elementor-based است، integration را هماهنگ با architecture فعلی انجام بده.

در صورت مناسب بودن:

* Elementor widget
* shortcode
* dynamic component

استفاده شود.

چند سیستم موازی نساز.

---

# 67. REST API

در صورت وجود architecture مناسب:

یک endpoint داخلی برای Nearby ایجاد کن.

Conceptually:

GET /wp-json/topersia/v1/nearby

Parameters:

lat
lng
radius
category
language
limit

اما ابتدا REST architecture فعلی را بررسی کن.

Endpoint نهایی باید با conventions فعلی پروژه هماهنگ باشد.

---

# 68. Response Normalization

Raw Neshan response را مستقیم به frontend نده.

Normalized models داشته باش:

LocationResult
NearbyContentResult
RouteResult
PlaceResult
DistanceResult

---

# 69. Example Nearby Response

```json
{
  "location": {
    "latitude": 35.XXXX,
    "longitude": 51.XXXX
  },
  "radius_meters": 5000,
  "count": 3,
  "items": [
    {
      "id": 123,
      "type": "museum",
      "title": "National Museum of Iran",
      "url": "/...",
      "image": "...",
      "distance_meters": 1800,
      "latitude": 35.XXXX,
      "longitude": 51.XXXX
    }
  ]
}
```

---

# 70. API Security

هر backend endpoint باید:

* validation
* sanitization
* rate protection where necessary
* safe output
* no secret exposure

داشته باشد.

---

# 71. Neshan API Key

API key را:

* hard-code نکن
* git commit نکن
* frontend expose نکن مگر مستندات رسمی سرویس صراحتاً client-side key را لازم بداند
* log نکن

Configuration امن ایجاد کن.

مثلاً:

NESHAN_API_KEY

مقدار واقعی را در report نهایی نمایش نده.

---

# 72. Cache

قابلیت cache باید در architecture باشد.

موارد قابل cache:

* geocoding
* reverse geocoding
* search
* POI
* static maps
* distance matrix
* routes where appropriate
* nearby query where safe

اما user location دقیق را به شکلی که با location کاربران دیگر مخلوط شود cache نکن.

---

# 73. Content Cache

محتوای ToPersia می‌تواند cache شود.

اگر Destination تغییر کرد، cache invalid شود.

---

# 74. Search Debounce

برای search input:

Debounce

اجرا کن.

برای هر keypress API call نکن.

---

# 75. Performance

هدف:

* minimal JS
* lazy map loading
* no map on pages without map
* no duplicate requests
* cached APIs
* efficient DB spatial query
* no N+1 routing calls
* mobile-first
* fast frontend

---

# 76. Mobile Performance

در mobile:

Map را lazy load کن.

ابتدا content/list را قابل مشاهده کن.

Interactive Map را هنگام نیاز فعال کن.

---

# 77. Graceful Degradation

اگر Map/Location service fail شد:

صفحه نباید خراب شود.

حداقل:

Address
Distance where available
Coordinates where appropriate
ToPersia content
Navigation links where possible

قابل استفاده باقی بماند.

---

# 78. Content Source Separation

در تمام resultها source را واضح نگه دار.

مثلاً:

content_source = topersia

map_source = neshan

route_source = neshan

navigation_provider = google/waze/neshan/etc.

این metadata در backend قابل تشخیص باشد.

---

# 79. Example Full User Journey

این سناریو باید end-to-end کار کند:

## Step 1

User opens ToPersia.

## Step 2

Clicks:

📍 اطراف من

## Step 3

Browser permission.

## Step 4

User grants location.

## Step 5

ToPersia receives coordinates.

## Step 6

ToPersia searches own database.

## Step 7

Results:

موزه ملی ایران
موزه آبگینه
کاخ گلستان
...

## Step 8

User clicks:

موزه ملی ایران

## Step 9

ToPersia Destination Page opens.

## Step 10

Page contains:

* editorial content
* images
* audio story
* map
* address
* nearby
* route preview

## Step 11

User sees:

3.4 km
23 min walking
8 min driving

## Step 12

User clicks:

مسیریابی

## Step 13

Provider selection:

Google Maps
Waze
Neshan
...

## Step 14

User chooses Google Maps.

## Step 15

Google Maps opens.

## Step 16

Navigation continues outside ToPersia.

---

# 80. Audio Story Integration

اگر Destination دارای:

AI Audio Story

است، Near Me card می‌تواند نشان دهد:

🎧 روایت صوتی

User clicks.

Audio player خود ToPersia اجرا می‌شود.

هیچ navigation خارج از سایت لازم نیست.

---

# 81. AI Integration

در آینده:

### «اطراف من چی هست؟»

کاربر سؤال می‌کند.

Flow:

User Location
→
ToPersia Nearby Content
→
AI receives only relevant ToPersia content
→
AI generates answer

مثلاً:

«نزدیک شما سه موزه و دو بنای تاریخی وجود دارد.»

AI نباید مکان‌هایی خارج از ToPersia results را بدون source وارد پاسخ کند.

---

# 82. AI Must Not Invent Locations

اگر ToPersia result list شامل:

National Museum
Golestan Palace

است، AI حق ندارد بدون source بگوید:

«موزه X هم 500 متر جلوتر است»

مگر اینکه داده معتبر وارد context شده باشد.

---

# 83. Location Intelligence Layer

تمام functionality را در یک لایه منطقی قرار بده:

Location Intelligence

این layer باید بین:

WordPress content
و
External location providers

قرار گیرد.

---

# 84. Architecture

Conceptually:

```text
                 TOPERSIA
                    │
          ┌─────────┴─────────┐
          │                   │
      Content Layer     Location Layer
          │                   │
   WordPress/JetEngine     Location Intelligence
          │                   │
          └─────────┬─────────┘
                    │
          Provider Abstraction
                    │
          ┌─────────┼──────────┐
          │         │          │
       Neshan     Other      Future
       Services   Provider   Provider
```

Business logic نباید مستقیماً به provider-specific payloadها وابسته شود.

---

# 85. Core Services

در صورت سازگاری با architecture فعلی، services مشابه:

NearbyContentService
LocationService
GeocodingService
ReverseGeocodingService
MapService
RoutingService
DistanceService
TripPlannerService
NavigationProviderService
LocationMatchingService
StaticMapService

داشته باش.

اما قبل از ایجاد این classes ساختار موجود project را بررسی کن.

---

# 86. Navigation Provider Abstraction

حتماً architecture برای providerهای navigation جدا باشد.

مثلاً:

NavigationProviderInterface

GoogleMapsNavigationProvider

WazeNavigationProvider

NeshanNavigationProvider

AppleMapsNavigationProvider

این abstraction باید قابل توسعه باشد.

---

# 87. Navigation Provider Output

Backend/Frontend نباید hard-coded URLهای پراکنده داشته باشد.

Provider باید چیزی شبیه:

NavigationTarget

بسازد:

destination_name
latitude
longitude
external_url
provider

---

# 88. Provider Detection

اگر provider app یا OS خاصی نیاز به رفتار متفاوت دارد، implementation را در provider class نگه دار.

UI نباید منطق deep-link provider را بداند.

---

# 89. No Navigation Lock-In

کاربر نباید مجبور باشد Neshan را نصب کند.

کاربر نباید مجبور باشد Google Maps را نصب کند.

کاربر نباید مجبور باشد Waze را نصب کند.

هر provider مستقل باشد.

---

# 90. City Discovery

در صفحه City بتوان:

* Explore Map
* Nearby
* Attractions
* Museums
* Hotels
* Restaurants
* Historical
* Nature

را داخل ToPersia انجام داد.

---

# 91. Province Discovery

در Province نیز:

Province
→
Cities
→
Attractions
→
Map

داخل ToPersia باشد.

---

# 92. Route-Based Discovery

در future:

### Places Along My Route

کاربر مسیر را تعیین می‌کند.

ToPersia مکان‌های دارای محتوای خودش را در طول مسیر پیدا می‌کند.

مثلاً:

Tehran
→
Isfahan

و مکان‌های relevant در مسیر را نشان دهد.

---

# 93. Route Recommendations

این بخش باید از:

ToPersia Content
+
Routing Geometry
+
Distance

استفاده کند.

---

# 94. Important Business Rule

هیچ external provider نباید به تنهایی تصمیم بگیرد:

«کاربر چه چیزی را ببیند.»

Provider می‌تواند location facts بدهد.

اما:

**Discovery relevance**

و:

**Editorial experience**

متعلق به ToPersia است.

---

# 95. Data Quality Audit

قبل از فعال‌سازی عمومی Near Me:

کل database مربوط به location را بررسی کن.

Report:

* valid coordinates
* missing coordinates
* invalid coordinates
* duplicate coordinates if relevant
* unpublished entities
* entities without URL
* entities without translations
* entities outside expected geography

---

# 96. Bulk Geocoding

اگر تعداد زیادی entity مختصات ندارد:

Bulk Geocoding architecture ایجاد کن.

ولی:

* queue-based
* batched
* rate-aware

باشد.

صدها request را داخل یک page request اجرا نکن.

---

# 97. Background Jobs

برای موارد سنگین از:

* WP Cron
* existing queue
* background job architecture

استفاده کن.

همان سیستم queue موجود project را ترجیح بده.

---

# 98. Documentation

داخل repository documentation ایجاد کن:

Neshan Integration Architecture
Location Intelligence Architecture
Near Me Specification
Navigation Provider Architecture
Caching Strategy
Location Data Model
WPML Integration
JetEngine Integration
Elementor Integration
Security
Performance
Testing

---

# 99. Service Matrix

یک جدول documentation بساز:

| Service           | Provider                | ToPersia Use          | Frontend/Backend | Cache     | Status |
| ----------------- | ----------------------- | --------------------- | ---------------- | --------- | ------ |
| Map               | Neshan                  | Interactive maps      | Frontend         | -         |        |
| Geocoding         | Neshan                  | Address → Coordinates | Backend          | Yes       |        |
| Reverse Geocoding | Neshan                  | Coordinates → Address | Backend          | Yes       |        |
| Search            | Neshan/ToPersia         | Location discovery    | Mixed            | Yes       |        |
| Nearby            | ToPersia DB             | Nearby content        | Backend          | Carefully |        |
| Routing           | Neshan                  | Route preview         | Backend          | Selective |        |
| Pedestrian        | Neshan                  | Walking route         | Backend          | Selective |        |
| Distance Matrix   | Neshan                  | Trip planning         | Backend          | Yes       |        |
| TSP               | Neshan/ToPersia         | Stop ordering         | Backend          | Selective |        |
| Isochrone         | Neshan                  | Reachability          | Backend          | Selective |        |
| Static Map        | Neshan                  | Lightweight maps      | Backend          | Yes       |        |
| Navigation        | Google/Waze/Neshan/etc. | External navigation   | Frontend         | No        |        |

سرویس‌های دیگر رسمی Neshan را نیز در جدول بررسی کن.

---

# 100. Official Documentation Rule

هر API را قبل از implementation از documentation رسمی بررسی کن.

منبع اصلی:

https://platform.neshan.org/docs/

و:

https://platform.neshan.org/api/

اگر documentation با فرضیات این specification تفاوت دارد:

**documentation رسمی را ملاک قرار بده و architecture را با آن تطبیق بده.**

---

# 101. Existing Project Audit

قبل از coding:

کل پروژه را بررسی کن.

حتماً مشخص کن:

* WordPress version
* PHP version
* existing plugin architecture
* Elementor
* JetEngine
* WPML
* CPTs
* taxonomies
* location fields
* map fields
* REST endpoints
* AJAX endpoints
* existing location code
* existing JS
* existing CSS
* existing caching
* existing cron
* current database tables

---

# 102. Do Not Rewrite Working Code

اگر سیستم فعلی چیزی مشابه این قابلیت دارد:

آن را audit و extend کن.

بدون دلیل:

* delete
* rewrite
* duplicate
* parallel architecture

انجام نده.

---

# 103. Code Quality

Code/API/database/Git/documentation:

English

Communication with project owner:

Persian

Code modular باشد.

Separation of Concerns رعایت شود.

No giant class.

No giant function.

No duplicated business logic.

---

# 104. Testing

حداقل این تست‌ها را اجرا کن:

### Location

* permission granted
* permission denied
* invalid coordinates
* unavailable browser location

### Nearby

* 1 km
* 5 km
* 10 km
* no results
* category filtering
* sorting

### Content

* published
* draft excluded
* missing coordinates
* WPML

### Map

* marker
* selected marker
* clustering
* desktop
* mobile

### Routing

* driving
* walking
* route failure

### Navigation

* Google
* Waze
* Neshan
* supported providers

### Trip Planner

* multiple stops
* distance matrix
* TSP
* time constraints where implemented

---

# 105. Performance Test

Network requests را بررسی کن.

هدف:

Near Me:

1 main backend request

و فقط در صورت نیاز:

map / route enrichment

هیچ API storm ایجاد نشود.

---

# 106. Mobile Test

حتماً:

Android Chrome
iOS Safari where testable
Desktop browser

بررسی شوند.

---

# 107. Security Test

بررسی کن:

* API key not exposed
* no unauthorized REST calls
* nonce/capability
* input validation
* output escaping
* CORS safety
* no location storage
* no provider URL injection

---

# 108. Final UX Principle

کاربر نباید مجبور باشد بداند:

Neshan چیست.

Google API چیست.

Waze API چیست.

کاربر فقط باید یک تجربه ساده ببیند:

### نزدیک من

### روی نقشه ببین

### اطلاعات را بخوان

### مسیر را ببین

### مسیریابی کن

و در مرحله آخر:

### انتخاب کن با چه اپی مسیریابی شود.

---

# 109. نمونه UI نهایی

```text
┌──────────────────────────────────────────────┐
│ 📍 اطراف من                                │
│                                              │
│ موقعیت شما: میدان فردوسی، تهران            │
│                                              │
│ [همه] [موزه] [تاریخی] [جاذبه] [رستوران]   │
│                                              │
│ 1 km  2 km  5 km  10 km                    │
└──────────────────────────────────────────────┘


┌──────────────────────┬───────────────────────┐
│ Nearby ToPersia      │                       │
│                      │         MAP           │
│ 🏛 موزه ملی ایران    │        📍              │
│ 1.8 km               │    📍      📍         │
│ [مشاهده] [مسیر]      │                       │
│                      │                       │
│ 🏺 موزه آبگینه       │                       │
│ 2.2 km               │                       │
│ [مشاهده] [مسیر]      │                       │
└──────────────────────┴───────────────────────┘
```

---

# 110. مثال Destination Page

```text
موزه ملی ایران

[ Hero Image ]

توضیحات کامل ToPersia

🎧 روایت صوتی
▶ 4:12

📍 موقعیت

[ Interactive Map ]

فاصله از شما:
3.4 km

🚶 23 دقیقه پیاده
🚗 8 دقیقه با خودرو

[ مسیریابی ]
```

کلیک روی:

مسیریابی

→

```text
مسیریابی با:

🗺 Google Maps
🚗 Waze
📍 Neshan
🍎 Apple Maps
```

---

# 111. مثال Trip Planner

```text
Trip Planner

شروع:
میدان فردوسی

مقصدها:
☑ موزه ملی
☑ کاخ گلستان
☑ بازار تهران
☑ موزه آبگینه

[ Optimize Route ]

Result:

1. موزه آبگینه
2. موزه ملی
3. بازار تهران
4. کاخ گلستان

Total:
Distance
Estimated Time

[ View Route ]

[ Start Navigation ]
```

Start Navigation:

Provider Picker

---

# 112. مثال «چی اطراف منه؟»

```text
📍 نزدیک شما:

5 جاذبه گردشگری
3 موزه
2 بنای تاریخی
4 رستوران
6 هتل
```

و همه resultها:

ToPersia Content

---

# 113. مثال AI

User:

«من الان نزدیک میدان فردوسی هستم، چه جاهایی برای دیدن دارم؟»

AI context:

Nearby ToPersia results only.

Response:

«در اطراف شما چند مکان با محتوای ToPersia وجود دارد؛ از جمله موزه ملی ایران، موزه آبگینه و کاخ گلستان.»

AI نباید result خارجی را بدون source وارد پاسخ کند.

---

# 114. What Neshan Does NOT Do

Neshan نباید:

* منبع editorial content باشد
* جایگزین ToPersia destination pages شود
* UI اصلی تجربه گردشگری باشد
* کاربر را برای مشاهده اطلاعات به Neshan بفرستد
* تنها منبع Near Me باشد

---

# 115. What External Navigation Apps DO

Google/Waze/Neshan/etc. فقط:

Navigation Execution

را انجام می‌دهند.

یعنی:

ToPersia decides:
WHAT to show.

User decides:
WHICH navigation app to use.

Navigation app decides:
HOW to navigate.

---

# 116. Final Product Definition

تعریف نهایی این سیستم:

## ToPersia

کاربر location خود را به ToPersia می‌دهد.

ToPersia از دیتابیس خودش محتوای نزدیک را پیدا می‌کند.

ToPersia آن‌ها را روی map و list نمایش می‌دهد.

ToPersia اطلاعات، تصاویر، محتوای چندزبانه، Audio Story و AI experience را ارائه می‌کند.

ToPersia distance/route preview را در سایت نمایش می‌دهد.

وقتی کاربر آماده حرکت شد، ToPersia یک destination target به navigation provider انتخابی کاربر می‌دهد.

Google Maps / Waze / Neshan / سایر providerها فقط navigation واقعی را انجام می‌دهند.

---

# 117. مهم‌ترین جمله برای کل پروژه

این اصل را دقیقاً در documentation نهایی repository قرار بده:

**ToPersia is the destination discovery and content layer.**

**Neshan and other mapping providers are infrastructure.**

**Google Maps, Waze, Neshan and other navigation apps are optional navigation destinations chosen by the user.**

---

# 118. Final Implementation Instructions

قبل از implementation:

1. Audit existing project.
2. Audit current location architecture.
3. Audit JetEngine fields.
4. Audit Elementor.
5. Audit WPML.
6. Audit existing map/location code.
7. Read official Neshan documentation.
8. Build service matrix.
9. Design provider abstraction.
10. Implement Nearby Content Engine.
11. Implement Map.
12. Implement Route Preview.
13. Implement external navigation provider abstraction.
14. Implement Trip Planner foundation.
15. Implement caching/security.
16. Test.
17. Document.

---

# 119. Final Report

در پایان گزارش فارسی بده:

## 1. Architecture Audit

## 2. Existing Location System

## 3. Near Me Implementation

## 4. Map Implementation

## 5. Neshan Integration

## 6. Navigation Providers

## 7. Trip Planner

## 8. WPML

## 9. JetEngine

## 10. Elementor

## 11. Database Changes

## 12. Cache

## 13. Security

## 14. Performance

## 15. Tests

## 16. Files Created

## 17. Files Modified

## 18. Deferred Features

## 19. Remaining Issues

کلیدهای API یا secretها را هرگز در گزارش چاپ نکن.

---

# 120. Final Rule

**Do not start by writing code.**

اول project را بفهم.

بعد architecture را مستند کن.

بعد implementation را انجام بده.

هیچ assumptionی درباره ساختار ToPersia، CPTها، JetEngine fields، REST API یا Neshan endpoints نداشته باش.

از واقعیت repository و documentation رسمی استفاده کن.

هدف:

# A complete, fast, multilingual, accessible, content-first location intelligence experience for ToPersia — entirely inside ToPersia, with external navigation only when the user explicitly chooses it.
