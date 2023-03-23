<?php namespace MarlonFreire\MercadoLibre\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateTableOffersAddPriceMeli extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('lovata_shopaholic_offers')) {
            return;
        }

        Schema::table('lovata_shopaholic_offers', function($table)
        {
            $table->decimal('price_meli', 15, 2)->nullable();
        });
    }
    
    public function down()
    {

    }
}
