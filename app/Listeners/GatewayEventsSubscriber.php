<?php

namespace MarlonFreire\MercadoLibre\App\Listeners;

use MarlonFreire\MercadoLibre\App\Contracts\SyncJob;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Session;

class GatewayEventsSubscriber implements ShouldQueue
{
    use Queueable, DispatchesJobs;

    protected $job;

    /**
     * GatewayEventsSubscriber constructor.
     */
    public function __construct(SyncJob $job)
    {
        $this->job = $job;
        $this->config = $this->job->getGateway()->getConfigModel();
        if(!in_array('MarlonFreire\MercadoLibre\App\Contracts\Synchronizable',class_implements($this->config)))
            throw new \Exception('The configuration model has to implement Synchronizable contract');

    }


    /**
     * Handle created events.
     */
    public function onModelCreated($event_name,$model)
    {
        $model = current($model);
        $this->job->setEventName($this->getEloquentEventName($event_name));
        $this->handle($model);
        //createLogic
    }

    /**
     * Handle medias synchronization event.
     */
    public function onMediasSync($model)
    {
        $this->job->setEventName('syncMedias');
        $this->handle($model, true);
        //createLogic
    }

    /**
     * Handle reset access tokens event.
     */
    public function onClearAccessTokens()
    {
        $this->job->getGateway()->clearTokens();
    }

     /**
     * Handle updating events.
     */
    public function onModelUpdating($event_name,$model)
    {
        $model = current($model);
        //Updating Logic
        //Save in session flash bag dirty fields from update event
        Session::flash('dirty_fields',$model->getDirty());
    }

    /**
     * Handle updated events.
     */
    public function onModelUpdated($event_name,$model)
    {
        $model = current($model);
        $model->setAttribute('dirty_fields',Session::get('dirty_fields'));
        $this->job->setEventName($this->getEloquentEventName($event_name));
        $this->handle($model);
        //Update Logic
    }

    /**
     * Handle deleted events.
     */
    public function onModelDeleted($event_name,$model)
    {
        $model = current($model);
        $this->job->setEventName($this->getEloquentEventName($event_name));
        $this->handle($model);
        //Delete logic
    }

    private function getEloquentEventName($name){
        preg_match('/.*\.(?P<event>.*)\:/',$name,$matches);

        return $matches['event'];
    }

    public function onSync($model){
        if($model->isPublished()){
            $model->setAttribute('dirty_fields',$model->getAttributes());
            $this->job->setEventName('updated');
        }
        else
            $this->job->setEventName('created');

        $this->handle($model,true);
    }

    public function onPredict($model){
        
        $this->job->setEventName('predict');
        $this->handle($model, true);
        
    }


    private function handle($model,$inmediate = false){
        if(in_array('MarlonFreire\MercadoLibre\App\Contracts\Synchronizable',class_implements($model)) && get_class($model) !== get_class($this->config)){
            if($this->config->isEnabled() || $inmediate){
                //Ejecuto el job Dispatcher de la pasarela
                $this->job->setItemToSynchronize($model);
                if($this->config->hasDelay()){
                    $this->dispatch($this->job)
                        ->onConnection('default')
                        ->onQueue('syncing_api')
                        ->delay(now()->addMinutes($this->config->getFrecuency()));
                }
                else
                    $this->dispatchNow($this->job);                
            }

        }
    }


}
