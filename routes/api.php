<?php

use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Todas las rutas API empiezan con /api/v1 y devuelven respuestas JSON.
// Por ahora solo se habilitan GET para consultar y POST para crear registros.
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Areas: listado, detalle y creación.
    Route::get('/areas', [ResourceController::class, 'index'])->defaults('resource', 'areas')->name('areas.index');
    Route::get('/areas/{id}', [ResourceController::class, 'show'])->defaults('resource', 'areas')->name('areas.show');
    Route::post('/areas', [ResourceController::class, 'store'])->defaults('resource', 'areas')->name('areas.store');

    // Contactos: listado, detalle y creación.
    Route::get('/contacts', [ResourceController::class, 'index'])->defaults('resource', 'contacts')->name('contacts.index');
    Route::get('/contacts/{id}', [ResourceController::class, 'show'])->defaults('resource', 'contacts')->name('contacts.show');
    Route::post('/contacts', [ResourceController::class, 'store'])->defaults('resource', 'contacts')->name('contacts.store');

    // Galeria: listado ordenado, detalle y creación.
    Route::get('/galleries', [ResourceController::class, 'index'])->defaults('resource', 'galleries')->name('galleries.index');
    Route::get('/galleries/{id}', [ResourceController::class, 'show'])->defaults('resource', 'galleries')->name('galleries.show');
    Route::post('/galleries', [ResourceController::class, 'store'])->defaults('resource', 'galleries')->name('galleries.store');

    // Centros de formacion: listado, detalle y creación.
    Route::get('/training-centers', [ResourceController::class, 'index'])->defaults('resource', 'training-centers')->name('training-centers.index');
    Route::get('/training-centers/{id}', [ResourceController::class, 'show'])->defaults('resource', 'training-centers')->name('training-centers.show');
    Route::post('/training-centers', [ResourceController::class, 'store'])->defaults('resource', 'training-centers')->name('training-centers.store');

    // Computadores: listado, detalle y creación.
    Route::get('/computers', [ResourceController::class, 'index'])->defaults('resource', 'computers')->name('computers.index');
    Route::get('/computers/{id}', [ResourceController::class, 'show'])->defaults('resource', 'computers')->name('computers.show');
    Route::post('/computers', [ResourceController::class, 'store'])->defaults('resource', 'computers')->name('computers.store');

    // Cursos: listado, detalle y creación.
    Route::get('/courses', [ResourceController::class, 'index'])->defaults('resource', 'courses')->name('courses.index');
    Route::get('/courses/{id}', [ResourceController::class, 'show'])->defaults('resource', 'courses')->name('courses.show');
    Route::post('/courses', [ResourceController::class, 'store'])->defaults('resource', 'courses')->name('courses.store');

    // Instructores: listado, detalle y creación.
    Route::get('/teachers', [ResourceController::class, 'index'])->defaults('resource', 'teachers')->name('teachers.index');
    Route::get('/teachers/{id}', [ResourceController::class, 'show'])->defaults('resource', 'teachers')->name('teachers.show');
    Route::post('/teachers', [ResourceController::class, 'store'])->defaults('resource', 'teachers')->name('teachers.store');

    // Aprendices: listado, detalle y creación.
    Route::get('/apprentices', [ResourceController::class, 'index'])->defaults('resource', 'apprentices')->name('apprentices.index');
    Route::get('/apprentices/{id}', [ResourceController::class, 'show'])->defaults('resource', 'apprentices')->name('apprentices.show');
    Route::post('/apprentices', [ResourceController::class, 'store'])->defaults('resource', 'apprentices')->name('apprentices.store');
});
