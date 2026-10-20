<?php defined('ABSPATH') || exit;
/*
 * نامۀ تراکنشی — HTML سبک.  ۱٫۱۴٫۰ · P5-D391 · LOGIN.md §۳-پ
 *
 * یک جدول، CSS درون‌خطی، بی تصویر، بی پیکسل، بی هیچ پیوند (نشانی سایت فقط متن)، بی نام کاربر یا شماره.
 * کد با رقم لاتین و dir="ltr". جهت از زبان نامه: fa و ar راست‌چین.
 *
 * متغیرها از ToPersia_Email_Code::compose():
 *   $tp_m = array{lang:string, dir:string, title:string, lines:string[], code:string, notes:string[], footer:string}
 */
$tp_m = isset($tp_m) && is_array($tp_m) ? $tp_m : [];
$tp_dir = 'rtl' === ($tp_m['dir'] ?? 'rtl') ? 'rtl' : 'ltr';
$tp_align = 'rtl' === $tp_dir ? 'right' : 'left';
$tp_font = 'ltr' === $tp_dir ? 'Arial,Helvetica,sans-serif' : 'Tahoma,Arial,sans-serif';
?><!doctype html>
<html lang="<?php echo esc_attr($tp_m['lang'] ?? 'fa'); ?>" dir="<?php echo esc_attr($tp_dir); ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title><?php echo esc_html($tp_m['title'] ?? ''); ?></title></head>
<body style="margin:0;padding:0;background:#f4f1ea;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ea;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" dir="<?php echo esc_attr($tp_dir); ?>" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #e2dccf;border-radius:12px;font-family:<?php echo esc_attr($tp_font); ?>;color:#1f1d1a;text-align:<?php echo esc_attr($tp_align); ?>;">
<tr><td style="padding:24px 28px 8px;font-size:20px;font-weight:bold;">ToPersia</td></tr>
<tr><td style="padding:0 28px 8px;font-size:17px;font-weight:bold;"><?php echo esc_html($tp_m['title'] ?? ''); ?></td></tr>
<?php foreach ((array) ($tp_m['lines'] ?? []) as $tp_line) : ?>
<tr><td style="padding:4px 28px;font-size:15px;line-height:1.8;"><?php echo esc_html($tp_line); ?></td></tr>
<?php endforeach; ?>
<?php if (!empty($tp_m['code'])) : ?>
<tr><td style="padding:16px 28px;" align="center"><span dir="ltr" style="display:inline-block;font-family:Consolas,'Courier New',monospace;font-size:32px;font-weight:bold;word-spacing:4px;padding:12px 20px;border:1px solid #c9bfa9;border-radius:8px;background:#faf7f0;color:#1f1d1a;"><?php echo esc_html($tp_m['code']); ?></span></td></tr>
<?php endif; ?>
<?php foreach ((array) ($tp_m['notes'] ?? []) as $tp_note) : ?>
<tr><td style="padding:4px 28px;font-size:13px;line-height:1.8;color:#4a463f;"><?php echo esc_html($tp_note); ?></td></tr>
<?php endforeach; ?>
<tr><td style="padding:16px 28px 24px;font-size:12px;line-height:1.7;color:#4a463f;border-top:1px solid #eee7da;"><?php echo esc_html($tp_m['footer'] ?? ''); ?></td></tr>
</table>
</td></tr>
</table>
</body>
</html>
