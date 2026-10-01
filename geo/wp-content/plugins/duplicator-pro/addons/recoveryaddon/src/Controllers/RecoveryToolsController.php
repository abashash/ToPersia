<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Controllers;

use Duplicator\Addons\RecoveryAddon\Core\LicenseRecovery;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\SubMenuItem;
use Duplicator\Core\Views\TplMng;

/**
 * Hooks into the Tools page to add the Recovery tab.
 */
class RecoveryToolsController
{
    const L2_SLUG_RECOVERY = 'recovery';

    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        $toolsSlug = ControllersManager::TOOLS_SUBMENU_SLUG;
        add_filter('duplicator_sub_menu_items_' . $toolsSlug, [self::class, 'addSubMenu']);
        add_action('duplicator_render_page_content_' . $toolsSlug, [self::class, 'renderContent'], 10, 2);
        add_action('duplicator_before_render_page_' . $toolsSlug, [self::class, 'beforeRenderPage'], 10, 2);
    }

    /**
     * Add recovery sub-menu item
     *
     * @param SubMenuItem[] $subMenus Current sub-menus
     *
     * @return SubMenuItem[]
     */
    public static function addSubMenu(array $subMenus): array
    {
        $subMenus[] = new SubMenuItem(self::L2_SLUG_RECOVERY, __('Recovery', 'duplicator-pro'), '', CapMng::CAP_BACKUP_RESTORE, 60);

        return $subMenus;
    }

    /**
     * Set blur before rendering recovery page
     *
     * @param string[] $currentLevelSlugs Current menu slugs
     * @param string   $innerPage         Current inner page
     *
     * @return void
     */
    public static function beforeRenderPage(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_RECOVERY) {
            return;
        }

        TplMng::getInstance()->setGlobalValue('blur', !LicenseRecovery::can());
    }

    /**
     * Render recovery page content
     *
     * @param string[] $currentLevelSlugs Current menu slugs
     * @param string   $innerPage         Current inner page
     *
     * @return void
     */
    public static function renderContent(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_RECOVERY) {
            return;
        }

        TplMng::getInstance()->render('recoveryaddon/admin_pages/tools/recovery/recovery');
    }

    /**
     * Filter help context for tools sub-tabs (hooked into duplicator_help_tools_context)
     *
     * @param string      $context   current help context
     * @param string|null $tab       current tab slug
     * @param string      $innerPage current inner page
     *
     * @return string
     */
    public static function filterHelpContext(string $context, ?string $tab, string $innerPage): string
    {
        if ($tab !== self::L2_SLUG_RECOVERY) {
            return $context;
        }

        return 'recovery';
    }

    /**
     * Return true if current page is recovery page
     *
     * @return bool
     */
    public static function isRecoveryPage(): bool
    {
        return ControllersManager::isCurrentPage(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_RECOVERY
        );
    }
}
