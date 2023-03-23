<?php namespace MarlonFreire\MercadoLibre\Models;

use Lovata\Shopaholic\Models\Category;
use Lovata\Shopaholic\Models\Product;
use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\NestedTree;

/**
 * Model
 */
class WebCategoria extends Model
{
    use Validation;
    use NestedTree;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'marlonfreire_mercadolibre_categorias_web';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required',
        'slug' => 'required|unique:marlonfreire_mercadolibre_categorias_web',
    ];

    public $slugs = ['slug' => 'name'];

    protected $fillable = [
        'name', 'slug', 'category_id', 'parent_id', 'active'
    ];

    public $attachOne = [ 'image' => 'System\Models\File'];

    public $belongsTo = [
        'category' => [Category::class],
    ];

    public $belongsToMany = [
        'product' => [
            Product::class,
            'table' => 'lovata_shopaholic_additional_categories',
            'key' => 'category_id',
            'otherKey' => 'product_id',
        ],
    ];

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
