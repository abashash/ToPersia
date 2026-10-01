<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Controllers;

use Duplicator\Addons\RecoveryAddon\Ajax\ServicesRecovery;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryStatus;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Views\TplMng;
use Duplicator\Models\TemplateEntity;
use Duplicator\Package\AbstractPackage;
use Duplicator\Package\DupPackage;
use Duplicator\Addons\RecoveryAddon\Views\RecoveryViewHelper;
use Error;
use Exception;

/**
 * Hooks into the Packages page to add recovery UI, flags, and actions.
 */
class RecoveryPackagesController
{
    const ACTION_SET_RECOVERY_POINT = 'set_recovery_point';

    const FLAG_DISASTER_AVAIABLE = 'DISASTER_AVAIABLE';
    const FLAG_DISASTER_SET      = 'DISASTER_SET';

    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        $packagesSlug = ControllersManager::PACKAGES_SUBMENU_SLUG;
        add_filter('duplicator_page_actions_' . $packagesSlug, [self::class, 'pageActions']);

        // Package flags — inject disaster recovery flags
        add_filter('duplicator_package_update_flags', [self::class, 'updateRecoveryFlags'], 10, 2);

        // Template recovery eligibility (used by ScheduleAddon)
        add_filter('duplicator_is_template_recovery_eligible', [self::class, 'isTemplateRecoveryEligible'], 10, 2);

        // Packages toolbar — recovery button
        add_action('duplicator_packages_toolbar_buttons', [self::class, 'renderToolbarButton']);

        // Package flags icons — disaster icons
        add_action('duplicator_package_flags_icons', [self::class, 'renderFlagIcons'], 20);
        add_filter('duplicator_package_flags_legend', [self::class, 'addLegendEntries']);
        add_filter('duplicator_package_flags_tooltip_groups', [self::class, 'appendTooltipGroup'], 20, 2);

        // Package row classes — recovery highlight
        add_filter('duplicator_package_row_classes', [self::class, 'addPackageRowClasses'], 10, 2);

        // Package detail actions — disaster recovery button
        add_action('duplicator_package_detail_actions', [self::class, 'renderPackageDetailAction']);

