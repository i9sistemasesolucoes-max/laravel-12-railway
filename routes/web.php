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
        // 1. Recebe as variáveis dinâmicas enviadas pela Base44
        $xmlRecebido = $request->input('xml_nota');
        $certificadoBase64 = $request->input('certificado_base64'); // O arquivo .pfx convertido em texto Base64
        $senhaCertificado = $request->input('senha_certificado');
        $cnpjEmitente = $request->input('cnpj_emitente');
        $razaoSocial = $request->input('razao_social');
        $uf = $request->input('uf');
        
        // 2. Valida se a Base44 mandou tudo o que precisa
        if (!$xmlRecebido || !$certificadoBase64 || !$senhaCertificado || !$cnpjEmitente) {
            return response()->json(['status' => 'erro', 'mensagem' => 'Faltam variaveis: xml_nota, certificado_base64, senha_certificado ou cnpj_emitente.'], 400);
        }

        // 3. Converte o certificado de volta para o formato original
        $certificadoPfx = base64_decode($certificadoBase64);

        // 4. Monta a configuração dinamicamente para o CNPJ que está emitindo a nota no momento
        $configJson = json_encode([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => 2, // 2 = Homologação (Testes), 1 = Produção
            "razaosocial" => $razaoSocial,
            "siglaUF" => $uf,
            "cnpj" => preg_replace('/[^0-9]/', '', $cnpjEmitente), // Limpa pontos e traços do CNPJ
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
        ]);

        // 5. Instancia o motor fiscal com as variáveis dinâmicas
        $certificado = Certificate::readPfx($certificadoPfx, $senhaCertificado);
        $tools = new Tools($configJson, $certificado);

        // 6. Assina o XML e transmite para a SEFAZ
        $xmlAssinado = $tools->signNFe($xmlRecebido);
        $respostaSefaz = $tools->sefazEnviaLote([$xmlAssinado], 1);

        // Retorna o recibo de sucesso para o painel
        return response()->json(['status' => 'sucesso', 'recibo_sefaz' => $respostaSefaz]);

    } catch (\Exception $e) {
        // Retorna o erro caso a senha venha errada da Base44 ou a SEFAZ rejeite
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 400);
    }
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
