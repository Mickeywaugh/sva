<?php

namespace App\Controller\System;


use App\Controller\BaseController;
use App\Repository\System\SysUserRepository;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('users')]
class UserController extends BaseController
{
    private $userRepo;
    public function __construct(SysUserRepository $_userRepo)
    {
        $this->userRepo = $_userRepo;
    }

    #[Route('/me', name: 'users.me', methods: ['GET'])]
    public function currentUserData(AuthService $authService): JsonResponse
    {
        $currUser = $authService->getCurrentUser();
        if ($currUser) {
            try {
                $data = [
                    "userId" => $currUser->getId(),
                    "username" => $currUser->getUsername(),
                    "nickname" => $currUser->getNickname(),
                    "avatar" => $currUser->getAvatar(),
                    "roles" => $currUser->getRoles(),
                    "perms" => $currUser->getPermissions()
                ];
                return $this->success($data, msg: "获取用户信息成功");
            } catch (\Exception $e) {
                return $this->error("获取用户信息失败:" . $e->getMessage());
            }
        } else {
            return $this->error("用户认证令牌已失效");
        }
    }

    #[Route('/page', name: 'users.page', methods: ['POST'])]
    public function page(Request $request): JsonResponse
    {
        $params = $request->toArray();
        $keywords = $params['keywords'] ?? '';
        if (!empty($keywords)) {
            $params['username'] = "%$keywords%";
            $params['nickname'] = "%$keywords%";
        }
        if (isset($params['dept'])) {
            // $dept = $this->deptRepo->find($params['dept']);
            $params['dept'] = $params['dept'];
        }
        $params['isDeleted'] = 0;
        $data = $this->userRepo->page($params);
        if (isset($params['roleId']) && !empty($params['roleId'])) {

            $filterList = [];
            $filterList = array_filter($data['list'], function ($user) use ($params) {
                return in_array($params['roleId'], $user['roleIds']);
            });
            $data['list'] = [];
            foreach ($filterList as $f) {
                $data['list'][] = $f;
            }
            $data['total'] = count($filterList);
        }
        return $this->success($data, msg: "获取用户列表成功");
    }

    #[Route('/options', name: 'users.options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $users = $this->userRepo->findBy(['isDeleted' => 0], ['dept' => 'ASC']);
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
                    'employeeId' => $user->getEmployeeId(),
                ]
            ];
        }, $users);

        return $this->success($data, msg: "获取下拉列表数据成功");
    }

    #[Route('', name: 'users.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $user = $this->userRepo->create($data);
        if ($user) {
            $update["qrcode"] = $request->getSchemeAndHttpHost();
            $newUser = $this->userRepo->update($user->getId(), $update);
        }
        if ($newUser) {
            return $this->success($newUser->toArray(), msg: "创建用户成功");
        } else {
            return $this->error("创建用户失败");
        }
    }

    #[Route('/{id}/form', name: 'user.get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get($id): JsonResponse
    {
        $user = $this->userRepo->find($id);
        if ($user) {
            return $this->success($user->toArray(), msg: "获取用户信息成功");
        } else {
            return $this->error("获取用户信息失败");
        }
    }

    #[Route('/{id}', name: 'users.update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $user = $this->userRepo->updateUser($id, $data);
        if ($user) {
            return $this->success($user->toArray(), msg: "更新成功");
        } else {
            return $this->error("更新失败");
        }
    }

    #[Route('/{id}/status', name: 'users.setStatus', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function setStatus(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $dept = $this->userRepo->update($id, $data);
        if ($dept) {
            return $this->success($dept->toArray(), msg: "更新成功");
        } else {
            return $this->error("更新失败");
        }
    }

    #[Route('/{ids}', name: 'users.delete', methods: ['DELETE'])]
    public function delete($ids): JsonResponse
    {
        if (empty($ids)) {
            return $this->error("参数错误");
        }
        $result = $this->userRepo->delete(explode(",", $ids));
        if ($result) {
            return $this->success(["ids" => $result], msg: "删除成功");
        } else {
            return $this->error("删除失败");
        }
    }

    #[Route('/{id}/password', name: 'users.password', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function setPassword(Request $request, $id): JsonResponse
    {
        $password = $request->query->get("password");
        if (empty($password)) {
            return $this->error("参数错误");
        }
        if ($this->userRepo->resetPassword($id, $password)) {
            return $this->success(msg: "重置密码成功");
        } else {
            return $this->error("重置密码失败");
        }
    }

    #[Route('/{id}/signature', name: 'users.signature', methods: ['POST'])]
    public function signature(int $id, Request $request): JsonResponse
    {
        $signature = $request->request->get('signature');
        if (null == $signature) {
            return $this->error("参数错误");
        }

        $user = $this->userRepo->find($id);
        $user->setSignature($signature);
        if ($this->userRepo->flush($user)) {
            return $this->success(['signature' => $signature], msg: "更新签名成功");
        } else {
            return $this->error("更新签名失败");
        }
    }

    #[Route('/{id}/avatar', name: 'users.avatar', methods: ['POST'])]
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
            return $this->success(['avatar' => $avatar]);
        } else {
            return $this->error("更新签名失败");
        }
    }

    #[Route('/{id}/profile', name: 'users.getProfile', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProfile(int $id): JsonResponse
    {
        if (empty($id)) {
            return $this->error("参数错误");
        }
        $user = $this->userRepo->find($id);
        if ($user) {
            return $this->success($user->toArray());
        } else {
            return $this->error("获取失败");
        }
    }

    #[Route('/{id}/profile', name: 'users.updateProfile', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateProfile(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $user = $this->userRepo->update($id, $data);
        if ($user) {
            return $this->success($user->toArray());
        } else {
            return $this->error("更新失败");
        }
    }

    #[Route('/genqrcode', name: 'users.genQrcode', methods: ['POST'])]
    public function genQrcode(Request $request): JsonResponse
    {
        $params = $request->toArray();
        $where = [];
        if (isset($params['ids']) && !empty($params['ids'])) {
            $where[] = ["id", "IN", $params['ids']];
        }

        $users = $this->userRepo->findEntities($where);
        $users = $this->userRepo->batchUpdate($users, ["qrcode" => $request->getSchemeAndHttpHost()]);
        if ($users) {
            return $this->success(["total" => count($users)]);
        } else {
            return $this->error("更新失败");
        }
    }
}
