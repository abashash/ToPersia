<?php

declare(strict_types=1);

namespace Duplicator\Package;

use Duplicator\Utils\ManagedHost\ManagedHostMng;
use Duplicator\Utils\Logging\DupLog;
use Duplicator\Core\Constants;
use Duplicator\Libs\Shell\Shell;
use Duplicator\Libs\Shell\ShellZipUtils;
use Duplicator\Libs\Snap\SnapOpenBasedir;
use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Libs\Snap\SnapNet;
use Duplicator\Libs\Snap\SnapServer;
use Duplicator\Libs\Snap\SnapString;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Libs\Snap\SnapWP;
use Duplicator\Package\AbstractPackage;
use Duplicator\Libs\WpUtils\WpArchiveUtils;
use Duplicator\Libs\WpUtils\WpDbUtils;
use Duplicator\Core\Exceptions\DupliException;

/**
 * Class used to get server info
 */
class BuildRequirements
{
    /**
     * Gets the system checks which are not required
     *
     * @param AbstractPackage $package The Backup to check
     *
     * @return array<string,mixed> An array of system checks
     */
    public static function getChecks(AbstractPackage $package): array
    {
        $checks = [];

        //-----------------------------
        //PHP SETTINGS
        $testWebSrv = false;
        if (defined('WP_CLI') && WP_CLI) {
            $testWebSrv = true;
        } else {
            $serverSoftware = SnapUtil::sanitizeTextInput(INPUT_SERVER, 'SERVER_SOFTWARE', '');
            if (strlen($serverSoftware) > 0) {
                foreach (Constants::SERVER_LIST as $value) {
                    if (stristr($serverSoftware, (string) $value)) {
                        $testWebSrv = true;
                        break;
                    }
                }
            }
        }
        self::logCheckFalse($testWebSrv, 'Any out of server software (' . implode(', ', Constants::SERVER_LIST) . ') doesn\'t exist.');

        // True if open_basedir is disabled
        $testOpenBaseDir = !SnapOpenBasedir::isEnabled();
        self::logCheckFalse($testOpenBaseDir, 'open_basedir is enabled.');

        $pathsOutOpenbaseDir = array_filter($package->Archive->FilterInfo->Dirs->Unknown, fn(string $path): bool => !SnapOpenBasedir::isPathValid($path));
        self::logCheckFalse(empty($pathsOutOpenbaseDir), 'Some paths are out of open_basedir restriction: ' . implode(', ', $pathsOutOpenbaseDir));

        // If open_basedir is enabled, ensure no paths are outside its restrictions; if disabled, the check passes automatically.
        $openBasedirCheck = $testOpenBaseDir || empty($pathsOutOpenbaseDir);

        $max_execution_time = ini_get("max_execution_time");
        $testMaxExecTime    = ($max_execution_time > DUPLICATOR_SCAN_TIMEOUT) || (strcmp($max_execution_time, 'Off') == 0 || $max_execution_time == 0);

        if (strcmp($max_execution_time, 'Off') == 0) {
            $max_execution_time_error_message = 'max_execution_time should not be' . $max_execution_time;
        } else {
            $max_execution_time_error_message = 'max_execution_time (' . $max_execution_time . ') should not be lower than the DUPLICATOR_SCAN_TIMEOUT ' .
                DUPLICATOR_SCAN_TIMEOUT;
        }
        self::logCheckFalse($testMaxExecTime, $max_execution_time_error_message);

        $testMySqlConnect = function_exists('mysqli_connect');
        self::logCheckFalse($testMySqlConnect, 'mysqli_connect function doesn\'t exist.');

        $testURLFopen = SnapServer::isURLFopenEnabled();
        self::logCheckFalse($testURLFopen, 'URL Fopen isn\'t enabled.');

        $testCURL = SnapUtil::isCurlEnabled();
        self::logCheckFalse($testCURL, 'curl_init function doesn\'t exist.');

        $testMemory = SnapServer::memoryLimitCheck(DUPLICATOR_MIN_MEMORY_LIMIT);
        self::logCheckFalse($testCURL, 'memory_limit is less than DUPLICATOR_MIN_MEMORY_LIMIT: ' . DUPLICATOR_MIN_MEMORY_LIMIT);

        $checks['SRV']['HOST'] = ManagedHostMng::getInstance()->getActiveHostings();

        $checks['SRV']['PHP']['websrv']        = $testWebSrv;
        $checks['SRV']['PHP']['openbase']      = $openBasedirCheck;
        $checks['SRV']['PHP']['maxtime']       = $testMaxExecTime;
        $checks['SRV']['PHP']['mysqli']        = $testMySqlConnect;
        $checks['SRV']['PHP']['allowurlfopen'] = $testURLFopen;
        $checks['SRV']['PHP']['curlavailable'] = $testCURL;
        $checks['SRV']['PHP']['minMemory']     = $testMemory;
        $checks['SRV']['PHP']['version']       = true; // now the plugin is activated only if the minimum version is valid, so this check is always true
        $allCheck                              = true;
        foreach ($checks['SRV']['PHP'] as $key => $check) {
            if ($check === false) {
                $allCheck = false;
                break;
            }
        }
        $checks['SRV']['PHP']['ALL'] = $allCheck;

        //-----------------------------
        //WORDPRESS SETTINGS

        //Core dir and files logic
        $testHasWpCoreFiltered = !$package->Archive->hasWpCoreFolderFiltered();

        $testIsMultisite = is_multisite();

        $checks['SRV']['WP']['version'] = true; // This check is always true because the plugin is activated only if the minimum version is valid
        $checks['SRV']['WP']['core']    = $testHasWpCoreFiltered;
        // $checks['SRV']['WP']['cache'] = $testCache;
        $checks['SRV']['WP']['ismu']     = $testIsMultisite;
        $checks['SRV']['WP']['ismuplus'] = !$testIsMultisite;

        if ($testIsMultisite) {
            $checks['SRV']['WP']['ALL'] = ($testHasWpCoreFiltered && $checks['SRV']['WP']['ismuplus']);
            self::logCheckFalse($checks['SRV']['WP']['ismuplus'], 'WP is multi-site setup and multisite support is not available.');
        } else {
            $checks['SRV']['WP']['ALL'] = ($testHasWpCoreFiltered);
        }

        // Connectivity test result from package (set during requirements phase)
        $checks['SRV']['SELF_REQUEST'] = !$package->isClientSideKickoff();

        return apply_filters('duplicator_build_requirement_checks', $checks);
    }

