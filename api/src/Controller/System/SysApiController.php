<?php

namespace App\Controller\System;

use App\Command\ApiCommand;
use App\Controller\BaseController;
use App\Repository\System\SysApiRepository;
use App\Service\Logger;
use App\Service\SysApiService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/api', name: 'system.api.')]
class SysApiController extends BaseController
{
  private SysApiRepository $sysApiRepo;
  private ApiCommand $apiCommand;
  private SysApiService $apiService;
  public function __construct(SysApiRepository $_apiRepo, ApiCommand $apiCommand, SysApiService $apiService)
  {
    $this->sysApiRepo = $_apiRepo;
    $this->apiCommand = $apiCommand;
    $this->apiService = $apiService;
  }

  #[Route('/page', name: 'page', methods: ['POST'])]
  public function page(Request $request): JsonResponse
  {
    $params = $request->toArray();
    if (isset($params['keywords']) && $params['keywords']) {
      $params['name'] = ['LIKE' => $params['keywords']];
    }
    unset($params['keywords']);
    if (isset($params['module']) && $params['module']) {
      $params['module'] = ['LIKE' => $params['module']];
    }
    $data = $this->sysApiRepo->init()->page($params);
    return $this->success($data);
  }

  #[Route('/{id}', name: 'set', methods: ['POST'], requirements: ['id' => '\d+'])]
  public function set(int $id, Request $request): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data)) {
      return $this->error("参数错误");
    }
    // id=0 为新境,id>0 为更新
    if ($id > 0) {
      $config = $this->sysApiRepo->update($id, $data);
    } else {
      $config = $this->sysApiRepo->create($data);
    }
    if ($config) {
      return $this->success($config->toArray());
    } else {
      return $this->error("操作失败");
    }
  }

  #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
  public function delete(int $id): JsonResponse
  {
    if (empty($id)) {
      return $this->error("参数错误");
    }
    $config = $this->sysApiRepo->delete([$id]);
    if ($config) {
      return $this->success([$id]);
    } else {
      return $this->error("删除失败");
    }
  }

  #[Route('/moduleOptions', name: 'moduleOptions', methods: ['GET'])]
  public function getModuleOptions(): JsonResponse
  {
    $modules = $this->sysApiRepo->distinct("module");
    $retArray = [];
    foreach ($modules as $module) {
      $retArray[] = ["label" => $module['module'], "value" => $module['module']];
    }
    return $this->success($retArray);
  }

  #[Route('/sync', name: 'sync', methods: ['GET'])]
  public function sync(): JsonResponse
  {
    $this->apiCommand->process();
    return $this->success([]);
  }

  #[Route('/test/{id}', name: 'test', methods: ['GET'], requirements: ['id' => '\d+'])]
  public function test(int $id): JsonResponse
  {
    if (!$id) $this->error("关键参数缺失.");
    $sysApi = $this->sysApiRepo->find($id);
    if (!$sysApi) return $this->error("API不存在");
    try {
      $sysApi = $this->apiService->test($sysApi);
      if (!$sysApi) return $this->error("测试失败");
      return $this->success($sysApi->toArray());
    } catch (Exception $e) {
      return $this->error("测试失败: " . $e->getMessage());
    }
  }

  #[Route('/autoTest', name: 'autoTest', methods: ['GET'])]
  public function autoTest(): StreamedResponse
  {
    return new StreamedResponse(function () {
      // 禁用输出缓冲，确保数据实时推送
      if (ob_get_level()) {
        ob_end_clean();
      }
      header('Content-Type: application/x-ndjson');
      header('X-Accel-Buffering: no');
      header('Cache-Control: no-cache');

      foreach ($this->apiService->autoTestStream() as $result) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        if (ob_get_level()) {
          ob_flush();
        }
        flush();
      }
    }, 200, [
      'Content-Type'  => 'application/x-ndjson',
      'X-Accel-Buffering' => 'no',
      'Cache-Control' => 'no-cache',
    ]);
  }
}
