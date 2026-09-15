<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\MessageQueue\ScheduledTask;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Store\InAppPurchase;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: ExpireRedirectsTask::class)]
final class ExpireRedirectsTaskHandler extends ScheduledTaskHandler
{
    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly InAppPurchase $inAppPurchase,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        if (!$this->inAppPurchase->isActive('ScopPlatformRedirecter', self::IN_APP_PURCHASE_ID)) {
            return;
        }

        // Datetimes are stored in UTC; compare against UTC_TIMESTAMP() rather than NOW().
        $this->connection->executeStatement(
            'UPDATE `scop_platform_redirecter_redirect`
                SET `enabled` = 0
                WHERE `active_until` IS NOT NULL
                  AND `active_until` < UTC_TIMESTAMP()
                  AND `enabled` = 1'
        );
    }
}
