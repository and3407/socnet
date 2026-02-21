<?php

use App\Database\migrations\upload_friends\UploadFriends;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

$uploadFriends = new UploadFriends();
$uploadFriends->execute();