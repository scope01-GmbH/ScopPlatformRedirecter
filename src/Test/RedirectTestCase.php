<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types=1);

/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Test;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\FilesystemBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\RequestStackTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SessionTestBehaviour;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class RedirectTestCase
 * @package Scop\PlatformDirecter\Tests
 */
abstract class RedirectTestCase extends TestCase
{

    use KernelTestBehaviour;
    use FilesystemBehaviour;
    use CacheTestBehaviour;
    use BasicTestDataBehaviour;
    use SessionTestBehaviour;
    use RequestStackTestBehaviour;
    use SalesChannelApiTestBehaviour;

    /**
     * @var KernelBrowser
     */
    private $browser;

    /**
     * @var TestDataCollection
     */
    private $ids;

    /**
     * @var string
     */
    protected  $host = "sw67.local";

    /**
     * @var string
     */
    protected  $hostWithLanguage = "sw67.local/de-DE";

    /**
     * Set up test case
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->ids = new IdsCollection();

        // These tests do not run inside a rolled-back transaction, so a sales channel created by a
        // previous test (with the same fixed domain) still exists. Remove it first to avoid a unique
        // constraint violation on the domain URL, mirroring how the core helper de-duplicates localhost.
        $this->deleteSalesChannelsForDomain('https://' . $this->host);

        // The domain must match the request host/scheme below, otherwise Shopware's RequestTransformer
        // cannot map the request to a sales channel and never sets the "sw-original-request-uri"
        // attribute that the redirect decorator relies on (no attribute -> no redirect).
        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
            'domains' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'https://' . $this->host,
                ],
            ],
        ]);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $this->ids->create('token'));
        $this->browser->followRedirects(false);
        $this->browser->setServerParameter('HTTP_HOST', $this->host);
        $this->browser->setServerParameter('HTTPS', true);

        /** @var Connection $conn */
        $conn = $this->getContainer()->get(Connection::class);

        // DELETE (child first) instead of TRUNCATE: the 404 log table has a foreign key to the
        // redirect table, and MySQL refuses to TRUNCATE a table referenced by a foreign key.
        $conn->executeStatement('DELETE FROM scop_platform_redirecter_404', []);
        $conn->executeStatement('DELETE FROM scop_platform_redirecter_redirect', []);
        foreach ($this->getDatabaseRedirects() as $testRedirect) {
            $salesChannelId = $testRedirect[5] ?? null;
            $conn->executeStatement('INSERT INTO scop_platform_redirecter_redirect (id, sourceURL, targetURL, httpCode, enabled, queryParamsHandling, salesChannelId, created_at) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ' . ($salesChannelId !== null ? 'UNHEX(' : '') . '?' . ($salesChannelId !== null ? ')' : '') . ', CURRENT_TIMESTAMP())', [UUID::randomHex(), $testRedirect[0], $testRedirect[1], $testRedirect[2], $testRedirect[3] ? 1 : 0, $testRedirect[4] ?? 0, $salesChannelId]);
        }

        /** @var EntityRepository $productRepo */
        $productRepo = $this->getContainer()->get('product.repository');

        $data = [
            'id' => Uuid::randomHex(),
            'name' => 'Test Product',
            'productNumber' => 'P_' . rand(0, 10000000),
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'net' => 4,
                    'gross' => 10,
                    'linked' => true
                ]
            ],
            'taxId' => $this->getContainer()->get(Connection::class)->fetchOne('SELECT LOWER(HEX(id)) FROM tax WHERE tax_rate = "19.000"'),
            'stock' => 10
        ];

        $productRepo->upsert([$data], Context::createDefaultContext());

    }

    public function tearDown(): void
    {
        parent::tearDown();

        $conn = $this->getContainer()->get(Connection::class);
        $conn->executeStatement('DELETE FROM product WHERE product_number LIKE "P_%"');
        $conn->executeStatement('DELETE FROM seo_url');

    }

    protected function checkRedirect(string $path, ?array $expectedLocation = null, int $expectedStatusCode = -1, bool $notExpected = false, string $method = 'GET'): void
    {
        $this->browser->request($method, $path);

        if ($expectedLocation !== null) {
            if ($notExpected)
                self::assertNotContains($this->browser->getInternalResponse()->getHeader("location"), $expectedLocation, "Path: $path");
            else
                self::assertContains($this->browser->getInternalResponse()->getHeader("location"), $expectedLocation, "Path: $path");
        }
        if ($expectedStatusCode !== -1) {
            if ($notExpected)
                self::assertNotSame($expectedStatusCode, $this->browser->getInternalResponse()->getStatusCode(), "Path: $path");
            else
                self::assertSame($expectedStatusCode, $this->browser->getInternalResponse()->getStatusCode(), "Path: $path");
        }
    }

    private function createCustomSalesChannelBrowser(array $salesChannelOverride = []): KernelBrowser
    {
        $kernel = $this->getKernel();

        $salesChannelApiBrowser = $kernel->getContainer()->get('test.browser');
        $salesChannelApiBrowser->disableReboot();

        $salesChannelApiBrowser->setServerParameters([
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_' . PlatformRequest::HEADER_CONTEXT_TOKEN => Random::getAlphanumericString(32),
        ]);

        $this->authorizeSalesChannelBrowser($salesChannelApiBrowser, $salesChannelOverride);

        return $salesChannelApiBrowser;
    }

    private function deleteSalesChannelsForDomain(string $url): void
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('domains.url', $url));

        $ids = $salesChannelRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
        if (empty($ids)) {
            return;
        }

        $salesChannelRepository->delete(array_map(static fn ($id) => ['id' => $id], $ids), Context::createDefaultContext());
    }

    protected abstract function getDatabaseRedirects(): array;
}
