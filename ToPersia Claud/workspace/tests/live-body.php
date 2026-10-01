<?php
/**
 * کلاس‌های `<body>` از سورس زندهٔ سایت.  P5-D206
 *
 * چرا اهمیت دارد: بخش بزرگی از `topersia-tokens.css` زیر `.tp-shell`
 * زندگی می‌کند. صفحهٔ آزمون این کلاس را نداشت، پس آن لایه در آزمون
 * **خاموش** بود — و ایرادی که تیتر سربرگ را تیره می‌کرد اصلاً دیده
 * نمی‌شد. صفحهٔ آزمونی که کلاس‌های بدنهٔ واقعی را ندارد، صفحهٔ دیگری است.
 *
 * رشته در `reference/live-body-class.txt` نشسته تا مثل CSS‌های بیگانه
 * ردیابی‌پذیر باشد و دستی در دو جا تکرار نشود.
 */

if ( ! function_exists( 'tp_live_body_class' ) ) {

	/**
	 * @param string $root ریشهٔ فضای کاری.
	 * @return string کلاس‌ها، جداشده با فاصله.
	 */
	function tp_live_body_class( $root ) {

		$file = rtrim( $root, '/\\' ) . '/reference/live-body-class.txt';

		if ( ! is_file( $file ) ) { return 'tp-shell'; }

		$out = array();

		foreach ( file( $file ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) { continue; }
			$out[] = $line;
		}

		$cls = trim( implode( ' ', $out ) );

		return '' === $cls ? 'tp-shell' : $cls;
	}
}
