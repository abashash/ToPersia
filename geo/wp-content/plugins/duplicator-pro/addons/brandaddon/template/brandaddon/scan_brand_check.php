<?php

/**
 * Brand scan check section (HTML + JS)
 */

defined("ABSPATH") or die("");
?>

<span id="data-srv-brand-check"></span>&nbsp;
<b><?php esc_html_e('Brand', 'duplicator-pro'); ?>: </b>
<span id="data-srv-brand-name"><?php esc_html_e('Default', 'duplicator-pro'); ?></span>
<br />
<div class="scan-system-subnote" id="data-srv-brand-note">
    <?php esc_html_e('The default content used when a brand is not defined.', 'duplicator-pro'); ?>
</div>
<hr size="1" />
<script>
    (function($) {
        $(document).on('duplicator:scanDataLoaded', function(e, data) {
            if (!data.SRV.Brand) {
                return;
            }
            $("#data-srv-brand-name").text(data.SRV.Brand.Name);
            if (data.SRV.Brand.LogoImageExists) {
                $("#data-srv-brand-note").text(data.SRV.Brand.Notes);
                $("#data-srv-brand-check").html('<div class="scan-good"><i class="fa fa-check"></i></div>');
            } else {
                $("#data-srv-brand-note").html(
                    <?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- single string split for readability
                    echo json_encode(
                        esc_html__(
                            'WARNING! Logo images no longer can be found inside brand. 
                            Please edit this brand and place new images. 
                            After that you can build your Backup with this brand.',
                            'duplicator-pro'
                        )
                    ); ?>
                );
                $("#data-srv-brand-check").html('<div class="scan-warn"><i class="fa fa-exclamation-triangle fa-sm"></i></div>');
            }
        });
    }(window.jQuery));
</script>
