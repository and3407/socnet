<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Database\Db;
try {
    $pdoWrite = Db::getInstance(Db::QUERY_TYPE_WRITE);
    echo 'Write connection OK\n';
    $pdoWrite->exec("INSERT INTO users (uuid, first_name, second_name, password, birthdate, city, biography) VALUES ('test-uuid', 'John', 'Doe', 'pass', '2000-01-01', 'City', 'Bio')");
    echo 'Insert OK\n';
    $pdoRead = Db::getInstance(Db::QUERY_TYPE_READ);
    echo 'Read connection OK\n';
    $stmt = $pdoRead->query('SELECT COUNT(*) FROM users');
    $count = $stmt->fetchColumn();
    echo 'Count: ' . $count . '\n';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . '\n';
}
