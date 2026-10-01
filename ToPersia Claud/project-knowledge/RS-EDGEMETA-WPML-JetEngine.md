# ترجمهٔ چندزبانهٔ متادیتای روابط (Relation/Edge Meta) در JetEngine + WPML — تشخیص فنی و راهکار

## TL;DR
- محدودیت واقعی است اما **ذاتی و بدون‌راه‌حل نیست**: WPML به‌طور معماری متای یال JetEngine را نمی‌بیند و کپی/ترجمه نمی‌کند، اما با کد سفارشی روی هوک‌های رسمی JetEngine و WPML قابل حل است.
- علت ریشه‌ای: متای رابطه در جداول اختصاصی (`wp_jet_rel_default_meta` یا `wp_jet_rel_{ID}_meta`) ذخیره می‌شود که **هیچ ستون زبان ندارند** و خارج از `wp_postmeta` هستند؛ به همین دلیل ATE و بخش `custom-fields` فایل wpml-config.xml هرگز آن‌ها را پوشش نمی‌دهند.
- توصیهٔ راهبردی: برای مقیاس ۲۱٬۶۰۰ موجودیت و ۹ زبان، متای یالِ **قابل‌ترجمه** را از رابطه خارج و به postmeta روی یک CPT واسط (reification) منتقل کنید؛ متای صرفاً عددی/غیرزبانی را روی یال نگه‌دارید و با اسنیپت هوک بین ترجمه‌ها سینک کنید.

## Key Findings

۱. **موضع رسمی**: مستندات Crocoblock دربارهٔ «ترجمهٔ روابط با WPML» فقط ترجمهٔ آیتم‌های والد/فرزند و برچسب‌های رابطه (از طریق String Translation) را پوشش می‌دهد؛ **هیچ اشاره‌ای به ترجمهٔ متای یال ندارد**. WPML رسماً اعلام می‌کند «WPML is now fully compatible with the JetEngine plugin»، اما دامنهٔ این سازگاری در همان سند فقط CPT، Listing Items و Components است — نه متای رابطه.

۲. **محل ذخیره‌سازی**: داده در جدول رابطه‌ای اختصاصی ذخیره می‌شود، نه در `wp_postmeta`. ستون‌ها: جدول اتصال `_ID، rel_id، parent_object_id، child_object_id، parent_rel، child_rel، order` و جدول متا `_ID، rel_id، parent_object_id، child_object_id، meta_key، meta_value`. هیچ ستون language/locale وجود ندارد.

۳. **چرا ATE چیزی نشان نمی‌دهد**: WPML فقط چیزی را در ATE می‌آورد که در `wp_postmeta` باشد و در `custom-fields` فایل wpml-config.xml اعلام شده باشد. متای یال در هیچ‌کدام نیست، پس نامرئی است. این قابل رفع با پیکربندی نیست؛ نیاز به کد دارد.

۴. **مقدار کپی نمی‌شود (عربی)**: چون WPML هیچ مکانیزمی برای این جدول ندارد، هنگام ساخت ترجمه فقط ردیف اتصال (که به آیتم فرزند ترجمه‌شده remap می‌شود) کپی می‌شود، اما متای یال کپی نمی‌شود و خالی می‌ماند.

۵. **CCT با WPML بدتر است**: طبق مستند رسمی Crocoblock (نقل مستقیم): «Please note that, as of now, it is not possible to translate Custom Content Types (CCTs) with WPML.» پس CCT جایگزین مناسبی برای رفع مشکل ترجمه نیست.

## Details

### الف) محل ذخیره‌سازی فیزیکی
ماژول Relations داده را در جداول اختصاصی JetEngine خارج از هستهٔ WordPress نگه می‌دارد:
- **جدول اتصال (یال)**: به‌طور پیش‌فرض `wp_jet_rel_default`. با روشن‌بودن «Register separate DB table» جدولی مثل `wp_jet_rel_81` (که ۸۱ شناسهٔ رابطه است) ساخته می‌شود. ستون‌ها: `_ID` (کلید اصلی)، `rel_id`، `parent_object_id`، `child_object_id`، `parent_rel`، `child_rel`، `order`.
- **جدول متای یال**: `wp_jet_rel_default_meta` یا در حالت اختصاصی `wp_jet_rel_81_meta`. ستون‌ها: `_ID`، `rel_id`، `parent_object_id`، `child_object_id`، `meta_key`، `meta_value` — دقیقاً همان الگوی EAV مثل postmeta اما در جدولی جدا. (منبع رسمی: KB «How to Filter by Relation Meta» و بلاگ فنی CTO؛ نام ستون‌ها از گیست‌های رسمی Crocoblock تأیید شده.)
- **نکتهٔ کلیدی**: هیچ‌کدام از این جداول ستون کد زبان ندارند. مقدار متا به جفت (parent_object_id, child_object_id) گره خورده است، نه به زبان. تفاوت حالت پیش‌فرض و اختصاصی صرفاً نام جدول و جداسازی داده است؛ رفتار ترجمه در هر دو یکسان (و ناموجود) است.

