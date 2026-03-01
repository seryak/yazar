<?php

use App\Http\Controllers\ContentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContentController::class, 'renderMainPage'])->name('front-page');
Route::get('/{pageNumber}', [ContentController::class, 'renderMainPage'])
    ->whereNumber('pageNumber')
    ->name('front-page.pagination');

Route::get('/{slug}/{pageNumber}', [ContentController::class, 'showCategoryPage'])
    ->where('slug', '.+')
    ->whereNumber('pageNumber')
    ->name('content.category.pagination');

Route::get('/{slug}', [ContentController::class, 'show'])
    ->where('slug', '.+')
    ->name('content.show');
