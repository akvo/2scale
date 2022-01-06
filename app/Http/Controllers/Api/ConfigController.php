<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Partnership;
use App\Http\Controllers\Api\PartnershipPageController;

class ConfigController extends Controller
{
    public function getPartnership(Request $request, Partnership $partnerships)
    {
        $partnershipPageController = new PartnershipPageController();
        $ps = $partnerships->has('parents')->get();
        if ($request->parent_id !== "0") {
            $ps = $partnerships->where('parent_id',$request->parent_id)->get();
        }

        $ps = $ps->transform(function ($p) use ($partnershipPageController) {
            $p['name'] = $partnershipPageController->getPartnershipName($p['name']);
            return $p;
        });

        return $ps;
    }
}
