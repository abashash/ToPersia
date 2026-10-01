<?php

/**
 * Version Pro Base addon class
 */

namespace Duplicator\Addons\ProBase;

// phpcs:disable
require_once __DIR__ . '/third-party/edd/EDD_SL_Plugin_Updater.php';
// phpcs:enable

use Duplicator\Addons\ProBase\ComponentsPlus\ComponentsPlusHooks;
use Duplicator\Addons\ProBase\Core\CapLicense;
use Duplicator\Addons\ProBase\StorageTeaser\UnavailableStorageTeaser;
use Duplicator\Addons\ProBase\DupCloud\DupCloudQuickConnect;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\License\LicenseNotices;
use Duplicator\Addons\ProBase\Translations\Translations;
use Duplicator\Addons\ProBase\Models\ActivityLog\LogEventLicenseActivation;
use Duplicator\Addons\ProBase\Models\ActivityLog\LogEventLicenseDeactivation;
use Duplicator\Addons\ProBase\Models\ActivityLog\LogEventLicenseKeyCleared;
use Duplicator\Addons\ProBase\Models\ActivityLog\LogEventLicenseStatusChanged;
use Duplicator\Addons\ProBase\Models\ActivityLog\LogEventLicenseVisibilityChanged;
use Duplicator\Addons\ProBase\Models\LicenseData;
use Duplicator\Core\Bootstrap;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\MigrationMng;
use Duplicator\Installer\Core\Params\PrmMng;
use Duplicator\Installer\Models\MigrateData;
use Duplicator\Models\ActivityLog\LogUtils;
use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Models\StaticGlobal;
use Duplicator\Utils\UsageStatistics\StatsBootstrap;

/**
 * Version Pro Base addon class
 *
 * @link http://snapcreek.com
 */
