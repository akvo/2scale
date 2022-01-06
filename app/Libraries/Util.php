<?php

namespace App\Libraries;
use Illuminate\Support\Str;

class Util {
  //
  public function __construct() { }

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
                  "dimensions" => [$dim],
                  "chart_title" => (isset($r["chart_title"])) ? $r["chart_title"] : "",
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

  public static function findFirstDimensionValue($dimension, $search = [])
  {
    $filter = collect($dimension["values"])->filter(function ($v) use ($search) {
      return Str::containsAll(Str::lower($v["name"]), $search);
    })->first();
    return $filter;
  }

  public static function addUiiAutomateCalculation($data)
  {
    // Custom automate calculation
    $result = $data->transform(function ($d) {
      $totalAchieved = isset($d["actual_value"]) ? $d["actual_value"] : 0;
      $dimension = collect($d["dimensions"])->first();

      if (
        Str::contains($d['uii'], "UII-2") || Str::contains($d['uii'], "UII2")
        || Str::contains($d['uii'], "UII-6") || Str::contains($d['uii'], "UII6")
        || (
            (Str::contains($d['uii'], "UII-8") || Str::contains($d['uii'], "UII8"))
            && (
              Str::contains($d['target_text'], "smallholder farmers")
              || Str::contains($d['target_text'], "MSMEs"))
          )
      ) {
        /* ## SHF - UII 2
        - % of women smallholder farmers reached ((senior women+junior women) /total achieved %)
        - % of youth smallholder farmers reached ((junior men+junior women) /total achieved %) */

        /* ## MSMEs - UII 6
        - % of women micro-entreprenuers ((senior women+junior women)/total achieved %)
        - % of youth micro-entreprenuers ((junior men+junior women) /total achieved *%) */

        /* ## Access to finance
        - % of women smallholder farmers accessing additional ((senior women+junior women) /total achieved *%)
        - % of youth smallholder farmers accessing additional ((junior men+junior women) /total achieved *%)
        - % of women micro-entreprenuers accessing additional ((senior women+junior women) /total achieved *%)
        - % of youth micro-entreprenuers accessing additional ((junior men+junior women) /total achieved *%) */

        $seniorWomenAchieved = 0;
        $seniorWomenAchievedValue = self::findFirstDimensionValue($dimension, ["senior", "women"]);
        if (isset($seniorWomenAchievedValue["actual_value"])) {
          $seniorWomenAchieved = $seniorWomenAchievedValue["actual_value"];
        }
        $juniorWomenAchieved = 0;
        $juniorWomenAchievedValue = self::findFirstDimensionValue($dimension, ["junior", "women"]);
        if (isset($juniorWomenAchievedValue["actual_value"])) {
          $juniorWomenAchieved = $juniorWomenAchievedValue["actual_value"];
        }
        $juniorMenAchieved = 0;
        $juniorMenAchievedValue = self::findFirstDimensionValue($dimension, ["junior", "men"]);
        if (isset($juniorMenAchievedValue["actual_value"])) {
          $juniorMenAchieved = $juniorMenAchievedValue["actual_value"];
        }
        $womenShf = $totalAchieved ? ($seniorWomenAchieved + $juniorWomenAchieved) / $totalAchieved : 0;
        $youthShf = $totalAchieved ? ($juniorMenAchieved + $juniorWomenAchieved) / $totalAchieved : 0;
        $d["automate_calculation"] = [
          [
            "text" => "##number## women",
            "value" => $womenShf * 100
          ],
          [
            "text" => "##number## youth",
            "value" => $youthShf * 100
          ]
        ];
        return $d;
      }

      if (Str::contains($d['uii'], "UII-4") || Str::contains($d['uii'], "UII4")) {
        /* ## SMEs
        - % of women-led SMEs ((women-led SMEs/total achieved*%)) */
        $womenLedAchieved = 0;
        $womenLedAchievedValue = self::findFirstDimensionValue($dimension, ["female-led", "owned"]);
        if (isset($womenLedAchievedValue["actual_value"])) {
          $womenLedAchieved = $womenLedAchievedValue["actual_value"];
        }
        $womenSmes = $totalAchieved ? $womenLedAchieved / $totalAchieved : 0;
        $d["automate_calculation"] = [
          [
            "text" => "##number## women",
            "value" => $womenSmes * 100
          ],
        ];
        return $d;
      }

      return $d;
    });

    return $result;
  }
}
