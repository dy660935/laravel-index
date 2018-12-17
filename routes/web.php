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

/*Route::get('/', function () {
    return view('welcome');
});*/
Route::post('/', '\App\Http\Controllers\IndexController@index');

Route::post('/index/productInfo', '\App\Http\Controllers\IndexController@productInfo');

Route::post('/Index/cashRedEnvelope', '\App\Http\Controllers\IndexController@cashRedEnvelope');

Route::post('/Index/userCopy', '\App\Http\Controllers\IndexController@userCopy');

Route::post('/common/searchIndex', '\App\Http\Controllers\CommonController@searchIndex');

Route::post('/common/searchResult', '\App\Http\Controllers\CommonController@searchResult');

Route::post('/common/applyRecord', '\App\Http\Controllers\CommonController@applyRecord');

Route::post('/category/categoryIndex', '\App\Http\Controllers\CategoryController@categoryIndex');

Route::post('/category/categoryBrand', '\App\Http\Controllers\CategoryController@categoryBrand');

Route::post('/strategy/strategyIndex', '\App\Http\Controllers\StrategyController@strategyIndex');

Route::post('/strategy/strategyDetail', '\App\Http\Controllers\StrategyController@strategyDetail');

Route::post('/user/userIndex', '\App\Http\Controllers\UserController@userIndex');

Route::post('/user/userBonusInfo', '\App\Http\Controllers\UserController@userBonusInfo');

Route::post('/user/userCash', '\App\Http\Controllers\UserController@userCash');

Route::post('/login/sessionKey', '\App\Http\Controllers\LoginController@sessionKey');

Route::post('/login/loginDo', '\App\Http\Controllers\LoginController@loginDo');



Route::post('/product/productDetail', '\App\Http\Controllers\ProductController@productDetail');

Route::post('/product/productDetailsPage', '\App\Http\Controllers\ProductController@productDetailsPage');

Route::post('/index/getRedPackage', '\App\Http\Controllers\IndexController@getRedPackage');


















