<?php



defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$blur                       = $tplMng->getDataValueBool('blur');
$adminMessageViewModeSwtich = $tplMng->getDataValueBool('adminMessageViewModeSwtich');

if ($adminMessageViewModeSwtich && !$blur) {
    $tplMng->render('importaddon/step1/message-view-mode-switch');
}

?>

<div class="dupli-import-header" >
    <h2 class="title">
        <b>
            <?php printf(esc_html__("Step %s of 2: Upload Backup", 'duplicator-pro'), '<span class="red">1</span>'); ?>
        </b>
    </h2>
</div>

<div class="dup-import-header-content-wrapper <?php echo ($blur ? 'dup-mock-blur' : ''); ?>" >
    <?php $tplMng->render('importaddon/step1/add-file-area'); ?>
    <?php $tplMng->render('importaddon/step1/packages-list'); ?>
</div>


