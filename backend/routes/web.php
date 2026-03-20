<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'spa');

if (app()->environment('local')) {
    Route::view('/__qa/chat-api', 'qa.chat-api');
}

Route::fallback(function () {
    return view('spa');
});
