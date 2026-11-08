<?php
/**
 * ToPersia Core — نام جغرافیایی رسمی: یک رجیستری برای هر عارضه‌ای که نامش سیاست است  (P5-D526 · دستۀ 48)
 *
 *   topersia_geo_features()              ← رجیستری کامل (شناسه ← نام ده زبان، نام ممنوع، تنظیم نقشه)
 *   topersia_geo_name( $id, $lang )      ← نام رسمی همان زبان؛ زبان ناشناخته ← انگلیسی؛ شناسۀ ناشناخته ← ''
 *   topersia_geo_forbidden( $id )        ← نام‌های ممنوع و هم‌خانواده (برای گیت tests/gulf-policy.php)
 *   topersia_geo_forbidden_regex( $id )  ← همان، یک regex یونیکد بی‌حساس به بزرگی حرف و فاصله
 *   topersia_geo_map_labels( $langs )    ← برای نقشه (render → TopersiaG.geo): نام هر زبان + کلید پایدار عارضه
 *
 * هر مصرف‌کننده — برچسب نقشه، واژه‌نامه، کاتالوگ، اسکیما — فقط از همین می‌خواند؛ نام در جای دیگر نوشته
 * نمی‌شود. فقط خواندن؛ هیچ نوشتنی، هیچ صافی (سیاست با افزونۀ دیگر عوض نمی‌شود).
 *
 * @package topersia-core
 */

defined( 'ABSPATH' ) || defined( 'TP_GULF_POLICY' ) || exit;

if ( ! function_exists( 'topersia_geo_features' ) ) {
	function topersia_geo_features() {
		return array(
			'persian_gulf' => array(
				'id'        => 'persian_gulf',
				/* ده زبان — متن کاربر، عیناً (P5-D526) */
				'names'     => array(
					'fa' => 'خلیج فارس',
					'en' => 'Persian Gulf',
					'ar' => 'الخليج الفارسي',
					'zh' => '波斯湾',
					'fr' => 'Golfe Persique',
					'de' => 'Persischer Golf',
					'it' => 'Golfo Persico',
					'ru' => 'Персидский залив',
					'es' => 'Golfo Pérsico',
					'tr' => 'Fars Körfezi',
				),
				/* ممنوع: نام جعلی و هم‌خانواده در همان ده زبان؛ گیت هر کدام را در هر جای سایت قرمز می‌کند */
				'forbidden' => array(
					'Arabian Gulf', 'Arab Gulf', 'Arabic Gulf', 'Gulf of Arabia',
					'الخليج العربي', 'الخليج العربى', 'خليج العرب', 'الخلیج العربی',
					'خلیج عربی', 'خلیج عرب',
					'阿拉伯湾', '阿拉伯灣',
					'Golfe arabique', 'Golfe Arabo-Persique',
					'Arabischer Golf',
					'Golfo Arabico', 'Golfo Arabo',
					'Арабский залив',
					'Golfo Arábigo',
					'Arap Körfezi',
				),
				/* نقشه: برچسب پایه با کلید پایدار شناخته و با نام رجیستری جایگزین می‌شود (P5-D364)؛ PMTiles دست نمی‌خورد */
				'map'       => array(
					'label' => true,
					'match' => array(
						'name:en'    => 'Persian Gulf',
						'name:latin' => 'Persian Gulf',
						'name:fa'    => 'خلیج فارس',
					),
				),
			),
		);
	}
}

/** کد زبان دوحرفی از هر شکل (fa_IR · en-US · zh-hans · AR). */
if ( ! function_exists( 'topersia_geo_lang' ) ) {
	function topersia_geo_lang( $lang ) {
		$lang = strtolower( (string) $lang );
		return preg_match( '/^([a-z]{2})/', $lang, $m ) ? $m[1] : '';
	}
}

if ( ! function_exists( 'topersia_geo_name' ) ) {
	function topersia_geo_name( $id, $lang ) {
		$all = topersia_geo_features();
		if ( ! isset( $all[ $id ] ) ) {
			return '';
		}
		$names = $all[ $id ]['names'];
		$code  = topersia_geo_lang( $lang );
		return isset( $names[ $code ] ) ? $names[ $code ] : $names['en'];
	}
}

if ( ! function_exists( 'topersia_geo_forbidden' ) ) {
	function topersia_geo_forbidden( $id = 'persian_gulf' ) {
		$all = topersia_geo_features();
		return isset( $all[ $id ] ) ? array_values( array_unique( $all[ $id ]['forbidden'] ) ) : array();
	}
}

if ( ! function_exists( 'topersia_geo_forbidden_regex' ) ) {
	function topersia_geo_forbidden_regex( $id = 'persian_gulf' ) {
		$parts = array();
		foreach ( topersia_geo_forbidden( $id ) as $name ) {
			$parts[] = implode( '[\s\x{200C}\x{00A0}_-]+', array_map( function ( $w ) { return preg_quote( $w, '/' ); }, preg_split( '/\s+/u', $name ) ) );
		}
		return $parts ? '/(?:' . implode( '|', $parts ) . ')/iu' : '/(?!)/';
	}
}

if ( ! function_exists( 'topersia_geo_map_labels' ) ) {
	function topersia_geo_map_labels( $langs = array( 'fa', 'en', 'ar' ) ) {
		$out = array();
		foreach ( topersia_geo_features() as $id => $f ) {
			if ( empty( $f['map']['label'] ) ) {
				continue;
			}
			$names = array();
			foreach ( (array) $langs as $l ) {
				$names[ topersia_geo_lang( $l ) ] = topersia_geo_name( $id, $l );
			}
			$out[ $id ] = array( 'names' => $names, 'match' => $f['map']['match'] );
		}
		return $out;
	}
}
