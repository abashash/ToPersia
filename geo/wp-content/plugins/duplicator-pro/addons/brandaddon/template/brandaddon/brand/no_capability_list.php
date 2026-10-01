<?php



defined("ABSPATH") or die("");

use Duplicator\Addons\BrandAddon\BrandAddon;
use Duplicator\Addons\BrandAddon\Core\LicenseBrand;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Views\KsesHelper;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

if (LicenseBrand::can()) {
    return;
}
?>
<div class="width-xlarge" >
    <?php
    esc_html_e(
        "Create your own WordPress distribution by adding a custom name and logo to the installer!
        Installer branding lets you create multiple brands for your installers and then choose
        which one you want when the Backup is built (example shown below).",
        'duplicator-pro'
    );
    ?>
    <br/><br/>
    <?php
        printf(
            wp_kses(
                __(
                    'This option isn\'t available at the <b>%1$s</b> license level.',
                    'duplicator-pro'
                ),
                KsesHelper::GEN_TAGS
            ),
            esc_html(License::getLicenseToString())
        );
        ?>
    <b>
    <?php
        printf(
            esc_html_x(
                'To enable this option %1$supgrade%2$s the License.',
                '%1$s and %2$s represents the opening and closing HTML tags for an anchor or link',
                'duplicator-pro'
            ),
            '<a href="' . esc_url(LicenseBrand::getUpsellURL()) . '" target="_blank">',
            '</a>'
        );
        ?>
    </b>
</div>

<div style="border:0px solid #999; padding: 5px; margin: 5px; border-radius: 5px; width:700px">
    <img src="<?php echo esc_attr(BrandAddon::getAddonUrl() . '/assets/img/dupli-brand.png'); ?>" >
</div>
