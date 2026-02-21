<?php

use App\Database\migrations\upload_posts\PostCreator;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

$creator = new PostCreator();
$creator->execute();