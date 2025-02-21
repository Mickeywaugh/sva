<?php

namespace App\Entity;

class Base
{

  const TRUE = 1;
  const FALSE = 0;

  const QRCODEPATH = "download/images/QrCode/";

  const QRCODE = [
    "user" => SELF::QRCODEPATH . "user/",
    "order" => SELF::QRCODEPATH . "order/",
    "material" => SELF::QRCODEPATH . "material/",
    "machine" => SELF::QRCODEPATH . "machine/",
  ];

  const ORDERDATAPATH = "data/order/";

  public function spliceArray(array &$retArray, array $methodNames): array
  {

    foreach ($methodNames as $k => $v) {
      //如果$k为数字键，则直接调用方法

      if (is_int($k)) {
        $methodName = "get" . ucfirst($v) . "Array";
      } else { // 非数字键，则调用getXXXArray方法,将$v作为参数
        $methodName = "get" . ucfirst($k) . "Array";
      }


      if (method_exists($this, $methodName)) {
        if (is_int($k)) {

          $retArray = $retArray + $this->$methodName();
        } else {
          $retArray = $retArray + $this->$methodName($v);
        }
      }
    }
    return $retArray;
  }
}
