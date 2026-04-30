<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::any('/nfe/emitir', function (Request $request) {
    try {
        $autoload = base_path('vendor/autoload.php');
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (!class_exists('NFePHP\NFe\Tools')) {
            return response()->json([
                'status' => 'erro', 
                'mensagem' => 'A biblioteca sped-nfe nao foi instalada corretamente.'
            ], 200); 
        }

        $certificadoBase64 = $request->input('certificado_base64');
        $senhaCertificado = $request->input('senha_certificado');
        $xmlRecebido = $request->input('xml_nota');

        if (!$certificadoBase64 || !$senhaCertificado || !$xmlRecebido) {
            return response()->json([
                'status' => 'erro', 
                'mensagem' => 'O Base44 nao enviou os dados.'
            ], 200);
        }

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
        $respostaSefaz = $tools->sefazEnviaLote([$xmlAssinado], 1);

        return response()->json(['status' => 'sucesso', 'retorno' => $respostaSefaz], 200);

    // MUDANÇA CRÍTICA AQUI: Usando Throwable para segurar Erros Fatais
    } catch (\Throwable $e) { 
        return response()->json([
            'status' => 'erro', 
            'mensagem' => 'ERRO FATAL PHP: ' . $e->getMessage() . ' na linha ' . $e->getLine()
        ], 200);
    }
});

