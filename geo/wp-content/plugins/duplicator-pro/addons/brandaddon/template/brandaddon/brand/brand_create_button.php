<?php

use Duplicator\Addons\BrandAddon\Controllers\BrandSettingsController;
use Duplicator\Core\Controllers\ControllersManager;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$brandNewUrl = ControllersManager::getCurrentLink(
    [
        ControllersManager::QUERY_STRING_INNER_PAGE => BrandSettingsController::BRAND_INNER_PAGE_EDIT,
        'action'                                    => 'new',
    ]
);

$tipContent = __(
    'Create a new Brand.',
    'duplicator-pro'
);
?>
<span
    data-tooltip="<?php echo esc_attr($tipContent); ?>"
>
    <a
        href="<?php echo esc_url($brandNewUrl); ?>"
        id="dupli-create-new"
        class="button primary small margin-0"
    >
        <?php esc_html_e('Add New', 'duplicator-pro'); ?>
    </a>
</span>
