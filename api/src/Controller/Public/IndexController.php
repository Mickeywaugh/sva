<?php

namespace App\Controller\Public;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('public/', name: 'public.')]
class IndexController extends BaseController
{

  public function __construct() {}

  #[Route('index', name: 'index')]
  public function index(): JsonResponse
  {
    return $this->success([]);
  }
}
