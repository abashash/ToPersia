<?php

/**
 * BRAND ADDON
 */

declare(strict_types=1);

namespace Duplicator\Addons\BrandAddon;

use Duplicator\Addons\BrandAddon\Ajax\ServicesBrand;
use Duplicator\Addons\BrandAddon\Build\BrandBuildHooks;
use Duplicator\Addons\BrandAddon\Controllers\BrandSettingsController;
use Duplicator\Addons\BrandAddon\Models\BrandEntity;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Core\Bootstrap;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Models\ActivityLog\LogUtils;

/**
 * Brand addon class
 *
 * Adds installer branding: create custom brands with logos for the installer.
 */
class BrandAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * Initialize the addon
     *
     * @return void
     */
    public function init(): void
    {
        add_action('duplicator_register_types', [BrandEntity::class, 'registerType']);

        BrandSettingsController::register();

        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);

        // Build pipeline hooks
        add_filter('duplicator_archive_config', [BrandBuildHooks::class, 'filterArchiveConfig'], 10, 2);
        add_filter('duplicator_build_requirement_checks', [BrandBuildHooks::class, 'filterBuildRequirementChecks']);

        // Template data hook
        add_action('duplicator_template_after_set_from_post', [BrandSettingsController::class, 'onTemplateSetFromPost'], 10, 2);

        // UI hooks
        add_action('duplicator_backup_installer_section_before', [BrandSettingsController::class, 'renderInstallerSetupSection']);
        add_action('duplicator_package_detail_installer_rows', [BrandSettingsController::class, 'renderPackageDetailBrand']);
        add_action('duplicator_scan_system_checks', [BrandSettingsController::class, 'renderScanBrandCheck']);

        // Activity log registration
        add_action('duplicator_register_activity_log_types', [self::class, 'registerActivityLogTypes']);

        // Upgrade entity resave (after core resaveAllEntities at priority 10000)
        add_action('duplicator_upgrade', [self::class, 'resaveBrandEntities'], 10001, 4);

        // Telemetry
        add_filter('duplicator_telemetry_snapshot', [self::class, 'addTelemetryData']);

        // AJAX service
        (new ServicesBrand())->init();

        // Register addon assets
        add_action('admin_init', [self::class, 'registerJsCss']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueStyles']);
    }

    /**
     * Register styles and scripts
     *
     * @return void
     */
    public static function registerJsCss(): void
    {
        if (wp_doing_ajax()) {
            return;
        }

        $min = Bootstrap::getMinPrefix();
        wp_register_style(
            'dupli-addon-brand',
            self::getAddonUrl() . "/assets/css/brandaddon{$min}.css",
            [
                'dupli-plugin-global-style',
                'dupli-main',
            ],
            DUPLICATOR_VERSION
        );
    }

    /**
     * Enqueue CSS styles on Duplicator admin pages only
     *
     * @return void
     */
    public static function enqueueStyles(): void
    {
        if (!ControllersManager::getInstance()->isDuplicatorPage()) {
            return;
        }
        wp_enqueue_style('dupli-addon-brand');
    }

    /**
     * Get template file path for addon templates
     *
     * @param string $path    current path
     * @param string $slugTpl template slug
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'brandaddon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }
        return $path;
    }

    /**
     * Add the current number of custom brands to telemetry.
     *
     * @param array<string, mixed> $data Current snapshot
     *
     * @return array<string, mixed>
     */
    public static function addTelemetryData(array $data): array
    {
        $brands                 = BrandEntity::getAll();
        $data['branding_count'] = is_array($brands) ? count($brands) : 0;

        return $data;
    }

    /**
     * Resave all brand entities during upgrade
     *
     * @param string       $oldVariant previous variant identifier, '' on fresh install
     * @param false|string $oldVersion previous version, false on fresh install
     * @param string       $newVariant current variant
     * @param string       $newVersion current version
     *
     * @return void
     */
    public static function resaveBrandEntities($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        if (($brands = BrandEntity::getAll()) === false) {
            return;
        }
        foreach ($brands as $brand) {
            $brand->save();
        }
    }

    /**
     * Register activity log types
     *
     * @return void
     */
    public static function registerActivityLogTypes(): void
    {
        LogUtils::registerLogType(
            Models\ActivityLog\LogEventBrandCreateUpdate::getType(),
            Models\ActivityLog\LogEventBrandCreateUpdate::class
        );
        LogUtils::registerLogType(
            Models\ActivityLog\LogEventBrandDelete::getType(),
            Models\ActivityLog\LogEventBrandDelete::class
        );
    }

    /**
     * Get addon path
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     * Get addon file
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
