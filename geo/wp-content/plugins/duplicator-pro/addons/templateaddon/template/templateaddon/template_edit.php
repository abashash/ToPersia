<?php



defined("ABSPATH") or die("");

use Duplicator\Addons\TemplateAddon\Controllers\TemplateToolsController;
use Duplicator\Core\Views\TplMng;
use Duplicator\Models\TemplateEntity;
use Duplicator\Views\UI\UiDialog;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */
$blur              = $tplMng->getDataValueBool('blur');
$template          = $tplMng->getDataValueObj('template', TemplateEntity::class);
$edit_template_url = TemplateToolsController::getTemplateEditURL();
?>

<?php $tplMng->render('templateaddon/parts/template_edit_toolbar'); ?>
<form
    id="dupli-template-form"
    class="dup-monitored-form <?php echo ($blur ? 'dup-mock-blur' : ''); ?>"
    data-parsley-validate data-parsley-ui-enabled="true"
    action="<?php echo esc_url($edit_template_url); ?>"
    method="post">
    <?php $tplMng->getAction(TemplateToolsController::ACTION_SAVE_TEMPLATE)->getActionNonceFileds(); ?>
    <input type="hidden" name="package_template_id" value="<?php echo intval($template->getId()); ?>">

    <div class="dupli-setup-split">
        <div class="dupli-setup-main">
            <div class="dup-box dupli-box-static dupli-general-box">
                <div class="dup-box-title">
                    <i class="fas fa-list-check fa-sm"></i> <?php esc_html_e('General', 'duplicator-pro') ?>
                    <?php $tplMng->render('parts/packages/filters/section_filters_incons'); ?>
                </div>
                <div class="dup-box-panel">
                    <div class="dupli-template-general">
                        <?php do_action('duplicator_template_edit_extra_info', $template); ?>
                    </div>
                    <div class="dupli-general-area dupli-general-area-template">
                        <div class="dupli-general-aside">
                            <div class="dupli-general-field-head">
                                <label class="lbl-larger large" for="template-name">
                                    <?php esc_html_e("Template Name", 'duplicator-pro'); ?>:
                                </label>
                            </div>
                            <input type="text" id="template-name" name="name" class="dupli-general-field-control"
                                data-parsley-errors-container="#template_name_error_container"
                                data-parsley-required="true" value="<?php echo esc_attr($template->name); ?>" autocomplete="off" maxlength="125">
                            <div id="template_name_error_container" class="duplicator-error-container"></div>
                        </div>
                        <div class="dupli-general-name">
                            <?php
                            TplMng::getInstance()->render(
                                'admin_pages/packages/setup/name-format-controls',
                                ['nameFormat' => $template->package_name_format]
                            );
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $tplMng->render(
                'parts/packages/filters/section_filters',
                [
                    'isTemplateEdit' => true,
                    'template'       => $template,
                ]
            );

            $tplMng->render(
                'parts/packages/filters/section_installer',
                ['template' => $template]
            );
            ?>

            <button
                class="button primary small dup-save-template-btn"
                type="submit">
                <?php esc_html_e('Save Template', 'duplicator-pro'); ?>
            </button>
        </div>
        <div class="dupli-setup-side">
            <?php
            $tplMng->render(
                'parts/packages/security_notes_box',
                [
                    'secureOn'   => $template->installer_opts_secure_on,
                    'securePass' => $template->installerPassowrd,
                    'notes'      => $template->notes,
                ]
            );
            ?>
        </div>
    </div>
</form>




<?php
$alert1          = new UiDialog();
$alert1->title   = __('Transfer Error', 'duplicator-pro');
$alert1->message = __('You can\'t exclude all sites!', 'duplicator-pro');
$alert1->initAlert();
?>

<script>
    window.DupliJs = window.DupliJs || {};
    DupliJs.Template = DupliJs.Template || {};
    jQuery(document).ready(function($) {

        /* Enables strike through on excluded DB table */
        DupliJs.Template.ExcludeTable = function(check) {
            var $cb = $(check);
            if ($cb.is(":checked")) {
                $cb.closest("label").css('textDecoration', 'line-through');
            } else {
                $cb.closest("label").css('textDecoration', 'none');
            }
        }

        //INIT
        $('#template-name').focus().select();
        //Default to cPanel tab if used
        $('#cpnl-enable').is(":checked") ? $('#dupli-cpnl-tab-lbl').trigger("click") : null;
        DupliJs.EnableInstallerPassword();

        //MU-Transfer buttons
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

        $('#dupli-template-form').submit(function() {
            DupliJs.Pack.FillExcludeTablesList();
        });

        //Defaults to Installer cPanel tab if 'Auto Select cPanel' is checked
        $('#installer_opts_cpnl_enable').is(":checked") ? $('#dupli-cpnl-tab-lbl').trigger("click") : null;
    });
</script>
