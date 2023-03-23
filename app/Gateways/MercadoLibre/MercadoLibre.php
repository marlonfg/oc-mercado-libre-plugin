<?php

namespace MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre;


use MarlonFreire\MercadoLibre\App\Contracts\Gateway;
use MarlonFreire\MercadoLibre\App\Contracts\GatewayResponse;
use Exception;

class MercadoLibre extends MercadoBase implements Gateway, GatewayResponse
{
    use MeliResponse;

    /**
     * @version 1.1.0
     */
    const VERSION = "1.1.0";
    /**
     * @var $API_ROOT_URL is a main URL to access the Meli API's.
     * @var $AUTH_URL is a url to redirect the user for login.
     */
    protected static $API_ROOT_URL = "https://api.mercadolibre.com";
    protected static $OAUTH_URL = "/oauth/token";
    protected static $USER_URL = 'users/me';

    public static $AUTH_URL = array(
        "MLA" => "https://auth.mercadolibre.com.ar", // Argentina
        "MLB" => "https://auth.mercadolivre.com.br", // Brasil
        "MCO" => "https://auth.mercadolibre.com.co", // Colombia
        "MCR" => "https://auth.mercadolibre.com.cr", // Costa Rica
        "MEC" => "https://auth.mercadolibre.com.ec", // Ecuador
        "MLC" => "https://auth.mercadolibre.cl", // Chile
        "MLM" => "https://auth.mercadolibre.com.mx", // Mexico
        "MLU" => "https://auth.mercadolibre.com.uy", // Uruguay
        "MLV" => "https://auth.mercadolibre.com.ve", // Venezuela
        "MPA" => "https://auth.mercadolibre.com.pa", // Panama
        "MPE" => "https://auth.mercadolibre.com.pe", // Peru
        "MPT" => "https://auth.mercadolibre.com.pt", // Prtugal
        "MRD" => "https://auth.mercadolibre.com.do"  // Dominicana
    );
    /**
     * Configuration for CURL
     */
    public static $CURL_OPTS = array(
        CURLOPT_USERAGENT => "MELI-PHP-SDK-1.1.0",
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 60
    );

    protected $attempts = 0;

    /**
     * Constructor method. Set all variables to connect in Meli
     *
     * @param string $client_id
     * @param string $client_secret
     * @param string $access_token
     * @param string $refresh_token
     */
    public function __construct()
    {
        //Ejecuto la simulacion del constructor con el metodo estatico para obtener las credenciales 
        parent::__construct();
        $this->success_codes = $this->getConfigKey('response_codes.success');
        $this->error_codes = $this->getConfigKey('response_codes.error');
        $this->getCredentials();
    }

    public function getUserData()
    {
        if(!parent::getUserData())
            $this->setUserData($this->connect()->get($this->getConfigKey('routes.user_data') ?? self::$USER_URL)->getResponseResult());
        
        return $this->user_data; 
    }

