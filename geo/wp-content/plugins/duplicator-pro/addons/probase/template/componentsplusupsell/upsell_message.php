<?php

/**
 * Upsell message for Package Components Plus feature.
 *
 * Shown when the license does not include Media Only and Custom component presets.
 */

use Duplicator\Addons\ProBase\ComponentsPlus\ComponentsPlusHooks;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Package\Create\BuildComponents;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */
?>
<div id="dup-upgrade-license-info" class="margin-top-1">
    <?php
    printf(
        esc_html_x(
            'The %1$s and %2$s options are not included in your license plan.',
            '%1$s and %2$s are the options not included in the license.',
            'duplicator-pro'
        ),
        '<b>' . esc_html(BuildComponents::getActionLabel(ComponentsPlusHooks::COMP_ACTION_MEDIA)) . '</b>',
        '<b>' . esc_html(BuildComponents::getActionLabel(ComponentsPlusHooks::COMP_ACTION_CUSTOM)) . '</b>'
    );
    ?>
    <br>
    <?php
    printf(
        esc_html_x(
            'To enable advanced options please %1$supgrade your license%2$s.',
            '%1$s and %2$s represents the opening and closing HTML tags for an anchor or link.',
            'duplicator-pro'
        ),
        '<a href="' . esc_url(License::getUpsellURL()) . '" target="_blank">',
        '</a>'
    );
    ?>
</div>
