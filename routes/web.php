<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
});
Route::post('/locale', [ProfileController::class, 'locale'])->name('locale');
Route::middleware(['auth', 'account'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/programs', [CatalogController::class, 'index'])->name('programs');
    Route::get('/programs/{program}', [CatalogController::class, 'program'])->name('programs.show');
    Route::get('/materials', [CatalogController::class, 'materials'])->name('materials');
    Route::get('/materials/{material}', [CatalogController::class, 'material'])->name('materials.show');
    Route::post('/materials/{material}/complete', [CatalogController::class, 'complete'])->name('materials.complete');
    Route::get('/packages/{package}', [PracticeController::class, 'package'])->name('packages.show');
    Route::post('/packages/{package}/start', [PracticeController::class, 'start'])->name('packages.start');
    Route::get('/practice/{attempt}', [PracticeController::class, 'show'])->name('practice.show');
    Route::post('/practice/{attempt}/answer', [PracticeController::class, 'answer'])->name('practice.answer');
    Route::get('/practice/{attempt}/status', [PracticeController::class, 'status'])->name('practice.status');
    Route::post('/practice/{attempt}/submit', [PracticeController::class, 'submit'])->name('practice.submit');
    Route::get('/results/{attempt}', [PracticeController::class, 'result'])->name('practice.result');
    Route::get('/history', [PracticeController::class, 'history'])->name('history');
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/import', [ImportController::class, 'index'])->name('import');
        Route::get('/import/template/{format}', [ImportController::class, 'template'])->name('import.template');
        Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('/import/confirm', [ImportController::class, 'confirm'])->name('import.confirm');
        Route::get('/results', [ResultController::class, 'index'])->name('results');
        Route::get('/results/export', [ResultController::class, 'export'])->name('results.export');
        Route::get('/devices', [StudentController::class, 'devices'])->name('devices');
        Route::post('/students/{student}/reset-device', [StudentController::class, 'resetDevice'])->name('students.reset-device');
        Route::put('/students/{student}/access', [StudentController::class, 'access'])->name('students.access');
        Route::get('/students', [StudentController::class, 'index'])->name('students');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::get('/content/{kind}', [ContentController::class, 'index'])->name('content.index');
        Route::get('/content/{kind}/create', [ContentController::class, 'create'])->name('content.create');
        Route::post('/content/{kind}', [ContentController::class, 'store'])->name('content.store');
        Route::get('/content/{kind}/{id}/edit', [ContentController::class, 'edit'])->name('content.edit');
        Route::put('/content/{kind}/{id}', [ContentController::class, 'update'])->name('content.update');
        Route::delete('/content/{kind}/{id}', [ContentController::class, 'archive'])->name('content.archive');
    });
});
