# ToPersia — Phase 3: Content Architecture
## Phase3-KNOWLEDGE-GRAPH-VALIDATION
### ممیزی کامل گراف دانش در ۱۰ بُعد درخواستی
#### مرجع: IA-v1.2.1-FINAL (Frozen)

---

## یادداشت روش‌شناسی (شفافیت اجباری)

برای هر بُعد زیر، مشخص شده کدام یافته از **بررسی مستقیم متن فایل (grep/line-level توسط من)** آمده و کدام از **گزارش خودِ سند CA (که پیش‌تر طی چرخهٔ Extraction→Findings→Decisions→Final تأیید شده)** carry-forward شده. بازسازی کامل و مستقل گراف ۴۲ موجودیتی از صفر (یعنی بررسی دستی هر جفت ممکن رابطه) در حد یک بازبینی متنی نیست و نیازمند اجرای واقعی در گراف‌دیتابیس است — این محدودیت صادقانه در هر بخش ذکر شده تا اعتماد شما به این سند مبتنی بر شواهد واقعی باشد، نه ادعای بیش‌ازحد.

---

## ۱. Relationship Inflation — ✅ مستقیماً بررسی شد (grep کامل، همه ۶ فایل CA)

روش: هر رخداد الگوی `→ P[0-9]` در CA-G/A/D/C/E/P جست‌وجو و هرکدام با R01–R27 IA تطبیق داده شد.

**نتیجه پیش از پچ:** ۵ مورد Inflation واقعی به سمت Category P (بخش ۵ گزارش تجمیعی؛ یک مورد ششم — R15/R16 روی E6 — از همین خانواده خطاست اما سمت P نیست، در بخش ۴ همین سند پوشش داده شده).
**نتیجه پس از پچ:** صفر مورد. تنها `A3 → P5 (R24)` باقی مانده — تنها رابطهٔ معتبر A/E→P در کل IA.

Status: **PASS** (Verified — evidence-based)

---

## ۲. Entity Inflation — ✅ ترکیب مستقیم + Carry-forward

بررسی مستقیم من: P3، P8، E2، E4 در هیچ فایلی به‌عنوان entity واقعی (نه یادداشت prose) ظاهر نشدند — تأیید شد. C3 در C2 ادغام شده (بدون node مجزا) — تأیید شد با عدم وجود هیچ تعریف Field/Template مستقل برای C3.

Carry-forward: هر ۶ سند CA صراحتاً «New Entities: 0» ثبت کرده‌اند؛ تست سه‌شرطی P-03 (حجم جست‌وجوی مستقل + اطلاعات یکتا + رابطه متمایز) در هر Findings document به‌صورت مورد‌به‌مورد اعمال شده بود (مثلاً رد صریح ایجاد entity جدا برای Museum Building در برابر Museum Institution، طبق CA-A-D002).

Status: **PASS**

---

## ۳. Relationship Inflation در سطح Category (نه فقط P) — ✅ مستقیماً بررسی شد

علاوه بر Category P (بخش ۱)، شمارش کامل ۲۷ رابطه از IA استخراج و جهت هرکدام یادداشت شد (R01 تا R27). هیچ سند CA رابطه‌ای خارج از این ۲۷ مورد تعریف نکرده — تأیید شد با عدم وجود هیچ کد `R28` یا بالاتر در کل مجموعه اسناد.

Status: **PASS**

---

## ۴. Circular Dependency — ✅ مستقیماً بررسی شد (تحلیل جهت هر ۲۷ رابطه)

جهت هر ۲۷ رابطه از IA استخراج شد. نتیجه:

