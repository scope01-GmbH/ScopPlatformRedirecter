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
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\FilesystemBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\RequestStackTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SessionTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseHelper\TestBrowser;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
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
    protected  $host = "shopware6.local";

    /**
     * @var string
     */
    protected  $hostWithLanguage = "shopware6.local/de-DE";

    /**
     * Set up test case
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->ids = new TestDataCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $this->ids->create('token'));
        $this->browser->followRedirects(false);
        $this->browser->setServerParameter('HTTP_HOST', $this->host);
        $this->browser->setServerParameter('HTTPS', true);

        /** @var Connection $conn */
        $conn = $this->getContainer()->get(Connection::class);

        $conn->executeUpdate('TRUNCATE scop_platform_redirecter_redirect', []);
        foreach ($this->getDatabaseRedirects() as $testRedirect) {
            $salesChannelId = $testRedirect[5] ?? null;
            $conn->executeUpdate('INSERT INTO scop_platform_redirecter_redirect (id, sourceURL, targetURL, httpCode, enabled, queryParamsHandling, salesChannelId, created_at) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ' . ($salesChannelId !== null ? 'UNHEX(' : '') . '?' . ($salesChannelId !== null ? ')' : '') . ', CURRENT_TIMESTAMP())', [UUID::randomHex(), $testRedirect[0], $testRedirect[1], $testRedirect[2], $testRedirect[3] ? 1 : 0, $testRedirect[4] ?? 0, $salesChannelId]);
        }

    }

    protected function checkRedirect(string $path, array $expectedLocation = null, int $expectedStatusCode = -1, bool $notExpected = false, string $method = 'GET'): void
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

    protected abstract function getDatabaseRedirects(): array;
}
