<?php

namespace MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre;
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 7/25/2019
 * Time: 2:07 PM
 */
trait MeliTools
{
    protected $currencies = [
        858 => 'UYU',
        840 => 'USD'
    ];

    public function getCurrencySymbol($id)
    {
        return $this->currencies[$id];
    }
    
    public function getCurrencyCode($symbol)
    {
        return array_flip($this->currencies)[$symbol];
    }

    public function getStatusValue($value, $stock = null){
        if($stock === 0)
            return 'paused';

        return $value ? 'active' : 'paused';
    }

    public function getActiveValue($value){
        return $value == 'active';
    }
}