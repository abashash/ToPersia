<?php

declare(strict_types=1);

namespace Duplicator\Addons\TemplateAddon\Controllers;

use Duplicator\Addons\TemplateAddon\Core\LicenseTemplate;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Controllers\SubMenuItem;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\TemplateEntity;
use Duplicator\Views\PackageScreen;

/**
 * Controller for the template management sub-tab on the Tools page
 */
class TemplateToolsController
{
    const L2_SLUG_TEMPLATE = 'templates';

    const TEMPLATE_INNER_PAGE_LIST = 'templates';
    const TEMPLATE_INNER_PAGE_EDIT = 'edit';

    const ACTION_SAVE_TEMPLATE   = 'save-template';
    const ACTION_COPY_TEMPLATE   = 'copy-template';
    const ACTION_DELETE_TEMPLATE = 'delete-template';

    /**
     * Register hooks for tools page integration
     *
     * @return void
     */
    public static function register(): void
    {
        $toolsSlug = ControllersManager::TOOLS_SUBMENU_SLUG;
        add_filter('duplicator_sub_menu_items_' . $toolsSlug, [self::class, 'addSubMenu']);
        add_action('duplicator_render_page_content_' . $toolsSlug, [self::class, 'renderContent'], 10, 2);
        add_filter('duplicator_page_actions_' . $toolsSlug, [self::class, 'pageActions']);
        add_action('duplicator_before_render_page_' . $toolsSlug, [self::class, 'beforeRenderPage'], 10, 2);
    }

    /**
     * Add templates sub-menu to tools page
     *
     * @param SubMenuItem[] $subMenus sub menus list
     *
     * @return SubMenuItem[]
     */
    public static function addSubMenu(array $subMenus): array
    {
        $subMenus[] = new SubMenuItem(self::L2_SLUG_TEMPLATE, __('Templates', 'duplicator-pro'), '', CapMng::CAP_CREATE, 50);
        return $subMenus;
    }

    /**
     * Set blur before rendering template pages
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page
     *
     * @return void
     */
    public static function beforeRenderPage(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_TEMPLATE) {
            return;
        }

