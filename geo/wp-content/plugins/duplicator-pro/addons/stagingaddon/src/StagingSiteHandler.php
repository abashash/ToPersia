<?php

declare(strict_types=1);

namespace Duplicator\Addons\StagingAddon;

use WP_Admin_Bar;
use Duplicator\Core\CapMng;
use Duplicator\Core\Views\TplMng;
use Duplicator\Installer\Models\MigrateData;
use Duplicator\Models\DynamicGlobalEntity;

/**
 * Runtime behavior and restrictions applied on staging sites
 *
 * Covers post-installation configuration, admin UI indicators, email blocking,
 * and backup table-filter enforcement.
 */
class StagingSiteHandler
{
    const KEY_MAIN_URL   = 'staging_main_url';
    const KEY_PAGE_URL   = 'staging_page_url';
    const KEY_CREATED_AT = 'staging_created_at';
    const KEY_IDENTIFIER = 'staging_identifier';
    const KEY_COLOR      = 'staging_color_scheme';
    const KEY_TITLE      = 'staging_title';

    /** @var string[] All staging DynamicGlobalEntity keys */
    const ALL_KEYS = [
        self::KEY_MAIN_URL,
        self::KEY_PAGE_URL,
        self::KEY_CREATED_AT,
        self::KEY_IDENTIFIER,
        self::KEY_COLOR,
        self::KEY_TITLE,
    ];

    /** @var string[] Capabilities disabled on staging sites by default */
    const DISABLED_CAPS = [
        CapMng::CAP_STORAGE,
    ];

    /** @var string Script handle for the Customizer controls-page patch. */
    const CUSTOMIZER_CONTROLS_HANDLE = 'dupli-addon-staging-customizer-controls';

    /** @var string Script handle for the Customizer preview-iframe patch. */
    const CUSTOMIZER_PREVIEW_HANDLE = 'dupli-addon-staging-customizer-preview';

    /** @var string Localized object name shared by both Customizer patches. */
    const CUSTOMIZER_LOCALIZE_OBJECT = 'dupli_staging_customizer';

    /**
     * Initialize staging site restrictions and UI elements
     *
     * @return void
     */
    public function init(): void
    {
        // Disable capabilities not needed on staging sites
        add_filter('duplicator_cap_enabled', [$this, 'filterCapabilities'], 10, 2);

        // Block all emails
        add_filter('wp_mail', [$this, 'blockEmails'], 999);

        // Add admin bar indicator
        add_action('admin_bar_menu', [$this, 'addAdminBarIcon'], 100);

        // Add admin notice
        add_action('admin_notices', [$this, 'showNotice']);

        // Enqueue staging styles for admin bar icon
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);

