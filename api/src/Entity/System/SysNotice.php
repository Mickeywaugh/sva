<?php

namespace App\Entity\System;

use App\Entity\EntityBase;
use App\Repository\System\SysNoticeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysNoticeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysNotice extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 63)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $level = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $targetType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $targetUserIds = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $publishStatus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $revokeTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishTime = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $createBy = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $isDeleted = null;

    #[ORM\ManyToOne(targetEntity: SysUser::class)]
    #[ORM\JoinColumn(name: 'publisher_id', referencedColumnName: 'id')]
    private ?SysUser $publisher = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        parent::onPrePersist();
        $this->setPublishStatus();
        $this->setIsDeleted();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getTargetType(): ?int
    {
        return $this->targetType;
    }

    public function setTargetType(int $targetType): static
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetUserIds(): ?string
    {
        return $this->targetUserIds;
    }

    public function setTargetUserIds(?string $targetUserIds): static
    {
        $this->targetUserIds = $targetUserIds;

        return $this;
    }

    public function getPublisher(): ?SysUser
    {
        return $this->publisher;
    }

    public function setPublisher(SysUser $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getPublishStatus(): ?int
    {
        return $this->publishStatus;
    }

    public function setPublishStatus(?int $publishStatus = 0): static
    {
        $this->publishStatus = $publishStatus;
        if ($this->publishStatus === 1) {
            $this->setPublishTime(new \DateTime());
        }
        if ($this->publishStatus === 0) {
            $this->setRevokeTime(new \DateTime());
        }
        return $this;
    }

    public function getRevokeTime(): ?string
    {
        return $this->revokeTime ? $this->revokeTime->format('Y-m-d H:i:s') : null;
    }

    public function setRevokeTime(?\DateTimeInterface $revokeTime): static
    {
        $this->revokeTime = $revokeTime;

        return $this;
    }

    public function getPublishTime(): ?string
    {
        return $this->publishTime ? $this->publishTime->format('Y-m-d H:i:s') : null;
    }

    public function setPublishTime(?\DateTimeInterface $publishTime): static
    {
        $this->publishTime = $publishTime;

        return $this;
    }

    public function getIsDeleted(): ?int
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(int $isDeleted = 0): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function setCreateBy(?string $createBy = null): static
    {
        $this->createBy = $createBy;

        return $this;
    }

    public function getCreateBy(): ?string
    {
        return $this->createBy;
    }

    public function toArray(array $splices = []): array
    {
        $retArray = [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'type' => $this->getType(),
            'level' => $this->getLevel(),
            'targetType' => $this->getTargetType(),
            'content' => $this->getContent(),
            'publishStatus' => $this->getPublishStatus(),
            'revokeTime' => $this->getRevokeTime(),
            'publishTime' => $this->getPublishTime(),
            'createBy' => $this->getCreateBy(),
            'publisherName' => $this->getPublisher()->getUsername(),
            'isDeleted' => $this->getIsDeleted(),
            'createTime' => $this->getCreateTime(),
            'updateTime' => $this->getUpdateTime(),
        ];
        return $this->spliceArray($retArray, $splices);
    }
}
