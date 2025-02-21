<?php

namespace App\Entity\Traits;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait EntityTime
{

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeInterface $createTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updateTime = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (property_exists($this, 'createTime')) {
            $this->setCreateTime();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        if (property_exists($this, 'updateTime')) {
            $this->setUpdateTime();
        }
    }

    public function getCreateTime(): ?string
    {
        if (property_exists($this, 'createTime')) {
            return $this->createTime ? $this->createTime->format('Y-m-d H:i:s') : null;
        } else {
            return null;
        }
    }

    public function setCreateTime(?\DateTimeInterface $createTime = new DateTimeImmutable()): static
    {
        $this->createTime = $createTime;
        return $this;
    }
    public function getUpdateTime(): ?string
    {
        if (property_exists($this, 'updateTime')) {
            return $this->updateTime ? $this->updateTime->format('Y-m-d H:i:s') : null;
        } else {
            return null;
        }
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime = new DateTimeImmutable()): static
    {
        $this->updateTime = $updateTime;
        return $this;
    }
}
