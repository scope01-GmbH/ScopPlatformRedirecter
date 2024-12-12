<?php

namespace Scop\PlatformRedirecter\Decorator;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\Routing\CanonicalRedirectService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalRedirectServiceDecorator extends CanonicalRedirectService
{

    /**
     * @var CanonicalRedirectService
     */
    private $inner;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var SystemConfigService
     */
    private SystemConfigService $configService;

    public function __construct(CanonicalRedirectService $inner, SystemConfigService $configService, EntityRepository $redirectRepository)
    {
        parent::__construct($configService);
        $this->configService = $configService;
        $this->repository = $redirectRepository;
        $this->inner = $inner;
    }

    /**
     * Redirect to the new url if found in redirects
     * Otherwise do nothing
     * Modules like admin, api or widgets are excluded from redirects
     *
     * @param Request $request
     * @return Response|null
     */
    public function getRedirect(Request $request): ?Response
    {
        $requestUri = (string)$request->get("sw-original-request-uri");

        $storefrontUri = $request->get('sw-sales-channel-absolute-base-url');
        $requestBase = $request->getPathInfo();
        $requestBaseUrl = $request->getBaseUrl();

        $salesChannelId = $request->get('sw-sales-channel-id');

        // Block overriding /admin and /api and widgets, so you can't lock out of the administration.
        if (\strpos($requestBase, "/admin") === 0) {
            return $this->inner->getRedirect($request);
        }
        if (\strpos($requestBase, "/api") === 0) {
            return $this->inner->getRedirect($request);
        }
        if (\strpos($requestBase, "/widgets") === 0) {
            return $this->inner->getRedirect($request);
        }
        if (\strpos($requestBase, "/store-api") === 0) {
            return $this->inner->getRedirect($request);
        }
        if (\strpos($requestBase, "/_profiler") === 0) {
            return $this->inner->getRedirect($request);
        }

        if ($this->configService->getBool('ScopPlatformRedirecter.config.specialCharSupport') ?? false) {
            $requestUri = urldecode($requestUri);
            $storefrontUri = urldecode($storefrontUri);
            $requestBaseUrl = urldecode($requestBaseUrl);
        }

        $context = Context::createDefaultContext();

        $search = [
            $requestBaseUrl . '/' . $requestUri, // relative url with shopware 6 in sub folder: /public/Ergonomic-Concrete-Cough-Machine/48314803f1244f609a2ce907bfb48f53
            $requestBaseUrl . $requestUri, // relative url with shopware 6 in sub folder url is not shopware seo url: /public/test
            $storefrontUri . $requestUri, // absolute url with shopware 6 in sub folder, full url with host: http://shopware-platform.local/public/test1
            $storefrontUri . '/' . $requestUri, // absolute url with shopware 6 in sub folder, full url with host and slash at the end: http://shopware-platform.local/public/Freizeit-Elektro/Telefone/
            $requestUri, // relative url domain configured in public folder: /Ergonomic-Concrete-Cough-Machine/48314803f1244f609a2ce907bfb48f53 or /test4
            '/' . $requestUri, // absolute url domain configured in public folder: http://shopware-platform.local/Shoes-Baby/
            \substr($requestUri, 1), // e.g. "test"
        ];

        // search for the redirect in the database
        $redirects = $this->repository->search((new Criteria())->addFilter(new EqualsAnyFilter('sourceURL', $search))->addFilter(new EqualsFilter('enabled', true))->addFilter(new OrFilter([new EqualsFilter('salesChannelId', $salesChannelId), new EqualsFilter('salesChannelId', null)]))
            ->setLimit(1), $context);

        if ($redirects->count() === 0) {
            // Checks if the requested URL contains Query parameters, and if so, checks if a redirect can be found with the ignoreQueryParams option
            if (str_contains($requestUri, '?')) {
                $searchWithoutQuery = [];
                foreach ($search as $string)
                    $searchWithoutQuery[] = explode('?', $string)[0];

                $redirects = $this->repository->search((new Criteria())->addFilter(new EqualsAnyFilter('sourceURL', $searchWithoutQuery))->addFilter(new EqualsFilter('enabled', true))->addFilter(new EqualsAnyFilter('queryParamsHandling', [1, 2]))
                    ->setLimit(1), $context);

                // No Redirect found for this URL, do nothing
                if ($redirects->count() === 0) {
                    return $this->inner->getRedirect($request);
                }
            } else {
                // No Redirect found for this URL, do nothing
                return $this->inner->getRedirect($request);
            }
        }

        $redirect = $redirects->first();
        $targetURL = $redirect->getTargetURL();
        $code = $redirect->getHttpCode();

        // If configured in the redirect, adds the query parameters from the requested URL to the target URL
        if ($redirect->getQueryParamsHandling() === 2 && str_contains($requestUri, '?')) {
            $targetURL .= '?' . explode('?', $requestUri, 2)[1];
        }

        // Prevent endless redirecting when target url and source url have only different capitalisation
        if (in_array(trim($targetURL), $search, true)) {
            return $this->inner->getRedirect($request);
        }

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
        return new RedirectResponse($targetURL, $code);
    }

}
