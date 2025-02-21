<?php

namespace App\Controller\System;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Gregwar\Captcha\CaptchaBuilder;
use App\Controller\BaseController;
use App\Service\AuthService;

#[Route('auth/')]
class AuthController extends BaseController
{
    private $authService;
    public function __construct(AuthService $_authService)
    {
        $this->authService = $_authService;
    }


    #[Route('refresh-token', name: 'auth.login', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');
        if (!$token) {
            return $this->error("Token not found", "A0231");
        }
        return $this->authService->refreshToken($token);
    }

    #[Route('login', name: 'auth.login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        $token = $this->authService->checkLogin($username, $password);
        if (!$token) {
            return $this->error("用户名或密码错误");
        } else {
            return $this->success(
                [
                    "accessToken" => $token,
                    "tokenType" => "bearer",
                    "refreshToken" => "",
                    "expires" => 3600 * 24 * 7
                ],
                "登录成功",
            );
        }
    }

    #[Route('logout', name: 'auth.logout', methods: ['DELETE'])]
    public function logout(): JsonResponse
    {
        if ($this->authService->clearToken()) {
            return $this->success(msg: "退出成功");
        } else {
            return $this->error("未登录");
        }
    }

    #[Route('captcha', name: 'auth.captcha', methods: ['POST', 'GET'])]
    public function captcha(Request $request): JsonResponse
    {
        $builder = new CaptchaBuilder;
        $builder->setBackgroundColor(0, 0, 0, 127);
        $builder->build();
        $base64 = $builder->inline();
        $captchaKey = $builder->getPhrase();
        if ($captchaKey) {
            $request->getSession()->set("captcha_key", $captchaKey);
            return $this->success(
                ["captchaBase64" => $base64, "captchaKey" => ""],
                "获取验证码码成功"
            );
        }
    }
}
