<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class AbsoluteURLWithLanguageRedirectsTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "https://" . $this->hostWithLanguage . "/wau",
                "/Aerodynamic-Aluminum-Wordlobster/7958b4c7e4f74220981f091454b2484e",
                302,
                true
            ],
            [
                "/details",
                "https://" . $this->hostWithLanguage . "/checkout/cart/",
                301,
                true
            ],
            [
                "https://" . $this->hostWithLanguage . "/test",
                "https://" . $this->hostWithLanguage . "/account/",
                301,
                true
            ],
            [
                "https://" . $this->hostWithLanguage . "/googling",
                "www.google.com",
                302,
                true
            ],
            [
                "/google",
                "https://" . $this->hostWithLanguage . "/account/",
                302,
                true
            ],
            [
                "https://" . $this->hostWithLanguage . "/men",
                "https://" . $this->hostWithLanguage . "/checkout/",
                301,
                true
            ],
            [
                "https://" . $this->hostWithLanguage . "/women",
                "/checkout?c=5",
                301,
                true
            ],
            [
                "https://" . $this->hostWithLanguage . "/dummy",
                "https://" . $this->hostWithLanguage . "/index",
                301,
                true
            ]
        ];
    }

    public function testAbsoluteURLWithLanguageRedirects(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2]);
    }
}
