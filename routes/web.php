<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


Route::middleware(['auth'])->group(function() {
    Route::get('/survey/{id}', 'PageController@survey')->name('survey');
    Route::get('/survey', 'PageController@surveys')->name('surveys');
    Route::get('/database', 'PageController@database')->name('database');
});

Route::get('/dashboard', 'PageController@dashboard')->name('dashboard');
Route::get('/impactreach', 'PageController@impactreach')->name('impactreach');
Route::get('/partners', 'PageController@partners')->name('partners');
Route::get('/countries', 'PageController@countries')->name('countries');
Route::get('/partnership', 'PageController@partnership')->name('partnership');

Route::get('/', 'PageController@home')->name('home');
Route::get( '/auth0/callback', '\Auth0\Login\Auth0Controller@callback' )->name('auth0-callback');
Route::get( '/login', 'Auth\Auth0IndexController@login' )->name('login');
Route::get( '/logout', 'Auth\Auth0IndexController@logout' )->name('logout');
Route::get('support', 'PageController@support')->name('support');
Route::get('report', 'PageController@report')->name('report');
// Route::post('/rsr-report', 'Api\AkvoRsrController@generateReport'); // old rsr endpoint
