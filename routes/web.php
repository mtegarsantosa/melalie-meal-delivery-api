<?php
use Illuminate\Support\Facades\Route;
use App\Menu;

Route::get('/', function() {
  return 'Melalie Meal Delivery API: /v1';
});

Route::group([
  "prefix" => "v1"
], function () {
  // Import Database Seed
  Route::group([
    "prefix" => "import"
  ], function() {
    Route::post('/restaurants', 'RestaurantController@import');
    Route::post('/users', 'UserController@import');
  });

  // Restaurants
  Route::group([
    "prefix" => "restaurants",
    "middleware" => "auth"
  ], function() {
    Route::get('/', 'RestaurantController@index');
  });

  // Users
  Route::group([
    "prefix" => "users",
    "middleware" => "auth"
  ], function() {
    Route::get('/', 'UserController@index');
    Route::get('/restaurants', 'UserController@restaurantsIndex');

    Route::post('/purchases', 'UserController@buy');
  });

  Route::get('/debug', function() {
    return Menu::where([
      ['restaurant_id', '=', 1]
    ])->get();
  });

  // Admin
  Route::group([
    "prefix" => "admins",
    "middleware" => "admin"
  ], function() {
    Route::get('/restaurants', 'AdminController@restaurantsIndex');
    Route::get('/users', 'AdminController@usersIndex');
    Route::get('/users/recap', 'AdminController@userRecap');
  });
});


Route::get('/debug', function() {
  return Menu::where([
    ['restaurant_id', '=', 1]
  ])->get();
});
