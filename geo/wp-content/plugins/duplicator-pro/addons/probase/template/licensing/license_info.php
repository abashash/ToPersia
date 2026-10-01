<?php

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\LicensingController;
use Duplicator\Addons\ProBase\Models\LicenseData;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */
$refreshAction = $tplMng->getAction(LicensingController::ACTION_FORCE_REFRESH);
?>
<p>
    <i>
        <?php
        printf(
            esc_html_x(
                'If your license has been updated or is incorrect, then please %1$sForce a Refresh%2$s.',
                '1: <a> tag, 2: </a> tag',
                'duplicator-pro'
            ),
            '<a href="' . esc_url($refreshAction->getUrl()) . '">',
            '</a>'
        );
        ?>
    </i>
</p>
<?php
$licenseData = LicenseData::getInstance();
$status      = $licenseData->getStatus();
$isValid     = ($status === LicenseData::STATUS_VALID);
$sitesLimit  = License::isUnlimited() ? __('Unlimited', 'duplicator-pro') : $licenseData->getLicenseLimit();
$sitesCount  = $licenseData->getSiteCount() < 0 ? '?' : $licenseData->getSiteCount();

$expDaysText = '';
if ($isValid) {
    $expDays = $licenseData->getExpirationDays();
    if ($expDays === false) {
        $expDaysText = __('no data', 'duplicator-pro');
    } elseif ($expDays <= 0) {
        $expDaysText = __('expired', 'duplicator-pro');
    } elseif ($expDays === PHP_INT_MAX) {
        $expDaysText = __('no expiration', 'duplicator-pro');
    } else {
        $expDaysText = sprintf(__('%d days left', 'duplicator-pro'), $expDays);
    }
}

$siteTipContent = __(
    'Indicates the number of sites the plugin can be active on at any one time.
    At any point you may deactivate/uninstall the plugin to free up the license and use the plugin elsewhere if needed.',
    'duplicator-pro'
);
?>
<div id="dup-license-info">
    <div>
        <?php esc_html_e('License:', 'duplicator-pro'); ?>
        <?php if ($isValid) { ?>
            <b><?php echo esc_html(License::getLicenseToString()); ?></b>
        <?php } elseif ($status === LicenseData::STATUS_EXPIRED) { ?>
            <b class="alert-color"><?php esc_html_e('Expired', 'duplicator-pro'); ?></b>
            &mdash;
            <a target="_blank" href="<?php echo esc_url(License::getLicenseCheckoutURL()); ?>">
                <?php esc_html_e('Renew your license now', 'duplicator-pro'); ?>
            </a>
        <?php } elseif ($status === LicenseData::STATUS_SITE_INACTIVE && $licenseData->haveNoActivationsLeft()) { ?>
            <b class="alert-color"><?php esc_html_e('Inactive (out of site licenses)', 'duplicator-pro'); ?></b>
            <br>
            <?php echo wp_kses_post(License::getNoActivationLeftMessage()); ?>
        <?php } elseif (in_array($status, [LicenseData::STATUS_INACTIVE, LicenseData::STATUS_SITE_INACTIVE], true)) { ?>
            <b class="alert-color"><?php esc_html_e('Inactive', 'duplicator-pro'); ?></b>
        <?php } else { ?>
            <b class="alert-color"><?php echo esc_html(LicenseData::getStatusLabel($status)); ?></b>
        <?php } ?>
    </div>
    <?php if ($isValid) { ?>
        <div>
            <?php esc_html_e('Expire date:', 'duplicator-pro'); ?>
            <b><?php echo esc_html($licenseData->getExpirationDate(get_option('date_format'))); ?></b>
            <?php if ($expDays <= 0) { ?>
                <span class="alert-color">(<?php echo esc_html($expDaysText); ?>)</span>
            <?php } else { ?>
                (<?php echo esc_html($expDaysText); ?>)
            <?php } ?>
        </div>
    <?php } ?>
    <div>
        <?php esc_html_e('Sites:', 'duplicator-pro'); ?>
        <b><?php
        printf(
            esc_html_x(
                '%1$s of %2$s',
                '1 = (number) Active sites, 2 = (number|string) License limit',
                'duplicator-pro'
            ),
            esc_html($sitesCount),
            esc_html($sitesLimit)
        );
        ?></b>&nbsp;
        <i
            class="fa-solid fa-question-circle fa-sm dark-gray-color"
            data-tooltip-title="<?php esc_attr_e('Sites', 'duplicator-pro'); ?>"
            data-tooltip="<?php echo esc_attr($siteTipContent); ?>">
        </i>
    </div>
    <?php do_action('duplicator_license_info_features'); ?>
    <?php if (License::canBeUpgraded()) { ?>
        <p class="margin-top-1">
            <?php
            printf(
                esc_html_x(
                    '🔥 To unlock more features see our %1$sUpgrade Offers%2$s.',
                    '1: <a> tag, 2: </a> tag',
                    'duplicator-pro'
                ),
                '<a class="dup-upgrade-license-link primary-color" href="' . esc_url(License::getUpsellURL()) . '" target="_blank">',
                '</a>'
            );
            ?>
        </p>
    <?php } ?>
</div>