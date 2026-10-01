<?php

/**
 * Duplicator scheduled backup failed to start notification email
 *
 * Sent when a scheduled backup fails before a package is created
 * (e.g., missing template, database error, storage validation failure).
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$messageTitle     = $tplMng->getDataValueStringRequired('messageTitle');
$scheduleName     = $tplMng->getDataValueStringRequired('scheduleName');
$errorMessage     = $tplMng->getDataValueStringRequired('errorMessage');
$storageNames     = $tplMng->getDataValueArrayRequired('storageNames');
$scheduleEditLink = $tplMng->getDataValueString('scheduleEditLink');
?>
<p><?php echo esc_html($messageTitle); ?></p>
<p>
    <strong><?php esc_html_e('Schedule', 'duplicator-pro'); ?>: </strong><?php echo esc_html($scheduleName); ?><br/>
    <strong><?php esc_html_e('Date', 'duplicator-pro'); ?>: </strong><?php echo esc_html(date_i18n('Y-m-d H:i:s')); ?>
</p>
<p>
    <strong><?php esc_html_e('Error', 'duplicator-pro'); ?>: </strong><?php echo esc_html($errorMessage); ?>
</p>
<p>
    <strong><?php esc_html_e('Assigned Storages', 'duplicator-pro'); ?>: </strong>
    <?php foreach ($storageNames as $storageName) : ?>
        <br/> - <?php echo esc_html($storageName); ?>
    <?php endforeach; ?>
</p>
<?php if (!empty($scheduleEditLink)) : ?>
<p>
    <?php
    printf(
        esc_html_x(
            'To review and fix this schedule %1$sclick here%2$s.',
            '%1$s and %2$s represent the opening and closing anchor tags (<a> and </a>)',
            'duplicator-pro'
        ),
        '<a href="' . esc_url($scheduleEditLink) . '" target="_blank">',
        '</a>'
    );
    ?>
</p>
<?php endif; ?>
