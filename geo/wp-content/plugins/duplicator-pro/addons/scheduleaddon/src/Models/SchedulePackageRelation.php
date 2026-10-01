<?php

/**
 * Manages the relationship table between schedules and packages
 */

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Models;

use Duplicator\Libs\Snap\SnapWP;
use wpdb;

/**
 * Manages the duplicator_schedule_packages relationship table.
 *
 * This table records which schedule created each package, preserving
 * the schedule name at the time of creation for display purposes even
 * if the schedule is later renamed or deleted.
 *
 * The execution type itself lives in the package execution info. The
 * relation row (and its run_now flag) remains the source for schedule
 * linkage and schedule-owned display details; it does not backfill execution
 * metadata for packages created before that metadata existed.
 */
class SchedulePackageRelation
{
    /** @var array<int, ?array{package_id: int, schedule_id: int, schedule_name: string, run_now: bool}> */
    private static $cache = [];
    /**
     * Create the schedule_packages table if it does not exist
     *
     * @return void
     */
    public static function createTable(): void
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table           = esc_sql(self::getTableName());

        // PRIMARY KEY must have 2 spaces before for dbDelta to work
        $sql = <<<SQL
CREATE TABLE `{$table}` (
    `package_id` int(11) NOT NULL,
    `schedule_id` int(11) NOT NULL,
    `schedule_name` varchar(255) NOT NULL,
    `run_now` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY  (`package_id`),
    KEY `schedule_id` (`schedule_id`)
) {$charset_collate};
SQL;

        SnapWP::dbDelta($sql);
    }

    /**
     * Insert or replace a schedule-package relationship
     *
     * Uses $wpdb->replace() for idempotent upsert behavior.
     *
     * @param int    $packageId    The package ID
     * @param int    $scheduleId   The schedule ID
     * @param string $scheduleName The schedule name at time of package creation
     * @param bool   $runNow       Whether package was triggered via "Run Now"
     *
     * @return bool True on success, false on failure
     */
    public static function insert(int $packageId, int $scheduleId, string $scheduleName, bool $runNow = false): bool
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $result = $wpdb->replace(
            self::getTableName(),
            [
                'package_id'    => $packageId,
                'schedule_id'   => $scheduleId,
                'schedule_name' => $scheduleName,
                'run_now'       => $runNow ? 1 : 0,
            ],
            [
                '%d',
                '%d',
                '%s',
                '%d',
            ]
        );

        unset(self::$cache[$packageId]);

        return $result !== false;
    }

    /**
     * Get the relationship row for a given package ID
     *
     * @param int $packageId The package ID
     *
     * @return ?array{package_id: int, schedule_id: int, schedule_name: string, run_now: bool} Row data or null if not found
     */
    public static function getByPackageId(int $packageId): ?array
    {
        if (array_key_exists($packageId, self::$cache)) {
            return self::$cache[$packageId];
        }

        /** @var wpdb $wpdb */
        global $wpdb;

        $table = esc_sql(self::getTableName());
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT `package_id`, `schedule_id`, `schedule_name`, `run_now` FROM `{$table}` WHERE `package_id` = %d",
                $packageId
            ),
            ARRAY_A
        );

        if ($row === null) {
            self::$cache[$packageId] = null;
            return null;
        }

        self::$cache[$packageId] = [
            'package_id'    => (int) $row['package_id'],
            'schedule_id'   => (int) $row['schedule_id'],
            'schedule_name' => (string) $row['schedule_name'],
            'run_now'       => (bool) $row['run_now'],
        ];

        return self::$cache[$packageId];
    }

    /**
     * Get the schedule ID that created a given package
     *
     * @param int $packageId The package ID
     *
     * @return int The schedule ID, or 0 if not found
     */
    public static function getScheduleIdByPackageId(int $packageId): int
    {
        $row = self::getByPackageId($packageId);
        return $row !== null ? $row['schedule_id'] : 0;
    }

    /**
     * Delete the relationship row for a given package ID
     *
     * @param int $packageId The package ID
     *
     * @return bool True on success, false on failure
     */
    public static function deleteByPackageId(int $packageId): bool
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $result = $wpdb->delete(
            self::getTableName(),
            ['package_id' => $packageId],
            ['%d']
        );

        unset(self::$cache[$packageId]);

        return $result !== false;
    }

    /**
     * Delete relationship rows for multiple package IDs
     *
     * @param int[] $packageIds Array of package IDs
     *
     * @return int Number of rows deleted, or -1 on error
     */
    public static function deleteByPackageIds(array $packageIds): int
    {
        if (count($packageIds) === 0) {
            return 0;
        }

        /** @var wpdb $wpdb */
        global $wpdb;

        $table        = esc_sql(self::getTableName());
        $placeholders = implode(',', array_fill(0, count($packageIds), '%d'));
        $result       = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$table}` WHERE `package_id` IN ({$placeholders})",
                ...$packageIds
            )
        );

        foreach ($packageIds as $id) {
            unset(self::$cache[$id]);
        }

        return $result === false ? -1 : (int) $result;
    }

    /**
     * Get the full table name including the WordPress base prefix
     *
     * @return string
     */
    public static function getTableName(): string
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        return $wpdb->base_prefix . 'duplicator_addon_schedule_packages';
    }

    /**
     * Drop the in-memory relation cache.
     *
     * The class keeps writes coherent by invalidating cached rows on
     * insert/delete, so production never needs this. It exists for callers
     * that mutate the table out-of-band (e.g. tests wiping it with a raw
     * query) and must discard cached rows that no longer match the table.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
