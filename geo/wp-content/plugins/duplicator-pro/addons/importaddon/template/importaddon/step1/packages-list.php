<?php

use Duplicator\Addons\ImportAddon\Controllers\ImportPageController;
use Duplicator\Addons\ImportAddon\Package\ImportPackage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$viewMode = $tplMng->getDataValueStringRequired('viewMode');

switch ($viewMode) {
    case ImportPageController::VIEW_MODE_ADVANCED:
        $viewModeClass = 'view-list-item';
        break;
    case ImportPageController::VIEW_MODE_BASIC:
    default:
        $viewModeClass = 'view-single-item';
        break;
}

?>
<div id="dupli-import-available-packages" class="<?php echo esc_attr($viewModeClass); ?>" >
    <table class="dup-import-avail-packs packages-list">
        <thead>
            <tr>
                <th class="name"><?php esc_html_e("Backups", 'duplicator-pro'); ?></th>
                <th class="size"><?php esc_html_e("Size", 'duplicator-pro'); ?></th>
                <th class="created"><?php esc_html_e("Created", 'duplicator-pro'); ?></th>
                <th class="funcs"><?php esc_html_e("Status", 'duplicator-pro'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $importObjs = ImportPackage::getArchiveObjects();
            if (count($importObjs) === 0) {
                $tplMng->render('importaddon/step1/package-row-no-found');
            } else {
                foreach ($importObjs as $importObj) {
                    $tplMng->render(
                        'importaddon/step1/package-row',
                        [
                            'importObj' => $importObj,
                            'idRow'     => '',
                        ]
                    );
                }
            }
            ?>
        </tbody>
    </table>
    <div class="no-display" >
        <table id="dupli-import-available-packages-templates">
            <?php
            $tplMng->render(
                'importaddon/step1/package-row',
                [
                    'importObj' => null,
                    'idRow'     => 'dupli-import-row-template',
                ]
            );
            $tplMng->render('importaddon/step1/package-row-no-found');
            ?>
        </table>
    </div>
</div>