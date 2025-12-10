<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController; // 🔥 Ye line bahut zaroori hai

// Purana default route humne hata diya hai.
// Ab Home Page (/) par Task Manager khulega.

Route::get('/', [TaskController::class, 'index']); // Jarvis UI
Route::post('/ai-command', [TaskController::class, 'handleCommand'])->name('ai.command'); // AI Logic