<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\MessageQueue\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CleanupNotFoundLogTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'scop_platform_redirecter.cleanup_404_log';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
