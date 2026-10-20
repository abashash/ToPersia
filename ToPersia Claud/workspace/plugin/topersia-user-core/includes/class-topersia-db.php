<?php
defined('ABSPATH') || exit;

final class ToPersia_DB {
    /**
     * شماره در جدول OTP فقط به‌شکل HMAC با نمک سایت — ۱٫۱۱٫۱ · P5-D375.
     *
     * ۳۲ نویسۀ نخستِ HMAC-SHA256 (۱۲۸ بیت؛ ستون `mobile` همان varchar(32) پیشین است، پس ساختار جدول عوض نمی‌شود).
     * ورودی پیش از هر مقایسه هش می‌شود؛ شمارۀ متن ساده هرگز به جدول نمی‌رسد. پیامک به مدیانا همان شمارۀ ورودی را می‌گیرد.
     */
    public static function mobile_hash(string $mobile): string {
        return substr(hash_hmac('sha256', 'tp-otp-mobile|' . $mobile, wp_salt('auth')), 0, 32);
    }

    public static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'topersia_otp_attempts';
    }

    /**
     * جدول اختصاصی «مکان‌های ذخیره‌شده» — جایگزین آرایۀ سریال‌شده در
     * user_meta. قبلاً هر افزودن کل بلوک را بازنویسی می‌کرد؛ حالا یک سطر
     * است، با کلید یکتای (user_id, post_id) که خودِ دیتابیس از رکورد
     * تکراری جلوگیری می‌کند.
     */
    public static function saved_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'topersia_saved';
    }

    public static function activate(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $table = self::table_name();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mobile varchar(32) NOT NULL,
            purpose varchar(32) NOT NULL DEFAULT 'login',
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            request_id varchar(128) NULL,
            attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
            verified tinyint(1) unsigned NOT NULL DEFAULT 0,
            ip_hash char(64) NULL,
            PRIMARY KEY (id),
            KEY mobile_purpose (mobile,purpose),
            KEY expires_at (expires_at),
            KEY request_id (request_id)
        ) {$charset};";
        dbDelta($sql);

        $saved_table = self::saved_table_name();
        $saved_sql = "CREATE TABLE {$saved_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            post_type varchar(32) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_post (user_id, post_id),
            KEY user_id (user_id),
            KEY post_id (post_id)
        ) {$charset};";
        dbDelta($saved_sql);

        update_option('topersia_user_core_db_version', TOPERSIA_USER_CORE_VERSION, false);

        /* ۱٫۱۱٫۰ · P5-D355 — کلید پیامکِ ذخیره‌شده از autoload بیرون (ارتقا اجرای همین متد است) */
        if (function_exists('wp_set_option_autoload') && false !== get_option('topersia_mediana_api_key', false)) {
            wp_set_option_autoload('topersia_mediana_api_key', false);
        }

        if (!wp_next_scheduled('topersia_user_core_prune')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'topersia_user_core_prune');
        }
    }

    /**
     * غیرفعال‌سازی — فقط رویداد زمان‌بندی‌شده را برمی‌دارد.
     *
     * `wp_clear_scheduled_hook` هر نمونۀ ثبت‌شده از این قلاب را پاک
     * می‌کند، نه فقط نمونۀ بعدی. داده‌ها اینجا لمس نمی‌شوند: کاربری که
     * افزونه را برای رفع اشکال موقتاً خاموش می‌کند نباید مکان‌های
     * ذخیره‌شده‌اش را از دست بدهد.
     */
    public static function deactivate(): void {
        wp_clear_scheduled_hook('topersia_user_core_prune');
        wp_clear_scheduled_hook('topersia_user_core_inactivity');
    }

    /**
     * اگر ساختار جدول در نسخه‌ای عوض شود، activate دیگر اجرا نمی‌شود.
     * این مقایسه در هر بارگذاری، تغییر نسخه را می‌گیرد.
     */
    public static function maybe_upgrade(): void {
        if (get_option('topersia_user_core_db_version') === TOPERSIA_USER_CORE_VERSION) {
            return;
        }
        self::activate();
    }

    /**
     * ردیف‌های متن‌ساده‌ی پیشین را یک‌بار هش می‌کند (دسته‌ای)؛ سپس پرچم. شمارۀ ایرانی ۱۱ رقمی با ۰۹ آغاز می‌شود و
     * هش ۳۲ نویسۀ هگز است؛ پس تشخیص دوگانه‌ی نمی‌ماند. ردیف‌های منقضی را کرون پاک می‌کند.  P5-D375
     */
    public static function maybe_hash_otp_rows(): void {
        if ('1' === get_option('topersia_user_core_otp_hashed')) {
            return;
        }

        global $wpdb;
        $table = self::table_name();

        /* نام جدول در برخی میزبان‌ها (lower_case_table_names=1، ویندوز) کوچک برمی‌گردد */
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));

        if (!is_string($found) || 0 !== strcasecmp($found, $table)) {
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            $rows = (array) $wpdb->get_results('SELECT id, mobile FROM ' . $table . " WHERE mobile REGEXP '^09[0-9]{9}$' LIMIT 500", ARRAY_A);

            if (!$rows) {
                update_option('topersia_user_core_otp_hashed', '1', false);
                return;
            }

            foreach ($rows as $row) {
                $wpdb->update($table, ['mobile' => self::mobile_hash((string) $row['mobile'])], ['id' => (int) $row['id']], ['%s'], ['%d']);
            }
        }
    }

    /** کرون روزانه همیشه برقرار است (نه فقط هنگام فعال‌سازی). */
    public static function ensure_cron(): void {
        if (!wp_next_scheduled('topersia_user_core_prune')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'topersia_user_core_prune');
        }
    }

    /**
     * هرس رکوردهای منقضی.
     *
     * بدون این، جدول برای همیشه رشد می‌کند. هر دو رکورد (تأییدشده و تأییدنشده) حداکثر ۲۴ ساعت می‌مانند
     * (P5-D376؛ پیش‌تر تأییدشده ۷ روز). هیچ محدودیت نرخی پنجره‌ای بیش از یک ساعت ندارد
     * و از این جدول نمی‌خواند — ترنزینت است.
     */
    public static function prune(): void {
        global $wpdb;
        $table = self::table_name();

        $wpdb->query($wpdb->prepare(
            'DELETE FROM ' . $table . ' WHERE verified = 0 AND created_at < %s',
            gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)
        ));

        $wpdb->query($wpdb->prepare(
            'DELETE FROM ' . $table . ' WHERE verified = 1 AND created_at < %s',
            gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)
        ));

        /* ردیف منقضی: هر ردیفی که مهلتش یک ساعت پیش تمام شده، تأییدشده یا نه — P5-D375 */
        $wpdb->query($wpdb->prepare(
            'DELETE FROM ' . $table . ' WHERE expires_at < %s',
            gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS)
        ));
    }

    public static function create_attempt(string $mobile, string $purpose, string $expires_at, string $request_id = '', string $ip_hash = ''): int {
        global $wpdb;
        $wpdb->insert(self::table_name(), [
            'mobile' => self::mobile_hash($mobile),
            'purpose' => $purpose,
            'created_at' => current_time('mysql', true),
            'expires_at' => $expires_at,
            'request_id' => $request_id,
            'attempts' => 0,
            'verified' => 0,
            'ip_hash' => $ip_hash,
        ], ['%s','%s','%s','%s','%s','%d','%d','%s']);
        return (int) $wpdb->insert_id;
    }

    public static function mark_verified(int $id): void {
        global $wpdb;
        $wpdb->update(self::table_name(), ['verified' => 1], ['id' => $id], ['%d'], ['%d']);
    }

    public static function increment_attempt(int $id): void {
        global $wpdb;
        $wpdb->query($wpdb->prepare('UPDATE ' . self::table_name() . ' SET attempts = attempts + 1 WHERE id = %d', $id));
    }

    public static function latest_for_mobile(string $mobile, string $purpose): ?array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table_name() . ' WHERE mobile = %s AND purpose = %s ORDER BY id DESC LIMIT 1',
            self::mobile_hash($mobile),
            $purpose
        ), ARRAY_A);
        return $row ?: null;
    }

    /* ==================================================================
       مکان‌های ذخیره‌شده
       ================================================================== */

    /**
     * ردیف‌های خام کاربر از جدول — بدون هیچ اعتبارسنجی زندۀ پست؛ آن کار
     * به عهدۀ ToPersia_JetEngine است، چون فقط او معنای «معتبر بودن»
     * موجودیت را می‌داند.
     *
     * @return array<int,array{post_id:int,post_type:string}>
     */
    public static function saved_rows(int $user_id): array {
        global $wpdb;
        $table = self::saved_table_name();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, post_type FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        if (!$rows) return [];

        return array_map(
            static fn(array $row): array => [
                'post_id'   => (int) $row['post_id'],
                'post_type' => (string) $row['post_type'],
            ],
            $rows
        );
    }

    /**
     * افزودن یک مکان. INSERT IGNORE یعنی اگر کاربر همان مکان را دوبار
     * (حتی هم‌زمان، از دو تب) بفرستد، کلید یکتای (user_id, post_id)
     * خودِ دیتابیس از رکورد تکراری جلوگیری می‌کند — دیگر نیازی به
     * خواندن-سپس-نوشتن با ریسک رقابت (race condition) نیست.
     */
    public static function saved_add(int $user_id, int $post_id, string $post_type): void {
        global $wpdb;
        $table = self::saved_table_name();

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (user_id, post_id, post_type, created_at) VALUES (%d, %d, %s, %s)",
            $user_id,
            $post_id,
            $post_type,
            current_time('mysql', true)
        ));
    }

    public static function saved_remove(int $user_id, int $post_id): void {
        global $wpdb;
        $wpdb->delete(self::saved_table_name(), ['user_id' => $user_id, 'post_id' => $post_id], ['%d', '%d']);
    }

    /**
     * جابه‌جایی شناسۀ پست یک ردیف ذخیره‌شده — برای هم‌سان‌سازی WPML.
     *
     * کلید یکتای (user_id, post_id) یعنی اگر کاربر همان موجودیت را قبلاً
     * با شناسۀ مقصد هم ذخیره کرده باشد، UPDATE با خطای کلید تکراری شکست
     * می‌خورد. پس ترتیب درست این است: ابتدا وجود مقصد را بسنج، و اگر بود
     * ردیف مبدأ را حذف کن نه اینکه بازنویسی‌اش کنی.
     */
    public static function saved_set_post_id(int $user_id, int $old_post_id, int $new_post_id, string $post_type): void {
        global $wpdb;
        $table = self::saved_table_name();

        if ($old_post_id === $new_post_id || $old_post_id <= 0 || $new_post_id <= 0) {
            return;
        }

        $target_exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND post_id = %d",
            $user_id,
            $new_post_id
        ));

        if ($target_exists > 0) {
            self::saved_remove($user_id, $old_post_id);
            return;
        }

        $wpdb->update(
            $table,
            ['post_id' => $new_post_id, 'post_type' => $post_type],
            ['user_id' => $user_id, 'post_id' => $old_post_id],
            ['%d', '%s'],
            ['%d', '%d']
        );
    }

    public static function saved_count(int $user_id): int {
        global $wpdb;
        $table = self::saved_table_name();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $user_id));
    }

    /**
     * مهاجرت یک‌بارۀ یک کاربر از آرایۀ سریال‌شدۀ قدیمی به جدول.
     *
     * فقط وقتی کاری می‌کند که متای قدیمی هنوز وجود داشته باشد (پس روی
     * کاربرهایی که قبلاً مهاجرت شده‌اند تقریباً رایگان است: فقط یک
     * `metadata_exists` که از کش شیء وردپرس می‌آید، نه کوئری اضافه).
     * بعد از مهاجرت موفق، متای قدیمی پاک می‌شود تا دو منبع داده هم‌زمان
     * وجود نداشته باشد.
     */
    public static function maybe_migrate_saved(int $user_id): void {
        if (!metadata_exists('user', $user_id, '_topersia_saved_entities')) {
            return;
        }

        $old = get_user_meta($user_id, '_topersia_saved_entities', true);

        if (is_array($old)) {
            foreach ($old as $item) {
                $post_id = absint($item['post_id'] ?? 0);
                $type    = sanitize_key((string) ($item['post_type'] ?? ''));

                if ($post_id > 0 && $type !== '') {
                    self::saved_add($user_id, $post_id, $type);
                }
            }
        }

        delete_user_meta($user_id, '_topersia_saved_entities');
        delete_user_meta($user_id, '_topersia_saved_count');
    }
}
