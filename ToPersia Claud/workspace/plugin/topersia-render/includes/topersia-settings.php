<?php
/**
 * ToPersia Render — تنظیمات نمایش
 *
 * قلم، شکل، رنگ و وضعیت پل تم. رابطش با همان پالت و همان زبان بصری سایت
 * ساخته شده تا پنل و محصول یکی به نظر برسند.  P5-D160 · P5-D164
 *
 * ثبت رویداد و تشخیص کار هسته است، نه این فایل.
 *
 * @package ToPersia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'topersia_render_settings_all' ) ) {
	return;
}

/* ==========================================================================
 * ۱ — مقادیر
 * ========================================================================== */

/**
 * پیش‌فرض تنظیمات.
 *
 * @return array
 */
if ( ! function_exists( 'topersia_render_settings_defaults' ) ) {
	function topersia_render_settings_defaults() {
		return array(
			'font_name'        => '',
			'font_variable'    => 0,
			'font_woff2'       => '',
			'font_w300'        => '',
			'font_w400'        => '',
			'font_w500'        => '',
			'font_w600'        => '',
			'font_w700'        => '',
			'font_w800'        => '',
			'font_stack'       => 'Vazirmatn, system-ui, sans-serif',
			'base_size'        => 15,
			'radius'           => 10,
			'control_radius'   => 8,
			'border_width'     => 1,
			'control_height'   => 42,
			'container'        => 1222,
			'shadow'           => 'soft',
			'default_mode'     => 'auto',
			'own_typography'   => 1,
			'bridge_woodmart'  => 1,
			'bridge_elementor' => 1,
			/* سقف روزانۀ تولید هوش مصنوعی — ۴٫۳٫۰ بند ۴ · P5-D235 */
			'ai_user_daily'    => 5,   // ۴٫۴٫۳: کمترین مصرف
			'ai_site_daily'    => 100,
		);
	}
}

/**
 * خواندن تنظیمات با اعمال پیش‌فرض‌ها.
 *
 * @return array
 */
if ( ! function_exists( 'topersia_render_settings_all' ) ) {
	function topersia_render_settings_all() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$saved = function_exists( 'get_option' ) ? get_option( 'topersia_render_settings', array() ) : array();

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$cache = array_merge( topersia_render_settings_defaults(), $saved );

		return $cache;
	}
}

/**
 * یک مقدار تنظیم.
 *
 * @param string $key      کلید.
 * @param mixed  $fallback جایگزین.
 * @return mixed
 */
if ( ! function_exists( 'topersia_render_setting' ) ) {
	function topersia_render_setting( $key, $fallback = '' ) {
		$all = topersia_render_settings_all();

		return isset( $all[ $key ] ) ? $all[ $key ] : $fallback;
	}
}

/* ==========================================================================
 * ۲ — ثبت
 * ========================================================================== */

/**
 * منوی ToPersia.
 *
 * @return void
 */
if ( ! function_exists( 'topersia_render_settings_menu' ) ) {
	function topersia_render_settings_menu() {
		if ( ! function_exists( 'add_menu_page' ) ) {
			return;
		}

		/* اگر هسته نصب باشد، زیرمنوی آن می‌شویم تا همۀ افزونه‌های ToPersia
		   زیر یک نام بمانند. وگرنه منوی مستقل خودمان. P5-D166 */
		if ( function_exists( 'topersia_admin_page' ) ) {
			add_submenu_page(
				'topersia',
				esc_html__( 'نمایش', 'topersia' ),
				esc_html__( 'نمایش', 'topersia' ),
				'manage_options',
				'topersia-render',
				'topersia_render_settings_page'
			);

			return;
		}

		add_menu_page(
			esc_html__( 'هستۀ نمایش', 'topersia' ),
			esc_html__( 'هستۀ نمایش', 'topersia' ),
			'manage_options',
			'topersia-render',
			'topersia_render_settings_page',
			'dashicons-visibility',
			3
		);
	}
}

/**
 * ثبت گزینه.
 *
 * @return void
 */
if ( ! function_exists( 'topersia_render_settings_register' ) ) {
	function topersia_render_settings_register() {
		if ( ! function_exists( 'register_setting' ) ) {
			return;
		}

		register_setting(
			'topersia_render_group',
			'topersia_render_settings',
			array( 'sanitize_callback' => 'topersia_render_settings_sanitize' )
		);
	}
}

/**
 * پاک‌سازی ورودی.
 *
 * @param array $input ورودی خام.
 * @return array
 */
