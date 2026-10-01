<?php

/**
 * Recovery custom path fields for import settings page
 */

use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng $tplMng
 */
?>

<h3 class="title"><?php esc_html_e('Recovery', 'duplicator-pro') ?> </h3>
<hr size="1" />

<label class="lbl-larger" for="input_recovery_custom_path">
    <?php esc_html_e("Recovery custom path", 'duplicator-pro'); ?>
</label>
<div class="margin-bottom-1">
    <input
        class="width-xxlarge margin-bottom-0"
        type="text"
        name="recovery_custom_path"
        id="input_recovery_custom_path"
        value="<?php echo esc_attr(RecoveryPackage::getRecoveryCustomPath()); ?>"
        placeholder="">
    <p class="description">
        <?php
        esc_html_e(
            "Setting a custom path changes the location the recovery points are generated.",
            'duplicator-pro'
        );
        ?>
    </p>
</div>
