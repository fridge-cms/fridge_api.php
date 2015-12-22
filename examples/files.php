<?php

require dirname(__DIR__).'/vendor/autoload.php';

$client = new \FridgeApi\Client("", "");

// retrieve an existing file
$path = 'Koen-AtorSledding.jpg';
$file = $client->file($path);
// echo $file;

// upload a file
$test = fopen('/path/to/file', 'r');
$result = $client->upload($test);
// print_r($result);
