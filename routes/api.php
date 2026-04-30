<?php

use Illuminate\Support\Facades\Route;

Route::any('/nfe/emitir', function () {
    return response()->json([
        'status' => 'sucesso',
        'motor' => 'Neuraif Online'
    ]);
});
