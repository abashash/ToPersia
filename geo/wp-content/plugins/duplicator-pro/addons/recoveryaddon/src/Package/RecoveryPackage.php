<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Package;

use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Libs\Snap\SnapURL;
use Duplicator\Libs\WpUtils\WpArchiveUtils;
use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Package\AbstractPackage;
use Duplicator\Package\DupPackage;
use Duplicator\Package\Restore\BackupPackage;
use Duplicator\Utils\PHPExecCheck;
use Error;
use Exception;

class RecoveryPackage extends BackupPackage
{
    const MAX_PACKAGES_LIST         = 50;
    const OPTION_RECOVER_PACKAGE_ID = 'dupli_opt_recover_point';
    const OUT_TO_HOURS_LIMIT        = 43200; // Seconds in 12 hours

    /** @var ?array<int, array{id: int, created: string, nameHash: string, name: string}> */
    protected static $recoveablesPackages;

    /**
     * @return bool check if Recovery is disallowed from wp-config.php
     */
    public static function isDisallow(): bool
    {
        return (bool) DUPLICATOR_DISALLOW_RECOVERY;
    }
    /** @var ?self */
    protected static $instance;

    /**
     *
     * @return int
     */
    public function getPackageId(): int
    {
        return $this->package->getId();
    }

    /**
     * Return Backup life
     *
     * @param string $type can be hours,human,timestamp
     *
     * @return int|string Backup life in hours, timestamp or human readable format
     */
    public function getPackageLife($type = 'timestamp')
    {
        $created = strtotime($this->getCreated());
        $current = strtotime(gmdate("Y-m-d H:i:s"));
        $delta   = $current - $created;

        switch ($type) {
            case 'hours':
                return max(0, floor($delta / 60 / 60));
            case 'human':
                return human_time_diff($created, $current);
            case 'timestamp':
            default:
                return $delta;
        }
    }

    /**
     * This function check if Backup is importable from scan info
     *
     * @param string $failMessage message if isn't importable
     *
     * @return bool
     */
    public function isImportable(&$failMessage = null): bool
    {
        if (parent::isImportable($failMessage) === false) {
            return false;
        }

        //The scan logic is going to be refactored, so only use info from the scan.json, if it's too complex to use the
        // archive config info
        if ($this->package->Archive->hasWpCoreFolderFiltered()) {
            $failMessage = __(
                'The Backup is missing WordPress core folder(s)! It must include wp-admin, wp-content, wp-includes, uploads, plugins, and themes folders.',
                'duplicator-pro'
            );
            return false;
        }

        if ($this->info->mu_mode !== 0 && $this->info->mu_is_filtered) {
            $failMessage = __('The Backup is missing some subsites.', 'duplicator-pro');
            return false;
        }

        if ($this->info->dbInfo->tablesBaseCount != $this->info->dbInfo->tablesFinalCount) {
            $failMessage = __('The Backup is missing some of the site tables.', 'duplicator-pro');
            return false;
        }

        $failMessage = '';
        return true;
    }

    /**
     *
     * @return bool
     */
    public function isOutToDate(): bool
    {
        return $this->getPackageLife() > self::OUT_TO_HOURS_LIMIT;
    }

    /**
     * Return installer folder path
     *
     * @return string|false false if impossibile exec the installer
     */
    public function getInstallerFolderPath()
    {
        switch ($this->getPathMode()) {
            case self::PATH_MODE_BACKUP:
                return DUPLICATOR_RECOVER_PATH;
            case self::PATH_MODE_CUSTOM:
                return self::getRecoveryCustomPath();
            case self::PATH_MODE_BRIDGE:
            case self::PATH_MODE_HOME:
            case self::PATH_MODE_CLASSIC:
            case self::PATH_MODE_NONE:
            default:
                return false;
        }
    }

