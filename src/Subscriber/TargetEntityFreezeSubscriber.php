<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeleteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * When a product or category that is referenced by a redirect (via target_entity_type / target_entity_id)
 * gets deleted, freeze the last known SEO URL into the redirect's targetURL column so the redirect stays
 * functional after the entity is gone. The frozen URL respects the redirect's chosen target language and
 * sales channel and is stored as an absolute URL when the target lives on a specific domain, so a
 * cross-channel redirect keeps pointing to the correct storefront.
 */
class TargetEntityFreezeSubscriber implements EventSubscriberInterface
{
    private const ENTITY_ROUTE_MAP = [
        'product' => 'frontend.detail.page',
        'category' => 'frontend.navigation.page',
    ];

    public function __construct(
        private readonly EntityRepository $redirectRepository,
        private readonly EntityRepository $seoUrlRepository,
        private readonly EntityRepository $salesChannelDomainRepository,
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
        $context = $event->getContext();

        $byEntityType = [
            'product' => $event->getIds(ProductDefinition::ENTITY_NAME),
            'category' => $event->getIds(CategoryDefinition::ENTITY_NAME),
        ];

        $updates = [];
        foreach ($byEntityType as $entityType => $entityIds) {
            if (empty($entityIds)) {
                continue;
            }

            $routeName = self::ENTITY_ROUTE_MAP[$entityType] ?? null;
            if ($routeName === null) {
                continue;
            }

            foreach ($this->findRedirectsLinkedTo($entityType, $entityIds, $context) as $redirect) {
                $frozenUrl = $this->resolveFrozenUrl(
                    $routeName,
                    $redirect['targetEntityId'],
                    $redirect['targetSalesChannelId'],
                    $redirect['targetLanguageId'],
                    $context
                ) ?? $redirect['targetURL'];

                if ($frozenUrl === null || $frozenUrl === '') {
                    $frozenUrl = '/';
                }

                $updates[] = [
                    'id' => $redirect['id'],
                    'targetURL' => $frozenUrl,
                    'targetEntityType' => null,
                    'targetEntityId' => null,
                    'targetLanguageId' => null,
                    'targetSalesChannelId' => null,
                ];
            }
        }

        if (empty($updates)) {
            return;
        }

        $event->addSuccess(function () use ($updates, $context): void {
            try {
                $this->redirectRepository->update($updates, $context);
            } catch (\Throwable) {
                // freeze must never block the underlying delete operation
            }
        });
    }

    /**
     * @param string[] $entityIds
     * @return array<int, array{id: string, targetURL: string, targetEntityId: string, targetLanguageId: string|null, targetSalesChannelId: string|null}>
     */
    private function findRedirectsLinkedTo(string $entityType, array $entityIds, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('targetEntityType', $entityType));
        $criteria->addFilter(new EqualsAnyFilter('targetEntityId', $entityIds));

        $entities = $this->redirectRepository->search($criteria, $context);

        $rows = [];
        foreach ($entities as $redirect) {
            $rows[] = [
                'id' => $redirect->getId(),
                'targetURL' => $redirect->getTargetURL(),
                'targetEntityId' => $redirect->getTargetEntityId(),
                'targetLanguageId' => $redirect->getTargetLanguageId(),
                'targetSalesChannelId' => $redirect->getTargetSalesChannelId(),
            ];
        }

        return $rows;
    }

    private function resolveFrozenUrl(string $routeName, string $entityId, ?string $salesChannelId, ?string $languageId, Context $context): ?string
    {
        $path = $this->findSeoPath($routeName, $entityId, $salesChannelId, $languageId, $context);
        if ($path === null) {
            return null;
        }

        // Store an absolute URL when the target has a specific domain, so the frozen redirect keeps
        // working across sales channels / language subpaths after the entity is gone.
        $baseUrl = $this->resolveTargetDomainBaseUrl($salesChannelId, $languageId, $context);
        if ($baseUrl !== null) {
            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        }

        return '/' . ltrim($path, '/');
    }

    private function findSeoPath(string $routeName, string $entityId, ?string $salesChannelId, ?string $languageId, Context $context): ?string
    {
        $search = function (?string $channelMode, ?string $channelId) use ($routeName, $entityId, $languageId, $context) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('routeName', $routeName));
            $criteria->addFilter(new EqualsFilter('foreignKey', $entityId));
            $criteria->addFilter(new EqualsFilter('isCanonical', true));
            if ($languageId !== null) {
                $criteria->addFilter(new EqualsFilter('languageId', $languageId));
            }
            // 'exact' -> a specific channel, 'null' -> channel-independent rows, null -> any channel
            if ($channelMode === 'exact') {
                $criteria->addFilter(new EqualsFilter('salesChannelId', $channelId));
            } elseif ($channelMode === 'null') {
                $criteria->addFilter(new EqualsFilter('salesChannelId', null));
            }
            $criteria->setLimit(1);

            return $this->seoUrlRepository->search($criteria, $context)->first();
        };

        $seoUrl = $salesChannelId !== null ? $search('exact', $salesChannelId) : null;
        if ($seoUrl === null) {
            $seoUrl = $search('null', null);
        }
        if ($seoUrl === null && $salesChannelId === null) {
            $seoUrl = $search(null, null);
        }

        if ($seoUrl === null) {
            return null;
        }

        $path = $seoUrl->getSeoPathInfo();

        return ($path === null || $path === '') ? null : $path;
    }

    private function resolveTargetDomainBaseUrl(?string $salesChannelId, ?string $languageId, Context $context): ?string
    {
        if ($salesChannelId === null) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        if ($languageId !== null) {
            $criteria->addFilter(new EqualsFilter('languageId', $languageId));
        }
        $criteria->setLimit(1);

        $domain = $this->salesChannelDomainRepository->search($criteria, $context)->first();

        return $domain?->getUrl();
    }
}
