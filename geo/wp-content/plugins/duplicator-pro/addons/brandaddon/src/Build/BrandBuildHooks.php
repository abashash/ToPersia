<?php

declare(strict_types=1);

namespace Duplicator\Addons\BrandAddon\Build;

use Duplicator\Addons\BrandAddon\Models\BrandEntity;
use Duplicator\Models\TemplateEntity;

/**
 * Build pipeline hooks for brand addon
 *
 * Handles archive descriptor configuration and build requirement checks.
 */
class BrandBuildHooks
{
    /**
     * Filter archive config to add brand header data
     *
     * @param object                              $ac      archive config descriptor
     * @param \Duplicator\Package\AbstractPackage $package the package being built
     *
     * @return object
     */
    public static function filterArchiveConfig(object $ac, $package): object
    {
        $template = TemplateEntity::getById($package->template_id);
        $brandId  = $template !== false ? (int) $template->getExtraData('brand_id', -2) : -2;

        $brand = BrandEntity::getByIdOrDefault($brandId);
        $brand->prepareAttachmentsInstaller();

        $brandProperty              = [];
        $brandProperty['isDefault'] = $brand->isDefault();
        $brandProperty['name']      = $brandProperty['isDefault'] ? DUPLICATOR____NAME : $brand->name;
        $brandProperty['logo']      = $brand->logo;

        $arrImg = [];
        if (preg_match_all('/<img.*?src="([^"]+)".*?>/', $brand->logo, $arrImg, PREG_PATTERN_ORDER) >= 1) {
            if (count($brand->attachments) > 0 && count($arrImg[1]) === count($brand->attachments)) {
                foreach ($arrImg[1] as $i => $find) {
                    $brandProperty['logo'] = str_replace($find, 'assets/images/brand' . $brand->attachments[$i], $brandProperty['logo']);
                }
            }
        }
        $brandProperty['logo'] = stripslashes($brandProperty['logo']);

        $brandProperty['enabled'] = (!empty($brandProperty['logo']) && $brand->active);
        $brandProperty['style']   = $brand->style;

        $ac->header = array_replace([
            'name'      => DUPLICATOR____NAME,
            'isDefault' => true,
            'logo'      => '',
            'enabled'   => false,
            'style'     => [],
        ], $brandProperty);

        return $ac;
    }

    /**
     * Filter build requirement checks to add brand logo check
     *
     * @param array<string, mixed> $checks current checks
     *
     * @return array<string, mixed>
     */
    public static function filterBuildRequirementChecks(array $checks): array
    {
        $manualTemplate = TemplateEntity::getManualTemplate();
        $brandId        = (int) $manualTemplate->getExtraData('brand_id', -2);
        $brand          = BrandEntity::getByIdOrDefault($brandId);

        if (!is_array($brand->attachments)) {
            $checks['SRV']['Brand'] = [
                'LogoAttachmentExists' => false,
                'LogoCount'            => 0,
                'LogoFinded'           => 0,
                'LogoImageExists'      => true,
                'LogoImages'           => [],
                'Name'                 => $brand->name,
                'Notes'                => $brand->notes,
            ];
            return $checks;
        }

        $attachments = count($brand->attachments);
        $exists      = [];
        if ($attachments > 0) {
            $installer = DUPLICATOR____PATH . '/installer/dup-installer/assets/images/brand';
            if (file_exists($installer) && is_dir($installer)) {
                foreach ($brand->attachments as $attachment) {
                    if (file_exists("{$installer}{$attachment}")) {
                        $exists[] = "{$installer}{$attachment}";
                    }
                }
            }
        }

        $checks['SRV']['Brand'] = [
            'LogoAttachmentExists' => ($attachments > 0),
            'LogoCount'            => $attachments,
            'LogoFinded'           => count($exists),
            'LogoImageExists'      => ($attachments == count($exists)),
            'LogoImages'           => $exists,
            'Name'                 => $brand->name,
            'Notes'                => $brand->notes,
        ];

        return $checks;
    }
}
