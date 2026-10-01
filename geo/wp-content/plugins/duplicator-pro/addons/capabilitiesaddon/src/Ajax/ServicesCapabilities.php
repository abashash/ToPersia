<?php

declare(strict_types=1);

namespace Duplicator\Addons\CapabilitiesAddon\Ajax;

use Duplicator\Addons\CapabilitiesAddon\Core\LicenseCapabilities;
use Duplicator\Ajax\AbstractAjaxService;
use Duplicator\Ajax\AjaxWrapper;
use Duplicator\Core\CapMng;
use Duplicator\Libs\Snap\SnapUtil;

/**
 * AJAX service for capabilities user search
 */
class ServicesCapabilities extends AbstractAjaxService
{
    const USERS_PAGE_SIZE = 10;

    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init(): void
    {
        $this->addAjaxCall('wp_ajax_duplicator_settings_cap_users_list', 'capUsersList');
    }

    /**
     * Return user list for capabilities select
     *
     * @return mixed[]
     */
    public static function capUsersListCallback(): array
    {
        $searchStr = SnapUtil::sanitizeTextInput(INPUT_POST, 'search');
        $page      = SnapUtil::sanitizeIntInput(INPUT_POST, 'page', 1);
        $result    = [
            'results'    => [],
            'pagination' => ['more' => false],
        ];

        if ($page === 1) {
            foreach (CapMng::getSelectableRoles() as $role => $roleName) {
                if (strlen($searchStr) === 0 || stripos($role, $searchStr) !== false) {
                    $result['results'][] = [
                        'id'   => $role,
                        'text' => $roleName,
                    ];
                }
            }
        }

        if (LicenseCapabilities::can()) {
            $args = [
                'search'         => '*' . $searchStr . '*',
                'search_columns' => [
                    'user_login',
                    'user_email',
                ],
                'number'         => self::USERS_PAGE_SIZE,
                'paged'          => $page,
            ];
            if (is_multisite()) {
                $args['blog_id'] = 0;
                $superAdmins     = get_super_admins();
                if (count($superAdmins) > 0) {
                    $args['login__in'] = $superAdmins;
                }
            }
            $users = get_users($args);
            foreach ($users as $user) {
                $result['results'][] = [
                    'id'   => $user->ID,
                    'text' => $user->user_email,
                ];
            }

            // Check if there are more users
            $args['paged']                = $page + 1;
            $users                        = get_users($args);
            $result['pagination']['more'] = count($users) > 0;
        }

        return $result;
    }

    /**
     * AJAX handler for user list
     *
     * @return void
     */
    public function capUsersList(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'capUsersListCallback',
            ],
            'duplicator_settings_cap_users_list',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapMng::CAP_SETTINGS
        );
    }
}
