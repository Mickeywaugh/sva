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
    $data = $this->logRepo->page($params);
    return $this->success($data);
  }

  #[Route('visit-trend', name: 'visitTrend', methods: ['GET'])]
  public function visitTrend(Request $request): JsonResponse
  {
    $startDate = $request->query->get('startDate');
    $endDate = $request->query->get('endDate');
    return $this->success(["dates" => [$startDate, $endDate], "pvList" => [], "ipList" => []]);
    return $this->success($data);
  }

  #[Route('visit-status', name: 'visitStatus', methods: ['GET'])]
  public function visitStatus(Request $request): JsonResponse
  {
    $retArray = [
      "todayUvCount" => 0,
      "totalUvCount" => 0,
      "uvGrowthRate" => 0,
      "todayPvCount" => 0,
      "totalPvCount" => 0,
      "pvGrowthRate" => 0
    ];
    return $this->success($retArray);
  }
}
