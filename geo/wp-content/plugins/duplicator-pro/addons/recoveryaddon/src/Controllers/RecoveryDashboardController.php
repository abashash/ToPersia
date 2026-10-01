<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Controllers;

use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Core\CapMng;
use Duplicator\Package\DupPackage;

/**
 * Hooks into the Dashboard widget to show recovery point status.
 */
class RecoveryDashboardController
{
    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('duplicator_dashboard_widget_sections', [self::class, 'renderSection']);
    }

    /**
     * Render recovery point section in dashboard widget
     *
     * @return void
     */
    public static function renderSection(): void
    {
        if (!CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) {
            return;
        }

        $recoveryUrl       = esc_url(RecoveryController::getRecoverPageLink());
        $recoverDateString = self::getDateString();
        $hasRecovery       = RecoveryPackage::getRecoverPackageId() !== false;
        ?>
        <li class="dup-flex-content">
            <span class="dup-section-label-fixed-width">
                <span class="dashicons dashicons-image-rotate gary"></span>
                <a href="<?php echo esc_url($recoveryUrl); ?>"><?php
                    esc_html_e('Recovery Point', 'duplicator-pro');
                ?></a>
            </span>
            <span>
                <?php if (!$hasRecovery) { ?>
                    <span class="maroon"><b><?php esc_html_e('Not set', 'duplicator-pro'); ?></b></span>
                <?php } else { ?>
                    <span class="green"><b><?php esc_html_e('Set On', 'duplicator-pro'); ?></b></span>&nbsp;
                    <b><?php echo esc_html($recoverDateString); ?></b>
                <?php } ?>
            </span>
        </li>
        <?php
    }

    /**
     * Get recovery date string for display
     *
     * @return string
     */
    private static function getDateString(): string
    {
        $recoverId = RecoveryPackage::getRecoverPackageId();
        if ($recoverId === false) {
            return '';
        }

        $recoverPackage = DupPackage::getById($recoverId);
        if ($recoverPackage === false) {
            return '';
        }

        $recoverTime  = (int) strtotime($recoverPackage->getCreated());
        $recoverDate  = date_i18n(get_option('date_format'), $recoverTime);
        $recoverHours = date_i18n(get_option('time_format'), $recoverTime);

        return $recoverDate . ' ' . $recoverHours;
    }
}
