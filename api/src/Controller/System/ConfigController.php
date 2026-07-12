<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysConfigRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/config', name: 'system.config.')]
class ConfigController extends BaseController
{
  private SysConfigRepository $configRepo;
  public function __construct(SysConfigRepository $_configRepo)
  {
    $this->configRepo = $_configRepo;
  }

  #[Route('/page', name: 'page', methods: ['POST'])]
  public function page(Request $request): JsonResponse
  {
    $params = $request->toArray();
    if (isset($params['keywords']) && $params['keywords']) {
      $params['configName'] = ['LIKE' => $params['keywords']];
    }
    unset($params['keywords']);
    $data = $this->configRepo->init()->page($params);
    return $this->success($data);
  }

  #[Route('', name: 'create', methods: ['POST'])]
  public function create(Request $request): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data)) {
      return $this->error("参数错误");
    }
    $config = $this->configRepo->create($data);
    if ($config) {
      return $this->success($config->toArray());
    } else {
      return $this->error("创建失败");
    }
  }


  // 获取配置数据
  #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
  public function get(int $id): JsonResponse
  {
    if (!$id) {
      return $this->error("参数错误");
    }
    $config = $this->configRepo->find($id);
    if ($config) {
      return $this->success($config->toArray());
    } else {
      return $this->error("获取配置数据失败");
    }
  }

  //新增或修改配置数据
  #[Route('/{id}', name: 'set', methods: ['POST'], requirements: ['id' => '\d+'])]
  public function set(int $id, Request $request): JsonResponse
  {
    $data = $request->toArray();
    if ($id == 0) {
      unset($data["id"]);
      $data["createBy"] = $this->getCurrUser()->getUsername();
      $config = $this->configRepo->create($data);
    } else {
      $data["updateBy"] = $this->getCurrUser()->getUsername();
      $config = $this->configRepo->update($id, $data);
    }
    if ($config) {
      return $this->success($config->toArray());
    } else {
      return $this->error("更新失败");
    }
  }

  #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
  public function delete(int $id): JsonResponse
  {
    if (empty($id)) {
      return $this->error("参数错误");
    }
    $result = $this->configRepo->delete([$id]);
    if ($result) {
      return $this->success([$id]);
    } else {
      return $this->error("删除失败");
    }
  }

  //刷新配置表单缓存数据
  #[Route('/refresh', name: 'refresh', methods: ['PUT'])]
  public function refresh(): JsonResponse
  {

    return $this->success();
  }

  //刷新配置数据
  #[Route('', name: 'patch', methods: ['PATCH'])]
  public function patch(): JsonResponse
  {
    return $this->success();
  }
}
