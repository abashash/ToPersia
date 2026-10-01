<?php

use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$info   = $tplMng->getDataValueArrayRequired('info');
$pLabel = $tplMng->getDataValueString('pLabel');
?>
<p>
    <?php echo esc_html($info['desc']); ?>
</p>
<?php if (strlen($info['parent']) > 0) { ?>
    <br>
    <?php esc_html_e('Parent', 'duplicator-pro'); ?>: <b><?php echo esc_html($pLabel); ?></b>
<?php } ?>
