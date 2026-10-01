<?php

/**
 * Template selector for package setup page
 *
 * Rendered via duplicator_package_general_area_after hook.
 */

defined("ABSPATH") or die("");

use Duplicator\Addons\TemplateAddon\Controllers\TemplateToolsController;
use Duplicator\Models\TemplateEntity;
use Duplicator\Package\PackageUtils;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$manualTemplate = $tplMng->getDataValueObjRequired('manualTemplate', TemplateEntity::class);
$templates      = TemplateEntity::getAll();
if ($templates === false) {
    $templates = [];
}

$templatesUrl        = TemplateToolsController::getTemplateListURL();
$templateEditBaseUrl = TemplateToolsController::getTemplateEditURL();
?>
<div class="dupli-general-field-head">
    <label for="template_id" class="lbl-larger large">
        <?php esc_html_e('Template', 'duplicator-pro') ?>:
    </label>
    <i class="fa-solid fa-question-circle fa-sm dark-gray-color margin-bottom-0"
        data-tooltip-title="<?php esc_attr_e("Apply Template", 'duplicator-pro'); ?>"
        data-tooltip="<?php
                        esc_attr_e(
                            'An optional template configuration that can be applied to this backup setup.
                An [Unassigned] template will retain the settings from the last scan/build.',
                            'duplicator-pro'
                        ); ?>">
    </i>

    <div class="dupli-general-field-head-actions">
        <a
            href="<?php echo esc_url($templatesUrl); ?>"
            class="clear button gray xtiny margin-bottom-0"
            title="<?php esc_attr_e("List All Templates", 'duplicator-pro') ?>">
            <i class="fa-regular fa-clone"></i>
        </a>
        <button
            type="button"
            onclick="DupliJs.Pack.EditTemplate()"
            class="clear button gray xtiny margin-bottom-0"
            title="<?php esc_attr_e("Edit Selected Template", 'duplicator-pro') ?>">
            <i class="fa-solid fa-pen-to-square"></i>
        </button>
    </div>
</div>

<div class="dupli-general-field-control">
    <select
        data-parsley-ui-enabled="false"
        onChange="DupliJs.Pack.EnableTemplate();"
        name="template_id" id="template_id"
        aria-label="Prefill option with selected template">
        <option value="<?php echo intval($manualTemplate->getId()); ?>"><?php echo '[' . esc_html__('Unassigned', 'duplicator-pro') . ']' ?></option>
        <?php
        if (count($templates) == 0) {
            ?>
            <option value="-1">
                <?php esc_html_e('No Templates', 'duplicator-pro'); ?>
            </option>
            <?php
        } else {
            foreach ($templates as $template) {
                if ($template->is_manual) {
                    continue;
                }
                ?>
                <option value="<?php echo (int) $template->getId(); ?>">
                    <?php echo esc_html($template->name); ?>
                </option>
                <?php
            }
        }
        ?>
    </select>
</div>

