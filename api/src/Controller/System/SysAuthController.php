<?php

namespace App\Controller\System;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Mickeywaugh\Captcha\CaptchaBuilder;
use App\Controller\BaseController;
use App\Service\AuthService;
use App\Service\RedisService;

#[Route('system/auth', name: "system.auth.")]
class SysAuthController extends BaseController
{
    private AuthService $authService;
    static string $captchaKeyPrefix = "system:captchaIds:";
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

        if (isset($captchaId) && $captchaId  && isset($captchaCode) && $captchaCode) {
            // 验证码验证
            if (!$captchaId) return $this->error("验证码Key为空");
            if (!$captchaCode) return $this->error("验证码为空");
            $redisCaptcha = RedisService::getInstance()->get(self::$captchaKeyPrefix . $captchaId);
            if ($redisCaptcha == null) {
                return $this->error("验证码已过期" . $captchaId);
            } else {
                if (strtolower($redisCaptcha)  != strtolower($captchaCode)) {
                    return $this->error("验证码错误");
                } else {
                    RedisService::getInstance()->del(self::$captchaKeyPrefix . $captchaId);
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
        $builder = new CaptchaBuilder();
        $base64 = $builder->setBackgroundColor(0, 0, 0,127)->build(120,30)->inline();
        $captchaPhrase = $builder->getPhrase(); //验证码
        // 生成验证码id
        $captchaId = md5(uniqid() . microtime());
        if ($captchaPhrase) {
            // 保存验证码至redis
            $redis = RedisService::getInstance();
            $redis->setex(self::$captchaKeyPrefix . $captchaId, 60 * 5, $captchaPhrase);
            return $this->success(
                ["captchaBase64" => $base64, "captchaId" => $captchaId],
                "获取验证码码成功"
            );
        } else {
            return $this->error("获取验证码码失败");
        }
    }
}
