<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Ajax;

use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryController;
use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryToolsController;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Ajax\AbstractAjaxService;
use Duplicator\Ajax\AjaxWrapper;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Package\DupPackage;
use Duplicator\Utils\Logging\DupLog;
use Exception;

/**
 * AJAX service for recovery point operations.
 */
class ServicesRecovery extends AbstractAjaxService
{
    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init(): void
    {
        $this->addAjaxCall('wp_ajax_duplicator_get_recovery_widget', 'getWidget');
        $this->addAjaxCall('wp_ajax_duplicator_set_recovery', 'setRecovery');
        $this->addAjaxCall('wp_ajax_duplicator_reset_recovery', 'resetRecovery');
        $this->addAjaxCall('wp_ajax_duplicator_disaster_launcher_download', 'launcherDownload');
        $this->addAjaxCall('wp_ajax_duplicator_get_recovery_box_content', 'recoveryBoxContent');
    }

    /**
     * Get recovery widget detail elements
     *
     * @param string $fromPageTab from page/tab unique id
     *
     * @return bool[]
     */
    protected static function getRecoveryDetailsOptions($fromPageTab): array
    {
        if ($fromPageTab == ControllersManager::getPageUniqueId(ControllersManager::TOOLS_SUBMENU_SLUG, RecoveryToolsController::L2_SLUG_RECOVERY)) {
            $detailsOptions = [
                'selector'   => true,
                'copyLink'   => true,
                'copyButton' => true,
                'launch'     => true,
                'download'   => true,
                'info'       => true,
            ];
        } else {
            /**
             * Filter to allow addons to provide recovery detail options based on the originating page.
             *
             * @param array<string, bool> $detailsOptions Detail options (empty = not handled)
             * @param string              $fromPageTab    The originating page tab identifier
             */
            $detailsOptions = apply_filters('duplicator_recovery_detail_options', [], $fromPageTab);
        }

        if (!is_array($detailsOptions)) {
            $detailsOptions = [];
        }

        return $detailsOptions;
    }

    /**
     * Set recovery callback
     *
     * @return array<string, mixed>
     */
    public static function setRecoveryCallback(): array
    {
        $recPackageId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'recovery_package', -1);
        DupLog::trace("SET RECOVERY PACKAGE ID {$recPackageId}");
        if ($recPackageId !== RecoveryPackage::getRecoverPackageId()) {
            DupLog::trace("RESET OLD RECORY PACKAGE ID " . RecoveryPackage::getRecoverPackageId());
            RecoveryPackage::removeRecoveryFolder();

            $errorMessage = '';
            if (!RecoveryPackage::setRecoveablePackage($recPackageId, $errorMessage)) {
                $urlImport = apply_filters('duplicator_deploy_settings_url', '');

                $msg  = sprintf(__("Error: <b>%s</b>", 'duplicator-pro'), $errorMessage) . '<br><br>';
                $msg .= __("The old Recovery Point was removed but this Backup can't be set as the Recovery Point.", 'duplicator-pro') . '<br>';
                $msg .= __("Possible solutions:", 'duplicator-pro') . '<br>';
                $msg .= sprintf(
                    _x(
                        '- In some hosting the execution of PHP scripts is blocked in the wp-content folder, %1$s[try setting a custom recovery path]%2$s',
                        '%1$s and %2$s represents the opening and closing HTML tags for an anchor or link',
                        'duplicator-pro'
                    ),
                    '<a href="' . esc_url($urlImport) . '" target="_blank">',
                    '</a>'
                ) . '<br>';
                $msg .= __(
                    "- you may still be able to download the Backup manually and perform an import or a classic backup installation.
                    If you wish to install the Backup on the site where it was created, the restore backup mode should be activated.",
                    'duplicator-pro'
                );
                throw new Exception($msg);
            }
            DupLog::trace("RECOVER PACKAGE SET");
        }

        $recoverPackage = RecoveryPackage::getRecoverPackage();
        DupLog::trace("RECOVER PACKAGE READED");
        if (!$recoverPackage instanceof RecoveryPackage) {
            throw new Exception(esc_html__('Can\'t get recover Backup', 'duplicator-pro'));
        }
        $fromPageTab    = SnapUtil::sanitizeDefaultInput(INPUT_POST, 'fromPageTab', false);
        $detailsOptions = self::getRecoveryDetailsOptions($fromPageTab);
        DupLog::trace("RECOVER PACKAGE DETAILS OPTIONS READED");

