<?php

namespace App\Entity\System;

use App\Entity\BaseEntity;
use App\Repository\System\SysDictRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;;

#[ORM\Entity(repositoryClass: SysDictRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysDict extends BaseEntity
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

    /**
     * @var Collection<int,SysDictData>
     */
    #[ORM\OneToMany(targetEntity: SysDictData::class, mappedBy: 'dict', cascade: ['persist', 'remove'])]
    private ?Collection $dictData;
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

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    /**
     * @return ?Collection<int,SysDictData>
     */
    public function getDictData(): ?Collection
    {
        return $this->dictData ?: new ArrayCollection();
    }

    public function setDictData(?SysDictData $dictData): static
    {
        $this->dictData = $dictData;

        return $this;
    }

    public function getDictOptions(): array
    {
        $retArray = [];
        foreach ($this->getDictData() as $dictData) {
            $retArray[] = ["value" => $dictData->getValue(), "label" => $dictData->getLabel()];
        }
        return $retArray;
    }

    public function toArray(array $splices = []): array
    {
        $retArray = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            "dictCode" => $this->getDictCode(),
            'status' => $this->getStatus(),
            'remark' => $this->getRemark(),
            'isNumber' => $this->getIsNumber(),
            'createTime' => $this->getCreateTime(),
            'updateTime' => $this->getUpdateTime()
        ];
        return $this->mergeArray($retArray, $splices);
    }
}
