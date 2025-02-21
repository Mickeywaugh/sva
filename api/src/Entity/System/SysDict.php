<?php

namespace App\Entity\System;

use App\Entity\EntityBase;
use App\Repository\System\SysDictRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity(repositoryClass: SysDictRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysDict extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 64)]
    private ?string $dictCode = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $status = null;

    #[ORM\Column(length: 255)]
    private ?string $remark = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $isNumber = 1;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $isDeleted = null;

    #[ORM\OneToMany(targetEntity: SysDictData::class, mappedBy: 'dictCode', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'dict_code', referencedColumnName: 'id', nullable: true)]
    private $dictData;

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

    public function getDictCode(): ?string
    {
        return $this->dictCode;
    }

    public function setDictCode(?string $dictCode): static
    {
        $this->dictCode = $dictCode;

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

    public function getIsNumber(): ?int
    {
        return $this->isNumber;
    }

    public function setIsNumber(int $isNumber): static
    {
        $this->isNumber = $isNumber;

        return $this;
    }

    public function setRemark(?string $remark): static
    {
        $this->remark = $remark;

        return $this;
    }

    public function setIsDeleted(int $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function getIsDeleted(): ?int
    {
        return $this->isDeleted;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getDictData(): ?PersistentCollection
    {
        return $this->dictData;
    }

    public function setDictData(?SysDictData $dictData): static
    {
        $this->dictData = $dictData;

        return $this;
    }

    public function getDictDataArray(): array
    {
        $retArray = [];
        foreach ($this->getDictData() as $dictData) {
            $retArray[] = $dictData->toArray();
        }
        return ["dictDataList" => $retArray];
    }

    public function toArray(array $splices = []): array
    {
        $retArray = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            "dictCode" => $this->getdictCode(),
            'status' => $this->getStatus(),
            'remark' => $this->getRemark(),
            'isNumber' => $this->getIsNumber(),
            'createTime' => $this->getCreateTime(),
            'updateTime' => $this->getUpdateTime(),
            'isDeleted' => $this->getIsDeleted()
        ];
        return $this->spliceArray($retArray, $splices);
    }
}
