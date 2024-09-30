<?php

use Illuminate\Support\Facades\Route;
use App\Http\SweetApi\Test\SwaggerEndpoints;
use App\Http\SweetApi\Test\PippoEndpoints;

Route::prefix('swagger')->group(function () {
	Route::controller(SwaggerEndpoints::class)->group(function () {
		Route::get('/docs', 'index')->name('swagger_index');
		Route::get('/json', 'json')->name('swagger_json');
	});
});

Route::prefix('pippo')->group(function () {
	Route::controller(PippoEndpoints::class)->group(function () {
		Route::get('/sweet/get', 'sweet_get')->name('pippo_sweet_get');
		Route::post('/sweet/post', 'sweet_post')->name('pippo_sweet_post');
		Route::put('/sweet/put', 'sweet_put')->name('pippo_sweet_put');
		Route::patch('/sweet/patch', 'sweet_patch')->name('pippo_sweet_patch');
		Route::delete('/sweet/delete', 'sweet_delete')->name('pippo_sweet_delete');
		Route::options('/sweet/options', 'sweet_options')->name('pippo_sweet_options');
		Route::any('/sweet/any', 'sweet_any')->name('pippo_sweet_any');
		Route::match(['get','post'], '/sweet/match', 'sweet_match')->name('pippo_sweet_match');
	});
});

