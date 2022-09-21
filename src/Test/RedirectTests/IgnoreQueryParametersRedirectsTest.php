<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class IgnoreQueryParametersRedirectsTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "/details",
                "/checkout/cart",
                301,
                true,
                true
            ],
            [
                "/test?hallo",
                "/account/",
                301,
                true,
                true
            ],
            [
                "/googling",
                "www.google.com",
                302,
                true,
                true
            ],
            [
                "/google?lang=de",
                "/de/account/",
                302,
                false,
                true
            ],
            [
                "/men",
                "/checkout/",
                301,
                true,
                false
            ],
            [
                "/women",
                "/checkout?c=5",
                301,
                true,
                true
            ],
            [
                "/dummy",
                "/index",
                301,
                false,
                false
            ]
        ];
    }

    // Should redirect if the redirect is enabled
    public function testIgnoreQueryParametersRedirectsCallingAsTheyAre(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect($redirect[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2], !$redirect[3]);
    }

    // Should redirect if the redirect is enabled and the SourceURL does not contain a query parameter
    public function testRedirectsWithQueryParametersCallingWithoutParameters(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect(explode('?', $redirect[0])[0], [$redirect[1], "http://" . $redirect[1]], $redirect[2], !$redirect[3] || str_contains($redirect[0], '?'));
    }

    // Should redirect if the redirect is enabled and it has ignoreQueryParams enabled and the SourceURL does not contain a query parameter
    public function testRedirectsWithQueryParametersCallingWithOtherParameters(){
        foreach ($this->getDatabaseRedirects() as $redirect)
            $this->checkRedirect(explode('?', $redirect[0])[0] . '?test=other', [$redirect[1], "http://" . $redirect[1]], $redirect[2], !$redirect[3] || !$redirect[4] || str_contains($redirect[0], '?'));
    }
}
