<?php

use Illuminate\Support\Facades\Route;

Route::post('/nfe/emitir', function () {
    return response()->json([
        'status' => 'sucesso',
        'mensagem' => 'O estagiário acertou o caminho',
        'motor' => 'Neuraif Online'
    ]);
});
