<?php

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

Route::get('thanh-an','ApiController@demoApi')->name('demo-api');
//check out api
Route::post('post-cart-checkout-mini',[
    'as'         => 'post-checkout-mini',
    'uses'       => 'ApiController@postCheckOutMini'])
    ->middleware(['cors']);
//api get user by phone
Route::get('get-customer-info',[
    'as'=>'get-customer-by-phone',
    'uses'=>'ApiController@getCustomerByPhone'
])->middleware(['cors']);

//
Route::get('get-product-by-name',[
    'as'=>'get-product-by-name',
    'uses'=>'ApiController@searchProduct'
])->middleware(['cors']);

//get product search when collaborator create order
Route::get('get-product-all-variants',[
   'as'=>'get-product-all-variants',
    'uses'=>'ApiController@getAllProductAndVariations'
]);
//get customer search when collaborator create order
Route::get('get-list-customer-search',[
    'as'=>'get-list-customer-search',
    'uses'=>'ApiController@getListCustomerForSearch'
]);
//get List country when collaborator create order
Route::get('get-list-country-search',[
    'as'=>'get-list-country-search',
    'uses'=>'ApiController@getListCountry'
]);
//
Route::get('get-list-state-by-country',[
    'as'=>'get-list-state-by-country',
    'uses'=>'ApiController@ajaxGetStates'
]);
//
Route::get('get-list-city-by-state',[
    'as'=>'get-list-city-by-state',
    'uses'=>'ApiController@ajaxGetCities'
]);
//
Route::get('get-customer-order-number',[
    'as'=>'get-customer-order-number',
    'uses'=>'ApiController@getCustomerOrderNumbers'
]);
//get order by phone
Route::get('get-list-order-by-user-phone',[
    'as'=>'get-list-order-by-user-phone',
    'uses'=>'ApiController@getListOrderByPhone'
]);
//get order infor
Route::get('get-order-info-by-id',[
    'as'=>'get-order-info-by-id',
    'uses'=>'ApiController@getOrderInfo'
]);
//notification cộng phân trăm hoa hồng
Route::get('get-list-notification-by-phone',[
    'as'=>'get-list-notification-by-phone',
    'uses'=>'ApiController@getNotification'
]);
//Edit customer
Route::post('post-edit-customer-by-phone',[
   'as'=>'post-edit-customer-by-phone',
   'uses'=>'ApiController@postEditCustomer'
]);
//all city
Route::get('get-list-city-all',[
    'as'=>'get-list-city-all',
    'uses'=>'ApiController@getListCity'
]);
//get district by city
Route::get('get-list-district-by-city',[
    'as'=>'get-list-district-by-city',
    'uses'=>'ApiController@getDistrictbyCity'
]);
//get district by city
Route::get('get-list-ward-by-district',[
    'as'=>'get-list-ward-by-district',
    'uses'=>'ApiController@getWardByDistrict'
]);