<?php

namespace App\Service;

use App\Entity\System\SysUser;
use App\Repository\BaseRepository;
use App\Repository\System\SysUserRepository;
use App\Service\BaseService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthService extends BaseService
{

    private JWTTokenManagerInterface $jwtManager;
    private BaseRepository $userRepo;
    private TokenStorageInterface $tokenStore;
    private ?int $lastCount = null;

    public function __construct(
        SysUserRepository $_userRepo,
        JWTTokenManagerInterface $_jwtManager,
        TokenStorageInterface $_tokenStore,
    ) {
        $this->jwtManager = $_jwtManager;
        $this->userRepo = $_userRepo;
        $this->tokenStore = $_tokenStore;
    }

    public function getCurrentUser(): ?SysUser
    {
        $token = $this->tokenStore->getToken();
        if (!$token) null;
        $username = $token->getUser()->getUserIdentifier();
        return $this->userRepo->findOneBy(['username' => $username]);
    }

    public function clearToken()
    {
        $this->tokenStore->setToken(null);
        $token = $this->tokenStore->getToken();
        return (!$token);
    }

    public function checkLogin(string $username, string $password)
    {

        $user = $this->userRepo->findOneBy(['username' => $username]); // return a user entity

        if (!$user) {
            $this->errorResponse("用户不存在");
            return false;
        }
        if (!$user instanceof SysUser) {
            $this->errorResponse("用户类型错误");
            return false;
        }

        if ($user->verifyPassword($password)) {
            // 密码验证通过，生成 JWT payload
            $payload = [
                'username' => $user->getUsername(),
                'id' => $user->getId(),
                // Mercure SSE 订阅权限配置
                'mercure' => [
                    // 允许订阅所有主题
                    'subscribe' => ['*']
                ],
            ];
            return $this->jwtManager->createFromPayload($user, $payload);
        } else {
            $this->errorResponse("用户名或或密码错误");
        }
    }
}
