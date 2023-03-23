<?php namespace MarlonFreire\MercadoLibre\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use MarlonFreire\MercadoLibre\App\Jobs\MercadoLibreSyncJob;
use MarlonFreire\MercadoLibre\Models\Categoria as Model;
use Illuminate\Http\Request;

class LoadCategoria extends Controller
{
    public $implement = [        'Backend\Behaviors\FormController'    ];
    
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'manage_sync' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('marlonfreire.mercadolibre', 'main-menu-sync', 'side-menu-loadcategoria');
    }

    private function parseToTreeSelect($item)
    {
        $parsed = ['id' => $item->id, 'label' => $item->name];
        if ($item->children > 0)
            $parsed['children'] = null;
        return $parsed;
    }

    /**
     *
     */
    public function getLeafs($id)
    {
        $order = ['name', 'asc'];

        $results = Model::withCount('subcategorias as children')
            ->where('id_father', $id)
            ->orderBy($order[0], $order[1])
            ->get()->transform(function ($item) {
                return $this->parseToTreeSelect($item);
            });

        return response()->json([
            'status' => 'OK',
            'data' => $results
        ]);
    }

    public function getRoot($id)
    {
        $results = [];
        $upToRoot = function ($leaf) use (&$upToRoot, &$results) {
            if ($leaf->id_father !== 0) {
                $ancestor = Model::where('id', $leaf->id_father)->first();
                $siblings = Model::withCount('subcategorias as children')
                    ->where('id_father', $leaf->id_father)
                    ->get()
                    ->transform(function ($item) {
                        return $this->parseToTreeSelect($item);
                    })->toArray();
                $results[] = [
                    'id' => $ancestor->id,
                    'label' => $ancestor->name,
                    'children' => $siblings
                ];
                $upToRoot($ancestor);
            } else {
                foreach ($results as $index => $item) {
                    if (next($results)) {
                        $indexOf = 0;
                        array_walk($results[$index + 1]['children'], function ($child, $key) use ($item, &$indexOf) {
                            if ($item['id'] === $child['id']) {
                                $indexOf = $key;
                                return false;
                            }
                        });
                        $results[$index + 1]['children'][$indexOf] = $results[$index];
                        $results[$index] = $results[$index + 1];
                    }
                }
                $results = array_pop($results);
            }
        };

        $leaf  = Model::find($id);

        $upToRoot($leaf);

        // dd($results);
        return response()->json([
            'status' => 'OK',
            'data' => [$results]
        ]);
    }

    public function onSyncCategorias()
    {
        if(Input::has('Categoria.categorias')){
            $categorias = Input::get('Categoria.categorias');
            $job = new MercadoLibreSyncJob('loadCategories', $categorias);
            dispatch_now($job);
            $result = $job->getCompletedAction();

            if ($result->status) {

                return response()->json([
                    'status' => 'OK',
                    'on' => $result->cursor_on
                ]);
            }

            return response()->json([
                'status' => 'ERROR',
                'error' => $result->exception
            ]);

            return back();

        }
    }


}
