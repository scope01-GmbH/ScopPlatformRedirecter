<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\MessageQueue\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ExpireRedirectsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'scop_platform_redirecter.expire_redirects';
    }

    public static function getDefaultInterval(): int
    {
        return self::HOURLY;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
