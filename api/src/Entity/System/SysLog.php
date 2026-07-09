<?php

namespace App\Entity\System;

use App\Entity\Base;
use App\Repository\System\SysLogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SysLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SysLog extends Base
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestUri = null;

    #[ORM\Column(length: 63)]
    private ?string $requestMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requestParams = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?string $responseCode = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $province = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(nullable: true)]
    private ?int $executionTime = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $browser = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $browserVersion = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $os = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $createBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeInterface $createTime = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->setCreateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function setRequestMethod(string $requestMethod): static
    {
        $this->requestMethod = $requestMethod;

        return $this;
    }

    public function getRequestParams(): ?string
    {
        return $this->requestParams;
    }

    public function setRequestParams(?string $requestParams): static
    {
        $this->requestParams = $requestParams;

        return $this;
    }

    public function getRequestUri(): ?string
    {
        return $this->requestUri;
    }

    public function setRequestUri(?string $requestUri): static
    {
        $this->requestUri = $requestUri;

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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): static
    {
        $this->province = $province;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getExecutionTime(): ?int
    {
        return $this->executionTime;
    }

    public function setExecutionTime(?int $executionTime): static
    {
        $this->executionTime = $executionTime;

        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): static
    {
        $this->browser = $browser;

        return $this;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    public function setBrowserVersion(?string $browserVersion): static
    {
        $this->browserVersion = $browserVersion;

        return $this;
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    public function setOs(?string $os): static
    {
        $this->os = $os;

        return $this;
    }

    public function getCreateBy(): ?string
    {
        return $this->createBy;
    }

    public function setCreateBy(?string $createBy): static
    {
        $this->createBy = $createBy;

        return $this;
    }

    public function getCreateTime(): ?string
    {
        return $this->createTime ? $this->createTime->format('Y-m-d H:i:s') : "";
    }

    public function setCreateTime(?\DateTimeInterface $createTime = new DateTimeImmutable()): static
    {
        $this->createTime = $createTime;
        return $this;
    }

    public function toArray(array $splices = []): array
    {
        $retArray = [
            'id' => $this->getId(),
            'requestMethod' => $this->getRequestMethod(),
            'requestUri' => $this->getRequestUri(),
            'requestParams' => $this->getRequestParams(),
            'responseCode' => $this->getResponseCode(),
            'ip' => $this->getIp(),
            'region' => $this->getProvince() . "/" . $this->getCity(),
            "browser" => $this->getBrowser() . "/" . $this->getBrowserVersion(),
            'os' => $this->getOs(),
            'executionTime' => $this->getExecutionTime(),
            'operator' => $this->getCreateBy(),
            'createTime' => $this->getCreateTime(),
        ];
        return $this->mergeArray($retArray, $splices);
    }
}
