<?php
namespace DipenduRoy\LumenZipkin;

use Illuminate\Support\ServiceProvider;
use DipenduRoy\LumenZipkin\ZipkinTrace;

class LumenZipkinServiceProvider extends ServiceProvider
{

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
        $this->app->singleton(ZipkinTrace::class, function ($app) {
            return new ZipkinTrace(!in_array('x-b3-traceid',array_keys(app('Illuminate\Http\Request')->headers->all())));
        });
        $this->app->singleton('Trace\ZipkinTrace', function ($app) {
            return $app->make(ZipkinTrace::class);
        });
    }
}