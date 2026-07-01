<?php

namespace App\Service;

use App\Entity\System\SysUser;
use App\Repository\BaseRepository;
use App\Repository\System\SysUserRepository;
use App\Service\BaseService;
use Symfony\Component\HttpFoundation\RequestStack;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthService extends BaseService
{

    private JWTTokenManagerInterface $jwtManager;
    private BaseRepository $userRepo;
    private TokenStorageInterface $tokenStore;
    private RequestStack $requestStack;

    public function __construct(
        SysUserRepository $_userRepo,
        JWTTokenManagerInterface $_jwtManager,
        TokenStorageInterface $_tokenStore,
        RequestStack $_requestStack
    ) {
        $this->jwtManager = $_jwtManager;
        $this->userRepo = $_userRepo;
        $this->tokenStore = $_tokenStore;
        $this->requestStack = $_requestStack;
    }

    public function getCurrentUser(): ?SysUser
    {
        $payload = $this->getPayload();
        $userid = $payload['id'] ?? null;
        if (!$userid) {
            return null;
        }
        return $this->userRepo->find($userid);
    }

    public function getPayload()
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
                'id' => $user->getId()
            ];
            return $this->jwtManager->createFromPayload($user, $payload);
        } else {
            $this->errorResponse("用户名或或密码错误");
        }
    }
}