این یعنی WPML، که کل مدل ترجمهٔ متایش بر `wp_postmeta` و اعلان در `<custom-fields>` استوار است، از نظر معماری هیچ قلابی به این جدول ندارد. به همین دلیل فایل wpml-config.xml شما (با ۱۴۴ کلید) هرگز نمی‌تواند متای یال را پوشش دهد — این کلیدها همه postmeta هستند.

### ب) موضع رسمی Crocoblock و WPML
- Crocoblock سندی به نام «How to Translate JetEngine Relations Using WPML» دارد، اما تمام مراحل آن دربارهٔ ترجمهٔ آیتم‌های والد/فرزند و برچسب‌های رابطه است. متای یال اصلاً ذکر نشده — یعنی رسماً پشتیبانی‌نشده و مستندنشده است (سکوت، نه انکار صریح).
- WPML رسماً JetEngine را «توصیه‌شده و کاملاً سازگار» می‌داند، اما نمونه‌های سازگاری CPT/متا/تاکسونومی/listing هستند.
- WPML یک errata رسمی دارد: «JetEngine – Post Type Relationship Moves to Translated Post». نقل مستقیم: «If a relation is set between those two custom post types in default language, and then the shop post is translated, the relationship is unexpectedly moved to the translated shop post. Translate your custom post type (coupon) before setting the relationship. … So far we don’t have another alternative.» این تأیید می‌کند که خودِ اتصال رابطه با ترجمه رفتار ناپایدار دارد و مستقیماً با رفتار غیرقطعی مشاهده‌شدهٔ شما (ردیف خراب در انگلیسی ولی نه در عربی) هم‌خوان است.
- درخواست‌های باز روی مخزن Crocoblock/suggestions در GitHub نشان می‌دهد شکاف‌های ترجمهٔ JetEngine+WPML شناخته‌شده و هنوز باز هستند؛ از جمله issue #1663 با عنوان صریح «URGENT. Full compatibility with WPML» که تصریح می‌کند «the only translatable functions are the Jet Engine Custom Post types and taxonomies» (یعنی فیلدهای متای checkbox/radio/select و به‌طریق‌اولی متای رابطه بومی ترجمه‌پذیر نیستند)، و issue #7998 دربارهٔ ترجمه‌ناپذیری Option Pages.

### ج) راهکار کدنویسی‌شده (راه‌حل قطعی)
JetEngine یک API کامل PHP برای خواندن/نوشتن متای یال دارد (کلاس Relation). طبق گیست رسمی Crocoblock (get-related.md) این متدها باید «on 'init' action with the priority of 12 or later» فراخوانی شوند، چون «Before init 12 there are no relations registered»:
- گرفتن شیء رابطه: `jet_engine()->relations->get_active_relations( $rel_id )`
- خواندن متا: `$relation->get_meta( $parent_id, $child_id, $meta_key )`
- نوشتن متا: `$relation->update_meta( $parent_id, $child_id, $meta_key, $meta_value )`
- هوک‌ها: `jet-engine/relation/update/after` (با امضای `function( $parent_object_id, $child_object_id, $item_id, $relation ){}, 0, 4`) و `jet-engine/relation/update-all-meta/after`

از سمت WPML، توابع/هوک‌های کلیدی: فیلتر `wpml_object_id` (برای remap شناسه بین زبان‌ها؛ جایگزین `icl_object_id` منسوخ) و اکشن `wpml_pro_translation_completed` (پس از کامل‌شدن ترجمه) و `wpml_after_save_post`.

