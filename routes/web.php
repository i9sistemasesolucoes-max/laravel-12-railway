<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

// Mantém a tela inicial funcionando
Route::get('/', function () {
    return view('welcome');
});

// A porta da Base44 para transmitir a NF-e
Route::post('api/nfe/emitir', function (Request $request) {
    try {
        // 1. Verifica se o certificado digital foi colocado na pasta correta
        $caminhoCertificado = storage_path('app/certificado.pfx');
        if (!file_exists($caminhoCertificado)) {
            return response()->json(['status' => 'erro', 'mensagem' => 'Certificado digital não encontrado no servidor. Coloque o arquivo certificado.pfx na pasta storage/app'], 400);
        }

        // 2. Lê o certificado digital
        $certificadoDigital = file_get_contents($caminhoCertificado);
        
        // ATENÇÃO: TROQUE A PALAVRA ABAIXO PELA SENHA REAL DO SEU CERTIFICADO
        $senhaCertificado = 'COLOQUE_SUA_SENHA_AQUI'; 

        // 3. Configuração básica (Altere com os dados reais da empresa que vai emitir)
        $configJson = json_encode([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => 2, // 2 = Homologação (Testes), 1 = Produção
            "razaosocial" => "NOME DA EMPRESA LTDA",
            "siglaUF" => "SP",
            "cnpj" => "00000000000000",
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
        ]);

        $certificado = Certificate::readPfx($certificadoDigital, $senhaCertificado);
        $tools = new Tools($configJson, $certificado);

        // 4. Pega o XML enviado pela Base44
        $xmlRecebido = $request->input('xml_nota') ?? $request->getContent();
        if (empty($xmlRecebido)) {
            return response()->json(['status' => 'erro', 'mensagem' => 'O XML da nota não foi enviado na requisição.'], 400);
        }

        // 5. Assina o XML e transmite para a SEFAZ
        $xmlAssinado = $tools->signNFe($xmlRecebido);
        $respostaSefaz = $tools->sefazEnviaLote([$xmlAssinado], 1);

        // Retorna o sucesso para o seu painel
        return response()->json(['status' => 'sucesso', 'recibo_sefaz' => $respostaSefaz]);

    } catch (\Exception $e) {
        // Retorna o erro exato caso a senha esteja errada ou o XML tenha problemas
        return response()->json(['status' => 'erro', 'mensagem' => $e->getMessage()], 400);
    }
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
