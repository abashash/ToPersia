<?php

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon;

use Duplicator\Addons\ImportAddon\Ajax\ServicesImport;
use Duplicator\Addons\ImportAddon\Controllers\ImportInstallerPageController;
use Duplicator\Addons\ImportAddon\Controllers\ImportPageController;
use Duplicator\Addons\ImportAddon\Controllers\ImportSettingsController;
use Duplicator\Addons\ImportAddon\Core\CapImport;
use Duplicator\Addons\ImportAddon\Models\ActivityLog\LogEventImportSettingsChange;
use Duplicator\Addons\ImportAddon\Package\ImportPackage;
use Duplicator\Addons\ImportAddon\RESTPoints\SubsiteActions;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Core\Bootstrap;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\REST\AbstractRESTPoint;
use Duplicator\Models\ActivityLog\LogUtils;
use Duplicator\Models\DynamicGlobalEntity;

/**
 * Import addon class
 *
 * Adds backup import support: upload/download archives and deploy
 * them via the built-in installer for site restoration.
 */
class ImportAddon extends AbstractAddonCore
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
        if (!defined('DUPLICATOR_DISALLOW_IMPORT')) {
            define('DUPLICATOR_DISALLOW_IMPORT', false);
        }
    }

    /**
     * Initialize the addon
     *
     * @return void
     */
    public function init(): void
    {
        DynamicGlobalEntity::registerDefaults(['import_chunk_size' => DUPLICATOR_DEFAULT_CHUNK_UPLOAD_SIZE]);

        CapImport::register();
        ImportSettingsController::register();

        // Page registration
        add_filter('duplicator_menu_pages', [$this, 'addMenuPages']);

        // Template resolution
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);

        // Packages toolbar: import button
        add_action('duplicator_packages_toolbar_buttons', [ImportPageController::class, 'renderImportToolbarButton']);

        // Help context
        add_filter('duplicator_help_page_context', [ImportPageController::class, 'addHelpContext'], 10, 3);

        // Capability labels
        add_filter('duplicator_capability_labels', [CapImport::class, 'addCapabilityLabels']);

        // Activity log
        add_action('duplicator_register_activity_log_types', [self::class, 'registerLogTypes']);

        // Deploy settings URL (used by recovery to link to custom path settings)
        add_filter('duplicator_deploy_settings_url', [ImportSettingsController::class, 'getSettingsUrl']);

        // Recovery detail options when set from import page
        add_filter('duplicator_recovery_detail_options', [ImportPageController::class, 'getRecoveryDetailOptions'], 10, 2);

        // REST endpoints
        add_filter('duplicator_endpoints', [self::class, 'addRestEndpoints']);

        // Disable import capability on staging sites
        add_filter('duplicator_staging_disabled_caps', [CapImport::class, 'addStagingDisabledCaps']);

        // Add import custom path to deploy folders
        add_filter('duplicator_deploy_folders_to_check', [ImportPackage::class, 'addImportCustomPath']);

        // AJAX services
        (new ServicesImport())->init();

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
            'dupli-addon-import',
            self::getAddonUrl() . "/assets/css/importaddon{$min}.css",
            [
                'dupli-plugin-global-style',
                'dupli-main',
            ],
            DUPLICATOR_VERSION
        );

        wp_register_script(
            'dupli-import-installer',
            self::getAddonUrl() . '/assets/js/import-installer.js',
            [
                'jquery',
                'dupli-vendor-bundle',
            ],
            DUPLICATOR_VERSION,
            true
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
        wp_enqueue_style('dupli-addon-import');
    }

    /**
     * Add import pages to the menu
     *
     * @param AbstractMenuPageController[] $pages Menu pages
     *
     * @return AbstractMenuPageController[]
     */
    public function addMenuPages(array $pages): array
    {
        $pages[] = ImportPageController::getInstance();
        if (($pageSlug = ImportPageController::getInstance()->getMenuHookSuffix()) !== false) {
            add_action('admin_print_scripts-' . $pageSlug, [Bootstrap::class, 'enqueueScripts']);
            add_action('admin_print_styles-' . $pageSlug, [Bootstrap::class, 'enqueueStyles']);
        }

        $pages[] = ImportInstallerPageController::getInstance();

        return $pages;
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
        if (strpos($slugTpl, 'importaddon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }

        return $path;
    }

    /**
     * Register import-specific activity log types
     *
     * @return void
     */
    public static function registerLogTypes(): void
    {
        LogUtils::registerLogType(
            LogEventImportSettingsChange::getType(),
            LogEventImportSettingsChange::class
        );
    }

    /**
     * Add import REST endpoints
     *
     * @param AbstractRESTPoint[] $endpoints REST endpoints
     *
     * @return AbstractRESTPoint[]
     */
    public static function addRestEndpoints(array $endpoints): array
    {
        $endpoints[] = new SubsiteActions();

        return $endpoints;
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
