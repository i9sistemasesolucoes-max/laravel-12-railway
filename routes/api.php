<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/nfe/emitir', function (Request $request) {
    return response()->json(['status' => 'sucesso', 'mensagem' => 'Porteira aberta!']);
});
