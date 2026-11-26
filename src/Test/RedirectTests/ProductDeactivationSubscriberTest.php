<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Scop\PlatformRedirecter\Subscriber\ProductDeactivationSubscriber;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSet;
use Shopware\Core\Framework\Store\InAppPurchase;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class ProductDeactivationSubscriberTest extends TestCase
{
    private Context $context;
    private string $parentId;
    private string $variantId;
    private EntityRepository&MockObject $products;
    private EntityRepository&MockObject $redirects;
    private EntityRepository&MockObject $seoUrls;
    private EntityRepository&Stub $domains;
    private ProductDeactivationSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->context->setConsiderInheritance(true);
        $this->parentId = Uuid::randomHex();
        $this->variantId = Uuid::randomHex();
        $this->products = $this->createMock(EntityRepository::class);
        $this->redirects = $this->createMock(EntityRepository::class);
        $this->seoUrls = $this->createMock(EntityRepository::class);
        $this->domains = $this->createStub(EntityRepository::class);

        $config = $this->createStub(SystemConfigService::class);
        $config->method('get')->willReturn(true);
        // Seed Shopware's in-memory purchase cache without a Store request.
        $purchase = (new \ReflectionClass(InAppPurchase::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(InAppPurchase::class, 'activePurchases'))
            ->setValue($purchase, ['ScopPlatformRedirecter' => ['scopPlatformRedirecterPremium']]);
        $this->subscriber = new ProductDeactivationSubscriber(
            $this->redirects,
            $this->seoUrls,
            $this->domains,
            $this->products,
            $config,
            $purchase,
        );
    }

    #[DataProvider('variantChanges')]
    public function testUsesInheritedActivationState(
        bool $parentActive,
        ?int $before,
        ?bool $after,
        bool $createRedirect,
        bool $deleteRedirect,
    ): void {
        $parent = new ProductEntity();
        $parent->setId($this->parentId);
        $parent->setActive($parentActive);
        $this->products->expects(self::once())->method('search')->with(
            self::callback(fn (Criteria $criteria): bool => $criteria->getIds() === [$this->parentId]),
            self::callback(static fn (Context $context): bool => !$context->considerInheritance()),
        )->willReturn($this->searchResult(new ProductCollection([$parent])));
        $this->products->expects(self::never())->method('searchIds');

        $this->expectRedirectChanges(
            $createRedirect ? [$this->variantId] : [],
            $deleteRedirect ? [$this->variantId] : [],
        );
        $this->dispatch($this->activeUpdate($this->variantId, $before, $after, $this->parentId));
        self::assertTrue($this->context->considerInheritance());
    }

    public static function variantChanges(): iterable
    {
        yield 'explicitly active to inherited active' => [true, 1, null, false, false];
        yield 'explicitly inactive to inherited active' => [true, 0, null, false, true];
        yield 'inherited active to explicitly inactive' => [true, null, false, true, false];
        yield 'explicitly active to inherited inactive' => [false, 1, null, true, false];
        yield 'explicitly inactive to inherited inactive' => [false, 0, null, false, false];
        yield 'inherited inactive to explicitly active' => [false, null, true, false, true];
    }

    public function testUsesBothParentStatesDuringBulkEdit(): void
    {
        $alreadyInactiveId = Uuid::randomHex();
        $untouchedVariantId = Uuid::randomHex();
        $newVariantId = Uuid::randomHex();
        $this->products->expects(self::never())->method('search');
        $this->products->expects(self::once())->method('searchIds')->with(
            self::callback(function (Criteria $criteria): bool {
                self::assertEquals([
                    new EqualsAnyFilter('parentId', [$this->parentId]),
                    new EqualsFilter('active', null),
                ], $criteria->getFilters());
                return true;
            }),
            self::callback(static fn (Context $context): bool => !$context->considerInheritance()),
        )->willReturn(IdSearchResult::fromIds(
            [$this->variantId, $alreadyInactiveId, $untouchedVariantId, $newVariantId],
            new Criteria(),
            $this->context,
        ));
        $this->expectRedirectChanges([$this->variantId, $this->parentId, $untouchedVariantId], []);

        $this->dispatch(
            $this->activeUpdate($this->variantId, 1, null, $this->parentId),
            $this->activeUpdate($alreadyInactiveId, 0, null, $this->parentId),
            $this->activeUpdate($this->parentId, 1, false),
            new EntityWriteResult($newVariantId, ['parentId' => $this->parentId], ProductDefinition::ENTITY_NAME, EntityWriteResult::OPERATION_INSERT),
        );
    }

    public function testCloseoutInheritanceAloneDoesNotChangeRedirects(): void
    {
        $this->products->expects(self::never())->method('search');
        $this->products->expects(self::never())->method('searchIds');
        $this->expectRedirectChanges([], []);
        $this->dispatch(new EntityWriteResult(
            $this->variantId,
            ['isCloseout' => null],
            ProductDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_UPDATE,
            null,
            new ChangeSet(['is_closeout' => 1], ['is_closeout' => null], false),
        ));
    }

    public function testDraftActivationDoesNotChangeLiveRedirects(): void
    {
        $this->products->expects(self::never())->method('search');
        $this->products->expects(self::never())->method('searchIds');
        $this->expectRedirectChanges([], []);

        $this->subscriber->onProductWritten(new EntityWrittenEvent(
            ProductDefinition::ENTITY_NAME,
            [$this->activeUpdate($this->variantId, 0, true, $this->parentId)],
            $this->context->createWithVersionId(Uuid::randomHex()),
        ));
    }

    private function activeUpdate(string $productId, ?int $before, ?bool $after, ?string $parentId = null): EntityWriteResult
    {
        return new EntityWriteResult(
            $productId,
            ['active' => $after],
            ProductDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_UPDATE,
            null,
            new ChangeSet(
                ['active' => $before, 'parent_id' => $parentId === null ? null : Uuid::fromHexToBytes($parentId)],
                ['active' => $after === null ? null : (int) $after],
                false,
            ),
        );
    }

    private function expectRedirectChanges(array $deactivated, array $activated): void
    {
        if ($deactivated === []) {
            $this->seoUrls->expects(self::never())->method('search');
            $this->redirects->expects(self::never())->method('upsert');
        } else {
            $domain = new SalesChannelDomainEntity();
            $domain->setId(Uuid::randomHex());
            $domain->setSalesChannelId(Uuid::randomHex());
            $domain->setLanguageId(Uuid::randomHex());
            $domain->setUrl('https://example.test/en');
            $this->domains->method('search')->willReturn($this->searchResult(new SalesChannelDomainCollection([$domain])));

            $urls = new SeoUrlCollection();
            foreach ($deactivated as $productId) {
                $url = new SeoUrlEntity();
                $url->setId(Uuid::randomHex());
                $url->setForeignKey($productId);
                $url->setSeoPathInfo($productId);
                $url->setSalesChannelId($domain->getSalesChannelId());
                $url->setLanguageId($domain->getLanguageId());
                $urls->add($url);
            }

            $this->seoUrls->expects(self::once())->method('search')->with(self::callback(
                static fn (Criteria $criteria): bool => $criteria->getFilters()[0] == new EqualsAnyFilter('foreignKey', $deactivated),
            ))->willReturn($this->searchResult($urls));
            $this->redirects->expects(self::once())->method('upsert')->with(self::callback(
                static function (array $payload) use ($deactivated): bool {
                    self::assertSame($deactivated, array_column($payload, 'productId'));
                    foreach ($payload as $row) {
                        self::assertSame(302, $row['httpCode']);
                        self::assertSame('/en/' . $row['productId'], $row['sourceURL']);
                    }
                    return true;
                },
            ), $this->context);
        }

        if ($activated === []) {
            $this->redirects->expects(self::never())->method('searchIds');
            $this->redirects->expects(self::never())->method('delete');
            return;
        }

        $redirectId = Uuid::randomHex();
        $this->redirects->expects(self::once())->method('searchIds')->with(self::callback(
            static fn (Criteria $criteria): bool => $criteria->getFilters()[0] == new EqualsAnyFilter('productId', $activated),
        ))->willReturn(IdSearchResult::fromIds([$redirectId], new Criteria(), $this->context));
        $this->redirects->expects(self::once())->method('delete')->with([['id' => $redirectId]], $this->context);
    }

    private function dispatch(EntityWriteResult ...$results): void
    {
        $this->subscriber->onProductWritten(new EntityWrittenEvent(ProductDefinition::ENTITY_NAME, $results, $this->context));
    }

    private function searchResult(EntityCollection $entities): EntitySearchResult
    {
        return new EntitySearchResult(
            $entities->first()?->getApiAlias() ?? 'product',
            $entities->count(),
            $entities,
            null,
            new Criteria(),
            $this->context,
        );
    }
}
