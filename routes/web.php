<?php

use App\Http\Controllers\Api\ResourceController;
use App\Models\Gallery;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', function () {
    try {
        $databaseGalleries = Gallery::where('is_active', 1)->orderBy('order')->get();
    } catch (QueryException $exception) {
        Log::warning('Unable to load the home page gallery; using project images.', [
            'exception' => $exception,
        ]);

        $databaseGalleries = collect();
    }

    $folderPath = public_path('imagene/Imagenes SENA');
    $projectImages = collect();

    if (is_dir($folderPath)) {
        $projectImages = collect(glob($folderPath.DIRECTORY_SEPARATOR.'*'))
            ->filter(fn ($file) => is_file($file))
            ->reject(fn ($file) => strtolower(basename($file)) === 'images.png')
            ->map(function ($file) {
                return (object) [
                    'title' => '',
                    'image_path' => 'imagene/Imagenes SENA/'.basename($file),
                    'description' => '',
                    'order' => 0,
                    'is_active' => true,
                ];
            });
    }

    $galleries = $databaseGalleries->isNotEmpty() ? $databaseGalleries : $projectImages;

    return view('welcome', ['galleries' => $galleries]);
});

// Authentication Routes
Route::get('/login', [ResourceController::class, 'authShowLogin'])->name('login');
Route::post('/login', [ResourceController::class, 'authLogin'])->name('auth.login');
Route::get('/register', [ResourceController::class, 'authShowRegister'])->name('register');
Route::post('/register', [ResourceController::class, 'authRegister'])->name('auth.register');
Route::post('/logout', [ResourceController::class, 'authLogout'])->name('logout');
Route::get('apprentices/access', [ResourceController::class, 'authShowApprenticeAccess'])->name('apprentices.access');
Route::post('apprentices/access', [ResourceController::class, 'authApprenticeAccess']);

Route::middleware('auth')->group(function () {
    Route::get('/admin/perfil', [ResourceController::class, 'adminProfile'])->name('admin.profile');
    Route::put('/admin/perfil', [ResourceController::class, 'adminUpdate'])->name('admin.profile.update');
});

// Contact Routes
Route::get('contacts', [ResourceController::class, 'contactIndex'])->name('contacts.index');
Route::get('contacts/create', [ResourceController::class, 'contactCreate'])->name('contacts.create');
Route::post('contacts', [ResourceController::class, 'contactStore'])->name('contacts.store');
Route::get('contacts/{contact}', [ResourceController::class, 'contactShow'])->name('contacts.show');
Route::get('contacts/{contact}/edit', [ResourceController::class, 'contactEdit'])->name('contacts.edit');
Route::match(['PUT', 'PATCH'], 'contacts/{contact}', [ResourceController::class, 'contactUpdate'])->name('contacts.update');
Route::delete('contacts/{contact}', [ResourceController::class, 'contactDestroy'])->name('contacts.destroy');

// Gallery Routes
Route::get('galleries', [ResourceController::class, 'galleryIndex'])->name('galleries.index');
Route::get('galleries/create', [ResourceController::class, 'galleryCreate'])->name('galleries.create');
Route::post('galleries', [ResourceController::class, 'galleryStore'])->name('galleries.store');
Route::get('galleries/{gallery}', [ResourceController::class, 'galleryShow'])->name('galleries.show');
Route::get('galleries/{gallery}/edit', [ResourceController::class, 'galleryEdit'])->name('galleries.edit');
Route::match(['PUT', 'PATCH'], 'galleries/{gallery}', [ResourceController::class, 'galleryUpdate'])->name('galleries.update');
Route::delete('galleries/{gallery}', [ResourceController::class, 'galleryDestroy'])->name('galleries.destroy');

// Training Centers Routes
Route::get('training-centers', [ResourceController::class, 'trainingCenterIndex'])->name('training-centers.index');
Route::get('training-centers/create', [ResourceController::class, 'trainingCenterCreate'])->name('training-centers.create');
Route::post('training-centers', [ResourceController::class, 'trainingCenterStore'])->name('training-centers.store');

// Computers Routes
Route::get('computers', [ResourceController::class, 'computerIndex'])->name('computers.index');
Route::get('computers/create', [ResourceController::class, 'computerCreate'])->name('computers.create');
Route::post('computers', [ResourceController::class, 'computerStore'])->name('computers.store');
Route::put('computers/{computer}/assignment', [ResourceController::class, 'computerAssign'])->name('computers.assignment');
Route::post('computers/{computer}/image', [ResourceController::class, 'computerUploadImage'])->name('computers.image.upload');
Route::delete('computers/{computer}/image', [ResourceController::class, 'computerDeleteImage'])->name('computers.image.destroy');
Route::delete('computers/{computer}', [ResourceController::class, 'computerDestroy'])->name('computers.destroy');

// Courses Routes
Route::get('courses', [ResourceController::class, 'courseIndex'])->name('courses.index');
Route::get('courses/{course}/info', [ResourceController::class, 'courseInformation'])->name('courses.information');
Route::get('courses/create', [ResourceController::class, 'courseCreate'])->name('courses.create');
Route::post('courses', [ResourceController::class, 'courseStore'])->name('courses.store');

// Teachers Routes
Route::get('teachers', [ResourceController::class, 'teacherIndex'])->name('teachers.index');
Route::get('teachers/create', [ResourceController::class, 'teacherCreate'])->name('teachers.create');
Route::post('teachers', [ResourceController::class, 'teacherStore'])->name('teachers.store');

// Apprentices Routes
Route::middleware('apprentice.access')->group(function () {
    Route::get('apprentices', [ResourceController::class, 'apprenticeIndex'])->name('apprentices.index');
    Route::get('apprentices/create', [ResourceController::class, 'apprenticeCreate'])->name('apprentices.create');
    Route::post('apprentices', [ResourceController::class, 'apprenticeStore'])->name('apprentices.store');
    Route::delete('apprentices/{apprentice}', [ResourceController::class, 'apprenticeDestroy'])->name('apprentices.destroy');
});
Route::get('courses/{course}/enroll', [ResourceController::class, 'apprenticeEnrollmentForm'])->name('courses.enroll.form');
Route::post('courses/{course}/enroll', [ResourceController::class, 'apprenticeEnroll'])->name('courses.enroll');
