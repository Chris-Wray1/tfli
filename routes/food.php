<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenFoodFacts;

Route::name('food.')
	->prefix('/food')
	->controller(OpenFoodFacts::class)
	->group(function () {

		Route::get('/productList', function (Request $request) {
			$food = new OpenFoodFacts($request);
	        return response($food->productList(), 200)->header('Content-Type', 'application/json');
		})->name('productList');

		Route::get('/{barcode}', function (Request $request, int $barcode) {
			$food = new OpenFoodFacts($request);
			$food->barcode($barcode);
	        return response($food->product(), 200)->header('Content-Type', 'application/json');

		})->name('product');

	});


?>