class ProBase extends \Duplicator\Core\Addons\AbstractAddonCore
{
    /**
     * @return void
     */
    public function init(): void
    {
        DynamicGlobalEntity::registerDefaults(['license_key_visible' => License::VISIBILITY_ALL]);

        CapLicense::register();

        add_filter('duplicator_can_view_notifications', [self::class, 'filterCanViewNotifications']);
        add_filter('duplicator_capability_labels', [self::class, 'addLicenseCapabilityLabel']);

        add_action('duplicator_register_types', [LicenseData::class, 'registerType']);
        add_action('init', [$this, 'hookInit']);

        add_action(MigrationMng::HOOK_FIRST_LOGIN_AFTER_INSTALL, function (MigrateData $migrationData): void {
            License::clearVersionCache(true);
            $licenseData = LicenseData::getInstance();
            if ($licenseData->getStatus() !== LicenseData::STATUS_VALID) {
                $licenseData->activate();
            }
        });

        add_action('duplicator_after_activation', [$this, 'onUpgradePlugin'], 10, 4);
        add_action('duplicator_upgrade', [self::class, 'applyVariantDefaultsOnSwitch'], 600, 4);
        add_action('duplicator_upgrade', [self::class, 'resaveLicenseData'], 10001, 4);
        add_action('duplicator_before_update_crypt_setting', [self::class, 'beforeCryptUpdateSettings']);
        add_action('duplicator_after_update_crypt_setting', [self::class, 'afterCryptUpdateSettings']);

        add_filter('duplicator_dynamic_data_skip_reset', function (array $skipResetData): array {
            $skipResetData[] = 'license_key_visible';
            $skipResetData[] = 'license_key_visible_pwd';
            return $skipResetData;
        });

        add_filter('duplicator_dynamic_skip_data_export', function (array $skipExportData): array {
            $skipExportData[] = 'license_key_visible';
            $skipExportData[] = 'license_key_visible_pwd';
            return $skipExportData;
        });

        add_filter('duplicator_deploy_overwrite_params', [self::class, 'addLicenseToOverwriteParams']);
        add_action('duplicator_register_activity_log_types', [$this, 'registerActivityLogTypes']);

        add_filter('duplicator_support_url', [self::class, 'getSupportUrl']);
        add_filter('duplicator_plugin_footer_links', [self::class, 'getFooterLinks']);
        add_filter('duplicator_default_usage_tracking', '__return_true');
        add_filter('duplicator_default_uninstall_cleanup', '__return_false');
        LicenseNotices::init();
        LicensingController::init();
        ComponentsPlusHooks::init();
        DupCloudQuickConnect::init();
        UnavailableStorageTeaser::init();
        ProTelemetryIntegration::register();
        (new Translations(DUPLICATOR____TEXT_DOMAIN, Translations::API_URL))->init();

        add_action('admin_init', [self::class, 'registerStyles']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueStyles']);
    }

    /**
     * Add license type to deploy overwrite params
     *
     * @param array<string, array{value: mixed, formStatus?: string}> $params overwrite params
     *
     * @return array<string, array{value: mixed, formStatus?: string}>
     */
    public static function addLicenseToOverwriteParams(array $params): array
    {
        if (isset($params[PrmMng::PARAM_OVERWRITE_SITE_DATA]['value'])) {
            $params[PrmMng::PARAM_OVERWRITE_SITE_DATA]['value']['dupLicense'] = License::getType();
        }

        return $params;
    }

    /**
     * Restrict notifications visibility to users with the License capability.
     *
     * @param bool $canView Default core decision
     *
     * @return bool
     */
    public static function filterCanViewNotifications(bool $canView): bool
    {
        if (!$canView) {
            return false;
        }
        return CapMng::can(CapLicense::CAP_LICENSE, false);
    }

    /**
     * Add the License Management label to the settings change descriptor labels.
     *
     * @param array<string,string> $labels Existing labels
     *
     * @return array<string,string>
     */
    public static function addLicenseCapabilityLabel(array $labels): array
    {
        $labels[CapLicense::CAP_LICENSE] = __('License Management', 'duplicator-pro');
        return $labels;
    }

    /**
     * Register license-related activity log types
     *
     * @return void
     */
    public function registerActivityLogTypes(): void
    {
        LogUtils::registerLogType(LogEventLicenseActivation::getType(), LogEventLicenseActivation::class);
        LogUtils::registerLogType(LogEventLicenseDeactivation::getType(), LogEventLicenseDeactivation::class);
        LogUtils::registerLogType(LogEventLicenseKeyCleared::getType(), LogEventLicenseKeyCleared::class);
        LogUtils::registerLogType(LogEventLicenseVisibilityChanged::getType(), LogEventLicenseVisibilityChanged::class);
        LogUtils::registerLogType(LogEventLicenseStatusChanged::getType(), LogEventLicenseStatusChanged::class);
    }

    /**
     * Function calle on duplicator_addons_loaded hook
     *
     * @return void
     */
    public function hookInit(): void
    {
        License::check();
    }

    /**
     * Function called on plugin upgrade
     *
     * @param string       $oldVariant previous variant identifier, '' on fresh install
     * @param false|string $oldVersion previous version, false on fresh install
     * @param string       $newVariant current variant identifier
     * @param string       $newVersion current version
     *
     * @return void
     */
    public function onUpgradePlugin($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        if ($oldVersion !== false && version_compare($oldVersion, '4.5.16-beta1', '<')) {
            $legacyKey = get_option(LicenseData::LICENSE_OLD_KEY_OPTION_NAME, '');
            if (!empty($legacyKey)) {
                LicenseData::getInstance()->setKey($legacyKey);
            }
            delete_option(LicenseData::LICENSE_OLD_KEY_OPTION_NAME);
        }
        License::clearVersionCache(false);
    }

    /**
     * Re-save LicenseData entity during upgrade
     *
     * @param string       $oldVariant previous variant identifier, '' on fresh install
     * @param false|string $oldVersion previous version, false on fresh install
     * @param string       $newVariant current variant
     * @param string       $newVersion current version
     *
     * @return void
     */
    public static function resaveLicenseData($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        LicenseData::getInstance()->save();
    }

    /**
     * Apply Pro defaults on variant switch.
     *
     * @param string       $oldVariant previous variant identifier, '' on fresh install
     * @param false|string $oldVersion previous version, false on fresh install
     * @param string       $newVariant current variant identifier
     * @param string       $newVersion current version
     *
     * @return void
     */
    public static function applyVariantDefaultsOnSwitch($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        if ($oldVariant === '' || $oldVariant === $newVariant) {
            return;
        }

        StaticGlobal::setUninstallPackageOption(false);
        StaticGlobal::setUninstallSettingsOption(false);

        StatsBootstrap::setTrackingAllowed(true);
    }

    /**
     * Before crypt update settings
     *
     * @return void
     */
    public static function beforeCryptUpdateSettings(): void
    {
        // make sure the license data is read before the settings are updated
        LicenseData::getInstance();
    }

    /**
     * After crypt update settings
     *
     * @return void
     */
    public static function afterCryptUpdateSettings(): void
    {
        LicenseData::getInstance()->save();
    }

    /**
     * @return void
     */
    public static function registerStyles(): void
    {
        if (wp_doing_ajax()) {
            return;
        }
        $min = Bootstrap::getMinPrefix();
        wp_register_style(
            'dupli-addon-probase',
            self::getAddonUrl() . "/assets/css/probase{$min}.css",
            [
                'dupli-plugin-global-style',
                'dupli-main',
            ],
            DUPLICATOR_VERSION
        );
    }

    /**
     * @return void
     */
    public static function enqueueStyles(): void
    {
        if (!ControllersManager::getInstance()->isDuplicatorPage()) {
            return;
        }
        wp_enqueue_style('dupli-addon-probase');
    }

    /**
     * Support URL for the Pro variant. Filters `duplicator_support_url` with the Pro account URL,
     * ignoring the upstream value because the Pro account destination is unconditional.
     *
     * @param string $url Incoming filter value (ignored).
     *
     * @return string
     */
    public static function getSupportUrl(string $url = ''): string
    {
        return 'https://duplicator.com/my-account/support/';
    }

    /**
     * Footer links shown in the plugin admin footer for the Pro variant.
     *
     * @param array<int, array{label: string, url: string}> $links Links accumulated by the filter.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function getFooterLinks(array $links = []): array
    {
        return array_merge($links, [
            [
                'label' => __('Support', 'duplicator-pro'),
                'url'   => self::getSupportUrl(),
            ],
            [
                'label' => __('Docs', 'duplicator-pro'),
                'url'   => 'https://duplicator.com/knowledge-base/?utm_source=WordPress&utm_campaign=proplugin&utm_medium=plugin-pro-footer',
            ],
            [
                'label' => __('Migration Services', 'duplicator-pro'),
                'url'   => 'https://duplicator.com/migration-services/',
            ],
        ]);
    }

    /**
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