    /**
     * Logs checks false informative message
     *
     * @param boolean $check        Either it is true or false
     * @param string  $errorMessage Error message which should be logged when check is false
     *
     * @return void
     */
    private static function logCheckFalse(bool $check, string $errorMessage): void
    {
        if (empty($errorMessage)) {
            throw new DupliException('Exception: Empty $errorMessage [File: ' . __FILE__ . ', Ln: ' . __LINE__);
        }

        if (false === $check) {
            DupLog::trace($errorMessage);
        }
    }

    /**
     * Returns the server settings data
     *
     * @return array<mixed>
     */
    public static function getServerSettingsData(): array
    {
        $serverSettings = [];

        //GENERAL SETTINGS
        $serverSettings[] = [
            'title'    => __('General', 'duplicator-pro'),
            'settings' => self::getGeneralServerSettings(),
        ];

        //WORDPRESS SETTINGS
        $serverSettings[] = [
            'title'    => __('WordPress', 'duplicator-pro'),
            'settings' => self::getWordPressServerSettings(),
        ];

        //PHP SETTINGS
        $serverSettings[] = [
            'title'    => __('PHP', 'duplicator-pro'),
            'settings' => self::getPHPServerSettings(),
        ];

        //MYSQL SETTINGS
        $serverSettings[] = [
            'title'    => __('MySQL', 'duplicator-pro'),
            'settings' => self::getMysqlServerSettings(),
        ];

        // Paths Info
        $serverSettings[] = [
            'title'    => __('Paths Info', 'duplicator-pro'),
            'settings' => self::getPathsSettings(),
        ];

        //URLs info
        $urlsSettings = [];
        foreach (WpArchiveUtils::getOriginalURLs() as $key => $url) {
            $urlsSettings[] = [
                'label'    => __('URL ', 'duplicator-pro') . $key,
                'logLabel' => 'URL ' . $key,
                'value'    => $url,
            ];
        }

        $serverSettings[] = [
            'title'    => __('URLs Info', 'duplicator-pro'),
            'settings' => $urlsSettings,
        ];

        //Disk Space
        $home_path          = SnapWP::getHomePath(true);
        $space              = SnapIO::diskTotalSpace($home_path);
        $space_free         = SnapIO::diskFreeSpace($home_path);
        $serverDiskSettings = [
            [
                'label'           => __('Free Space', 'duplicator-pro'),
                'logLabel'        => 'Free Space',
                'value'           => sprintf(
                    __('%1$s%% -- %2$s from %3$s', 'duplicator-pro'),
                    round($space_free / $space * 100, 2),
                    SnapString::byteSize($space_free),
                    SnapString::byteSize($space)
                ),
                'valueNoteBottom' => __(
                    'Note: This value is the physical server\'s hard-drive allocation.
                    On shared hosts check your control panel for the "TRUE" disk space quota value.',
                    'duplicator-pro'
                ),
            ],
        ];

        $serverSettings[] = [
            'title'    => __('Server Disk', 'duplicator-pro'),
            'settings' => $serverDiskSettings,
        ];

        return $serverSettings;
    }

