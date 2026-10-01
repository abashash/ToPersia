<?php

declare(strict_types=1);

namespace Duplicator\Addons\ProBase\ComponentsPlus;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\Models\LicenseData;
use Duplicator\Addons\ProBase\ProBase;
use Duplicator\Core\Views\TplMng;
use Duplicator\Package\AbstractPackage;
use Duplicator\Package\Create\BuildComponents;

/**
 * Hooks for Package Components Plus license gating.
 *
 * ProBase ADDS the Media Only and Custom presets to the components UI.
 * When the license qualifies, presets are enabled. Otherwise, they are added but disabled + upsell shown.
 */
class ComponentsPlusHooks
{
    const COMP_ACTION_MEDIA  = 'media';
    const COMP_ACTION_CUSTOM = 'custom';

    /**
     * Register hooks
     *
     * @return void
     */
    public static function init(): void
    {
        add_filter('duplicator_component_preset_actions', [self::class, 'addPlusPresetActions']);
        add_filter('duplicator_package_components_from_input', [self::class, 'filterComponentsFromInput'], 10, 2);
        add_filter('duplicator_archive_config', [self::class, 'filterArchiveConfig'], 10, 2);
        add_action('duplicator_package_components_after_presets', [self::class, 'renderUpsellMessage']);
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);
        add_filter('duplicator_component_action_from_components', [self::class, 'filterActionFromComponents'], 10, 2);
        add_filter('duplicator_component_action_label', [self::class, 'filterActionLabel'], 10, 2);
        add_filter('duplicator_component_action_icon', [self::class, 'filterActionIcon'], 10, 2);
        add_action('duplicator_telemetry_package_build', [self::class, 'trackPackageBuild'], 10, 2);
    }

    /**
     * Add Media Only and Custom presets to the components UI.
     *
     * When license qualifies for FEATURE_PACKAGE_COMPONENTS_PLUS, presets are enabled.
     * Otherwise, they are added as disabled.
     *
     * @param array<array{value: string, disabled: bool}> $actions current preset actions
     *
     * @return array<array{value: string, disabled: bool}>
     */
    public static function addPlusPresetActions(array $actions): array
    {
        $disabled = !License::can(License::FEATURE_PACKAGE_COMPONENTS_PLUS);

        $actions[] = [
            'value'    => self::COMP_ACTION_MEDIA,
            'disabled' => $disabled,
        ];
        $actions[] = [
            'value'    => self::COMP_ACTION_CUSTOM,
            'disabled' => $disabled,
        ];

        return $actions;
    }

    /**
     * Restrict component selection when license doesn't qualify.
     *
     * Without FEATURE_PACKAGE_COMPONENTS_PLUS, only Full Site and Database Only are allowed.
     *
     * @param string[] $components parsed components from input
     * @param string[] $input      raw input array
     *
     * @return string[]
     */
    public static function filterComponentsFromInput(array $components, array $input): array
    {
        if (License::can(License::FEATURE_PACKAGE_COMPONENTS_PLUS)) {
            return $components;
        }

        if ($components === BuildComponents::COMPONENTS_DEFAULT || (count($components) === 1 && $components[0] === BuildComponents::COMP_DB)) {
            return $components;
        }

        return BuildComponents::COMPONENTS_DEFAULT;
    }

    /**
     * Inject license type into archive config descriptor.
     *
     * @param object                              $ac      archive config descriptor
     * @param \Duplicator\Package\AbstractPackage $package the package being built
     *
     * @return object
     */
    public static function filterArchiveConfig(object $ac, $package): object
    {
        $ac->license_type = LicenseData::getInstance()->getLicenseType();

        return $ac;
    }

    /**
     * Render upsell message when license doesn't qualify for components plus.
     *
     * @return void
     */
    public static function renderUpsellMessage(): void
    {
        if (License::can(License::FEATURE_PACKAGE_COMPONENTS_PLUS)) {
            return;
        }

        TplMng::getInstance()->render('componentsplusupsell/upsell_message');
    }

    /**
     * Resolve template files for this feature
     *
     * @param string $path    current template path
     * @param string $slugTpl slug of the template
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'componentsplusupsell/') === 0) {
            return ProBase::getAddonPath() . '/template/' . $slugTpl . '.php';
        }

        return $path;
    }

    /**
     * Determine component action for non-core component sets.
     *
     * @param string   $action     current action (empty if unresolved)
     * @param string[] $components the active components
     *
     * @return string
     */
    public static function filterActionFromComponents(string $action, array $components): string
    {
        if ($action !== '') {
            return $action;
        }

        if (count($components) === 1 && $components[0] === BuildComponents::COMP_UPLOADS) {
            return self::COMP_ACTION_MEDIA;
        }

        return self::COMP_ACTION_CUSTOM;
    }

    /**
     * Return label for addon component actions.
     *
     * @param string $label           current label (empty if unresolved)
     * @param string $componentAction the component action
     *
     * @return string
     */
    public static function filterActionLabel(string $label, string $componentAction): string
    {
        if ($label !== '') {
            return $label;
        }

        switch ($componentAction) {
            case self::COMP_ACTION_MEDIA:
                return __('Media Only', 'duplicator-pro');
            case self::COMP_ACTION_CUSTOM:
                return __('Custom', 'duplicator-pro');
            default:
                return $label;
        }
    }

    /**
     * Return icon for addon component actions.
     *
     * @param string $icon            current icon HTML (empty if unresolved)
     * @param string $componentAction the component action
     *
     * @return string
     */
    public static function filterActionIcon(string $icon, string $componentAction): string
    {
        if ($icon !== '') {
            return $icon;
        }

        switch ($componentAction) {
            case self::COMP_ACTION_MEDIA:
                return '<i class="fa-solid fa-images"></i>';
            case self::COMP_ACTION_CUSTOM:
                return '<i class="fa-solid fa-puzzle-piece"></i>';
            default:
                return $icon;
        }
    }

    /**
     * Track media/custom package builds in ProBase usage stats.
     *
     * @param AbstractPackage $package the built package
     * @param string          $action  the component action
     *
     * @return void
     */
    public static function trackPackageBuild(AbstractPackage $package, string $action): void
    {
        $proStats = ProComponentStats::getInstance();
        switch ($action) {
            case self::COMP_ACTION_MEDIA:
                $proStats->incrementMdOnlyCount();
                break;
            case self::COMP_ACTION_CUSTOM:
                if (
                    array_intersect(
                        $package->components,
                        [
                            BuildComponents::COMP_PLUGINS_ACTIVE,
                            BuildComponents::COMP_THEMES_ACTIVE,
                        ]
                    )
                ) {
                    $proStats->incrementCustomOnlyActiveCount();
                } else {
                    $proStats->incrementCustomCount();
                }
                break;
        }
    }
}
