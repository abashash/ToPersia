<?php

/**
 * RECOVERY ADDON
 */

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon;

use Duplicator\Addons\RecoveryAddon\Ajax\ServicesRecovery;
use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryController;
use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryDashboardController;
use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryImportController;
use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryPackagesController;
use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryToolsController;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Core\Bootstrap;
use Duplicator\Core\Controllers\ControllersManager;

/**
 * Recovery addon class
 *
 * Adds disaster recovery point management: set a backup as a pre-configured
 * recovery point for quick site restoration in case of emergency.
 */
class RecoveryAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * Called during addon discovery (before validation).
     * Define constants here so paths are available early.
     *
     * @return void
     */
    protected function onConstruct(): void
    {
        if (!defined('DUPLICATOR_RECOVER_DIR_NAME')) {
            define('DUPLICATOR_RECOVER_DIR_NAME', 'recover');
        }

        if (!defined('DUPLICATOR_RECOVER_PATH')) {
            define('DUPLICATOR_RECOVER_PATH', DUPLICATOR_SSDIR_PATH . '/' . DUPLICATOR_RECOVER_DIR_NAME);
        }

        if (!defined('DUPLICATOR_RECOVER_URL')) {
            define('DUPLICATOR_RECOVER_URL', DUPLICATOR_SSDIR_URL . '/' . DUPLICATOR_RECOVER_DIR_NAME);
        }

        if (!defined('DUPLICATOR_DISALLOW_RECOVERY')) {
            define('DUPLICATOR_DISALLOW_RECOVERY', false);
        }

        if (!defined('DUPLICATOR_RECOVERY_GUIDE_URL')) {
            define('DUPLICATOR_RECOVERY_GUIDE_URL', DUPLICATOR_BLOG_URL . 'restore-wordpress-from-backup/');
        }
    }

    /**
     * Initialize the addon
     *
     * @return void
     */
    public function init(): void
    {
        // Page controllers
        RecoveryToolsController::register();
        RecoveryPackagesController::register();
        RecoveryImportController::register();
        RecoveryDashboardController::register();

        // Recovery info on template/schedule pages
        RecoveryController::registerRecoverableInfoHooks();

        // Storage select list — append "Recovery Point Capable" badge on local rows
        add_filter('duplicator_storage_select_list_type_suffix', [RecoveryController::class, 'filterStorageSelectListTypeSuffix'], 10, 2);

        // Template resolution
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);

        // Migration — reset recovery point after migration
        add_action('duplicator_after_migration_settings_update', [RecoveryPackage::class, 'resetOnMigration']);

        // Migration — protect recovery paths from cleanup
        add_filter('duplicator_is_protected_path', [RecoveryPackage::class, 'filterIsProtectedPath'], 10, 2);

        // Storage — add recovery paths for index.php protection
        add_filter('duplicator_index_protected_paths', [RecoveryPackage::class, 'filterIndexProtectedPaths']);

        // Restore — refresh recovery package after restore
        add_action('duplicator_after_restore_flags_update', [RecoveryPackage::class, 'refreshAfterRestore']);

        // Help context for recovery tab
        add_filter('duplicator_help_tools_context', [RecoveryToolsController::class, 'filterHelpContext'], 10, 3);

        // Data filters for other addons (ImportAddon, ProBase)
        add_filter('duplicator_recovery_package_id', [RecoveryPackage::class, 'getRecoverPackageId']);
        add_filter('duplicator_recovery_package_data', [RecoveryController::class, 'filterRecoveryPackageData']);
        add_filter('duplicator_recovery_page_link', [RecoveryController::class, 'getRecoverPageLink']);
        add_filter('duplicator_telemetry_snapshot', [self::class, 'addTelemetryData']);

        // AJAX service
        (new ServicesRecovery())->init();

        // Register addon assets
        add_action('admin_init', [self::class, 'registerJsCss']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueStyles']);
    }

    /**
     * Add recovery-point state to telemetry.
     *
     * @param array<string, mixed> $data Current snapshot
     *
     * @return array<string, mixed>
     */
    public static function addTelemetryData(array $data): array
    {
        $data['is_recovery_point_set'] = RecoveryPackage::getRecoverPackageId() !== false;
        return $data;
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
            'dupli-addon-recovery',
            self::getAddonUrl() . "/assets/css/recoveryaddon{$min}.css",
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
        wp_enqueue_style('dupli-addon-recovery');
    }

    /**
     * Return template file path for addon templates
     *
     * @param string $path    Current path
     * @param string $slugTpl Template slug
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'recoveryaddon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }

        return $path;
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