if ( ! function_exists( 'topersia_render_settings_sanitize' ) ) {
	function topersia_render_settings_sanitize( $input ) {
		$clean = topersia_render_settings_defaults();

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		foreach ( array( 'font_name', 'font_stack' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = function_exists( 'sanitize_text_field' )
					? sanitize_text_field( $input[ $key ] )
					: strip_tags( (string) $input[ $key ] );
			}
		}

		$urls = array( 'font_woff2', 'font_w300', 'font_w400', 'font_w500',
			'font_w600', 'font_w700', 'font_w800' );

		foreach ( $urls as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = function_exists( 'esc_url_raw' )
					? esc_url_raw( $input[ $key ] )
					: filter_var( $input[ $key ], FILTER_SANITIZE_URL );
			}
		}

		/* هر عدد بازۀ خودش را دارد؛ بیرون از بازه به پیش‌فرض برمی‌گردد. */
		$ranges = array(
			'base_size'      => array( 14, 22, 15 ),
			'radius'         => array( 0, 28, 10 ),
			'control_radius' => array( 0, 28, 8 ),
			'border_width'   => array( 0, 4, 1 ),
			'control_height' => array( 32, 60, 42 ),
			'container'      => array( 960, 1600, 1222 ),
			/* صفر یعنی بسته؛ بیرون از بازه به پیش‌فرض برمی‌گردد.  P5-D235 */
			'ai_user_daily'  => array( 0, 1000, 5 ),
			'ai_site_daily'  => array( 0, 100000, 100 ),
		);

		foreach ( $ranges as $key => $r ) {
			$n             = isset( $input[ $key ] ) ? (int) $input[ $key ] : $r[2];
			$clean[ $key ] = ( $n >= $r[0] && $n <= $r[1] ) ? $n : $r[2];
		}

		$shadow          = isset( $input['shadow'] ) ? (string) $input['shadow'] : 'soft';
		$clean['shadow'] = in_array( $shadow, array( 'none', 'soft', 'deep' ), true ) ? $shadow : 'soft';

		$mode                  = isset( $input['default_mode'] ) ? (string) $input['default_mode'] : 'auto';
		$clean['default_mode'] = in_array( $mode, array( 'auto', 'light', 'evening', 'dark' ), true ) ? $mode : 'auto';

		foreach ( array( 'font_variable', 'bridge_woodmart', 'bridge_elementor', 'own_typography' ) as $flag ) {
			$clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( 'topersia_render_bridge_audit' );
		}

		return $clean;
	}
}

/* ==========================================================================
 * ۳ — ممیزی پل
 * ========================================================================== */

/**
 * آیا تم هنوز متغیرهای نگاشت‌شده را مصرف می‌کند؟
 *
 * هر به‌روزرسانی تم می‌تواند نام یک متغیر را عوض کند و پل بی‌صدا بمیرد.
 * این تابع نام‌ها را از خود فایل پل بیرون می‌کشد، پس هر نگاشت تازه‌ای هم
 * خودکار زیر ممیزی می‌رود.
 *
 * نسخۀ اول فایل‌ها را در یک رشته جمع می‌کرد و سر هشتادمین فایل می‌ایستاد؛
 * نتیجه‌اش شش هشدار دروغین بود، چون header-base.css و wp-blocks.css بیرون
 * از آن هشتاد تا می‌افتادند. حالا هر فایل جدا خوانده و رها می‌شود و جست‌وجو
 * وقتی تمام می‌شود که هیچ نام پیدانشده‌ای نمانده باشد.
 *
 * @param bool $fresh نادیده‌گرفتن کش.
 * @return array
 */
