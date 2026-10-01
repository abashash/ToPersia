<?php

/**
 * Version Pro Base Installer addon class
 */

namespace Duplicator\Installer\Addons\ProBase;

use Duplicator\Installer\Core\Hooks\HooksMng;

/**
 * Version Pro Base Installer addon class
 *
 * @link http://snapcreek.com
 */
class ProBase extends \Duplicator\Installer\Core\Addons\InstAbstractAddonCore
{
    /**
     * Main init addon
     *
     * @return void
     */
    public function init(): void
    {
        HooksMng::getInstance()->addFilter(
            'dupx_main_header',
            fn($value): string => 'Duplicator PRO'
        );

        HooksMng::getInstance()->addFilter('duplicator_installer_license_string', fn(): string => License::getLicenseToString());
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
