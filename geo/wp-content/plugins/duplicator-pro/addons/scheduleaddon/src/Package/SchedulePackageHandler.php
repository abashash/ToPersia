<?php

/**
 * Post-build processing handler for scheduled backups
 */

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Package;

use Duplicator\Addons\ScheduleAddon\Controllers\SchedulePageController;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Addons\ScheduleAddon\Models\SchedulePackageRelation;
use Duplicator\Addons\ScheduleAddon\Settings\ScheduleSettings;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapString;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Package\AbstractPackage;
use Duplicator\Package\DupPackage;
use Duplicator\Utils\Logging\DupLog;
use Exception;
use Throwable;

/**
 * Handles post-build processing for scheduled backups.
 *
 * Replaces the old TraitPackageSchedule + DupPackage::sendBuildEmail() logic
 * by hooking into the duplicator_package_after_set_status action.
 */
class SchedulePackageHandler
{
    /** @var int[] Package IDs that have already had an email sent */
    private static array $emailSentPackages = [];

    /**
     * Terminal statuses that trigger post-build processing
     */
    private const TERMINAL_STATUSES = [
        AbstractPackage::STATUS_COMPLETE,
        AbstractPackage::STATUS_ERROR,
        AbstractPackage::STATUS_BUILD_CANCELLED,
        AbstractPackage::STATUS_STORAGE_CANCELLED,
        AbstractPackage::STATUS_STORAGE_FAILED,
        AbstractPackage::STATUS_REQUIREMENTS_FAILED,
    ];

    /**
     * Handle package status change for scheduled backups
     *
     * Hooked to duplicator_package_after_set_status at priority 10.
     *
     * @param AbstractPackage $package The package that changed status
     * @param int             $status  The new status
     *
     * @return void
     */
    public static function onStatusChange(AbstractPackage $package, int $status): void
    {
        if (!in_array($status, self::TERMINAL_STATUSES, true)) {
            return;
        }

        $relation = SchedulePackageRelation::getByPackageId($package->getId());
        if ($relation === null) {
            return;
        }

        $schedule = ScheduleEntity::getById($relation['schedule_id']);
        if ($schedule === false) {
            return;
        }

        $success          = ($status === AbstractPackage::STATUS_COMPLETE);
        $defaultRunStatus = $success ? ScheduleEntity::RUN_STATUS_SUCCESS : ScheduleEntity::RUN_STATUS_FAILURE;

        /**
         * Filter the run-status recorded for a schedule after a terminal package status change.
         *
         * Lets an addon reclassify an outcome without altering the default
         * success/failure mapping for every other consumer.
         *
         * @param int             $defaultRunStatus Default status (RUN_STATUS_SUCCESS or RUN_STATUS_FAILURE)
         * @param AbstractPackage $package          Package that changed status
         * @param int             $status           New terminal package status
         * @param ScheduleEntity  $schedule         Schedule that owns the package
         */
        $runStatus = (int) apply_filters('duplicator_schedule_run_status', $defaultRunStatus, $package, $status, $schedule);

        $schedule->times_run++;
        $schedule->last_run_time   = time();
        $schedule->last_run_status = $runStatus;
        $schedule->save();

        /**
         * Filter whether a build notification email should be sent for this status change.
         *
         * @param bool            $sendEmail Whether to send the build email (default true)
         * @param AbstractPackage $package   Package that changed status
         * @param ScheduleEntity  $schedule  Schedule that owns the package
         * @param bool            $success   Whether the build completed successfully
         */
        $sendEmail = (bool) apply_filters('duplicator_schedule_send_build_email', true, $package, $schedule, $success);

        if ($sendEmail && $package instanceof DupPackage) {
            self::sendBuildEmail($package, $schedule, $success);
        }
    }

