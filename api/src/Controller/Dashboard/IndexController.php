<?php

namespace App\Controller\Dashboard;

use App\Controller\BaseController;
use App\Service\AuthService;
use App\Service\BaseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('dashboard/', name: 'dashboard.')]
class IndexController extends BaseController
{
  public function __construct(
    AuthService $_authService,
  ) {
    parent::__construct($_authService);
  }

  #[Route('index', name: 'index', methods: ['GET'])]
  public function index(): JsonResponse
  {
    return $this->success([]);
  }

  #[Route('systemInfo', name: 'systemInfo', methods: ['GET'])]
  public function systemInfo(): JsonResponse
  {
    $vueVersion = '3.5.34';
    return $this->success([
      'serverInfo' => php_uname(),
      'phpVersion' => PHP_VERSION,
      'Vue3Version' => $vueVersion,
      'RedisVerion' =>  BaseService::getRedisVersion(),
      'MysqlVerion' => BaseService::getMysqlVersion(),
    ]);
  }
}
