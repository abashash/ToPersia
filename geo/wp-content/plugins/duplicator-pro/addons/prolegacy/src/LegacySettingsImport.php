<?php

/**
 * Legacy settings import compatibility.
 */

declare(strict_types=1);

namespace Duplicator\Addons\ProLegacy;

use Duplicator\Models\DynamicGlobalEntity;

final class LegacySettingsImport
{
    /**
     * Register legacy settings import hooks.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('duplicator_settings_import_data', [self::class, 'importGlobalSettings'], 10, 5);
    }

    /**
     * Import GlobalEntity settings from exports created before their dynamic migration.
     *
     * @param array<string,mixed> $importData  Full imported data
     * @param string              $version     Export version
     * @param array<int,int>      $storageMap  Imported storage ID map
     * @param array<int,int>      $templateMap Imported template ID map
     * @param string[]            $opts        Selected import sections
     *
     * @return void
     */
    public static function importGlobalSettings(
        array $importData,
        string $version,
        array $storageMap,
        array $templateMap,
        array $opts
    ): void {
        if (
            !in_array('settings', $opts, true) ||
            !isset($importData['settings']) ||
            !is_array($importData['settings'])
        ) {
            return;
        }

        $settings = $importData['settings'];
        $dGlobal  = DynamicGlobalEntity::getInstance();
        $changed  = false;

        foreach (LegacyGlobalSettings::getDefinitions() as $legacyKey => $definition) {
            if (!array_key_exists($legacyKey, $settings)) {
                continue;
            }

            $value = $settings[$legacyKey];
            if ($definition['type'] === LegacyGlobalSettings::TYPE_ARRAY && !is_array($value)) {
                continue;
            }

            LegacyGlobalSettings::setDynamicValue(
                $dGlobal,
                $definition['key'],
                $definition['type'],
                LegacyGlobalSettings::normalizeValue($legacyKey, $value, $definition['type'])
            );
            $changed = true;
        }

        if ($changed) {
            $dGlobal->save();
        }
    }
}
