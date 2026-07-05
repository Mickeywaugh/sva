<?php

namespace App\Repository\System;

use App\Entity\System\SysMenu;
use App\Repository\BaseRepository;
use App\Service\Logger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SysMenu>
 *
 * @method SysMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysMenu[]    findAll()
 * @method SysMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysMenuRepository extends BaseRepository
{
    private SysUserRepository $userRepo;
    public function __construct(ManagerRegistry $registry, SysUserRepository $_userRepo)
    {
        parent::__construct($registry);
        $this->userRepo = $_userRepo;
    }

    protected static function getEntityClass(): string
    {
        return SysMenu::class;
    }

    public function getList(?SysMenu $pnode = null)
    {
        $menuTree = [];
        $menuList = $this->findBy(["parent" => $pnode], ["sort" => "ASC"]);
        foreach ($menuList as $menu) {
            $child = $this->getList($menu);
            $data = $menu->toArray();
            if ($child) {
                $data["children"] = $child;
            }
            $menuTree[] = $data;
        }
        return $menuTree;
    }

    /**
     * 获取菜单树状结构
     * @param SysMenu|null $pnode 父节点
     * @param bool $retObj 是否返回对象
     * @param int $without 跳过的菜单类型
     * @return array|ArrayCollection
     */
    public function getTree(?SysMenu $pnode = null, bool $retObj = true, int $without = 0): array|ArrayCollection
    {
        $treeMenus = new ArrayCollection();
        $treeMenusArr = [];
        $menuList = $this->findBy(["parent" => $pnode], ["sort" => "ASC"]); //["visible" => 1]
        foreach ($menuList as $menu) {
            //跳过type =4的btn类型
            if ($menu->getType() == $without) continue;
            $data = [
                "value" => $menu->getId(),
                "label" => $menu->getName()
            ];
            $child = $this->getTree($menu, $retObj, $without);
            if ($child) {
                if ($retObj) {
                    $menu->setChildren($child);
                }
                $data["children"] = $child;
            }
            $treeMenus->add($menu);
            $treeMenusArr[] = $data;
        }
        return $retObj ? $treeMenus : $treeMenusArr;
    }

    /**
     * 获取用户菜单, 用户菜单不包括按钮权限
     * @param int $userId
     * @return array
     */
    public function getMenuTree(int $userId = 0): array
    {
        if ($userId) {
            //返回用户菜单树
            $user = $this->userRepo->find($userId);
            $roles = $user->getRoles();            // 角色为ROOT时返回所有菜单
            if (!$roles) return [];
            if (in_array('ROOT', $user->getRoles())) {
                return $this->getUserMenuTree();
            }
            return $this->getUserMenuTree($user->getFlatMenus());
        } else {
            return $this->getUserMenuTree();
        }
    }

    // 获取用户路由树
    public function getUserMenuTree(?array $flatMenus = null, ?SysMenu $pnode = null)
    {
        $userMenuTree = [];
        // 如果flatMenus为null，则返回空数组
        $menus = $this->getTree($pnode, true, 4);
        foreach ($menus as $menu) {
            if ($flatMenus) {
                $id = $menu->getId();
                if (in_array($id, $flatMenus)) {
                    $childMenu = $menu->getRoute();
                    $child = $menu->getChildren();
                    if ($child) {
                        $childMenu["children"] = $this->getUserMenuTree($flatMenus, $menu);
                    }
                    $userMenuTree[] = $childMenu;
                }
            } else {
                $childMenu = $menu->getRoute();
                $child = $menu->getChildren();
                if ($child) {
                    $childMenu["children"] = $this->getUserMenuTree($flatMenus, $menu);
                }
                $userMenuTree[] = $childMenu;
            }
        }
        return $userMenuTree;
    }
}
