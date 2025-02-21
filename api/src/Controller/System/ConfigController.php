<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysConfigRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('config')]
class ConfigController extends BaseController
{
  private $configRepo;
  public function __construct(SysConfigRepository $_configRepo)
  {
    $this->configRepo = $_configRepo;
  }

  #[Route('/page', name: 'config.page', methods: ['GET'])]
  public function page(Request $request): JsonResponse
  {
    $params = $request->query->all();
    if (isset($params['keywords']) && $params['keywords']) {
      $params[] = ['configName', 'like', $params['keywords']];
    }
    unset($params['keywords']);
    $data = $this->configRepo->page($params);
    return $this->success($data);
  }

  #[Route('', name: 'config.create', methods: ['POST'])]
  public function create(Request $request): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data)) {
      return $this->error("参数错误");
    }
    $config = $this->configRepo->create($data);
    if ($config) {
      return $this->success($config->toArray());
    }
  }

  #[Route('/{id}', name: 'config.delete', methods: ['DELETE'])]
  public function delete($id): JsonResponse
  {
    if (empty($id)) {
      return $this->error("参数错误");
    }
    $config = $this->configRepo->delete([$id]);
    if ($config) {
      return $this->success([$id]);
    } else {
      return $this->error("删除失败");
    }
  }
  // 获取配置数据
  #[Route('/{id}/form', name: 'config.get', methods: ['GET'])]
  public function get($id): JsonResponse
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

  //更新配置数据
  #[Route('/{id}', name: 'config.update', methods: ['PUT'], requirements: ['id' => '\d+'])]
  public function update(Request $request, $id): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data) || !$id) {
      return $this->error("参数错误");
    }
    $config = $this->configRepo->update($id, $data);
    if ($config) {
      return $this->success($config->toArray());
    } else {
      return $this->error("更新失败");
    }
  }

  //刷新配置表单缓存数据
  #[Route('/refresh', name: 'config.refresh', methods: ['PUT'])]
  public function refresh(): JsonResponse
  {

    return $this->success();
  }

  //刷新配置数据
  #[Route('', name: 'config.patch', methods: ['PATCH'])]
  public function patch(): JsonResponse
  {
    return $this->success();
  }
}
