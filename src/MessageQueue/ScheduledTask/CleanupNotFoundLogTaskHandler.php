<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\MessageQueue\ScheduledTask;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: CleanupNotFoundLogTask::class)]
final class CleanupNotFoundLogTaskHandler extends ScheduledTaskHandler
{
    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
        private readonly InAppPurchase $inAppPurchase,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        if (!$this->inAppPurchase->isActive('ScopPlatformRedirecter', self::IN_APP_PURCHASE_ID)) {
            return;
        }

        if (!$this->systemConfigService->getBool('ScopPlatformRedirecter.config.notFoundLogCleanupEnabled')) {
            return;
        }

        $retentionDays = (int) $this->systemConfigService->getInt('ScopPlatformRedirecter.config.notFoundLogRetentionDays');
        if ($retentionDays <= 0) {
            return;
        }

        $threshold = (new \DateTimeImmutable())
            ->modify(\sprintf('-%d days', $retentionDays))
            ->format('Y-m-d H:i:s.v');

        $this->connection->executeStatement(
            'DELETE FROM `scop_platform_redirecter_404`
                WHERE `last_hit_at` < :threshold
                  AND `ignored` = 0
                  AND `redirect_id` IS NULL',
            ['threshold' => $threshold],
        );
    }
}
