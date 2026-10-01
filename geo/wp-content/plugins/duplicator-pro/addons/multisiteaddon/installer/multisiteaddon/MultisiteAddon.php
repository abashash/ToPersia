<?php

/**
 * Multisite Installer addon class
 */

namespace Duplicator\Installer\Addons\MultisiteAddon;

use Duplicator\Installer\Core\Addons\InstAbstractAddonCore;

class MultisiteAddon extends InstAbstractAddonCore
{
    /**
     * Main init addon
     *
     * @return void
     */
    public function init(): void
    {
        add_filter(
            'duplicator_installer_install_type_accepted_values',
            [
                MultisiteInstallTypes::class,
                'filterAcceptedValues',
            ],
            10,
            3
        );
        add_filter(
            'duplicator_installer_install_type_note',
            [
                MultisiteInstallTypes::class,
                'filterInstallTypeNote',
            ],
            10,
            2
        );
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
