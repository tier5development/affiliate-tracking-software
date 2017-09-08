<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Http\Request;


Route::get('/', 'Auth\LoginController@showLoginForm');

Auth::routes();

Route::group(['middleware' => ['auth']], function () {

    //Agency Routes

    Route::get('dashboard',[
        'uses' => 'DashboardController@index',
        'as' => 'dashboard'
    ]);
    Route::get('show/affiliate',[
        'uses' => 'AffiliateController@showAffiliate',
        'as' => 'showAffiliate'
    ]);
    Route::get('add/affiliate',[
        'uses' => 'AgencyController@addAffiliate',
        'as' => 'get.add.affiliate'
    ]);
    Route::get('allAffiliate',[
        'uses' => 'AffiliateController@allAffiliate',
        'as' => 'allAffiliate'
    ]);
    Route::post('addaffiliate',[
        'uses' => 'AffiliateController@addAffiliate',
        'as' => 'addAffiliate'
    ]);
    Route::get('settings',[
        'uses' => 'AgencyController@getSettings',
        'as' => 'settings'
    ]);
    Route::post('register/url',[
        'uses' => 'AgencyController@registerUrl',
        'as' => 'register.url'
    ]);
    Route::get('affiliates/{affiliateId}',[
        'uses' => 'AgencyController@showAffiliate',
        'as' => 'agency.affiliateDetail'
    ]);
    Route::get('affiliate/{affiliateKey}',[
        'uses' => 'AgencyController@affiliateDashboard',
        'as' => 'agency.affiliateDashboard'
    
    ]);
    Route::get('campaign',[
        'uses' => 'CampaignController@getCampaign',
        'as' => 'get.campaign'

    ]);
    Route::post('campaign/create',[
        'uses' => 'CampaignController@createCampaign',
        'as' => 'create.campaign'

    ]);
    Route::post('campaign/delete',[
        'uses' => 'CampaignController@deleteCampaign',
        'as' => 'delete.campaign'
    ]);
    Route::post('campaign/edit',[
        'uses' => 'CampaignController@editCampaign',
        'as' => 'edit.campaign'
    ]);
    Route::get('campaign/details/{key}',[
        'uses' => 'CampaignController@detailsCampaign',
        'as' => 'details.campaign'
    ]);

    Route::post('add/affiliate/new',[
        'uses' => 'CampaignController@addAffiliate',
        'as' => 'add.affiliate.new'
    ]);
    Route::post('approve/affiliate',[
        'uses' => 'CampaignController@approveAffiliate',
        'as' => 'approve.affiliate'
    ]);
    Route::post('affiliate/delete',[
        'uses' => 'CampaignController@deleteAffiliate',
        'as' => 'delete.affiliate'
    ]);
    Route::get('affiliate/details/{id}',[
        'uses' => 'AffiliateController@detailsAffiliate',
        'as' => 'details.affiliate'
    ]);
    Route::get('campaign/products/{id}',[
        'uses' => 'CampaignController@campaignProduct',
        'as' => 'campaign.products'
    ]);
    Route::get('sendEmail/{affiliate}',[
        'uses' => 'AffiliateController@sendEmail',
        'as' => 'affiliate.sendEmail'
    ]);
    Route::post('product/create',[
        'uses' => 'ProductController@createProduct',
        'as' => 'create.product'
    ]);
    Route::post('product/choice',[
        'uses' => 'ProductController@chooseProduct',
        'as' => 'choice.product'
    ]);
    Route::delete('product/delete', [
        'uses' => 'ProductController@deleteProduct',
        'as' => 'delete.product'
    ]);
    Route::get('product/get', [
        'uses' => 'ProductController@getProduct',
        'as' => 'get.product'
    ]);
    Route::put('product/edit', [
        'uses' => 'ProductController@editProduct',
        'as' => 'edit.product'
    ]);
    Route::get('all/affiliate', [
        'uses' => 'AffiliateController@allAffiliateShow',
        'as' => 'all.affiliate'
    ]);
    Route::get('all/sales', [
        'uses' => 'AffiliateController@allSalesShow',
        'as' => 'all.sales'
    ]);
    Route::post('refund/sales', [
        'uses' => 'AffiliateController@salesRefund',
        'as' => 'sale.refund'
    ]);
    Route::get('admin/affiliate/login/{affiliate}',[
       'uses' => 'AffiliateController@adminAffiliateLogin',
       'as' => 'admin.affiliate.login'
    ]);
    Route::get('affiliate/data/sales',[
        'uses' => 'AffiliateController@affiliateSales',
        'as' => 'affiliate.sales'
    ]);
    Route::get('admin/affiliate/details/{affiliate_id}',[
        'uses' => 'AffiliateController@affiliateAllDetails',
        'as' => 'all.details.affiliate'
    ]);
    Route::post('admin/pay/commission',[
        'uses' => 'AffiliateController@payCommission',
        'as' => 'pay.commission'
    ]);
    Route::get('view/details/{user_type}/{user_id}/{link_type}',[
        'uses' => 'AffiliateController@viewDetailsLink',
        'as' => 'view.link'
    ]);
    Route::get('payout/affiliate',[
        'uses' => 'PaymentController@affiliatePayout',
        'as' => 'affiliate.payout'
    ]);
    Route::get('payout/admin',[
        'uses' => 'PaymentController@adminPayout',
        'as' => 'admin.payout'
    ]);
});
Route::get('affiliate/request/{affiliateKey}',[
    'uses' => 'CampaignController@affiliateRegistrationForm',
    'as' => 'affiliate.registerForm'
]);

Route::post('affiliate/registration',[
    'uses' => 'AffiliateController@affiliateRegistration',
    'as' => 'affiliate.registration'
]);

Route::post('affiliate/login',[
    'uses' => 'AffiliateController@affiliateLogin',
    'as' => 'affiliate.login'
]);

Route::get('thank-you',[
    'uses' => 'AffiliateController@thankYou',
    'as' => 'affiliate.thankYou'
]);

Route::get('logout',[
    'uses' => 'DashboardController@logout',
    'as' => 'logout'
]);

Route::get('admin/logout',[
    'uses' => 'DashboardController@adminlogout',
    'as' => 'admin.logout'
]);

