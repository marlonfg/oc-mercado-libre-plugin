<?php namespace MarlonFreire\MercadoLibre\Models;

use MarlonFreire\MercadoLibre\App\Contracts\Synchronizable;
use Model;

/**
 * Model
 */
class Configuracion extends Model implements Synchronizable
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'marlonfreire_mercadolibre_configuracion';

    protected $fillable = ['meli_app_id','meli_app_secret',
        'meli_frequency_delay','meli_frequency_delay_check','meli_automatic_sync','meli_full_sync_date'];


    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public function getAppID()
    {
        return $this->meli_app_id;
    }

    public function getAppSecret()
    {
        return $this->meli_app_secret;
    }

    //Synchronizable Contract

    public function getFrecuency()
    {
        // TODO: Implement getFrecuency() method.
    }

    public function isEnabled()
    {
        // TODO: Implement isEnabled() method.
    }

    public function hasDelay()
    {
        // TODO: Implement hasDelay() method.
    }
}
