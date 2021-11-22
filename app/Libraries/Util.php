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

  public static function transformUii8Value($res, $uii, $group, $tab)
  {
      // UII8 Modification to show all dimension target/achieve value
      $childs = $res->reject(function ($r) use ($uii) {
          return Str::contains($r['uii'], $uii);
      });
      $uii8_custom = $res->filter(function ($r) use ($uii) {
          return Str::contains($r['uii'], $uii);
      })->transform(function ($r) use ($childs, $group, $tab) {
          $r = $r["dimensions"]->map(function($d, $di) use ($r, $childs, $group, $tab) {
              $dim = collect($r["dimensions"][$di]);
              $dimVal = collect($dim["values"]);
              $targetValue = $dimVal->sum("target_value");
              $actualValue = $dimVal->sum("actual_value");
              if (count($dimVal) == 0) {
                  $targetValue = $dim["target_value"];
                  $actualValue = $dim["actual_value"];
              }
              $new = [
                  "uii" => $r["uii"],
                  "target_text" => $d["target_text"],
                  "target_value" => $targetValue,
                  "actual_value" => $actualValue,
                  "dimensions" => [$dim]
              ];
              if ($group) {
                $new["group"] = $r['group'];
              }
              if ($tab) {
                $new["tab"] = $r['tab'];
              }
              $childs->push($new);
              return $new;
          });
          return $r;
      });
      return $childs;
  }
}
