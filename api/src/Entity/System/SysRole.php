<?php

namespace App\Entity\System;

use App\Entity\EntityBase;
use App\Repository\System\SysRoleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysRoleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysRole extends EntityBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?int $sort = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $status = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $dataScope = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private ?int $isDeleted = 0;


    #[ORM\ManyToMany(targetEntity: SysMenu::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'sys_role_menu')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'menu_id', referencedColumnName: 'id')]
    private $menus;

    // 多对多单向关联
    #[ORM\ManyToMany(targetEntity: SysUser::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: "sys_user_role")]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private $users;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
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
        if ($this->isDeleted) {
            return 0;
        } else {
            return $this->status;
        }
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDataScope(): ?int
    {
        return $this->dataScope;
    }

    public function setDataScope(?int $dataScope): static
    {
        $this->dataScope = $dataScope;

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

    public function setMenus($menus): static
    {
        $this->menus = $menus;
        return $this;
    }

    public function getMenus()
    {
        return $this->menus;
    }

    public function getMenuIds(): array
    {
        $menuIds = [];
        foreach ($this->getMenus() as $menu) {
            $menuIds[] = $menu->getId();
        }
        return $menuIds;
    }

    public function getPermissions(): array
    {
        $permissions = [];
        $menus = $this->getMenus();
        foreach ($menus as $menu) {
            try {
                if ($menu->getPerm()) {
                    array_push($permissions, $menu->getPerm());
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $permissions;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'sort' => $this->sort,
            'status' => $this->status,
            'dataScope' => $this->getDataScope(),
            'isDeleted' => $this->isDeleted
        ];
    }
}
