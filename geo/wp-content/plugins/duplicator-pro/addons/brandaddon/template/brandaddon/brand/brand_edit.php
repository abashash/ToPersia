<?php



defined("ABSPATH") or die("");

use Duplicator\Addons\BrandAddon\Controllers\BrandSettingsController;
use Duplicator\Addons\BrandAddon\Models\BrandEntity;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Views\UI\UiDialog;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */
$brand          = $tplMng->getDataValueObjRequired('brand', BrandEntity::class);
$brand_list_url = ControllersManager::getCurrentLink([ControllersManager::QUERY_STRING_INNER_PAGE => BrandSettingsController::BRAND_INNER_PAGE_LIST]);
$brand_edit_url = ControllersManager::getCurrentLink([ControllersManager::QUERY_STRING_INNER_PAGE => BrandSettingsController::BRAND_INNER_PAGE_EDIT]);

// Let's make impossible - do TinyMCE textarea required
add_action('the_editor', function ($editorMarkup) {
    if (stripos($editorMarkup, 'required') !== false) {
        $editorMarkup = str_replace('<textarea', '<textarea required="true"', $editorMarkup);
    }
    return $editorMarkup;
});
?>
<div class="dup-toolbar">
    <a href="<?php echo esc_url($brand_list_url); ?>" class="button secondary hollow small ">
        <i class="far fa-image"></i> <?php esc_html_e('Brands', 'duplicator-pro'); ?>
    </a>
</div>
<hr class="dup-toolbar-divider" />
<form
    id="dupli-package-brand-form"
    class="dup-monitored-form"
    action="<?php echo esc_url($brand_edit_url); ?>" method="post" data-parsley-ui-enabled="true">
    <?php $tplMng->getAction(BrandSettingsController::ACTION_BRAND_SAVE)->getActionNonceFileds(); ?>
    <input type="hidden" name="id" id="brand-id" value="<?php echo (int) $brand->getId(); ?>" />
    <input type="hidden" name="attachments" id="brand-attachments" value="<?php echo esc_attr(join(";", $brand->attachments)); ?>" />
    <div class="dup-settings-wrapper margin-bottom-1">
        <?php
        if ($brand->isDefault()) {
            $tplMng->render('brandaddon/brand/brand_edit_default', ['brand' => $brand]);
        } else {
            $tplMng->render('brandaddon/brand/brand_edit_new', ['brand' => $brand]);
        }
        ?>
    </div>
    <h2><?php esc_html_e('Preview Area:', 'duplicator-pro'); ?></h2>
    <div class="preview-area">
        <div class="preview-box">
            <div class="preview-header">
                <div class="preview-title">
                    <div id="preview-logo">
                        <?php echo $brand->logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>
                    </div>
                    <div class="preview-version">
                        <?php
                        esc_html_e("version: ", 'duplicator-pro');
                        echo esc_html(DUPLICATOR_VERSION);
                        ?> <br />
                        » <a href="javascript:void(0)">
                            <?php esc_html_e("info", 'duplicator-pro'); ?>
                        </a> » <a href="javascript:void(0)">
                            <?php esc_html_e("help", 'duplicator-pro'); ?>
                        </a> <i class="fa-solid fa-question-circle fa-sm dark-gray-color"></i>
                    </div>
                </div>
            </div>
            <div class="preview-content">
                <div class="preview-mode"><?php esc_html_e("Mode: Standard Install", 'duplicator-pro'); ?></div>
                <div class="preview-steps">
                    <?php esc_html_e("Step 1 of 4: Deployment", 'duplicator-pro'); ?>
                </div>
            </div>
        </div>
        <div class="preview-notes">
            <?php esc_html_e("Note: Be sure to validate the final results in the installer.php file.", 'duplicator-pro'); ?>
        </div>
    </div>
    <br style="clear:both" />
    <button
        id="dup-save-brand-button"
        class="button primary small" type="button"
        onclick="return DupliJs.Settings.Brand.Save();"
        <?php disabled($brand->isDefault()); ?>>
        <?php esc_html_e('Save Brand', 'duplicator-pro'); ?>
    </button>
</form>

<!-- ==========================================
THICK-BOX DIALOGS: -->
<?php
$alert1               = new UiDialog();
$alert1->title        = __('Branding Guide', 'duplicator-pro');
$alert1->templatePath = 'brandaddon/dialogs/branding-guide';
$alert1->width        = 650;
$alert1->height       = 400;
$alert1->initAlert();

$alert2          = new UiDialog();
$alert2->title   = __('Brand Name', 'duplicator-pro');
$alert2->message = __("WARNING: Brand name cannot be named like <strong>Default</strong> because it is a reserved name.", 'duplicator-pro');
$alert2->initAlert();

$alert3          = new UiDialog();
$alert3->title   = __('Brand Logo', 'duplicator-pro');
$alert3->message = __("WARNING: Brand logo has a wrong URL.", 'duplicator-pro');
$alert3->initAlert();
?>

