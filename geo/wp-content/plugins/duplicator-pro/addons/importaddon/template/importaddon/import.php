<?php



defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */
?> 

<div class="dupli-tab-content-wrapper" >
    <div id="dupli-import-phase-one" >
        <?php $tplMng->render('importaddon/step1/import-step1'); ?>
    </div>
    <div id="dupli-import-phase-two" class="no-display" >
        <?php $tplMng->render('importaddon/import-step2'); ?>
    </div>
</div>
<?php
$tplMng->render('importaddon/import-scripts');
