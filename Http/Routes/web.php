<?php

Route::get('/', 'NetworkFlightController@index')->name('frontend.index');
Route::post('/bids/{bid}/start', 'NetworkFlightController@startBid')->name('frontend.bids.start');
Route::post('/profile/vatsim', 'NetworkFlightController@storeVatsimId')->name('frontend.profile.vatsim');

/*
 * To register a route that needs to be authentication, wrap it in a
 * Route::group() with the auth middleware
 */
// Route::group(['middleware' => 'auth'], function() {
//     Route::get('/', 'IndexController@index');
// })
