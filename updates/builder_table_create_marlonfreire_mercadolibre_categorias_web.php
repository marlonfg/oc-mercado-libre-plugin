<?php namespace MarlonFreire\MercadoLibre\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMarlonfreireMercadolibreCategoriasWeb extends Migration
{
    public function up()
    {
        if (Schema::hasTable('marlonfreire_mercadolibre_categorias_web')) {
            return;
        }
            
        Schema::create('marlonfreire_mercadolibre_categorias_web', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('category_meli_id')->nullable()->unsigned();
            $table->foreign('category_meli_id')->references('id')->on('marlonfreire_mercadolibre_categorias')->onDelete('cascade');
            $table->integer('category_id')->nullable()->unsigned();
            $table->foreign('category_id')->references('id')->on('lovata_shopaholic_categories')->onDelete('cascade');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {

    }
}
