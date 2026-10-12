<?php
defined('ABSPATH') || exit;

final class ToPersia_Dashboard {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_shortcode('topersia_user_dashboard', [$this, 'shortcode']);
    }

    private function default_tabs_fallback(): array {
        return [
            'overview'=>['type'=>'builtin','enabled'=>1,'order'=>10,'icon'=>'home','fa'=>'نمای کلی','en'=>'Overview','ar'=>'نظرة عامة'],
            'profile'=>['type'=>'builtin','enabled'=>1,'order'=>20,'icon'=>'user','fa'=>'پروفایل من','en'=>'My Profile','ar'=>'ملفي الشخصي'],
            'saved'=>['type'=>'builtin','enabled'=>1,'order'=>30,'icon'=>'bookmark','fa'=>'مکان‌های ذخیره‌شده','en'=>'Saved Places','ar'=>'الأماكن المحفوظة'],
            'following'=>['type'=>'builtin','enabled'=>1,'order'=>35,'icon'=>'follow','fa'=>'دنبال‌شده‌ها','en'=>'Following','ar'=>'المتابَعة'],
            'trips'=>['type'=>'builtin','enabled'=>1,'order'=>40,'icon'=>'route','fa'=>'سفرهای من','en'=>'My Trips','ar'=>'رحلاتي'],
            'reviews'=>['type'=>'builtin','enabled'=>1,'order'=>60,'icon'=>'star','fa'=>'نظرات و امتیازها','en'=>'Reviews & Ratings','ar'=>'التقييمات'],
            'notifications'=>['type'=>'builtin','enabled'=>1,'order'=>70,'icon'=>'bell','fa'=>'اعلان‌ها','en'=>'Notifications','ar'=>'الإشعارات'],
            'settings'=>['type'=>'builtin','enabled'=>1,'order'=>80,'icon'=>'cog','fa'=>'تنظیمات','en'=>'Settings','ar'=>'الإعدادات'],
        ];
    }

    /* count_saved() حذف شد: هیچ‌جا فراخوانده نمی‌شد. شمارش واقعی از روی
       همان فهرستی انجام می‌شود که رندر می‌شود (count($saved_entities))، پس
       عدد روی تب و تعداد کارت‌ها هرگز اختلاف پیدا نمی‌کنند. */

    /**
     * تعداد سفرها.
     *
     * هشدار: متای `_topersia_trips_count` در هیچ نقطه‌ای از این افزونه
     * نوشته نمی‌شود، پس تا زمانی که مدل دادۀ سفر تصمیم‌گیری و پیاده
     * نشود، این عدد همیشه صفر است. عمداً نگه داشته شده تا قرارداد
     * قالب در پیاده‌سازی بعدی نشکند.
     */
    private function count_trips(int $user_id): int {
        return (int) get_user_meta($user_id, '_topersia_trips_count', true);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function my_reviews(int $user_id): array {
        if (!class_exists('ToPersia_Reviews')) return [];
        return ToPersia_Reviews::instance()->my_reviews($user_id);
    }

    public function shortcode(): string {
        try {
            return $this->render_dashboard();
        } catch (\Throwable $e) {
            /* error_log روی این هاست غیرفعال است و خودش خطای مرگبار می‌دهد.
               لاگ هسته جایش را گرفت. */
            if (function_exists('topersia_log')) {
                topersia_log('خطای پنل کاربری: ' . $e->getMessage(), 'error', 'user', [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            return '<div class="tp-dashboard-error" role="alert">' . esc_html('خطایی در بارگذاری پنل کاربری رخ داد. لطفاً دوباره تلاش کنید.') . '</div>';
        }
    }

    private function render_dashboard(): string {
        if (!is_user_logged_in()) {
            return do_shortcode('[topersia_login]');
        }

        $user = wp_get_current_user();
        $first_name = (string) get_user_meta($user->ID, 'first_name', true);
        $last_name = (string) get_user_meta($user->ID, 'last_name', true);
        $name = trim($first_name . ' ' . $last_name);
        if ($name === '') {
            /* ۱٫۱۱٫۰ · P5-D354 — هرگز نام ورود */
            $name = class_exists('ToPersia_Privacy') ? ToPersia_Privacy::public_name((string) $user->display_name, (int) $user->ID) : (string) $user->display_name;
        }

        $mobile = (string) get_user_meta($user->ID, '_topersia_mobile', true);
        $country = (string) get_user_meta($user->ID, '_topersia_country', true);
        $language = (string) get_user_meta($user->ID, '_topersia_language', true);
        if ($language === '') $language = 'fa';

        $travel_style = (string) get_user_meta($user->ID, '_topersia_travel_style', true);
        $avatar = get_avatar_url($user->ID, ['size' => 96]);

        $saved_entities = class_exists('ToPersia_JetEngine')
            ? ToPersia_JetEngine::instance()->saved_ids($user->ID)
            : [];

        /* دنبال‌شده‌ها، هر کدام با آخرین خبرهایش.  ۱٫۱۰٫۰ · P5-D238 */
        $following = class_exists('ToPersia_Follow') ? ToPersia_Follow::items($user->ID, 3) : [];

        $my_reviews = $this->my_reviews($user->ID);
        $reviews_replies_count = 0;
        foreach ($my_reviews as $review) {
            $reviews_replies_count += count($review['replies']);
        }



        $language_code = class_exists('ToPersia_I18n') ? ToPersia_I18n::current_language() : 'fa';
        $direction = class_exists('ToPersia_I18n') ? ToPersia_I18n::direction() : 'rtl';

        $tabs = class_exists('ToPersia_Admin') && method_exists('ToPersia_Admin', 'tabs') ? ToPersia_Admin::tabs() : $this->default_tabs_fallback();
        $tabs = array_filter($tabs, static fn($tab) => !empty($tab['enabled']));
        $default_tab = array_key_first($tabs) ?: 'overview';

        $data = [
            'name'              => $name,
            'avatar'            => $avatar,
            'mobile'            => $mobile,
            /* ایمیل ساختگیِ حساب پیامکی نمایش داده نمی‌شود  ۱٫۱۱٫۰ */
            'email'             => (class_exists('ToPersia_Privacy') && ToPersia_Privacy::synthetic_email((string) $user->user_email)) ? '' : $user->user_email,
            'country'           => $country,
            'language'          => $language,
            'travel_style'      => $travel_style,
            'saved_count'       => count($saved_entities),
            'trips_count'       => $this->count_trips($user->ID),
            'reviews_count'     => count($my_reviews),
            'reviews_items'     => $my_reviews,
            'reviews_replies_count' => $reviews_replies_count,
            'notification_count'=> 0,
            'saved_entities'    => $saved_entities,
            'following_items'   => $following,
            'following_count'   => count($following),
            'language_code'     => $language_code,
            'direction'         => $direction,
            'tabs'              => $tabs,
            'default_tab'       => $default_tab,
        ];

        /* قالب این سه را لازم دارد و هیچ‌جا تعریف نشده بودند؛ $tp که یک
           متغیر تعریف‌نشده بود، هنگام فراخوانی خطای Throwable می‌داد و کل
           پنل به پیام «خطایی رخ داد» می‌افتاد.

           $tp عمداً خروجی را فرار می‌دهد: قالب همه‌جا <?php echo $tp(...) ?>
           می‌نویسد، پس فرار در همین یک نقطه، آن سینک باز را هم می‌بندد. */
        $tp_lang = $language_code;
        $tp_dir  = $direction;

        $tp = static function (string $key, ?string $default = null): string {
            if (class_exists('ToPersia_I18n')) {
                return esc_html(ToPersia_I18n::t($key, $default));
            }
            return esc_html($default ?? $key);
        };

        ob_start();
        require TOPERSIA_USER_CORE_DIR . 'templates/dashboard.php';
        return (string) ob_get_clean();
    }
}