    /**
     * Return installer folder url
     *
     * @return string|false false if impossibile exec the installer
     */
    public function getInstallerFolderUrl()
    {
        switch ($this->getPathMode()) {
            case self::PATH_MODE_BACKUP:
                return DUPLICATOR_RECOVER_URL;
            case self::PATH_MODE_CUSTOM:
                return self::getRecoveryCustomURL();
            case self::PATH_MODE_BRIDGE:
            case self::PATH_MODE_HOME:
            case self::PATH_MODE_CLASSIC:
            case self::PATH_MODE_NONE:
            default:
                return false;
        }
    }

    /**
     * return true if path have a recovery point sub path
     *
     * @param string $path path to check
     *
     * @return boolean
     */
    public static function isRecoverPath($path): bool
    {
        if (!defined('DUPLICATOR_SSDIR_NAME') || !defined('DUPLICATOR_RECOVER_DIR_NAME')) {
            return false;
        }

        $result = preg_match(
            '/[\/]' . preg_quote(DUPLICATOR_SSDIR_NAME, '/') . '[\/]' . preg_quote(DUPLICATOR_RECOVER_DIR_NAME, '/') . '[\/]/',
            $path
        );
        return ($result === 1);
    }

    /**
     * Return installer link
     *
     * @return string
     */
    public function getInstallLink(): string
    {
        $queryStr = http_build_query([
            'archive'    => dirname($this->archive),
            'dup_folder' => 'dup-installer-' . $this->info->packInfo->secondaryHash,
        ]);
        return $this->getInstallerFolderUrl() . '/' . $this->getInstallerName() . '?' . $queryStr;
    }

    /**
     * Get HTML launcher fil name
     *
     * @return string
     */
    public function getLauncherFileName(): string
    {

        $parseUrl     = SnapURL::parseUrl(get_home_url());
        $siteFileName = str_replace([':', '\\', '/', '.'], '_', $parseUrl['host'] . $parseUrl['path']);

        return 'recover_' . sanitize_file_name($siteFileName) . '_' . date("Ymd_His", strtotime($this->getCreated())) . '.html';
    }


    /**
     * Init recovery Backup by id
     *
     * @param int $packageId Backup id
     *
     * @return boolean|self
     */
    protected static function getInitRecoverPackageById($packageId)
    {
        try {
            if (!($package = DupPackage::getById($packageId))) {
                throw new Exception('Invalid packag id');
            }

            if (($archivePath = $package->getLocalPackageFilePath(AbstractPackage::FILE_TYPE_ARCHIVE)) == false) {
                throw new Exception('Archive file not found');
            }

            $result = new self($archivePath, $package);
        } catch (Exception $e) {
            \Duplicator\Utils\Logging\DupLog::trace('ERROR ON RECOVER PACKAGE ID, msg:' . $e->getMessage());
            return false;
        }

        return $result;
    }

    /**
     *
     * @param boolean $reset if true reset Backup
     *
     * @return false|self return false if recover Backup isn't set or recover Backup object
     */
    public static function getRecoverPackage($reset = false)
    {
        if (is_null(self::$instance) || $reset) {
            if (($packageId = get_option(self::OPTION_RECOVER_PACKAGE_ID)) == false) {
                self::$instance = null;
                return false;
            }

            if (!self::isPackageIdRecoverable($packageId, $reset)) {
                self::$instance = null;
                return false;
            }

            self::$instance = self::getInitRecoverPackageById($packageId);
        }

        return self::$instance;
    }

    /**
     * Get recover Backup id
     *
     * @return false|int return false if not set or Backup id
     */
    public static function getRecoverPackageId()
    {
        if (self::isDisallow()) {
            return false;
        }

        $recoverPackage = self::getRecoverPackage();
        if ($recoverPackage instanceof self) {
            return $recoverPackage->getPackageId();
        } else {
            return false;
        }
    }