        // Packages page scripts — recovery JS and dialogs
        add_action('duplicator_packages_page_scripts', [self::class, 'renderPackagesPageScripts']);
    }

    /**
     * Add recovery point page action
     *
     * @param PageAction[] $actions Current actions
     *
     * @return PageAction[]
     */
    public static function pageActions(array $actions): array
    {
        $actions[] = new PageAction(
            self::ACTION_SET_RECOVERY_POINT,
            [
                self::class,
                'setRecoveryPoint',
            ],
            [ControllersManager::PACKAGES_SUBMENU_SLUG]
        );

        return $actions;
    }

    /**
     * Set recovery point action handler
     *
     * @return array<string, mixed>
     */
    public static function setRecoveryPoint(): array
    {
        $result = ['recoverySet' => false];

        try {
            $recoveryData             = ServicesRecovery::setRecoveryCallback();
            $result['recoverySet']    = true;
            $result['successMessage'] = $recoveryData['adminMessage'];
        } catch (Exception | Error $e) {
            $result['recoverySet']  = false;
            $result['errorMessage'] = $e->getMessage();
            return $result;
        }

        return $result;
    }

    /**
     * Update package flags with disaster recovery flags
     *
     * @param string[]        $flags   Current flags
     * @param AbstractPackage $package Package
     *
     * @return string[]
     */
    public static function updateRecoveryFlags(array $flags, AbstractPackage $package): array
    {
        $flags = array_values(array_diff($flags, [self::FLAG_DISASTER_SET, self::FLAG_DISASTER_AVAIABLE]));

        if ($package->getStatus() < AbstractPackage::STATUS_COMPLETE) {
            return $flags;
        }

        if (RecoveryPackage::getRecoverPackageId() === $package->getId()) {
            $flags[] = self::FLAG_DISASTER_SET;
        } else {
            $status = new RecoveryStatus($package);
            if ($status->isRecoverable()) {
                $flags[] = self::FLAG_DISASTER_AVAIABLE;
            }
        }

        return $flags;
    }

    /**
     * Check if a template is recovery-eligible
     *
     * @param bool           $eligible Current eligibility
     * @param TemplateEntity $template Template entity
     *
     * @return bool
     */
    public static function isTemplateRecoveryEligible(bool $eligible, $template): bool
    {
        if ($eligible) {
            return true;
        }

        $status = new RecoveryStatus($template);
        return $status->isRecoverable();
    }

    /**
     * Render recovery toolbar button in packages page
     *
     * @return void
     */
    public static function renderToolbarButton(): void
    {
        if (!CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) {
            return;
        }

        ?>
        <span
            class="dupli-toolbar-recovery-info
            button hollow secondary small <?php echo (RecoveryPackage::getRecoverPackageId() === false ? 'dup-recovery-unset' : ''); ?>"
            data-tooltip-title="<?php esc_attr_e("Disaster Recovery", 'duplicator-pro') ?>"
            data-tooltip="<?php esc_attr_e("Quickly restore this site to a specific point in time.", 'duplicator-pro') ?>"
        >
            <?php RecoveryViewHelper::disasterIcon(); ?>
        </span>
        <?php
    }

    /**
     * Render disaster recovery flag icons per-package
     *
     * @param DupPackage $package Package
     *
     * @return void
     */
    public static function renderFlagIcons(DupPackage $package): void
    {
        if (!CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) {
            return;
        }

        if (!$package->hasFlag(self::FLAG_DISASTER_AVAIABLE) && !$package->hasFlag(self::FLAG_DISASTER_SET)) {
            return;
        }

        if ($package->hasFlag(self::FLAG_DISASTER_SET)) {
            $title      = __('Disaster Recovery URL is set on this Backup', 'duplicator-pro');
            $colorClass = 'green';
        } else {
            $title      = __('This Backup is available for Disaster Recovery', 'duplicator-pro');
            $colorClass = 'dupli-recovery-flag-icon';
        }
        ?>
        <span
            class="dupli-btn-open-recovery-box icon-wrapper link-style no-decoration"
            aria-label="<?php echo esc_attr($title); ?>"
            title="<?php echo esc_attr($title); ?>"
            data-package-id="<?php echo (int) $package->getId(); ?>">
            <?php RecoveryViewHelper::disasterIcon(true, $colorClass); ?>
        </span>
        <?php
    }

    /**
     * Append disaster recovery entry to the package flags aggregated tooltip.
     *
     * @param array<int, array{icon: string, label: string, details: string}> $groups  Existing tooltip groups
     * @param DupPackage                                                      $package The package
     *
     * @return array<int, array{icon: string, label: string, details: string}>
     */
    public static function appendTooltipGroup(array $groups, DupPackage $package): array
    {
        if (!CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) {
            return $groups;
        }
        if (!$package->hasFlag(self::FLAG_DISASTER_AVAIABLE) && !$package->hasFlag(self::FLAG_DISASTER_SET)) {
            return $groups;
        }

        if ($package->hasFlag(self::FLAG_DISASTER_SET)) {
            $label     = esc_html__('Disaster Recovery URL is set on this Backup', 'duplicator-pro');
            $iconClass = 'fa-solid fa-house-fire green';
        } else {
            $label     = esc_html__('This Backup is available for Disaster Recovery', 'duplicator-pro');
            $iconClass = 'fa-solid fa-house-fire';
        }

        $groups[] = [
            'icon'    => $iconClass,
            'label'   => $label,
            'details' => '',
        ];
        return $groups;
    }

    /**
     * Append disaster-recovery entries to the Status column legend.
     *
     * @param array<int, array{icon: string, label: string}> $entries Existing legend entries
     *
     * @return array<int, array{icon: string, label: string}>
     */
    public static function addLegendEntries(array $entries): array
    {
        $entries[] = [
            'icon'  => RecoveryViewHelper::disasterIcon(false, 'link-style no-decoration'),
            'label' => __('This Backup is available for Disaster Recovery <b>[clickable]</b>', 'duplicator-pro'),
        ];
        $entries[] = [
            'icon'  => RecoveryViewHelper::disasterIcon(false, 'green'),
            'label' => __('Disaster Recovery URL is set on this Backup <b>[clickable]</b>', 'duplicator-pro'),
        ];
        return $entries;
    }

    /**
     * Add recovery-specific CSS classes to package row
     *
     * @param string[]        $classes Current classes
     * @param AbstractPackage $package Package
     *
     * @return string[]
     */
    public static function addPackageRowClasses(array $classes, AbstractPackage $package): array
    {
        if (
            $package->getStatus() >= AbstractPackage::STATUS_COMPLETE &&
            RecoveryPackage::getRecoverPackageId() === $package->getId()
        ) {
            $classes[] = 'dup-recovery-package';
        }

        return $classes;
    }

    /**
     * Render disaster recovery button in the expanded row header actions
     *
     * @param DupPackage $package Package
     *
     * @return void
     */
    public static function renderPackageDetailAction(DupPackage $package): void
    {
        if (!CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) {
            return;
        }

        $isRecoverable = $package->hasFlag(self::FLAG_DISASTER_AVAIABLE) || $package->hasFlag(self::FLAG_DISASTER_SET);
        ?>
        <button
            type="button"
            aria-label="<?php esc_attr_e("Recover this Backup", 'duplicator-pro') ?>"
            class="button hollow secondary small dupli-btn-open-recovery-box <?php echo ($isRecoverable) ? '' : 'maroon' ?>"
            data-package-id="<?php echo (int) $package->getId(); ?>">
            <?php RecoveryViewHelper::disasterIcon(true); ?>
            <?php esc_html_e("Disaster Recovery", 'duplicator-pro'); ?>
        </button>
        <?php
    }

    /**
     * Render recovery JS, UiDialogs and click handlers for packages page
     *
     * @return void
     */
    public static function renderPackagesPageScripts(): void
    {
        TplMng::getInstance()->render('recoveryaddon/admin_pages/packages/recovery_scripts');
    }
}
