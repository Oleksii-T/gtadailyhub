<?php

use App\Http\Controllers\Api\EditorialController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

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

Route::middleware('api-bearer')->group(function () {
    Route::get('editorial/posts', [EditorialController::class, 'posts']);
    Route::get('editorial/posts/{id}', [EditorialController::class, 'post'])->whereNumber('id');
    Route::get('editorial/categories', [EditorialController::class, 'categories']);
    Route::get('editorial/pages', [EditorialController::class, 'pages']);
    Route::get('editorial/events', [EditorialController::class, 'events']);
    Route::get('editorial/events/by-request/{requestId}', [EditorialController::class, 'eventByRequest'])->where('requestId', '[A-Za-z0-9._:-]{1,120}');
    Route::get('editorial/redirects', [EditorialController::class, 'redirects']);
    Route::put('editorial/posts/by-key/{editorialKey}', [EditorialController::class, 'upsertPost'])->where('editorialKey', '[A-Za-z0-9._:-]{1,120}');
    Route::put('editorial/categories/by-key/{editorialKey}', [EditorialController::class, 'upsertCategory'])->where('editorialKey', '[A-Za-z0-9._:-]{1,120}');
    Route::put('editorial/pages/by-key/{editorialKey}', [EditorialController::class, 'upsertPage'])->where('editorialKey', '[A-Za-z0-9._:-]{1,120}');
    Route::put('editorial/redirects/by-key/{editorialKey}', [EditorialController::class, 'upsertRedirect'])->where('editorialKey', '[A-Za-z0-9._:-]{1,120}');

    Route::post('posts', [PostController::class, 'store']);
    Route::put('posts/{id}', [PostController::class, 'update']);
});
