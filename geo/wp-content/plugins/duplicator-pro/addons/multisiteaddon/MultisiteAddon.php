<?php

/**
 * MULTISITE ADDON
 */

declare(strict_types=1);

namespace Duplicator\Addons\MultisiteAddon;

use Duplicator\Addons\MultisiteAddon\Controllers\MultisitePackagesController;
use Duplicator\Addons\MultisiteAddon\Controllers\MultisiteScanController;
use Duplicator\Core\Addons\AbstractAddonCore;

class MultisiteAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * Initialize addon
     *
     * @return void
     */
    public function init(): void
    {
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);

        MultisitePackagesController::register();
        MultisiteScanController::register();
    }

    /**
     * Resolve addon template files
     *
     * @param string $path    Default template path
     * @param string $slugTpl Template slug
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'multisiteaddon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }

        return $path;
    }

    /**
     * Get addon main folder
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     * Get addon main file
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
