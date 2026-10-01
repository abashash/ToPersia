<?php
defined('ABSPATH') || exit;

final class ToPersia_JetEngine {
    private static ?self $instance = null;

    /**
     * یادسپاری درون‌درخواستی فهرست ذخیره‌شده‌ها، کلید: "user_id:lang".
     *
     * @var array<string,array<int,array{post_id:int,post_type:string,view_id:int}>>
     */
    private static array $saved_cache = [];

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_shortcode('topersia_save_button', [$this, 'save_button_shortcode']);
        add_action('wp_ajax_topersia_save_place', [$this, 'save_place']);
        add_action('wp_ajax_topersia_remove_saved_place', [$this, 'remove_saved_place']);
    }

    /* ==================================================================
       هم‌سان‌سازی شناسه با WPML

       در این سایت، WPML خودِ پست موجودیت را ترجمه می‌کند — نه
       متاکلیدهایش را. پس یک موجودیت واحد (مانند «اصفهان») به ازای هر
       زبان یک پست با شناسۀ متفاوت دارد.

       پیامدش در جدول «ذخیره‌شده‌ها» یک باگ داده بود: کلید یکتای این
       جدول (user_id, post_id) است، پس همان اصفهان اگر یک‌بار از /fa/ و
       یک‌بار از /en/ ذخیره می‌شد، دو ردیف جدا می‌ساخت و دو کارت تکراری
       در پنل نشان می‌داد — و حذف یکی دیگری را برنمی‌داشت.

       قاعدۀ تازه: در جدول، همیشه شناسۀ زبان پیش‌فرض ذخیره می‌شود
       (canonical_id)؛ برای نمایش، همیشه به شناسۀ زبان جاری ترجمه
       می‌شود (localized_id).
       ================================================================== */

    /**
     * شناسۀ موجودیت در زبان پیش‌فرض سایت — کلید ذخیره‌سازی.
     *
     * بدون WPML (یا اگر ترجمه‌ای نباشد) همان شناسۀ ورودی برمی‌گردد،
     * پس افزونه بدون WPML هم سالم کار می‌کند.
     */
    public static function canonical_id(int $post_id, string $post_type): int {
        if ($post_id <= 0 || !function_exists('apply_filters')) return $post_id;

        $default = apply_filters('wpml_default_language', null);
        if (!is_string($default) || $default === '') return $post_id;

        $canonical = apply_filters('wpml_object_id', $post_id, $post_type, true, $default);

        return is_numeric($canonical) && (int) $canonical > 0 ? (int) $canonical : $post_id;
    }

    /**
     * شناسۀ موجودیت در زبان جاری — کلید نمایش.
     *
     * آرگومان سوم `true` یعنی «اگر ترجمه نبود، اصل را بده» — پس موجودیتی
     * که هنوز به عربی ترجمه نشده، جای ناپدید شدن از پنل، به فارسی دیده
     * می‌شود.
     */
    public static function localized_id(int $post_id, string $post_type): int {
        if ($post_id <= 0 || !function_exists('apply_filters')) return $post_id;

        $lang = apply_filters('wpml_current_language', null);
        if (!is_string($lang) || $lang === '') return $post_id;

        $localized = apply_filters('wpml_object_id', $post_id, $post_type, true, $lang);
        $localized = is_numeric($localized) && (int) $localized > 0 ? (int) $localized : $post_id;

        /* ترجمۀ منتشرنشده (پیش‌نویس، خصوصی، رمزدار) هرگز نمایش داده نمی‌شود؛
           اصل جایش می‌نشیند.  P5-D247 */
        return $localized !== $post_id && !self::is_live($localized) ? $post_id : $localized;
    }

    /**
     * منتشرشده و بی‌گذرواژه — همان دو شرطِ دروازۀ مرکزیِ «هستۀ نمایش»
     * (`topersia_g_published`) برای یک پست.  P5-D247
     *
     * شرطِ سومِ آن دروازه — نسخۀ منتشرشدۀ زبان جاری — در پنل به شکل
     * localized_id() است: ترجمۀ منتشرنشده جایش را به اصل می‌دهد، نه اینکه
     * موجودیت از پنل برود. پنل کاربر هر چیزی را که صفحۀ عمومی نشان
     * نمی‌دهد نشان نمی‌دهد: نه عنوان، نه پیوند.
     */
    public static function is_live(int $post_id): bool {
        $post = $post_id > 0 ? get_post($post_id) : null;

        return $post && $post->post_status === 'publish' && (string) $post->post_password === '';
    }

    /**
     * نام نمایشی — ترتیب متاکلیدها دیگر ثابت نیست.
     *
     * پیش‌تر همیشه اول `name_fa` خوانده می‌شد؛ چون عملاً هر موجودیتی
     * `name_fa` دارد، نام روی پنل انگلیسی و عربی هم فارسی نشان داده
     * می‌شد. حالا کلید زبان جاری اول است، و چون WPML خودِ پست را ترجمه
     * می‌کند، متا از روی پستِ همان زبان خوانده می‌شود.
     *
     * در نهایت `get_the_title()` می‌ماند که خودش عنوان ترجمه‌شده را می‌دهد.
     */
    public static function display_name(int $post_id): string {
        $lang = class_exists('ToPersia_I18n') ? ToPersia_I18n::current_language() : 'fa';

        $candidates = ['name_' . $lang, 'name_local', 'name_en', 'name_fa', 'name_ar'];

        foreach (array_unique($candidates) as $key) {
            $value = trim((string) get_post_meta($post_id, $key, true));
            if ($value !== '') return $value;
        }

        return get_the_title($post_id) ?: ('#' . $post_id);
    }

    /**
     * خلاصه — متاکلیدها در این سایت به‌ازای زبان جدا نیستند؛ WPML
     * خودِ پست را ترجمه می‌کند. پس فراخوان موظف است شناسۀ زبان جاری را
     * بدهد — و همین کاری است که saved_ids() حالا با view_id می‌کند.
     */
    public static function summary(int $post_id): string {
        foreach (['short_summary', 'overview_text'] as $key) {
            $value = trim(wp_strip_all_tags((string) get_post_meta($post_id, $key, true)));
            if ($value !== '') return wp_trim_words($value, 22);
        }
        return '';
    }

    public static function image(int $post_id): string {
        $thumb = get_the_post_thumbnail_url($post_id, 'medium');
        if ($thumb) return $thumb;

        $hero = get_post_meta($post_id, 'hero_image', true);

        if (is_numeric($hero)) {
            $url = wp_get_attachment_image_url((int) $hero, 'medium');
            if ($url) return $url;
        }

        /* hero_image می‌تواند نشانی هم باشد نه شناسۀ پیوست. پیش‌تر این حالت
           بی‌سروصدا دور ریخته می‌شد و کارت بی‌تصویر می‌ماند. */
        if (is_string($hero) && $hero !== '' && filter_var($hero, FILTER_VALIDATE_URL)) {
            return $hero;
        }

        return '';
    }

    /**
     * مکان‌های ذخیره‌شدۀ کاربر — از جدول اختصاصی (نه دیگر از یک آرایۀ
     * سریال‌شده در user_meta). اولین فراخوانی برای هر کاربر، اگر متای
     * قدیمی هنوز مانده باشد، یک‌بار آن را به جدول منتقل می‌کند.
     *
     * اعتبارسنجی زنده (پست هنوز منتشر است؟ نوع پستش عوض نشده؟) هنوز اینجا
     * انجام می‌شود چون فقط این کلاس معنای «موجودیت معتبر ToPersia» را
     * می‌داند — اما حالا با یک کوئری روی همۀ آیتم‌ها به‌جای یکی به ازای هر
     * آیتم.
     *
     * @return array<int,array{post_id:int,post_type:string}>
     */
    public function saved_ids(int $user_id): array {
        if (!class_exists('ToPersia_DB')) return [];

        /* یادسپاری درون‌درخواستی. پیش‌تر هر فراخوانی از صفر می‌رفت:
           بررسی مهاجرت + کوئری جدول + یک WP_Query کامل. و شورت‌کد دکمۀ
           ذخیره به ازای هر نمونه یک‌بار صدایش می‌زند، پس یک شبکۀ ۲۴
           کارتی می‌شد ۲۴ برابر همان کار. کلید کش زبان جاری را هم دارد،
           چون view_id به زبان وابسته است. */
        $lang = class_exists('ToPersia_I18n') ? ToPersia_I18n::current_language() : 'fa';
        $cache_key = $user_id . ':' . $lang;

        if (isset(self::$saved_cache[$cache_key])) {
            return self::$saved_cache[$cache_key];
        }

        ToPersia_DB::maybe_migrate_saved($user_id);
        $this->maybe_normalize_saved($user_id);

        $rows = ToPersia_DB::saved_rows($user_id);
        if (!$rows) return self::$saved_cache[$cache_key] = [];

        $by_id = [];
        foreach ($rows as $row) {
            if ($row['post_id'] > 0 && $row['post_type'] !== '' && str_starts_with($row['post_type'], 'tp_')) {
                $by_id[$row['post_id']] = $row['post_type'];
            }
        }
        if (!$by_id) return self::$saved_cache[$cache_key] = [];

        $published = get_posts([
            'post_type'      => array_unique(array_values($by_id)),
            'post__in'       => array_keys($by_id),
            'post_status'    => 'publish',
            'has_password'   => false,   // P5-D247
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'orderby'        => 'post__in',
        ]);

        $clean = [];
        foreach ($published as $post_id) {
            $post_id = (int) $post_id;
            // اگر نوع پست از زمان ذخیره‌شدن عوض شده باشد، دیگر معتبر نیست.
            if (get_post_type($post_id) !== $by_id[$post_id]) {
                continue;
            }

            $clean[] = [
                'post_id'   => $post_id,
                'post_type' => $by_id[$post_id],
                /* شناسه‌ای که قالب باید برای عنوان، خلاصه، تصویر و پیوند
                   به کار ببرد — ترجمۀ زبان جاری همان موجودیت. */
                'view_id'   => self::localized_id($post_id, $by_id[$post_id]),
            ];
        }

        return self::$saved_cache[$cache_key] = $clean;
    }

    /**
     * هم‌سان‌سازی یک‌بارۀ ردیف‌های یک کاربر به شناسۀ زبان پیش‌فرض.
     *
     * همان الگویی که maybe_migrate_saved() قبلاً در این افزونه اثبات کرده:
     * تنبل، پراکنده روی کاربرها، و دروازه‌بندی‌شده با یک متا. مهاجرت
     * سراسری در maybe_upgrade() عمداً انجام نمی‌شود: اولین بازدیدکنندۀ
     * پس از بروزرسانی نباید هزینۀ کل جدول را بپردازد.
     */
    private function maybe_normalize_saved(int $user_id): void {
        if ($user_id <= 0) return;

        if (get_user_meta($user_id, '_topersia_saved_wpml_normalized', true) === '1') {
            return;
        }

        /* بدون WPML کاری لازم نیست، ولی باز هم علامت می‌زنیم تا هر بار
           دوباره سنجیده نشود. */
        if (function_exists('apply_filters')) {
            $default = apply_filters('wpml_default_language', null);

            if (is_string($default) && $default !== '') {
                foreach (ToPersia_DB::saved_rows($user_id) as $row) {
                    $canonical = self::canonical_id($row['post_id'], $row['post_type']);

                    if ($canonical !== $row['post_id']) {
                        ToPersia_DB::saved_set_post_id($user_id, $row['post_id'], $canonical, $row['post_type']);
                    }
                }
            }
        }

        update_user_meta($user_id, '_topersia_saved_wpml_normalized', '1');
    }

    /**
     * پاک‌کردن یادسپاری — پس از هر نوشتن لازم است، وگرنه پاسخ AJAX
     * همان فهرست قبل از تغییر را می‌دهد.
     */
    private static function flush_saved_cache(int $user_id): void {
        foreach (array_keys(self::$saved_cache) as $key) {
            if (str_starts_with((string) $key, $user_id . ':')) {
                unset(self::$saved_cache[$key]);
            }
        }
    }

    public function save_button_shortcode($atts): string {
        if (!is_user_logged_in()) return '';

        /* برچسب پیش‌فرض دیگر فارسی سخت‌کد نیست. پیش‌تر روی صفحۀ
           انگلیسی و عربی هم دکمه فارسی بود — لایۀ I18n کاملاً دور
           زده شده بود. مقدار صریح در شورت‌کد همچنان برنده است. */
        $atts = shortcode_atts([
            'id'    => get_the_ID(),
            'label' => '',
        ], $atts, 'topersia_save_button');

        $label = trim((string) $atts['label']);
        if ($label === '') {
            $label = class_exists('ToPersia_I18n')
                ? ToPersia_I18n::t('save_button', 'ذخیره در مکان‌های من')
                : 'ذخیره در مکان‌های من';
        }

        $saved_label = class_exists('ToPersia_I18n')
            ? ToPersia_I18n::t('saved_badge', 'ذخیره‌شده')
            : 'ذخیره‌شده';

        $post_id = absint($atts['id']);
        $post = get_post($post_id);

        if (!$post || !str_starts_with($post->post_type, 'tp_') || !self::is_live($post_id)) {
            return '';
        }

        /* مقایسه باید روی شناسۀ زبان پیش‌فرض انجام شود، وگرنه روی
           /en/ دکمه همیشه «ذخیره‌نشده» نشان می‌داد، حتی اگر کاربر همان
           موجودیت را از /fa/ ذخیره کرده بود. */
        $canonical = self::canonical_id($post_id, $post->post_type);

        $active = false;
        foreach ($this->saved_ids(get_current_user_id()) as $item) {
            if ((int) $item['post_id'] === $canonical) {
                $active = true;
                break;
            }
        }

        return sprintf(
            '<button type="button" class="tp-save-entity%s" data-post-id="%d" data-post-type="%s">%s</button>',
            $active ? ' is-saved' : '',
            $post_id,
            esc_attr($post->post_type),
            esc_html($active ? '✓ ' . $saved_label : $label)
        );
    }

    public function save_place(): void {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!is_user_logged_in() || !wp_verify_nonce($nonce, 'topersia_user_core')) {
            wp_send_json_error(['message' => ToPersia_I18n::t('invalid_request', 'درخواست نامعتبر است.')], 403);
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $type = sanitize_key(wp_unslash($_POST['post_type'] ?? ''));
        $post = get_post($post_id);

        if (!$post || !self::is_live($post_id) || $post->post_type !== $type || !str_starts_with($type, 'tp_')) {
            wp_send_json_error(['message' => ToPersia_I18n::t('invalid_entity', 'موجودیت معتبر نیست.')], 400);
        }

        $user_id = get_current_user_id();

        if (!class_exists('ToPersia_DB')) {
            wp_send_json_error(['message' => ToPersia_I18n::t('internal_error', 'خطای داخلی.')], 500);
        }

        ToPersia_DB::maybe_migrate_saved($user_id);

        /* هر چه مرورگر فرستاده باشد — شناسۀ فارسی، انگلیسی یا عربی —
           در جدول فقط شناسۀ زبان پیش‌فرض می‌نشیند. این تنها راه نگه‌داشتن
           معنای کلید یکتای (user_id, post_id) در یک سایت چندزبانه است. */
        $canonical = self::canonical_id($post_id, $type);
        $canonical_type = get_post_type($canonical) ?: $type;

        ToPersia_DB::saved_add($user_id, $canonical, $canonical_type);
        self::flush_saved_cache($user_id);

        wp_send_json_success([
            'message' => class_exists('ToPersia_I18n')
                ? ToPersia_I18n::t('save_added', 'به مکان‌های ذخیره‌شده اضافه شد.')
                : 'به مکان‌های ذخیره‌شده اضافه شد.',
            'count'   => count($this->saved_ids($user_id)),
        ]);
    }

    public function remove_saved_place(): void {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!is_user_logged_in() || !wp_verify_nonce($nonce, 'topersia_user_core')) {
            wp_send_json_error(['message' => ToPersia_I18n::t('invalid_request', 'درخواست نامعتبر است.')], 403);
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $type    = sanitize_key(wp_unslash($_POST['post_type'] ?? ''));
        $user_id = get_current_user_id();

        if (!class_exists('ToPersia_DB')) {
            wp_send_json_error(['message' => ToPersia_I18n::t('internal_error', 'خطای داخلی.')], 500);
        }

        ToPersia_DB::maybe_migrate_saved($user_id);

        if ($type === '') {
            $type = (string) get_post_type($post_id);
        }

        /* همان هم‌سان‌سازی مسیر نوشتن. بدون این، حذف از روی صفحۀ
           انگلیسی ردیفی را در جدول پیدا نمی‌کرد و بی‌سروصدا هیچ کاری
           نمی‌کرد — دکمه وانمود می‌کرد حذف شده، و بارگذاری مجدد خلافش
           را نشان می‌داد. */
        $canonical = $type !== '' ? self::canonical_id($post_id, $type) : $post_id;

        ToPersia_DB::saved_remove($user_id, $canonical);
        self::flush_saved_cache($user_id);

        wp_send_json_success([
            'message' => class_exists('ToPersia_I18n')
                ? ToPersia_I18n::t('save_removed', 'از مکان‌های ذخیره‌شده حذف شد.')
                : 'از مکان‌های ذخیره‌شده حذف شد.',
            'count'   => count($this->saved_ids($user_id)),
        ]);
    }
}
