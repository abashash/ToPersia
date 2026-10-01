<?php
defined('ABSPATH') || exit;

/**
 * مجموعۀ آیکون‌های SVG درون‌خطی این افزونه — جایگزین Font Awesome.
 *
 * سایت سه سیستم آیکون همزمان داشت: فونت آیکون تم Woodmart، SVGهای
 * درون‌خطی «هستۀ نمایش»، و Font Awesome این افزونه. این کلاس سومی را
 * حذف می‌کند: به‌جای کلاس CSS آزاد (که کاربر می‌توانست هر رشتۀ دلخواهی
 * بنویسد)، فقط یک کلید از یک فهرست بسته انتخاب می‌شود — هم سبک‌تر، هم
 * امن‌تر (چون دیگر نیازی به sanitize کردن یک رشتۀ کلاس دلخواه نیست).
 *
 * هر SVG با stroke="currentColor" است تا رنگش از CSS محیطش (رنگ متن تب)
 * بیاید، نه یک رنگ ثابت.
 */
final class ToPersia_Icons {

    /**
     * کلید => محتوای داخلی SVG (بدون تگ svg بیرونی، تا اندازه/کلاس در
     * زمان چاپ اضافه شود).
     */
    private const PATHS = [
        'home'     => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9a1 1 0 0 0 1 1H9a1 1 0 0 0 1-1v-4h4v4a1 1 0 0 0 1 1h2.5a1 1 0 0 0 1-1v-9"/>',
        'user'     => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.2-4 4-6 7.5-6s6.3 2 7.5 6"/>',
        'bookmark' => '<path d="M6 3.5h12a1 1 0 0 1 1 1V21l-7-4.2L5 21V4.5a1 1 0 0 1 1-1Z"/>',
        'route'    => '<circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="6" r="2.2"/><path d="M6 15.8V13a4 4 0 0 1 4-4h4a4 4 0 0 0 4-4"/>',
        'suitcase' => '<rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5"/><path d="M3 13h18"/>',
        'star'     => '<path d="M12 3.5 14.6 9l6 .8-4.4 4.1 1.1 6-5.3-2.9-5.3 2.9 1.1-6L3.4 9.8l6-.8Z"/>',
        'bell'     => '<path d="M6 10a6 6 0 0 1 12 0v4.5l1.6 2.5H4.4L6 14.5Z"/><path d="M10 19.5a2 2 0 0 0 4 0"/>',
        'cog'      => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2.2M12 18.8V21M4.9 4.9l1.55 1.55M17.55 17.55l1.55 1.55M3 12h2.2M18.8 12H21M4.9 19.1l1.55-1.55M17.55 6.45l1.55-1.55"/>',
        'puzzle'   => '<path d="M9 4h4a1 1 0 0 1 1 1v2.2a1.8 1.8 0 1 0 0 3.6V13a1 1 0 0 1-1 1h-2.2a1.8 1.8 0 1 1-3.6 0H5a1 1 0 0 1-1-1V9a1.8 1.8 0 1 0 0-3.6V4a1 1 0 0 1 1-1h2.2A1.8 1.8 0 0 1 9 4Z"/>',
        'heart'    => '<path d="M12 20.5S3.5 15 3.5 9.2A4.2 4.2 0 0 1 12 7.8a4.2 4.2 0 0 1 8.5 1.4C20.5 15 12 20.5 12 20.5Z"/>',
        'tag'      => '<path d="M11 3.5h6a1 1 0 0 1 1 1v6L9.5 19 4 13.5 11 3.5Z"/><circle cx="15" cy="8" r="1.2"/>',
        'map-pin'  => '<path d="M12 21s7-6.6 7-11.5A7 7 0 0 0 5 9.5C5 14.4 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/>',
        'gift'     => '<rect x="3.5" y="9" width="17" height="11" rx="1.2"/><path d="M3.5 9h17M12 9v11M12 9C9 9 8 6.5 9.3 5c1.2-1.3 2.7.3 2.7 4Zm0 0c3 0 4-2.5 2.7-4-1.2-1.3-2.7.3-2.7 4Z"/>',
        'message'  => '<path d="M4 5.5h16v11H8.5L4 20.5Z"/>',
        'shield'   => '<path d="M12 3.5 19 6v6c0 5-3 7.9-7 8.5-4-.6-7-3.5-7-8.5V6Z"/>',
        'camera'   => '<rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="12" cy="13.5" r="3.3"/><path d="M9 7.5 10.2 5h3.6L15 7.5"/>',
        'mail'     => '<rect x="3.5" y="5.5" width="17" height="13" rx="1.5"/><path d="M4 6.5 12 13l8-6.5"/>',
        'phone'    => '<path d="M6.5 4h3l1.3 4.3-2 1.7a11.5 11.5 0 0 0 5.2 5.2l1.7-2 4.3 1.3v3a1.5 1.5 0 0 1-1.6 1.5A15.5 15.5 0 0 1 5 5.6 1.5 1.5 0 0 1 6.5 4Z"/>',
        'calendar' => '<rect x="3.5" y="5.5" width="17" height="15" rx="1.5"/><path d="M3.5 10h17M8 3.5v3.2M16 3.5v3.2"/>',
        'info'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 8v.1"/>',
        'circle'   => '<circle cx="12" cy="12" r="8.5"/>',
        /* دنبال‌کردن خبر یک مکان — نشانگر مکان با موج خبر.  ۱٫۱۰٫۰ */
        'follow'   => '<path d="M10 20s5.5-5.2 5.5-9A5.5 5.5 0 0 0 4.5 11c0 3.8 5.5 9 5.5 9Z"/><circle cx="10" cy="11" r="1.9"/><path d="M17.5 4.5a6 6 0 0 1 2 4.5M16 7a3 3 0 0 1 1 2"/>',
    ];

