<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthFailureListener
{
  public function onAuthenticationFailure(AuthenticationFailureEvent $event)
  {
    $response = new JsonResponse(['code' => 401, 'message' => '登录状态过期'], 200);
    $event->setResponse($response);
  }
}
