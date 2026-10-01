<?php

use Duplicator\Addons\ProBase\Core\CapLicense;
use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$renewal_url = $tplMng->getDataValueStringRequired('renewal_url');
?>
<span class='dashicons dashicons-warning'></span>
<div class="dup-sub-content">
    <h3>
        <?php printf(
            /* translators: %s: plugin name */
            esc_html__('Warning! Your %s license has expired...', 'duplicator-pro'),
            esc_html(DUPLICATOR____NAME)
        ); ?>
    </h3>
    <?php esc_html_e('You\'re currently missing:', 'duplicator-pro'); ?>
    <ul class="dupli-simple-style-disc" >
        <li><?php esc_html_e('Access to Advanced Features', 'duplicator-pro'); ?></li>
        <?php do_action('duplicator_license_expired_features_list'); ?>
        <li><?php esc_html_e('Storages Management', 'duplicator-pro'); ?></li>
        <li><?php esc_html_e('Templates Management', 'duplicator-pro'); ?></li>
        <li><?php esc_html_e('New Features', 'duplicator-pro'); ?></li>
        <li><?php esc_html_e('Important Updates for Security Patches', 'duplicator-pro'); ?></li>
        <li><?php esc_html_e('Bug Fixes', 'duplicator-pro'); ?></li>
        <li><?php esc_html_e('Support Requests', 'duplicator-pro'); ?></li>
    </ul>
    <?php if (CapMng::can(CapLicense::CAP_LICENSE, false)) { ?>
        <a class="button primary small" target="_blank" href="<?php echo esc_url($renewal_url); ?>">
            <?php esc_html_e('Renew Now!', 'duplicator-pro'); ?>
        </a>
    <?php } else { ?>
        <?php
        echo '<b>' . esc_html__(
            'Please contact the Duplicator license manager to activate it.',
            'duplicator-pro'
        ) . '</b>';
        ?>
    <?php } ?>
</div>
