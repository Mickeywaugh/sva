<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysNoticeRepository;
use App\Repository\System\SysUserNoticeRepository;
use App\Repository\System\SysUserRepository;
use App\Service\AuthService;
use App\Service\BaseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('notices')]
class NoticeController extends BaseController
{
  private $noticeRepo;
  private $userNoticeRepo;
  private $userRepo;
  public function __construct(SysNoticeRepository $_noticeRepo, SysUserNoticeRepository $_userNoticeRepo, SysUserRepository $_userRepo)
  {
    $this->noticeRepo = $_noticeRepo;
    $this->userNoticeRepo = $_userNoticeRepo;
    $this->userRepo = $_userRepo;
  }

  #[Route('/page', name: 'notice.page', methods: ['GET'])]
  public function page(Request $request): JsonResponse
  {
    $params = $request->query->all();
    extract($params);
    $data = $this->noticeRepo->page($params);
    return $this->success($data);
  }

  #[Route('/my-page', name: 'notice.userPage', methods: ['GET'])]
  public function myPage(Request $request): JsonResponse
  {
    $params = $request->query->all();

    $userName = $this->getCurrentUser()->getUserIdentifier();
    $currUser = $this->userRepo->findOneBy(['username' => $userName]);
    if ($currUser) {
      $params['userId'] = $currUser->getId();
    }
    $data = $this->userNoticeRepo->page($params);
    return $this->success($data);
  }

  #[Route('', name: 'notice.create', methods: ['POST'])]
  public function create(Request $request, AuthService $auth): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data)) {
      return $this->error("参数错误");
    }
    $data["create_by"] = $auth->getCurrentUser()->getId();
    $role = $this->noticeRepo->create($data);
    if ($role) {
      return $this->success($role->toArray());
    } else {
      return $this->error();
    }
  }

  #[Route('/{id}', name: 'notice.update', methods: ['PUT'])]
  public function update(Request $request, $id): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data)) {
      return $this->error("参数错误");
    }
    $role = $this->noticeRepo->update($id, $data);
    if ($role) {
      return $this->success($role->toArray());
    } else {
      return $this->error();
    }
  }

  #[Route('/{id}/status', name: 'notice.setStatus', methods: ['PUT'])]
  public function setStatus(Request $request, $id): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data)) {
      return $this->error("参数错误");
    }
    $dept = $this->noticeRepo->update($id, $data);
    if ($dept) {
      return $this->success($dept->toArray());
    } else {
      return $this->error("更新失败");
    }
  }

  #[Route('/{id}/form', name: 'notice.getFormData', methods: ['GET'])]
  public function getFormData($id): JsonResponse
  {
    $data = $this->noticeRepo->find($id);
    if ($data) {
      return $this->success($data->toArray());
    } else {
      return $this->error("数据不存在");
    }
  }


  #[Route('/{id}/detail', name: 'notice.getFormData', methods: ['GET'])]
  public function getDetail($id): JsonResponse
  {
    $data = $this->noticeRepo->find($id);
    if ($data) {
      return $this->success($data->toArray());
    } else {
      return $this->error("数据不存在");
    }
  }

  #[Route('/read-all', name: 'notice.read', methods: ['PUT'])]
  public function read(): JsonResponse
  {
    $currUser = $this->getCurrentUser();
    if (!$currUser) {
      return $this->error("用户不存在");
    }

    $this->userNoticeRepo->readAll($currUser->getUserIdentifier());

    return $this->success();
  }


  #[Route('/{id}/publish', name: 'notice.publish', methods: ['PATCH'])]
  public function publish($id): JsonResponse
  {
    $notice = $this->noticeRepo->find($id);
    $notice->setPublishStatus(1);
    $this->noticeRepo->flush($notice);
    return $this->success();
  }

  #[Route('/{id}/revoke', name: 'notice.revoke', methods: ['PATCH'])]
  public function revoke($id): JsonResponse
  {
    $notice = $this->noticeRepo->find($id);
    $notice->setPublishStatus(0);
    $this->noticeRepo->flush($notice);
    return $this->success();
  }


  #[Route('/{ids}', name: 'notice.delete', methods: ['DELETE'])]
  public function delete($ids): JsonResponse
  {
    $result = $this->noticeRepo->delete(explode(",", $ids));
    if ($result) {
      return $this->success(["ids" => $result]);
    } else {
      return $this->error("删除失败");
    }
  }
}
