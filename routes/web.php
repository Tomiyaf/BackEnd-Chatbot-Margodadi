<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name', 'Chatbot Margodadi API'),
        'status' => 'API is running',
        'version' => '1.0.0',
    ]);
});
