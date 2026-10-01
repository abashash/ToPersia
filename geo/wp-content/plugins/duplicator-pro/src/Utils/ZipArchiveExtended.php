<?php

namespace Duplicator\Utils;

use Duplicator\Core\Exceptions\DupliException;
use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Libs\Snap\SnapLog;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Utils\Logging\DupLog;
use Throwable;
use ZipArchive;

class ZipArchiveExtended
{
    /** @var string */
    protected $archivePath = '';
    protected \ZipArchive $zipArchive;
    /** @var bool */
    protected $isOpened = false;
    /** @var bool */
    protected $compressed = false;
    /** @var bool */
    protected $encrypt = false;
    /** @var string */
    protected $password = '';

    /**
     * Class constructor
     *
     * @param string $path zip archive path
     *
     * @throws DupliException
     */
    public function __construct($path)
    {
        if (!self::isPhpZipAvailable()) {
            throw new DupliException(
                'ZipArchive PHP module is not installed/enabled.',
                DupliException::CODE_ZIP_NOT_AVAILABLE
            );
        }
        if (file_exists($path) && (!is_file($path) || !is_writeable($path))) {
            throw new DupliException(
                'Zip path exists but isn\'t a writable file.' . "\n" . SnapLog::v2str($path),
                DupliException::CODE_ZIP_PATH_NOT_WRITABLE
            );
        }

        $this->archivePath = $path;
        $this->zipArchive  = new ZipArchive();
        $this->setCompressed(true);
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Check if class ZipArchvie is available
     *
     * @return bool
     */
    public static function isPhpZipAvailable()
    {
        return SnapUtil::classExists(ZipArchive::class);
    }

    /**
     * Add full dir in archive
     *
     * @param string $dirPath     dir path
     * @param string $archivePath local archive path
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function addDir($dirPath, $archivePath)
    {
        if (!is_dir($dirPath) || !is_readable($dirPath)) {
            return false;
        }

        $dirPath     = SnapIO::safePathTrailingslashit($dirPath);
        $archivePath = SnapIO::safePathTrailingslashit($archivePath);
        $thisObj     = $this;

        return SnapIO::regexGlobCallback(
            $dirPath,
            function ($path) use ($dirPath, $archivePath, $thisObj): void {
                $newPath = $archivePath . SnapIO::getRelativePath($path, $dirPath);

                if (is_dir($path)) {
                    $thisObj->addEmptyDir($newPath);
                } else {
                    $thisObj->addFile($path, $newPath);
                }
            },
            ['recursive' => true]
        );
    }

    /**
     * Add empty dir on zip archive
     *
     * @param string $path archive dir to add
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function addEmptyDir($path): bool
    {
        return $this->zipArchive->addEmptyDir($path);
    }

    /**
     * Add file on zip archive
     *
     * @param string $filepath        file path
     * @param string $archivePath     archive path, if empty use file name
     * @param bool   $forceUncompress if true the file will be stored uncompressed
     * @param int    $maxSize         max size of file, if the size is grater than this value the file will be truncated, 0 for no limit
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function addFile($filepath, $archivePath = '', $forceUncompress = false, $maxSize = 0)
    {
        if (!is_file($filepath) || !is_readable($filepath)) {
            return false;
        }
        if (strlen($archivePath) === 0) {
            $archivePath = basename($filepath);
        }
        if ($maxSize > 0 && filesize($filepath) > $maxSize) {
            if (($content = file_get_contents($filepath, false, null, 0, $maxSize)) === false) {
                return false;
            }
            $result = $this->zipArchive->addFromString($archivePath, $content);
        } else {
            $result = $this->zipArchive->addFile($filepath, $archivePath);
        }
        if ($result && $this->encrypt) {
            $this->zipArchive->setEncryptionName($archivePath, ZipArchive::EM_AES_256);
        }
        if ($result && (!$this->compressed || $forceUncompress)) {
            $this->zipArchive->setCompressionName($archivePath, ZipArchive::CM_STORE);
        }
        return $result;
    }

    /**
     * Creates a temporary file with the given content. The file will be deleted after the zip is created
     *
     * @param string $archivePath Filename in archive
     * @param string $content     Content of the file
     * @param int    $maxSize     max size of file, if the size is grater than this value the file will be truncated, 0 for no limit
     *
     * @return bool returns true if the file was created successfully
     */
    public function addFileFromString($archivePath, $content, $maxSize = 0)
    {
        if ($maxSize > 0 && strlen($content) > $maxSize) {
            $content = substr($content, 0, $maxSize);
        }
        $result = $this->zipArchive->addFromString($archivePath, $content);
        if ($result && $this->encrypt) {
            $this->zipArchive->setEncryptionName($archivePath, ZipArchive::EM_AES_256);
        }
        if ($result && !$this->compressed) {
            $this->zipArchive->setCompressionName($archivePath, ZipArchive::CM_STORE);
        }
        return $result;
    }

    /**
     * Open Zip archive, create it if don't exists
     *
     * @return bool True on success, false on failure (the ZipArchive error code is logged)
     */
    public function open(): bool
    {
        if ($this->isOpened) {
            return true;
        }

        if (($result = $this->zipArchive->open($this->archivePath, ZipArchive::CREATE)) !== true) {
            DupLog::infoTrace("ZipArchive open failed with code {$result} on {$this->archivePath}");
            return false;
        }

        $this->isOpened = true;
        if ($this->encrypt) {
            $this->zipArchive->setPassword($this->password);
        } else {
            $this->zipArchive->setPassword('');
        }
        return true;
    }

    /**
     * Close zip archive
     *
     * @return bool True on success or false on failure.
     */
    public function close()
    {
        if (!$this->isOpened) {
            return true;
        }

        $result = false;
        try {
            if (($result = $this->zipArchive->close()) !== true) {
                DupLog::infoTrace("ZipArchive close failed on {$this->archivePath} [{$this->getLastErrorDetails()}]");
            }
        } catch (Throwable $e) {
            DupLog::infoTrace('ZipArchive close error: ' . $e->getMessage());
            $result = false;
        }

        // Closing is one-shot: after a failed close PHP invalidates the internal
        // object, so no further operation may be attempted on it. Never throws:
        // it is also called from the destructor, where an exception during stack
        // unwinding would replace the original in-flight exception.
        $this->isOpened = false;

        return $result;
    }

    /**
     * Describe the last ZipArchive error for logging
     *
     * @return string
     */
    protected function getLastErrorDetails(): string
    {
        try {
            $statusString = $this->zipArchive->getStatusString();
            return 'status ' . $this->zipArchive->status . '/' . $this->zipArchive->statusSys .
                ': ' . ($statusString === false ? 'unknown' : $statusString);
        } catch (Throwable $e) {
            return 'status unavailable: ' . $e->getMessage();
        }
    }

    /**
     * Get num files in zip archive
     *
     * @return int
     */
    public function getNumFiles(): int
    {
        $this->open();
        return $this->zipArchive->numFiles;
    }

    /**
     * Get the value of compressed\
     *
     * @return bool
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * Se compression if is available
     *
     * @param bool $compressed if true compress zip archive
     *
     * @return bool return compressd value
     */
    public function setCompressed($compressed)
    {
        $this->compressed = $compressed;
        return $this->compressed;
    }

    /**
     * Get the value of encrypt
     *
     * @return bool
     */
    public function isEncrypted()
    {
        return $this->encrypt;
    }

    /**
     * Return true if ZipArchive encryption is available
     *
     * @return bool
     */
    public static function isEncryptionAvaliable()
    {
        static $isEncryptAvailable = null;
        if ($isEncryptAvailable === null) {
            if (!self::isPhpZipAvailable()) {
                $isEncryptAvailable = false;
                return false;
            }

            if (version_compare(self::getLibzipVersion(), '1.2.0', '<')) {
                $isEncryptAvailable = false;
                return false;
            }

            $isEncryptAvailable = true;
        }

        return $isEncryptAvailable;
    }

    /**
     * Get libzip version
     *
     * @return string
     */
    public static function getLibzipVersion()
    {
        static $libzipVersion = null;

        if (is_null($libzipVersion)) {
            ob_start();
            SnapUtil::phpinfo(INFO_MODULES);
            $info = (string) ob_get_clean();

            if (preg_match('/<td\s.*?>\s*(libzip.*\sver.+?)\s*<\/td>\s*<td\s.*?>\s*(.+?)\s*<\/td>/i', $info, $matches) !== 1) {
                $libzipVersion = "0";
            } else {
                $libzipVersion = $matches[2];
            }
        }

        return $libzipVersion;
    }

    /**
     * Set encryption
     *
     * @param bool   $encrypt  true if archvie must be encrypted
     * @param string $password password

     * @return bool
     */
    public function setEncrypt($encrypt, $password = '')
    {
        $this->encrypt = (self::isEncryptionAvaliable() && $encrypt);

        $this->password = $this->encrypt ? $password : '';

        if ($this->isOpened) {
            if ($this->encrypt) {
                $this->zipArchive->setPassword($this->password);
            } else {
                $this->zipArchive->setPassword('');
            }
        }

        return $this->encrypt;
    }

    /**
     * Files regex search and return zip file stat
     *
     * @param string $path     Archive path
     * @param string $regex    Regex to search
     * @param string $password Password if archive is encrypted or empty string
     *
     * @return false|array{name:string,index:int,crc:int,size:int,mtime:int,comp_size:int,comp_method:int}
     *
     * @throws DupliException
     */
    public static function searchRegex($path, $regex, $password = '')
    {
        if (!self::isPhpZipAvailable()) {
            throw new DupliException(
                'ZipArchive PHP module is not installed/enabled.',
                DupliException::CODE_ZIP_NOT_AVAILABLE,
                __('ZipArchive PHP module is not installed/enabled. The current Backup cannot be opened.', 'duplicator-pro')
            );
        }

        $zip = new ZipArchive();
        if (($result = $zip->open($path)) !== true) {
            throw new DupliException(
                'Cannot open the ZipArchive file, error code ' . $result . "\n" . SnapLog::v2str($path),
                DupliException::CODE_ZIP_OPEN_FAILED,
                __('Cannot open the Backup archive file.', 'duplicator-pro')
            );
        }

        if (strlen($password)) {
            $zip->setPassword($password);
        }

        $result = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            /** @var array{name:string,index:int,crc:int,size:int,mtime:int,comp_size:int,comp_method:int} */
            $stat = $zip->statIndex($i);
            $name = basename($stat['name']);
            if (preg_match($regex, $name) === 1) {
                $result = $stat;
                break;
            }
        }

        $zip->close();
        return $result;
    }
}
