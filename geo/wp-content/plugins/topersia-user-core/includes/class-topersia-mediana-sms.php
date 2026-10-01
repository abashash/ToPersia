<?php
defined('ABSPATH') || exit;

final class ToPersia_Mediana_SMS implements ToPersia_SMS_Provider {
    public function send_otp(string $recipient, string $otp, string $pattern_code): array {
        $api_key = (string) get_option('topersia_mediana_api_key', '');
        if ($api_key === '' || $pattern_code === '') {
            return ['success' => false, 'message' => (class_exists('ToPersia_I18n') ? ToPersia_I18n::t('sms_not_configured', 'اتصال Mediana تنظیم نشده است.') : 'اتصال Mediana تنظیم نشده است.'), 'request_id' => null];
        }

        $response = wp_remote_post('https://api.mediana.ir/sms/v1/send/otp', [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-API-KEY' => $api_key,
            ],
            'body' => wp_json_encode([
                'patternCode' => $pattern_code,
                'recipient' => $recipient,
                'otpCode' => $otp,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => (class_exists('ToPersia_I18n') ? ToPersia_I18n::t('sms_unreachable', 'ارتباط با سرویس پیامک برقرار نشد.') : 'ارتباط با سرویس پیامک برقرار نشد.'), 'request_id' => null];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        $ok = $status >= 200 && $status < 300 && !empty($data['data']['Succeed']);
        $message = isset($data['meta']['errorMessage']) ? sanitize_text_field((string) $data['meta']['errorMessage']) : '';

        if (!$ok) {
            return [
                'success' => false,
                'message' => $message !== '' ? $message : (class_exists('ToPersia_I18n') ? ToPersia_I18n::t('otp_send_failed', 'ارسال کد تأیید ناموفق بود.') : 'ارسال کد تأیید ناموفق بود.'),
                'request_id' => $data['data']['RequestId'] ?? null,
            ];
        }

        return [
            'success' => true,
            'message' => (class_exists('ToPersia_I18n') ? ToPersia_I18n::t('otp_sent', 'کد تأیید ارسال شد.') : 'کد تأیید ارسال شد.'),
            'request_id' => $data['data']['RequestId'] ?? null,
        ];
    }
}