    /**
     * Returns the geleral server settings
     *
     * @return array<mixed>
     */
    private static function getGeneralServerSettings(): array
    {
        $serverSoftware = SnapUtil::sanitizeTextInput(
            INPUT_SERVER,
            'SERVER_SOFTWARE',
            __('Unknown', 'duplicator-pro')
        );

        return [
            [
                'label'     => __('Duplicator Version', 'duplicator-pro'),
                'logLabel'  => 'Duplicator Version',
                'value'     => DUPLICATOR_VERSION,
                'valueNote' => sprintf(
                    _x(
                        '- %1$sCheck WordPress Updates%2$s',
                        '%1$s and %2$s are the opening and closing anchor tags',
                        'duplicator-pro'
                    ),
                    '<a href="' . esc_url(admin_url('update-core.php')) . '">',
                    '</a>'
                ),
            ],
            [
                'label'    => __('Operating System', 'duplicator-pro'),
                'logLabel' => 'Operating System',
                'value'    => PHP_OS,
            ],
            [
                'label'     => __('Timezone', 'duplicator-pro'),
                'logLabel'  => 'Timezone',
                'value'     => function_exists('wp_timezone_string') ? wp_timezone_string() :  __('Unknown', 'duplicator-pro'),
                'valueNote' => sprintf(
                    _x(
                        'This is a %1$sWordPress Setting%2$s',
                        '%1$s and %2$s are the opening and closing anchor tags',
                        'duplicator-pro'
                    ),
                    '<a href="options-general.php">',
                    '</a>'
                ),
            ],

            [
                'label'    => __('Server Time', 'duplicator-pro'),
                'logLabel' => 'Server Time',
                'value'    => current_time('Y-m-d H:i:s'),
            ],
            [
                'label'    => __('Web Server', 'duplicator-pro'),
                'logLabel' => 'Web Server',
                'value'    => $serverSoftware,
            ],
            [
                'label'    => __('Loaded PHP INI', 'duplicator-pro'),
                'logLabel' => 'Loaded PHP INI',
                'value'    => php_ini_loaded_file(),
            ],
            [
                'label'    => __('Server IP', 'duplicator-pro'),
                'logLabel' => 'Server IP',
                'value'    => (SnapNet::getServerIP() !== '') ? SnapNet::getServerIP() : __("Can't detect", 'duplicator-pro'),
            ],
            [
                'label'    => __('Outbound IP', 'duplicator-pro'),
                'logLabel' => 'Outbound IP',
                'value'    => (SnapNet::getOutboundIP() !== '') ? SnapNet::getOutboundIP() : __("Can't detect", 'duplicator-pro'),
            ],
            [
                'label'    => __('Client IP', 'duplicator-pro'),
                'logLabel' => 'Client IP',
                'value'    => (SnapNet::getClientIP() !== '') ? SnapNet::getClientIP() : __("Can't detect", 'duplicator-pro'),
            ],
            [
                'label'    => __('Host', 'duplicator-pro'),
                'logLabel' => 'Host',
                'value'    => parse_url(get_site_url(), PHP_URL_HOST),
            ],
            [
                'label'    => __('Duplicator Version', 'duplicator-pro'),
                'logLabel' => 'Duplicator Version',
                'value'    => DUPLICATOR_VERSION,
            ],
        ];
    }