    /**
     * Reset recovery Backup
     *
     * @param bool $emptyDir if true remove recovery Backup files
     *
     * @return void
     */
    public static function resetRecoverPackage($emptyDir = false): void
    {
        self::$instance = null;

        if ($emptyDir) {
            static::cleanFolder();
        }

        if (($recoverPackageId = get_option(self::OPTION_RECOVER_PACKAGE_ID)) !== false) {
            delete_option(self::OPTION_RECOVER_PACKAGE_ID);
            $package = DupPackage::getById($recoverPackageId);
            if ($package instanceof DupPackage) {
                $package->save();
            }
        }
    }

    /**
     * Set recoveable Backup
     *
     * @param false|int $id           if empty reset Backup
     * @param ?string   $errorMessage error message
     *
     * @return bool false if fail
     */
    public static function setRecoveablePackage($id, &$errorMessage = null): bool
    {
        $id = (int) $id;

        self::resetRecoverPackage(true);

        if (empty($id)) {
            return true;
        }

        try {
            if (!self::isPackageIdRecoverable($id, true)) {
                throw new Exception('Backup isn\'t in recoverable list');
            }

            $recoverPackage = self::getInitRecoverPackageById($id);
            if (!$recoverPackage instanceof self) {
                throw new Exception('Can\'t initialize recovery Backup');
            }

            if (!SnapIO::mkdir($recoverPackage->getInstallerFolderPath(), 0755, true)) {
                throw new Exception('Can\'t create recovery Backup folder or set its permissions to 0755');
            }
            SnapIO::createSilenceIndex($recoverPackage->getInstallerFolderPath());

            // Checks if php is executable in the recover folder
            $path     = $recoverPackage->getInstallerFolderPath();
            $url      = $recoverPackage->getInstallerFolderUrl();
            $phpCheck = new PHPExecCheck($path, $url);
            if ($phpCheck->check() != PHPExecCheck::PHP_OK) {
                throw new Exception($phpCheck->getLastError());
            }

            $recoverPackage->prepareToInstall();

            if (!update_option(self::OPTION_RECOVER_PACKAGE_ID, $id)) {
                delete_option(self::OPTION_RECOVER_PACKAGE_ID);
                throw new Exception('Can\'t update ' . self::OPTION_RECOVER_PACKAGE_ID . ' option');
            }

            $package = DupPackage::getById($id);
            $package->save();
        } catch (Exception | Error $e) {
            delete_option(self::OPTION_RECOVER_PACKAGE_ID);
            $errorMessage = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     *
     * @param bool $removeArchive not used, always removes the archives in the recovery folder
     *
     * @return bool
     */
    public static function cleanFolder($removeArchive = false): bool
    {
        $customFolder = self::getRecoveryCustomPath();
        $path         = strlen($customFolder) > 0 ? $customFolder : DUPLICATOR_RECOVER_PATH;

        if (!file_exists($path) && !wp_mkdir_p($path)) {
            throw new Exception('Can\'t create ' . $path);
        }
        SnapIO::emptyDir($path, ['index.php']);

        return true;
    }

    /**
     * Get error message if installer path couldn't be determined
     *
     * @return string
     */
    protected static function getNotExecPhpErrorMessage(): string
    {
        $customFolder = self::getRecoveryCustomPath();
        $path         = strlen($customFolder) > 0 ? $customFolder : DUPLICATOR_RECOVER_PATH;

        return sprintf(
            __(
                'Duplicator can\'t set Recovery Point because on this Server it isn\'t possible to determine the installer path %s',
                'duplicator-pro'
            ),
            $path
        );
    }

    /**
     * Determine possible path for installer.
     * If is none the installer can't be executed
     *
     * @return string can be duplicator, home, none
     */
    protected function getPathMode(): string
    {
        if (strlen(self::getRecoveryCustomPath()) > 0) {
            return self::PATH_MODE_CUSTOM;
        }
        return (self::isPathBackupAvailable() ? self::PATH_MODE_BACKUP : self::PATH_MODE_NONE);
    }

    /**
     * Return recoverable Backups list
     *
     * @param bool $reset if true reset Backups list
     *
     * @return array<int, array{id: int, created: string, nameHash: string, name: string}>
     */
    public static function getRecoverablesPackages($reset = false)
    {
        if (is_null(self::$recoveablesPackages) || $reset) {
            self::$recoveablesPackages = [];
            DupPackage::dbSelectByStatusCallback(
                [
                    self::class,
                    'recoverablePackageCheck',
                ],
                [
                    [
                        'op'     => '>=',
                        'status' => AbstractPackage::STATUS_COMPLETE,
                    ],
                ],
                self::MAX_PACKAGES_LIST,
                0,
                '`created` DESC'
            );
        }
        self::addRecoverPackageToListIfNotExists();

        return self::$recoveablesPackages;
    }

    /**
     * Add current recovery Backup in list if not exists
     *
     * @return bool  Returns true if it does not exist
     */
    protected static function addRecoverPackageToListIfNotExists()
    {
        if (($recoverPackageId = get_option(self::OPTION_RECOVER_PACKAGE_ID)) === false) {
            return true;
        }

        if (in_array($recoverPackageId, array_keys(self::$recoveablesPackages))) {
            return true;
        }

        $recoverPackage = DupPackage::getById($recoverPackageId);
        if (!$recoverPackage instanceof DupPackage) {
            return false;
        }

        return self::recoverablePackageCheck($recoverPackage);
    }

    /**
     * return true if packages id is recoverable
     *
     * @param int     $id    package id
     * @param boolean $reset if true reset Backups list
     *
     * @return boolean
     */
    public static function isPackageIdRecoverable($id, $reset = false)
    {
        if (self::isDisallow()) {
            return false;
        }

        return in_array($id, self::getRecoverablesPackagesIds($reset));
    }

    /**
     * Get recoverable Backup ids
     *
     * @param bool $reset if true reset list
     *
     * @return int[]
     */
    public static function getRecoverablesPackagesIds($reset = false): array
    {
        return array_keys(self::getRecoverablesPackages($reset));
    }

    /**
     * Check if Backup is recoverable
     *
     * @param DupPackage $package Backup to check
     *
     * @return bool true if is added
     */
    public static function recoverablePackageCheck(DupPackage $package): bool
    {
        $status = new RecoveryStatus($package);
        if (!$status->isRecoverable()) {
            return false;
        }

        self::$recoveablesPackages[$package->getId()] = [
            'id'       => $package->getId(),
            'created'  => $package->getCreated(),
            'nameHash' => $package->getNameHash(),
            'name'     => $package->getName(),
        ];
        return true;
    }

    /**
     * Remove recovery folders
     *
     * @return void
     */
    public static function removeRecoveryFolder(): void
    {
        if (defined('DUPLICATOR_RECOVER_PATH') && file_exists(DUPLICATOR_RECOVER_PATH)) {
            SnapIO::rrmdir(DUPLICATOR_RECOVER_PATH);
        }

        $customFolder = self::getRecoveryCustomPath();
        if (strlen($customFolder) > 0 && file_exists($customFolder)) {
            SnapIO::rrmdir($customFolder);
        }
    }

    /**
     * Return recovery custom path from addon option
     *
     * @return string
     */
    public static function getRecoveryCustomPath(): string
    {
        return DynamicGlobalEntity::getInstance()->getValString('recovery_custom_path');
    }

    /**
     * Return recovery custom URL
     *
     * @return string return empty URL if custom path isn't set
     */
    public static function getRecoveryCustomURL(): string
    {
        $customPath = self::getRecoveryCustomPath();
        if (strlen($customPath) == 0) {
            return '';
        }

        if (SnapIO::isChildPath($customPath, WpArchiveUtils::getArchiveListPaths('wpcontent'), false, true, true)) {
            $mainPath = WpArchiveUtils::getArchiveListPaths('wpcontent');
            $mainURL  = WpArchiveUtils::getOriginalUrls('wpcontent');
        } else {
            $mainPath = WpArchiveUtils::getArchiveListPaths('home');
            $mainURL  = WpArchiveUtils::getOriginalUrls('home');
        }

        return $mainURL . '/' . SnapIO::getRelativePath($customPath, $mainPath, true);
    }

    /**
     * Set recovery custom path
     *
     * @param string $path        path
     * @param string $failMessage return fail message
     *
     * @return bool
     */
    public static function setRecoveryCustomPath($path, &$failMessage = ''): bool
    {
        $remove = false;

        try {
            if (strlen($path) == 0) {
                $dGlobal = DynamicGlobalEntity::getInstance();
                $dGlobal->setValString('recovery_custom_path', '', true);
                return true;
            }

            if (file_exists($path)) {
                if (
                    !is_dir($path) ||
                    !is_writable($path)
                ) {
                    throw new Exception(__('The Recovery custom path must be a folder with write permissions.', 'duplicator-pro'));
                }
            } else {
                if (wp_mkdir_p($path) == false) {
                    throw new Exception(sprintf(__('It is not possible to create the folder %s', 'duplicator-pro'), $path));
                }
            }

            if (
                !SnapIO::isChildPath($path, WpArchiveUtils::getArchiveListPaths('home'), false, false, true) &&
                !SnapIO::isChildPath($path, WpArchiveUtils::getArchiveListPaths('wpcontent'), false, false, true)
            ) {
                throw new Exception(__('The custom Recovery path must be a child folder of the home path or wp-content', 'duplicator-pro'));
            }

            if (\Duplicator\Libs\WpUtils\PathUtil::isPathInCoreDirs($path)) {
                throw new Exception(__('The Recovery custom path cannot be a WordPress core folder.', 'duplicator-pro'));
            }
        } catch (Exception $e) {
            $remove      = true;
            $failMessage = $e->getMessage();
            return false;
        } finally {
            if ($remove) {
                rmdir($path);
            }
        }

        $dGlobal = DynamicGlobalEntity::getInstance();
        $dGlobal->setValString('recovery_custom_path', $path, true);
        return true;
    }

    /**
     * Action callback: reset recovery point after migration
     *
     * @param \Duplicator\Installer\Models\MigrateData $migrationData Migration data
     *
     * @return void
     */
    public static function resetOnMigration($migrationData): void
    {
        self::resetRecoverPackage();
    }

    /**
     * Filter callback: check if path is a recovery-protected path
     *
     * @param bool   $isProtected Current protected status
     * @param string $path        Path to check
     *
     * @return bool
     */
    public static function filterIsProtectedPath(bool $isProtected, string $path): bool
    {
        if ($isProtected) {
            return true;
        }

        return self::isRecoverPath($path);
    }

    /**
     * Filter callback: add recovery paths for index.php protection
     *
     * @param string[] $paths Current paths
     *
     * @return string[]
     */
    public static function filterIndexProtectedPaths(array $paths): array
    {
        if (defined('DUPLICATOR_RECOVER_PATH')) {
            $paths[] = SnapIO::safePath(DUPLICATOR_RECOVER_PATH);
        }

        $customPath = self::getRecoveryCustomPath();
        if (strlen($customPath) > 0) {
            $paths[] = SnapIO::safePath($customPath);
        }

        return $paths;
    }

    /**
     * Action callback: refresh recovery package after restore by finding
     * the package with the DISASTER_SET flag
     *
     * @param \Duplicator\Installer\Models\MigrateData $migrationData Migration data
     *
     * @return void
     */
    public static function refreshAfterRestore($migrationData): void
    {
        $ids = DupPackage::dbSelect(
            'FIND_IN_SET(\'' . \Duplicator\Addons\RecoveryAddon\Controllers\RecoveryPackagesController::FLAG_DISASTER_SET . '\', `flags`)',
            0,
            0,
            '',
            'ids'
        );
        if (count($ids)) {
            self::setRecoveablePackage($ids[0]);
        }
    }
}
