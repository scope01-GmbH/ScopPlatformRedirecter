<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Doctrine\DBAL\Connection;
use Scop\PlatformRedirecter\Test\RedirectTestCase;

class SalesChannelLimitedRedirectsTest extends RedirectTestCase
{

    /**
     * @var array $salesChannels
     */
    protected $salesChannels = [];

    protected function getDatabaseRedirects(): array
    {
        if (empty($this->salesChannels)) {
            $this->loadSalesChannelData();
        }
        return [
            [
                "/wau",
                "/Aerodynamic-Aluminum-Wordlobster/7958b4c7e4f74220981f091454b2484e",
                302,
                true,
                0,
                null
            ],
            [
                "/details",
                "/checkout/cart/",
                301,
                true,
                0,
                $this->salesChannels[0]['id']
            ],
            [
                "/test",
                "/account/",
                301,
                true,
                0,
                $this->salesChannels[1]['id']
            ],
            [
                "/googling",
                "www.google.com",
                302,
                true,
                0,
                null
            ],
            [
                "/google",
                "/account/",
                302,
                true,
                0,
                $this->salesChannels[0]['id']
            ],
            [
                "/men",
                "/checkout/",
                301,
                true,
                0,
                $this->salesChannels[1]['id']
            ],
            [
                "/women",
                "/checkout?c=5",
                301,
                true,
                0,
                $this->salesChannels[0]['id']
            ],
            [
                "/dummy",
                "/index",
                301,
                true,
                1,
                $this->salesChannels[1]['id']
            ]
        ];
    }

    private function loadSalesChannelData()
    {
        /** @var Connection $conn */
        $conn = $this->getContainer()->get(Connection::class);

        $result = $conn->executeQuery("SELECT HEX(sc.id) as id, sales_channel_domain.url as url FROM `sales_channel` sc LEFT JOIN sales_channel_type ON sc.type_id = sales_channel_type.id LEFT JOIN sales_channel_domain ON sales_channel_domain.id = (SELECT id FROM sales_channel_domain WHERE sales_channel_domain.sales_channel_id = sc.id LIMIT 1) WHERE sales_channel_type.icon_name = 'regular-storefront' ORDER BY RAND() LIMIT 2");
        self::assertTrue($result->rowCount() > 1, 'This test requires at least two sales channels!');

        while ($salesChannel = $result->fetch()) {
            $this->salesChannels[] = ['id' => $salesChannel['id'], 'url' => $salesChannel['url']];
        }
    }

    public function testRedirectsInFirstSalesChanel()
    {
        $this->dotestRedirectsInSalesChannel(0);
    }

    public function testRedirectsInSecondSalesChanel()
    {
        $this->dotestRedirectsInSalesChannel(1);
    }

    private function dotestRedirectsInSalesChannel($salesChannelIndex)
    {
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($this->salesChannels[$salesChannelIndex]['url'] . $redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2], $redirect[5] !== null && $redirect[5] !== $this->salesChannels[$salesChannelIndex]['id']);
    }
}
