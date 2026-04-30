<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/nfe/emitir', function (Request $request) {
    try {
        $autoload = base_path('vendor/autoload.php');
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (!class_exists('NFePHP\NFe\Tools')) {
            return response()->json([
                'status' => 'erro', 
                'mensagem' => 'A biblioteca sped-nfe nao foi instalada corretamente.'
            ], 500);
        }

        $certificadoBase64 = $request->input('certificado_base64');
        $senhaCertificado = $request->input('senha_certificado');
        $xmlRecebido = $request->input('xml_nota');

        if (!$certificadoBase64 || !$senhaCertificado || !$xmlRecebido) {
            return response()->json(['status' => 'erro', 'mensagem' => 'Variaveis insuficientes no JSON.'], 400);
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

        return response()->json(['status' => 'sucesso', 'retorno' => $respostaSefaz]);

    } catch (\Exception $e) {
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 500);
    }
});
