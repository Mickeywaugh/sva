<?php

namespace App\Entity\System;

use App\Entity\EntityBase;
use App\Repository\System\SysDeptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysDeptRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysDept extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column]
    private int $parentId = 0;

    #[ORM\Column(nullable: true)]
    private ?int $sort = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $status = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private ?int $isDeleted = 0;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $createBy = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $updateBy = null;

    #[ORM\ManyToOne(targetEntity: SysDept::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: false, options: ['default' => 0])]
    private ?SysDept $parent = null;

    #[ORM\OneToMany(targetEntity: SysDept::class, mappedBy: 'parent')]
    private $children;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setParent(?SysDept $parent)
    {
        $this->parent = $parent;
    }

    public function getParent(): ?SysDept
    {
        if ($this->parentId == 0) {
            return null;
        }
        return $this->parent;
    }

    public function getParentId(): int
    {
        return $this->getParent() ? $this->getParent()->getId() : 0;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIsDeleted(): ?int
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?int $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getCreateBy(): ?string
    {
        return $this->createBy;
    }

    public function setCreateBy(?int $createBy): static
    {
        $this->createBy = $createBy;

        return $this;
    }

    public function getUpdateBy(): ?string
    {
        return $this->updateBy;
    }

    public function setUpdateBy(?int $updateBy): static
    {
        $this->updateBy = $updateBy;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "parentId" => $this->getParentId(),
            "status" => $this->getStatus(),
            "sort" => $this->getSort(),
        ];
    }
}
