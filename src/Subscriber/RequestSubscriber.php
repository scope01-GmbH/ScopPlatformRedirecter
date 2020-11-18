<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types = 1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license proprietär
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
        /** @var EntityRepositoryInterface $repository*/
        $this->repository = $redirectRepository;
    }

    /**
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest'
        ];
    }

    /**
     *
     * @param RequestEvent $event
     */
    public function onRequest(RequestEvent $event): void
    {
        $requestURI = $event->getRequest()->getUri();
        $requestHost = $event->getRequest()->getHost();
        $requestBase = $event->getRequest()->getPathInfo();

        // Block overriding /admin and /api, so you can't lock out of the administration.
        if (strpos($requestBase, "/admin") === 0) {
            return;
        }
        if (strpos($requestBase, "/api") === 0) {
            return;
        }

        // Search for Redirect
        $search = [
            $requestBase, // e.g. "/test"
            substr($requestBase, 1, strlen($requestBase) - 1), // e.g. "test"
            $requestHost . $requestBase, // e.g. "localhost/test"
            $requestURI // e.g. "http://localhost/test" or "https://localhost/test"
        ];

        $redirects = $this->repository->search((new Criteria())->addFilter(new EqualsAnyFilter('sourceURL', $search))
            ->setLimit(1), Context::createDefaultContext(null));
        if ($redirects->count() === 0) {
            return;
        } // No Redirect found for this URL

        $redirect = $redirects->first();
        $targetURL = $redirect->getTargetURL();
        $code = $redirect->getHttpCode();

        /*
         *  checks if $targetURL is a full url or path and redirects accordingly
         */
        if (! (strpos($targetURL, "http:") === 0 || strpos($targetURL, "https:") === 0)) {
            if (strpos($targetURL, "www.") === 0) {
                $targetURL = "http://" . $targetURL;
            } else {
                if (strpos($targetURL, "/") !== 0) {
                    $targetURL = "/" . $targetURL;
                }
            }
        }
        $event->setResponse(new RedirectResponse($targetURL, $code));
    }
}
