<?php

/**
 * Google Drive ADDON
 */

namespace Duplicator\Addons\GDriveAddon;

use Duplicator\Addons\GDriveAddon\Core\LicenseGDrive;
use Duplicator\Addons\GDriveAddon\Models\GDriveStorage;
use Duplicator\Addons\GDriveAddon\Utils\Autoloader;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Package\Storage\UploadInfo;

class GDriveAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * @return void
     */
    public function init(): void
    {
        Autoloader::register();

        add_action('duplicator_register_storage_types', [$this, 'registerStorages']);
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);
        add_filter('duplicator_telemetry_storage_metrics', [self::class, 'getStorageUsageStats'], 10, 2);
        add_filter('duplicator_telemetry_event_fields', [self::class, 'addTelemetryEventFields'], 10, 4);
    }

    /**
     * @return void
     */
    public function registerStorages(): void
    {
        GDriveStorage::registerStorageSubtype();

        add_filter('duplicator_pro_unavailable_storage_types', function (array $items): array {
            if (LicenseGDrive::can()) {
                return $items;
            }
            $items[] = [
                'type' => GDriveStorage::getSType(),
                'name' => GDriveStorage::getStypeName(),
                'icon' => GDriveStorage::getStypeIcon(),
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
        if (strpos($slugTpl, 'gdriveaddon/') === 0) {
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

        $storageNums['storages_gdrive_count'] = 0;

        foreach ($storages as $index => $storage) {
            if ($storage->getSType() === GDriveStorage::getSType()) {
                $storageNums['storages_gdrive_count']++;
            }
        }

        return $storageNums;
    }

    /**
     * Add the Google Drive wire name to storage-transfer telemetry.
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
        if (
            $eventType === 'storage_transfer' &&
            $context instanceof UploadInfo &&
            $context->getStorage()::getSType() === GDriveStorage::getSType()
        ) {
            $fields['storage_type'] = 'gdrive';
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
