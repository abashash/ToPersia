<?php

/**
 * Recovery icon of the General box status icons (package setup and template edit pages).
 *
 * The icon is reactive: the script mirrors the recoverability checks on the current
 * form selection (required components, WP core directory filters, WP-prefixed table
 * filters, excluded subsites and, when the form selects storages, at least one local
 * storage) and shows or hides the icon accordingly. On the Template edit page the
 * form has no storage inputs and the storage check is skipped, matching the
 * template-only server-side eligibility.
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$isRecoverable      = $tplMng->getDataValueBoolRequired('isRecoverable');
$requiredComponents = $tplMng->getDataValueArrayRequired('requiredComponents');
$wpCoreDirs         = $tplMng->getDataValueArrayRequired('wpCoreDirs');
$tablePrefix        = $tplMng->getDataValueStringRequired('tablePrefix');
?>
<span
    id="dupli-recovery-status-icon"
    class="<?php echo ($isRecoverable ? '' : 'no-display'); ?>"
    data-tooltip-title="<?php esc_attr_e('Disaster Recovery', 'duplicator-pro'); ?>"
    data-tooltip="<?php esc_attr_e('Backups created with these settings can be set as Disaster Recovery points.', 'duplicator-pro'); ?>">
    <i class="fa-solid fa-house-fire fa-sm dupli-recovery-flag-icon"></i>
</span>
<script>
    (function($) {
        var wpCoreDirs = <?php echo wp_json_encode(array_values($wpCoreDirs)); ?>;
        var tablePrefix = <?php echo wp_json_encode($tablePrefix); ?>;
        // The component checkbox ids are the FullBackupStatus::COMPONENTS_REQUIRED values
        var requiredComponents = <?php echo wp_json_encode(array_values($requiredComponents)); ?>;

        DupliJs.Recovery = DupliJs.Recovery || {};

        DupliJs.Recovery.IsSetupRecoverable = function() {
            for (var i = 0; i < requiredComponents.length; i++) {
                var $component = $('#' + requiredComponents[i]);
                if ($component.length > 0 && !$component.is(':checked')) {
                    return false;
                }
            }

            if ($('#files-filter-on').is(':checked')) {
                var paths = String($('#filter-paths').val() || '').split(';');
                for (var p = 0; p < paths.length; p++) {
                    if (wpCoreDirs.indexOf(paths[p].trim()) !== -1) {
                        return false;
                    }
                }
            }

            if ($('#dbfilter-on').is(':checked')) {
                var prefixedFound = false;
                $('#dup-db-tables-exclude .dup-pseudo-checkbox.checked').each(function() {
                    if (String($(this).data('value')).indexOf(tablePrefix) === 0) {
                        prefixedFound = true;
                    }
                });
                if (prefixedFound) {
                    return false;
                }
            }

            if ($('#mu-exclude option').length > 0) {
                return false;
            }

            // Recovery points require a local archive: when the form selects the
            // Backup storages, at least one checked storage must be local.
            var $storageInputs = $('input[name="_storage_ids[]"]');
            if (
                $storageInputs.length > 0 &&
                $storageInputs.filter(':checked:not(:disabled)[data-dupli-is-local="1"]').length === 0
            ) {
                return false;
            }

            return true;
        };

        DupliJs.Recovery.UpdateSetupStatusIcon = function() {
            $('#dupli-recovery-status-icon').toggleClass('no-display', !DupliJs.Recovery.IsSetupRecoverable());
        };

        jQuery(function() {
            // Deferred so the refresh reads the state left by the handler that triggered it
            var refresh = function() {
                setTimeout(DupliJs.Recovery.UpdateSetupStatusIcon, 0);
            };

            $(document).on(
                'change',
                '.dup-components-checkbox, input[name="auto-select-components"], #files-filter-on, #dbfilter-on, input[name="_storage_ids[]"]',
                refresh
            );
            $(document).on('input change', '#filter-paths', refresh);
            $(document).on('click', '#dup-db-tables-exclude .dup-pseudo-checkbox, #mu-include-btn, #mu-exclude-btn', refresh);
            $(document).on('duplicator:templateChanged', refresh);

            refresh();
        });
    })(jQuery);
</script>
