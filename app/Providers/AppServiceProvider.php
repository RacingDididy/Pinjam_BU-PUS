<?php

namespace App\Providers;

use App\Http\Controllers\BukuController;
use App\Models\Kategori;
use Database\Seeders\KoleksiPerpustakaanSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
            || str_contains((string) request()->getHost(), 'wasmer.app')
        ) {
            URL::forceScheme('https');
        }

        if (! $this->app->runningInConsole() && config('database.default') === 'sqlite') {
            try {
                $dbPath = config('database.connections.sqlite.database');
                if ($dbPath && $dbPath !== ':memory:' && ! file_exists($dbPath)) {
                    $dir = dirname($dbPath);
                    if (! is_dir($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    @touch($dbPath);
                }

                if (! Schema::hasTable('kategoris')) {
                    Artisan::call('migrate', ['--force' => true, '--seed' => true]);
                } else {
                    if (Schema::hasTable('bukus') && ! Schema::hasColumn('bukus', 'tanggal_terbit')) {
                        Artisan::call('migrate', ['--force' => true]);
                    }

                    $seedLock = ($dbPath && $dbPath !== ':memory:') ? (dirname($dbPath).'/koleksi_500_v3.lock') : null;
                    if ($seedLock && ! file_exists($seedLock)) {
                        Artisan::call('db:seed', [
                            '--class' => KoleksiPerpustakaanSeeder::class,
                            '--force' => true,
                        ]);
                        @file_put_contents($seedLock, now()->toIso8601String());
                    }
                }
            } catch (Throwable) {
                //
            }
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
