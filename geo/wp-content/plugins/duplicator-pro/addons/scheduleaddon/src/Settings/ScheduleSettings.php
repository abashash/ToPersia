<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Settings;

use Duplicator\Models\DynamicGlobalEntity;
use Exception;

/**
 * Owns the global settings used by scheduled backup notifications.
 */
final class ScheduleSettings
{
    const SEND_EMAIL_ON_BUILD_MODE_KEY   = 'send_email_on_build_mode';
    const NOTIFICATION_EMAIL_ADDRESS_KEY = 'notification_email_address';

    const EMAIL_BUILD_MODE_NEVER   = 0;
    const EMAIL_BUILD_MODE_FAILURE = 1;
    const EMAIL_BUILD_MODE_ALL     = 2;

    /**
     * Register schedule-owned dynamic defaults.
     *
     * @return void
     */
    public static function registerDefaults(): void
    {
        DynamicGlobalEntity::registerDefaults([
            self::SEND_EMAIL_ON_BUILD_MODE_KEY => self::EMAIL_BUILD_MODE_FAILURE,
        ]);
    }

    /** @return int<0,2> Scheduled-build notification mode */
    public static function getEmailBuildMode(): int
    {
        /** @var int<0,2> $mode */
        $mode = DynamicGlobalEntity::getInstance()->getValInt(self::SEND_EMAIL_ON_BUILD_MODE_KEY);
        return $mode;
    }

    /**
     * @param int  $mode Scheduled-build notification mode
     * @param bool $save Whether to persist immediately
     *
     * @return bool True on success
     */
    public static function setEmailBuildMode(int $mode, bool $save = true): bool
    {
        if (!in_array($mode, [self::EMAIL_BUILD_MODE_NEVER, self::EMAIL_BUILD_MODE_FAILURE, self::EMAIL_BUILD_MODE_ALL], true)) {
            throw new Exception('Invalid scheduled-build notification mode.');
        }

        return DynamicGlobalEntity::getInstance()->setValInt(self::SEND_EMAIL_ON_BUILD_MODE_KEY, $mode, $save);
    }

    /** @return string Scheduled-build notification email */
    public static function getNotificationEmailAddress(): string
    {
        return DynamicGlobalEntity::getInstance()->getValString(self::NOTIFICATION_EMAIL_ADDRESS_KEY);
    }

    /**
     * @param string $email Scheduled-build notification email
     * @param bool   $save  Whether to persist immediately
     *
     * @return bool True on success
     */
    public static function setNotificationEmailAddress(string $email, bool $save = true): bool
    {
        return DynamicGlobalEntity::getInstance()->setValString(self::NOTIFICATION_EMAIL_ADDRESS_KEY, $email, $save);
    }
}
