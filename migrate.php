<?php

use App\Database\Migration;
use App\Domain\Dialog\DialogMigration;

require_once __DIR__ . '/vendor/autoload.php';

$migration = new Migration();
$migration->run();

$dialogMigration = new DialogMigration();
$dialogMigration->run();