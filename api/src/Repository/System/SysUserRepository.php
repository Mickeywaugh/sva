<?php

namespace App\Repository\System;

use App\Entity\System\SysUser;
use App\Repository\BaseRepository;
use App\Service\BaseService as Util;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysUserRepository extends BaseRepository
{
    private $deptRepo;
    private $roleRepo;

    public function __construct(ManagerRegistry $register, SysDeptRepository $_deptRepo, SysRoleRepository $_roleRepo)
    {
        parent::__construct($register);
        $this->deptRepo = $_deptRepo;
        $this->roleRepo = $_roleRepo;
    }

    /**
     * 实现抽象方法，返回实体类名
     */
    protected static function getEntityClass(): string
    {
        return SysUser::class; // 替换为你的实体类名
    }
    /**F
     * 获取用户信息
     * @param array $filters
     * @return array
     */
    public function get(array $filters): array|null
    {
        return $this->findOneBy($filters)->toArray();
    }

    /**
     * 获取用户列表
     * @param array $filters
     * @return array
     */
    public function list(array $filter = [], array $names = [], $orders = ['id' => 'DESC']): array
    {
        if (isset($filter['startTime'])) {
            $filter[] = ["createTime", "BETWEEN", ["{$filter['startTime']} 00:00:00", "{$filter['endTime']} 23:59:59"]];
            unset($filter['startTime'], $filter['endTime']);
        }
        return parent::list($filter, $orders);
    }

    public function create($data)
    {
        $user = new SysUser();
        try {
            if (isset($data["username"])) {
                $findUser = $this->findOneBy(['username' => $data['username']]);
                if ($findUser) {
                    throw new \Exception("用户名已存在");
                }
            }

            if (isset($data["dept"])) {
                $dept = $this->deptRepo->find($data['dept']);
                if ($dept) {
                    $data["dept"] = $dept;
                }
            }

            // 获取role ids，并创建role集合，将集合赋值给user->role
            if (isset($data["roleIds"]) && is_array($data["roleIds"])) {
                $roles = [];
                foreach ($data["roleIds"] as $roleId) {
                    $role = $this->roleRepo->find($roleId);
                    if ($role) {
                        $roles[] = $role;
                    }
                }
                $data["role"] = $roles;
                unset($data['roleIds']);
            }
            //设置初始密码
            $data["password"] = Util::$INITIALPASSWORD;
            $user = parent::create($data);
            return $user;
        } catch (\Exception $e) {
            Util::log($e->getMessage());
            return false;
        }
    }

    public function updateUser($id, $data)
    {
        $id = $id ?: $data['id'];
        try {
            // 更新部门
            if (isset($data["dept"])) {
                $dept = $this->deptRepo->find($data['dept']);
                if ($dept) {
                    $data["dept"] = $dept;
                }
            }

            // 获取role ids，并创建role集合，将集合赋值给user->role
            if (isset($data["roleIds"]) && is_array($data["roleIds"])) {
                $roles = [];
                foreach ($data["roleIds"] as $roleId) {
                    $role = $this->roleRepo->find($roleId);
                    if ($role) {
                        $roles[] = $role;
                    }
                }
                $data["role"] = $roles;
            }
            return parent::update($id, $data);
        } catch (\Exception $e) {
            Util::log($e->getMessage());
            return false;
        }
    }

    public function resetPassword($id, $password)
    {
        $updatedUser = $this->update($id, ["password" => $password]);
        if ($updatedUser) {
            return true;
        } else {
            return true;
        }
        return true;
    }
}
