<?php namespace MarlonFreire\MercadoLibre\Updates;

use MarlonFreire\MercadoLibre\Models\Configuracion;
use Seeder;

class SeederCreateConfiguracion extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Configuracion::firstOrCreate([
            'meli_app_id' => env('MERCADOLIBRE_APP_ID')??'',
            'meli_app_secret' => env('MERCADOLIBRE_SECRET_KEY')??'',
        ]);
    }
}
