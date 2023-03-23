<?php namespace MarlonFreire\MercadoLibre\Models;

use Illuminate\Support\Facades\Session;
use MarlonFreire\MercadoLibre\App\Contracts\GatewayItemFields;
use Lovata\Shopaholic\Models\Category;
use Lovata\Shopaholic\Models\Product;
use MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre\MercadoLibre;
use Model;

/**
 * Model
 */
class Categoria extends Model implements GatewayItemFields
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'marlonfreire_mercadolibre_categorias';

    public $rules = [
        'name' => 'required',
        'slug' => 'required|unique:marlonfreire_mercadolibre_categorias',
    ];

    public $attributeNames = [
        'name' => 'lovata.toolbox::lang.field.name',
        'slug' => 'lovata.toolbox::lang.field.slug',
    ];

    public $slugs = ['slug' => 'name'];

    protected $fillable = [
        'meli_id', 'name', 'active', 'slug', 'parent_id'
    ];

    public $belongsToMany = [
        'category' => [
            Category::class,
            'table' => 'marlonfreire_mercadolibre_categorias_web',
            'key'   => 'category_meli_id',
            'otherKey'  =>  'category_id'
            ],
    ];

    public $hasMany = [
        'product' => [
            Product::class,
            'otherKey'   =>  'category_meli_id'
        ],
    ];

    /**
     * Before validate event handler
     */
    public function beforeValidate()
    {
        if (empty($this->slug)) {
            $this->slugAttributes();
        }
    }

    public function fetchFromMeli(){
        $meli_config = Configuracion::firstOrFail();
        if(!empty($meli_config->meli_app_id) && !empty($meli_config->meli_app_secret)) {
            $default_gateway = config('gateway.default_gateway');

            $gateway = new MercadoLibre();

            $val = config(sprintf('gateway.configured.%s.site_id', $default_gateway));

            $uri = $gateway->getConfigKey('routes.categories');

            $result = $gateway->get(str_replace('{param}', $val, $uri))->getResponseResult();

            $categorias = [];
            if ($result)
                foreach ($result as $key => $value)
                    $categorias[$value->id] = $value->name;

            return $categorias;
        }else{
            $status = "warning";
            $message = "Es obligatorio haber guardado las credenciales para realizar la sincronización";
            $actions = Session::get(config('gateway.flag_msg'), []);
            $actions[] = compact('status', 'message');

            Session::put(config('gateway.flag_msg'), $actions);

            return [];
        }

    }

    public static function getSelected(){

        $meli_config = Configuracion::firstOrFail();
        if(!empty($meli_config->meli_app_id) && !empty($meli_config->meli_app_secret)) {
            $selected = Categoria::whereNull('parent_id')->pluck('name')->toArray();

            return $selected;
        }else
            return [];
    }

    //GatewayItemFields Contract

    public static function getFieldsToFill()
    {
        return ['meli_id','name', 'parent_id'];
    }


    public static function gatewayID()
    {
        return 'meli_id';
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

    /**
     * Get by parent ID
     * @param Category $obQuery
     * @param string   $sData
     * @return Category
     */
    public function scopeGetByParentID($obQuery, $sData)
    {
        return $obQuery->where('parent_id', $sData);
    }
}
