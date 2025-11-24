<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\MessageQueue\Handler;

use GuzzleHttp\Psr7\Request;
use Scop\PlatformRedirecter\MessageQueue\Message\CheckRedirectMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CheckRedirectHandler
{
    public function __invoke(CheckRedirectMessage $message)
    {

        $redirect = $message->getRedirect();
        $sourceUrl = $redirect->getSourceURL();

        $request = new Request(
            'get',
            $sourceUrl
        );


    }
}