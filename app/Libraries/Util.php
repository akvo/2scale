<?php

namespace App\Libraries;
use Illuminate\Support\Str;

class Util {
  //
  public static function transformDimensionValueName($name)
  {
    if (!Str::contains($name, ">") && !Str::contains($name, "<")) {
      if (Str::contains($name, "Male")) {
        $name = "Male-led/owned";
      }
      if (Str::contains($name, "Female")) {
        $name = "Female-led/owned";
      }
    }
    if (Str::contains($name, ">") || Str::contains($name, "<")) {
      if (Str::contains($name, "Male") && Str::contains($name, ">")) {
        $name = "Senior Men - SM";
      }
      if (Str::contains($name, "Male") && Str::contains($name, "<")) {
        $name = "Junior Men - JM";
      }
      if (Str::contains($name, "Female") && Str::contains($name, ">")) {
        $name = "Senior Women - SW";
      }
      if (Str::contains($name, "Female") && Str::contains($name, "<")) {
        $name = "Junior Women - JW";
      }
    }
    return $name;
  }
}
