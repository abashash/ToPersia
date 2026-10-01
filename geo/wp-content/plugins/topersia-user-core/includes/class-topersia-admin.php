<?php
/**
 * ToPersia User Core — مدیریت
 *
 * رابط این صفحه از همان شیوه‌نامه و همان واژگان کلاسِ «هستۀ نمایش» استفاده
 * می‌کند: tp-admin و tp-tabs و tp-card و tp-field. کد دو افزونه جدا می‌ماند،
 * فقط زبان بصری مشترک است.  P5-D165
 *
 * دو صفحۀ جدا به یک صفحۀ سه‌زبانه‌ای تبدیل شد. همۀ فیلدها در یک فرم‌اند و
 * پنل‌های غیرفعال فقط پنهان می‌شوند، وگرنه ذخیره مقادیر زبانه‌های دیگر را
 * پاک می‌کرد.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Admin {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ), 999 );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/* ==================================================================
	   داده
	   ================================================================== */

	public static function default_tabs(): array {
		return array(
			'overview'      => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 10, 'icon' => 'home',    'fa' => 'نمای کلی',            'en' => 'Overview',          'ar' => 'نظرة عامة' ),
			'profile'       => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 20, 'icon' => 'user',    'fa' => 'پروفایل من',           'en' => 'My Profile',        'ar' => 'ملفي الشخصي' ),
			'saved'         => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 30, 'icon' => 'bookmark','fa' => 'مکان‌های ذخیره‌شده',   'en' => 'Saved Places',      'ar' => 'الأماكن المحفوظة' ),
			'following'     => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 35, 'icon' => 'follow',  'fa' => 'دنبال‌شده‌ها',          'en' => 'Following',         'ar' => 'المتابَعة' ),
			'trips'         => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 40, 'icon' => 'route',   'fa' => 'سفرهای من',            'en' => 'My Trips',          'ar' => 'رحلاتي' ),
			'reviews'       => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 60, 'icon' => 'star',    'fa' => 'نظرات و امتیازها',     'en' => 'Reviews & Ratings', 'ar' => 'التقييمات' ),
			'notifications' => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 70, 'icon' => 'bell',    'fa' => 'اعلان‌ها',              'en' => 'Notifications',     'ar' => 'الإشعارات' ),
			'settings'      => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 80, 'icon' => 'cog',     'fa' => 'تنظیمات',              'en' => 'Settings',          'ar' => 'الإعدادات' ),
		);
	}

	/**
	 * شناسۀ برگۀ حساب کاربری — نه نشانی آن.
	 *
	 * چرا شناسه و نه نشانی: در یک سایت WPML هر ترجمه یک برگۀ جداست با
	 * شناسه و نشانی خودش. تنظیم مدیر یک نشانی ثابت است (مثلاً
	 * `https://topersia.com/account/`)، پس هر مقایسه یا هدایتی که روی آن
	 * نشانی بنا شود، روی `/en/account/` و `/ar/account/` غلط از آب درمی‌آید.
	 * شناسه، لنگرِ زبان‌مستقل است.
	 *
	 * @return int صفر اگر برگه پیدا نشود.
	 */
	public static function account_page_id(): int {
		static $cached = null;

		if ( null !== $cached ) {
			return $cached;
		}

		$value = (string) get_option( 'topersia_account_page_url', '' );

		if ( '' !== $value ) {
			$found = url_to_postid( $value );

			if ( $found > 0 ) {
				return $cached = (int) $found;
			}
		}

		$page = get_page_by_path( 'account' );

		return $cached = ( $page ? (int) $page->ID : 0 );
	}

	/**
	 * شناسۀ برگۀ حساب در زبان پیش‌فرض — برای مقایسه، نه برای نمایش.
	 */
	public static function account_page_id_canonical(): int {
		$id = self::account_page_id();

		if ( $id <= 0 ) {
			return 0;
		}

		$default = apply_filters( 'wpml_default_language', null );

		if ( ! is_string( $default ) || '' === $default ) {
			return $id;
		}

		$canonical = apply_filters( 'wpml_object_id', $id, 'page', true, $default );

		return is_numeric( $canonical ) && (int) $canonical > 0 ? (int) $canonical : $id;
	}

	/**
	 * نشانی برگۀ حساب کاربری در زبان جاری.
	 *
	 * باگی که این حل می‌کند: پیش‌تر همیشه یک نشانی ثابت برمی‌گشت. دو پیامد
	 * واقعی داشت — (۱) کاربر انگلیسی پس از ورود موفق به برگۀ فارسی هدایت
	 * می‌شد، که خوشۀ hreflang را می‌شکند و بازدیدکننده را از زبانش پرت
	 * می‌کند؛ (۲) تشخیص بارگذاری دارایی‌ها در ToPersia_Assets نشانی‌ها را
	 * مقایسه می‌کرد، پس روی `/en/account/` هیچ‌وقت جور نمی‌شد و CSS و JS
	 * پنل اصلاً بار نمی‌شدند — یعنی پنل انگلیسی بدون قالب و بدون تب.
	 */
	public static function account_url(): string {
		$id = self::account_page_id();

		if ( $id > 0 ) {
			$lang = apply_filters( 'wpml_current_language', null );

			if ( is_string( $lang ) && '' !== $lang ) {
				$localized = apply_filters( 'wpml_object_id', $id, 'page', true, $lang );

				if ( is_numeric( $localized ) && (int) $localized > 0 ) {
					$id = (int) $localized;
				}
			}

			$permalink = get_permalink( $id );

			if ( $permalink ) {
				return $permalink;
			}
		}

		/* برگه پیدا نشد. نشانی خام تنظیمات، و در نبود آن خانه. */
		$value = (string) get_option( 'topersia_account_page_url', '' );

		if ( '' !== $value ) {
			return esc_url_raw( $value );
		}

		return (string) apply_filters( 'wpml_home_url', home_url( '/account/' ) );
	}

	public static function tabs(): array {
		$saved    = get_option( 'topersia_user_tabs', array() );
		$defaults = self::default_tabs();

		if ( ! is_array( $saved ) || ! $saved ) {
			$saved = $defaults;
		}

		$merged = array();

		foreach ( $defaults as $id => $def ) {
			$value          = is_array( $saved[ $id ] ?? null ) ? $saved[ $id ] : array();
			$merged[ $id ]  = array_merge( $def, $value );
			$merged[ $id ]['type'] = 'builtin';
		}

		$custom = is_array( $saved['_custom'] ?? null ) ? $saved['_custom'] : array();

		foreach ( $custom as $id => $value ) {

			if ( ! is_array( $value ) ) {
				continue;
			}

			$id = sanitize_key( (string) $id );

			if ( '' === $id || isset( $merged[ $id ] ) ) {
				continue;
			}

			$merged[ $id ] = array_merge(
				array(
					'type' => 'custom', 'enabled' => 1, 'order' => 100, 'icon' => 'puzzle',
					'fa' => 'تب جدید', 'en' => 'New Tab', 'ar' => 'تب جديد', 'shortcode' => '',
				),
				$value
			);

			$merged[ $id ]['type']      = 'custom';
			$merged[ $id ]['enabled']   = ! empty( $merged[ $id ]['enabled'] ) ? 1 : 0;
			$merged[ $id ]['order']     = (int) ( $merged[ $id ]['order'] ?? 100 );
			$merged[ $id ]['shortcode'] = trim( (string) ( $merged[ $id ]['shortcode'] ?? '' ) );
		}

		uasort( $merged, static fn( $a, $b ) => ( (int) ( $a['order'] ?? 100 ) ) <=> ( (int) ( $b['order'] ?? 100 ) ) );

		return $merged;
	}

	/* ==================================================================
	   منو و ثبت
	   ================================================================== */

	/**
	 * منو.
	 *
	 * اگر «هستۀ نمایش» نصب باشد، این صفحه زیرمنوی آن می‌شود تا منوهای
	 * سطح‌بالای ToPersia به یکی برسند. اگر نباشد، منوی مستقل خودش را
	 * می‌سازد — هیچ وابستگی سختی ایجاد نمی‌شود.
	 *
	 * @return void
	 */
	public function menu(): void {

		$cap  = 'manage_options';
		$hook = false;

		/*
		 * اولویت با منوی هسته است تا همۀ افزونه‌ها زیر یک نام بمانند؛
		 * اگر نبود، زیر منوی نمایش؛ اگر آن هم نبود، منوی مستقل.
		 *
		 * add_submenu_page() اگر افزونۀ والد به هر دلیلی (ترتیب بارگذاری،
		 * آپدیت، غیرفعال بودن موقت) هنوز منوی خودش را نساخته باشد، false
		 * برمی‌گرداند و وردپرس هیچ خطایی نشان نمی‌دهد — منو فقط بی‌صدا
		 * ناپدید می‌شود. یک‌بار همین باعث شد «کاربران» کلاً از پیشخوان گم
		 * شود؛ برای همین دیگر هرگز با return زودهنگام به نتیجۀ
		 * add_submenu_page() اعتماد نمی‌کنیم و همیشه نتیجه را چک می‌کنیم.
		 */
		if ( function_exists( 'topersia_admin_page' ) ) {
			$hook = add_submenu_page( 'topersia', 'کاربران', 'کاربران', $cap, 'topersia-user-core', array( $this, 'page' ) );
		}

		if ( ! $hook && function_exists( 'topersia_render_settings_page' ) ) {
			$hook = add_submenu_page( 'topersia-render', 'کاربران', 'کاربران', $cap, 'topersia-user-core', array( $this, 'page' ) );
		}

		if ( ! $hook ) {
			add_menu_page(
				'ToPersia کاربران',
				'ToPersia کاربران',
				$cap,
				'topersia-user-core',
				array( $this, 'page' ),
				'dashicons-admin-users',
				30
			);
		}
	}

	public function settings(): void {

		register_setting(
			'topersia_user_core',
			'topersia_mediana_api_key',
			array( 'type' => 'string', 'sanitize_callback' => static fn( $v ) => sanitize_text_field( (string) $v ) )
		);

		register_setting(
			'topersia_user_core',
			'topersia_mediana_pattern_code',
			array( 'type' => 'string', 'sanitize_callback' => static fn( $v ) => sanitize_text_field( (string) $v ) )
		);

		register_setting(
			'topersia_user_core',
			'topersia_account_page_url',
			array( 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' )
		);

		register_setting(
			'topersia_user_core',
			'topersia_user_tabs',
			array( 'type' => 'array', 'sanitize_callback' => array( $this, 'sanitize_tabs' ) )
		);
	}

	public function sanitize_tabs( $input ): array {

		$defaults = self::default_tabs();
		$output   = array();
		$input    = is_array( $input ) ? $input : array();

		foreach ( $defaults as $id => $def ) {

			$row = is_array( $input[ $id ] ?? null ) ? $input[ $id ] : array();

			$output[ $id ] = array(
				'type'    => 'builtin',
				'enabled' => ! empty( $row['enabled'] ) ? 1 : 0,
				'order'   => isset( $row['order'] ) ? max( 1, min( 999, (int) $row['order'] ) ) : $def['order'],
				'icon'    => $this->sanitize_icon( (string) ( $row['icon'] ?? $def['icon'] ) ),
				'fa'      => sanitize_text_field( (string) ( $row['fa'] ?? $def['fa'] ) ),
				'en'      => sanitize_text_field( (string) ( $row['en'] ?? $def['en'] ) ),
				'ar'      => sanitize_text_field( (string) ( $row['ar'] ?? $def['ar'] ) ),
			);
		}

		$output['_custom'] = array();
		$custom            = is_array( $input['_custom'] ?? null ) ? $input['_custom'] : array();

		foreach ( $custom as $raw_id => $row ) {

			if ( ! is_array( $row ) ) {
				continue;
			}

			$id = sanitize_key( (string) $raw_id );

			if ( '' === $id || isset( $defaults[ $id ] ) ) {
				continue;
			}

			$output['_custom'][ $id ] = array(
				'type'      => 'custom',
				'enabled'   => ! empty( $row['enabled'] ) ? 1 : 0,
				'order'     => isset( $row['order'] ) ? max( 1, min( 999, (int) $row['order'] ) ) : 100,
				'icon'      => $this->sanitize_icon( (string) ( $row['icon'] ?? 'puzzle' ) ),
				'fa'        => sanitize_text_field( (string) ( $row['fa'] ?? 'تب جدید' ) ),
				'en'        => sanitize_text_field( (string) ( $row['en'] ?? 'New Tab' ) ),
				'ar'        => sanitize_text_field( (string) ( $row['ar'] ?? 'تب جديد' ) ),
				'shortcode' => sanitize_textarea_field( (string) ( $row['shortcode'] ?? '' ) ),
			);
		}

		return $output;
	}

	private function sanitize_icon( string $icon ): string {
		return ToPersia_Icons::normalize( $icon );
	}

	/**
	 * دارایی‌های پیشخوان.
	 *
	 * admin.css دیگر بار نمی‌شود: شیوه‌نامه‌اش با هستۀ نمایش یکی شد و
	 * قاعدۀ transition:.16s آن روی «همۀ ویژگی‌ها» بود که هاور را کند
	 * نشان می‌داد. فقط admin.js برای ساخت تب سفارشی می‌ماند.
	 *
	 * @param string $hook شناسۀ صفحه.
	 * @return void
	 */
	public function admin_assets( string $hook ): void {

		if ( false === strpos( $hook, 'topersia-user-core' ) ) {
			return;
		}

		wp_enqueue_script(
			'topersia-user-core-admin',
			TOPERSIA_USER_CORE_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			TOPERSIA_USER_CORE_VERSION,
			true
		);
	}

	/* ==================================================================
	   رابط
	   ================================================================== */

	/**
	 * شیوه‌نامه — از هستۀ نمایش می‌آید تا دو پنل یک زبان بصری داشته باشند.
	 *
	 * @return string
	 */
	private function styles(): string {

		$base = '';

		if ( function_exists( 'topersia_admin_css' ) ) {
			$base = topersia_admin_css();
		} elseif ( function_exists( 'topersia_render_settings_css' ) ) {
			$base = topersia_render_settings_css();
		}
		$font = function_exists( 'topersia_render_settings_font_css' ) ? topersia_render_settings_font_css() : '';

		if ( '' === $base ) {
			/* بدون هستۀ نمایش، حداقلِ لازم تا صفحه بی‌ریخت نشود. */
			$base = '.tp-admin{--c-bg:#F4EFE6;--c-card:#FAF7F1;--c-line:#DDD5C8;--c-ink:#2A2A28;'
				. '--c-ink-2:#6F6B64;--c-teal:#177E7A;--c-teal-deep:#0F6461;--c-amber:#B9871A;'
				. '--c-red:#73292C;--c-radius:12px;max-inline-size:1180px;color:var(--c-ink);font-size:14px;line-height:1.9}'
				. '.tp-card{background:var(--c-card);border:1px solid var(--c-line);border-radius:12px;padding:1.1rem 1.2rem}'
				. '.tp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:1.1rem;align-items:start}';
		}

		/* افزودۀ اختصاصی این صفحه — جدول تب‌ها. عمداً هیچ transition روی
		   «همۀ ویژگی‌ها» ندارد. */
		$extra = '
.tp-admin .tp-rows{display:grid; gap:.4rem}
.tp-admin .tp-row{display:grid; grid-template-columns:auto 4.5rem 1fr 1fr 1fr 1fr; gap:.5rem;
  align-items:center; padding:.5rem .7rem; background:#fff; border:1px solid var(--c-line); border-radius:10px}
.tp-admin .tp-row--head{background:transparent; border:0; padding-block:0;
  font-size:12px; font-weight:700; color:var(--c-ink-2)}
.tp-admin .tp-row input[type="text"]{inline-size:100%; direction:rtl}
.tp-admin .tp-row input[dir="ltr"]{direction:ltr}
.tp-admin .tp-row input[type="number"]{inline-size:100%}
.tp-admin .tp-code{font-family:ui-monospace,monospace; font-size:11px; color:var(--c-ink-2); direction:ltr}
.tp-admin .tp-custom-row{background:#fff; border:1px solid var(--c-line); border-radius:12px;
  padding:.9rem 1rem; margin-block-end:.7rem}
.tp-admin .tp-custom-top{display:flex; justify-content:space-between; align-items:center; margin-block-end:.6rem}
.tp-admin .tp-custom-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:.6rem}
.tp-admin .tp-custom-grid label{display:grid; gap:.25rem; font-size:12.5px; font-weight:600; color:var(--c-ink-2)}
.tp-admin .tp-custom-shortcode{grid-column:1/-1}
.tp-admin .tp-custom-grid textarea{font:inherit; font-size:13px; padding:.4rem .6rem;
  border:1px solid var(--c-line); border-radius:8px; background:#fff; direction:ltr}
.tp-admin .tp-kpi{display:flex; justify-content:space-between; padding-block:.45rem;
  border-block-start:1px solid var(--c-line)}
.tp-admin .tp-kpi:first-of-type{border-block-start:0}
.tp-admin .tp-kpi strong{font-size:1.05rem}
@media (max-width:900px){ .tp-admin .tp-row{grid-template-columns:auto 4rem 1fr 1fr} }
';

		return $base . $font . $extra;
	}

	/**
	 * صفحۀ کاربران — سه زبانه، یک فرم.
	 *
	 * @return void
	 */
	public function page(): void {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs    = self::tabs();
		$builtin = array_filter( $tabs, static fn( $t ) => 'builtin' === ( $t['type'] ?? '' ) );
		$custom  = array_filter( $tabs, static fn( $t ) => 'custom' === ( $t['type'] ?? '' ) );
		$schema  = $this->schema();
		$on      = count( array_filter( $builtin, static fn( $t ) => ! empty( $t['enabled'] ) ) );
		?>
		<style><?php echo $this->styles(); // phpcs:ignore ?></style>

		<div class="wrap tp-admin" dir="rtl">

			<div class="tp-admin__head">
				<h1><?php echo esc_html__( 'کاربران ToPersia', 'topersia-user-core' ); ?></h1>
				<p><?php echo esc_html__( 'بخش‌هایی که کاربر در حساب خودش می‌بیند، اتصال پیامک، و نشانی صفحۀ حساب.', 'topersia-user-core' ); ?></p>
			</div>

			<div class="tp-tabs" role="tablist">
				<button type="button" role="tab" data-tp-tab="panel"  aria-selected="true"><?php echo esc_html__( 'تب‌های پنل', 'topersia-user-core' ); ?></button>
				<button type="button" role="tab" data-tp-tab="custom" aria-selected="false"><?php echo esc_html__( 'تب سفارشی', 'topersia-user-core' ); ?></button>
				<button type="button" role="tab" data-tp-tab="conn"   aria-selected="false"><?php echo esc_html__( 'اتصال و حساب', 'topersia-user-core' ); ?></button>
				<button type="button" role="tab" data-tp-tab="jetreviews" aria-selected="false">JetReviews</button>
			</div>

			<?php if ( isset( $_GET['tp_jetreviews_setup'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible" style="margin:14px 0 0">
					<p>
						<?php
						printf(
							/* translators: %s: count */
							esc_html__( 'نظرات روی %s نوع‌پست ToPersia فعال شد.', 'topersia-user-core' ),
							esc_html( (string) absint( $_GET['tp_jetreviews_count'] ?? 0 ) ) // phpcs:ignore
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'topersia_user_core' ); ?>

				<!-- ══ تب‌های پنل ══ -->
				<div class="tp-panel" data-tp-panel="panel">
					<div class="tp-grid">
						<div class="tp-card" style="grid-column:1/-1">
							<h2><?php echo esc_html__( 'تب‌های پنل کاربری', 'topersia-user-core' ); ?></h2>
							<p class="hint">
								<?php
								/* translators: %s: count */
								printf( esc_html__( 'هم‌اکنون %s تب فعال است. ترتیب با عدد کوچک‌تر بالاتر می‌آید.', 'topersia-user-core' ), esc_html( (string) $on ) );
								?>
							</p>

							<div class="tp-rows">
								<div class="tp-row tp-row--head">
									<span><?php echo esc_html__( 'فعال', 'topersia-user-core' ); ?></span>
									<span><?php echo esc_html__( 'ترتیب', 'topersia-user-core' ); ?></span>
									<span><?php echo esc_html__( 'نشانه', 'topersia-user-core' ); ?></span>
									<span><?php echo esc_html__( 'فارسی', 'topersia-user-core' ); ?></span>
									<span>English</span>
									<span>العربية</span>
								</div>

								<?php foreach ( $builtin as $id => $tab ) : ?>
									<div class="tp-row">
										<input type="checkbox" name="topersia_user_tabs[<?php echo esc_attr( $id ); ?>][enabled]" value="1" <?php checked( ! empty( $tab['enabled'] ) ); ?>>
										<input type="number" min="1" max="999" name="topersia_user_tabs[<?php echo esc_attr( $id ); ?>][order]" value="<?php echo esc_attr( (string) $tab['order'] ); ?>">
										<select class="tp-icon-input" dir="ltr" name="topersia_user_tabs[<?php echo esc_attr( $id ); ?>][icon]">
										<?php foreach ( ToPersia_Icons::choices() as $icon_key => $icon_label ) : ?>
											<option value="<?php echo esc_attr( $icon_key ); ?>" <?php selected( ToPersia_Icons::normalize( $tab['icon'] ), $icon_key ); ?>><?php echo esc_html( $icon_label ); ?></option>
										<?php endforeach; ?>
									</select>
										<input type="text" name="topersia_user_tabs[<?php echo esc_attr( $id ); ?>][fa]" value="<?php echo esc_attr( $tab['fa'] ); ?>">
										<input dir="ltr" type="text" name="topersia_user_tabs[<?php echo esc_attr( $id ); ?>][en]" value="<?php echo esc_attr( $tab['en'] ); ?>">
										<input type="text" name="topersia_user_tabs[<?php echo esc_attr( $id ); ?>][ar]" value="<?php echo esc_attr( $tab['ar'] ); ?>">
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- ══ تب سفارشی ══ -->
				<div class="tp-panel" data-tp-panel="custom" hidden>
					<div class="tp-grid">
						<div class="tp-card" style="grid-column:1/-1">
							<h2><?php echo esc_html__( 'تب سفارشی', 'topersia-user-core' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'برای هر قابلیتی که با شورت‌کد ارائه می‌شود، یک تب مستقل بسازید.', 'topersia-user-core' ); ?></p>

							<div id="tp-custom-tabs">
								<?php foreach ( $custom as $id => $tab ) { $this->custom_row( (string) $id, $tab ); } ?>
							</div>

							<button type="button" class="button" id="tp-add-custom-tab"><?php echo esc_html__( 'افزودن تب سفارشی', 'topersia-user-core' ); ?></button>

							<template id="tp-custom-template"><?php
								$this->custom_row(
									'__KEY__',
									array( 'enabled' => 1, 'order' => 100, 'icon' => 'puzzle', 'fa' => 'تب جدید', 'en' => 'New Tab', 'ar' => 'تب جديد', 'shortcode' => '' )
								);
							?></template>
						</div>
					</div>
				</div>

				<!-- ══ اتصال و حساب ══ -->
				<div class="tp-panel" data-tp-panel="conn" hidden>
					<div class="tp-grid">

						<div class="tp-card">
							<h2><?php echo esc_html__( 'اتصال پیامک', 'topersia-user-core' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'کلید فقط روی سرور استفاده می‌شود و هرگز به مرورگر نمی‌رود.', 'topersia-user-core' ); ?></p>

							<div class="tp-field tp-field--stack">
								<label for="tp_api">Mediana API Key</label>
								<input id="tp_api" name="topersia_mediana_api_key" type="password" autocomplete="new-password"
									value="<?php echo esc_attr( get_option( 'topersia_mediana_api_key', '' ) ); ?>">
							</div>

							<div class="tp-field tp-field--stack">
								<label for="tp_pattern">OTP Pattern Code</label>
								<input id="tp_pattern" name="topersia_mediana_pattern_code" type="text" dir="ltr"
									value="<?php echo esc_attr( get_option( 'topersia_mediana_pattern_code', '' ) ); ?>">
							</div>

							<div class="tp-field tp-field--stack">
								<label for="tp_account"><?php echo esc_html__( 'نشانی صفحۀ حساب', 'topersia-user-core' ); ?></label>
								<input id="tp_account" name="topersia_account_page_url" type="url" dir="ltr"
									placeholder="https://topersia.com/account/"
									value="<?php echo esc_attr( get_option( 'topersia_account_page_url', '' ) ); ?>">
								<p class="note"><?php echo esc_html__( 'خالی بماند، برگۀ account پیدا می‌شود.', 'topersia-user-core' ); ?></p>
							</div>
						</div>

						<div class="tp-card">
							<h2><?php echo esc_html__( 'وضعیت داده', 'topersia-user-core' ); ?></h2>
							<p class="hint"><?php echo esc_html__( 'ساختار JetEngine بخشی از رابط کاربر نیست و فقط برای توسعه نگه داشته می‌شود.', 'topersia-user-core' ); ?></p>

							<div class="tp-kpi"><span>Post Type</span><strong><?php echo esc_html( (string) count( $schema['post_types'] ?? array() ) ); ?></strong></div>
							<div class="tp-kpi"><span>Meta Field</span><strong><?php echo esc_html( (string) $this->meta_count( $schema ) ); ?></strong></div>
							<div class="tp-kpi"><span>Relation</span><strong><?php echo esc_html( (string) count( $schema['relations'] ?? array() ) ); ?></strong></div>
							<div class="tp-kpi"><span><?php echo esc_html__( 'ورود با پیامک', 'topersia-user-core' ); ?></span>
								<strong><?php echo get_option( 'topersia_mediana_api_key', '' ) ? esc_html__( 'پیکربندی شده', 'topersia-user-core' ) : esc_html__( 'پیکربندی نشده', 'topersia-user-core' ); ?></strong></div>
						</div>

					</div>
				</div>

				<div class="tp-save"><?php submit_button( esc_html__( 'ذخیرۀ تغییرات', 'topersia-user-core' ), 'primary', 'submit', false ); ?></div>
			</form>

			<!-- ══ JetReviews ══ -->
			<div class="tp-panel" data-tp-panel="jetreviews" hidden>
				<div class="tp-grid">
					<div class="tp-card" style="grid-column:1/-1">
						<h2><?php echo esc_html__( 'راه‌اندازی نظرات روی نوع‌پست‌های ToPersia', 'topersia-user-core' ); ?></h2>
						<p class="hint">
							<?php echo esc_html__( 'این دکمه نظرات را روی نوع‌پست‌های پنج خانوادۀ مقصد، طبیعت، میراث، رویداد و گذرگاه مرزی روشن می‌کند و برای هر نوع‌پست یک review type با معیارهای خانواده‌اش می‌سازد. کشور، منطقه، شهرستان، صنایع دستی، خوراک و نوع‌های اطلاعاتی نظر نمی‌گیرند.', 'topersia-user-core' ); ?>
						</p>

						<?php $jr_status = class_exists( 'ToPersia_JetReviews_Setup' ) ? ToPersia_JetReviews_Setup::status() : array( 'plugin_active' => false, 'total' => 0, 'configured' => 0 ); ?>

						<div class="tp-kpi">
							<span><?php echo esc_html__( 'وضعیت افزونۀ JetReviews', 'topersia-user-core' ); ?></span>
							<strong><?php echo $jr_status['plugin_active'] ? esc_html__( 'فعال', 'topersia-user-core' ) : esc_html__( 'یافت نشد / غیرفعال', 'topersia-user-core' ); ?></strong>
						</div>
						<div class="tp-kpi">
							<span><?php echo esc_html__( 'نوع‌پست‌های دارای نظرات فعال', 'topersia-user-core' ); ?></span>
							<strong><?php echo esc_html( sprintf( '%d / %d', (int) $jr_status['configured'], (int) $jr_status['total'] ) ); ?></strong>
						</div>

						<?php if ( ! $jr_status['plugin_active'] ) : ?>
							<p class="note"><?php echo esc_html__( 'ابتدا افزونۀ JetReviews را نصب و فعال کنید، بعد به این تب برگردید.', 'topersia-user-core' ); ?></p>
						<?php else : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( ToPersia_JetReviews_Setup::NONCE_ACTION ); ?>
								<input type="hidden" name="action" value="topersia_setup_jetreviews">

								<div class="tp-field" style="margin-block:12px">
									<label>
										<input type="checkbox" name="need_approve" value="1" checked disabled>
										<?php echo esc_html__( 'نظر و پاسخ‌ها پیش از نمایش عمومی تأیید مدیر می‌خواهند (الزامی)', 'topersia-user-core' ); ?>
									</label>
								</div>

								<?php submit_button( esc_html__( 'راه‌اندازی نظرات خانواده‌ها', 'topersia-user-core' ), 'primary', 'submit', false ); ?>
							</form>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<script>
		( function () {
			var KEY   = 'tp-user-admin-tab';
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
		} )();
		</script>
		<?php
	}

	private function custom_row( string $id, array $tab ): void {

		$key = '__KEY__' === $id ? '__KEY__' : sanitize_key( $id );
		?>
		<article class="tp-custom-row" data-key="<?php echo esc_attr( $key ); ?>">
			<div class="tp-custom-top">
				<strong><?php echo esc_html__( 'تب سفارشی', 'topersia-user-core' ); ?> <span class="tp-code"><?php echo esc_html( $key ); ?></span></strong>
				<button type="button" class="button-link-delete tp-remove-custom"><?php echo esc_html__( 'حذف', 'topersia-user-core' ); ?></button>
			</div>
			<div class="tp-custom-grid">
				<label><?php echo esc_html__( 'فعال', 'topersia-user-core' ); ?>
					<input type="checkbox" name="topersia_user_tabs[_custom][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $tab['enabled'] ) ); ?>></label>
				<label><?php echo esc_html__( 'ترتیب', 'topersia-user-core' ); ?>
					<input type="number" min="1" max="999" name="topersia_user_tabs[_custom][<?php echo esc_attr( $key ); ?>][order]" value="<?php echo esc_attr( (string) ( $tab['order'] ?? 100 ) ); ?>"></label>
				<label><?php echo esc_html__( 'نشانه', 'topersia-user-core' ); ?>
					<select class="tp-icon-input" dir="ltr" name="topersia_user_tabs[_custom][<?php echo esc_attr( $key ); ?>][icon]">
						<?php foreach ( ToPersia_Icons::choices() as $icon_key => $icon_label ) : ?>
							<option value="<?php echo esc_attr( $icon_key ); ?>" <?php selected( ToPersia_Icons::normalize( (string) ( $tab['icon'] ?? 'puzzle' ) ), $icon_key ); ?>><?php echo esc_html( $icon_label ); ?></option>
						<?php endforeach; ?>
					</select></label>
				<label><?php echo esc_html__( 'فارسی', 'topersia-user-core' ); ?>
					<input type="text" name="topersia_user_tabs[_custom][<?php echo esc_attr( $key ); ?>][fa]" value="<?php echo esc_attr( (string) ( $tab['fa'] ?? 'تب جدید' ) ); ?>"></label>
				<label dir="ltr">English
					<input dir="ltr" type="text" name="topersia_user_tabs[_custom][<?php echo esc_attr( $key ); ?>][en]" value="<?php echo esc_attr( (string) ( $tab['en'] ?? 'New Tab' ) ); ?>"></label>
				<label>العربية
					<input type="text" name="topersia_user_tabs[_custom][<?php echo esc_attr( $key ); ?>][ar]" value="<?php echo esc_attr( (string) ( $tab['ar'] ?? 'تب جديد' ) ); ?>"></label>
				<label class="tp-custom-shortcode">Shortcode
					<textarea rows="2" name="topersia_user_tabs[_custom][<?php echo esc_attr( $key ); ?>][shortcode]"><?php echo esc_textarea( (string) ( $tab['shortcode'] ?? '' ) ); ?></textarea></label>
			</div>
		</article>
		<?php
	}

	private function schema(): array {

		$path = TOPERSIA_USER_CORE_DIR . 'includes/jetengine-schema.php';

		if ( ! is_readable( $path ) ) {
			return array();
		}

		$data = require $path;

		return is_array( $data ) ? $data : array();
	}

	private function meta_count( array $schema ): int {

		$count = 0;

		foreach ( ( $schema['meta_boxes'] ?? array() ) as $box ) {
			if ( is_array( $box['meta_fields'] ?? null ) ) {
				$count += count( $box['meta_fields'] );
			}
		}

		return $count;
	}
}
