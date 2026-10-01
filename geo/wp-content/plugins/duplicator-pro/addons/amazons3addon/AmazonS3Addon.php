<?php

/**
 * AWS ADDON
 */

namespace Duplicator\Addons\AmazonS3Addon;

use Duplicator\Addons\AmazonS3Addon\Core\LicenseAmazonS3;
use Duplicator\Addons\AmazonS3Addon\Models\AmazonS3Storage;
use Duplicator\Addons\AmazonS3Addon\Models\AmazonS3CompatibleStorage;
use Duplicator\Addons\AmazonS3Addon\Models\BackblazeStorage;
use Duplicator\Addons\AmazonS3Addon\Models\CloudflareStorage;
use Duplicator\Addons\AmazonS3Addon\Models\DigitalOceanStorage;
use Duplicator\Addons\AmazonS3Addon\Models\DreamStorage;
use Duplicator\Addons\AmazonS3Addon\Models\GoogleCloudStorage;
use Duplicator\Addons\AmazonS3Addon\Models\VultrStorage;
use Duplicator\Addons\AmazonS3Addon\Models\WasabiStorage;
use Duplicator\Addons\AmazonS3Addon\Utils\Autoloader;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Package\Storage\UploadInfo;

/**
 * Storage s3 and S3 compatible addon class
 */
class AmazonS3Addon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * @return void
     */
    public function init(): void
    {
        Autoloader::register();

        add_action('duplicator_daily_actions', [self::class, 'purgeOldS3MultipartUploads']);
        add_action('duplicator_register_storage_types', [$this, 'registerStorages']);
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);
        add_filter('duplicator_telemetry_storage_metrics', [self::class, 'getStorageUsageStats'], 10, 2);
        add_filter('duplicator_telemetry_event_fields', [self::class, 'addTelemetryEventFields'], 10, 4);
    }

    /**
     * Register storages
     *
     * @return void
     */
    public function registerStorages(): void
    {
        AmazonS3Storage::registerStorageSubtype();
        AmazonS3CompatibleStorage::registerStorageSubtype();
        GoogleCloudStorage::registerStorageSubtype();
        BackblazeStorage::registerStorageSubtype();
        DreamStorage::registerStorageSubtype();
        DigitalOceanStorage::registerStorageSubtype();
        VultrStorage::registerStorageSubtype();
        CloudflareStorage::registerStorageSubtype();
        WasabiStorage::registerStorageSubtype();

        add_filter('duplicator_pro_unavailable_storage_types', function (array $items): array {
            if (LicenseAmazonS3::can()) {
                return $items;
            }
            foreach (
                [
                    AmazonS3Storage::class,
                    AmazonS3CompatibleStorage::class,
                    GoogleCloudStorage::class,
                    BackblazeStorage::class,
                    DreamStorage::class,
                    DigitalOceanStorage::class,
                    VultrStorage::class,
                    CloudflareStorage::class,
                    WasabiStorage::class,
                ] as $class
            ) {
                $items[] = [
                    'type' => $class::getSType(),
                    'name' => $class::getStypeName(),
                    'icon' => $class::getStypeIcon(),
                ];
            }
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
        if (strpos($slugTpl, 'amazons3addon/') === 0) {
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

        $storageNums['storages_s3_count']            = 0;
        $storageNums['storages_s3_compatible_count'] = 0;

        foreach ($storages as $index => $storage) {
            switch ($storage->getStype()) {
                case AmazonS3Storage::getSType():
                    $storageNums['storages_s3_count']++;
                    break;
                case AmazonS3CompatibleStorage::getSType():
                case GoogleCloudStorage::getSType():
                case BackblazeStorage::getSType():
                case DreamStorage::getSType():
                case DigitalOceanStorage::getSType():
                case VultrStorage::getSType():
                case CloudflareStorage::getSType():
                case WasabiStorage::getSType():
                    $storageNums['storages_s3_compatible_count']++;
                    break;
            }
        }

        return $storageNums;
    }


    /**
     * Add the provider wire name to storage-transfer telemetry.
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

        $names = [
            AmazonS3Storage::getSType()           => 's3',
            AmazonS3CompatibleStorage::getSType() => 's3_compatible',
            BackblazeStorage::getSType()          => 'backblaze',
            WasabiStorage::getSType()             => 'wasabi',
            CloudflareStorage::getSType()         => 'cloudflare',
            VultrStorage::getSType()              => 'vultr',
            DreamStorage::getSType()              => 'dream',
            DigitalOceanStorage::getSType()       => 'digitalocean',
            GoogleCloudStorage::getSType()        => 'googlecloud',
        ];
        $sType = $context->getStorage()::getSType();
        if (isset($names[$sType])) {
            $fields['storage_type'] = $names[$sType];
        }

        return $fields;
    }

    /**
     * Purge old S3 multipart uploads
     *
     * @return void
     */
    public static function purgeOldS3MultipartUploads(): void
    {
        if (($storages = AbstractStorageEntity::getAll()) == false) {
            return;
        }

        foreach ($storages as $storage) {
            if (!$storage instanceof \Duplicator\Addons\AmazonS3Addon\Models\AmazonS3Storage) {
                continue;
            }

            $storage->purgeMultipartUpload();
        }
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
