<?php

use Illuminate\Support\Facades\Route;
use Yazar\Http\Controllers\ContentController;
use Yazar\Http\Middleware\ImportEmptyContent;

Route::middleware(ImportEmptyContent::class)->group(function () {
    Route::get('/', [ContentController::class, 'renderMainPage'])->name('front-page');
    Route::get('/{pageNumber}', [ContentController::class, 'renderMainPage'])
        ->whereNumber('pageNumber')
        ->name('front-page.pagination');

    Route::get('/{url}/{pageNumber}', [ContentController::class, 'showCategoryPage'])
        ->where('url', '.+')
        ->whereNumber('pageNumber')
        ->name('content.category.pagination');

    Route::get('/{url}', [ContentController::class, 'show'])
        ->where('url', '.+')
        ->name('content.show');
});
