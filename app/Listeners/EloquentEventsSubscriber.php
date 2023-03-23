<?php

namespace MarlonFreire\MercadoLibre\App\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class EloquentEventsSubscriber implements ShouldQueue
{
    use Queueable;
    /**
     * Handle created events.
     */
    public function onModelCreated($event_name,$model)
    {
        $model = current($model);
        $class = get_class($model);
        if($class !== 'App\\Models\\Portada'){
            if(in_array('pos',$model->getFillable()))
                $model->increment('pos',$class::max('pos')+200);
        }
        else
            $model->increment('pos',$class::max('pos') + 1);

    }

     /**
     * Handle deleting events.
     */
    public function onModelDeleting($event_name,$model)
    {
        $model = current($model);
        foreach ($model->getRelations() as $relation){
            list($name,$action) = $relation;
            $model->{$name}()->{$action}();
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'eloquent.created: *',
            'MarlonFreire\MercadoLibre\App\Listeners\EloquentEventsSubscriber@onModelCreated'
        );

        $events->listen(
            'eloquent.deleting: *',
            'MarlonFreire\MercadoLibre\App\Listeners\EloquentEventsSubscriber@onModelDeleting'
        );
    }
}
