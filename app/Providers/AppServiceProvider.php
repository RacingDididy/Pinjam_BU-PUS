<?php

namespace App\Providers;

use App\Http\Controllers\BukuController;
use App\Models\Kategori;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            request()->header('X-Forwarded-Proto') === 'https'
            || str_contains((string) request()->getHost(), 'ngrok')
        ) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view): void {
            $rawCart = session('cart_pinjam', []);
            $cartPayload = BukuController::buildCartPayload(is_array($rawCart) ? $rawCart : []);
            $footerKategoris = Kategori::orderBy('nama_kategori')->take(14)->get();

            $view->with([
                'cartCount' => $cartPayload['total_qty'],
                'layoutCartPayload' => $cartPayload,
                'footerKategoris' => $footerKategoris,
            ]);
        });
    }
}
