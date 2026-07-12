<?php

namespace App\Controller\System;


use App\Controller\BaseController;
use App\Repository\System\SysRoleRepository;
use App\Repository\System\SysMenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/roles', name: 'system.roles.')]
class RoleController extends BaseController
{
    private SysRoleRepository $roleRepo;
    private SysMenuRepository $menuRepo;
    public function __construct(SysRoleRepository $_roleRepo, SysMenuRepository $_menuRepo)
    {
        $this->roleRepo = $_roleRepo;
        $this->menuRepo = $_menuRepo;
    }

    #[Route('/page', name: 'pages', methods: ['POST'])]
    public function list(Request $request): JsonResponse
    {
        $params = $request->toArray();
        $keywords = $params['keywords'] ?? '';
        if (!empty($keywords)) {
            $params['name'] = ["LIKE" => $keywords];
        }
        unset($params['keywords']);
        $data = $this->roleRepo->init()->page($params);
        return $this->success($data);
    }

    #[Route('/options', name: 'option', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $data = $this->roleRepo->getTree();
        return $this->success($data);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $role = $this->roleRepo->findOneBy(['id' => $id]);
        if ($role) {
            return $this->success($role->toArray());
        } else {
            return $this->error("获取角色数据失败");
        }
    }

    #[Route('/{id}', name: 'set', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function set(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        if ($id == 0) {
            unset($data["id"]);
            $role = $this->roleRepo->create($data);
        } else {
            $role = $this->roleRepo->update($id, $data);
        }
        if ($role) {
            return $this->success($role->toArray());
        } else {
            return $this->error("操作失败");
        }
    }


    #[Route('/{ids}', name: 'delete', methods: ['DELETE'], requirements: ['ids' => '\w+'])]
    public function delete(string $ids): JsonResponse
    {
        if (empty($ids)) {
            return $this->error("参数错误");
        }
        $ids = explode(",", $ids);
        $result = $this->roleRepo->delete($ids);
        if ($result) {
            return $this->success(["ids" => $ids]);
        } else {
            return $this->error("删除失败");
        }
    }

    #[Route('/{id}/menuIds', name: 'menuIds', methods: ['GET'])]
    public function menuIds(int $id): JsonResponse
    {
        $role = $this->roleRepo->find($id);
        if ($role) {
            $roleMenu = $role->getMenuIds();
            return $this->success($roleMenu);
        } else {
            return $this->error("获取角色数据失败");
        }
    }

    #[Route('/{id}/menus', name: 'menus', methods: ['PUT'])]
    public function menus(Request $request, int $id): JsonResponse
    {
        $menuIds = $request->toArray();
        if (empty($id)) {
            return $this->error("参数错误");
        }
        try {
            $menus = new ArrayCollection();
            if (!empty($menuIds)) {
                foreach ($menuIds as $menuId) {
                    $menu = $this->menuRepo->find($menuId);
                    if (!$menu) {
                        continue;
                    } else {
                        $menus->add($menu);
                    }
                }
            }
            $role = $this->roleRepo->update($id, ["menus" => $menus]);
            return $this->success($role->toArray());
        } catch (\Exception $e) {
            return $this->critical($e->getMessage());
        }
    }
}
