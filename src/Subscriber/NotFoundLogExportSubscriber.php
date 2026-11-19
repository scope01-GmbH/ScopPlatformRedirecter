<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Subscriber;

use Scop\PlatformRedirecter\NotFoundLog\NotFoundLogDefinition;
use Shopware\Core\Content\ImportExport\Event\EnrichExportCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Store\InAppPurchase;
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

        match ($filter['status'] ?? null) {
            'open' => $criteria->addFilter(new EqualsFilter('redirectId', null), new EqualsFilter('ignored', false)),
            'linked' => $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('redirectId', null)])),
            'ignored' => $criteria->addFilter(new EqualsFilter('ignored', true)),
            default => null,
        };
    }
}
