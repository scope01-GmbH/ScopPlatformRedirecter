<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class AbsoluteURLRedirectsTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "https://" . $this->host . "/wau",
                "/Aerodynamic-Aluminum-Wordlobster/7958b4c7e4f74220981f091454b2484e",
                302,
                true
            ],
            [
                "/details",
                "https://" . $this->host . "/checkout/cart/",
                301,
                true
            ],
            [
                "https://" . $this->host . "/test",
                "https://" . $this->host . "/account/",
                301,
                true
            ],
            [
                "https://" . $this->host . "/googling",
                "www.google.com",
                302,
                true
            ],
            [
                "/google",
                "https://" . $this->host . "/account/",
                302,
                true
            ],
            [
                "https://" . $this->host . "/men",
                "https://" . $this->host . "/checkout/",
                301,
                true
            ],
            [
                "https://" . $this->host . "/women",
                "/checkout?c=5",
                301,
                true
            ],
            [
                "https://" . $this->host . "/dummy",
                "https://" . $this->host . "/index",
                301,
                true
            ]
        ];
    }

    public function testAbsoluteURLRedirects(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2]);
    }
}
