<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

// Função auxiliar para instanciar o motor em todas as rotas
function iniciarMotorFiscal(Request $request) {
    $certificadoBase64 = $request->input('certificado_base64');
    $senhaCertificado = $request->input('senha_certificado');
    $cnpjEmitente = $request->input('cnpj_emitente');
    $razaoSocial = $request->input('razao_social');
    $uf = $request->input('uf');

    if (!$certificadoBase64 || !$senhaCertificado || !$cnpjEmitente) {
        throw new \Exception('Faltam credenciais obrigatórias: certificado_base64, senha_certificado ou cnpj_emitente.');
    }

    $configJson = json_encode([
        "atualizacao" => date('Y-m-d H:i:s'),
        "tpAmb" => 2, // 2 = Homologação / 1 = Produção
        "razaosocial" => $razaoSocial ?? 'EMPRESA EMITENTE',
        "siglaUF" => $uf ?? 'SP',
        "cnpj" => preg_replace('/[^0-9]/', '', $cnpjEmitente),
        "schemes" => "PL_009_V4",
        "versao" => "4.00",
    ]);

    $certificado = Certificate::readPfx(base64_decode($certificadoBase64), $senhaCertificado);
    return new Tools($configJson, $certificado);
}

// ---------------------------------------------------
// ROTAS DA API - MOTOR FISCAL NEURAIF
// ---------------------------------------------------

Route::get('/', function () { return view('welcome'); });

// 1. Emitir NF-e
Route::post('api/nfe/emitir', function (Request $request) {
    try {
        $tools = iniciarMotorFiscal($request);
        $xmlRecebido = $request->input('xml_nota');
        if (!$xmlRecebido) throw new \Exception('XML da nota não informado.');

        $xmlAssinado = $tools->signNFe($xmlRecebido);
        $respostaSefaz = $tools->sefazEnviaLote([$xmlAssinado], 1);
        return response()->json(['status' => 'sucesso', 'retorno' => $respostaSefaz]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 400);
    }
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// 2. Cancelar NF-e
Route::post('api/nfe/cancelar', function (Request $request) {
    try {
        $tools = iniciarMotorFiscal($request);
        $chave = $request->input('chave_nfe');
        $protocolo = $request->input('protocolo_autorizacao');
        $justificativa = $request->input('justificativa');

        if (!$chave || !$protocolo || !$justificativa) throw new \Exception('Chave, protocolo ou justificativa ausentes.');

        $respostaSefaz = $tools->sefazCancela($chave, $justificativa, $protocolo);
        return response()->json(['status' => 'sucesso', 'retorno' => $respostaSefaz]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 400);
    }
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// 3. Carta de Correção (CC-e)
Route::post('api/nfe/cce', function (Request $request) {
    try {
        $tools = iniciarMotorFiscal($request);
        $chave = $request->input('chave_nfe');
        $correcao = $request->input('texto_correcao');
        $sequencia = $request->input('sequencia_evento') ?? 1;

        if (!$chave || !$correcao) throw new \Exception('Chave ou texto de correção ausentes.');

        $respostaSefaz = $tools->sefazCCe($chave, $correcao, $sequencia);
        return response()->json(['status' => 'sucesso', 'retorno' => $respostaSefaz]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 400);
    }
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// 4. Consultar Status da NF-e
Route::post('api/nfe/consultar', function (Request $request) {
    try {
        $tools = iniciarMotorFiscal($request);
        $chave = $request->input('chave_nfe');

        if (!$chave) throw new \Exception('Chave da NF-e ausente.');

        $respostaSefaz = $tools->sefazConsultaChave($chave);
        return response()->json(['status' => 'sucesso', 'retorno' => $respostaSefaz]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 400);
    }
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
