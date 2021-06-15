<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Datapoint;
use App\RnrGender;
use App\Partnership;
use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    public function getRnrGender(Request $request)
    {
        $partnership = Cache::get('partnership');
        if (!$partnership) {
            $partnership = Partnership::all();
            Cache::put('partnership', $partnership, 86400);
        }

        $country_id = $request->country_id;
        $partnership_id = $request->partnership_id;
        $start = $request->start;
        $end = $request->end;

        $rnrGender = RnrGender::all();
        if ($country_id !== "0") {
            $rnrGender = $rnrGender->where('country_id', $country_id);
        }
        if ($partnership_id !== "0") {
            $rnrGender = $rnrGender->where('partnership_id', $partnership_id);
        }
        if ($start !== "0") {
            $rnrGender = $rnrGender->whereBetween('event_date', [date($start), date($end)]);
        }

        $genderGroupByCountryAndType = $rnrGender->groupBy('country_id')
            ->map(function ($country, $key) use ($partnership) {
                $category = $country->groupBy('question_id')
                            ->map(function ($d, $key) {
                                return [
                                    'gender' => $d->first()->gender,
                                    'age' => $d->first()->age,
                                    'total' => $d->sum('total')
                                ];
                            })->values();
                return [
                    'country_id' => $key,
                    'country' => $partnership->where('id', $key)->first()->name,
                    'total' => $category->sum('total'),
                    'categories' => $category,
                ];
            })->values();

        $results = $partnership->where('level', 'country')->values()
            ->map(function ($p) use ($genderGroupByCountryAndType) {
                $find = $genderGroupByCountryAndType->where('country_id', $p->id)->first();
                if (!$find) {
                    return [
                        'country' =>$p->name,
                        'total' => 0,
                        'categories' => []
                    ];
                }
                return collect($find)->except('country_id');
            });

        return $results;
    }
}
