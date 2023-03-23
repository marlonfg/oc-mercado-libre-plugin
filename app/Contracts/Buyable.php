<?php
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 7/25/2019
 * Time: 1:45 PM
 */

namespace App\Contracts;


interface Buyable
{
    public function getUser();

    public function getItems();
}