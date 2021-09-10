<?php

namespace App\Libraries;
use Illuminate\Support\Str;

class Util {
  //
  public static function transformDimensionValueName($name)
  {
    if (Str::contains($name, "Senior")) {
        $name = str_replace("Senior",">", $name);
    }
    if (Str::contains($name, "Junior")) {
        $name = str_replace("Junior","<", $name);
    }
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
        $name = "Senior\nMen";
      }
      if (Str::contains($name, "Male") && Str::contains($name, "<")) {
        $name = "Junior\nMen";
      }
      if (Str::contains($name, "Female") && Str::contains($name, ">")) {
        $name = "Senior\nWomen";
      }
      if (Str::contains($name, "Female") && Str::contains($name, "<")) {
        $name = "Junior\nWomen";
      }
    }
    return $name;
  }
}
