<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types=1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Redirect;

use phpDocumentor\Reflection\Types\Boolean;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class Redirect extends Entity
{
    use EntityIdTrait;

    /**
     * @var String $sourceURL
     * @var String $targetURL
     * @var int $httpCode
     * @var boolean $enabled
     * @var boolean $ignoreQueryParams
     */
    protected $sourceURL;
    protected $targetURL;
    protected $httpCode;
    protected $enabled;
    protected $ignoreQueryParams;

    /**
     * @return string
     */
    public function getSourceURL(): string
    {
        return $this->sourceURL;
    }

    /**
     * @return string
     */
    public function getTargetURL(): string
    {
        return $this->targetURL;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isIgnoreQueryParams(): bool
    {
        return $this->ignoreQueryParams;
    }

    /**
     * @param String $sourceURL
     */
    public function setSourceURL(string $sourceURL): void
    {
        $this->sourceURL = $sourceURL;
    }

    /**
     * @param String $targetURL
     */
    public function setTargetURL(string $targetURL): void
    {
        $this->targetURL = $targetURL;
    }

    /**
     * @param int $httpCode
     */
    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled(boolean $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @param boolean $ignoreQueryParams
     */
    public function setIgnoreQueryParams(boolean $ignoreQueryParams): void
    {
        $this->ignoreQueryParams = $ignoreQueryParams;
    }
}
