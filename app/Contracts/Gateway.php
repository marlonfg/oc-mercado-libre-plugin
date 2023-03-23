<?php

/**
 * Created by PhpStorm.
 * User: jose
 * Date: 7/16/2019
 * Time: 12:36 PM
 */

namespace MarlonFreire\MercadoLibre\App\Contracts;

interface Gateway
{
    public function connect();

    public function isConnected();
    
    public function getCredentials();

    public function getAccessToken();

    public function setAccessToken($token);

    public function setExpirationTime($lifetime);

    public function getExpirationTime();

    public function getRefreshToken();

    public function isConnectionExpired();
   
    public function setRefreshToken($token);

    public function clearTokens();

    public function getUserData();

    public function setUserData($data);

    public function getTestMode();

    public function setTestMode($mode);

    public function getConfigKey($path);

    public function getConfigModel();
}