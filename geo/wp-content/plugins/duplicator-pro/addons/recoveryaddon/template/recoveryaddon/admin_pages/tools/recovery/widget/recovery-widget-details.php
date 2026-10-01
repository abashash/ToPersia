<?php



defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryController;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$recoverPackage    = $tplMng->getDataValueObj('recoverPackage', RecoveryPackage::class);
$viewMode          = $tplMng->getDataValueStringRequired('viewMode');
$subtitle          = $tplMng->getDataValueString('subtitle');
$displayInfo       = $tplMng->getDataValueBool('displayInfo');
$importFailMessage = $tplMng->getDataValueString('importFailMessage');

switch ($viewMode) {
    case RecoveryController::VIEW_WIDGET_NO_PACKAGE_SET:
        ?>
        <div class="dupli-recovery-active-link-header">
            <i class="fa-solid fa-house-fire main-icon"></i>
            <div class="main-title">
                <?php esc_html_e('Disaster Recovery Backup is Not Set', 'duplicator-pro'); ?>
            </div>
            <div class="main-subtitle margin-bottom-1">
                <b><?php esc_html_e('Backup Age:', 'duplicator-pro'); ?></b>&nbsp;
                <span class="dupli-recovery-status red"><?php _e('not set', 'duplicator-pro'); ?></span>
            </div>
        </div>
        <div class="margin-bottom-1">
            <?php
            _e(
                'A Disaster Recovery feature allows one to quickly restore the site to a prior state. 
                To use this, mark a Backup as the Disaster Recovery Backup, then copy and save off the associated Disaster Recovery URL. 
                Then, if a problem occurs, browse to the URL to launch a streamlined installer to quickly restore the site.',
                'duplicator-pro'
            );
            ?>
        </div>
        <?php
        break;
    case RecoveryController::VIEW_WIDGET_NOT_VALID:
        ?>
        <div class="orangered margin-bottom-1">
            <?php echo esc_html($importFailMessage); ?>
        </div>
        <?php
        break;
    case RecoveryController::VIEW_WIDGET_VALID:
        ?>
        <div class="dupli-recovery-active-link-wrapper" >
            <div class="dupli-recovery-active-link-header" >
                <i class="fas fa-house-fire main-icon"></i>
                <div class="main-title" >
                    <?php _e('Disaster Recovery Backup is Set', 'duplicator-pro'); ?>
                </div>
                <div class="main-subtitle margin-bottom-1" >
                    <b><?php esc_html_e('Backup Age:', 'duplicator-pro'); ?></b>&nbsp;
                    <span class="dupli-recovery-status green">
                        <?php echo esc_html($recoverPackage->getPackageLife('human')); ?>
                    </span>
                </div>
            </div>
            <?php if (strlen($subtitle)) { ?>
            <p>
                <?php echo $subtitle; ?>
            </p>
            <?php } ?>
            <?php if ($displayInfo) { ?>
                <div class="dupli-recovery-package-info margin-bottom-1" >
                    <table>
                        <tbody>
                            <tr>
                                <td><?php _e('Name', 'duplicator-pro'); ?>:</td>
                                <td><b><?php echo esc_html($recoverPackage->getPackageName()); ?></b></td>
                            </tr>
                            <tr>
                                <td><?php _e('Date', 'duplicator-pro'); ?>:</td>
                                <td><b><?php echo esc_html($recoverPackage->getCreated()); ?></b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        break;
    default:
        ?>
        <p class="orangered">
            <?php echo __('Invalid view mode.', 'duplicator-pro'); ?>
        </p>
        <?php
}