    /**
     * Returns the WP server settings
     *
     * @return array<mixed>
     */
    private static function getWordPressServerSettings(): array
    {
        global $wp_version;
        $managedHosting = (ManagedHostMng::getInstance()->isManaged() === false) ?
            __('No managed hosting detected', 'duplicator-pro') :
            implode(', ', ManagedHostMng::getInstance()->getActiveHostings());

        return [
            [
                'label'    => __('WordPress Version', 'duplicator-pro'),
                'logLabel' => 'WordPress Version',
                'value'    => $wp_version,
            ],
            [
                'label'    => __('Language', 'duplicator-pro'),
                'logLabel' => 'Language',
                'value'    => get_bloginfo('language'),
            ],
            [
                'label'    => __('Charset', 'duplicator-pro'),
                'logLabel' => 'Charset',
                'value'    => get_bloginfo('charset'),
            ],
            [
                'label'    => __('Memory Limit', 'duplicator-pro'),
                'logLabel' => 'Memory Limit',
                'value'    => WP_MEMORY_LIMIT,
            ],
            [
                'label'    => __('Managed hosting', 'duplicator-pro'),
                'logLabel' => 'Managed hosting',
                'value'    => $managedHosting,
            ],
        ];
    }

    /**
     * Returns the PHP server settings
     *
     * @return array<mixed>
     */
    private static function getPHPServerSettings(): array
    {
        return [
            [
                'label'    => __('PHP Version', 'duplicator-pro'),
                'logLabel' => 'PHP Version',
                'value'    => phpversion(),
            ],
            [
                'label'    => __('PHP SAPI', 'duplicator-pro'),
                'logLabel' => 'PHP SAPI',
                'value'    => PHP_SAPI,
            ],
            [
                'label'    => __('User', 'duplicator-pro'),
                'logLabel' => 'User',
                'value'    => SnapServer::getPHPUser(),
            ],
            [
                'label'     => __('Memory Limit', 'duplicator-pro'),
                'logLabel'  => 'Memory Limit',
                'labelLink' => 'http://www.php.net/manual/en/ini.core.php#ini.memory-limit',
                'value'     => @ini_get('memory_limit'),
            ],
            [
                'label'    => __('Memory In Use', 'duplicator-pro'),
                'logLabel' => 'Memory In Use',
                'value'    => size_format(memory_get_usage(true)),
            ],
            [
                'label'        => __('Max Execution Time', 'duplicator-pro'),
                'logLabel'     => 'Max Execution Time',
                'labelLink'    => 'http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time',
                'value'        => @ini_get('max_execution_time'),
                'valueNote'    => sprintf(
                    _x('(default) - %1$s', '%1$s = "is dynamic" or "value is fixed" based on settings', 'duplicator-pro'),
                    set_time_limit(0) ? __('is dynamic', 'duplicator-pro') : __('value is fixed', 'duplicator-pro')
                ),
                'valueTooltip' =>
                __(
                    'If the value shows dynamic then this means it\'s possible for PHP to run longer than the default.
                    If the value is fixed then PHP will not be allowed to run longer than the default.',
                    'duplicator-pro'
                ),
            ],
            [
                'label'     => __('open_basedir', 'duplicator-pro'),
                'logLabel'  => 'open_basedir',
                'labelLink' => 'http://php.net/manual/en/ini.core.php#ini.open-basedir',
                'value'     => empty(@ini_get('open_basedir')) ? __('Off', 'duplicator-pro') : @ini_get('open_basedir'),
            ],
            [
                'label'     => __('Shell (shell_exec)', 'duplicator-pro'),
                'logLabel'  => 'Shell (shell_exec)',
                'labelLink' => 'http://us3.php.net/shell_exec',
                'value'     => !Shell::hasDisabledFunctions('shell_exec') ? __('Is Supported', 'duplicator-pro') : __('Not Supported', 'duplicator-pro'),
            ],
            [
                'label'     => __('Shell (popen)', 'duplicator-pro'),
                'logLabel'  => 'Shell (popen)',
                'labelLink' => 'http://us3.php.net/popen',
                'value'     => !Shell::hasDisabledFunctions('popen') ? __('Is Supported', 'duplicator-pro') : __('Not Supported', 'duplicator-pro'),
            ],
            [
                'label'     => __('Shell (exec)', 'duplicator-pro'),
                'logLabel'  => 'Shell (exec)',
                'labelLink' => 'https://www.php.net/manual/en/function.exec.php',
                'value'     => !Shell::hasDisabledFunctions('exec') ? __('Is Supported', 'duplicator-pro') : __('Not Supported', 'duplicator-pro'),
            ],
            [
                'label'    => __('Shell Exec Zip', 'duplicator-pro'),
                'logLabel' => 'Shell Exec Zip',
                'value'    => (ShellZipUtils::getShellExecZipPath() != null) ? __('Is Supported', 'duplicator-pro') : __('Not Supported', 'duplicator-pro'),
            ],
            [
                'label'     => __('Suhosin Extension', 'duplicator-pro'),
                'logLabel'  => 'Suhosin Extension',
                'labelLink' => 'https://suhosin5.suhosin.org/stories/index.html',
                'value'     => Shell::isSuhosinEnabled() ? __('Enabled', 'duplicator-pro') : __('Disabled', 'duplicator-pro'),
            ],
            [
                'label'    => __('Architecture', 'duplicator-pro'),
                'logLabel' => 'Architecture',
                'value'    => SnapUtil::getArchitectureString(),
            ],
            [
                'label'    => __('Error Log File', 'duplicator-pro'),
                'logLabel' => 'Error Log File',
                'value'    => @ini_get('error_log'),
            ],
        ];
    }

