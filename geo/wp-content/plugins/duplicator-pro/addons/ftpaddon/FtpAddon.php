<?php

/**
 * FTP/SFTP ADDON
 */

namespace Duplicator\Addons\FtpAddon;

use Duplicator\Addons\FtpAddon\Core\LicenseFtp;
use Duplicator\Addons\FtpAddon\Models\FTPStorage;
use Duplicator\Addons\FtpAddon\Models\SFTPStorage;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Package\Storage\UploadInfo;

/**
 * Storage ftp/sftp addon class
 */
class FtpAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * @return void
     */
    public function init(): void
    {
        add_action('init', [$this, 'hookInit']);
        add_action('duplicator_register_storage_types', [$this, 'registerStorages']);
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);
        add_filter('duplicator_telemetry_storage_metrics', [self::class, 'getStorageUsageStats'], 10, 2);
        add_filter('duplicator_telemetry_event_fields', [self::class, 'addTelemetryEventFields'], 10, 4);
    }

    /**
     * Function calle on duplicator_addons_loaded hook
     *
     * @return void
     */
    public function hookInit()
    {
    }

    /**
     * Register storages
     *
     * @return void
     */
    public function registerStorages(): void
    {
        FTPStorage::registerStorageSubtype();
        SFTPStorage::registerStorageSubtype();

        add_filter('duplicator_pro_unavailable_storage_types', function (array $items): array {
            if (LicenseFtp::can()) {
                return $items;
            }
            $items[] = [
                'type' => FTPStorage::getSType(),
                'name' => FTPStorage::getStypeName(),
                'icon' => FTPStorage::getStypeIcon(),
            ];
            $items[] = [
                'type' => SFTPStorage::getSType(),
                'name' => SFTPStorage::getStypeName(),
                'icon' => SFTPStorage::getStypeIcon(),
            ];
            return $items;
        });
    }

    /**
     * Return template file path
     *
     * @param string $path    path to the template file
     * @param string $slugTpl slug of the template
     *
     * @return string
     */
    public static function getTemplateFile($path, $slugTpl)
    {
        if (strpos($slugTpl, 'ftpaddon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }
        return $path;
    }

    /**
     * Get storage usage stats
     *
     * @param array<string,int>        $storageNums Storages num
     * @param ?AbstractStorageEntity[] $storages    Preloaded storages
     *
     * @return array<string,int>
     */
    public static function getStorageUsageStats($storageNums, $storages = null)
    {
        if (!is_array($storages)) {
            $storages = AbstractStorageEntity::getAll();
            $storages = is_array($storages) ? $storages : [];
        }

        $storageNums['storages_ftp_count']  = 0;
        $storageNums['storages_sftp_count'] = 0;

        foreach ($storages as $index => $storage) {
            switch ($storage->getSType()) {
                case FTPStorage::getSType():
                    $storageNums['storages_ftp_count']++;
                    break;
                case SFTPStorage::getSType():
                    $storageNums['storages_sftp_count']++;
                    break;
            }
        }

        return $storageNums;
    }

    /**
     * Add the FTP/SFTP wire name to storage-transfer telemetry.
     *
     * @param array<string,mixed> $fields    Event fields
     * @param string              $eventType Event family
     * @param string              $_subType  Event subtype
     * @param mixed               $context   Event source object
     *
     * @return array<string,mixed>
     */
    public static function addTelemetryEventFields(array $fields, string $eventType, string $_subType, $context): array
    {
        if ($eventType !== 'storage_transfer' || !$context instanceof UploadInfo) {
            return $fields;
        }

        switch ($context->getStorage()::getSType()) {
            case FTPStorage::getSType():
                $fields['storage_type'] = 'ftp';
                break;
            case SFTPStorage::getSType():
                $fields['storage_type'] = 'sftp';
                break;
        }

        return $fields;
    }

    /**
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
