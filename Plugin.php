<?php namespace MarlonFreire\MercadoLibre;

use System\Classes\PluginBase;
use Lovata\Shopaholic\Models\Product;
use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Controllers\Products;
use MarlonFreire\MercadoLibre\Models\Categoria;

class Plugin extends PluginBase
{

    public $require = ['Lovata.Shopaholic', 'MarlonFreire.Sitios'];

    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function registerFormWidgets()
    {
        return [
            'MarlonFreire\MercadoLibre\FormWidgets\SelectMultiple' => [
                'label' => 'marlonfreire.mercadolibre::lang.category',
                'code' => 'SelectMultiple'
            ]
        ];
    }

    public function boot()
    {
        // Agregar campos y funciones al modelo Product
        Product::extend(function ($model) {
            $model->addFillable([
                'meli_id',
                'meli_condition',
                'listing_type_id',
                'category_meli_id',
            ]);

            //Agregar a fields.yaml
            $model->addDynamicField('description', [
                'label' => 'Descripcion',
                'size' => 'large',
                'span' => 'auto',
                'context' => -'update' -'preview',
                'type' => 'textarea',
                'tab' => 'Mercado Libre',
                'permissions' =>
                    - 'manage_sync'
            ]);

            $model->addDynamicField('listing_type_id', [
                'label' => 'Tipo de Publicacion',
                'options' => 'getApprovedListingTypes',
                'showSearch' => 'true',
                'span' => 'auto',
                'context' => -'update' -'preview',
                'defaultFrom' => 'listing_type_id',
                'type' => 'dropdown',
                'tab' => 'Mercado Libre',
                'permissions' =>
                    - 'manage_sync'
            ]);

            $model->addDynamicField('meli_condition', [
                'label' => 'Condicion',
                'options' => [
                    'new' => 'Nuevo',
                    'used' => 'Usado',
                    'not_specified' => 'Sin especificar'
                    ],
                'emptyOption' => 'new',
                'showSearch' => 'true',
                'span' => 'left',
                'context' => -'update' -'preview',
                'type' => 'dropdown',
                'tab' => 'Mercado Libre',
                'permissions' =>
                    - 'manage_sync'
            ]);

            $model->addDynamicField('category_meli', [
                'label' => 'Categoria',
                'nameFrom' => 'name',
                'descriptionFrom' => 'description',
                'span' => 'auto',
                'context' => -'update' -'preview',
                'type' => 'relation',
                'tab' => 'Mercado Libre',
                'permissions' =>
                    - 'manage_sync'
            ]);

            //Agregar a columns.yaml
            $model->addDynamicColumn('meli_id', [
                'label' => 'Publicado',
                'type' => 'switch',
                'searchable' => 'true',
                'sortable' => 'true',
                'permissions' => -'manage_sync'
            ]);

            $model->belongsTo['category_meli'] = [Categoria::class];

            $model->addDynamicMethod('isPublished', function() {
                return $this->meli_id != null;
            });

            $model->addDynamicMethod('getApprovedListingTypes', function(){
                $meli_config = Configuracion::first();
                if($meli_config && $meli_config->meli_app_id && $meli_config->meli_app_secret) {

                    $blueprint = config(sprintf('gateway.configured.%s.sync.class', config('gateway.default_gateway')));

                    $gateway = new $blueprint();
                    if ($gateway->isConnected()) {
                        $result = $gateway->get(str_replace('{param}', $gateway->getConfigKey('site_id'), $gateway->getConfigKey('routes.listing_types.general')), [
                        ]);

                        if ($result->isSuccesfullResponse()) {
                            $listing = [];
                            foreach ($result->getResponseResult() as $key => $value)
                                $listing[$value->id] = $value->name;

                            return $listing;
                        }
                    }
                }

                return [];
            });

            $model->addDynamicMethod('afterSave', function(){
                $meli_config = Configuracion::first();
                if($meli_config && $meli_config->meli_automatic_sync){
                    if($this->preview_image && $this->offer->isNotEmpty()){
                        $producto = Producto::where('product_id', $this->id)->first();
                        if($producto){
                            $job = $producto->isPublished() ?
                                new MercadoLibreSyncJob('updated', $producto) :
                                new MercadoLibreSyncJob('created', $producto);
                        }else{
                            $producto = Producto::create(['product_id' => $this->id, 'meli_condition'=>Input::get('Product.meli_condition')]);
                            $job = new MercadoLibreSyncJob('created', $producto);
                        }
                        dispatch_now($job);
                    }
                }
            });

            $model->addDynamicMethod('beforeDelete', function(){
                $meli_config = Configuracion::firstOrFail();
                if(!empty($meli_config->meli_app_id) && !empty($meli_config->meli_app_secret)){
                    $producto = Producto::where('product_id', $this->id)->first();
                    if($producto){
                        $job = new MercadoLibreSyncJob('deleted', $producto);
                        dispatch_now($job);
                    }
                }

                if($this->preview_image)
                    $this->preview_image->delete();
            });
        });

        // Agregar campos y funciones al modelo Offer
        Offer::extend(function ($model) {
            $model->addFillable([
                'price_meli',
            ]);

            $model->addCached([
                'price_meli',
            ]);

            $model->addDynamicMethod('getPriceMeliAttribute', function(){
                return !empty($this->attributes['price_meli']) ? $this->attributes['price_meli'] : 0;
            });

            $model->addDynamicMethod('getPriceListAttribute', function(){
                $arResult = [];

                foreach ($this->price_link as $obPrice) {
                    $arResult[$obPrice->price_type_id] = [
                        'price'     => $obPrice->price_value,
                        'old_price' => $obPrice->old_price_value,
                        'price_meli' => $this->price_meli,
                        'sale_price' => $this->sale_price,
                    ];
                }

                return $arResult;
            });

            $model->addDynamicMethod('setPriceListAttribute', function($arPriceList){
                if (empty($arPriceList) || !is_array($arPriceList)) {
                    return;
                }

                if (isset($arPriceList[0])) {
                    $this->fSavedPrice = array_get($arPriceList[0], 'price');
                    $this->fSavedOldPrice = array_get($arPriceList[0], 'old_price');
                    if(array_key_exists('price_meli', $arPriceList[0]))
                        $this->price_meli = array_get($arPriceList[0], 'price_meli');
                    if(array_key_exists('sale_price', $arPriceList[0]))
                		$this->sale_price = array_get($arPriceList[0], 'sale_price');
                    $this->save();
                    unset($arPriceList[0]);
                }

                $this->arSavedPriceList = $arPriceList;
            });

        });

        //Agregar funciones al controller Products de Shopaholic
        Products::extend(function ($controller) {
            $controller->addDynamicMethod('onMeliUpdate', function () {
                // Código para la función onMeliUpdate
                if(Input::has('Product')){
                    $prod_shop = Product::whereName(Input::get('Product.name'))->first();
                    if($prod_shop && $prod_shop->preview_image && $prod_shop->offer->isNotEmpty()){
                        $producto = Producto::where('product_id', $prod_shop->id)->first();
                        if($producto){
                            $job = $producto->isPublished() ?
                                new MercadoLibreSyncJob('updated', $producto) :
                                new MercadoLibreSyncJob('created', $producto);
                        }else{
                            $producto = Producto::create(['product_id' => $prod_shop->id, 'meli_condition'=>Input::get('Product.meli_condition')]);
                            $job = new MercadoLibreSyncJob('created', $producto);
                        }
                        dispatch_now($job);

                        return back();

                    }

                }
            });

            $controller->addDynamicMethod('onMeliUpdateSelected', function () {
                // Código para la función onMeliUpdateSelected
                if(Input::has('checked')){

                    $ids = Input::get('checked');

                    foreach($ids as $id){
                        $prod_shop = Product::findOrFail($id);
                        if($prod_shop && $prod_shop->preview_image && $prod_shop->offer->isNotEmpty()){
                            $producto = Producto::where('product_id', $prod_shop->id)->first();
                            if($producto){
                                $job = $producto->isPublished() ?
                                    new MercadoLibreSyncJob('updated', $producto) :
                                    new MercadoLibreSyncJob('created', $producto);
                            }else{
                                $producto = Producto::create(['product_id' => $prod_shop->id, 'meli_condition'=>$prod_shop->meli_condition]);
                                $job = new MercadoLibreSyncJob('created', $producto);
                            }
                            dispatch_now($job);
                        }
                    }

                    return back();
                }
            });

            
        });
    }
}
