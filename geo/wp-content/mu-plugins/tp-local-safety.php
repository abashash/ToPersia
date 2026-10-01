<?php
/**
 * فقط نسخهٔ محلیِ topersia.local — هیچ ایمیلی بیرون نمی‌رود.  ۴٫۲٫۲
 *
 * `WP_HTTP_BLOCK_EXTERNAL` فقط HTTP APIِ وردپرس را می‌بندد؛ ایمیل از راه
 * `mail()` یا SMTP می‌رود و آن را نمی‌گیرد. این فیلتر `wp_mail` را پیش از
 * هر ارسالی قطع می‌کند. روی سایتِ زنده نیست و نباید باشد.
 */
if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'local' === WP_ENVIRONMENT_TYPE ) {
	add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
}
