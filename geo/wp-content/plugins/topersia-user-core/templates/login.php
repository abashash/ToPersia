<?php defined('ABSPATH') || exit;
/*
 * فرم ورود با پیامک.
 *
 * ۱٫۱۰٫۰: دو متغیر اختیاری از فراخوان —
 *   $tp_redirect  نشانیِ بازگشت پس از ورود (پیش‌تر اعتبارسنجی شده)
 *   $tp_compact   درون «پنجرۀ ورود»: نشان و تیتر خودِ پنجره را دارد
 * دستگیرۀ دکمه‌ها در assets/js/user-core.js است و به شناسه‌ها درون
 * همین کارت بسته می‌شود، نه به شناسۀ سراسری.
 */
$tp_redirect = isset($tp_redirect) ? (string) $tp_redirect : '';
$tp_compact  = !empty($tp_compact);
/* متن به زبان صفحه — همان واژه‌نامۀ سه‌زبانۀ پنل و پنجرۀ ورود.  ۱٫۱۰٫۳ */
$tp_t = static function (string $key): string {
    return esc_html(ToPersia_I18n::t($key));
};
?>
<?php /* جهت از زبان صفحه، نه ثابت — پنجرۀ ورودِ صفحۀ انگلیسی چپ‌به‌راست است.  SPRINT-4.4 ۲٫۰٫۸ */ ?>
<div class="tp-auth-wrap" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
  <div class="tp-auth-card" data-tp-auth<?php echo $tp_redirect !== '' ? ' data-tp-redirect="' . esc_url($tp_redirect) . '"' : ''; ?>>
    <?php if (!$tp_compact) : ?>
    <div class="tp-brand-large">ToPersia</div>
    <span class="tp-eyebrow">ACCOUNT</span>
    <h2><?php echo $tp_t('login_heading'); ?></h2>
    <p class="tp-muted"><?php echo $tp_t('mobile_prompt'); ?></p>
    <?php endif; ?>
    <div class="tp-field"><label for="tp-mobile"><?php echo $tp_t('mobile_number'); ?></label><input id="tp-mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="09123456789"></div>
    <button class="tp-btn" id="tp-send-otp" type="button"><?php echo $tp_t('send_code'); ?></button>
    <div id="tp-otp-box" class="tp-hidden">
      <div class="tp-field"><label for="tp-otp"><?php echo $tp_t('otp_code'); ?></label><input id="tp-otp" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="••••••"></div>
      <button class="tp-btn" id="tp-verify-otp" type="button"><?php echo $tp_t('verify_login'); ?></button>
    </div>
    <div id="tp-auth-message" class="tp-message" aria-live="polite"></div>
  </div>
</div>
