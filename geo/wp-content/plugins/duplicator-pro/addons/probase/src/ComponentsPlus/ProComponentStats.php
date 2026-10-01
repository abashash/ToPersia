<?php

declare(strict_types=1);

namespace Duplicator\Addons\ProBase\ComponentsPlus;

use VendorDuplicator\Amk\JsonSerialize\JsonSerialize;

/**
 * Persisted storage for component-plus build statistics.
 *
 * Follows the same pattern as ScheduleUsageStats: singleton, WP option, JsonSerialize.
 */
class ProComponentStats
{
    const OPTION_KEY = 'dupli_opt_pro_component_stats';

    /** @var ?self */
    private static $instance = null;

    /** @var int */
    private $mdOnlyCount = 0;

    /** @var int */
    private $customCount = 0;

    /** @var int */
    private $customOnlyActiveCount = 0;

    /**
     * Class constructor
     */
    private function __construct()
    {
        if (($data = get_option(self::OPTION_KEY)) !== false) {
            JsonSerialize::unserializeToObj($data, $this);
        }
    }

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Increment media-only build count
     *
     * @return void
     */
    public function incrementMdOnlyCount(): void
    {
        $this->mdOnlyCount++;
        $this->save();
    }

    /**
     * Increment custom build count
     *
     * @return void
     */
    public function incrementCustomCount(): void
    {
        $this->customCount++;
        $this->save();
    }

    /**
     * Increment custom (active-only) build count
     *
     * @return void
     */
    public function incrementCustomOnlyActiveCount(): void
    {
        $this->customOnlyActiveCount++;
        $this->save();
    }

    /**
     * @return int
     */
    public function getMdOnlyCount(): int
    {
        return $this->mdOnlyCount;
    }

    /**
     * @return int
     */
    public function getCustomCount(): int
    {
        return $this->customCount;
    }

    /**
     * @return int
     */
    public function getCustomOnlyActiveCount(): int
    {
        return $this->customOnlyActiveCount;
    }

    /**
     * Persist to database
     *
     * @return void
     */
    private function save(): void
    {
        update_option(self::OPTION_KEY, JsonSerialize::serialize($this));
    }
}
