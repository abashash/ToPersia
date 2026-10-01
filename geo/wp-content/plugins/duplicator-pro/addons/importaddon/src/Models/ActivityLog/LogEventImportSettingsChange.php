<?php

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon\Models\ActivityLog;

use Duplicator\Addons\ImportAddon\Core\CapImport;
use Duplicator\Models\ActivityLog\LogEventSettingsChange;

/**
 * Log event for import settings changes
 */
class LogEventImportSettingsChange extends LogEventSettingsChange
{
    const SUB_TYPE = 'import';

    /**
     * Class constructor
     *
     * @param array<string, mixed> $context Additional context data
     */
    public function __construct(array $context = [])
    {
        parent::__construct(self::SUB_TYPE, $context);
    }

    /**
     * Return entity type identifier
     *
     * @return string
     */
    public static function getType(): string
    {
        return 'import_settings_change';
    }

    /**
     * Return entity type label
     *
     * @return string
     */
    public static function getTypeLabel(): string
    {
        return __('Import Settings Change', 'duplicator-pro');
    }

    /**
     * Return required capability for this log event
     *
     * @return string
     */
    public static function getCapability(): string
    {
        return CapImport::CAP_IMPORT;
    }

    /**
     * Create and save an import settings change log event
     *
     * @param array<string, mixed> $context Additional context data
     *
     * @return self
     */
    public static function createEvent(array $context = []): self
    {
        $logEvent = new self($context);
        $logEvent->save();
        return $logEvent;
    }
}
