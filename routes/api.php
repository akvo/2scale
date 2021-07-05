<?php

use Illuminate\Http\Request;
use App\Libraries\Akvo;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/*
Route::get('/folders', 'Api\FolderController@getFolders');
Route::get('/surveys', 'Api\SurveyController@getSurveys');
Route::get('/survey/{id}', 'Api\SurveyController@getSurvey');
Route::get('/datapoints', 'Api\DataController@getDataPoint');
Route::get('/forminstances', 'Api\DataController@getFormInstance');
Route::get('/download', 'Api\DataController@downloadCSV');
Route::get('/csv', 'Api\DataController@downloadCSV2');
Route::get('/cron', 'Api\DataController@cron');
 */

/*
|
| SYNC API
|
*/

Route::get('/sync-survey-forms', 'Api\SyncController@syncSurveyForms');
Route::get('/sync-questions', 'Api\SyncController@syncQuestions');
Route::get('/sync-question-options', 'Api\SyncController@syncQuestionOptions');
Route::get('/sync-partnerships', 'Api\SyncController@syncPartnerships');
Route::get('/sync-datapoints', 'Api\SyncController@syncDataPoints');
Route::get('/count-sync-data', 'Api\SyncController@countSyncData');
Route::get('/sync-data', 'Api\SyncController@SyncData');

/*
|
| SEED API
|
*/

Route::get('/seed-datapoints/{survey_group_id}', 'Api\SeedController@seedDataPoint');

/*
|
| CHARTS API
|
*/

Route::get('/charts-dropdown', 'Api\ChartController@questionList');
Route::get('/chart/{id}', 'Api\ChartController@chartsById');


/*
|
| DATATABLES API
|
*/
// unused
Route::get('/datatables/{form_id}','Api\DataTableController@getDataPoints');
Route::get('/datatables/{form_id}/{start}/{end}','Api\DataTableController@getDataPoints');
Route::get('/datatables/{form_id}/{country}/{start}/{end}','Api\DataTableController@getDataPoints');


/*
|
| PAGE INTERACTION API
|
*/

Route::get('/partnership/{parent_id}','Api\ConfigController@getPartnership');


// Email
Route::post('/send_email', 'Api\SupportController@send');

// RSR
Route::post('/rsr-report', 'Api\RsrReportController@generateReport');
Route::get('/seed-rsr', 'Api\RsrSeedController@seedRsr');
Route::get('/seed-rsr/{total_batch}/{batch}', 'Api\RsrSeedController@seedRsr');
Route::get('/seed-rsr-projects', 'Api\RsrSeedController@seedRsrProjects');
Route::get('/seed-rsr-results/{total_batch}/{batch}', 'Api\RsrSeedController@seedRsrResults');


/** TESTING */
Route::get('/test', function (Request $request) {
    $config = config('akvo-rsr');
    $uii = \App\RsrResult::where('rsr_project_id', $config['projects']['parent'])->orderBy('order')
            // ->with('rsr_indicators.rsr_dimensions.rsr_dimension_values')
            // ->with('rsr_indicators.rsr_periods.rsr_period_dimension_values')
            ->get();
    return $uii;
});


/** POC  */
// home investment not yet
Route::get('/flow/partnerships', 'Api\ApiController@getPartnership');
Route::get('/flow/sectors', 'Api\ApiController@getSector');
Route::get('/flow/rnr-gender', 'Api\ApiController@getRnrGender');
Route::get('/rsr/impact-reach', 'Api\ApiController@getRsrImpactReach');
Route::get('/rsr/impact-reach/uii', 'Api\ApiController@getRsrUiiReport');
Route::get('/rsr/impact-reach/uii-after', 'Api\ApiController@getRsrUiiReportAfter');
Route::get('/rsr/word-report/{country_id}/{partnership_id}/{year}/{selector}', 'Api\RsrWordReportController@getRsrWordReport');

Route::get('/flow/partnership/commodities/{country_id}/{partnership_id}/{start}/{end}', 'Api\ApiController@getPartnershipCommodities');

// * Partnership page endpoint
Route::get('/flow/partnership/text/{partnership_id}', 'Api\PartnershipPageController@getTextVisual');
Route::get('/rsr/partnership/implementing-partner/{partnership_id}', 'Api\PartnershipPageController@getImplementingPartner');
Route::get('/rsr/partnership/charts/{partnership_id}', 'Api\PartnershipPageController@getResultFramework');