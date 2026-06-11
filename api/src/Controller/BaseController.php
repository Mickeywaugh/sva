<?php

namespace App\Controller;

use App\Entity\System\SysUser;
use App\Service\AuthService;
use App\Service\BaseService;
use App\Service\Logger;
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

    public function getCurrentUser(AuthService $_authService): SysUser
    {
        return $_authService->getCurrentUser();
    }


    public function success(array $data = [], string $msg = "Succeed"): JsonResponse
    {
        return new JsonResponse([
            'code' => 0,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public function error(string $msg = "Error", int $code = 1, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public function critical(string $msg = "Critical Error", array $data = []): JsonResponse
    {
        Logger::critical($msg, $data);
        return new JsonResponse([
            'code' => 502,
            'msg' => $msg,
            'data' => $data
        ]);
    }
    public function forbidden(string $msg, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 401, $data);
    }

    public function notFound(string $msg, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 404, $data);
    }
}
