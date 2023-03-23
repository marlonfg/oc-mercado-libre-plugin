<?php
namespace App\Contracts;

interface Mediable{

    public function updateUrl($id, $extension);
    
    public function store($type, $file, $hash_id, $user_sizes, $ignore);

    public function getHasher();
}