    /**
     * Returns the MySQL server settings
     *
     * @return array<mixed>
     */
    private static function getMysqlServerSettings(): array
    {
        return [
            [
                'label'    => __('Version', 'duplicator-pro'),
                'logLabel' => 'Version',
                'value'    => WpDbUtils::getVersion(),
            ],
            [
                'label'    => __('Charset', 'duplicator-pro'),
                'logLabel' => 'Charset',
                'value'    => DB_CHARSET,
            ],
            [
                'label'     => __('Wait Timeout', 'duplicator-pro'),
                'logLabel'  => 'Wait Timeout',
                'labelLink' => 'http://dev.mysql.com/doc/refman/5.0/en/server-system-variables.html#sysvar_wait_timeout',
                'value'     => WpDbUtils::getVariable('wait_timeout'),
            ],
            [
                'label'     => __('Max Allowed Packets', 'duplicator-pro'),
                'logLabel'  => 'Max Allowed Packets',
                'labelLink' => 'http://dev.mysql.com/doc/refman/5.0/en/server-system-variables.html#sysvar_max_allowed_packet',
                'value'     => WpDbUtils::getVariable('max_allowed_packet'),
            ],
            [
                'label'     => __('mysqldump Path', 'duplicator-pro'),
                'logLabel'  => 'mysqldump Path',
                'labelLink' => 'http://dev.mysql.com/doc/refman/5.0/en/mysqldump.html',
                'value'     => WpDbUtils::getMySqlDumpPath() !== false ? WpDbUtils::getMySqlDumpPath() : __('Path Not Found', 'duplicator-pro'),
            ],
        ];
    }

    /**
     * Returns the paths settings
     *
     * @return array<mixed>
     */
    private static function getPathsSettings(): array
    {
        $pathsSettings = [
            [
                'label'    => __('Target root path', 'duplicator-pro'),
                'logLabel' => 'Target root path',
                'value'    => WpArchiveUtils::getTargetRootPath(),
            ],
        ];

        foreach (WpArchiveUtils::getOriginalPaths() as $key => $origPath) {
            $pathsSettings[] = [
                'label'    => __('Original ', 'duplicator-pro') . $key,
                'logLabel' => 'Original ' . $key,
                'value'    => $origPath,
            ];
        }

        foreach (WpArchiveUtils::getArchiveListPaths() as $key => $archivePath) {
            $pathsSettings[] = [
                'label'    => __('Archive ', 'duplicator-pro') . $key,
                'logLabel' => 'Archive ' . $key,
                'value'    => $archivePath,
            ];
        }

        return $pathsSettings;
    }
}
