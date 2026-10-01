<?php

defined("ABSPATH") or die("");

use Duplicator\Controllers\SettingsPageController;
?>
<b><?php esc_html_e('How backup build steps are chained together.', 'duplicator-pro'); ?></b>
<br><br>
<b><?php esc_html_e('Disabled (server-side)', 'duplicator-pro'); ?></b>:<br>
<?php esc_html_e(
    'The server triggers each step automatically. Fastest and most reliable.',
    'duplicator-pro'
); ?>
<br><br>
<b><?php esc_html_e('Enabled (client-side)', 'duplicator-pro'); ?></b>:<br>
<?php esc_html_e(
    'The browser triggers each step. Slower, and you must keep the page open during the entire backup.',
    'duplicator-pro'
); ?>
<br><br>
<?php esc_html_e(
    'Client-side kickoff activates when the server cannot reach its own admin-ajax endpoint (loopback failure).
    Backups still work but are slower.',
    'duplicator-pro'
); ?><br><br>
<?php esc_html_e(
    'Check your hosting firewall or loopback settings to restore full speed.',
    'duplicator-pro'
); ?><br>
<?php printf(
    esc_html_x(
        'This setting can be overridden in %1$sBackup Settings%2$s.',
        '%1$s and %2$s are opening and closing anchor tags',
        'duplicator-pro'
    ),
    '<a href="' . esc_url(SettingsPageController::getInstance()->getMenuLink(SettingsPageController::L2_SLUG_PACKAGE)) . '">',
    '</a>'
); ?>
