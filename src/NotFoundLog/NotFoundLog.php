<?php declare(strict_types=1);

namespace Scop\PlatformRedirecter\NotFoundLog;

use Scop\PlatformRedirecter\Redirect\Redirect;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class NotFoundLog extends Entity
{
    use EntityIdTrait;

    protected string $url;
    protected ?string $salesChannelId = null;
    protected ?SalesChannelEntity $salesChannel = null;
    protected int $hitCount = 1;
    protected ?\DateTimeInterface $lastHitAt = null;
    protected ?string $redirectId = null;
    protected ?Redirect $redirect = null;
    protected bool $ignored = false;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    public function setHitCount(int $hitCount): void
    {
        $this->hitCount = $hitCount;
    }

    public function getLastHitAt(): ?\DateTimeInterface
    {
        return $this->lastHitAt;
    }

    public function setLastHitAt(?\DateTimeInterface $lastHitAt): void
    {
        $this->lastHitAt = $lastHitAt;
    }

    public function getRedirectId(): ?string
    {
        return $this->redirectId;
    }

    public function setRedirectId(?string $redirectId): void
    {
        $this->redirectId = $redirectId;
    }

    public function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }

    public function setRedirect(?Redirect $redirect): void
    {
        $this->redirect = $redirect;
    }

    public function isIgnored(): bool
    {
        return $this->ignored;
    }

    public function setIgnored(bool $ignored): void
    {
        $this->ignored = $ignored;
    }
}
