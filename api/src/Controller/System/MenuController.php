<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Entity\System\SysMenu;
use App\Repository\System\SysMenuRepository;
use App\Service\AuthService;
use App\Service\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/menus', name: 'system.menu.')]
class MenuController extends BaseController
{
    private SysMenuRepository $menuRepo;
    public function __construct(SysMenuRepository $_MenuRepository, AuthService $_authService)
    {
        parent::__construct($_authService);
        $this->menuRepo = $_MenuRepository;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        try {
            $menu = SysMenu::create($data);
            $menu = $this->menuRepo->flush($menu);
            Logger::log("MenuController.create", $menu->toArray());
            if ($menu) {
                return $this->success($menu->toArray());
            } else {
                return $this->error("Failed");
            }
        } catch (\Exception $e) {
            return $this->critical("Failed:" . $e->getMessage());
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $menuList = [];
        try {
            $menuList = $this->menuRepo->getList();
            return $this->success($menuList);
        } catch (\Exception $e) {
            return $this->critical("Failed:" . $e->getMessage());
        }
    }

    //获取用户路由树,不包含按钮权限
    #[Route('/routes', name: 'routes', methods: ['GET'])]
    public function routes(): JsonResponse
    {
        $currUser = $this->currUser;
        if ($currUser) {
            try {
                //将当前用户id传入，获取用户菜单
                $data = $this->menuRepo->getMenuTree($currUser->getId());
                return $this->success($data);
            } catch (\Exception $e) {
                return $this->critical("Failed:" . $e->getMessage());
            }
        } else {
            return $this->error("用户认证令牌已失效");
        }
    }

    // 获取菜单树,包括菜单树和按钮权限
    #[Route('/options', name: 'options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $menuTree = [];
        try {
            $menuTree = $this->menuRepo->getTree(0, false, true);
            return $this->success($menuTree);
        } catch (\Exception $e) {
            return $this->critical("Failed:" . $e->getMessage());
        }
    }

    // 获取菜单树,不包括按钮权限
    #[Route('/menuOptions', name: 'options4', methods: ['GET'])]
    public function optionsWithoutButton(): JsonResponse
    {
        $menuTree = [];
        try {
            $menuTree = $this->menuRepo->getTree(0, false);
            return $this->success($menuTree);
        } catch (\Exception $e) {
            return $this->critical("Failed:" . $e->getMessage());
        }
    }

    #[Route('/{id}/form', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $menu = $this->menuRepo->findOneBy(['id' => $id]);
        if ($menu) {
            return $this->success($menu->toArray());
        } else {
            return $this->error("Failed");
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            if ($this->menuRepo->delete([$id])) {
                return $this->success();
            } else {
                return $this->error("Failed");
            }
        } catch (\Exception $e) {
            return $this->critical("Failed:" . $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
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

    #[Route('/{id}/status', name: 'setStatus', methods: ['PUT'])]
    public function setStatus(int $id, Request $request): JsonResponse
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
