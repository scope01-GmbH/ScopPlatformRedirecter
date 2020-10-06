<?php
namespace Scop\PlatformRedirecter\Redirect;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class Redirect extends Entity
{

    use EntityIdTrait;

    protected $sourceURL, $targetURL, $httpCode;

    public function getSourceURL(): string
    {
        return $this->sourceURL;
    }

    public function getTargetURL(): string
    {
        return $this->targetURL;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function setSourceURL($sourceURL): void
    {
        $this->sourceURL = $sourceURL;
    }

    public function setTargetURL($targetURL): void
    {
        $this->targetURL = $targetURL;
    }

    public function setHttpCode($httpCode): void
    {
        $this->httpCode = $httpCode;
    }
}

