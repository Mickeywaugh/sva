<?php

namespace App\Service;

use App\Entity\System\SysUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\BaseService;
use App\Repository\System\SysUserRepository;

class AuthService extends BaseService
{
    private $jwtManager;
    private $passwordEncoder;
    private $userRepo;
    private $tokenStore;

    public function __construct(
        SysUserRepository $_userRepo,
        UserPasswordHasherInterface $_passwordEncoder,
        JWTTokenManagerInterface $_jwtManager,
        TokenStorageInterface $_tokenStore,
    ) {
        $this->passwordEncoder = $_passwordEncoder;
        $this->jwtManager = $_jwtManager;
        $this->userRepo = $_userRepo;
        $this->tokenStore = $_tokenStore;
    }

    public static function validPassword(SysUser $user, string $password)
    {
        return self::$passwordEncoder->isPasswordValid($user, $password);
    }

    public function getCurrentUser(): SysUser
    {
        $token = $this->tokenStore->getToken();
        if (!$token) self::errorResponse('未登录或token过期', "A0230");
        $tokenUser = $token->getUser();

        return $tokenUser ?: self::errorResponse('token无效', "A0230");
    }

    public function clearToken()
    {
        $this->tokenStore->setToken(null);
        $token = $this->tokenStore->getToken();
        return (!$token);
    }

    public function refreshToken($userToken): string
    {
        // 解析用户token获取用户信息
        $payLoad = $this->jwtManager->decode($userToken);

        if (isset($payLoad['id'])) {
            $user = $this->userRepo->find($payLoad['id']);
        }
        if (!$user) {
            return self::errorResponse("无效的持有令牌", "A0231");
        }

        // 生成新的访问令牌
        $payload = [
            'username' => $user->getUsername(),
            'id' => $user->getId()
        ];

        $newAccessToken = $this->jwtManager->createFromPayload($user, $payload);
        if (!$newAccessToken) {
            return self::errorResponse("生成新访问令牌失败", 500);
        }
        $retData = [
            "accessToken" => $newAccessToken,
            "tokenType" => "bearer",
            "expires" => 3600 * 24 * 7
        ];
        return Self::successResponse("刷新令牌成功", data: $retData);
    }

    public function checkLogin(string $username, string $password)
    {

        $user = $this->userRepo->findOneBy(['username' => $username, 'isDeleted' => 0]); // return a user entity

        if (!$user) {
            Self::errorResponse("用户不存在");
            return false;
        }
        if (!$user instanceof SysUser) {
            Self::errorResponse("用户类型错误");
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
            Self::errorResponse("用户名或或密码错误", "A0230");
        }
    }
}
