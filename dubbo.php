<?php
require_once "./vendor/autoload.php";

use DubboPhp\Client\Client;
use DubboPhp\Client\Type;

$options = [
    'registry_address' => '172.31.214.198:2181',
    'protocol'         => 'dubbo',
    'version'          => null
];

try {
    $client  = new Client($options);
    $service = $client->getService("com.tuner.basecenter.service.newservice.CenterUserService");

    $result = $service->getUserIdByToken(Type::string("263ba24e3b2437bc13249a1928c576721619429458938"));
    var_dump($result);
    die();
} catch (Throwable $e) {
    print($e->getMessage());
}