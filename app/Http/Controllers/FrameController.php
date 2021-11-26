<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrameController extends Controller
{
	public function blank()
    {
        return view('frames.frame-blank');
	}

	public function undermaintenance()
    {
        return view('frames.frame-undermaintenance');
	}

    public function home()
    {
        return view('frames.frame-home');
    }

    public function impactreach(Request $request)
    {
        return view('frames.frame-impactreach');
    }

    public function partners(Request $request)
    {
        return view('frames.frame-partners', ['surveys' => config('surveys')]);
    }

    public function countries(Request $request)
    {
        return view('frames.frame-countries');
    }

    public function partnership(Request $request)
    {
        $start = '2018-01-01';
        $end = date("Y-m-d");
        if (isset($request->start)) {
            $start = $request->start;
            $end = $request->end;
        }
        return view('frames.frame-partnership',
            [
                'country_id' => $request->country_id,
                'partnership_id' => $request->partnership_id,
                'form_id' => $request->form_id,
                'start' => $start,
                'end' => $end
            ]
        );
    }

	public function database(Request $request)
	{
        $url = '/' . $request->form_id;
        $country = '';
        if(isset($request->country)) {
            $url .= '/' . $request->country;
        }
        $partnership = '';
        if(isset($request->partnership)) {
            $url .= '/' . $request->partnership;
        }
        return view('frames.frame-database', [
            'url' => $url . '/' . $request->start . '/' . $request->end,
            'start' => $request->start,
            'end' => $request->end,
        ]);
	}


    public function support()
    {
        return view('frames.frame-support');
    }

    public function report()
    {
        return view('frames.frame-report');
    }

}
