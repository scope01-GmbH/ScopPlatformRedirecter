<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Scop\PlatformRedirecter\Test\RedirectTestCase;

class TransferQueryParametersRedirectsTest extends RedirectTestCase
{

    protected function getDatabaseRedirects(): array
    {
        return [
            [
                "/details",
                "/checkout/cart",
                301,
                true,
                2
            ],
            [
                "/test?hallo",
                "/account/",
                301,
                true,
                2
            ],
            [
                "/googling",
                "www.google.com",
                302,
                true,
                2
            ],
            [
                "/google?lang=de",
                "/de/account/",
                302,
                false,
                1
            ],
            [
                "/men",
                "/checkout/",
                301,
                true,
                0
            ],
            [
                "/women",
                "/checkout?c=5",
                301,
                true,
                1
            ],
            [
                "/dummy",
                "/index",
                301,
                false,
                0
            ]
        ];
    }

    // Should redirect if the redirect is enabled
    public function testTransferQueryParametersRedirectsCallingAsTheyAre()
    {
        foreach ($this->getDatabaseRedirects() as $redirect) {
            $target = $redirect[1];
            if ($redirect[4] === 2 && str_contains($redirect[0], '?')) {
                $target .= '?' . explode('?', $redirect[0], 2)[1];
            }
            $this->checkRedirect($redirect[0], [$target, "http://" . $target], $redirect[2], !$redirect[3]);
        }
    }

    // Should redirect if the redirect is enabled and the SourceURL does not contain a query parameter
    public function testRedirectsWithQueryParametersCallingWithoutParameters()
    {
        foreach ($this->getDatabaseRedirects() as $redirect) {
            $target = $redirect[1];
            if ($redirect[4] === 2 && str_contains($redirect[0], '?')) {
                $target .= '?' . explode('?', $redirect[0], 2)[1];
            }
            $this->checkRedirect(explode('?', $redirect[0])[0], [$target, "http://" . $target], $redirect[2], !$redirect[3] || str_contains($redirect[0], '?'));
        }
    }

    // Should redirect if the redirect is enabled and it has ignoreQueryParams enabled and the SourceURL does not contain a query parameter
    public function testRedirectsWithQueryParametersCallingWithOtherParameters()
    {
        foreach ($this->getDatabaseRedirects() as $redirect) {
            $target = $redirect[1];
            if ($redirect[4] === 2) {
                if (str_contains($redirect[0], '?')) {
                    $target .= '?' . explode('?', $redirect[0], 2)[1];
                } else {
                    $target .= '?test=other';
                }
            }
            $this->checkRedirect(explode('?', $redirect[0])[0] . '?test=other', [$target, "http://" . $target], $redirect[2], !$redirect[3] || $redirect[4] === 0 || str_contains($redirect[0], '?'));
        }
    }
}
