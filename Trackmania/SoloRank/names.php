<?php

include __DIR__ . '/../phpOMS/Autoloader.php';
include __DIR__ . '/db.php';
include __DIR__ . '/config.php';

use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\Rest;
use phpOMS\Uri\HttpUri;

function authenticate($email, $identifier, $token)
{
    $request = new HttpRequest(new HttpUri('https://api.trackmania.com/api/access_token'));
    $request->header->set('Content-Type', 'application/json');
    $request->header->set('User-Agent', 'Map pack ranking / ' . $email);
    $request->setMethod('POST');
    $request->data['grant_type'] = 'client_credentials';
    $request->data['client_id'] = $identifier;
    $request->data['client_secret'] = $token;

    return Rest::request($request);
}

$authResponse = authenticate($email, $identifier, $token);

$MAX_DRIVER_PER_MAP = 50;

$time = \time();

$maps = MapMapper::getAll()->execute();
$lastId = 0;
$drivers = [];

while (true) {
    $drivers = DriverMapper::getAll()
        ->where('id', $lastId, '>')
        ->where('last_name_check', $time - 60 * 60 * 24 * 6, '<')
        ->sort('id', 'ASC')
        ->limit($MAX_DRIVER_PER_MAP)
        ->execute();

    if (empty($drivers) || \reset($drivers)->id === 0) {
        break;
    }

    $namesToCheck = [];

    foreach ($drivers as $driver) {
        $namesToCheck[] = $driver->uid;
    }

    // Check names
    if (!empty($namesToCheck)) {
        $nameRequest = new HttpRequest(new HttpUri('https://api.trackmania.com/api/display-names?accountId[]=' . \implode('&accountId[]=', $namesToCheck), '&'));
        $nameRequest->header->set('Content-Type', 'application/json');
        $nameRequest->header->set('Authorization', 'Bearer ' . \trim($authResponse->data['access_token'] ?? ''));
        $nameRequest->header->set('User-Agent', 'Map pack ranking / ' . $email);
        $nameRequest->setMethod('GET');
        $nameResponse = Rest::request($nameRequest);

        if ($nameResponse->header->status !== 200) {
            echo "Invalid name response for " . $map->uid . "\n";

            $authResponse = authenticate($email, $identifier, $token);

            $nameRequest = new HttpRequest(new HttpUri('https://api.trackmania.com/api/display-names?accountId[]=' . \implode('&accountId[]=', $namesToCheck), '&'));
            $nameRequest->header->set('Content-Type', 'application/json');
            $nameRequest->header->set('Authorization', 'Bearer ' . \trim($authResponse->data['access_token'] ?? ''));
            $nameRequest->header->set('User-Agent', 'Map pack ranking / ' . $email);
            $nameRequest->setMethod('GET');
            $nameResponse = Rest::request($nameRequest);

            if ($nameResponse->header->status !== 200) {
                \sleep(1);

                continue;
            }
        }

        foreach ($nameResponse->data as $nid => $account) {
            foreach ($drivers as $id => $driver) {
                if ($driver->uid === $nid) {
                    $drivers[$id]->name = $account ?? $drivers[$id]->name;
                    $drivers[$id]->last_name_check = $time;

                    DriverMapper::update()->execute($drivers[$id]);

                    break;
                }
            }
        }
    }
}
