<?php
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

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest'
        ];
    }

    public function onRequest(RequestEvent $event)
    {
        $requestURI = $event->getRequest()->getUri();
        $requestHost = $event->getRequest()->getHost();
        $requestBase = $event->getRequest()->getPathInfo();

        if (substr($requestBase, 0, 6) === "/admin")
            return;
        if (substr($requestBase, 0, 4) === "/api")
            return;

        $search = [
            $requestBase, // e.g. "/test"
            substr($requestBase, 1, strlen($requestBase) - 1), // e.g. "test"
            $requestHost . $requestBase, // e.g. "localhost/test"
            $requestURI // e.g. "http://localhost/test" or "https://localhost/test"
        ];

        $redirects = $this->repository->search((new Criteria())->addFilter(new EqualsAnyFilter('sourceURL', $search))
            ->setLimit(1), Context::createDefaultContext(null));
        if ($redirects->count() == 0)
            return;

        $redirect = $redirects->first();
        $targetURL = $redirect->getTargetURL();
        $code = $redirect->getHttpCode();

        if (! (substr($targetURL, 0, 5) === "http:" || substr($targetURL, 0, 6) === "https:")) {
            if (substr($targetURL, 0, 4) === "www.") {
                $targetURL = "http://" . $targetURL;
            } else {
                if (substr($targetURL, 0, 1) !== "/") {
                    $targetURL = "/" . $targetURL;
                }
            }
        }
        $event->setResponse(new RedirectResponse($targetURL, $code));
    }

    public function __construct(EntityRepositoryInterface $redirectRepository)
    {
        $this->repository = $redirectRepository;
    }
}
    
