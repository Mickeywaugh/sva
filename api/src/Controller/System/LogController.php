<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysLogRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('logs')]
class LogController extends BaseController
{
  private $logsRepo;
  public function __construct(SysLogRepository $_logsRepo)
  {
    $this->logsRepo = $_logsRepo;
  }

  #[Route('/page', name: 'logs.page', methods: ['GET'])]
  public function page(Request $request): JsonResponse
  {
    $params = $request->query->all();
    if (isset($params['keywords']) && $params['keywords']) {
      $params[] = ['module', 'like', $params['keywords']];
    }

    if (isset($params['createTime']) && count(array_filter($params['createTime'])) > 0) {
      $params[] = ['createTime', 'BETWEEN', $params['createTime']];
    }

    unset($params['keywords']);
    unset($params['createTime']);
    $data = $this->logsRepo->page($params);
    return $this->success($data);
  }

  #[Route('/visit-stats', name: 'logs.visitStats', methods: ['GET'])]
  public function visitStats(): JsonResponse
  {
    $data = $this->logsRepo->visitStats();
    return $this->success($data);
  }

  //visit-trend
  #[Route('/visit-trend', name: 'logs.visitTrend', methods: ['GET'])]
  public function visitTrend(): JsonResponse
  {
    $data = $this->logsRepo->visitTrend();
    return $this->success($data);
  }
}
