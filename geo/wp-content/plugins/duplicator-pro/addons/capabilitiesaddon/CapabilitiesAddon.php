<?php

/**
 * CAPABILITIES ADDON
 */

declare(strict_types=1);

namespace Duplicator\Addons\CapabilitiesAddon;

use Duplicator\Addons\CapabilitiesAddon\Ajax\ServicesCapabilities;
use Duplicator\Addons\CapabilitiesAddon\Controllers\CapabilitiesSettingsController;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Addons\CapabilitiesAddon\CapabilitiesUsageStats;

/**
 * Capabilities addon class
 *
 * Adds advanced capability management: assign capabilities to custom roles and individual users.
 */
class CapabilitiesAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * Initialize the addon
     *
     * @return void
     */
    public function init(): void
    {
        CapMngAdvanced::register();
        CapabilitiesSettingsController::register();
        CapabilitiesUsageStats::register();

        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);

        (new ServicesCapabilities())->init();
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
        if (strpos($slugTpl, 'capabilitiesaddon/') === 0) {
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
