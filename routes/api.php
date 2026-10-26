<?php

use App\Http\Controllers\Api\ResourceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Todos los recursos comparten estas rutas CRUD.
    $resources = [
        'areas',
        'contacts',
        'galleries',
        'training-centers',
        'computers',
        'courses',
        'teachers',
        'apprentices',
    ];

    $actions = [
        ['GET', '', 'index', 'index'],
        ['GET', '/{id}', 'show', 'show'],
        ['POST', '', 'store', 'store'],
        ['PUT', '/{id}', 'update', 'update'],
        ['DELETE', '/{id}', 'destroy', 'destroy'],
    ];

    foreach ($resources as $resource) {
        foreach ($actions as [$method, $suffix, $action, $name]) {
            Route::match([$method], "/{$resource}{$suffix}", [ResourceController::class, $action])
                ->defaults('resource', $resource)
                ->name("{$resource}.{$name}");
        }
    }
});
