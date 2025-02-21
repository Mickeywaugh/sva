<?php

namespace App\Entity\System;

use App\Service\BaseService as Util;
use App\Entity\EntityBase;
use App\Repository\System\SysConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysConfigRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysConfig extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $configName = null;

    #[ORM\Column(length: 50)]
    private ?string $configKey = null;

    #[ORM\Column(length: 255)]
    private ?string $configValue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $remark = null;

    #[ORM\Column]
    private ?bool $isDeleted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfigName(): ?string
    {
        return $this->configName;
    }

    public function setConfigName(string $configName): static
    {
        $this->configName = $configName;

        return $this;
    }

    public function getConfigKey(): ?string
    {
        return $this->configKey;
    }

    public function setConfigKey(string $configKey): static
    {
        $this->configKey =  strtoupper(Util::toSnakeCase($configKey));

        return $this;
    }

    public function getConfigValue(): ?string
    {
        return $this->configValue;
    }

    public function setConfigValue(string $configValue): static
    {
        $this->configValue = $configValue;

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


    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'configName' => $this->configName,
            'configKey' => $this->configKey,
            'configValue' => $this->configValue,
            'remark' => $this->remark
        ];
    }
}
