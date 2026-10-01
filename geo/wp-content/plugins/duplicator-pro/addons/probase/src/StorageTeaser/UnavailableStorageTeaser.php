<?php

declare(strict_types=1);

namespace Duplicator\Addons\ProBase\StorageTeaser;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\ProBase;
use Duplicator\Core\Views\TplMng;

class UnavailableStorageTeaser
{
    /**
     * Register hooks for the unavailable storage teaser.
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('duplicator_pro_after_storage_form', [self::class, 'render']);
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);
    }

    /**
     * Render the teaser grid below the storage list when the license is missing.
     *
     * @return void
     */
    public static function render(): void
    {
        if (License::can(License::FEATURE_STORAGE_PLUS)) {
            return;
        }

        $items = apply_filters('duplicator_pro_unavailable_storage_types', []);
        if (empty($items)) {
            return;
        }

        TplMng::getInstance()->render(
            'storageteaser/unavailable_storage_grid',
            ['items' => $items]
        );
    }

    /**
     * @param string $path    path to the template file
     * @param string $slugTpl slug of the template
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'storageteaser/') === 0) {
            return ProBase::getAddonPath() . '/template/' . $slugTpl . '.php';
        }
        return $path;
    }
}
