<?php

namespace App\Controller;

use App\Entity\System\SysUser;
use App\Service\AuthService;
use App\Service\BaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController extends AbstractController
{

    const Enable = 1;
    const Disable = 0;
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * 获取当前用户。
     * Worker 模式下不做缓存，每次调用都从 AuthService 实时获取。
     * AuthService 直接从 Request header 解析 JWT，不依赖可能跨请求残留的 TokenStorage。
     */
    protected function getCurrUser(): ?SysUser
    {
        return $this->authService->getCurrentUser();
    }
    public function success(array $data = [], string $msg = "Succeed"): JsonResponse
    {
        return BaseService::successResponse($msg, $data);
    }

    public function error(string $msg = "Error", int $code = 1, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, $code, $data);
    }

    public function critical(string $msg = "Error", array $data = []): JsonResponse
    {
        return BaseService::criticalResponse($msg, $data);
    }
    public function forbidden(string $msg, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 403, $data);
    }

    public function notFound(string $msg, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 404, $data);
    }

    public function getCurrentUser(AuthService $_authService): SysUser
    {
        return $_authService->getCurrentUser();
    }
}
