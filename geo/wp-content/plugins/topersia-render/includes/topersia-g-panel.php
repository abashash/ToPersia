<?php
/**
 * ثبت‌نامۀ واحد «پنل چپ» — دستۀ ۱ پرامت یکپارچه · P5-D347
 *
 * واژه‌نامه (در کد، سند و گزارش): «پنل چپ» = ستون کناری که نقشه دارد (`.tp-rail`) · «باکس» = هر کارت درونش ·
 * «کاشی» = هر پیوند درون باکس · «ستون راست» = محتوای اصلی (`.tp-main-col`).
 *
 * یک جدول، سه خروجی:
 *   ۱ ترتیب DOM پنل چپ = ترتیب موبایل (ستون‌ها در موبایل `display:contents`‌اند و فقط `order` می‌چیند)
 *   ۲ `order` دسکتاپ — فقط CSS، نسخۀ تکراری از محتوا ساخته نمی‌شود
 *   ۳ بلوک CSS هر دو، که مولد قالب (`scripts/tp-template-gen.php`) به قالب می‌افزاید؛ شیوه‌نامه دست‌نویس نیست
 *
 * هیچ باکسی حذف نمی‌شود؛ محتوا، رنگ و سبک عوض نمی‌شود. باکسی که در این جدول نیست (کاشی‌های هم‌جوار مثل نشان‌ها، طبیعت،
 * باستانی، روستاها، اطلاعات سفر، مسیرها، گذرهای مرزی، …) با ترتیب فعلی‌اش درون گروه «باکس‌های کاشی هم‌جوار» می‌نشیند:
 * `TILES` در DOM، و در CSS مقدار پیش‌فرض گروه.
 *
 * ترتیب‌ها: دسکتاپ — دکمه‌ها ← نقشه ← مسیریابی ← [مسیر: شهرهای مسیر] ← بهترین زمان سفر ← هوای این روزها ←
 * کیفیت هوا ← اطلاعات جغرافیایی ← کاشی‌های هم‌جوار ← روایت ← منابع.
 * موبایل — دکمه‌ها همان جای پیشین (JavaScript بالای مسیر راهنما می‌برد)؛ ستون راست کامل؛ سپس پنل چپ:
 * نقشه ← مسیریابی ← [شهرهای مسیر] ← هوای این روزها ← کیفیت هوا ← بهترین زمان سفر ← اطلاعات جغرافیایی ← کاشی‌های هم‌جوار ←
 * روایت ← منابع؛ و «نظر بازدیدکنندگان» (در ستون راست) آخرین.
 *
 * ۱۳ اکتبر · P5-D358 (اصلاح P5-D347): بلوک «این مقصد را در یک نگاه بفهمید» (ToPersia AI) از این ثبت‌نامه بیرون است و به جای پیشین خودش
 * برگشته: ستون راست، بلافاصله بالای «درباره …»، در دسکتاپ و موبایل. بقیۀ ترتیب پنل چپ بی‌تغییر.
 *
 * @package ToPersia_Render
 */

if ( ! defined( 'ABSPATH' ) && 'cli' !== PHP_SAPI ) {
	exit;
}

/** نشانۀ جای گروه کاشی‌های هم‌جوار در ترتیب DOM. */
if ( ! defined( 'TOPERSIA_G_PANEL_TILES' ) ) {
	define( 'TOPERSIA_G_PANEL_TILES', '@tiles' );
}

/**
 * جدول پنل چپ، به ترتیب DOM (= ترتیب موبایل).
 *
 * @return array<int,array{id:string,fn:string,nav:string,m:int|null,d:int}> m = order موبایل (null: دست نمی‌خورد) · d = order دسکتاپ
 */
if ( ! function_exists( 'topersia_g_panel_registry' ) ) {
	function topersia_g_panel_registry() {

		return array(
			array( 'id' => 'tp-actions',      'fn' => 'topersia_g_sc_actionbar', 'nav' => '',          'm' => null, 'd' => 1 ),
			array( 'id' => 'tp-map',          'fn' => 'topersia_g_sc_map',       'nav' => 'نقشه',      'm' => 20,   'd' => 2 ),
			array( 'id' => 'tp-routing',   'fn' => 'topersia_g_sc_routing', 'nav' => '',         'm' => 21,   'd' => 4 ),
			array( 'id' => 'tp-route-cities', 'fn' => 'topersia_g_sc_route_cities', 'nav' => '',       'm' => 22,   'd' => 5 ),
			array( 'id' => 'tp-weather',      'fn' => 'topersia_g_sc_weather',   'nav' => '',          'm' => 23,   'd' => 7 ),
			array( 'id' => 'tp-aqi',          'fn' => 'topersia_g_sc_air',       'nav' => '',          'm' => 24,   'd' => 8 ),
			array( 'id' => 'tp-season',       'fn' => 'topersia_g_sc_seasons',   'nav' => 'زمان سفر',  'm' => 25,   'd' => 6 ),
			array( 'id' => 'tp-geo',          'fn' => 'topersia_g_sc_facts',     'nav' => 'جغرافیا',   'm' => 26,   'd' => 9 ),
			array( 'id' => TOPERSIA_G_PANEL_TILES, 'fn' => '', 'nav' => '', 'm' => 50, 'd' => 11 ),
			array( 'id' => 'tp-audio',        'fn' => 'topersia_g_sc_audio',     'nav' => 'روایت‌ها',  'm' => 80,   'd' => 12 ),
			array( 'id' => 'tp-sources',      'fn' => 'topersia_g_sc_sources',   'nav' => 'منابع',     'm' => 90,   'd' => 13 ),
		);
	}
}

