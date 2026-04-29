<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

Route::get('/', function () {
    return view('welcome');
});

Route::post('api/nfe/emitir', function (Request $request) {
    try {
        // Verifica se a biblioteca foi instalada corretamente
        if (!class_exists('NFePHP\NFe\Tools')) {
            return response()->json(['status' => 'erro', 'mensagem' => 'Biblioteca NFePHP nao encontrada. Verifique o composer.'], 500);
        }

        $xmlRecebido = $request->input('xml_nota');
        $certificadoBase64 = $request->input('certificado_base64');
        $senhaCertificado = $request->input('senha_certificado');
        $cnpjEmitente = $request->input('cnpj_emitente');

        if (!$xmlRecebido || !$certificadoBase64 || !$senhaCertificado) {
            return response()->json(['status' => 'erro', 'mensagem' => 'Dados incompletos no JSON.'], 400);
        }

        $configJson = json_encode([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => 2,
            "razaosocial" => $request->input('razao_social', 'EMITENTE'),
            "siglaUF" => $request->input('uf', 'SP'),
            "cnpj" => preg_replace('/[^0-9]/', '', $cnpjEmitente),
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
        ]);

        $certificado = Certificate::readPfx(base64_decode($certificadoBase64), $senhaCertificado);
        $tools = new Tools($configJson, $certificado);

        $xmlAssinado = $tools->signNFe($xmlRecebido);
        $respostaSefaz = $tools->sefazEnviaLote([$xmlAssinado], 1);

        return response()->json(['status' => 'sucesso', 'retorno' => $respostaSefaz]);

    } catch (\Exception $e) {
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 500);
    }
});
