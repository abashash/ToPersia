<?php

/**
 * Duplicator schedule success mail
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$messageTitle = $tplMng->getDataValueStringRequired('messageTitle');
$packageName  = $tplMng->getDataValueStringRequired('packageName');
$packageID    = $tplMng->getDataValueIntRequired('packageID');
$scheduleName = $tplMng->getDataValueStringRequired('scheduleName');
$success      = $tplMng->getDataValueBool('success');
$fileCount    = $tplMng->getDataValueString('fileCount');
$packageSize  = $tplMng->getDataValueString('packageSize');
$tableCount   = $tplMng->getDataValueString('tableCount');
$sqlSize      = $tplMng->getDataValueString('sqlSize');
$storageNames = $tplMng->getDataValueArrayRequired('storageNames');
$packagesLink = $tplMng->getDataValueStringRequired('packagesLink');
?>
<p><?php echo esc_html($messageTitle) ?></p>
<p>
    <strong><?php esc_html_e('Backup Name', 'duplicator-pro') ?>: </strong><?php echo esc_html($packageName); ?><br/>
    <strong><?php esc_html_e('Backup ID', 'duplicator-pro') ?>: </strong><?php echo (int) $packageID; ?><br/>
    <strong><?php esc_html_e('Date', 'duplicator-pro') ?>: </strong><?php echo esc_html(date_i18n('Y-m-d H:i:s')); ?><br/>
    <strong><?php esc_html_e('Schedule', 'duplicator-pro') ?>: </strong><?php echo esc_html($scheduleName); ?>
</p>

<?php if ($success) : ?>
<p>
    <strong><?php esc_html_e('Number of Files', 'duplicator-pro') ?>: </strong><?php echo esc_html($fileCount); ?><br/>
    <strong><?php esc_html_e('Backup size', 'duplicator-pro') ?>: </strong><?php echo esc_html($packageSize); ?>
</p>
<p>
    <strong><?php esc_html_e('Number of tables', 'duplicator-pro') ?>: </strong><?php echo esc_html($tableCount); ?><br/>
    <strong><?php esc_html_e('DB dump size', 'duplicator-pro') ?>: </strong><?php echo esc_html($sqlSize); ?>
</p>
<?php else : ?>
<p>
    <?php esc_html_e(
        'Diagnostic details are available only inside Duplicator. Open the Backups screen to review the failure.',
        'duplicator-pro'
    ); ?>
</p>
<?php endif; ?>

<p>
    <strong><?php esc_html_e('Storages', 'duplicator-pro') ?>: </strong>
    <?php foreach ($storageNames as $storageName) : ?>
        <br/> - <?php echo esc_html($storageName); ?>
    <?php endforeach; ?>
</p>
<p>
    <?php
        printf(
            esc_html_x(
                'To go to the "Backups" screen %1$sclick here%2$s.',
                '%1$s and %2$s represent the opening and closing anchor tags (<a> and </a>)',
                'duplicator-pro'
            ),
            '<a href="' . esc_url($packagesLink) . '" target="_blank">',
            '</a>'
        );
        ?>
</p>
