<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\MessageQueue\Message;

use Scop\PlatformRedirecter\Redirect\Redirect;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

class CheckRedirectMessage implements AsyncMessageInterface
{
    private Redirect $redirect;

    public function __construct(Redirect $redirect)
    {
        $this->redirect = $redirect;
    }

    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }
}