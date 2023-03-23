<?php

namespace MarlonFreire\MercadoLibre\App\Utils;

use MarlonFreire\MercadoLibre\App\Contracts\APITranslator;
use Illuminate\Support\Facades\Log;

class RapidAPITranslator implements APITranslator
{

    protected $url;

    protected $key;

    public function __construct($key)
    {
        $this->url = "https://google-translate1.p.rapidapi.com/language/translate/v2";
        $this->key = $key;
    }

    public function setAPIURL($url): void
    {
        $this->url = $url;
    }

    public function setAPIKey($key): void
    {
        $this->key = $key;
    }

    public function getAPIURL($url): string
    {
        return $this->url;
    }

    public function getAPIKey($key): string
    {
        return $this->key;
    }

    public function translate($needle, $lang = 'es', $source = 'en'): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query([
                'source' => $source,
                'q' => $needle,
                'target' => $lang
            ]),
            CURLOPT_HTTPHEADER => array(
                "accept-encoding: application/gzip",
                "content-type: application/x-www-form-urlencoded",
                "x-rapidapi-host: google-translate1.p.rapidapi.com",
                "x-rapidapi-key: $this->key"
            ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            Log::info('Error in comnunications with rapidApi', (array) $error);
            return '';
        } else {
                $response = json_decode($response);
                if(isset($response->data)){
                    return $response->data->translations[0]->translatedText;
                }else
                    return '';
        }
    }
}
