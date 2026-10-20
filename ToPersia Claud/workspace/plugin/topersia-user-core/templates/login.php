<?php defined('ABSPATH') || exit;
/*
 * فرم ورود — دو کانال: پیامک موبایل ایران و کد ایمیلی.  ۱٫۱۴٫۰ · P5-D391 · LOGIN.md §۶
 *
 * ۱٫۱۰٫۰: دو متغیر اختیاری از فراخوان —
 *   $tp_redirect  نشانیِ بازگشت پس از ورود (پیش‌تر اعتبارسنجی شده)
 *   $tp_compact   درون «پنجرۀ ورود»: نشان و تیتر خودِ پنجره را دارد
 * دستگیرۀ دکمه‌ها در assets/js/user-core.js است و به شناسه‌ها درون
 * همین کارت بسته می‌شود، نه به شناسۀ سراسری.
 *
 * ۱٫۱۴٫۰: زبانه‌ها با الگوی ARIA tabs؛ پیش‌فرض از زبان صفحه، نه IP — fa ← موبایل، en و ar ← ایمیل (LOGIN.md §۶-الف)؛
 * انتخاب کاربر در localStorage (فقط ترجیح). ورودی ایمیل و شماره همیشه dir="ltr". جعبۀ کد مشترک دو کانال.
 */
$tp_redirect = isset($tp_redirect) ? (string) $tp_redirect : '';
$tp_compact  = !empty($tp_compact);
/* متن به زبان صفحه — همان واژه‌نامۀ سه‌زبانۀ پنل و پنجرۀ ورود.  ۱٫۱۰٫۳ */
$tp_t = static function (string $key): string {
    return esc_html(ToPersia_I18n::t($key));
};
$tp_method = 'fa' === ToPersia_I18n::current_language() ? 'mobile' : 'email';
$tp_on     = static function (string $m) use ($tp_method): bool { return $m === $tp_method; };
?>
<?php /* جهت از زبان صفحه، نه ثابت — پنجرۀ ورودِ صفحۀ انگلیسی چپ‌به‌راست است.  SPRINT-4.4 ۲٫۰٫۸ */ ?>
<div class="tp-auth-wrap" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
  <div class="tp-auth-card" data-tp-auth data-tp-default-method="<?php echo esc_attr($tp_method); ?>"<?php echo $tp_redirect !== '' ? ' data-tp-redirect="' . esc_url($tp_redirect) . '"' : ''; ?>>
    <?php if (!$tp_compact) : ?>
    <div class="tp-brand-large">ToPersia</div>
    <span class="tp-eyebrow">ACCOUNT</span>
    <h2><?php echo $tp_t('login_heading'); ?></h2>
    <p class="tp-muted"><?php echo $tp_t('login_intro'); ?></p>
    <?php endif; ?>
    <div class="tp-auth-tabs" role="tablist" aria-label="<?php echo $tp_t('login_methods'); ?>">
      <button type="button" role="tab" class="tp-auth-tab" id="tp-auth-tab-mobile" data-tp-method="mobile" aria-controls="tp-auth-panel-mobile" aria-selected="<?php echo $tp_on('mobile') ? 'true' : 'false'; ?>" tabindex="<?php echo $tp_on('mobile') ? '0' : '-1'; ?>"><?php echo $tp_t('login_tab_mobile'); ?></button>
      <button type="button" role="tab" class="tp-auth-tab" id="tp-auth-tab-email" data-tp-method="email" aria-controls="tp-auth-panel-email" aria-selected="<?php echo $tp_on('email') ? 'true' : 'false'; ?>" tabindex="<?php echo $tp_on('email') ? '0' : '-1'; ?>"><?php echo $tp_t('login_tab_email'); ?></button>
    </div>
    <div class="tp-auth-panel" role="tabpanel" id="tp-auth-panel-mobile" aria-labelledby="tp-auth-tab-mobile" data-tp-method-panel="mobile"<?php echo $tp_on('mobile') ? '' : ' hidden'; ?>>
      <div class="tp-field"><label for="tp-mobile"><?php echo $tp_t('mobile_number'); ?></label><input id="tp-mobile" type="tel" dir="ltr" inputmode="numeric" autocomplete="tel" placeholder="09123456789"></div>
      <button class="tp-btn" id="tp-send-otp" type="button"><?php echo $tp_t('send_code'); ?></button>
    </div>
    <div class="tp-auth-panel" role="tabpanel" id="tp-auth-panel-email" aria-labelledby="tp-auth-tab-email" data-tp-method-panel="email"<?php echo $tp_on('email') ? '' : ' hidden'; ?>>
      <div class="tp-field"><label for="tp-email"><?php echo $tp_t('email_address'); ?></label><input id="tp-email" type="email" dir="ltr" inputmode="email" autocomplete="email" autocapitalize="off" spellcheck="false" placeholder="name@example.com"></div>
      <button class="tp-btn" id="tp-send-email" type="button"><?php echo $tp_t('send_code'); ?></button>
    </div>
    <div id="tp-otp-box" class="tp-hidden">
      <div class="tp-field"><label for="tp-otp"><?php echo $tp_t('otp_code'); ?></label><input id="tp-otp" type="text" dir="ltr" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="••••••"></div>
      <button class="tp-btn" id="tp-verify-otp" type="button"><?php echo $tp_t('verify_login'); ?></button>
    </div>
    <div id="tp-auth-message" class="tp-message" aria-live="polite"></div>
  </div>
</div>
