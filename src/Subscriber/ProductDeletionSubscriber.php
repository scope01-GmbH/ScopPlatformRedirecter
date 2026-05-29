<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeleteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductDeletionSubscriber implements EventSubscriberInterface
{
    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';
    private const ALLOWED_HTTP_CODES = [301, 302];
    private const DEFAULT_HTTP_CODE = 301;

    public function __construct(
        private readonly EntityRepository $redirectRepository,
        private readonly EntityRepository $seoUrlRepository,
        private readonly EntityRepository $salesChannelDomainRepository,
        private readonly EntityRepository $productRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly InAppPurchase $inAppPurchase,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityDeleteEvent::class => 'onEntityDelete',
        ];
    }

    public function onEntityDelete(EntityDeleteEvent $event): void
    {
        if (!$this->isFeatureEnabled()) {
            return;
        }

        $productIds = $event->getIds(ProductDefinition::ENTITY_NAME);
        if (empty($productIds)) {
            return;
        }

        $context = $event->getContext();

        // Read SEO URLs BEFORE the delete cascades them away.
        $allProductIds = $this->expandWithVariantIds($productIds, $context);
        $seoUrls = $this->getSeoUrlsForProducts($allProductIds, $context);

        if (empty($seoUrls)) {
            return;
        }

        $domainPrefixes = $this->getDomainPrefixes($context);
        $httpCode = $this->getHttpCode();

        // Register a callback that runs only after the delete succeeded.
        $event->addSuccess(function () use ($seoUrls, $domainPrefixes, $httpCode, $context): void {
            $this->createRedirectsForDeletedProducts($seoUrls, $domainPrefixes, $httpCode, $context);
        });
    }

    private function isFeatureEnabled(): bool
    {
        if (!$this->inAppPurchase->isActive('ScopPlatformRedirecter', self::IN_APP_PURCHASE_ID)) {
            return false;
        }

        return (bool) $this->systemConfigService->get('ScopPlatformRedirecter.config.autoRedirectOnDeleteEnabled');
    }

    private function getHttpCode(): int
    {
        $code = (int) $this->systemConfigService->getInt('ScopPlatformRedirecter.config.autoRedirectOnDeleteHttpCode');
        return \in_array($code, self::ALLOWED_HTTP_CODES, true) ? $code : self::DEFAULT_HTTP_CODE;
    }

    /**
     * @param string[] $productIds
     * @return string[]
     */
    private function expandWithVariantIds(array $productIds, Context $context): array
    {
        if (empty($productIds)) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('parentId', $productIds));

        $variantIds = $this->productRepository->searchIds($criteria, $context)->getIds();

        return \array_values(\array_unique(\array_merge($productIds, $variantIds)));
    }

    /**
     * @param string[] $productIds
     * @return array<int, array{seoPathInfo: string, salesChannelId: ?string, languageId: ?string}>
     */
    private function getSeoUrlsForProducts(array $productIds, Context $context): array
    {
        if (empty($productIds)) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('foreignKey', $productIds));
        $criteria->addFilter(new EqualsFilter('routeName', 'frontend.detail.page'));
        $criteria->addFilter(new EqualsFilter('isCanonical', true));

        $seoUrls = $this->seoUrlRepository->search($criteria, $context);

        $result = [];
        foreach ($seoUrls as $seoUrl) {
            $result[] = [
                'seoPathInfo' => $seoUrl->getSeoPathInfo(),
                'salesChannelId' => $seoUrl->getSalesChannelId(),
                'languageId' => $seoUrl->getLanguageId(),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array{seoPathInfo: string, salesChannelId: ?string, languageId: ?string}> $seoUrls
     * @param array<string, string> $domainPrefixes
     */
    private function createRedirectsForDeletedProducts(
        array $seoUrls,
        array $domainPrefixes,
        int $httpCode,
        Context $context,
    ): void {
        $redirectData = [];
        foreach ($seoUrls as $seoUrl) {
            $seoPath = \ltrim($seoUrl['seoPathInfo'], '/');
            $salesChannelId = $seoUrl['salesChannelId'] ?? null;
            $languageId = $seoUrl['languageId'] ?? null;

            $prefix = $this->resolvePrefix($domainPrefixes, $salesChannelId, $languageId);
            $sourceUrl = \rtrim($prefix, '/') . '/' . $seoPath;

            $redirectData[] = [
                'id' => Uuid::randomHex(),
                'sourceURL' => $sourceUrl,
                'targetURL' => '/',
                'httpCode' => $httpCode,
                'enabled' => true,
                'salesChannelId' => $salesChannelId,
            ];
        }

        if (empty($redirectData)) {
            return;
        }

        try {
            $this->redirectRepository->upsert($redirectData, $context);
        } catch (\Throwable) {
            // Auto-redirect on delete must never block the delete operation.
        }
    }

    /**
     * @return array<string, string>
     */
    private function getDomainPrefixes(Context $context): array
    {
        $criteria = new Criteria();
        $domains = $this->salesChannelDomainRepository->search($criteria, $context);

        $prefixes = [];
        foreach ($domains as $domain) {
            $url = $domain->getUrl();
            $path = \parse_url($url, \PHP_URL_PATH) ?? '';
            $key = $domain->getSalesChannelId() . '-' . $domain->getLanguageId();
            $prefixes[$key] = $path;
        }

        return $prefixes;
    }

    private function resolvePrefix(array $domainPrefixes, ?string $salesChannelId, ?string $languageId): string
    {
        if ($salesChannelId === null || $languageId === null) {
            return '/';
        }

        $key = $salesChannelId . '-' . $languageId;

        return $domainPrefixes[$key] ?? '/';
    }
}
