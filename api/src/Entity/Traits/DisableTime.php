<?php

namespace App\Entity\Traits;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait DisableTime
{

  #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
  protected ?\DateTimeInterface $disableTime = null;

  public function setDisableTime(?\DateTimeInterface $disableTime = null): static
  {
    $this->disableTime = $disableTime ?: new DateTimeImmutable();
    return $this;
  }

  public function getDisableTime(bool $delete): static
  {
    $this->disableTime = $delete ? new DateTimeImmutable() : null;
    return $this;
  }

  public function getDisable(): bool| string
  {
    return $this->disableTime ? true : false;
  }

  public function setDisable(bool $deleted): static
  {
    $this->disableTime = $deleted ? new DateTimeImmutable() : null;
    return $this;
  }
}
