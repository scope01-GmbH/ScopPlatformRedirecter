<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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
        private readonly EntityRepository $productRepository,
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

            if (!$command instanceof UpdateCommand) {
                continue;
            }

            if ($command->hasField('active') || $command->hasField('parent_id')) {
                $command->requestChangeSet();
            }
        }
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        $context = $event->getContext();
        if ($context->getVersionId() !== Defaults::LIVE_VERSION || !$this->isFeatureEnabled()) {
            return;
        }

        $changes = [];
        $newProductIds = [];
        $parentIds = [];

        foreach ($event->getWriteResults() as $result) {
            $productId = $result->getPrimaryKey();
            if (!\is_string($productId)) {
                continue;
            }

            if ($result->getOperation() === EntityWriteResult::OPERATION_INSERT) {
                $newProductIds[] = $productId;
                continue;
            }

            $changeSet = $result->getChangeSet();
            if ($changeSet === null || (!$result->hasPayload('active') && !$result->hasPayload('parentId'))) {
                continue;
            }

            $before = $changeSet->getBefore('active');
            $before = $before === null ? null : (bool) $before;
            $after = $result->hasPayload('active') ? $result->getProperty('active') : $before;
            $oldParentBytes = $changeSet->getBefore('parent_id');
            $oldParentId = $oldParentBytes === null ? null : Uuid::fromBytesToHex($oldParentBytes);
            $newParentId = $result->hasPayload('parentId') ? $result->getProperty('parentId') : $oldParentId;

            if ($before === $after && $oldParentId === $newParentId) {
                continue;
            }

            $changes[$productId] = [
                'before' => $before,
                'after' => $after,
                'oldParentId' => $oldParentId,
                'newParentId' => $newParentId,
            ];

            if ($before === null && $oldParentId !== null) {
                $parentIds[$oldParentId] = true;
            }
            if ($after === null && $newParentId !== null) {
                $parentIds[$newParentId] = true;
            }
        }

        if ($changes === []) {
            return;
        }

        $rawContext = clone $context;
        $rawContext->setConsiderInheritance(false);
        $parentActive = $this->getParentActiveStates(array_keys(array_diff_key($parentIds, $changes)), $rawContext);
        $deactivatedProductIds = [];
        $activatedProductIds = [];
        $deactivatedParentIds = [];
        $activatedParentIds = [];

        foreach ($changes as $productId => $change) {
            $oldParentId = $change['oldParentId'] ?? '';
            $newParentId = $change['newParentId'] ?? '';
            $before = $change['before'] ?? $changes[$oldParentId]['before'] ?? $parentActive[$oldParentId] ?? false;
            $after = $change['after'] ?? $changes[$newParentId]['after'] ?? $parentActive[$newParentId] ?? false;

            if ($before && !$after) {
                $deactivatedProductIds[] = $productId;
                if ($change['newParentId'] === null) {
                    $deactivatedParentIds[] = $productId;
                }
            } elseif (!$before && $after) {
                $activatedProductIds[] = $productId;
                if ($change['newParentId'] === null) {
                    $activatedParentIds[] = $productId;
                }
            }
        }

        // Variants explicitly changed in this write already had both their states evaluated.
        $skipVariantIds = array_merge(array_keys($changes), $newProductIds);
        $deactivatedProductIds = array_merge($deactivatedProductIds, $this->getInheritedVariantIds($deactivatedParentIds, $skipVariantIds, $rawContext));
        $activatedProductIds = array_merge($activatedProductIds, $this->getInheritedVariantIds($activatedParentIds, $skipVariantIds, $rawContext));

        if ($deactivatedProductIds !== []) {
            $this->createRedirectsForDeactivatedProducts($deactivatedProductIds, $context);
        }

        if ($activatedProductIds !== []) {
            $this->deleteRedirectsForActivatedProducts($activatedProductIds, $context);
        }
    }

    /**
     * @param list<string> $parentIds
     * @return array<string, bool>
     */
    private function getParentActiveStates(array $parentIds, Context $context): array
    {
        if ($parentIds === []) {
            return [];
        }

        $states = [];
        foreach ($this->productRepository->search(new Criteria($parentIds), $context) as $parent) {
            if ($parent instanceof ProductEntity) {
                $states[$parent->getId()] = (bool) $parent->getActive();
            }
        }

        return $states;
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

        $redirectData = [];
        foreach ($this->getSeoUrlsForProducts($productIds, $context) as $seoUrl) {
            $seoPath = ltrim($seoUrl['seoPathInfo'], '/');
            $salesChannelId = $seoUrl['salesChannelId'];
            $languageId = $seoUrl['languageId'];

            $prefix = $this->resolvePrefix($domainPrefixes, $salesChannelId, $languageId);
            if ($prefix === null) {
                continue;
            }

            $redirectData[] = [
                'id' => Uuid::randomHex(),
                'sourceURL' => rtrim($prefix, '/') . '/' . $seoPath,
                'targetURL' => '/',
                'httpCode' => 302,
                'enabled' => true,
                'productId' => $seoUrl['productId'],
                'salesChannelId' => $salesChannelId,
            ];
        }

        if ($redirectData !== []) {
            $this->redirectRepository->upsert($redirectData, $context);
        }
    }

    private function deleteRedirectsForActivatedProducts(array $productIds, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));

        $ids = $this->redirectRepository->searchIds($criteria, $context)->getIds();
        if ($ids === []) {
            return;
        }

        $deleteData = array_map(static fn (string $id) => ['id' => $id], $ids);
        $this->redirectRepository->delete($deleteData, $context);
    }

    /**
     * @param list<string> $parentIds
     * @param list<string> $skipVariantIds
     * @return list<string>
     */
    private function getInheritedVariantIds(array $parentIds, array $skipVariantIds, Context $context): array
    {
        if ($parentIds === []) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('parentId', $parentIds));
        $criteria->addFilter(new EqualsFilter('active', null));

        $variantIds = $this->productRepository->searchIds($criteria, $context)->getIds();

        return array_values(array_diff($variantIds, $skipVariantIds));
    }

    /**
     * Returns canonical SEO URLs for multiple product IDs.
     */
    private function getSeoUrlsForProducts(array $productIds, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('foreignKey', $productIds));
        $criteria->addFilter(new EqualsFilter('routeName', 'frontend.detail.page'));
        $criteria->addFilter(new EqualsFilter('isCanonical', true));

        $seoUrls = $this->seoUrlRepository->search($criteria, $context);

        $result = [];
        foreach ($seoUrls as $seoUrl) {
            $result[] = [
                'productId' => $seoUrl->getForeignKey(),
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

    private function resolvePrefix(array $domainPrefixes, ?string $salesChannelId, ?string $languageId): ?string
    {
        if ($salesChannelId === null || $languageId === null) {
            return null;
        }

        $key = $salesChannelId . '-' . $languageId;

        return $domainPrefixes[$key] ?? null;
    }
}
