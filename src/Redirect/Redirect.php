<?php
declare(strict_types = 1);
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

    public function setSourceURL(String $sourceURL): void
    {
        $this->sourceURL = $sourceURL;
    }

    public function setTargetURL(String $targetURL): void
    {
        $this->targetURL = $targetURL;
    }

    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }
}

