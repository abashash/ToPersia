<?php
defined('ABSPATH') || exit;

/**
 * همگام‌سازی «نمایش و خوانایی» با حساب کاربری.
 *
 * مکانیزم اصلیِ اعمال (localStorage + صفت روی <html>) دست‌نخورده در
 * افزونۀ «هستۀ نمایش» می‌ماند — طبق تصمیم آن پروژه (P5-D151)، هیچ HTML
 * سروری هرگز به‌خاطر ترجیح یک کاربر عوض نمی‌شود، حتی اینجا.
 *
 * این کلاس فقط یک لایۀ همگام‌سازیِ اضافه است، فقط برای کاربر واردشده —
 * روی همان هفت کنترلی که پنل «نمایش و خوانایی» سربرگ سایت دارد: حالت
 * رنگ، اندازۀ متن، فاصلۀ متن، پهنای ستون متن، کنتراست بالا، کاهش شدت
 * رنگ و نور، توقف حرکت و پخش خودکار.
 *
 *  - هر بار کاربر یکی از این سه را عوض می‌کند (هستۀ نمایش رویداد
 *    `topersia:display-changed` را در مرورگر پخش می‌کند)، اینجا با AJAX
 *    در متای کاربر ذخیره می‌شود.
 *  - در بازدید بعدی — حتی از یک دستگاه دیگر — این کلاس مقدار ذخیره‌شده
 *    را فقط برای کاربر واردشده به‌صورت یک اسکریپت کوچک محلی‌سازی‌شده
 *    می‌دهد؛ جاوااسکریپت سمت کلاینت همان مقدار را در localStorage همان
 *    دستگاه می‌نویسد و همان تابع apply() موجود را صدا می‌زند.
 *
 * چون خروجی فقط برای کاربر واردشده فرق می‌کند — نه بازدیدکنندۀ ناشناس —
 * و صفحات واردشده معمولاً از کش تمام‌صفحه کنار گذاشته می‌شوند، این با
 * همان روح P5-D151 (کش سراسری دست‌نخورده) سازگار است.
 */
final class ToPersia_Display_Prefs {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private const META_KEY = '_topersia_display_prefs';

    /** همان کلیدها و همان مقادیر مجاز خودِ هستۀ نمایش — عیناً از
     *  topersia-render.php کپی شده تا هیچ گزینه‌ای از قلم نیفتد. */
    private const ALLOWED = [
        'mode'     => ['auto', 'light', 'evening', 'dark'],
        'scale'    => ['1', '1.15', '1.3', '1.5'],
        'spacing'  => ['compact', 'normal', 'loose'],
        'measure'  => ['narrow', 'standard'],
        'contrast' => ['high', ''],
        'calm'     => ['on', ''],
        'motion'   => ['off', ''],
    ];

    private function __construct() {
        add_action('wp_ajax_topersia_save_display_prefs', [$this, 'save']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 30);
        add_action('wp_footer', [$this, 'print_prefs'], 5);
    }

