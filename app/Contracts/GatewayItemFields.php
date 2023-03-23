<?php
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 8/14/2019
 * Time: 2:18 PM
 */

namespace MarlonFreire\MercadoLibre\App\Contracts;


interface GatewayItemFields
{
    public static function gatewayID();

    public static function getFieldsToFill();

    public static function getFieldsToSync();

    public static function getStatusField();

    public function isPublished();
}