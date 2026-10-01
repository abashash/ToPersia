<?php

declare(strict_types=1);

namespace Duplicator\Addons\MultisiteAddon\Controllers;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\MultisiteAddon\LicenseMultisite;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Libs\WpUtils\WpUtilsMultisite;

/**
 * Handles multisite integration in scan and build requirement checks
 */
class MultisiteScanController
{
    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        add_filter('duplicator_scan_report', [self::class, 'adjustScanReport'], 10, 2);
        add_filter('duplicator_build_requirement_checks', [self::class, 'adjustBuildRequirements']);
        add_action('duplicator_scan_setup_wordpress_multisite', [self::class, 'renderScanMultisiteSection']);
    }

    /**
     * Adjust scan report PackageIsNotImportable based on license
     *
     * @param array<string, mixed>                $report  Scan report data
     * @param \Duplicator\Package\AbstractPackage $package Package being scanned
     *
     * @return array<string, mixed>
     */
    public static function adjustScanReport(array $report, $package): array
    {
        if (!is_multisite()) {
            return $report;
        }

        $filterSites = $package->Multisite->FilterSites;

        // Recalculate subsites with filtered sites applied
        $filteredTables = ($package->Database->FilterOn ? explode(',', $package->Database->FilterTables) : []);
        $subsites       = WpUtilsMultisite::getSubsites($filterSites, $filteredTables);

        $report['ARC']['Subsites']                        = $subsites;
        $report['ARC']['Status']['HasImportableSites']    = SnapUtil::inArrayExtended(
            $subsites,
            fn($subsite): bool => count($subsite->filteredTables) === 0
        );
        $report['ARC']['Status']['HasNotImportableSites'] = SnapUtil::inArrayExtended(
            $subsites,
            fn($subsite): bool => count($subsite->filteredTables) > 0
        );

        // Populate filtered sites for display
        if (!empty($filterSites)) {
            $report['ARC']['FilteredSites']              = get_sites([
                'site__in' => $filterSites,
                'number'   => count($filterSites),
            ]);
            $report['ARC']['Status']['HasFilteredSites'] = true;
        }

        // Network status: full network backup with no exclusions
        $hasFilteredSites      = !empty($filterSites);
        $hasNotImportableSites = $report['ARC']['Status']['HasNotImportableSites'];

        $report['ARC']['Status']['Network'] = !$hasNotImportableSites && !$hasFilteredSites;

        // Adjust PackageIsNotImportable based on license
        $hasFilteredSiteTables = $report['ARC']['Status']['HasFilteredSiteTables'] ?? false;
        $hasImportableSites    = $report['ARC']['Status']['HasImportableSites'];

        $report['ARC']['Status']['PackageIsNotImportable'] = !(
            (!$hasFilteredSiteTables || $hasImportableSites) &&
            (!$hasNotImportableSites || LicenseMultisite::canMultisitePlus(License::getType()))
        );

        return $report;
    }

    /**
     * Adjust build requirement checks for multisite
     *
     * @param array<string, mixed> $checks Build requirement checks
     *
     * @return array<string, mixed>
     */
    public static function adjustBuildRequirements(array $checks): array
    {
        if (!is_multisite()) {
            return $checks;
        }

        $checks['SRV']['WP']['ismuplus'] = LicenseMultisite::canMultisitePlus(License::getType());
        $checks['SRV']['WP']['ALL']      = ($checks['SRV']['WP']['core'] ?? true) && $checks['SRV']['WP']['ismuplus'];

        return $checks;
    }

    /**
     * Render multisite section in scan WordPress details
     *
     * @return void
     */
    public static function renderScanMultisiteSection(): void
    {
        TplMng::getInstance()->render('multisiteaddon/scan/multisite-details');
    }
}
