<?php

use App\Database\Migration;

require_once __DIR__ . '/vendor/autoload.php';

$migration = new Migration();
$migration->run();