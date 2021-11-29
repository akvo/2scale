<?php
/*
|--------------------------------------------------------------------------
| Frames
|--------------------------------------------------------------------------
*/

Route::get('/blank', 'FrameController@blank');
Route::get('/blank/{page}', 'FrameController@blank');
Route::get('/undermaintenance', 'FrameController@undermaintenance');

Route::get('/home', 'FrameController@home');
Route::get('/impactreach', 'FrameController@impactreach');
Route::get('/partners', 'FrameController@partners');

Route::get('/countries', 'FrameController@countries');

Route::get('/partnership/{country_id}/{partnership_id}', 'FrameController@partnership');
Route::get('/partnership/{country_id}/{partnership_id}/{start}/{end}', 'FrameController@partnership');

Route::get('/database/{form_id}/{start}/{end}', 'FrameController@database');
Route::get('/database/{form_id}/{country}/{start}/{end}', 'FrameController@database');
Route::get('/database/{form_id}/{country}/{partnership}/{start}/{end}', 'FrameController@database');

Route::get('/support', 'FrameController@support');
Route::get('/report', 'FrameController@report');
Route::get('/uii-datatable-report', 'FrameController@uiiDatatableReport');
Route::get('/uii-datatable-report/{country_id}/{partnership_id}', 'FrameController@uiiDatatableReport');

// RSR
Route::post('/rsr-report', 'Api\RsrReportController@generateReport');
