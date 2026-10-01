<?php

use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Models\TemplateEntity;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryStatus;
use Duplicator\Addons\RecoveryAddon\Views\RecoveryViewHelper;

defined('ABSPATH') || defined('DUPXABSPATH') || exit;


/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 */
$recoveryStatus = $tplMng->getDataValueObjRequired('recoveryStatus', RecoveryStatus::class);

$tooltipContent = (string) apply_filters(
    'duplicator_recovery_widget_tooltip',
    __(
        'A Template is not required to have a recovery point. For example if backing up only a database
        then the recovery will always be disabled and may be desirable.',
        'duplicator-pro'
    ),
    $recoveryStatus
);

$templateInfo = apply_filters('duplicator_recovery_widget_info', null, $recoveryStatus);
if ($templateInfo === null) {
    /** @var TemplateEntity */
    $template = $recoveryStatus->getObject();
    $infoRows = [
        [
            'label' => __("Template", 'duplicator-pro'),
            'value' => $template->name,
        ],
        [
            'label' => __('Notes', 'duplicator-pro'),
            'value' => (strlen($template->notes)) ? $template->notes : __("- no notes -", 'duplicator-pro'),
        ],
    ];
} else {
    $infoRows = $templateInfo;
}
?>
<div class="dup-recover-dlg-title">
    <b><?php RecoveryViewHelper::disasterIcon() ?>&nbsp;<?php esc_html_e('Status', 'duplicator-pro'); ?>: </b>
    <?php esc_html_e('Disabled', 'duplicator-pro'); ?>
    <sup>
        <i class="fas fa-question-circle fa-xs"
            data-tooltip-title="<?php esc_html_e('Recovery Status', 'duplicator-pro'); ?>"
            data-tooltip="<?php echo esc_attr($tooltipContent); ?>">
        </i>
    </sup>
</div>

<div class="dup-recover-dlg-subinfo">
    <table>
        <?php foreach ($infoRows as $row) { ?>
            <tr>
                <td><b><?php echo esc_html($row['label']); ?>:</b>&nbsp;</td>
                <td><?php echo wp_kses_post($row['value']); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
TplMng::getInstance()->render(
    'parts/recovery/exclude_data_box',
    ['recoverStatus' => $recoveryStatus]
);
