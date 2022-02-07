<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class RedirectsWithQueryParametersTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "/details?city=frankfurt",
                "/checkout/cart?city=frankfurt",
                301,
                true
            ],
            [
                "/test?hallo",
                "/account/",
                301,
                true
            ],
            [
                "/googling?key=value",
                "www.google.com?key=value",
                302,
                true
            ],
            [
                "/google?lang=de",
                "/de/account/",
                302,
                true
            ],
            [
                "/men?test",
                "/checkout/",
                301,
                true
            ],
            [
                "/women?test",
                "/checkout?c=5",
                301,
                true
            ],
            [
                "/dummy?name=dummy",
                "/index",
                301,
                true
            ]
        ];
    }

    public function testRedirectsWithQueryParameters(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2]);
    }

    public function testRedirectsWithQueryParametersCallingWithoutThoseParameters(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect(explode('?', $redirect[0])[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2], true);
    }
}
