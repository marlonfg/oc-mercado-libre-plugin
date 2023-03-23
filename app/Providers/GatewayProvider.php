<?php

namespace MarlonFreire\MercadoLibre\App\Providers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class GatewayProvider extends ServiceProvider
{
    protected $config;

    /**
     * GatewayProvider constructor.
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->config = config('gateway');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //Metodo insert para inserta valor en una hoja de un arbol en una coleccion recursiva
        if (!Collection::hasMacro('insert')) {

            Collection::macro('insert', function ($value) {
                array_walk_recursive($this,function(&$item,$key,$replace){
                    if(empty($item))
                        $item = $replace;
                },$value);
                return $this;
            });
        }
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //Registro los bindings para los prefijos de las rutas que esten en app.php
        $gateway = $this->getConfigKey('default_gateway');
        $bindings = $this->getConfigKey("configured.$gateway.sync.bindings");
        $passes = false;

        //Recorro la lista de rutas permitidas para el registro de los providers
        collect($bindings)->each(function ($b) use (&$passes) {
            return ($passes = request()->is($b)) ? false : true;
        });

        //if encuentro una ruta permitida inyecto en el contenedor de servicios las clases
        if ($passes){
            //Registro Pasarela, Suscripcion a eventos de Eloquent y Job de sincronizacion
//            $this->app['events']->listen(['eloquent.created: *','gateway.sync.create'], 'MarlonFreire\MercadoLibre\App\Listeners\GatewayEventsSubscriber@onModelCreated');
//            $this->app['events']->listen(['eloquent.updating: *','gateway.sync.updating'], 'MarlonFreire\MercadoLibre\App\Listeners\GatewayEventsSubscriber@onModelUpdating');
//            $this->app['events']->listen(['eloquent.updated: *','gateway.sync.update'], 'MarlonFreire\MercadoLibre\App\Listeners\GatewayEventsSubscriber@onModelUpdated');
//            $this->app['events']->listen(['eloquent.deleted: *','gateway.sync.delete'], 'MarlonFreire\MercadoLibre\App\Listeners\GatewayEventsSubscriber@onModelDeleted');
//            $this->app['events']->listen('medias.sync', 'MarlonFreire\MercadoLibre\App\Listeners\GatewayEventsSubscriber@onMediasSync');
//            $this->app['events']->listen('clear.tokens', 'App\Listeners\GatewayEventsSubscriber@onClearAccessTokens');
//            $this->app['events']->listen('gateway.sync', 'App\Listeners\GatewayEventsSubscriber@onSync');

//            $this->app['events']->listen('category.predict', 'App\Listeners\GatewayEventsSubscriber@onPredict');

//            $this->app->bind('MarlonFreire\MercadoLibre\App\Contracts\SyncJob',$this->getConfigKey("configured.$gateway.sync.job"));
        }
    }

    private function getConfigKey($path){
        $value = array_get($this->config,$path);
        if(is_null($value))
            throw new RuntimeException("La llave '$path' no existe en el listado de configuraciones de gateway.php");

        return $value;
    }
}
