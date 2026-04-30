Route::get('/limpar-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        return response('<h1>Cache destruído com sucesso. O motor está limpo.</h1>', 200);
    } catch (\Exception $e) {
        return response('Erro ao limpar cache: ' . $e->getMessage(), 500);
    }
});
