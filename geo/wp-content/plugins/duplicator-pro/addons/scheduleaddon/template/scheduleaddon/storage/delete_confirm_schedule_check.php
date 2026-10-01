<?php

/**
 * Storage delete confirmation - schedule check script
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$nonce = $tplMng->getDataValueStringRequired('nonce');
?>
<template id="dup-schedule-check-tpl">
    <div class="schedule-area">
        <b><?php esc_html_e('Linked Schedules', 'duplicator-pro'); ?>:</b><br/>
        <small><?php esc_html_e('Schedules linked to the storage items above', 'duplicator-pro'); ?>:</small>
        <div class="schedule-progress">
            <i class="fas fa-circle-notch fa-spin"></i>
            <?php esc_html_e('Finding Schedule Links...  Please wait', 'duplicator-pro'); ?>
        </div>
        <small>
            <?php esc_html_e('To remove storage items and unlink schedules click OK.', 'duplicator-pro'); ?>
            <?php
            printf(
                esc_html_x(
                    'Schedules with asterisk%1$s will be deactivated if storage is removed.',
                    '%1$s is an asterisk symbol',
                    'duplicator-pro'
                ),
                '<span class="maroon">*</span>'
            );
            ?>
        </small>
    </div>
</template>

<template id="dup-schedule-item-tpl">
    <div class="schedule-item">
        <i class="far fa-clock"></i>
        <a data-ref="link" href=""></a>
        <span class="maroon" data-ref="asterisk"></span>
    </div>
</template>

<script>
(function($) {
    $(document).on('duplicator_storage_delete_confirm', function(e, idList, dlgID) {
        var $content = $('#' + dlgID + '_message');

        var $tpl = $(document.getElementById('dup-schedule-check-tpl').content.cloneNode(true));
        var $progress = $tpl.find('.schedule-progress');
        $progress.attr('id', dlgID + '-schedule-progress');
        $content.append($tpl);

        DupliJs.Util.ajaxWrapper(
            {
                action: 'duplicator_schedule_get_storage_links',
                storage_ids: idList,
                nonce: '<?php echo esc_js($nonce); ?>'
            },
            function(result, data, funcData) {
                var $target = $('#' + dlgID + '-schedule-progress');
                if (funcData.schedules && funcData.schedules.length > 0) {
                    var itemTpl = document.getElementById('dup-schedule-item-tpl');
                    var $items = $();
                    funcData.schedules.forEach(function(schedule) {
                        var $item = $(itemTpl.content.cloneNode(true));
                        $item.find('[data-ref="link"]').attr('href', schedule.editURL).text(schedule.name);
                        $item.find('[data-ref="asterisk"]').text(schedule.hasOneStorage ? '*' : '');
                        $items = $items.add($item);
                    });
                    $target.empty().append($items);
                } else {
                    $target.text('<?php echo esc_js(__('- No linked schedules found -', 'duplicator-pro')); ?>');
                }
            },
            function() {
                $('#' + dlgID + '-schedule-progress').html(
                    '<i class="fas fa-exclamation-triangle"></i> <?php esc_html_e('Unable to get schedule data.', 'duplicator-pro'); ?>'
                );
            }
        );
    });
})(jQuery);
</script>
