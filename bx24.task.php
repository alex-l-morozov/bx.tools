<?php
// Адрес вебхука.
$queryUrl = 'https://___/rest/16/83hn3e5qx7wh147o/tasks.task.add.json';
// Массив который передается
$queryData = http_build_query(array(
    "RESPONSIBLE_ID" => "1", // Ответственный
    "TITLE" => "Название задачи",
    "DESCRIPTION" => "Описание задачи",
));

// отправка производится через curl
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_POST => 1,
    CURLOPT_HEADER => 0,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $queryUrl,
    CURLOPT_POSTFIELDS => $queryData,
));

$result = curl_exec($curl);
var_dump($result);
curl_close($curl);

$result = json_decode($result, 1);

echo "<pre>" . print_r($result, true) ."</pre>";