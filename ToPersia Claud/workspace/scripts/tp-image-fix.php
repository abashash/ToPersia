<?php
/**
 * اصلاح تصویرهای اشتباه و alt — دستهٔ 55-د بند ۵ · P5-D646. فقط topersia.local.
 *
 *   wp eval-file scripts/tp-image-fix.php apply <پشتیبان.json>     (نخست مقدار پیشین را می‌نویسد و بازمی‌خواند، سپس تغییر می‌دهد)
 *   wp eval-file scripts/tp-image-fix.php restore <پشتیبان.json>   (مقدار پیشین را بازمی‌گرداند)
 *   wp eval-file scripts/tp-image-fix.php check
 *
 * الف) جایگاه اشتباه خالی می‌شود، تصویر جایگزین نمی‌نشیند (P5-D646): #1948 و #1945 تصویر ترکیبی تهران (#3762) را به‌عنوان
 * hero_image و _thumbnail_id دارند؛ #204 favicon (#190). site_icon سایت = #190 درست است و دست نمی‌خورد.
 * ب) alt: #86 سه زبان (پیوند ترجمه‌ها #4266 en · #4267 ar)؛ #3762 فقط fa با واژهٔ «تصویرسازی» (فیلد برچسب ماهیت روی پیوست نیست).
 */
if ( 'topersia.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { WP_CLI::error( 'فقط topersia.local' ); }
$mode = (string) ( $args[0] ?? 'check' );
$file = (string) ( $args[1] ?? '' );
$wrong = array( 1948 => 3762, 1945 => 3762, 204 => 190 );   // پست => تصویری که نباید باشد
$keys  = array( 'hero_image', '_thumbnail_id' );
$alts  = array(
	86   => 'میدان نقش جهان اصفهان هنگام غروب؛ گنبد مسجد شیخ لطف‌الله، سردر و گنبد مسجد امام و کاخ عالی‌قاپو گرداگرد حوض و فواره‌ها',
	4266 => 'Naqsh-e Jahan Square in Isfahan at sunset, with the Sheikh Lotfollah Mosque dome, the Imam Mosque portal and dome, and Ali Qapu Palace around the fountain pool',
	4267 => 'ميدان نقش جهان في أصفهان عند الغروب، مع قبة مسجد الشيخ لطف الله وبوابة مسجد الإمام وقبته وقصر عالي قابو حول حوض النوافير',
	3762 => 'تصویر ترکیبی و تصویرسازی‌شده از نمادهای استان تهران: رشته‌کوه البرز و قلهٔ دماوند، برج میلاد و افق شهر، و بناهای تاریخی گنبددار کنار آب',
);
$snap = function () use ( $wrong, $keys, $alts ) {
	$s = array( 'when' => gmdate( 'c' ), 'meta' => array(), 'alt' => array() );
	foreach ( $wrong as $pid => $img ) { foreach ( $keys as $k ) { $s['meta'][ $pid . ':' . $k ] = array( 'exists' => metadata_exists( 'post', $pid, $k ), 'value' => (string) get_post_meta( $pid, $k, true ) ); } }
	foreach ( $alts as $aid => $t ) { $s['alt'][ $aid ] = array( 'exists' => metadata_exists( 'post', $aid, '_wp_attachment_image_alt' ), 'value' => (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) ); }
	return $s;
};
if ( 'check' === $mode ) {
	foreach ( $snap()['meta'] as $k => $v ) { echo "$k = " . ( $v['exists'] ? $v['value'] : '(نیست)' ) . "\n"; }
	foreach ( $snap()['alt'] as $k => $v ) { echo "alt#$k = " . ( $v['exists'] ? $v['value'] : '(نیست)' ) . "\n"; }
	echo 'site_icon=' . get_option( 'site_icon' ) . "\n";
	exit( 0 );
}
if ( '' === $file ) { WP_CLI::error( 'مسیر پشتیبان لازم است' ); }
if ( 'apply' === $mode ) {
	$s = $snap();
	foreach ( $wrong as $pid => $img ) { foreach ( $keys as $k ) { if ( $s['meta'][ $pid . ':' . $k ]['exists'] && (string) $img !== $s['meta'][ $pid . ':' . $k ]['value'] ) { WP_CLI::error( "ناهماهنگی: $pid:$k مقدارش «{$s['meta'][ $pid . ':' . $k ]['value']}» است، نه $img — توقف" ); } } }
	file_put_contents( $file, wp_json_encode( $s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	$back = json_decode( (string) file_get_contents( $file ), true );
	if ( $back['meta'] !== $s['meta'] || $back['alt'] !== $s['alt'] ) { WP_CLI::error( 'پشتیبان خوانده‌شده با دادهٔ زنده یکی نیست — توقف' ); }
	foreach ( $wrong as $pid => $img ) { foreach ( $keys as $k ) { delete_post_meta( $pid, $k ); } clean_post_cache( $pid ); }
	foreach ( $alts as $aid => $t ) { update_post_meta( $aid, '_wp_attachment_image_alt', $t ); }
	WP_CLI::success( 'اعمال شد' );
	exit( 0 );
}
if ( 'restore' === $mode ) {
	$s = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $s ) ) { WP_CLI::error( 'پشتیبان نامعتبر' ); }
	foreach ( $s['meta'] as $pk => $v ) { list( $pid, $k ) = explode( ':', $pk, 2 ); if ( $v['exists'] ) { update_post_meta( (int) $pid, $k, $v['value'] ); } else { delete_post_meta( (int) $pid, $k ); } clean_post_cache( (int) $pid ); }
	foreach ( $s['alt'] as $aid => $v ) { if ( $v['exists'] ) { update_post_meta( (int) $aid, '_wp_attachment_image_alt', $v['value'] ); } else { delete_post_meta( (int) $aid, '_wp_attachment_image_alt' ); } }
	WP_CLI::success( 'بازگردانده شد' );
	exit( 0 );
}
WP_CLI::error( 'حالت: check | apply <json> | restore <json>' );
