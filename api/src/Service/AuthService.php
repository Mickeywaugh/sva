<?php

namespace App\Service;

use App\Entity\MiniApp\Wxuser;
use App\Entity\System\SysUser;
use App\Repository\BaseRepository;
use App\Repository\MiniApp\WxuserRepository;
use App\Repository\System\SysUserRepository;
use App\Service\BaseService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthService extends BaseService
{
    private JWTTokenManagerInterface $jwtManager;
    private BaseRepository $userRepo;
    private TokenStorageInterface $tokenStore;
    private BaseRepository $wxuserRepo;

    public function __construct(
        SysUserRepository $_userRepo,
        JWTTokenManagerInterface $_jwtManager,
        TokenStorageInterface $_tokenStore,
        WxuserRepository $_wxuserRepo
    ) {
        $this->jwtManager = $_jwtManager;
        $this->userRepo = $_userRepo;
        $this->tokenStore = $_tokenStore;
        $this->wxuserRepo = $_wxuserRepo;
    }

    public function getCurrentUser(): ?SysUser
    {
        $token = $this->tokenStore->getToken();
        if (!$token) null;
        $username = $token->getUser()->getUserIdentifier();
        return $this->userRepo->findOneBy(['username' => $username]);
    }

    public function getCurrentWxuser(): ?Wxuser
    {
        $token = $this->tokenStore->getToken();
        if (!$token) null;
        $username = $token->getUser()->getUserIdentifier();
        return $this->wxuserRepo->findOneBy(['username' => $username]);
    }
    public function clearToken()
    {
        $this->tokenStore->setToken(null);
        $token = $this->tokenStore->getToken();
        return (!$token);
    }

    public function checkLogin(string $username, string $password)
    {

        $user = $this->userRepo->findOneBy(['username' => $username, 'deleteTime' => null]); // return a user entity

        if (!$user) {
            self::errorResponse("用户不存在");
            return false;
        }
        if (!$user instanceof SysUser) {
            self::errorResponse("用户类型错误");
            return false;
        }

        if ($user->verifyPassword($password)) {
            //密码验证通过
            $payload = [
                'username' => $user->getUsername(),
                'id' => $user->getId()
            ];
            return $this->jwtManager->createFromPayload($user, $payload);
        } else {
            self::errorResponse("用户名或或密码错误");
        }
    }
}
