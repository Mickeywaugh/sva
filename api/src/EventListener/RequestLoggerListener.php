<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use App\Repository\System\SysLogRepository;
use Jenssegers\Agent\Agent;

class RequestLoggerListener
{
  private $logger;
  private $agent;
  private $logData;
  protected $startTime;
  protected $save = false;
  public function __construct(SysLogRepository $logger)
  {
    $this->logger = $logger;
    $this->agent = new Agent();
    $this->logData = [
      "browser" => $this->agent->browser(),
      "browserVersion" => $this->agent->version($this->agent->browser()),
      "os" => sprintf("%s->%s:%s", $this->agent->device(), $this->agent->platform(), $this->agent->version($this->agent->platform()))
    ];
    $this->save = $_ENV['APP_LOGS'] ?? $this->save;
  }

  public function onKernelRequest(RequestEvent $reqEvent)
  {
    if (!$this->save) return;

    $request = $reqEvent->getRequest();
    if ($request->getMethod() === 'OPTIONS') {
      return;
    }
    if ($request->getPathInfo() === '/api/v1/auth/login') {
      return;
    }
    $this->startTime = microtime(true) * 1000;
    //添加请求信息
    $this->logData = $this->logData + [
      "requestMethod" => $request->getMethod(),
      "requestParams" => $request->getQueryString(),
      'content' => $request->getContent(),
      "requestUri" => $request->getRequestUri(),
      "method" => $request->getMethod(),
      "ip" => implode(",", $request->getClientIps())
    ];
  }

  public function onKernelResponse(ResponseEvent $resEvent)
  {
    if (!$this->save) return;

    $response = $resEvent->getResponse();
    if (!$resEvent->isMainRequest()) {
      return;
    }

    // 添加响应信息
    $responseTime = microtime(true) * 1000;
    $this->logData = $this->logData + [
      "module" => $resEvent->getRequest()->getPathInfo(),
      "responseContent" => $response->getStatusCode(),
      "executionTime" => $responseTime - $this->startTime,
      "createBy" => "logListener"
    ];
    // 记录日志
    // BaseService::log($this->logData);
    $this->logger->create($this->logData);
  }
}
