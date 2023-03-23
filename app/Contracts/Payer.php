<?php
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 7/25/2019
 * Time: 2:30 PM
 */

namespace App\Contracts;


interface Payer
{
    public function getName();

    public function getSurname();

    public function getAddress();

    public function getShipmentAddress();

    public function getPhone();

    public function getIdentification();

    public function getEmail();

    public function getZipCode();

    public function getShipmentZipCode();
}