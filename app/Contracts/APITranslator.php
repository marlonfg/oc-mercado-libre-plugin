<?php

namespace MarlonFreire\MercadoLibre\App\Contracts;

interface APITranslator{
    
    public function setAPIURL($url): void;

    public function setAPIKey($key): void;
    
    public function getAPIURL($url): string;

    public function getAPIKey($key): string;

    public function translate($needle, $lang, $source): string;
}