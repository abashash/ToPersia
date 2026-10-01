<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Package;

use Duplicator\Package\AbstractPackage;
use Duplicator\Package\AbstractPackageDeployer;
use Duplicator\Package\DupPackage;
use Duplicator\Package\FullBackupStatus;

/**
 * Extends FullBackupStatus with recovery-specific checks.
 *
 * A package is "recoverable" when it is a full backup, has local storage,
 * and meets the minimum version requirement for deployment.
 *
 * @phpstan-import-type IneligibilityReasons from FullBackupStatus
 */
class RecoveryStatus extends FullBackupStatus
{
    /**
     * Return true if current object is recoverable.
     *
     * Recoverable = full backup + local storage + version compatible.
     *
     * @return bool
     */
    public function isRecoverable()
    {
        if (
            ($this->object instanceof DupPackage) &&
            version_compare($this->object->getVersion(), AbstractPackageDeployer::DEPLOY_ENABLE_MIN_VERSION, '<')
        ) {
            return false;
        }

        return $this->isLocalStorageEnabled() && $this->isFullBackup();
    }

    /**
     * Backward-compatible alias for isFullBackup().
     *
     * @param IneligibilityReasons $reasons Populated by reference with ineligibility data
     *
     * @return bool
     */
    public function meetsRecoveryRequirements(array &$reasons = []): bool
    {
        return $this->isFullBackup($reasons);
    }

    /**
     * Is the local storage type enabled for the various object types
     *
     * @return bool Returns true if the object type has a local default storage associated with it
     *
     * @notes:
     * Templates do not have local storage associations so the result will always be true for that type
     */
    public function isLocalStorageEnabled(): bool
    {
        if ($this->object instanceof DupPackage) {
            return $this->object->getLocalPackageFilePath(AbstractPackage::FILE_TYPE_ARCHIVE) !== false;
        }

        // Templates do not have local storage associations so the result will always be true
        return true;
    }
}
