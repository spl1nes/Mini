<?php

// run with nohup watch -n 2 matchmaking.php &

include __DIR__ . '/../phpOMS/Autoloader.php';

use phpOMS\DataStorage\Database\Connection\SQLiteConnection;
use phpOMS\DataStorage\Database\DatabaseStatus;
use phpOMS\DataStorage\Database\Mapper\DataMapperFactory;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\Rest;
use phpOMS\Uri\HttpUri;

// Config
$RATE_LIMIT = 500;//100000; too many, request times out in between
$time = \time();
$date = (int) ($time / (60 * 60 * 24));
$email = '';
$password = '';
$max_id = 6834266;

class Driver
{
    public int $id = 0;
    public string $uid = '';
    public string $name = '';
    public int $last_name_check = 0;
}

class NullDriver extends Driver {}

class DriverMapper extends DataMapperFactory
{
    public const COLUMNS = [
        'driver_id'   => ['name' => 'driver_id',   'type' => 'int',    'internal' => 'id'],
        'driver_uid'  => ['name' => 'driver_uid',  'type' => 'string', 'internal' => 'uid'],
        'driver_name' => ['name' => 'driver_name', 'type' => 'string', 'internal' => 'name'],
        'driver_last_name_check' => ['name' => 'driver_last_name_check', 'type' => 'string', 'internal' => 'last_name_check'],
    ];

    public const TABLE = 'driver';
    public const PRIMARYFIELD = 'driver_id';
}

class NadeoMatch
{
    public int $id = 0;
    public int $nid = 0;
    public string $driver = '';
    public int $start = 0;
    public int $score = 0;
    public int $rank = 0;
    public int $points = 0;
    public int $elo_score = 0;
    public int $glicko1_score = 0;
    public int $glicko1_rd = 0;
    public int $glicko2_score = 0;
    public int $glicko2_rd = 0;
    public float $glicko2_vol = 0.0;
}

class NullNadeoMatch extends NadeoMatch {}

class NadeoMatchMapper extends DataMapperFactory
{
    public const COLUMNS = [
        'match_id'     => ['name' => 'match_id',     'type' => 'int',    'internal' => 'id'],
        'match_nid'    => ['name' => 'match_nid',    'type' => 'int',    'internal' => 'nid'],
        'match_driver' => ['name' => 'match_driver', 'type' => 'string', 'internal' => 'driver'],
        'match_start'  => ['name' => 'match_start',  'type' => 'int',    'internal' => 'start'],
        'match_score'  => ['name' => 'match_score',  'type' => 'int',    'internal' => 'score'],
        'match_rank'   => ['name' => 'match_rank',   'type' => 'int',    'internal' => 'rank'],
        'match_points'   => ['name' => 'match_points',   'type' => 'int',    'internal' => 'points'],
        'match_elo_score'   => ['name' => 'match_elo_score',   'type' => 'int',    'internal' => 'elo_score'],
        'match_glicko1_score'   => ['name' => 'match_glicko1_score',   'type' => 'int',    'internal' => 'glicko1_score'],
        'match_glicko1_rd'   => ['name' => 'match_glicko1_rd',   'type' => 'int',    'internal' => 'glicko1_rd'],
        'match_glicko2_score'   => ['name' => 'match_glicko2_score',   'type' => 'int',    'internal' => 'glicko2_score'],
        'match_glicko2_rd'   => ['name' => 'match_glicko2_rd',   'type' => 'int',    'internal' => 'glicko2_rd'],
        'match_glicko2_vol'   => ['name' => 'match_glicko2_vol',   'type' => 'float',    'internal' => 'glicko2_vol'],
    ];

    public const TABLE = 'match';
    public const PRIMARYFIELD = 'match_id';
}

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

// Service Authentication
$request = new HttpRequest(new HttpUri('https://public-ubiservices.ubi.com/v3/profiles/sessions'));
$request->header->set('Content-Type', 'application/json');
$request->header->set('Ubi-AppId', '86263886-327a-4328-ac69-527f0d20a237');
$request->header->set('Authorization', 'Basic ' . \base64_encode($email . ':' . $password));
$request->header->set('User-Agent', 'Solo Ranking / ' . $email);
$request->setMethod('POST');
$request->data['audience'] = 'NadeoServices';
$response = Rest::request($request);

$request = new HttpRequest(new HttpUri('https://prod.trackmania.core.nadeo.online/v2/authentication/token/ubiservices'));
$request->header->set('Content-Type', 'application/json');
$request->header->set('Authorization', 'ubi_v1 t=' . \trim($response->data['ticket'] ?? ''));
$request->header->set('User-Agent', 'Solo Ranking / ' . $email);
$request->setMethod('POST');
$request->data['audience'] = 'NadeoServices';
$authResponse2 = Rest::request($request);

if ($authResponse2->header->status !== 200) {
    \sleep(60);

    exit;
}

// DB connection
$db = new SQLiteConnection([
    'db' => 'sqlite',
    'database' => __DIR__ . '/soloranking.sqlite',
]);

$db->connect();

if ($db->getStatus() !== DatabaseStatus::OK) {
    exit;
}

DataMapperFactory::db($db);

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
    $eloToCheck = [];

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

            $eloToCheck[] = (string) $uid;

            DriverMapper::create()->execute($driver);
        }

        $drivers[$rank] = $driver;
        $match[$rank] = [
            'driver' => clone $driver,
            'points' => (int) $score,
            'rank' => (int) $rank,
            'start' => ((int) ($matchResponse->data['startDate'] ?? 0)) / (60 * 60 * 24),
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
