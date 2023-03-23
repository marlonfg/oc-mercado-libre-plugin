<?php

namespace MarlonFreire\MercadoLibre\App\Providers;

use Illuminate\Support\ServiceProvider;

class APITranslatorProvider extends ServiceProvider
{
     /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('MarlonFreire\MercadoLibre\App\Contracts\APITranslator',function () {
            $blueprint = config("translator.default.class");
            return new $blueprint(config("translator.default.api_key"));
         });
    }
}
