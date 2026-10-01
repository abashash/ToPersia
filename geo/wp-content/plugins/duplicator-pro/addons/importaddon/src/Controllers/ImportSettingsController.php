<?php

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon\Controllers;

use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Controllers\SubMenuItem;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Addons\ImportAddon\Models\ActivityLog\LogEventImportSettingsChange;
use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Models\GlobalEntity;
use Duplicator\Utils\ActivityLog\SettingsChangeTracker;

/**
 * Controller for the import settings sub-tab
 */
class ImportSettingsController
{
    const L2_SLUG_IMPORT = 'import';

    const ACTION_IMPORT_SAVE_SETTINGS = 'import-save-set';

    /**
     * Register hooks for settings integration
     *
     * @return void
     */
    public static function register(): void
    {
        $settingsSlug = ControllersManager::SETTINGS_SUBMENU_SLUG;
        add_filter('duplicator_sub_menu_items_' . $settingsSlug, [self::class, 'addSubMenu'], 10);
        add_action('duplicator_render_page_content_' . $settingsSlug, [self::class, 'renderContent'], 10, 2);
        add_filter('duplicator_page_actions_' . $settingsSlug, [self::class, 'pageActions']);
    }

    /**
     * Add import sub-menu to settings page
     *
     * @param SubMenuItem[] $subMenus sub menus list
     *
     * @return SubMenuItem[]
     */
    public static function addSubMenu(array $subMenus): array
    {
        $subMenus[] = new SubMenuItem(self::L2_SLUG_IMPORT, __('Import', 'duplicator-pro'), '', true, 60);
        return $subMenus;
    }

    /**
     * Render import settings content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page
     *
     * @return void
     */
    public static function renderContent(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_IMPORT) {
            return;
        }

        TplMng::getInstance()->render('importaddon/settings/import');
    }

    /**
     * Register page actions for import settings save
     *
     * @param PageAction[] $actions actions lists
     *
     * @return PageAction[]
     */
    public static function pageActions(array $actions): array
    {
        $settingsSlug = ControllersManager::SETTINGS_SUBMENU_SLUG;

        $actions[] = new PageAction(
            self::ACTION_IMPORT_SAVE_SETTINGS,
            [
                self::class,
                'saveImportSettings',
            ],
            [
                $settingsSlug,
                self::L2_SLUG_IMPORT,
            ]
        );

        return $actions;
    }

    /**
     * Save import settings
     *
     * @return array<string, mixed>
     */
    public static function saveImportSettings(): array
    {
        $result        = ['saveSuccess' => true];
        $changesTraker = new SettingsChangeTracker();
        $global        = GlobalEntity::getInstance();
        $dGlobal       = DynamicGlobalEntity::getInstance();

        // Track import chunk size changes
        $newChunkSize           = filter_input(
            INPUT_POST,
            'import_chunk_size',
            FILTER_VALIDATE_INT,
            [
                'options' => ['default' => DUPLICATOR_DEFAULT_CHUNK_UPLOAD_SIZE],
            ]
        );
        $importChunkSizeOptions = ImportPageController::getChunkSizes();
        $changesTraker->addChange(
            'import_chunk_size',
            $dGlobal->getValInt('import_chunk_size'),
            $newChunkSize,
            'optionChanged',
            $importChunkSizeOptions
        );
        $dGlobal->setValInt('import_chunk_size', $newChunkSize);

        // Track import custom path changes
        $newCustomPath = filter_input(
            INPUT_POST,
            'import_custom_path',
            FILTER_CALLBACK,
            [
                'options' => [
                    SnapUtil::class,
                    'sanitizeNSCharsNewlineTrim',
                ],
            ]
        );
        $changesTraker->addChange(
            'import_custom_path',
            $dGlobal->getValString('import_custom_path'),
            $newCustomPath,
            'fieldChanged',
            [
                'truncate' => true,
                'max'      => 80,
            ]
        );
        $dGlobal->setValString('import_custom_path', $newCustomPath);

        if (
            strlen($newCustomPath) > 0 &&
            (
                !is_dir($newCustomPath) ||
                !is_readable($newCustomPath)
            )
        ) {
            $result['errorMessage'] = __(
                'The custom path isn\'t a valid directory. Check that it exists or that access to it is not restricted by PHP\'s open_basedir setting.',
                'duplicator-pro'
            );
            $dGlobal->setValString('import_custom_path', '');
            $result['saveSuccess'] = false;
            return $result;
        }

        // Allow other addons to save their settings and report errors
        $result = apply_filters('duplicator_import_settings_save', $result, $changesTraker);
        if ($result['saveSuccess'] === false) {
            return $result;
        }

        $globalSaved  = $global->save();
        $dynamicSaved = $dGlobal->save();

        if (!$globalSaved || !$dynamicSaved) {
            $result['saveSuccess']  = false;
            $result['errorMessage'] = __('Can\'t save settings data', 'duplicator-pro');
        } else {
            $result['saveSuccess']    = true;
            $result['successMessage'] = __('Settings updated.', 'duplicator-pro');
            $changes                  = $changesTraker->consumeChanges();
            if (!empty($changes)) {
                LogEventImportSettingsChange::createEvent(['changes' => $changes]);
            }
        }

        return $result;
    }

    /**
     * Return the URL to the import settings tab
     *
     * @param string $url Current URL
     *
     * @return string
     */
    public static function getSettingsUrl(string $url): string
    {
        return ControllersManager::getMenuLink(
            ControllersManager::SETTINGS_SUBMENU_SLUG,
            self::L2_SLUG_IMPORT
        );
    }
}
