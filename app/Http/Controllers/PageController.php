<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ResponseCache;
use App\Partnership;
use App\Libraries\FlowApi as Flow;
use App\Repositories\CustomUserRepository;
use App\Http\Controllers\Auth\Auth0IndexController;

class PageController extends Controller
{
	/**
	*
	* Create a new controller instance.
	*
	*
	* @return void
	**/
	public function __construct() {
	}

	/**
	*
	* Show the application dashboard.
	*
	* @return \Illuminate\Http\Response
	*
	*/
	public function home()
	{
		$fetch = new CustomUserRepository();
		// User on instance
		if (\Auth::check() && !$fetch->fetchFlowUser()) {
			return view('pages.home');
		}
		// User not on an intance
		if (\Auth::check() && $fetch->fetchFlowUser()) {
			$auth = new Auth0IndexController();
			return $auth->logout(true);
		}

		return view('pages.home');
	}

	public function dashboard()
	{
		return view('pages.dashboard');
	}

	public function database(Partnership $partnerships)
	{
		$countries = $partnerships->has('childrens')->get();
		$data = [
			'forms' => config('surveys.forms'),
			'countries' => $countries,
		];
		return view('pages.database', ['surveys' => $data]);
	}

	public function surveys()
	{
		return view('pages.survey2', ['surveys' => config('surveys')]);
	}

	public function survey(Request $request, Flow $flow)
	{
		$survey = $flow->forminstance($request->id);
		$survey = json_decode($survey['state']);
		$url = config('surveys.url') . '/' . $survey->_formId . '/' . $request->id;
		return view('pages.survey',
			['surveys' => config('surveys'), 'saved_survey' => $url]
		);
	}

	public function impactreach(Partnership $partnerships, Request $request)
	{
		$countries = $partnerships->has('childrens')->get();
		return view('pages.impactreach');
	}

	public function partners()
	{
		return view('pages.partners');
	}

	public function countries()
	{
		return view('pages.countries');
	}


	public function partnership(Partnership $partnerships, Request $request)
	{
		$countries = $partnerships->has('childrens')->get();
		return view('pages.partnership', [ 'countries' => $countries]);
	}

	public function support()
	{
		return view('pages.support');
	}

	public function report()
  {
		return view('pages.report');
	}

	public function lumenDashboard()
  {
		$config = config('lumen-dashboard');
		return view('pages.lumen-dashboard', ['config' => $config]);
	}

}
