<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Ajax;

use Duplicator\Addons\ScheduleAddon\Core\CapSchedule;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Addons\ScheduleAddon\Models\SchedulePackageRelation;
use Duplicator\Ajax\AbstractAjaxService;
use Duplicator\Ajax\AjaxWrapper;
use Duplicator\Core\CapMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Package\DupPackage;
use Duplicator\Package\PackageUtils;
use Duplicator\Package\Runner;
use Duplicator\Utils\Logging\DupLog;
use Exception;
use stdClass;

class ServicesSchedule extends AbstractAjaxService
{
    const SCHEDULE_BULK_DELETE     = 1;
    const SCHEDULE_BULK_ACTIVATE   = 2;
    const SCHEDULE_BULK_DEACTIVATE = 3;

    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init(): void
    {
        $this->addAjaxCall('wp_ajax_duplicator_schedule_bulk_action', 'bulkAction');
        $this->addAjaxCall('wp_ajax_duplicator_get_schedule_infos', 'getScheduleInfo');
        $this->addAjaxCall('wp_ajax_duplicator_run_schedule_now', 'runScheduleNow');
        $this->addAjaxCall('wp_ajax_duplicator_schedule_get_storage_links', 'getStorageLinks');
    }

    /**
     * Schedule bulk actions
     *
     * @return void
     */
    public function bulkAction(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'bulkActionCallback',
            ],
            'duplicator_schedule_bulk_action',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapSchedule::CAP_SCHEDULE
        );
    }

    /**
     * Schedule bulk actions callback
     *
     * @return array<string, mixed>
     */
    public static function bulkActionCallback(): array
    {
        $inputData   = filter_input_array(INPUT_POST, [
            'schedule_ids' => [
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => ['default' => false],
            ],
            'perform'      => [
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => ['default' => false],
            ],
        ]);
        $scheduleIDs = $inputData['schedule_ids'];
        $action      = $inputData['perform'];

        if (empty($scheduleIDs) || in_array(false, $scheduleIDs) || $action === false) {
            throw new Exception(__("Invalid Request.", 'duplicator-pro'));
        }

        $result = [
            'success' => true,
            'message' => '',
        ];

        if ($action === self::SCHEDULE_BULK_ACTIVATE && PackageUtils::isBackupCreationBlocked($blockMessage)) {
            throw new Exception((string) $blockMessage);
        }

        foreach ($scheduleIDs as $id) {
            switch ($action) {
                case self::SCHEDULE_BULK_DELETE:
                    ScheduleEntity::deleteById($id);
                    break;
                case self::SCHEDULE_BULK_ACTIVATE:
                    $schedule = ScheduleEntity::getById($id);
                    if (!$schedule->isValid()) {
                        // The message is rendered with jQuery .html()
                        $result['success']  = false;
                        $result['message'] .= sprintf(
                            __(
                                'Could not activate schedule "%1$s" because it has no valid storage locations. Please check the storage configurations.',
                                'duplicator-pro'
                            ),
                            esc_html($schedule->name)
                        ) . "<br>";
                    } else {
                        $schedule->setActive(true);
                        $schedule->save();
                    }
                    break;
                case self::SCHEDULE_BULK_DEACTIVATE:
                    $schedule = ScheduleEntity::getById($id);
                    $schedule->setActive(false);
                    $schedule->save();
                    break;
                default:
                    throw new Exception("Invalid schedule bulk action.");
            }
        }

        return $result;
    }

    /**
     * Get schedule info action
     *
     * @return void
     */
    public function getScheduleInfo(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'getScheduleInfoCallback',
            ],
            'duplicator_get_schedule_infos',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapSchedule::CAP_SCHEDULE
        );
    }

    /**
     * Get schedule info callback
     *
     * @return array<int, stdClass>
     */
    public static function getScheduleInfoCallback(): array
    {
        $schedules      = ScheduleEntity::getAll();
        $schedule_infos = [];

        if (count($schedules) > 0) {
            $package = DupPackage::getNextActive();

            foreach ($schedules as $schedule) {
                $schedule_info = new stdClass();

                $schedule_info->schedule_id     = $schedule->getId();
                $schedule_info->last_ran_string = $schedule->getLastRanString();

                $schedule_info->is_running = $package != null
                    && SchedulePackageRelation::getScheduleIdByPackageId($package->getId()) === $schedule->getId();

                $schedule_infos[] = $schedule_info;
            }
        }

        return $schedule_infos;
    }

    /**
     * Run schedule action
     *
     * @return void
     */
    public function runScheduleNow(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'runScheduleNowCallback',
            ],
            'duplicator_run_schedule_now',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapSchedule::CAP_SCHEDULE
        );
    }

    /**
     * Run schedule now callback
     *
     * @return array<string, mixed>
     */
    public static function runScheduleNowCallback(): array
    {
        $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);

        if ($schedule_id === false) {
            throw new Exception(__("Invalid schedule id", 'duplicator-pro'));
        }

        $schedule = ScheduleEntity::getById($schedule_id);

        if ($schedule == false) {
            DupLog::trace("Attempted to queue up a job for non existent schedule $schedule_id");
            throw new Exception(__("Invalid schedule id", 'duplicator-pro'));
        }

        if (PackageUtils::isBackupCreationBlocked($blockMessage)) {
            throw new Exception((string) $blockMessage);
        }

        DupLog::trace("Inserting new Backup for schedule $schedule->name due to manual request");
        $schedule->insertNewPackage(true);
        Runner::kickOffWorker();

        return [
            'success' => true,
            'message' => '',
        ];
    }

    /**
     * Get schedules linked to given storage IDs
     *
     * @return void
     */
    public function getStorageLinks(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'getStorageLinksCallback',
            ],
            'duplicator_schedule_get_storage_links',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapSchedule::CAP_SCHEDULE
        );
    }

    /**
     * Get storage links callback
     *
     * @return array<string, mixed>
     */
    public static function getStorageLinksCallback(): array
    {
        $result    = [
            'success'   => true,
            'schedules' => [],
        ];
        $inputData = filter_input_array(INPUT_POST, [
            'storage_ids' => [
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => ['default' => false],
            ],
        ]);

        $storageIDs = $inputData['storage_ids'];
        if (empty($storageIDs)) {
            return $result;
        }

        $seen = [];
        foreach ($storageIDs as $storageId) {
            foreach (ScheduleEntity::getSchedulesByStorageId($storageId) as $schedule) {
                if (isset($seen[$schedule->getId()])) {
                    continue;
                }
                $seen[$schedule->getId()] = true;
                $result['schedules'][]    = [
                    'id'            => $schedule->getId(),
                    'name'          => $schedule->name,
                    'hasOneStorage' => count($schedule->storage_ids) <= 1,
                    'editURL'       => \Duplicator\Addons\ScheduleAddon\Controllers\SchedulePageController::getInstance()->getEditUrl($schedule->getId()),
                ];
            }
        }

        return $result;
    }
}
