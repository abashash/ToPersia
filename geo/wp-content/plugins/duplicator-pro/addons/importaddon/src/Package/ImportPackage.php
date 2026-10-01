<?php

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon\Package;

use Duplicator\Addons\ImportAddon\Controllers\ImportInstallerPageController;
use Duplicator\Addons\ImportAddon\Controllers\ImportPageController;
use Duplicator\Core\Views\TplMng;
use Duplicator\Installer\Core\Params\PrmMng;
use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Package\AbstractPackageDeployer;
use Duplicator\Views\ScreenBase;

/**
 * Import package - extends AbstractPackageDeployer with import-specific parameters
 */
class ImportPackage extends AbstractPackageDeployer
{
    /**
     * Return import-specific overwrite params
     *
     * @param array<string, array{value: mixed, formStatus?: string}> $baseParams Base params
     *
     * @return array<string, array{value: mixed, formStatus?: string}>
     */
    protected function getOverwriteParamsExtended(array $baseParams): array
    {
        $recoveryData = apply_filters('duplicator_recovery_package_data', [
            'id'             => false,
            'install_link'   => '',
            'is_out_to_date' => true,
            'package_life'   => -1,
            'page_link'      => '',
        ]);

        return [
            PrmMng::PARAM_TEMPLATE              => ['value' => 'import-base'],
            PrmMng::PARAM_RECOVERY_LINK         => ['value' => $recoveryData['install_link']],
            PrmMng::PARAM_FROM_SITE_IMPORT_INFO => [
                'value' => [
                    'import_page'             => ImportPageController::getImportPageLink(),
                    'recovery_page'           => $recoveryData['page_link'],
                    'recovery_is_out_to_date' => $recoveryData['is_out_to_date'],
                    'recovery_package_life'   => $recoveryData['package_life'],
                    'color-scheme'            => ScreenBase::getCurrentColorScheme(),
                    'color-primary-button'    => ScreenBase::getPrimaryButtonColorByScheme(),
                ],
            ],
        ];
    }

    /**
     * Generate html Backup details
     *
     * @param bool $echo if true echo html
     *
     * @return string
     */
    public function getHtmlDetails(bool $echo = true): string
    {
        return TplMng::getInstance()->render(
            'importaddon/import-package-details',
            ['importObj' => $this],
            $echo
        );
    }

    /**
     * Return admin installer page link for this archive
     *
     * @return string
     */
    public function getInstallerPageLink(): string
    {
        return ImportInstallerPageController::getInstallerPageLink($this->archive);
    }

    /**
     * Add import custom path to the list of deploy folders to check for archives
     *
     * @param string[] $folders Current folders list
     *
     * @return string[]
     */
    public static function addImportCustomPath(array $folders): array
    {
        $customPath = DynamicGlobalEntity::getInstance()->getValString('import_custom_path');
        if (strlen($customPath) > 0 && is_dir($customPath) && is_readable($customPath)) {
            $folders[] = $customPath;
        }

        return $folders;
    }
}
