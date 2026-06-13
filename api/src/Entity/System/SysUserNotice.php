<?php

namespace App\Entity\System;

use App\Entity\BaseEntity;
use App\Repository\System\SysUserNoticeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysUserNoticeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysUserNotice extends BaseEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $noticeId = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $isRead = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $readTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNoticeId(): ?int
    {
        return $this->noticeId;
    }

    public function setNoticeId(int $noticeId): static
    {
        $this->noticeId = $noticeId;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getIsRead(): ?int
    {
        return $this->isRead;
    }

    public function setIsRead(int $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getReadTime(): ?\DateTimeInterface
    {
        return $this->readTime;
    }

    public function setReadTime(?\DateTimeInterface $readTime): static
    {
        $this->readTime = $readTime;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'noticeId' => $this->getNoticeId(),
            'readTime' => $this->getReadTime()
        ];
    }
}
