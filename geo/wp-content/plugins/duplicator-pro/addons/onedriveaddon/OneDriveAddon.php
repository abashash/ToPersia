<?php

/**
 * OneDrive ADDON
 */

namespace Duplicator\Addons\OneDriveAddon;

use Duplicator\Addons\OneDriveAddon\Core\LicenseOneDrive;
use Duplicator\Addons\OneDriveAddon\Models\OneDriveStorage;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Package\Storage\UploadInfo;

class OneDriveAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * @return void
     */
    public function init(): void
    {
        add_action('duplicator_register_storage_types', [$this, 'registerStorages']);
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);
        add_filter('duplicator_telemetry_storage_metrics', [self::class, 'getStorageUsageStats'], 10, 2);
        add_filter('duplicator_telemetry_event_fields', [self::class, 'addTelemetryEventFields'], 10, 4);
    }

    /**
     * Register storages
     *
     * @return void
     *
     * @throws \Exception
     */
    public function registerStorages(): void
    {
        OneDriveStorage::registerStorageSubtype();

        add_filter('duplicator_pro_unavailable_storage_types', function (array $items): array {
            if (LicenseOneDrive::can()) {
                return $items;
            }
            $items[] = [
                'type' => OneDriveStorage::getSType(),
                'name' => OneDriveStorage::getStypeName(),
                'icon' => OneDriveStorage::getStypeIcon(),
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
        if (strpos($slugTpl, 'onedriveaddon/') === 0) {
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

        $storageNums['storages_onedrive_count'] = 0;

        foreach ($storages as $storage) {
            if ($storage->getSType() === OneDriveStorage::getSType()) {
                $storageNums['storages_onedrive_count']++;
            }
        }

        return $storageNums;
    }

    /**
     * Add the OneDrive wire name to storage-transfer telemetry.
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
            $context->getStorage()::getSType() === OneDriveStorage::getSType()
        ) {
            $fields['storage_type'] = 'onedrive';
        }

        return $fields;
    }

    /**
     * @return string
     */
    public static function getAddonPath()
    {
        return static::ADDON_PATH;
    }

    /**
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
