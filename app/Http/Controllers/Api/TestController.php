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
    //
    public function getRnrGender(Request $request)
    {
        $partnership = Cache::get('partnership');
        if (!$partnership) {
            $partnership = Partnership::all();
            Cache::put('partnership', $partnership, 86400);
        }

        $country_id = $request->country_id;
        $partnership_id = $request->partnership_id;
        // we need country & partnership id on view table

        $genderGroupByCountryAndType = RnrGender::all()
            ->groupBy('country_id')
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
                    'country_id' => $partnership->where('id',$key)->first()->name,
                    'total' => $category->sum('total'),
                    'categories' => $category,
                ];
            })->values();
        return $genderGroupByCountryAndType;
    }
}
