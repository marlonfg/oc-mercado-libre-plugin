<?php

namespace MarlonFreire\MercadoLibre\App\Jobs;

use Illuminate\Support\Facades\Config;
use Lovata\PropertiesShopaholic\Models\Property;
use Lovata\PropertiesShopaholic\Models\PropertySet;
use Lovata\PropertiesShopaholic\Models\PropertyValue;
use Lovata\PropertiesShopaholic\Models\PropertyValueLink;
use Lovata\Shopaholic\Models\Category;
use Lovata\Shopaholic\Models\Currency;
use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Models\Product;
use Lovata\Shopaholic\Models\Settings;
use MarlonFreire\MercadoLibre\App\Contracts\Gateway;
use MarlonFreire\MercadoLibre\App\Contracts\SyncJob;
use MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre\MeliTools;
use MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre\MercadoLibre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Exception;
use Illuminate\Support\Facades\Session;
use MarlonFreire\MercadoLibre\App\Utils\RapidAPITranslator;
use System\Models\File;

use Str;

class MercadoLibreSyncJob implements ShouldQueue, SyncJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, MeliTools;

    protected $executionStartTime;
    protected $executionEndTime;
    protected $gateway;
    protected $cache_key;
    protected $lifetime;
    protected $meli_user;
    protected $load;
    protected $item;
    protected $event;
    protected $translator;

    /**
     * @var
     */

    /**
     * Create a new job instance.
     *
     * @param bool $initial
     */
    public function __construct($event = 'loadProducts', $item = null)
    {
        $this->item = $item;
        $this->event = $event;
        $this->translator = new RapidAPITranslator(Config::get('translator.default.api_key'));
        $this->gateway = new MercadoLibre();
        $this->meli_user = $this->gateway->getUserData();
        $this->cache_key = $this->gateway->getConfigKey('cache.key');
        $this->lifetime = now()->addMinutes($this->gateway->getConfigKey('cache.lifetime'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->{'on' . ucfirst($this->event)}();
    }

    private function combineAttributes($dirty = null, $ignore_guarded = true)
    {

        $to_sync = [
            'status' => $this->getStatusValue($this->item->active, $this->item->stock),
            'available_quantity' => $this->item->stock,
            'price' => $this->item->offer->first()->price_meli,
            'pictures' => [['source' => $this->item->preview_image->getPath()]],
            'attributes' => []
        ];

        if(isset($this->item->images)){
            foreach($this->item->images as $img){
                array_push($to_sync['pictures'], ['source' => $img->getPath()]);
            }
        }

        if(isset($this->item->brand)){
            $attributes =  [
                'id' => "BRAND",
                'value_name' => $this->item->brand->name
            ];

            array_push($to_sync['attributes'],$attributes);
        }

        if(!empty($this->item->property)){
            foreach($this->item->property_value as $prop){
                $attributes =  [
                    'id' => $prop->property->getTranslateAttribute('name', 'en'),
                    'name' => $prop->property->name,
                    'value_name' => $prop->value->value
                ];

                array_push($to_sync['attributes'],$attributes);
            }
        }
        
        if($this->item->free_shipping){
            $to_sync['shipping'] = [
                'mode' => "not_specified",
                'local_pick_up' => false,
                'free_shipping' => true,
                'methods' => [],
                'costs' => []
            ];
        }

        return $to_sync;
    }

    private function onCreated()
    {
        //Create mercadolibre item in API
        $sync = array_merge( $this->combineAttributes(),
            [
                'title' => $this->item->name,
                'category_id' => $this->item->category_meli->meli_id,
                'currency_id' => $this->item->offer->first()->currency->code,
                'listing_type_id' => $this->item->listing_type_id
            ]
        );

        if($this->item->meli_condition){
            $sync['condition'] = $this->item->meli_condition;
        }

        if(!empty($this->item->offer->first()->property) && $this->item->offer->first()->preview_image){
            $sync['variations'] = collect($this->item->offer)->map(function ($of ) use ($sync){
                if(!empty($of->property) && $of->preview_image ){
                    array_push($sync['pictures'], ['source' => $of->preview_image->getPath()]);
                    $var['attribute_combinations'] = collect($of->property_value)->map(function ($prop){
                        return [
                            'name' => $prop->property->name,
                            'value_name' => $prop->value->value
                        ];
                    })->toArray();

                    return array_merge($var,[
                        'price' => $of->price_meli,
                        'available_quantity' => $of->quantity,
                        'picture_ids' => [$of->preview_image->getPath()]]);
                }

            })->toArray();
        }

        $user = $this->gateway->get($this->gateway->getConfigKey('routes.user_data'));

        $sync['location'] = [
            'country' => ['id'=> 'UY'],
            'state' => ['id'=> $user->getResponseResult()->address->state],
            'city' => ['name'=> $user->getResponseResult()->address->city]
        ];

        $result = $this->gateway->post(
            $this->gateway->getConfigKey('routes.items.create'),
            $sync
        );

        //TODO manejar el response de acuerdo al status_code
        if ($result->isSuccesfullResponse()) {
            //Actualizo item en DB
            $this->item->update([
                $this->item->meli_id => $result->getResponseResult()->id
            ]);

            //Publicar description del producto
            if(isset($this->item->description)){
                $put_desc = $this->gateway->put(
                    str_replace('{param}', $this->item->meli_id, $this->gateway->getConfigKey('routes.items.update.description')),
                    [
                        'plain_text' => $this->item->description
                    ]
                );
            }

            //Obtengo el producto creado
            $result = $this->gateway->get(str_replace('{param}',
                $this->item->meli_id,
                $this->gateway->getConfigKey('routes.items.get.one')));

            //Actualizo medias del producto en DB
            $pictures_id = collect($result->getResponseResult()->pictures)->pluck('id')->toArray();
            $files_prod = File::whereAttachmentIdAndAttachmentType($this->item->id, 'Lovata\Shopaholic\Models\Product')->get();
            $files_prod->each(function ($m, $index) use ($pictures_id) {
                $m->update([
                    'meli_id' => $pictures_id[$index]
                ]);
            });

            //Actualizo medias de las variaciones en DB
            if(!empty($result->getResponseResult()->variations)){
                $var = collect($result->getResponseResult()->variations)->pluck('picture_ids')->toArray();
                $files_offer = File::whereIn('attachment_id', $this->item->offer->pluck('id'))->where('attachment_type', 'Lovata\Shopaholic\Models\Offer')->get();
                $files_offer->each(function ($m, $index) use ($var) {
                    $m->update([
                        'meli_id' => $var[$index][0]
                    ]);
                });
            }

            $this->setJobAction('Publicado en MercadoLibre ' . $result->getResponseResult()->id);
        } else
            $this->handleResponse($result);
    }

    private function onUpdated()
    {
        // Update description field
        $result = $this->gateway->put(
            str_replace('{param}', $this->item->meli_id, $this->gateway->getConfigKey('routes.items.update.description')),
            [
                'plain_text' => $this->item->description
            ]
        );

//        $this->onSyncMedias();

        $item = $this->gateway->get(str_replace('{param}',
            $this->item->meli_id,
            $this->gateway->getConfigKey('routes.items.get.one')))->getResponseResult();


        if(empty($item->variations)){
            $combined = $this->combineAttributes();
        }else{
            $combined = ['status' => $this->getStatusValue($this->item->active, $this->item->stock)];
            $variations = $item->variations;
            $offers = $this->item->offer;
            $pictures_id = collect($item->pictures)->pluck('id');
            $pictures = collect($pictures_id)->map(function ($p){
                return ['id' => $p];
            })->toArray();
            $combined['pictures'] = $pictures;

            $combined['variations'] = collect($offers)->map(function ($of, $index) use ($variations, $combined) {

                $var = collect($variations)->pluck('attribute_combinations');
                if($index < count($variations))
                    $val = collect($var[$index])->pluck('value_name')->toArray();
                else
                    $val = [];
                if(!empty($val) && (empty(array_diff($val, $of->property)) || empty($of->property))){
                    return [
                        'id'=>$variations[$index]->id,
                        'price'=>$of->price_meli,
                        'available_quantity'=>$of->quantity
                    ];
                }else{
                    $new['attribute_combinations'] = collect($of->property_value)->map(function ($prop){
                        return [
                            'name' => $prop->property->name,
                            'value_name' => $prop->value->value
                        ];
                    })->toArray();

                    return array_merge($new,[
                        'price' => $of->price_meli,
                        'available_quantity' => $of->quantity,
                        'picture_ids' => [$of->preview_image ? $of->preview_image->getPath() : $this->item->preview_image->getPath()]]);
                }

            })->toArray();

            foreach($combined['variations'] as $var){
                if(isset($var['picture_ids']))
                    array_push($combined['pictures'], ['source' => $var['picture_ids'][0]]);
            }
        }

        if($item->sold_quantity == 0){
            $combined['title'] = $this->item->name;
        }

        if(!empty($combined)){
                    $result = $this->gateway->put(
                        str_replace('{param}', $this->item->meli_id, $this->gateway->getConfigKey('routes.items.update.all')),
                        $combined
                    );

                    if ($result->isSuccesfullResponse()){

                        $this->setJobAction('Actualizado en MercadoLibre ' . $result->getResponseResult()->id);
                    }
                    else
                        $this->handleResponse($result);
                }
    }

    private function onSyncMedias()
    {
        if($this->item->isPublished()){
//            if (in_array('App\Models\Base\MainModel', class_parents($this->item))) {
                //Update pictures in meli
                $result = $this->gateway->get(str_replace(
                    '{param}',
                    $this->item->meli_id,
                    $this->gateway->getConfigKey('routes.items.get.general')
                ));

                if ($result->isSuccesfullResponse()) {
                    $pictures_id = collect($result->getResponseResult()->pictures)->pluck('id');

                    $files = File::whereAttachmentIdAndAttachmentType($this->item->id, 'Lovata\Shopaholic\Models\Product')->get();

                    $medias = $files->whereNotNull('meli_id')->pluck('meli_id');

                    $intersect = $medias->intersect($pictures_id);

                    $new_medias = $files->whereNull('meli_id')->values();

                    $to_sync = collect($new_medias)->transform(function ($m) {
                        return ['source' => $m->getPath()];
                    })->merge($intersect->transform(function ($i) {
                        return ['id' => $i];
                    }))->all();

                    $result = $this->gateway->put(
                        str_replace('{param}', $this->item->meli_id, $this->gateway->getConfigKey('routes.items.update.all')),
                        [
                            'pictures' => $to_sync
                        ]
                    );


                    if ($result->isSuccesfullResponse()) {
                        $pictures_id = collect($result->getResponseResult()->pictures)->pluck('id')->diff($intersect->pluck('id'))->toArray();

                        $new_medias->each(function ($m, $index) use ($pictures_id) {
                            $m->update([
                                'meli_id' => $pictures_id[$index]
                            ]);
                        });

                        $this->setJobAction('Medias actualizadas en MercadoLibre');
                    } else
                        $this->handleResponse($result);
                }
//            }
        }
    }

    private function onPredict()
    {
        // Log::info('Entrando');

        // Log::info('Valor:' . $this->item->category_meli->meli_id);

        if ($this->item->category_meli->meli_id == NULL) {

            //  Log::info('Voy a predecir');

            $default_gateway = config('gateway.default_gateway');

            $val = config(sprintf('gateway.configured.%s.site_id', $default_gateway));

            $uri = $this->gateway->getConfigKey('routes.items.get.predict');

            $uri = str_replace('{param}', $val, $uri);

            $result = $this->gateway->get(str_replace('{title}', str_replace(' ', '%20', $this->item->name), $uri))->getResponseResult();

            // Log::info('Predice: ' . $result->id);

            $category = $this->gateway->getConfigKey('models.categories.class')::where($this->gateway->getConfigKey('models.categories.class')::gatewayID(), $result->id)->first();

            // Log::info('Asociado: ' . $category->id);

            if ($category)
                $this->gateway->getConfigKey('models.items.class')::where('id', $this->item->id)->update(['categorias_id' => $category->id]);
            else {
                $this->setJobAction("La categoria con ID $result->id no está guardada en DB, debe asignar una categoría directamente sin pasar por la predicción");
                Log::info("MelySync Prediction: La categoria con ID $result->id no está guardada en DB");
            }
        }
    }


    private function onDeleted()
    {
        //Delete mercadolibre item in API
        if ($this->item->isPublished()) {
            $result = $this->gateway->put(
                str_replace('{param}', $this->item->meli_id, $this->gateway->getConfigKey('routes.items.delete')),
                [
                    'status' => 'closed'
                ]
            );

            if ($result->isSuccesfullResponse()) {
                $result = $this->gateway->put(
                    str_replace('{param}', $this->item->meli_id, $this->gateway->getConfigKey('routes.items.delete')),
                    [
                        'deleted' => 'true'
                    ]
                );
                if ($result->isSuccesfullResponse())
                    $this->setJobAction('Eliminado en MercadoLibre ' . $result->getResponseResult()->id);
                else {
                    $this->setJobAction('Cerrado en MercadoLibre ' . $result->getResponseResult()->id);
                    $this->handleResponse($result);
                }
            } else
                $this->handleResponse($result);
        }
    }

    private function onSyncProducts()
    {
        //Cargo los productos sincronizando la lista de productos sin truncar la DB
        $this->onLoadProducts(true);
    }

    private function onLoadProducts($partial = false)
    {
        set_time_limit(600);
        $completed = (object) [
            'status' => false,
            'cursor_on' => Cache::tags([$this->cache_key,'sync'])->get('cursor'),
            'exception' => null,
            'time' => 0
        ];

        if ($this->isConnected()) {
            if (!Cache::tags([$this->cache_key,'sync'])->has('cursor'))
                Cache::tags([$this->cache_key,'sync'])->put('cursor', 0, $this->lifetime);

            //Borro la DB de productos
            if ((Cache::tags([$this->cache_key, 'sync'])->get('cursor') == 0) && !$partial) {
                Schema::disableForeignKeyConstraints();

                //Elimino las medias asociadas asumiendo que el modelo de medias tenga la implementacion del borrado fisico de los archivos

                $m_prod = $this->gateway->getConfigKey('models.medias.class')::where('attachment_type', "Lovata\Shopaholic\Models\Product")->get();
                if($m_prod->isNotEmpty()){
                    foreach ($m_prod as $m) {
                        $m->delete();
                    }
                }

                $m_offer = $this->gateway->getConfigKey('models.medias.class')::where('attachment_type', "Lovata\Shopaholic\Models\Offer")->get();
                if($m_offer->isNotEmpty()){
                    foreach ($m_offer as $m) {
                        $m->delete();
                    }
                }

                //Elimino productos y ofertas
                DB::table('lovata_shopaholic_offers')->truncate();
                DB::table('lovata_shopaholic_products')->truncate();

                Schema::enableForeignKeyConstraints();
            }

            $executionStartTime = microtime(true);

            $items = Cache::tags([$this->cache_key,'sync'])->get('items') ?? [];

            if (empty($items)) {
                $scroll_id = '';
                do {
                    $data = $this->gateway->get(str_replace('{param}', $this->meli_user->id, $this->gateway->getConfigKey('routes.items.search')), [
                        'search_type' => 'scan',
                        'scroll_id' => $scroll_id,
                        'limit' => 100
                    ])->getResponseResult();
                    if($data){
                        $scroll_id = $data->scroll_id;
                        $items = array_merge($items, $data->results);
                    }

                } while (!empty($data->results));


                Cache::tags([$this->cache_key,'sync'])->put('items', $items, $this->lifetime);
                Cache::tags([$this->cache_key,'sync'])->put('total', count($items), $this->lifetime);
            }

            $cat_model = MarlonFreire\MercadoLibre\Models\Categoria::class;

            $item_model =  Lovata\Shopaholic\Models\Product::class;

            $slice = collect($items)->slice(Cache::tags([$this->cache_key, 'sync'])->get('cursor'), 50);

            $slice->each(function ($id, $index) use (&$completed, $executionStartTime, $cat_model, $item_model, $partial, $slice) {
                try {
                    if($partial)
                        $on_db = $item_model::whereNotNull('meli_id')->pluck('meli_id');

                    $item = $this->gateway->get(str_replace('{param}', $id, $this->gateway->getConfigKey('routes.items.get.general')))->getResponseResult();

                    if(!$item)
                        throw new Exception("Item doesn't fetched");

                    $category = $cat_model::where('meli_id', $item->category_id)->first();

                    if ($category) {

                        if ($partial) {

                            if (!$on_db->contains($item->id))
                                $this->saveItem($item, $category);
                            else
                                $this->updateItem($item);
                        } else
                            $this->saveItem($item, $category);
                    } else
                        Log::info('No pertenece el item a esta categoria: ' . $item->category_id);

                    Cache::tags([$this->cache_key, 'sync'])->put('cursor', $index + 1, $this->lifetime);
                    $completed->cursor_on = $index + 1;
                } catch (\Exception $e) {
                    $executionEndTime = microtime(true);
                    $completed->exception = $e->getMessage();

                    $completed->time = ($executionEndTime - $executionStartTime);
                    Log::error('MeliSync', [$e->getMessage()]);
                    $this->setJobAction('Ha ocurrido un error durante la sincronización' , 'error');
                    return false;
                }
            });


            if (!$completed->exception) {
                if (Cache::tags([$this->cache_key,'sync'])->get('cursor') == Cache::tags([$this->cache_key,'sync'])->get('total')) {
                    Cache::tags(['sync'])->flush();
                    $completed->cursor_on = null;
                    $completed->status = true;

                    Cache::tags([$this->cache_key,'sync'])->put('completed', $completed, $this->lifetime);
                    $this->setJobAction('Sincronización completada' , 'ok');
                }else{
                    $this->onLoadProducts($partial);
                }


            }
        } else{
            $completed->exception = 'El servidor no pudo establecer comunicaciones con MercadoLibre,
             verifique sus configuraciones e intente nuevamente';
            $this->setJobAction($completed->exception , 'error');
        }

    }

    private function saveItem($item, $category)
    {
        DB::beginTransaction();

        $item->category_id = $category->meli_id;

        $description = $this->gateway->get(str_replace('{param}', $item->id, $this->gateway->getConfigKey('routes.items.get.description')))->getResponseResult();

        //Procedo a sincronizar el item
        $item_model = Lovata\Shopaholic\Models\Product::class;

        $item->descriptions = @$description->plain_text ?? ' ';

        //Obtener categorias de shopaholic asociadas
        $webcats = $category->category->get();

        $item_entity = Product::create([
            'name' => $item->title,
            'active'=> $this->getActiveValue($item->status),
            'description'=> $item->descriptions,
            'category_id'=> $webcats->first()->id,
            'category_meli_id'=> $category->id,
            'listing_type_id'=> $item->listing_type_id,
            'meli_condition' => $item->condition,
            'meli_id' => $item->id
        ]);

        //Setear categoria adicionales de shopaholic
        foreach($webcats as $wc){
            $item_entity->additional_category()->sync([$wc->id]);
        }

        //Obtengo currency para ofertas
        $currency_id = Currency::whereCode($item->currency_id)->get()->first()->id;

        if(!empty($item->variations)) {
            $set = PropertySet::first();
            if (empty($set))
                $set = PropertySet::create(['name' => 'Propiedades', 'code' => 'propiedades', 'is_global' => 1]);

            foreach ($item->variations as $var) {
                $offer = Offer::create(['active' => 1, 'name' => $item->title, 'currency_id' => $currency_id, 'price' => $var->price, 'old_price' => $var->price, 'price_meli' => $var->price, 'quantity' => $var->available_quantity, 'product_id' => $item_entity->id]);
                foreach ($var->attribute_combinations as $c) {
                    $prop = Property::whereName($c->name)->get()->first();
                    if (empty($prop)) {
                        $prop = Property::create(['name' => $c->name, 'type' => 'select', 'settings' => ["is_translatable" => 1, "tab" => "Propiedades", "datepicker" => "date", "mediafinder" => "file"]]);
                        $set->property()->attach($prop->id);
                    }

                    $slug = $this->getSlugValue($c->value_name);
                    $value = PropertyValue::whereSlug($slug)->get()->first();
//                    $value = PropertyValue::whereValue($c->value_name)->get()->first();
                    if (empty($value)){
                        $value = PropertyValue::create(['value' => $c->value_name, 'label' => $c->value_name]);
                        $prop->property_value()->attach($value->id);
                    }

                    $prop_offer = PropertyValueLink::firstOrCreate([
                        'value_id' => $value->id,
                        'property_id' => $prop->id,
                        'element_id' => $offer->id,
                        'element_type' => 'Lovata\Shopaholic\Models\Offer',
                        'product_id' => $item_entity->id
                    ]);
                }
                $pictures = [];
                foreach ($item->pictures as $p) {
                    foreach ($var->picture_ids as $v)
                        if ($p->id == $v)
                            array_push($pictures, $p);
                }
                if (!empty($pictures))
                    $this->saveMedias($offer, $pictures);
            }
        }
        else
            $offer = Offer::create(['active'=> 1, 'name' => $item->title, 'currency_id' => $currency_id, 'price'=> $item->price, 'old_price'=> $item->price, 'price_meli' => $item->price, 'quantity' => $item->available_quantity, 'product_id'=> $item_entity->id]);

        $this->saveMedias($item_entity, $item->pictures);

        DB::commit();
    }

    private function updateItem($item){
        DB::beginTransaction();

        $item_model = Lovata\Shopaholic\Models\Product::class;

        $description = $this->gateway->get(str_replace('{param}', $item->id, $this->gateway->getConfigKey('routes.items.get.description')))->getResponseResult();

        $item->descriptions = @$description->plain_text ?? '';

        $producto = $item_model::where('meli_id', $item->id)->first();

        if($producto->name != $item->title || $producto->active != $this->getActiveValue($item->status) || $producto->description != $item->descriptions){
            $producto->update([
                'name' => $item->title,
                'active'=> $this->getActiveValue($item->status),
                'description'=> $item->descriptions
            ]);
        }

        //Obtengo currency para ofertas
        $currency_id = Currency::whereCode($item->currency_id)->get()->first()->id;

        if(!empty($item->variations)) {
            $offers = $producto->offer;
            $variations = $item->variations;

            collect($offers)->map(function ($of, $index) use ($variations, $producto, $item, $currency_id) {

                $var = collect($variations)->pluck('attribute_combinations');
                if($index < count($variations))
                    $val = collect($var[$index])->pluck('value_name')->toArray();
                else
                    $val = [];

                $of_currency = $of->currency ? $of->currency->name : "";

                if( !empty($val) && empty(array_diff($val, $of->property)) ){
                    if($of->price_meli != $variations[$index]->price || $of->quantity != $variations[$index]->available_quantity || $of_currency != $item->currency_id){

                        $of_update = $of->update([
                            'currency_id' => $currency_id,
                            'price' => $variations[$index]->price,
                            'old_price' => $variations[$index]->price,
                            'price_meli' => $variations[$index]->price,
                            'quantity' => $variations[$index]->available_quantity
                        ]);
                    }
                }elseif( !empty($val) && !empty(array_diff($val, $of->property)) ){
                    if($of->price_meli != $variations[$index]->price || $of->quantity != $variations[$index]->available_quantity || $of_currency != $item->currency_id){

                        $of_update = $of->update([
                            'currency_id' => $currency_id,
                            'price' => $variations[$index]->price,
                            'old_price' => $variations[$index]->price,
                            'price_meli' => $variations[$index]->price,
                            'quantity' => $variations[$index]->available_quantity
                        ]);
                    }

                    collect($var[$index])->map(function ($property) use ($of, $producto){
                        $set = PropertySet::first();
                        if (empty($set))
                            $set = PropertySet::create(['name' => 'Propiedades', 'code' => 'propiedades', 'is_global' => 1]);

                        $prop = Property::whereName($property->name)->get()->first();
                        if (empty($prop)) {
                            $prop = Property::create(['name' => $property->name, 'type' => 'select', 'settings' => ["is_translatable" => 1, "tab" => "Propiedades", "datepicker" => "date", "mediafinder" => "file"]]);
                            $set->property()->attach($prop->id);
                        }
                        $slug = $this->getSlugValue($property->value_name);
                        $value = PropertyValue::whereSlug($slug)->get()->first();
//                    $value = PropertyValue::whereValue($property->value_name)->get()->first();
                        if (empty($value)){
                            $value = PropertyValue::create(['value' => $property->value_name, 'label' => $property->value_name]);
                            $prop->property_value()->attach($value->id);
                        }

                        $prop_offer = PropertyValueLink::firstOrCreate([
                            'value_id' => $value->id,
                            'property_id' => $prop->id,
                            'element_id' => $of->id,
                            'element_type' => 'Lovata\Shopaholic\Models\Offer',
                            'product_id' => $producto->id
                        ]);
                    });

                    $pictures = [];
                    foreach ($item->pictures as $p) {
                        foreach ($variations[$index]->picture_ids as $v)
                            if ($p->id == $v)
                                array_push($pictures, $p);
                    }
//                    if (!empty($pictures))
//                        $this->saveMedias($of, $pictures);

                }else
                    $of->delete();

            });

//          si hay menos ofertas que variaciones crear ofertas
            collect($variations)->map(function ($var, $index) use ($offers, $producto, $item) {
                if($index >= count($offers)){
                    $offer = Offer::create(['active' => 1, 'name' => $item->title, 'price' => $var->price, 'old_price' => $var->price, 'price_meli' => $var->price, 'quantity' => $var->available_quantity, 'product_id' => $producto->id]);
                    foreach ($var->attribute_combinations as $c) {
                        $set = PropertySet::first();
                        if (empty($set))
                            $set = PropertySet::create(['name' => 'Propiedades', 'code' => 'propiedades', 'is_global' => 1]);

                        $prop = Property::whereName($c->name)->get()->first();
                        if (empty($prop)) {
                            $prop = Property::create(['name' => $c->name, 'type' => 'select', 'settings' => ["is_translatable" => 1, "tab" => "Propiedades", "datepicker" => "date", "mediafinder" => "file"]]);
                            $set->property()->attach($prop->id);
                        }
                        $value = PropertyValue::whereValue($c->value_name)->get()->first();
                        if (empty($value)){
                            $value = PropertyValue::create(['value' => $c->value_name, 'label' => $c->value_name]);
                            $prop->property_value()->attach($value->id);
                        }

                        $prop_offer = PropertyValueLink::firstOrCreate([
                            'value_id' => $value->id,
                            'property_id' => $prop->id,
                            'element_id' => $offer->id,
                            'element_type' => 'Lovata\Shopaholic\Models\Offer',
                            'product_id' => $producto->id
                        ]);
                    }
                    $pictures = [];
                    foreach ($item->pictures as $p) {
                        foreach ($var->picture_ids as $v)
                            if ($p->id == $v)
                                array_push($pictures, $p);
                    }
                    if (!empty($pictures))
                        $this->saveMedias($offer, $pictures);
                }
            });

        }else{
            $offer = $producto->offer->first();

            $of_currency = $offer->currency ? $offer->currency->name : "";

            if($offer->price_meli != $item->price || $offer->quantity != $item->available_quantity || $of_currency != $item->currency_id){

                $offer = $offer->update([
                    'currency_id' => $currency_id,
                    'price' => $item->price,
                    'old_price' => $item->price,
                    'price_meli' => $item->price,
                    'quantity' => $item->available_quantity
                ]);
            }

        }

        DB::commit();
    }

    private function onLoadCategories()
    {
        set_time_limit(120);
        ini_set('memory_limit', '256M');

        $completed = (object) [
            'status' => false,
            'cursor_on' => Cache::tags([$this->cache_key,'sync'])->get('cursor'),
            'exception' => null,
            'time' => 0
        ];

        if ($this->isConnected()) {

            if (!Cache::tags([$this->cache_key,'sync'])->has('cursor'))
                Cache::tags([$this->cache_key,'sync'])->put('cursor', 0, $this->lifetime);

            if (empty($this->item))
                $this->item = $this->gateway->get(str_replace('{param}', $this->gateway->getConfigKey('routes.categories'), $this->gateway->getConfigKey('site_id')))->getResponseResult();

            Cache::tags([$this->cache_key,'sync'])->put('total', count($this->item), $this->lifetime);

            //Obtengo el diff de las categorias que tengo en DB y las que pasan por parametro y las elimino de la DB
            $cat_model = MarlonFreire\MercadoLibre\Models\Categoria::class;
//
//            $cat_on_db = Category::whereNull('parent_id')->pluck('id')->toArray();
//            $on_db = $cat_model::whereIn('category_id', $cat_on_db)->pluck($cat_model::gatewayID())->toArray();
//
//            $on_dif = $cat_model::whereIn($cat_model::gatewayID(),array_diff($on_db,$this->item))->pluck('category_id')->toArray();

//            Category::whereIn('id',$on_dif)->delete();

            //recorro el arreglo de categorias selecionadas
            $index = 0;
            foreach ($this->item as $category) {
                $uri = $this->gateway->getConfigKey('routes.items.get.categories');

                $self = $this->gateway->get(str_replace('{param}', $category, $uri), [])->getResponseResult();

                if(isset($self->name)){
                    DB::beginTransaction();
                    //Inserto la categoria
                    $cat_entity = $cat_model::firstOrCreate([
                        'name' => $self->name,
                        'active'=> 1,
                        'meli_id' => $category
                    ]);

                    DB::commit();

                    $index = $index + 1;
                    Cache::tags([$this->cache_key, 'sync'])->put('cursor', $index, $this->lifetime);
                    $completed->cursor_on = $index;

                    //recorro los children_categories de este $result
                    $this->getChildCategories($category, $cat_entity->id);
                }

            }

            if (!$completed->exception) {
                if (Cache::tags([$this->cache_key,'sync'])->get('cursor') == Cache::tags([$this->cache_key,'sync'])->get('total')) {
                    Cache::tags(['sync'])->flush();
                    $completed->cursor_on = null;
                }

                $completed->status = true;
            }
        } else{
            $completed->exception = 'El servidor no pudo establecer comunicaciones con MercadoLibre,
             verifique sus configuraciones e intente nuevamente';
            $this->setJobAction($completed->exception , 'error');
        }

        Cache::tags([$this->cache_key,'sync'])->put("completed", $completed, $this->lifetime);
        $this->setJobAction('Sincronización completada' , 'ok');
    }

    private function getChildCategories($item, $father)
    {

        set_time_limit(120);

        //busco los hijos
        $uri = $this->gateway->getConfigKey('routes.items.get.categories');

        $result = $this->gateway->get(str_replace('{param}', $item, $uri), [])->getResponseResult();

        foreach ($result->children_categories as $child) {

            DB::beginTransaction();

            //Inserto la categoria
            $cat_model = MarlonFreire\MercadoLibre\Models\Categoria::class;
            $cat_entity = $cat_model::where('meli_id', $child->id)->first();

            if (!$cat_entity){
                $cat_entity = $cat_model::firstOrCreate([
                    'name' => $child->name,
                    'parent_id' => $father,
                    'active'=> 1,
                    'meli_id' => $child->id
                ]);
            }

            DB::commit();

            $this->getChildCategories($child->id, $cat_entity->id);
        }
    }

    private function saveMedias(&$entity, $medias)
    {
        if (!is_array($medias))
            $medias = (array) $medias;

        foreach ($medias as $picture) {
            $url = $picture->secure_url;
            $info = pathinfo($url);
            $contents = file_get_contents($url);
            if (!is_dir(public_path('/storage/app/uploads/public')))
                mkdir(public_path('/storage/app/uploads/public'));

            $file = public_path('/storage/app/uploads/public/') . $info['basename'];

            file_put_contents($file, $contents);
            $uploaded_file = new UploadedFile($file, $info['basename']);

            $media_obj = (new File())->fromPost($uploaded_file);

            $media = File::create([
                'file_name' => $media_obj->file_name,
                'file_size' => $uploaded_file->getSize(),
                'content_type' => $media_obj->content_type,
                'field' => 'images',
                'attachment_id' => $entity->id,
                'attachment_type' => get_class($entity),
                'meli_id' => $picture->id
            ]);

            if($medias[0]->id == $picture->id)
                $media->field = 'preview_image';


            $media->disk_name = $media_obj->attributes['disk_name'];
            $media->save();
        }
    }

    //Obtener preguntas de Meli
    public function onMeliQuestions(){

        $result = $this->gateway->get('users/me');
      
        if(!$result->isSuccesfullResponse())
            return;
            
        $id = $result->getResponseResult()->id;
        
        $preguntas = $this->gateway->get('questions/search', [
            'seller_id' => $id
        ])->getResponseResult()->questions;

        $preguntas = array_filter($preguntas, function($v) {
            return $v->status == "UNANSWERED";
        });

        foreach($preguntas as $p){
            if(Product::whereMeliId($p->item_id)->count())
                $q = Meliquestion::firstOrCreate([
                    'id'=>$p->id,
                    'from_id'=>$p->from->id,
                    'seller_id'=>$p->seller_id,
                    'item_id'=>$p->item_id,
                    'text'=>$p->text,
                    'date_created'=>(new \DateTime(explode("T", $p->date_created)[0].' '.explode( "." , explode("T", $p->date_created)[1] )[0]))->format('Y-m-d H:i:s')
                ]);
        }
    }

    public function onAnswer(){

    }

    public function answer($question_id, $text){
        $respuesta = $this->gateway->post('answers', [
            'question_id' => $question_id,
            'text' => $text
        ]);

        if ($respuesta->isSuccesfullResponse())
            $this->setJobAction('Pregunta respondida satisfactoriamente' , 'ok');
    }

    public function deleteAnswer($question_id){
        $result = $this->gateway->delete('questions/'.$question_id);

    }

    public function getUser($id){
        $result = $this->gateway->get('users/'.$id)->getResponseResult();

        return $result->nickname;
    }

    private function isConnected()
    {
        return $this->gateway->isConnected();
    }

    public function getCompletedAction()
    {
        return Cache::tags([$this->cache_key,'sync'])->get('completed');
    }

    public function setItemToSynchronize($item)
    {
        $this->item = $item;
    }

    public function getItemToSynchronize()
    {
        return $this->item;
    }

    /**
     * @return Gateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    public function setJobAction($message, $status = 'ok')
    {
        $actions = Session::get(config('gateway.flag_msg'), []);
        $actions[] = compact('status', 'message');

        Session::put(config('gateway.flag_msg'), $actions);
    }

    public function setEventName($name)
    {
        $this->event = $name;
    }

    public function handleResponse($response)
    {
        switch ($response->getResponseStatusCode()) {
            case 400:
                //Validation error
                $this->setJobAction('Ocurrió un error de validación en la sincronización con MercadoLibre', 'error');
                break;
            case 403:
                //Forbidden error
                $this->setJobAction('Ocurrió un error de prohibición en la sincronización con MercadoLibre', 'warning');
                break;
            case 422:
                //Sanitization error
                $this->setJobAction('Ocurrió un error en los campos enviados a MercadoLibre', 'warning');
                break;
            default:
                //Default error
                $this->setJobAction('Ocurrió un error en la sincronización con MercadoLibre', 'error');
                break;
        }
        if ($response->getResponseResult()) {
            if (isset($response->getResponseResult()->cause)) {
                $causes = collect($response->getResponseResult()->cause);
                $causes->each(function ($cause) {
                    try {
                        $result = $this->translate($cause->message);
                        $this->setJobAction(sprintf('Mercadolibre dice: %s', $result == '' ? $cause->message : $result), 'warning');
                    } catch (Exception $e) {
                        Log::info('Traducción inválida: ' . $e->getMessage());
                    }
                });
            }
            Log::error('MeliSync', (array) $response->getResponseResult());
        }
    }

    private function translate($message)
    {
        if (is_null($this->translator))
            throw new Exception('There isn\'t any configured api translator for this app');

        return $this->translator->translate($message, 'es');
    }

    public function getSlugValue($sValue)
    {
        $bUseUrlencode = (bool) Settings::getValue('property_value_with_urlencode');
        $bNotUseStrSlug = (bool) Settings::getValue('property_value_without_str_slug');

        if ($bUseUrlencode) {
            $sValue = urlencode($sValue);
        }

        $sSlug = $sValue;
        if (!$bNotUseStrSlug) {
            $sSlug = str_replace([',', '.'], 'x', $sValue);
            $sSlug = Str::slug($sSlug, '');
        }

        $sSlug = (string) $sSlug;

        return $sSlug;
    }
}
