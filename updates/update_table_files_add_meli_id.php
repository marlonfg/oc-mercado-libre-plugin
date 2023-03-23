<?php namespace MarlonFreire\MercadoLibre\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateTableFilesAddMeliId extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('system_files')) {
            return;
        }

        Schema::table('system_files', function($table)
        {
            $table->string('meli_id')->nullable();
        });
    }
    
    public function down()
    {

    }
}
