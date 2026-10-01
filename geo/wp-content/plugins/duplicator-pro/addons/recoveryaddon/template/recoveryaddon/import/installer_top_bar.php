<?php

/**
 * Recovery URL button injected into import installer top bar
 */

use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng $tplMng
 */

$recoverPackage = RecoveryPackage::getRecoverPackage();
$installLink    = ($recoverPackage instanceof RecoveryPackage) ? $recoverPackage->getInstallLink() : '';
$hasLink        = strlen($installLink) > 0;
?>
<span class="link-style no-decoration recovery-copy-top-wrapper" >
    <?php if ($hasLink) { ?>
        <span class="button secondary hollow small margin-bottom-0"
              data-tooltip-placement="right"
              data-dup-copy-value="<?php echo esc_url($installLink); ?>"
              data-dup-copy-title="<?php esc_attr_e("Copy Recovery URL to clipboard", 'duplicator-pro'); ?>"
              data-dup-copied-title="<?php esc_attr_e("Recovery URL copied to clipboard", 'duplicator-pro'); ?>" >
                  <?php esc_html_e("Copy Recovery URL", 'duplicator-pro'); ?>
        </span>
    <?php } else { ?>
        <span class="button secondary hollow disabled small margin-bottom-0">
            <i class="fas fa-exclamation-circle"></i> <?php esc_html_e("Recovery Point Not Set", 'duplicator-pro'); ?>
        </span>
    <?php } ?>
</span>
