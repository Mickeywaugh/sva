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

    // 获取菜单树状结构,返回结构为实体树
    public function getTree(?SysMenu $pnode = null, bool $retObj = true, int $without = 0): array|ArrayCollection
    {
        $treeMenus = new ArrayCollection();
        $treeMenusArr = [];
        $menuList = $this->findBy(["parent" => $pnode], ["sort" => "ASC"]); //["visible" => 1]
        if (!$menuList) return $retObj ? $treeMenus : $treeMenusArr;
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
            if ($user->getUsername() == "root") {
                return $this->getTree(null, false, 4);
            }
            $roles = $user->getRoles();
            $flatMenus = $user->getFlatMenus();
            return $this->getUserMenuTree(null, $flatMenus, $roles);
        } else {
            //返回菜单数组，去掉type=4的btn类型
            return $this->getTree(null, false, 4);
        }
    }

    // 获取用户路由树
    public function getUserMenuTree(?SysMenu $pnode, array $flatMenus, array $roles)
    {
        $userMenuTree = [];
        $menus = $this->getTree($pnode, true, 4);
        if (!$menus) return $userMenuTree;
        foreach ($menus as $menu) {
            $id = $menu->getId();
            if (in_array($id, $flatMenus)) {
                $childMenu = $menu->getRoute();
                $childMenu["meta"]["roles"] = $roles;
                $child = $menu->getChildren();
                if ($child) {
                    unset($childMenu["redirect"]);
                    $childMenu["children"] = $this->getUserMenuTree($menu, $flatMenus, $roles);
                }
                $userMenuTree[] = $childMenu;
            }
        }
        return $userMenuTree;
    }
}
