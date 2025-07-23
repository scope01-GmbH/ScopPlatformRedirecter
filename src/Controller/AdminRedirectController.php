<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\InAppPurchase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('framework')]
class AdminRedirectController extends AbstractController
{
    const inAppPurchaseId = 'my-iap-identifier';

    public function __construct(private readonly EntityRepository $redirectRepository, private readonly InAppPurchase $inAppPurchase)
    {
    }

    #[Route(path: '/api/_admin/scop-check-redirects', name: 'api.admin.scop.check.redirects', defaults: ['_entity' => 'scop_platform_redirecter_redirect'], methods: ['POST'])]
    public function checkRedirects(Request $request, Criteria $criteria, Context $context): JsonResponse
    {
        if ($this->inAppPurchase->isActive('ScopPlatformRedirecter', self::inAppPurchaseId) === false) {
           /* return new JsonResponse([
                'total' => 0,
                'broken' => 0,
                'error' => 'in_app_purchase_invalid',
            ]);*/
        }

        $criteria = new Criteria();
        $redirects = $this->redirectRepository->search($criteria, $context);

        foreach ($redirects as $redirect) {

        }

        dd($redirects);


        $broken = [];
        $total = 0;

        return new JsonResponse([
            'total' => $total,
            'broken' => $broken,
            'error' => ''
        ]);
    }
}
