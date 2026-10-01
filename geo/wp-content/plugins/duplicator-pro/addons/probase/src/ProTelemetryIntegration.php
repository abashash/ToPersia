<?php

declare(strict_types=1);

namespace Duplicator\Addons\ProBase;

use Duplicator\Addons\ProBase\ComponentsPlus\ComponentsPlusHooks;
use Duplicator\Addons\ProBase\ComponentsPlus\ProComponentStats;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\Models\LicenseData;
use Duplicator\Models\TemplateEntity;
use Duplicator\Package\Create\BuildComponents;

/**
 * Contributes Pro license identity and capabilities to core telemetry.
 */
class ProTelemetryIntegration
{
    /**
     * Register Pro telemetry filters.
     *
     * @return void
     */
    public static function register(): void
    {
        add_filter('duplicator_telemetry_identity', [self::class, 'addIdentity']);
        add_filter('duplicator_telemetry_snapshot', [self::class, 'addSnapshotFields']);
    }

    /**
     * Add the canonical Pro license identity.
     *
     * Unlicensed Pro installations report too (variant + unlicensed tier let
     * the worker measure Pro-without-license adoption). The key is omitted
     * when empty so absence is the canonical unlicensed wire shape.
     *
     * @param array<string, mixed> $identity Core site identity
     *
     * @return array<string, mixed>
     */
    public static function addIdentity(array $identity): array
    {
        $identity['plan_tier']      = License::getType();
        $identity['license_status'] = License::getLicenseStatus();
        if (License::getLicenseKey() !== '') {
            $identity['license_key'] = License::getLicenseKey();
        }

        return $identity;
    }

    /**
     * Add Pro-only license capacity and capability fields.
     *
     * @param array<string, mixed> $snapshot Core snapshot
     *
     * @return array<string, mixed>
     */
    public static function addSnapshotFields(array $snapshot): array
    {
        $canUseAdvanced                     = License::can(License::FEATURE_PACKAGE_COMPONENTS_PLUS);
        $componentStats                     = ProComponentStats::getInstance();
        $snapshot['license_limit']          = LicenseData::getInstance()->getLicenseLimit();
        $snapshot['has_custom_components']  = $canUseAdvanced;
        $snapshot['can_use_adv_components'] = $canUseAdvanced;
        $snapshot['has_storage_plus']       = License::can(License::FEATURE_STORAGE_PLUS);
        $snapshot['packages_build_comp_mdonly_count']             = $componentStats->getMdOnlyCount();
        $snapshot['packages_build_comp_custom_count']             = $componentStats->getCustomCount();
        $snapshot['packages_build_comp_custom_only_active_count'] = $componentStats->getCustomOnlyActiveCount();

        return array_merge($snapshot, self::collectAdvancedTemplateFields());
    }

    /**
     * Collect component fields whose vocabulary is registered by ProBase.
     *
     * @return array<string, int|string>
     */
    private static function collectAdvancedTemplateFields(): array
    {
        $result = [
            'templates_monly_count'              => 0,
            'templates_custom_count'             => 0,
            'templates_custom_only_active_count' => 0,
        ];

        $manualTemplate = TemplateEntity::getManualTemplate();
        $manualAction   = BuildComponents::getActionFromComponents($manualTemplate->components);
        if ($manualAction === ComponentsPlusHooks::COMP_ACTION_MEDIA) {
            $result['package_manual_create_component'] = 'mediaonly';
        } elseif ($manualAction === ComponentsPlusHooks::COMP_ACTION_CUSTOM) {
            $result['package_manual_create_component'] = self::hasActiveOnlyComponents($manualTemplate)
                ? 'custom_only_active'
                : 'custom';
        }

        $templates = TemplateEntity::getAllWithoutManualMode();
        if (!is_array($templates)) {
            return $result;
        }

        foreach ($templates as $template) {
            $action = BuildComponents::getActionFromComponents($template->components);
            if ($action === ComponentsPlusHooks::COMP_ACTION_MEDIA) {
                $result['templates_monly_count']++;
            } elseif ($action === ComponentsPlusHooks::COMP_ACTION_CUSTOM) {
                $key = self::hasActiveOnlyComponents($template)
                    ? 'templates_custom_only_active_count'
                    : 'templates_custom_count';
                $result[$key]++;
            }
        }

        return $result;
    }

    /**
     * Check whether a custom template uses active-only plugin or theme components.
     *
     * @param TemplateEntity $template Template to inspect
     *
     * @return bool
     */
    private static function hasActiveOnlyComponents(TemplateEntity $template): bool
    {
        return array_intersect(
            $template->components,
            [
                BuildComponents::COMP_PLUGINS_ACTIVE,
                BuildComponents::COMP_THEMES_ACTIVE,
            ]
        ) !== [];
    }
}
