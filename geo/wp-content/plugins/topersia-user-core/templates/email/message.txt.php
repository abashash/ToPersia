<?php defined('ABSPATH') || exit;
/*
 * نامۀ تراکنشی — متن ساده (text/plain، AltBody).  ۱٫۱۴٫۰ · P5-D391
 * همان محتوای message.php، بی پیوند. متغیر: $tp_m (ToPersia_Email_Code::compose()).
 */
$tp_m = isset($tp_m) && is_array($tp_m) ? $tp_m : [];
$tp_out = ['ToPersia', '', (string) ($tp_m['title'] ?? ''), ''];
foreach ((array) ($tp_m['lines'] ?? []) as $tp_line) {
    $tp_out[] = (string) $tp_line;
}
if (!empty($tp_m['code'])) {
    $tp_out[] = '';
    $tp_out[] = '    ' . (string) $tp_m['code'];
    $tp_out[] = '';
}
foreach ((array) ($tp_m['notes'] ?? []) as $tp_note) {
    $tp_out[] = (string) $tp_note;
}
$tp_out[] = '';
$tp_out[] = '-- ';
$tp_out[] = (string) ($tp_m['footer'] ?? '');
echo implode("\n", $tp_out) . "\n";
