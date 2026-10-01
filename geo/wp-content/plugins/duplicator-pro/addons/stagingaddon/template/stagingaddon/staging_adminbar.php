<?php

/**
 * Staging site admin bar content
 */

use Duplicator\Addons\StagingAddon\StagingAddon;
use Duplicator\Core\Controllers\ControllersManager;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$adminBar       = $tplMng->getDataValueObjRequired('adminBar', WP_Admin_Bar::class);
$mainSiteUrl    = $tplMng->getDataValueStringRequired('mainSiteUrl');
$identifier     = $tplMng->getDataValueStringRequired('identifier');
$stagingPageUrl = $tplMng->getDataValueStringRequired('stagingPageUrl');
$createdAt      = $tplMng->getDataValueStringRequired('createdAt');

$iconUrl = StagingAddon::getAddonUrl() . '/assets/img/duplicator-logo-icon-white.svg';

// Main node
$adminBar->add_node([
    'id'    => 'duplicator-staging-indicator',
    'title' => sprintf(
        '<img src="%1$s" alt="%2$s" class="dupli-staging-bar-icon"><span class="ab-label">%3$s</span>',
        esc_url($iconUrl),
        esc_attr__('Duplicator', 'duplicator-pro'),
        esc_html__('Staging Site', 'duplicator-pro')
    ),
    'href'  => '#',
    'meta'  => [
        'class' => 'dupli-staging-bar',
        'title' => __('This is a staging site', 'duplicator-pro'),
    ],
]);

// Sub-menu items
if (!empty($mainSiteUrl)) {
    $adminBar->add_node([
        'id'     => 'duplicator-staging-main-site',
        'parent' => 'duplicator-staging-indicator',
        'title'  => __('Go to Main Site', 'duplicator-pro'),
        'href'   => esc_url($stagingPageUrl),
    ]);

    // Build delete URL - this is a cross-site link from staging site to main site
    if (!empty($stagingPageUrl)) {
        $deleteUrl = add_query_arg(['delete_staging_id' => $identifier], $stagingPageUrl);
    } else {
        // Fallback for older staging sites without stagingPageUrl
        $deleteUrl = add_query_arg([
            'page'              => ControllersManager::MAIN_MENU_SLUG . '-staging',
            'delete_staging_id' => $identifier,
        ], $mainSiteUrl . '/wp-admin/admin.php');
    }

    $adminBar->add_node([
        'id'     => 'duplicator-staging-delete',
        'parent' => 'duplicator-staging-indicator',
        'title'  => __('Delete Staging Site', 'duplicator-pro'),
        'href'   => esc_url($deleteUrl),
    ]);
}

// Info item
if (!empty($createdAt)) {
    $adminBar->add_node([
        'id'     => 'duplicator-staging-info',
        'parent' => 'duplicator-staging-indicator',
        'title'  => sprintf(
            __('Created: %s', 'duplicator-pro'),
            date_i18n(get_option('date_format'), strtotime($createdAt))
        ),
    ]);
}
