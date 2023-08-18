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
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class Redirect extends Entity
{
    use EntityIdTrait;

    /**
     * @var String $sourceURL
     * @var String $targetURL
     * @var int $httpCode
     * @var boolean $enabled
     */
    protected $sourceURL;
    protected $targetURL;
    protected $httpCode;
    protected $enabled;

    /**
     * @var int $queryParamsHandling
     * 0 = Consider Query Parameters during search<br>
     * 1 = Ignore Query Parameters during search<br>
     * 2 = Ignore Query Parameters during search and add them to the target URL
     */
    protected $queryParamsHandling;

    /**
     * @var string|null $salesChannelId
     */
    protected $salesChannelId;
    /**
     * @var SalesChannelEntity|null $salesChannel
     */
    protected $salesChannel;

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
     * @return int
     */
    public function getQueryParamsHandling(): int
    {
        return $this->queryParamsHandling;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    /**
     * @return SalesChannelEntity|null
     */
    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
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
     * @param int $queryParamsHandling
     */
    public function setQueryParamsHandling(int $queryParamsHandling): void
    {
        $this->queryParamsHandling = $queryParamsHandling;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @param SalesChannelEntity|null $salesChannel
     */
    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }
}