if ( ! function_exists( 'topersia_render_bridge_audit' ) ) {
	function topersia_render_bridge_audit( $fresh = false ) {

		$key = 'topersia_render_bridge_audit';

		if ( ! $fresh && function_exists( 'get_transient' ) ) {
			$cached = get_transient( $key );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$result = array( 'mapped' => 0, 'alive' => 0, 'dead' => array(), 'files' => 0, 'error' => '' );

		$bridge = topersia_render_path() . 'assets/topersia-theme-bridge.css';

		if ( ! @file_exists( $bridge ) ) {
			$result['error'] = 'فایل پل پیدا نشد.';
			return $result;
		}

		$css = (string) @file_get_contents( $bridge );

		/* فقط نام‌هایی که ما اعلام می‌کنیم، نه آن‌هایی که مصرف می‌کنیم. */
		preg_match_all( '/(--(?:wd|btn|e-global)-[a-z0-9-]+)\s*:/', $css, $m );

		$names = array_values( array_unique( isset( $m[1] ) ? $m[1] : array() ) );

		$result['mapped'] = count( $names );

		if ( ! $names ) {
			$result['error'] = 'هیچ نگاشتی در فایل پل نیست.';
			return $result;
		}

		/* المنتور فایلش را در زمان اجرا می‌سازد و روی دیسک نیست. */
		$pending = array();

		foreach ( $names as $name ) {
			if ( 0 === strpos( $name, '--e-global' ) ) {
				$result['alive']++;
			} else {
				$pending[ $name ] = true;
			}
		}

		$files = array();

		if ( function_exists( 'get_template_directory' ) ) {
			$root = get_template_directory();

			foreach ( array( '/css/parts/*.css', '/css/*.css', '/*.css' ) as $pattern ) {
				$found = @glob( $root . $pattern );

				if ( is_array( $found ) ) {
					$files = array_merge( $files, $found );
				}
			}
		}

		if ( ! $files ) {
			$result['error'] = 'CSS تم پیدا نشد.';
			return $result;
		}

		foreach ( $files as $file ) {

			if ( ! $pending ) {
				break;
			}

			$body = (string) @file_get_contents( $file );
			$result['files']++;

			if ( '' === $body ) {
				continue;
			}

			foreach ( array_keys( $pending ) as $name ) {
				if ( false !== strpos( $body, 'var(' . $name ) ) {
					unset( $pending[ $name ] );
					$result['alive']++;
				}
			}
		}

		$result['dead'] = array_keys( $pending );

		if ( function_exists( 'set_transient' ) ) {
			set_transient( $key, $result, 12 * HOUR_IN_SECONDS );
		}

		return $result;
	}
}

/* ==========================================================================
 * ۴ — رابط
 * ========================================================================== */

/**
 * شیوه‌نامۀ پنل.
 *
 * از همان پالت حالت روز و همان زبان بصری سربرگ سایت — نوار زبانه‌ها همان
 * کلید سه‌حالتۀ رنگ است، با همان شکل. پنل و محصول باید یکی به نظر برسند.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_settings_css' ) ) {
	function topersia_render_settings_css() {
		/* منبع واحد در هسته است؛ اینجا فقط وقتی تکرار می‌شود که هسته نباشد. */
	if ( function_exists( 'topersia_admin_css' ) ) {
		return topersia_admin_css();
	}

	return '
.tp-admin{
  --c-bg:#F4EFE6; --c-card:#FAF7F1; --c-line:#DDD5C8;
  --c-ink:#2A2A28; --c-ink-2:#6F6B64;
  --c-teal:#177E7A; --c-teal-deep:#0F6461; --c-amber:#B9871A; --c-red:#73292C;
  --c-radius:12px;
  max-inline-size:1180px; color:var(--c-ink); font-size:14px; line-height:1.9;
}
.tp-admin *{box-sizing:border-box}
.tp-admin__head{display:flex; align-items:flex-end; gap:1rem; flex-wrap:wrap; margin-block:1rem .4rem}
.tp-admin__head h1{margin:0; font-size:1.6rem; font-weight:800; color:var(--c-ink)}
.tp-admin__head p{margin:0; color:var(--c-ink-2); max-inline-size:56ch}

/* نوار زبانه — همان شکل کلید حالت رنگ در سربرگ سایت */
.tp-tabs{display:inline-flex; gap:2px; padding:3px; margin-block:1.1rem .9rem;
  background:var(--c-bg); border:1px solid var(--c-line); border-radius:999px}
.tp-tabs button{font:inherit; font-weight:700; font-size:13.5px; cursor:pointer;
  padding:.42rem 1.05rem; border:0; border-radius:999px; background:transparent; color:var(--c-ink-2)}
.tp-tabs button:hover{color:var(--c-teal)}
.tp-tabs button[aria-selected="true"]{background:var(--c-teal); color:#fff}
.tp-tabs button:focus-visible{outline:3px solid var(--c-teal); outline-offset:2px}

.tp-panel[hidden]{display:none}
.tp-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:1.1rem; align-items:start}

.tp-card{background:var(--c-card); border:1px solid var(--c-line); border-radius:var(--c-radius);
  padding:1.1rem 1.2rem 1.2rem}
.tp-card h2{margin:0 0 .2rem; font-size:.95rem; font-weight:800; color:var(--c-ink)}
.tp-card p.hint{margin:0 0 .9rem; font-size:12.5px; color:var(--c-ink-2)}
.tp-card--wide{grid-column:1/-1}

.tp-field{display:grid; grid-template-columns:1fr auto; gap:.35rem .8rem;
  align-items:center; padding-block:.55rem; border-block-start:1px solid var(--c-line)}
.tp-field:first-of-type{border-block-start:0}
.tp-field label{font-weight:600}
.tp-field .note{grid-column:1/-1; margin:0; font-size:12px; color:var(--c-ink-2)}
.tp-field--stack{grid-template-columns:1fr}

.tp-admin input[type="text"],.tp-admin input[type="url"],.tp-admin input[type="number"],.tp-admin select{
  font:inherit; padding:.35rem .6rem; border:1px solid var(--c-line); border-radius:8px;
  background:#fff; color:var(--c-ink); max-inline-size:100%}
.tp-admin input[type="url"],.tp-admin input[type="text"]{inline-size:100%; direction:ltr}
.tp-admin input[type="number"]{inline-size:5.5rem; text-align:center}
.tp-admin input:focus{border-color:var(--c-teal); outline:2px solid rgba(23,126,122,.25); outline-offset:0}
.tp-admin label.tp-check{display:flex; gap:.5rem; align-items:flex-start; font-weight:600; padding-block:.35rem}
.tp-admin label.tp-check span{font-weight:400; color:var(--c-ink-2); font-size:12.5px; display:block}

/* وضعیت پل */
.tp-status{display:flex; gap:.6rem; align-items:center; flex-wrap:wrap; margin-block-end:.7rem}
.tp-pill{display:inline-flex; align-items:center; gap:.4rem; padding:.28rem .8rem;
  border-radius:999px; font-weight:700; font-size:12.5px; border:1.5px solid currentColor}
.tp-pill--ok{color:#3F7C4E} .tp-pill--warn{color:var(--c-amber)} .tp-pill--bad{color:var(--c-red)}
.tp-dead{margin:.4rem 0 0; padding:.6rem .8rem; background:#fff; border:1px dashed var(--c-amber);
  border-radius:8px; direction:ltr; font-family:ui-monospace,monospace; font-size:12px; line-height:2}

/* پیش‌نمایش زنده */
.tp-preview{background:var(--c-card); border:1px solid var(--c-line); border-radius:var(--c-radius); overflow:hidden}
.tp-preview__bar{display:flex; gap:.4rem; padding:.7rem .9rem; border-block-end:1px solid var(--c-line)}
.tp-preview__bar button{font:inherit; font-size:12.5px; font-weight:700; cursor:pointer;
  padding:.25rem .75rem; border-radius:999px; border:1px solid var(--c-line); background:#fff; color:var(--c-ink-2)}
.tp-preview__bar button[aria-pressed="true"]{background:var(--c-teal); border-color:var(--c-teal); color:#fff}
.tp-stage{padding:1.1rem 1.2rem}
.tp-stage h3{margin:0 0 .3rem}
.tp-stage p{margin:0 0 .9rem}
.tp-stage .row{display:flex; gap:.5rem; align-items:center; flex-wrap:wrap}
.tp-swatches{display:flex; gap:6px; margin-block-start:.9rem; flex-wrap:wrap}
.tp-swatches i{inline-size:34px; block-size:22px; border-radius:6px; display:block; border:1px solid rgba(0,0,0,.12)}

.tp-save{position:sticky; inset-block-end:0; margin-block-start:1.2rem; padding:.9rem 0;
  background:linear-gradient(to top, #F4EFE6 65%, rgba(244,239,230,0))}
.tp-admin .button-primary{background:var(--c-teal); border-color:var(--c-teal); box-shadow:none;
  border-radius:999px; padding-inline:1.4rem; height:auto; line-height:2.1}
.tp-admin .button-primary:hover{background:var(--c-teal-deep); border-color:var(--c-teal-deep)}
@media(prefers-reduced-motion:no-preference){.tp-panel{animation:tp-fade .18s ease}}
@keyframes tp-fade{from{opacity:0}to{opacity:1}}
';
	}
}

/**
 * تعریف قلم برای خود پنل.
 *
 * پنل با همان قلمی نوشته می‌شود که همین‌جا تنظیمش می‌کنید — کوتاه‌ترین راه
 * برای دیدن نتیجه.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_settings_font_css' ) ) {
	function topersia_render_settings_font_css() {

		$name = trim( (string) topersia_render_setting( 'font_name', '' ) );

		if ( '' === $name ) {
			return '';
		}

		$safe = str_replace( array( '<', '>', '{', '}', ';', '"', '\\' ), '', $name );
		$css  = '';

		if ( topersia_render_setting( 'font_variable', 0 ) ) {

			$file = trim( (string) topersia_render_setting( 'font_woff2', '' ) );

			if ( '' !== $file ) {
				$css .= '@font-face{font-family:"' . $safe . '";src:url("' . esc_url( topersia_render_font_url( $file ) )
					. '") format("woff2");font-weight:100 900;font-style:normal;font-display:swap;}';
			}
		} else {

			foreach ( array( '300', '400', '500', '600', '700', '800' ) as $weight ) {

				$file = trim( (string) topersia_render_setting( 'font_w' . $weight, '' ) );

				if ( '' === $file ) {
					continue;
				}

				$css .= '@font-face{font-family:"' . $safe . '";src:url("' . esc_url( topersia_render_font_url( $file ) )
					. '") format("woff2");font-weight:' . $weight . ';font-style:normal;font-display:swap;}';
			}
		}

		$stack = trim( (string) topersia_render_setting( 'font_stack', 'Vazirmatn, sans-serif' ) );
		$css  .= '.tp-admin{font-family:"' . $safe . '", ' . str_replace( array( '{', '}', ';' ), '', $stack ) . ';}';

		return $css;
	}
}

/**
 * یک ردیف عددی.
 *
 * @param array  $o     تنظیمات.
 * @param string $key   کلید.
 * @param string $label برچسب.
 * @param int    $min   کمینه.
 * @param int    $max   بیشینه.
 * @return void
 */
if ( ! function_exists( 'topersia_render_settings_number' ) ) {
	function topersia_render_settings_number( $o, $key, $label, $min, $max ) {
		?>
		<div class="tp-field">
			<label for="tp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<span>
				<input type="number" id="tp_<?php echo esc_attr( $key ); ?>"
					min="<?php echo (int) $min; ?>" max="<?php echo (int) $max; ?>" step="1"
					name="topersia_render_settings[<?php echo esc_attr( $key ); ?>]"
					value="<?php echo esc_attr( (int) $o[ $key ] ); ?>"> px
			</span>
			<p class="note"><?php printf( esc_html__( 'میان %1$s و %2$s', 'topersia' ), esc_html( $min ), esc_html( $max ) ); ?></p>
		</div>
		<?php
	}
}

/**
 * یک ردیف نشانی.
 *
 * @param array  $o     تنظیمات.
 * @param string $key   کلید.
 * @param string $label برچسب.
 * @return void
 */
if ( ! function_exists( 'topersia_render_settings_url' ) ) {
	function topersia_render_settings_url( $o, $key, $label ) {
		?>
		<div class="tp-field tp-field--stack">
			<label for="tp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="url" id="tp_<?php echo esc_attr( $key ); ?>"
				name="topersia_render_settings[<?php echo esc_attr( $key ); ?>]"
				value="<?php echo esc_attr( $o[ $key ] ); ?>">
		</div>
		<?php
	}
}

/**
 * صفحۀ تنظیمات.
 *
 * @return void
 */
if ( ! function_exists( 'topersia_render_settings_page' ) ) {
	function topersia_render_settings_page() {

		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$o     = topersia_render_settings_all();
		$fresh = isset( $_GET['tp_recheck'] );
		$audit = topersia_render_bridge_audit( $fresh );
		$dead  = count( $audit['dead'] );
		?>
		<style><?php echo topersia_render_settings_css() . topersia_render_settings_font_css(); // phpcs:ignore ?></style>

		<div class="wrap tp-admin" dir="rtl">

			<div class="tp-admin__head">
				<h1><?php echo esc_html__( 'هستۀ نمایش ToPersia', 'topersia' ); ?></h1>
				<p><?php echo esc_html__( 'قلم، شکل و رنگ سایت از اینجا کنترل می‌شوند. تنظیمات وودمارت و المنتور نادیده گرفته می‌شوند تا یک مرجع واحد بماند.', 'topersia' ); ?></p>
			</div>

			<div class="tp-tabs" role="tablist">
				<button type="button" role="tab" data-tp-tab="font"   aria-selected="true"><?php echo esc_html__( 'قلم', 'topersia' ); ?></button>
				<button type="button" role="tab" data-tp-tab="shape"  aria-selected="false"><?php echo esc_html__( 'شکل و ابعاد', 'topersia' ); ?></button>
				<button type="button" role="tab" data-tp-tab="color"  aria-selected="false"><?php echo esc_html__( 'رنگ', 'topersia' ); ?></button>
				<button type="button" role="tab" data-tp-tab="ai"     aria-selected="false"><?php echo esc_html__( 'هوش مصنوعی', 'topersia' ); ?></button>
				<button type="button" role="tab" data-tp-tab="bridge" aria-selected="false">
					<?php echo esc_html__( 'پل تم', 'topersia' ); ?><?php echo $dead ? ' ●' : ''; ?>
				</button>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'topersia_render_group' ); ?>

				<!-- ══ قلم ══ -->
				<div class="tp-panel" data-tp-panel="font">
					<div class="tp-grid">

						<div class="tp-card">
							<h2><?php echo esc_html__( 'قلم اختصاصی', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'فایل‌ها را در کتابخانه یا پوشۀ آپلود بگذارید و نشانی‌شان را اینجا وارد کنید.', 'topersia' ); ?></p>

							<div class="tp-field tp-field--stack">
								<label for="tp_font_name"><?php echo esc_html__( 'نام قلم', 'topersia' ); ?></label>
								<input type="text" id="tp_font_name" name="topersia_render_settings[font_name]"
									value="<?php echo esc_attr( $o['font_name'] ); ?>" placeholder="Peyda">
								<p class="note"><?php echo esc_html__( 'خالی بماند، از پشتۀ جایگزین استفاده می‌شود.', 'topersia' ); ?></p>
							</div>

							<div class="tp-field tp-field--stack">
								<label class="tp-check">
									<input type="checkbox" name="topersia_render_settings[font_variable]" value="1"
										<?php checked( 1, (int) $o['font_variable'] ); ?>>
									<span style="font-weight:600;color:inherit"><?php echo esc_html__( 'قلم متغیر است', 'topersia' ); ?>
										<span><?php echo esc_html__( 'همۀ وزن‌ها در یک فایل — فقط نشانی فایل متغیر پر شود.', 'topersia' ); ?></span>
									</span>
								</label>
							</div>

							<?php topersia_render_settings_url( $o, 'font_woff2', esc_html__( 'نشانی فایل متغیر', 'topersia' ) ); ?>

							<div class="tp-field tp-field--stack">
								<label for="tp_font_stack"><?php echo esc_html__( 'پشتۀ جایگزین', 'topersia' ); ?></label>
								<input type="text" id="tp_font_stack" name="topersia_render_settings[font_stack]"
									value="<?php echo esc_attr( $o['font_stack'] ); ?>">
							</div>

							<?php topersia_render_settings_number( $o, 'base_size', esc_html__( 'اندازۀ پایۀ متن', 'topersia' ), 14, 22 ); ?>
						</div>

						<div class="tp-card">
							<h2><?php echo esc_html__( 'وزن‌ها', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'هر وزنی که خالی بماند بارگذاری نمی‌شود. شش وزن زیر همان‌هایی‌اند که طرح استفاده می‌کند.', 'topersia' ); ?></p>
							<?php
							$weights = array(
								'font_w300' => esc_html__( 'وزن ۳۰۰ — نازک', 'topersia' ),
								'font_w400' => esc_html__( 'وزن ۴۰۰ — معمولی', 'topersia' ),
								'font_w500' => esc_html__( 'وزن ۵۰۰ — متوسط', 'topersia' ),
								'font_w600' => esc_html__( 'وزن ۶۰۰ — نیمه‌ضخیم', 'topersia' ),
								'font_w700' => esc_html__( 'وزن ۷۰۰ — ضخیم', 'topersia' ),
								'font_w800' => esc_html__( 'وزن ۸۰۰ — خیلی ضخیم', 'topersia' ),
							);

							foreach ( $weights as $key => $label ) {
								topersia_render_settings_url( $o, $key, $label );
							}
							?>
						</div>

					</div>
				</div>

				<!-- ══ شکل ══ -->
				<div class="tp-panel" data-tp-panel="shape" hidden>
					<div class="tp-grid">

						<div class="tp-card">
							<h2><?php echo esc_html__( 'گوشه و حاشیه', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'همین عددها به متغیرهای وودمارت هم می‌روند تا دکمۀ تم و دکمۀ ما یک شکل بمانند.', 'topersia' ); ?></p>
							<?php
							topersia_render_settings_number( $o, 'radius', esc_html__( 'گوشۀ کارت و بلوک', 'topersia' ), 0, 28 );
							topersia_render_settings_number( $o, 'control_radius', esc_html__( 'گوشۀ دکمه و فرم', 'topersia' ), 0, 28 );
							topersia_render_settings_number( $o, 'border_width', esc_html__( 'ضخامت حاشیۀ فرم', 'topersia' ), 0, 4 );
							?>
						</div>

						<div class="tp-card">
							<h2><?php echo esc_html__( 'ابعاد', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'ارتفاع دکمه و فرم روی هم‌ترازی ردیف سربرگ اثر می‌گذارد.', 'topersia' ); ?></p>
							<?php
							topersia_render_settings_number( $o, 'control_height', esc_html__( 'ارتفاع دکمه و فرم', 'topersia' ), 32, 60 );
							topersia_render_settings_number( $o, 'container', esc_html__( 'پهنای ظرف صفحه', 'topersia' ), 960, 1600 );
							?>
							<div class="tp-field">
								<label for="tp_shadow"><?php echo esc_html__( 'عمق سایه', 'topersia' ); ?></label>
								<select id="tp_shadow" name="topersia_render_settings[shadow]">
									<option value="none" <?php selected( 'none', $o['shadow'] ); ?>><?php echo esc_html__( 'بدون سایه', 'topersia' ); ?></option>
									<option value="soft" <?php selected( 'soft', $o['shadow'] ); ?>><?php echo esc_html__( 'ملایم', 'topersia' ); ?></option>
									<option value="deep" <?php selected( 'deep', $o['shadow'] ); ?>><?php echo esc_html__( 'عمیق', 'topersia' ); ?></option>
								</select>
								<p class="note"><?php echo esc_html__( 'شدت سایه در هر سه حالت رنگ جدا تعریف شده؛ این فقط پله را انتخاب می‌کند.', 'topersia' ); ?></p>
							</div>
						</div>

					</div>
				</div>

				<!-- ══ رنگ ══ -->
				<div class="tp-panel" data-tp-panel="color" hidden>
					<div class="tp-grid">

						<div class="tp-card">
							<h2><?php echo esc_html__( 'حالت پیش‌فرض', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'تا وقتی بازدیدکننده خودش حالتی انتخاب نکرده، سایت با این حالت باز می‌شود.', 'topersia' ); ?></p>
							<div class="tp-field">
								<label for="tp_default_mode"><?php echo esc_html__( 'حالت', 'topersia' ); ?></label>
								<select id="tp_default_mode" name="topersia_render_settings[default_mode]">
									<option value="auto"    <?php selected( 'auto', $o['default_mode'] ); ?>><?php echo esc_html__( 'پیروی از سیستم کاربر', 'topersia' ); ?></option>
									<option value="light"   <?php selected( 'light', $o['default_mode'] ); ?>><?php echo esc_html__( 'روز', 'topersia' ); ?></option>
									<option value="evening" <?php selected( 'evening', $o['default_mode'] ); ?>><?php echo esc_html__( 'بعدازظهر', 'topersia' ); ?></option>
									<option value="dark"    <?php selected( 'dark', $o['default_mode'] ); ?>><?php echo esc_html__( 'شب', 'topersia' ); ?></option>
								</select>
							</div>
							<p class="note" style="margin-top:.7rem"><?php echo esc_html__( 'پالت سه‌حالته با تصمیم P5-D150 قفل است و از این پنل ویرایش نمی‌شود.', 'topersia' ); ?></p>
						</div>

						<div class="tp-preview">
							<div class="tp-preview__bar">
								<button type="button" data-tp-mode="light" aria-pressed="true"><?php echo esc_html__( 'روز', 'topersia' ); ?></button>
								<button type="button" data-tp-mode="evening" aria-pressed="false"><?php echo esc_html__( 'بعدازظهر', 'topersia' ); ?></button>
								<button type="button" data-tp-mode="dark" aria-pressed="false"><?php echo esc_html__( 'شب', 'topersia' ); ?></button>
							</div>
							<div class="tp-stage" id="tp-stage">
								<h3><?php echo esc_html__( 'استان اصفهان', 'topersia' ); ?></h3>
								<p><?php echo esc_html__( 'پهناورترین استان مرکزی ایران؛ جایی که زاگرس در غرب به کویر مرکزی می‌رسد.', 'topersia' ); ?></p>
								<div class="row">
									<span class="tp-btn"><?php echo esc_html__( 'دکمۀ نمونه', 'topersia' ); ?></span>
									<span class="tp-input"><?php echo esc_html__( 'فیلد نمونه', 'topersia' ); ?></span>
								</div>
								<div class="tp-swatches"></div>
							</div>
						</div>

					</div>
				</div>

				<!-- ══ پل تم ══ -->
				<div class="tp-panel" data-tp-panel="bridge" hidden>
					<div class="tp-grid">

						<div class="tp-card">
							<h2><?php echo esc_html__( 'تصاحب قلم و رنگ', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'هر کدام جدا خاموش می‌شود. با خاموش‌کردن، همان بخش به تنظیمات خود تم برمی‌گردد.', 'topersia' ); ?></p>

							<label class="tp-check">
								<input type="checkbox" name="topersia_render_settings[own_typography]" value="1"
									<?php checked( 1, (int) $o['own_typography'] ); ?>>
								<span style="font-weight:600;color:inherit"><?php echo esc_html__( 'قلم پروژه مقدم باشد', 'topersia' ); ?>
									<span><?php echo esc_html__( 'قلم آیکونی تم دست‌نخورده می‌ماند.', 'topersia' ); ?></span></span>
							</label>

							<label class="tp-check">
								<input type="checkbox" name="topersia_render_settings[bridge_woodmart]" value="1"
									<?php checked( 1, (int) $o['bridge_woodmart'] ); ?>>
								<span style="font-weight:600;color:inherit"><?php echo esc_html__( 'پل وودمارت', 'topersia' ); ?>
									<span><?php echo esc_html__( 'رنگ‌های تم از پالت پروژه می‌آیند.', 'topersia' ); ?></span></span>
							</label>

							<label class="tp-check">
								<input type="checkbox" name="topersia_render_settings[bridge_elementor]" value="1"
									<?php checked( 1, (int) $o['bridge_elementor'] ); ?>>
								<span style="font-weight:600;color:inherit"><?php echo esc_html__( 'پل المنتور', 'topersia' ); ?>
									<span><?php echo esc_html__( 'رنگ‌های سراسری از پالت پروژه می‌آیند.', 'topersia' ); ?></span></span>
							</label>
						</div>

						<div class="tp-card">
							<h2><?php echo esc_html__( 'ممیزی نگاشت', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'اگر به‌روزرسانی تم نام متغیری را عوض کند، پل بی‌صدا می‌میرد. اینجا خودش را نشان می‌دهد.', 'topersia' ); ?></p>

							<?php if ( '' !== $audit['error'] ) : ?>
								<p class="tp-pill tp-pill--bad"><?php echo esc_html( $audit['error'] ); ?></p>
							<?php else : ?>
								<div class="tp-status">
									<span class="tp-pill <?php echo $dead ? 'tp-pill--warn' : 'tp-pill--ok'; ?>">
										<?php printf( esc_html__( '%1$s از %2$s نگاشت زنده', 'topersia' ), esc_html( $audit['alive'] ), esc_html( $audit['mapped'] ) ); ?>
									</span>
									<span class="tp-pill" style="color:var(--c-ink-2)">
										<?php printf( esc_html__( '%s فایل تم خوانده شد', 'topersia' ), esc_html( $audit['files'] ) ); ?>
									</span>
								</div>

								<?php if ( $dead ) : ?>
									<p class="note"><?php echo esc_html__( 'این نگاشت‌ها دیگر در CSS تم مصرف نمی‌شوند:', 'topersia' ); ?></p>
									<div class="tp-dead"><?php echo esc_html( implode( '  ·  ', $audit['dead'] ) ); ?></div>
								<?php endif; ?>
							<?php endif; ?>

							<p class="note" style="margin-top:.8rem">
								<a href="<?php echo esc_url( add_query_arg( 'tp_recheck', '1' ) ); ?>"><?php echo esc_html__( 'بررسی دوباره', 'topersia' ); ?></a>
								<?php echo esc_html__( ' — نتیجه دوازده ساعت کش می‌شود و با هر ذخیره تازه می‌شود.', 'topersia' ); ?>
							</p>
						</div>

					</div>
				</div>

				<!-- ══ هوش مصنوعی — ۴٫۳٫۰ بند ۴ · P5-D235 ══ -->
				<div class="tp-panel" data-tp-panel="ai" hidden>
					<div class="tp-grid">
						<div class="tp-card">
							<h2><?php echo esc_html__( 'سقف روزانۀ تولید', 'topersia' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'مهمان و ربات فقط خروجی ذخیره‌شده را می‌بینند و هیچ فراخوانی نمی‌سازند. تولید فقط برای کاربر واردشده است؛ خروجی هر پست، نوع و زبان یک‌بار ساخته و برای همه نگه داشته می‌شود و فقط تولیدِ تازه از سقف کم می‌کند. صفر یعنی بسته.', 'topersia' ); ?></p>
							<?php foreach ( array(
								'ai_user_daily' => array( esc_html__( 'سقف روزانۀ هر کاربر', 'topersia' ), 0, 1000 ),
								'ai_site_daily' => array( esc_html__( 'سقف روزانۀ کل سایت', 'topersia' ), 0, 100000 ),
							) as $tp_key => $tp_f ) : ?>
								<div class="tp-field">
									<label for="tp_<?php echo esc_attr( $tp_key ); ?>"><?php echo esc_html( $tp_f[0] ); ?></label>
									<span>
										<input type="number" id="tp_<?php echo esc_attr( $tp_key ); ?>"
											min="<?php echo (int) $tp_f[1]; ?>" max="<?php echo (int) $tp_f[2]; ?>" step="1"
											name="topersia_render_settings[<?php echo esc_attr( $tp_key ); ?>]"
											value="<?php echo esc_attr( (int) $o[ $tp_key ] ); ?>"> <?php echo esc_html__( 'بار در روز', 'topersia' ); ?>
									</span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="tp-save"><?php submit_button( esc_html__( 'ذخیرۀ تغییرات', 'topersia' ), 'primary', 'submit', false ); ?></div>
			</form>

			<?php
			/* گالری تجمیعی (P5-D274 · P5-D300) — کارت در topersia-g-gallery-agg.php، با کاتالوگ */
			if ( function_exists( 'topersia_g_gallery_agg_admin_card' ) ) {
				topersia_g_gallery_agg_admin_card();
			}
			?>
		</div>

		<script>
		( function () {
			var KEY   = 'tp-admin-tab';
			var tabs  = document.querySelectorAll( '[data-tp-tab]' );
			var panes = document.querySelectorAll( '[data-tp-panel]' );

			function show( name ) {
				for ( var i = 0; i < tabs.length; i++ ) {
					tabs[ i ].setAttribute( 'aria-selected', tabs[ i ].dataset.tpTab === name ? 'true' : 'false' );
				}
				for ( var j = 0; j < panes.length; j++ ) {
					panes[ j ].hidden = panes[ j ].dataset.tpPanel !== name;
				}
				try { localStorage.setItem( KEY, name ); } catch ( e ) {}
			}

			for ( var t = 0; t < tabs.length; t++ ) {
				tabs[ t ].addEventListener( 'click', function () { show( this.dataset.tpTab ); } );
			}

			try {
				var saved = localStorage.getItem( KEY );
				if ( saved ) { show( saved ); }
			} catch ( e ) {}

			/* ── پیش‌نمایش زنده ─────────────────────────────────────────
			   سه حالت پالت، با همان اعدادی که بالا تنظیم می‌کنید. */
			var PAL = {
				light:   { bg:'#FAF7F1', surface:'#F4EFE6', text:'#2A2A28', text2:'#6F6B64',
				           border:'#DDD5C8', hover:'#177E7A', onHover:'#FFFFFF',
				           sw:['#1C9E9A','#2E6E8E','#D69C1E','#3F7D4E','#C9861A','#B54145'] },
				evening: { bg:'#F6EFE4', surface:'#EFE4D3', text:'#332B26', text2:'#706156',
				           border:'#D8C7B2', hover:'#984D29', onHover:'#FFFFFF',
				           sw:['#AF5E35','#4E6B52','#E1A62B','#5C8A5B','#CC7A2C','#B24B4A'] },
				dark:    { bg:'#11161B', surface:'#1B232B', text:'#E6E1D9', text2:'#B5B1AA',
				           border:'#313B46', hover:'#2EB8AF', onHover:'#11161B',
				           sw:['#27A59D','#3D5E8C','#D4A63D','#4E9B69','#D18A2F','#CC5A5F'] }
			};

			var stage = document.getElementById( 'tp-stage' );
			if ( ! stage ) { return; }

			function num( id, fallback ) {
				var el = document.getElementById( id );
				return el ? ( parseInt( el.value, 10 ) || fallback ) : fallback;
			}

			function paint( mode ) {
				var p = PAL[ mode ] || PAL.light;
				var r = num( 'tp_control_radius', 8 );
				var h = num( 'tp_control_height', 42 );
				var b = num( 'tp_border_width', 1 );
				var s = num( 'tp_base_size', 15 );

				stage.style.background = p.bg;
				stage.style.color      = p.text;
				stage.style.fontSize   = s + 'px';

				var title = stage.querySelector( 'h3' );
				if ( title ) { title.style.color = p.text; title.style.fontWeight = 800; }

				var btn = stage.querySelector( '.tp-btn' );
				if ( btn ) {
					btn.style.cssText = 'display:inline-flex;align-items:center;padding:0 1rem;font-weight:700;'
						+ 'height:' + h + 'px;border-radius:' + r + 'px;background:' + p.hover + ';color:' + p.onHover;
				}

				var inp = stage.querySelector( '.tp-input' );
				if ( inp ) {
					inp.style.cssText = 'display:inline-flex;align-items:center;padding:0 1rem;'
						+ 'height:' + h + 'px;border-radius:' + r + 'px;background:' + p.surface
						+ ';color:' + p.text2 + ';border:' + b + 'px solid ' + p.border;
				}

				var box = stage.querySelector( '.tp-swatches' );
				if ( box ) {
					box.innerHTML = '';
					for ( var i = 0; i < p.sw.length; i++ ) {
						var el = document.createElement( 'i' );
						el.style.background = p.sw[ i ];
						box.appendChild( el );
					}
				}
			}

			var modeBtns = document.querySelectorAll( '[data-tp-mode]' );
			for ( var m = 0; m < modeBtns.length; m++ ) {
				modeBtns[ m ].addEventListener( 'click', function () {
					for ( var k = 0; k < modeBtns.length; k++ ) {
						modeBtns[ k ].setAttribute( 'aria-pressed', modeBtns[ k ] === this ? 'true' : 'false' );
					}
					paint( this.dataset.tpMode );
				} );
			}

			[ 'tp_control_radius', 'tp_control_height', 'tp_border_width', 'tp_base_size' ].forEach( function ( id ) {
				var el = document.getElementById( id );
				if ( el ) { el.addEventListener( 'input', function () { paint( current() ); } ); }
			} );

			function current() {
				for ( var i = 0; i < modeBtns.length; i++ ) {
					if ( 'true' === modeBtns[ i ].getAttribute( 'aria-pressed' ) ) { return modeBtns[ i ].dataset.tpMode; }
				}
				return 'light';
			}

			paint( 'light' );
		} )();
		</script>
		<?php
	}
}
