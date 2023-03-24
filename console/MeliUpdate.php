<?php


namespace MarlonFreire\Sitios\Console;


use Carbon\Carbon;
use Illuminate\Console\Command;
use MarlonFreire\MercadoLibre\App\Jobs\MercadoLibreSyncJob;
use MarlonFreire\MercadoLibre\Models\Configuracion as Model;
use Cache;

class MeliUpdate extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'meli:update';

    /**
     * @var string The console command description.
     */
    protected $description = 'Sincro parcial desde meli, solo trae lo nuevo';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $meli_config = \MarlonFreire\Sincronizar\Models\Configuracion::firstOrFail();

        if(!empty($meli_config->meli_app_id) && !empty($meli_config->meli_app_secret)){
            $job = new MercadoLibreSyncJob();

            $job->setEventName('syncProducts');

            dispatch_now($job);
            $result = $job->getCompletedAction();
            if (isset($result->status) && $result->status) {
                Model::first()->update([
                    'meli_full_sync_date' => Carbon::now()
                ]);

                Cache::flush();
                $this->output->writeln('Catálogo Actualizado!!!');
            }else
                $this->output->writeln('Ha ocurrido un error durante la sincronización!!!');
        }else
            $this->output->writeln('Faltan las credenciales!!!');
    }
}