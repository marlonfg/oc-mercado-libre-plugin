<?php namespace MarlonFreire\MercadoLibre\Models;

use MarlonFreire\MercadoLibre\App\Contracts\GatewayItemFields;
use Model;
use System\Models\File;

/**
 * Model
 */
class Media extends Model implements GatewayItemFields
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
    public $table = 'marlonfreire_mercadolibre_medias';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = [
        'meli_id', 'media_id'
    ];

    public $belongsTo = [
        'media' => [File::class, 'delete' => true],
    ];

    //GatewayItemFields Contract


    public static function gatewayID()
    {
        return 'meli_id';
    }

    public static function getFieldsToFill()
    {
        // TODO: Implement getFieldsToSync() method.
    }

    public static function getFieldsToSync()
    {
        // TODO: Implement getFieldsToSync() method.
    }

    public static function getStatusField()
    {
        // TODO: Implement getStatusField() method.
    }

    public function isPublished()
    {
        return $this->getAttribute(self::gatewayID()) != null;
    }
}
