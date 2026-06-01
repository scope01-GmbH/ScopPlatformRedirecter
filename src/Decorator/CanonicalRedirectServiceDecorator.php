<?php

namespace Scop\PlatformRedirecter\Decorator;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\Routing\CanonicalRedirectService;
use Shopware\Core\Framework\Extensions\ExtensionDispatcher;
use Shopware\Core\Framework\Store\InAppPurchase;
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

    private EntityRepository $seoUrlRepository;

    private InAppPurchase $inAppPurchase;

    public function __construct(CanonicalRedirectService $inner, SystemConfigService $configService, EntityRepository $redirectRepository, ExtensionDispatcher $extensionDispatcher, EntityRepository $seoUrlRepository, InAppPurchase $inAppPurchase)
    {
        parent::__construct($configService, $extensionDispatcher);
        $this->configService = $configService;
        $this->repository = $redirectRepository;
        $this->inner = $inner;
        $this->seoUrlRepository = $seoUrlRepository;
        $this->inAppPurchase = $inAppPurchase;
    }

    private const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

    private const ENTITY_ROUTE_MAP = [
        'product' => 'frontend.detail.page',
        'category' => 'frontend.navigation.page',
    ];

    private function isEntityLinkFeatureEnabled(): bool
    {
        return $this->inAppPurchase->isActive('ScopPlatformRedirecter', self::IN_APP_PURCHASE_ID);
    }

    private function resolveEntityUrl(string $entityType, string $entityId, ?string $salesChannelId, Context $context): ?string
    {
        $routeName = self::ENTITY_ROUTE_MAP[$entityType] ?? null;
        if ($routeName === null) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('routeName', $routeName));
        $criteria->addFilter(new EqualsFilter('foreignKey', $entityId));
        $criteria->addFilter(new EqualsFilter('isCanonical', true));
        if ($salesChannelId !== null) {
            $criteria->addFilter(new OrFilter([
                new EqualsFilter('salesChannelId', $salesChannelId),
                new EqualsFilter('salesChannelId', null),
            ]));
        }
        $criteria->setLimit(1);

        $seoUrl = $this->seoUrlRepository->search($criteria, $context)->first();
        if ($seoUrl === null) {
            return null;
        }

        $path = $seoUrl->getSeoPathInfo();
        if ($path === null || $path === '') {
            return null;
        }

        return '/' . ltrim($path, '/');
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

                $criteria = new Criteria();
                $criteria->addFilter(new EqualsAnyFilter('sourceURL', $searchWithoutQuery));
                $criteria->addFilter(new EqualsFilter('enabled', true));
                $criteria->addFilter(new EqualsAnyFilter('queryParamsHandling', [1, 2]));
                $criteria->addFilter(new OrFilter([new EqualsFilter('salesChannelId', $salesChannelId), new EqualsFilter('salesChannelId', null)]));
                $criteria->setLimit(1);

                $redirects = $this->repository->search($criteria, $context);
                
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
        $code = $redirect->getHttpCode();

        // Prefer dynamically resolved entity URL when the redirect is linked to a product/category.
        // Entity-link resolution is part of the Premium IAP; without it, fall back to the stored targetURL.
        $entityType = $redirect->getTargetEntityType();
        $entityId = $redirect->getTargetEntityId();
        $resolvedEntityUrl = null;
        if ($entityType !== null && $entityId !== null && $this->isEntityLinkFeatureEnabled()) {
            $resolvedEntityUrl = $this->resolveEntityUrl($entityType, $entityId, $salesChannelId, $context);
        }

        $targetURL = $resolvedEntityUrl ?? $redirect->getTargetURL();

        // Dangling entity link: linked to an entity, but neither the live SEO URL is resolvable nor is a frozen
        // targetURL stored. Bail out instead of redirecting to "/" to avoid masking 404s with a broken redirect.
        if ($entityType !== null && $entityId !== null && $resolvedEntityUrl === null && ($targetURL === null || trim($targetURL) === '')) {
            return $this->inner->getRedirect($request);
        }

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