راهبرد: چون متا به جفت (parent,child) گره خورده و مقدارش (سال ثبت عددی، معیار متنی) اغلب باید بین زبان‌ها یکسان بماند (Copy) یا ترجمه شود، بهترین کار **سینک خودکار متای یال از نسخهٔ زبان مبنا به جفت معادل در زبان مقصد** است. اسنیپت زیر را در پلاگین اختصاصی `topersia-core.php` قرار دهید:

```php
add_action( 'wpml_pro_translation_completed', 'topersia_sync_relation_meta', 20, 1 );
add_action( 'save_post', 'topersia_sync_relation_meta_on_save', 30, 1 );

function topersia_sync_relation_meta_on_save( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
    topersia_sync_relation_meta( $post_id );
}

function topersia_sync_relation_meta( $tr_post_id ) {
    if ( ! function_exists( 'jet_engine' ) ) return;

    $default_lang = apply_filters( 'wpml_default_language', null );
    $post_type    = get_post_type( $tr_post_id );

    // شناسهٔ نسخهٔ زبان مبنا (فارسی) برای همین پست
    $src_post_id = apply_filters( 'wpml_object_id', $tr_post_id, $post_type, true, $default_lang );
    if ( ! $src_post_id || $src_post_id == $tr_post_id ) return; // خودِ زبان مبنا است

    // شناسهٔ روابطی که می‌خواهید سینک شوند (ID رابطه در URL صفحهٔ Edit Relation)
    $relation_ids = array( 27 ); // مطابق پروژهٔ خود مقداردهی کنید

    foreach ( $relation_ids as $rel_id ) {
        $relation = jet_engine()->relations->get_active_relations( $rel_id );
        if ( ! $relation ) continue;

        // فرض: پست جاری «فرزند» رابطه است؛ در صورت والدبودن get_children بگیرید
        $src_rows = $relation->get_parents( $src_post_id, 'all' );
        if ( empty( $src_rows ) ) $src_rows = $relation->get_children( $src_post_id, 'all' );
        if ( empty( $src_rows ) ) continue;

        foreach ( $src_rows as $row ) {
            $src_parent = (int) $row['parent_object_id'];
            $src_child  = (int) $row['child_object_id'];

            // remap هر دو سرِ یال به زبان پست ترجمه‌شده
            $tr_lang    = apply_filters( 'wpml_element_language_code', null, array(
                'element_id' => $tr_post_id, 'element_type' => 'post_' . $post_type ) );
            $p_type     = get_post_type( $src_parent );
            $c_type     = get_post_type( $src_child );
            $tr_parent  = apply_filters( 'wpml_object_id', $src_parent, $p_type, true, $tr_lang );
            $tr_child   = apply_filters( 'wpml_object_id', $src_child,  $c_type, true, $tr_lang );
            if ( ! $tr_parent || ! $tr_child ) continue;

            // اطمینان از وجود اتصال در زبان مقصد
            $relation->update( $tr_parent, $tr_child );

            // کپی همهٔ کلیدهای متای یال از مبنا به مقصد
            $meta_keys = array( 'registration_year', 'registration_criteria' );
            foreach ( $meta_keys as $mkey ) {
                $val = $relation->get_meta( $src_parent, $src_child, $mkey );
                if ( $val !== null && $val !== '' ) {
                    $relation->update_meta( $tr_parent, $tr_child, $mkey, $val );
                }
            }
        }
    }
}
```

**توضیح محل قرارگیری**: این کد باید در پلاگین سفارشی (mu-plugin یا `topersia-core.php`) بارگذاری شود، نه در `functions.php` قالب (تا مستقل از قالب Woodmart بماند). آرایهٔ `$relation_ids` و `$meta_keys` را با شناسه‌های واقعی روابط و کلیدهای متای خود پر کنید. برای متای عددی «سال ثبت» که باید یکسان بماند این عیناً کپی می‌کند؛ برای متای متنی «معیار ثبت» که باید ترجمه شود، به‌جای کپی خودکار می‌توانید مقدار مبنا را به‌عنوان مقدار اولیه بگذارید و سپس دستی ویرایش کنید، یا آن کلید را از یال خارج کرده و به postmeta منتقل کنید (بخش ه).

