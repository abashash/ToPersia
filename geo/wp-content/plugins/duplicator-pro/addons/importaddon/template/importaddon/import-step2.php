<?php



defined("ABSPATH") or die("");

use Duplicator\Libs\Snap\SnapWP;

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$postTypeCount = SnapWP::getPostTypesCount();
?>
<div class="dupli-import-header" >
    <h2 class="title">
        <b>
            <?php printf(esc_html__("Step %s of 2: Confirmation", 'duplicator-pro'), '<span class="red">2</span>'); ?>
        </b>
    </h2>
</div>
<div class="dupli-import-step2-wrapper" >
    <?php do_action('duplicator_import_step2_before_overview'); ?>

    <div class="dupli-import-box closable opened" >
        <div class="box-title" >
            <?php esc_html_e('System Overview', 'duplicator-pro'); ?>
        </div>
        <div class="box-content">
            <div id="dupli-recovery-details-overview" >
                <div>
                    <?php esc_html_e("This site currently contains", 'duplicator-pro'); ?>:
                </div>

                <table class="margin-left-2" >
                    <?php foreach ($postTypeCount as $label => $count) { ?>
                        <tr>
                            <td><?php echo esc_html($label); ?></td>
                            <td class="text-right"><?php echo (int) $count; ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <p>
                    <?php esc_html_e("This process will:", 'duplicator-pro') ?>
                </p>
                <ul>
                    <li>
                        <i class="far fa-check-circle"></i>&nbsp;
                        <?php esc_html_e("Launch the interactive installer wizard to install this new Backup.", 'duplicator-pro'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="dupli-import-confirm-buttons">
        <input 
            id="dupli-import-launch-installer-cancel" 
            type="button" class="button secondary hollow small recovery-reset" 
            value="<?php esc_attr_e('Cancel', 'duplicator-pro'); ?>"
        >&nbsp;
        <button 
            id="dupli-import-launch-installer-confirm" 
            type="button" 
            class="button primary small" 
            onclick="DupliJs.ImportManager.confirmLaunchInstaller();"
        >
            <i class="fa fa-bolt fa-sm"></i> <?php esc_html_e('Launch Installer', 'duplicator-pro'); ?>
        </button>
    </div>
</div>
