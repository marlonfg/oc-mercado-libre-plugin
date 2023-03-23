<?php

namespace App\Contracts;

/**
 * Created by PhpStorm.
 * User: jose
 * Date: 7/16/2019
 * Time: 12:32 PM
 */
interface Payment
{
    public function buy($purchase);

    public function getCardData();

    public function getPaymentMethods();

    public function prepareData();

    public function getPurchase();

    public function getPreference();
}