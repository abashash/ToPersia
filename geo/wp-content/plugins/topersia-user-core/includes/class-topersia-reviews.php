<?php
defined('ABSPATH') || exit;

/**
 * پل فقط-خواندنی به جدول‌های افزونه JetReviews (Crocoblock).
 *
 * این کلاس هیچ داده‌ای در جدول‌های JetReviews نمی‌نویسد و هیچ فرم ثبت/ویرایش
 * نظری ارائه نمی‌دهد؛ ثبت و مدیریت نظر همچنان کاملاً به عهده خود JetReviews
 * است (فرم‌ها، AJAX، REST). کار این کلاس فقط این است: نظرات هر کاربر و
 * پاسخ‌هایی که دیگران به آن‌ها داده‌اند را برای نمایش در پنل ToPersia واکشی کند.
 *
 * اگر JetReviews نصب/فعال نباشد، جدول‌ها وجود نخواهند داشت و available()
 * به‌سادگی false برمی‌گرداند؛ بقیه متدها هم آرایه خالی می‌دهند، نه خطا.
 */
final class ToPersia_Reviews {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    private static function table(string $key): string {
        global $wpdb;
        // پیشوند «jet_» دقیقاً همان چیزی‌ست که خود JetReviews در
        // includes/db/manager.php برای نام‌گذاری جدول‌هایش استفاده می‌کند.
        return $wpdb->prefix . 'jet_' . $key;
    }

    public static function available(): bool {
        static $cached = null;
        if ($cached !== null) return $cached;

        global $wpdb;
        $table = self::table('reviews');
        /* در LIKE، نویسۀ `_` خودش یک وایلدکارت است که با هر نویسه جور
           می‌شود. نام جدول ما (jwWSU9v_jet_reviews) دو تا از این‌ها دارد، پس
           مقایسه می‌توانست جدولی غیر از مقصود را پیدا کند. کم‌احتمال، ولی
           تشخیصِ خرابِ ناشی از آن دوازده ساعت وقت می‌برد. */
        $found = $wpdb->get_var($wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $wpdb->esc_like($table)
        ));

