<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Views;

use Duplicator\Views\ViewHelper;

class RecoveryViewHelper
{
    /**
     * Get disaster recovery icon
     *
     * @param bool            $echo    Echo or return
     * @param string|string[] $classes HTML class list
     *
     * @return string HTML string
     */
    public static function disasterIcon($echo = true, $classes = []): string
    {
        if (!is_array($classes)) {
            $classes = [$classes];
        }
        array_unshift($classes, 'fa-house-fire');
        return ViewHelper::icon($echo, $classes);
    }
}