**نکتهٔ String Translation**: ثبت مستقیم متای یال در WPML String Translation ممکن نیست، چون String Translation فقط رشته‌های ثبت‌شده از طریق API (`wpml_register_string`) یا wpml-config را می‌شناسد و به جدول رابطه دسترسی ندارد. تنها راه، ثبت برنامه‌نویسی‌شدهٔ هر مقدار متن با `do_action('wpml_register_string', ...)` و بازخوانی با `wpml_translate_string` است — که برای هزاران ردیف عملی نیست.

### د) خطای «Error: Empty object ID»
این پیام هنگام رندر جدول آیتم‌های مرتبط در ادمین ظاهر می‌شود، وقتی یک ردیف اتصال به یک object ID اشاره می‌کند که JetEngine نمی‌تواند آن را به یک پست/آیتم زندهٔ معتبر resolve کند (شناسهٔ صفر، خالی، یا پستِ حذف‌شده/ترجمه‌نشده). چون Edit/View نیاز به شیء زنده دارند حذف می‌شوند، اما Edit Meta و Disconnect که فقط روی خودِ ردیف اتصال و متایش کار می‌کنند باقی می‌مانند — دقیقاً همان چیزی که دیدید.

- **علت در سناریوی شما**: هنگام ساخت نسخهٔ انگلیسی، WPML/JetEngine یک ردیف اتصال اضافه ایجاد کرده که یک سرش به شناسه‌ای اشاره دارد که در آن زبان معادل معتبری ندارد (remap ناموفق) → یال یتیم. غیرقطعی‌بودن آن (رخ‌دادن در انگلیسی ولی نه عربی) با errata رسمی WPML دربارهٔ «جابه‌جایی رابطه به پست ترجمه‌شده» هم‌خوان است: ترتیب ترجمهٔ والد/فرزند نتیجه را تغییر می‌دهد.
- **پیشگیری**: (۱) قبل از برقراری رابطه، هر دو سرِ یال را در همهٔ زبان‌ها ترجمه کنید (راهکار رسمی errata: «Translate your custom post type before setting the relationship»). (۲) اسنیپت سینک بالا اتصال معادل را صریحاً می‌سازد و از یال یتیم جلوگیری می‌کند. (۳) جداول اتصال هیچ کلید خارجی/cascade ندارند، پس حذف یک پست ردیف را یتیم می‌کند؛ پاک‌سازی دوره‌ای لازم است.
- **پاک‌سازی ردیف‌های خراب**: روی ردیف یتیم دکمهٔ **Disconnect** را بزنید، یا برنامه‌نویسی‌شده `$relation->delete_rows( $parent_object_id, $child_object_id, $clear_meta )` را فراخوانی کنید. طبق گیست رسمی Crocoblock، «$clear_meta – true / false, whether to delete associated meta; default true» و هشدار مهم: «CAUTION: if both empty – all rows for the relation will be deleted». توجه: در ماژول Relations دکمهٔ اختصاصی «Reindex Relation Table» وجود ندارد؛ ابزار Indexer متعلق به JetSmartFilters است و این نوع یتیمی را رفع نمی‌کند. برای مقیاس بزرگ، یک اسکریپت SQL برای یافتن ردیف‌هایی که `parent_object_id` یا `child_object_id` آن‌ها در `wp_posts` وجود ندارد بنویسید و آن‌ها را (و ردیف‌های متای متناظر) حذف کنید.

### ه) الگوهای جایگزین معماری (رتبه‌بندی برای افق ۵ ساله)
با توجه به مقیاس (۲۱٬۶۰۰ موجودیت، ۴۲ CPT، ۲۷ رابطه، ۳→۹ زبان):

