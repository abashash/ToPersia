<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Controllers;

use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryStatus;
use Duplicator\Addons\RecoveryAddon\Views\RecoveryViewHelper;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\WpUtils\PathUtil;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Package\FullBackupStatus;
use Duplicator\Models\TemplateEntity;
use Error;
use Exception;

class RecoveryController
{
    const VIEW_WIDGET_NO_PACKAGE_SET = 'nop';
    const VIEW_WIDGET_NOT_VALID      = 'notvalid';
    const VIEW_WIDGET_VALID          = 'valid';

    /** @var bool */
    protected static $isError = false;
    /** @var string */
    protected static $errorMessage = '';

    /**
     * @return bool check if Recovery is disallowed from wp-config.php
     *
     * @deprecated Use RecoveryPackage::isDisallow() instead
     */
    public static function isDisallow(): bool
    {
        return RecoveryPackage::isDisallow();
    }

    /**
     *
     * @return string
     */
    public static function getErrorMessage(): string
    {
        return self::$errorMessage;
    }

    /**
     * Get recovery page link
     *
     * @return string
     */
    public static function getRecoverPageLink(): string
    {
        return ControllersManager::getMenuLink(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            RecoveryToolsController::L2_SLUG_RECOVERY
        );
    }

    /**
     * Reset recovery point
     *
     * @return bool
     */
    public static function actionResetRecoveryPoint(): bool
    {
        try {
            RecoveryPackage::removeRecoveryFolder();
            RecoveryPackage::setRecoveablePackage(false);
        } catch (Exception | Error $e) {
            self::$isError      = true;
            self::$errorMessage = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Filter recovery package data for other addons
     *
     * @param array<string, mixed> $data Default data
     *
     * @return array<string, mixed>
     */
    public static function filterRecoveryPackageData(array $data): array
    {
        $recoverPackage = RecoveryPackage::getRecoverPackage();
        if (!$recoverPackage instanceof RecoveryPackage) {
            return $data;
        }

        return [
            'id'             => $recoverPackage->getPackageId(),
            'install_link'   => $recoverPackage->getInstallLink(),
            'is_out_to_date' => $recoverPackage->isOutToDate(),
            'package_life'   => $recoverPackage->getPackageLife('hours'),
            'page_link'      => self::getRecoverPageLink(),
        ];
    }

    /**
     * Register hooks for recovery info rendering on template and schedule pages.
     *
     * @return void
     */
    public static function registerRecoverableInfoHooks(): void
    {
        // Recovery icon in the General box status icons (new backup setup and template edit)
        add_action('duplicator_package_setup_status_icons', [self::class, 'renderSetupStatusIcon']);

        // Recovery icon in the template and schedule list flags cells and their legends
        add_action('duplicator_template_flags_icons', [self::class, 'renderTemplateFlagsIcon']);
        add_action('duplicator_schedule_flags_icons', [self::class, 'renderScheduleFlagsIcon']);
        add_filter('duplicator_template_flags_legend', [self::class, 'addFlagsLegendEntry']);
        add_filter('duplicator_schedule_flags_legend', [self::class, 'addFlagsLegendEntry']);

        // Schedule addon hooks
        add_action('duplicator_schedule_edit_extra_info', [self::class, 'renderScheduleEditRecoveryInfo']);
    }

    /**
     * Append the "Recovery Point Capable" badge to local storage rows
     * in the core storage select list.
     *
     * @param string                $suffix  HTML accumulated by previous filters
     * @param AbstractStorageEntity $storage The storage entity for the row
     *
     * @return string
     */
    public static function filterStorageSelectListTypeSuffix(string $suffix, AbstractStorageEntity $storage): string
    {
        if (!$storage->isLocal()) {
            return $suffix;
        }

        return $suffix . TplMng::getInstance()->render(
            'recoveryaddon/parts/recovery/storage_select_list_suffix',
            [],
            false
        );
    }

    /**
     * Render the recovery icon in the General box status icons. The icon is reactive:
     * it shows or hides with the current form selection, mirroring the recoverability
     * checks client-side.
     *
     * @param TemplateEntity $template The template entity
     *
     * @return void
     */
    public static function renderSetupStatusIcon(TemplateEntity $template): void
    {
        global $wpdb;

        TplMng::getInstance()->render(
            'recoveryaddon/parts/recovery/setup_status_icon',
            [
                'isRecoverable'      => (new RecoveryStatus($template))->isRecoverable(),
                'requiredComponents' => FullBackupStatus::COMPONENTS_REQUIRED,
                'wpCoreDirs'         => PathUtil::getWPCoreDirs(),
                'tablePrefix'        => $wpdb->prefix,
            ]
        );
    }

    /**
     * Render the recovery icon in the template list flags cell when the template is recoverable.
     *
     * @param TemplateEntity $template The template entity
     *
     * @return void
     */
    public static function renderTemplateFlagsIcon(TemplateEntity $template): void
    {
        if (!(new RecoveryStatus($template))->isRecoverable()) {
            return;
        }
        self::renderFlagsCellRecoveryIcon();
    }

    /**
     * Render the recovery icon in the schedule list flags cell when the schedule
     * targets a local storage and its template is recoverable.
     *
     * @param ScheduleEntity $schedule The schedule entity
     *
     * @return void
     */
    public static function renderScheduleFlagsIcon(ScheduleEntity $schedule): void
    {
        $template = $schedule->getTemplate();
        if ($template === false || !$schedule->hasLocalStorage()) {
            return;
        }
        if (!(new RecoveryStatus($template))->isRecoverable()) {
            return;
        }
        self::renderFlagsCellRecoveryIcon();
    }

    /**
     * Append the recovery entry to the template and schedule flags legends.
     *
     * @param array<int, array{icon: string, label: string, section?: string}> $entries Existing legend entries
     *
     * @return array<int, array{icon: string, label: string, section?: string}>
     */
    public static function addFlagsLegendEntry(array $entries): array
    {
        $entries[] = [
            'icon'  => RecoveryViewHelper::disasterIcon(false, 'dupli-recovery-flag-icon'),
            'label' => __('Backups created with these settings can be set as Disaster Recovery points', 'duplicator-pro'),
        ];
        return $entries;
    }

    /**
     * Render the shared recovery flags cell icon.
     *
     * @return void
     */
    protected static function renderFlagsCellRecoveryIcon(): void
    {
        ?>
        <span
            class="icon-wrapper"
            data-tooltip-title="<?php esc_attr_e('Disaster Recovery', 'duplicator-pro'); ?>"
            data-tooltip="<?php esc_attr_e('Backups created with these settings can be set as Disaster Recovery points.', 'duplicator-pro'); ?>">
            <?php RecoveryViewHelper::disasterIcon(true, 'dupli-recovery-flag-icon'); ?>
        </span>
        <?php
    }

    /**
     * Render recovery status row on schedule edit page.
     *
     * @param ScheduleEntity $schedule The schedule entity
     *
     * @return void
     */
    public static function renderScheduleEditRecoveryInfo(ScheduleEntity $schedule): void
    {
        $template = $schedule->getTemplate();
        ?>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e('Recovery Status', 'duplicator-pro'); ?></label></th>
            <td class="dup-recovery-template">
                <div class="margin-bottom-1">
                    <?php
                    if ($template !== false) {
                        if (!$schedule->hasLocalStorage()) {
                            echo '<span>' . esc_html__('Disabled', 'duplicator-pro') . '</span>';
                        } else {
                            TplMng::getInstance()->render('recoveryaddon/parts/recovery/widget/recoverable-template-info', [
                                'template' => $template,
                                'schedule' => $schedule,
                                'isList'   => false,
                            ]);
                        }
                    } else {
                        esc_html_e('Unavailable', 'duplicator-pro');
                        ?>
                        <i class="fa-solid fa-question-circle fa-sm dark-gray-color"
                            data-tooltip-title="<?php esc_attr_e('Recovery Status', 'duplicator-pro'); ?>"
                            data-tooltip="<?php
                                            esc_attr_e(
                                                'Status is unavailable. Please save the schedule to view recovery status',
                                                'duplicator-pro'
                                            );
                                            ?>"></i>
                    <?php } ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Render recovery widget with scripts (action callback for duplicator_render_recovery_widget).
     * Scripts are rendered only once per page load.
     *
     * @param array<string, mixed> $options widget options
     *
     * @return void
     */
    public static function renderWidgetWithScripts(array $options = []): void
    {
        static $scriptsRendered = false;

        self::renderRecoveryWidget($options);

        if (!$scriptsRendered) {
            $scriptsRendered = true;
            TplMng::getInstance()->render('recoveryaddon/admin_pages/tools/recovery/widget/recovery-widget-scripts');
        }
    }

    /**
     * Render recovery widget
     *
     * @param array<string, mixed> $options widget options
     * @param bool                 $echo    echo or return
     *
     * @return string
     */
    public static function renderRecoveryWidget(array $options = [], bool $echo = true): string
    {
        $options = array_merge(
            [
                'details'    => true,
                'selector'   => false,
                'subtitle'   => '',
                'copyLink'   => false,
                'copyButton' => true,
                'launch'     => true,
                'download'   => false,
                'info'       => true,
            ],
            $options
        );

        $recoverPackage    = RecoveryPackage::getRecoverPackage();
        $importFailMessage = '';

        if (!$recoverPackage instanceof RecoveryPackage) {
            $viewMode = self::VIEW_WIDGET_NO_PACKAGE_SET;
        } elseif (!$recoverPackage->isImportable($importFailMessage)) {
            $viewMode = self::VIEW_WIDGET_NOT_VALID;
        } else {
            $viewMode = self::VIEW_WIDGET_VALID;
        }

        $recoverPackageId = RecoveryPackage::getRecoverPackageId();

        return TplMng::getInstance()->render('recoveryaddon/admin_pages/tools/recovery/widget/recovery-widget', [
            'recoverPackage'      => $recoverPackage instanceof RecoveryPackage ? $recoverPackage : null,
            'recoverPackageId'    => is_int($recoverPackageId) ? $recoverPackageId : 0,
            'recoverablePackages' => RecoveryPackage::getRecoverablesPackages(),
            'selector'            => $options['selector'],
            'subtitle'            => $options['subtitle'],
            'displayDetails'      => $options['details'],
            'displayCopyLink'     => $options['copyLink'],
            'displayCopyButton'   => $options['copyButton'],
            'displayLaunch'       => $options['launch'],
            'displayDownload'     => $options['download'],
            'displayInfo'         => $options['info'],
            'viewMode'            => $viewMode,
            'importFailMessage'   => $importFailMessage,
        ], $echo);
    }
}
