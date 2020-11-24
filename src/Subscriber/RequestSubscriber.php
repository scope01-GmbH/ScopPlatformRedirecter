<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types=1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RequestSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $repository;

    /**
     * @param EntityRepositoryInterface $redirectRepository
     */
    public function __construct(EntityRepositoryInterface $redirectRepository)
    {
        /** @var EntityRepositoryInterface $repository */
        $this->repository = $redirectRepository;
    }

    /**
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSendResponseEvent::class => 'redirectBeforeSendResponse'
        ];
    }

    /**
     * Redirect to the new url if found in redirects
     * Otherwise do nothing
     * Modules like admin, api or widgets are excluded from redirects
     *
     * @param BeforeSendResponseEvent $event
     */
    public function redirectBeforeSendResponse(BeforeSendResponseEvent $event): void
    {
        $requestUri = $event->getRequest()->getUri();
        $requestHost = $event->getRequest()->getHost();
        $requestBase = $event->getRequest()->getPathInfo();

        // Block overriding /admin and /api and widgets, so you can't lock out of the administration.
        if (\strpos($requestBase, "/admin") === 0) {
            return;
        }
        if (\strpos($requestBase, "/api") === 0) {
            return;
        }
        if (\strpos($requestBase, "/widgets") === 0) {
            return;
        }

        // Search for Redirect
        $search = [
            $requestBase, // e.g. "/test"
            \substr($requestBase, 1, strlen($requestBase) - 1), // e.g. "test"
            $requestHost . $requestBase, // e.g. "localhost/test"
            $requestUri // e.g. "http://localhost/test" or "https://localhost/test"
        ];

        $redirects = $this->repository->search((new Criteria())->addFilter(new EqualsAnyFilter('sourceURL', $search))
            ->setLimit(1), Context::createDefaultContext(null));

        // No Redirect found for this URL, do nothing
        if ($redirects->count() === 0) {
            return;
        }

        $redirect = $redirects->first();
        $targetURL = $redirect->getTargetURL();
        $code = $redirect->getHttpCode();

        /*
         *  checks if $targetURL is a full url or path and redirects accordingly
         */
        if (!(\strpos($targetURL, "http:") === 0 || \strpos($targetURL, "https:") === 0)) {
            if (\strpos($targetURL, "www.") === 0) {
                $targetURL = "http://" . $targetURL;
            } else {
                if (\strpos($targetURL, "/") !== 0) {
                    $targetURL = "/" . $targetURL;
                }
            }
        }
        $event->setResponse(new RedirectResponse($targetURL, $code));
    }
}