/**
 * کاشی‌های هم‌جوار: «هم‌مرزها» اول، سپس هر باکسی که در جدول بالا نیست با ترتیب فعلی‌اش.
 *
 * `order` موبایل `neighbors` جلوتر از بقیۀ گروه است.
 */
if ( ! function_exists( 'topersia_g_panel_tiles_order' ) ) {
	function topersia_g_panel_tiles_order() {

		return array( 'neighbors' => array( 'm' => 40, 'd' => 10 ) );
	}
}

/**
 * «ستون راست» در موبایل: ترتیب مطالب، پرسش‌ها پیش از پنل چپ، نظرها آخر.
 *
 * @return array<string,int>
 */
if ( ! function_exists( 'topersia_g_panel_main_mobile' ) ) {
	function topersia_g_panel_main_mobile() {

		return array(
			'tp-quick' => 1, 'tp-overview' => 2, 'tp-gallery' => 3, 'tp-attractions' => 4, 'tp-places' => 5, 'tp-culture' => 6, 'tp-news' => 7,
			'tp-faq' => 10, 'tp-reviews' => 99,
		);
	}
}

/**
 * بلوک CSS ترتیب — خروجی ثبت‌نامه برای مولد قالب (پس از بلوک `order` مرجع می‌نشیند و همان وزن را دارد).
 *
 * @return string
 */
if ( ! function_exists( 'topersia_g_panel_css' ) ) {
	function topersia_g_panel_css() {

		$pre = 'html .tp-page.tp-page ';   /* وزن بیشتر از بلوک order مرجع */
		$m   = '';
		$d   = '';

		foreach ( topersia_g_panel_registry() as $row ) {

			if ( TOPERSIA_G_PANEL_TILES === $row['id'] || null === $row['m'] ) {
				if ( null === $row['m'] && TOPERSIA_G_PANEL_TILES !== $row['id'] ) {
					$d .= $pre . '#' . $row['id'] . '{order:' . $row['d'] . '}' . "\n";
				}
				continue;
			}

			$m .= $pre . '#' . $row['id'] . '{order:' . $row['m'] . '}' . "\n";
			$d .= $pre . '#' . $row['id'] . '{order:' . $row['d'] . '}' . "\n";
		}

		foreach ( topersia_g_panel_tiles_order() as $key => $o ) {
			$m .= $pre . '#tp-' . $key . '{order:' . $o['m'] . '}' . "\n";
			$d .= $pre . '#tp-' . $key . '{order:' . $o['d'] . '}' . "\n";
		}

		foreach ( topersia_g_panel_main_mobile() as $id => $o ) {
			$m .= $pre . '#' . $id . '{order:' . $o . '}' . "\n";
		}

		/* باکس‌های کاشیِ مرجع که شناسه‌شان `order` موبایل مرجع داشت (۲۴ تا ۴۴) به پیش‌فرض گروه برمی‌گردند */
		$reset = array( 'tp-badges', 'tp-nature', 'tp-ancient', 'tp-practical', 'tp-routes' );
		$mr    = '';

		foreach ( $reset as $id ) {
			$mr .= $pre . '#' . $id . '{order:50}' . "\n";
		}

		/* توضیح CSS خروجی عمداً لاتین است: رشتۀ فارسی داخل کد را فهرست ترجمه می‌گیرد */
		return "/* === Left panel order from the single registry (includes/topersia-g-panel.php) - P5-D347 ===
"
			. "   Mobile: right column first, then the left panel, visitor reviews last. Desktop: order only, same DOM. */
"
			. "@media(max-width:720px){\n"
			. $pre . ".tp-main-col>*{order:12}\n"
			. $pre . ".tp-rail>*{order:50}\n"
			. $m . $mr
			. "}\n"
			. "@media(min-width:721px){\n"
			. $pre . ".tp-rail>*{order:11}\n"
			. $d
			. "}\n";
	}
}
