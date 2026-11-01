<?php
/**
 * P5-D497 — هشدار «ممکن است قدیمی باشد» از سن last_verified_date، بی فیلد تازه.
 * از assert.php بار می‌شود؛ PHP خالص با stub.
 */

echo "\nP5-D497 — هشدار قدیمی‌بودن\n" . str_repeat( '─', 78 ) . "\n";

$keep497 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], isset( $GLOBALS['tp_rel'] ) ? $GLOBALS['tp_rel'] : null, isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : null, isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : null );
$f2_world( $f2_reg );
$GLOBALS['tp2g_status'] = 'active';

require_once TP_PLUGIN_DIR . 'includes/topersia-settings.php';
$now497 = strtotime( '2026-11-01' );
t( 'مرز آستانه: یک روز بیشتر قدیمی، خود آستانه نه؛ آینده، خالی، نامعتبر و آستانهٔ صفر هرگز',
	topersia_g_is_stale( '2026-10-01', 30, $now497 ) && ! topersia_g_is_stale( '2026-10-02', 30, $now497 )
	&& ! topersia_g_is_stale( '2027-01-01', 30, $now497 ) && ! topersia_g_is_stale( '', 30, $now497 )
	&& ! topersia_g_is_stale( 'نامعتبر', 30, $now497 ) && ! topersia_g_is_stale( '2000-01-01', 0, $now497 ) );

$stale497 = 'ممکن است قدیمی باشد';
$f2_post( 8201, 'tp_currency', 'ارز قدیمی' );
$f2_post( 8202, 'tp_currency', 'ارز تازه' );
$f2_post( 8203, 'tp_currency', 'ارز بی تاریخ' );
$f2_post( 8204, 'tp_visa', 'روادید ۶۰ روزه' );
$f2_post( 8205, 'tp_province', 'استان قدیمی' );
$text497 = array( 'content_status' => 'verified', 'price_reading_rule' => 'متن', 'fee_policy_note' => 'متن', 'overview_text' => '<p>متن</p>' );
$day497  = function ( $n ) { return gmdate( 'Y-m-d', time() - $n * 86400 ); };
$GLOBALS['tp_meta'][8201] = $text497 + array( 'last_verified_date' => $day497( 45 ) );
$GLOBALS['tp_meta'][8202] = $text497 + array( 'last_verified_date' => $day497( 5 ) );
$GLOBALS['tp_meta'][8203] = $text497;
$GLOBALS['tp_meta'][8204] = $text497 + array( 'last_verified_date' => $day497( 60 ) );
$GLOBALS['tp_meta'][8205] = $text497 + array( 'last_verified_date' => $day497( 4000 ) );
$old497 = r( 8201, 'tp_overview' );
$ok497  = function ( $h ) use ( $stale497 ) { return 1 === substr_count( $h, '<strong class="tp-verified__stale">' . $stale497 . '</strong>' ) && false !== strpos( $h, '<p class="tp-verified">' ); };
tp441_mut( 'ارز ۴۵ روزه با آستانهٔ پیش‌فرض ۳۰ هشدار را کنار مهر می‌گیرد — جهش: هشدار حذف شود می‌افتد', $ok497, $old497, str_replace( 'tp-verified__stale', 'x', $old497 ) );
t( 'ارز ۵ روزه بی هشدار، با مهر', false === strpos( r( 8202, 'tp_overview' ), $stale497 ) && false !== strpos( r( 8202, 'tp_overview' ), 'tp-verified' ) );
t( 'تاریخ خالی: چیزی نشان داده نمی‌شود', false === strpos( r( 8203, 'tp_overview' ), $stale497 ) );
t( 'آستانهٔ هر نوع جداست: روادید ۶۰ روزه با پیش‌فرض ۹۰ هشدار نمی‌گیرد', false === strpos( r( 8204, 'tp_overview' ), $stale497 ) );
t( 'نوع بی مهر (استان ۴۰۰۰ روزه): هیچ', false === strpos( r( 8205, 'tp_overview' ), $stale497 ) );

/* تنظیمات: قابل‌تغییر و با بازه */
$st497 = topersia_render_settings_sanitize( array( 'stale_days_tp_currency' => 10, 'stale_days_tp_visa' => 99999 ) );
t( 'تنظیمات: آستانه قابل‌تغییر؛ بیرون از بازه به پیش‌فرض برمی‌گردد؛ پیش‌فرض ارز ۳۰ و روادید ۹۰',
	10 === $st497['stale_days_tp_currency'] && 90 === $st497['stale_days_tp_visa'] && 30 === topersia_render_settings_defaults()['stale_days_tp_currency'] && 90 === topersia_render_settings_defaults()['stale_days_tp_visa'] );

/* کاتالوگ */
$cat497 = include __DIR__ . '/../scripts/i18n/topersia-render.php';
t( 'سه‌زبانه: متن هشدار در کاتالوگ انگلیسی و عربی', ! empty( $cat497[ $stale497 ]['en'] ) && ! empty( $cat497[ $stale497 ]['ar'] ) && ! preg_match( '/[پچژگکی]/u', $cat497[ $stale497 ]['ar'] ) );

$GLOBALS['tp_posts'] = $keep497[0]; $GLOBALS['tp_meta'] = $keep497[1];
if ( null === $keep497[2] ) { unset( $GLOBALS['tp_rel'] ); } else { $GLOBALS['tp_rel'] = $keep497[2]; }
if ( null === $keep497[3] ) { unset( $GLOBALS['tp_filters'] ); } else { $GLOBALS['tp_filters'] = $keep497[3]; }
if ( null === $keep497[4] ) { unset( $GLOBALS['tp2g_status'] ); } else { $GLOBALS['tp2g_status'] = $keep497[4]; }
