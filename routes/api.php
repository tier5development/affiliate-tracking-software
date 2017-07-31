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

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

// auth by ip

Route::get('api/users/{user}', function (\App\User $user) {
    return $user;
});

Route::get('api/users/{agencyId}', function() {
	//get users by agency id
	//\App\User::get
});

// update individual user
Route::put('api/user', function() {

});

// create lead
Route::post('api/lead/{lead}', function(\App\Lead $lead) {
	return $lead;
});

// update lead
Route::put('api/lead/{lead}', function(\App\Lead $lead) {
	return $lead;
});

Route::post('affiliate/links', 'AffiliateController@links');

Route::any('/problems', function(){
	return "Problems might occur but never give up do it by yourself";
})->name('fuckyuproblem');

Route::post('affiliate',[
    'uses' => 'AffiliateController@links',
    'as' => '/affiliate'
]);
// generate affiliate links


// get affiliate report

Route::post('/affiliate/report',[
    'uses' => 'AffiliateController@getReport',
    'as' => 'affiliate.report'
]);
Route::post('/affiliate/lead',[
    'uses' => 'AffiliateController@getLead',
    'as' => 'affiliate.lead'
]);
