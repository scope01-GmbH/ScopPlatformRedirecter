<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class NotFoundSubscriber implements EventSubscriberInterface
{
    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';
    private const REFERER_CAP = 20;
    private const ALLOWED_MODES = ['full', 'origin_path', 'origin'];
    private const DEFAULT_MODE = 'origin_path';

    public function __construct(
        private readonly Connection $connection,
        private readonly InAppPurchase $inAppPurchase,
        private readonly SystemConfigService $systemConfigService,
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

        $excludedPrefixes = ['/admin', '/api', '/widgets', '/store-api', '/_profiler', '/bundles'];
        foreach ($excludedPrefixes as $prefix) {
            if (\strpos($requestBase, $prefix) === 0) {
                return;
            }
        }

        if ($this->matchesIgnorePattern($requestBase)) {
            return;
        }

        $salesChannelId = $request->get('sw-sales-channel-id');
        $salesChannelIdBin = $salesChannelId ? Uuid::fromHexToBytes($salesChannelId) : null;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.v');
        $nowIso = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $url = \substr($requestUri, 0, 500);

        $normalizedReferer = $this->normalizeReferer(
            $request->headers->get('referer'),
            $this->getStorageMode()
        );

        try {
            $existingReferers = $this->fetchExistingReferers($url, $salesChannelIdBin);
            $mergedList = $this->mergeReferers($existingReferers, $normalizedReferer, $nowIso);
            $refererJson = empty($mergedList)
                ? null
                : \json_encode($mergedList, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

            $sql = <<<SQL
                INSERT INTO `scop_platform_redirecter_404` (`id`, `url`, `sales_channel_id`, `hit_count`, `last_hit_at`, `referers`, `created_at`)
                VALUES (:id, :url, :salesChannelId, 1, :now, :referers, :now)
                ON DUPLICATE KEY UPDATE
                    `hit_count` = `hit_count` + 1,
                    `last_hit_at` = :now,
                    `referers` = :referers,
                    `updated_at` = :now
SQL;

            $this->connection->executeStatement($sql, [
                'id' => Uuid::randomBytes(),
                'url' => $url,
                'salesChannelId' => $salesChannelIdBin,
                'now' => $now,
                'referers' => $refererJson,
            ]);
        } catch (\Throwable) {
            // Silently fail - logging should not break the response
        }
    }

    private function getStorageMode(): string
    {
        $mode = (string) $this->systemConfigService->getString('ScopPlatformRedirecter.config.refererStorageMode');
        if (!\in_array($mode, self::ALLOWED_MODES, true)) {
            return self::DEFAULT_MODE;
        }

        return $mode;
    }

    private function matchesIgnorePattern(string $path): bool
    {
        $raw = (string) $this->systemConfigService->getString('ScopPlatformRedirecter.config.notFoundIgnorePatterns');
        if ($raw === '') {
            return false;
        }

        $patterns = \preg_split('/\r\n|\r|\n/', $raw) ?: [];
        foreach ($patterns as $pattern) {
            $pattern = \trim($pattern);
            if ($pattern === '' || $pattern[0] === '#') {
                continue;
            }
            if (\fnmatch($pattern, $path, \FNM_CASEFOLD)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeReferer(?string $referer, string $mode): ?string
    {
        if ($referer === null || $referer === '') {
            return null;
        }

        if ($mode === 'full') {
            return \mb_substr($referer, 0, 500);
        }

        $parts = \parse_url($referer);
        if (!\is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'];

        if ($mode === 'origin') {
            return $scheme . '://' . $host;
        }

        $path = $parts['path'] ?? '';

        return \mb_substr($scheme . '://' . $host . $path, 0, 500);
    }

    private function fetchExistingReferers(string $url, ?string $salesChannelIdBin): ?string
    {
        if ($salesChannelIdBin === null) {
            $result = $this->connection->fetchOne(
                'SELECT `referers` FROM `scop_platform_redirecter_404` WHERE `url` = :url AND `sales_channel_id` IS NULL',
                ['url' => $url]
            );
        } else {
            $result = $this->connection->fetchOne(
                'SELECT `referers` FROM `scop_platform_redirecter_404` WHERE `url` = :url AND `sales_channel_id` = :sc',
                ['url' => $url, 'sc' => $salesChannelIdBin]
            );
        }

        return \is_string($result) ? $result : null;
    }

    private function mergeReferers(?string $existingJson, ?string $newReferer, string $nowIso): array
    {
        $list = [];
        if ($existingJson !== null) {
            $decoded = \json_decode($existingJson, true);
            if (\is_array($decoded)) {
                $list = $decoded;
            }
        }

        if ($newReferer !== null) {
            $found = false;
            foreach ($list as &$entry) {
                if (isset($entry['url']) && $entry['url'] === $newReferer) {
                    $entry['hitCount'] = (int) ($entry['hitCount'] ?? 0) + 1;
                    $entry['lastHitAt'] = $nowIso;
                    $found = true;
                    break;
                }
            }
            unset($entry);

            if (!$found) {
                $list[] = ['url' => $newReferer, 'hitCount' => 1, 'lastHitAt' => $nowIso];
            }
        }

        \usort($list, static function (array $a, array $b): int {
            $byHits = ((int) ($b['hitCount'] ?? 0)) <=> ((int) ($a['hitCount'] ?? 0));
            if ($byHits !== 0) {
                return $byHits;
            }

            return \strcmp((string) ($b['lastHitAt'] ?? ''), (string) ($a['lastHitAt'] ?? ''));
        });

        return \array_slice($list, 0, self::REFERER_CAP);
    }
}
