<?php

declare(strict_types=1);

namespace Duplicator\Addons\CapabilitiesAddon;

use Duplicator\Addons\CapabilitiesAddon\Core\LicenseCapabilities;
use WP_User;

/**
 * Advanced capability management logic injected into core CapMng via filters.
 *
 * Adds user-level capability assignments (individual users, not just roles).
 */
class CapMngAdvanced
{
    /**
     * Register hooks into core CapMng
     *
     * @return void
     */
    public static function register(): void
    {
        add_filter('duplicator_capability_update_users', [self::class, 'processUsers'], 10, 3);
        add_filter('duplicator_add_current_user_role', [self::class, 'addCurrentUserById'], 10, 2);
    }

    /**
     * Process user-level capability assignments during update.
     *
     * If the license allows FEATURE_CAPABILITIES_MNG_PLUS, validates and adds
     * individual user IDs from the raw input data.
     *
     * @param array{roles: string[], users: int[]} $capData Processed capability data (roles filled, users empty)
     * @param array{roles: string[], users: int[]} $rawData Raw input data including any user IDs
     * @param string                               $cap     Capability name
     *
     * @return array{roles: string[], users: int[]}
     */
    public static function processUsers(array $capData, array $rawData, string $cap): array
    {
        if (!LicenseCapabilities::canPlus() || empty($rawData['users'])) {
            return $capData;
        }

        foreach ($rawData['users'] as $userId) {
            $user = get_user_by('id', $userId);
            if ($user) {
                $capData['users'][] = $user->ID;
            }
        }

        return $capData;
    }

    /**
     * Add current user by ID instead of by role when PLUS license is available.
     *
     * This is the user-level variant of the "prevent locking yourself out" mechanism.
     * Instead of adding the user's role (which grants access to all users with that role),
     * it adds the specific user ID.
     *
     * @param array{roles: string[], users: int[]} $capData Capability data
     * @param WP_User                              $user    Current user
     *
     * @return array{roles: string[], users: int[]}
     */
    public static function addCurrentUserById(array $capData, WP_User $user): array
    {
        if (!LicenseCapabilities::can()) {
            return $capData;
        }

        $capData['users'][] = $user->ID;
        return $capData;
    }
}
