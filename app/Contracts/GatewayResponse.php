<?php
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 10/8/19
 * Time: 4:58 PM
 */

namespace MarlonFreire\MercadoLibre\App\Contracts;


interface GatewayResponse
{
    public function getResponseResult();

    public function getResponseStatusCode();

    public function getFullResponse();

    public function isSuccesfullResponse();

    public function isErrorResponse();
}