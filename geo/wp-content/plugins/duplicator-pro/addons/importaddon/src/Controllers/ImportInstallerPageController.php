<?php

/**
 * Import installer page controller
 */

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon\Controllers;

use Duplicator\Addons\ImportAddon\Core\CapImport;
use Duplicator\Core\Bootstrap;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\AbstractSinglePageController;
use Duplicator\Core\Views\TplMng;
use Duplicator\Addons\ImportAddon\Package\ImportPackage;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Package\AbstractPackageDeployer;

class ImportInstallerPageController extends AbstractSinglePageController
{
    const IMPORT_INSTALLER_PAGE = ControllersManager::MAIN_MENU_SLUG . '-import-installer';

    /**
     * Return admin installer page link for the given archive
     *
     * @param string $archive Archive path
     *
     * @return string
     */
    public static function getInstallerPageLink(string $archive): string
    {
        $url      = is_multisite() ? network_admin_url('admin.php') : admin_url('admin.php');
        $queryStr = http_build_query([
            'page'    => self::IMPORT_INSTALLER_PAGE,
            'package' => $archive,
        ]);
        return $url . '?' . $queryStr;
    }

    /** @var AbstractPackageDeployer */
    protected static $importObj;
    /** @var string */
    protected static $iframeSrc;

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $this->pageSlug     = self::IMPORT_INSTALLER_PAGE;
        $this->pageTitle    = __('Install Backup', 'duplicator-pro');
        $this->capatibility = CapImport::CAP_IMPORT;

        add_action('duplicator_before_run_actions_' . $this->pageSlug, [$this, 'packageCheck']);
        add_action('duplicator_render_page_content_' . $this->pageSlug, [$this, 'renderContent'], 10, 2);
    }

    /**
     * Return true if current page is enabled
     *
     * @return boolean
     */
    public function isEnabled(): bool
    {
        return !((bool) DUPLICATOR_DISALLOW_IMPORT); // @phpstan-ignore-line
    }

    /**
     * called on admin_print_styles-[page] hook
     *
     * @return void
     */
    public function pageStyles(): void
    {
        Bootstrap::enqueueStyles();
        wp_enqueue_style('dupli-addon-import');
    }

    /**
     * called on admin_print_scripts-[page] hook
     *
     * @return void
     */
    public function pageScripts(): void
    {
        self::dequeueAllScripts();
        Bootstrap::enqueueScripts();
        wp_enqueue_script('dupli-import-installer');
    }

    /**
     * dequeue all scripts except jquery and dupli- scripts
     *
     * @return boolean // false if scripts can't be dequeued
     */
    public static function dequeueAllScripts(): bool
    {

        if (!function_exists('wp_scripts')) {
            return false;
        }

        $scripts = wp_scripts();
        foreach ($scripts->registered as $handle => $script) {
            if (
                strpos($handle, 'jquery') === 0 ||
                strpos($handle, 'dupli-') === 0
            ) {
                continue;
            }
            wp_dequeue_script($handle);
        }

        return true;
    }

    /**
     * Load import object and make a redirect if is a lite Backup
     *
     * @param array<string, string> $currentLevelSlugs current menu page
     *
     * @return void
     */
    public function packageCheck($currentLevelSlugs): void
    {
        $archivePath     = SnapUtil::sanitizeDefaultInput(INPUT_GET, 'package');
        self::$importObj = new ImportPackage($archivePath);
        self::$iframeSrc = self::$importObj->prepareToInstall();
    }

    /**
     * Render page content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page, empty if not set
     *
     * @return void
     */
    public function renderContent($currentLevelSlugs, $innerPage): void
    {
        $tplMng = TplMng::getInstance();
        $data   = $tplMng->getGlobalData();

        if ($data['actionsError']) {
            $tplMng->render('importaddon/import-installer-error');
        } else {
            $tplMng->render(
                'importaddon/import-installer',
                [
                    'importObj' => self::$importObj,
                    'iframeSrc' => self::$iframeSrc,
                ]
            );
        }
    }
}
