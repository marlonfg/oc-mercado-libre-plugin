<?php

/**
 * Created by PhpStorm.
 * User: jose
 * Date: 7/29/2019
 * Time: 4:06 PM
 */

namespace MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre;


use MarlonFreire\MercadoLibre\App\Contracts\Gateway;
use Exception;
use Illuminate\Support\Facades\Cache;

class MercadoBase implements Gateway
{
    protected $config;
    protected $client_id;
    protected $client_secret;
    protected $access_token;
    protected $refresh_token;
    protected $expires_at;
    protected $test_mode = true;
    protected $cache_key;
    protected $lifetime;
    protected $user_data;

    /**
     * MercadoBase constructor.
     */
    public function __construct($key = null)
    {
        $this->config = config('meli');
        $this->cache_key = $key ?? $this->getConfigKey('cache.key');
        $this->lifetime = now()->addMinutes($this->getConfigKey('cache.lifetime'));
        $this->expires_at = now();
    }

    public function connect()
    {
        return null;
    }

    public function isConnected()
    {
        return $this->getAccessToken() !== null;
    }

    public function getCredentials()
    {
        if (!$this->getConfigKey('use_env')) {
            $config = $this->getConfigModel();
            $this->client_id = $config->getAppID();
            $this->client_secret = $config->getAppSecret();
        } else {
            $this->client_id = env('MERCADOLIBRE_APP_ID');
            $this->client_secret = env('MERCADOLIBRE_SECRET_KEY');
        }
    }

    public function getTestMode()
    {
        if (!$this->getConfigKey('use_env')) {
            return $this->test_mode = $this->getConfigModel()->meli_test_mode;
        } else
            return $this->test_mode = env('MERCADOLIBRE_MODE_TEST');
    }

    public function getConfigModel()
    {
        $config_model = $this->getConfigKey('config_model');
        if (!class_exists($config_model))
            throw new Exception('El modelo de configuraciones de mercadolibre no existe');

        return $config_model::firstOrFail();
    }

    public function getConfigKey($path)
    {
        $value = array_get($this->config, $path);
        if (!is_null($value))
            return array_get($this->config, $path);

        throw new Exception("La llave '$value' no existe en el listado de configuraciones de wo2.meli.php");
    }

    public function getUserData()
    {
        return $this->user_data = Cache::tags([$this->cache_key])->get("user_data");;
    } 
    
    public function setUserData($data)
    {
        $this->user_data = $data;
        Cache::tags([$this->cache_key])->put("user_data", $data, $this->lifetime);
    }

    public function setAccessToken($token)
    {
        $this->access_token = $token;
        Cache::tags([$this->cache_key])->put("access_token", $token, $this->lifetime);
    }

    public function setRefreshToken($token)
    {
        $this->refresh_token = $token;
        Cache::tags([$this->cache_key])->put("refresh_token", $token, $this->lifetime);
    }

    public function setExpirationTime($timestamp)
    {
        $this->expires_at = $timestamp;
        Cache::tags([$this->cache_key])->put("expires_at", $timestamp, $this->lifetime);
    }

    public function getExpirationTime()
    {
        return $this->expires_at = Cache::tags([$this->cache_key])->get("expires_at");
    }

    public function getAccessToken()
    {
        return $this->access_token = Cache::tags([$this->cache_key])->get("access_token");
    }

    public function getRefreshToken()
    {
        return $this->refresh_token = Cache::tags([$this->cache_key])->get("refresh_token");
    }

    public function isConnectionExpired()
    {
        return now()->diffInSeconds($this->getExpirationTime()) <= 10;
    }

    public function clearTokens()
    {
        Cache::tags([$this->cache_key])->forget("access_token");
        Cache::tags([$this->cache_key])->forget("refresh_token");
        Cache::tags([$this->cache_key])->forget("expires_at");
        Cache::tags([$this->cache_key])->forget("user_data");
    }

    public function setTestMode($mode)
    {
        $this->test_mode = $mode;
    }

    public function getClientID()
    {
        return $this->client_id;
    }

    public function getClientSecret()
    {
        return $this->client_secret;
    }
}
