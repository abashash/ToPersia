# ToPersia — فضای کاری قالب دستهٔ G

این پوشه همه‌چیزِ لازم برای ادامهٔ کار را دارد. ساختارش عمدی است: کد، آزمون، مرجع، اسکریپت، سند — هر کدام جای خودش.

---

## چه چیزی کجاست

```
plugin/topersia-render/        ← افزونهٔ زنده. هر تغییری اینجا
plugin/ToPersia-G-Single-Template-v2.json   ← قالب المنتور

tests/                         ← ۴۹ آزمون · بدون وردپرس اجرا می‌شوند
reference/                     ← مرجع‌های خواندنی · هرگز ویرایش نشوند
scripts/tp-data-doctor.php     ← تشخیص و اصلاح ایرادهای داده
docs/                          ← سند تحویل، بریف کدکس، پرامت کلاد وب
```

### `reference/` — این‌ها دست نمی‌خورند

| فایل | نقش |
|---|---|
| `Jetengine_Skin.json` | **مرجع نهایی.** ساختار زندهٔ JetEngine: ۴۲ نوع پست، ۱۳۵ رابطه، ۷۴ واژه‌نامه. پیش از هر ادعا دربارهٔ فیلد یا رابطه، این خوانده می‌شود |
| `ToPersia-G3-Province-Template.html` | مرجع بصری تأییدشده. مقصد نهایی ظاهر همین است |
| `TP-G-FIELDMAP.json` | نقشهٔ ۵۷ فیلد با ناحیه، نوع، واحد، برچسب |
| `TP-G-RELATIONS.md` | ۳۴ رابطهٔ استان — ⚠ **ستون جهتش برای هر ۳۴ سطر وارونه است.** جهت را از `Jetengine_Skin.json` بگیر (`P5-D170`) |
| `topersia-core.php` | امضای `topersia_relation` · `topersia_active_rows` · `topersia_entity_meta` |
| `topersia-schema.php` | موتور JSON-LD — برای قاعدهٔ تک‌تولیدکنندهٔ `P5-D145` |
| `ToPersia_Last_Colors_Palet.png` | مرجع رنگ — `P5-D150` |
| `HANDOVER-Template-G-v1_0.md` | سند تحویل مرحلهٔ پیشین |

---

## پیش‌نیاز

**PHP ۷٫۴ یا بالاتر روی PATH.** آزمون‌ها بدون وردپرس اجرا می‌شوند ولی به مفسر PHP نیاز دارند.

```bash
php -v     # اگر خطا داد، اول PHP را نصب کن
```

ویندوز: از php.net نسخهٔ NTS x64 را بگیر، جایی بازش کن، و پوشه‌اش را به PATH اضافه کن.
مک: `brew install php` · لینوکس: `apt install php-cli`

**دیو-سرور لازم نیست.** این یک افزونهٔ وردپرس است، نه اپ نود یا ویت. اگر ابزاری پیشنهاد داد
`launch.json` بسازد و `preview` بالا بیاورد، رد کن — چیزی برای سرو کردن وجود ندارد.
پیش‌نمایش واقعی، خود سایت وردپرس است.

## از کجا شروع کنم

### اگر می‌خواهی کد بزنی

```bash
php tests/assert.php     # باید ۴۹ سبز بدهد
```

اگر این سبز شد، محیط سالم است. بعد `docs/CODEX-BRIEF.md` را بخوان — راه‌اندازی و کارها به ترتیب آنجاست.

افزونه را با symlink به وردپرس وصل کن تا هر ویرایش بلافاصله زنده شود:

```bash
ln -s "$(pwd)/plugin/topersia-render" /path/to/wp-content/plugins/topersia-render
```

### اگر می‌خواهی دربارهٔ معماری تصمیم بگیری

`docs/PROMPT-CHAT-BAADI.md` را در یک چت تازهٔ کلاد وب بگذار، با فایل‌های `reference/` و `docs/HANDOVER-Template-G-v2_0.md`.

### اگر فقط می‌خواهی بدانی کجای کاریم

`docs/HANDOVER-Template-G-v2_0.md` — استک، هفده تصمیم، یافته‌های داده، پنج باگ رفع‌شده، کارهای باز.

---

## ترتیب پیشنهادی کار

**۱ — سه ایراد داده.** هر سه در دیتابیس‌اند نه در کد، و تا حل نشوند ظاهر کامل نمی‌شود.

```bash
wp eval-file scripts/tp-data-doctor.php -- --report
wp eval-file scripts/tp-data-doctor.php -- --fix-titles --dry
wp eval-file scripts/tp-data-doctor.php -- --fix-titles
```

مهم‌ترینش: `post_title` رکوردهای استان برابر کد استان است (`04`)، پس رنک‌مث همان را در `<title>` می‌گذارد. روی ۱۹۴٬۰۰۰ صفحه بزرگ‌ترین ایراد سئوی پروژه.

**۲ — نقشهٔ رابطه برای دوازده نوع پست دیگر.** الان فقط استان پر است.

**۳ — سه ناحیه‌ای که داده نمی‌خواهند:** نوار پایین موبایل، دو مودال، شبکهٔ فرهنگ.

**۴ — شش ناحیهٔ باقی‌مانده** که هرکدام یک تصمیم می‌خواهند.

---

## دو قاعده که گران تمام شده‌اند

**هر تغییر دسته‌ای روی کد باید `assert` داشته باشد.** پنج بار در چت قبل با اسکریپت جای‌گزینی انبوه شد و هر پنج بار یک مورد جا ماند. بدترینش یک مقدار پیش‌فرض پارامتر PHP بود که شش کارت را بی‌استایل کرد و سه دور طول کشید تا پیدا شود.

**وقتی چیزی درست به نظر نمی‌رسد، سورس رندرشدهٔ صفحه را ببین، نه عکس را.** چهار باگ از پنج باگ نهایی مستقیم از سورس درآمدند.

---

## نصب روی سایت

```
۱. بکاپ با Duplicator Pro
۲. Plugins → Add New → Upload → زیپ افزونه → Replace current
۳. Templates → Theme Builder → Single Post → Import → قالب JSON
۴. Preview Settings → یک استان واقعی
۵. Display Conditions → دوازده قاعدهٔ Include / Singular / Post Type
۶. Theme Settings → Layouts: هیچ Custom Layout روی singleهای دستهٔ G نباشد
۷. Ctrl+Shift+R
```

دوازده نوع پست: `tp_country` `tp_region` `tp_province` `tp_city` `tp_district` `tp_village` `tp_island` `tp_mountain` `tp_water_body` `tp_desert` `tp_forest_plain` `tp_border_crossing`

`tp_county` عمداً نیست — `P5-D172`.
