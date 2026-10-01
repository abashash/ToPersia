<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Controllers;

use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Utils\ActivityLog\SettingsChangeTracker;

/**
 * Hooks into the Import page/settings to inject recovery-related UI and save logic.
 */
class RecoveryImportController
{
    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        // Import step 2 — disaster recovery section
        add_action('duplicator_import_step2_before_overview', [self::class, 'renderStep2RecoverySection']);

        // Import installer — recovery URL in top bar
        add_action('duplicator_import_installer_top_bar', [self::class, 'renderInstallerTopBar']);

        // Import settings — inject recovery custom path fields
        add_action('duplicator_import_settings_after_fields', [self::class, 'renderSettingsFields']);

        // Import settings — save recovery custom path
        add_filter('duplicator_import_settings_save', [self::class, 'saveSettings'], 10, 2);

        // Capability labels
        add_filter('duplicator_capability_labels', [self::class, 'addCapabilityLabels']);
    }

    /**
     * Render disaster recovery section in import step 2
     *
     * @return void
     */
    public static function renderStep2RecoverySection(): void
    {
        TplMng::getInstance()->render('recoveryaddon/import/step2_recovery_section');
    }

    /**
     * Render recovery URL button in import installer top bar
     *
     * @return void
     */
    public static function renderInstallerTopBar(): void
    {
        TplMng::getInstance()->render('recoveryaddon/import/installer_top_bar');
    }

    /**
     * Render recovery settings fields in import settings
     *
     * @return void
     */
    public static function renderSettingsFields(): void
    {
        TplMng::getInstance()->render('recoveryaddon/settings/import_recovery_fields');
    }

    /**
     * Save recovery custom path during import settings save
     *
     * @param array<string, mixed>  $result         Current save result
     * @param SettingsChangeTracker $changesTracker Changes tracker
     *
     * @return array<string, mixed>
     */
    public static function saveSettings(array $result, SettingsChangeTracker $changesTracker): array
    {
        if ($result['saveSuccess'] === false) {
            return $result;
        }

        $newRecoveryCustomPath = filter_input(
            INPUT_POST,
            'recovery_custom_path',
            FILTER_CALLBACK,
            [
                'options' => [
                    SnapUtil::class,
                    'sanitizeNSCharsNewlineTrim',
                ],
            ]
        );

        if ($newRecoveryCustomPath === null) {
            return $result;
        }

        $changesTracker->addChange(
            'recovery_custom_path',
            RecoveryPackage::getRecoveryCustomPath(),
            $newRecoveryCustomPath,
            'fieldChanged',
            [
                'truncate' => true,
                'max'      => 80,
            ]
        );

        $failMessage = '';
        if (RecoveryPackage::setRecoveryCustomPath($newRecoveryCustomPath, $failMessage) === false) {
            $result['saveSuccess']  = false;
            $result['errorMessage'] = $failMessage;
        }

        return $result;
    }

    /**
     * Add recovery custom path capability label
     *
     * @param array<string, string> $labels Current labels
     *
     * @return array<string, string>
     */
    public static function addCapabilityLabels(array $labels): array
    {
        $labels['recovery_custom_path'] = __('Recovery custom path', 'duplicator-pro');

        return $labels;
    }
}
