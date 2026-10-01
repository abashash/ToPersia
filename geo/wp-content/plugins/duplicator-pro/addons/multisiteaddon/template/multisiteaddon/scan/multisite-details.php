<?php

/**
 * Multisite scan details section
 */

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\MultisiteAddon\LicenseMultisite;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

if (LicenseMultisite::canMultisitePlus(License::getType())) { ?>
    <hr size="1" />
    <span>
        <div class="dup-scan-good"><i class="fa fa-check"></i></div>
    </span>
    <b> <?php esc_html_e('Multisite: Detected', 'duplicator-pro'); ?></b> <br />
    <div class="scan-system-subnote">
        <?php esc_html_e('This license level has full access to all Multisite Plus+ features.', 'duplicator-pro'); ?>
    </div>
<?php } else { ?>
    <hr size="1" />
    <span>
        <div class="dup-scan-warn"><i class="fa fa-exclamation-triangle fa-sm"></i></div>
    </span>
    <b><?php esc_html_e('Multisite: Detected', 'duplicator-pro'); ?> </b> <br />
    <div class="scan-system-subnote">
        <?php echo esc_html(
            sprintf(
                /* translators: %1$s: plugin name, %2$s: license level */
                __(
                    '%1$s is at the %2$s license level which allows for backups and migrations of an entire Multisite network.',
                    'duplicator-pro'
                ),
                DUPLICATOR____NAME,
                License::getLicenseToString()
            )
        ); ?>
        <br>
        <?php echo wp_kses(
            __(
                "To unlock all <b>Multisite Plus</b> features please upgrade the license before building a Backup.",
                'duplicator-pro'
            ),
            ['b' => []]
        ); ?>
        <br />
        <a href="<?php echo esc_url(License::getUpsellURL()); ?>" target='_blank'>
            <?php esc_html_e('Upgrade Here', 'duplicator-pro'); ?>
        </a>&nbsp;|&nbsp;
        <a href="<?php echo esc_url(DUPLICATOR_DUPLICATOR_DOCS_URL . 'how-does-duplicator-handle-multisite-support'); ?>" target="_blank">
            <?php esc_html_e('Multisite Plus Feature Overview', 'duplicator-pro'); ?>
        </a>
    </div>
<?php } ?>
