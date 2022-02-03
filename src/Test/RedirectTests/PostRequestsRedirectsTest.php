<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class PostRequestsRedirectsTest extends RedirectTestCase
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
                true
            ],
            [
                "/test",
                "/account/",
                301,
                true
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
                true
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
                true
            ]
        ];
    }

    public function testPostRequestRedirects(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2], false, 'POST');
    }
}
