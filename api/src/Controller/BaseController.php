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
    protected SysUser $currUser;
    public function __construct(AuthService $authService)
    {
        $this->currUser = $this->getCurrentUser($authService);
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
