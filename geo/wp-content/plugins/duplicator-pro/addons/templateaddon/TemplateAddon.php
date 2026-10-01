<?php

/**
 * TEMPLATE ADDON
 */

declare(strict_types=1);

namespace Duplicator\Addons\TemplateAddon;

use Duplicator\Addons\TemplateAddon\Controllers\TemplateToolsController;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Models\TemplateEntity;

/**
 * Template addon class
 *
 * Adds multi-template management: create, edit, delete, and copy backup templates.
 */
class TemplateAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * Initialize the addon
     *
     * @return void
     */
    public function init(): void
    {
        TemplateToolsController::register();

        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);

        // Inject template selector dropdowns into package and schedule forms
        add_action('duplicator_package_general_area_after', [TemplateToolsController::class, 'renderPackageTemplateSelector']);
        add_action('duplicator_schedule_edit_after_name', [TemplateToolsController::class, 'renderScheduleTemplateSelector']);

        // Add "Templates" shortcut buttons to packages/schedules toolbars and dashboard widget
        add_action('duplicator_packages_toolbar_buttons', [TemplateToolsController::class, 'renderPackagesToolbarButton']);
        add_action('duplicator_schedules_toolbar_buttons', [TemplateToolsController::class, 'renderSchedulesToolbarButton']);
        add_action('duplicator_dashboard_widget_sections', [TemplateToolsController::class, 'renderDashboardSection']);

        // Map tools sub-tab slug to help context key for contextual help
        add_filter('duplicator_help_tools_context', [TemplateToolsController::class, 'filterHelpContext'], 10, 3);

        // Link template names to the edit page in recovery widget
        add_filter('duplicator_recovery_widget_template_name', [self::class, 'filterRecoveryWidgetTemplateName'], 10, 2);
    }

    /**
     * Get template file path for addon templates
     *
     * @param string $path    current path
     * @param string $slugTpl template slug
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'templateaddon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }
        return $path;
    }

    /**
     * Wrap template name in a link to the template edit page (recovery widget filter)
     *
     * @param string         $nameHtml escaped template name HTML
     * @param TemplateEntity $template the template entity
     *
     * @return string
     */
    public static function filterRecoveryWidgetTemplateName(string $nameHtml, TemplateEntity $template): string
    {
        if ($template->getId() < 0) {
            return $nameHtml;
        }

        return '<a href="' . esc_url(TemplateToolsController::getTemplateEditURL($template->getId())) . '">'
            . $nameHtml
            . '</a>';
    }

    /**
     * Get addon path
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     * Get addon file
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
