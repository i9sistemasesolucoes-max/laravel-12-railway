} catch (\Throwable $e) {
    // Isso vai retornar o erro detalhado para o seu painel de transmissão
    return response($e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine(), 500)
                  ->header('Content-Type', 'text/plain');
}
