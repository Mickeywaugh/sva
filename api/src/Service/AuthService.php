<?php

namespace App\Service;

use App\Entity\System\SysUser;
use App\Repository\System\SysUserRepository;
use App\Service\BaseService;
use App\Service\MercureService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Predis\Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthService extends BaseService
{
    private const REDIS_KEY = 'system:onlineUsers';
    private const TTL = 1800; // 30分钟

    private JWTTokenManagerInterface $jwtManager;
    private SysUserRepository $userRepo;
    private TokenStorageInterface $tokenStore;
    private RequestStack $requestStack;
    private MercureService $mercureService;
    private ?int $lastCount = null;

    public function __construct(
        SysUserRepository $_userRepo,
        JWTTokenManagerInterface $_jwtManager,
        TokenStorageInterface $_tokenStore,
        RequestStack $_requestStack,
        MercureService $_mercureService,
    ) {
        $this->jwtManager = $_jwtManager;
        $this->userRepo = $_userRepo;
        $this->tokenStore = $_tokenStore;
        $this->requestStack = $_requestStack;
        $this->mercureService = $_mercureService;
    }

    /**
     * 获取当前用户。
     * Worker 模式下绕过 TokenStorage（可能跨请求残留），
     * 直接从 Request header 中解析 JWT token 获取 username。
     */
    public function getCurrentUser(): ?SysUser
    {
        $payload = $this->getPayload();
        if (!$payload) {
            return null;
        }
        $userId = $payload['id'] ?? null;
        if (!$userId) {
            return null;
        }
        $user = $this->userRepo->find($userId);
        if (!$user) {
            return null;
        }
        return $user;
    }

    public function getPayload(): ?array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        $jwt = substr($authHeader, 7);
        try {
            $payload = $this->jwtManager->parse($jwt);
        } catch (\Throwable) {
            return null;
        }
        return $payload;
    }

    public function clearToken()
    {
        $this->tokenStore->setToken(null);
        $token = $this->tokenStore->getToken();
        return (!$token);
    }

    public function checkLogin(string $username, string $password)
    {

        $user = $this->userRepo->init()->findOne(['username' => $username, 'disableTime' => NULL]); // return a user entity

        if (!$user) {
            self::errorResponse("用户不存在");
            return false;
        }
        if (!$user instanceof SysUser) {
            self::errorResponse("用户类型错误");
            return false;
        }

        if ($user->verifyPassword($password)) {
            // 密码验证通过，生成 JWT payload
            $payload = [
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ];
            return $this->jwtManager->createFromPayload($user, $payload);
        } else {
            self::errorResponse("用户名或或密码错误");
        }
    }

    /**
     * 心跳处理：更新 Redis ZSET 中 username 对应的最后心跳时间，
     * 同时清除超过 30 分钟未心跳的条目，人数变化时推送 SSE。
     */
    public function heartbeat(): int
    {
        $token = $this->tokenStore->getToken();
        if (!$token) {
            return 0;
        }
        $username = $token->getUser()->getUserIdentifier();

        $now = time();

        // 使用连接池，自动 borrow/release
        $count = RedisService::with(function (Client $redis) use ($username, $now): int {
            // 更新当前用户的心跳时间
            $redis->zadd(self::REDIS_KEY, [$username => $now]);

            // 移除超过 30 分钟未心跳的用户
            $redis->zremrangebyscore(self::REDIS_KEY, 0, $now - self::TTL);

            // 获取当前在线人数
            return $redis->zcard(self::REDIS_KEY);
        });

        // 数量变化时推送
        if ($count !== $this->lastCount) {
            $this->lastCount = $count;
            $this->mercureService->onlineCount($count);
        }

        return $count;
    }

    /**
     * 注销时从 Redis ZSET 中移除当前用户的 username，并推送在线人数。
     */
    public function logout(): int
    {
        $token = $this->tokenStore->getToken();
        if ($token) {
            $username = $token->getUser()->getUserIdentifier();
            // 使用连接池，自动 borrow/release
            $count = RedisService::with(function (Client $redis) use ($username): int {
                $redis->zrem(self::REDIS_KEY, $username);
                return $redis->zcard(self::REDIS_KEY);
            });
            $this->lastCount = $count;
            $this->mercureService->onlineCount($count);
        }

        $this->clearToken();
        return $this->lastCount ?? 0;
    }
}
