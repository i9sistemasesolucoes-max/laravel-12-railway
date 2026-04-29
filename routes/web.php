<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Esta é a rota que mantém a sua tela inicial funcionando
Route::get('/', function () {
    return view('welcome');
});

// Esta é a porta nova que estamos abrindo para a Base44 transmitir a NF-e
Route::post('api/nfe/emitir', function (Request $request) {
    return response()->json([
        'status' => 'sucesso', 
        'mensagem' => 'Motor Fiscal Ativo - Transmissão Recebida'
    ]);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
