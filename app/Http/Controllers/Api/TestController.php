<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Datapoint;
use App\RnrGender;

class TestController extends Controller
{
    //
    public function getRnrGender(Request $request)
    {
        $country_id = $request->country_id;
        $partnership_id = $request->partnership_id;
        // we need country & partnership id on view table

        $genderGroupByCountry = RnrGender::all()
            ->groupBy('country')
            ->map(function ($val, $key) {
                return [
                    'country' => $key,
                    'total' => $val->sum('total')
                ];
            })->values();

        $genderGroupByCountryAndType = RnrGender::all()
            ->groupBy('country')
            ->map(function ($country, $key) {
                $type = $country->groupBy('text')
                            ->map(function ($text, $key) {
                                return [
                                    'type' => $key,
                                    'total' => $text->sum('total')
                                ];
                            })->values();
                return [
                    'country' => $key,
                    'total' => $type->sum('total'),
                    'types' => $type,
                ];
            })->values();
        return $genderGroupByCountryAndType;
    }
}
