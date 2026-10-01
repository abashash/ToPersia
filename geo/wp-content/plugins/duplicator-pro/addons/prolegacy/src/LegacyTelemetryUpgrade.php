<?php

declare(strict_types=1);

namespace Duplicator\Addons\ProLegacy;

use Duplicator\Utils\Logging\DupLog;
use Duplicator\Utils\UsageStatistics\Telemetry\TelemetryState;
use ReflectionClass;

/**
 * Migrates legacy Pro telemetry data into the consolidated core state.
 */
class LegacyTelemetryUpgrade
{
    const LEGACY_FIRST_SENT_OPTION         = 'dupli_opt_telemetry_first_sent';
    const LEGACY_FAILURE_STREAK_OPTION     = 'dupli_opt_telemetry_failure_streak';
    const LEGACY_INSTALL_EVENT_TIME_OPTION = 'dupli_opt_telemetry_install_event_time';
    const LEGACY_PLUGIN_DATA_OPTION        = 'dupli_opt_plugin_data_stats';

    /**
     * Register the telemetry migration.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('duplicator_upgrade', [self::class, 'consolidateState'], 253, 4);
    }

    /**
     * Move the Pro telemetry options into the consolidated core state.
     *
     * @param string       $oldVariant Previous variant
     * @param false|string $oldVersion Previous version
     * @param string       $newVariant Current variant
     * @param string       $newVersion Current version
     *
     * @return void
     */
    public static function consolidateState($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        if ($oldVersion === false) {
            return;
        }

        $pluginDataRaw = get_option(self::LEGACY_PLUGIN_DATA_OPTION, false);
        $pluginData    = is_string($pluginDataRaw) ? json_decode($pluginDataRaw, true) : null;
        $pluginData    = is_array($pluginData) ? $pluginData : [];
        $historyKeys   = [
            'buildCount',
            'buildLastDate',
            'buildFailedCount',
            'buildFailedLastDate',
            'packagesBuildCompFullCount',
            'packagesBuildCompDbOnlyCount',
            'usedRecoveryCount',
            'siteSizeMB',
            'siteNumFiles',
            'siteDbSizeMB',
            'siteDbNumTables',
        ];
        $hasHistory    = false;
        foreach ($historyKeys as $key) {
            if (array_key_exists($key, $pluginData)) {
                $hasHistory = true;
                break;
            }
        }

        $optionValues = [
            self::LEGACY_FIRST_SENT_OPTION         => get_option(self::LEGACY_FIRST_SENT_OPTION, false),
            self::LEGACY_FAILURE_STREAK_OPTION     => get_option(self::LEGACY_FAILURE_STREAK_OPTION, false),
            self::LEGACY_INSTALL_EVENT_TIME_OPTION => get_option(self::LEGACY_INSTALL_EVENT_TIME_OPTION, false),
        ];

        $hasOperationalState = false;
        foreach ($optionValues as $value) {
            if ($value !== false) {
                $hasOperationalState = true;
                break;
            }
        }
        if (!$hasHistory && !$hasOperationalState) {
            return;
        }

        $counterState  = [];
        $nullableState = [];
        if ($hasHistory) {
            $counterState  = [
                'buildCount'                   => self::legacyInt($pluginData, 'buildCount'),
                'buildLastDate'                => self::legacyInt($pluginData, 'buildLastDate'),
                'buildFailedCount'             => self::legacyInt($pluginData, 'buildFailedCount'),
                'buildFailedLastDate'          => self::legacyInt($pluginData, 'buildFailedLastDate'),
                'packagesBuildCompFullCount'   => self::legacyInt($pluginData, 'packagesBuildCompFullCount'),
                'packagesBuildCompDbOnlyCount' => self::legacyInt($pluginData, 'packagesBuildCompDbOnlyCount'),
                'usedRecoveryCount'            => self::legacyInt($pluginData, 'usedRecoveryCount'),
            ];
            $nullableState = [
                'siteSizeMB'      => self::legacyNullableFloat($pluginData, 'siteSizeMB'),
                'siteNumFiles'    => self::legacyNullableInt($pluginData, 'siteNumFiles'),
                'siteDbSizeMB'    => self::legacyNullableFloat($pluginData, 'siteDbSizeMB'),
                'siteDbNumTables' => self::legacyNullableInt($pluginData, 'siteDbNumTables'),
            ];
        }
        if ($hasOperationalState) {
            $counterState['firstSnapshotSentAt'] = max(0, (int) $optionValues[self::LEGACY_FIRST_SENT_OPTION]);
            $counterState['failureStreak']       = max(0, (int) $optionValues[self::LEGACY_FAILURE_STREAK_OPTION]);
            $counterState['installEventSentAt']  = max(0, (int) $optionValues[self::LEGACY_INSTALL_EVENT_TIME_OPTION]);
        }

        if (!self::writeState($counterState, $nullableState)) {
            return;
        }

        foreach (array_keys($optionValues) as $option) {
            delete_option($option);
        }
        DupLog::trace('LEGACY MIGRATION: Consolidated Pro telemetry state');
    }

    /**
     * Write converted values without exposing migration APIs from core.
     *
     * @param array<string, int>            $counterState  Counter and timestamp values
     * @param array<string, int|float|null> $nullableState Measured values
     *
     * @return bool
     */
    private static function writeState(array $counterState, array $nullableState): bool
    {
        $state              = TelemetryState::getInstance();
        $reflection         = new ReflectionClass($state);
        $requiresAccessible = PHP_VERSION_ID < 80100;

        foreach ($counterState as $name => $value) {
            $property = $reflection->getProperty($name);
            if ($requiresAccessible) {
                $property->setAccessible(true);
            }
            $property->setValue($state, max((int) $property->getValue($state), $value));
        }
        foreach ($nullableState as $name => $value) {
            if ($value === null) {
                continue;
            }
            $property = $reflection->getProperty($name);
            if ($requiresAccessible) {
                $property->setAccessible(true);
            }
            if ($property->getValue($state) === null) {
                $property->setValue($state, $value);
            }
        }

        $save = $reflection->getMethod('save');
        if ($requiresAccessible) {
            $save->setAccessible(true);
        }
        return (bool) $save->invoke($state);
    }

    /**
     * @param array<string, mixed> $data Legacy state
     * @param string               $key  Legacy field
     *
     * @return int
     */
    private static function legacyInt(array $data, string $key): int
    {
        return isset($data[$key]) ? max(0, (int) $data[$key]) : 0;
    }

    /**
     * @param array<string, mixed> $data Legacy state
     * @param string               $key  Legacy field
     *
     * @return ?int
     */
    private static function legacyNullableInt(array $data, string $key): ?int
    {
        $value = self::legacyInt($data, $key);
        return $value === 0 ? null : $value;
    }

    /**
     * @param array<string, mixed> $data Legacy state
     * @param string               $key  Legacy field
     *
     * @return ?float
     */
    private static function legacyNullableFloat(array $data, string $key): ?float
    {
        $value = isset($data[$key]) ? max(0.0, (float) $data[$key]) : 0.0;
        return $value == 0.0 ? null : $value;
    }
}
