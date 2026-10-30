# کلیدها و رازها — ثابت wp-config پیش از گزینه · P5-D478 (دستهٔ 37)

> فقط مستندسازی؛ کدی عوض نشد. هیچ مقدار یا بخشی از مقدار کلید در این سند، لاگ یا خروجی ابزارها نیست.

## قاعده

کد ToPersia کلید را **اول از ثابت wp-config** می‌خواند و فقط اگر ثابت نبود از گزینهٔ پایگاه‌داده (پنل). همین الان چنین است:

| مصرف‌کننده | ثابت wp-config | اگر ثابت هست | مرجع کد |
|---|---|---|---|
| خلاصه‌ساز AI (موتور داخلی) | `TOPERSIA_AI_API_KEY` | کلید پنل خوانده و ذخیره نمی‌شود؛ پنل فقط «ثابت فعال است» نشان می‌دهد | `class-tp-ai-options.php` `key_from_constant()` · P5-D355 |
| Google Gemini TTS | `TOPERSIA_GEMINI_API_KEY` | ثابت بر پنل برنده است | هستهٔ `topersia-services.php` · P5-D249 |
| ElevenLabs | `TOPERSIA_ELEVENLABS_API_KEY` | همان |
| Open-Meteo (هوا و کیفیت هوا) | `TOPERSIA_OPEN_METEO_API_KEY` | همان |
| MapTiler (ماهواره) | `TOPERSIA_MAPTILER_API_KEY` | همان |
| نشان (مسیریابی · ماتریس · آدرس) | `TOPERSIA_NESHAN_API_KEY` | همان |
| ورود با Google | `TOPERSIA_GOOGLE_CLIENT_SECRET` | همان |

ثابت‌های **تازه** افزوده نشد؛ جدول بالا همهٔ ثابت‌های کلید ToPersia است. نمونه در wp-config (مقدار را خودتان بگذارید، در git نباشد):

```php
define( 'TOPERSIA_AI_API_KEY', '…' );   // خلاصه‌ساز
define( 'TOPERSIA_GEMINI_API_KEY', '…' );
```

کلیدهای ذخیره‌شده از پنل هسته با sodium **رمز** می‌شوند و کلید رمزگشا از salt در `wp-config.php` می‌آید؛ کلید ذخیره‌شده در پنل **خلاصه‌ساز** (گزینهٔ `topersia_ai_settings`) رمز نمی‌شود و به‌صورت متن ساده در پایگاه‌داده می‌ماند.

## آنچه روی لوکال دیده شد (فقط نام، بی مقدار)

گزینه‌هایی که مقدار کلید واقعی دارند و **متن ساده** در پایگاه‌داده‌اند:
- `connectors_ai_openai_api_key` · `connectors_ai_anthropic_api_key` · `connectors_ai_google_api_key` — اتصال‌دهنده‌های هوش مصنوعی هستهٔ وردپرس (Settings ← Connectors).
- `topersia_ai_settings` — پنل خلاصه‌ساز (یک کلید داخل آرایهٔ تنظیمات).
- (غیر AI) `mcp_jwt_secret` و گزینه‌های مجوز افزونه‌های تجاری.

## اتصال‌دهنده‌های AI وردپرس (`connectors_ai_*`) — طبق مستند رسمی

منبع: [Introducing the Connectors API in WordPress 7.0](https://make.wordpress.org/core/2026/03/18/introducing-the-connectors-api-in-wordpress-7-0/) (مارس ۲۰۲۶)؛ تغییری در کد داده نشد.

- **هم متغیر محیطی و هم ثابت PHP پشتیبانی می‌شود.** ترتیب خواندن: ۱) متغیر محیطی ۲) ثابت PHP ۳) پایگاه‌داده.
- نام‌گذاری برای ارائه‌دهندهٔ AI: `{PROVIDER_ID}_API_KEY` (مستند فقط نمونهٔ `anthropic` ← `ANTHROPIC_API_KEY` را می‌آورد؛ برای `openai` و `google` الگو همان است ولی نمونهٔ صریح ندارد: `OPENAI_API_KEY` و `GOOGLE_API_KEY`).
- گزینهٔ پایگاه‌داده `connectors_ai_{provider_id}_api_key` است؛ **کلیدهای پایگاه‌داده رمز نمی‌شوند** (در رابط فقط پوشانده می‌شوند؛ رمزنگاری در تیکت core #64789 بررسی می‌شود).
- پیامد: اگر ثابت یا متغیر محیطی تعریف شود، مقدار پایگاه‌داده اهمیتی ندارد و می‌توان آن گزینه را پاک کرد.

> پیشنهاد (کار کاربر، تصمیم با شما): کلیدها را به ثابت‌های بالا و `ANTHROPIC_API_KEY` / `OPENAI_API_KEY` / `GOOGLE_API_KEY` در wp-config ببرید، سپس مقدار گزینه‌های بالا را از پیشخوان پاک کنید، و کلیدهایی را که در متن ساده بوده‌اند نزد ارائه‌دهنده **بچرخانید**.

## پشتیبان

`scripts/tp-backup.php` ردیف‌های رازدار را از بسته بیرون می‌گذارد (نام گزینه‌های مطابق `api_key|secret|token|passw|credential|license|private_key` یا با مقداری شبیه کلید، و نشست‌های کاربر) و خروجی پایگاه‌داده را هنگام نوشتن با الگوهای کلید پویش می‌کند. در گزارش‌ها **فقط نام گزینه و شمار** چاپ می‌شود، هرگز مقدار یا پیشوند. بازگردانی بسته یعنی این کلیدها و تنظیمات AI دوباره وارد شوند (بهتر: ثابت wp-config).
