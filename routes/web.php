<?php

use App\Http\Controllers\ApprenticeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ComputerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TrainingCenterController;
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
        $projectImages = collect(glob($folderPath . DIRECTORY_SEPARATOR . '*'))
            ->filter(fn ($file) => is_file($file))
            ->reject(fn ($file) => strtolower(basename($file)) === 'images.png')
            ->map(function ($file) {
                return (object) [
                    'title' => '',
                    'image_path' => 'imagene/Imagenes SENA/' . basename($file),
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
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); 
Route::get('apprentices/access', [AuthController::class, 'showApprenticeAccess'])->name('apprentices.access');
Route::post('apprentices/access', [AuthController::class, 'apprenticeAccess']);

Route::middleware('auth')->group(function () {
    Route::get('/admin/perfil', [AdminController::class, 'profile'])->name('admin.profile');
    Route::put('/admin/perfil', [AdminController::class, 'update'])->name('admin.profile.update');
});

// Contact Routes
Route::resource('contacts', ContactController::class);

// Gallery Routes
Route::resource('galleries', GalleryController::class);

// Training Centers Routes
Route::get('training-centers', [TrainingCenterController::class, 'index'])->name('training-centers.index');
Route::get('training-centers/create', [TrainingCenterController::class, 'create'])->name('training-centers.create');
Route::post('training-centers', [TrainingCenterController::class, 'store'])->name('training-centers.store');

// Computers Routes
Route::get('computers', [ComputerController::class, 'index'])->name('computers.index');
Route::get('computers/create', [ComputerController::class, 'create'])->name('computers.create');
Route::post('computers', [ComputerController::class, 'store'])->name('computers.store');
Route::put('computers/{computer}/assignment', [ComputerController::class, 'assign'])->name('computers.assignment');
Route::post('computers/{computer}/image', [ComputerController::class, 'uploadImage'])->name('computers.image.upload');
Route::delete('computers/{computer}/image', [ComputerController::class, 'deleteImage'])->name('computers.image.destroy');
Route::delete('computers/{computer}', [ComputerController::class, 'destroy'])->name('computers.destroy');

// Courses Routes
Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course}/info', [CourseController::class, 'information'])->name('courses.information');
Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
Route::post('courses', [CourseController::class, 'store'])->name('courses.store');

// Teachers Routes
Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
Route::get('teachers/create', [TeacherController::class, 'create'])->name('teachers.create');
Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');

// Apprentices Routes
Route::middleware('apprentice.access')->group(function () {
    Route::get('apprentices', [ApprenticeController::class, 'index'])->name('apprentices.index');
    Route::get('apprentices/create', [ApprenticeController::class, 'create'])->name('apprentices.create');
    Route::post('apprentices', [ApprenticeController::class, 'store'])->name('apprentices.store');
    Route::delete('apprentices/{apprentice}', [ApprenticeController::class, 'destroy'])->name('apprentices.destroy');
});
Route::get('courses/{course}/enroll', [ApprenticeController::class, 'enrollmentForm'])->name('courses.enroll.form');
Route::post('courses/{course}/enroll', [ApprenticeController::class, 'enroll'])->name('courses.enroll');
