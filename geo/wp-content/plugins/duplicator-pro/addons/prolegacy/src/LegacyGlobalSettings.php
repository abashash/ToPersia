<?php

/**
 * Shared legacy GlobalEntity setting definitions.
 */

declare(strict_types=1);

namespace Duplicator\Addons\ProLegacy;

use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Models\GlobalEntity;
use Duplicator\Utils\GroupOptions;
use Duplicator\Utils\UsageStatistics\StatsBootstrap;
use Exception;

final class LegacyGlobalSettings
{
    const TYPE_STRING = 'string';
    const TYPE_ARRAY  = 'array';
    const TYPE_BOOL   = 'bool';
    const TYPE_INT    = 'int';

    /**
     * Return legacy property mappings.
     *
     * @return array<string,array{key:string,type:string}>
     */
    public static function getDefinitions(): array
    {
        return [
            GlobalEntity::EMAIL_SUMMARY_FREQUENCY_KEY            => [
                'key'  => GlobalEntity::EMAIL_SUMMARY_FREQUENCY_KEY,
                'type' => self::TYPE_STRING,
            ],
            GlobalEntity::EMAIL_SUMMARY_RECIPIENTS_KEY           => [
                'key'  => GlobalEntity::EMAIL_SUMMARY_RECIPIENTS_KEY,
                'type' => self::TYPE_ARRAY,
            ],
            'usageTracking'                                      => [
                'key'  => StatsBootstrap::USAGE_TRACKING_KEY,
                'type' => self::TYPE_BOOL,
            ],
            'amNotices'                                          => [
                'key'  => GlobalEntity::AM_NOTICES_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::PACKAGE_MYSQLDUMP_KEY                  => [
                'key'  => GlobalEntity::PACKAGE_MYSQLDUMP_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::PACKAGE_MYSQLDUMP_PATH_KEY             => [
                'key'  => GlobalEntity::PACKAGE_MYSQLDUMP_PATH_KEY,
                'type' => self::TYPE_STRING,
            ],
            GlobalEntity::PACKAGE_PHPDUMP_MODE_KEY               => [
                'key'  => GlobalEntity::PACKAGE_PHPDUMP_MODE_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::PACKAGE_MYSQLDUMP_QRYLIMIT_KEY         => [
                'key'  => GlobalEntity::PACKAGE_MYSQLDUMP_QRYLIMIT_KEY,
                'type' => self::TYPE_INT,
            ],
            'packageMysqldumpOptions'                            => [
                'key'  => GlobalEntity::PACKAGE_MYSQLDUMP_OPTIONS_KEY,
                'type' => self::TYPE_ARRAY,
            ],
            GlobalEntity::ARCHIVE_BUILD_MODE_KEY                 => [
                'key'  => GlobalEntity::ARCHIVE_BUILD_MODE_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::ARCHIVE_COMPRESSION_KEY                => [
                'key'  => GlobalEntity::ARCHIVE_COMPRESSION_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::ZIPARCHIVE_VALIDATION_KEY              => [
                'key'  => GlobalEntity::ZIPARCHIVE_VALIDATION_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::ZIPARCHIVE_MODE_KEY                    => [
                'key'  => GlobalEntity::ZIPARCHIVE_MODE_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::ZIPARCHIVE_CHUNK_SIZE_IN_MB_KEY        => [
                'key'  => GlobalEntity::ZIPARCHIVE_CHUNK_SIZE_IN_MB_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::HOMEPATH_AS_ABSPATH_KEY                => [
                'key'  => GlobalEntity::HOMEPATH_AS_ABSPATH_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::SERVER_LOAD_REDUCTION_KEY              => [
                'key'  => GlobalEntity::SERVER_LOAD_REDUCTION_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::MAX_PACKAGE_RUNTIME_IN_MIN_KEY         => [
                'key'  => GlobalEntity::MAX_PACKAGE_RUNTIME_IN_MIN_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::MAX_PACKAGE_TRANSFER_TIME_IN_MIN_KEY   => [
                'key'  => GlobalEntity::MAX_PACKAGE_TRANSFER_TIME_IN_MIN_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::CLEANUP_MODE_KEY                       => [
                'key'  => GlobalEntity::CLEANUP_MODE_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::CLEANUP_EMAIL_KEY                      => [
                'key'  => GlobalEntity::CLEANUP_EMAIL_KEY,
                'type' => self::TYPE_STRING,
            ],
            GlobalEntity::AUTO_CLEANUP_HOURS_KEY                 => [
                'key'  => GlobalEntity::AUTO_CLEANUP_HOURS_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::INSTALLER_NAME_MODE_KEY                => [
                'key'  => GlobalEntity::INSTALLER_NAME_MODE_KEY,
                'type' => self::TYPE_STRING,
            ],
            GlobalEntity::SKIP_ARCHIVE_SCAN_KEY                  => [
                'key'  => GlobalEntity::SKIP_ARCHIVE_SCAN_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::LEGACY_SEND_EMAIL_ON_BUILD_MODE_PROP   => [
                'key'  => GlobalEntity::LEGACY_SEND_EMAIL_ON_BUILD_MODE_PROP,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::LEGACY_NOTIFICATION_EMAIL_ADDRESS_PROP => [
                'key'  => GlobalEntity::LEGACY_NOTIFICATION_EMAIL_ADDRESS_PROP,
                'type' => self::TYPE_STRING,
            ],
            GlobalEntity::STORAGE_HTACCESS_OFF_KEY               => [
                'key'  => GlobalEntity::STORAGE_HTACCESS_OFF_KEY,
                'type' => self::TYPE_BOOL,
            ],
            'purgeBackupRecords'                                 => [
                'key'  => GlobalEntity::PURGE_BACKUP_RECORDS_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::MANUAL_MODE_STORAGE_IDS_KEY            => [
                'key'  => GlobalEntity::MANUAL_MODE_STORAGE_IDS_KEY,
                'type' => self::TYPE_ARRAY,
            ],
            GlobalEntity::LAST_SYSTEM_CHECK_TIMESTAMP_KEY        => [
                'key'  => GlobalEntity::LAST_SYSTEM_CHECK_TIMESTAMP_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::INITIAL_ACTIVATION_TIMESTAMP_KEY       => [
                'key'  => GlobalEntity::INITIAL_ACTIVATION_TIMESTAMP_KEY,
                'type' => self::TYPE_INT,
            ],
            GlobalEntity::SSL_USE_SERVER_CERTS_KEY               => [
                'key'  => GlobalEntity::SSL_USE_SERVER_CERTS_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::SSL_DISABLE_VERIFY_KEY                 => [
                'key'  => GlobalEntity::SSL_DISABLE_VERIFY_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::IPV4_ONLY_KEY                          => [
                'key'  => GlobalEntity::IPV4_ONLY_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::UNHOOK_THIRD_PARTY_JS_KEY              => [
                'key'  => GlobalEntity::UNHOOK_THIRD_PARTY_JS_KEY,
                'type' => self::TYPE_BOOL,
            ],
            GlobalEntity::UNHOOK_THIRD_PARTY_CSS_KEY             => [
                'key'  => GlobalEntity::UNHOOK_THIRD_PARTY_CSS_KEY,
                'type' => self::TYPE_BOOL,
            ],
        ];
    }

    /**
     * Normalize a legacy value for dynamic persistence.
     *
     * @param string $legacyKey Legacy property name
     * @param mixed  $value     Legacy value
     * @param string $type      Definition type
     *
     * @return mixed
     */
    public static function normalizeValue(string $legacyKey, $value, string $type)
    {
        switch ($type) {
            case self::TYPE_STRING:
                return (string) $value;
            case self::TYPE_BOOL:
                return (bool) $value;
            case self::TYPE_INT:
                return (int) $value;
            case self::TYPE_ARRAY:
                if (!is_array($value)) {
                    throw new Exception('Legacy array setting must be an array.');
                }
                if ($legacyKey !== 'packageMysqldumpOptions') {
                    return $value;
                }

                $normalized = [];
                foreach ($value as $item) {
                    if ($item instanceof GroupOptions) {
                        $normalized[] = $item->toArray();
                    } elseif (is_array($item)) {
                        $normalized[] = $item;
                    }
                }
                return $normalized;
            default:
                throw new Exception('Unsupported legacy setting type: ' . $type);
        }
    }

    /**
     * Read a typed dynamic value.
     *
     * @param DynamicGlobalEntity $entity Dynamic settings entity
     * @param string              $key    Dynamic key
     * @param string              $type   Definition type
     *
     * @return mixed
     */
    public static function getDynamicValue(DynamicGlobalEntity $entity, string $key, string $type)
    {
        switch ($type) {
            case self::TYPE_STRING:
                return $entity->getValString($key);
            case self::TYPE_ARRAY:
                return $entity->getValArray($key);
            case self::TYPE_BOOL:
                return $entity->getValBool($key);
            case self::TYPE_INT:
                return $entity->getValInt($key);
            default:
                throw new Exception('Unsupported legacy setting type: ' . $type);
        }
    }

    /**
     * Write a typed dynamic value without persisting the entity.
     *
     * @param DynamicGlobalEntity $entity Dynamic settings entity
     * @param string              $key    Dynamic key
     * @param string              $type   Definition type
     * @param mixed               $value  Value to write
     *
     * @return void
     */
    public static function setDynamicValue(DynamicGlobalEntity $entity, string $key, string $type, $value): void
    {
        switch ($type) {
            case self::TYPE_STRING:
                $entity->setValString($key, (string) $value);
                return;
            case self::TYPE_ARRAY:
                $entity->setValArray($key, is_array($value) ? $value : []);
                return;
            case self::TYPE_BOOL:
                $entity->setValBool($key, (bool) $value);
                return;
            case self::TYPE_INT:
                $entity->setValInt($key, (int) $value);
                return;
            default:
                throw new Exception('Unsupported legacy setting type: ' . $type);
        }
    }
}
