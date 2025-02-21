<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\BaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController extends AbstractController
{

    const Enable = 1;
    const Disable = 0;
    public function success(array $data = [], string $msg = "Success"): JsonResponse
    {
        return BaseService::successResponse($msg, $data);
    }

    public function error(string $msg = "Error", int $code = 500, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, $code, $data);
    }

    public function forbidden(string $msg = "Forbidden", array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 403, $data);
    }

    public function notFound(string $msg = "NotFound", array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 404, $data);
    }

    public function getCurrentUser()
    {
        return $this->getUser();
    }
}