        // Enqueue staging styles for front-end admin bar icon
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);

        // Patch WordPress Customizer to work on staging sites (workaround for Trac #65030).
        add_action('customize_controls_enqueue_scripts', [$this, 'enqueueCustomizerControlsPatch']);
        add_action('customize_preview_init', [$this, 'enqueueCustomizerPreviewPatch']);

        // Backups on a staging site must only include its own prefixed tables
        add_filter('duplicator_force_database_prefix_filter', '__return_true');
    }

    /**
     * Check if current site is a staging site
     *
     * @return bool
     */
    public static function isStagingSite(): bool
    {
        return DynamicGlobalEntity::getInstance()->getValString(self::KEY_IDENTIFIER) !== '';
    }

    /**
     * Get staging data for current site
     *
     * @return array<string, string>
     */
    public static function getStagingData(): array
    {
        if (!self::isStagingSite()) {
            return [];
        }

        $dGlobal = DynamicGlobalEntity::getInstance();
        $data    = [];
        foreach (self::ALL_KEYS as $key) {
            $data[$key] = $dGlobal->getValString($key);
        }

        return $data;
    }

    /**
     * Store staging data for current site
     *
     * @param array<string, string> $data Staging metadata keyed by KEY_* constants
     *
     * @return void
     */
    public static function setStagingData(array $data): void
    {
        $dGlobal = DynamicGlobalEntity::getInstance();
        foreach (self::ALL_KEYS as $key) {
            $dGlobal->setValString($key, $data[$key] ?? '');
        }
        $dGlobal->save();
    }

    /**
     * Remove staging data for current site
     *
     * @return void
     */
    public static function clearStagingData(): void
    {
        $dGlobal = DynamicGlobalEntity::getInstance();
        foreach (self::ALL_KEYS as $key) {
            $dGlobal->removeVal($key);
        }
        $dGlobal->save();
    }

    /**
     * Keep staging state out of settings reset and export/import
     *
     * @param string[] $keys DynamicGlobalEntity keys to skip
     *
     * @return string[]
     */
    public static function skipDynamicStagingData(array $keys): array
    {
        return array_merge($keys, self::ALL_KEYS);
    }

    /**
     * Configure staging site after installation
     *
     * Sets up staging metadata, disables search indexing, applies color scheme
     *
     * @param MigrateData $migrationData Migration data
     *
     * @return void
     */
    public function configure(MigrateData $migrationData): void
    {
        $staging = $migrationData->extra['staging'] ?? [];
        if (empty($staging['enabled'])) {
            $this->resetStagingMarkers();
            return;
        }

        self::setStagingData([
            self::KEY_MAIN_URL   => $staging['mainSiteUrl'] ?? '',
            self::KEY_PAGE_URL   => $staging['pageUrl'] ?? '',
            self::KEY_CREATED_AT => current_time('mysql'),
            self::KEY_IDENTIFIER => $staging['identifier'] ?? '',
            self::KEY_COLOR      => $staging['colorScheme'] ?? 'fresh',
            self::KEY_TITLE      => $staging['title'] ?? '',
        ]);

        update_option('blog_public', '0');

        if (!empty($staging['title'])) {
            update_option('blogname', $staging['title']);
        }

        $this->applyColorScheme($staging['colorScheme'] ?? 'fresh');
    }

    /**
     * Clear staging markers left over from a restored staging-site database
     *
     * @return void
     */
    protected function resetStagingMarkers(): void
    {
        if (!self::isStagingSite()) {
            return;
        }

        self::clearStagingData();
        update_option('blog_public', '1');
    }

    /**
     * Apply color scheme to all admin users on staging site
     *
     * @param string $colorScheme WordPress admin color scheme
     *
     * @return void
     */
    protected function applyColorScheme(string $colorScheme): void
    {
        $colorScheme = self::sanitizeColorScheme($colorScheme);

        $adminUsers = get_users([
            'role__in' => ['administrator'],
            'fields'   => 'ID',
        ]);

        foreach ($adminUsers as $userId) {
            update_user_meta($userId, 'admin_color', $colorScheme);
        }
    }

    /**
     * Get valid WordPress admin color schemes
     *
     * Uses WordPress global $_wp_admin_css_colors which contains all registered schemes.
     *
     * @return string[]
     */
    public static function getValidColorSchemes(): array
    {
        global $_wp_admin_css_colors;

        return is_array($_wp_admin_css_colors) ? array_keys($_wp_admin_css_colors) : [];
    }

    /**
     * Validate and sanitize a color scheme value
     *
     * @param string $colorScheme Color scheme to validate
     *
     * @return string Valid color scheme (defaults to 'fresh' if invalid)
     */
    public static function sanitizeColorScheme(string $colorScheme): string
    {
        return in_array($colorScheme, self::getValidColorSchemes(), true) ? $colorScheme : 'fresh';
    }

    /**
     * Filter capabilities to disable pages not needed on staging sites.
     *
     * Addons can register their own disabled capabilities via the
     * 'duplicator_staging_disabled_caps' filter.
     *
     * @param bool   $enabled Whether the capability is enabled
     * @param string $cap     The capability being checked
     *
     * @return bool
     */
    public function filterCapabilities(bool $enabled, string $cap): bool
    {
        /** @var string[] */
        $disabledCaps = apply_filters('duplicator_staging_disabled_caps', self::DISABLED_CAPS);
        if (in_array($cap, $disabledCaps, true)) {
            return false;
        }

        return $enabled;
    }

    /**
     * Block emails on staging site
     *
     * @param array<string, mixed> $args Email arguments
     *
     * @return array<string, mixed>
     */
    public function blockEmails(array $args): array
    {
        // Return empty 'to' to prevent email from being sent
        $args['to'] = '';
        return $args;
    }

    /**
     * Add staging indicator to admin bar
     *
     * @param WP_Admin_Bar $adminBar Admin bar instance
     *
     * @return void
     */
    public function addAdminBarIcon(WP_Admin_Bar $adminBar): void
    {
        $stagingData = self::getStagingData();

        TplMng::getInstance()->render('stagingaddon/staging_adminbar', [
            'adminBar'       => $adminBar,
            'mainSiteUrl'    => $stagingData[self::KEY_MAIN_URL] ?? '',
            'identifier'     => $stagingData[self::KEY_IDENTIFIER] ?? '',
            'stagingPageUrl' => $stagingData[self::KEY_PAGE_URL] ?? '',
            'createdAt'      => $stagingData[self::KEY_CREATED_AT] ?? '',
        ]);
    }

    /**
     * Show staging site admin notice
     *
     * @return void
     */
    public function showNotice(): void
    {
        $stagingData = self::getStagingData();

        TplMng::getInstance()->render('stagingaddon/staging_notice', [
            'mainSiteUrl' => $stagingData[self::KEY_MAIN_URL] ?? '',
        ]);
    }

    /**
     * Enqueue styles for staging site admin bar
     *
     * @return void
     */
    public function enqueueStyles(): void
    {
        wp_enqueue_style(
            'dupli-addon-staging-site',
            StagingAddon::getAddonUrl() . '/assets/css/staging-site.css',
            [],
            DUPLICATOR_VERSION
        );
    }

    /**
     * Enqueue JS patch for the Customizer controls page on staging sites
     *
     * Wraps wp.customize.previewer.previewUrl's setter so URLs under the
     * staging baseUrl bypass core's /wp-content/ rejection regex.
     *
     * @return void
     */
    public function enqueueCustomizerControlsPatch(): void
    {
        $this->enqueueCustomizerPatch(
            self::CUSTOMIZER_CONTROLS_HANDLE,
            'customizer-controls-patch.js',
            'customize-controls'
        );
    }

    /**
     * Enqueue JS patch for the Customizer preview iframe on staging sites
     *
     * Wraps wp.customize.isLinkPreviewable so internal preview links under
     * the staging baseUrl are not marked unpreviewable by core's regex.
     *
     * @return void
     */
    public function enqueueCustomizerPreviewPatch(): void
    {
        $this->enqueueCustomizerPatch(
            self::CUSTOMIZER_PREVIEW_HANDLE,
            'customizer-preview-patch.js',
            'customize-preview'
        );
    }

    /**
     * Register and localize one Customizer JS patch.
     *
     * @param string $handle Script handle.
     * @param string $file   JS filename under assets/js/.
     * @param string $dep    WordPress core script handle this patch depends on.
     *
     * @return void
     */
    private function enqueueCustomizerPatch(string $handle, string $file, string $dep): void
    {
        wp_enqueue_script(
            $handle,
            StagingAddon::getAddonUrl() . '/assets/js/' . $file,
            [$dep],
            DUPLICATOR_VERSION,
            true
        );

        wp_localize_script(
            $handle,
            self::CUSTOMIZER_LOCALIZE_OBJECT,
            ['baseUrl' => trailingslashit(home_url())]
        );
    }
}
