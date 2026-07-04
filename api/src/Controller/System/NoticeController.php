<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysNoticeRepository;
use App\Repository\System\SysUserNoticeRepository;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/notices', name: 'system.notice.')]
class NoticeController extends BaseController
{
  private SysNoticeRepository $noticeRepo;
  private SysUserNoticeRepository $userNoticeRepo;
  public function __construct(SysNoticeRepository $_noticeRepo, SysUserNoticeRepository $_userNoticeRepo, AuthService $_authService)
  {
    parent::__construct($_authService);
    $this->noticeRepo = $_noticeRepo;
    $this->userNoticeRepo = $_userNoticeRepo;
  }

  #[Route('/page', name: 'page', methods: ['POST'])]
  public function page(Request $request): JsonResponse
  {
    $params = $request->toArray();
    if (isset($params['title'])  && !empty($params['title'])) {
      $params['title'] = ["LIKE" => $params['title']];
    }
    $data = $this->noticeRepo->page($params);
    return $this->success($data);
  }

  #[Route('/my-page', name: 'userPage', methods: ['POST'])]
  public function myPage(Request $request): JsonResponse
  {
    $params = $request->toArray();
    if ($this->getCurrUser()) {
      $params['userId'] = $this->getCurrUser()->getId();
    }
    $data = $this->userNoticeRepo->page($params);
    return $this->success($data);
  }

  #[Route('', name: 'create', methods: ['POST'])]
  public function create(Request $request): JsonResponse
  {
    $data = $request->toArray();
    if (empty($data)) {
      return $this->error("参数错误");
    }
    $data["createBy"] = $this->getCurrUser()->getId();
    $data["publisher"] = $this->getCurrUser();
    $role = $this->noticeRepo->create($data);
    if ($role) {
      return $this->success($role->toArray());
    } else {
      return $this->error();
    }
  }

  #[Route('/{id}', name: 'update', methods: ['PUT'])]
  public function update(Request $request, int $id): JsonResponse
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

  #[Route('/{id}/status', name: 'setStatus', methods: ['PUT'])]
  public function setStatus(int $id, Request $request): JsonResponse
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

  #[Route('/{id}/form', name: 'getFormData', methods: ['GET'])]
  public function getFormData(int $id): JsonResponse
  {
    $data = $this->noticeRepo->find($id);
    if ($data) {
      return $this->success($data->toArray());
    } else {
      return $this->error("数据不存在");
    }
  }


  #[Route('/{id}/detail', name: 'getFormData', methods: ['GET'])]
  public function getDetail(int $id): JsonResponse
  {
    $data = $this->noticeRepo->find($id);
    if ($data) {
      return $this->success($data->toArray());
    } else {
      return $this->error("数据不存在");
    }
  }

  #[Route('/read-all', name: 'read', methods: ['PUT'])]
  public function read(): JsonResponse
  {
    if (!$this->getCurrUser()) {
      return $this->error("用户不存在");
    }

    $this->userNoticeRepo->readAll($this->getCurrUser()->getId());

    return $this->success();
  }


  #[Route('/{id}/publish', name: 'publish', methods: ['PATCH'])]
  public function publish(int $id): JsonResponse
  {
    $notice = $this->noticeRepo->find($id);
    $notice->setPublishStatus(1);
    $this->noticeRepo->flush($notice);
    return $this->success();
  }

  #[Route('/{id}/revoke', name: 'revoke', methods: ['PATCH'])]
  public function revoke(int $id): JsonResponse
  {
    $notice = $this->noticeRepo->find($id);
    $notice->setPublishStatus(0);
    $this->noticeRepo->flush($notice);
    return $this->success();
  }


  #[Route('/{ids}', name: 'delete', methods: ['DELETE'], requirements: ['ids' => '\w+'])]
  public function delete(string $ids): JsonResponse
  {
    $result = $this->noticeRepo->delete(explode(",", $ids));
    if ($result) {
      return $this->success(["ids" => $result]);
    } else {
      return $this->error("删除失败");
    }
  }
}
