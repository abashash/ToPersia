<?php
/**
 * P5-D470 (دستهٔ 40) — برق کشور: ولتاژ و بسامد. فقط tp_country، عددی، WPML کپی.
 * ساختار مثل tp_p5d450_fields(): نام ← [ kind, برچسب fa, en, ar, راهنمای مدیر (fa), نوع‌ها, req, واژه‌نامه ].
 * «دوشاخۀ برق» (electric_plug) در P5-D450 است؛ این‌جا دو عدد کنار آن می‌نشینند.
 */
if ( ! function_exists( 'tp_p5d470_fields' ) ) {
	function tp_p5d470_fields() {
		return array(
			'electric_voltage'   => array( 'number', 'ولتاژ برق', 'Mains voltage', 'جهد الكهرباء', 'فقط عدد به ولت (مثلاً ۲۳۰).', array( 'tp_country' ), false ),
			'electric_frequency' => array( 'number', 'بسامد برق', 'Mains frequency', 'تردد الكهرباء', 'فقط عدد به هرتز (مثلاً ۵۰).', array( 'tp_country' ), false ),
		);
	}
}
if ( ! function_exists( 'tp_p5d450_all_fields' ) ) {
	/** فیلدهای P5-D450 به‌علاوهٔ P5-D470 — برای ساخت محلی (tp-local-28.php). */
	function tp_p5d450_all_fields() {
		return tp_p5d450_fields() + tp_p5d470_fields();
	}
}
