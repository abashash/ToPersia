<?php
defined('ABSPATH') || exit;

final class ToPersia_Profile {
    private static ?self $instance = null;
    public static function instance(): self { return self::$instance ??= new self(); }

    private function __construct() {
        add_action('wp_ajax_topersia_save_profile', [$this, 'save_profile']);
    }

    public function save_profile(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'topersia_user_core')) wp_send_json_error(['message' => ToPersia_I18n::t('invalid_request')], 403);
        if (!is_user_logged_in()) wp_send_json_error(['message' => ToPersia_I18n::t('login_required', 'برای ادامه وارد حساب شوید.')], 401);

        $user_id = get_current_user_id();
        $first_name = sanitize_text_field(wp_unslash($_POST['first_name'] ?? ''));
        $last_name = sanitize_text_field(wp_unslash($_POST['last_name'] ?? ''));
        $language = sanitize_text_field(wp_unslash($_POST['language'] ?? 'fa'));
        $country = sanitize_text_field(wp_unslash($_POST['country'] ?? ''));
        $travel_style = sanitize_text_field(wp_unslash($_POST['travel_style'] ?? ''));

        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, '_topersia_language', in_array($language, ['fa','en','ar'], true) ? $language : 'fa');
        update_user_meta($user_id, '_topersia_country', $country);
        update_user_meta($user_id, '_topersia_travel_style', $travel_style);
        wp_update_user([
            'ID' => $user_id,
            /* ۱٫۱۱٫۰ · P5-D354 — از نام و نام خانوادگی؛ خالی یا شماره‌دار ← نام خنثی */
            'display_name' => class_exists('ToPersia_Privacy')
                ? ToPersia_Privacy::public_name(trim($first_name . ' ' . $last_name), $user_id, 'fa')
                : (trim($first_name . ' ' . $last_name) ?: wp_get_current_user()->display_name),
        ]);

        wp_send_json_success(['message' => ToPersia_I18n::t('success_saved')]);
    }
}
