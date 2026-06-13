<?php

namespace App\Entity\System;

use App\Entity\BaseEntity;
use App\Repository\System\SysDeptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysDeptRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysDept extends BaseEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $code = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(type: Types::BIGINT, options: ['default' => 0])]
    private int $parentId = 0;

    #[ORM\Column(nullable: true)]
    private ?int $sort = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $status = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $treePath = null;

    #[ORM\ManyToOne(targetEntity: SysDept::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: false, options: ['default' => 0])]
    private ?SysDept $parent = null;

    /**
     * @var Collection<int,SysDept>
     */
    #[ORM\OneToMany(targetEntity: SysDept::class, mappedBy: 'parent')]
    private ?Collection $children;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
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

    public function setParent(?SysDept $parent): static
    {
        $this->parent = $parent;
        $this->setTreePath();
        return $this;
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

    public function setParentId(int $parentId): static
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getChildren(): ?Collection
    {
        return $this->children ?: new ArrayCollection();
    }

    public function setTreePath(): static
    {   //
        if ($this->parentId == 0) {
            $treePathArray = [0];
        } else {
            $treePathArray = $this->parent->getTreePath() + [$this->parent->getId()];
        }
        $this->treePath = implode(",", $treePathArray);

        return $this;
    }

    public function getTreePath(): ?array
    {
        return explode(",", $this->parentId == 0 ? "0" : $this->treePath);
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

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "code" => $this->getCode(),
            "name" => $this->getName(),
            "parentId" => $this->getParentId(),
            "status" => $this->getStatus(),
            "sort" => $this->getSort(),
        ];
    }
}
