<?php

namespace App\Entity\Traits;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait DeleteTime
{

  #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
  protected ?\DateTimeInterface $deleteTime = null;

  public function setDeleteTime(?\DateTimeInterface $deleteTime = null): static
  {
    $this->deleteTime = $deleteTime ?: new DateTimeImmutable();
    return $this;
  }

  public function getDeleteTime(bool $delete): static
  {
    $this->deleteTime = $delete ? new DateTimeImmutable() : null;
    return $this;
  }

  public function getIsDeleted(): bool| string
  {
    return $this->deleteTime ? true : false;
  }
}
