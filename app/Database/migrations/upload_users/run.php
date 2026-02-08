<?php

use App\Domain\User\Repositories\UserRepository;
use App\Utils\Uuid;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

function processBatch(array $batch, UserRepository $userRepository)
{
    try {


    $values = [];
    foreach ($batch as $num => $row) {
        $nameData = explode(' ', $row[0]);

        $uuid = Uuid::createUuid();
        $first_name = $nameData[1];
        $second_name = $nameData[0];
        //password_hash('P@asword123', PASSWORD_DEFAULT)
        $password = '$2y$12$EejjQQex4oY6P6.NXRhj6uK8fglQksJQr1DKoc3ICL1IaQf2FmaWy';
        $birthdate = $row[1];
        $city = empty($row[2]) ? '' : $row[2];

        $value = sprintf(
            "('%s', '%s', '%s', '%s', '%s', '%s')",
            $uuid,
            $first_name,
            $second_name,
            $password,
            $birthdate,
            $city
        );

//        echo $num . ' ' .$value . PHP_EOL;

        $values[] = $value;
    }

    $userRepository->batchInsert($values);

    } catch (Throwable $e) {
        
    }

}

$batchCount = 0;
$totalBatchCount = 0;

if (($handle = fopen("people.v2.csv", "r")) !== FALSE) {
    $batch = [];
    $batchSize = 100;

    $userRepository = new UserRepository();

    while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== FALSE) {
        $batch[] = $data;
        $batchCount++;
//        echo $batchCount . PHP_EOL;

        if (count($batch) >= $batchSize) {
            processBatch($batch, $userRepository);;
            $batchCount = 0;
            $totalBatchCount++;
            echo $totalBatchCount . PHP_EOL;
            $batch = [];
        }
    }

    // Обработка оставшихся записей (после завершения цикла)
    if (!empty($batch)) {
        processBatch($batch, $userRepository);
    }

    fclose($handle);
}

echo 'End' . PHP_EOL;