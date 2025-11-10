<?php
declare(strict_types=1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Subscriber;


use Scop\PlatformRedirecter\Redirect\Redirect;
use Scop\PlatformRedirecter\Redirect\RedirectDefinition;
use Shopware\Core\Content\ImportExport\Event\ImportExportBeforeImportRecordEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class ImportSubscriber implements EventSubscriberInterface
{

    public function __construct(private EntityRepository $redirectRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ImportExportBeforeImportRecordEvent::class => 'preImportRecord',
        ];
    }

    /**
     * @param ImportExportBeforeImportRecordEvent $event
     * @return void
     */
    public function preImportRecord(ImportExportBeforeImportRecordEvent $event): void
    {
        $config = $event->getConfig();
        if ($config->get('sourceEntity') !== RedirectDefinition::ENTITY_NAME) {
            return;
        }

        $record = $event->getRecord();
        $recordId = $record['id'] ?? null;
        if (!empty($record['sourceURL'])) {
            $sourceURL = $record['sourceURL'];
            $criteria = new Criteria();
            $criteria->addFilter(
                new EqualsFilter('sourceURL', $sourceURL)
            );
            $existedRedirect = $this->redirectRepository->search($criteria, $event->getContext())->first();
            if ($existedRedirect instanceof Redirect && $existedRedirect->getId() !== $recordId) {
                // the SourceURLs match and should be updated, updating it
                $record['id'] = $existedRedirect->getId();
            }
            $event->setRecord($record);
        }
    }
}