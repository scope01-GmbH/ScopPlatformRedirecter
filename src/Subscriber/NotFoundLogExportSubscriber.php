<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Scop\PlatformRedirecter\NotFoundLog\NotFoundLogDefinition;
use Shopware\Core\Content\ImportExport\Event\EnrichExportCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class NotFoundLogExportSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly InAppPurchase $inAppPurchase)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EnrichExportCriteriaEvent::class => 'onEnrichCriteria',
        ];
    }

    public function onEnrichCriteria(EnrichExportCriteriaEvent $event): void
    {
        $log = $event->getLogEntity();
        if ($log->getProfile()?->getSourceEntity() !== NotFoundLogDefinition::ENTITY_NAME) {
            return;
        }

        if (!$this->inAppPurchase->isActive('ScopPlatformRedirecter', 'scopPlatformRedirecterPremium')) {
            throw new AccessDeniedHttpException('Exporting the 404 log requires ScopPlatformRedirecter Premium.');
        }

        $filter = $log->getConfig()['parameters']['scopNotFoundLogFilter'] ?? null;
        if (!\is_array($filter)) {
            return;
        }

        $criteria = $event->getCriteria();
        $term = $filter['term'] ?? null;
        if (\is_string($term) && $term !== '') {
            $criteria->setTerm($term);
        }

        $hitCount = $this->rangeBounds(
            $filter['hitCount'] ?? null,
            static fn (mixed $value): bool => \is_int($value) && $value >= 0,
        );
        if ($hitCount !== []) {
            $criteria->addFilter(new RangeFilter('hitCount', $hitCount));
        }

        $lastHitAt = $this->rangeBounds(
            $filter['lastHitAt'] ?? null,
            static fn (mixed $value): bool => \is_string($value)
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}$/D', $value) === 1,
        );
        if ($lastHitAt !== []) {
            $criteria->addFilter(new RangeFilter('lastHitAt', $lastHitAt));
        }

        $salesChannelIds = $filter['salesChannelIds'] ?? [];
        if (\is_array($salesChannelIds)) {
            $salesChannelIds = array_values(array_filter(
                $salesChannelIds,
                static fn (mixed $id): bool => \is_string($id) && Uuid::isValid($id),
            ));
            if ($salesChannelIds !== []) {
                $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $salesChannelIds));
            }
        }

        // Existing export jobs may still contain the previous single-status parameter.
        $statuses = $filter['statuses'] ?? [$filter['status'] ?? null];
        $statusFilters = [];
        foreach (\is_array($statuses) ? $statuses : [] as $status) {
            $statusFilter = match ($status) {
                'open' => new AndFilter([new EqualsFilter('ignored', false), new EqualsFilter('redirectId', null)]),
                'linked' => new AndFilter([
                    new EqualsFilter('ignored', false),
                    new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('redirectId', null)]),
                ]),
                'ignored' => new EqualsFilter('ignored', true),
                default => null,
            };
            if ($statusFilter !== null) {
                $statusFilters[] = $statusFilter;
            }
        }
        if ($statusFilters !== []) {
            $criteria->addFilter(new OrFilter($statusFilters));
        }
    }

    /** @return array<string, int|string> */
    private function rangeBounds(mixed $range, callable $isValid): array
    {
        if (!\is_array($range)) {
            return [];
        }

        return array_filter(
            array_intersect_key($range, array_flip([RangeFilter::GTE, RangeFilter::LTE])),
            $isValid,
        );
    }
}
