<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scop\PlatformRedirecter\Redirect\Redirect;
use Scop\PlatformRedirecter\Redirect\RedirectCollection;
use Scop\PlatformRedirecter\Subscriber\ProductDeletionSubscriber;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeleteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class ProductDeletionSubscriberTest extends TestCase
{
    private Context $context;
    private string $productId;
    private string $variantId;
    private string $salesChannelId;
    private EntityRepository&MockObject $redirects;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->productId = Uuid::randomHex();
        $this->variantId = Uuid::randomHex();
        $this->salesChannelId = Uuid::randomHex();
        $this->redirects = $this->createMock(EntityRepository::class);
    }

    public function testUpdatesTheExistingVariantRedirectInPlaceAfterDeletion(): void
    {
        $matching = $this->redirect($this->variantId, $this->salesChannelId);
        $matching->setTargetURL('/custom-target');
        $manual = $this->redirect(null, $this->salesChannelId);
        $otherChannel = $this->redirect($this->variantId, Uuid::randomHex());
        $otherProduct = $this->redirect(Uuid::randomHex(), $this->salesChannelId);
        $subscriber = $this->subscriber(new RedirectCollection([$manual, $otherChannel, $otherProduct, $matching]), 0);

        $written = [];
        $this->redirects->expects(self::once())->method('upsert')->with(
            self::callback(static function (array $payload) use (&$written): bool {
                $written = $payload;
                return true;
            }),
            $this->context,
        );

        $event = $this->deleteEvent();
        $subscriber->onEntityDelete($event);
        self::assertSame([], $written);
        $event->success();

        // A partial update keeps the ID, custom target and existing references.
        self::assertSame([['id' => $matching->getId(), 'httpCode' => 301, 'productId' => null]], $written);
    }

    public function testCreatesOneRedirectWithTheConfiguredCodeWhenNoAutomaticRedirectMatches(): void
    {
        $manual = $this->redirect(null, $this->salesChannelId);
        $subscriber = $this->subscriber(new RedirectCollection([$manual]), 302);
        $written = [];
        $this->redirects->expects(self::once())->method('upsert')->with(
            self::callback(static function (array $payload) use (&$written): bool {
                $written = $payload;
                return true;
            }),
            $this->context,
        );

        $event = $this->deleteEvent();
        $subscriber->onEntityDelete($event);
        $event->success();

        self::assertCount(1, $written);
        self::assertTrue(Uuid::isValid($written[0]['id']));
        self::assertNotSame($manual->getId(), $written[0]['id']);
        unset($written[0]['id']);
        self::assertSame([
            'sourceURL' => '/en/variant',
            'targetURL' => '/',
            'httpCode' => 302,
            'enabled' => true,
            'salesChannelId' => $this->salesChannelId,
        ], $written[0]);
    }

    public function testDoesNotWriteRedirectsWhenDeletionFails(): void
    {
        $subscriber = $this->subscriber(new RedirectCollection([$this->redirect($this->variantId, $this->salesChannelId)]), 301);
        $this->redirects->expects(self::never())->method('upsert');

        $event = $this->deleteEvent();
        $subscriber->onEntityDelete($event);
        $event->error();
    }

    private function subscriber(RedirectCollection $existing, int $httpCode): ProductDeletionSubscriber
    {
        $languageId = Uuid::randomHex();
        $seoUrl = new SeoUrlEntity();
        $seoUrl->setId(Uuid::randomHex());
        $seoUrl->setForeignKey($this->variantId);
        $seoUrl->setSeoPathInfo('variant');
        $seoUrl->setSalesChannelId($this->salesChannelId);
        $seoUrl->setLanguageId($languageId);

        $domain = new SalesChannelDomainEntity();
        $domain->setId(Uuid::randomHex());
        $domain->setUrl('https://example.test/en');
        $domain->setSalesChannelId($this->salesChannelId);
        $domain->setLanguageId($languageId);

        $products = $this->createStub(EntityRepository::class);
        $products->method('searchIds')->willReturn(IdSearchResult::fromIds([$this->variantId], new Criteria(), $this->context));
        $seoUrls = $this->createStub(EntityRepository::class);
        $seoUrls->method('search')->willReturn($this->searchResult(new SeoUrlCollection([$seoUrl])));
        $domains = $this->createStub(EntityRepository::class);
        $domains->method('search')->willReturn($this->searchResult(new SalesChannelDomainCollection([$domain])));
        $this->redirects->expects(self::once())->method('search')
            ->with(self::callback(function (Criteria $criteria): bool {
                $filter = $criteria->getFilters()[0];
                self::assertInstanceOf(EqualsAnyFilter::class, $filter);
                self::assertSame('productId', $filter->getField());
                self::assertSame([$this->productId, $this->variantId], $filter->getValue());
                return true;
            }), $this->context)
            ->willReturn($this->searchResult($existing));

        $config = $this->createStub(SystemConfigService::class);
        $config->method('get')->willReturn(true);
        $config->method('getInt')->willReturn($httpCode);

        // Seed Shopware's in-memory purchase cache without a Store request.
        $purchase = (new \ReflectionClass(InAppPurchase::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(InAppPurchase::class, 'activePurchases'))
            ->setValue($purchase, ['ScopPlatformRedirecter' => ['scopPlatformRedirecterPremium']]);

        return new ProductDeletionSubscriber($this->redirects, $seoUrls, $domains, $products, $config, $purchase);
    }

    private function deleteEvent(): EntityDeleteEvent
    {
        $command = $this->createStub(DeleteCommand::class);
        $command->method('getEntityName')->willReturn(ProductDefinition::ENTITY_NAME);
        $command->method('getDecodedPrimaryKey')->willReturn(['id' => $this->productId]);

        return EntityDeleteEvent::create(WriteContext::createFromContext($this->context), [$command]);
    }

    private function redirect(?string $productId, string $salesChannelId): Redirect
    {
        $redirect = new Redirect();
        $redirect->setId(Uuid::randomHex());
        $redirect->setProductId($productId);
        $redirect->setSalesChannelId($salesChannelId);
        $redirect->setSourceURL('/en/variant');
        $redirect->setHttpCode(302);

        return $redirect;
    }

    private function searchResult(EntityCollection $entities): EntitySearchResult
    {
        return new EntitySearchResult(
            $entities->first()?->getApiAlias() ?? 'scop_platform_redirecter_redirect',
            $entities->count(),
            $entities,
            null,
            new Criteria(),
            $this->context,
        );
    }
}
