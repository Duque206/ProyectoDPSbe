<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Tightenco\Ziggy\Ziggy;


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

Route::post('login',[AuthController::class,'login'])->name('login');

Route::post('logout',[AuthController::class,'logout'])->middleware('auth:sanctum')->name('logout');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //Products
    //INDEX: api/products | GET (para ver todos los productos)
    //STORE: api/products | POST (para crear un nuevo producto)
    //SHOW: api/products/{id|product} | GET (para ver un producto existente)
    //UPDATE: api/products/{id|product} | PATCH (para actualizar un producto existente)
    //DESTROY: api/products/{id|product} | DELETE (para borrar un producto existente)
    Route::apiResource('products', ProductController::class);
    Route::delete('products/{product}/remove', [ProductController::class, 'remove'])->name('products.remove');
    Route::post('products/{product}/addToOrder', [ProductController::class, 'addToOrder'])->name('products.order.add');
    Route::post('products/{product}/removeFromOrder', [ProductController::class, 'removeFromOrder'])->name('products.order.remove');
    Route::patch('products/{product}/restore', [ProductController::class, 'restore'])->withTrashed()->name('products.restore');

    //Warehouses
    Route::apiResource('warehouses', WarehouseController::class);
    Route::put('warehouses/{warehouse}/restore', [WarehouseController::class, 'restore'])->name('warehouses.restore')->withTrashed();

    //Users
    Route::apiResource('users', UserController::class);

    //Categories
    Route::apiResource('categories', CategoryController::class);

    //Orders
    Route::resource('orders', OrderController::class);
    Route::post('orders/{order}/finish', [OrderController::class, 'finishOrder'])->name('orders.finish');
    Route::delete('orders/{order}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
});

Route::get('ziggy', fn () => response()->json(new Ziggy));
