<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysRoleRepository;
use App\Repository\System\SysMenuRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('roles')]
class RoleController extends BaseController
{
    private $roleRepo;
    private $menuRepo;
    public function __construct(SysRoleRepository $_roleRepo, SysMenuRepository $_menuRepo)
    {
        $this->roleRepo = $_roleRepo;
        $this->menuRepo = $_menuRepo;
    }

    #[Route('/page', name: 'roles.pages', methods: ['POST'])]
    public function list(Request $request): JsonResponse
    {
        $params = $request->toArray();
        $keywords = $params['keywords'] ?? '';
        if (!empty($keywords)) {
            $params['name'] = "%$keywords%";
        }
        $params['isDeleted'] = 0; //获取未删除角色
        $data = $this->roleRepo->page($params);
        return $this->success($data);
    }

    #[Route('/options', name: 'roles.option', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $data = $this->roleRepo->getTree();
        return $this->success($data);
    }

    #[Route('/{id}/form', name: 'roles.get', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $role = $this->roleRepo->findOneBy(['id' => $id]);
        if ($role) {
            return $this->success($role->toArray());
        } else {
            return $this->error("获取角色数据失败");
        }
    }


    #[Route('', name: 'roles.create', methods: ['POST'])]
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

    #[Route('/{id}', name: 'roles.update', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
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

    #[Route('/{id}/status', name: 'roles.setStatus', methods: ['PUT'])]
    public function setStatus(Request $request, $id): JsonResponse
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

    #[Route('/{ids}', name: 'roles.delete', methods: ['DELETE'])]
    public function delete($ids): JsonResponse
    {
        $result = $this->roleRepo->delete(explode(",", $ids));
        if ($result) {
            return $this->success(["ids" => $result]);
        } else {
            return $this->error("删除失败");
        }
    }

    #[Route('/{id}/menuIds', name: 'roles.menuIds', methods: ['GET'])]
    public function menuIds($id): JsonResponse
    {
        $role = $this->roleRepo->find($id);
        if ($role) {
            $roleMenu = $role->getMenuIds();
            return $this->success($roleMenu);
        } else {
            return $this->error("获取角色数据失败");
        }
    }

    #[Route('/{id}/menus', name: 'roles.menus', methods: ['PUT'])]
    public function menus(Request $request, $id): JsonResponse
    {
        $menuIds = $request->toArray();
        if (empty($menuIds) || empty($id)) {
            return $this->error("参数错误");
        }
        try {
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
            return $this->error($e->getMessage());
        }
    }
}