    /**
     * این دارایی باید در هر صفحه‌ای که کاربر واردشده می‌بیند بار شود —
     * نه فقط صفحۀ حساب — چون شخصی‌سازی نمایش سراسری است. برای همین از
     * منطق محدودکنندۀ ToPersia_Assets::should_load() جدا نگه داشته شده.
     */
    public function enqueue(): void {
        if (!is_user_logged_in()) return;

        wp_enqueue_script(
            'topersia-display-sync',
            TOPERSIA_USER_CORE_URL . 'assets/js/display-sync.js',
            [],
            TOPERSIA_USER_CORE_VERSION,
            true
        );

        wp_localize_script('topersia-display-sync', 'ToPersiaDisplaySync', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('topersia_user_core'),
        ]);
    }

    /**
     * مقدار ذخیره‌شدۀ کاربر را به‌صورت یک متغیر جاوااسکریپت کوچک چاپ
     * می‌کند — فقط برای کاربر واردشده، هیچ‌وقت برای بازدیدکنندۀ ناشناس.
     */
    public function print_prefs(): void {
        if (!is_user_logged_in()) return;

        $prefs = $this->get_prefs(get_current_user_id());
        if (!$prefs) return;

        echo '<script>window.ToPersiaDisplayPrefs = ' . wp_json_encode($prefs) . ';</script>' . "\n"; // phpcs:ignore
    }

    /**
     * @return array<string,string>
     */
    private function get_prefs(int $user_id): array {
        $raw = get_user_meta($user_id, self::META_KEY, true);
        return is_array($raw) ? $raw : [];
    }

    /**
     * خلاصۀ خوانا از ترجیحات ذخیره‌شده — برای نمایش در تب تنظیمات پنل
     * کاربری. اگر چیزی هنوز ذخیره نشده، رشتۀ خالی برمی‌گرداند.
     */
    public function summary(int $user_id): string {
        $p = $this->get_prefs($user_id);
        if (!$p) return '';

        $parts = [];

        $modes = ['auto' => 'خودکار', 'light' => 'روز', 'evening' => 'بعدازظهر', 'dark' => 'شب'];
        if (!empty($p['mode']) && isset($modes[$p['mode']])) {
            $parts[] = 'حالت رنگ: ' . $modes[$p['mode']];
        }

        $scales = ['1' => '۱۰۰٪', '1.15' => '۱۱۵٪', '1.3' => '۱۳۰٪', '1.5' => '۱۵۰٪'];
        if (!empty($p['scale']) && isset($scales[$p['scale']])) {
            $parts[] = 'اندازۀ متن: ' . $scales[$p['scale']];
        }

        if (!empty($p['contrast'])) {
            $parts[] = 'کنتراست بالا: فعال';
        }

        $spacings = ['compact' => 'فشرده', 'normal' => 'استاندارد', 'loose' => 'باز'];
        if (!empty($p['spacing']) && isset($spacings[$p['spacing']])) {
            $parts[] = 'فاصلۀ متن: ' . $spacings[$p['spacing']];
        }

        $measures = ['narrow' => 'باریک', 'standard' => 'استاندارد'];
        if (!empty($p['measure']) && isset($measures[$p['measure']])) {
            $parts[] = 'پهنای ستون متن: ' . $measures[$p['measure']];
        }

        if (!empty($p['calm'])) {
            $parts[] = 'کاهش شدت رنگ و نور: فعال';
        }

        if (!empty($p['motion'])) {
            $parts[] = 'توقف حرکت: فعال';
        }

        return $parts ? implode(' · ', $parts) : '';
    }

    public function save(): void {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!is_user_logged_in() || !wp_verify_nonce($nonce, 'topersia_user_core')) {
            wp_send_json_error(['message' => ToPersia_I18n::t('invalid_request', 'درخواست نامعتبر است.')], 403);
        }

        $key = sanitize_key(wp_unslash($_POST['key'] ?? ''));

        // کلید ناشناخته (مثلاً از نسخۀ جدیدتر هستۀ نمایش که این کلاس
        // هنوز نمی‌شناسدش) بی‌سروصدا نادیده گرفته می‌شود — نه خطا.
        if (!isset(self::ALLOWED[$key])) {
            wp_send_json_success(['saved' => false, 'skipped' => true]);
        }

        $val = sanitize_text_field(wp_unslash($_POST['value'] ?? ''));

        if (!in_array($val, self::ALLOWED[$key], true)) {
            wp_send_json_error(['message' => ToPersia_I18n::t('invalid_value', 'مقدار نامعتبر است.')], 400);
        }

        $user_id = get_current_user_id();
        $prefs   = $this->get_prefs($user_id);
        $prefs[$key] = $val;
        update_user_meta($user_id, self::META_KEY, $prefs);

        wp_send_json_success(['saved' => true]);
    }
}
