<?php namespace MarlonFreire\MercadoLibre\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre\MercadoLibre;
use MarlonFreire\MercadoLibre\App\Jobs\MercadoLibreSyncJob;
use MarlonFreire\MercadoLibre\Models\Configuracion as Model;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Configuracion extends Controller
{
    public $implement = [        'Backend\Behaviors\FormController'    ];
    
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'manage_sync' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('marlonfreire.mercadolibre', 'main-menu-sync', 'side-menu-config');

        $this->gateway =  new MercadoLibre();
    }

    public function toggleSync()
    {
        $config = Model::firstOrFail();
        $config->meli_automatic_sync = $config->meli_automatic_sync === 1 ? 0 : 1;
        $config->save();

        return response()->json([
            'status' => 'OK',
            'id' => 0,
            'errors' => []
        ]);
    }

    public function checkConnection(Request $request)
    {
        Model::firstOrFail()->update($request->all());
        $this->gateway->clearTokens();

        return response()->json([
            'status' => $this->gateway->isConnected() ? 'OK' : 'ERROR'
        ]);
    }

    public function onMeliSync()
    {
        $meli_config = \MarlonFreire\MercadoLibre\Models\Configuracion::firstOrFail();
        if(!empty($meli_config->meli_app_id) && !empty($meli_config->meli_app_secret)) {
            if (Input::has('Configuracion')) {
                $job = new MercadoLibreSyncJob();

                if (Input::has('Configuracion.partial')) {
                    $partial = Input::get('Configuracion.partial');
                    if ($partial)
                        $job->setEventName('syncProducts');
                }

                dispatch_now($job);
                $result = $job->getCompletedAction();
                if ($result->status) {
                    Model::first()->update([
                        'meli_full_sync_date' => Carbon::now()
                    ]);

                    return back();
                }


                return back();
            }

        }else{
            $status = "warning";
            $message = "Es obligatorio haber guardado las credenciales para realizar la sincronización";
            $actions = Session::get(config('gateway.flag_msg'), []);
            $actions[] = compact('status', 'message');

            Session::put(config('gateway.flag_msg'), $actions);

            return back();
        }
    }
}
