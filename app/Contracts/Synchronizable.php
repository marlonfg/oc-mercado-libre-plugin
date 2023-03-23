<?php
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 7/29/2019
 * Time: 3:40 PM
 */

namespace MarlonFreire\MercadoLibre\App\Contracts;


interface Synchronizable
{
    public function getFrecuency();

    public function hasDelay();

    public function isEnabled();
}