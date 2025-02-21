<?php

namespace App\Entity\System;

use App\Service\BaseService as Util;
use App\Entity\System\SysRole;
use App\Entity\EntityBase;
use App\Repository\System\SysUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: SysUserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysUser extends EntityBase implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $username = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $gender = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $avatar = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $mobile = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $status = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private ?int $isDeleted = 0;

    #[ORM\Column(length: 32)]
    private ?string $employeeId = null;

    #[ORM\ManyToOne(targetEntity: SysDept::class)]
    #[ORM\JoinColumn(name: 'dept', referencedColumnName: 'id', nullable: true)]
    private $dept;

    // 多对多单向关联
    #[ORM\ManyToMany(targetEntity: SysRole::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: "sys_user_role")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id')]
    private $role;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function setGender(int $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $plantPassword): static
    {
        $this->password = password_hash($plantPassword, PASSWORD_DEFAULT);
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        // Util::log("输入密码:" . $password . "动态hash密码：" . password_hash($password, PASSWORD_DEFAULT) . ",数据库中的" . $this->password);
        return password_verify($password, $this->password);
    }

    public function setDept(?SysDept $dept): static
    {
        $this->dept = $dept;
        return $this;
    }

    public function getDept(): ?SysDept
    {
        return $this->dept;
    }


    public function getDeptName(): ?string
    {
        return $this->getDept() ? $this->getDept()->getName() : "";
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatarBase64): static
    {
        if ($avatarBase64) {
            $filePath = sprintf("userData/avatar/avatar_%s.png", $this->id);
            if (!Util::saveBase64Image($avatarBase64, $filePath)) {
                $this->avatar = $filePath;
            }
        }
        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): static
    {
        $this->mobile = $mobile;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?string $employeeId): static
    {
        $this->employeeId = $employeeId;

        return $this;
    }

    public function setRole($roles): static
    {

        $this->role = $roles;
        return $this;
    }

    public function getRole()
    {
        return $this->role;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array
    {
        // guarantees that a user always has at least one role for security
        if ($this->role->isEmpty()) return ["ROLE_GUEST"];

        foreach ($this->role as $role) {
            $roles[] = $role->getCode();
        }

        return array_unique($roles);
    }

    public function getRoleIds(): array
    {
        $roles = [];
        if ($this->role->isEmpty()) return $roles;

        foreach ($this->role as $role) {
            $roles[] = $role->getId();
        }

        return array_unique($roles);
    }

    public function getFlatMenus(): array
    {
        $menuRoutes = [];
        if ($this->role->isEmpty()) return $menuRoutes;
        foreach ($this->role as $role) {
            foreach ($role->getMenus() as $menu) {
                $menuRoutes[] = $menu->getId();
            }
        }
        return $menuRoutes;
    }

    public function getRolesName(): string
    {
        if ($this->role->isEmpty()) return "";

        foreach ($this->role as $role) {
            $roles[] = $role->getName();
        }

        return implode(",", array_unique($roles));
    }

    public function getPermissions(): array
    {
        $permissions = [];
        if ($this->role->isEmpty()) return $permissions;
        foreach ($this->role as $role) {
            foreach ($role->getPermissions() as $permission) {
                array_push($permissions, $permission);
            }
        }

        return array_unique($permissions);
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function eraseCredentials(): void
    {
        return;
    }

    public function getDeptId(): ?int
    {
        return $this->getDept() ? $this->getDept()->getId() : 0;
    }


    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'gender' => $this->gender,
            'avatar' => $this->avatar,
            'mobile' => $this->mobile,
            'status' => $this->getStatus(),
            'email' => $this->email,
            'dept' => $this->getDeptId(),
            'roleIds' => $this->getRoleIds(),
            'rolesNames' => $this->getRolesName(),
            'deptName' => $this->getDeptName()
        ];
    }
}
