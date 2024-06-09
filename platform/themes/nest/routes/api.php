<?php
// Custom routes
// You can delete this route group if you don't need to add your custom routes.
Route::group(['namespace' => 'Theme\Nest\Http\Controllers', 'middleware' => ['api', 'core']], function () {
    Route::group(apply_filters(BASE_FILTER_GROUP_PUBLIC_ROUTE, []), function () {
        Route::get('ajax/get-district-from-search','ApiThemeController@getListDistrict')
            ->name('public.api.get-district-from-search');
    });
});