۱. **CPT واسط برای بازنمایی یال (reification) — رتبهٔ ۱ برای متای قابل‌ترجمه.** یال را به یک CPT واقعی تبدیل کنید (مثلاً «ثبت میراث») که دو رابطهٔ one-to-many به دو سرِ اصلی دارد و فیلدهای «سال ثبت» و «معیار ثبت» را به‌عنوان postmeta معمولی نگه می‌دارد. این postmeta کاملاً با WPML/ATE و wpml-config.xml شما کار می‌کند و از همان مسیر آزمودهٔ CPT ترجمه می‌شود. هزینهٔ نگهداری پایین، مقیاس‌پذیری عالی، سازگاری کامل. عیب: پیچیدگی مدل و کوئری بیشتر.
۲. **فیلد مسطح روی موجودیت (postmeta + کلاس ترجمهٔ WPML) — رتبهٔ ۲.** اگر متا واقعاً به یک سرِ رابطه تعلق دارد (نه به خودِ جفت)، آن را postmeta همان CPT کنید. ساده‌ترین و سازگارترین، اما فقط وقتی معنای «به ازای هر جفت» را از دست ندهید.
۳. **Repeater روی متاباکس موجودیت — رتبهٔ ۳.** یک repeater که «هدف + سال + معیار» را نگه می‌دارد. با WPML قابل ترجمه است اما ترجمهٔ repeater در JetEngine+WPML شکننده است و در مقیاس هزاران ردیف مدیریت داده دشوار می‌شود.
۴. **نگه‌داشتن روی یال + اسنیپت سینک — رتبهٔ ۴ (فقط برای متای غیرزبانی).** برای دادهٔ عددی/یکسان بین زبان‌ها (سال ثبت) کاملاً مناسب و کم‌هزینه است؛ برای متن قابل‌ترجمه توصیه نمی‌شود.
۵. **CCT به‌جای رابطه — توصیه نمی‌شود.** چون CCT رسماً با WPML قابل ترجمه نیست، مشکل ترجمه را بدتر می‌کند.

**توصیهٔ ترکیبی**: متای عددی/غیرزبانی را روی یال با اسنیپت سینک نگه‌دارید؛ متای متنیِ قابل‌ترجمه را با reification به CPT واسط منتقل کنید.

