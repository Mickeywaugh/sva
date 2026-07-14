<?php

namespace App\Service;

use App\Entity\System\SysApi;
use App\Repository\System\SysApiRepository;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SysApiService
{
  private ?HttpClientInterface $httpClient;
  private string $baseUrl = "http://localhost";
  private string $apiUsername = "root";
  private string $apiPassword = "boingtech";
  private ?string $token = null; //jwt
  static string $redisKey = "system:autoTestToken"; //全局变量，每次实例化时赋值，避免多次请求token
  private array $httpOptions = [
    'headers' => [
      'Content-Type' => 'application/json'
    ]
  ];

  private SysApiRepository $sysApiRepo;

  public function __construct(SysApiRepository $_sysApiRepo)
  {
    // 创建httpClient对象
    $this->httpClient = HttpClient::createForBaseUri($this->baseUrl, $this->httpOptions);
    $this->sysApiRepo = $_sysApiRepo;
    // 获取jwt
    $this->getToken();
  }

  // 静态方法获取实例
  public static function getInstance(?SysApiRepository $_sysApiRepo = null): self
  {
    return new static($_sysApiRepo);
  }

  private function refreshToken(): ?self
  {
    $bodyArray = [
      "username" => $this->apiUsername,
      "password" => $this->apiPassword
    ];

    $this->httpOptions['body'] = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $url = sprintf("%s/api/v1/system/auth/login", $this->baseUrl);
    $response = $this->httpClient->request('POST', $url, $this->httpOptions);
    $statusCode = $response->getStatusCode();
    if ($statusCode === 200) {
      $responseData = json_decode($response->getContent());
      //认证成功
      if ($responseData->code == 0) {
        if ($responseData->data->accessToken) {
          // 更新httpClient header 请求中的Authorization参数值
          $this->httpOptions['headers']['authorization'] = $this->token = "Bearer " . $responseData->data->accessToken;
          // 保存token到Redis 1小时
          RedisService::getInstance()->set(self::$redisKey, $this->token, 'EX',  60 * 60);
          // 重置body参数
          $this->httpOptions['body'] = null;
          return $this;
        } else {
          Logger::error("SysApi service refresh token failed with out accessToken.");
          return null;
        }
      } else {
        Logger::error("SysApi service refresh token failed with reason " . $responseData->msg);
        return null;
      }
    } else {
      Logger::error("SysApi Service get token error: $statusCode");
      return null;
    }
  }

  private function getToken(): void
  {
    //从Redis中获取
    $this->token = RedisService::getInstance()->get(self::$redisKey);
    if ($this->token) {
      $this->httpOptions['headers']['authorization'] = $this->token;
    } else {
      $this->refreshToken();
    }
  }


  public function test(SysApi $sysApi): ?SysApi
  {
    $this->httpOptions['body'] = json_encode($sysApi->getBodyParams(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $url = $this->baseUrl . $sysApi->getRoutePath();
    $response = $this->httpClient->request($sysApi->getMethod(), $url, $this->httpOptions);
    $statusCode = $response->getStatusCode();
    if ($statusCode === 200) {
      $responseData = json_decode($response->getContent());
      if ($responseData->code == 0) {
        $sysApi->setResult(1)->setResponseContext($responseData->data);
      } else {
        $sysApi->setResult(0)->setResponseContext($responseData->message);
      }
      $sysApi->setResponseCode($statusCode)->setUpdateTime();
      return $this->sysApiRepo->flush($sysApi);
    } elseif ($statusCode == 401) {
      // auth and retry
      return $this->refreshToken()?->test($sysApi);
    } else {
      // 其他错误,不更新ResponseContext
      $sysApi->setResult(0)->setResponseCode($statusCode)->setResponseContext(["throw" => $response->getContent()])->setUpdateTime();
      return $this->sysApiRepo->flush($sysApi);
    }
  }

  /**
   * 流式自动测试，每完成一个接口测试即 yield 结果
   */
  public function autoTestStream(): \Generator
  {
    $sysApis = $this->sysApiRepo->findEntities(["disabled" => ["<>" => 1]]);
    foreach ($sysApis as $sysApi) {
      $sysApi = $this->test($sysApi);
      yield $sysApi->getMsgArray();
    }
  }

  public function __destruct()
  {
    $this->httpClient = null;
  }
}
