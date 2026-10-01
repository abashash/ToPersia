<?php

/**
 * Duplicator Backup row in table Backups list
 */

use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 */

$recoverPackage = $tplMng->getDataValueObjRequired('recoverPackage', RecoveryPackage::class);
?><!DOCTYPE html>
<html lang="en-US" >
    <head>
        <title><?php esc_html_e('Recovery Backup Launcher', 'duplicator-pro'); ?></title>
    </head>
    <body>
        <h2><?php printf(esc_html__('Recovery Backup Launcher created on %s', 'duplicator-pro'), esc_html($recoverPackage->getCreated())); ?></h2>
        <p>
            <?php
            printf(
                esc_html_x(
                    'If the installer does not start automatically, you can click on this %1$slink and start it manually%2$s.',
                    '%1$s and %2$s represent the opening and closing link tags (<a> and </a>)',
                    'duplicator-pro'
                ),
                '<a href="' . esc_url($recoverPackage->getInstallLink()) . '">',
                '</a>'
            );
            ?>
        </p>
        <script>
            window.location.href = <?php echo json_encode($recoverPackage->getInstallLink()); ?>;
        </script>
    </body>
</html>