    /**
     * Send build notification email for a scheduled backup
     *
     * @param DupPackage     $package  The completed package
     * @param ScheduleEntity $schedule The schedule that created the package
     * @param bool           $success  Whether the build was successful
     *
     * @return void
     */
    private static function sendBuildEmail(DupPackage $package, ScheduleEntity $schedule, bool $success): void
    {
        $switchedLocale = false;
        try {
            if (in_array($package->getId(), self::$emailSentPackages, true)) {
                return;
            }

            switch (ScheduleSettings::getEmailBuildMode()) {
                case ScheduleSettings::EMAIL_BUILD_MODE_NEVER:
                    return;
                case ScheduleSettings::EMAIL_BUILD_MODE_ALL:
                    break;
                case ScheduleSettings::EMAIL_BUILD_MODE_FAILURE:
                    if ($success) {
                        return;
                    }
                    break;
                default:
                    return;
            }

            $to = self::resolveNotificationRecipient();
            DupLog::trace("Attempting to send build notification to $to");

            // Emails use the site language; workers can run with an admin's profile locale loaded
            $switchedLocale = switch_to_locale(get_locale());

            $data = [
                'success'      => $success,
                'messageTitle' => __('BACKUP SUCCEEDED', 'duplicator-pro'),
                'packageID'    => $package->getId(),
                'packageName'  => $package->getName(),
                'scheduleName' => $schedule->name,
                'storageNames' => array_map(
                    fn(AbstractStorageEntity $s): string => $s->getName(),
                    $package->getStorages()
                ),
                'packagesLink' => ControllersManager::getMenuLink(
                    ControllersManager::PACKAGES_SUBMENU_SLUG,
                    null,
                    null,
                    [],
                    false
                ),
            ];

            if ($success) {
                $data = array_merge($data, [
                    'fileCount'   => $package->Archive->FileCount,
                    'packageSize' => SnapString::byteSize($package->Archive->Size),
                    'tableCount'  => $package->Database->info->tablesFinalCount,
                    'sqlSize'     => SnapString::byteSize($package->Database->Size),
                ]);

                $subject = sprintf(
                    __('Backup of %1$s (%2$s) Succeeded', 'duplicator-pro'),
                    home_url(),
                    $schedule->name
                );
            } else {
                $data['messageTitle'] = __('BACKUP FAILED', 'duplicator-pro');
                $subject              = sprintf(
                    __('Backup of %1$s (%2$s) Failed', 'duplicator-pro'),
                    home_url(),
                    $schedule->name
                );
            }

            $message = TplMng::getInstance()->render('scheduleaddon/mail/scheduled-build', $data, false);

            if (!wp_mail($to, $subject, $message, ['Content-Type: text/html; charset=UTF-8'])) {
                throw new Exception("Problem sending build notification to {$to} regarding Backup {$package->getId()}");
            }

            self::$emailSentPackages[] = $package->getId();
            DupLog::trace('wp_mail reporting send success');
        } catch (Throwable $ex) {
            DupLog::traceException($ex, "Problem sending build notification email");
        } finally {
            if ($switchedLocale) {
                restore_previous_locale();
            }
        }
    }

    /**
     * Send failure notification email when a scheduled backup fails before package creation
     *
     * Called from ScheduleEntity::process() when insertNewPackage() throws
     * or otherwise fails before a package record exists in the database.
     *
     * @param ScheduleEntity $schedule     The schedule that failed
     * @param string         $errorMessage The error/exception message
     *
     * @return void
     */
    public static function sendScheduleFailureEmail(ScheduleEntity $schedule, string $errorMessage): void
    {
        $switchedLocale = false;
        try {
            switch (ScheduleSettings::getEmailBuildMode()) {
                case ScheduleSettings::EMAIL_BUILD_MODE_NEVER:
                    return;
                case ScheduleSettings::EMAIL_BUILD_MODE_FAILURE:
                case ScheduleSettings::EMAIL_BUILD_MODE_ALL:
                    break;
                default:
                    return;
            }

            $to = self::resolveNotificationRecipient();
            DupLog::trace("Attempting to send schedule failure notification to $to");

            // Emails use the site language; the init tick can run with an admin's profile locale loaded
            $switchedLocale = switch_to_locale(get_locale());

            $subject = sprintf(
                __('Backup of %1$s (%2$s) Failed', 'duplicator-pro'),
                home_url(),
                $schedule->name
            );

            $data = [
                'messageTitle'     => __('SCHEDULED BACKUP FAILED TO START', 'duplicator-pro'),
                'scheduleName'     => $schedule->name,
                'errorMessage'     => $errorMessage,
                'storageNames'     => self::resolveStorageNames($schedule->storage_ids),
                'scheduleEditLink' => ControllersManager::getMenuLink(
                    SchedulePageController::SCHEDULES_SUBMENU_SLUG,
                    null,
                    null,
                    [
                        ControllersManager::QUERY_STRING_INNER_PAGE => 'edit',
                        'schedule_id'                               => $schedule->getId(),
                    ],
                    false
                ),
            ];

            $message = TplMng::getInstance()->render('scheduleaddon/mail/scheduled-start-failed', $data, false);

            if (!wp_mail($to, $subject, $message, ['Content-Type: text/html; charset=UTF-8'])) {
                throw new Exception("Problem sending schedule failure notification to {$to}");
            }

            DupLog::trace('wp_mail schedule failure notification sent successfully');
        } catch (Throwable $ex) {
            DupLog::traceException($ex, "Problem sending schedule failure notification email");
        } finally {
            if ($switchedLocale) {
                restore_previous_locale();
            }
        }
    }

    /**
     * Resolve the notification email recipient
     *
     * @return string Email address
     *
     * @throws Exception If no recipient can be determined
     */
    private static function resolveNotificationRecipient(): string
    {
        $to = ScheduleSettings::getNotificationEmailAddress();
        if (empty($to)) {
            // Multisite scheduled backups are network-owned: use the Network Admin Email.
            $to = is_multisite() ? get_site_option('admin_email') : get_option('admin_email');
        }
        if (empty($to)) {
            throw new Exception("Cannot send notification: no email address is configured.");
        }
        return $to;
    }

    /**
     * Resolve storage IDs to their display names
     *
     * @param int[] $storageIds Array of storage IDs
     *
     * @return string[]
     */
    private static function resolveStorageNames(array $storageIds): array
    {
        $names = [];
        foreach ($storageIds as $id) {
            $storage = AbstractStorageEntity::getById((int) $id);
            $names[] = $storage !== false ? $storage->getName() : sprintf(__('Unknown Storage (ID: %d)', 'duplicator-pro'), $id);
        }
        return $names;
    }
}
