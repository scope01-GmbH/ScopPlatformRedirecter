<?php

namespace Scop\PlatformRedirecter\Test;

use Shopware\Core\Framework\Event\BeforeSendRedirectResponseEvent;
use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Shopware\Core\Framework\Routing\CanonicalRedirectService;
use Shopware\Core\Framework\Test\TestCaseHelper\TestBrowser;
use Shopware\Core\Kernel;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Response as DomResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ScopTestBrowser extends TestBrowser
{

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(Kernel $kernel, EventDispatcherInterface $eventDispatcher, ?History $history = null, ?CookieJar $cookieJar = null)
    {
        parent::__construct($kernel, $eventDispatcher, $kernel->getContainer()->getParameter("test.client.parameters"), $history, $cookieJar);

        $this->eventDispatcher = $eventDispatcher;
        //$kernel->getContainer()->get("class-loader");
    }

    /**
     * @param Response $response
     *
     * @return DomResponse
     */
    protected function filterResponse($response): DomResponse
    {
        if (!($response instanceof RedirectResponse)) {
            $event = new BeforeSendResponseEvent($this->lastRequest, $response);
            $this->eventDispatcher->dispatch($event);
        }

        $filteredResponse = parent::filterResponse(isset($event) ? $event->getResponse() : $response);
        return $filteredResponse;
    }

    protected function doRequest($request): Response
    {
        $container = $this->getContainer();

        $redirect = $container
            ->get(CanonicalRedirectService::class)
            ->getRedirect($request);

        if ($redirect instanceof RedirectResponse) {
                        $event = new BeforeSendRedirectResponseEvent($request, $redirect);
            $container->get('event_dispatcher')->dispatch($event);
            return $event->getResponse();
        }
        return parent::doRequest($request);
    }

}
