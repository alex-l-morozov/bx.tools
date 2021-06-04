<?php
// Адрес вебхука. Надо будет создать пользователя системного, под ним создать свой вебхук и потом добавить в чат.
$queryUrl = 'https://____/rest/16/83hn3e5qx7wh147o/im.message.add.json';
// Массив который передается
$queryData = http_build_query(array(
    "DIALOG_ID" => "chat18", // чат в который будет отправлятся сообщение
    "MESSAGE" => "отправка из скрипта сработала.", // сообщение
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