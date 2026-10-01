<?php

/**
 * Multisite subsite filter tab content
 */

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\MultisiteAddon\LicenseMultisite;
use Duplicator\Libs\WpUtils\WpUtilsMultisite;
use Duplicator\Models\TemplateEntity;
use Duplicator\Views\UI\UiDialog;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$template      = $tplMng->getDataValueObjRequired('template', TemplateEntity::class);
$excludedSites = $template->getExtraData('filter_sites', []);
?>
<div class="filter-mu-tab-wrapper">
    <div class="filter-mu-tab-content <?php echo (LicenseMultisite::canMultisitePlus(License::getType()) ? '' : esc_attr('disabled')); ?>">
        <div class="dupli-mu-overview">
            <?php if (!LicenseMultisite::canMultisitePlus(License::getType())) { ?>
                <div class="dupli-panel-optional-txt alert-disabled">
                    <b><?php esc_html_e("Notice:", 'duplicator-pro'); ?></b>
                    <?php
                    printf(
                        esc_html__(
                            'This option isn\'t available at the %1$s license level.',
                            'duplicator-pro'
                        ),
                        esc_html(License::getLicenseToString())
                    );
                    ?>
                    <br>
                    <?php
                    printf(
                        esc_html_x(
                            'To enable this option %1$supgrade%2$s the License.',
                            '%1$s and %2$s represents the opening and closing HTML tags for an anchor or link',
                            'duplicator-pro'
                        ),
                        '<a href="' . esc_url(License::getUpsellURL()) . '" target="_blank">',
                        '</a>'
                    );
                    ?>
                </div>
            <?php } ?>
            <p>
                <b><?php esc_html_e("Overview", 'duplicator-pro'); ?>:</b>
                <?php esc_html_e(
                    "This section controls which sub-sites of the network are included in the Backup.
                    By default all sub-sites are included; excluding sub-sites helps shrink the Backup if needed.",
                    'duplicator-pro'
                ); ?>
                <?php esc_html_e(
                    "To move a full multisite network or convert a subsite to a standalone site, create a standard
                    Backup and choose the corresponding option on Step 1 of the installer.",
                    'duplicator-pro'
                ); ?>
            </p>
            <p class="dupli-mu-note">
                <?php
                echo wp_kses(
                    __(
                        "<b>Important:</b> full network restoration is available at install time only if <b>all</b> subsites are included.
                        If any subsites are excluded, they can only be restored as standalone sites.",
                        'duplicator-pro'
                    ),
                    ['b' => []]
                );
                ?>
            </p>
        </div>
        <div class="dupli-mu-transfer">
            <div class="dupli-mu-transfer-list">
                <b><?php esc_html_e("Included Sub-Sites", 'duplicator-pro'); ?>:</b>
                <select name="mu-include[]" id="mu-include" multiple="true" class="mu-selector">
                    <?php
                    $subsites = LicenseMultisite::canMultisitePlus(License::getType()) ? WpUtilsMultisite::getSubsites() : [];
                    foreach ($subsites as $site) {
                        if (!in_array((int) $site->id, $excludedSites)) {
                            echo "<option value='" . (int) $site->id . "'>" . esc_html($site->domain . $site->path) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="dupli-mu-transfer-controls">
                <button type="button" id="mu-exclude-btn" class="button hollow secondary small margin-0">
                    <i class="fa fa-chevron-right"></i>
                </button>
                <button type="button" id="mu-include-btn" class="button hollow secondary small margin-0">
                    <i class="fa fa-chevron-left"></i>
                </button>
            </div>
            <div class="dupli-mu-transfer-list">
                <b><?php esc_html_e("Excluded Sub-Sites", 'duplicator-pro'); ?>:</b>
                <select name="mu-exclude[]" id="mu-exclude" multiple="true" class="mu-selector">
                    <?php
                    foreach ($subsites as $site) {
                        if (in_array((int) $site->id, $excludedSites)) {
                            echo "<option value='" . (int) $site->id . "'>" . esc_html($site->domain . $site->path) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <?php
    $alert1          = new UiDialog();
    $alert1->title   = __('ERROR!', 'duplicator-pro');
    $alert1->message = __('You can\'t exclude all sites.', 'duplicator-pro');
    $alert1->initAlert();
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#mu-include-btn').click(function() {
                return !$('#mu-exclude option:selected').remove().appendTo('#mu-include');
            });

            $('#mu-exclude-btn').click(function() {
                var include_all_count = $('#mu-include option').length;
                var include_selected_count = $('#mu-include option:selected').length;

                if (include_all_count > include_selected_count) {
                    return !$('#mu-include option:selected').remove().appendTo('#mu-exclude');
                } else {
                    <?php $alert1->showAlert(); ?>
                }
            });
        });
    </script>
</div>