    public function getTestUser()
    {
        $opts = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'site_id' => $this->getConfigKey('site_id')
            ])
        );        

        $this->connect()->get($this->getConfigKey('routes.test_user'), null, $opts);

        if ($this->isSuccesfullResponse()) 
            return $this->getResponseResult();
        
        throw new Exception('Error retrieving Test User: ' . $this->getResponseResult()->message);
    }

    public function connect()
    {
        if ($this->isConnectionExpired()) {
            if($this->getRefreshToken())
                $this->refreshAccessToken();
            else{
                    $body = array("grant_type" => "client_credentials", "client_id" => $this->client_id, "client_secret" => $this->client_secret);
                
                $opts = array(
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $body
                );
                $this->execute(self::$OAUTH_URL, $opts)->setTokens();
            }            
        }
        
        return $this;
    }

    /**
     * Return an string with a complete Meli login url.
     * NOTE: You can modify the $AUTH_URL to change the language of login
     *
     * @param string $redirect_uri
     * @return string
     */
    public function getAuthUrl($redirect_uri, $auth_url)
    {
        $this->redirect_uri = $redirect_uri;
        $params = array("client_id" => $this->client_id, "response_type" => "code", "redirect_uri" => $redirect_uri);
        $auth_uri = $auth_url . "/authorization?" . http_build_query($params);
        return $auth_uri;
    }

    /**
     * Executes a POST Request to authorize the application and take
     * an AccessToken.
     *
     * @param string $code
     * @param string $redirect_uri
     *
     */
    public function authorize($code, $redirect_uri)
    {
        if ($redirect_uri)
            $this->redirect_uri = $redirect_uri;
        $body = array(
            "grant_type" => "authorization_code",
            "client_id" => $this->client_id,
            "client_secret" => $this->client_secret,
            "code" => $code,
            "redirect_uri" => $this->redirect_uri
        );
        $opts = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body
        );

        $this->execute(self::$OAUTH_URL, $opts);
        if ($this->response["httpCode"] == 200) {
            $this->access_token = $this->response["body"]->access_token;
            if (isset($this->response["body"]->refresh_token))
                $this->refresh_token = $this->response["body"]->refresh_token;
            return $this->response;
        } else {
            return $this->response;
        }
    }

    /**
     * Execute a POST Request to create a new AccessToken from a existent refresh_token
     *
     * @return string|mixed
     */
    public function refreshAccessToken()
    {
        if ($this->getRefreshToken()) {
            $body = array(
                "grant_type" => "refresh_token",
                "client_id" => $this->client_id,
                "client_secret" => $this->client_secret,
                "refresh_token" => $this->refresh_token
            );
            $opts = array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body
            );

            $this->execute(self::$OAUTH_URL, $opts)->setTokens();
        } else {
           throw new Exception('Error en conexión, revise sus credenciales de MercadoLibre e intente nuevamente la conexión');
        }
    }

    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param boolean $assoc
     * @return mixed
     */
    public function get($path, $params = null, $opts = null, $assoc = false)
    {
        $params['access_token'] = $this->getAccessToken();
        return $this->execute($path, $opts, $params, $assoc);
    }

    /**
     * Execute a POST Request
     *
     * @param string $body
     * @param array $params
     * @return mixed
     */
    public function post($path, $body = null, $params = array())
    {
        $params['access_token'] = $this->getAccessToken();
        $body = json_encode($body);
        $opts = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body
        );

        return $this->execute($path, $opts, $params);
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @return mixed
     */
    public function put($path, $body = null, $params = array())
    {
        $params['access_token'] = $this->getAccessToken();
        $body = json_encode($body);
        $opts = array(
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $body
        );

        return $this->execute($path, $opts, $params);
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function delete($path, $params)
    {
        $params['access_token'] = $this->getAccessToken();
        $opts = array(
            CURLOPT_CUSTOMREQUEST => "DELETE"
        );

        return $this->execute($path, $opts, $params);
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public function options($path, $params = null)
    {
        $opts = array(
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        );

        return $this->execute($path, $opts, $params);
    }

    /**
     * Execute all requests and returns the json body and headers
     *
     * @param string $path
     * @param array $opts
     * @param array $params
     * @param boolean $assoc
     * @return mixed
     */
    public function execute($path, $opts = array(), $params = array(), $assoc = false)
    {
        $uri = $this->make_path($path, $params);

        //Loggeo la url del endpoint

        // Log::info('uri',[$uri]);

        $ch = curl_init($uri);
        curl_setopt_array($ch, self::$CURL_OPTS);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if(isset($this->access_token)){
            $acces_token = 'Authorization: Bearer '.$this->access_token;

            if (empty($opts)){
                $opts = array(
                    CURLOPT_HTTPHEADER => array($acces_token),
                );
            }else{
                if(isset($opts[CURLOPT_HTTPHEADER]))
                    array_push($opts[CURLOPT_HTTPHEADER], $acces_token);
                else
                    $opts[CURLOPT_HTTPHEADER] = array($acces_token);
            }
        }
        if (!empty($opts))
            curl_setopt_array($ch, $opts);

        $response["body"] = json_decode(curl_exec($ch), $assoc);
        $response["httpCode"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->response = $response;
        //        Log::info('response',[json_encode($response)]);

//        if ($this->isErrorResponse()){
//           throw new Exception($this->getResponseResult()->message);
//
//        }

        return $this;
    }

    /**
     * Check and construct an real URL to make request
     *
     * @param string $path
     * @param array $params
     * @return string
     */
    public function make_path($path, $params = array())
    {
        if (!preg_match("/^http/", $path)) {
            if (!preg_match("/^\//", $path)) {
                $path = '/' . $path;
            }
            $uri = self::$API_ROOT_URL . $path;
        } else {
            $uri = $path;
        }
        if (!empty($params)) {
            $paramsJoined = array();
            foreach ($params as $param => $value) {
                $paramsJoined[] = "$param=$value";
            }
            $params = '?' . implode('&', $paramsJoined);
            $uri = $uri . $params;
        }
        return $uri;
    }

    private function setTokens()
    {
        if($this->response["body"]){
            $this->setAccessToken($this->response["body"]->access_token);
            if(isset($this->response["body"]->refresh_token)){
                $this->setRefreshToken($this->response["body"]->refresh_token);
            }
            $this->setExpirationTime(now()->addSeconds($this->response['body']->expires_in)) ;
        }

    }

    public function isConnected()
    {
        return $this->getUserData() !== null;
    }
}
