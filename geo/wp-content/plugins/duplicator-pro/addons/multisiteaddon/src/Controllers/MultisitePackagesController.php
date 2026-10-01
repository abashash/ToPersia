<?php

declare(strict_types=1);

namespace Duplicator\Addons\MultisiteAddon\Controllers;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Controllers\SettingsPageController;
use Duplicator\Installer\Addons\MultisiteAddon\LicenseMultisite;
use Duplicator\Addons\ProBase\Core\CapLicense;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;
use Duplicator\Models\TemplateEntity;
use Duplicator\Package\AbstractPackage;

/**
 * Handles multisite integration on the packages page:
 * - Enables CAP_CREATE on multisite via duplicator_cap_enabled filter
 * - Adds the multisite subsite filter tab to the archive setup
 */
class MultisitePackagesController
{
    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        add_filter('duplicator_cap_enabled', [self::class, 'enableMultisiteCreate'], 10, 3);
        add_filter('duplicator_multisite_supported', [self::class, 'multisiteSupported']);
        add_filter('duplicator_multisite_unsupported_notice', [self::class, 'multisiteUnsupportedNotice']);
        add_filter('duplicator_package_setup_filter_sections', [self::class, 'addFilterSection']);
        add_action('duplicator_template_after_set_from_post', [self::class, 'onTemplateSetFromPost'], 10, 2);
        add_action('duplicator_package_set_from_template', [self::class, 'onPackageSetFromTemplate'], 10, 2);
    }

    /**
     * Mark multisite as supported when the active license includes multisite
     * capability.
     *
     * @param bool $supported Current value.
     *
     * @return bool
     */
    public static function multisiteSupported(bool $supported): bool
    {
        return $supported || LicenseMultisite::canMultisite(License::getType());
    }

    /**
     * Re-enable CAP_CREATE on multisite when license allows it.
     *
     * Only overrides the core environmental restriction (multisite default-deny),
     * never overrides explicit per-user capability denials from CapabilitiesAddon.
     *
     * @param bool   $result     Current capability result
     * @param string $cap        Capability being checked
     * @param bool   $userHasCap Whether the user passed the base WordPress capability check
     *
     * @return bool
     */
    public static function enableMultisiteCreate(bool $result, string $cap, bool $userHasCap = false): bool
    {
        if ($cap === CapMng::CAP_CREATE && $userHasCap && is_multisite() && LicenseMultisite::canMultisite(License::getType())) {
            return true;
        }

        return $result;
    }

    /**
     * Provide title, message and button data for the multisite-unsupported
     * notice based on the current license state.
     *
     * @param array{title:string, message:string, buttonUrl:string, buttonLabel:string} $data Default notice data.
     *
     * @return array{title:string, message:string, buttonUrl:string, buttonLabel:string}
     */
    public static function multisiteUnsupportedNotice(array $data): array
    {
        if (!is_multisite()) {
            return $data;
        }

        $isUnlicensed = License::getType() === AbstractLicense::TYPE_UNLICENSED;

        if ($isUnlicensed) {
            $data['title']   = __('Activate your license to enable WordPress Multisite support', 'duplicator-pro');
            $data['message'] = __(
                'Backup creation on multisite installations requires an active Duplicator license that supports WordPress Multisite.',
                'duplicator-pro'
            );
        } else {
            $data['title']   = __('Your license does not support WordPress Multisite', 'duplicator-pro');
            $data['message'] = __(
                'Upgrade your license to unlock the ability to create backups and do advanced migrations on multi-site installations!',
                'duplicator-pro'
            );
        }

        if (CapMng::can(CapLicense::CAP_LICENSE, false)) {
            if ($isUnlicensed) {
                $data['buttonUrl']   = ControllersManager::getMenuLink(
                    ControllersManager::SETTINGS_SUBMENU_SLUG,
                    SettingsPageController::L2_SLUG_GENERAL
                );
                $data['buttonLabel'] = __('Activate Your License', 'duplicator-pro');
            } else {
                $data['buttonUrl']   = License::getUpsellURL();
                $data['buttonLabel'] = __('Upgrade Now!', 'duplicator-pro');
            }
        }

        return $data;
    }

    /**
     * Add the multisite section to the Backup box sections
     *
     * @param array<string, array{label: string, class: string, callback: callable}> $sections Backup box sections
     *
     * @return array<string, array{label: string, class: string, callback: callable}>
     */
    public static function addFilterSection(array $sections): array
    {
        if (!is_multisite()) {
            return $sections;
        }

        // Insert 'multisite' after 'filters'
        $newSections = [];
        foreach ($sections as $key => $section) {
            $newSections[$key] = $section;
            if ($key === 'filters') {
                $newSections['multisite'] = [
                    'label'    => __('Multisite', 'duplicator-pro'),
                    'class'    => 'filter-mu-tab',
                    'callback' => [
                        self::class,
                        'renderFilterSectionContent',
                    ],
                ];
            }
        }

        return $newSections;
    }

    /**
     * Render the multisite section content
     *
     * @return void
     */
    public static function renderFilterSectionContent(): void
    {
        TplMng::getInstance()->render('multisiteaddon/setup/archive-filter-mu-tab');
    }

    /**
     * Save multisite filter sites to template extraData when form is submitted (manual template)
     *
     * @param TemplateEntity       $template the template being saved
     * @param array<string, mixed> $post     POST data
     *
     * @return void
     */
    public static function onTemplateSetFromPost(TemplateEntity $template, array $post): void
    {
        $filterSites = !empty($post['mu-exclude']) ? array_map('intval', $post['mu-exclude']) : [];
        $template->setExtraData('filter_sites', $filterSites);
    }

    /**
     * Populate package FilterSites from template extraData
     *
     * @param AbstractPackage $package  the package being configured
     * @param TemplateEntity  $template the source template
     *
     * @return void
     */
    public static function onPackageSetFromTemplate(AbstractPackage $package, TemplateEntity $template): void
    {
        $package->Multisite->FilterSites = $template->getExtraData('filter_sites', []);
    }
}
