<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysLogRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/logs/', name: 'system.log.')]
class SysLogController extends BaseController
{
  private SysLogRepository $logRepo;
  public function __construct(SysLogRepository $_logRepo)
  {
    $this->logRepo = $_logRepo;
  }

  #[Route('page', name: 'page', methods: ['POST'])]
  public function pgae(Request $request): JsonResponse
  {
    $params = $request->toArray();
    $keywords = $params['keywords'] ?? '';
    if (isset($keywords) && !empty($keywords)) {
      $params['username|nickname'] = ["LIKE" => $keywords];
    }
    unset($params['keywords']);
    $data = $this->logRepo->init()->page($params);
    return $this->success($data);
  }
}
