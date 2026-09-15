<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Doctrine\DBAL\Connection;
use Scop\PlatformRedirecter\Test\RedirectTestCase;

class TemporaryRedirectsTest extends RedirectTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // The base setUp() inserts the rows with NULL date windows. Apply the temporary windows here
        // in UTC (matching how Shopware stores datetimes), relative to now.
        $conn = $this->getContainer()->get(Connection::class);
        $past = (new \DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s');
        $future = (new \DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');

        // Expired: end date already passed -> must not fire.
        $conn->executeStatement('UPDATE scop_platform_redirecter_redirect SET active_until = ? WHERE sourceURL = ?', [$past, '/expired']);
        // Scheduled: start date in the future -> must not fire yet.
        $conn->executeStatement('UPDATE scop_platform_redirecter_redirect SET active_from = ? WHERE sourceURL = ?', [$future, '/scheduled']);
        // Active window that contains now -> must fire.
        $conn->executeStatement('UPDATE scop_platform_redirecter_redirect SET active_from = ?, active_until = ? WHERE sourceURL = ?', [$past, $future, '/active-window']);
    }

    protected function getDatabaseRedirects(): array
    {
        return [
            ["/expired", "/account/", 301, true],
            ["/scheduled", "/account/", 301, true],
            ["/active-window", "/account/", 301, true],
            ["/no-window", "/account/", 301, true],
        ];
    }

    public function testExpiredRedirectDoesNotFire(): void
    {
        $this->checkRedirect('/expired', ['/account/', 'http://sw67.local/account/'], 301, true);
    }

    public function testScheduledRedirectDoesNotFireBeforeStart(): void
    {
        $this->checkRedirect('/scheduled', ['/account/', 'http://sw67.local/account/'], 301, true);
    }

    public function testRedirectFiresInsideWindow(): void
    {
        $this->checkRedirect('/active-window', ['/account/', 'http://sw67.local/account/'], 301);
    }

    public function testRedirectWithoutWindowFires(): void
    {
        $this->checkRedirect('/no-window', ['/account/', 'http://sw67.local/account/'], 301);
    }
}
