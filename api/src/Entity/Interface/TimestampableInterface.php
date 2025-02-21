<?php

namespace App\Entity\Interface;

interface TimestampableInterface
{
  public function setCreateTime(\DateTimeInterface $createTime): self;
  public function getCreateTime(): ?string;
  public function setUpdateTime(\DateTimeInterface $updateTime): self;
  public function getUpdateTime(): ?string;
}
