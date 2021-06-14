<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Datapoint;
use App\Option;

class TestController extends Controller
{
    //
    public function getFlowData(Request $request)
    {
        $indicators = $request->indicators;
        $datapoints = Datapoint::with([
            'answers' => function ($q) use ($indicators) {
                $q->whereIn('question_id', [123456]);
            }
        ]);
        return $request->indicator_id;
    }
}
