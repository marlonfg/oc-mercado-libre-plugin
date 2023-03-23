<?php

namespace MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre;

use Exception;

trait MeliResponse
{

    protected $response;
    protected $success_codes = [200, 201, 402];
    protected $error_codes = [404, 400, 401, 403];

    public function getFullResponse()
    {
        return $this->response;
    }

    public function getResponseResult()
    {
        return @$this->response['body'];
    }

    public function getResponseStatusCode()
    {
        return @$this->response['httpCode'] ?? @$this->response['code'];
    }

    public function isSuccesfullResponse()
    {
        $this->validateResponse();

        return in_array($this->getResponseStatusCode(), $this->success_codes);
    }

    public function isErrorResponse()
    {
        $this->validateResponse();

        return in_array($this->getResponseStatusCode(), $this->error_codes);
    }

    private function validateResponse()
    {
        if (!$this->response)
            throw new Exception('El objeto Response no existe.');
    }
}
