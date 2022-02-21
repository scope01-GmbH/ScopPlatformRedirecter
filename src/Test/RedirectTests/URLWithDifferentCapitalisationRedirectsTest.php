<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class URLWithDifferentCapitalisationRedirectsTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "https://" . $this->host . "/Dummy",
                "https://" . $this->host . "/dummy",
                301,
                true
            ],
            [
                "https://" . $this->host . "/Product/Super-Product-1000",
                "https://" . $this->host . "/product/super-product-1000",
                302,
                true
            ],
            [
                "/men",
                "/Men",
                301,
                true
            ],
            [
                "/InDeX",
                "/iNdEx",
                302,
                true
            ]
        ];
    }

    public function testAbsoluteURLRedirects(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2]);
    }
}
