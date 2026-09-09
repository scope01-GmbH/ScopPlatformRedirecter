<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Doctrine\DBAL\Connection;
use Scop\PlatformRedirecter\Test\RedirectTestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\CanonicalRedirectService;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Verifies that entity-link resolution (target = product/category) returns the SEO URL of the
 * language selected on the redirect (target_language_id) instead of always the default language.
 *
 * The decorator's resolveEntityUrl() is exercised directly: it is the single place the language
 * filter was added, and testing it this way avoids the Premium IAP gate / storefront plumbing while
 * still asserting the real behaviour against the database.
 */
class EntityLinkLanguageRedirectsTest extends RedirectTestCase
{
    protected function getDatabaseRedirects(): array
    {
        return [];
    }

    public function testResolvesSeoUrlForEachSelectedLanguage(): void
    {
        $languageIds = $this->getConnection()->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM language LIMIT 2');
        if (\count($languageIds) < 2) {
            static::markTestSkipped('Need at least two languages to test language-specific resolution.');
        }
        [$langA, $langB] = $languageIds;

        $categoryId = Uuid::randomHex();
        $pathA = 'category-lang-a-' . Uuid::randomHex();
        $pathB = 'category-lang-b-' . Uuid::randomHex();

        // language A is inserted first, so it is what the unfiltered "first canonical" lookup returns.
        $this->insertSeoUrl($categoryId, $langA, null, $pathA);
        $this->insertSeoUrl($categoryId, $langB, null, $pathB);

        // Selecting a language must return exactly that language's SEO URL.
        static::assertSame('/' . $pathA, $this->resolveEntityUrl('category', $categoryId, null, $langA));
        static::assertSame('/' . $pathB, $this->resolveEntityUrl('category', $categoryId, null, $langB));

        // Without a selected language the legacy behaviour (first canonical) is kept.
        static::assertContains(
            $this->resolveEntityUrl('category', $categoryId, null, null),
            ['/' . $pathA, '/' . $pathB]
        );
    }

    public function testReturnsNullWhenSelectedLanguageHasNoSeoUrl(): void
    {
        $languageIds = $this->getConnection()->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM language LIMIT 2');
        if (\count($languageIds) < 2) {
            static::markTestSkipped('Need at least two languages to test language-specific resolution.');
        }
        [$langA, $langB] = $languageIds;

        $categoryId = Uuid::randomHex();
        $this->insertSeoUrl($categoryId, $langA, null, 'only-lang-a-' . Uuid::randomHex());

        // Language B has no SEO URL for this entity -> nothing to resolve (redirect would bail out).
        static::assertNull($this->resolveEntityUrl('category', $categoryId, null, $langB));
    }

    public function testLanguageResolutionIsScopedToRequestSalesChannel(): void
    {
        $salesChannelIds = $this->getConnection()->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 2');
        if (\count($salesChannelIds) < 2) {
            static::markTestSkipped('Need at least two sales channels to test channel-scoped resolution.');
        }
        [$channelX, $channelY] = $salesChannelIds;
        $language = $this->getConnection()->fetchOne('SELECT LOWER(HEX(id)) FROM language LIMIT 1');

        $categoryId = Uuid::randomHex();
        $path = 'channel-x-only-' . Uuid::randomHex();
        // SEO URL exists only for channel X.
        $this->insertSeoUrl($categoryId, $language, $channelX, $path);

        // Requested from channel X -> resolves. Requested from channel Y -> nothing (would bail out).
        static::assertSame('/' . $path, $this->resolveEntityUrl('category', $categoryId, $channelX, $language));
        static::assertNull($this->resolveEntityUrl('category', $categoryId, $channelY, $language));
    }

    public function testChannelAgnosticSeoUrlResolvesForAnyChannel(): void
    {
        $salesChannelIds = $this->getConnection()->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 2');
        if (\count($salesChannelIds) < 2) {
            static::markTestSkipped('Need at least two sales channels to test channel-scoped resolution.');
        }
        [, $channelY] = $salesChannelIds;
        $language = $this->getConnection()->fetchOne('SELECT LOWER(HEX(id)) FROM language LIMIT 1');

        $categoryId = Uuid::randomHex();
        $path = 'channel-agnostic-' . Uuid::randomHex();
        // Channel-independent SEO URL (sales_channel_id = NULL).
        $this->insertSeoUrl($categoryId, $language, null, $path);

        // A NULL-channel SEO URL matches requests from any sales channel.
        static::assertSame('/' . $path, $this->resolveEntityUrl('category', $categoryId, $channelY, $language));
    }

    private function resolveEntityUrl(string $entityType, string $entityId, ?string $salesChannelId, ?string $targetLanguageId): ?string
    {
        $decorator = $this->getContainer()->get(CanonicalRedirectService::class);

        $method = new \ReflectionMethod($decorator, 'resolveEntityUrl');
        $method->setAccessible(true);

        return $method->invoke($decorator, $entityType, $entityId, $salesChannelId, $targetLanguageId, Context::createDefaultContext());
    }

    private function insertSeoUrl(string $foreignKey, string $languageId, ?string $salesChannelId, string $seoPath): void
    {
        $this->getConnection()->executeStatement(
            'INSERT INTO seo_url
                (id, language_id, sales_channel_id, foreign_key, route_name, path_info, seo_path_info, is_canonical, is_modified, is_deleted, created_at)
             VALUES (UNHEX(?), UNHEX(?), ' . ($salesChannelId !== null ? 'UNHEX(?)' : 'NULL') . ', UNHEX(?), ?, ?, ?, 1, 0, 0, CURRENT_TIMESTAMP())',
            $salesChannelId !== null
                ? [Uuid::randomHex(), $languageId, $salesChannelId, $foreignKey, 'frontend.navigation.page', '/navigation/' . $foreignKey, $seoPath]
                : [Uuid::randomHex(), $languageId, $foreignKey, 'frontend.navigation.page', '/navigation/' . $foreignKey, $seoPath]
        );
    }

    private function getConnection(): Connection
    {
        return $this->getContainer()->get(Connection::class);
    }
}
