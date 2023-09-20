<?php

include_once __DIR__ . '/../phpOMS/Autoloader.php';

use phpOMS\DataStorage\Database\Mapper\DataMapperFactory;
use phpOMS\DataStorage\Database\Connection\SQLiteConnection;
use phpOMS\DataStorage\Database\DatabaseStatus;


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
