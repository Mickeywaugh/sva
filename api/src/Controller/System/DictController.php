<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysDictDataRepository;
use App\Repository\System\SysDictRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/dict', name: 'system.dict.')]
class DictController extends BaseController
{

    private SysDictRepository $dictRepo;
    private SysDictDataRepository $dictDataRepo;
    public function __construct(SysDictRepository $_dictRepo, SysDictDataRepository $_dictDataRepo)
    {
        $this->dictRepo = $_dictRepo;
        $this->dictDataRepo = $_dictDataRepo;
    }

    /**
     * 字典列表
     */
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $dictList = [];
        try {
            $dictList = $this->dictRepo->init()->list($params, ["DictData"]);
            return $this->success($dictList);
        } catch (\Exception $e) {
            return $this->critical("字典数据获取失败:" . $e->getMessage());
        }
    }

    /**
     * 字典分页
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/page/{node}', name: 'page', methods: ['POST'], requirements: ['node' => 'dict|item'])]
    public function page(string $node, Request $request): JsonResponse
    {
        $params = $request->toArray();
        try {
            $repo = match ($node) {
                'dict' => $this->dictRepo,
                'item' => $this->dictDataRepo
            };
            if (!$repo) return $this->error('参数错误');
            if (isset($params['keyword']) && !empty($params['keyword'])) {
                $params['name|dictCode'] = ["LIKE" => $params['keyword']];
            }
            unset($params['keyword']);
            $page = $repo->init()->page($params);
            return $this->success($page);
        } catch (\Exception $e) {
            return $this->critical("字典数据获取失败:" . $e->getMessage());
        }
    }

    /**
     * 字典数据表单
     */
    #[Route('/{node}/{id}', name: 'get', methods: ['GET'], requirements: ['node' => 'dict|item', 'id' => '\d+'])]
    public function get(string $node, int $id): JsonResponse
    {
        if (empty($id)) {
            return $this->error('参数错误');
        }
        $repo = match ($node) {
            'dict' => $this->dictRepo,
            'item' => $this->dictDataRepo
        };
        if (!$repo) return $this->error('参数错误');
        $entity = $repo->find($id);
        if (!$entity) {
            return $this->error("字典不存在");
        }
        return $this->success($entity->toArray());
    }

    #[Route('/{node}/{id}', name: 'set', methods: ['PUT', 'POST'], requirements: ['node' => 'dict|item', 'id' => '\d+'])]
    public function set(string $node, int $id, Request $request): JsonResponse
    {
        $params = $request->toArray();
        $repo = match ($node) {
            'dict' => $this->dictRepo,
            'item' => $this->dictDataRepo
        };
        if (!$repo) return $this->error('参数错误');
        if (!$id) {
            if (isset($params['dictId']) && $params['dictId']) {
                $params['dict'] = $this->dictRepo->find($params['dictId']);
            }
            $entity = $repo->create($params);
        } else {
            $entity = $repo->update($id, $params);
        }
        if (!$entity) {
            return $this->error("保存失败");
        }
        return $this->success($entity->toArray());
    }


    #[Route('/{node}/{id}', name: 'delete', methods: ['DELETE'], requirements: ['node' => 'dict|item', 'id' => '\d+'])]
    public function delete(string $node, int $id): JsonResponse
    {
        $repo = match ($node) {
            'dict' => $this->dictRepo,
            'item' => $this->dictDataRepo
        };
        if (!$repo) return $this->error('参数错误');
        if (!$id) {
            return $this->error("参数错误");
        }
        $entity = $repo->delete([$id]);
        if (!$entity) {
            return $this->error("删除失败");
        }
        return $this->success(["id" => $id]);
    }
    /**
     * 批量删除字典项
     */
    #[Route('/batchDelete/{ids}', name: 'deletes', methods: ['DELETE'])]
    public function batchDelete(string $ids): JsonResponse
    {
        if (empty($ids)) {
            return $this->error("参数错误");
        }
        $result = $this->dictDataRepo->delete(explode(',', $ids));
        if ($result) {
            return $this->success(["ids" => $result]);
        } else {
            return $this->error("删除失败");
        }
    }

    /**
     * 获取数据项列表
     */
    #[Route('/item/options/{code}', name: 'dataOptions', methods: ['GET'])]
    public function dictOptions(string $code): JsonResponse
    {
        $dict = $this->dictRepo->findOneBy(['dictCode' => $code]);
        if (!$dict) return $this->error("字典不存在");
        return $this->success($dict->getDictOptions());
    }
}
