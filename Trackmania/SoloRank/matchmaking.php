<?php

// run with nohup watch -n 2 matchmaking.php &

include __DIR__ . '/../phpOMS/Autoloader.php';
include __DIR__ . '/db.php';
include __DIR__ . '/config.php';

use phpOMS\DataStorage\Database\Connection\SQLiteConnection;
use phpOMS\DataStorage\Database\DatabaseStatus;
use phpOMS\DataStorage\Database\Mapper\DataMapperFactory;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\Rest;
use phpOMS\Uri\HttpUri;

// Config
$RATE_LIMIT = 500;//100000; too many, request times out in between
$time = \time();
$max_id = 6834266;

// Load match id
if (!\is_file(__DIR__ . '/match_id.txt')) {
    \file_put_contents(__DIR__ . '/match_id.txt', '6150000');
}

$match_id = (int) \file_get_contents(__DIR__ . '/match_id.txt');

// Authenticate
$request = new HttpRequest(new HttpUri('https://public-ubiservices.ubi.com/v3/profiles/sessions'));
$request->header->set('Content-Type', 'application/json');
$request->header->set('Ubi-AppId', '86263886-327a-4328-ac69-527f0d20a237');
$request->header->set('Authorization', 'Basic ' . \base64_encode($email . ':' . $password));
$request->header->set('User-Agent', 'Solo Ranking / ' . $email);
$request->setMethod('POST');
$request->data['audience'] = 'NadeoClubServices';
$response = Rest::request($request);

$request = new HttpRequest(new HttpUri('https://prod.trackmania.core.nadeo.online/v2/authentication/token/ubiservices'));
$request->header->set('Content-Type', 'application/json');
$request->header->set('Authorization', 'ubi_v1 t=' . \trim($response->data['ticket'] ?? ''));
$request->header->set('User-Agent', 'Solo Ranking / ' . $email);
$request->setMethod('POST');
$request->data['audience'] = 'NadeoClubServices';
$authResponse = Rest::request($request);

if ($authResponse->header->status !== 200) {
    echo "Invalid authentication response.\n";

    \sleep(60);

    exit;
}

$match_id_new = $match_id;

$errorCounter = 0;

$lastMatch = NadeoMatchMapper::get()->sort('match_nid', 'DESC')->limit(1)->execute();
if ($lastMatch->id !== 0) {
    $match_id_new = \max($match_id_new, $lastMatch->nid);
    \file_put_contents(__DIR__ . '/match_id.txt', $match_id_new);
}

for ($i = 0; $i < $RATE_LIMIT; ++$i) {
    ++$match_id_new;

    if ($match_id_new > $max_id) {
        exit;
    }

    // Load Match
    $matchRequest = new HttpRequest(new HttpUri('https://meet.trackmania.nadeo.club/api/matches/' . $match_id_new));
    $matchRequest->header->set('Content-Type', 'application/json');
    $matchRequest->header->set('Authorization', 'nadeo_v1 t=' . \trim($authResponse->data['accessToken'] ?? ''));
    $matchRequest->header->set('User-Agent', 'Solo Ranking / ' . $email);
    $matchRequest->data['audience'] = 'NadeoClubServices';
    $matchRequest->setMethod('GET');
    $matchResponse = Rest::request($matchRequest);

    // Invalid response (i.e. match doesn't exist)
    if ($matchResponse->header->status === 404) {
        ++$errorCounter;

        sleep(1);

        if ($errorCounter > 100) {
            $match_id_new -= $errorCounter;

            echo "No match found\n";

            break;
        } else {
            continue;
        }
    }

    // Not a matchmaking match
    if (($matchResponse->data['name'] ?? '') !== 'Official 3v3 - match') {
        ++$RATE_LIMIT;

        continue;
    }

    // Ongoing match
    if (($matchResponse->data['status'] ?? '') !== 'COMPLETED') {
        ++$errorCounter;

        sleep (1);

        if ($errorCounter > 100) {
            $match_id_new -= $errorCounter;

            echo "No completed match found\n";

            break;
        } else {
            continue;
        }
    }

    $errorCounter = 0;

    // Load Participants
    $participantsRequest = new HttpRequest(new HttpUri('https://meet.trackmania.nadeo.club/api/matches/' . $match_id_new . '/participants'));
    $participantsRequest->header->set('Content-Type', 'application/json');
    $participantsRequest->header->set('Authorization', 'nadeo_v1 t=' . \trim($authResponse->data['accessToken'] ?? ''));
    $participantsRequest->header->set('User-Agent', 'Solo Ranking / ' . $email);
    $participantsRequest->data['audience'] = 'NadeoClubServices';
    $participantsRequest->setMethod('GET');
    $participantsResponse = Rest::request($participantsRequest);

    $drivers = [];
    $match = [];

    foreach ($participantsResponse->data as $participant) {
        if (\is_string($participant)) {
            echo "No participant found\n";

            break 2;
        }

        $uid = $participant['participant'];
        $rank = $participant['rank'];
        $points = $participant['score'];

        $driver = DriverMapper::get()->where('uid', (string) $uid)->execute();

        // Driver doesn't exist in db, create it
        if ($driver->uid === '') {
            $driver = new Driver();
            $driver->uid = (string) $uid;

            DriverMapper::create()->execute($driver);
        }

        $drivers[$rank] = $driver;
        $match[$rank] = [
            'driver' => clone $driver,
            'points' => (int) $score,
            'rank' => (int) $rank,
            'start' => (int) ($matchResponse->data['startDate'] ?? 0),
        ];
    }

    // Not enough drivers
    if (\count($drivers) < 2) {
        continue;
    }

    foreach ($match as $mData) {
        $m = new NadeoMatch();
        $m->nid = $match_id_new;
        $m->driver = $mData['driver']->uid;
        $m->start = (int) $mData['start'];
        $m->points = $mData['points'];
        $m->rank = $mData['rank'];

        NadeoMatchMapper::create()->execute($m);
    }

    \usleep(100000);
}

$lastMatch = NadeoMatchMapper::get()->sort('match_nid', 'DESC')->limit(1)->execute();
if ($lastMatch->id !== 0) {
    $match_id_new = \max($match_id_new, $lastMatch->nid);
}

\file_put_contents(__DIR__ . '/match_id.txt', $match_id_new);

$db->close();