        $subtitle = __('Copy the Link and keep it in case of need or download Disaster Recovery Launcher.', 'duplicator-pro');
        return [
            'id'             => $recoverPackage->getPackageId(),
            'name'           => $recoverPackage->getPackageName(),
            'recoveryLink'   => $recoverPackage->getInstallLink(),
            'adminMessage'   => RecoveryController::renderRecoveryWidget([
                'selector'   => false,
                'subtitle'   => $subtitle,
                'copyLink'   => false,
                'copyButton' => true,
                'launch'     => false,
                'download'   => true,
                'info'       => true,
            ], false),
            'packageDetails' => RecoveryController::renderRecoveryWidget($detailsOptions, false),
        ];
    }

    /**
     * Set recovery action
     *
     * @return void
     */
    public function setRecovery(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'setRecoveryCallback',
            ],
            'duplicator_set_recovery',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce', ''),
            CapMng::CAP_BACKUP_RESTORE
        );
    }

    /**
     * Get widget callback
     *
     * @return string[]
     */
    public static function getWidgetCallback(): array
    {
        $fromPageTab    = SnapUtil::sanitizeDefaultInput(INPUT_POST, 'fromPageTab', false);
        $detailsOptions = self::getRecoveryDetailsOptions($fromPageTab);

        return [
            'widget' => RecoveryController::renderRecoveryWidget($detailsOptions, false),
        ];
    }

    /**
     * Get widget action
     *
     * @return void
     */
    public function getWidget(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'getWidgetCallback',
            ],
            'duplicator_get_recovery_widget',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce', ''),
            CapMng::CAP_BACKUP_RESTORE
        );
    }

    /**
     * Reset recovery callback
     *
     * @return string[]
     */
    public static function resetRecoveryCallback(): array
    {
        if (RecoveryController::actionResetRecoveryPoint() === false) {
            throw new Exception(RecoveryController::getErrorMessage());
        }

        $fromPageTab    = SnapUtil::sanitizeDefaultInput(INPUT_POST, 'fromPageTab', false);
        $detailsOptions = self::getRecoveryDetailsOptions($fromPageTab);

        return [
            'adminMessage'   => RecoveryController::renderRecoveryWidget([], false),
            'packageDetails' => RecoveryController::renderRecoveryWidget($detailsOptions, false),
        ];
    }

    /**
     * Reset recovery action
     *
     * @return void
     */
    public function resetRecovery(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'resetRecoveryCallback',
            ],
            'duplicator_reset_recovery',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce', ''),
            CapMng::CAP_BACKUP_RESTORE
        );
    }

    /**
     * Launcher download callback
     *
     * @return array<string,scalar>
     */
    public static function launcherDownloadCallback(): array
    {
        $result = [
            'success'     => false,
            'message'     => '',
            'fileContent' => '',
            'fileName'    => '',
        ];

        try {
            if (($recoverPackage = RecoveryPackage::getRecoverPackage()) == false) {
                throw new Exception(__('Can\'t get recover Backup', 'duplicator-pro'));
            }

            $result['fileContent'] = TplMng::getInstance()->render(
                'recoveryaddon/parts/recovery/launcher_content',
                ['recoverPackage' => $recoverPackage],
                false
            );

            $result['fileName'] = $recoverPackage->getLauncherFileName();
            $result['success']  = true;
        } catch (Exception $ex) {
            $result['success'] = false;
            $result['message'] = $ex->getMessage();
            DupLog::traceError($ex->getMessage());
        }

        return $result;
    }

    /**
     * Download disaster recovery launcher
     *
     * @return void
     */
    public function launcherDownload(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'launcherDownloadCallback',
            ],
            'duplicator_disaster_launcher_download',
            SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'nonce', ''),
            CapMng::CAP_BACKUP_RESTORE
        );
    }

    /**
     * Recovery box content callback
     *
     * @return array<string,scalar>
     */
    public static function recoveryBoxContentCallback(): array
    {
        $result = [
            'success'       => false,
            'message'       => '',
            'content'       => '',
            'isRecoverable' => false,
        ];

        try {
            $packageId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'packageId', 0);

            if (($package = DupPackage::getById($packageId)) === false) {
                throw new Exception(__('Backup is invalid', 'duplicator-pro'));
            }

            $result['content']       = TplMng::getInstance()->render(
                'recoveryaddon/admin_pages/packages/recovery_info/row_recovery_box',
                ['package' => $package],
                false
            );
            $result['isRecoverable'] = RecoveryPackage::isPackageIdRecoverable($package->getId());
            $result['success']       = true;
        } catch (Exception $ex) {
            $result['success'] = false;
            $result['message'] = $ex->getMessage();
            DupLog::traceError($ex->getMessage());
        }

        return $result;
    }

    /**
     * Get recovery box content
     *
     * @return void
     */
    public function recoveryBoxContent(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'recoveryBoxContentCallback',
            ],
            'duplicator_get_recovery_box_content',
            SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'nonce', ''),
            CapMng::CAP_BACKUP_RESTORE
        );
    }
}
