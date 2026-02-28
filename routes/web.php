<?php
use App\Http\Controllers\QuizController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/dashboard', [QuizController::class, 'index'])->name('dashboard');
Route::get('/quiz/{id}', [QuizController::class, 'show'])->name('quiz.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [QuizController::class, 'index'])->name('dashboard');
    Route::get('/quiz/{id}', [QuizController::class, 'show'])->name('quiz.show');
});
