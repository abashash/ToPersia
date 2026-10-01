<?php

/**
 * Duplicator Backup row in table Backups list
 */

use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Package\DupPackage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 */
$package = $tplMng->getDataValueObjRequired('package', DupPackage::class);

$timeDiff = sprintf(
    _x('%s ago', '%s represents the time diff, eg. 2 days', 'duplicator-pro'),
    $package->getPackageLife('human')
);

?>
<table>
    <tr>
        <td><b><?php esc_html_e('Backup', 'duplicator-pro'); ?>:</b></td>
        <td><?php echo esc_html($package->getName()); ?></td>
    </tr>
    <tr>
        <td><b><?php esc_html_e('Created', 'duplicator-pro'); ?>:</b>&nbsp; </td>
        <td>
            <?php echo esc_html($package->getCreated()); ?>&nbsp;-&nbsp;<i><?php echo esc_html($timeDiff); ?></i>
        </td>
    </tr>
</table>