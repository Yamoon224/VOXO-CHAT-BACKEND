<?php

use App\Domains\Shared\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes web
|--------------------------------------------------------------------------
|
| Le backend est une API : la seule page HTML servie est la documentation.
| L'interface vit dans le projet Next.js (`web/`).
|
*/

Route::get('/docs', [DocumentationController::class, 'ui'])->name('docs');
Route::get('/docs/openapi.json', [DocumentationController::class, 'specification'])->name('docs.openapi');
