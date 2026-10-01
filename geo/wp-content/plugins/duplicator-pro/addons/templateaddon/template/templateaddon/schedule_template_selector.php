<?php

/**
 * Template selector for schedule edit page
 *
 * Rendered via duplicator_schedule_edit_after_name hook.
 */

defined("ABSPATH") or die("");

use Duplicator\Addons\TemplateAddon\Controllers\TemplateToolsController;
use Duplicator\Models\TemplateEntity;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$selectedTemplateId = $tplMng->getDataValueInt('selectedTemplateId', -1);

$templatesPageUrl = TemplateToolsController::getTemplateListURL();
$editTemplateUrl  = TemplateToolsController::getTemplateEditURL();
?>
<tr valign="top">
    <th scope="row">
        <label><?php esc_html_e('Backup Template', 'duplicator-pro'); ?></label>
    </th>
    <td>
        <div class="schedule-template display-flex">
            <div>
                <select id="schedule-template-selector" class="width-medium margin-bottom-0" required data-parsley-group="standard">
                    <?php
                    $templates = TemplateEntity::getAllWithoutManualMode();
                    if ($templates === false) {
                        $templates = [];
                    }
                    if (count($templates) == 0) {
                        $no_templates = __('No Templates Found', 'duplicator-pro');
                        printf('<option value="">%1$s</option>', esc_html($no_templates));
                    } else {
                        echo "<option value='' selected='true'>" . esc_html__("&lt;Choose A Template&gt;", 'duplicator-pro') . "</option>";
                        foreach ($templates as $template) {
                            ?>
                            <option
                                <?php selected($selectedTemplateId, $template->getId()); ?>
                                value="<?php echo (int) $template->getId(); ?>">
                                <?php echo esc_html($template->name); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
                <div class="margin-bottom-1">
                    <small>
                        <a href="<?php echo esc_url($templatesPageUrl); ?>" target="edit-template">
                            [<?php esc_html_e("Show All Templates", 'duplicator-pro') ?>]
                        </a>
                    </small>
                </div>
            </div>
            <div>
                <a
                    id="schedule-template-edit-btn"
                    href="javascript:void(0)"
                    onclick="DupliJs.Schedule.EditTemplate()"
                    style="display:none"
                    class="pack-temp-btns button hollow secondary small margin-bottom-0"
                    title="<?php esc_attr_e("Edit Selected Template", 'duplicator-pro') ?>">
                    <i class="far fa-edit"></i>
                </a>
                <a
                    id="schedule-template-add-btn"
                    href="<?php echo esc_url($editTemplateUrl); ?>"
                    class="pack-temp-btns button hollow secondary small margin-bottom-0"
                    title="<?php esc_attr_e("Add New Template", 'duplicator-pro') ?>"
                    target="edit-template">
                    <i class="far fa-plus-square"></i>
                </a>
                <a
                    id="schedule-template-sync-btn"
                    href="javascript:window.location.reload()"
                    class="pack-temp-btns button hollow secondary small margin-bottom-0"
                    title="<?php esc_attr_e("Refresh Template List", 'duplicator-pro') ?>">
                    <i class="fas fa-sync-alt"></i>
                </a>
                &nbsp;
                <i
                    class="fa-solid fa-question-circle fa-sm dark-gray-color"
                    data-tooltip-title="<?php esc_attr_e("Template Details", 'duplicator-pro'); ?>"
                    data-tooltip="<?php
                                    esc_attr_e(
                                        'The template specifies which files and database tables should be included in the archive.<br/><br/>
                    Choose from an existing template or create a new one by clicking the "Add New Template" button.
                    To edit a template, select it and then click the "Edit Selected Template" button.',
                                        'duplicator-pro'
                                    );
                                    ?>">
                </i>
            </div>
        </div>
    </td>
</tr>

<script>
window.DupliJs = window.DupliJs || {};
DupliJs.Schedule = DupliJs.Schedule || {};
jQuery(document).ready(function($) {
    DupliJs.Schedule.EditTemplate = function() {
        var templateID = $('#schedule-template-selector').val();
        var url = <?php echo wp_json_encode($editTemplateUrl); ?> + '&package_template_id=' + templateID;
        window.open(url, 'edit-template');
    };

    DupliJs.Schedule.ToggleTemplateEditBtn = function() {
        $('#schedule-template-edit-btn, #schedule-template-add-btn, #schedule-template-sync-btn').hide();
        if ($("#schedule-template-selector").val() > 0) {
            $('#schedule-template-edit-btn').show();
        } else {
            $('#schedule-template-add-btn, #schedule-template-sync-btn').show();
        }
    }

    DupliJs.Schedule.SyncTemplateId = function() {
        $('#schedule-template-id').val($('#schedule-template-selector').val());
    };

    DupliJs.Schedule.SyncTemplateId();
    DupliJs.Schedule.ToggleTemplateEditBtn();

    $("#schedule-template-selector").change(function() {
        DupliJs.Schedule.SyncTemplateId();
        DupliJs.Schedule.ToggleTemplateEditBtn();
    });
});
</script>
