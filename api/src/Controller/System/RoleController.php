<?php

namespace App\Controller\System;


use App\Controller\BaseController;
use App\Repository\System\SysRoleRepository;
use App\Repository\System\SysMenuRepository;
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
        $data = $this->roleRepo->page($params);
        return $this->success($data);
    }

    #[Route('/options', name: 'option', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $data = $this->roleRepo->getTree();
        return $this->success($data);
    }

    #[Route('/{id}/form', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $role = $this->roleRepo->findOneBy(['id' => $id]);
        if ($role) {
            return $this->success($role->toArray());
        } else {
            return $this->error("获取角色数据失败");
        }
    }


    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }

        $role = $this->roleRepo->create($data);
        if ($role) {
            return $this->success($role->toArray());
        } else {
            return $this->error("角色创建失败");
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $role = $this->roleRepo->update($id, $data);
        if ($role) {
            return $this->success($role->toArray());
        } else {
            return $this->error("更新失败");
        }
    }

    #[Route('/{id}/status', name: 'setStatus', methods: ['PUT'])]
    public function setStatus(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $dept = $this->roleRepo->update($id, $data);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("更新失败");
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
    public function menus(int $id, Request $request): JsonResponse
    {
        $menuIds = $request->toArray();
        if (empty($menuIds) || empty($id)) {
            return $this->error("参数错误");
        }
        try {
            $menus = [];
            foreach ($menuIds as $menuId) {
                $menu = $this->menuRepo->find($menuId);
                if (!$menu) {
                    continue;
                } else {
                    $menus[] = $menu;
                }
            }
            $role = $this->roleRepo->update($id, ["menus" => $menus]);
            return $this->success($role->toArray());
        } catch (\Exception $e) {
            return $this->critical($e->getMessage());
        }
    }
}
