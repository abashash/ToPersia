<?php

declare(strict_types=1);

namespace Duplicator\Addons\StagingAddon;

use Duplicator\Utils\UsageStatistics\Telemetry\TelemetryEvents;
use Duplicator\Addons\StagingAddon\Models\StagingEntity;

/**
 * Bridges StagingAddon into the core telemetry pipeline.
 *
 * Core has no compile-time view of staging entities; emitting the events here
 * keeps the dependency one-way (staging -> core, never the reverse).
 */
class StagingTelemetryIntegration
{
    /**
     * Register hooks. Called from StagingAddon::init().
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('duplicator_staging_ready', [self::class, 'onStagingReady'], 100, 1);
        add_action('duplicator_staging_failed', [self::class, 'onStagingFailed'], 100, 1);
    }

    /**
     * Emit `staging/created` when a staging site reaches the ready state.
     *
     * @param StagingEntity $_entity Newly ready staging entity (unused, kept for hook signature)
     *
     * @return void
     */
    public static function onStagingReady(StagingEntity $_entity): void
    {
        TelemetryEvents::track('staging', 'created', [
            'total_active_staging_sites' => self::countActiveStagingSites(),
        ]);
    }

    /**
     * Emit `staging/failed` when a staging build transitions to failed.
     *
     * @param StagingEntity $_entity Failed staging entity (unused, kept for hook signature)
     *
     * @return void
     */
    public static function onStagingFailed(StagingEntity $_entity): void
    {
        TelemetryEvents::track('staging', 'failed', [
            'total_active_staging_sites' => self::countActiveStagingSites(),
        ]);
    }

    /**
     * Count staging entities currently in the ready state. Cheap: the count
     * is bounded by license tier (a handful at most).
     *
     * @return int
     */
    private static function countActiveStagingSites(): int
    {
        $entities = StagingEntity::getAll();
        if (!is_array($entities)) {
            return 0;
        }

        $active = 0;
        foreach ($entities as $entity) {
            if ($entity instanceof StagingEntity && $entity->getStatus() === StagingEntity::STATUS_READY) {
                $active++;
            }
        }
        return $active;
    }
}
