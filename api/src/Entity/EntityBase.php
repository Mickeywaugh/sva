<?php

namespace App\Entity;

use App\Entity\Traits\EntityTime;
use App\Entity\Interface\TimestampableInterface;

class EntityBase extends Base implements TimestampableInterface
{
  use EntityTime;
}
