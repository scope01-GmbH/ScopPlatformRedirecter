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
 * functional after the entity is gone.
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

            $redirectRows = $this->findRedirectsLinkedTo($entityType, $entityIds, $context);
            if (empty($redirectRows)) {
                continue;
            }

            $seoUrls = $this->getCanonicalSeoUrlsByEntity($entityType, $entityIds, $context);

            foreach ($redirectRows as $redirect) {
                $entityId = $redirect['targetEntityId'];
                $frozenUrl = $seoUrls[$entityId] ?? $redirect['targetURL'];
                if ($frozenUrl === null || $frozenUrl === '') {
                    $frozenUrl = '/';
                }

                $updates[] = [
                    'id' => $redirect['id'],
                    'targetURL' => $frozenUrl,
                    'targetEntityType' => null,
                    'targetEntityId' => null,
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
     * @return array<int, array{id: string, targetURL: string, targetEntityId: string}>
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
            ];
        }

        return $rows;
    }

    /**
     * @param string[] $entityIds
     * @return array<string, string> entityId (lowercase hex) => seo path with leading slash
     */
    private function getCanonicalSeoUrlsByEntity(string $entityType, array $entityIds, Context $context): array
    {
        $routeName = self::ENTITY_ROUTE_MAP[$entityType] ?? null;
        if ($routeName === null) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('routeName', $routeName));
        $criteria->addFilter(new EqualsAnyFilter('foreignKey', $entityIds));
        $criteria->addFilter(new EqualsFilter('isCanonical', true));

        $result = [];
        foreach ($this->seoUrlRepository->search($criteria, $context) as $seoUrl) {
            $fk = $seoUrl->getForeignKey();
            if ($fk === null) {
                continue;
            }
            $path = $seoUrl->getSeoPathInfo();
            if ($path === null || $path === '') {
                continue;
            }
            // first-canonical wins (per-sales-channel duplicates are normal)
            if (!isset($result[$fk])) {
                $result[$fk] = '/' . ltrim($path, '/');
            }
        }

        return $result;
    }
}
