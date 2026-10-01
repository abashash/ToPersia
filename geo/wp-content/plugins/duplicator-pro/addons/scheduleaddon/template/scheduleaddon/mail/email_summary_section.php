<?php

/**
 * Schedule section for email summary
 */

defined("ABSPATH") or die("");

use Duplicator\Utils\Email\EmailHelper;

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$schedules = $tplMng->getDataValueArrayRequired('schedules');
?>
<p <?php EmailHelper::printStyle('p stats-title'); ?>>
    <strong><?php esc_html_e('New Schedules:', 'duplicator-pro'); ?></strong>
</p>
<table class="dup-table schedules" <?php EmailHelper::printStyle('table stats-tbl'); ?>>
    <tr <?php EmailHelper::printStyle('tr'); ?>>
        <th class="cell-name" <?php EmailHelper::printStyle('th'); ?>>
            <?php esc_html_e('Schedule Name', 'duplicator-pro'); ?>
        </th>
        <th class="cell-storage" <?php EmailHelper::printStyle('th'); ?>>
            <?php esc_html_e('Storage(s)', 'duplicator-pro'); ?>
        </th>
    </tr>
    <?php foreach ($schedules as $scheduleInfo) : ?>
    <tr <?php EmailHelper::printStyle('tr'); ?>>
        <td class="cell-name" <?php EmailHelper::printStyle('td stats-cell'); ?>>
            <?php echo esc_html($scheduleInfo['name']); ?>
        </td>
        <td class="cell-storage" <?php EmailHelper::printStyle('td stats-cell'); ?>>
            <?php echo esc_html(implode(', ', $scheduleInfo['storages'])); ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
