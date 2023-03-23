<?php
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 8/21/2019
 * Time: 2:38 PM
 */

namespace MarlonFreire\MercadoLibre\App\Contracts;


interface SyncJob
{
    public function setItemToSynchronize($item);

    public function getItemToSynchronize();

    public function getGateway();

    public function setJobAction($action);

    public function setEventName($name);

    public function handleResponse($response);
}