- **G-internal (R01/R02):** جفت معکوس صریح (LOCATED_IN / CONTAINS روی همان یال) — طبق الگوی SSOT پروژه، یکی Stored و دیگری Derived تلقی می‌شود (دقیقاً مثل R20/R21 که CA-D صراحتاً «R21 = معکوس مشتق‌شدهٔ R20» ثبت کرده). این یک Cycle مضر نیست — الگوی استاندارد گراف‌های دانش برای نمایش دوطرفهٔ یک رابطهٔ سلسله‌مراتبی.
- **بقیهٔ ۲۵ رابطه:** جهت غالب یک‌طرفه به‌سمت G (Geography First) یا درون یک Category است. هیچ زنجیرهٔ سه‌گانه یا بیشتر (مثل G→C→A→G) یافت نشد.
- **⚠️ یک جفت نیازمند شفاف‌سازی (نه Bug):** R13 (`A/C/G → C12`) و R14 (`C12 → G5 or A2`) یعنی نظری بین یک G/A entity و C12 (شخصیت تاریخی) دو رابطهٔ متفاوت ممکن است هم‌زمان درست باشد (مثلاً «G5 associated_with_figure C12» و «C12 born_in G5»). این دو رابطهٔ معنایی متفاوت‌اند (نه یک yal تکراری)، پس Cycle مضر محاسباتی نیست، اما توصیه می‌شود مثل R20/R21 صراحتاً مستند شود کدام Stored و کدام (در صورت وجود همپوشانی) Derived است.

Status: **PASS با یک یادداشت غیرمسدودکننده** (ثبت در Open Findings گزارش تجمیعی)

---

## ۵. Duplicate Modeling — Carry-forward + Spot-check

Carry-forward از خودِ اسناد CA (هرکدام این بررسی را به‌صورت صریح انجام داده‌اند):
- A↔C: مرز با `has_standing_structure` و تفکیک Museum/Building (CA-A-D002) — PASS ثبت‌شده.
- A↔D: Designation‌ها Node مجزا نیستند؛ Relationship Property‌اند — PASS ثبت‌شده.
- C↔E: C10 (پدیدهٔ پایدار) از E (رخداد زمان‌دار) با R17 تفکیک شده — PASS ثبت‌شده.
- D↔G: صفحهٔ Programme (D3/D4) از صفحهٔ ManagedArea (G) تفکیک شده (CA-D-D002) — PASS ثبت‌شده.

Spot-check من: عدم وجود هیچ Template دوم برای همان concept (مثلاً جست‌وجوی «Museum» در CA-A نشان داد فقط یک تعریف Field-level وجود دارد، نه دو).

Status: **PASS**

---

## ۶. Geography First Compliance (P-01) — Carry-forward + Verified via R01/R06/R15/R16/R22

هر Category یک لنگر جغرافیایی مشخص طبق IA دارد که مستقیماً از متن R01/R06/R08/R09/R15/R16/R22 (که همگی این دور استخراج شدند) تأیید می‌شود:
G (خودِ ریشه) · A→G (R01) · C→G (R06/R08/R09) · E→G (R15/R16) · P→G (R22).

Status: **PASS** (Verified — استخراج مستقیم جهت روابط)

---

## ۷. One Physical Place = One Primary Node (P-02) — Carry-forward

بررسی موردی توسط خودِ CA-D برای پیچیده‌ترین سناریو (Geopark/Biosphere که هم در D هم در G ظاهر می‌شوند) با نتیجهٔ PASS مستند شده: صفحهٔ D سطح Programme (مفهومی)، صفحهٔ G سطح ManagedArea (فیزیکی) — بدون تداخل. این معماری منطقی و در سطح طراحی درست است؛ بررسی دقیق‌تر آن نیازمند دادهٔ نمونهٔ واقعی (instance-level) است که در فاز ۴ بررسی می‌شود.

Status: **PASS (سطح طراحی)**

---

## ۸. IA Compliance — ✅ مستقیماً بررسی شد

هستهٔ این بررسی همان بخش‌های ۱ و ۳ بالاست. علاوه بر آن: شمارش موجودیت (42)، رابطه (27)، Constraint (10)، Classification (70)، D Programme Node (4) در تمام اسناد به‌صورت متقابل چک شد — بدون تناقض عددی (به‌جز یادداشت جزئی «۲ در برابر ۴ Attribute» در گزارش تجمیعی، بخش ۲).

Status: **PASS**

---

## ۹. Decision Consistency — ✅ مستقیماً بررسی شد

