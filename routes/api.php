<?php

use Illuminate\Http\Request;

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


Route::post('/login','AccessController@login');
Route::post('/register','AccessController@register');
Route::get('/items','AccessController@items');


Route::group(['middleware' => 'auth:api'], function(){
	
	Route::get('/{user}','UsersController@show');
	Route::get('/{user}/inventory', 'UsersController@showUserBelongings');
	Route::get('/{user}/notify', 'UsersController@notifications');
	
	Route::post('/{user}/report', 'UsersController@reportInfectedUser');
	Route::post('/{user}/notify', 'UsersController@notify');
	
	Route::patch('/{user}', 'UsersController@update');
	Route::patch('/{user}/location', 'UsersController@updateUserLocation');
	Route::patch('/{user}/notify', 'UsersController@notificationSeen');
	
	Route::get('/{user}/offer/', 'OffersController@myOffers');
	Route::get('/{user}/offer/find', 'OffersController@findOffersNearBy');
	Route::get('/{user}/offer/trade', 'OffersController@myOpenTrades');
	
	Route::post('/{user}/offer/', 'OffersController@create');
	Route::post('/{user}/offer/trade', 'OffersController@closeTrade');
	
	Route::delete('/{user}/offer/', 'OffersController@destroy');
	Route::post('/logout','AccessController@logout');

	Route::get('/{user}/reports', 'ReportsController@zssnReports');
});


/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
