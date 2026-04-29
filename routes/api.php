<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/nfe/emitir', function (Request $request) {
    try {
        // --- SEU CÓDIGO DE EMISSÃO COMEÇA AQUI ---
        
        $certificadoBase64 = $request->input('certificado_base64');
        $senhaCertificado = $request->input('senha_certificado');
        $xmlRecebido = $request->input('xml_nota');

        // Se faltar algum dado, o PHP vai te avisar agora
        if (!$certificadoBase64) throw new \Exception("Faltando certificado_base64");
        if (!$senhaCertificado) throw new \Exception("Faltando senha_certificado");
        if (!$xmlRecebido) throw new \Exception("Faltando xml_nota");

        $configJson = json_encode([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => 2,
            "razaosocial" => $request->input('razao_social', 'EMITENTE'),
            "siglaUF" => $request->input('uf', 'SP'),
            "cnpj" => preg_replace('/[^0-9]/', '', $request->input('cnpj_emitente')),
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
        ]);

        $certificado = \NFePHP\Common\Certificate::readPfx(base64_decode($certificadoBase64), $senhaCertificado);
        $tools = new \NFePHP\NFe\Tools($configJson, $certificado);

        $xmlAssinado = $tools->signNFe($xmlRecebido);
        
        return response()->json([
            'status' => 'sucesso',
            'mensagem' => 'Nota assinada com sucesso!',
            'xml' => $xmlAssinado
        ]);

    } catch (\Throwable $e) {
        // ISSO AQUI É A CHAVE: Ele vai devolver o erro real como texto JSON
        return response()->json([
            'status' => 'erro_tecnico',
            'mensagem' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine()
        ], 500);
    }
});
