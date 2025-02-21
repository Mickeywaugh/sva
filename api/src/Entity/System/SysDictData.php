<?php

namespace App\Entity\System;

use App\Entity\EntityBase;
use App\Repository\System\SysDictDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysDictDataRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysDictData extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $status = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $tagType = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $sort = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $remark = null;

    #[ORM\ManyToOne(targetEntity: SysDict::class)]
    #[ORM\JoinColumn(name: 'dict_code', referencedColumnName: 'id', nullable: true)]
    private $dictCode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

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

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): static
    {
        $this->remark = $remark;

        return $this;
    }

    public function getTagType(): ?string
    {
        return $this->tagType;
    }

    public function setTagType(string $tagType): static
    {
        $this->tagType = $tagType;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getDictCode(): ?SysDict
    {
        return $this->dictCode;
    }

    public function setDictCode(?SysDict $dictCode): static
    {
        $this->dictCode = $dictCode;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'value' => $this->getValue(),
            'label' => $this->getLabel(),
            'tagType' => $this->getTagType(),
            'sort' => $this->getSort(),
            'dictCode' => $this->getDictCode()?->toArray(),
            'status' => $this->getStatus(),
            'remark' => $this->getRemark(),
        ];
    }
}
