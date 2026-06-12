<?php

namespace App\Entity;

use App\Entity\Traits\CUTime;

abstract class BaseEntity extends Base
{
  //带自动处理 创建时间、更新时间
  use CUTime;
}
