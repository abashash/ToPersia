<?php

declare(strict_types=1);

namespace Duplicator\Addons\ProBase\License;

use Duplicator\Core\Exceptions\DupliException;

/**
 * License-domain exception. Follows the DupliException model: the failure
 * type is the error code (CODE_*). License codes live here, in their own
 * code space outside the core one.
 */
class DupliLicenseException extends DupliException
{
    const CODE_SCHEDULE_LICENSE_BLOCKED = 1000;

    /**
     * License codes recognized as expected conditions are classified
     * handled; anything else falls back to the core default.
     *
     * @return string one of the SEVERITY_* constants
     */
    public function getSeverity(): string
    {
        switch ($this->getCode()) {
            case self::CODE_SCHEDULE_LICENSE_BLOCKED:
                return self::SEVERITY_HANDLED;
            default:
                return parent::getSeverity();
        }
    }
}
