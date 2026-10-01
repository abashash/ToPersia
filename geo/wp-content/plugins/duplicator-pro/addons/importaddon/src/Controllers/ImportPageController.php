<?php

/**
 * Import menu page controller
 */

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon\Controllers;

use Duplicator\Addons\ImportAddon\Core\CapImport;
use Duplicator\Addons\ImportAddon\Core\LicenseImport;
use Duplicator\Addons\ImportAddon\Package\ImportPackage;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\DynamicGlobalEntity;

class ImportPageController extends AbstractMenuPageController
{
    const IMPORT_SUBMENU_SLUG = ControllersManager::MAIN_MENU_SLUG . '-import';

    const L2_TAB_UPLOAD     = 'upd';
    const L2_TAB_REMOTE_URL = 'dwn';

    const USER_META_VIEW_MODE = 'dupli_opt_import_view_mode';
    const VIEW_MODE_BASIC     = 'single';
    const VIEW_MODE_ADVANCED  = 'list';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $this->parentSlug   = ControllersManager::MAIN_MENU_SLUG;
        $this->pageSlug     = self::IMPORT_SUBMENU_SLUG;
        $this->pageTitle    = __('Import Backups', 'duplicator-pro');
        $this->menuLabel    = __('Import Backups', 'duplicator-pro');
        $this->capatibility = CapImport::CAP_IMPORT;
        $this->menuPos      = 20;

        add_filter('duplicator_page_template_data_' . $this->pageSlug, [$this, 'templateData']);
        add_action('duplicator_render_page_content_' . $this->pageSlug, [$this, 'renderContent'], 10, 2);
        add_action('duplicator_before_render_page_' . $this->pageSlug, [$this, 'beforeRenderPage'], 10, 2);
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
     * Set Backup object before render pages
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page, empty if not set
     *
     * @return void
     */
    public function beforeRenderPage($currentLevelSlugs, $innerPage): void
    {
        TplMng::getInstance()->setGlobalValue('blur', !LicenseImport::can());
    }

    /**
     * Import view mode
     *
     * @return string
     */
    public static function getViewMode(): string
    {
        return self::VIEW_MODE_ADVANCED;
    }

    /**
     * Return body header template. Can be overriden by child classes for custom header.
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page, empty if not set
     *
     * @return string
     */
    protected function getBodyHeaderTpl($currentLevelSlugs, $innerPage): string
    {
        return 'importaddon/import_wpbody_header';
    }

    /**
     * Return import page link
     *
     * @return string
     */
    public static function getImportPageLink(): string
    {
        $url      = is_multisite() ? network_admin_url('admin.php') : admin_url('admin.php');
        $queryStr = http_build_query(['page' => self::IMPORT_SUBMENU_SLUG]);
        return $url . '?' . $queryStr;
    }

    /**
     * Return chunk size
     *
     * @return int chunk size in k
     */
    public static function getChunkSize()
    {
        static $chunkSize = null;
        if (is_null($chunkSize)) {
            $postMaxSize       = SnapUtil::convertToBytes(ini_get('post_max_size'));
            $uploadMaxFilesize = SnapUtil::convertToBytes(ini_get('upload_max_filesize'));
            $importChunkSize   = DynamicGlobalEntity::getInstance()->getValInt('import_chunk_size');
            $chunkSettings     = SnapUtil::convertToBytes($importChunkSize . 'k');

            $chunkSize = floor(min(
                empty($postMaxSize) ? PHP_INT_MAX : max(0, $postMaxSize - MB_IN_BYTES),
                empty($uploadMaxFilesize) ? PHP_INT_MAX : $uploadMaxFilesize,
                $chunkSettings
            ) / 1024);
        }
        return $chunkSize;
    }

    /**
     * Return chunk sizes list with labels
     *
     * @return string[]
     */
    public static function getChunkSizes(): array
    {
        return [
            128   => __('100k [Slowest]', 'duplicator-pro'),
            256   => __('200k', 'duplicator-pro'),
            512   => __('500k', 'duplicator-pro'),
            1024  => __('1M', 'duplicator-pro'),
            2048  => __('2M', 'duplicator-pro'),
            5120  => __('5M', 'duplicator-pro'),
            10240 => __('10M [Very Fast]', 'duplicator-pro'),
            0     => __('Disabled [Fastest, BUT php.ini limits archive size]', 'duplicator-pro'),
        ];
    }

    /**
     * Add template data
     *
     * @param array<string, mixed> $data template glabal data
     *
     * @return array<string, mixed>
     */
    public function templateData($data)
    {
        $viewMode = self::getViewMode();
        $archives = ImportPackage::getArchiveList();
        if ($viewMode == self::VIEW_MODE_BASIC && count($archives) > 1) {
            $viewMode = self::VIEW_MODE_ADVANCED;
            update_user_meta(get_current_user_id(), self::USER_META_VIEW_MODE, $viewMode);

            $adminMessageViewModeSwtich = true;
        } else {
            $adminMessageViewModeSwtich = false;
        }

        $data['viewMode']                   = $viewMode;
        $data['adminMessageViewModeSwtich'] = $adminMessageViewModeSwtich;

        $slugs = $this->getCurrentMenuSlugs(false);
        if (isset($slugs[1]) && $slugs[1] == self::L2_TAB_REMOTE_URL) {
            $data['defSubtab'] = self::L2_TAB_REMOTE_URL;
        } else {
            $data['defSubtab'] = self::L2_TAB_UPLOAD;
        }

        return $data;
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
        TplMng::getInstance()->render('importaddon/import');
    }

    /**
     * Add help context for import pages
     *
     * @param string      $context   Current help context
     * @param string|null $pageSlug  Current page slug
     * @param string|null $innerPage Current inner page
     *
     * @return string
     */
    public static function addHelpContext(string $context, ?string $pageSlug, ?string $innerPage): string
    {
        if ($pageSlug !== self::IMPORT_SUBMENU_SLUG) {
            return $context;
        }

        return 'import';
    }

    /**
     * Render import button in packages toolbar
     *
     * @return void
     */
    public static function renderImportToolbarButton(): void
    {
        TplMng::getInstance()->render('importaddon/packages_toolbar_button');
    }

    /**
     * Provide recovery detail options when set from import page
     *
     * @param array<string, bool> $detailsOptions Current options
     * @param string              $fromPageTab    Originating page tab
     *
     * @return array<string, bool>
     */
    public static function getRecoveryDetailOptions(array $detailsOptions, string $fromPageTab): array
    {
        if (!empty($detailsOptions)) {
            return $detailsOptions;
        }

        $importPageId = ControllersManager::getPageUniqueId(self::IMPORT_SUBMENU_SLUG);
        if ($fromPageTab === $importPageId) {
            return [
                'selector'   => true,
                'launch'     => false,
                'download'   => true,
                'copyLink'   => true,
                'copyButton' => true,
                'info'       => true,
            ];
        }

        return $detailsOptions;
    }
}
