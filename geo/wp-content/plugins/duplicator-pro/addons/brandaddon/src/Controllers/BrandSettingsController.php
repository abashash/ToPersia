<?php

declare(strict_types=1);

namespace Duplicator\Addons\BrandAddon\Controllers;

use Duplicator\Addons\BrandAddon\Models\ActivityLog\LogEventBrandCreateUpdate;
use Duplicator\Addons\BrandAddon\Models\ActivityLog\LogEventBrandDelete;
use Duplicator\Addons\BrandAddon\Models\BrandEntity;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Controllers\SubMenuItem;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\TemplateEntity;
use Duplicator\Utils\Logging\DupLog;

/**
 * Controller for the brand settings sub-tab
 */
class BrandSettingsController
{
    const L2_SLUG_PACKAGE_BRAND = 'brand';

    const BRAND_INNER_PAGE_LIST = 'list';
    const BRAND_INNER_PAGE_EDIT = 'edit';

    const ACTION_BRAND_SAVE   = 'save-brand';
    const ACTION_BRAND_DELETE = 'delete-brand';

    /**
     * Register hooks for settings integration
     *
     * @return void
     */
    public static function register(): void
    {
        $settingsSlug = ControllersManager::SETTINGS_SUBMENU_SLUG;
        add_filter('duplicator_sub_menu_items_' . $settingsSlug, [self::class, 'addSubMenu'], 10);
        add_action('duplicator_render_page_content_' . $settingsSlug, [self::class, 'renderContent'], 10, 2);
        add_filter('duplicator_page_actions_' . $settingsSlug, [self::class, 'pageActions']);
    }

    /**
     * Add brand sub-menu to settings page
     *
     * @param SubMenuItem[] $subMenus sub menus list
     *
     * @return SubMenuItem[]
     */
    public static function addSubMenu(array $subMenus): array
    {
        $subMenus[] = new SubMenuItem(self::L2_SLUG_PACKAGE_BRAND, __('Installer Branding', 'duplicator-pro'), '', true, 30);
        return $subMenus;
    }

    /**
     * Render brand page content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page
     *
     * @return void
     */
    public static function renderContent(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_PACKAGE_BRAND) {
            return;
        }

        switch ($innerPage) {
            case self::BRAND_INNER_PAGE_EDIT:
                $brandId = TplMng::getInstance()->getGlobalValue(
                    'actionBrandId',
                    SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'id', -1)
                );

                if ($brandId === -1) {
                    $brand = new BrandEntity();
                } elseif ($brandId === 0) {
                    $brand = BrandEntity::getDefaultBrand();
                } else {
                    $brand = BrandEntity::getById($brandId);
                }
                DupLog::trace("IS DEFAULT: " . $brand->isDefault());
                TplMng::getInstance()->render(
                    'brandaddon/brand/brand_edit',
                    ['brand' => $brand]
                );
                break;
            case self::BRAND_INNER_PAGE_LIST:
            default:
                TplMng::getInstance()->render('brandaddon/brand/brand_list');
                break;
        }
    }

    /**
     * Register page actions for brand save/delete
     *
     * @param PageAction[] $actions actions list
     *
     * @return PageAction[]
     */
    public static function pageActions(array $actions): array
    {
        $settingsSlug = ControllersManager::SETTINGS_SUBMENU_SLUG;

        $actions[] = new PageAction(
            self::ACTION_BRAND_SAVE,
            [
                self::class,
                'brandSave',
            ],
            [
                $settingsSlug,
                self::L2_SLUG_PACKAGE_BRAND,
            ],
            self::BRAND_INNER_PAGE_EDIT
        );
        $actions[] = new PageAction(
            self::ACTION_BRAND_DELETE,
            [
                self::class,
                'brandDelete',
            ],
            [
                $settingsSlug,
                self::L2_SLUG_PACKAGE_BRAND,
            ]
        );

        return $actions;
    }

    /**
     * Brand save
     *
     * @return array<string, mixed>
     */
    public static function brandSave(): array
    {
        $id    = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'id', -1);
        $brand = $id === -1 ? new BrandEntity() : BrandEntity::getById($id);

        $result = [];
        $brand->setFromInput(SnapUtil::INPUT_REQUEST);
        if ($brand->save()) {
            $result['successMessage'] = __('Brand saved.', 'duplicator-pro');
            $result['actionBrandId']  = $brand->getId();

            LogEventBrandCreateUpdate::create(
                $id === -1 ? LogEventBrandCreateUpdate::ACTION_CREATE : LogEventBrandCreateUpdate::ACTION_UPDATE,
                $brand
            );
        } else {
            $result['errorMessage'] = __('Couldn\'t save brand.', 'duplicator-pro');
        }

        return $result;
    }

    /**
     * Brand delete
     *
     * @return array<string, mixed>
     */
    public static function brandDelete(): array
    {
        if (!isset($_REQUEST['selected_id'])) {
            return ['errorMessage' => __("No Brand selected.", 'duplicator-pro')];
        }

        $brandIds = filter_var_array($_REQUEST, [
            'selected_id' => [
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => ['default' => false],
            ],
        ])['selected_id'];

        if ($brandIds === false) {
            return ['errorMessage' => __("No Brand selected.", 'duplicator-pro')];
        }

        foreach ($brandIds as $id) {
            BrandEntity::deleteById($id);
        }

        LogEventBrandDelete::create($brandIds);

        return ['successMessage' => __("Brand deleted.", 'duplicator-pro')];
    }

    /**
     * Save brand data to template extraData when form is submitted
     *
     * @param TemplateEntity       $template the template being saved
     * @param array<string, mixed> $post     POST data
     *
     * @return void
     */
    public static function onTemplateSetFromPost(TemplateEntity $template, array $post): void
    {
        $brandId = (isset($post['installer_opts_brand']) && (int) $post['installer_opts_brand'] > 0)
            ? (int) $post['installer_opts_brand']
            : -1;
        $template->setExtraData('brand_id', $brandId);
    }

    /**
     * Render brand section in package setup installer area
     *
     * @return void
     */
    public static function renderInstallerSetupSection(): void
    {
        $manualTemplate = TemplateEntity::getManualTemplate();
        $activeBrandId  = (int) $manualTemplate->getExtraData('brand_id', -1);

        TplMng::getInstance()->render(
            'brandaddon/package_brand_selector',
            ['activeBrandId' => $activeBrandId]
        );
    }

    /**
     * Render brand info in package detail view
     *
     * @param \Duplicator\Package\AbstractPackage $package the package
     *
     * @return void
     */
    public static function renderPackageDetailBrand($package): void
    {
        // Brand name is no longer stored on the package — detail display will be handled in UI phase
    }

    /**
     * Render brand scan check section
     *
     * @return void
     */
    public static function renderScanBrandCheck(): void
    {
        TplMng::getInstance()->render('brandaddon/scan_brand_check');
    }
}
