<?php namespace MarlonFreire\MercadoLibre\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMarlonfreireMercadolibreCategorias extends Migration
{
    public function up()
    {
        if (Schema::hasTable('marlonfreire_mercadolibre_categorias')) {
            return;
        }

        Schema::create('marlonfreire_mercadolibre_categorias', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('meli_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->default(0);
            $table->integer('parent_id')->nullable()->unsigned();
            $table->integer('nest_left')->nullable()->unsigned();
            $table->integer('nest_right')->nullable()->unsigned();
            $table->integer('nest_depth')->nullable()->unsigned();
            $table->timestamps();
        });
    }
    
    public function down()
    {

    }
}
