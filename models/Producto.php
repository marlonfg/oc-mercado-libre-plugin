<?php namespace MarlonFreire\MercadoLibre\Models;

use MarlonFreire\MercadoLibre\App\Contracts\Selleable;
use MarlonFreire\MercadoLibre\App\Contracts\GatewayItemFields;
use MarlonFreire\MercadoLibre\App\Contracts\Synchronizable;
use MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre\MeliTools;
use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Models\Product;
use Model;

/**
 * Model
 */
class Producto extends Model implements Selleable, GatewayItemFields, Synchronizable
{
    use \October\Rain\Database\Traits\Validation;
    use MeliTools;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'marlonfreire_mercadolibre_productos';

    protected $fillable = [
        'meli_id', 'meli_condition', 'product_id'
    ];

    protected $appends = [

    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsTo = [
        'product' => [Product::class, 'delete' => true],
    ];

    //Implementaciones de metodos sobre interfaces

    //Selleable Contract
    public function getTitle()
    {
        return $this->friendly_url;
    }

    public function getTableAttribute()
    {
        return $this->getTable();
    }

    public function getFinalPriceAttribute()
    {
        return  $this->offer->price;
    }

    public function getFinalCurrencyAttribute()
    {
        return  $this->attributes['currency'];
    }


    //GatewayItemFields Contract

    public static function getStatusField()
    {
        return 'active';
    }

    public static function gatewayID()
    {
        return 'meli_id';
    }

    public static function getFieldsToFill()
    {
        //Atributos que se mezclan con los valores obtenidos del elemento obtenido de la api y que se insertaran solo los que esten contenidos
        //dentro del $fillable, pero que son necesarios luego para la sincronizacion
        return [
            'meli_id', 'name', 'meli_condition', 'meli_category', 'price', 'old_price', 'currency', 'description', 'created_at', 'updated_at'
        ];
    }

    public static function getFieldsToSync()
    {
        //Atributos que se mezclan con los valores obtenidos del elemento obtenido de la api y que se insertaran solo los que esten contenidos
        //dentro del $fillable, pero que son necesarios luego para la sincronizacion
        return [
            'nombre', 'descripcion', 'meli_category', 'qty', 'meli_condition', 'meli_listing_type', 'precio', 'currency'
        ];
    }

    public function isPublished()
    {
        return $this->getAttribute(self::gatewayID()) != null;
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
