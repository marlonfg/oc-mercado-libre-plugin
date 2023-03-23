<?php
/**
 * Created by PhpStorm.
 * User: penna
 * Date: 9/25/18
 * Time: 12:49 PM
 */

namespace MarlonFreire\MercadoLibre\App\Contracts;


/**
 * Interface Selleable
 * @package App\Contractss
 */
interface Selleable
{
    public function getTitle();

    public function getTableAttribute();

    public function getFinalPriceAttribute();

    public function getFinalCurrencyAttribute();

}