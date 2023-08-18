<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class RedirectsWithUmlautsTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "/möbel",
                "/Aerodynamic-Aluminum-Wordlobster/7958b4c7e4f74220981f091454b2484e",
                302,
                true
            ],
            [
                "/hölzer",
                "/checkout/cart/",
                301,
                true
            ],
            [
                "/häuser",
                "/account/",
                301,
                true
            ],
            [
                "/oiawüitoioiwüth",
                "www.google.com",
                302,
                true
            ],
            [
                "/gefäße",
                "/account/",
                302,
                true
            ],
            [
                "/soßen",
                "/checkout/",
                301,
                true
            ],
            [
                "/ränder",
                "/checkout?c=5",
                301,
                true
            ],
            [
                "/stühle",
                "/index",
                301,
                true
            ]
        ];
    }

    private function doTestRedirectsWithUmlauts($supportEnabled)
    {
        /** @var SystemConfigService $config */
        $config = $this->getContainer()->get(SystemConfigService::class);

        $config->set('ScopPlatformRedirecter.config.specialCharSupport', $supportEnabled);

        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect(urlencode($redirect[0]), [$redirect[1], "http://" . $redirect[1]], $redirect[2], !$supportEnabled);
    }

    public function testRedirectsWithUmlautsWithSupportEnabled()
    {
        $this->doTestRedirectsWithUmlauts(true);
    }

    public function testRedirectsWithUmlautsWithSupportDisabled()
    {
        $this->doTestRedirectsWithUmlauts(false);
    }
}
