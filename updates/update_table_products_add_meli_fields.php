<?php namespace MarlonFreire\MercadoLibre\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateTableProductsAddMeliFields extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('lovata_shopaholic_products')) {
            return;
        }

        Schema::table('lovata_shopaholic_products', function($table)
        {
            $table->string('meli_id')->nullable();
            $table->string('meli_condition')->nullable();
            $table->string('listing_type_id')->nullable();
            $table->integer('category_meli_id')->nullable()->unsigned();
        });
    }
    
    public function down()
    {

    }
}
