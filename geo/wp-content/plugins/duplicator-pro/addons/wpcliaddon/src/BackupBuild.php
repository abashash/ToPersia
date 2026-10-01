<?php

namespace Duplicator\Addons\WpCliAddon;

use Duplicator\Core\Exceptions\DupliException;
use Duplicator\Core\Options\OptionsManager;
use Duplicator\Utils\Logging\DupLog;
use Duplicator\Package\DupPackage;
use Duplicator\Package\AbstractPackage;
use Duplicator\Ajax\ServicesPackage;
use Duplicator\Libs\Snap\SnapUtil;
use Exception;
use Throwable;
use WP_CLI;

class BackupBuild
{
    /**
     * Process schedules by cron
     *
     * @param DupPackage $package Package to process
     *
     * @return void
     */
    public static function process(DupPackage $package): void
    {
        if (SnapUtil::isIniValChangeable('memory_limit')) {
            @ini_set('memory_limit', -1);
        }

        // One-time gate: the first pass validates the configuration for this
        // package and freezes the resolved build options into it; once frozen
        // they are taken as good and later calls skip every check.
        $validation = OptionsManager::getInstance()->validatePackageConfig($package);
        if (!$validation->isValid()) {
            throw new Exception("Requirements failed:\n" . implode("\n", $validation->getLogLines()));
        }

        $start_time = time();
        DupLog::trace("PACKAGE {$package->getId()}:PROCESSING");

        if ($package->getStatus() < AbstractPackage::STATUS_AFTER_SCAN) {
            try {
                // Scan step built into package build - used by schedules - NOT manual build where scan is done in web service.
                DupLog::trace("PACKAGE {$package->getId()}:SCANNING");
                //After scanner runs.  Save FilterInfo (unreadable, warnings, globals etc)
                if (!$package->Archive->scanFiles(true)) {
                    while ($package->Archive->scanFiles() !== true) {
                        DupLog::trace("CONTINUE SCANNING");
                    }
                }
                $scan_report = $package->createScanReport();
                $package->setStatus(AbstractPackage::STATUS_AFTER_SCAN);

                $end_time  = time();
                $scan_time = $end_time - $start_time;
                DupLog::trace("SCAN TIME=$scan_time seconds");
                WP_CLI::debug("Scan result\n" . json_encode($scan_report, JSON_PRETTY_PRINT));
                if ($scan_report['Status'] > ServicesPackage::EXEC_STATUS_PASS) {
                    if (empty($scan_report['Message'])) {
                        $scan_report['Message'] = __('The Backup scan failed.', 'duplicator-pro');
                    }
                    throw new DupliException(
                        sprintf(
                            "Scan report returned a failure status.\nStatus: %d\nMessage: %s",
                            (int) $scan_report['Status'],
                            wp_strip_all_tags((string) $scan_report['Message'])
                        ),
                        DupliException::CODE_SCAN_FAILED,
                        (string) $scan_report['Message']
                    );
                } else {
                    WP_CLI::success("Scan success");
                }
            } catch (Throwable $exception) {
                $package->buildFail($exception, false);
                throw $exception;
            }
        } elseif ($package->getStatus() < AbstractPackage::STATUS_COPIEDPACKAGE) {
            DupLog::trace("PACKAGE {$package->getId()}:BUILDING");
            $package->runBuild(false);
            $end_time   = time();
            $build_time = $end_time - $start_time;
            DupLog::trace("BUILD TIME=$build_time seconds");
        } elseif ($package->getStatus() < AbstractPackage::STATUS_COMPLETE) {
            DupLog::trace("PACKAGE {$package->getId()}:STORAGE PROCESSING");
            $package->setStatus(AbstractPackage::STATUS_STORAGE_PROCESSING);
            $package->processStorages();
            $end_time   = time();
            $build_time = $end_time - $start_time;
            DupLog::trace("STORAGE CHUNK PROCESSING TIME=$build_time seconds");
            if ($package->getStatus() == AbstractPackage::STATUS_COMPLETE) {
                DupLog::trace("PACKAGE {$package->getId()} COMPLETE");
            } elseif ($package->getStatus() == AbstractPackage::STATUS_ERROR) {
                DupLog::trace("PACKAGE {$package->getId()} IN ERROR STATE");
            }

            $packageCompleteStatuses = [
                AbstractPackage::STATUS_COMPLETE,
                AbstractPackage::STATUS_ERROR,
            ];
            if (in_array($package->getStatus(), $packageCompleteStatuses)) {
                $info  = "\n";
                $info .= "********************************************************************************\n";
                $info .= "********************************************************************************\n";
                $info .= DUPLICATOR____NAME . " PACKAGE CREATION OR MANUAL STORAGE TRANSFER END: " . @date("Y-m-d H:i:s") . "\n";
                $info .= "NOTICE: Do NOT post to public sites or forums \n";
                $info .= "********************************************************************************\n";
                $info .= "********************************************************************************\n";
                DupLog::infoTrace($info);
            }

            if ($package->getStatus() == AbstractPackage::STATUS_ERROR) {
                throw new Exception("Storage failed");
            }
        }
    }
}