    /**
     * برای بالا-آوردن سالم سایت‌هایی که از قبل مقدار قدیمی (کلاس Font
     * Awesome) در `wp_options` ذخیره کرده‌اند — بدون این نگاشت، بعد از
     * آپدیت همۀ آیکون‌های تب‌ها خالی می‌ماندند.
     */
    private const LEGACY_ALIASES = [
        'fas fa-home'          => 'home',
        'fas fa-user'          => 'user',
        'fas fa-bookmark'      => 'bookmark',
        'fas fa-route'         => 'route',
        'fas fa-suitcase'      => 'suitcase',
        'fas fa-star'          => 'star',
        'fas fa-bell'          => 'bell',
        'fas fa-cog'           => 'cog',
        'fas fa-puzzle-piece'  => 'puzzle',
        'fas fa-heart'         => 'heart',
        'fas fa-tag'           => 'tag',
        'fas fa-map-marker-alt'=> 'map-pin',
        'fas fa-gift'          => 'gift',
        'fas fa-comment'       => 'message',
        'fas fa-shield-alt'    => 'shield',
        'fas fa-camera'        => 'camera',
        'fas fa-envelope'      => 'mail',
        'fas fa-phone'         => 'phone',
        'fas fa-calendar'      => 'calendar',
        'fas fa-info-circle'   => 'info',
        'fas fa-circle'        => 'circle',
    ];

    /**
     * کلید معتبر می‌سازد؛ کلید قدیمی Font Awesome را نگاشت می‌کند؛ هرچیز
     * ناشناخته به 'circle' برمی‌گردد. هرگز رشتۀ خام برنمی‌گرداند.
     */
    public static function normalize(string $key): string {
        $key = trim($key);

        if (isset(self::PATHS[$key])) {
            return $key;
        }

        $key_lower = strtolower($key);
        if (isset(self::LEGACY_ALIASES[$key_lower])) {
            return self::LEGACY_ALIASES[$key_lower];
        }

        return 'circle';
    }

    /**
     * فهرست کلید=>برچسب برای پرکردن یک <select> در پیشخوان.
     *
     * @return array<string,string>
     */
    public static function choices(): array {
        return [
            'home'     => 'خانه',
            'user'     => 'کاربر',
            'bookmark' => 'نشان/ذخیره',
            'route'    => 'مسیر',
            'suitcase' => 'چمدان',
            'star'     => 'ستاره',
            'bell'     => 'زنگ اعلان',
            'cog'      => 'چرخ‌دنده',
            'puzzle'   => 'قطعه پازل',
            'heart'    => 'قلب',
            'tag'      => 'برچسب',
            'map-pin'  => 'پین نقشه',
            'gift'     => 'هدیه',
            'message'  => 'پیام',
            'shield'   => 'سپر',
            'camera'   => 'دوربین',
            'mail'     => 'پاکت نامه',
            'phone'    => 'تلفن',
            'calendar' => 'تقویم',
            'info'     => 'اطلاعات',
            'circle'   => 'دایرۀ ساده',
        ];
    }

    /**
     * SVG کامل و امن برای چاپ مستقیم (بدون نیاز به esc_html، چون خروجی
     * همیشه از یک فهرست ثابت داخلی می‌آید، نه از ورودی آزاد).
     */
    public static function svg(string $key, string $class = 'tp-tab-icon'): string {
        $key = self::normalize($key);
        $body = self::PATHS[$key];
        $class_attr = preg_replace('/[^a-z0-9_\-\s]/i', '', $class);

        return '<svg class="' . $class_attr . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}