        /* بی‌حساسیت به حروف.  ۱٫۱۰٫۰ · P5-D234
           MySQL با lower_case_table_names=1 (ویندوز، و برخی میزبان‌ها) نام را
           کوچک برمی‌گرداند — `jwwsu9v_jet_reviews` برای پیشوند `jwWSU9v_` —
           و `===` جدولی را که هست «نیست» می‌دید؛ پنل نظرها خالی می‌ماند. */
        return $cached = (is_string($found) && $found !== '' && strcasecmp($found, $table) === 0);
    }

    /**
     * منتشرشده و بی‌گذرواژه — همان قاعدۀ ToPersia_JetEngine::is_live().  P5-D247
     */
    private static function entity_live(int $post_id): bool {
        if (class_exists('ToPersia_JetEngine')) {
            return ToPersia_JetEngine::is_live($post_id);
        }

        $post = $post_id > 0 ? get_post($post_id) : null;

        return $post && $post->post_status === 'publish' && (string) $post->post_password === '';
    }

    private static function is_approved($value): bool {
        return ((int) $value) > 0;
    }

    private static function resolve_author_name(int $user_id): string {
        if ($user_id <= 0) return ToPersia_I18n::t('reviews_guest_author', 'کاربر مهمان');
        $user = get_userdata($user_id);
        if (!$user) return ToPersia_I18n::t('reviews_deleted_author', 'کاربر حذف‌شده');
        return $user->display_name ?: $user->user_login;
    }

    /**
     * تمام نظرات کاربر جاری، هرکجای سایت که ثبت شده باشد، به‌همراه
     * پاسخ‌های تأییدشده‌ای که دیگران (نه خودش) به آن نظرات داده‌اند.
     *
     * @return array<int,array<string,mixed>>
     */
    public function my_reviews(int $user_id, int $limit = 100): array {
        if ($user_id <= 0 || !self::available()) return [];

        global $wpdb;
        $reviews_table = self::table('reviews');

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, post_id, post_type, title, content, rating, approved, date
             FROM {$reviews_table}
             WHERE author = %s
             ORDER BY date DESC
             LIMIT %d",
            /* ستون author در jet_reviews از نوع varchar(255) است. با %d، MySQL
               مجبور می‌شد خودِ ستون را به عدد تبدیل کند — و تبدیل روی
               ستون، ایندکس را غیرقابل‌استفاده می‌کند و کوئری به پیمایش
               کامل جدول می‌افتد. روی جدول کوچک دیده نمی‌شود؛ روی چند صد
               هزار نقد مرگ‌آور است. */
            (string) $user_id,
            max(1, $limit)
        ), ARRAY_A);

        if (!$rows) return [];

        $review_ids = array_map(static fn(array $row): int => (int) $row['id'], $rows);
        $replies_by_review = $this->replies_for_reviews($review_ids, $user_id);

        $out = [];
        foreach ($rows as $row) {
            $review_id = (int) $row['id'];
            $post_id = (int) $row['post_id'];
            /* نظر می‌ماند؛ ولی پستی که دیگر منتشرشده نیست — پیش‌نویس، خصوصی،
               در انتظار، رمزدار، زباله یا حذف‌شده — نه عنوانش می‌آید نه پیوندش.
               پنل کاربر چیزی را نشان نمی‌دهد که صفحۀ عمومی نشان نمی‌دهد.  P5-D247 */
            $live = self::entity_live($post_id);

            $out[] = [
                'id'         => $review_id,
                'post_id'    => $post_id,
                'post_title' => $live
                    ? (class_exists('ToPersia_JetEngine') ? ToPersia_JetEngine::display_name($post_id) : (string) get_the_title($post_id))
                    : ToPersia_I18n::t('reviews_unavailable_entity', 'این مطلب دیگر در دسترس نیست'),
                'post_url'   => $live ? (string) get_permalink($post_id) : '',
                'title'      => (string) $row['title'],
                'content'    => (string) $row['content'],
                'rating'     => (float) $row['rating'],
                'approved'   => self::is_approved($row['approved']),
                'date'       => (string) $row['date'],
                'replies'    => $replies_by_review[$review_id] ?? [],
            ];
        }

        return $out;
    }

    /**
     * پاسخ‌های تأییدشده روی مجموعه‌ای از نظرات، به‌جز پاسخ‌هایی که
     * خودِ صاحب نظر داده (که «پاسخ دیگران» محسوب نمی‌شود).
     *
     * @param array<int,int> $review_ids
     * @return array<int,array<int,array<string,mixed>>> کلید = review_id
     */
    private function replies_for_reviews(array $review_ids, int $owner_user_id): array {
        $review_ids = array_values(array_unique(array_filter(array_map('absint', $review_ids))));
        if (!$review_ids || !self::available()) return [];

        global $wpdb;
        $comments_table = self::table('review_comments');
        $placeholders = implode(',', array_fill(0, count($review_ids), '%d'));

        $sql = "SELECT id, review_id, author, content, date, approved
                FROM {$comments_table}
                WHERE review_id IN ({$placeholders})
                ORDER BY date ASC";

        $rows = $wpdb->get_results($wpdb->prepare($sql, $review_ids), ARRAY_A);
        if (!$rows) return [];

        $grouped = [];
        foreach ($rows as $row) {
            if (!self::is_approved($row['approved'])) continue;

            $author_id = (int) $row['author'];
            if ($author_id === $owner_user_id) continue; // پاسخ به نظر خودش، نه از طرف دیگران

            $grouped[(int) $row['review_id']][] = [
                'id'          => (int) $row['id'],
                'author_name' => self::resolve_author_name($author_id),
                'content'     => (string) $row['content'],
                'date'        => (string) $row['date'],
            ];
        }

        return $grouped;
    }

    /* replies_received_count() حذف شد: هیچ‌جا فراخوانده نمی‌شد، و کلِ
       my_reviews() — یعنی دو کوئری و یک حلقۀ کامل — را فقط برای یک عدد
       دوباره اجرا می‌کرد. پنل همین عدد را از روی همان فهرستی که قبلاً
       گرفته، در ToPersia_Dashboard می‌شمارد. */
}
