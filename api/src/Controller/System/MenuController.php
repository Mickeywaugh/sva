<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Service\AuthService;
use App\Repository\System\SysMenuRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('menus')]
class MenuController extends BaseController
{
    private $authService;
    private $menuRepo;
    public function __construct(AuthService $_authService, SysMenuRepository $_MenuRepository)
    {
        $this->authService = $_authService;
        $this->menuRepo = $_MenuRepository;
    }

    #[Route('', name: 'menus.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        try {
            if ($data["parentId"] > 0) {
                $parent = $this->menuRepo->find($data["parentId"]);
                $data["parent"] = $parent;
                unset($data["parentId"]);
            } else {
                $data["parent"] = null;
            }
            $menu = $this->menuRepo->create($data);
            if ($menu) {
                return $this->success(data: $menu->toArray());
            } else {
                return $this->error("Failed");
            }
        } catch (\Exception $e) {
            return $this->error("Failed:" . $e->getMessage());
        }
    }

    #[Route('', name: 'menus.list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $menuList = [];
        try {
            $menuList = $this->menuRepo->getList();
            return $this->success(data: $menuList);
        } catch (\Exception $e) {
            return $this->error("Failed:" . $e->getMessage());
        }
    }


    //获取用户路由树,不包含按钮权限
    #[Route('/routes', name: 'menus.routes', methods: ['GET'])]
    public function routes(): JsonResponse
    {
        $currUser = $this->authService->getCurrentUser();
        if ($currUser) {
            try {
                //将当前用户id传入，获取用户菜单
                $data = $this->menuRepo->getMenuTree($currUser->getId());
                return $this->success(data: $data);
            } catch (\Exception $e) {
                return $this->error("Failed:" . $e->getMessage());
            }
        } else {
            return $this->error("用户认证令牌已失效");
        }
    }

    // 获取菜单树,包括菜单树和按钮权限
    #[Route('/options', name: 'menus.options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $menuTree = [];
        try {
            $menuTree = $this->menuRepo->getTree(0, false, 0);
            return $this->success(data: $menuTree);
        } catch (\Exception $e) {
            return $this->error("Failed:" . $e->getMessage());
        }
    }

    // 获取菜单树,包括菜单树和按钮权限
    #[Route('/menuOptions', name: 'menus.options4', methods: ['GET'])]
    public function options4(): JsonResponse
    {
        $menuTree = [];
        try {
            $menuTree = $this->menuRepo->getTree(0, false, 4);
            return $this->success(data: $menuTree);
        } catch (\Exception $e) {
            return $this->error("Failed:" . $e->getMessage());
        }
    }

    #[Route('/{id}/form', name: 'menus.get', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $menu = $this->menuRepo->findOneBy(['id' => $id]);
        if ($menu) {
            return $this->success($menu->toArray());
        } else {
            return $this->error("Failed");
        }
    }

    #[Route('/{id}', name: 'menus.delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        try {
            if ($this->menuRepo->delete([$id])) {
                return $this->success();
            } else {
                return $this->error("Failed");
            }
        } catch (\Exception $e) {
            return $this->error("Failed:" . $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'menus.update', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }

        $menu = $this->menuRepo->update($id, $data);
        if ($menu) {
            return $this->success($menu->toArray());
        } else {
            return $this->error("Failed");
        }
    }

    #[Route('/{id}/status', name: 'menus.setStatus', methods: ['PUT'])]
    public function setStatus(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $dept = $this->menuRepo->update($id, $data);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("Failed");
        }
    }
}
