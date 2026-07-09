<?php

namespace App\Controller\System;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Gregwar\Captcha\CaptchaBuilder;
use App\Controller\BaseController;
use App\Service\AuthService;
use App\Service\RedisService;

#[Route('system/auth', name: "system.auth.")]
class SysAuthController extends BaseController
{
    private AuthService $authService;
    public function __construct(AuthService $_authService)
    {
        $this->authService = $_authService;
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $params = $request->toArray();
        extract($params);

        if (!isset($username) || !isset($password) || empty($username) || empty($password)) {
            return $this->error('Invalid username or password');
        }

        if (isset($captchaKey) && $captchaKey  && isset($captchaCode) && $captchaCode) {
            // 验证码验证
            if (!$captchaKey) return $this->error("验证码Key为空");
            if (!$captchaCode) return $this->error("验证码为空");
            $redisCaptcha = RedisService::getInstance()->get("captcha:" . $captchaKey);
            if ($redisCaptcha == null) {
                return $this->error("验证码已过期" . $captchaKey);
            } else {
                if (strtolower($redisCaptcha)  != strtolower($captchaCode)) {
                    return $this->error("验证码错误");
                } else {
                    RedisService::getInstance()->del("captcha:" . $captchaKey);
                }
            }
        }

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
                "登录成功"
            );
        }
    }

    #[Route('/logout', name: 'logout', methods: ['DELETE'])]
    public function logout(): JsonResponse
    {
        if ($this->authService->clearToken()) {
            return $this->success([]);
        } else {
            return $this->error("未登录");
        }
    }

    #[Route('/captcha', name: 'captcha', methods: ['GET'])]
    public function captcha(): JsonResponse
    {
        $builder = new captchaBuilder();
        $builder->setBackgroundColor(0, 0, 0)->setBackgroundAlpha(127);
        $builder->build();
        $base64 = $builder->inline();
        $captchaPhrase = $builder->getPhrase(); //验证码
        // 生成验证码id
        $captchaKey = md5(uniqid() . microtime());
        if ($captchaPhrase) {
            // 保存验证码至redis
            $redis = RedisService::getInstance();
            $redis->setex("captcha:" . $captchaKey, 60 * 5, $captchaPhrase);
            return $this->success(
                ["captchaBase64" => $base64, "captchaKey" => $captchaKey],
                "获取验证码码成功"
            );
        } else {
            return $this->error("获取验证码码失败");
        }
    }
}
