<?php

namespace DipenduRoy\LumenZipkin;

use Illuminate\Support\ServiceProvider;
use DipenduRoy\LumenZipkin\LumenZipkinController;

class LumenZipkinServiceProvider extends ServiceProvider {
    
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //Register Our Package routes
        include __DIR__.'/routes.php';
        
        // Let Laravel Ioc Container know about our Controller
        $this->app->make('DipenduRoy\LumenZipkin\LumenZipkinController');
        //$this->app->make(LumenZipkinController::class);
    }
    
}