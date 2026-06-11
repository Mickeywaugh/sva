<?php

namespace App\Repository\System;

use App\Entity\System\SysMenu;
use App\Repository\BaseRepository;
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

    public function getList(int $pid = 0)
    {
        $menuTree = [];
        $menuList = $this->resetQueryBuilder()->findBy(["parentId" => $pid], ["sort" => "ASC"]);
        foreach ($menuList as $menu) {
            $child = $this->getList($menu->getId());
            $data = $menu->toArray();
            if ($child) {
                $data["children"] = $child;
            }
            $menuTree[] = $data;
        }
        return $menuTree;
    }

    /**
     *  获取菜单树状结构,
     * @param int $pid 父级ID
     * @param bool $retObj 是否返回对象，默认返回对象
     * @param bool $withBt 是否包含按钮权限，默认不包含
     * @return array|ArrayCollection
     */
    public function getTree(int $pid = 0, bool $retObj = true, bool $withBt = false): array|ArrayCollection
    {
        $filter = ["parentId" => $pid];
        if (!$withBt) {
            $filter["type"] = ["<>" => "B"];
        }
        $menuList = $this->resetQueryBuilder()->findEntities($filter, ["sort" => "ASC"]);

        if ($retObj) {
            $treeMenus = new ArrayCollection();
            foreach ($menuList as $menu) {
                $children = $this->getTree($menu->getId(), true, $withBt);
                if ($children) {
                    $menu->setChildren($children);
                }
                $treeMenus->add($menu);
            }
            return $treeMenus;
        } else {
            $treeMenusArr = [];
            foreach ($menuList as $menu) {
                $data = [
                    "value" => $menu->getId(),
                    "label" => $menu->getName()
                ];
                $children = $this->getTree($menu->getId(), false, $withBt);
                if ($children) {
                    $data["children"] = $children;
                }
                $treeMenusArr[] = $data;
            }
            return $treeMenusArr;
        }
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
                return $this->getTree(0, false);
            }
            $roles = $user->getRoles();
            $flatMenus = $user->getFlatMenus();
            return $this->getUserMenuTree(0, $flatMenus, $roles);
        } else {
            //返回菜单数组，去掉type=B的btn类型
            return $this->getTree(0, false);
        }
    }

    // 获取用户路由树
    public function getUserMenuTree(int $pid,  array $flatMenus, array $roles)
    {
        $userMenuTree = [];
        $menus = $this->getTree($pid, true);
        foreach ($menus as $menu) {
            $id = $menu->getId();
            if (in_array($id, $flatMenus)) {
                $childMenu = $menu->getRoute();
                $childMenu["meta"]["roles"] = $roles;
                $child = $menu->getChildren();
                if ($child) {
                    unset($childMenu["redirect"]);
                    $childMenu["children"] = $this->getUserMenuTree($id, $flatMenus, $roles);
                }
                $userMenuTree[] = $childMenu;
            }
        }
        return $userMenuTree;
    }
}