### و) Polylang و TranslatePress
- **Polylang**: با متای یال JetEngine هم مشکل مشابه دارد (JetEngine روابط را با روش‌های غیرمتعارف می‌سازد که Polylang به‌خوبی نمی‌شناسد)؛ گزارش‌های کاربران دربارهٔ مشکلات فیلد Gallery/media و رندرنشدن Components در زبان‌های غیرمبنا وجود دارد (issue #7591 و #8061 در Crocoblock/suggestions).
- **TranslatePress**: چون مبتنی بر ترجمهٔ خروجی HTML است، کوئری روابط را در زبان دوم درست اعمال نمی‌کند (کاربران در فروم WordPress.org گزارش داده‌اند کوئری رابطه در زبان دوم نادیده گرفته می‌شود و آخرین پست‌ها را نمایش می‌دهد).
- **جمع‌بندی**: Crocoblock رسماً WPML را توصیه می‌کند (شراکت پیوسته). هیچ‌کدام از رقبا متای یال را بومی ترجمه نمی‌کنند؛ مهاجرت از WPML مزیتی برای این مسئلهٔ خاص ندارد.

## Recommendations
۱. **فوری (هفتهٔ اول)**: تصمیم بگیرید کدام کلیدهای متای یال «غیرزبانی» (کپی) و کدام «قابل‌ترجمه» هستند. برای پروژهٔ نمونه: «سال ثبت» = کپی؛ «معیار ثبت» = قابل‌ترجمه.
۲. **کوتاه‌مدت**: اسنیپت سینک بخش ج را در `topersia-core.php` مستقر کنید تا متای غیرزبانی خودکار کپی و از یال یتیم پیشگیری شود. روی یک محیط staging با نسخه‌گیری کامل آزمایش کنید.
۳. **میان‌مدت (پیش از توسعه به ۹ زبان)**: کلیدهای متای قابل‌ترجمه را با reification به یک CPT واسط منتقل کنید و در wpml-config.xml به‌صورت Translate اعلام کنید. این تنها الگویی است که در افق ۵ ساله و ۹ زبان بدون بدهی فنی مقیاس می‌گیرد.
۴. **پاک‌سازی**: یک روتین (WP-CLI یا اسکریپت زمان‌بندی‌شده) برای یافتن و حذف ردیف‌های یتیم رابطه و متای متناظر بنویسید و ماهانه اجرا کنید.
۵. **آستانه‌های تغییر تصمیم**: اگر Crocoblock در نسخه‌ای آینده ترجمهٔ بومی متای رابطه یا CCT را اضافه کرد (issueهای باز GitHub را دنبال کنید)، الگوی reification را می‌توان کنار گذاشت. اگر تعداد کلیدهای قابل‌ترجمهٔ یال کم و ثابت بماند، Repeater هم گزینهٔ قابل قبولی است.

## Caveats
- خطای «Empty object ID» به‌عنوان رشتهٔ دقیق در هیچ مستند رسمی یا گزارش عمومی کاربری تأیید نشد؛ تحلیل علت/رفع بر پایهٔ ساختار جداول و رفتار مستندِ دکمه‌های Edit Meta/Disconnect است (استنتاج مکانیزمی قوی، نه نقل‌قول مستقیم). برای قطعیت، گروه فیسبوک Crocoblock (۳۰هزار+ عضو، عمدتاً ایندکس‌نشده) یا سورس پلاگین را جست‌وجو کنید.
- نوع دقیق ستون‌های SQL (bigint/mediumint و…) از سورس عمومی پلاگین قابل تأیید نبود؛ **نام** ستون‌ها از گیست‌های رسمی Crocoblock و KB تأیید شده و **نبود ستون زبان** از فهرست کامل ستون‌ها استنتاج شده است (هیچ منبعی خلاف آن نگفته).
- اسنیپت ارائه‌شده الگو است؛ جهت والد/فرزند، شناسهٔ روابط و کلیدهای متا باید با ساختار واقعی شما تطبیق داده و روی staging آزمایش شود.
- ترجمهٔ خودکار WPML غیرفعال است؛ اسنیپت با هوک `wpml_pro_translation_completed` و `save_post` کار می‌کند، اما اگر گردش‌کار ATE متفاوت باشد ممکن است نیاز به هوک `wpml_after_save_post` یا اجرای دستی داشته باشد.

---

### منابع اصلی (URL)
**مستندات رسمی Crocoblock:**
- ترجمهٔ روابط با WPML: https://crocoblock.com/knowledge-base/jetengine/translating-jetengine-relations-using-wpml/
- ترجمهٔ CPT/Listing/Components (تصریح ترجمه‌ناپذیری CCT): https://crocoblock.com/knowledge-base/jetengine/translating-cpts-listing-items-and-components-with-wpml/
- فیلتر بر اساس متای رابطه (نام جداول و ستون‌ها): https://crocoblock.com/knowledge-base/jetengine/how-to-filter-by-relation-meta/
- مرور تنظیمات Relations: https://crocoblock.com/knowledge-base/features/relations-overview/
- ویرایش آیتم‌های مرتبط (دکمه‌های Edit Meta/Disconnect): https://crocoblock.com/knowledge-base/jetengine/related-items-editing-overview/
- REST API روابط: https://crocoblock.com/knowledge-base/jetengine/jetengine-getting-and-updating-relation-data-via-rest-api/
- گیست رسمی متدها/هوک‌های متای رابطه: https://gist.github.com/Crocoblock/b5b6ef62436b01ce23bc60d1a554a85b
- گیست ثبت/به‌روزرسانی برنامه‌نویسی‌شدهٔ روابط: https://gist.github.com/Crocoblock/102314ceabe4e6759e1ff196f1d91079

**مستندات و errata رسمی WPML:**
- سازگاری JetEngine + WPML: https://wpml.org/plugin/jetengine/
- راهنمای ترجمهٔ JetEngine با WPML: https://wpml.org/documentation/plugins-compatibility/how-to-translate-jetengine-with-wpml/
- errata «Relationship Moves to Translated Post»: https://wpml.org/errata/jetengine-post-type-relationship-moves-to-translated-post/
- فیلتر `wpml_object_id`: https://wpml.org/wpml-hook/wpml_object_id/
- مرجع هوک‌های WPML: https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/
- ترجمهٔ فیلدهای سفارشی: https://wpml.org/documentation/getting-started-guide/translating-custom-fields/

**درخواست‌ها/تجربهٔ کاربران (تفکیک‌شده از مستندات رسمی):**
- GitHub Crocoblock/suggestions #1663 «URGENT. Full compatibility with WPML»: https://github.com/Crocoblock/suggestions/issues/1663
- GitHub #7998 (ترجمه‌ناپذیری Option Pages): https://github.com/Crocoblock/suggestions/issues/7998
- GitHub #7591 (Polylang + Components): https://github.com/Crocoblock/suggestions/issues/7591
- فروم WPML «cant translate relationship jetengine»: https://wpml.org/forums/topic/cant-translate-relationsship-jetengine/
- فروم WordPress.org «Translatepress and Jet Engine Relations»: https://wordpress.org/support/topic/translatepress-and-jet-engine-relations/