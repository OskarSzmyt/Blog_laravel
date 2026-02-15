<?php

use App\Http\Controllers\ForumController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ForumController::class, 'index'])->name('forum.index');
Route::post('/', [ForumController::class, 'submit'])->name('forum.submit');
Route::get('/captcha-img', [ForumController::class, 'captcha'])->name('forum.captcha');
Route::get('/image/{id}', [ForumController::class, 'image'])->whereNumber('id')->name('forum.image');
Route::get('/logout', [ForumController::class, 'logout'])->name('forum.logout');
