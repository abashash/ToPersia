<?php
/**
 * ردیف‌های رازدار که در بسته نمی‌روند — دستهٔ 37 · P5-D475. با `wp eval-file` اجرا می‌شود؛ فقط می‌خواند.
 *
 *   wp --skip-plugins --skip-themes eval-file scripts/lib/tp-backup-scrub.php <خروجی.json>
 *
 * خروجی: { options: [نام گزینه…], usermeta: [کلید…] } — فقط نام، هرگز مقدار.
 * گزینه: نامش کلید/راز/توکن/گذرواژه/مجوز را می‌گوید و مقدارش خالی نیست، یا مقدارش به الگوی کلید (AIza · sk- ·
 * xi-api-key · کلید خصوصی) می‌خورد. ترنزینت‌ها بیرون‌اند. usermeta: نشست‌ها و گذرواژهٔ برنامه.
 */
global $wpdb;
$name_re = '/api[_-]?key|secret|token|passw|credential|license|private[_-]?key/i';
$val_re  = '/AIza[0-9A-Za-z_-]{35}|(?<![A-Za-z0-9_])sk-(?:proj-)?[A-Za-z0-9_-]{20,}|xi-api-key[\'"]?\s*(?::|=>?|,)\s*[\'"]?[A-Za-z0-9_]{20,}|-----BEGIN (?:[A-Z]+ )*PRIVATE KEY-----/';
$opts    = array();
foreach ( $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name NOT LIKE '\\_transient%' AND option_name NOT LIKE '\\_site\\_transient%'", ARRAY_A ) as $r ) {
	if ( ( '' !== (string) $r['option_value'] && preg_match( $name_re, $r['option_name'] ) ) || preg_match( $val_re, (string) $r['option_value'] ) ) {
		$opts[] = $r['option_name'];
	}
}
sort( $opts );
file_put_contents( (string) $args[0], wp_json_encode( array( 'options' => $opts, 'usermeta' => array( 'session_tokens', '_application_passwords' ) ), JSON_UNESCAPED_UNICODE ) );
echo "ok\n";