<script>
jQuery(function($) {
    var packageTemplates = <?php TemplateEntity::getTemplatesFrontendListData(true, $templates); ?>

    DupliJs.Pack.GetTemplateById = function(templateId) {
        for (var i = 0; i < packageTemplates.length; i++) {
            var currentTemplate = packageTemplates[i];
            if (currentTemplate.id == templateId) {
                return currentTemplate;
            }
        }
        return null;
    };

    $.fn.selectOption = function(val) {
        this.val(val)
            .find('option')
            .prop('selected', false)
            .parent()
            .find('option[value="' + val + '"]')
            .prop('selected', true)
            .parent()
            .trigger('change');
        return this;
    };

    DupliJs.Pack.PopulateCurrentTemplate = function() {
        var selectedId = $('#template_id').val();
        var selectedTemplate = DupliJs.Pack.GetTemplateById(selectedId);
        if (selectedTemplate != null) {
            let formatInput = $('#package-name-format');
            formatInput.val(selectedTemplate.package_name_format);

            $("#package-notes").val(selectedTemplate.notes);

            $("#files-filter-on").prop("checked", selectedTemplate.archive_filter_on);
            $("#filter-names").prop("checked", selectedTemplate.archive_filter_names);

            let filterDirs = selectedTemplate.archive_filter_dirs.split(';').join(";\n");
            let filterFiles = selectedTemplate.archive_filter_files.split(';').join(";\n")
            let separator = filterDirs.length > 0 && filterFiles.length > 0 ? ";\n" : '';

            $("#filter-paths").val(filterDirs + separator + filterFiles);
            $("#filter-exts").val(selectedTemplate.archive_filter_exts);
            $("#dbfilter-on").prop("checked", selectedTemplate.database_filter_on);
            if (!$("#db-prefix-filter").data("force-checked")) {
                $("#db-prefix-filter").prop("checked", selectedTemplate.databasePrefixFilter);
            }
            $("#db-prefix-sub-filter").prop("checked", selectedTemplate.databasePrefixSubFilter);

            $(".dup-components-checkbox").each(function(i, component) {
                $(component).prop("checked", false);
            });

            selectedTemplate.components.forEach(function(component) {
                $("#" + component).prop("checked", true);
            });

            DupliJs.Pack.ToggleDBOnly()
            DupliJs.Pack.SetComponentsSelect();

            if (typeof selectedTemplate.filter_sites != 'undefined' && selectedTemplate.filter_sites.length > 0) {
                for (var i = 0; i < selectedTemplate.filter_sites.length; i++) {
                    var site_id = selectedTemplate.filter_sites[i];
                    var exclude_option = $('#mu-include').find("option[value=" + site_id + "]").first();
                    $("#mu-exclude").append(exclude_option.clone());
                    exclude_option.remove();
                }
            }

            //-- cPanel
            $("#cpnl-enable").prop("checked", selectedTemplate.installer_opts_cpnl_enable);
            $("#cpnl-host").val(selectedTemplate.installer_opts_cpnl_host);
            $("#cpnl-user").val(selectedTemplate.installer_opts_cpnl_user);

            $('.secure-on-input-wrapper input').prop("checked", false);
            $('.secure-on-input-wrapper input[value=' + selectedTemplate.installer_opts_secure_on + ']:enabled').prop("checked", true);
            $("#skipscan").prop("checked", selectedTemplate.installer_opts_skip_scan);
            $("#secure-pass").val(selectedTemplate.installerPassowrd);
            $("#cpnl-dbaction").selectOption(selectedTemplate.installer_opts_cpnl_db_action);
            $("#cpnl-dbhost").val(selectedTemplate.installer_opts_cpnl_db_host);
            $("#cpnl-dbname").val(selectedTemplate.installer_opts_cpnl_db_name);
            $("#cpnl-dbuser").val(selectedTemplate.installer_opts_cpnl_db_user);

            //-- Database
            if (selectedTemplate.database_filter_tables.length) {
                let databaseFilterTables = selectedTemplate.database_filter_tables.split(",");
                let tablesToExclude = $("#dup-db-tables-exclude");

                tablesToExclude.find(".dup-pseudo-checkbox").each(function() {
                    let node = $(this);
                    if (databaseFilterTables.includes(node.data('value'))) {
                        node.addClass('checked');
                    } else {
                        node.removeClass('checked');
                    }
                });
            }

            $("#dbhost").val(selectedTemplate.installer_opts_db_host);
            $("#dbname").val(selectedTemplate.installer_opts_db_name);
            $("#dbuser").val(selectedTemplate.installer_opts_db_user);

            $(document).trigger('duplicator:templateChanged', [selectedTemplate]);

        }

        //Default to Installer cPanel tab if used
        $('#cpnl-enable').is(":checked") ? $('#dupli-cpnl-tab-lbl').trigger("click") : $('#dupli-bsc-tab-lbl').trigger("click");
    };

    DupliJs.Pack.EditTemplate = function() {
        var manualTemplateID = <?php echo (int) $manualTemplate->getId(); ?>;
        var templateID = $('#template_id').val();
        var url;

        if (templateID <= 0 || templateID == manualTemplateID) {
            url = <?php echo json_encode($templatesUrl); ?>;
        } else {
            url = <?php echo json_encode($templateEditBaseUrl); ?> + '&package_template_id=' + templateID;
        }
        window.open(url, 'edit-template');
    };

    // Override EnableTemplate to include template population
    DupliJs.Pack.EnableTemplate = function() {
        $('#dupli-template-specific-area').show(0);
        DupliJs.Pack.PopulateCurrentTemplate();
        DupliJs.EnableInstallerPassword();
        DupliJs.Pack.ToggleFileFilters();
        DupliJs.Pack.ToggleDBFilters();
        DupliJs.Pack.ToggleActiveThemes();
        DupliJs.Pack.ToggleActivePlugins();
        DupliJs.Pack.ToggleDBExcluded();
        DupliJs.Pack.ToggleNoPrefixTables(false);
        DupliJs.Pack.ToggleNoSubsiteExistsTables(false);
    };

    // Initialize on load
    DupliJs.Pack.EnableTemplate();
});
</script>
