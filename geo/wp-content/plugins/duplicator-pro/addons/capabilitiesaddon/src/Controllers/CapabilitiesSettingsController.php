<?php

declare(strict_types=1);

namespace Duplicator\Addons\CapabilitiesAddon\Controllers;

use Duplicator\Addons\CapabilitiesAddon\Core\LicenseCapabilities;
use Duplicator\Addons\ProBase\Core\CapLicense;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Controllers\SubMenuItem;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\ActivityLog\LogEventSettingsChange;
use Duplicator\Utils\ActivityLog\SettingsChangeTracker;

/**
 * Controller for the capabilities settings sub-tab
 */
class CapabilitiesSettingsController
{
    const L2_SLUG_CAPABILITIES = 'capabilities';

    const ACTION_CAPABILITIES_SAVE  = 'cap-save';
    const ACTION_CAPABILITIES_RESET = 'cap-reset';

    /**
     * Register hooks for settings integration
     *
     * @return void
     */
    public static function register(): void
    {
        $settingsSlug = ControllersManager::SETTINGS_SUBMENU_SLUG;
        add_filter('duplicator_sub_menu_items_' . $settingsSlug, [self::class, 'addSubMenu'], 20);
        add_action('duplicator_render_page_content_' . $settingsSlug, [self::class, 'renderContent'], 10, 2);
        add_filter('duplicator_page_actions_' . $settingsSlug, [self::class, 'pageActions']);
    }

    /**
     * Add capabilities sub-menu to settings page
     *
     * @param SubMenuItem[] $subMenus sub menus list
     *
     * @return SubMenuItem[]
     */
    public static function addSubMenu(array $subMenus): array
    {
        $subMenus[] = new SubMenuItem(self::L2_SLUG_CAPABILITIES, __('Access', 'duplicator-pro'), '', true, 80);
        return $subMenus;
    }

    /**
     * Render capabilities page content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page
     *
     * @return void
     */
    public static function renderContent(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_CAPABILITIES) {
            return;
        }

        TplMng::getInstance()->render('capabilitiesaddon/capabilities');
    }

    /**
     * Register page actions for capabilities save/reset
     *
     * @param PageAction[] $actions actions list
     *
     * @return PageAction[]
     */
    public static function pageActions(array $actions): array
    {
        $settingsSlug = ControllersManager::SETTINGS_SUBMENU_SLUG;

        $actions[] = new PageAction(
            self::ACTION_CAPABILITIES_SAVE,
            [
                self::class,
                'saveCapabilities',
            ],
            [
                $settingsSlug,
                self::L2_SLUG_CAPABILITIES,
            ]
        );
        $actions[] = new PageAction(
            self::ACTION_CAPABILITIES_RESET,
            [
                self::class,
                'resetCapabilities',
            ],
            [
                $settingsSlug,
                self::L2_SLUG_CAPABILITIES,
            ]
        );

        return $actions;
    }

    /**
     * Save capabilities settings
     *
     * @return array<string, mixed>
     */
    public static function saveCapabilities(): array
    {
        $result        = ['saveSuccess' => false];
        $changesTraker = new SettingsChangeTracker();

        if (!LicenseCapabilities::can()) {
            $result['errorMessage'] = __('Current license does not allow managing capabilities.', 'duplicator-pro');
            return $result;
        }

        $capabilities = [];
        foreach (CapMng::getCapsList() as $capName) {
            $capabilities[$capName] = [
                'roles' => [],
                'users' => [],
            ];

            $inputName  = TplMng::getInputName('cap', $capName);
            $inputValue = filter_input(INPUT_POST, $inputName, FILTER_UNSAFE_RAW, [
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => [
                    'default' => [],
                ],
            ]);
            if ($inputValue === []) {
                continue;
            }

            foreach ($inputValue as $roleOrUser) {
                $roleOrUser = SnapUtil::sanitizeNSCharsNewlineTrim($roleOrUser);
                if (is_numeric($roleOrUser)) {
                    $capabilities[$capName]['users'][] = (int) $roleOrUser;
                } else {
                    $capabilities[$capName]['roles'][] = $roleOrUser;
                }
            }
        }

        // Track detailed capability changes
        $currentCapabilities = CapMng::getInstance()->getAllCapabilities();
        foreach (CapMng::getCapsList() as $capName) {
            $oldCap = $currentCapabilities[$capName] ?? [
                'roles' => [],
                'users' => [],
            ];
            $newCap = $capabilities[$capName] ?? [
                'roles' => [],
                'users' => [],
            ];
            if ($oldCap !== $newCap) {
                $changesTraker->addChange($capName, $oldCap, $newCap, 'singleCapabilityChanged');
            }
        }

        if (CapMng::getInstance()->update($capabilities) === false) {
            $result['saveSuccess']  = false;
            $result['errorMessage'] = __('Can\'t update capabilities.', 'duplicator-pro');
            return $result;
        }

        $result['successMessage'] = __('Capabilities updated.', 'duplicator-pro');
        $result['saveSuccess']    = true;
        $changesTraker->createLog(LogEventSettingsChange::SUB_TYPE_CAPABILITIES);

        return $result;
    }

    /**
     * Reset capabilities settings
     *
     * @return array<string, mixed>
     */
    public static function resetCapabilities(): array
    {
        $result        = ['saveSuccess' => false];
        $changesTraker = new SettingsChangeTracker();

        $capabilities = CapMng::getDefaultCaps();
        if (!CapMng::can(CapLicense::CAP_LICENSE)) {
            unset($capabilities[CapLicense::CAP_LICENSE]);
        }

        // Track detailed capability reset changes
        $currentCapabilities = CapMng::getInstance()->getAllCapabilities();
        foreach (CapMng::getCapsList() as $capName) {
            $oldCap = $currentCapabilities[$capName] ?? [
                'roles' => [],
                'users' => [],
            ];
            $newCap = $capabilities[$capName] ?? [
                'roles' => [],
                'users' => [],
            ];
            if ($oldCap !== $newCap) {
                $changesTraker->addChange($capName, $oldCap, $newCap, 'singleCapabilityChanged');
            }
        }

        if (CapMng::getInstance()->update($capabilities) === false) {
            $result['saveSuccess']  = false;
            $result['errorMessage'] = __('Can\'t update capabilities.', 'duplicator-pro');
        } else {
            $result['successMessage'] = __('Capabilities updated.', 'duplicator-pro');
            $result['saveSuccess']    = true;
            $changesTraker->createLog(LogEventSettingsChange::SUB_TYPE_CAPABILITIES, 'settings_reset');
        }

        return $result;
    }
}
