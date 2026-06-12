<?php

namespace App\Entity\System;

use App\Service\BaseService as Util;
use App\Entity\System\SysRole;
use App\Entity\System\SysDept;
use App\Entity\BaseEntity;
use App\Entity\Traits\DeleteTime;
use App\Repository\System\SysUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Table(name: 'sys_user')]
#[ORM\Entity(repositoryClass: SysUserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysUser extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $deptId = null;

    #[ORM\ManyToOne(targetEntity: SysDept::class)]
    #[ORM\JoinColumn(name: 'dept_id', referencedColumnName: 'id', nullable: true)]
    private ?SysDept $dept;

    use DeleteTime;

    /**
     * 多对多关联角色
     * @var Collection<int,SysRole>;
     */
    #[ORM\ManyToMany(targetEntity: SysRole::class, cascade: ['persist'])]
    #[ORM\JoinTable(
        name: 'sys_user_role',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    )]
    private $userRoles;

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
        // Logger::log("输入密码:" . $password . "动态hash密码：" . password_hash($password, PASSWORD_DEFAULT) . ",数据库中的" . $this->password);
        return password_verify($password, $this->password);
    }

    public function setDept(?SysDept $dept): self
    {
        $this->dept = $dept;
        return $this;
    }

    public function getDept(): ?SysDept
    {
        return $this->dept;
    }

    public function getDeptId(): ?int
    {
        return $this->deptId;
    }

    public function setDeptId(?int $deptId): self
    {
        $this->deptId = $deptId;
        return $this;
    }

    public function getDeptName(): ?string
    {
        return $this->getDept() ? $this->getDept()->getName() : "";
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatarBase64): static
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
        return $this->status;
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

    public function setUserRoles(Collection $roles): static
    {

        $this->userRoles = $roles;
        return $this;
    }

    /**
     * @return Collection<int,SysRole>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array
    {
        // guarantees that a user always has at least one role for security
        if ($this->userRoles->isEmpty()) return ["ROLE_GUEST"];
        $roles = [];
        foreach ($this->userRoles as $role) {
            $roles[] = $role->getCode();
        }

        return array_unique($roles);
    }

    public function getRoleIds(): array
    {
        $roles = [];
        if ($this->userRoles->isEmpty()) return $roles;

        foreach ($this->userRoles as $role) {
            $roles[] = $role->getId();
        }

        return array_unique($roles);
    }

    public function getFlatMenus(): array
    {
        $menuRoutes = [];
        if ($this->userRoles->isEmpty()) return $menuRoutes;
        foreach ($this->userRoles as $role) {
            foreach ($role->getMenus() as $menu) {
                $menuRoutes[] = $menu->getId();
            }
        }
        return $menuRoutes;
    }

    public function getRolesName(): string
    {
        if ($this->userRoles->isEmpty()) return "";
        $roles = [];
        foreach ($this->userRoles as $role) {
            $roles[] = $role->getName();
        }

        return implode(",", array_unique($roles));
    }

    public function getPermissions(): array
    {
        $permissions = [];
        if ($this->userRoles->isEmpty()) return $permissions;
        foreach ($this->userRoles as $role) {
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


    public function getTimeArray(): array
    {
        return [
            'createTime' => $this->getCreateTime(),
            'updateTime' => $this->getCreateTime(),
        ];
    }

    public function getTopics(): array
    {
        return [
            "user.{$this->getId()}.notices",
        ];
    }

    public function getUserInfo(): array
    {
        return [
            "username" => $this->getUsername(),
            "nickname" => $this->getNickname(),
            'gender' => $this->gender,
            "avatar" => $this->getAvatar(),
            "roles" => $this->getRoles(),
            "perms" => $this->getPermissions(),
            "sseTopics" => $this->getTopics()
        ];
    }

    public function toArray(array $splices = []): array
    {
        $retArray = $this->getUserInfo() + [
            'id' => $this->id,
            'mobile' => $this->mobile,
            'status' => $this->getStatus(),
            'email' => $this->email,
            'dept' => $this->getDeptId(),
            'roleIds' => $this->getRoleIds(),
            'rolesNames' => $this->getRolesName(),
            'deptName' => $this->getDeptName()
        ];

        return $this->mergeArray($retArray, $splices);
    }
}
