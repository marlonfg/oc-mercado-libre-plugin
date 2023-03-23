<?php namespace MarlonFreire\MercadoLibre\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMarlonfreireMercadolibreConfiguracion extends Migration
{
    public function up()
    {
        if (Schema::hasTable('marlonfreire_mercadolibre_configuracion')) {
            return;
        }

        Schema::create('marlonfreire_mercadolibre_configuracion', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('meli_app_id');
            $table->string('meli_app_secret');
            $table->smallInteger('meli_frequency_delay')->default(0);
            $table->boolean('meli_frequency_delay_check')->default(0);
            $table->boolean('meli_automatic_sync')->default(0);
            $table->boolean('partial')->default(0);
            $table->dateTime('meli_full_sync_date')->nullable();
        });
    }
    
    public function down()
    {

    }
}
