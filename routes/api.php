<?php
/**
 * File name: api.php
 * Last modified: 2020.04.30 at 08:21:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

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

Route::prefix('driver')->group(function () {
    Route::post('login', 'API\Driver\UserAPIController@login');
    Route::post('register', 'API\Driver\UserAPIController@register');
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\Driver\UserAPIController@user');
    Route::post('logout', 'API\Driver\UserAPIController@logout');
    Route::get('settings', 'API\Driver\UserAPIController@settings');
});


Route::post('login', 'API\UserAPIController@login');
Route::post('register', 'API\UserAPIController@register');
Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
Route::get('user', 'API\UserAPIController@user');
Route::get('logout', 'API\UserAPIController@logout');
Route::get('settings', 'API\UserAPIController@settings');

Route::resource('cuisines', 'API\CuisineAPIController');
Route::resource('categories', 'API\CategoryAPIController');
Route::resource('subcategories', 'API\SubCategoryAPIController');
Route::resource('restaurants', 'API\RestaurantAPIController');

Route::resource('faq_categories', 'API\FaqCategoryAPIController');
Route::resource('foods', 'API\FoodAPIController');
Route::resource('galleries', 'API\GalleryAPIController');
Route::resource('food_reviews', 'API\FoodReviewAPIController');
Route::resource('nutrition', 'API\NutritionAPIController');
Route::resource('extras', 'API\ExtraAPIController');
Route::resource('extra_groups', 'API\ExtraGroupAPIController');
Route::resource('faqs', 'API\FaqAPIController');
Route::resource('restaurant_reviews', 'API\RestaurantReviewAPIController');
Route::resource('currencies', 'API\CurrencyAPIController');

Route::middleware('auth:api')->group(function () {
    Route::group(['middleware' => ['role:driver']], function () {
        Route::prefix('driver')->group(function () {
            Route::resource('orders', 'API\OrderAPIController');
            Route::resource('notifications', 'API\NotificationAPIController');
            Route::post('users/{id}', 'API\UserAPIController@update');
            Route::resource('faq_categories', 'API\FaqCategoryAPIController');
            Route::resource('faqs', 'API\FaqAPIController');
        });
    });
    Route::group(['middleware' => ['role:manager']], function () {
        Route::prefix('manager')->group(function () {
            
            Route::resource('drivers', 'API\DriverAPIController');

            Route::resource('earnings', 'API\EarningAPIController');

            Route::resource('driversPayouts', 'API\DriversPayoutAPIController');

            Route::resource('restaurantsPayouts', 'API\RestaurantsPayoutAPIController');
        });
    });
    Route::post('users/{id}', 'API\UserAPIController@update');

    Route::resource('order_statuses', 'API\OrderStatusAPIController');

    Route::get('payments/byMonth', 'API\PaymentAPIController@byMonth')->name('payments.byMonth');
    Route::resource('payments', 'API\PaymentAPIController');

    Route::get('favorites/exist', 'API\FavoriteAPIController@exist');
    Route::resource('favorites', 'API\FavoriteAPIController');

    Route::resource('orders', 'API\OrderAPIController');
    Route::post('checkpromo', 'API\OrderAPIController@checkCode');

    Route::resource('food_orders', 'API\FoodOrderAPIController');

    Route::resource('notifications', 'API\NotificationAPIController');

    Route::get('carts/count', 'API\CartAPIController@count')->name('carts.count');
    Route::resource('carts', 'API\CartAPIController');
    Route::post('carts/addcart', 'API\CartAPIController@addCart');
    Route::delete('carts/clearcart/{id}', 'API\CartAPIController@clearCart');

    Route::resource('delivery_addresses', 'API\DeliveryAddressAPIController');

    //---------------------------------------------------------------------------
    Route::get('orderhistory', 'API\OrderAPIController@getOrderHistory');
    Route::put('deactivate_address/{id}', 'API\DeliveryAddressAPIController@deactivate');
    Route::get('users/getpoints/{id}', 'API\UserAPIController@getPoints');

});

Route::get('similaritems/{id}', 'API\FoodAPIController@getSimilarItems');
Route::get('getDriverAvail/{id}', 'API\UserAPIController@getDriverAvail');
Route::put('setDriverAvail/{id}/{isAvail}', 'API\UserAPIController@toggleDriverAvail');
Route::put('setDebugger/{id}/{isDebugger}', 'API\UserAPIController@setDebugger');
Route::put('updateDriverAvail/{id}', 'API\UserAPIController@updateDriverAvail');
Route::get('subcategories/getSubcatFromCat/{id}', 'API\SubCategoryAPIController@getSubcatFromCat');
Route::get('searchInSubcat', 'API\FoodAPIController@searchInSubcat');
Route::get('test', 'API\UserAPIController@test');

