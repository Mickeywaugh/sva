<?php

namespace App\Entity;

use App\Entity\Traits\CUTime;

abstract class BaseEntity extends Base
{
  use CUTime;
}
