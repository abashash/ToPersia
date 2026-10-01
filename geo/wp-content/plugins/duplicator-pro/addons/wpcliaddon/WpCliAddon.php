<?php

/**
 * Add wp cli commands
 */

namespace Duplicator\Addons\WpCliAddon;

use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Package\AbstractPackage;
use Duplicator\Views\PackageScreen;

/**
 * WP-CLI addon class
 *
 * @link http://snapcreek.com
 */
class WpCliAddon extends AbstractAddonCore
{
    /**
     * @return void
     */
    public function init(): void
    {
        $cli = new DuplicatorCli();

        add_filter('duplicator_package_origin_group', [self::class, 'resolveOriginGroup'], 10, 2);
        add_filter('duplicator_package_flags_legend', [self::class, 'addLegendEntries']);
    }

    /**
     * Resolve the origin group (icon and label) of Backups created from WP-CLI; other
     * packages pass through untouched.
     *
     * @param ?array{icon: string, label: string, details: string} $origin  Origin resolved so far
     * @param AbstractPackage                                      $package The package
     *
     * @return ?array{icon: string, label: string, details: string}
     */
    public static function resolveOriginGroup(?array $origin, AbstractPackage $package): ?array
    {
        if ($package->getExecutionType() !== DuplicatorCli::EXECUTION_TYPE_WP_CLI) {
            return $origin;
        }

        return [
            'icon'    => 'fa-solid fa-terminal',
            'label'   => esc_html__('Created from WP-CLI', 'duplicator-pro'),
            'details' => '',
        ];
    }

    /**
     * Append the WP-CLI origin entry to the Status column legend.
     *
     * @param array<int, array{icon: string, label: string, section?: string}> $entries Existing legend entries
     *
     * @return array<int, array{icon: string, label: string, section?: string}>
     */
    public static function addLegendEntries(array $entries): array
    {
        $entries[] = [
            'icon'    => '<i class="fa-solid fa-terminal"></i>',
            'label'   => __('The Backup was created from WP-CLI', 'duplicator-pro'),
            'section' => PackageScreen::FLAGS_SECTION_ORIGIN,
        ];
        return $entries;
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