        TplMng::getInstance()->setGlobalValue('blur', !LicenseTemplate::can());
    }

    /**
     * Render template page content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page
     *
     * @return void
     */
    public static function renderContent(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_TEMPLATE) {
            return;
        }

        switch ($innerPage) {
            case self::TEMPLATE_INNER_PAGE_EDIT:
                $templateId = TplMng::getInstance()->getGlobalValue(
                    'actionTemplateId',
                    SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'package_template_id', -1)
                );
                $template   = self::getTemplateOrNew($templateId);
                TplMng::getInstance()->render(
                    'templateaddon/template_edit',
                    ['template' => $template]
                );
                break;
            case self::TEMPLATE_INNER_PAGE_LIST:
            default:
                TplMng::getInstance()->render('templateaddon/template_list');
                break;
        }
    }

    /**
     * Register page actions for template save/copy/delete
     *
     * @param PageAction[] $actions actions list
     *
     * @return PageAction[]
     */
    public static function pageActions(array $actions): array
    {
        $toolsSlug = ControllersManager::TOOLS_SUBMENU_SLUG;

        $actions[] = new PageAction(
            self::ACTION_SAVE_TEMPLATE,
            [
                self::class,
                'actionSaveTemplate',
            ],
            [$toolsSlug],
            self::TEMPLATE_INNER_PAGE_EDIT
        );
        $actions[] = new PageAction(
            self::ACTION_COPY_TEMPLATE,
            [
                self::class,
                'actionCopyTemplate',
            ],
            [$toolsSlug],
            self::TEMPLATE_INNER_PAGE_EDIT
        );
        $actions[] = new PageAction(
            self::ACTION_DELETE_TEMPLATE,
            [
                self::class,
                'actionDeleteTemplate',
            ],
            [$toolsSlug]
        );

        return $actions;
    }

    /**
     * Action save template
     *
     * @return array<string, mixed>
     */
    public static function actionSaveTemplate(): array
    {
        $templateId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'package_template_id', -1);
        $template   = self::getTemplateOrNew($templateId);

        $result = [];
        if ($template->setFromInput(SnapUtil::INPUT_REQUEST) && $template->save()) {
            $result['successMessage'] = __("Template saved.", 'duplicator-pro');
        } else {
            $result['errorMessage'] = __("Couldn't save template.", 'duplicator-pro');
        }

        $result['actionTemplateId'] = $template->getId();

        return $result;
    }

    /**
     * Action copy template
     *
     * @return array<string, mixed>
     */
    public static function actionCopyTemplate(): array
    {
        $sourceTemplateId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'dupli-source-template-id', -1);
        if ($sourceTemplateId < 0) {
            return [];
        }

        $templateId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'package_template_id', -1);
        $template   = self::getTemplateOrNew($templateId);

        $template->copyFromSourceId($sourceTemplateId);

        $result = [];
        if ($template->save()) {
            $result['successMessage'] = __("Template saved.", 'duplicator-pro');
        } else {
            $result['errorMessage'] = __("Couldn't save template.", 'duplicator-pro');
        }

        $result['actionTemplateId'] = $template->getId();

        return $result;
    }

    /**
     * Action delete template
     *
     * @return array<string, mixed>
     */
    public static function actionDeleteTemplate(): array
    {
        if (!isset($_REQUEST['selected_id'])) {
            return ['errorMessage' => __("No template selected.", 'duplicator-pro')];
        }

        $templateIds = filter_var_array($_REQUEST, [
            'selected_id' => [
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => ['default' => false],
            ],
        ]);

        if (empty($templateIds['selected_id'])) {
            return ['errorMessage' => __("No template selected.", 'duplicator-pro')];
        }

        foreach ($templateIds['selected_id'] as $id) {
            $template = TemplateEntity::getById($id);
            if ($template === false || $template->is_default) {
                // The default template is not deletable.
                continue;
            }
            TemplateEntity::deleteById($id);
        }

        return ['successMessage' => __("Template(s) deleted.", 'duplicator-pro')];
    }

    /**
     * Get existing template by ID or create a new one
     *
     * @param int $templateId template ID, -1 for new template
     *
     * @return TemplateEntity
     */
    private static function getTemplateOrNew(int $templateId): TemplateEntity
    {
        if ($templateId == -1) {
            return new TemplateEntity();
        }

        $template = TemplateEntity::getById($templateId);
        if (!$template instanceof TemplateEntity) {
            return new TemplateEntity();
        }

        return $template;
    }

    /**
     * Return template edit URL
     *
     * @param false|int $templateId template ID, if false return base template edit url
     *
     * @return string
     */
    public static function getTemplateEditURL($templateId = false): string
    {
        $data = [ControllersManager::QUERY_STRING_INNER_PAGE => 'edit'];
        if ($templateId !== false) {
            $data['package_template_id'] = $templateId;
        }
        return ControllersManager::getMenuLink(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_TEMPLATE,
            null,
            $data
        );
    }

    /**
     * Return template list URL
     *
     * @return string
     */
    public static function getTemplateListURL(): string
    {
        return ControllersManager::getMenuLink(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_TEMPLATE
        );
    }

    /**
     * Render template selector in package setup (hooked into duplicator_package_general_area_after)
     *
     * @param TemplateEntity $manualTemplate the manual template
     *
     * @return void
     */
    public static function renderPackageTemplateSelector(TemplateEntity $manualTemplate): void
    {
        TplMng::getInstance()->render(
            'templateaddon/package_template_selector',
            ['manualTemplate' => $manualTemplate]
        );
    }

    /**
     * Render template selector in schedule edit (hooked into duplicator_schedule_edit_after_name)
     *
     * @param \Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity $schedule the schedule being edited
     *
     * @return void
     */
    public static function renderScheduleTemplateSelector($schedule): void
    {
        TplMng::getInstance()->render(
            'templateaddon/schedule_template_selector',
            ['selectedTemplateId' => (int) $schedule->template_id]
        );
    }

    /**
     * Render template button in packages toolbar (hooked into duplicator_packages_toolbar_buttons)
     *
     * @return void
     */
    public static function renderPackagesToolbarButton(): void
    {
        TplMng::getInstance()->render('templateaddon/packages_toolbar_button');
    }

    /**
     * Render template button in schedules toolbar (hooked into duplicator_schedules_toolbar_buttons)
     *
     * @return void
     */
    public static function renderSchedulesToolbarButton(): void
    {
        TplMng::getInstance()->render('templateaddon/schedules_toolbar_button');
    }

    /**
     * Render template section in dashboard widget (hooked into duplicator_dashboard_widget_sections)
     *
     * @return void
     */
    public static function renderDashboardSection(): void
    {
        TplMng::getInstance()->render('templateaddon/dashboard_widget_section');
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
        if ($tab !== self::L2_SLUG_TEMPLATE) {
            return $context;
        }

        if ($innerPage === 'edit') {
            return 'template_edit';
        }

        return 'templates';
    }

    /**
     * Build the legend entries of the Templates list flags icons, filtered for addon entries.
     *
     * @return array<int, array{icon: string, label: string, section: string}>
     */
    public static function getTemplateFlagsLegendEntries(): array
    {
        /**
         * Filter the Templates list flags legend entries. Addons append their own;
         * a missing `section` key places the entry in the last section.
         *
         * @param array<int, array{icon: string, label: string, section?: string}> $entries
         */
        return apply_filters('duplicator_template_flags_legend', PackageScreen::getTemplateFlagsLegendEntries());
    }
}
