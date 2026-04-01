<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductDeactivationSubscriber implements EventSubscriberInterface
{
    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

    public function __construct(
        private readonly EntityRepository $redirectRepository,
        private readonly EntityRepository $seoUrlRepository,
        private readonly EntityRepository $salesChannelDomainRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly InAppPurchase $inAppPurchase,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'requestChangeSet',
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
        ];
    }

    public function requestChangeSet(PreWriteValidationEvent $event): void
    {
        foreach ($event->getCommands() as $command) {
            if ($command->getEntityName() !== ProductDefinition::ENTITY_NAME) {
                continue;
            }

            if (!$command instanceof UpdateCommand || !$command instanceof ChangeSetAware) {
                continue;
            }

            if ($command->hasField('active')) {
                $command->requestChangeSet();
            }
        }
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        if (!$this->isFeatureEnabled()) {
            return;
        }

        $deactivatedProductIds = [];
        $activatedProductIds = [];

        foreach ($event->getWriteResults() as $result) {
            $changeSet = $result->getChangeSet();

            if ($changeSet === null || !$changeSet->hasChanged('active')) {
                continue;
            }

            $productId = $result->getPrimaryKey();
            if (!\is_string($productId)) {
                continue;
            }

            $before = $changeSet->getBefore('active');
            $after = $changeSet->getAfter('active');

            if ($before && !$after) {
                $deactivatedProductIds[] = $productId;
            } elseif (!$before && $after) {
                $activatedProductIds[] = $productId;
            }
        }

        $context = $event->getContext();

        if (!empty($deactivatedProductIds)) {
            $this->createRedirectsForDeactivatedProducts($deactivatedProductIds, $context);
        }

        if (!empty($activatedProductIds)) {
            $this->deleteRedirectsForActivatedProducts($activatedProductIds, $context);
        }
    }

    private function isFeatureEnabled(): bool
    {
        if (!$this->inAppPurchase->isActive('ScopPlatformRedirecter', self::IN_APP_PURCHASE_ID)) {
            return false;
        }

        return (bool) $this->systemConfigService->get('ScopPlatformRedirecter.config.autoRedirectEnabled');
    }

    private function createRedirectsForDeactivatedProducts(array $productIds, Context $context): void
    {
        $domainPrefixes = $this->getDomainPrefixes($context);

        foreach ($productIds as $productId) {
            $seoUrls = $this->getProductSeoUrls($productId, $context);

            if (empty($seoUrls)) {
                continue;
            }

            $redirectData = [];
            foreach ($seoUrls as $seoUrl) {
                $seoPath = ltrim($seoUrl['seoPathInfo'], '/');
                $salesChannelId = $seoUrl['salesChannelId'] ?? null;
                $languageId = $seoUrl['languageId'] ?? null;

                $prefix = $this->resolvePrefix($domainPrefixes, $salesChannelId, $languageId);
                $sourceUrl = rtrim($prefix, '/') . '/' . $seoPath;

                $redirectData[] = [
                    'id' => Uuid::randomHex(),
                    'sourceURL' => $sourceUrl,
                    'targetURL' => '/',
                    'httpCode' => 302,
                    'enabled' => true,
                    'productId' => $productId,
                    'salesChannelId' => $salesChannelId,
                ];
            }

            if (!empty($redirectData)) {
                $this->redirectRepository->upsert($redirectData, $context);
            }
        }
    }

    private function deleteRedirectsForActivatedProducts(array $productIds, Context $context): void
    {
        foreach ($productIds as $productId) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('productId', $productId));

            $ids = $this->redirectRepository->searchIds($criteria, $context);

            if ($ids->getTotal() === 0) {
                continue;
            }

            $deleteData = array_map(
                fn (string $id) => ['id' => $id],
                $ids->getIds()
            );

            $this->redirectRepository->delete($deleteData, $context);
        }
    }

    private function getProductSeoUrls(string $productId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('foreignKey', $productId));
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

    private function getDomainPrefixes(Context $context): array
    {
        $criteria = new Criteria();
        $domains = $this->salesChannelDomainRepository->search($criteria, $context);

        $prefixes = [];
        foreach ($domains as $domain) {
            $url = $domain->getUrl();
            $path = parse_url($url, PHP_URL_PATH) ?? '';
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
