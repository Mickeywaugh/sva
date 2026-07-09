<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysLogRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/statistics/', name: 'system.statistics.')]
class StatisticsController extends BaseController
{
  private SysLogRepository $logRepo;
  public function __construct(SysLogRepository $_logRepo)
  {
    $this->logRepo = $_logRepo;
  }

  #[Route('visits/trend', name: 'visitsTrend', methods: ['GET'])]
  public function visitsTrend(Request $request): JsonResponse
  {
    $startDate = $request->query->get('startDate');
    $endDate = $request->query->get('endDate');
    return $this->success(["dates" => [$startDate, $endDate], "pvList" => [], "ipList" => []]);
  }

  #[Route('visits/overview', name: 'overview', methods: ['GET'])]
  public function overView(Request $request): JsonResponse
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