<script>
    window.DupliJs = window.DupliJs || {};
    DupliJs.Settings = DupliJs.Settings || {};
    DupliJs.Settings.Brand = DupliJs.Settings.Brand || {};
    DupliJs.Brand = new Object();

    /*  Shows the style Guide */
    DupliJs.Brand.ShowStyleGuide = function() {
        <?php $alert1->showAlert(); ?>
        return;
    }


    jQuery(document).ready(function($) {
        /*
         * CHECK IS IMAGE
         * @url: https://github.com/CreativForm/CreativeTools
         */
        $.isImage = function(string) {
            if (null === string || false === string)
                return false;
            return (string.match(/\.(jpeg|jpg|gif|png|bmp|svg|tiff|jfif|exif|ppm|pgm|pbm|pnm|webp|hdr|hif|bpg|img|pam|tga|psd|psp|xcf|cpt|vicar)$/) != null ?
                true : false);
        };

        /*
         * CHECK IF IMAGE EXISTS
         * @url: https://github.com/CreativForm/CreativeTools
         */
        $.imageExists = function(string, callback) {
            if ($.isImage(string)) {
                var img = new Image(10, 10);
                img.src = string;
                img.onload = function() {
                    if (typeof callback == 'function') {
                        callback(true);
                        img = null;
                    }
                };
                img.onerror = function() {
                    if (typeof callback == 'function') {
                        callback(false);
                        img = null;
                    }
                };
            } else {
                if (typeof callback == 'function') {
                    callback(false);
                    img = null;
                }
            }
        };
        var strip_tags = function(input, allowed) {
            var allowed = (((allowed || '') + '')
                    .toLowerCase()
                    .match(/<[a-z][a-z0-9]*>/g) || [])
                .join('');
            let tags = '/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi';
            let commentsAndPhpTags = '/<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi';

            return input.replace(commentsAndPhpTags, '').replace(
                tags,
                function($0, $1) {
                    return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
                });
        }

        DupliJs.Settings.Debounce;
        DupliJs.Settings.Brand.Save = function(e) {
            clearTimeout(DupliJs.Settings.Debounce);
            if ($('#dupli-package-brand-form').parsley().validate()) {

                var $logo = $("#brand-logo");

                $('#brand-action').val('save');
                $logo.removeClass('parsley-error');
                var image_valid = true;
                // Check is images valid
                var images = $('<div />').html($logo.val()).children('img').map(function() {
                    var image = $(this).attr('src');
                    return image;
                }).get();

                for (var i = 0; i < images.length; i++) {
                    $.imageExists(images[i], function(r) {
                        if (!r) {
                            image_valid = false;
                            $logo.removeClass('parsley-success').addClass('parsley-error');
                        }
                    });
                }

                DupliJs.Settings.Debounce = setTimeout(function() {
                    // Check is brand name reserved
                    if ($('#brand-name') && $.trim($('#brand-name').val()).toLowerCase() == 'default') {
                        <?php $alert2->showAlert(); ?>
                        e.preventDefault();

                    } else if (!image_valid) {
                        <?php $alert3->showAlert(); ?>
                        e.preventDefault();
                    } else {
                        DupliJs.UI.hasUnsavedChanges = false;
                        $('#dupli-package-brand-form').submit();
                    }
                }, 200);
            }
        }

        <?php if (!$brand->isDefault()) : ?>
            //INIT
            $('#dupli-package-brand-form #brand-name').focus();

            // Let's automate this things
            DupliJs.Settings.Automatization = function(e) {

                if (e.originalEvent !== undefined) {
                    clearTimeout(DupliJs.Settings.Debounce);
                    var $this = $("#dupli-package-brand-form #brand-logo"),
                        $debounce = 800;

                    // Smart debounce
                    if (e.currentTarget) {
                        if ($(e.currentTarget).hasClass('button')) $debounce = 5;
                        if ($(e.currentTarget).hasClass('preview-area')) $debounce = 200;
                    }

                    DupliJs.Settings.Debounce = setTimeout(function() {
                        var $value = $this.val();

                        $this.val(strip_tags($value, '<a><i><b><u><em><ins><div><img><span><strong>'));

                        // Do preview
                        $("#dupli-package-brand-form #preview-logo").html($value);

                        // Now we must made array for path of all images (if the are on server) We don't need remote images (CDN is cool thing)
                        // Let's first collect all images
                        var images = $('<div />').html($value).children('img').map(function() {
                            return $(this).attr('src')
                        }).get();
                        images = $.unique(images);
                        $("#dupli-package-brand-form #brand-attachments").val('');

                        // New magic trick is to determinate is CDN or uploaded image
                        // - CDN will not be return like path
                        // - Server side images will be returned like image real path
                        if (images.length > 0) {
                            var path = images.map(function(src) {

                                var hostname = <?php echo wp_json_encode(plugins_url()); ?>.replace(/https?|\:\/\/|\/wp-content\/plugins/gi, '');

                                if (new RegExp('(https?:)?//' + hostname, 'ig').test(src)) {
                                    return src.replace(new RegExp('(https?:)?//' + hostname + '/wp-content|/uploads', 'ig'), '');
                                }
                            });

                            if (path.length > 0) $("#dupli-package-brand-form #brand-attachments").val(path.join(';'));
                        }

                    }, $debounce);
                }
            };
            // On textarea change
            $(document).on(
                'change keyup paste input mouseout mouseover propertychange',
                "#dupli-package-brand-form #brand-logo",
                DupliJs.Settings.Automatization
            );
            // On other boxes
            $(document).on('mouseover', "#dupli-package-brand-form .preview-area", DupliJs.Settings.Automatization);

        <?php endif; ?>

    });
</script>
