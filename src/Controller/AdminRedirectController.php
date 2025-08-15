<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Controller;

use Scop\PlatformRedirecter\MessageQueue\Message\CheckRedirectMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\SalesChannelRepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\InAppPurchase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('framework')]
class AdminRedirectController extends AbstractController
{
    const inAppPurchaseId = 'scopPlatformRedirecterPremium';

    public function __construct(private readonly EntityRepository $redirectRepository, private readonly InAppPurchase $inAppPurchase, private readonly MessageBusInterface $bus)
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
        $iterator = new RepositoryIterator($this->redirectRepository, $context, $criteria);

        $total = $iterator->getTotal();

        if ($total > 0) {
            while ($redirectItem = $iterator->fetch()) {
                foreach ($redirectItem->getEntities() as $redirect) {
                    $this->bus->dispatch(new CheckRedirectMessage($redirect));
                }
            }
        }

        $total = 0;

        return new JsonResponse([
            'total' => $total,
            'error' => ''
        ]);
    }
}
