<?php

declare(strict_types=1);

namespace Duplicator\Installer\Addons\MultisiteAddon;

use Duplicator\Installer\Addons\ProBase\License;
use Duplicator\Installer\Core\InstState;
use Duplicator\Installer\Core\Params\Items\ParamOption;

/**
 * Adds multisite install types (standalone, subsite import) based on license
 */
class MultisiteInstallTypes
{
    /**
     * Check if current license tier includes multisite plus
     *
     * @return bool
     */
    private static function canMultisitePlus(): bool
    {
        return LicenseMultisite::canMultisitePlus(License::getType());
    }

    /**
     * Add multisite install types to accepted values
     *
     * @param int[]               $acceptValues  Current accepted install types
     * @param \DUPX_ArchiveConfig $archiveConfig Archive configuration
     * @param array<string,mixed> $overwriteData Overwrite site data
     *
     * @return int[]
     */
    public static function filterAcceptedValues(array $acceptValues, $archiveConfig, $overwriteData): array
    {
        if (
            $archiveConfig->mu_mode > 0 &&
            self::canMultisitePlus()
        ) {
            $acceptValues[] = InstState::TYPE_STANDALONE;
        }

        if (
            InstState::isImportFromBackendMode() &&
            $overwriteData['isMultisite'] &&
            self::canMultisitePlus()
        ) {
            if (version_compare($overwriteData['wpVersion'], InstState::SUBSITE_IMPORT_WP_MIN_VERSION, '>=')) {
                if ($archiveConfig->mu_mode == 0) {
                    if ($overwriteData['subdomain']) {
                        $acceptValues[] = InstState::TYPE_SINGLE_ON_SUBDOMAIN;
                    } else {
                        $acceptValues[] = InstState::TYPE_SINGLE_ON_SUBFOLDER;
                    }
                } else {
                    if ($overwriteData['subdomain']) {
                        $acceptValues[] = InstState::TYPE_SUBSITE_ON_SUBDOMAIN;
                    } else {
                        $acceptValues[] = InstState::TYPE_SUBSITE_ON_SUBFOLDER;
                    }
                }
            } else {
                $msg  = "The option to import the site into the multisite network has been disabled " .
                    "since it's only available for WordPress <b>" . InstState::SUBSITE_IMPORT_WP_MIN_VERSION . " +</b>.<br>";
                $msg .= " To overcome the issue please update WordPress to the most recent version.";

                $noticeManager = \DUPX_NOTICE_MANAGER::getInstance();
                $noticeManager->addNextStepNotice([
                    'shortMsg'    => 'Import site into network disabled',
                    'level'       => \DUPX_NOTICE_ITEM::NOTICE,
                    'longMsg'     => $msg,
                    'longMsgMode' => \DUPX_NOTICE_ITEM::MSG_MODE_HTML,
                ], \DUPX_NOTICE_MANAGER::ADD_UNIQUE, 'import-site-into-network-disabled');
            }
        }

        return $acceptValues;
    }

    /**
     * Add license notes to multisite install types
     *
     * @param string      $note   Current note
     * @param ParamOption $option Install type option
     *
     * @return string
     */
    public static function filterInstallTypeNote(string $note, ParamOption $option): string
    {
        switch ($option->value) {
            case InstState::TYPE_STANDALONE:
                if (!self::canMultisitePlus()) {
                    return License::getLicenseUpdateText();
                }
                return $note;
            case InstState::TYPE_SINGLE_ON_SUBDOMAIN:
            case InstState::TYPE_SINGLE_ON_SUBFOLDER:
            case InstState::TYPE_SUBSITE_ON_SUBDOMAIN:
            case InstState::TYPE_SUBSITE_ON_SUBFOLDER:
                if (!self::canMultisitePlus()) {
                    $notes   = $note !== '' ? [$note] : [];
                    $notes[] = License::getLicenseUpdateText();
                    return implode('<br>', $notes);
                }
                return $note;
            default:
                return $note;
        }
    }
}
