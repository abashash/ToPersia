<?php
/**
 * منوی سربرگ — آیتم سفارشی بی‌مقصد.  دستۀ 55-ب بند ۴
 *
 * متن کاربر: «والد بی‌مقصد با زیرمنو مجاز است ولی باید دکمۀ قابل دسترس (aria-expanded) باشد نه پیوند «#»».
 * آیتم سفارشی (type=custom) با نشانی «#» یا تهی:
 *   فرزند دارد یا بلوک وودمارت (`_menu_item_block`، زیرمنوی بلوکی)  ← دکمه: role="button" tabindex="0" aria-expanded aria-haspopup، بی href
 *   نه فرزند و نه بلوک                                               ← رندر نمی‌شود (پیوند مرده)
 * والدی که همۀ فرزندانش چنین‌اند پس از برداشتنشان خودش بی فرزند است؛ پس برداشتن تا پایدار شدن تکرار می‌شود.
 * فقط صفحۀ عمومی؛ آیتم صفحه و نوع و واژه نشانی خودش را دارد و تعویض‌گر زبان WPML نوع جدا دارد، پس فقط custom.
 * وودمارت (Mega_Menu_Walker) ویژگی تهی را نمی‌نویسد و href را خودش از نشانی آیتم می‌گذارد؛ پس دکمه با بازنویسی نخستین <a …>
 * خروجی آیتم ساخته می‌شود (walker_nav_menu_start_el)، نه با nav_menu_link_attributes. aria-expanded را assets/topersia-g-nav.js
 * با حال واقعی زیرمنو هم‌گام می‌کند.
 * عنوان en/ar منو از ترجمۀ WPML خود منوست (scripts/tp-main-menu.php)، نه از این پرونده.
 */

if ( ! function_exists( 'topersia_g_nav_is_placeholder' ) ) {
	/** آیا آیتم سفارشیِ بی‌مقصد است: نشانی تهی یا فقط «#». */
	function topersia_g_nav_is_placeholder( $item ) {
		return is_object( $item ) && 'custom' === (string) ( $item->type ?? '' ) && in_array( trim( (string) ( $item->url ?? '' ) ), array( '', '#' ), true );
	}
}

if ( ! function_exists( 'topersia_g_nav_has_block' ) ) {
	/** آیا آیتم زیرمنوی بلوکی وودمارت دارد. */
	function topersia_g_nav_has_block( $item ) {
		return '' !== trim( (string) get_post_meta( (int) $item->ID, '_menu_item_block', true ) );
	}
}

if ( ! function_exists( 'topersia_g_nav_front' ) ) {
	/** فقط صفحۀ عمومی — نه پیشخوان (ویرایشگر منو) و نه REST. */
	function topersia_g_nav_front() {
		return ! ( function_exists( 'is_admin' ) && is_admin() ) && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}

if ( ! function_exists( 'topersia_g_nav_objects' ) ) {
	/**
	 * صافی wp_nav_menu_objects: آیتم بی‌مقصدِ بی فرزند و بی بلوک برداشته می‌شود؛ تا وقتی برداشتنی مانده تکرار.
	 *
	 * @param array $items آیتم‌های مرتب‌شدۀ منو.
	 * @return array
	 */
	function topersia_g_nav_objects( $items ) {
		if ( ! is_array( $items ) || ! topersia_g_nav_front() ) {
			return $items;
		}
		do {
			$before  = count( $items );
			$parents = array();
			foreach ( $items as $it ) {
				$parents[ (int) ( $it->menu_item_parent ?? 0 ) ] = true;
			}
			$items = array_values( array_filter( $items, function ( $it ) use ( $parents ) {
				return ! topersia_g_nav_is_placeholder( $it ) || isset( $parents[ (int) $it->ID ] ) || topersia_g_nav_has_block( $it );
			} ) );
		} while ( count( $items ) < $before );
		return $items;
	}
	add_filter( 'wp_nav_menu_objects', 'topersia_g_nav_objects', 20 );
}

if ( ! function_exists( 'topersia_g_nav_button' ) ) {
	/** نخستین <a …> خروجی را دکمه می‌کند: href برداشته، role/tabindex/aria افزوده؛ بی <a> همان ورودی. */
	function topersia_g_nav_button( $html ) {
		$out = preg_replace_callback( '/<a\b([^>]*)>/', function ( $m ) {
			$attrs = preg_replace( '/\s(?:href|role|tabindex|aria-expanded|aria-haspopup)=(["\'])[^"\']*\1/', '', $m[1] );
			return '<a' . $attrs . ' role="button" tabindex="0" aria-expanded="false" aria-haspopup="true" data-tp-nav-btn>';
		}, (string) $html, 1 );
		return null === $out ? (string) $html : $out;
	}
}

if ( ! function_exists( 'topersia_g_nav_start_el' ) ) {
	/**
	 * صافی walker_nav_menu_start_el: آیتم بی‌مقصدی که پس از topersia_g_nav_objects مانده (پس زیرمنو دارد) دکمه می‌شود.
	 *
	 * @param string $output خروجی آیتم.
	 * @param object $item   آیتم منو.
	 * @return string
	 */
	function topersia_g_nav_start_el( $output, $item ) {
		if ( ! topersia_g_nav_front() || ! topersia_g_nav_is_placeholder( $item ) ) {
			return $output;
		}
		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script( 'topersia-g-nav', topersia_render_url() . 'assets/topersia-g-nav.js', array(), topersia_render_asset_version( 'assets/topersia-g-nav.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
		return topersia_g_nav_button( $output );
	}
	add_filter( 'walker_nav_menu_start_el', 'topersia_g_nav_start_el', 20, 2 );
}
