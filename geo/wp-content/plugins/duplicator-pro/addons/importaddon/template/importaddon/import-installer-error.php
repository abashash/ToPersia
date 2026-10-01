<?php



defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

?>
<div class="wrap">
    <h1>
        <?php esc_html_e("Install Backup error", 'duplicator-pro'); ?>
    </h1>
    <p>
        <?php esc_html_e("Error on Backup prepare, please go back and try again.", 'duplicator-pro'); ?>
    </p>
</div>
