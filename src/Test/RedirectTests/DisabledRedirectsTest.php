<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class DisabledRedirectsTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "/wau",
                "/Aerodynamic-Aluminum-Wordlobster/7958b4c7e4f74220981f091454b2484e",
                302,
                true
            ],
            [
                "/details",
                "/checkout/cart/",
                301,
                false
            ],
            [
                "/test",
                "/account/",
                301,
                false
            ],
            [
                "/googling",
                "www.google.com",
                302,
                true
            ],
            [
                "/google",
                "/account/",
                302,
                true
            ],
            [
                "/men",
                "/checkout/",
                301,
                false
            ],
            [
                "/women",
                "/checkout?c=5",
                301,
                true
            ],
            [
                "/dummy",
                "/index",
                301,
                false
            ]
        ];
    }

    public function testDisabledRedirects(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2], !$redirect[3]);
    }
}