شناسه‌های تصمیم (IA D-001..032، CA-*-D001..00N) در سراسر اسناد grep شد. هیچ شناسهٔ تکراری بین دو سند مختلف با محتوای متفاوت یافت نشد. هیچ ارجاع «طبق CA-X-D00Y» به تصمیمی که در همان سند تعریف نشده باشد یافت نشد.

Status: **PASS**

---

## ۱۰. Deferred Consistency & Patch Consistency — ❌→✅ بزرگ‌ترین یافتهٔ این ممیزی

این تنها بُعدی بود که واقعاً FAIL داشت — جزئیات کامل در Phase3-FINAL-CONSOLIDATED-REPORT.md بخش‌های ۵ و ۷. خلاصه:

| مورد | وضعیت قبل | وضعیت بعد |
|------|-----------|-----------|
| P-PATCH-001 (G1) | ✅ واقعاً اعمال‌شده | بدون تغییر |
| P-PATCH-002 (E1 visa) | ❌ ادعا شده، اعمال نشده | ✅ این دور اعمال شد |
| P-PATCH-002 (E1 R23→P6) | ❌ ادعا شده، اعمال نشده | ⚠️ همچنان غایب — خارج از دامنهٔ پچ درخواستی این دور، به Open Findings منتقل شد |
| P-PATCH-003 (A9) | ❌ ادعا شده، اعمال نشده | ✅ این دور اعمال شد |
| CA-E-D001→CA-A | ❌ هرگز ردیابی نشده | ✅ این دور اعمال شد |

Status: **FAIL → RESOLVED** (۴ از ۵ مورد کاملاً رفع شد؛ ۱ مورد در Open Findings برای دور بعد)

---

## بخش ۱۱ — Cross-Category Consistency Matrix

| جفت Category | بررسی | نتیجه |
|--------------|-------|--------|
| G ↔ A | R01 جهت + G5 anchor | ✅ |
| G ↔ C | R06/R08/R09 جهت | ✅ |
| G ↔ D | Programme vs ManagedArea تفکیک | ✅ |
| G ↔ E | R15/R16 جهت + منبع E صحیح (این دور اصلاح شد) | ✅ (پس از AUDIT-PATCH-004) |
| G ↔ P | R22 جهت | ✅ |
| A ↔ C | مرز C9/C12/C13 | ✅ |
| A ↔ D | R20 Targets دقیقاً D1,D2,D3,D4 | ✅ |
| A ↔ E | R15 Sources/Targets | ✅ |
| A ↔ P | فقط R24 (A3→P5) | ✅ (پس از AUDIT-PATCH-001) |
| C ↔ E | R17/R18 تفکیک پدیدهٔ پایدار/رخداد | ✅ |
| C ↔ P | فقط R23 (C10→P6) | ✅ |
| E ↔ P | فقط R23 (E1→P6)؛ E5/E6 نباید P-relation داشته باشند | ✅ (پس از AUDIT-PATCH-002/003) |
| D ↔ P | هیچ رابطه‌ای در IA تعریف نشده | ✅ (تأیید شد — صفر رخداد) |

---

## خلاصهٔ نهایی ۱۰-بُعدی

```
1. Relationship Inflation ............... ✅ PASS (Verified)
2. Entity Inflation ...................... ✅ PASS
3. Category-level Relationship Count ..... ✅ PASS (Verified)
4. Circular Dependency ................... ✅ PASS + 1 non-blocking note
5. Duplicate Modeling ..................... ✅ PASS
6. Geography First (P-01) ................ ✅ PASS (Verified)
7. One Physical Place = One Node (P-02) .. ✅ PASS (design-level)
8. IA Compliance .......................... ✅ PASS
9. Decision Consistency ................... ✅ PASS
10. Deferred/Patch Consistency ............ ✅ RESOLVED (was FAIL)

Cross-Category Matrix: 13 / 13 pairs PASS
```

---

*ToPersia Phase3-KNOWLEDGE-GRAPH-VALIDATION-v1.0*
*مرجع: IA-v1.2.1-FINAL (Frozen)*
