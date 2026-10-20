<?php
defined('ABSPATH') || exit;

/**
 * دارایی‌های فرانت‌اند (CSS/JS) فقط جایی بار می‌شوند که واقعاً لازم‌اند:
 * صفحۀ حساب کاربری، صفحۀ ورود، و صفحات تک هر نوع‌پست ToPersia (چون دکمۀ
 * «ذخیره» می‌تواند آنجا باشد). قبلاً روی هر صفحۀ سایت بار می‌شد — با
 * ۲۱٬۶۰۰ صفحۀ برنامه‌ریزی‌شده، این یعنی ~۳۰ کیلوبایت CSS/JS اضافه روی هر
 * بازدید، برای چیزی که اکثر صفحات اصلاً لازمش ندارند.
 */
final class ToPersia_Assets {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 25);
    }

    /**
     * شورت‌کدهایی که به CSS/JS این افزونه نیاز دارند.
     */
    private const SHORTCODES = ['topersia_login', 'topersia_user_dashboard', 'topersia_save_button'];

    public function enqueue(): void {
        if (!$this->should_load()) {
            return;
        }

        wp_enqueue_style(
            'topersia-user-core',
            TOPERSIA_USER_CORE_URL . 'assets/css/user-core.css',
            [],
            TOPERSIA_USER_CORE_VERSION
        );

        wp_enqueue_script(
            'topersia-user-core',
            TOPERSIA_USER_CORE_URL . 'assets/js/user-core.js',
            [],
            TOPERSIA_USER_CORE_VERSION,
            true
        );

        wp_localize_script('topersia-user-core', 'ToPersiaUserCore', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            /* ۱٫۱۲٫۱ · P5-D374 — مهمان: بی نانس (HTML مهمان کش می‌شود)؛ از REST بی‌کش گرفته می‌شود. */
            'nonce'     => is_user_logged_in() ? wp_create_nonce('topersia_user_core') : '',
            'nonceUrl'  => esc_url_raw(rest_url('topersia/v1/uc-nonce')),
            'accountUrl'=> class_exists('ToPersia_Admin') ? ToPersia_Admin::account_url() : home_url('/'),
            'homeUrl'   => apply_filters('wpml_home_url', home_url('/')),
            'savedLabel' => class_exists('ToPersia_I18n') ? ToPersia_I18n::t('saved_badge') : 'Saved',
            'removeSavedLabel' => class_exists('ToPersia_I18n') ? ToPersia_I18n::t('remove_saved') : 'Remove from saved',
            /* برچسب حالت «ذخیره‌نشده» — جاوااسکریپت پس از حذف به همین
               برمی‌گردد. پیش‌تر وجود نداشت و اسکریپت مجبور بود یکی از دو
               برچسب موجود را جای نادرست به کار ببرد. */
            'saveLabel' => class_exists('ToPersia_I18n') ? ToPersia_I18n::t('save_button') : 'Save',
            /* ۱٫۱۰٫۰ — «هستۀ نمایش» از روی این‌ها تصمیم می‌گیرد کنش را
               بفرستد یا پنجرۀ ورود را باز کند. HTML صفحه برای مهمان کش
               می‌شود، پس همین مقدار هم با همان HTML کش می‌شود و هم‌خوان
               می‌ماند. */
            'loggedIn'  => is_user_logged_in(),
            /* ۱٫۱۴٫۰ — زبان نامۀ کد ایمیلی = زبان صفحه (LOGIN.md §۳-پ)؛ HTML مهمان به ازای زبان کش می‌شود */
            'lang'      => class_exists('ToPersia_I18n') ? ToPersia_I18n::current_language() : 'fa',
            'loginUrl'  => class_exists('ToPersia_Admin') ? ToPersia_Admin::account_url() : home_url('/'),
            'i18n'      => class_exists('ToPersia_I18n') ? [
                'otpSending'    => ToPersia_I18n::t('otp_sending'),
                'otpChecking'   => ToPersia_I18n::t('otp_checking'),
                'networkError'  => ToPersia_I18n::t('network_error'),
                'reasonSave'    => ToPersia_I18n::t('login_reason_save'),
                'reasonFollow'  => ToPersia_I18n::t('login_reason_follow'),
                'reasonReview'  => ToPersia_I18n::t('login_reason_review'),
                'reasonAi'      => ToPersia_I18n::t('login_reason_ai'),
                'follow'        => ToPersia_I18n::t('follow_button'),
                'following'     => ToPersia_I18n::t('following_badge'),
                'unfollow'      => ToPersia_I18n::t('unfollow'),
            ] : [],
        ]);
    }

    /**
     * تشخیص اینکه آیا صفحۀ جاری واقعاً به این دارایی‌ها نیاز دارد.
     *
     * چهار راه برای «بله» وجود دارد:
     * ۱) یکی از شورت‌کدهای این افزونه در متن نوشته باشد (روش معمول).
     * ۲) نشانی صفحۀ جاری همان نشانی تنظیم‌شدۀ حساب کاربری باشد (برای
     *    مواردی که صفحه‌ساز محتوا را به‌شکلی غیر از شورت‌کد ساده نگه می‌دارد).
     * ۳) صفحه، تکِ یکی از نوع‌پست‌های ToPersia باشد — چون دکمۀ «ذخیره» با
     *    قالب Listing/Elementor روی این صفحات می‌نشیند و معمولاً داخل
     *    post_content واقعی نیست تا با has_shortcode() دیده شود.
     * ۴) متای المنتور صفحه (اگر با المنتور ساخته شده) به‌صورت متنی حاوی
     *    یکی از نام‌های شورت‌کد باشد — چون ویجت Shortcode/Text المنتور هم
     *    محتوایش را در post_content معمولی ذخیره نمی‌کند.
     *
     * برای هر حالت پیش‌بینی‌نشده، فیلتر `topersia_user_core_load_assets`
     * راه فرار همیشگی است.
     */
    private function should_load(): bool {
        global $post;

        $load = false;

        if ($post instanceof WP_Post) {
            foreach (self::SHORTCODES as $shortcode) {
                if (has_shortcode((string) $post->post_content, $shortcode)) {
                    $load = true;
                    break;
                }
            }

            if (!$load && post_type_exists($post->post_type) && strpos($post->post_type, 'tp_') === 0) {
                $load = true;
            }

            if (!$load && did_action('elementor/loaded')) {
                $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
                if (is_string($elementor_data) && $elementor_data !== '') {
                    foreach (self::SHORTCODES as $shortcode) {
                        if (strpos($elementor_data, $shortcode) !== false) {
                            $load = true;
                            break;
                        }
                    }
                }
            }
        }

        /* تشخیص برگۀ حساب — با شناسه، نه با مقایسۀ رشتۀ نشانی.

           مقایسۀ قبلی دو راه شکست داشت: (۱) در چندزبانه، نشانی
           تنطیمات فارسی بود و روی `/en/account/` هیچ‌وقت جور نمی‌شد، پس
           پنل انگلیسی بدون CSS و JS رندر می‌شد؛ (۲) هر اختلاف جزئی
           در نشانی (www، http/https، اسلش پایانی، پارامتر کوئری) بی‌سروصدا
           همین نتیجه را می‌داد. شناسه هیچ‌کدام از این حساسیت‌ها را ندارد. */
        if (!$load && class_exists('ToPersia_Admin')) {
            $account_id = ToPersia_Admin::account_page_id_canonical();

            if ($account_id > 0) {
                $current_id = (int) get_queried_object_id();

                if ($current_id > 0) {
                    /* برگۀ جاری را هم به زبان پیش‌فرض می‌بریم تا دو طرف
                       مقایسه در یک فضا باشند. */
                    $default = apply_filters('wpml_default_language', null);

                    if (is_string($default) && $default !== '') {
                        $mapped = apply_filters('wpml_object_id', $current_id, 'page', true, $default);

                        if (is_numeric($mapped) && (int) $mapped > 0) {
                            $current_id = (int) $mapped;
                        }
                    }

                    if ($current_id === $account_id) {
                        $load = true;
                    }
                }
            }
        }

        /**
         * فیلتر فرار برای هر موردی که این تشخیص خودکار نتواند پوشش دهد
         * (مثلاً یک ویجت سایدبار سراسری که دکمۀ ذخیره را جای دیگری می‌گذارد).
         */
        return (bool) apply_filters('topersia_user_core_load_assets', $load);
    }
}
