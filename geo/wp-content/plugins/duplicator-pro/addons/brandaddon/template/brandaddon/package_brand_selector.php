<?php

/**
 * Brand section for package setup installer area (HTML + JS)
 */

defined("ABSPATH") or die("");

use Duplicator\Addons\BrandAddon\Controllers\BrandSettingsController;
use Duplicator\Addons\BrandAddon\Core\LicenseBrand;
use Duplicator\Addons\BrandAddon\Models\BrandEntity;
use Duplicator\Core\Controllers\ControllersManager;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */
$activeBrandId = $tplMng->getDataValueInt('activeBrandId', -1);
$tipContent    = __(
    'This option changes the branding of the installer file. Click the preview button to see the selected style.',
    'duplicator-pro'
);
?>

<div class="dup-package-hdr-1">
    <?php esc_html_e("Installer Header", 'duplicator-pro') ?>
</div>
<label class="lbl-larger">
    <?php esc_html_e('Branding', 'duplicator-pro'); ?>
    <i
        class="fa-solid fa-question-circle fa-sm dark-gray-color"
        data-tooltip-title="<?php esc_attr_e("Choose Brand", 'duplicator-pro'); ?>"
        data-tooltip="<?php echo esc_attr($tipContent); ?>"></i>
</label>
<div>
    <?php
    if (LicenseBrand::can()) :
        $brands = BrandEntity::getAllWithDefault();
        ?>
        <select name="installer_opts_brand" id="brand" class="width-auto">
            <?php foreach ($brands as $i => $brand) { ?>
                <option
                    value="<?php echo (int) $brand->getId(); ?>"
                    title="<?php echo esc_attr($brand->notes); ?>"
                    <?php selected($brand->getId(), $activeBrandId); ?>>
                    <?php echo esc_html($brand->name); ?>
                </option>
            <?php } ?>
        </select>
        <?php
        if ($activeBrandId > 0) {
            $preview_url = ControllersManager::getMenuLink(
                ControllersManager::SETTINGS_SUBMENU_SLUG,
                BrandSettingsController::L2_SLUG_PACKAGE_BRAND,
                null,
                [
                    ControllersManager::QUERY_STRING_INNER_PAGE => BrandSettingsController::BRAND_INNER_PAGE_EDIT,
                    'action'                                    => 'edit',
                    'id'                                        => intval($activeBrandId),
                ]
            );
        } else {
            $preview_url = ControllersManager::getMenuLink(
                ControllersManager::SETTINGS_SUBMENU_SLUG,
                BrandSettingsController::L2_SLUG_PACKAGE_BRAND,
                null,
                [
                    ControllersManager::QUERY_STRING_INNER_PAGE => BrandSettingsController::BRAND_INNER_PAGE_EDIT,
                    'action'                                    => 'default',
                ]
            );
        }
        ?>
        &nbsp;
        <a href="<?php echo esc_url($preview_url); ?>" target="_blank" class="button hollow secondary small" id="brand-preview">
            <?php esc_html_e("Preview", 'duplicator-pro'); ?>
        </a> &nbsp;
    <?php else :
        $link = ControllersManager::getMenuLink(
            ControllersManager::SETTINGS_SUBMENU_SLUG,
            BrandSettingsController::L2_SLUG_PACKAGE_BRAND
        );
        ?>
        <a href="<?php echo esc_url($link); ?>"><?php esc_html_e("Enable Branding", 'duplicator-pro'); ?></a>
    <?php endif; ?>
</div>

<?php if (LicenseBrand::can()) : ?>
<script>
    (function($) {
        var $brand = $("#brand"),
            brandCheck = function(e) {
                var $this = $(this) || $brand;
                var $id = $this.val();
                <?php
                $prewURLs = [
                    ControllersManager::getMenuLink(
                        ControllersManager::SETTINGS_SUBMENU_SLUG,
                        BrandSettingsController::L2_SLUG_PACKAGE_BRAND,
                        null,
                        [
                            ControllersManager::QUERY_STRING_INNER_PAGE => BrandSettingsController::BRAND_INNER_PAGE_EDIT,
                            'action'                                    => 'default',
                        ]
                    ),
                    ControllersManager::getMenuLink(
                        ControllersManager::SETTINGS_SUBMENU_SLUG,
                        BrandSettingsController::L2_SLUG_PACKAGE_BRAND,
                        null,
                        [
                            ControllersManager::QUERY_STRING_INNER_PAGE => BrandSettingsController::BRAND_INNER_PAGE_EDIT,
                            'action'                                    => 'edit',
                        ]
                    ),
                ];
                ?>
                var $url = <?php echo json_encode($prewURLs); ?>;
                $url[1] += "&id=" + $id;

                $("#brand-preview").attr('href', $url[$id > 0 ? 1 : 0]);

                $this.find('option[value="' + $id + '"]')
                    .prop('selected', true)
                    .parent();
            };
        $brand.on('select change', brandCheck);

        $(document).on('duplicator:templateChanged', function(e, template) {
            var brandId = (template.extraData && template.extraData.brand_id) ? Number(template.extraData.brand_id) : -1;
            $brand.selectOption(brandId);
        });
    }(window.jQuery));
</script>
<?php endif; ?>
