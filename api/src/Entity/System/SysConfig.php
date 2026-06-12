<?php

namespace App\Entity\System;

use App\Service\BaseService as Util;
use App\Entity\BaseEntity;
use App\Entity\Traits\DeleteTime;
use App\Service\DbalService;
use App\Repository\System\SysConfigRepository;
use App\Service\DbService;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysConfigRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysConfig extends BaseEntity
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

    use DeleteTime;

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


    // 获取
    public static function get(string $key): string
    {
        return DbService::table('sys_config')->wheres(["config_key" => $key, "delete_time" => ["NULL" => NULL]])->getValue("config_value");
    }

    // 更新
    public static function set(string $key, string $value)
    {
        return DbService::table('sys_config')->wheres(["config_key" => $key])->update(["config_value" => $value, "update_time" => (new \DateTime())->format("Y-m-d H:i:s")]);
    }

    // 创建
    public static function create(string $key, string $value = "")
    {
        return DbService::table('sys_config')->insert([
            "config_key" => $key,
            "config_value" => $value,
            "create_time" => (new \DateTime())->format("Y-m-d H:i:s")
        ]);
    }

    // 删除
    public static function delete(string $key)
    {
        return DbService::table('sys_config')->wheres(["config_key" => $key])->delete();
    }

    public static function __callStatic(string $method, array $arguments)
    {
        return self::{$method}(...$arguments);
    }
}
