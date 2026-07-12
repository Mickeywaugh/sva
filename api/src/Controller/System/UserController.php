<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysDeptRepository;
use App\Repository\System\SysRoleRepository;
use App\Repository\System\SysUserRepository;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/users/', name: 'system.users.')]
class UserController extends BaseController
{
    private SysUserRepository $userRepo;
    private SysRoleRepository $roleRepo;
    private SysDeptRepository $deptRepo;
    public function __construct(
        SysUserRepository $_userRepo,
        SysRoleRepository $_roleRepo,
        SysDeptRepository $_deptRepo,
        AuthService $_authService
    ) {
        parent::__construct($_authService);
        $this->userRepo = $_userRepo;
        $this->roleRepo = $_roleRepo;
        $this->deptRepo = $_deptRepo;
    }

    #[Route('me', name: 'me', methods: ['GET'])]
    public function currentUserData(): JsonResponse
    {
        $currUser = $this->getCurrUser();
        if ($currUser) {
            try {
                return $this->success($currUser->getUserInfo());
            } catch (\Exception $e) {
                return $this->critical("获取用户信息失败:" . $e->getMessage());
            }
        } else {
            return $this->error("用户认证令牌已失效");
        }
    }

    #[Route('page', name: 'page', methods: ['POST'])]
    public function page(Request $request): JsonResponse
    {
        $params = $request->toArray();
        $keywords = $params['keywords'] ?? '';
        if (!empty($keywords)) {
            $params['username|nickname'] = ["LIKE" => $keywords];
            unset($params['keywords']);
        }
        if (isset($params['dept'])) {
            // $dept = $this->deptRepo->find($params['dept']);
            $params['dept'] = $params['dept'];
        }

        if (isset($params['roleId']) && !empty($params['roleId'])) {
            $params['r.id'] = $params['roleId'];
            unset($params['roleId']);
            $data = $this->userRepo->join('t.userRoles', 'r')->page($params, ["time"]);
        } else {
            $data = $this->userRepo->init()->page($params, ["time"]);
        }
        return $this->success($data);
    }

    #[Route('options', name: 'options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $users = $this->userRepo->findBy(['disableTime' => ["NULL" => true]], ['dept' => 'ASC']);
        $data = array_map(function ($user) {
            return [
                'label' => $user->getUsername(),
                'value' => $user->getId(),
                'meta' => [
                    'avatar' => $user->getAvatar(),
                    'nickname' => $user->getNickname(),
                    'deptName' => $user->getDeptName(),
                    'gender' => $user->getGender(),
                    'mobile' => $user->getMobile(),
                    'email' => $user->getEmail(),
                ]
            ];
        }, $users);

        return $this->success($data);
    }

    #[Route('{id}/form', name: 'user.get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->userRepo->find($id);
        if ($user) {
            return $this->success($user->toArray());
        } else {
            return $this->error("获取用户信息失败");
        }
    }

    #[Route('{id}', name: 'set', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function set(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }

        if (isset($data['roleIds']) && $data['roleIds']) {
            $data['userRoles'] = $this->roleRepo->init()->findEntities(["id" => ["IN" => $data['roleIds']]]);
            unset($data['roleIds']);
        }

        if (isset($data['dept']) && $data['dept']) {
            $data['dept'] = $this->deptRepo->find($data['dept']);
        }
        if ($id == 0) {
            unset($data["id"]);
            $user = $this->userRepo->create($data);
        } else {
            $user = $this->userRepo->updateUser($id, $data);
        }
        if ($user) {
            return $this->success($user->toArray());
        } else {
            return $this->error("操作失败");
        }
    }

    #[Route('{ids}', name: 'delete', methods: ['DELETE'], requirements: ['ids' => '\d+(,\d+)*'])]
    public function delete(string $ids): JsonResponse
    {
        if (empty($ids)) {
            return $this->error("参数错误");
        }
        $ids = explode(",", $ids);
        $result = $this->userRepo->delete($ids);
        if ($result) {
            return $this->success(["ids" => $ids]);
        } else {
            return $this->error("删除失败");
        }
    }

    //管理后台重置密码
    #[Route('{id}/resetPassword', name: 'resetPassword', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function resetPassword(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        extract($data);
        if (!isset($password) || !$password || !$id) {
            return $this->error("参数错误");
        }
        $user = $this->userRepo->find($id);
        $user->setPassword($password);
        if ($this->userRepo->flush($user)) {
            return $this->success(msg: "重置密码成功");
        } else {
            return $this->error("重置密码失败");
        }
    }

    /**
     * 用户修改密码
     * @Route('password', name: 'setPassword', methods: ['PUT'])
     */
    #[Route('password', name: 'setPassword', methods: ['PUT'])]
    public function setPassword(Request $request): JsonResponse
    {
        $data = $request->toArray();
        extract($data); //confirmPassword: "123456" newPassword: "123456" oldPassword: "123456"
        if (empty($confirmPassword) || empty($newPassword) || empty($oldPassword)) {
            return $this->error("参数错误");
        }
        //验证旧密码
        if (!$this->getCurrUser()->verifyPassword($oldPassword)) {
            return $this->error("旧密码错误");
        };
        if ($confirmPassword != $newPassword) {
            return $this->error("新密码和确认密码不一致");
        }

        if ($this->userRepo->update($this->getCurrUser()->getId(), ["password" => $newPassword])) {
            return $this->success(msg: "重置密码成功");
        } else {
            return $this->error("重置密码失败");
        }
    }

    #[Route('{id}/signature', name: 'signature', methods: ['POST'])]
    public function signature(int $id, Request $request): JsonResponse
    {
        $signature = $request->request->get('signature');
        if (null == $signature) {
            return $this->error("参数错误");
        }

        $user = $this->userRepo->find($id);
        // $user->setSignature($signature);
        if ($this->userRepo->flush($user)) {
            return $this->success(['signature' => $signature], "更新签名成功");
        } else {
            return $this->error("更新签名失败");
        }
    }

    #[Route('{id}/avatar', name: 'avatar', methods: ['POST'])]
    public function avatar(int $id, Request $request): JsonResponse
    {
        $avatar = $request->files->get('avatar');
        if (null == $avatar || !$avatar->isValid()) {
            return $this->error("参数错误");
        }
        $avatarContent = file_get_contents($avatar->getPathName());
        $base64avatar = base64_encode($avatarContent);
        $user = $this->userRepo->find($id);
        $user->setavatar($base64avatar);
        if ($this->userRepo->flush($user)) {
            return $this->success(['avatar' => $avatar], "更新图像成功");
        } else {
            return $this->error("更新签名失败");
        }
    }

    #[Route('profile', name: 'getProfile', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProfile(): JsonResponse
    {
        if ($this->getCurrUser()) {
            return $this->success($this->getCurrUser()->toArray());
        } else {
            return $this->error("获取失败");
        }
    }

    #[Route('profile', name: 'updateProfile', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $user = $this->getCurrUser();
        $user = $this->userRepo->update($user->getId(), $data);
        if ($user) {
            return $this->success($user->toArray());
        } else {
            return $this->error("更新失败");
        }
    }

    /**
     * 发送手机验证码
     */
    #[Route('sendSmsCode', name: 'sendSmsCode', methods: ['POST'])]
    public function sendSmsCode(Request $request): JsonResponse
    {
        $phone = $request->request->get('mobile');
        if (empty($phone)) {
            return $this->error("参数错误");
        }
        $code = rand(1000, 9999);
        // $this->smsService->send($phone, $code);
        return $this->success(["code" => $code]);
    }
}
