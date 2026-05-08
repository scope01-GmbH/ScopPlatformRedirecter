<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class NotFoundSubscriber implements EventSubscriberInterface
{
    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

    public function __construct(
        private readonly Connection $connection,
        private readonly InAppPurchase $inAppPurchase,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -100],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->inAppPurchase->isActive('ScopPlatformRedirecter', self::IN_APP_PURCHASE_ID)) {
            return;
        }

        $response = $event->getResponse();
        if ($response->getStatusCode() !== Response::HTTP_NOT_FOUND) {
            return;
        }

        $request = $event->getRequest();
        $requestUri = (string) $request->get('sw-original-request-uri', $request->getRequestUri());
        $requestBase = $request->getPathInfo();

        // Exclude admin, api, widgets, store-api, profiler
        $excludedPrefixes = ['/admin', '/api', '/widgets', '/store-api', '/_profiler', '/bundles'];
        foreach ($excludedPrefixes as $prefix) {
            if (\strpos($requestBase, $prefix) === 0) {
                return;
            }
        }

        $salesChannelId = $request->get('sw-sales-channel-id');
        $salesChannelIdBin = $salesChannelId ? Uuid::fromHexToBytes($salesChannelId) : null;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');

        $sql = <<<SQL
            INSERT INTO `scop_platform_redirecter_404` (`id`, `url`, `sales_channel_id`, `hit_count`, `last_hit_at`, `created_at`)
            VALUES (:id, :url, :salesChannelId, 1, :now, :now)
            ON DUPLICATE KEY UPDATE
                `hit_count` = `hit_count` + 1,
                `last_hit_at` = :now,
                `updated_at` = :now
SQL;

        try {
            $this->connection->executeStatement($sql, [
                'id' => Uuid::randomBytes(),
                'url' => \substr($requestUri, 0, 500),
                'salesChannelId' => $salesChannelIdBin,
                'now' => $now,
            ]);
        } catch (\Throwable) {
            // Silently fail - logging should not break the response
        }
    }
}
