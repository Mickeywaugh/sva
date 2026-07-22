<?php

namespace App\Entity\System;

use App\Entity\BaseEntity;
use App\Repository\System\SysApiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysApiRepository::class)]
#[ORM\Table(name: 'sys_api')]
#[ORM\HasLifecycleCallbacks]
class SysApi extends BaseEntity
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column(type: Types::INTEGER)]
  private int $id;

  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  private ?int $step = NULL;

  #[ORM\Column(length: 255)]
  private ?string $module = NULL;

  #[ORM\Column(length: 255)]
  private ?string $name = NULL;

  #[ORM\Column(length: 255)]
  private ?string $path = NULL;

  #[ORM\Column(length: 255)]
  private ?string $method = NULL;

  #[ORM\Column(type: Types::SMALLINT)]
  private ?int $withJwt = 1;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $routeParams = NULL;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $queryParams = NULL;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $bodyParams = NULL;

  #[ORM\Column(type: Types::SMALLINT)]
  private ?int $disabled = NULL;

  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  private ?int $result = NULL;

  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  private ?int $responseCode = NULL;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $responseContext  = NULL;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getStep(): ?int
  {
    return $this->step;
  }

  public function setStep(int $step): static
  {
    $this->step = $step;
    return $this;
  }

  public function getModule(): ?string
  {
    return $this->module;
  }

  public function setModule(string $module): static
  {
    $this->module = $module;
    return $this;
  }

  public function getName(): ?string
  {
    return $this->name;
  }

  public function setName(string $name): static
  {
    $this->name = $name;
    return $this;
  }

  public function getPath(): ?string
  {
    return $this->path;
  }

  public function setPath(string $path): static
  {
    $this->path = $path;
    return $this;
  }

  public function getRealPath(): string
  {
    $routeParams = $this->getRouteParams();
    $realPath = $this->path;
    //将路由路径/api/v1/typeset/{id} 中的{id}参数替换为 routeParams:{"id":1}中的id值
    if ($routeParams) {
      foreach ($routeParams as $key => $value) {
        $realPath = str_replace("{" . $key . "}", $value, $realPath);
      }
    }
    return $realPath;
  }

  public function getRoutePath(): string
  {
    $retRoute = $this->getRealPath();
    //当请求类型为GET且 queryParams不为空时，将queryParams拼接到路由路径后
    if ($this->getMethod() === 'GET' && $this->getQueryParams()) {
      $retRoute .= '?' . http_build_query($this->getQueryParams());
    }
    return $retRoute;
  }

  public function setMethod(string $method): static
  {
    $this->method = $method;
    return $this;
  }

  public function getMethod(): ?string
  {
    return $this->method;
  }


  public function setRouteParams(?array $_routeParams): static
  {
    $this->routeParams = $_routeParams ? json_encode($_routeParams) : NULL;
    return $this;
  }

  public function getRouteParams(): ?array
  {
    return $this->routeParams ? json_decode($this->routeParams, true) : NULL;
  }

  public function setQueryParams(?array $_queryParams): static
  {
    $this->queryParams = $_queryParams ? json_encode($_queryParams) : NULL;
    return $this;
  }

  public function getQueryParams(): ?array
  {
    return $this->queryParams ? json_decode($this->queryParams, true) : NULL;
  }

  public function setBodyParams(?array $_bodyParams): static
  {
    $this->bodyParams = $_bodyParams ? json_encode($_bodyParams) : NULL;
    return $this;
  }

  public function getBodyParams(): ?array
  {
    return $this->bodyParams ? json_decode($this->bodyParams, true) : NULL;
  }

  public function getWithJwt(): ?int
  {
    return $this->withJwt;
  }

  public function setWithJwt(int $withJwt): static
  {
    $this->withJwt = $withJwt;
    return $this;
  }

  public function setDisabled(?int $disabled): static
  {
    $this->disabled = $disabled;
    return $this;
  }

  public function getDisabled(): ?bool
  {
    return $this->disabled ? true : false;
  }

  public function getResult(): ?int
  {
    return $this->result;
  }

  public function setResult(?int $result): static
  {
    $this->result = $result;
    return $this;
  }

  public function getResponseCode(): ?int
  {
    return $this->responseCode;
  }

  public function setResponseCode(?int $responseCode): static
  {
    $this->responseCode = $responseCode;
    return $this;
  }

  public function getResponseContext(): ?array
  {
    return  $this->responseContext ? json_decode($this->responseContext, true) : NULL;
  }

  public function setResponseContext(mixed $responseContext): static
  {
    $this->responseContext = (is_array($responseContext) || is_object($responseContext)) ? json_encode($responseContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $responseContext;
    return $this;
  }

  public function getMsgArray(): array
  {
    return [
      "id" => $this->getId(),
      "name" => $this->getName(),
      "result" => $this->getResult(),
      "responseCode" => $this->getResponseCode(),
      "responseContext" => $this->getResponseContext()
    ];
  }

  public function toArray(): array
  {
    return [
      'id' => $this->getId(),
      'module' => $this->getModule(),
      'withJwt' => $this->getWithJwt(),
      'name' => $this->getName(),
      'path' => $this->getPath(),
      'realPath' => $this->getRealPath(),
      'routeParams' => $this->getRouteParams(),
      'queryParams' => $this->getQueryParams(),
      'bodyParams' => $this->getBodyParams(),
      'method' => $this->getMethod(),
      'result' => $this->getResult(),
      'disabled' => $this->getDisabled(),
      'responseCode' => $this->getResponseCode(),
      'responseContext' => $this->getResponseContext(),
      'createTime' => $this->getCreateTime(),
      'updateTime' => $this->getUpdateTime()
    ];
  }
}
