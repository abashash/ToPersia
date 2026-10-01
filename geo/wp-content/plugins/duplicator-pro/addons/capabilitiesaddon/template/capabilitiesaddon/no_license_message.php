<?php

use Duplicator\Addons\CapabilitiesAddon\Core\LicenseCapabilities;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\Models\LicenseData;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

if (LicenseCapabilities::can()) {
    return;
}
?>
<p id="dup-no-license-message" class="dup-border-left-red-notice" >
    <b>
        <?php
        esc_html_e(
            'The current license does not allow managing the capabilities. ',
            'duplicator-pro'
        );
        ?>
    </b>
    <br>
    <?php
    if (License::getLicenseStatus() === LicenseData::STATUS_VALID) {
        printf(
            esc_html_x(
                'If you want to manage the capabilities please %1$supgrade your license%2$s.',
                '1: <a> tag, 2: </a> tag',
                'duplicator-pro'
            ),
            '<a href="' . esc_url(LicenseCapabilities::getUpsellURL()) . '" target="_blank">',
            '</a>'
        );
    } else {
        printf(
            esc_html_x(
                'If you want to manage the capabilities please %1$srenew your license%2$s.',
                '1: <a> tag, 2: </a> tag',
                'duplicator-pro'
            ),
            '<a href="' . esc_url(LicenseCapabilities::getUpsellURL()) . '" target="_blank">',
            '</a>'
        );
    }
    ?>
    <br><br>
    <?php
    esc_html_e(
        'It\'s possible to reset the capabilities to the default values with "Reset to default" button.',
        'duplicator-pro'
    );
    ?>

</p>
